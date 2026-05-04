@extends('layouts.front')

@section('title', 'Accueil')

@section('content')

  <home-component
    :routes="{
      registerClient:     '{{ route('register.client') }}',
      registerContractor: '{{ route('register.contractor') }}',
      dashboard:          '{{ route('dashboard') }}'
    }"
    :is-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    :initial-services="{{ json_encode($services) }}"
  ></home-component>

@endsection
