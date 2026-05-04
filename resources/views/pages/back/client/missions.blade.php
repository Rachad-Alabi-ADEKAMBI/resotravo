{{-- resources/views/pages/back/client/missions.blade.php --}}
@extends('layouts.back')

@section('title', 'Mes commandes - Mesotravo')

@php
    $active = 'missions';
    $hasProfilePhoto = \App\Models\Document::where('user_id', $user->id)
        ->where('type', 'photo_profil')
        ->where('status', 'approved')
        ->exists();
@endphp

@section('content')
    <client-mission-component
        :user="{{ json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'photo_url' => $hasProfilePhoto ? route('profile.photo.user', ['userId' => $user->id]) : null,
        ]) }}"
        :client-profile="{{ json_encode([
            'id'           => $client?->id,
            'first_name'   => $client?->first_name,
            'last_name'    => $client?->last_name,
            'phone'        => $client?->phone,
            'city'         => $client?->city,
            'profile_picture' => $client?->profile_picture
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($client->profile_picture)
                : ($hasProfilePhoto ? route('profile.photo.user', ['userId' => $user->id]) : null),
            'account_type' => $client?->account_type ?? 'individual',
            'company_name' => $client?->company_name,
        ]) }}"
        :doc-progress-data="{{ json_encode($docProgress) }}"
        :user-status="{{ json_encode($user->status) }}"
        :initial-services="{{ json_encode($services) }}"
        :routes="{{ json_encode($routes) }}"
        :diagnostic-fee="{{ $diagnosticFee }}"
    ></client-mission-component>
@endsection
