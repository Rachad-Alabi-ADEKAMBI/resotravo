@extends('layouts.front')

@section('title', "Conditions Generales d'Utilisation")

@section('content')

<div class="legal-page">

    <!-- HERO LEGAL -->
    <div class="legal-hero">
        <div class="legal-hero-inner">
            <div class="legal-badge">
                <span>📜</span> Document officiel
            </div>
            <h1>Conditions Generales<br /><span class="hl">d'Utilisation</span></h1>
            <p>Derniere mise a jour : Mars 2026 &mdash; Version 2.0</p>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="legal-container">

        <!-- Sommaire -->
        <div class="legal-toc">
            <h3>Sommaire</h3>
            <ol>
                <li><a href="#article-1">Objet de la plateforme</a></li>
                <li><a href="#article-2">Statut de la plateforme</a></li>
                <li><a href="#article-3">Creation et utilisation des comptes</a></li>
                <li><a href="#article-4">Deroulement d'une mission</a></li>
                <li><a href="#article-5">Devis, prix et paiement</a></li>
                <li><a href="#article-6">Commission de la plateforme</a></li>
                <li><a href="#article-7">Obligations des artisans</a></li>
                <li><a href="#article-8">Obligations des clients</a></li>
                <li><a href="#article-9">Avis, notation et reputation</a></li>
                <li><a href="#article-10">Litiges et mediation</a></li>
                <li><a href="#article-11">Responsabilite</a></li>
                <li><a href="#article-12">Donnees, fiscalite et conformite</a></li>
                <li><a href="#article-13">Suspension et resiliation</a></li>
                <li><a href="#article-14">Droit applicable et juridiction</a></li>
            </ol>
        </div>

        <!-- Articles -->
        <div class="legal-body">

            <div class="legal-intro">
                <p>
                    Les presentes Conditions Generales d'Utilisation (CGU) regissent l'utilisation de la
                    plateforme <strong>MESOTRAVO</strong>, accessible a l'adresse
                    <strong>Mesotravo.com</strong>, editee par la societe Mesotravo,
                    dont le contact est disponible au <strong>+229 01 90 00 36 26</strong>
                    et par WhatsApp au <strong>+229 01 90 00 36 26</strong>.
                </p>
                <p>
                    En creant un compte ou en utilisant nos services, vous acceptez sans reserve l'integralite
                    des presentes CGU. Si vous n'acceptez pas ces conditions, vous ne devez pas utiliser la plateforme.
                </p>
            </div>

            <!-- Article 1 -->
            <div class="legal-article" id="article-1">
                <h2><span class="art-num">01</span> Objet de la plateforme</h2>
                <p>
                    MESOTRAVO est une plateforme numerique de mise en relation entre :
                </p>
                <ul>
                    <li>des clients recherchant des services ou travaux a domicile,</li>
                    <li>des artisans independants exercant legalement une activite professionnelle.</li>
                </ul>
                <div class="legal-warning">
                    La plateforme n'execute aucun travail et n'emploie aucun artisan.<br>
                    Le contrat de prestation est conclu directement entre le client et l'artisan, conformement au droit en vigueur en Republique du Benin et au droit OHADA.
                </div>
            </div>

            <!-- Article 2 -->
            <div class="legal-article" id="article-2">
                <h2><span class="art-num">02</span> Statut de la plateforme</h2>
                <p>La plateforme agit uniquement comme intermediaire technique et commercial :</p>
                <ul>
                    <li>organisation des echanges,</li>
                    <li>suivi de la mission,</li>
                    <li>securisation du paiement via un prestataire agree.</li>
                </ul>
                <p>Elle n'est pas responsable de :</p>
                <ul>
                    <li>la qualite technique des travaux,</li>
                    <li>les dommages materiels lies a l'intervention,</li>
                    <li>les relations personnelles entre les parties.</li>
                </ul>
                <p>Cette clarification est exigee pour toute plateforme e-commerce au Benin.</p>
            </div>

            <!-- Article 3 -->
            <div class="legal-article" id="article-3">
                <h2><span class="art-num">03</span> Creation et utilisation des comptes</h2>

                <h3>Clients</h3>
                <ul>
                    <li>Inscription gratuite.</li>
                    <li>Les informations fournies doivent etre exactes.</li>
                    <li>Toute tentative de fraude, de non-paiement ou de contournement entraine la suspension du compte.</li>
                </ul>

                <h3>Artisans</h3>
                <p>L'artisan declare :</p>
                <ul>
                    <li>etre professionnel independant declare (personne physique ou morale),</li>
                    <li>etre autorise a exercer son metier,</li>
                    <li>disposer des assurances professionnelles necessaires (si applicables),</li>
                    <li>repondre personnellement de ses actes et de son travail.</li>
                </ul>
                <p>La plateforme peut verifier les informations et refuser ou suspendre un compte sans justification extensive.</p>
            </div>

            <!-- Article 4 -->
            <div class="legal-article" id="article-4">
                <h2><span class="art-num">04</span> Deroulement d'une mission</h2>
                <div class="legal-steps">
                    <div class="legal-step"><div class="legal-step-num">1</div><div>Le client exprime un besoin</div></div>
                    <div class="legal-step"><div class="legal-step-num">2</div><div>La plateforme propose la mission a un artisan disponible</div></div>
                    <div class="legal-step"><div class="legal-step-num">3</div><div>L'artisan accepte ou refuse</div></div>
                    <div class="legal-step"><div class="legal-step-num">4</div><div>Diagnostic sur place (le cas echeant)</div></div>
                    <div class="legal-step"><div class="legal-step-num">5</div><div>Devis soumis dans l'application</div></div>
                    <div class="legal-step"><div class="legal-step-num">6</div><div>Acceptation par le client</div></div>
                    <div class="legal-step"><div class="legal-step-num">7</div><div>Paiement exclusivement via la plateforme</div></div>
                </div>
                <div class="legal-warning">
                    Tout paiement en dehors de la plateforme est interdit pour une mission issue de celle-ci.
                </div>
            </div>

            <!-- Article 5 -->
            <div class="legal-article" id="article-5">
                <h2><span class="art-num">05</span> Devis, prix et paiement</h2>
                <ul>
                    <li>Les prix sont librement fixes par l'artisan.</li>
                    <li>Le client valide obligatoirement le devis avant travaux.</li>
                    <li>Le paiement est effectue via un prestataire de paiement agree BCEAO / UEMOA (Mobile Money, carte, etc.).</li>
                    <li>La plateforme ne conserve pas les fonds, mais les fait transiter de maniere securisee.</li>
                </ul>
            </div>

            <!-- Article 6 -->
            <div class="legal-article" id="article-6">
                <h2><span class="art-num">06</span> Commission de la plateforme</h2>
                <p>La plateforme percoit une commission pour son service.</p>
                <p>
                    <strong>Regle simple et claire :</strong> toute mission obtenue grace a la plateforme donne lieu au paiement de la commission,
                    meme si les parties tentent de finaliser en dehors de celle-ci.
                    Cette obligation s'applique pour toute relation client–artisan initiee via la plateforme pendant une duree de [X mois].
                </p>
                <p>En cas de contournement :</p>
                <ul>
                    <li>suspension ou suppression du compte,</li>
                    <li>blocage des paiements,</li>
                    <li>exclusion definitive.</li>
                </ul>
            </div>

            <!-- Article 7 -->
            <div class="legal-article" id="article-7">
                <h2><span class="art-num">07</span> Obligations specifiques des artisans</h2>
                <p>L'artisan s'engage a :</p>
                <ul>
                    <li>respecter les delais annonces,</li>
                    <li>executer les travaux conformement au devis accepte,</li>
                    <li>adopter un comportement respectueux et professionnel,</li>
                    <li>respecter les regles de securite au domicile du client.</li>
                </ul>
                <p>La plateforme peut appliquer : avertissement, suspension, dereferencement.</p>
            </div>

            <!-- Article 8 -->
            <div class="legal-article" id="article-8">
                <h2><span class="art-num">08</span> Obligations des clients</h2>
                <p>Le client s'engage a :</p>
                <ul>
                    <li>fournir des informations exactes,</li>
                    <li>permettre l'acces au lieu d'intervention,</li>
                    <li>respecter l'artisan,</li>
                    <li>proceder au paiement tel que valide.</li>
                </ul>
                <p>Toute tentative de fraude ou de pression sur l'artisan entraine la suspension du compte.</p>
            </div>

            <!-- Article 9 -->
            <div class="legal-article" id="article-9">
                <h2><span class="art-num">09</span> Avis, notation et reputation</h2>
                <ul>
                    <li>Les avis doivent etre honnetes, factuels et respectueux.</li>
                    <li>Les faux avis ou abus peuvent etre supprimes.</li>
                    <li>Le systeme de notation conditionne la visibilite des artisans.</li>
                </ul>
                <p>C'est un element central de confiance sur la plateforme.</p>
            </div>

            <!-- Article 10 -->
            <div class="legal-article" id="article-10">
                <h2><span class="art-num">10</span> Litiges et mediation</h2>
                <ul>
                    <li>La plateforme peut intervenir comme facilitateur de dialogue.</li>
                    <li>Elle peut analyser les elements disponibles dans l'application.</li>
                    <li>Elle peut decider d'un deblocage, blocage ou remboursement partiel selon les cas.</li>
                </ul>
                <p>Elle ne remplace pas les tribunaux, mais facilite une resolution amiable.</p>
            </div>

            <!-- Article 11 -->
            <div class="legal-article" id="article-11">
                <h2><span class="art-num">11</span> Responsabilite</h2>
                <ul>
                    <li>Chaque artisan est seul responsable de son travail.</li>
                    <li>La plateforme n'est pas responsable des dommages materiels ou corporels.</li>
                    <li>Les utilisateurs reconnaissent utiliser le service a leurs risques contractuels.</li>
                </ul>
                <p>Cette clause est conforme au cadre OHADA et e-commerce beninois.</p>
            </div>

            <!-- Article 12 -->
            <div class="legal-article" id="article-12">
                <h2><span class="art-num">12</span> Donnees, fiscalite et conformite</h2>
                <ul>
                    <li>Les utilisateurs acceptent que certaines informations de transaction puissent etre communiquees aux autorites fiscales, conformement a la loi beninoise en vigueur a partir de 2025.</li>
                    <li>Les donnees personnelles sont protegees selon les lois beninoises applicables.</li>
                </ul>
            </div>

            <!-- Article 13 -->
            <div class="legal-article" id="article-13">
                <h2><span class="art-num">13</span> Suspension et resiliation</h2>
                <p>La plateforme peut suspendre ou fermer un compte en cas de :</p>
                <ul>
                    <li>fraude,</li>
                    <li>contournement,</li>
                    <li>comportement dangereux,</li>
                    <li>non-respect repete des CGU.</li>
                </ul>
                <p>L'utilisateur peut fermer son compte a tout moment hors mission en cours.</p>
            </div>

            <!-- Article 14 -->
            <div class="legal-article" id="article-14">
                <h2><span class="art-num">14</span> Droit applicable et juridiction competente</h2>
                <ul>
                    <li>Les presentes CGU sont regies par le droit beninois et le droit OHADA.</li>
                    <li>Tout litige releve des juridictions competentes de la Republique du Benin.</li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="legal-article" id="article-contact">
                <h2><span class="art-num">📞</span> Contact</h2>
                <div class="legal-contact-card">
                    <div class="legal-contact-title">Mesotravo</div>
                    <div class="legal-contact-item">
                        <span>&#x1F310;</span>
                        <span><a href="https://Mesotravo.com" class="legal-link">Mesotravo.com</a></span>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x2709;&#xFE0F;</span>
                        <span><a href="mailto:contact@Mesotravo.com" class="legal-link">contact@Mesotravo.com</a></span>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4DE;</span>
                        <span><a href="tel:+22901966363644" class="legal-link">+229 01 90 00 36 26</a></span>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4AC;</span>
                        <span>WhatsApp : <a href="https://wa.me/22901966363644" class="legal-link">+229 01 90 00 36 26</a></span>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4CD;</span>
                        <span>Cotonou, Benin</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@section('styles')
<style>
/* Page legale */
.legal-page { min-height:60vh;background:var(--wh) }

.legal-hero { background:var(--dk2);color:#fff;padding:52px 4% 44px;position:relative;overflow:hidden }
.legal-hero::before { content:'';position:absolute;top:-150px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.12) 0%,transparent 70%);pointer-events:none }
.legal-hero-inner { position:relative;z-index:2;max-width:700px }
.legal-badge { display:inline-flex;align-items:center;gap:8px;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:var(--am);padding:6px 15px;border-radius:99px;font-size:13px;font-weight:600;margin-bottom:18px }
.legal-hero h1 { font-size:clamp(28px,5vw,48px);font-weight:800;line-height:1.15;margin-bottom:12px }
.legal-hero p { font-size:14.5px;color:#b8ada7 }

.legal-container { max-width:920px;margin:0 auto;padding:40px 4% 60px;display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:start }
@media(max-width:768px) { .legal-container { grid-template-columns:1fr } }

/* Sommaire */
.legal-toc { position:sticky;top:80px;background:var(--cr);border-radius:14px;padding:20px;border:1.5px solid var(--grl) }
.legal-toc h3 { font-size:14px;font-weight:700;color:var(--dk);margin-bottom:12px }
.legal-toc ol { padding-left:16px;display:flex;flex-direction:column;gap:6px }
.legal-toc li a { font-size:12.5px;color:var(--gr);text-decoration:none;transition:color .18s;line-height:1.5 }
.legal-toc li a:hover { color:var(--or) }
@media(max-width:768px) { .legal-toc { position:static } }

/* Corps */
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

.legal-defs { display:flex;flex-direction:column;gap:10px }
.legal-def { background:var(--cr);border-radius:8px;padding:10px 14px;font-size:13.5px;color:var(--gr);line-height:1.6 }
.legal-def strong { color:var(--dk) }

.legal-steps { display:flex;flex-direction:column;gap:8px;margin:14px 0 }
.legal-step { display:flex;align-items:center;gap:12px;background:var(--cr);border-radius:8px;padding:10px 14px;font-size:13.5px;color:var(--gr) }
.legal-step-num { width:26px;height:26px;border-radius:7px;background:var(--or);color:#fff;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0 }

.legal-warning { background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px 18px;font-size:14px;color:#dc2626;font-weight:600;margin:12px 0 }

.legal-contact-card { background:var(--cr);border-radius:14px;padding:22px;border:1.5px solid var(--grl);display:flex;flex-direction:column;gap:12px }
.legal-contact-title { font-size:16px;font-weight:800;color:var(--dk) }
.legal-contact-item { display:flex;align-items:center;gap:10px;font-size:14px;color:var(--gr) }
.legal-contact-item span:first-child { font-size:18px }
.legal-link { color:var(--or);text-decoration:none;font-weight:600 }
.legal-link:hover { text-decoration:underline }
</style>

@endsection