<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Notifications\AppNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenderController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // PUBLIC — Liste des AO publiés
    // ══════════════════════════════════════════════════════════════

    public function index(Request $request): JsonResponse
    {
        $query = Tender::published()
            ->when($request->domain,   fn($q) => $q->where('domain', $request->domain))
            ->when($request->location, fn($q) => $q->where('location', $request->location))
            ->when($request->profile_type, fn($q) => $q->where('profile_type', $request->profile_type))
            ->when($request->search, fn($q) =>
                $q->where(fn($s) =>
                    $s->where('title', 'like', "%{$request->search}%")
                      ->orWhere('description', 'like', "%{$request->search}%")
                )
            )
            ->when($request->budget === 'low',  fn($q) => $q->whereRaw("CAST(REPLACE(REPLACE(budget, ' ', ''), 'FCFA', '') AS UNSIGNED) < 100000"))
            ->when($request->budget === 'mid',  fn($q) => $q->whereRaw("CAST(REPLACE(REPLACE(budget, ' ', ''), 'FCFA', '') AS UNSIGNED) BETWEEN 100000 AND 500000"))
            ->when($request->budget === 'high', fn($q) => $q->whereRaw("CAST(REPLACE(REPLACE(budget, ' ', ''), 'FCFA', '') AS UNSIGNED) > 500000"))
            ->orderBy($request->sort === 'candidatures' ? 'candidatures_count' : 'published_at', 'desc')
            ->paginate(20);

        return response()->json(
            $query->through(fn($t) => $this->formatTender($t))
        );
    }

    // ── Stats pour le hero ────────────────────────────────────────
    public function stats(): JsonResponse
    {
        return response()->json([
            'active'    => Tender::published()->count(),
            'domains'   => Tender::published()->distinct('domain')->count('domain'),
            'companies' => Tender::published()->distinct('user_id')->count('user_id'),
            'talents'   => User::where('role', 'talent')->count(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // AUTH — Publier un AO (client entreprise ou talent)
    // ══════════════════════════════════════════════════════════════

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'domain'       => 'required|string|max:100',
            'location'     => 'required|string|max:100',
            'duration'     => 'required|string|max:100',
            'budget'       => 'nullable|string|max:100',
            'deadline'     => 'required|date|after:today',
            'profile_type' => 'required|in:prestataire,talent,both',
            'description'  => 'required|string|min:20|max:5000',
            'requirements' => 'nullable|string|max:2000',
        ], [
            'title.required'        => 'Le titre de la mission est obligatoire.',
            'title.max'             => 'Le titre ne doit pas dépasser 255 caractères.',
            'domain.required'       => 'Le domaine requis est obligatoire.',
            'location.required'     => 'La localisation est obligatoire.',
            'duration.required'     => 'La durée estimée est obligatoire.',
            'deadline.required'     => 'La date limite de candidature est obligatoire.',
            'deadline.date'         => 'La date limite doit être une date valide.',
            'deadline.after'        => 'La date limite doit être postérieure à aujourd\'hui.',
            'profile_type.required' => 'Le type de profil recherché est obligatoire.',
            'profile_type.in'       => 'Le type de profil doit être : prestataire, talent ou les deux.',
            'description.required'  => 'La description de la mission est obligatoire.',
            'description.min'       => 'La description doit contenir au moins 20 caractères.',
            'description.max'       => 'La description ne doit pas dépasser 5000 caractères.',
        ]);

        $tender = Tender::create([
            ...$data,
            'user_id' => Auth::id(),
            'status'  => Tender::STATUS_PENDING,
        ]);

        // Notifier les admins
        User::where('role', 'admin')->each(fn(User $admin) =>
            $admin->notify(new AppNotification(
                event: 'tender.pending',
                title: 'Nouvel appel d\'offres à valider',
                body:  "« {$tender->title} » soumis par " . Auth::user()->name . " — en attente de validation.",
                url:   "/admin/tenders/{$tender->id}",
                icon:  'file-text',
                extra: ['tender_id' => $tender->id],
            ))
        );

        return response()->json([
            'message' => 'Appel d\'offres soumis. Il sera mis en ligne après validation sous 24h.',
            'tender'  => $this->formatTender($tender),
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // AUTH — Candidater à un AO
    // ══════════════════════════════════════════════════════════════

    public function apply(Request $request, Tender $tender): JsonResponse
    {
        if ($tender->status !== Tender::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Cet appel d\'offres n\'est plus disponible.'], 422);
        }

        if ($tender->isExpired()) {
            return response()->json(['message' => 'La date limite de candidature est dépassée.'], 422);
        }

        // Vérifier si déjà candidaté
        $existing = TenderApplication::where('tender_id', $tender->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà postulé à cet appel d\'offres.'], 422);
        }

        $data = $request->validate([
            'motivation'    => 'required|string|min:10|max:3000',
            'tarif'         => 'nullable|string|max:100',
            'disponibilite' => 'nullable|string|max:200',
        ], [
            'motivation.required' => 'La lettre de motivation est obligatoire.',
            'motivation.min'      => 'La lettre de motivation doit contenir au moins 10 caractères.',
            'motivation.max'      => 'La lettre de motivation ne doit pas dépasser 3000 caractères.',
        ]);

        $application = TenderApplication::create([
            ...$data,
            'tender_id' => $tender->id,
            'user_id'   => Auth::id(),
            'status'    => TenderApplication::STATUS_PENDING,
        ]);

        // Incrémenter le compteur
        $tender->increment('candidatures_count');

        // Notifier le propriétaire de l'AO
        $tender->user?->notify(new AppNotification(
            event: 'tender.application',
            title: 'Nouvelle candidature reçue',
            body:  "Quelqu'un a postulé à votre appel d'offres « {$tender->title} ».",
            url:   "/market?tab=myTenders&id={$tender->id}",
            icon:  'user-plus',
            extra: ['tender_id' => $tender->id, 'application_id' => $application->id],
        ));

        return response()->json([
            'message'     => 'Candidature envoyée avec succès.',
            'application' => $this->formatApplication($application),
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // AUTH — Mes candidatures (prestataire / talent)
    // ══════════════════════════════════════════════════════════════

    public function myApplications(): JsonResponse
    {
        $applications = TenderApplication::with('tender')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json(
            $applications->map(fn($a) => $this->formatApplication($a))
        );
    }

    // ══════════════════════════════════════════════════════════════
    // AUTH — Mes appels d'offres (client)
    // ══════════════════════════════════════════════════════════════

    public function myTenders(): JsonResponse
    {
        $tenders = Tender::where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json(
            $tenders->map(fn($t) => $this->formatTender($t))
        );
    }


    // ══════════════════════════════════════════════════════════════
    // ADMIN — Validation / rejet
    // ══════════════════════════════════════════════════════════════

    public function adminIndex(Request $request): JsonResponse
    {
        $tenders = Tender::with('user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(40);

        return response()->json(
            $tenders->through(fn($t) => $this->formatTender($t, true))
        );
    }

    public function adminUpdateStatus(Request $request, Tender $tender): JsonResponse
    {
        $data = $request->validate([
            'status'        => 'required|in:published,rejected,closed',
            'reject_reason' => 'nullable|string|max:500',
        ]);

        $tender->update([
            'status'        => $data['status'],
            'reject_reason' => $data['reject_reason'] ?? null,
            'published_at'  => $data['status'] === Tender::STATUS_PUBLISHED ? now() : $tender->published_at,
        ]);

        // Notifier l'auteur
        $notifTitle = match ($data['status']) {
            Tender::STATUS_PUBLISHED => 'Votre appel d\'offres est en ligne !',
            Tender::STATUS_REJECTED  => 'Appel d\'offres non validé',
            Tender::STATUS_CLOSED    => 'Appel d\'offres clôturé',
            default => '',
        };
        $notifBody = match ($data['status']) {
            Tender::STATUS_PUBLISHED => "« {$tender->title} » est maintenant visible par tous les prestataires et Talents.",
            Tender::STATUS_REJECTED  => "« {$tender->title} » n'a pas été validé. Motif : " . ($data['reject_reason'] ?? 'Non précisé'),
            Tender::STATUS_CLOSED    => "« {$tender->title} » a été clôturé par l'équipe Resotravo.",
            default => '',
        };

        $tender->user?->notify(new AppNotification(
            event: "tender.{$data['status']}",
            title: $notifTitle,
            body:  $notifBody,
            url:   "/market",
            icon:  $data['status'] === Tender::STATUS_PUBLISHED ? 'check-circle' : 'x-circle',
            extra: ['tender_id' => $tender->id],
        ));

        return response()->json([
            'message' => 'Statut mis à jour.',
            'tender'  => $this->formatTender($tender->fresh(), true),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE — Formatters
    // ══════════════════════════════════════════════════════════════

    private function formatTender(Tender $t, bool $admin = false): array
    {
        $base = [
            'id'                 => $t->id,
            'title'              => $t->title,
            'domain'             => $t->domain,
            'domainIcon'         => $t->domainIcon(),
            'location'           => $t->location,
            'duration'           => $t->duration,
            'budget'             => $t->budget ?? '—',
            'deadline'           => $t->deadline->format('d M Y'),
            'deadline_raw'       => $t->deadline->toDateString(),
            'profile_type'       => $t->profile_type,
            'description'        => $t->description,
            'requirements'       => $t->requirements,
            'status'             => $t->status,
            'urgency'            => $t->urgency(),
            'urgencyLabel'       => $t->urgencyLabel(),
            'candidatures'       => $t->candidatures_count,
            'company'            => $t->user?->name ?? '—',
            'published_at'       => $t->published_at?->toISOString(),
            'created_at'         => $t->created_at->toISOString(),
            // Tags déduits automatiquement
            'tags'               => $t->autoTags(),
            'user_id'            => $t->user_id,
        ];

        if ($admin) {
            $base['reject_reason'] = $t->reject_reason;
        }

        return $base;
    }

    private function formatApplication(TenderApplication $a): array
    {
        $tender = $a->tender;
        return [
            'id'            => $a->id,
            'tender_id'     => $a->tender_id,
            'motivation'    => $a->motivation,
            'tarif'         => $a->tarif,
            'disponibilite' => $a->disponibilite,
            'status'        => $a->status,
            'statusLabel'   => $a->statusLabel(),
            'statusIcon'    => $a->statusIcon(),
            'statusClass'   => $a->status,
            'created_at'    => $a->created_at->toISOString(),
            'date'          => $a->created_at->format('d M Y'),
            // Infos de l'AO associé
            'title'         => $tender?->title,
            'company'       => $tender?->user?->name ?? '—',
            'location'      => $tender?->location,
            'domainIcon'    => $tender?->domainIcon() ?? '📋',
        ];
    }
}