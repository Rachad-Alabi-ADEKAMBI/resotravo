<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        session(['google_intended_role' => $role]);

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

        return redirect()->route('auth.google.complete');
    }

    /**
     * Affiche le formulaire de complétion d'inscription.
     */
    public function showComplete()
    {
        if (! session('google_user')) {
            return redirect()->route('login');
        }

        $role = session('google_intended_role', 'client');
        $googleUser = session('google_user');

        return view('auth.google-complete', compact('googleUser', 'role'));
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
        $role       = session('google_intended_role', 'client');

        $rules = [
            'role'       => 'required|in:client,contractor',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|max:20',
        ];

        if ($role === 'client') {
            $rules['account_type'] = 'required|in:individual,company';
            $rules['address']      = 'required|string|max:255';
            $rules['company_name'] = 'nullable|required_if:account_type,company|string|max:255';
        } else {
            $rules['specialty']         = 'required|string|max:100';
            $rules['intervention_zone'] = 'required|string|max:255';
            $rules['experience_years']  = 'required|integer|min:0|max:60';
            $rules['city']              = 'required|string|max:100';
        }

        $data = $request->validate($rules);

        $firstName = ucfirst(strtolower(trim($data['first_name'])));
        $lastName  = strtoupper(trim($data['last_name']));

        if (User::where('email', $googleUser['email'])->exists()) {
            session()->forget(['google_user', 'google_intended_role']);
            return redirect()->route('login')->withErrors(['email' => 'Ce compte existe déjà.']);
        }

        $user = User::create([
            'name'              => $firstName . ' ' . $lastName,
            'email'             => $googleUser['email'],
            'password'          => Hash::make(Str::random(32)),
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
                'specialty'         => trim($data['specialty']),
                'intervention_zone' => trim($data['intervention_zone']),
                'experience_years'  => $data['experience_years'],
                'accreditation'     => 'home',
            ]);
        }

        session()->forget(['google_user', 'google_intended_role']);

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
}
