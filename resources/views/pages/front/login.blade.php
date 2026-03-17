@extends('layouts.front')

@section('title', "Connexion")
@section('content')


   <login-component :routes="{
    home:               '{{ route('home') }}',
    forgot:             '{{ route('password.request') }}',
    dashboard:          '{{ route('dashboard') }}',
    registerClient:     '{{ route('register.client') }}',
    registerContractor: '{{ route('register.contractor') }}'
}"></login-component>


@endsection