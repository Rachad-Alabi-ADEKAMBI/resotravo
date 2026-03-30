{{-- resources/views/pages/front/market.blade.php --}}
@extends('layouts.front')
@section('title', "Appels d'offres — Resotravo")
@php
    $active   = 'market';
    $authUser = Auth::user();
@endphp

@section('content')
<market-component
    :auth="{{ json_encode($authUser ? ['id' => $authUser->id, 'name' => $authUser->name, 'role' => $authUser->role] : null) }}"
    :routes="{{ json_encode([
        'tenders_index'   => route('tenders.index'),
        'tenders_stats'   => route('tenders.stats'),
        'tenders_store'   => route('client.tenders.store'),
        'tenders_apply'   => url('/tenders/{id}/apply'),
        'my_applications' => route('tenders.my-applications'),
        'my_tenders'      => route('client.tenders.mine'),
        'login'           => route('login'),
        'register'        => route('register.client'),
    ]) }}"
></market-component>
@endsection