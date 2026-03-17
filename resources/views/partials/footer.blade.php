{{--
  ┌─────────────────────────────────────────────────────────────────┐
  │  COMPOSANT : resources/views/partials/_footer.blade.php         │
  │  Usage     : @include('partials._footer')                       │
  └─────────────────────────────────────────────────────────────────┘
--}}

<footer>
  <div class="footer-grid">

    {{-- Colonne marque --}}
    <div class="fbrand">
      <a class="logo" href="{{ route('home') }}">
        <div class="logo-mark">R</div>
        <span class="logo-name" style="color:#fff">RESO<em style="color:var(--or)">TRAVO</em></span>
      </a>
      <p>Plateforme de mise en relation entre particuliers, entreprises et prestataires spécialisés au Bénin.</p>
      <div class="fsoc-row">
        <a class="fsoc" href="#" aria-label="Facebook">f</a>
        <a class="fsoc" href="#" aria-label="LinkedIn">in</a>
        <a class="fsoc" href="#" aria-label="Twitter / X">𝕏</a>
        <a class="fsoc" href="#" aria-label="YouTube">▶</a>
      </div>
    </div>

    {{-- Colonne plateforme --}}
    <div class="fcol">
      <h5>🔧 Plateforme</h5>
      <a href="{{ route('home') }}#services">Services</a>
      <a href="{{ route('talent') }}">Espace Talents</a>
      <a href="{{ route('market') }}">Appels d'Offres</a>
      <a href="{{ route('consulting') }}">Allô Conseils</a>
    </div>

    {{-- Colonne compte --}}
    <div class="fcol">
      <h5>👤 Compte</h5>
      <a href="{{ route('login') }}">Se connecter</a>
      <a href="{{ route('register') }}">Inscription Client</a>
      <a href="{{ route('register') }}">Inscription Prestataire</a>
      @auth
        <a href="{{ route('dashboard') }}">Mon tableau de bord</a>
      @endauth
    </div>

    {{-- Colonne support --}}
    <div class="fcol">
      <h5>💬 Support</h5>
      <a href="{{ route('consulting') }}">📞 Allo Conseils</a>
      <a href="{{ route('home') }}">❓ FAQ</a>
      <a href="{{ route('home') }}#contact">✉️ Contact</a>
      <a href="{{ route('cgu') }}">📜 CGU</a>
      <a href="{{ route('policy') }}">🔒 Confidentialité</a>
    </div>

  </div>

  <div class="fbot">
    <span>© {{ date('Y') }} ResoTravo · Tous droits réservés</span>
    <span>Built with Blood, Sweat and Tears by <a href="https://rachad-alabi-adekambi.github.io/portfolio/">RA</a> </span>
  </div>
</footer>