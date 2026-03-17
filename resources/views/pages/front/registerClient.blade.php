@extends('layouts.front')
@section('title', 'Inscription Client')
@section('content')
    <register-client-component :routes="{
        login:     '{{ route('login') }}',
        dashboard: '{{ route('dashboard') }}',
        cgu:       '{{ route('cgu') }}',
        policy:    '{{ route('policy') }}'
    }"></register-client-component>
@endsection