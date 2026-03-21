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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        /* ── Avatar photo de profil ── */
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

@yield('scripts')
</body>
</html>