{{-- resources/views/pages/back/admin/services.blade.php --}}
@extends('layouts.back')

@section('title', 'Catalogue des Services — Mesotravo')

@php $active = 'services'; @endphp

@section('content')

    <admin-services-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'services_index'   => route('admin.services.index'),
            'services_store'   => route('admin.services.store'),
            'services_update'  => url('/admin/services/{service}'),
            'services_status'  => url('/admin/services/{service}/status'),
            'notifications'    => route('notifications.index'),
            'notifications_read' => url('/notifications/{id}/read'),
            'notifications_all'  => route('notifications.read-all'),
        ]) }}"
    ></admin-services-component>

@endsection