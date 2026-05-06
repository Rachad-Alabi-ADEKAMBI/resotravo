@extends('layouts.front')

@section('title', "Conditions Générales d'Utilisation")

@section('content')
<div class="legal-page">
    <div class="legal-hero">
        <div class="legal-hero-inner">
            <div class="legal-badge">
                <span>Doc</span> Document officiel
            </div>
            <h1>Conditions Générales<br><span class="hl">d'Utilisation</span></h1>
            <p>Dernière mise à jour : Mars 2026 - Version 2.0</p>
        </div>
    </div>

    <div class="legal-container">
        <div class="legal-toc">
            <h3>Sommaire</h3>
            <ol>
                <li><a href="#article-1">Objet de la plateforme</a></li>
                <li><a href="#article-2">Statut de la plateforme</a></li>
                <li><a href="#article-3">Création et utilisation des comptes</a></li>
                <li><a href="#article-4">Déroulement d'une mission</a></li>
                <li><a href="#article-5">Devis, prix et paiement</a></li>
                <li><a href="#article-6">Commission de la plateforme</a></li>
                <li><a href="#article-7">Obligations des artisans</a></li>
                <li><a href="#article-8">Obligations des clients</a></li>
                <li><a href="#article-9">Avis, notation et réputation</a></li>
                <li><a href="#article-10">Litiges et médiation</a></li>
                <li><a href="#article-11">Responsabilité</a></li>
                <li><a href="#article-12">Données, fiscalité et conformité</a></li>
                <li><a href="#article-13">Suspension et résiliation</a></li>
                <li><a href="#article-14">Droit applicable et juridiction</a></li>
            </ol>
        </div>

        <div class="legal-body">
            @include('partials.cgu-content')
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.legal-page { min-height:60vh;background:var(--wh) }
.legal-hero { background:var(--dk2);color:#fff;padding:52px 4% 44px;position:relative;overflow:hidden }
.legal-hero::before { content:'';position:absolute;top:-150px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.12) 0%,transparent 70%);pointer-events:none }
.legal-hero-inner { position:relative;z-index:2;max-width:700px }
.legal-badge { display:inline-flex;align-items:center;gap:8px;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:var(--am);padding:6px 15px;border-radius:99px;font-size:13px;font-weight:600;margin-bottom:18px }
.legal-hero h1 { font-size:clamp(28px,5vw,48px);font-weight:800;line-height:1.15;margin-bottom:12px }
.legal-hero p { font-size:14.5px;color:#b8ada7 }
.legal-container { max-width:920px;margin:0 auto;padding:40px 4% 60px;display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:start }
.legal-toc { position:sticky;top:80px;background:var(--cr);border-radius:14px;padding:20px;border:1.5px solid var(--grl) }
.legal-toc h3 { font-size:14px;font-weight:700;color:var(--dk);margin-bottom:12px }
.legal-toc ol { padding-left:16px;display:flex;flex-direction:column;gap:6px }
.legal-toc li a { font-size:12.5px;color:var(--gr);text-decoration:none;transition:color .18s;line-height:1.5 }
.legal-toc li a:hover { color:var(--or) }
.legal-body { display:flex;flex-direction:column;gap:0 }
.legal-intro { background:var(--or3);border:1px solid rgba(249,115,22,.2);border-radius:14px;padding:20px 22px;margin-bottom:32px }
.legal-intro p { font-size:14px;color:var(--dk);line-height:1.75;margin-bottom:8px }
.legal-intro p:last-child { margin-bottom:0 }
.legal-article { padding:28px 0;border-bottom:1px solid var(--grl) }
.legal-article:last-child { border-bottom:none }
.legal-article h2 { font-size:18px;font-weight:800;color:var(--dk);margin-bottom:16px;display:flex;align-items:center;gap:12px }
.legal-article h3 { font-size:15px;font-weight:700;color:var(--dk);margin:18px 0 8px }
.art-num { width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--or),var(--or2));color:#fff;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0 }
.legal-article p { font-size:14.5px;color:var(--gr);line-height:1.8;margin-bottom:10px }
.legal-article ul,.legal-article ol { padding-left:20px;margin:10px 0;display:flex;flex-direction:column;gap:7px }
.legal-article li { font-size:14px;color:var(--gr);line-height:1.65 }
.legal-steps { display:flex;flex-direction:column;gap:8px;margin:14px 0 }
.legal-step { display:flex;align-items:center;gap:12px;background:var(--cr);border-radius:8px;padding:10px 14px;font-size:13.5px;color:var(--gr) }
.legal-step-num { width:26px;height:26px;border-radius:7px;background:var(--or);color:#fff;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0 }
.legal-warning { background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px 18px;font-size:14px;color:#dc2626;font-weight:600;margin:12px 0 }
.legal-contact-card { background:var(--cr);border-radius:14px;padding:22px;border:1.5px solid var(--grl);display:flex;flex-direction:column;gap:12px }
.legal-contact-title { font-size:16px;font-weight:800;color:var(--dk) }
.legal-contact-item { display:flex;align-items:center;gap:10px;font-size:14px;color:var(--gr) }
.legal-contact-item span:first-child { min-width:72px;font-size:12px;font-weight:800;color:var(--dk) }
.legal-link { color:var(--or);text-decoration:none;font-weight:600 }
.legal-link:hover { text-decoration:underline }
@media(max-width:768px) {
    .legal-container { grid-template-columns:1fr }
    .legal-toc { position:static }
}
</style>
@endsection
