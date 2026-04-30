{{-- resources/views/pages/back/client/paiements.blade.php --}}
@extends('layouts.back')

@section('title', 'Mes paiements — Mesotravo')

@php $active = 'paiements'; @endphp

@section('content')
    <client-paiements-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :routes="{{ json_encode($routes) }}"
    ></client-paiements-component>
@endsection