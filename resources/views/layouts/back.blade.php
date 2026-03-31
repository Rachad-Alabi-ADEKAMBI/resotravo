{{--
    FICHIER  : resources/views/layouts/back.blade.php

    Sections disponibles :
      @section('title')    → titre onglet
      @section('styles')   → CSS additionnel par page
      @section('content')  → contenu principal (composant Vue)
      @section('scripts')  → JS additionnel par page

    Usage :
      @extends('layouts.back')
      @section('title', 'Tableau de bord')
      @section('content')
        <admin-dashboard-component :admin="..."></admin-dashboard-component>
      @endsection
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Mobile / PWA --}}
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="ResoTravo" />
    <meta name="theme-color" content="#1C1412" />


    <title>@yield('title', 'Tableau de bord') — ResoTravo</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet" />

    {{-- Vite : charge resources/js/app.js et resources/css/app.css --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ── RESET ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }

        /* ── VARIABLES GLOBALES (partagees avec front.blade.php) ── */
        :root {
            --or:  #F97316;
            --or2: #EA580C;
            --or3: #FFF7ED;
            --am:  #FCD34D;
            --dk:  #1C1412;
            --dk2: #110D0B;
            --cr:  #FEF3E2;
            --cr2: #FDF0DC;
            --gr:  #4A3F3A;
            --grl: #E8DDD4;
            --grm: #8A7D78;
            --wh:  #FFFFFF;

            /* Sidebar */
            --sidebar-w: 250px;
            /* Topbar back-office */
            --topbar-h: 64px;
        }

        /* ── BASE ── */
        html { font-size: 16px; scroll-behavior: smooth }
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            background: #f8f4f0;
            color: var(--dk);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.65;
        }
        ::-webkit-scrollbar       { width: 4px }
        ::-webkit-scrollbar-track { background: var(--cr) }
        ::-webkit-scrollbar-thumb { background: var(--or); border-radius: 99px }

        /* ── LAYOUT ── */
        .ab-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Zone de contenu : decalee de la largeur de la sidebar sur desktop */
        .ab-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin-left: var(--sidebar-w);
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
            overflow-x: hidden;
        }
        @media(max-width: 899px) {
            .ab-main { margin-left: 0 }
        }

        /* Burger — caché sur desktop, visible uniquement mobile */
        .ad-burger { display: none !important }
        @media(max-width: 899px) {
            .ad-burger { display: flex !important }
        }

        /* ── TOPBAR FIXE (back-office) ── */
        .ab-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            z-index: 150;
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--grl);
            display: flex;
            align-items: center;
            padding: 0 24px;
            transition: left .3s cubic-bezier(.4,0,.2,1), box-shadow .3s;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
        }
        @media(max-width: 899px) {
            .ab-topbar { left: 0 }
        }

        /* ── SIDEBAR FIXE ── */
        /* La sidebar doit être fixed pour rester visible au scroll */
        /* (à appliquer dans partials/sidebar.blade.php si ce n'est pas déjà le cas) */
        /* #ab-sidebar { position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; } */


        .ab-avatar-photo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--or);
        }
    </style>

    @yield('styles')
</head>
<body>

<div class="ab-layout">

    {{--
        SIDEBAR (hors du #app Vue — Blade pur)
        Chemin : resources/views/partials/sidebar.blade.php
        $active : slug de la page active (ex: 'dashboard', 'validation', 'clients'...)
    --}}
    @include('partials.sidebar', [
        'active' => $active ?? 'dashboard',
    ])

    {{-- MAIN — #app ici : Vue monte uniquement sur le contenu --}}
    @php
        /*
         * CORRECTION : on utilise route('profile.photo.user', ['userId' => $authUser->id])
         * au lieu de route('profile.photo') qui retournait toujours la photo
         * de l'utilisateur connecté, causant un affichage incorrect pour tous.
         *
         * L'URL est maintenant unique par utilisateur (/profile/photo/2, /profile/photo/4…)
         * ce qui évite aussi les conflits de cache navigateur entre comptes.
         *
         * Le type de document est déterminé côté serveur dans la route :
         *   - client      → type 'photo_profil'
         *   - contractor  → type 'photo'
         */
        $authUser = Auth::user();
        $photoType = $authUser?->role === 'client' ? 'photo_profil' : 'photo';
        $hasPhoto  = \App\Models\Document::where('user_id', $authUser?->id)
                        ->where('type', $photoType)
                        ->where('status', 'approved')
                        ->exists();
        // URL unique par utilisateur → chaque avatar affiche la bonne photo
        $photoUrl  = $hasPhoto
            ? route('profile.photo.user', ['userId' => $authUser->id])
            : null;
    @endphp
    @php
        $messagesUrl = match($authUser?->role) {
            'admin'      => route('admin.messages'),
            'client'     => route('client.messages'),
            'contractor' => route('contractor.messages'),
            default      => null,
        };
    @endphp
    <div class="ab-main" id="resotravo-app" data-photo="{{ $photoUrl }}">
        @yield('content')
    </div>

</div>


<script>
(function () {
    var sidebar = document.getElementById('ab-sidebar');
    var overlay = document.getElementById('ab-sidebar-overlay');
    var isOpen  = false;

    function openSidebar() {
        if (!sidebar) return;
        isOpen = true;
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (!sidebar) return;
        isOpen = false;
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        window.dispatchEvent(new CustomEvent('ab-sidebar-close'));
    }

    /* Ecoute le toggle emis par AdminDashboardComponent via emitToggleSidebar() */
    window.addEventListener('ab-sidebar-toggle', function (e) {
        if (e.detail && e.detail.open) { openSidebar(); } else { closeSidebar(); }
    });

    /* Clic sur l'overlay → fermer */
    overlay.addEventListener('click', closeSidebar);

    /* Echap → fermer */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) closeSidebar();
    });

    /* Resize → fermer si on passe en desktop */
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 900 && isOpen) closeSidebar();
    }, { passive: true });
}());
</script>

<script>
(function () {
    var photoUrl = document.getElementById('resotravo-app')?.dataset?.photo;
    if (!photoUrl) return;

    // Toutes les classes d'avatar utilisées dans les composants Vue
    var AVATAR_SELECTOR = [
        '.ctr-avatar',
        '.amis-avatar',
        '.cd-avatar',
        '.pm-avatar',
        '.rv-avatar',
        '.cp-avatar',
        '.cac-avatar',
        '.msg-avatar',
        '.ab-user-av',
    ].join(', ');

    function injectPhotos() {
        document.querySelectorAll(AVATAR_SELECTOR).forEach(function (el) {
            if (el.dataset.photoInjected) return;
            el.dataset.photoInjected = '1';
            el.style.padding  = '0';
            el.style.overflow = 'hidden';
            el.innerHTML = '<img src="' + photoUrl + '" class="ab-avatar-photo" alt="Photo de profil" onerror="this.parentElement.removeAttribute(\'data-photo-injected\')" />';
        });
    }

    // 1 — Premier essai rapide (composants déjà montés)
    setTimeout(injectPhotos, 300);
    // 2 — Deuxième essai après montage complet de Vue
    setTimeout(injectPhotos, 1000);
    // 3 — MutationObserver : détecte quand Vue injecte les avatars dans le DOM
    var observer = new MutationObserver(function () {
        injectPhotos();
    });
    observer.observe(document.getElementById('resotravo-app') || document.body, {
        childList: true,
        subtree:   true,
    });
    // Arrêter l'observer après 5s pour ne pas consommer de ressources inutilement
    setTimeout(function () { observer.disconnect(); }, 5000);

    // 4 — Re-injecter si navigation sidebar
    window.addEventListener('ab-sidebar-toggle', function () {
        setTimeout(injectPhotos, 300);
    });
}());
</script>

{{-- ── Popup nouveau message ── --}}
<div id="rt-msg-popup" aria-live="polite">
    <div id="rt-msg-popup-inner">
        <div id="rt-msg-popup-head">
            <span id="rt-msg-popup-icon">💬</span>
            <span id="rt-msg-popup-title">Nouveau message</span>
            <button id="rt-msg-popup-close" aria-label="Fermer">✕</button>
        </div>
        <div id="rt-msg-popup-body"></div>
        <div id="rt-msg-popup-foot">
            @if($messagesUrl)
            <a id="rt-msg-popup-open" href="{{ $messagesUrl }}">Ouvrir →</a>
            @endif
        </div>
    </div>
</div>

<style>
#rt-msg-popup{
    position:fixed;bottom:24px;right:24px;z-index:9999;
    transform:translateY(120%);opacity:0;
    transition:transform .35s cubic-bezier(.34,1.56,.64,1),opacity .25s ease;
    pointer-events:none;
}
#rt-msg-popup.rt-visible{
    transform:translateY(0);opacity:1;pointer-events:auto;
}
#rt-msg-popup-inner{
    background:#fff;border-radius:14px;
    box-shadow:0 8px 32px rgba(0,0,0,.18);
    padding:14px 16px 12px;min-width:280px;max-width:340px;
    border-left:4px solid #F97316;
}
#rt-msg-popup-head{
    display:flex;align-items:center;gap:8px;margin-bottom:6px;
}
#rt-msg-popup-icon{font-size:18px;flex-shrink:0}
#rt-msg-popup-title{
    font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;
    color:#1C1412;flex:1;
}
#rt-msg-popup-close{
    background:none;border:none;cursor:pointer;font-size:14px;
    color:#999;padding:2px 4px;line-height:1;
    border-radius:4px;transition:background .15s;
}
#rt-msg-popup-close:hover{background:#f0f0f0;color:#333}
#rt-msg-popup-body{
    font-family:'Poppins',sans-serif;font-size:12.5px;color:#555;
    line-height:1.7;margin-bottom:10px;word-break:break-word;
}
#rt-msg-popup-foot{display:flex;justify-content:flex-end}
#rt-msg-popup-open{
    display:inline-block;padding:6px 16px;border-radius:8px;
    background:linear-gradient(135deg,#F97316,#EA580C);
    color:#fff;font-family:'Poppins',sans-serif;font-size:12px;
    font-weight:700;text-decoration:none;
    transition:opacity .15s;
}
#rt-msg-popup-open:hover{opacity:.88}
</style>

<script>
(function () {
    var prevTotal  = null;
    var popupTimer = null;
    var messagesUrl = @json($messagesUrl);

    /* ── Sidebar badge ── */
    function updateSidebarBadge(total) {
        var el = document.getElementById('sidebar-msg-count');
        if (!el) return;
        if (total > 0) {
            el.textContent = ' (' + (total > 99 ? '99+' : total) + ')';
            el.style.display = 'inline';
        } else {
            el.style.display = 'none';
            el.textContent   = '';
        }
    }

    /* ── Échapper le HTML pour éviter les injections ── */
    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }

    /* ── Popup ── */
    function showPopup(latest) {
        var popup   = document.getElementById('rt-msg-popup');
        var body    = document.getElementById('rt-msg-popup-body');
        var openBtn = document.getElementById('rt-msg-popup-open');
        if (!popup || !body || !latest) return;

        body.innerHTML =
            '<strong style="color:#1C1412">De\u00a0:</strong> ' + escHtml(latest.sender_name) +
            '<br><span style="color:#8A7D78">Mission\u00a0:</span> ' + escHtml(latest.mission_service);

        /* Mettre à jour le lien vers la conversation exacte */
        if (openBtn && latest.conversation_id) {
            openBtn.href = messagesUrl + '?conversation=' + latest.conversation_id;
        }

        popup.classList.add('rt-visible');

        clearTimeout(popupTimer);
        popupTimer = setTimeout(hidePopup, 8000);
    }

    function hidePopup() {
        var popup = document.getElementById('rt-msg-popup');
        if (popup) popup.classList.remove('rt-visible');
    }

    var closeBtn = document.getElementById('rt-msg-popup-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            hidePopup();
            clearTimeout(popupTimer);
        });
    }

    /* ── Polling ── */
    function poll() {
        fetch('/unread-messages', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;

            var total = data.total || 0;

            updateSidebarBadge(total);

            /* Diffuser aux composants Vue */
            window.dispatchEvent(new CustomEvent('rt-unread-update', { detail: data }));

            /* Afficher popup seulement si nouveau(x) message(s) */
            if (prevTotal !== null && total > prevTotal && data.latest) {
                showPopup(data.latest);
            }

            prevTotal = total;
        })
        .catch(function () {});
    }

    /* Premier appel après montage Vue (2 s), puis toutes les 15 s */
    setTimeout(poll, 2000);
    setInterval(poll, 15000);
}());
</script>

@yield('scripts')
</body>
</html>