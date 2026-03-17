@extends('layouts.front')
@section('title', 'Inscription Prestataire')
@section('content')
    <register-contractor-component :routes="{
        login:     '{{ route('login') }}',
        dashboard: '{{ route('dashboard') }}',
        cgu:       '{{ route('cgu') }}',
        policy:    '{{ route('policy') }}'
    }"></register-contractor-component>
@endsection