{{-- resources/views/pages/back/contractor/obligations.blade.php --}}
@extends('layouts.back')

@section('title', 'Obligations fiscales — Mesotravo')

@php
    $active = 'obligations';
    $hasProfilePhoto = \App\Models\Document::where('user_id', $user->id)
        ->where('type', 'photo')
        ->where('status', 'approved')
        ->exists();
    $profilePhotoUrl = $user->contractor?->profile_picture
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->contractor->profile_picture)
        : ($hasProfilePhoto ? route('profile.photo.user', ['userId' => $user->id]) : null);
@endphp

@section('content')
    <contractor-obligations-component
        :user="{{ json_encode([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'photo_url' => $profilePhotoUrl,
        ]) }}"
        :contractor="{{ json_encode([
            'ifu' => $contractor?->ifu ?? '',
        ]) }}"
        :routes="{{ json_encode($routes) }}"
    ></contractor-obligations-component>
@endsection
