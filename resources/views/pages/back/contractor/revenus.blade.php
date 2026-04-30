{{-- resources/views/pages/back/contractor/revenus.blade.php --}}
@extends('layouts.back')

@section('title', 'Mes revenus — Mesotravo')

@php $active = 'revenus'; @endphp

@section('content')
    <contractor-revenus-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :routes="{{ json_encode($routes) }}"
    ></contractor-revenus-component>
@endsection