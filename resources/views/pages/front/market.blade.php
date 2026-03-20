@extends('layouts.front')

@section('title', "Appels d'offres")
@section('content')

<market-component
    :routes="{{ json_encode([
        'register'        => route('register.client'),
        'login'           => route('login'),
        'tenders_index'   => url('/tenders'),
        'tenders_stats'   => url('/tenders/stats'),
        'tenders_store'   => route('client.tenders.store'),
        'tenders_apply'   => url('/tenders/{id}/apply'),
        'my_applications' => url('/tenders/my-applications'),
    ]) }}"
    :auth="{{ json_encode(auth()->check() ? [
        'id'   => auth()->id(),
        'name' => auth()->user()->name,
        'role' => auth()->user()->role,
    ] : null) }}"
></market-component>

@endsection