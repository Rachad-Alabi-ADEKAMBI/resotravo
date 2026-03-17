<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContractorController extends Controller
{
    /**
     * Dashboard prestataire
     * GET /contractor/dashboard
     */
    public function dashboardIndex()
    {
        $user       = Auth::user()->load('contractor');
        $contractor = $user->contractor;

        return view('pages.back.contractor.dashboard', [
            'active'      => 'dashboard',
            'user'        => $user,
            'contractor'  => $contractor,
            'docProgress' => $user->documentProgress(),
            'documents'   => $user->documents()
                ->get()
                ->map(fn($doc) => [
                    'type'   => $doc->type,
                    'label'  => $doc->label,
                    'icon'   => $doc->icon,
                    'status' => $doc->status,
                ]),
        ]);
    }

    /**
     * Afficher le profil du prestataire connecté
     * GET /contractor/profil
     */
    public function show()
    {
        $contractor = Contractor::where('user_id', Auth::id())
            ->with(['user.documents'])
            ->firstOrFail();

        return response()->json([
            'contractor'        => $contractor,
            'documents'         => $contractor->user->documents,
            'document_progress' => $contractor->user->documentProgress(),
            'missing_documents' => $contractor->user->missingDocuments(),
            'is_certified'      => $contractor->user->isCertified(),
        ]);
    }

    /**
     * Créer le profil prestataire
     * POST /contractor/profil
     */
    public function store(Request $request)
    {
        if (Contractor::where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Profile already exists.'], 422);
        }

        $data = $request->validate([
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'phone'             => ['required', 'string', 'unique:contractors,phone'],
            'city'              => ['required', 'string', 'max:100'],
            'specialty'         => ['required', 'string', 'max:100'],
            'specialties'       => ['nullable', 'array'],
            'specialties.*'     => ['string', 'max:100'],
            'intervention_zone' => ['required', 'string', 'max:255'],
            'experience_years'  => ['required', 'integer', 'min:0', 'max:60'],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'radius_km'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'working_days'      => ['nullable', 'array'],
            'start_time'        => ['nullable', 'date_format:H:i'],
            'end_time'          => ['nullable', 'date_format:H:i'],
        ]);

        $contractor = Contractor::create([...$data, 'user_id' => Auth::id()]);

        return response()->json([
            'message'    => 'Profile created successfully.',
            'contractor' => $contractor,
        ], 201);
    }

    /**
     * Mettre à jour le profil
     * PUT /contractor/profil
     */
    public function update(Request $request)
    {
        $contractor = Contractor::where('user_id', Auth::id())->firstOrFail();

        $data = $request->validate([
            'first_name'        => ['sometimes', 'string', 'max:100'],
            'last_name'         => ['sometimes', 'string', 'max:100'],
            'phone'             => ['sometimes', 'string', 'unique:contractors,phone,' . $contractor->id],
            'city'              => ['sometimes', 'string', 'max:100'],
            'specialty'         => ['sometimes', 'string', 'max:100'],
            'specialties'       => ['nullable', 'array'],
            'specialties.*'     => ['string', 'max:100'],
            'intervention_zone' => ['sometimes', 'string', 'max:255'],
            'experience_years'  => ['sometimes', 'integer', 'min:0', 'max:60'],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'radius_km'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'working_days'      => ['nullable', 'array'],
            'start_time'        => ['nullable', 'date_format:H:i'],
            'end_time'          => ['nullable', 'date_format:H:i'],
        ]);

        $contractor->update($data);

        return response()->json([
            'message'    => 'Profile updated.',
            'contractor' => $contractor->fresh(),
        ]);
    }

    /**
     * Mettre à jour la photo de profil
     * POST /contractor/photo
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $contractor = Contractor::where('user_id', Auth::id())->firstOrFail();

        if ($contractor->profile_picture) {
            Storage::disk('public')->delete($contractor->profile_picture);
        }

        $path = $request->file('photo')->store('photos/contractors', 'public');
        $contractor->update(['profile_picture' => $path]);

        return response()->json([
            'message' => 'Photo updated.',
            'url'     => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Mettre à jour la disponibilité
     * PATCH /contractor/disponibilite
     */
    public function updateDisponibilite(Request $request)
    {
        $request->validate([
            'available'   => ['required', 'boolean'],
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i'],
            'working_days'=> ['nullable', 'array'],
        ]);

        $contractor = Contractor::where('user_id', Auth::id())->firstOrFail();
        $contractor->update($request->only(['available', 'start_time', 'end_time', 'working_days']));

        return response()->json([
            'message'   => 'Availability updated.',
            'available' => $contractor->available,
        ]);
    }

    /**
     * Mettre à jour la position GPS
     * PATCH /contractor/position
     */
    public function updatePosition(Request $request)
    {
        $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $contractor = Contractor::where('user_id', Auth::id())->firstOrFail();
        $contractor->update([
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json(['message' => 'Location updated.']);
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    /**
     * Lister tous les prestataires (admin)
     * GET /admin/contractors
     */
    public function adminIndex(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $query = Contractor::with('user')
            ->when($request->specialty,     fn($q) => $q->bySpecialty($request->specialty))
            ->when($request->city,          fn($q) => $q->byCity($request->city))
            ->when($request->status,        fn($q) => $q->whereHas('user', fn($u) => $u->where('status', $request->status)))
            ->when($request->accreditation, fn($q) => $q->where('accreditation', $request->accreditation))
            ->when($request->search,        fn($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('specialty',  'like', "%{$request->search}%");
            }))
            ->orderByDesc('created_at');

        return response()->json($query->paginate(20));
    }

    /**
     * Attribuer une accréditation (admin)
     * PATCH /admin/contractors/{contractor}/accreditation
     */
    public function updateAccreditation(Request $request, Contractor $contractor)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $request->validate([
            'accreditation' => ['required', 'in:' . implode(',', array_keys(Contractor::$accreditations))],
        ]);

        $contractor->update(['accreditation' => $request->accreditation]);

        return response()->json([
            'message'       => 'Accreditation updated.',
            'accreditation' => $contractor->accreditation_label,
        ]);
    }

    /**
     * Changer le statut d'un prestataire (admin)
     * PATCH /admin/contractors/{contractor}/statut
     */
    public function updateStatut(Request $request, Contractor $contractor)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $contractor->user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status updated.',
            'status'  => $request->status,
        ]);
    }
}