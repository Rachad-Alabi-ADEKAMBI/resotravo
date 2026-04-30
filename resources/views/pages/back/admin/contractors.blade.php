@extends('layouts.back')

@section('title', 'Gestion des Prestataires — Mesotravo')

@section('content')

    <admin-contractors-component
        :user="{{ json_encode($auth) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-contractors-component>

@endsection