{{-- resources/views/pages/back/client/dashboard.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon espace client')

@php $active = 'dashboard'; @endphp

@section('content')

    <client-dashboard-component
        :user="{{ json_encode([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :client-profile="{{ json_encode([
            'first_name'   => $client?->first_name   ?? '',
            'last_name'    => $client?->last_name    ?? '',
            'phone'        => $client?->phone        ?? '',
            'address'      => $client?->address      ?? '',
            'city'         => $client?->city         ?? 'Cotonou',
            'account_type' => $client?->account_type ?? 'individual',
            'company_name' => $client?->company_name ?? null,
        ]) }}"
        :routes="{{ json_encode([
            'missions_index'         => route('client.missions.index'),
            'missions_store'         => route('client.missions.store'),
            'missions_status'        => url('/client/missions/{id}/status'),
            'notifications'          => route('notifications.index'),
            'notifications_read'     => url('/notifications/{id}/read'),
            'notifications_all'      => route('notifications.read-all'),
            'conversations_mission'  => url('/conversations/mission/{id}'),
            'conversations_messages' => url('/conversations/{id}/messages'),
            'conversations_send'     => url('/conversations/{id}/messages'),
            'conversations_attach'   => url('/conversations/{id}/attachment'),
            'conversations_read'     => url('/conversations/{id}/read'),
        ]) }}"
    ></client-dashboard-component>

@endsection