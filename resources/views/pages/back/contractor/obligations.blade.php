{{-- resources/views/pages/back/contractor/obligations.blade.php --}}
@extends('layouts.back')

@section('title', 'Obligations fiscales — Mesotravo')

@php $active = 'obligations'; @endphp

@section('content')
    <contractor-obligations-component
        :user="{{ json_encode([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]) }}"
        :contractor="{{ json_encode([
            'ifu' => $contractor?->ifu ?? '',
        ]) }}"
        :routes="{{ json_encode($routes) }}"
    ></contractor-obligations-component>
@endsection
