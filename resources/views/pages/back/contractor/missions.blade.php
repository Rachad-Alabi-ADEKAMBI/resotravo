{{-- resources/views/pages/back/contractor/missions.blade.php --}}
@extends('layouts.back')
@section('title', 'Mes missions')
@php $active = 'missions'; @endphp

@section('content')
<contractor-mission-component
    :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'role' => $user->role]) }}"
    :routes="{{ json_encode([
        'missions_index'         => route('contractor.missions.index'),
        'missions_status'        => url('/contractor/missions/{id}/status'),
        'missions_quote_store'   => url('/contractor/missions/{id}/quote'),
        'notifications'          => route('notifications.index'),
        'notifications_read'     => url('/notifications/{id}/read'),
        'notifications_all'      => route('notifications.read-all'),
        'conversations_mission'  => url('/conversations/mission/{id}'),
        'conversations_messages' => url('/conversations/{id}/messages'),
        'conversations_send'     => url('/conversations/{id}/messages'),
        'conversations_attach'   => url('/conversations/{id}/attachment'),
        'conversations_read'     => url('/conversations/{id}/read'),
        'unread_summary'         => route('unread-messages'),
    ]) }}"
></contractor-mission-component>
@endsection