{{-- resources/views/pages/back/client/missions.blade.php --}}
@extends('layouts.back')

@section('title', 'Mes commandes — Resotravo')

@php $active = 'missions'; @endphp

@section('content')
    <client-mission-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :client-profile="{{ json_encode([
            'id'           => $client?->id,
            'first_name'   => $client?->first_name,
            'last_name'    => $client?->last_name,
            'phone'        => $client?->phone,
            'city'         => $client?->city,
            'account_type' => $client?->account_type ?? 'individual',
            'company_name' => $client?->company_name,
        ]) }}"
        :doc-progress-data="{{ json_encode($docProgress) }}"
        :user-status="{{ json_encode($user->status) }}"
        :routes="{{ json_encode($routes) }}"
    ></client-mission-component>
@endsection