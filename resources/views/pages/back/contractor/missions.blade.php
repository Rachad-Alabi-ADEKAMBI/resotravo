{{-- resources/views/pages/back/contractor/missions.blade.php --}}
@extends('layouts.back')
@section('title', 'Mes missions')
@php
    $active = 'missions';
    $documentsVerified = $user->isCertified();
    $hasProfilePhoto = \App\Models\Document::where('user_id', $user->id)
        ->where('type', 'photo')
        ->where('status', 'approved')
        ->exists();
    $profilePhotoUrl = $user->contractor?->profile_picture
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->contractor->profile_picture)
        : ($hasProfilePhoto ? route('profile.photo.user', ['userId' => $user->id]) : null);
@endphp

@section('content')
<contractor-mission-component
    :user="{{ json_encode([
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role,
        'status' => $user->status,
        'documents_verified' => $documentsVerified,
        'documents_need_verification' => !$documentsVerified,
        'photo_url' => $profilePhotoUrl,
        'accreditation' => $user->contractor?->accreditation ?? 'none',
        'completed_missions' => $user->contractor?->completed_missions ?? 0,
    ]) }}"
    :diagnostic-fee="{{ (float) \App\Models\Setting::get('diagnostic_fee', 5000) }}"
    :commission-diagnostic="{{ (float) \App\Models\Setting::get('commission_diagnostic', 10) }}"
    :commission-main-oeuvre="{{ (float) \App\Models\Setting::get('commission_main_oeuvre', 10) }}"
    :routes="{{ json_encode([
        'missions_index'         => route('contractor.missions.index'),
        'missions_available'     => route('contractor.missions.available'),
        'missions_status'        => url('/contractor/missions/{id}/status'),
        'mission_receipt'        => url('/contractor/missions/{id}/receipt'),
        'missions_proposal_expire' => url('/contractor/missions/{id}/proposal-expire'),
        'missions_quote_store'   => url('/contractor/missions/{id}/quote'),
        'notifications'          => route('notifications.index'),
        'notifications_read'     => url('/notifications/{id}/read'),
        'notifications_all'      => route('notifications.read-all'),
        'conversations_mission'  => url('/conversations/mission/{id}'),
        'conversations_messages' => url('/conversations/{id}/messages'),
        'conversations_send'     => url('/conversations/{id}/messages'),
        'conversations_attach'   => url('/conversations/{id}/attachment'),
        'conversations_read'     => url('/conversations/{id}/read'),
        'unread_summary'         => route('unread-messages'),
        'accreditation_page'     => route('contractor.accreditation'),
    ]) }}"
></contractor-mission-component>
@endsection
