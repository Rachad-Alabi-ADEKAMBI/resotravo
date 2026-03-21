@extends('layouts.back')
@section('title', 'Paramètres')
@section('content')
    <parameters-component
        :user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status]) }}"
        :routes="{{ json_encode($routes) }}"
    ></parameters-component>
@endsection