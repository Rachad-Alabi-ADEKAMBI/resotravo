{{-- resources/views/partials/sidebar.blade.php --}}
@php
    $role     = Auth::user()->role ?? 'client';
    $active   = $active ?? 'dashboard';
    $user     = Auth::user();
    $initials = strtoupper(substr($user->name ?? 'U', 0, 1) . substr(explode(' ', $user->name ?? 'U ')[1] ?? '', 0, 1));

    if ($role === 'admin') {
        $pendingDocs = \App\Models\User::whereIn('role', ['contractor', 'client'])->where('status','pending')->count();
    }

    if ($role === 'contractor') {
        $contractorStatus = $user->status ?? 'pending';
        $docProgress      = $user->documentProgress();
        $contractor       = $user->contractor;
        $accreditation    = $contractor?->accreditation ?? 'none';
        $accredLabels     = [
            'home'     => '🏠 Domicile',
            'business' => '🏢 Entreprise',
            'both'     => '🏠+🏢 Dom. & Entr.',
        ];
    }
@endphp

<aside class="ab-sidebar" id="ab-sidebar">

    {{-- Logo --}}
    <div class="ab-sidebar-logo">
        <a href="{{ route('home') }}" class="ab-logo">
            <div class="ab-logo-mark">M</div>
            <span class="ab-logo-name">MESO<em>TRAVO</em></span>
        </a>
        @if($role === 'admin')
            <span class="ab-role-badge admin">ADMIN</span>
        @elseif($role === 'contractor')
            <span class="ab-role-badge contractor">PRO</span>
        @elseif($role === 'talent')
            <span class="ab-role-badge talent">TALENT</span>
        @endif
    </div>

    {{-- ── STATUT PRESTATAIRE sous le logo ── --}}
    @if($role === 'contractor')
    <div class="ab-contractor-status">

        {{-- Certification --}}
        @if($contractorStatus === 'approved')
            <div class="ab-status-chip ab-status-certified">
                <span class="ab-status-dot dot-green"></span> 🏅 Profil certifié
            </div>
        @elseif($contractorStatus === 'pending')
            @php $da = $docProgress['approved'] ?? 0; $dt = $docProgress['total'] ?? 5; @endphp
            <div class="ab-status-chip ab-status-pending">
                <span class="ab-status-dot dot-orange"></span>
                Dossier en attente ({{ $da }}/{{ $dt }})
            </div>
        @elseif($contractorStatus === 'rejected')
            <div class="ab-status-chip ab-status-rejected">
                <span class="ab-status-dot dot-red"></span> Dossier refusé
            </div>
        @endif

        {{-- Accréditation --}}
        @if($contractorStatus === 'approved' && $accreditation !== 'none')
            <div class="ab-status-chip ab-status-accred">
                {{ $accredLabels[$accreditation] ?? $accreditation }}
            </div>
        @elseif($contractorStatus === 'approved' && $accreditation === 'none')
            <div class="ab-status-chip ab-status-no-accred">
                ⚠️ Sans accréditation
            </div>
        @endif
    </div>
    @endif

    {{-- Navigation --}}
    <nav class="ab-nav">

        {{-- ══════ ADMIN ══════ --}}
        @if($role === 'admin')
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Vue globale</div>
            <a class="ab-nav-item {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <span class="ab-nav-icon">📊</span><span>Tableau de bord</span>
            </a>
            <a class="ab-nav-item {{ $active === 'validation' ? 'active' : '' }}" href="{{ route('admin.validation') }}">
                <span class="ab-nav-icon">🛡️</span><span>Validation dossiers</span>
                @if(isset($pendingDocs) && $pendingDocs > 0)
                    <span class="ab-nav-badge">{{ $pendingDocs }}</span>
                @endif
            </a>
            <a class="ab-nav-item {{ $active === 'contractors' ? 'active' : '' }}" href="{{ route('admin.contractors.page') }}">
                <span class="ab-nav-icon">👷</span><span>Prestataires</span>
            </a>
            <a class="ab-nav-item {{ $active === 'clients' ? 'active' : '' }}" href="{{ route('admin.clients.page') }}">
                <span class="ab-nav-icon">👤</span><span>Clients</span>
            </a>
            <a class="ab-nav-item {{ $active === 'talents' ? 'active' : '' }}" href="{{ route('admin.talents.page') }}">
                <span class="ab-nav-icon">⭐</span><span>Talents</span>
            </a>
            <a class="ab-nav-item {{ $active === 'accreditation' ? 'active' : '' }}" href="{{ route('admin.accreditation') }}">
                <span class="ab-nav-icon">🏅</span><span>Accréditations</span>
            </a>
            <a class="ab-nav-item {{ $active === 'missions' ? 'active' : '' }}" href="{{ route('admin.missions') }}">
                <span class="ab-nav-icon">📋</span><span>Missions</span>
            </a>
        </div>
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Finance</div>
            <a class="ab-nav-item {{ $active === 'revenus' ? 'active' : '' }}" href="{{ route('admin.revenus') }}">
                <span class="ab-nav-icon">💸</span><span>Revenus & Finances</span>
            </a>
        </div>
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Contenu</div>
            <a class="ab-nav-item {{ $active === 'tenders' ? 'active' : '' }}" href="{{ route('admin.tenders.admin-page') }}">
                <span class="ab-nav-icon">📝</span><span>Appels d'offres</span>
            </a>
            <a class="ab-nav-item {{ $active === 'services' ? 'active' : '' }}" href="{{ route('admin.services.page') }}">
                <span class="ab-nav-icon">🔧</span><span>Catalogue services</span>
            </a>
            <a class="ab-nav-item {{ $active === 'consulting' ? 'active' : '' }}" href="{{ route('admin.consulting.page') }}">
                <span class="ab-nav-icon">💬</span><span>Allô Conseils</span>
            </a>
        </div>
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Outils</div>
            <a class="ab-nav-item {{ $active === 'disputes' ? 'active' : '' }}" href="{{ route('admin.disputes.page') }}">
                <span class="ab-nav-icon">⚖️</span><span>Litiges</span>
            </a>
            <a class="ab-nav-item {{ $active === 'mail' ? 'active' : '' }}" href="{{ route('admin.mail') }}">
                <span class="ab-nav-icon">@</span><span>Mail</span>
            </a>
            <a class="ab-nav-item {{ $active === 'configuration' ? 'active' : '' }}" href="{{ route('admin.configuration') }}">
                <span class="ab-nav-icon">🔧</span><span>Configuration</span>
            </a>
            <a class="ab-nav-item {{ $active === 'parameters' ? 'active' : '' }}" href="{{ route('admin.parameters') }}">
                <span class="ab-nav-icon">⚙️</span><span>Paramètres</span>
            </a>
        </div>
        @endif

        {{-- ══════ CLIENT ══════ --}}
        @if($role === 'client')
        @php $clientUser = Auth::user(); $clientApproved = $clientUser->status === 'approved'; @endphp

        {{-- Badge statut --}}
        <div class="ab-contractor-status">
            @if($clientApproved)
                <div class="ab-status-chip ab-status-certified">
                    <span class="ab-status-dot dot-green"></span> ✅ Identité vérifiée
                </div>
            @else
                <div class="ab-status-chip ab-status-pending">
                    <span class="ab-status-dot dot-orange"></span> ⏳ Validation en attente
                </div>
            @endif
        </div>

        {{-- Navigation — toujours visible --}}
        <div class="ab-nav-section">
            <a class="ab-nav-item {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
                <span class="ab-nav-icon">🏠</span><span>Tableau de bord</span>
            </a>
            <a class="ab-nav-item {{ $active === 'missions' ? 'active' : '' }}" href="{{ route('client.missions.page') }}">
                <span class="ab-nav-icon">📋</span><span>Mes commandes</span>
            </a>
        </div>

        {{-- Dossier — toujours visible --}}
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Mon dossier</div>
            <a class="ab-nav-item {{ $active === 'dossier' ? 'active' : '' }}" href="{{ route('client.dossier') }}">
                <span class="ab-nav-icon">📂</span><span>Mon dossier</span>
                @if($clientApproved)
                    <span class="ab-nav-badge green">✓</span>
                @else
                    <span class="ab-nav-badge orange">⏳</span>
                @endif
            </a>
        </div>

        {{-- Finance — toujours visible --}}
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Finance</div>
            <a class="ab-nav-item {{ $active === 'paiements' ? 'active' : '' }}" href="{{ route('client.paiements') }}">
                <span class="ab-nav-icon">💳</span><span>Mes paiements</span>
            </a>
        </div>

        {{-- Compte — toujours visible --}}
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Compte</div>
            <a class="ab-nav-item {{ $active === 'messages' ? 'active' : '' }}" href="{{ route('client.messages') }}" id="sidebar-msg-link">
                <span class="ab-nav-icon">💬</span><span>Messages<span id="sidebar-msg-count" class="ab-msg-count"></span></span>
            </a>
            <a class="ab-nav-item {{ $active === 'parameters' ? 'active' : '' }}" href="{{ route('client.parameters') }}">
                <span class="ab-nav-icon">👤</span><span>Mon profil</span>
            </a>
        </div>

        @endif

        {{-- ══════ CONTRACTOR ══════ --}}
        @if($role === 'contractor')
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Mon activité</div>
            <a class="ab-nav-item {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('contractor.dashboard') }}">
                <span class="ab-nav-icon">🏠</span><span>Tableau de bord</span>
            </a>
            <a class="ab-nav-item {{ $active === 'missions' ? 'active' : '' }}" href="{{ route('contractor.missions.page') }}">
                <span class="ab-nav-icon">📋</span><span>Mes missions</span>
            </a>
        </div>

        <div class="ab-nav-section">
            <div class="ab-section-lbl">Dossier & Certification</div>

            <a class="ab-nav-item {{ $active === 'dossier' ? 'active' : '' }}" href="{{ route('contractor.dossier') }}">
                <span class="ab-nav-icon">📂</span><span>Mon dossier</span>
                @if($contractorStatus === 'approved')
                    <span class="ab-nav-badge green">✓</span>
                @elseif($contractorStatus === 'pending')
                    @php $da = $docProgress['approved'] ?? 0; $dt = $docProgress['total'] ?? 5; @endphp
                    <span class="ab-nav-badge orange">{{ $da }}/{{ $dt }}</span>
                @elseif($contractorStatus === 'rejected')
                    <span class="ab-nav-badge red">✗</span>
                @endif
            </a>

            <a class="ab-nav-item {{ $active === 'accreditation' ? 'active' : '' }}" href="{{ route('contractor.accreditation') }}">
                <span class="ab-nav-icon">🏅</span><span>Mon accréditation</span>
                @if($accreditation === 'home')
                    <span class="ab-nav-badge blue">🏠</span>
                @elseif($accreditation === 'business')
                    <span class="ab-nav-badge teal">🏢</span>
                @elseif($accreditation === 'both')
                    <span class="ab-nav-badge purple">🏠+🏢</span>
                @elseif($contractorStatus === 'approved')
                    <span class="ab-nav-badge" style="background:#f59e0b">⚠️</span>
                @endif
            </a>
        </div>

        <div class="ab-nav-section">
            <div class="ab-section-lbl">Finance</div>
            <a class="ab-nav-item {{ $active === 'revenus' ? 'active' : '' }}" href="{{ route('contractor.revenus') }}">
                <span class="ab-nav-icon">💰</span><span>Mes revenus</span>
            </a>
            <a class="ab-nav-item {{ $active === 'obligations' ? 'active' : '' }}" href="{{ route('contractor.obligations') }}">
                <span class="ab-nav-icon">🏛️</span><span>Obligations fiscales</span>
            </a>
        </div>

        <div class="ab-nav-section">
            <div class="ab-section-lbl">Compte</div>
            <a class="ab-nav-item {{ $active === 'messages' ? 'active' : '' }}" href="{{ route('contractor.messages') }}" id="sidebar-msg-link">
                <span class="ab-nav-icon">💬</span><span>Messages<span id="sidebar-msg-count" class="ab-msg-count"></span></span>
            </a>
            <a class="ab-nav-item {{ $active === 'parameters' ? 'active' : '' }}" href="{{ route('contractor.parameters') }}">
                <span class="ab-nav-icon">👤</span><span>Mon profil</span>
            </a>
        </div>
        @endif

        {{-- ══════ TALENT ══════ --}}
        @if($role === 'talent')
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Mon espace</div>
            <a class="ab-nav-item {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('talent.dashboard') }}">
                <span class="ab-nav-icon">🏠</span><span>Tableau de bord</span>
            </a>
            <button class="ab-nav-item" onclick="wip('Appels d\'offres')"><span class="ab-nav-icon">📋</span><span>Appels d'offres</span></button>
            <button class="ab-nav-item" onclick="wip('Mes candidatures')"><span class="ab-nav-icon">📩</span><span>Mes candidatures</span></button>
        </div>
        <div class="ab-nav-section">
            <div class="ab-section-lbl">Profil</div>
            <button class="ab-nav-item" onclick="wip('Mon profil')"><span class="ab-nav-icon">👤</span><span>Mon profil</span></button>
            <button class="ab-nav-item" onclick="wip('Diplômes')"><span class="ab-nav-icon">🎓</span><span>Diplômes</span></button>
            <button class="ab-nav-item" onclick="wip('Messagerie')"><span class="ab-nav-icon">💬</span><span>Messagerie</span></button>
        </div>
        @endif

    </nav>

    {{-- Bottom --}}
    <div class="ab-sidebar-bottom">
        <div class="ab-user-chip">
            <div class="ab-user-av" style="background:{{ $role==='admin'?'#ef4444':($role==='contractor'?'var(--or,#F97316)':($role==='talent'?'#8b5cf6':'#3b82f6')) }}">
                {{ $initials }}
            </div>
            <div class="ab-user-info">
                <div class="ab-user-name">{{ $user->name ?? 'Utilisateur' }}</div>
                <div class="ab-user-role">
                    @if($role==='admin') Administrateur
                    @elseif($role==='client') Client
                    @elseif($role==='contractor') Prestataire
                    @elseif($role==='talent') Talent
                    @endif
                </div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" style="margin-top:8px">
            @csrf
            <button type="submit" class="ab-logout-btn">&#x21B5; Déconnexion</button>
        </form>
    </div>

</aside>

<div class="ab-sidebar-overlay" id="ab-sidebar-overlay"></div>

<div id="wip-overlay" style="display:none" onclick="wipClose()">
    <div id="wip-modal" onclick="event.stopPropagation()">
        <div id="wip-icon">🚧</div>
        <h3>Fonctionnalité en cours de développement</h3>
        <p id="wip-feature"></p>
        <p id="wip-sub">Cette section sera disponible très prochainement.<br>Merci de votre patience !</p>
        <button id="wip-close-btn" onclick="wipClose()">Compris →</button>
    </div>
</div>

<style>
.ab-sidebar{width:250px;flex-shrink:0;background:var(--dk2,#110D0B);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:50;transition:transform .3s cubic-bezier(.4,0,.2,1);transform:translateX(0)}
@media(max-width:899px){.ab-sidebar{transform:translateX(-100%);box-shadow:4px 0 24px rgba(0,0,0,.3)}.ab-sidebar.open{transform:translateX(0)}}
.ab-sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:49;display:none;cursor:pointer}.ab-sidebar-overlay.open{display:block}
/* Logo */
.ab-sidebar-logo{padding:20px 18px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0}
.ab-logo{display:flex;align-items:center;gap:8px;text-decoration:none}
.ab-logo-mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--or,#F97316),var(--or2,#EA580C));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:16px;flex-shrink:0}
.ab-logo-name{font-size:16px;font-weight:800;color:#fff;letter-spacing:-.3px}.ab-logo-name em{font-style:normal;color:var(--or,#F97316)}
.ab-role-badge{font-size:9px;font-weight:700;padding:2px 7px;border-radius:99px;letter-spacing:.5px;flex-shrink:0}
.ab-role-badge.admin{background:#ef4444;color:#fff}.ab-role-badge.contractor{background:var(--or,#F97316);color:#fff}.ab-role-badge.talent{background:#8b5cf6;color:#fff}
/* Statut contractor */
.ab-contractor-status{padding:8px 12px 6px;display:flex;flex-direction:column;gap:5px;border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:6px}
.ab-status-chip{display:flex;align-items:center;gap:7px;padding:5px 9px;border-radius:7px;font-size:11px;font-weight:700;line-height:1.3}
.ab-status-certified{background:rgba(34,197,94,.15);color:#4ade80}
.ab-status-pending{background:rgba(251,191,36,.12);color:#fbbf24}
.ab-status-rejected{background:rgba(239,68,68,.12);color:#f87171}
.ab-status-accred{background:rgba(99,102,241,.15);color:#a5b4fc}
.ab-status-no-accred{background:rgba(245,158,11,.12);color:#fbbf24}
.ab-status-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.dot-green{background:#4ade80}.dot-orange{background:#fbbf24}.dot-red{background:#f87171}
/* Nav */
.ab-nav{flex:1;overflow-y:auto;padding:0 10px 10px}
.ab-nav-section{margin-bottom:10px}
.ab-section-lbl{font-size:10px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:1px;padding:0 8px;margin-bottom:4px;margin-top:6px}
.ab-nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:9px;cursor:pointer;transition:all .18s;margin-bottom:2px;color:#aaa;font-size:13.5px;font-weight:500;text-decoration:none;background:none;border:none;width:100%;text-align:left;font-family:'Poppins',sans-serif}
.ab-nav-item:hover{background:rgba(255,255,255,.07);color:#fff}
.ab-nav-item.active{background:var(--or,#F97316)!important;color:#fff!important;font-weight:700}
.ab-nav-item.active:hover{background:var(--or2,#EA580C)!important}
.ab-nav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center}
/* Badges */
.ab-nav-badge{font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;margin-left:auto;background:#ef4444;color:#fff;flex-shrink:0}
.ab-nav-badge.orange{background:var(--or,#F97316)}.ab-nav-badge.green{background:#22c55e}.ab-nav-badge.red{background:#ef4444}
.ab-nav-badge.blue{background:#3b82f6}.ab-nav-badge.teal{background:#14b8a6}.ab-nav-badge.purple{background:#8b5cf6}
.ab-msg-count{display:none;font-size:12px;font-weight:700;color:inherit;margin-left:4px}
/* Bottom */
.ab-sidebar-bottom{padding:12px 10px;border-top:1px solid rgba(255,255,255,.08);flex-shrink:0}
.ab-user-chip{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;background:rgba(255,255,255,.05)}
.ab-user-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0}
.ab-user-info{min-width:0;flex:1;overflow:hidden}
.ab-user-name{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ab-user-role{font-size:11px;color:#777}
.ab-logout-btn{width:100%;padding:8px;border-radius:8px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#aaa;font-size:12.5px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;transition:all .18s;text-align:center}
.ab-logout-btn:hover{background:rgba(239,68,68,.2);color:#fca5a5;border-color:#ef4444}
/* WIP */
#wip-overlay{position:fixed;inset:0;background:rgba(17,13,11,.65);backdrop-filter:blur(4px);z-index:999;display:flex;align-items:center;justify-content:center;padding:20px;animation:wip-fade-in .2s ease}
@keyframes wip-fade-in{from{opacity:0}to{opacity:1}}
#wip-modal{background:#fff;border-radius:20px;padding:40px 36px;max-width:400px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:wip-slide-up .25s ease}
@keyframes wip-slide-up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
#wip-icon{font-size:52px;margin-bottom:16px;animation:wip-bounce .6s ease infinite alternate}
@keyframes wip-bounce{from{transform:translateY(0)}to{transform:translateY(-6px)}}
#wip-modal h3{font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:#1C1412;margin-bottom:10px;line-height:1.3}
#wip-feature{display:inline-block;background:#FFF7ED;color:#F97316;border:1.5px solid #FCD34D;border-radius:99px;padding:4px 16px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;margin-bottom:14px}
#wip-sub{font-family:'Poppins',sans-serif;font-size:14px;color:#7C6A5A;line-height:1.7;margin-bottom:28px}
#wip-close-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:12px 32px;border-radius:10px;background:linear-gradient(135deg,#F97316,#EA580C);color:#fff;font-family:'Poppins',sans-serif;font-size:14.5px;font-weight:700;border:none;cursor:pointer;transition:all .2s;box-shadow:0 4px 14px rgba(249,115,22,.35);width:100%}
#wip-close-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(249,115,22,.4)}
</style>

<script>
function wip(feature){
    document.getElementById('wip-feature').textContent=feature||'Cette fonctionnalité';
    document.getElementById('wip-overlay').style.display='flex';
    document.body.style.overflow='hidden';
}
function wipClose(){
    document.getElementById('wip-overlay').style.display='none';
    document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')wipClose();});

(function(){
    function scrollActiveSidebarItem(){
        var nav = document.querySelector('#ab-sidebar .ab-nav');
        var active = nav ? nav.querySelector('.ab-nav-item.active') : null;
        if(!nav || !active) return;

        var navRect = nav.getBoundingClientRect();
        var activeRect = active.getBoundingClientRect();
        var target = nav.scrollTop + (activeRect.top - navRect.top) - (nav.clientHeight / 2) + (active.offsetHeight / 2);

        nav.scrollTo({
            top: Math.max(0, target),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', function(){
            setTimeout(scrollActiveSidebarItem, 80);
        });
    }else{
        setTimeout(scrollActiveSidebarItem, 80);
    }

    window.addEventListener('ab-sidebar-toggle', function(e){
        if(e.detail && e.detail.open){
            setTimeout(scrollActiveSidebarItem, 280);
        }
    });
})();
</script>
