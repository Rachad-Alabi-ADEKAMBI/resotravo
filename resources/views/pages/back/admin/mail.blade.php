{{-- resources/views/pages/back/admin/mail.blade.php --}}
@extends('layouts.back')

@section('title', 'Mail - Mesotravo')

@php $active = 'mail'; @endphp

@section('content')
    <admin-mail-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role]) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-mail-component>
@endsection
