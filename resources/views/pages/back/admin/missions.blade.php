{{-- resources/views/pages/back/admin/missions.blade.php --}}
@extends('layouts.back')

@section('title', 'Gestion des missions')

@php $active = 'missions'; @endphp

@section('content')

    <admin-mission-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'missions_index'        => route('admin.missions.index'),
            'missions_show'         => url('/admin/missions/{id}'),
            'missions_propose'      => url('/admin/missions/{id}/propose'),
            'missions_cancel'       => url('/admin/missions/{id}/status'),
            'contractors_available' => route('admin.contractors.available'),
            'notifications'         => route('notifications.index'),
            'notifications_read'    => url('/notifications/{id}/read'),
            'notifications_all'     => route('notifications.read-all'),
        ]) }}"
    ></admin-mission-component>

@endsection