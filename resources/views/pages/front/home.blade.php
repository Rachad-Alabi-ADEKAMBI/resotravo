@extends('layouts.front')

@section('title', 'Accueil')

@section('content')

  <home-component :routes="{
    registerClient:     '{{ route('register.client') }}',
    registerContractor: '{{ route('register.contractor') }}'
  }"></home-component>

@endsection