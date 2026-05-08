<?php

namespace App\Http\Controllers;

use App\Models\AccreditationRequest;
use App\Models\Contractor;
use App\Models\Mission;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccreditationRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1500'],
        ]);

        $user = Auth::user();
        $contractor = $user->contractor;

        if (!$contractor) {
            return response()->json(['message' => 'Profil prestataire introuvable.'], 404);
        }

        if (in_array($contractor->accreditation, ['business', 'both'], true)) {
            return response()->json(['message' => "Vous avez déjà l'accréditation Entreprise."], 422);
        }

        $completedCount = $this->completedMissionsCount($contractor);
        if ($completedCount < 5) {
            return response()->json([
                'message' => "Vous devez terminer 5 missions avant de demander l'accréditation Entreprise.",
                'completed_missions' => $completedCount,
            ], 422);
        }

        $pending = AccreditationRequest::where('contractor_id', $contractor->id)
            ->where('type', AccreditationRequest::TYPE_BUSINESS)
            ->where('status', AccreditationRequest::STATUS_PENDING)
            ->first();

        if ($pending) {
            return response()->json([
                'success' => true,
                'request' => $this->formatRequest($pending),
                'message' => 'Une demande est déjà en attente.',
            ]);
        }

        $accreditationRequest = AccreditationRequest::create([
            'contractor_id' => $contractor->id,
            'user_id' => $user->id,
            'type' => AccreditationRequest::TYPE_BUSINESS,
            'status' => AccreditationRequest::STATUS_PENDING,
            'message' => $data['message'] ?? null,
        ]);

        User::where('role', 'admin')->each(fn(User $admin) => $admin->notify(new AppNotification(
            event: 'accreditation.requested',
            title: 'Demande accréditation Entreprise',
            body: $user->name . " demande l'accréditation Entreprise.",
            url: route('admin.accreditation'),
            icon: 'award',
            extra: ['accreditation_request_id' => $accreditationRequest->id],
        )));

        return response()->json([
            'success' => true,
            'request' => $this->formatRequest($accreditationRequest),
        ], 201);
    }

    public function adminIndex(): JsonResponse
    {
        $requests = AccreditationRequest::with(['contractor.user.documents', 'contractor.service', 'reviewer'])
            ->latest()
            ->get()
            ->map(fn(AccreditationRequest $request) => $this->formatRequestForAdmin($request));

        return response()->json(['data' => $requests]);
    }

    public function adminUpdate(Request $request, AccreditationRequest $accreditationRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:1500', 'required_if:status,rejected'],
        ]);

        if ($accreditationRequest->status !== AccreditationRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $contractor = $accreditationRequest->contractor;

        if ($data['status'] === AccreditationRequest::STATUS_APPROVED) {
            $contractor->update([
                'accreditation' => in_array($contractor->accreditation, ['home', 'none'], true) ? 'both' : 'business',
            ]);
        }

        $accreditationRequest->update([
            'status' => $data['status'],
            'admin_reason' => $data['reason'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $contractor->user?->notify(new AppNotification(
            event: 'accreditation.reviewed',
            title: $data['status'] === AccreditationRequest::STATUS_APPROVED
                ? 'Accréditation Entreprise acceptée'
                : 'Accréditation Entreprise refusée',
            body: $data['status'] === AccreditationRequest::STATUS_APPROVED
                ? "Votre demande d'accréditation Entreprise a été acceptée."
                : "Votre demande d'accréditation Entreprise a été refusée. Motif : " . ($data['reason'] ?? 'Non précisé.'),
            url: route('contractor.accreditation'),
            icon: 'award',
            extra: ['accreditation_request_id' => $accreditationRequest->id],
        ));

        return response()->json([
            'success' => true,
            'request' => $this->formatRequestForAdmin($accreditationRequest->fresh(['contractor.user.documents', 'contractor.service', 'reviewer'])),
        ]);
    }

    private function completedMissionsCount(Contractor $contractor): int
    {
        $dbCount = Mission::where('contractor_id', $contractor->id)
            ->whereIn('status', [Mission::STATUS_COMPLETED, Mission::STATUS_CLOSED])
            ->count();

        return max((int) $contractor->completed_missions, $dbCount);
    }

    private function formatRequest(AccreditationRequest $request): array
    {
        return [
            'id' => $request->id,
            'type' => $request->type,
            'status' => $request->status,
            'message' => $request->message,
            'admin_reason' => $request->admin_reason,
            'created_at' => $request->created_at?->toISOString(),
            'reviewed_at' => $request->reviewed_at?->toISOString(),
        ];
    }

    private function formatRequestForAdmin(AccreditationRequest $request): array
    {
        $contractor = $request->contractor;
        $user = $contractor?->user;

        $missions = $contractor
            ? Mission::with('client.user')
                ->where('contractor_id', $contractor->id)
                ->latest()
                ->take(20)
                ->get()
                ->map(fn(Mission $mission) => $this->formatMissionForRequest($mission))
                ->values()
            : collect();

        return [
            ...$this->formatRequest($request),
            'reviewer_name' => $request->reviewer?->name,
            'contractor' => [
                'id' => $contractor?->id,
                'user_id' => $user?->id,
                'name' => trim(($contractor?->first_name ?? '') . ' ' . ($contractor?->last_name ?? '')) ?: $user?->name,
                'email' => $user?->email,
                'phone' => $contractor?->phone,
                'status' => $user?->status,
                'specialty' => $contractor?->specialty,
                'service' => $contractor?->service?->name,
                'city' => $contractor?->city,
                'address' => $contractor?->address,
                'intervention_zone' => $contractor?->intervention_zone,
                'experience_years' => $contractor?->experience_years,
                'bio' => $contractor?->bio,
                'accreditation' => $contractor?->accreditation,
                'available' => (bool) ($contractor?->available ?? false),
                'average_rating' => (float) ($contractor?->average_rating ?? 0),
                'reviews_count' => (int) ($contractor?->reviews_count ?? 0),
                'total_missions' => (int) ($contractor?->total_missions ?? 0),
                'completed_missions' => $contractor ? $this->completedMissionsCount($contractor) : 0,
                'created_at' => $contractor?->created_at?->format('d/m/Y'),
                'documents' => ($user?->documents ?? collect())->map(fn($document) => [
                    'id' => $document->id,
                    'type' => $document->type,
                    'status' => $document->status,
                    'filename' => $document->original_name ?? $document->filename ?? null,
                ])->values(),
                'missions' => $missions,
            ],
        ];
    }

    private function formatMissionForRequest(Mission $mission): array
    {
        $fallbackClientName = trim(($mission->client?->first_name ?? '') . ' ' . ($mission->client?->last_name ?? ''));

        return [
            'id' => $mission->id,
            'service' => $mission->service,
            'status' => $mission->status,
            'status_label' => $mission->status_label,
            'total_amount' => $mission->total_amount,
            'created_at' => $mission->created_at?->format('d/m/Y'),
            'completed_at' => $mission->completed_at?->format('d/m/Y'),
            'client_name' => $mission->client?->user?->name ?: ($fallbackClientName ?: '—'),
        ];
    }
}
