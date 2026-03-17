<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\User;
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
            'phone'        => ['required', 'string', 'unique:clients,phone'],
            'address'      => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'required_if:account_type,company', 'string', 'max:255'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 1. Créer le User avec role = client
        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'client',
            'status'   => 'approved', // client approuvé directement
        ]);

        // 2. Créer le profil Client
        Client::create([
            'user_id'      => $user->id,
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone'        => $request->phone,
            'address'      => $request->address,
            'city'         => 'Cotonou',
            'account_type' => $request->account_type,
            'company_name' => $request->account_type === 'company' ? $request->company_name : null,
        ]);

        // 3. Connecter directement
        Auth::login($user);

        return response()->json([
            'message'  => 'Account created successfully.',
            'redirect' => route('client.dashboard'),
            'user'     => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
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
            'phone'             => ['required', 'string', 'unique:contractors,phone'],
            'specialty'         => ['required', 'string', 'max:100'],
            'intervention_zone' => ['required', 'string', 'max:255'],
            'experience_years'  => ['required', 'integer', 'min:0', 'max:60'],
            'city'              => ['required', 'string', 'max:100'],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'specialties'       => ['nullable', 'array'],
            'specialties.*'     => ['string', 'max:100'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 1. Créer le User avec role = contractor
        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'contractor',
            'status'   => 'pending', // en attente validation documents
        ]);

        // 2. Créer le profil Contractor
        Contractor::create([
            'user_id'           => $user->id,
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'phone'             => $request->phone,
            'city'              => $request->city,
            'bio'               => $request->bio,
            'specialty'         => $request->specialty,
            'specialties'       => $request->specialties ?? [],
            'intervention_zone' => $request->intervention_zone,
            'experience_years'  => $request->experience_years,
        ]);

        // 3. Connecter directement
        Auth::login($user);

        return response()->json([
            'message'  => 'Account created successfully. Please upload your documents.',
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
}