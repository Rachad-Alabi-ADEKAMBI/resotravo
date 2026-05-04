@extends('layouts.back')

@section('title', 'Mon espace prestataire')

@section('content')

    <contractor-dashboard-component
        :user="{{ json_encode([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ]) }}"
        :contractor-profile="{{ json_encode([
            'first_name'         => $contractor?->first_name         ?? '',
            'last_name'          => $contractor?->last_name          ?? '',
            'phone'              => $contractor?->phone              ?? '',
            'city'               => $contractor?->city               ?? 'Cotonou',
            'specialty'          => $contractor?->specialty          ?? '',
            'specialties'        => $contractor?->specialties        ?? [],
            'intervention_zone'  => $contractor?->intervention_zone  ?? '',
            'experience_years'   => $contractor?->experience_years   ?? 0,
            'bio'                => $contractor?->bio                ?? '',
            'profile_picture'    => $contractor?->profile_picture
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($contractor->profile_picture)
                : null,
            'accreditation'      => $contractor?->accreditation      ?? 'none',
            'available'          => $contractor?->available          ?? true,
            'start_time'         => $contractor?->start_time         ?? '08:00',
            'end_time'           => $contractor?->end_time           ?? '18:00',
            'radius_km'          => $contractor?->radius_km          ?? 10,
            'diagnostic_fee'     => $contractor?->diagnostic_fee     ?? 0,
            'total_missions'     => $contractor?->total_missions     ?? 0,
            'completed_missions' => $contractor?->completed_missions ?? 0,
            'average_rating'     => $contractor?->average_rating     ?? 0,
            'reviews_count'      => $contractor?->reviews_count      ?? 0,
        ]) }}"
        :doc-progress-data="{{ json_encode($docProgress) }}"
        :documents-data="{{ json_encode($documents) }}"
        :routes="{{ json_encode([
            'missions_index'     => route('contractor.missions.index'),
            'missions_available' => route('contractor.missions.available'),
            'missions_show'      => url('/contractor/missions/{id}'),
            'missions_status'    => url('/contractor/missions/{id}/status'),
            'notifications'      => route('notifications.index'),
            'notifications_read' => url('/notifications/{id}/read'),
            'notifications_all'  => route('notifications.read-all'),
            'availability'       => route('contractor.disponibilite'),
            'documents_upload'   => route('documents.upload'),
            'documents_index'    => route('documents.index'),
            'profil'             => route('contractor.profil.show'),
            'conversations_mission'  => url('/conversations/mission/{id}'),
            'conversations_messages' => url('/conversations/{id}/messages'),
            'conversations_send'     => url('/conversations/{id}/messages'),
            'conversations_attach'   => url('/conversations/{id}/attachment'),
            'missions_quote_store'   => url('/contractor/missions/{id}/quote'),
            'conversations_read'     => url('/conversations/{id}/read'),
        ]) }}"
    :diagnostic-fee="{{ (float) \App\Models\Setting::get('diagnostic_fee', 5000) }}"
    :commission-diagnostic="{{ (float) \App\Models\Setting::get('commission_diagnostic', 10) }}"
    :commission-main-oeuvre="{{ (float) \App\Models\Setting::get('commission_main_oeuvre', 10) }}"
    ></contractor-dashboard-component>

@endsection
