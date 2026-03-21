{{-- resources/views/pages/back/contractor/dossier.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon dossier — Resotravo')

@php $active = 'dossier'; @endphp

@section('content')
    <dossier-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :routes="{{ json_encode($routes) }}"
    ></dossier-component>
@endsection