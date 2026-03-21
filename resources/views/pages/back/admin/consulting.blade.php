{{-- resources/views/pages/back/admin/consulting.blade.php --}}
@extends('layouts.back')

@section('title', 'Allo Conseils — Resotravo')

@php $active = 'consulting'; @endphp

@section('content')

    <admin-consulting-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'tickets_index'    => route('admin.consulting.index'),
            'tickets_messages' => url('/admin/consulting/{ticket}/messages'),
            'tickets_reply'    => url('/admin/consulting/{ticket}/reply'),
            'tickets_status'   => url('/admin/consulting/{ticket}/status'),
            'tickets_assign'   => url('/admin/consulting/{ticket}/assign'),
            'tickets_note'     => url('/admin/consulting/{ticket}/note'),
            'notifications'    => route('notifications.index'),
            'notifications_all'=> route('notifications.read-all'),
        ]) }}"
    ></admin-consulting-component>

@endsection