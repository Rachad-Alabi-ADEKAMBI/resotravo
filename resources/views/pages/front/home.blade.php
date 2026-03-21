@extends('layouts.front')

@section('title', 'Accueil')

@section('content')

  <home-component
    :routes="{
      registerClient:     '{{ route('register.client') }}',
      registerContractor: '{{ route('register.contractor') }}'
    }"
    :initial-services="{{ json_encode($services) }}"
  ></home-component>

@endsection