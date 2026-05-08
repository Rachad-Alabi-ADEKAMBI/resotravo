{{-- resources/views/pages/back/contractor/accreditation.blade.php --}}
@extends('layouts.back')

@section('title', 'Mon accréditation — Mesotravo')

@php $active = 'accreditation'; @endphp

@section('content')
    <contractor-accreditation-component
        :user="{{ json_encode([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]) }}"
        :contractor="{{ json_encode([
            'accreditation'      => $contractor?->accreditation ?? 'none',
            'status'             => $user->status ?? 'pending',
            'completed_missions' => $completedMissions ?? ($contractor?->completed_missions ?? 0),
            'latest_accreditation_request' => $latestAccreditationRequest ? [
                'id' => $latestAccreditationRequest->id,
                'type' => $latestAccreditationRequest->type,
                'status' => $latestAccreditationRequest->status,
                'message' => $latestAccreditationRequest->message,
                'admin_reason' => $latestAccreditationRequest->admin_reason,
                'created_at' => $latestAccreditationRequest->created_at?->toISOString(),
                'reviewed_at' => $latestAccreditationRequest->reviewed_at?->toISOString(),
            ] : null,
        ]) }}"
        :routes="{{ json_encode($routes) }}"
    ></contractor-accreditation-component>
@endsection
