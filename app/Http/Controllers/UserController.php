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

class UserController extends Controller
{
    /**
     * Inscription Client
     * POST /register/client/store
     */
    public function storeClient(Request $request)
    {
        $request->validate([
            'account_type' => ['required', 'in:individual,company'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'phone'        => ['required', 'string', 'unique:clients,phone', 'unique:contractors,phone'],
            'address'      => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'required_if:account_type,company', 'string', 'max:255'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->validationMessages());

        // Formatage : prénom avec 1ère lettre majuscule, nom de famille tout en majuscules
        $firstName = $this->formatFirstNames($request->first_name);
        $lastName  = $this->formatLastNames($request->last_name);

        // 1. Créer le User avec role = client
        $user = User::create([
            'name'     => $firstName . ' ' . $lastName,
            'email'    => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role'     => 'client',
            'status'   => 'pending', // en attente validation documents
        ]);

        // 2. Créer le profil Client
        Client::create([
            'user_id'      => $user->id,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone'        => trim($request->phone),
            'address'      => trim($request->address),
            'city'         => 'Cotonou',
            'account_type' => $request->account_type,
            'company_name' => $request->account_type === 'company' ? trim($request->company_name) : null,
        ]);

        app(AdminTemplateMailService::class)->sendWelcomeMail(
            $user,
            'Email après inscription client',
            route('client.dashboard')
        );
        app(AdminTemplateMailService::class)->sendAdminRegistrationNotification($user);

        // 3. Connecter directement
        Auth::login($user);

        return response()->json([
            'message'  => "Compte créé avec succès, merci d'uploader vos documents",
            'redirect' => route('client.dashboard'),
            'user'     => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ],
        ], 201);
    }

    /**
     * Inscription Prestataire (Contractor)
     * POST /register/contractor/store
     */
    public function storeContractor(Request $request)
    {
        $request->validate([
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'unique:users,email'],
            'phone'             => ['required', 'string', 'unique:clients,phone', 'unique:contractors,phone'],
            'specialty'         => ['required', 'string', 'max:100'],
            'service_id'        => ['nullable', 'integer', 'exists:services,id'],
            'intervention_zone' => ['required', 'string', 'max:255'],
            'experience_years'  => ['required', 'integer', 'min:0', 'max:60'],
            'city'              => ['required', 'string', 'max:100'],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'specialties'       => ['nullable', 'array'],
            'specialties.*'     => ['string', 'max:100'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->validationMessages());

        // Formatage : prénom avec 1ère lettre majuscule, nom de famille tout en majuscules
        $firstName = $this->formatFirstNames($request->first_name);
        $lastName  = $this->formatLastNames($request->last_name);

        // 1. Créer le User avec role = contractor
        $user = User::create([
            'name'     => $firstName . ' ' . $lastName,
            'email'    => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role'     => 'contractor',
            'status'   => 'pending', // en attente validation documents
        ]);

        // 2. Créer le profil Contractor
        $serviceId = $request->service_id
            ?: Service::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($request->specialty))])->value('id');

        Contractor::create([
            'user_id'           => $user->id,
            'service_id'        => $serviceId,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'phone'             => trim($request->phone),
            'city'              => trim($request->city),
            'bio'               => $request->bio ? trim($request->bio) : null,
            'specialty'         => trim($request->specialty),
            'specialties'       => $request->specialties ?? [],
            'intervention_zone' => trim($request->intervention_zone),
            'experience_years'  => $request->experience_years,
            'accreditation'     => 'home',
        ]);

        app(AdminTemplateMailService::class)->sendWelcomeMail(
            $user,
            'Email après inscription prestataire',
            route('contractor.dashboard')
        );
        app(AdminTemplateMailService::class)->sendAdminRegistrationNotification($user);

        // 3. Connecter directement
        Auth::login($user);

        return response()->json([
            'message'  => "Compte créé avec succès, merci d'uploader vos documents",
            'redirect' => route('contractor.dashboard'),
            'user'     => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ],
        ], 201);
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
            'phone.required' => 'Please enter your phone number.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
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
            'password.required' => 'Please enter your password.',
            'password.min' => 'Your password must contain at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
