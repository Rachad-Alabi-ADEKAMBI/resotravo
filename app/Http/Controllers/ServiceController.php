<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Mission;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // PUBLIC — Page d'accueil
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /services/public
     * Retourne les services actifs pour la page d'accueil.
     */
    public function publicIndex(): JsonResponse
    {
        $services = Service::visible()
            ->get(['id', 'name', 'slug', 'icon', 'description', 'category', 'sort_order']);

        return response()->json($services);
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN — Catalogue complet
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /services/suggestions
     * Enregistre une suggestion de service envoyee depuis l'inscription prestataire.
     */
    public function storeSuggestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($data['name']);
        $existing = Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            return response()->json([
                'message' => 'Ce domaine existe deja.',
                'service' => $this->formatForAdmin($existing),
            ]);
        }

        $slug = Str::slug($name);

        if (Service::where('slug', $slug)->exists()) {
            $slug .= '-' . uniqid();
        }

        $service = Service::create([
            'name'          => $name,
            'slug'          => $slug,
            'icon'          => '🔧',
            'description'   => 'Suggestion proposee par un prestataire.',
            'category'      => 'Suggestion prestataire',
            'accreditation' => 'both',
            'admin_only'    => false,
            'is_suggestion' => true,
            'is_visible'    => false,
            'sort_order'    => 0,
            'status'        => 'pending',
        ]);

        return response()->json([
            'message' => 'Suggestion enregistree avec succes.',
            'service' => $this->formatForAdmin($service),
        ], 201);
    }

    /**
     * GET /admin/services
     * Liste tous les services (tous statuts) pour le tableau de bord admin.
     */
    public function adminIndex(): JsonResponse
    {
        // Pré-calcul en une seule requête chacun — évite N+1
        // Mission.service et Contractor.specialty sont des strings (ex: "Plomberie")

        $missionCounts = Mission::selectRaw('service, count(*) as total')
            ->whereNotNull('service')
            ->groupBy('service')
            ->pluck('total', 'service')         // ['Plomberie' => 3, ...]
            ->mapWithKeys(fn ($v, $k) => [strtolower(trim($k)) => $v]);

        $missionCompletedCounts = Mission::selectRaw('service, count(*) as total')
            ->whereNotNull('service')
            ->whereIn('status', [Mission::STATUS_COMPLETED, Mission::STATUS_CLOSED])
            ->groupBy('service')
            ->pluck('total', 'service')
            ->mapWithKeys(fn ($v, $k) => [strtolower(trim($k)) => $v]);

        // Contractors : on cherche dans specialty ET dans le tableau specialties (JSON)
        $contractorsBySpecialty = Contractor::selectRaw('specialty, count(*) as total')
            ->whereNotNull('specialty')
            ->groupBy('specialty')
            ->pluck('total', 'specialty')
            ->mapWithKeys(fn ($v, $k) => [strtolower(trim($k)) => $v]);

        $services = Service::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Service $s) use ($missionCounts, $missionCompletedCounts, $contractorsBySpecialty) {
                $key = strtolower(trim($s->name));

                return $this->formatForAdmin($s, [
                    'contractors_count'  => $contractorsBySpecialty->get($key, 0),
                    'missions_count'     => $missionCounts->get($key, 0),
                    'missions_completed' => $missionCompletedCounts->get($key, 0),
                ]);
            });

        return response()->json($services);
    }

    /**
     * POST /admin/services
     * Crée un nouveau service.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100|unique:services,name',
            'icon'          => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:300',
            'category'      => 'nullable|string|max:80',
            'accreditation' => ['nullable', Rule::in(['domicile', 'entreprise', 'both'])],
            'admin_only'    => 'boolean',
            'is_visible'    => 'boolean',
            'sort_order'    => 'integer|min:0',
            'status'        => ['nullable', Rule::in(['active', 'pending', 'archived'])],
        ]);

        $data['slug']          = Str::slug($data['name']);
        $data['icon']          = $data['icon'] ?? '🔨';   // emoji par défaut
        $data['accreditation'] = $data['accreditation'] ?? 'both';
        $data['status']        = $data['status'] ?? 'active';
        $data['is_suggestion'] = false;

        // Si le slug existe déjà, on le rend unique
        if (Service::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . uniqid();
        }

        $service = Service::create($data);

        return response()->json($this->formatForAdmin($service), 201);
    }

    /**
     * PUT /admin/services/{service}
     * Met à jour un service existant.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        $oldName = $service->name;

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100', Rule::unique('services', 'name')->ignore($service->id)],
            'icon'          => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:300',
            'category'      => 'nullable|string|max:80',
            'accreditation' => ['nullable', Rule::in(['domicile', 'entreprise', 'both'])],
            'admin_only'    => 'boolean',
            'is_visible'    => 'boolean',
            'sort_order'    => 'integer|min:0',
        ]);

        $data['icon'] = $data['icon'] ?? $service->icon ?? '🔨';

        // Regénère le slug si le nom a changé
        if ($data['name'] !== $service->name) {
            $newSlug = Str::slug($data['name']);
            $data['slug'] = Service::where('slug', $newSlug)
                ->where('id', '!=', $service->id)
                ->exists()
                ? $newSlug . '-' . uniqid()
                : $newSlug;
        }

        $service->update($data);

        $service = $service->fresh();

        if ($service->is_suggestion && $oldName !== $service->name) {
            $this->syncSuggestionContractors($service, $oldName);
        }

        return response()->json($this->formatForAdmin($service));
    }

    /**
     * PATCH /admin/services/{service}/status
     * Change le statut d'un service : active | archived | pending | rejected
     */
    public function updateStatus(Request $request, Service $service): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['active', 'pending', 'archived', 'rejected'])],
        ]);

        $service->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'id'      => $service->id,
            'status'  => $service->status,
        ]);
    }

    /**
     * DELETE /admin/services/{service}
     * Supprime définitivement un service (soft-delete non activé volontairement).
     * Préférer l'archivage dans la pratique.
     */
    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['success' => true, 'id' => $service->id]);
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════

    private function formatForAdmin(Service $s, array $counts = []): array
    {
        return [
            'id'                 => $s->id,
            'name'               => $s->name,
            'slug'               => $s->slug,
            'icon'               => $s->icon,
            'description'        => $s->description,
            'category'           => $s->category,
            'accreditation'      => $s->accreditation,
            'admin_only'         => $s->admin_only,
            'is_suggestion'      => $s->is_suggestion,
            'is_visible'         => $s->is_visible,
            'sort_order'         => $s->sort_order,
            'status'             => $s->status,
            'created_at_label'   => $s->created_at?->format('d/m/Y'),
            // Counts calculés dynamiquement par matching du nom
            'contractors_count'  => $counts['contractors_count']  ?? 0,
            'missions_count'     => $counts['missions_count']      ?? 0,
            'missions_completed' => $counts['missions_completed']  ?? 0,
        ];
    }

    private function syncSuggestionContractors(Service $service, string $oldName): void
    {
        $oldKey = mb_strtolower(trim($oldName));

        Contractor::where('service_id', $service->id)
            ->orWhereRaw('LOWER(specialty) = ?', [$oldKey])
            ->get()
            ->each(function (Contractor $contractor) use ($service, $oldKey) {
                $updates = [
                    'service_id' => $service->id,
                    'specialty'  => $service->name,
                ];

                $specialties = collect($contractor->specialties ?? [])
                    ->map(fn ($specialty) => mb_strtolower(trim($specialty)) === $oldKey ? $service->name : $specialty)
                    ->values()
                    ->all();

                if ($specialties !== ($contractor->specialties ?? [])) {
                    $updates['specialties'] = $specialties;
                }

                $contractor->update($updates);
            });
    }
}
