{{-- resources/views/pages/back/admin/configuration.blade.php --}}
@extends('layouts.back')

@section('title', 'Configuration — Mesotravo')

@php $active = 'configuration'; @endphp

@section('content')

    <admin-configuration-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'settings_index'     => route('admin.configuration.settings'),
            'settings_update'    => route('admin.configuration.settings.update'),
            'notifications'      => route('notifications.index'),
            'notifications_all'  => route('notifications.read-all'),
        ]) }}"
    ></admin-configuration-component>

@endsection
