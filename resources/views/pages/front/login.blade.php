@extends('layouts.front')

@section('title', "Connexion")
@section('content')


   <login-component :routes="{
    home:               '{{ route('home') }}',
    forgot:             '{{ route('password.request') }}',
    dashboard:          '{{ route('dashboard') }}',
    registerClient:     '{{ route('register.client') }}',
    registerContractor: '{{ route('register.contractor') }}',
    googleAuth:         '{{ route('auth.google.redirect') }}?role=client'
}"></login-component>


@endsection
