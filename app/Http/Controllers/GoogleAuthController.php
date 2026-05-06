<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Service;
use App\Models\User;
use App\Services\AdminTemplateMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirige vers Google OAuth.
     * Le paramètre ?role=client|contractor est conservé dans le state.
     */
    public function redirect(Request $request)
    {
        $role = in_array($request->query('role'), ['client', 'contractor'])
            ? $request->query('role')
            : 'client';
        $intent = $request->query('intent') === 'login' ? 'login' : 'register';

        session([
            'google_intended_role' => $role,
            'google_intent' => $intent,
        ]);

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Callback Google.
     * - Utilisateur existant → connexion directe
     * - Nouvel utilisateur  → stocke les données Google en session
     *                         et redirige vers le formulaire de complétion
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['google' => 'Authentification Google échouée. Réessayez.']);
        }

        // Utilisateur déjà inscrit ?
        $existing = User::where('email', $googleUser->getEmail())->first();

        if ($existing) {
            Auth::login($existing, true);
            return redirect($this->dashboardRoute($existing->role));
        }

        // Nouveau → stocker dans session + rediriger vers formulaire
        session([
            'google_user' => [
                'email'      => $googleUser->getEmail(),
                'name'       => $googleUser->getName(),
                'avatar'     => $googleUser->getAvatar(),
                'google_id'  => $googleUser->getId(),
                'first_name' => $this->extractFirstName($googleUser->getName()),
                'last_name'  => $this->extractLastName($googleUser->getName()),
            ],
            'google_intended_role' => session('google_intended_role', 'client'),
        ]);

        if (session('google_intent') === 'login') {
            return redirect()->route('auth.google.not-found');
        }

        return redirect()->route('auth.google.complete');
    }

    /**
     * Affiche le formulaire de complétion d'inscription.
     */
    public function showNotFound()
    {
        if (! session('google_user')) {
            return redirect()->route('login');
        }

        $googleUser = session('google_user');

        return view('auth.google-not-found', compact('googleUser'));
    }

    public function showComplete(Request $request)
    {
        if (! session('google_user')) {
            return redirect()->route('login');
        }

        if (in_array($request->query('role'), ['client', 'contractor'], true)) {
            session(['google_intended_role' => $request->query('role')]);
        }

        $role = session('google_intended_role', 'client');
        $googleUser = session('google_user');
        $services = Service::visible()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon', 'description', 'category', 'sort_order']);
        $oldServiceId = old('service_id');
        if ($oldServiceId && ! $services->contains('id', (int) $oldServiceId)) {
            $oldService = Service::query()
                ->whereKey($oldServiceId)
                ->first(['id', 'name', 'slug', 'icon', 'description', 'category', 'sort_order']);

            if ($oldService) {
                $services->push($oldService);
            }
        }
        $routes = [
            'service_suggestions' => route('services.suggestions'),
        ];

        return view('auth.google-complete', compact('googleUser', 'role', 'services', 'routes'));
    }

    /**
     * Finalise l'inscription après Google.
     */
    public function storeComplete(Request $request)
    {
        if (! session('google_user')) {
            return redirect()->route('login');
        }

        $googleUser = session('google_user');
        $role = in_array($request->input('role'), ['client', 'contractor'], true)
            ? $request->input('role')
            : session('google_intended_role', 'client');
        session(['google_intended_role' => $role]);

        $rules = [
            'role'       => 'required|in:client,contractor',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => ['required', 'regex:/^01[0-9]{8}$/', 'unique:clients,phone', 'unique:contractors,phone'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($role === 'client') {
            $rules['account_type'] = 'required|in:individual,company';
            $rules['address']      = 'required|string|max:255';
            $rules['company_name'] = 'nullable|required_if:account_type,company|string|max:255';
        } else {
            $rules['service_id']        = 'nullable|integer|exists:services,id';
            $rules['specialty']         = 'required|string|max:100';
            $rules['specialties']       = 'nullable|array';
            $rules['specialties.*']     = 'string|max:100';
            $rules['intervention_zone'] = 'required|string|max:255';
            $rules['experience_years']  = 'required|integer|min:0|max:60';
            $rules['city']              = 'required|string|max:100';
            $rules['bio']               = 'nullable|string|max:1000';
        }

        $rules['cgv'] = 'accepted';

        $validator = Validator::make($request->all(), $rules, $this->validationMessages());

        if ($validator->fails()) {
            return redirect()
                ->route('auth.google.complete')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        $firstName = $this->formatFirstNames($data['first_name']);
        $lastName  = $this->formatLastNames($data['last_name']);

        if (User::where('email', $googleUser['email'])->exists()) {
            session()->forget(['google_user', 'google_intended_role', 'google_intent']);
            return redirect()->route('login')->withErrors(['email' => 'This account already exists.']);
        }

        $user = User::create([
            'name'              => $firstName . ' ' . $lastName,
            'email'             => $googleUser['email'],
            'password'          => Hash::make($data['password']),
            'role'              => $role,
            'status'            => 'pending',
            'google_id'         => $googleUser['google_id'],
            'email_verified_at' => now(),
        ]);

        if ($role === 'client') {
            Client::create([
                'user_id'      => $user->id,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone'        => trim($data['phone']),
                'address'      => trim($data['address']),
                'city'         => 'Cotonou',
                'account_type' => $data['account_type'],
                'company_name' => ($data['account_type'] === 'company') ? trim($data['company_name'] ?? '') : null,
            ]);
        } else {
            Contractor::create([
                'user_id'           => $user->id,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'phone'             => trim($data['phone']),
                'city'              => trim($data['city']),
                'service_id'        => $data['service_id'] ?? null,
                'specialty'         => trim($data['specialty']),
                'specialties'       => $data['specialties'] ?? [],
                'intervention_zone' => trim($data['intervention_zone']),
                'experience_years'  => $data['experience_years'],
                'bio'               => isset($data['bio']) && trim($data['bio']) !== '' ? trim($data['bio']) : null,
                'accreditation'     => 'home',
            ]);
        }

        app(AdminTemplateMailService::class)->sendWelcomeMail(
            $user,
            $role === 'contractor' ? 'Email après inscription prestataire' : 'Email après inscription client',
            $this->dashboardRoute($role)
        );
        app(AdminTemplateMailService::class)->sendAdminRegistrationNotification($user);

        session()->forget(['google_user', 'google_intended_role', 'google_intent']);

        Auth::login($user, true);

        return redirect($this->dashboardRoute($role));
    }

    private function dashboardRoute(string $role): string
    {
        return match($role) {
            'contractor' => route('contractor.dashboard'),
            'admin'      => route('admin.dashboard'),
            default      => route('client.dashboard'),
        };
    }

    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? $fullName;
    }

    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        array_shift($parts);
        return implode(' ', $parts) ?: $fullName;
    }

    private function formatFirstNames(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function formatLastNames(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return mb_strtoupper($value, 'UTF-8');
    }

    private function validationMessages(): array
    {
        return [
            'phone.required' => 'Veuillez renseigner votre numéro de téléphone.',
            'phone.regex' => 'Veuillez saisir exactement 8 chiffres après +229 01.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'email.unique' => 'This email address is already registered.',
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'account_type.required' => 'Please choose an account type.',
            'address.required' => 'Please enter your address.',
            'company_name.required_if' => 'Please enter your company name.',
            'specialty.required' => 'Please choose a service category.',
            'intervention_zone.required' => 'Please enter your intervention area.',
            'experience_years.required' => 'Please enter your years of experience.',
            'city.required' => 'Please enter your city.',
            'cgv.accepted' => 'Veuillez ouvrir et accepter les CGU avant de créer votre compte.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
