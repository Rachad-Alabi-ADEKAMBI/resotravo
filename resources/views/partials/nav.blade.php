{{--
  COMPOSANT : resources/views/partials/nav.blade.php
  Usage     : @include('partials.nav', ['active' => 'home'])
  $active   : 'home' | 'services' | 'talent' | 'market' | 'consulting'
--}}

@php
    $links = [
        ['route' => 'home',       'label' => 'Accueil',        'icon' => '🏠', 'href' => route('home')],
      ['route' => 'services', 'label' => 'Services', 'icon' => '🔧', 'href' => route('home') . '#services'],
        ['route' => 'talent',     'label' => 'Espace Talents', 'icon' => '🌟', 'href' => route('talent')],
        ['route' => 'market',     'label' => 'Appels d\'offres','icon' => '📋', 'href' => route('market')],
        ['route' => 'consulting', 'label' => 'Allô Conseils',  'icon' => '📞', 'href' => route('consulting')],
    ];
    $current = $active ?? '';
@endphp

<nav id="main-nav">

    {{-- Logo --}}
    <a class="logo" href="{{ route('home') }}">
        <div class="logo-mark">R</div>
        <span class="logo-name">RESO<em>TRAVO</em></span>
    </a>

    {{-- Liens desktop --}}
    <div class="nav-links">
        @foreach ($links as $link)
            <a href="{{ $link['href'] }}"
               class="{{ $current === $link['route'] ? 'active' : '' }}">
                <span class="nav-icon">{{ $link['icon'] }}</span>
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>

    {{-- CTA desktop --}}
    <div class="nav-cta">
        @guest
            <a class="btn btn-outline" href="{{ route('login') }}">Se connecter</a>
            <a class="btn btn-primary" href="{{ route('home') }}#register">S'inscrire →</a>
        @endguest
        @auth
            <a class="btn btn-outline" href="{{ route('dashboard') }}">Mon espace</a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary">Déconnexion</button>
            </form>
        @endauth
    </div>

    {{-- Hamburger --}}
    <button class="hamburger" id="hamburger"
            aria-label="Menu" aria-expanded="false" aria-controls="nav-mobile">
        <span></span><span></span><span></span>
    </button>

</nav>

{{-- Menu mobile --}}
<div class="nav-mobile" id="nav-mobile" aria-hidden="true">

    @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="{{ $current === $link['route'] ? 'active' : '' }}">
            {{ $link['icon'] }} {{ $link['label'] }}
        </a>
    @endforeach

    <div class="nav-mobile-cta">
        @guest
            <a class="btn btn-outline btn-block" href="{{ route('login') }}">Se connecter</a>
            <a class="btn btn-primary btn-block" href="{{ route('home') }}#register">S'inscrire gratuitement →</a>
        @endguest
        @auth
            <a class="btn btn-outline btn-block" href="{{ route('dashboard') }}">Mon espace</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-block">Déconnexion</button>
            </form>
        @endauth
    </div>

</div>

{{-- Overlay --}}
<div class="nav-overlay" id="nav-overlay"></div>


<style>
/* Icones dans les liens desktop */
.nav-links a { display:inline-flex;align-items:center;gap:5px }
.nav-icon    { font-size:14px;line-height:1 }
</style>


<script>
(function () {
    'use strict';

    var nav       = document.getElementById('main-nav');
    var hamburger = document.getElementById('hamburger');
    var mobile    = document.getElementById('nav-mobile');
    var overlay   = document.getElementById('nav-overlay');
    var isOpen    = false;

    function open() {
        isOpen = true;
        mobile.classList.add('is-open');
        overlay.classList.add('is-open');
        hamburger.classList.add('is-open');
        hamburger.setAttribute('aria-expanded', 'true');
        mobile.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        isOpen = false;
        mobile.classList.remove('is-open');
        overlay.classList.remove('is-open');
        hamburger.classList.remove('is-open');
        hamburger.setAttribute('aria-expanded', 'false');
        mobile.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function () { isOpen ? close() : open(); });
    overlay.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) close();
    });

    mobile.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', close);
    });

    window.matchMedia('(min-width:900px)').addEventListener('change', function (e) {
        if (e.matches && isOpen) close();
    });

    window.addEventListener('scroll', function () {
        nav.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });

    /* Scroll vers #services depuis une autre page */
    (function () {
        if (window.location.hash !== '#services') return;
        var n = 0;
        var t = setInterval(function () {
            var el = document.querySelector('#services');
            if (el) {
                clearInterval(t);
                window.scrollTo({
                    top: el.getBoundingClientRect().top + window.scrollY - (nav ? nav.offsetHeight : 64) - 12,
                    behavior: 'smooth'
                });
            }
            if (++n >= 20) clearInterval(t);
        }, 100);
    }());

}());
</script>