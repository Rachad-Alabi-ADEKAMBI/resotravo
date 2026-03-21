{{-- resources/views/pages/back/client/dashboard.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon espace — Resotravo')

@php $active = 'dashboard'; @endphp

@section('content')
    <client-dashboard-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status, 'phone' => $user->client?->phone, 'city' => $user->client?->city]) }}"
        :doc-progress-data="{{ json_encode($docProgressData) }}"
        :routes="{{ json_encode($routes) }}"
    ></client-dashboard-component>
@endsection