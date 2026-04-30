{{-- resources/views/pages/front/consulting.blade.php --}}
@extends('layouts.front')

@section('title', 'Allô Conseils - Mesotravo')

@section('content')

    <consulting-component
        :auth="{{ json_encode($auth ?? null) }}"
        :routes="{{ json_encode($routes ?? [
            'register'         => route('register.client'),
            'login'            => route('login'),
            'tickets_store'    => route('consulting.tickets.store'),
            'tickets_index'    => route('consulting.tickets.index'),
            'tickets_messages' => url('/consulting/tickets/{ticket}/messages'),
            'tickets_send'     => url('/consulting/tickets/{ticket}/send'),
            'tickets_human'    => url('/consulting/tickets/{ticket}/request-human'),
        ]) }}"
    ></consulting-component>

@endsection
