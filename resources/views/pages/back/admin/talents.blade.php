@extends('layouts.back')

@section('title', 'Gestion des Talents — Mesotravo')

@section('content')

    <admin-talent-component
        :user="{{ json_encode($auth) }}"
        :routes="{{ json_encode($routes) }}"
    ></admin-talent-component>

@endsection