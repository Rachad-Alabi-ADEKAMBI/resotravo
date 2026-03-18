{{-- resources/views/pages/back/admin/accreditation.blade.php --}}
@extends('layouts.back')

@section('title', 'Accréditations prestataires')

@php $active = 'accreditation'; @endphp

@section('content')

    <admin-accreditation-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'contractors_index'         => route('admin.contractors.index'),
            'contractors_accreditation' => url('/admin/contractors/{id}/accreditation'),
            'notifications'             => route('notifications.index'),
            'notifications_read'        => url('/notifications/{id}/read'),
            'notifications_all'         => route('notifications.read-all'),
        ]) }}"
    ></admin-accreditation-component>

@endsection