<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compléter votre inscription — Mesotravo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', Arial, sans-serif; background: #f5f5f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .gc-card { background: #fff; border-radius: 18px; padding: 36px 32px; max-width: 520px; width: 100%; box-shadow: 0 4px 32px rgba(0,0,0,.08); }
        .gc-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .gc-logo-img { height: 36px; }
        .gc-title { font-size: 1.4rem; font-weight: 800; color: #1c1412; margin-bottom: 4px; }
        .gc-sub { font-size: 0.9rem; color: #6b7280; margin-bottom: 28px; }
        .gc-avatar { display: flex; align-items: center; gap: 12px; background: #f9fafb; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; }
        .gc-avatar img { width: 44px; height: 44px; border-radius: 50%; }
        .gc-avatar-info strong { font-size: 0.95rem; color: #1c1412; display: block; }
        .gc-avatar-info span { font-size: 0.82rem; color: #6b7280; }
        .gc-section-title { font-size: 0.8rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 14px; }
        .gc-role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .gc-role-btn { border: 2px solid #e5e7eb; border-radius: 10px; padding: 14px; cursor: pointer; background: #fff; text-align: center; transition: all .2s; }
        .gc-role-btn.active, .gc-role-btn:hover { border-color: #f97316; background: #fff7ed; }
        .gc-role-btn .gc-role-icon { font-size: 1.6rem; display: block; margin-bottom: 4px; }
        .gc-role-btn .gc-role-label { font-size: 0.88rem; font-weight: 600; color: #1c1412; }
        .gc-field { margin-bottom: 16px; }
        .gc-label { display: block; font-size: 0.83rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .gc-req { color: #ef4444; }
        .gc-input { width: 100%; border: 1.5px solid #e5e7eb; border-radius: 8px; padding: 9px 12px; font-size: 0.9rem; color: #1c1412; outline: none; transition: border .2s; }
        .gc-input:focus { border-color: #f97316; }
        .gc-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .gc-error { color: #dc2626; font-size: 0.82rem; margin-top: 4px; }
        .gc-errors-block { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; font-size: 0.85rem; color: #dc2626; }
        .gc-submit { width: 100%; background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; border-radius: 10px; padding: 13px; font-size: 1rem; font-weight: 700; cursor: pointer; margin-top: 8px; transition: opacity .2s; }
        .gc-submit:hover { opacity: .9; }
        .gc-divider { border: none; border-top: 1px solid #e5e7eb; margin: 20px 0; }
    </style>
</head>
<body>
<div class="gc-card">
    <div class="gc-logo">
        <img src="/images/favicon.PNG" alt="Mesotravo" class="gc-logo-img">
        <strong style="font-size:1.1rem;color:#1c1412">Mesotravo</strong>
    </div>

    <h1 class="gc-title">Complétez votre inscription</h1>
    <p class="gc-sub">Quelques informations supplémentaires pour finaliser votre compte Google.</p>

    @if ($errors->any())
        <div class="gc-errors-block">
            @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
    @endif

    <!-- Avatar Google -->
    <div class="gc-avatar">
        @if ($googleUser['avatar'])
            <img src="{{ $googleUser['avatar'] }}" alt="Avatar">
        @endif
        <div class="gc-avatar-info">
            <strong>{{ $googleUser['name'] }}</strong>
            <span>{{ $googleUser['email'] }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('auth.google.complete.store') }}" id="gc-form">
        @csrf

        <!-- Type de compte -->
        <div class="gc-section-title">Je suis</div>
        <div class="gc-role-grid" id="role-grid">
            <button type="button" class="gc-role-btn {{ $role === 'client' ? 'active' : '' }}" onclick="setRole('client')">
                <span class="gc-role-icon">🏠</span>
                <span class="gc-role-label">Client</span>
                <small style="color:#6b7280;font-size:.78rem">Je publie des missions</small>
            </button>
            <button type="button" class="gc-role-btn {{ $role === 'contractor' ? 'active' : '' }}" onclick="setRole('contractor')">
                <span class="gc-role-icon">🔧</span>
                <span class="gc-role-label">Prestataire</span>
                <small style="color:#6b7280;font-size:.78rem">Je réalise des missions</small>
            </button>
        </div>
        <input type="hidden" name="role" id="role-input" value="{{ $role }}">

        <hr class="gc-divider">

        <!-- Champs communs -->
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

        <div class="gc-field">
            <label class="gc-label">Téléphone <span class="gc-req">*</span></label>
            <input class="gc-input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="Ex : 97 00 00 00" required>
        </div>

        <!-- Champs CLIENT -->
        <div id="client-fields">
            <div class="gc-field">
                <label class="gc-label">Type de compte <span class="gc-req">*</span></label>
                <select class="gc-input" name="account_type" id="account-type" onchange="toggleCompany()">
                    <option value="individual" {{ old('account_type','individual')==='individual'?'selected':'' }}>Particulier</option>
                    <option value="company" {{ old('account_type')==='company'?'selected':'' }}>Entreprise</option>
                </select>
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

        <!-- Champs CONTRACTOR -->
        <div id="contractor-fields" style="display:none">
            <div class="gc-field">
                <label class="gc-label">Spécialité principale <span class="gc-req">*</span></label>
                <input class="gc-input" type="text" name="specialty" value="{{ old('specialty') }}" placeholder="Ex : Plomberie">
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
        </div>

        <button type="submit" class="gc-submit">Créer mon compte →</button>
    </form>
</div>

<script>
function setRole(role) {
    document.getElementById('role-input').value = role;
    document.querySelectorAll('.gc-role-btn').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('client-fields').style.display = role === 'client' ? '' : 'none';
    document.getElementById('contractor-fields').style.display = role === 'contractor' ? '' : 'none';
}
function toggleCompany() {
    const v = document.getElementById('account-type').value;
    document.getElementById('company-field').style.display = v === 'company' ? '' : 'none';
}
// Init
(function() {
    const r = document.getElementById('role-input').value;
    document.getElementById('client-fields').style.display = r === 'client' ? '' : 'none';
    document.getElementById('contractor-fields').style.display = r === 'contractor' ? '' : 'none';
    toggleCompany();
})();
</script>
</body>
</html>
