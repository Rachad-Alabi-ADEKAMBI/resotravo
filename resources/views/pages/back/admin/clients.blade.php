@extends('layouts.back')

@section('title', 'Gestion des Clients — Mesotravo')

@section('content')

    <admin-clients-component
        :user="{{ json_encode($auth) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-clients-component>

@endsection