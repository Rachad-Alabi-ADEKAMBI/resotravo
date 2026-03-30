{{-- resources/views/pages/back/contractor/accreditation.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon accréditation — Resotravo')

@php $active = 'accreditation'; @endphp

@section('content')
    <contractor-accreditation-component
        :user="{{ json_encode([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]) }}"
        :contractor="{{ json_encode([
            'accreditation' => $contractor?->accreditation ?? 'none',
            'status'        => $user->status ?? 'pending',
        ]) }}"
        :routes="{{ json_encode($routes) }}"
    ></contractor-accreditation-component>
@endsection