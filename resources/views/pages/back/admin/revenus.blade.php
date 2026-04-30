{{-- resources/views/pages/back/admin/revenus.blade.php --}}
@extends('layouts.back')

@section('title', 'Revenus & Finances — Mesotravo Admin')

@php $active = 'revenus'; @endphp

@section('content')
    <admin-revenus-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-revenus-component>
@endsection