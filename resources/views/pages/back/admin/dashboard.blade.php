@extends('layouts.back')

@section('title', 'Tableau de bord Admin')

@section('content')

    <admin-dashboard-component
        :admin="{
            name: '{{ Auth::user()->name }}',
            role: 'admin'
        }"
    ></admin-dashboard-component>

@endsection