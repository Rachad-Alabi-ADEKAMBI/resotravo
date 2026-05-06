<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compléter votre inscription - Mesotravo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, Arial, sans-serif; background: #f5f5f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; color: #1c1412; }
        .gc-card { background: #fff; border-radius: 18px; padding: 36px 32px; max-width: 680px; width: 100%; box-shadow: 0 4px 32px rgba(0,0,0,.08); font-size: 16px; line-height: 1.35; }
        .gc-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .gc-logo-img { height: 36px; }
        .gc-title { font-size: 1.45rem; font-weight: 850; margin-bottom: 4px; }
        .gc-sub { font-size: .92rem; color: #6b7280; margin-bottom: 26px; line-height: 1.5; }
        .gc-avatar { display: flex; align-items: center; gap: 12px; background: #f9fafb; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; }
        .gc-avatar img { width: 44px; height: 44px; border-radius: 50%; }
        .gc-avatar-info strong { font-size: .95rem; display: block; }
        .gc-avatar-info span { font-size: .82rem; color: #6b7280; overflow-wrap: anywhere; }
        .gc-section-title { font-size: .8rem; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 14px; }
        .gc-role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .gc-role-btn { border: 2px solid #e5e7eb; border-radius: 10px; padding: 14px; cursor: pointer; background: #fff; text-align: left; transition: all .2s; display: grid; grid-template-columns: 34px 1fr; column-gap: 10px; align-items: center; min-height: 76px; }
        .gc-role-btn.active, .gc-role-btn:hover { border-color: #f97316; background: #fff7ed; }
        .gc-role-icon { font-size: 1.55rem; display: grid; place-items: center; grid-row: span 2; }
        .gc-role-label { font-size: .9rem; font-weight: 800; line-height: 1.2; display: block; }
        .gc-role-btn small { color: #6b7280; font-size: .78rem; line-height: 1.25; display: block; margin-top: 3px; }
        .gc-field { margin-bottom: 20px; display: flex; flex-direction: column; gap: 4px; line-height: 1.25; }
        .gc-field-half { max-width: calc(50% - 6px); }
        .gc-label { display: block; font-size: .83rem; font-weight: 700; color: #374151; margin: 0; line-height: 1.2; }
        .gc-req { color: #ef4444; }
        .gc-input { display: block; width: 100%; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin: 0; font-size: .9rem; line-height: 1.35; color: #1c1412; outline: none; transition: border .2s; background: #fff; }
        .gc-input:focus { border-color: #f97316; }
        .gc-textarea { min-height: 92px; resize: vertical; }
        .gc-phone-wrap { position: relative; display: flex; align-items: center; }
        .gc-phone-prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8a7d78; font-size: .9rem; line-height: 1; font-weight: 800; pointer-events: none; z-index: 1; }
        .gc-phone-input { padding-left: 82px; }
        .gc-grid2 { display: grid; grid-template-columns: 1fr 1fr; column-gap: 12px; row-gap: 4px; }
        .gc-error { color: #dc2626; font-size: .82rem; margin-top: 5px; }
        .gc-feedback-overlay { position: fixed; inset: 0; z-index: 1300; background: rgba(17,13,11,.5); backdrop-filter: blur(3px); display: none; align-items: center; justify-content: center; padding: 20px; }
        .gc-feedback { width: min(420px, 100%); background: #fff; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,.22); overflow: hidden; }
        .gc-feedback-head { padding: 20px 22px 8px; display: flex; align-items: center; gap: 12px; }
        .gc-feedback-icon { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-weight: 900; color: #fff; background: #dc2626; flex: 0 0 auto; }
        .gc-feedback.success .gc-feedback-icon { background: #16a34a; }
        .gc-feedback-title { font-size: 17px; font-weight: 850; color: #1c1412; }
        .gc-feedback-body { padding: 4px 22px 18px; color: #4b5563; font-size: 14px; line-height: 1.6; }
        .gc-feedback-body ul { padding-left: 18px; display: grid; gap: 5px; }
        .gc-feedback-actions { padding: 14px 22px 20px; border-top: 1px solid #eee7e2; display: flex; justify-content: flex-end; }
        .gc-divider { border: none; border-top: 1px solid #e5e7eb; margin: 20px 0; }
        .gc-services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 10px; max-height: 260px; overflow: auto; padding: 2px; }
        .gc-service-btn { border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fff; padding: 12px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 9px; min-height: 54px; }
        .gc-service-btn.active { border-color: #f97316; background: #fff7ed; color: #9a3412; }
        .gc-service-icon { font-size: 20px; width: 26px; text-align: center; flex: 0 0 auto; }
        .gc-service-name { font-size: .84rem; font-weight: 750; line-height: 1.25; }
        .gc-service-select { position: relative; }
        .gc-service-select-btn { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 44px; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #fff; color: #1c1412; font-size: .9rem; line-height: 1.35; cursor: pointer; text-align: left; }
        .gc-service-select-btn:focus { outline: none; border-color: #f97316; }
        .gc-service-select-btn .gc-service-name { flex: 1; font-size: .9rem; }
        .gc-service-placeholder { color: #6b7280; font-weight: 500; }
        .gc-service-arrow { color: #8a7d78; font-size: 14px; }
        .gc-service-menu { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 20; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,.12); max-height: 260px; overflow: auto; padding: 6px; }
        .gc-service-select.open .gc-service-menu { display: grid; gap: 4px; }
        .gc-service-option { width: 100%; border: 0; background: transparent; border-radius: 8px; padding: 10px; display: flex; align-items: center; gap: 10px; text-align: left; cursor: pointer; color: #1c1412; }
        .gc-service-option:hover, .gc-service-option.active { background: #fff7ed; color: #9a3412; }
        .gc-account-select { position: relative; }
        .gc-account-select-btn { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 44px; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #fff; color: #1c1412; font-size: .9rem; cursor: pointer; text-align: left; }
        .gc-account-select-btn:focus { outline: none; border-color: #f97316; }
        .gc-account-icon { font-size: 20px; width: 26px; text-align: center; flex: 0 0 auto; }
        .gc-account-name { flex: 1; font-size: .9rem; font-weight: 750; }
        .gc-account-menu { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 21; background: #fff; border: 1.5px solid #e5e7eb; border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,.12); padding: 6px; }
        .gc-account-select.open .gc-account-menu { display: grid; gap: 4px; }
        .gc-account-option { width: 100%; border: 0; background: transparent; border-radius: 8px; padding: 10px; display: grid; grid-template-columns: 28px 1fr 26px; align-items: center; gap: 10px; text-align: left; cursor: pointer; color: #1c1412; }
        .gc-account-option:hover, .gc-account-option.active { background: #fff7ed; color: #9a3412; }
        .gc-help-btn { width: 24px; height: 24px; border-radius: 50%; border: 1.5px solid #fed7aa; background: #fff7ed; color: #ea580c; font-weight: 900; font-size: 13px; line-height: 1; display: grid; place-items: center; cursor: pointer; }
        .gc-help-btn:hover { background: #f97316; color: #fff; border-color: #f97316; }
        .gc-add-note { background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 10px; padding: 12px 14px; margin: 12px 0 18px; font-size: .86rem; color: #4b5563; }
        .gc-link-btn { border: 0; background: transparent; color: #ea580c; font-weight: 800; cursor: pointer; padding: 0; }
        .gc-cgu-block { margin: 18px 0 10px; }
        .gc-btn-cgu { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 18px; border-radius: 10px; border: 2px dashed #f97316; background: #fff7ed; color: #f97316; font-size: 14px; font-weight: 800; cursor: pointer; transition: all .2s; }
        .gc-btn-cgu:hover { background: rgba(249,115,22,.12); transform: translateY(-1px); }
        .gc-cgu-accepted { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #f0fdf4; border: 1.5px solid #86efac; font-size: 13.5px; color: #16a34a; font-weight: 700; }
        .gc-cgu-check { font-size: 16px; }
        .gc-cgu-reread { border: 0; background: transparent; color: #f97316; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0; }
        .gc-submit { width: 100%; background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 1rem; font-weight: 800; cursor: pointer; margin-top: 8px; transition: opacity .2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .gc-submit:hover { opacity: .9; }
        .gc-modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(17,13,11,.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; }
        .gc-modal { background: #fff; border-radius: 20px; width: min(440px, 100%); max-height: 90vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .gc-modal-head { padding: 20px 22px 12px; border-bottom: 1px solid #eee7e2; }
        .gc-modal-title { font-size: 1.1rem; font-weight: 850; }
        .gc-modal-sub { margin-top: 5px; color: #6b7280; font-size: .84rem; line-height: 1.45; }
        .gc-modal-body { padding: 18px 22px; max-height: 54vh; overflow: auto; color: #4b5563; font-size: .88rem; line-height: 1.65; }
        .gc-modal-body h4 { color: #1c1412; margin: 12px 0 4px; font-size: .92rem; }
        .gc-modal-actions { padding: 14px 22px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid #eee7e2; }
        .gc-modal-btn { border: 0; border-radius: 10px; padding: 12px 14px; font-size: 14px; line-height: 1.2; font-weight: 800; cursor: pointer; white-space: nowrap; }
        .gc-modal-btn.ghost { background: #f3f4f6; color: #374151; }
        .gc-modal-btn.primary { background: #f97316; color: #fff; }
        .gc-modal-btn:disabled { opacity: .48; cursor: not-allowed; }
        .gc-modal-cgu { width: min(860px, 100%); padding: 0; display: flex; flex-direction: column; max-height: 94vh; }
        .gc-modal-cgu .gc-modal-head { padding: 24px 28px 16px; border-bottom: 1.5px solid #e8ddd4; display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; }
        .gc-modal-icon { font-size: 28px; line-height: 1; }
        .gc-modal-cgu .gc-modal-title { font-size: 18px; font-weight: 850; color: #1c1412; }
        .gc-modal-cgu .gc-modal-sub { font-size: 12.5px; color: #8a7d78; margin: 0; font-style: italic; }
        .gc-modal-cgu .gc-modal-sub.ok { color: #16a34a; font-style: normal; font-weight: 700; }
        .gc-modal-cgu-body { flex: 1; overflow-y: auto; padding: 20px 28px; scroll-behavior: smooth; max-height: none; color: #7c6a5a; }
        .gc-modal-cgu-body::-webkit-scrollbar { width: 6px; }
        .gc-modal-cgu-body::-webkit-scrollbar-track { background: #e8ddd4; border-radius: 99px; }
        .gc-modal-cgu-body::-webkit-scrollbar-thumb { background: #f97316; border-radius: 99px; }
        .gc-cgu-content-intro { background: #fff7ed; border: 1px solid rgba(249,115,22,.2); border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; font-size: 13.5px; color: #1c1412; line-height: 1.7; }
        .gc-cgu-article { padding: 16px 0; border-bottom: 1px solid #e8ddd4; }
        .gc-cgu-article:last-of-type { border-bottom: none; }
        .gc-cgu-article h4 { display: flex; align-items: center; gap: 10px; font-size: 14.5px; font-weight: 850; color: #1c1412; margin-bottom: 8px; }
        .gc-cgu-num { width: 28px; height: 28px; border-radius: 7px; background: linear-gradient(135deg,#f97316,#ea580c); color: #fff; font-size: 11px; font-weight: 850; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .gc-cgu-article p { font-size: 13.5px; color: #7c6a5a; line-height: 1.75; }
        .gc-cgu-last-update { text-align: center; font-size: 12px; color: #8a7d78; padding: 16px 0 4px; font-style: italic; }
        .gc-modal-cgu-body .legal-intro { background: #fff7ed; border: 1px solid rgba(249,115,22,.2); border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; color: #1c1412; }
        .gc-modal-cgu-body .legal-intro p { font-size: 13.5px; line-height: 1.7; margin-bottom: 8px; }
        .gc-modal-cgu-body .legal-article { padding: 16px 0; border-bottom: 1px solid #e8ddd4; }
        .gc-modal-cgu-body .legal-article:last-child { border-bottom: none; }
        .gc-modal-cgu-body .legal-article h2 { display: flex; align-items: center; gap: 10px; font-size: 14.5px; font-weight: 850; color: #1c1412; margin: 0 0 8px; }
        .gc-modal-cgu-body .legal-article h3 { font-size: 13.5px; font-weight: 800; color: #1c1412; margin: 12px 0 4px; }
        .gc-modal-cgu-body .art-num { width: 28px; height: 28px; border-radius: 7px; background: linear-gradient(135deg,#f97316,#ea580c); color: #fff; font-size: 11px; font-weight: 850; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .gc-modal-cgu-body .legal-article p,
        .gc-modal-cgu-body .legal-article li { font-size: 13.5px; color: #7c6a5a; line-height: 1.75; }
        .gc-modal-cgu-body .legal-article ul { padding-left: 20px; margin: 8px 0 10px; }
        .gc-modal-cgu-body .legal-warning { background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 10px; padding: 12px 14px; font-size: 13.5px; color: #dc2626; font-weight: 700; margin: 12px 0; }
        .gc-modal-cgu-body .legal-steps { display: grid; gap: 8px; margin: 12px 0; }
        .gc-modal-cgu-body .legal-step { display: flex; align-items: center; gap: 10px; background: #fbf7f3; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; color: #7c6a5a; }
        .gc-modal-cgu-body .legal-step-num { width: 24px; height: 24px; border-radius: 7px; background: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 850; flex-shrink: 0; }
        .gc-modal-cgu-body .legal-contact-card { background: #fbf7f3; border: 1px solid #e8ddd4; border-radius: 10px; padding: 14px; display: grid; gap: 8px; }
        .gc-modal-cgu-body .legal-contact-title { font-weight: 850; color: #1c1412; }
        .gc-modal-cgu-body .legal-contact-item { display: flex; gap: 10px; font-size: 13px; color: #7c6a5a; }
        .gc-modal-cgu-body .legal-contact-item span:first-child { min-width: 68px; color: #1c1412; font-weight: 800; }
        .gc-modal-cgu-body .legal-link { color: #ea580c; font-weight: 800; text-decoration: none; }
        .gc-cgu-scroll-bar { height: 4px; background: #e8ddd4; flex-shrink: 0; }
        .gc-cgu-scroll-fill { height: 100%; width: 0; background: linear-gradient(90deg,#f97316,#ea580c); transition: width .1s; border-radius: 0 99px 99px 0; }
        .gc-modal-cgu-footer { padding: 16px 28px 24px; display: flex; gap: 12px; border-top: 1.5px solid #e8ddd4; }
        .gc-modal-cgu-footer .gc-modal-btn { flex: 1; border-radius: 10px; padding: 12px 24px; font-size: 15px; }
        .gc-modal-cgu-footer .gc-modal-btn.primary { flex: 2; background: linear-gradient(135deg,#f97316,#ea580c); box-shadow: 0 4px 14px rgba(249,115,22,.3); }
        .gc-modal-cgu-footer .gc-modal-btn.ghost { background: #e8ddd4; color: #1c1412; }
        .gc-success-msg { color: #166534; font-size: .84rem; font-weight: 700; margin-top: 8px; }
        @media (max-width: 560px) { .gc-grid2, .gc-role-grid, .gc-modal-actions { grid-template-columns: 1fr; } .gc-field-half { max-width: none; } .gc-modal-cgu-footer { flex-direction: column; } .gc-card { padding: 28px 20px; } }
    </style>
</head>
<body>
@php
    $selectedRole = old('role', $role);
    $selectedAccountType = old('account_type', 'individual');
    $selectedSpecialty = old('specialty', '');
    $selectedServiceId = old('service_id', '');
    $selectedBio = old('bio', '');
@endphp
<div class="gc-card">
    <div class="gc-logo">
        <img src="/images/favicon.PNG" alt="Mesotravo" class="gc-logo-img">
        <strong style="font-size:1.1rem;color:#1c1412">Mesotravo</strong>
    </div>

    <h1 class="gc-title">Complétez votre inscription</h1>
    <p class="gc-sub">Quelques informations supplémentaires pour finaliser votre compte Google.</p>

    <div class="gc-avatar">
        @if ($googleUser['avatar'])
            <img src="{{ $googleUser['avatar'] }}" alt="Avatar">
        @endif
        <div class="gc-avatar-info">
            <strong>{{ $googleUser['name'] }}</strong>
            <span>{{ $googleUser['email'] }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('auth.google.complete.store') }}" id="gc-form" novalidate>
        @csrf

        <div class="gc-section-title">Je suis</div>
        <div class="gc-role-grid" id="role-grid">
            <button type="button" class="gc-role-btn {{ $selectedRole === 'client' ? 'active' : '' }}" onclick="setRole('client', this)">
                <span class="gc-role-icon">🏠</span>
                <span>
                    <span class="gc-role-label">Client</span>
                    <small>Je publie des missions</small>
                </span>
            </button>
            <button type="button" class="gc-role-btn {{ $selectedRole === 'contractor' ? 'active' : '' }}" onclick="setRole('contractor', this)">
                <span class="gc-role-icon">🔧</span>
                <span>
                    <span class="gc-role-label">Prestataire</span>
                    <small>Je réalise des missions</small>
                </span>
            </button>
        </div>
        <input type="hidden" name="role" id="role-input" value="{{ $selectedRole }}">

        <hr class="gc-divider">

        <div class="gc-grid2">
            <div class="gc-field">
                <label class="gc-label">Prénom <span class="gc-req">*</span></label>
                <input class="gc-input" type="text" name="first_name" value="{{ old('first_name', $googleUser['first_name']) }}" required>
            </div>
            <div class="gc-field">
                <label class="gc-label">Nom <span class="gc-req">*</span></label>
                <input class="gc-input" type="text" name="last_name" value="{{ old('last_name', $googleUser['last_name']) }}" required>
            </div>
        </div>

        <div class="gc-field gc-field-half">
            <label class="gc-label">Téléphone <span class="gc-req">*</span></label>
            <div class="gc-phone-wrap">
                <span class="gc-phone-prefix">+229 01</span>
                <input
                    class="gc-input gc-phone-input"
                    type="tel"
                    name="phone"
                    id="phone-input"
                    value="{{ old('phone') ? preg_replace('/^01/', '', preg_replace('/\D/', '', old('phone'))) : '' }}"
                    placeholder="97 XX XX XX"
                    autocomplete="tel"
                    maxlength="11"
                    inputmode="numeric"
                    pattern="[0-9 ]{11}"
                    required
                    oninput="formatPhoneInput(this)"
                >
            </div>
            <div class="gc-error" id="phone-error" style="display:none">Veuillez saisir exactement 8 chiffres après +229 01.</div>
        </div>

        <div class="gc-grid2">
            <div class="gc-field">
                <label class="gc-label">Mot de passe <span class="gc-req">*</span></label>
                <input class="gc-input" type="password" name="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="gc-field">
                <label class="gc-label">Confirmer le mot de passe <span class="gc-req">*</span></label>
                <input class="gc-input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </div>
        </div>

        <div id="client-fields">
            <div class="gc-field">
                <label class="gc-label">Type de compte <span class="gc-req">*</span></label>
                <input type="hidden" name="account_type" id="account-type" value="{{ $selectedAccountType }}">
                <div class="gc-account-select" id="account-select">
                    <button type="button" class="gc-account-select-btn" id="account-select-btn" onclick="toggleAccountSelect()" aria-expanded="false">
                        <span class="gc-account-icon" id="account-selected-icon">👤</span>
                        <span class="gc-account-name" id="account-selected-name">Particulier</span>
                        <span class="gc-service-arrow">▾</span>
                    </button>
                    <div class="gc-account-menu" id="account-menu">
                        <button type="button" class="gc-account-option" data-value="individual" data-icon="👤" data-label="Particulier" onclick="selectAccountType('individual')">
                            <span class="gc-account-icon">👤</span>
                            <span class="gc-account-name">Particulier</span>
                            <span class="gc-help-btn" onclick="showAccountHelp(event, 'individual')">?</span>
                        </button>
                        <button type="button" class="gc-account-option" data-value="company" data-icon="🏢" data-label="Entreprise" onclick="selectAccountType('company')">
                            <span class="gc-account-icon">🏢</span>
                            <span class="gc-account-name">Entreprise</span>
                            <span class="gc-help-btn" onclick="showAccountHelp(event, 'company')">?</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="gc-field" id="company-field" style="display:none">
                <label class="gc-label">Nom de l'entreprise</label>
                <input class="gc-input" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Votre entreprise">
            </div>
            <div class="gc-field">
                <label class="gc-label">Adresse <span class="gc-req">*</span></label>
                <input class="gc-input" type="text" name="address" value="{{ old('address') }}" placeholder="Votre adresse">
            </div>
        </div>

        <div id="contractor-fields" style="display:none">
            <div class="gc-field">
                <label class="gc-label">Spécialité principale <span class="gc-req">*</span></label>
                <input type="hidden" name="specialty" id="specialty-input" value="{{ $selectedSpecialty }}">
                <input type="hidden" name="service_id" id="service-id-input" value="{{ $selectedServiceId }}">
                <div class="gc-service-select" id="service-select">
                    <button type="button" class="gc-service-select-btn" id="service-select-btn" onclick="toggleServiceSelect()" aria-expanded="false">
                        <span class="gc-service-icon" id="service-selected-icon">🔧</span>
                        <span class="gc-service-name gc-service-placeholder" id="service-selected-name">Sélectionnez une spécialité</span>
                        <span class="gc-service-arrow">▾</span>
                    </button>
                    <div class="gc-service-menu" id="service-menu"></div>
                </div>
                <div class="gc-error" id="service-error" style="display:none">Please choose a service category.</div>
            </div>
            <div class="gc-add-note">
                Votre métier n'est pas listé ?
                <button type="button" class="gc-link-btn" onclick="openSuggestionModal()">Proposer une catégorie de service</button>
            </div>
            <div class="gc-grid2">
                <div class="gc-field">
                    <label class="gc-label">Ville <span class="gc-req">*</span></label>
                    <input class="gc-input" type="text" name="city" value="{{ old('city', 'Cotonou') }}" placeholder="Cotonou">
                </div>
                <div class="gc-field">
                    <label class="gc-label">Années d'exp. <span class="gc-req">*</span></label>
                    <input class="gc-input" type="number" name="experience_years" value="{{ old('experience_years', 1) }}" min="0" max="60">
                </div>
            </div>
            <div class="gc-field">
                <label class="gc-label">Zone d'intervention <span class="gc-req">*</span></label>
                <input class="gc-input" type="text" name="intervention_zone" value="{{ old('intervention_zone', 'Cotonou') }}" placeholder="Ex : Cotonou, Calavi">
            </div>
            <div class="gc-field">
                <label class="gc-label">Bio</label>
                <textarea class="gc-input gc-textarea" name="bio" placeholder="Présentez brièvement votre expérience, vos compétences ou votre façon de travailler">{{ $selectedBio }}</textarea>
            </div>
        </div>

        <div class="gc-cgu-block">
            <div class="gc-cgu-accepted" id="cgu-accepted" style="display:none">
                <span class="gc-cgu-check">✓</span>
                <span>CGU acceptées - <button type="button" class="gc-cgu-reread" onclick="openCguModal()">Relire</button></span>
            </div>
            <button type="button" class="gc-btn-cgu" id="cgu-open-btn" onclick="openCguModal()">📜 Lire et accepter les CGU</button>
            <input type="hidden" name="cgv" id="cgv-input" value="{{ old('cgv') ? '1' : '' }}">
            <div class="gc-error" id="cgu-error" style="display:none">Veuillez ouvrir et accepter les CGU avant de créer votre compte.</div>
        </div>

        <button type="submit" class="gc-submit"><span>✓</span><span>Créer mon compte</span></button>
    </form>
</div>

<div class="gc-feedback-overlay" id="feedback-popup">
    <div class="gc-feedback" id="feedback-box">
        <div class="gc-feedback-head">
            <div class="gc-feedback-icon" id="feedback-icon">!</div>
            <div class="gc-feedback-title" id="feedback-title">Error</div>
        </div>
        <div class="gc-feedback-body" id="feedback-message"></div>
        <div class="gc-feedback-actions">
            <button type="button" class="gc-modal-btn primary" onclick="closeFeedbackPopup()">OK</button>
        </div>
    </div>
</div>

<div class="gc-modal-overlay" id="suggestion-modal">
    <div class="gc-modal">
        <div class="gc-modal-head">
            <div class="gc-modal-title">Proposer une catégorie de service</div>
            <div class="gc-modal-sub">Votre suggestion sera enregistrée et sélectionnée pour finaliser votre inscription.</div>
        </div>
        <div class="gc-modal-body">
            <label class="gc-label">Nom de la catégorie</label>
            <input class="gc-input" type="text" id="suggestion-name" placeholder="Ex : Installation panneaux solaires">
            <div class="gc-error" id="suggestion-error" style="display:none"></div>
            <div class="gc-success-msg" id="suggestion-success" style="display:none"></div>
        </div>
        <div class="gc-modal-actions">
            <button type="button" class="gc-modal-btn ghost" onclick="closeSuggestionModal()">Annuler</button>
            <button type="button" class="gc-modal-btn primary" id="suggestion-submit" onclick="submitSuggestion()">Envoyer</button>
        </div>
    </div>
</div>

<div class="gc-modal-overlay" id="cgu-modal">
    <div class="gc-modal gc-modal-cgu">
        <div class="gc-modal-head">
            <div class="gc-modal-icon">📜</div>
            <div class="gc-modal-title">Conditions Générales d'Utilisation</div>
            <div class="gc-modal-sub" id="cgu-hint">↓ Faites défiler jusqu'en bas pour accepter</div>
        </div>
        <div class="gc-modal-body gc-modal-cgu-body" id="cgu-scroll-box" onscroll="handleCguScroll()">
            @include('partials.cgu-content')
            <div class="gc-cgu-last-update">Version 2.0 - Mars 2026</div>
        </div>
        <div class="gc-cgu-scroll-bar">
            <div class="gc-cgu-scroll-fill" id="cgu-scroll-fill"></div>
        </div>
        <div class="gc-modal-cgu-footer">
            <button type="button" class="gc-modal-btn ghost" onclick="closeCguModal()">Fermer</button>
            <button type="button" class="gc-modal-btn primary" id="cgu-accept-btn" onclick="acceptCgu()" disabled>✓ Oui, j'ai lu et j'accepte</button>
        </div>
    </div>
</div>

<script>
const services = @json($services ?? []);
const routes = @json($routes ?? []);
const csrfToken = @json(csrf_token());
const serverErrors = @json($errors->all());
let selectedServiceName = document.getElementById('specialty-input').value || '';
let selectedServiceId = document.getElementById('service-id-input').value || '';

function showFeedbackPopup(type, title, messages) {
    const popup = document.getElementById('feedback-popup');
    const box = document.getElementById('feedback-box');
    const icon = document.getElementById('feedback-icon');
    const messageBox = document.getElementById('feedback-message');
    const list = Array.isArray(messages) ? messages : [messages];

    box.className = 'gc-feedback ' + (type === 'success' ? 'success' : 'error');
    icon.textContent = type === 'success' ? '✓' : '!';
    document.getElementById('feedback-title').textContent = title;
    messageBox.innerHTML = list.length > 1
        ? '<ul>' + list.map((message) => `<li>${escapeHtml(message)}</li>`).join('') + '</ul>'
        : escapeHtml(list[0] || '');
    popup.style.display = 'flex';
}

function closeFeedbackPopup() {
    document.getElementById('feedback-popup').style.display = 'none';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function setRole(role, button) {
    document.getElementById('role-input').value = role;
    document.querySelectorAll('.gc-role-btn').forEach((b) => b.classList.remove('active'));
    if (button) button.classList.add('active');
    document.getElementById('client-fields').style.display = role === 'client' ? '' : 'none';
    document.getElementById('contractor-fields').style.display = role === 'contractor' ? '' : 'none';
}

function toggleCompany() {
    const v = document.getElementById('account-type').value;
    document.getElementById('company-field').style.display = v === 'company' ? '' : 'none';
}

function toggleAccountSelect() {
    const wrap = document.getElementById('account-select');
    const open = !wrap.classList.contains('open');
    wrap.classList.toggle('open', open);
    document.getElementById('account-select-btn').setAttribute('aria-expanded', open ? 'true' : 'false');
}

function selectAccountType(value) {
    const option = document.querySelector(`.gc-account-option[data-value="${value}"]`);
    if (!option) return;

    document.getElementById('account-type').value = value;
    document.getElementById('account-selected-icon').textContent = option.dataset.icon;
    document.getElementById('account-selected-name').textContent = option.dataset.label;
    document.querySelectorAll('.gc-account-option').forEach((item) => item.classList.toggle('active', item.dataset.value === value));
    document.getElementById('account-select').classList.remove('open');
    document.getElementById('account-select-btn').setAttribute('aria-expanded', 'false');
    toggleCompany();
}

function showAccountHelp(event, type) {
    event.stopPropagation();
    const content = type === 'company'
        ? 'Choisissez Entreprise si la mission est publiée au nom d’une société, d’une organisation ou d’une activité professionnelle. Le nom de l’entreprise sera demandé.'
        : 'Choisissez Particulier si vous publiez des missions pour vos besoins personnels, votre domicile ou vos travaux privés.';
    showFeedbackPopup('success', type === 'company' ? 'Compte Entreprise' : 'Compte Particulier', content);
}

function renderServices() {
    const menu = document.getElementById('service-menu');
    menu.innerHTML = '';
    services
        .slice()
        .sort((a, b) => String(a.name).localeCompare(String(b.name), 'fr', { sensitivity: 'base' }))
        .forEach((service) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gc-service-option' + (String(service.id) === String(selectedServiceId) || service.name === selectedServiceName ? ' active' : '');
            btn.innerHTML = `<span class="gc-service-icon">${service.icon || '🔧'}</span><span class="gc-service-name"></span>`;
            btn.querySelector('.gc-service-name').textContent = service.name;
            btn.onclick = () => selectService(service);
            menu.appendChild(btn);
        });
    updateSelectedServiceView();
}

function toggleServiceSelect() {
    const wrap = document.getElementById('service-select');
    const open = !wrap.classList.contains('open');
    wrap.classList.toggle('open', open);
    document.getElementById('service-select-btn').setAttribute('aria-expanded', open ? 'true' : 'false');
}

function selectService(service) {
    selectedServiceName = service.name;
    selectedServiceId = service.id || '';
    document.getElementById('specialty-input').value = selectedServiceName;
    document.getElementById('service-id-input').value = selectedServiceId;
    if (!services.some((item) => String(item.id) === String(selectedServiceId))) {
        services.push(service);
    }
    document.getElementById('service-error').style.display = 'none';
    document.getElementById('service-select').classList.remove('open');
    document.getElementById('service-select-btn').setAttribute('aria-expanded', 'false');
    renderServices();
}

function updateSelectedServiceView() {
    const service = services.find((item) => String(item.id) === String(selectedServiceId) || item.name === selectedServiceName);
    const icon = document.getElementById('service-selected-icon');
    const name = document.getElementById('service-selected-name');
    if (service) {
        icon.textContent = service.icon || '🔧';
        name.textContent = service.name;
        name.classList.remove('gc-service-placeholder');
        document.getElementById('specialty-input').value = service.name;
        document.getElementById('service-id-input').value = service.id || '';
        selectedServiceId = service.id || '';
        selectedServiceName = service.name;
        return;
    }

    if (selectedServiceName) {
        icon.textContent = '🔧';
        name.textContent = selectedServiceName;
        name.classList.remove('gc-service-placeholder');
        document.getElementById('specialty-input').value = selectedServiceName;
        document.getElementById('service-id-input').value = selectedServiceId || '';
        return;
    }

    icon.textContent = '🔧';
    name.textContent = 'Sélectionnez une spécialité';
    name.classList.add('gc-service-placeholder');
}

function formatPhoneInput(input) {
    const digits = input.value.replace(/\D/g, '').slice(0, 8);
    input.value = digits.replace(/(\d{2})(?=\d)/g, '$1 ');
}

function openSuggestionModal() {
    document.getElementById('suggestion-modal').style.display = 'flex';
    document.getElementById('suggestion-name').value = '';
    document.getElementById('suggestion-error').style.display = 'none';
    document.getElementById('suggestion-success').style.display = 'none';
    setTimeout(() => document.getElementById('suggestion-name').focus(), 50);
}

function closeSuggestionModal() {
    document.getElementById('suggestion-modal').style.display = 'none';
}

document.addEventListener('click', function (event) {
    const serviceWrap = document.getElementById('service-select');
    if (serviceWrap && !serviceWrap.contains(event.target)) {
        serviceWrap.classList.remove('open');
        document.getElementById('service-select-btn').setAttribute('aria-expanded', 'false');
    }

    const accountWrap = document.getElementById('account-select');
    if (accountWrap && !accountWrap.contains(event.target)) {
        accountWrap.classList.remove('open');
        document.getElementById('account-select-btn').setAttribute('aria-expanded', 'false');
    }
});

async function submitSuggestion() {
    const input = document.getElementById('suggestion-name');
    const error = document.getElementById('suggestion-error');
    const success = document.getElementById('suggestion-success');
    const submit = document.getElementById('suggestion-submit');
    const name = input.value.trim();

    error.style.display = 'none';
    success.style.display = 'none';
    if (!name) {
        showFeedbackPopup('error', 'Missing category', 'Please enter a service category.');
        return;
    }

    submit.disabled = true;
    try {
        const res = await fetch(routes.service_suggestions, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ name }),
        });
        const data = await res.json();
        if (!res.ok) throw data;

        const service = data.service || { id: '', name, icon: '🔧' };
        if (!services.some((item) => String(item.id) === String(service.id))) {
            services.push(service);
        }
        selectService(service);
        showFeedbackPopup('success', 'Catégorie sélectionnée', 'Votre suggestion a été enregistrée et sélectionnée.');
        setTimeout(closeSuggestionModal, 700);
    } catch (e) {
        showFeedbackPopup('error', 'Suggestion failed', e?.errors?.name?.[0] || e?.message || 'Unable to save this suggestion.');
    } finally {
        submit.disabled = false;
    }
}

function openCguModal() {
    document.getElementById('cgu-modal').style.display = 'flex';
    const box = document.getElementById('cgu-scroll-box');
    box.scrollTop = 0;
    document.getElementById('cgu-accept-btn').disabled = true;
    document.getElementById('cgu-hint').classList.remove('ok');
    document.getElementById('cgu-hint').textContent = "↓ Faites défiler jusqu'en bas pour accepter";
    document.getElementById('cgu-scroll-fill').style.width = '0%';
    setTimeout(() => {
        if (box.scrollHeight <= box.clientHeight + 12) {
            document.getElementById('cgu-accept-btn').disabled = false;
            document.getElementById('cgu-hint').classList.add('ok');
            document.getElementById('cgu-hint').textContent = '✓ Vous avez lu les CGU';
            document.getElementById('cgu-scroll-fill').style.width = '100%';
        }
    }, 50);
}

function closeCguModal() {
    document.getElementById('cgu-modal').style.display = 'none';
}

function handleCguScroll() {
    const box = document.getElementById('cgu-scroll-box');
    const max = Math.max(box.scrollHeight - box.clientHeight, 1);
    const pct = Math.min(100, Math.round((box.scrollTop / max) * 100));
    document.getElementById('cgu-scroll-fill').style.width = pct + '%';
    const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 12;
    if (atBottom) {
        document.getElementById('cgu-accept-btn').disabled = false;
        document.getElementById('cgu-hint').classList.add('ok');
        document.getElementById('cgu-hint').textContent = '✓ Vous avez lu les CGU';
        document.getElementById('cgu-scroll-fill').style.width = '100%';
    }
}

function acceptCgu() {
    document.getElementById('cgv-input').value = '1';
    document.getElementById('cgu-open-btn').style.display = 'none';
    document.getElementById('cgu-accepted').style.display = 'flex';
    document.getElementById('cgu-error').style.display = 'none';
    closeCguModal();
}

document.getElementById('gc-form').addEventListener('submit', function (event) {
    let valid = true;
    const messages = [];
    const role = document.getElementById('role-input').value;
    const phone = document.getElementById('phone-input').value.replace(/\D/g, '');
    if (phone.length !== 8) {
        messages.push('Veuillez saisir exactement 8 chiffres après +229 01.');
        valid = false;
    } else {
        document.getElementById('phone-error').style.display = 'none';
    }

    if (role === 'contractor' && !document.getElementById('specialty-input').value.trim()) {
        messages.push('Please choose a service category.');
        valid = false;
    }
    if (document.getElementById('cgv-input').value !== '1') {
        messages.push('Veuillez ouvrir et accepter les CGU avant de créer votre compte.');
        valid = false;
    }
    if (!valid) {
        event.preventDefault();
        showFeedbackPopup('error', 'Please check your information', messages);
        formatPhoneInput(document.getElementById('phone-input'));
        return;
    }

    document.getElementById('phone-input').value = '01' + phone;
});

(function init() {
    const currentRole = document.getElementById('role-input').value;
    setRole(currentRole, document.querySelector('.gc-role-btn.active'));
    selectAccountType(document.getElementById('account-type').value || 'individual');
    toggleCompany();
    renderServices();
    formatPhoneInput(document.getElementById('phone-input'));
    if (serverErrors.length) {
        showFeedbackPopup('error', 'Please check your information', serverErrors);
    }
    if (document.getElementById('cgv-input').value === '1') {
        acceptCgu();
    }
})();
</script>
</body>
</html>
