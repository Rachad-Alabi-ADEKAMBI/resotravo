{{-- resources/views/pages/back/admin/dashboard.blade.php --}}
@extends('layouts.back')

@section('title', 'Tableau de bord — Resotravo')

@php $active = 'dashboard'; @endphp

@section('content')

    <admin-dashboard-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-dashboard-component>

@endsection