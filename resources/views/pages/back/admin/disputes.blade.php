{{-- resources/views/pages/back/admin/disputes.blade.php --}}
@extends('layouts.back')

@section('title', 'Litiges — Mesotravo')

@php $active = 'disputes'; @endphp

@section('content')

    <admin-disputes-component
        :user="{{ json_encode([
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]) }}"
        :routes="{{ json_encode([
            'disputes_index'      => route('admin.disputes.index'),
            'disputes_show'       => url('/admin/disputes/{dispute}'),
            'disputes_store'      => route('admin.disputes.store'),
            'disputes_message'    => url('/admin/disputes/{dispute}/message'),
            'disputes_attachment' => url('/admin/disputes/{dispute}/attachment'),
            'disputes_verdict'    => url('/admin/disputes/{dispute}/verdict'),
            'disputes_close'      => url('/admin/disputes/{dispute}/close'),
            'disputes_missions'   => route('admin.disputes.missions'),
            'notifications'       => route('notifications.index'),
            'notifications_all'   => route('notifications.read-all'),
        ]) }}"
    ></admin-disputes-component>

@endsection