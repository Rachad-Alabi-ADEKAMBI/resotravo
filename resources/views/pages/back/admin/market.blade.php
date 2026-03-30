{{-- resources/views/pages/back/admin/market.blade.php --}}
@extends('layouts.back')
@section('title', "Appels d'offres — Admin")
@php $active = 'tenders'; @endphp

@section('content')
<admin-market-component
    :user="{{ json_encode(['id' => auth()->id(), 'name' => auth()->user()->name, 'role' => auth()->user()->role]) }}"
    :routes="{{ json_encode([
        'tenders_index'  => route('admin.tenders.admin-index'),
        'tenders_status' => url('/admin/tenders/{id}/status'),
    ]) }}"
></admin-market-component>
@endsection