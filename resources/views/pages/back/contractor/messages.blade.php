{{-- resources/views/pages/back/contractor/messages.blade.php --}}
@extends('layouts.back')

@section('title', 'Messages — Resotravo')

@php $active = 'messages'; @endphp

@section('content')
    <messages-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'role' => $user->role]) }}"
        :routes="{{ json_encode($routes) }}"
        :open-conversation-id="{{ request('conversation') ? (int) request('conversation') : 'null' }}"
    ></messages-component>
@endsection