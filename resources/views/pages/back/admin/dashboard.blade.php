{{-- resources/views/pages/back/admin/dashboard.blade.php --}}
@extends('layouts.back')

@section('title', 'Tableau de bord Admin')

@section('content')

    <admin-dashboard-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'stats'                     => route('admin.stats'),
            'contractors_pending'       => route('admin.contractors.pending'),
            'contractors_index'         => route('admin.contractors.index'),
            'contractors_approve'       => url('/admin/contractors/{id}/status'),
            'contractors_accreditation' => url('/admin/contractors/{id}/accreditation'),
            'documents_approve'         => url('/admin/documents/{id}/status'),
            'missions_index'            => route('admin.missions.index'),
            'missions_show'             => url('/admin/missions/{id}'),
            'notifications'             => route('notifications.index'),
            'notifications_read'        => url('/notifications/{id}/read'),
            'notifications_all'         => route('notifications.read-all'),
        ]) }}"
        :initial-stats="{{ json_encode($stats ?? []) }}"
    ></admin-dashboard-component>

@endsection