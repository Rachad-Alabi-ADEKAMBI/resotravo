<?php

namespace App\Http\Controllers;

use App\Models\TalentApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TalentController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // PUBLIC
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /talent/list
     * Retourne les talents approuvés pour la page publique.
     */
    public function index(Request $request)
    {
        $query = TalentApplication::approved()
            ->select([
                'id', 'nom', 'prenom', 'title', 'domain',
                'level', 'exp', 'bio', 'availability',
                'linkedin', 'portfolio',
                'diplomas', 'certifications', 'experiences',
            ]);

        if ($request->filled('domain')) {
            $query->where('domain', $request->domain);
        }

        if ($request->filled('level')) {
            $query->where('level', 'like', '%' . $request->level . '%');
        }

        if ($request->filled('availability')) {
            $query->where('availability', $request->availability);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('nom',    'like', "%$q%")
                   ->orWhere('prenom', 'like', "%$q%")
                   ->orWhere('title',  'like', "%$q%")
                   ->orWhere('domain', 'like', "%$q%")
                   ->orWhere('bio',    'like', "%$q%");
            });
        }

        $talents = $query->orderByDesc('id')->get()->map(function ($t) {
            return [
                'id'           => $t->id,
                'name'         => $t->full_name,
                'title'        => $t->title,
                'domain'       => $t->domain,
                'domainIcon'   => $this->domainIcon($t->domain),
                'level'        => $t->level,
                'exp'          => $t->exp,
                'location'     => 'Bénin',
                'bio'          => $t->bio,
                'availability' => $t->availability_label,
                'availClass'   => 'avail-' . $t->availability,
                'score'        => 0,
                'color'        => $this->avatarColor($t->id),
                'skills'       => $this->extractSkills($t),
                'diplomas'     => $t->diplomas  ?? [],
                'experiences'  => $t->experiences ?? [],
                'linkedin'     => $t->linkedin,
                'portfolio'    => $t->portfolio,
            ];
        });

        return response()->json($talents);
    }

    /**
     * POST /talent/apply
     * Soumet une demande pour devenir Talent (sans compte).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'                       => 'required|string|max:100',
            'prenom'                    => 'required|string|max:100',
            'email'                     => 'required|email|max:200',
            'phone'                     => 'required|string|max:30',
            'title'                     => 'required|string|max:200',
            'domain'                    => 'required|string|max:100',
            'level'                     => 'required|string|max:50',
            'exp'                       => 'required|integer|min:0|max:60',
            'bio'                       => 'required|string|max:2000',
            'availability'              => 'required|in:immediate,parttime,mission',
            'linkedin'                  => 'nullable|url|max:300',
            'portfolio'                 => 'nullable|url|max:300',

            'diplomas'                  => 'required|array|min:1|max:3',
            'diplomas.*.title'          => 'required|string|max:200',
            'diplomas.*.school'         => 'required|string|max:200',
            'diplomas.*.year'           => 'required|max:10',

            'certifications'            => 'nullable|array|max:5',
            'certifications.*'          => 'nullable|string|max:200',

            'experiences'               => 'required|array|min:1',
            'experiences.*.role'        => 'required|string|max:200',
            'experiences.*.company'     => 'required|string|max:200',
            'experiences.*.period'      => 'required|string|max:50',
        ], [
            // Champs personnels
            'nom.required'                    => 'Le nom est obligatoire.',
            'nom.max'                         => 'Le nom ne doit pas dépasser 100 caractères.',
            'prenom.required'                 => 'Le prénom est obligatoire.',
            'prenom.max'                      => 'Le prénom ne doit pas dépasser 100 caractères.',
            'email.required'                  => 'L\'adresse email est obligatoire.',
            'email.email'                     => 'L\'adresse email n\'est pas valide.',
            'phone.required'                  => 'Le numéro de téléphone est obligatoire.',

            // Profil
            'title.required'                  => 'Le titre professionnel est obligatoire.',
            'domain.required'                 => 'Le domaine d\'expertise est obligatoire.',
            'level.required'                  => 'Le niveau d\'études est obligatoire.',
            'exp.required'                    => 'Le nombre d\'années d\'expérience est obligatoire.',
            'exp.integer'                     => 'L\'expérience doit être un nombre entier.',
            'exp.min'                         => 'L\'expérience ne peut pas être négative.',
            'exp.max'                         => 'L\'expérience ne peut pas dépasser 60 ans.',
            'bio.required'                    => 'La description "À propos" est obligatoire.',
            'bio.max'                         => 'La description ne doit pas dépasser 2000 caractères.',
            'availability.required'           => 'La disponibilité est obligatoire.',
            'availability.in'                 => 'La disponibilité sélectionnée n\'est pas valide.',

            // Liens
            'linkedin.url'                    => 'Le lien LinkedIn n\'est pas une URL valide.',
            'portfolio.url'                   => 'Le lien portfolio n\'est pas une URL valide.',

            // Diplômes
            'diplomas.required'               => 'Au moins un diplôme est requis.',
            'diplomas.min'                    => 'Veuillez ajouter au moins un diplôme.',
            'diplomas.max'                    => 'Vous ne pouvez pas ajouter plus de 3 diplômes.',
            'diplomas.*.title.required'       => 'L\'intitulé du diplôme est obligatoire.',
            'diplomas.*.school.required'      => 'L\'établissement est obligatoire.',
            'diplomas.*.year.required'        => 'L\'année d\'obtention est obligatoire.',

            // Expériences
            'experiences.required'            => 'Au moins une expérience professionnelle est requise.',
            'experiences.min'                 => 'Veuillez ajouter au moins une expérience.',
            'experiences.*.role.required'     => 'L\'intitulé du poste est obligatoire.',
            'experiences.*.company.required'  => 'Le nom de l\'entreprise est obligatoire.',
            'experiences.*.period.required'   => 'La période est obligatoire.',
        ]);

        // Nettoyer les certifications vides
        $data['certifications'] = array_values(
            array_filter($data['certifications'] ?? [], fn($c) => !empty(trim($c)))
        );

        // Vérifier si une demande pending/approved existe déjà pour cet email
        $exists = TalentApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une demande est déjà en cours ou approuvée pour cet email.',
            ], 422);
        }

        $application = TalentApplication::create($data);

        return response()->json([
            'message' => 'Votre dossier a été soumis. L\'équipe Mesotravo vous contactera sous 48h.',
            'id'      => $application->id,
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /admin/talents
     * Liste toutes les demandes (admin).
     */
    public function adminIndex(Request $request)
    {
        $query = TalentApplication::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->get()->map(function ($a) {
            return [
                'id'           => $a->id,
                'full_name'    => $a->full_name,
                'email'        => $a->email,
                'phone'        => $a->phone,
                'title'        => $a->title,
                'domain'       => $a->domain,
                'level'        => $a->level,
                'exp'          => $a->exp,
                'availability' => $a->availability_label,
                'status'       => $a->status,
                'status_label' => $a->status_label,
                'reject_reason'=> $a->reject_reason,
                'diplomas'     => $a->diplomas,
                'certifications'=> $a->certifications,
                'experiences'  => $a->experiences,
                'created_at'   => $a->created_at->format('d/m/Y'),
            ];
        });

        return response()->json($applications);
    }

    /**
     * PATCH /admin/talents/{application}/status
     * Approuver ou refuser une demande.
     * Body: { status: 'approved'|'rejected', reject_reason?: string }
     */
    public function adminUpdateStatus(Request $request, TalentApplication $application)
    {
        $data = $request->validate([
            'status'        => 'required|in:approved,rejected',
            'reject_reason' => 'nullable|string|max:500',
        ]);

        if ($data['status'] === 'approved' && !$application->user_id) {
            // Vérifier si un compte avec cet email existe déjà
            $existingUser = User::where('email', $application->email)->first();

            if ($existingUser) {
                // Compte existant : le convertir en talent actif
                $existingUser->update(['role' => 'talent', 'status' => 'active']);
                $application->user_id = $existingUser->id;
            } else {
                // Créer un compte user avec rôle talent et statut actif
                $user = User::create([
                    'name'     => $application->full_name,
                    'email'    => $application->email,
                    'password' => Hash::make(Str::random(12)),
                    'role'     => 'talent',
                    'status'   => 'active',
                ]);
                $application->user_id = $user->id;
            }
        }

        $application->status        = $data['status'];
        $application->reject_reason = $data['reject_reason'] ?? null;
        $application->save();

        return response()->json([
            'message' => $data['status'] === 'approved'
                ? 'Talent approuvé. Un compte a été créé.'
                : 'Demande refusée.',
            'application' => $application,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    private function domainIcon(string $domain): string
    {
        return match (true) {
            str_contains($domain, 'Civil')          => '🏗️',
            str_contains($domain, 'Electrique')     => '⚡',
            str_contains($domain, 'Informatique')   => '💻',
            str_contains($domain, 'Architecture')   => '📐',
            str_contains($domain, 'Solaire')        => '☀️',
            str_contains($domain, 'Mecanique')      => '⚙️',
            str_contains($domain, 'Finance')        => '💰',
            str_contains($domain, 'Juridique')      => '⚖️',
            str_contains($domain, 'Telecom')        => '📡',
            str_contains($domain, 'Sante')          => '🏥',
            str_contains($domain, 'Agriculture')    => '🌱',
            str_contains($domain, 'Environnement')  => '🌍',
            str_contains($domain, 'Management')     => '📋',
            str_contains($domain, 'Urbanisme')      => '🏙️',
            default                                  => '🎓',
        };
    }

    private function avatarColor(int $id): string
    {
        $colors = [
            'linear-gradient(135deg, #F97316, #EA580C)',
            'linear-gradient(135deg, #3b82f6, #1d4ed8)',
            'linear-gradient(135deg, #10b981, #059669)',
            'linear-gradient(135deg, #8b5cf6, #6d28d9)',
            'linear-gradient(135deg, #f59e0b, #d97706)',
            'linear-gradient(135deg, #ef4444, #dc2626)',
        ];
        return $colors[$id % count($colors)];
    }

    private function extractSkills(TalentApplication $t): array
    {
        // On retourne les titres de diplômes + certifications comme "skills"
        $skills = [];
        foreach ($t->diplomas ?? [] as $d) {
            if (!empty($d['title'])) $skills[] = $d['title'];
        }
        foreach ($t->certifications ?? [] as $c) {
            if (!empty($c)) $skills[] = $c;
        }
        return array_slice($skills, 0, 6);
    }
}