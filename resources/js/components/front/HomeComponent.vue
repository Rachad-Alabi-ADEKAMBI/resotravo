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
                    <span
                        class="hl"
                        style="
                            text-decoration: none !important;
                            border-bottom: none !important;
                            box-shadow: none !important;
                            background-image: none !important;
                        "
                        >qualifié &amp; certifié</span
                    ><br />
                    qu'il vous faut
                </h1>
                <p class="hero-desc au2">
                    🔧 Plomberie · ⚡ Électricité · ❄️ Climatisation · 🪚
                    Menuiserie.<br />
                    Des prestataires vérifiés, près de chez vous.
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
            </div>

            <div class="hero-visual">
                <img
                    class="hero-img"
                    :src="heroImage"
                    alt="Prestataire professionnel au travail"
                    loading="eager"
                    style="
                        object-fit: cover;
                        object-position: center 50%;
                        transform: scale(0.95);
                        transform-origin: center center;
                    "
                />
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
        <div v-if="false" class="search-wrap">
            <div class="search-box reveal">
                <div class="search-head">
                    <div class="search-head-icon">🔍</div>
                    <span>— Trouvez vite un prestataire</span>
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
                        <div class="sbi-lbl">Clients</div>
                    </div>
                </div>
                <div class="sbi reveal reveal-d3">
                    <span class="sbi-icon">🗺️</span>
                    <div>
                        <div class="sbi-num">12 départements</div>
                        <div class="sbi-lbl">Couverts</div>
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
                Des prestataires certifiés pour vos besoins essentiels.
            </div>
            <div class="services-grid">
                <a
                    class="scard reveal"
                    :class="'reveal-d' + ((i % 6) + 1)"
                    href="#"
                    v-for="(s, i) in displayedServices"
                    :key="s.name"
                    @click.prevent="selectService(s.name)"
                >
                    <div class="scard-icon">{{ s.icon }}</div>
                    <h4>{{ s.name }}</h4>
                    <p>{{ s.desc }}</p>
                    <span class="scard-arr">→</span>
                </a>
            </div>
            <div class="services-more" v-if="hasMoreServices">
                <button
                    type="button"
                    class="btn btn-ghost services-more-btn"
                    @click="showMoreServices"
                >
                    Voir plus
                </button>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         COMMENT ÇA MARCHE
    ═══════════════════════════════════════════ -->
        <section class="sec sec-cr" id="comment">
            <div class="sec-tag reveal">📋 Comment ça marche</div>
            <div class="sec-title reveal reveal-d1">Simple, rapide, fiable</div>
            <div class="sec-sub reveal reveal-d2">
                4 étapes, sans complication.
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
        <div class="split payment-split">
            <img
                class="split-img reveal-left"
                :src="image2"
                alt="Électricien certifié Bénin"
                loading="lazy"
            />
            <div class="split-content split-dark reveal-right">
                <div class="sec-tag">✅ Qualité garantie</div>
                <div class="sec-title">
                    Des pros vérifiés,<br />pas des inconnus
                </div>
                <p class="sec-sub">
                    Chaque prestataire est vérifié avant intervention.
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
                        ><span>Formation aux standards Mesotravo</span>
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
                <a v-if="false" class="btn btn-primary btn-lg" :href="routes.registerClient">
                    <span class="btn-icon">🚀</span> Commencez maintenant
                </a>
                <a class="btn btn-primary btn-lg" :href="routes.registerClient"
                    >🔍 Faire une demande →</a
                >
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         CONFIANCE
    ═══════════════════════════════════════════ -->
        <section class="sec" id="confiance">
            <div class="sec-tag reveal">🛡️ Pourquoi Mesotravo</div>
            <div class="sec-title reveal reveal-d1">
                La confiance au cœur<br />de chaque intervention
            </div>
            <div class="sec-sub reveal reveal-d2">
                Des profils contrôlés avant chaque mission.
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
                    Vous payez uniquement après validation des travaux.
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
                <a class="btn btn-primary btn-lg" :href="paymentCtaUrl">
                    <span class="btn-icon">🚀</span> Commencez maintenant
                </a>
                <a v-if="false" class="btn btn-primary btn-lg" :href="paymentCtaUrl"
                    >Commencer gratuitement →</a
                >
            </div>
            <img
                class="split-img payment-img reveal-right"
                :src="image3"
                alt="Paiement mobile sécurisé"
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
                Des retours simples et concrets.
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
                    <p>Suivez vos demandes sur mobile, bientôt disponible.</p>
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
                    <p class="sec-sub">Les réponses essentielles.</p>
                    <div class="faq-help">
                        💬 Une question ? Contactez-nous sur
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
                <h2>Prêt à trouver votre prestataire ? 🚀</h2>
                <p>Inscription gratuite, sans engagement.</p>
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
function shuffleServices(services) {
    return [...services].sort(() => Math.random() - 0.5);
}

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
                dashboard: "/dashboard",
            }),
        },
        isAuthenticated: {
            type: Boolean,
            default: false,
        },
        // Services injectés depuis la BDD via Blade (table services)
        // Chaque objet : { name, icon, desc }
        initialServices: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            /* ── Images (servies depuis public/images/) ── */
            heroImage: "/images/image1.png",
            image2: "/images/image2.png",
            image3: "/images/image3.png",

            /* ── UI ── */
            toastVisible: false,
            toastMsg: "",
            openFaq: null,
            showAllServices: false,
            visibleServicesCount: 8,

            /* ── Services (chargés depuis la BDD via prop) ── */
            services: shuffleServices(this.initialServices),

            /* ── Tags rapides (5 premiers services de la BDD, sinon fallback) ── */
            quickTags: this.initialServices.length
                ? this.initialServices
                      .slice(0, 8)
                      .map((s) => ({ icon: s.icon, name: s.name }))
                : [
                      { icon: "🚿", name: "Plomberie" },
                      { icon: "⚡", name: "Électricité" },
                      { icon: "❄️", name: "Climatisation" },
                      { icon: "🪚", name: "Menuiserie" },
                      { icon: "🧹", name: "Nettoyage" },
                      { icon: "🔩", name: "Mécanique" },
                      { icon: "🖌️", name: "Peinture" },
                      { icon: "🧱", name: "Maçonnerie" },
                  ],

            /* ── Étapes ── */
            steps: [
                {
                    icon: "📝",
                    title: "Décrivez votre besoin",
                    desc: "Indiquez le service et votre adresse en quelques secondes.",
                },
                {
                    icon: "🤖",
                    title: "Attribution automatique",
                    desc: "Le prestataire certifié le plus proche est attribué rapidement.",
                },
                {
                    icon: "📍",
                    title: "Suivi en temps réel",
                    desc: "Suivez l'arrivée du prestataire en temps réel.",
                },
                {
                    icon: "💸",
                    title: "Paiement sécurisé",
                    desc: "Validez la fin des travaux, puis payez.",
                },
            ],

            /* ── Confiance ── */
            trust: [
                {
                    icon: "✅",
                    title: "Prestataires certifiés",
                    desc: "Identité, diplômes et références vérifiés.",
                },
                {
                    icon: "🏅",
                    title: "Accréditation différenciée",
                    desc: "Seuls les profils validés accèdent aux missions.",
                },
                {
                    icon: "📍",
                    title: "Suivi GPS temps réel",
                    desc: "Position visible jusqu'à l'arrivée.",
                },
                {
                    icon: "💸",
                    title: "Paiement post-travaux",
                    desc: "Paiement après confirmation de fin des travaux.",
                },
                {
                    icon: "⭐",
                    title: "Système d'évaluation",
                    desc: "Les clients notent chaque intervention.",
                },
                {
                    icon: "🔄",
                    title: "Disponible 7j/7",
                    desc: "La plateforme reste accessible tous les jours.",
                },
                {
                    icon: "🛡️",
                    title: "Données protégées",
                    desc: "Vos données sont protégées.",
                },
                {
                    icon: "📞",
                    title: "Support réactif",
                    desc: "Une équipe disponible pour vous aider rapidement.",
                },
            ],

            /* ── Témoignages ── */
            testimonials: [
                {
                    name: "Adjovi Kokou",
                    role: "Particulier · Cotonou",
                    text: "Demande rapide, intervention propre et professionnelle.",
                },
                {
                    name: "Bertrand Houngbo",
                    role: "Chef de projet · Ouidah",
                    text: "Des prestataires sérieux et une attribution rapide.",
                },
                {
                    name: "Faridath ADEOSSI",
                    role: "Restauratrice · Porto-Novo",
                    text: "Un technicien est intervenu très vite. Service efficace.",
                },
                {
                    name: "Cossii ADEBOLA",
                    role: "Propriétaire · Calavi",
                    text: "Le paiement après travaux met vraiment en confiance.",
                },
                {
                    name: "Aïssatou Barry",
                    role: "DRH · Lokossa",
                    text: "Gain de temps et gestion simple de la maintenance.",
                },
                {
                    name: "Rodrigue Dossou",
                    role: "Particulier · Bohicon",
                    text: "Simple, rapide et fiable du début à la fin.",
                },
            ],

            /* ── FAQ ── */
            faqs: [
                {
                    icon: "👷",
                    q: "Comment Mesotravo sélectionne-t-il ses prestataires ?",
                    a: "Chaque prestataire est vérifié avant validation de son profil.",
                },
                {
                    icon: "⚡",
                    q: "Combien de temps faut-il pour trouver un prestataire ?",
                    a: "Le prestataire certifié le plus proche est attribué en quelques minutes.",
                },
                {
                    icon: "💸",
                    q: "Comment fonctionne le paiement ?",
                    a: "Vous payez après confirmation de fin des travaux, via MTN MoMo.",
                },
                {
                    icon: "😟",
                    q: "Que faire si je ne suis pas satisfait ?",
                    a: "Signalez le problème et notre équipe prend le relais rapidement.",
                },
                {
                    icon: "🗺️",
                    q: "Mesotravo est-il disponible hors de Cotonou ?",
                    a: "Oui, Mesotravo couvre Cotonou et plusieurs autres villes du Bénin.",
                },
                {
                    icon: "🚀",
                    q: "Comment devenir prestataire sur Mesotravo ?",
                    a: "Inscrivez-vous, envoyez vos documents et attendez la validation du profil.",
                },
                {
                    icon: "❤️",
                    q: "Puis-je demander un prestataire spécifique ?",
                    a: "Oui, vous pouvez solliciter à nouveau un prestataire déjà utilisé.",
                },
            ],
        };
    },

    computed: {
        displayedServices() {
            if (this.showAllServices) return this.services;
            return this.services.slice(0, this.visibleServicesCount);
        },

        hasMoreServices() {
            return (
                !this.showAllServices &&
                this.services.length > this.visibleServicesCount
            );
        },

        paymentCtaUrl() {
            return this.isAuthenticated
                ? this.routes.dashboard
                : this.routes.registerClient;
        },
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
                `🔍 Recherche : "${this.searchService}" — ${
                    this.searchLocation || "partout au Bénin"
                }`
            );
        },

        selectService(name) {
            this.showToast(`✅ Service sélectionné : ${name}`);
        },

        /* ── Toast ── */
        showMoreServices() {
            this.showAllServices = true;
            this.$nextTick(() => this.reObserveReveal());
        },

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
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed), .reveal-left:not(.revealed), .reveal-right:not(.revealed)"
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
                    { threshold: 0.12 }
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
                { threshold: 0.5 }
            );
            document
                .querySelectorAll(".sbi-num")
                .forEach((el) => io.observe(el));
        },
    },
};
</script>

<style>
/* ── Suppression du trait sous "qualifié & certifié" ── */
.hl {
    text-decoration: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    background-image: none !important;
    -webkit-text-decoration: none !important;
}
.hl::after,
.hl::before {
    display: none !important;
}

/* ── Dézoomer l'image hero ── */
.hero-img {
    object-fit: cover !important;
    object-position: center 50% !important;
    transform: scale(0.95) !important;
    transform-origin: center center !important;
}

.services-more {
    display: flex;
    justify-content: center;
    margin-top: 22px;
}

.services-more-btn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #c2410c;
    padding: 9px 18px;
    font-size: 0.9rem;
}

.services-more-btn:hover {
    background: #ffedd5;
    color: #9a3412;
}

.payment-img {
    align-self: stretch;
    justify-self: stretch;
    width: 100% !important;
    height: 100% !important;
    min-height: 430px;
    max-height: none;
    object-fit: cover;
    object-position: center;
}

.payment-split {
    align-items: stretch;
    overflow: hidden;
}

.btn-icon {
    display: inline-block;
    margin-right: 6px;
}

/* ═══════════════════════════════════════════════════════
   RESPONSIVE MOBILE — max-width: 768px
═══════════════════════════════════════════════════════ */
@media (max-width: 768px) {
    /* ── Base : empêcher tout débordement horizontal ── */
    * {
        box-sizing: border-box;
    }
    html,
    body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    /* ── HERO ── */
    .hero {
        flex-direction: column !important;
        grid-template-columns: 1fr !important;
        padding: 60px 16px 32px !important;
        min-height: unset !important;
        text-align: center;
    }
    .hero-inner {
        max-width: 100% !important;
        width: 100% !important;
        align-items: center !important;
        display: flex;
        flex-direction: column;
    }
    .hero-visual {
        display: none !important;
    }
    .hero h1,
    .au1 {
        font-size: clamp(1.6rem, 7vw, 2.4rem) !important;
        line-height: 1.25 !important;
    }
    .hero-desc,
    .au2 {
        font-size: 0.92rem !important;
        padding: 0 4px;
    }
    .hero-btns,
    .au3 {
        flex-direction: column !important;
        gap: 10px !important;
        width: 100% !important;
        align-items: stretch !important;
    }
    .hero-btns .btn {
        width: 100% !important;
        text-align: center !important;
        font-size: 0.95rem !important;
    }
    .hero-stats,
    .au4 {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
        width: 100% !important;
    }
    .hstat {
        flex-direction: column !important;
        text-align: center !important;
        padding: 10px 6px !important;
    }
    .hstat .num {
        font-size: 1.3rem !important;
    }
    .hstat .lbl {
        font-size: 0.72rem !important;
    }
    .hero-badge {
        font-size: 0.78rem !important;
        padding: 6px 12px !important;
    }

    /* ── RECHERCHE RAPIDE ── */
    .search-wrap {
        padding: 0 12px !important;
    }
    .search-box {
        padding: 18px 14px !important;
        border-radius: 14px !important;
    }
    .search-head {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }
    .search-head h3 {
        font-size: 1rem !important;
    }
    .search-head span {
        font-size: 0.78rem !important;
    }
    .search-row {
        flex-direction: column !important;
        gap: 10px !important;
    }
    .search-input {
        width: 100% !important;
        font-size: 0.88rem !important;
    }
    .search-row .btn {
        width: 100% !important;
        text-align: center !important;
    }
    .search-tags {
        gap: 6px !important;
        flex-wrap: wrap !important;
    }
    .stag {
        font-size: 0.78rem !important;
        padding: 5px 10px !important;
    }

    /* ── STATS BAND ── */
    .stats-band {
        padding: 20px 12px !important;
    }
    .stats-inner {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
    }
    .sbi {
        gap: 8px !important;
    }
    .sbi-icon {
        font-size: 1.4rem !important;
    }
    .sbi-num {
        font-size: 1.2rem !important;
    }
    .sbi-lbl {
        font-size: 0.72rem !important;
    }

    /* ── SECTIONS communes ── */
    .sec {
        padding: 40px 16px !important;
    }
    .sec-title {
        font-size: clamp(1.3rem, 6vw, 2rem) !important;
        line-height: 1.3 !important;
    }
    .sec-sub {
        font-size: 0.88rem !important;
    }

    /* ── SERVICES GRID ── */
    .services-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    .scard {
        padding: 16px 10px !important;
    }
    .scard-icon {
        font-size: 1.6rem !important;
    }
    .scard h4 {
        font-size: 0.85rem !important;
    }
    .scard p {
        font-size: 0.75rem !important;
    }

    /* ── STEPS (Comment ça marche) ── */
    .steps-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
    .step {
        padding: 18px 14px !important;
        text-align: center;
    }
    .step h4 {
        font-size: 0.95rem !important;
    }
    .step p {
        font-size: 0.82rem !important;
    }

    /* ── SPLIT SECTIONS ── */
    .split {
        flex-direction: column !important;
        grid-template-columns: 1fr !important;
    }
    .split-img {
        width: 100% !important;
        height: 220px !important;
        object-fit: cover !important;
        object-position: top !important;
    }
    .payment-img {
        width: 100% !important;
        height: 240px !important;
        min-height: 240px !important;
        max-height: 240px !important;
        object-fit: cover !important;
        object-position: center !important;
    }
    .split-content {
        padding: 28px 16px !important;
        width: 100% !important;
    }
    .split-content .sec-title {
        font-size: 1.4rem !important;
    }
    .checklist {
        padding-left: 0 !important;
    }
    .checklist li {
        font-size: 0.85rem !important;
        gap: 8px !important;
    }
    .split-content .btn {
        width: 100% !important;
        text-align: center !important;
    }

    /* ── TRUST GRID ── */
    .trust-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    .tcard {
        padding: 16px 10px !important;
    }
    .tcard-ico {
        font-size: 1.5rem !important;
    }
    .tcard h4 {
        font-size: 0.85rem !important;
    }
    .tcard p {
        font-size: 0.75rem !important;
    }

    /* ── TÉMOIGNAGES ── */
    .test-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
    .testcard {
        padding: 18px 14px !important;
    }
    .t-text {
        font-size: 0.85rem !important;
    }

    /* ── APP BANNER ── */
    .app-banner {
        padding: 28px 16px !important;
    }
    .app-banner-inner {
        flex-direction: column !important;
        gap: 18px !important;
        text-align: center;
        align-items: center !important;
    }
    .app-banner-inner h3 {
        font-size: 1.1rem !important;
    }
    .app-banner-inner p {
        font-size: 0.85rem !important;
    }
    .app-badges {
        flex-direction: row !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
    }
    .abadge {
        padding: 10px 14px !important;
        font-size: 0.85rem !important;
    }

    /* ── FAQ ── */
    .faq-layout {
        flex-direction: column !important;
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }
    .faq-left {
        width: 100% !important;
    }
    .faq-list {
        width: 100% !important;
    }
    .faq-q {
        font-size: 0.88rem !important;
        padding: 14px 12px !important;
    }
    .faq-a {
        font-size: 0.83rem !important;
        padding: 0 12px !important;
    }

    /* ── PARTENAIRES ── */
    .partners {
        padding: 20px 12px !important;
    }
    .prow {
        flex-wrap: wrap !important;
        gap: 8px !important;
        justify-content: center !important;
    }
    .pname {
        font-size: 0.8rem !important;
        padding: 6px 10px !important;
    }

    /* ── CTA FINAL ── */
    .cta-final {
        padding: 40px 16px !important;
        text-align: center !important;
    }
    .cta-inner h2 {
        font-size: 1.4rem !important;
    }
    .cta-inner p {
        font-size: 0.88rem !important;
    }
    .cta-btns {
        flex-direction: column !important;
        gap: 10px !important;
        align-items: stretch !important;
    }
    .cta-btns .btn {
        width: 100% !important;
        text-align: center !important;
    }

    /* ── TOAST ── */
    .toast {
        left: 12px !important;
        right: 12px !important;
        bottom: 16px !important;
        font-size: 0.85rem !important;
        text-align: center !important;
    }

    /* ── BOUTONS globaux ── */
    .btn-lg {
        padding: 12px 18px !important;
        font-size: 0.9rem !important;
    }
}

/* ═══════════════════════════════════════════════════════
   TRÈS PETIT ÉCRAN — max-width: 400px
═══════════════════════════════════════════════════════ */
@media (max-width: 400px) {
    .hero h1,
    .au1 {
        font-size: 1.45rem !important;
    }
    .services-grid {
        grid-template-columns: 1fr !important;
    }
    .trust-grid {
        grid-template-columns: 1fr !important;
    }
    .hero-stats,
    .au4 {
        grid-template-columns: 1fr 1fr !important;
    }
}
</style>
