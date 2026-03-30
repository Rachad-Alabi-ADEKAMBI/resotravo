<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Mission;
use App\Models\MissionProposal;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|max:10240',
        ]);

        if ($data['location_type'] === 'business' && $client->account_type === 'individual') {
            return response()->json([
                'message' => 'Individual accounts cannot create business missions.',
            ], 403);
        }

        $mission = Mission::create([
            ...collect($data)->except('images')->toArray(),
            'client_id'      => $client->id,
            'status'         => Mission::STATUS_PENDING,
            'min_distance_m' => $client->min_distance_m ?? 0,
        ]);

        // Store images after creation (need mission ID for path)
        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $img) {
                $paths[] = $img->store("missions/{$mission->id}", 'public');
            }
            $mission->update(['images' => $paths]);
        }

        $user->notify(new AppNotification(
            event: 'mission.created',
            title: 'Demande en cours de traitement',
            body:  "Votre mission « {$mission->service} » a bien été créée. Un prestataire certifié vous sera attribué automatiquement sous peu.",
            url:   "/client/missions/{$mission->id}",
            icon:  'clock',
            extra: ['mission_id' => $mission->id, 'service' => $mission->service],
        ));

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

        $query = Mission::with(['client', 'contractor', 'quote.items'])
            ->when($user->role === 'client', function ($q) use ($user) {
                $clientId = Client::where('user_id', $user->id)->value('id');
                $q->where('client_id', $clientId);
            })
            ->when($user->role === 'contractor', function ($q) use ($user) {
                $contractorId = Contractor::where('user_id', $user->id)->value('id');
                $q->where(function ($sub) use ($user, $contractorId) {
                    // Missions directement assignées à ce prestataire
                    $sub->where('contractor_id', $contractorId)
                        // OU missions avec une proposal pending/accepted pour ce user
                        ->orWhereHas('proposals', fn($p) =>
                            $p->where('contractor_id', $user->id)
                              ->whereIn('status', ['pending', 'accepted'])
                        );
                });
            })
            ->when($status, fn($q) => $q->byStatus($status))
            ->latest()
            ->paginate(50);

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

        $user = Auth::user();
        $this->checkTransitionRights($user, $mission, $data['status']);

        // ── Cas spécial : refus via proposal multi-prestataires ──
        if ($data['status'] === 'proposal_rejected') {
            $proposal = $mission->proposals()
                ->where('contractor_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if (!$proposal) {
                return response()->json(['message' => 'Proposition introuvable ou déjà traitée.'], 422);
            }

            $proposal->reject($data['reported_issue'] ?? null);

            return response()->json([
                'message' => 'Mission refusée.',
                'mission' => $this->formatMission($mission->fresh()->load(['client', 'contractor', 'quote.items'])),
            ]);
        }

        // ── Cas spécial : acceptation ──
        // Deux scénarios possibles :
        // A) Mission assignée directement (assignContractor) → contractor_id renseigné, pas de proposal
        // B) Mission proposée via adminPropose → contractor_id null, proposal en pending ou expired
        if ($data['status'] === Mission::STATUS_ACCEPTED && is_null($mission->contractor_id)) {

            // DEBUG TEMPORAIRE — à supprimer après diagnostic
            \Log::debug('ACCEPT DEBUG', [
                'user_id'          => $user->id,
                'mission_id'       => $mission->id,
                'mission_status'   => $mission->status,
                'contractor_id'    => $mission->contractor_id,
                'proposals'        => $mission->proposals()->get(['id','contractor_id','status','expires_at'])->toArray(),
            ]);

            $proposal = $mission->proposals()
                ->where('contractor_id', $user->id)
                ->where('status', 'pending') // les proposals expirées restent en 'pending' (expires_at est dépassé mais status inchangé)
                ->first();

            if (!$proposal) {
                return response()->json(['message' => 'Proposition introuvable ou déjà traitée.'], 422);
            }

            $proposal->accept(); // assigne contractor_id + invalide les autres proposals

            $this->notifyStatusChange($mission->fresh(), null);

            return response()->json([
                'message' => 'Mission acceptée.',
                'mission' => $this->formatMission($mission->fresh()->load(['client', 'contractor', 'quote.items'])),
            ]);
        }

        // ── Cas spécial : refus de devis par le client ──
        if ($data['status'] === Mission::STATUS_QUOTE_SUBMITTED && !empty($data['reported_issue'])) {
            if (!$mission->quote) {
                return response()->json(['message' => 'Aucun devis trouvé pour cette mission.'], 422);
            }

            $mission->quote->update([
                'status'           => \App\Models\MissionQuote::STATUS_REJECTED,
                'rejection_reason' => $data['reported_issue'],
            ]);

            $mission->update([
                'reported_issue' => $data['reported_issue'],
                'dispute_open'   => false,
            ]);

            // Notifier le prestataire
            $mission->contractor?->user?->notify(new AppNotification(
                event: 'quote.rejected',
                title: 'Devis refusé',
                body:  "Le client a refusé votre devis pour « {$mission->service} ». Motif : {$data['reported_issue']}",
                url:   "/contractor/missions/{$mission->id}",
                icon:  'x-circle',
                extra: ['mission_id' => $mission->id],
            ));

            return response()->json([
                'message' => 'Devis refusé.',
                'mission' => $this->formatMission($mission->fresh()->load(['client', 'contractor', 'quote.items'])),
            ]);
        }

        // ── Cas standard : transition normale ──
        standard_transition:
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

        $this->notifyStatusChange($mission, $data['reported_issue'] ?? null);

        return response()->json([
            'message' => 'Status updated.',
            'mission' => $this->formatMission($mission->fresh()->load(['client', 'contractor', 'quote.items'])),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /admin/missions/list  →  admin.missions.index  (JSON)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $missions = Mission::with(['client', 'contractor', 'proposals'])
            ->when($request->status,    fn($q) => $q->byStatus($request->status))
            ->when($request->service,   fn($q) => $q->where('service', $request->service))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(60);

        return response()->json(
            $missions->through(fn($m) => $this->formatMissionForAdmin($m))
        );
    }

    /**
     * GET /admin/missions/{mission}  →  admin.missions.show  (JSON)
     */
    public function adminShow(Mission $mission): JsonResponse
    {
        $mission->load(['client', 'contractor', 'quote', 'proposals.contractor.contractor']);

        return response()->json($this->formatMissionForAdmin($mission));
    }

    /**
     * PATCH /admin/missions/{mission}/status  →  admin.missions.status
     * Body : { status: 'cancelled' | 'pending', reason?: '...' }
     */
    public function adminStatus(Request $request, Mission $mission): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:cancelled,pending',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($request->status === 'cancelled') {
            $mission->cancelByAdmin($request->reason ?? '');

            // Notifier le client
            $mission->client?->user?->notify(new AppNotification(
                event: 'mission.cancelled',
                title: 'Mission annulée',
                body:  "Votre mission « {$mission->service} » a été annulée par Resotravo. Motif : " . ($request->reason ?? 'Non précisé'),
                url:   "/client/missions/{$mission->id}",
                icon:  'x-circle',
                extra: ['mission_id' => $mission->id],
            ));
        } else {
            // Réouverture
            $mission->update([
                'status'        => Mission::STATUS_PENDING,
                'contractor_id' => null,
                'cancel_reason' => null,
                'cancelled_at'  => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'mission' => $this->formatMissionForAdmin($mission->fresh()),
        ]);
    }

    /**
     * POST /admin/missions/{mission}/propose  →  admin.missions.propose
     * Body : { contractor_ids: [1, 2, 3] }
     *
     * Envoie la mission à plusieurs prestataires simultanément.
     * Le premier à accepter est assigné, les autres passent en "superseded".
     */
    public function adminPropose(Request $request, Mission $mission): JsonResponse
    {
        $request->validate([
            'contractor_ids'   => 'required|array|min:1',
            'contractor_ids.*' => 'integer|exists:users,id',
        ]);

        if (!$mission->canReceiveProposals()) {
            return response()->json([
                'message' => "Cette mission ne peut plus recevoir de propositions (statut : {$mission->status}).",
            ], 422);
        }

        // Éviter les doublons
        $alreadyProposed = $mission->proposals()
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('contractor_id')
            ->toArray();

        $newIds = array_values(array_diff($request->contractor_ids, $alreadyProposed));

        if (empty($newIds)) {
            return response()->json([
                'message'   => 'Tous ces prestataires ont déjà une proposition en cours.',
                'proposals' => [],
            ], 422);
        }

        $proposals = [];

        foreach ($newIds as $contractorId) {
            $proposal = $mission->proposals()->create([
                'contractor_id' => $contractorId,
                'status'        => 'pending',
                'proposed_at'   => now(),
                'expires_at'    => now()->addMinutes(5),
            ]);

            // Charger le profil pour la réponse JSON
            $contractorProfile = Contractor::where('user_id', $contractorId)->first();

            // Notifier le prestataire
            User::find($contractorId)?->notify(new AppNotification(
                event: 'mission.proposed',
                title: 'Nouvelle mission proposée',
                body:  "Une mission « {$mission->service} » à {$mission->address} vous a été proposée. Vous avez 5 min pour accepter.",
                url:   "/contractor/missions/{$mission->id}",
                icon:  'bell',
                extra: ['mission_id' => $mission->id, 'service' => $mission->service],
            ));

            $proposals[] = [
                'id'            => $proposal->id,
                'contractor_id' => $contractorId,
                'status'        => 'pending',
                'contractor'    => [
                    'first_name' => $contractorProfile?->first_name,
                    'last_name'  => $contractorProfile?->last_name,
                    'specialty'  => $contractorProfile?->specialty,
                ],
            ];
        }

        // Passer la mission en "assigned" si elle était encore en "pending"
        if ($mission->status === Mission::STATUS_PENDING) {
            $mission->update([
                'status'      => Mission::STATUS_ASSIGNED,
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'success'   => true,
            'proposals' => $proposals,
            'mission'   => $this->formatMissionForAdmin($mission->fresh()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Notifications
    // ══════════════════════════════════════════════════════════════

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

            // Prestataire proposé → notifier le prestataire
            case Mission::STATUS_ASSIGNED:
                $contractorUser?->notify(new AppNotification(
                    event: 'mission.assigned',
                    title: '📬 Nouvelle mission proposée',
                    body:  "Une mission « {$service} » à {$mission->address} vous a été proposée. Vous avez 5 min pour accepter.",
                    url:   $contractorUrl, icon: 'bell', extra: $extra,
                ));
                break;

            // Prestataire accepte → notifier le client
            case Mission::STATUS_ACCEPTED:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.accepted',
                    title: '✅ Prestataire trouvé',
                    body:  "Votre mission « {$service} » a été acceptée. Le prestataire va vous contacter via la messagerie.",
                    url:   $missionUrl, icon: 'check', extra: $extra,
                ));
                break;

            case Mission::STATUS_ON_THE_WAY:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.on_the_way',
                    title: 'Le prestataire est en route',
                    body:  "Votre prestataire est en chemin pour « {$service} ». Suivez sa position en temps réel.",
                    url:   $missionUrl, icon: 'navigation', extra: $extra,
                ));
                break;

            case Mission::STATUS_IN_PROGRESS:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.in_progress',
                    title: 'Intervention en cours',
                    body:  "Le prestataire est arrivé sur place. L'intervention « {$service} » a démarré.",
                    url:   $missionUrl, icon: 'tool', extra: $extra,
                ));
                break;

            case Mission::STATUS_QUOTE_SUBMITTED:
                $clientUser?->notify(new AppNotification(
                    event: 'quote.submitted',
                    title: 'Nouveau devis à approuver',
                    body:  "Un devis a été soumis pour « {$service} ». Consultez-le et approuvez-le pour démarrer les travaux.",
                    url:   $missionUrl, icon: 'file-text', extra: $extra,
                ));
                break;

            case Mission::STATUS_ORDER_PLACED:
                $contractorUser?->notify(new AppNotification(
                    event: 'quote.approved',
                    title: 'Devis approuvé — démarrez les travaux',
                    body:  "Le client a approuvé votre devis pour « {$service} ». Vous pouvez démarrer l'intervention.",
                    url:   $contractorUrl, icon: 'check-circle', extra: $extra,
                ));
                break;

            case Mission::STATUS_AWAITING_CONFIRM:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.awaiting_confirm',
                    title: 'Travaux terminés — votre confirmation requise',
                    body:  "Le prestataire a terminé l'intervention « {$service} ». Confirmez la fin des travaux pour débloquer le paiement.",
                    url:   $missionUrl, icon: 'check-square', extra: $extra,
                ));
                break;

            case Mission::STATUS_COMPLETED:
                $contractorUser?->notify(new AppNotification(
                    event: 'mission.completed',
                    title: 'Fin des travaux confirmée',
                    body:  "Le client a confirmé la fin de l'intervention « {$service} ». Le paiement va être déclenché.",
                    url:   $contractorUrl, icon: 'check-circle', extra: $extra,
                ));
                break;

            case Mission::STATUS_CLOSED:
                $net        = $mission->total_amount ? round($mission->total_amount * 0.9) : null;
                $commission = $mission->commission;

                // Incrémenter le compteur de missions terminées du client
                if ($mission->client) {
                    $mission->client->increment('completed_missions');
                    $mission->client->increment('total_missions');
                }

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

            // Mission annulée → notifier les deux parties
            case Mission::STATUS_CANCELLED:
                $clientUser?->notify(new AppNotification(
                    event: 'mission.cancelled',
                    title: '❌ Mission annulée',
                    body:  "Votre mission « {$service} » a été annulée." . ($reportedIssue ? " Motif : {$reportedIssue}" : ''),
                    url:   $missionUrl, icon: 'x-circle', extra: $extra,
                ));
                $contractorUser?->notify(new AppNotification(
                    event: 'mission.cancelled',
                    title: '❌ Mission annulée',
                    body:  "La mission « {$service} » a été annulée.",
                    url:   $contractorUrl, icon: 'x-circle', extra: $extra,
                ));
                break;
        }

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
                    $q->whereIn('accreditation', ['home', 'both']);
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

        $contractor = $query->orderByDesc('average_rating')->first();

        if ($contractor) {
            $mission->update([
                'contractor_id' => $contractor->id,
                'status'        => Mission::STATUS_ASSIGNED,
                'assigned_at'   => now(),
            ]);

            User::find($contractor->user_id)?->notify(new AppNotification(
                event: 'mission.assigned',
                title: 'Nouvelle mission disponible',
                body:  "Une mission « {$mission->service} » vous a été attribuée à {$mission->address}. Acceptez-la rapidement !",
                url:   "/contractor/missions/{$mission->id}",
                icon:  'user-check',
                extra: ['mission_id' => $mission->id, 'service' => $mission->service, 'address' => $mission->address],
            ));

            $mission->client?->user?->notify(new AppNotification(
                event: 'mission.assigned',
                title: 'Prestataire trouvé',
                body:  "Un prestataire certifié a été attribué à votre mission « {$mission->service} ». Il va bientôt vous contacter.",
                url:   "/client/missions/{$mission->id}",
                icon:  'user-check',
                extra: ['mission_id' => $mission->id],
            ));
        }
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Helpers
    // ══════════════════════════════════════════════════════════════

    private function authorizeAccess(User $user, Mission $mission): void
    {
        $clientUserId     = $mission->client?->user_id;
        $contractorUserId = $mission->contractor?->user_id;

        // Autoriser aussi le prestataire qui a une proposal active sur cette mission
        $hasProposal = $mission->proposals()
            ->where('contractor_id', $user->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($user->id !== $clientUserId && $user->id !== $contractorUserId && !$hasProposal) {
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
            Mission::STATUS_AWAITING_CONFIRM,
            // STATUS_QUOTE_SUBMITTED retiré : le client peut aussi l'envoyer pour refuser le devis
        ];

        $clientUserId     = $mission->client?->user_id;
        $contractorUserId = $mission->contractor?->user_id;

        // Un prestataire avec une proposal pending peut aussi accepter/refuser
        $hasProposal = $mission->proposals()
            ->where('contractor_id', $user->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        $isContractor = ($user->id === $contractorUserId) || $hasProposal;

        if (in_array($status, $clientOnly) && $user->id !== $clientUserId) {
            abort(403, 'Seul le client peut faire cette action.');
        }

        if (in_array($status, $contractorOnly) && !$isContractor) {
            abort(403, 'Seul le prestataire peut faire cette action.');
        }
    }

    /**
     * Format pour les vues client/contractor.
     */
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
            'images'           => collect($mission->images ?? [])->map(fn($p) => Storage::disk('public')->url($p))->values()->toArray(),
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
                'completed_missions' => Mission::where('client_id', $client->id)->where('status', Mission::STATUS_CLOSED)->count(),
                'is_verified'     => $client->user?->status === 'approved'
                                     && Mission::where('client_id', $client->id)->where('status', Mission::STATUS_CLOSED)->count() >= 5,
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
            'quote' => $mission->quote ? [
                'id'              => $mission->quote->id,
                'status'          => $mission->quote->status,
                'diagnosis'       => $mission->quote->diagnosis,
                'amount_excl_tax' => $mission->quote->amount_excl_tax,
                'amount_incl_tax' => $mission->quote->amount_incl_tax,
                'version'         => $mission->quote->version,
                'rejection_reason'=> $mission->quote->rejection_reason,
                'items'           => $mission->quote->relationLoaded('items')
                    ? $mission->quote->items->map(fn($i) => [
                        'id'          => $i->id,
                        'type'        => $i->type,
                        'description' => $i->description,
                        'quantity'    => $i->quantity,
                        'unit_price'  => $i->unit_price,
                        'line_total'  => (float)$i->quantity * (float)$i->unit_price,
                    ])->toArray()
                    : [],
            ] : null,
        ];
    }

    /**
     * Format enrichi pour les vues admin (AdminMissionComponent).
     */
    private function formatMissionForAdmin(Mission $mission): array
    {
        $client     = $mission->client;
        $contractor = $mission->contractor;

        return [
            'id'            => $mission->id,
            'status'        => $mission->status,
            'status_label'  => $mission->status_label,
            'step'          => $mission->step,
            'service'       => $mission->service,
            'address'       => $mission->address,
            'description'   => $mission->description,
            'location_type' => $mission->location_type ?? 'residential',
            'total_amount'  => $mission->total_amount,
            'commission'    => $mission->commission,
            'desired_date'  => $mission->availabilities[0] ?? null,
            'contractor_id' => $mission->contractor_id,
            'dispute_open'  => $mission->dispute_open,
            'cancel_reason' => $mission->cancel_reason ?? null,
            'created_at'    => $mission->created_at,
            'client' => $client ? [
                'name'         => trim("{$client->first_name} {$client->last_name}"),
                'email'        => $client->user?->email,
                'phone'        => $client->phone,
                'account_type' => $client->account_type,
                'is_verified'  => $client->user?->status === 'approved'
                                  && Mission::where('client_id', $client->id)->where('status', Mission::STATUS_CLOSED)->count() >= 5,
            ] : null,
            'contractor' => $contractor ? [
                'first_name'     => $contractor->first_name,
                'last_name'      => $contractor->last_name,
                'specialty'      => $contractor->specialty,
                'phone'          => $contractor->phone,
                'average_rating' => $contractor->average_rating,
                'available'      => $contractor->available,
            ] : null,
            'proposals' => ($mission->relationLoaded('proposals') ? $mission->proposals : collect())->map(fn($p) => [
                'id'            => $p->id,
                'contractor_id' => $p->contractor_id,
                'status'        => $p->status,
                'contractor'    => [
                    'first_name' => $p->contractor?->contractor?->first_name,
                    'last_name'  => $p->contractor?->contractor?->last_name,
                    'specialty'  => $p->contractor?->contractor?->specialty,
                ],
            ])->toArray(),
        ];
    }
}