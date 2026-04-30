@extends('layouts.front')

@section('title', "Politique de Confidentialite")

@section('styles')
<style>
/* Styles partages avec cgu.blade.php */
.legal-page { min-height:60vh;background:var(--wh) }
.legal-hero { background:var(--dk2);color:#fff;padding:52px 4% 44px;position:relative;overflow:hidden }
.legal-hero::before { content:'';position:absolute;top:-150px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.12) 0%,transparent 70%);pointer-events:none }
.legal-hero-inner { position:relative;z-index:2;max-width:700px }
.legal-badge { display:inline-flex;align-items:center;gap:8px;background:rgba(249,115,22,.15);border:1px solid rgba(249,115,22,.3);color:var(--am);padding:6px 15px;border-radius:99px;font-size:13px;font-weight:600;margin-bottom:18px }
.legal-hero h1 { font-size:clamp(28px,5vw,48px);font-weight:800;line-height:1.15;margin-bottom:12px }
.legal-hero p { font-size:14.5px;color:#b8ada7 }
.legal-container { max-width:920px;margin:0 auto;padding:40px 4% 60px;display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:start }
@media(max-width:768px) { .legal-container { grid-template-columns:1fr } }
.legal-toc { position:sticky;top:80px;background:var(--cr);border-radius:14px;padding:20px;border:1.5px solid var(--grl) }
.legal-toc h3 { font-size:14px;font-weight:700;color:var(--dk);margin-bottom:12px }
.legal-toc ol { padding-left:16px;display:flex;flex-direction:column;gap:6px }
.legal-toc li a { font-size:12.5px;color:var(--gr);text-decoration:none;transition:color .18s;line-height:1.5 }
.legal-toc li a:hover { color:var(--or) }
@media(max-width:768px) { .legal-toc { position:static } }
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
.legal-table { width:100%;border-collapse:collapse;margin:14px 0;font-size:13.5px }
.legal-table th { background:var(--dk);color:#fff;padding:10px 14px;text-align:left;font-weight:700 }
.legal-table td { padding:10px 14px;border-bottom:1px solid var(--grl);color:var(--gr);vertical-align:top;line-height:1.6 }
.legal-table tr:last-child td { border-bottom:none }
.legal-table tr:hover td { background:var(--cr) }
.legal-rights { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:14px 0 }
.legal-right-card { background:var(--cr);border-radius:10px;padding:14px;border:1px solid var(--grl) }
.legal-right-icon { font-size:22px;margin-bottom:8px }
.legal-right-title { font-size:13.5px;font-weight:700;color:var(--dk);margin-bottom:4px }
.legal-right-desc  { font-size:12.5px;color:var(--gr);line-height:1.6 }
.legal-warning { background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px 18px;font-size:14px;color:#dc2626;font-weight:600;margin:12px 0 }
.legal-success { background:#dcfce7;border:1.5px solid #86efac;border-radius:10px;padding:14px 18px;font-size:14px;color:#16a34a;font-weight:600;margin:12px 0 }
.legal-contact-card { background:var(--cr);border-radius:14px;padding:22px;border:1.5px solid var(--grl);display:flex;flex-direction:column;gap:12px }
.legal-contact-title { font-size:16px;font-weight:800;color:var(--dk) }
.legal-contact-item { display:flex;align-items:center;gap:10px;font-size:14px;color:var(--gr) }
.legal-contact-item span:first-child { font-size:18px }
.legal-link { color:var(--or);text-decoration:none;font-weight:600 }
.legal-link:hover { text-decoration:underline }
</style>
@endsection

@section('content')

<div class="legal-page">

    <!-- HERO -->
    <div class="legal-hero">
        <div class="legal-hero-inner">
            <div class="legal-badge">
                <span>&#x1F512;</span> Protection de vos donnees
            </div>
            <h1>Politique de<br /><span class="hl">Confidentialite</span></h1>
            <p>Derniere mise a jour : Mars 2026 &mdash; Version 2.0</p>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="legal-container">

        <!-- Sommaire -->
        <div class="legal-toc">
            <h3>Sommaire</h3>
            <ol>
                <li><a href="#pc-1">Responsable du traitement</a></li>
                <li><a href="#pc-2">Donnees collectees</a></li>
                <li><a href="#pc-3">Finalites du traitement</a></li>
                <li><a href="#pc-4">Base legale</a></li>
                <li><a href="#pc-5">Conservation des donnees</a></li>
                <li><a href="#pc-6">Partage des donnees</a></li>
                <li><a href="#pc-7">Securite</a></li>
                <li><a href="#pc-8">Vos droits</a></li>
                <li><a href="#pc-9">Cookies</a></li>
                <li><a href="#pc-10">Mineurs</a></li>
                <li><a href="#pc-11">Modifications</a></li>
                <li><a href="#pc-12">Contact DPO</a></li>
            </ol>
        </div>

        <!-- Corps -->
        <div class="legal-body">

            <div class="legal-intro">
                <p>
                    Mesotravo accorde une importance capitale a la protection de vos donnees personnelles.
                    La presente politique explique quelles donnees nous collectons, pourquoi, comment nous les utilisons
                    et quels sont vos droits.
                </p>
                <p>
                    Cette politique s'applique a tous les utilisateurs de la plateforme
                    <strong>Mesotravo.com</strong> : Clients, Prestataires, Talents et Visiteurs.
                </p>
            </div>

            <!-- Article 1 -->
            <div class="legal-article" id="pc-1">
                <h2><span class="art-num">01</span> Responsable du traitement</h2>
                <div class="legal-contact-card">
                    <div class="legal-contact-title">Mesotravo</div>
                    <div class="legal-contact-item">
                        <span>&#x1F310;</span>
                        <a href="https://Mesotravo.com" class="legal-link">www.Mesotravo.com</a>
                    </div>
                    <div class="legal-contact-item">
    <span>&#x2709;&#xFE0F;</span>
    <span><a href="mailto:contact@Mesotravo.com" class="legal-link">contact@Mesotravo.com</a></span>
</div>
                    <div class="legal-contact-item">
                        <span>&#x1F4DE;</span>
                        <a href="tel:+22901966363644" class="legal-link">+229 01 90 00 36 26</a>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4AC;</span>
                        WhatsApp : <a href="https://wa.me/2290190003626" class="legal-link">+229 01 90 00 36 26</a>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4CD;</span>
                        Cotonou, Benin
                    </div>
                </div>
            </div>

            <!-- Article 2 -->
            <div class="legal-article" id="pc-2">
                <h2><span class="art-num">02</span> Donnees collectees</h2>
                <h3>2.1 Donnees fournies par l'utilisateur</h3>
                <table class="legal-table">
                    <thead>
                        <tr>
                            <th>Categorie</th>
                            <th>Donnees collectees</th>
                            <th>Concerne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Identite</strong></td>
                            <td>Nom, prenom, photo de profil</td>
                            <td>Tous les utilisateurs</td>
                        </tr>
                        <tr>
                            <td><strong>Contact</strong></td>
                            <td>Email, numero de telephone</td>
                            <td>Tous les utilisateurs</td>
                        </tr>
                        <tr>
                            <td><strong>Adresse</strong></td>
                            <td>Adresse postale, zone d'intervention</td>
                            <td>Clients, Prestataires</td>
                        </tr>
                        <tr>
                            <td><strong>Documents officiels</strong></td>
                            <td>CNI/Passeport, casier judiciaire, diplomes, attestation de residence, CIP</td>
                            <td>Prestataires uniquement</td>
                        </tr>
                        <tr>
                            <td><strong>Professionnel</strong></td>
                            <td>Specialite, diplomes, certifications, experiences</td>
                            <td>Prestataires, Talents</td>
                        </tr>
                        <tr>
                            <td><strong>Paiement</strong></td>
                            <td>Numero MoMo (pour les transactions), historique des paiements</td>
                            <td>Clients, Prestataires</td>
                        </tr>
                    </tbody>
                </table>

                <h3>2.2 Donnees collectees automatiquement</h3>
                <ul>
                    <li>Adresse IP et donnees de connexion</li>
                    <li>Donnees de geolocalisation GPS (uniquement lors des interventions, avec consentement)</li>
                    <li>Historique des missions et transactions</li>
                    <li>Logs de navigation sur la plateforme</li>
                    <li>Donnees de cookies et traceurs (voir article 9)</li>
                </ul>
            </div>

            <!-- Article 3 -->
            <div class="legal-article" id="pc-3">
                <h2><span class="art-num">03</span> Finalites du traitement</h2>
                <p>Vos donnees sont collectees et traitees pour les finalites suivantes :</p>
                <ul>
                    <li><strong>Gestion des comptes</strong> : creation, authentification et securisation des comptes utilisateurs</li>
                    <li><strong>Mise en relation</strong> : attribution des missions, geolocalisation des prestataires</li>
                    <li><strong>Verification administrative</strong> : controle des dossiers prestataires (CNI, casier judiciaire, diplomes)</li>
                    <li><strong>Paiements</strong> : traitement des transactions via MTN MoMo, gestion des commissions</li>
                    <li><strong>Communication</strong> : notifications SMS/email, messagerie integree, service Allô Conseils</li>
                    <li><strong>Amelioration du service</strong> : analyses statistiques (donnees anonymisees), detection des fraudes</li>
                    <li><strong>Obligations legales</strong> : conservation des factures, reponse aux demandes des autorites competentes</li>
                </ul>
            </div>

            <!-- Article 4 -->
            <div class="legal-article" id="pc-4">
                <h2><span class="art-num">04</span> Base legale des traitements</h2>
                <table class="legal-table">
                    <thead>
                        <tr>
                            <th>Traitement</th>
                            <th>Base legale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gestion du compte et des missions</td>
                            <td>Execution du contrat</td>
                        </tr>
                        <tr>
                            <td>Verification des dossiers prestataires</td>
                            <td>Obligation legale + interet legitime</td>
                        </tr>
                        <tr>
                            <td>Geolocalisation lors des interventions</td>
                            <td>Consentement de l'utilisateur</td>
                        </tr>
                        <tr>
                            <td>Notifications marketing</td>
                            <td>Consentement de l'utilisateur</td>
                        </tr>
                        <tr>
                            <td>Analyses statistiques anonymisees</td>
                            <td>Interet legitime de Mesotravo</td>
                        </tr>
                        <tr>
                            <td>Conservation des factures</td>
                            <td>Obligation legale</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Article 5 -->
            <div class="legal-article" id="pc-5">
                <h2><span class="art-num">05</span> Conservation des donnees</h2>
                <table class="legal-table">
                    <thead>
                        <tr>
                            <th>Type de donnees</th>
                            <th>Duree de conservation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Donnees de compte actif</td>
                            <td>Duree de la relation + 3 ans apres resiliation</td>
                        </tr>
                        <tr>
                            <td>Documents de certification prestataire</td>
                            <td>Duree de la relation + 5 ans</td>
                        </tr>
                        <tr>
                            <td>Historique des missions et transactions</td>
                            <td>10 ans (obligations comptables)</td>
                        </tr>
                        <tr>
                            <td>Donnees de geolocalisation</td>
                            <td>72 heures apres la fin de l'intervention</td>
                        </tr>
                        <tr>
                            <td>Logs de connexion</td>
                            <td>12 mois</td>
                        </tr>
                        <tr>
                            <td>Donnees de cookies</td>
                            <td>13 mois maximum</td>
                        </tr>
                    </tbody>
                </table>
                <p>A l'expiration de ces delais, vos donnees sont supprimees ou anonymisees de maniere irreversible.</p>
            </div>

            <!-- Article 6 -->
            <div class="legal-article" id="pc-6">
                <h2><span class="art-num">06</span> Partage des donnees</h2>
                <h3>6.1 Partage interne a la plateforme</h3>
                <p>Certaines donnees sont visibles par d'autres utilisateurs dans le cadre du fonctionnement normal de la plateforme :</p>
                <ul>
                    <li>Le <strong>Client</strong> voit le nom, la photo, la note et la position GPS du Prestataire lors d'une mission</li>
                    <li>Le <strong>Prestataire</strong> voit le nom, l'adresse d'intervention et la description du besoin du Client</li>
                    <li>Les <strong>profils publics</strong> des Talents sont visibles par les Entreprises inscrites</li>
                </ul>
                <p>Les numeros de telephone sont masques dans la messagerie integree — aucun echange direct de coordonnees n'est autorise en dehors de la plateforme.</p>

                <h3>6.2 Partage avec des tiers</h3>
                <p>Mesotravo peut partager vos donnees avec :</p>
                <ul>
                    <li><strong>MTN MoMo</strong> : pour le traitement des paiements (donnees minimales requises)</li>
                    <li><strong>Google Maps</strong> : pour la geolocalisation (coordonnees GPS anonymisees)</li>
                    <li><strong>Hebergeur</strong> : le serveur dedie sur lequel la plateforme est deployee</li>
                    <li><strong>Autorites competentes</strong> : sur requisition legale uniquement</li>
                </ul>

                <div class="legal-success">
                    Mesotravo ne vend jamais vos donnees personnelles a des tiers a des fins commerciales.
                </div>
            </div>

            <!-- Article 7 -->
            <div class="legal-article" id="pc-7">
                <h2><span class="art-num">07</span> Securite des donnees</h2>
                <p>Mesotravo met en oeuvre des mesures techniques et organisationnelles appropriees pour proteger vos donnees :</p>
                <ul>
                    <li>Chiffrement HTTPS de toutes les communications</li>
                    <li>Chiffrement des mots de passe (hachage bcrypt)</li>
                    <li>Chiffrement des documents sensibles (CNI, casier judiciaire) en base de donnees</li>
                    <li>Acces aux donnees sensibles restreint au personnel habilite</li>
                    <li>Sauvegardes regulieres sur serveur dedie securise</li>
                    <li>Journalisation des acces aux donnees sensibles</li>
                </ul>
                <div class="legal-warning">
                    En cas de violation de donnees susceptible d'engendrer un risque pour vos droits et libertes, Mesotravo s'engage a vous en informer dans les meilleurs delais.
                </div>
            </div>

            <!-- Article 8 -->
            <div class="legal-article" id="pc-8">
                <h2><span class="art-num">08</span> Vos droits</h2>
                <p>Conformement a la reglementation applicable, vous disposez des droits suivants sur vos donnees personnelles :</p>
                <div class="legal-rights">
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x1F440;</div>
                        <div class="legal-right-title">Droit d'acces</div>
                        <div class="legal-right-desc">Obtenir une copie de vos donnees personnelles detenues par Mesotravo.</div>
                    </div>
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x270F;&#xFE0F;</div>
                        <div class="legal-right-title">Droit de rectification</div>
                        <div class="legal-right-desc">Faire corriger des donnees inexactes ou completer des donnees incompletes.</div>
                    </div>
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x1F5D1;&#xFE0F;</div>
                        <div class="legal-right-title">Droit a l'effacement</div>
                        <div class="legal-right-desc">Demander la suppression de vos donnees, sous reserve des obligations legales.</div>
                    </div>
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x23F8;&#xFE0F;</div>
                        <div class="legal-right-title">Droit a la limitation</div>
                        <div class="legal-right-desc">Demander la suspension temporaire du traitement de vos donnees.</div>
                    </div>
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x1F4E6;</div>
                        <div class="legal-right-title">Droit a la portabilite</div>
                        <div class="legal-right-desc">Recevoir vos donnees dans un format structure et lisible par machine.</div>
                    </div>
                    <div class="legal-right-card">
                        <div class="legal-right-icon">&#x1F6AB;</div>
                        <div class="legal-right-title">Droit d'opposition</div>
                        <div class="legal-right-desc">Vous opposer au traitement de vos donnees fonde sur l'interet legitime.</div>
                    </div>
                </div>
                <p>Pour exercer l'un de ces droits, contactez-nous :</p>
                <ul>
                    <li>Par telephone : <a href="tel:+22901966363644" class="legal-link">+229 01 90 00 36 26</a></li>
                    <li>Par WhatsApp : <a href="https://wa.me/22901966363644" class="legal-link">+229 01 90 00 36 26</a></li>
                    <li>Via le site : <a href="https://Mesotravo.com" class="legal-link">Mesotravo.com</a></li>
                </ul>
                <p>Nous nous engageons a repondre dans un delai d'un (1) mois a compter de la reception de votre demande.</p>
            </div>

            <!-- Article 9 -->
            <div class="legal-article" id="pc-9">
                <h2><span class="art-num">09</span> Cookies et traceurs</h2>
                <h3>9.1 Cookies utilises</h3>
                <table class="legal-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Finalite</th>
                            <th>Duree</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Session</strong></td>
                            <td>Maintien de la session utilisateur authentifie</td>
                            <td>Fin de session</td>
                        </tr>
                        <tr>
                            <td><strong>Preference</strong></td>
                            <td>Memorisation des preferences de navigation</td>
                            <td>12 mois</td>
                        </tr>
                        <tr>
                            <td><strong>Analytique</strong></td>
                            <td>Mesure d'audience anonymisee (amelioration du service)</td>
                            <td>13 mois</td>
                        </tr>
                        <tr>
                            <td><strong>Securite</strong></td>
                            <td>Protection contre les attaques CSRF et autres</td>
                            <td>Session</td>
                        </tr>
                    </tbody>
                </table>
                <h3>9.2 Gestion des cookies</h3>
                <p>Vous pouvez parametrer votre navigateur pour refuser les cookies non essentiels. Le refus des cookies de session peut empecher l'acces a certaines fonctionnalites de la plateforme.</p>
            </div>

            <!-- Article 10 -->
            <div class="legal-article" id="pc-10">
                <h2><span class="art-num">10</span> Mineurs</h2>
                <div class="legal-warning">
                    La plateforme Mesotravo est reservee aux personnes majeures (18 ans et plus). Mesotravo ne collecte pas sciemment de donnees personnelles concernant des mineurs.
                </div>
                <p>Si vous pensez qu'un mineur a cree un compte sur notre plateforme, contactez-nous immediatement afin que nous puissions supprimer le compte et les donnees associees.</p>
            </div>

            <!-- Article 11 -->
            <div class="legal-article" id="pc-11">
                <h2><span class="art-num">11</span> Modifications de la politique</h2>
                <p>Mesotravo se reserve le droit de modifier la presente politique de confidentialite a tout moment pour refleter des changements dans nos pratiques ou la reglementation applicable.</p>
                <p>Toute modification substantielle sera notifiee aux utilisateurs par email et/ou par un avis visible sur la plateforme au moins 15 jours avant son entree en vigueur.</p>
                <p>La version en vigueur est toujours accessible a l'adresse <a href="{{ route('policy') }}" class="legal-link">Mesotravo.com/policy</a>.</p>
            </div>

            <!-- Article 12 -->
            <div class="legal-article" id="pc-12">
                <h2><span class="art-num">12</span> Contact - Delegue a la Protection des Donnees</h2>
                <p>Pour toute question relative a la protection de vos donnees personnelles ou pour exercer vos droits, contactez notre equipe :</p>
                <div class="legal-contact-card">
                    <div class="legal-contact-title">Mesotravo - Protection des donnees</div>
                    <div class="legal-contact-item">
                        <span>&#x1F310;</span>
                        <a href="https://Mesotravo.com" class="legal-link">Mesotravo.com</a>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4DE;</span>
                        <a href="tel:+22901966363644" class="legal-link">+229 01 90 00 36 26</a>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4AC;</span>
                        WhatsApp : <a href="https://wa.me/22901966363644" class="legal-link">+229 01 90 00 36 26</a>
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4CD;</span>
                        Cotonou, Benin
                    </div>
                    <div class="legal-contact-item">
                        <span>&#x1F4C5;</span>
                        Reponse garantie sous 30 jours
                    </div>
                </div>
                <p style="margin-top:16px">
                    Vous avez egalement le droit d'introduire une reclamation aupres de l'autorite de protection des donnees competente au Benin si vous estimez que le traitement de vos donnees ne respecte pas la reglementation applicable.
                </p>
            </div>

        </div>
    </div>

</div>

@endsection