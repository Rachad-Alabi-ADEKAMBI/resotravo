{{--
  ┌──────────────────────────────────────────────────────────────────────┐
  │  FICHIER  : resources/views/layouts/front.blade.php                  │
  │                                                                      │
  │  Sections disponibles pour les vues enfants :                        │
  │    @section('title')    → titre de l'onglet                          │
  │    @section('styles')   → CSS supplémentaire par page                │
  │    @section('content')  → contenu principal                          │
  │    @section('scripts')  → JS supplémentaire par page                 │
  │                                                                      │
  │  Inclus automatiquement :                                            │
  │    @include('partials.nav')    → resources/views/partials/nav.blade.php    │
  │    @include('partials.footer') → resources/views/partials/footer.blade.php │
  └──────────────────────────────────────────────────────────────────────┘
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'ResoTravo') — Prestataires certifiés au Bénin</title>

  {{-- Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet"/>

  {{-- Chargement du build Vite (app.js + app.css) --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    /* ── RESET ── */
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

    /* ── VARIABLES ── */
    :root{
      --or:#F97316; --or2:#EA580C; --or3:#FFF7ED;
      --am:#FCD34D;
      --dk:#1C1412; --dk2:#110D0B;
      --cr:#FEF3E2; --cr2:#FDF0DC;
      --gr:#4A3F3A; --grl:#E8DDD4; --grm:#8A7D78;
      --wh:#FFFFFF;
      --nav-h:64px;
    }

    /* ── BASE ── */
    html{scroll-behavior:smooth;font-size:16px}
    body{
      font-family:'Poppins',sans-serif;
      background:var(--wh);
      color:var(--dk);
      overflow-x:hidden;
      -webkit-font-smoothing:antialiased;
      font-size:15px;
      line-height:1.65;
    }
    ::-webkit-scrollbar{width:4px}
    ::-webkit-scrollbar-track{background:var(--cr)}
    ::-webkit-scrollbar-thumb{background:var(--or);border-radius:99px}

    /* ── ANIMATIONS ── */
    @@keyframes fadeUp   {from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    @@keyframes fadeIn   {from{opacity:0}to{opacity:1}}
    @@keyframes floatY   {0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}
    @@keyframes blink    {0%,100%{opacity:1}50%{opacity:.2}}
    @@keyframes pulseRing{0%{box-shadow:0 0 0 0 rgba(249,115,22,.6)}70%{box-shadow:0 0 0 9px rgba(249,115,22,0)}100%{box-shadow:0 0 0 0 rgba(249,115,22,0)}}

    /* Hero entrance */
    .au {animation:fadeUp .55s ease both}
    .au1{animation:fadeUp .55s .08s ease both}
    .au2{animation:fadeUp .55s .16s ease both}
    .au3{animation:fadeUp .55s .24s ease both}
    .au4{animation:fadeUp .55s .32s ease both}

    /* ── SCROLL REVEAL ── */
    .reveal      {opacity:0;transform:translateY(28px);transition:opacity .6s cubic-bezier(.22,.68,0,1),transform .6s cubic-bezier(.22,.68,0,1)}
    .reveal-left {opacity:0;transform:translateX(-32px);transition:opacity .65s cubic-bezier(.22,.68,0,1),transform .65s cubic-bezier(.22,.68,0,1)}
    .reveal-right{opacity:0;transform:translateX(32px);transition:opacity .65s cubic-bezier(.22,.68,0,1),transform .65s cubic-bezier(.22,.68,0,1)}
    .reveal.revealed,.reveal-left.revealed,.reveal-right.revealed{opacity:1;transform:none}
    .reveal-d1{transition-delay:.07s} .reveal-d2{transition-delay:.14s} .reveal-d3{transition-delay:.21s}
    .reveal-d4{transition-delay:.28s} .reveal-d5{transition-delay:.35s} .reveal-d6{transition-delay:.42s}

    /* ────────────────────────────────────────────
       BOUTONS
    ──────────────────────────────────────────── */
    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:6px;
      padding:10px 20px;border-radius:8px;
      font-family:'Poppins',sans-serif;font-weight:600;font-size:14.5px;
      cursor:pointer;border:none;transition:all .2s;
      text-decoration:none;white-space:nowrap;
    }
    .btn-primary{background:linear-gradient(135deg,var(--or),var(--or2));color:#fff;box-shadow:0 3px 12px rgba(249,115,22,.3);position:relative;overflow:hidden}
    .btn-primary::after{content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);transform:skewX(-20deg);transition:left .5s ease}
    .btn-primary:hover::after{left:160%}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 5px 18px rgba(249,115,22,.4)}
    .btn-outline{background:transparent;border:1.5px solid var(--grl);color:var(--dk)}
    .btn-outline:hover{border-color:var(--or);color:var(--or)}
    .btn-dark{background:var(--dk);color:#fff}
    .btn-dark:hover{background:#2c1f1b;transform:translateY(-1px)}
    .btn-ghost{background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.22)}
    .btn-ghost:hover{background:rgba(255,255,255,.2)}
    .btn-lg{padding:14px 28px;font-size:16px;border-radius:10px}
    .btn-block{width:100%;justify-content:center}

    /* ────────────────────────────────────────────
       NAV — barre principale
    ──────────────────────────────────────────── */
    #main-nav{
      position:sticky;top:0;z-index:200;
      background:rgba(255,255,255,.95);
      backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
      border-bottom:1px solid var(--grl);
      height:var(--nav-h);
      display:flex;align-items:center;justify-content:space-between;
      padding:0 4%;
      transition:box-shadow .3s;
    }
    #main-nav.scrolled{box-shadow:0 2px 24px rgba(0,0,0,.09)}

    /* Logo */
    .logo{display:flex;align-items:center;gap:9px;text-decoration:none;flex-shrink:0}
    .logo-mark{
      width:36px;height:36px;border-radius:10px;
      background:linear-gradient(135deg,var(--or),var(--or2));
      display:flex;align-items:center;justify-content:center;
      color:#fff;font-weight:900;font-size:17px;
      box-shadow:0 3px 10px rgba(249,115,22,.3);
    }
    .logo-name{font-size:18px;font-weight:800;color:var(--dk);letter-spacing:-.5px}
    .logo-name em{font-style:normal;color:var(--or)}

    /* Liens desktop */
    .nav-links{display:none;align-items:center;gap:24px}
    .nav-links a{
      text-decoration:none;color:var(--gr);
      font-size:14.5px;font-weight:500;
      transition:color .18s;position:relative;padding-bottom:2px;
    }
    .nav-links a::after{
      content:'';position:absolute;left:0;bottom:-2px;
      width:0;height:2px;background:var(--or);
      border-radius:2px;transition:width .2s;
    }
    .nav-links a:hover{color:var(--dk)}
    .nav-links a:hover::after,.nav-links a.active::after{width:100%}
    .nav-links a.active{color:var(--or);font-weight:600}

    /* CTA desktop */
    .nav-cta{display:none;gap:8px;align-items:center}

    /* Hamburger */
    .hamburger{
      background:none;border:none;cursor:pointer;
      padding:6px;display:flex;flex-direction:column;gap:5px;flex-shrink:0;
    }
    .hamburger span{
      display:block;width:22px;height:2px;
      background:var(--dk);border-radius:2px;
      transition:transform .28s ease,opacity .2s ease;
    }
    /* Croix quand ouvert */
    .hamburger.is-open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
    .hamburger.is-open span:nth-child(2){opacity:0;transform:scaleX(0)}
    .hamburger.is-open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

    /* Desktop : show nav-links & nav-cta, hide hamburger */
    @@media(min-width:900px){
      .nav-links{display:flex}
      .nav-cta  {display:flex}
      .hamburger{display:none}
    }

    /* ────────────────────────────────────────────
       MENU MOBILE — panneau déroulant
    ──────────────────────────────────────────── */
    .nav-mobile{
      position:fixed;
      top:var(--nav-h);left:0;right:0;
      z-index:199;
      background:var(--wh);
      border-bottom:1px solid var(--grl);
      box-shadow:0 12px 40px rgba(0,0,0,.12);
      /* Fermé : hauteur 0 + invisible */
      max-height:0;
      overflow:hidden;
      opacity:0;
      pointer-events:none;
      transition:max-height .35s cubic-bezier(.4,0,.2,1),
                 opacity .25s ease;
    }
    /* Ouvert */
    .nav-mobile.is-open{
      max-height:600px;
      opacity:1;
      pointer-events:auto;
      padding:14px 4% 20px;
    }
    .nav-mobile a{
      display:flex;align-items:center;gap:8px;
      text-decoration:none;color:var(--dk);
      font-weight:500;font-size:15.5px;
      padding:10px 0;
      border-bottom:1px solid var(--grl);
      transition:color .18s,padding-left .18s;
    }
    .nav-mobile a:last-of-type{border-bottom:none}
    .nav-mobile a:hover{color:var(--or);padding-left:4px}
    .nav-mobile a.active{color:var(--or);font-weight:600}
    .nav-mobile-cta{display:flex;flex-direction:column;gap:8px;margin-top:14px}
    .nav-mobile-cta form{margin:0}

    /* Overlay */
    .nav-overlay{
      position:fixed;inset:0;z-index:198;
      background:rgba(0,0,0,.4);
      opacity:0;pointer-events:none;
      transition:opacity .3s ease;
    }
    .nav-overlay.is-open{opacity:1;pointer-events:auto}

    /* Cacher menu mobile sur desktop */
    @@media(min-width:900px){
      .nav-mobile ,.nav-overlay{display:none!important}
    }

    /* ────────────────────────────────────────────
       HERO
    ──────────────────────────────────────────── */
    .hero{background:var(--dk2);color:#fff;padding:52px 4% 44px;position:relative;overflow:hidden}
    .hero-glow{position:absolute;top:-200px;right:-150px;width:550px;height:550px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.14) 0%,transparent 68%);pointer-events:none}
    .hero-glow2{position:absolute;bottom:-100px;left:-80px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(252,211,77,.05) 0%,transparent 70%);pointer-events:none}
    .hero-dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.035) 1px,transparent 1px);background-size:24px 24px;pointer-events:none}
    @@media(min-width:900px){.hero{display:grid;grid-template-columns:55% 45%;align-items:center;gap:32px;padding:70px 4% 60px}}
    .hero-inner{position:relative;z-index:2}
    .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:var(--am);padding:6px 15px;border-radius:99px;font-size:13.5px;font-weight:600;margin-bottom:18px}
    @@keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(249,115,22,.6)}70%{box-shadow:0 0 0 8px rgba(249,115,22,0)}100%{box-shadow:0 0 0 0 rgba(249,115,22,0)}}
    .bdot{width:7px;height:7px;border-radius:50%;background:var(--or);animation:blink 1.4s infinite,pulse-ring 2s infinite;flex-shrink:0}
    .hero h1{font-size:clamp(30px,6vw,54px);font-weight:800;line-height:1.15;margin-bottom:14px;letter-spacing:-.5px}
    .hl{color:var(--or);position:relative}
    .hl::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--or),var(--am));border-radius:2px}
    .hero-desc{font-size:16px;color:#b8ada7;line-height:1.75;margin-bottom:26px;font-weight:400}
    .hero-btns{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:34px}
    .hero-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    @@media(min-width:480px){.hero-stats{display:flex;flex-wrap:wrap;gap:26px}}
    .hstat .num{font-size:26px;font-weight:800;color:var(--or);letter-spacing:-.5px}
    .hstat .lbl{font-size:12.5px;color:#7d6f65;margin-top:2px;font-weight:500}
    .hero-visual{display:none;position:relative;z-index:2}
    @@media(min-width:900px){.hero-visual{display:block}}
    .hero-img{width:100%;border-radius:16px;object-fit:cover;height:400px;display:block;box-shadow:0 20px 60px rgba(0,0,0,.5);border:1.5px solid rgba(255,255,255,.07)}
    .hcard{position:absolute;background:#fff;border-radius:12px;padding:12px 16px;box-shadow:0 10px 36px rgba(0,0,0,.2)}
    .hcard1{bottom:-16px;left:-22px;min-width:168px;animation:floatY 3.5s ease-in-out infinite}
    .hcard2{top:20px;right:-22px;min-width:150px;animation:floatY 3.5s 1.2s ease-in-out infinite}
    .hc-tag{font-size:11px;color:var(--gr);font-weight:500;margin-bottom:3px}
    .hc-val{font-size:17px;font-weight:800;color:var(--dk)}
    .hc-sub{font-size:11.5px;color:var(--or);font-weight:600;margin-top:2px}
    .hc-stars{color:var(--am);font-size:12px;margin-bottom:2px}

    /* ── SEARCH ── */
    .search-wrap{background:var(--cr);padding:24px 4%}
    .search-box{background:var(--wh);border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.1);padding:24px 22px;border:1px solid var(--grl);max-width:860px;margin:0 auto}
    .search-head{display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap}
    .search-head-icon{width:32px;height:32px;background:var(--or3);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px}
    .search-head h3{font-size:16px;font-weight:700;color:var(--dk)}
    .search-head span{font-size:13.5px;color:var(--gr)}
    .search-row{display:flex;flex-direction:column;gap:8px}
    @@media(min-width:600px){.search-row{flex-direction:row}}
    .search-input{flex:1;padding:12px 14px;border:1.5px solid var(--grl);border-radius:9px;font-family:'Poppins',sans-serif;font-size:14.5px;outline:none;transition:all .2s;background:var(--cr);color:var(--dk)}
    .search-input:focus{border-color:var(--or);background:#fff;box-shadow:0 0 0 3px rgba(249,115,22,.08)}
    .search-input::placeholder{color:var(--grm)}
    .search-tags{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
    .stag{padding:5px 13px;border-radius:99px;font-size:13px;font-family:'Poppins',sans-serif;background:var(--cr2);border:1px solid var(--grl);color:var(--gr);cursor:pointer;transition:all .18s;font-weight:500}
    .stag:hover{background:var(--or);color:#fff;border-color:var(--or)}

    /* ── STATS BAND ── */
    .stats-band{background:linear-gradient(135deg,var(--or),var(--or2));padding:16px 4%}
    .stats-inner{display:flex;flex-wrap:wrap;justify-content:center;gap:14px 32px;max-width:860px;margin:0 auto}
    .sbi{display:flex;align-items:center;gap:9px;color:#fff}
    .sbi-icon{font-size:18px;opacity:.9}
    .sbi-num{font-size:20px;font-weight:800}
    .sbi-lbl{font-size:12.5px;opacity:.85;font-weight:500}

    /* ── SECTIONS ── */
    .sec{padding:52px 4%}
    .sec-cr{background:var(--cr)}
    .sec-tag{font-size:12px;font-weight:700;color:var(--or);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px}
    .sec-tag::before{content:'';display:block;width:14px;height:2px;background:var(--or);border-radius:2px;flex-shrink:0}
    .sec-title{font-size:clamp(22px,4vw,34px);font-weight:800;color:var(--dk);margin-bottom:10px;letter-spacing:-.4px;line-height:1.2}
    .sec-sub{font-size:15.5px;color:var(--gr);max-width:500px;line-height:1.75;margin-bottom:30px;font-weight:400}

    /* ── SERVICES ── */
    .services-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    @@media(min-width:480px){.services-grid{grid-template-columns:repeat(3,1fr)}}
    @@media(min-width:900px){.services-grid{grid-template-columns:repeat(4,1fr)}}
    .scard{background:var(--wh);border:1.5px solid var(--grl);border-radius:14px;padding:18px 16px;cursor:pointer;transition:all .25s cubic-bezier(.22,.68,0,1.2);text-decoration:none;color:inherit;position:relative;overflow:hidden}
    .scard::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(249,115,22,.06),transparent);opacity:0;transition:opacity .25s}
    .scard:hover{border-color:var(--or);box-shadow:0 8px 28px rgba(249,115,22,.12);transform:translateY(-3px)}
    .scard:hover::after{opacity:1}
    .scard-icon{width:50px;height:50px;border-radius:12px;background:var(--or3);display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px;transition:transform .25s}
    .scard:hover .scard-icon{transform:scale(1.1) rotate(-4deg)}
    .scard h4{font-size:15px;font-weight:700;margin-bottom:5px}
    .scard p{font-size:13px;color:var(--gr);line-height:1.6}
    .scard-arr{position:absolute;top:14px;right:14px;color:var(--or);opacity:0;transform:translateX(-4px);transition:all .2s;font-size:15px;font-weight:700}
    .scard:hover .scard-arr{opacity:1;transform:translateX(0)}

    /* ── STEPS ── */
    .steps-grid{display:grid;grid-template-columns:1fr}
    @@media(min-width:600px){.steps-grid{grid-template-columns:repeat(2,1fr)}}
    @@media(min-width:900px){.steps-grid{grid-template-columns:repeat(4,1fr)}}
    .step{padding:28px 22px;position:relative;border-bottom:1px solid var(--grl);opacity:0;transform:translateY(14px);transition:all .4s ease}
    @@media(min-width:600px){.step{border-right:1px solid var(--grl);border-bottom:none}.step:nth-child(2n){border-right:none}}
    @@media(min-width:900px){.step{border-right:1px solid var(--grl)}.step:nth-child(2n){border-right:1px solid var(--grl)}.step:last-child{border-right:none}}
    .step.visible{opacity:1;transform:translateY(0)}
    .step-num{width:44px;height:44px;border-radius:11px;background:linear-gradient(135deg,var(--or),var(--or2));color:#fff;font-weight:800;font-size:18px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;box-shadow:0 4px 12px rgba(249,115,22,.28)}
    .step-ico{font-size:22px;margin-bottom:8px}
    .step h4{font-size:15.5px;font-weight:700;margin-bottom:7px}
    .step p{font-size:14px;color:var(--gr);line-height:1.65}

    /* ── SPLIT ── */
    .split{display:grid;grid-template-columns:1fr}
    @@media(min-width:768px){.split{grid-template-columns:1fr 1fr}}
    .split-img{width:100%;height:260px;object-fit:cover;display:block}
    @@media(min-width:768px){.split-img{height:100%;min-height:360px}}
    .split-content{padding:36px 5%;display:flex;flex-direction:column;justify-content:center}
    .split-dark{background:var(--dk)}
    .split-dark .sec-title{color:#fff}
    .split-dark .sec-sub{color:#c4b8b2}
    .split-dark .sec-tag{color:var(--am)}
    .split-dark .sec-tag::before{background:var(--am)}
    .checklist{list-style:none;display:flex;flex-direction:column;gap:11px;margin-bottom:26px}
    .checklist li{display:flex;align-items:flex-start;gap:10px;font-size:14.5px;line-height:1.6;color:#3d3228}
    .split-dark .checklist li{color:#c4b8b2}
    .ci{font-size:17px;flex-shrink:0;margin-top:1px}

    /* ── TRUST ── */
    .trust-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    @@media(min-width:600px){.trust-grid{grid-template-columns:repeat(4,1fr)}}
    .tcard{background:var(--dk);color:#fff;border-radius:14px;padding:22px 18px;position:relative;overflow:hidden;transition:transform .22s}
    .tcard::before{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.1),transparent)}
    .tcard:hover{transform:translateY(-3px)}
    .tcard-ico{width:44px;height:44px;border-radius:11px;background:rgba(249,115,22,.15);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px}
    .tcard h4{font-size:14.5px;font-weight:700;margin-bottom:7px}
    .tcard p{font-size:13px;color:#c4b8b2;line-height:1.65}

    /* ── TESTIMONIALS ── */
    .test-grid{display:grid;grid-template-columns:1fr;gap:12px}
    @@media(min-width:600px){.test-grid{grid-template-columns:repeat(2,1fr)}}
    @@media(min-width:900px){.test-grid{grid-template-columns:repeat(3,1fr)}}
    .testcard{background:var(--wh);border-radius:14px;padding:22px;border:1px solid var(--grl);transition:all .22s}
    .testcard:hover{box-shadow:0 8px 32px rgba(0,0,0,.1);transform:translateY(-2px)}
    .t-stars{font-size:15px;color:var(--am);margin-bottom:10px;letter-spacing:1px}
    .t-text{font-size:14px;color:var(--dk);line-height:1.75;margin-bottom:16px;font-style:italic}
    .t-auth{display:flex;align-items:center;gap:10px}
    .t-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--or),var(--am));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0}
    .t-name{font-size:14px;font-weight:700}
    .t-role{font-size:12.5px;color:#6e5f58}

    /* ── APP BANNER ── */
    .app-banner{background:var(--dk);color:#fff;padding:30px 4%}
    .app-banner-inner{display:flex;flex-direction:column;gap:18px;max-width:860px;margin:0 auto}
    @@media(min-width:768px){.app-banner-inner{flex-direction:row;align-items:center;justify-content:space-between}}
    .app-banner h3{font-size:20px;font-weight:800;margin-bottom:5px}
    .app-banner p{font-size:14px;color:#c4b8b2}
    .app-badges{display:flex;flex-wrap:wrap;gap:9px}
    .abadge{display:flex;align-items:center;gap:9px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:10px 14px;min-width:130px;cursor:pointer;transition:all .2s}
    .abadge:hover{background:rgba(255,255,255,.14)}
    .abi{font-size:22px}
    .abt{display:flex;flex-direction:column}
    .abt-s{font-size:10px;color:#c4b8b2;font-weight:500;text-transform:uppercase;letter-spacing:.5px}
    .abt-b{font-size:14.5px;font-weight:700;color:#fff}

    /* ── FAQ ── */
    .faq-layout{display:grid;grid-template-columns:1fr;gap:24px}
    @@media(min-width:768px){.faq-layout{grid-template-columns:1fr 2fr;gap:40px;align-items:start}}
    .faq-left .sec-sub{margin-bottom:18px}
    .faq-help{display:flex;align-items:flex-start;gap:9px;padding:13px 15px;background:var(--or3);border-radius:10px;font-size:14px;color:var(--dk);line-height:1.55}
    .faq-help a{color:var(--or);font-weight:600;text-decoration:none}
    .faq-list{border:1.5px solid var(--grl);border-radius:14px;overflow:hidden}
    .faq-item{border-bottom:1px solid var(--grl)}
    .faq-item:last-child{border-bottom:none}
    .faq-q{width:100%;background:none;border:none;text-align:left;padding:17px 18px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Poppins',sans-serif;font-size:14.5px;font-weight:600;color:var(--dk);transition:background .18s}
    .faq-q:hover{background:var(--cr)}
    .faq-q.open{background:var(--cr);color:var(--or)}
    .faq-chev{font-size:20px;color:var(--or);transition:transform .28s;flex-shrink:0;line-height:1}
    .faq-q.open .faq-chev{transform:rotate(45deg)}
    .faq-a{overflow:hidden;max-height:0;transition:max-height .35s cubic-bezier(.4,0,.2,1),padding .28s;padding:0 18px;font-size:14px;color:#3d3228;line-height:1.8}
    .faq-a.open{max-height:300px;padding:0 18px 18px}

    /* ── PARTNERS ── */
    .partners{padding:26px 4%;background:var(--cr2);border-top:1px solid var(--grl)}
    .plbl{text-align:center;font-size:12px;font-weight:600;color:#7a6d68;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:16px}
    .prow{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:14px 32px;opacity:.7}
    .pname{font-size:14px;font-weight:800;color:#3d3228;letter-spacing:-.3px}

    /* ── CTA FINAL ── */
    .cta-final{background:linear-gradient(135deg,var(--or) 0%,var(--or2) 100%);color:#fff;text-align:center;padding:60px 4%;position:relative;overflow:hidden}
    .cta-final::before{content:'';position:absolute;top:-80px;right:-60px;width:360px;height:360px;border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none}
    .cta-final::after{content:'';position:absolute;bottom:-70px;left:-50px;width:280px;height:280px;border-radius:50%;background:rgba(0,0,0,.06);pointer-events:none}
    .cta-inner{position:relative;z-index:1;max-width:540px;margin:0 auto}
    .cta-final h2{font-size:clamp(22px,4vw,38px);font-weight:800;margin-bottom:12px;letter-spacing:-.4px}
    .cta-final p{font-size:16px;opacity:.9;margin-bottom:28px;line-height:1.65}
    .cta-btns{display:flex;flex-wrap:wrap;justify-content:center;gap:12px}

    /* ────────────────────────────────────────────
       FOOTER
    ──────────────────────────────────────────── */
    footer{background:#0d0907;color:#d4c8c2;padding:48px 4% 24px}
    .footer-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:36px}
    @@media(min-width:600px){.footer-grid{grid-template-columns:2fr 1fr 1fr 1fr}}
    .fbrand p{font-size:13.5px;color:#b0a49e;line-height:1.75;margin-top:12px;max-width:240px}
    .fsoc-row{display:flex;gap:8px;margin-top:14px}
    .fsoc{width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;color:#d4c8c2;font-size:13px;text-decoration:none;transition:all .18s;font-style:normal;font-weight:700}
    .fsoc:hover{background:var(--or);border-color:var(--or);color:#fff}
    .fcol h5{color:#fff;font-size:14px;font-weight:700;margin-bottom:14px}
    .fcol a{display:flex;align-items:center;gap:6px;color:#b0a49e;text-decoration:none;font-size:13.5px;margin-bottom:10px;transition:color .18s}
    .fcol a:hover{color:var(--or)}
    .fbot{border-top:1px solid rgba(255,255,255,.08);padding-top:18px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:13px;color:#8a7d78}
    .fbot a{color:#a09590;text-decoration:none}
    .fbot a:hover{color:var(--or)}

    /* ── TOAST ── */
    .toast{position:fixed;bottom:22px;right:18px;left:18px;background:var(--dk);color:#fff;padding:12px 18px;border-radius:12px;font-size:13.5px;font-weight:500;border-left:3px solid var(--or);box-shadow:0 8px 32px rgba(0,0,0,.28);transition:all .28s cubic-bezier(.22,.68,0,1.2);z-index:999;text-align:center}
    @@media(min-width:480px){.toast{left:auto;min-width:260px;text-align:left}}
    .toast.hidden{opacity:0;transform:translateY(14px) scale(.97);pointer-events:none}

    /* ── BACK TO TOP ── */
    .btt{position:fixed;bottom:22px;left:18px;width:40px;height:40px;border-radius:10px;background:var(--or);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 14px rgba(249,115,22,.35);transition:all .2s;opacity:0;pointer-events:none;z-index:998;font-family:'Poppins',sans-serif;font-weight:700}
    .btt.show{opacity:1;pointer-events:auto}
    .btt:hover{transform:translateY(-2px)}
  </style>

  {{-- CSS supplémentaire de la page enfant --}}
  @yield('styles')
</head>

<body>

  {{-- ── Navigation ──
       Chemin : resources/views/partials/nav.blade.php
       Appel  : @include('partials.nav', ['active' => 'home'])
  --}}
  @include('partials.nav', ['active' => $active ?? ''])

  {{-- #app est ici : Vue monte sur ce div et trouve les composants dans @yield('content') --}}
 <div id="resotravo-app">

    <main>
      @yield('content')
    </main>
  </div>

  {{-- ── Footer ──
       Chemin : resources/views/partials/footer.blade.php
       Appel  : @include('partials.footer')
  --}}
  @include('partials.footer')

  {{-- ── Bouton retour en haut ── --}}
  <button class="btt" id="btt" aria-label="Retour en haut">↑</button>

  {{-- ── Scripts globaux ── --}}
  <script>
  (function () {
    'use strict';

    /* Back-to-top */
    var btt = document.getElementById('btt');
    window.addEventListener('scroll', function () {
      btt.classList.toggle('show', window.scrollY > 300);
    }, { passive: true });
    btt.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* Scroll reveal */
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add('revealed');
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

      document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
        .forEach(function (el) { io.observe(el); });

      /* Steps */
      var stepIo = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) e.target.classList.add('visible');
        });
      }, { threshold: 0.12 });

      document.querySelectorAll('.step')
        .forEach(function (el) { stepIo.observe(el); });
    }

  }());
  </script>

  {{-- Scripts supplémentaires par page (si besoin) --}}
  @stack('scripts')

</body>
</html>