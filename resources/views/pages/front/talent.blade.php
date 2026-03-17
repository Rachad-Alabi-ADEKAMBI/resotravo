@extends('layouts.front')

@section('title', 'Talents')

@section('content')

  <talent-component
    :routes="{
      register:            '{{ route('register') }}',
      registerPrestataire: '{{ route('register') }}'
    }"
  ></talent-component>

@endsection