<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Mission;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MissionController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // CLIENT — Create a mission
    // ══════════════════════════════════════════════════════════════

    public function store(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $client = Client::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'service'        => 'required|string|max:100',
            'address'        => 'required|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'description'    => 'required|string|min:20|max:2000',
            'availabilities' => 'nullable|array',
            'location_type'  => ['required', Rule::in(['residential', 'business'])],
        ]);

        if ($data['location_type'] === 'business' && $client->account_type === 'individual') {
            return response()->json([
                'message' => 'Individual accounts cannot create business missions.',
            ], 403);
        }

        $mission = Mission::create([
            ...$data,
            'client_id'      => $client->id,
            'status'         => Mission::STATUS_PENDING,
            'min_distance_m' => $client->min_distance_m ?? 0,
        ]);

        // ── Notifier le client : demande reçue, attribution en cours ──
        $user->notify(new AppNotification(
            event: 'mission.created',
            title: 'Demande en cours de traitement',
            body:  "Votre mission « {$mission->service} » a bien été créée. Un prestataire certifié vous sera attribué automatiquement sous peu.",
            url:   "/client/missions/{$mission->id}",
            icon:  'clock',
            extra: ['mission_id' => $mission->id, 'service' => $mission->service],
        ));

        // ── Notifier tous les admins : nouvelle mission soumise ──
        User::where('role', 'admin')->each(fn(User $admin) =>
            $admin->notify(new AppNotification(
                event: 'mission.created',
                title: 'Nouvelle mission reçue',
                body:  "Une nouvelle mission « {$mission->service} » vient d'être soumise par {$client->first_name} {$client->last_name}.",
                url:   "/admin/missions?id={$mission->id}",
                icon:  'plus',
                extra: ['mission_id' => $mission->id, 'service' => $mission->service],
            ))
        );

        $this->assignContractor($mission);

        return response()->json([
            'message' => 'Mission created successfully.',
            'mission' => $this->formatMission($mission->fresh()),
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // SHARED — Show / List
    // ══════════════════════════════════════════════════════════════

    public function show(Mission $mission): JsonResponse
    {
        $this->authorizeAccess(Auth::user(), $mission);

        return response()->json(
            $this->formatMission($mission->load(['client', 'contractor', 'quote']))
        );
    }

    public function index(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $status = $request->query('status');

        $query = Mission::with(['client', 'contractor'])
            ->when($user->role === 'client', function ($q) use ($user) {
                $clientId = Client::where('user_id', $user->id)->value('id');
                $q->where('client_id', $clientId);
            })
            ->when($user->role === 'contractor', function ($q) use ($user) {
                $contractorId = Contractor::where('user_id', $user->id)->value('id');
                $q->where('contractor_id', $contractorId);
            })
            ->when($status, fn($q) => $q->byStatus($status))
            ->latest()
            ->paginate(15);

        return response()->json($query);
    }

    // ══════════════════════════════════════════════════════════════
    // STATUS TRANSITIONS
    // ══════════════════════════════════════════════════════════════

    public function updateStatus(Request $request, Mission $mission): JsonResponse
    {
        $data = $request->validate([
            'status'         => 'required|string',
            'reported_issue' => 'nullable|string|max:1000',
        ]);

        $this->checkTransitionRights(Auth::user(), $mission, $data['status']);

        try {
            $mission->transitionTo($data['status']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (!empty($data['reported_issue'])) {
            $mission->update([
                'reported_issue' => $data['reported_issue'],
                'dispute_open'   => true,
            ]);
        }

        // ── Notifications selon le nouveau statut ─────────────────
        $this->notifyStatusChange($mission, $data['reported_issue'] ?? null);

        return response()->json([
            'message' => 'Status updated.',
            'mission' => $this->formatMission($mission->fresh()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    public function adminIndex(Request $request): JsonResponse
    {
        $missions = Mission::with(['client', 'contractor'])
            ->when($request->status,    fn($q) => $q->byStatus($request->status))
            ->when($request->service,   fn($q) => $q->where('service', $request->service))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(20);

        return response()->json($missions);
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Notifications
    // ══════════════════════════════════════════════════════════════

    /**
     * Envoie les notifications après chaque transition de statut.
     * Règle : on notifie la partie qui n'a PAS déclenché l'action,
     * plus l'admin pour les événements sensibles.
     */
    private function notifyStatusChange(Mission $mission, ?string $reportedIssue): void
    {
        $clientUser     = $mission->client?->user;
        $contractorUser = $mission->contractor?->user;
        $service        = $mission->service;
        $missionUrl     = "/client/missions/{$mission->id}";
        $contractorUrl  = "/contractor/missions/{$mission->id}";
        $adminUrl       = "/admin/missions?id={$mission->id}";
        $extra          = ['mission_id' => $mission->id];

        switch ($mission->status) {

            // Contractor accepte → notifier le client
            case Mission::STATUS_ACCEPTED:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.accepted',
                    title: 'Prestataire en route',
                    body:  "Votre mission « {$service} » a été acceptée. Le prestataire va bientôt vous contacter.",
                    url:   $missionUrl, icon: 'check', extra: $extra,
                ));
                break;

            // Contractor en route → notifier le client
            case Mission::STATUS_ON_THE_WAY:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.on_the_way',
                    title: 'Le prestataire est en route',
                    body:  "Votre prestataire est en chemin pour « {$service} ». Suivez sa position en temps réel.",
                    url:   $missionUrl, icon: 'navigation', extra: $extra,
                ));
                break;

            // Contractor arrivé → notifier le client
            case Mission::STATUS_IN_PROGRESS:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.in_progress',
                    title: 'Intervention en cours',
                    body:  "Le prestataire est arrivé sur place. L'intervention « {$service} » a démarré.",
                    url:   $missionUrl, icon: 'tool', extra: $extra,
                ));
                break;

            // Devis soumis → notifier le client
            case Mission::STATUS_QUOTE_SUBMITTED:
                $clientUser?->notify(new AppNotification(
                    event: 'quote.submitted',
                    title: 'Nouveau devis à approuver',
                    body:  "Un devis a été soumis pour « {$service} ». Consultez-le et approuvez-le pour démarrer les travaux.",
                    url:   $missionUrl, icon: 'file-text', extra: $extra,
                ));
                break;

            // Client approuve le devis → notifier le contractor
            case Mission::STATUS_ORDER_PLACED:
                $contractorUser?->notify(new AppNotification(
                    event: 'quote.approved',
                    title: 'Devis approuvé — démarrez les travaux',
                    body:  "Le client a approuvé votre devis pour « {$service} ». Vous pouvez démarrer l'intervention.",
                    url:   $contractorUrl, icon: 'check-circle', extra: $extra,
                ));
                break;

            // Contractor marque travaux terminés → notifier le client
            case Mission::STATUS_AWAITING_CONFIRM:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.awaiting_confirm',
                    title: 'Travaux terminés — votre confirmation requise',
                    body:  "Le prestataire a terminé l'intervention « {$service} ». Confirmez la fin des travaux pour débloquer le paiement.",
                    url:   $missionUrl, icon: 'check-square', extra: $extra,
                ));
                break;

            // Client confirme → notifier le contractor
            case Mission::STATUS_COMPLETED:
                $contractorUser?->notify(new AppNotification(
                    event: 'mission.completed',
                    title: 'Fin des travaux confirmée',
                    body:  "Le client a confirmé la fin de l'intervention « {$service} ». Le paiement va être déclenché.",
                    url:   $contractorUrl, icon: 'check-circle', extra: $extra,
                ));
                break;

            // Mission clôturée → notifier client + contractor + admin
            case Mission::STATUS_CLOSED:
                $net        = $mission->total_amount ? round($mission->total_amount * 0.9) : null;
                $commission = $mission->commission;

                $clientUser?->notify(new AppNotification(
                    event: 'payment.sent',
                    title: 'Mission clôturée',
                    body:  "Votre paiement pour « {$service} » a été effectué. La facture est disponible dans votre espace.",
                    url:   $missionUrl, icon: 'credit-card',
                    extra: array_merge($extra, ['amount' => $mission->total_amount]),
                ));

                $contractorUser?->notify(new AppNotification(
                    event: 'payment.received',
                    title: 'Paiement reçu',
                    body:  "Vous avez reçu " . ($net ? number_format($net, 0, ',', ' ') . ' FCFA' : 'votre paiement') . " pour « {$service} ».",
                    url:   $contractorUrl, icon: 'credit-card',
                    extra: array_merge($extra, ['net' => $net]),
                ));

                User::where('role', 'admin')->each(fn(User $admin) =>
                    $admin->notify(new AppNotification(
                        event: 'payment.commission',
                        title: 'Commission encaissée',
                        body:  "Commission de " . ($commission ? number_format($commission, 0, ',', ' ') . ' FCFA' : '10%') . " sur « {$service} ».",
                        url:   $adminUrl, icon: 'trending-up',
                        extra: array_merge($extra, ['commission' => $commission]),
                    ))
                );
                break;
        }

        // Litige signalé → notifier l'admin
        if ($mission->dispute_open && $reportedIssue) {
            User::where('role', 'admin')->each(fn(User $admin) =>
                $admin->notify(new AppNotification(
                    event: 'mission.dispute',
                    title: '⚠️ Litige signalé',
                    body:  "Problème signalé sur « {$service} » : {$reportedIssue}",
                    url:   $adminUrl, icon: 'alert-triangle',
                    extra: array_merge($extra, ['reason' => $reportedIssue]),
                ))
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Attribution automatique
    // ══════════════════════════════════════════════════════════════

    private function assignContractor(Mission $mission): void
    {
        $query = Contractor::where('available', true)
            ->where(function ($q) use ($mission) {
                if ($mission->location_type === 'business') {
                    $q->whereIn('accreditation', ['business', 'both']);
                } else {
                    $q->whereIn('accreditation', ['residential', 'both']);
                }
            })
            ->where(function ($q) use ($mission) {
                $q->where('specialty', $mission->service)
                  ->orWhereJsonContains('specialties', $mission->service);
            });

        if ($mission->latitude && $mission->longitude && $mission->min_distance_m > 0) {
            $minKm = $mission->min_distance_m / 1000;
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(latitude))
                )) >= ?
            ", [$mission->latitude, $mission->longitude, $mission->latitude, $minKm]);
        }

        // Phase 2 : trier par distance puis note
        // if ($mission->latitude && $mission->longitude) {
        //     $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude))
        //         * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))
        //         AS distance_km", [$mission->latitude, $mission->longitude, $mission->latitude])
        //         ->having('distance_km', '<=', \DB::raw('radius_km'))
        //         ->orderBy('distance_km');
        // }

        $contractor = $query->orderByDesc('average_rating')->first();

        if ($contractor) {
            $mission->update([
                'contractor_id' => $contractor->id,
                'status'        => Mission::STATUS_ASSIGNED,
                'assigned_at'   => now(),
            ]);

            // ── Notifier le contractor : mission attribuée ────────
            User::find($contractor->user_id)?->notify(new AppNotification(
                event: 'mission.assigned',
                title: 'Nouvelle mission disponible',
                body:  "Une mission « {$mission->service} » vous a été attribuée à {$mission->address}. Acceptez-la rapidement !",
                url:   "/contractor/missions/{$mission->id}",
                icon:  'user-check',
                extra: ['mission_id' => $mission->id, 'service' => $mission->service, 'address' => $mission->address],
            ));

            // ── Notifier le client : prestataire trouvé ──────────
            $mission->client?->user?->notify(new AppNotification(
                event: 'mission.assigned',
                title: 'Prestataire trouvé',
                body:  "Un prestataire certifié a été attribué à votre mission « {$mission->service} ». Il va bientôt vous contacter.",
                url:   "/client/missions/{$mission->id}",
                icon:  'user-check',
                extra: ['mission_id' => $mission->id],
            ));
        }
        // Aucun contractor trouvé → mission reste 'pending', pas de notif
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Helpers
    // ══════════════════════════════════════════════════════════════

    private function authorizeAccess(User $user, Mission $mission): void
    {
        $clientUserId     = $mission->client?->user_id;
        $contractorUserId = $mission->contractor?->user_id;

        if ($user->id !== $clientUserId && $user->id !== $contractorUserId) {
            abort(403, 'Unauthorized.');
        }
    }

    private function checkTransitionRights(User $user, Mission $mission, string $status): void
    {
        $clientOnly = [
            Mission::STATUS_ORDER_PLACED,
            Mission::STATUS_COMPLETED,
        ];

        $contractorOnly = [
            Mission::STATUS_ACCEPTED,
            Mission::STATUS_CONTACT_MADE,
            Mission::STATUS_ON_THE_WAY,
            Mission::STATUS_TRACKING,
            Mission::STATUS_IN_PROGRESS,
            Mission::STATUS_QUOTE_SUBMITTED,
            Mission::STATUS_AWAITING_CONFIRM,
        ];

        $clientUserId     = $mission->client?->user_id;
        $contractorUserId = $mission->contractor?->user_id;

        if (in_array($status, $clientOnly) && $user->id !== $clientUserId) {
            abort(403, 'Seul le client peut faire cette action.');
        }

        if (in_array($status, $contractorOnly) && $user->id !== $contractorUserId) {
            abort(403, 'Seul le prestataire peut faire cette action.');
        }
    }

    private function formatMission(Mission $mission): array
    {
        $client     = $mission->client;
        $contractor = $mission->contractor;

        return [
            'id'               => $mission->id,
            'status'           => $mission->status,
            'status_label'     => $mission->status_label,
            'step'             => $mission->step,
            'service'          => $mission->service,
            'address'          => $mission->address,
            'latitude'         => $mission->latitude,
            'longitude'        => $mission->longitude,
            'description'      => $mission->description,
            'availabilities'   => $mission->availabilities,
            'location_type'    => $mission->location_type,
            'min_distance_m'   => $mission->min_distance_m,
            'total_amount'     => $mission->total_amount,
            'commission'       => $mission->commission,
            'payment_unlocked' => $mission->paymentUnlocked(),
            'is_on_the_way'    => $mission->isOnTheWay(),
            'dispute_open'     => $mission->dispute_open,
            'reported_issue'   => $mission->reported_issue,
            'assigned_at'      => $mission->assigned_at?->toISOString(),
            'accepted_at'      => $mission->accepted_at?->toISOString(),
            'arrived_at'       => $mission->arrived_at?->toISOString(),
            'completed_at'     => $mission->completed_at?->toISOString(),
            'paid_at'          => $mission->paid_at?->toISOString(),
            'created_at'       => $mission->created_at->toISOString(),
            'client' => $client ? [
                'id'              => $client->id,
                'user_id'         => $client->user_id,
                'name'            => trim("{$client->first_name} {$client->last_name}"),
                'phone'           => $client->phone,
                'city'            => $client->city,
                'profile_picture' => $client->profile_picture,
                'account_type'    => $client->account_type,
                'company_name'    => $client->company_name,
            ] : null,
            'contractor' => $contractor ? [
                'id'              => $contractor->id,
                'user_id'         => $contractor->user_id,
                'name'            => trim("{$contractor->first_name} {$contractor->last_name}"),
                'phone'           => $contractor->phone,
                'city'            => $contractor->city,
                'profile_picture' => $contractor->profile_picture,
                'specialty'       => $contractor->specialty,
                'average_rating'  => $contractor->average_rating,
                'reviews_count'   => $contractor->reviews_count,
                'total_missions'  => $contractor->total_missions,
            ] : null,
            'quote' => $mission->quote,
        ];
    }
}