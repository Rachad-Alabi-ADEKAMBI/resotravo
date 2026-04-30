{{-- resources/views/pages/back/client/dashboard.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon espace — Mesotravo')

@php $active = 'dashboard'; @endphp

@section('content')
    <client-dashboard-component
        :user="{{ json_encode([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ]) }}"
        :client-profile="{{ json_encode([
            'first_name'          => $user->client?->first_name,
            'last_name'           => $user->client?->last_name,
            'phone'               => $user->client?->phone,
            'address'             => $user->client?->address,
            'city'                => $user->client?->city ?? 'Cotonou',
            'account_type'        => $user->client?->account_type ?? 'individual',
            'company_name'        => $user->client?->company_name,
            'completed_missions'  => $completedMissions,
        ]) }}"
        :doc-progress-data="{{ json_encode($docProgressData) }}"
        :user-status="{{ json_encode($userStatus) }}"
        :routes="{{ json_encode($routes) }}"
    ></client-dashboard-component>
@endsection