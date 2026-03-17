<template>
    <div>
        <!-- ═══════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════ -->
        <section class="hero">
            <div class="hero-glow"></div>
            <div class="hero-glow2"></div>
            <div class="hero-dots"></div>

            <div class="hero-inner">
                <div class="hero-badge au">
                    <span class="bdot"></span>⚡ Attribution automatique en
                    moins de 5 min
                </div>
                <h1 class="au1">
                    Trouvez le prestataire<br />
                    <span class="hl">qualifié &amp; certifié</span><br />
                    qu'il vous faut
                </h1>
                <p class="hero-desc au2">
                    🔧 Plomberie · ⚡ Électricité · ❄️ Climatisation · 🪚
                    Menuiserie · et plus.<br />
                    Des prestataires vérifiés, disponibles à Cotonou et dans
                    tout le Bénin.
                </p>
                <div class="hero-btns au3">
                    <a
                        class="btn btn-primary btn-lg"
                        :href="routes.registerClient"
                        >🔍 Faire une demande →</a
                    >
                    <a
                        class="btn btn-ghost btn-lg"
                        :href="routes.registerContractor"
                        >👷 Devenir prestataire</a
                    >
                </div>
                <div class="hero-stats au4">
                    <div class="hstat">
                        <span class="num">500+</span
                        ><span class="lbl">👷 Prestataires certifiés</span>
                    </div>
                    <div class="hstat">
                        <span class="num">2 000+</span
                        ><span class="lbl">✅ Interventions réalisées</span>
                    </div>
                    <div class="hstat">
                        <span class="num">4.8★</span
                        ><span class="lbl">😊 Note moyenne</span>
                    </div>
                    <div class="hstat">
                        <span class="num">&lt;5 min</span
                        ><span class="lbl">⚡ Attribution</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <img
                    class="hero-img"
                    src="https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=800&q=80"
                    alt="Artisan professionnel au travail"
                    loading="eager"
                />
                <div class="hcard hcard1">
                    <div class="hc-tag">🔧 Intervention en cours</div>
                    <div class="hc-val">Plombier assigné</div>
                    <div class="hc-sub">📍 En route · ETA 6 min</div>
                </div>
                <div class="hcard hcard2">
                    <div class="hc-stars">★★★★★</div>
                    <div class="hc-val">4.9 / 5</div>
                    <div class="hc-tag">😊 Satisfaction clients</div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         RECHERCHE RAPIDE
    ═══════════════════════════════════════════ -->
        <div class="search-wrap">
            <div class="search-box reveal">
                <div class="search-head">
                    <div class="search-head-icon">🔍</div>
                    <h3>Recherche rapide</h3>
                    <span>— Trouvez le bon artisan maintenant</span>
                </div>
                <div class="search-row">
                    <input
                        class="search-input"
                        type="text"
                        v-model="searchService"
                        placeholder="🔧 Quel service ? (Plomberie, Électricité…)"
                    />
                    <input
                        class="search-input"
                        type="text"
                        v-model="searchLocation"
                        placeholder="📍 Votre quartier à Cotonou…"
                    />
                    <button
                        class="btn btn-primary"
                        style="padding: 11px 22px; font-size: 14.5px"
                        @click="handleSearch"
                    >
                        Rechercher →
                    </button>
                </div>
                <div class="search-tags">
                    <span
                        class="stag"
                        v-for="t in quickTags"
                        :key="t.name"
                        @click="
                            searchService = t.name;
                            handleSearch();
                        "
                    >
                        {{ t.icon }} {{ t.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         STATS BAND
    ═══════════════════════════════════════════ -->
        <div class="stats-band">
            <div class="stats-inner">
                <div class="sbi reveal reveal-d1">
                    <span class="sbi-icon">👷</span>
                    <div>
                        <div class="sbi-num">500+</div>
                        <div class="sbi-lbl">Prestataires</div>
                    </div>
                </div>
                <div class="sbi reveal reveal-d2">
                    <span class="sbi-icon">✅</span>
                    <div>
                        <div class="sbi-num">2 000+</div>
                        <div class="sbi-lbl">Missions réussies</div>
                    </div>
                </div>
                <div class="sbi reveal reveal-d3">
                    <span class="sbi-icon">🗺️</span>
                    <div>
                        <div class="sbi-num">9 villes</div>
                        <div class="sbi-lbl">Couverture</div>
                    </div>
                </div>
                <div class="sbi reveal reveal-d4">
                    <span class="sbi-icon">😊</span>
                    <div>
                        <div class="sbi-num">98%</div>
                        <div class="sbi-lbl">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         SERVICES
    ═══════════════════════════════════════════ -->
        <section class="sec" id="services">
            <div class="sec-tag reveal">🔧 Catalogue de services</div>
            <div class="sec-title reveal reveal-d1">
                Tous vos besoins, un seul endroit
            </div>
            <div class="sec-sub reveal reveal-d2">
                Des artisans certifiés dans tous les corps de métier, attribués
                automatiquement selon votre localisation.
            </div>
            <div class="services-grid">
                <a
                    class="scard reveal"
                    :class="'reveal-d' + ((i % 6) + 1)"
                    href="#"
                    v-for="(s, i) in services"
                    :key="s.name"
                    @click.prevent="selectService(s.name)"
                >
                    <div class="scard-icon">{{ s.icon }}</div>
                    <h4>{{ s.name }}</h4>
                    <p>{{ s.desc }}</p>
                    <span class="scard-arr">→</span>
                </a>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         COMMENT ÇA MARCHE
    ═══════════════════════════════════════════ -->
        <section class="sec sec-cr" id="comment">
            <div class="sec-tag reveal">📋 Comment ça marche</div>
            <div class="sec-title reveal reveal-d1">Simple, rapide, fiable</div>
            <div class="sec-sub reveal reveal-d2">
                4 étapes pour trouver et gérer votre intervention de A à Z, sans
                tracas.
            </div>
            <div class="steps-grid">
                <div
                    class="step"
                    v-for="(s, i) in steps"
                    :key="i"
                    :style="{ transitionDelay: i * 0.1 + 's' }"
                >
                    <div class="step-num">{{ i + 1 }}</div>
                    <div class="step-ico">{{ s.icon }}</div>
                    <h4>{{ s.title }}</h4>
                    <p>{{ s.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         SPLIT 1 — Qualité garantie
    ═══════════════════════════════════════════ -->
        <div class="split">
            <img
                class="split-img reveal-left"
                src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&q=80"
                alt="Électricien certifié Bénin"
                loading="lazy"
            />
            <div class="split-content split-dark reveal-right">
                <div class="sec-tag">✅ Qualité garantie</div>
                <div class="sec-title">
                    Des pros vérifiés,<br />pas des inconnus
                </div>
                <p class="sec-sub">
                    Avant toute mission, chaque prestataire passe un processus
                    rigoureux de vérification.
                </p>
                <ul class="checklist">
                    <li>
                        <span class="ci">🪪</span
                        ><span
                            >CNI, casier judiciaire et diplômes vérifiés</span
                        >
                    </li>
                    <li>
                        <span class="ci">🏅</span
                        ><span>Badge DOMICILE et/ou ENTREPRISE attribué</span>
                    </li>
                    <li>
                        <span class="ci">📋</span
                        ><span>Formation aux standards Resotravo</span>
                    </li>
                    <li>
                        <span class="ci">⭐</span
                        ><span
                            >Évaluations clients après chaque intervention</span
                        >
                    </li>
                    <li>
                        <span class="ci">🔄</span
                        ><span>Accréditation renouvelée annuellement</span>
                    </li>
                </ul>
                <a class="btn btn-primary btn-lg" :href="routes.register"
                    >🔍 Faire une demande →</a
                >
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         CONFIANCE
    ═══════════════════════════════════════════ -->
        <section class="sec" id="confiance">
            <div class="sec-tag reveal">🛡️ Pourquoi Resotravo</div>
            <div class="sec-title reveal reveal-d1">
                La confiance au cœur<br />de chaque intervention
            </div>
            <div class="sec-sub reveal reveal-d2">
                Nous vérifions chaque prestataire avant qu'il accède à votre
                domicile ou entreprise.
            </div>
            <div class="trust-grid">
                <div
                    class="tcard reveal"
                    :class="'reveal-d' + ((i % 4) + 1)"
                    v-for="(t, i) in trust"
                    :key="t.title"
                >
                    <div class="tcard-ico">{{ t.icon }}</div>
                    <h4>{{ t.title }}</h4>
                    <p>{{ t.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         SPLIT 2 — Paiement sécurisé
    ═══════════════════════════════════════════ -->
        <div class="split">
            <div
                class="split-content reveal-left"
                style="background: var(--cr)"
            >
                <div class="sec-tag">💸 Paiement sécurisé</div>
                <div class="sec-title">Payez après,<br />jamais avant</div>
                <p class="sec-sub">
                    Votre argent ne bouge que quand vous confirmez la fin des
                    travaux. Zéro risque, zéro stress.
                </p>
                <ul class="checklist">
                    <li>
                        <span class="ci">📱</span
                        ><span>Paiement via MTN MoMo en 1 clic</span>
                    </li>
                    <li>
                        <span class="ci">🔒</span
                        ><span>Fonds bloqués jusqu'à votre validation</span>
                    </li>
                    <li>
                        <span class="ci">🧾</span
                        ><span>Facture automatique générée</span>
                    </li>
                    <li>
                        <span class="ci">📊</span
                        ><span>Historique complet de vos transactions</span>
                    </li>
                </ul>
                <a class="btn btn-primary btn-lg" :href="routes.register"
                    >Commencer gratuitement →</a
                >
            </div>
            <img
                class="split-img reveal-right"
                src="https://images.unsplash.com/photo-1687095956266-b724b1034f1f?q=80&w=930&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D?w=800&q=40"
                alt="Paiement mobile sécurisé"
                style="width: 95%; height: 95%"
                loading="lazy"
            />
        </div>

        <!-- ═══════════════════════════════════════════
         TÉMOIGNAGES
    ═══════════════════════════════════════════ -->
        <section class="sec sec-cr">
            <div class="sec-tag reveal">⭐ Avis clients</div>
            <div class="sec-title reveal reveal-d1">
                Ils nous font confiance
            </div>
            <div class="sec-sub reveal reveal-d2">
                Des centaines de clients satisfaits témoignent de leur
                expérience Resotravo.
            </div>
            <div class="test-grid">
                <div
                    class="testcard reveal"
                    :class="'reveal-d' + ((i % 3) + 1)"
                    v-for="(t, i) in testimonials"
                    :key="t.name"
                >
                    <div class="t-stars">★★★★★</div>
                    <p class="t-text">"{{ t.text }}"</p>
                    <div class="t-auth">
                        <div class="t-av">{{ t.name[0] }}</div>
                        <div>
                            <div class="t-name">{{ t.name }}</div>
                            <div class="t-role">{{ t.role }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         APP BANNER
    ═══════════════════════════════════════════ -->
        <div class="app-banner">
            <div class="app-banner-inner reveal">
                <div>
                    <h3>📲 Bientôt sur mobile</h3>
                    <p>
                        Gérez vos demandes depuis votre smartphone. Disponible
                        sur iOS &amp; Android très prochainement.
                    </p>
                </div>
                <div class="app-badges">
                    <div class="abadge">
                        <span class="abi">🍎</span>
                        <div class="abt">
                            <span class="abt-s">Bientôt sur</span
                            ><span class="abt-b">App Store</span>
                        </div>
                    </div>
                    <div class="abadge">
                        <span class="abi">🤖</span>
                        <div class="abt">
                            <span class="abt-s">Bientôt sur</span
                            ><span class="abt-b">Google Play</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         FAQ
    ═══════════════════════════════════════════ -->
        <section class="sec" id="faq">
            <div class="faq-layout">
                <div class="faq-left reveal-left">
                    <div class="sec-tag">❓ FAQ</div>
                    <div class="sec-title">Questions fréquentes</div>
                    <p class="sec-sub">
                        Tout ce que vous devez savoir sur Resotravo en quelques
                        clics.
                    </p>
                    <div class="faq-help">
                        💬 Pas de réponse ? Contactez-nous sur
                        <a href="#">WhatsApp</a> ou <a href="#">par email</a>
                    </div>
                </div>
                <div class="faq-list reveal-right">
                    <div class="faq-item" v-for="(f, i) in faqs" :key="i">
                        <button
                            class="faq-q"
                            :class="{ open: openFaq === i }"
                            @click="openFaq = openFaq === i ? null : i"
                        >
                            <span>{{ f.icon }} {{ f.q }}</span>
                            <span class="faq-chev">+</span>
                        </button>
                        <div class="faq-a" :class="{ open: openFaq === i }">
                            {{ f.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         PARTENAIRES
    ═══════════════════════════════════════════ -->
        <div class="partners">
            <div class="plbl">Partenaires &amp; Écosystème</div>
            <div class="prow">
                <span class="pname">📱 MTN MoMo</span>
                <span class="pname">📡 Moov Africa</span>
                <span class="pname">⚡ SBEE</span>
                <span class="pname">💧 SONEB</span>
                <span class="pname">🏛️ Mairie Cotonou</span>
                <span class="pname">🏢 CCIB</span>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         CTA FINAL
    ═══════════════════════════════════════════ -->
        <div class="cta-final" id="register">
            <div class="cta-inner reveal">
                <h2>Prêt à trouver votre artisan ? 🚀</h2>
                <p>
                    Rejoignez des milliers de clients et prestataires sur
                    Resotravo. C'est gratuit et sans engagement.
                </p>
                <div class="cta-btns">
                    <a class="btn btn-dark btn-lg" :href="routes.registerClient"
                        >🔍 Je cherche un prestataire →</a
                    >
                    <a
                        class="btn btn-ghost btn-lg"
                        :href="routes.registerContractor"
                        >👷 Je suis prestataire</a
                    >
                </div>
            </div>
        </div>

        <!-- Toast notification -->
        <div class="toast" :class="{ hidden: !toastVisible }">
            {{ toastMsg }}
        </div>
    </div>
</template>

<script>
export default {
    name: "HomeComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                register: "/register",
                registerPrestataire: "/register/prestataire",
                registerClient: "/register/client",
                registerContractor: "/register/contractor",
            }),
        },
    },

    data() {
        return {
            /* ── UI ── */
            searchService: "",
            searchLocation: "",
            toastVisible: false,
            toastMsg: "",
            openFaq: null,

            /* ── Tags rapides ── */
            quickTags: [
                { icon: "🚿", name: "Plomberie" },
                { icon: "⚡", name: "Électricité" },
                { icon: "❄️", name: "Climatisation" },
                { icon: "🪚", name: "Menuiserie" },
                { icon: "🧹", name: "Nettoyage" },
                { icon: "🔩", name: "Mécanique" },
                { icon: "🖌️", name: "Peinture" },
                { icon: "🧱", name: "Maçonnerie" },
            ],

            /* ── Services ── */
            services: [
                {
                    icon: "🚿",
                    name: "Plomberie",
                    desc: "Fuites, canalisations, robinetterie, sanitaires.",
                },
                {
                    icon: "⚡",
                    name: "Électricité",
                    desc: "Installations, pannes, tableaux électriques.",
                },
                {
                    icon: "🪚",
                    name: "Menuiserie",
                    desc: "Portes, fenêtres, meubles sur-mesure, parquet.",
                },
                {
                    icon: "⚙️",
                    name: "Ferronnerie",
                    desc: "Portails, grilles de sécurité, escaliers métalliques.",
                },
                {
                    icon: "❄️",
                    name: "Climatisation",
                    desc: "Installation, réparation, maintenance clim & froid.",
                },
                {
                    icon: "🔩",
                    name: "Mécanique Auto",
                    desc: "Entretien, réparations, diagnostic véhicules.",
                },
                {
                    icon: "🏗️",
                    name: "Maintenance",
                    desc: "Petits travaux, réparations diverses à domicile.",
                },
                {
                    icon: "🧹",
                    name: "Nettoyage",
                    desc: "Entretien ménager, nettoyage professionnel.",
                },
                {
                    icon: "🛞",
                    name: "Vulcanisation",
                    desc: "Pneus, chambres à air, équilibrage, jantes.",
                },
                {
                    icon: "🖌️",
                    name: "Peinture",
                    desc: "Peinture intérieure, extérieure, décoration murale.",
                },
                {
                    icon: "🪟",
                    name: "Vitrerie",
                    desc: "Pose et remplacement de vitres et miroirs.",
                },
                {
                    icon: "🧱",
                    name: "Maçonnerie",
                    desc: "Construction, rénovation, carrelage, béton.",
                },
                {
                    icon: "🔐",
                    name: "Serrurerie",
                    desc: "Remplacement de serrures, blindage, coffres-forts.",
                },
                {
                    icon: "🌿",
                    name: "Jardinage",
                    desc: "Taille, tonte, aménagement paysager, entretien.",
                },
                {
                    icon: "🏊",
                    name: "Piscine",
                    desc: "Installation, entretien et traitement de piscines.",
                },
                {
                    icon: "📡",
                    name: "Antenne & TV",
                    desc: "Installation d'antennes, paraboles, câblage TV.",
                },
            ],

            /* ── Étapes ── */
            steps: [
                {
                    icon: "📝",
                    title: "Décrivez votre besoin",
                    desc: "Service, adresse, description et disponibilités souhaitées en quelques secondes.",
                },
                {
                    icon: "🤖",
                    title: "Attribution automatique",
                    desc: "Le prestataire certifié le plus proche est assigné en moins de 5 min.",
                },
                {
                    icon: "📍",
                    title: "Suivi en temps réel",
                    desc: "Suivez l'arrivée du prestataire sur carte avec ETA dynamique recalculé.",
                },
                {
                    icon: "💸",
                    title: "Paiement sécurisé",
                    desc: "Confirmez la fin des travaux, puis payez via MTN MoMo en un clic.",
                },
            ],

            /* ── Confiance ── */
            trust: [
                {
                    icon: "✅",
                    title: "Prestataires certifiés",
                    desc: "6 pièces vérifiées : CNI, casier judiciaire, diplômes, références.",
                },
                {
                    icon: "🏅",
                    title: "Accréditation différenciée",
                    desc: "Badge DOMICILE & ENTREPRISE. Seuls les accrédités accèdent aux missions.",
                },
                {
                    icon: "📍",
                    title: "Suivi GPS temps réel",
                    desc: "Position visible sur carte. ETA recalculé jusqu'à l'arrivée.",
                },
                {
                    icon: "💸",
                    title: "Paiement post-travaux",
                    desc: "Vous payez après confirmation de fin. Zéro risque pour vous.",
                },
                {
                    icon: "⭐",
                    title: "Système d'évaluation",
                    desc: "Notez chaque prestataire. Les moins bien notés sont suspendus.",
                },
                {
                    icon: "🔄",
                    title: "Disponible 7j/7",
                    desc: "La plateforme fonctionne tous les jours, weekends et jours fériés.",
                },
                {
                    icon: "🛡️",
                    title: "Données protégées",
                    desc: "Vos infos sont chiffrées et jamais revendues à des tiers.",
                },
                {
                    icon: "📞",
                    title: "Support réactif",
                    desc: "Notre équipe répond en moins de 2h par chat, appel ou WhatsApp.",
                },
            ],

            /* ── Témoignages ── */
            testimonials: [
                {
                    name: "Adjovi Kokou",
                    role: "Particulier · Cotonou",
                    text: "En moins de 10 minutes après ma demande, le plombier était déjà en route. Travail propre, professionnel. Je recommande vivement !",
                },
                {
                    name: "Bertrand Houngbo",
                    role: "Chef de projet · SBEE",
                    text: "Nous utilisons Resotravo pour nos chantiers. La qualité des prestataires et la rapidité d'attribution sont bluffantes.",
                },
                {
                    name: "Fatoumata Diallo",
                    role: "Restauratrice · Porto-Novo",
                    text: "Ma climatisation est tombée en plein service. Grâce à Resotravo, un technicien était là en 12 minutes. Impressionnant !",
                },
                {
                    name: "Kofi Mensah",
                    role: "Propriétaire · Calavi",
                    text: "Le paiement post-travaux m'a totalement rassuré. Excellent service, je suis désormais client fidèle de Resotravo.",
                },
                {
                    name: "Aïssatou Barry",
                    role: "DRH · Lokossa",
                    text: "Nous avons sous-traité notre maintenance à Resotravo. Gain de temps énorme, factures automatiques. Parfait !",
                },
                {
                    name: "Rodrigue Dossou",
                    role: "Particulier · Bohicon",
                    text: "Simple, rapide, fiable. J'ai pu suivre l'électricien en direct sur la carte. Travaux finis en 1h chrono.",
                },
            ],

            /* ── FAQ ── */
            faqs: [
                {
                    icon: "👷",
                    q: "Comment Resotravo sélectionne-t-il ses prestataires ?",
                    a: "Chaque prestataire passe un processus rigoureux : CNI, casier judiciaire, diplômes et références professionnelles vérifiés. Ils reçoivent un badge d'accréditation DOMICILE et/ou ENTREPRISE.",
                },
                {
                    icon: "⚡",
                    q: "Combien de temps faut-il pour trouver un artisan ?",
                    a: "Notre algorithme vous met en relation avec le prestataire certifié le plus proche en moins de 5 minutes. À Cotonou, le délai moyen est de 3 minutes.",
                },
                {
                    icon: "💸",
                    q: "Comment fonctionne le paiement ?",
                    a: "Paiement sécurisé et post-travaux uniquement. Vous ne payez qu'après avoir confirmé la bonne réalisation. Le règlement se fait via MTN MoMo dans l'application.",
                },
                {
                    icon: "😟",
                    q: "Que faire si je ne suis pas satisfait ?",
                    a: "Signalez l'intervention dans l'app. Notre équipe intervient sous 2h pour trouver une solution, incluant le remboursement si nécessaire.",
                },
                {
                    icon: "🗺️",
                    q: "Resotravo est-il disponible hors de Cotonou ?",
                    a: "Oui ! Nous couvrons 9 villes : Cotonou, Porto-Novo, Parakou, Abomey-Calavi, Bohicon, Natitingou, Ouidah, Lokossa et Djougou.",
                },
                {
                    icon: "🚀",
                    q: "Comment devenir prestataire sur Resotravo ?",
                    a: "Inscrivez-vous, renseignez votre métier et vos documents. Notre équipe traite les dossiers sous 48h. Une fois validé, vous recevez des missions dans votre secteur.",
                },
                {
                    icon: "❤️",
                    q: "Puis-je demander un prestataire spécifique ?",
                    a: "Oui ! Après une première intervention, sauvegardez un prestataire en favori et sollicitez-le en priorité pour vos prochaines demandes.",
                },
            ],
        };
    },

    mounted() {
        this.$nextTick(() => {
            this.reObserveReveal();
            this.observeSteps();
            this.animateCounters();
        });
    },

    methods: {
        /* ── Recherche ── */
        handleSearch() {
            if (!this.searchService.trim()) {
                this.showToast("⚠️ Veuillez indiquer le service recherché.");
                return;
            }
            this.showToast(
                `🔍 Recherche : "${this.searchService}" — ${this.searchLocation || "partout au Bénin"}`,
            );
        },

        selectService(name) {
            this.searchService = name;
            document
                .querySelector(".search-wrap")
                ?.scrollIntoView({ behavior: "smooth" });
            this.showToast(`✅ Service sélectionné : ${name}`);
        },

        /* ── Toast ── */
        showToast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            setTimeout(() => {
                this.toastVisible = false;
            }, 3000);
        },

        /* ── Scroll reveal — ré-observe les éléments des v-for ── */
        reObserveReveal() {
            if (!("IntersectionObserver" in window)) return;
            setTimeout(() => {
                const io = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add("revealed");
                                io.unobserve(e.target);
                            }
                        });
                    },
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" },
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed), .reveal-left:not(.revealed), .reveal-right:not(.revealed)",
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },

        /* ── Steps — observe après rendu Vue du v-for ── */
        observeSteps() {
            setTimeout(() => {
                const steps = document.querySelectorAll(".step");

                if (!("IntersectionObserver" in window)) {
                    // Fallback : tout rendre visible directement
                    steps.forEach((el) => el.classList.add("visible"));
                    return;
                }

                const io = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add("visible");
                                io.unobserve(e.target);
                            }
                        });
                    },
                    { threshold: 0.12 },
                );

                steps.forEach((el) => {
                    // Si déjà dans le viewport → visible immédiatement sans attendre scroll
                    const rect = el.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        el.classList.add("visible");
                    } else {
                        io.observe(el);
                    }
                });
            }, 200);
        },

        /* ── Compteurs animés (stats band) ── */
        animateCounters() {
            if (!("IntersectionObserver" in window)) return;
            const io = new IntersectionObserver(
                (entries) => {
                    entries.forEach((e) => {
                        if (!e.isIntersecting) return;
                        const el = e.target;
                        const raw = el.textContent.trim();
                        const num = parseFloat(raw.replace(/[^0-9.]/g, ""));
                        const sfx = raw.replace(/[0-9.]/g, "");
                        if (isNaN(num)) {
                            io.unobserve(el);
                            return;
                        }
                        let v = 0;
                        const inc = num / (1200 / 16);
                        const timer = setInterval(() => {
                            v = Math.min(v + inc, num);
                            el.textContent =
                                (Number.isInteger(num)
                                    ? Math.round(v)
                                    : v.toFixed(1)) + sfx;
                            if (v >= num) {
                                clearInterval(timer);
                                el.textContent = raw;
                            }
                        }, 16);
                        io.unobserve(el);
                    });
                },
                { threshold: 0.5 },
            );
            document
                .querySelectorAll(".sbi-num")
                .forEach((el) => io.observe(el));
        },
    },
};
</script>
