<template>
    <div class="market-page">
        <!-- ═══════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════ -->
        <section class="mk-hero">
            <div class="mk-hero-glow"></div>
            <div class="mk-hero-glow2"></div>
            <div class="mk-hero-dots"></div>

            <div class="mk-hero-inner">
                <div class="mk-badge au">
                    <span class="bdot"></span>
                    📋 Entreprises & Prestataires · Validation admin avant
                    publication
                </div>
                <h1 class="au1">
                    Appels d'Offres<br />
                    <span class="hl">trouvez la mission</span><br />
                    qu'il vous faut
                </h1>
                <p class="mk-hero-desc au2">
                    Les entreprises publient leurs besoins. Les prestataires et
                    Talents y répondent.<br />
                    Chaque offre est validée par l'équipe Resotravo avant
                    publication.
                </p>
                <div class="mk-hero-btns au3">
                    <button
                        class="btn btn-primary btn-lg"
                        @click="activeTab = 'list'"
                    >
                        🔍 Voir les appels d'offres
                    </button>
                    <button
                        class="btn btn-ghost btn-lg"
                        @click="activeTab = 'publish'"
                    >
                        📝 Publier un appel d'offres
                    </button>
                </div>
                <div class="mk-hero-stats au4">
                    <div class="hstat">
                        <span class="num">{{ stats.active }}</span
                        ><span class="lbl">📋 AO actifs</span>
                    </div>
                    <div class="hstat">
                        <span class="num">{{ stats.domains }}</span
                        ><span class="lbl">🔧 Domaines couverts</span>
                    </div>
                    <div class="hstat">
                        <span class="num">{{ stats.companies }}</span
                        ><span class="lbl">🏢 Entreprises</span>
                    </div>
                    <div class="hstat">
                        <span class="num">{{ stats.talents }}</span
                        ><span class="lbl">⭐ Talents disponibles</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         TABS
    ═══════════════════════════════════════════ -->
        <div class="mk-tabs-bar">
            <div class="mk-tabs">
                <button
                    class="mk-tab"
                    :class="{ active: activeTab === 'list' }"
                    @click="activeTab = 'list'"
                >
                    📋 Appels d'offres
                </button>
                <button
                    class="mk-tab"
                    :class="{ active: activeTab === 'publish' }"
                    @click="activeTab = 'publish'"
                >
                    📝 Publier un AO
                </button>
                <button
                    class="mk-tab"
                    :class="{ active: activeTab === 'myCandidatures' }"
                    @click="activeTab = 'myCandidatures'"
                >
                    📁 Mes candidatures
                </button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         TAB 1 — LISTE DES AO
    ═══════════════════════════════════════════ -->
        <section class="mk-section" v-show="activeTab === 'list'">
            <div class="mk-filters reveal">
                <div class="mk-filter-row">
                    <input
                        class="search-input"
                        type="text"
                        v-model="searchQuery"
                        placeholder="🔍 Rechercher un appel d'offres…"
                    />
                    <select class="mk-select" v-model="filterDomain">
                        <option value="">🔧 Tous les domaines</option>
                        <option v-for="d in domains" :key="d" :value="d">
                            {{ d }}
                        </option>
                    </select>
                    <select class="mk-select" v-model="filterBudget">
                        <option value="">💰 Tous les budgets</option>
                        <option value="low">Moins de 100 000 FCFA</option>
                        <option value="mid">100 000 – 500 000 FCFA</option>
                        <option value="high">Plus de 500 000 FCFA</option>
                    </select>
                    <select class="mk-select" v-model="filterLocation">
                        <option value="">📍 Toutes les villes</option>
                        <option v-for="v in villes" :key="v" :value="v">
                            {{ v }}
                        </option>
                    </select>
                </div>
                <div class="mk-filter-tags">
                    <span
                        class="mk-ftag"
                        v-for="tag in activeTags"
                        :key="tag"
                        :class="{ active: filterTag === tag }"
                        @click="filterTag = filterTag === tag ? '' : tag"
                    >
                        {{ tag }}
                    </span>
                </div>
            </div>

            <!-- Résultats -->
            <div class="mk-results-header">
                <span class="mk-count"
                    >{{ filteredAOs.length }} appel(s) d'offres trouvé(s)</span
                >
                <select class="mk-select mk-sort" v-model="sortBy">
                    <option value="date">Trier par date</option>
                    <option value="budget">Trier par budget</option>
                    <option value="candidatures">Trier par candidatures</option>
                </select>
            </div>

            <div class="mk-ao-grid">
                <div
                    class="mk-ao-card reveal"
                    :class="'reveal-d' + ((i % 4) + 1)"
                    v-for="(ao, i) in filteredAOs"
                    :key="ao.id"
                    @click="openAO(ao)"
                >
                    <div class="mk-ao-header">
                        <div class="mk-ao-domain-badge">
                            {{ ao.domainIcon }} {{ ao.domain }}
                        </div>
                        <div class="mk-ao-urgency" :class="ao.urgency">
                            {{ ao.urgencyLabel }}
                        </div>
                    </div>
                    <h3 class="mk-ao-title">{{ ao.title }}</h3>
                    <p class="mk-ao-desc">{{ ao.desc }}</p>
                    <div class="mk-ao-meta">
                        <span>📍 {{ ao.location }}</span>
                        <span>⏱️ {{ ao.duration }}</span>
                        <span>💰 {{ ao.budget }}</span>
                    </div>
                    <div class="mk-ao-footer">
                        <div class="mk-ao-company">
                            <div class="mk-ao-company-av">
                                {{ ao.company[0] }}
                            </div>
                            <div>
                                <div class="mk-ao-company-name">
                                    {{ ao.company }}
                                </div>
                                <div class="mk-ao-deadline">
                                    📅 Limite : {{ ao.deadline }}
                                </div>
                            </div>
                        </div>
                        <div class="mk-ao-cands">
                            <span>{{ ao.candidatures }}</span>
                            <span class="mk-ao-cands-lbl">candidature(s)</span>
                        </div>
                    </div>
                    <button class="mk-ao-btn" @click.stop="postuler(ao)">
                        Postuler →
                    </button>
                </div>
            </div>

            <div class="mk-empty" v-if="filteredAOs.length === 0">
                <div class="mk-empty-icon">🔍</div>
                <div class="mk-empty-title">Aucun résultat</div>
                <div class="mk-empty-desc">
                    Essayez d'autres filtres ou revenez plus tard.
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         TAB 2 — PUBLIER UN AO
    ═══════════════════════════════════════════ -->
        <section class="mk-section" v-show="activeTab === 'publish'">
            <div class="mk-publish-layout">
                <div class="mk-publish-form reveal">
                    <div class="mk-form-header">
                        <h2>📝 Publier un appel d'offres</h2>
                        <p>
                            Votre publication sera validée par l'équipe
                            Resotravo avant mise en ligne.
                        </p>
                    </div>

                    <div class="mk-form-grid">
                        <div class="mk-field mk-field-full">
                            <label class="mk-label"
                                >Titre de la mission
                                <span class="mk-req">*</span></label
                            >
                            <input
                                class="mk-input"
                                type="text"
                                v-model="form.title"
                                placeholder="Ex : Rénovation électrique complète d'un immeuble R+3"
                            />
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Domaine requis
                                <span class="mk-req">*</span></label
                            >
                            <select class="mk-input" v-model="form.domain">
                                <option value="">Sélectionner…</option>
                                <option
                                    v-for="d in domains"
                                    :key="d"
                                    :value="d"
                                >
                                    {{ d }}
                                </option>
                            </select>
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Localisation
                                <span class="mk-req">*</span></label
                            >
                            <select class="mk-input" v-model="form.location">
                                <option value="">
                                    Sélectionner une ville…
                                </option>
                                <option v-for="v in villes" :key="v" :value="v">
                                    {{ v }}
                                </option>
                            </select>
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Durée estimée
                                <span class="mk-req">*</span></label
                            >
                            <input
                                class="mk-input"
                                type="text"
                                v-model="form.duration"
                                placeholder="Ex : 2 semaines, 1 mois…"
                            />
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Budget indicatif (FCFA)</label
                            >
                            <input
                                class="mk-input"
                                type="text"
                                v-model="form.budget"
                                placeholder="Ex : 250 000 FCFA"
                            />
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Date limite de candidature
                                <span class="mk-req">*</span></label
                            >
                            <input
                                class="mk-input"
                                type="date"
                                v-model="form.deadline"
                            />
                        </div>

                        <div class="mk-field">
                            <label class="mk-label"
                                >Type de profil recherché
                                <span class="mk-req">*</span></label
                            >
                            <select class="mk-input" v-model="form.profileType">
                                <option value="">Sélectionner…</option>
                                <option value="prestataire">
                                    Prestataire certifié
                                </option>
                                <option value="talent">
                                    Talent (BAC+3 min.)
                                </option>
                                <option value="both">Les deux</option>
                            </select>
                        </div>

                        <div class="mk-field mk-field-full">
                            <label class="mk-label"
                                >Description de la mission
                                <span class="mk-req">*</span></label
                            >
                            <textarea
                                class="mk-input mk-textarea"
                                v-model="form.description"
                                placeholder="Décrivez le contexte, les travaux à réaliser, les contraintes particulières…"
                                rows="5"
                            ></textarea>
                        </div>

                        <div class="mk-field mk-field-full">
                            <label class="mk-label"
                                >Exigences particulières</label
                            >
                            <textarea
                                class="mk-input mk-textarea"
                                v-model="form.requirements"
                                placeholder="Certifications requises, matériel fourni ou non, conditions d'accès…"
                                rows="3"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mk-form-notice">
                        ⚠️ Votre appel d'offres sera soumis à validation par
                        l'administrateur Resotravo avant publication. Délai
                        habituel : 24h.
                    </div>

                    <div class="mk-form-actions">
                        <button class="btn btn-outline" @click="resetForm">
                            Annuler
                        </button>
                        <button
                            class="btn btn-primary btn-lg"
                            :disabled="!formValid"
                            @click="submitForm"
                        >
                            {{
                                formSubmitted
                                    ? "✅ Soumis — En attente de validation"
                                    : "Soumettre l'appel d'offres →"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Infos / bonne pratiques -->
                <div class="mk-publish-tips reveal reveal-right">
                    <div class="mk-tips-card">
                        <h4>💡 Conseils pour un bon AO</h4>
                        <ul class="mk-tips-list">
                            <li v-for="tip in publishTips" :key="tip.text">
                                <span class="mk-tip-icon">{{ tip.icon }}</span>
                                <span>{{ tip.text }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mk-process-card">
                        <h4>🔄 Processus de publication</h4>
                        <div
                            class="mk-process-step"
                            v-for="(s, i) in processSteps"
                            :key="i"
                        >
                            <div class="mk-proc-num">{{ i + 1 }}</div>
                            <div>
                                <div class="mk-proc-title">{{ s.title }}</div>
                                <div class="mk-proc-desc">{{ s.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         TAB 3 — MES CANDIDATURES
    ═══════════════════════════════════════════ -->
        <section class="mk-section" v-show="activeTab === 'myCandidatures'">
            <div class="mk-cands-header reveal">
                <h2>📁 Mes candidatures</h2>
                <p>Suivez l'état de vos candidatures aux appels d'offres.</p>
            </div>
            <div class="mk-cands-list">
                <div
                    class="mk-cand-item reveal"
                    :class="'reveal-d' + ((i % 3) + 1)"
                    v-for="(c, i) in myCandidatures"
                    :key="i"
                >
                    <div class="mk-cand-left">
                        <div class="mk-cand-domain">{{ c.domainIcon }}</div>
                        <div>
                            <div class="mk-cand-title">{{ c.title }}</div>
                            <div class="mk-cand-meta">
                                {{ c.company }} · {{ c.location }}
                            </div>
                        </div>
                    </div>
                    <div class="mk-cand-right">
                        <div class="mk-cand-date">Soumis le {{ c.date }}</div>
                        <div class="mk-cand-status" :class="c.statusClass">
                            {{ c.statusIcon }} {{ c.status }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="mk-empty" v-if="myCandidatures.length === 0">
                <div class="mk-empty-icon">📁</div>
                <div class="mk-empty-title">Aucune candidature</div>
                <div class="mk-empty-desc">
                    Parcourez les appels d'offres et postulez !
                </div>
                <button class="btn btn-primary" @click="activeTab = 'list'">
                    Voir les AO →
                </button>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════
         MODAL CANDIDATURE
    ═══════════════════════════════════════════ -->
        <div
            class="mk-modal-overlay"
            v-if="showModal"
            @click.self="showModal = false"
        >
            <div class="mk-modal">
                <div class="mk-modal-header">
                    <h3>📩 Postuler à cet appel d'offres</h3>
                    <button class="mk-modal-close" @click="showModal = false">
                        ✕
                    </button>
                </div>
                <div class="mk-modal-body" v-if="selectedAO">
                    <div class="mk-modal-ao-info">
                        <strong>{{ selectedAO.title }}</strong>
                        <span
                            >{{ selectedAO.company }} ·
                            {{ selectedAO.location }}</span
                        >
                    </div>
                    <div class="mk-field">
                        <label class="mk-label"
                            >Lettre de motivation
                            <span class="mk-req">*</span></label
                        >
                        <textarea
                            class="mk-input mk-textarea"
                            v-model="candidature.motivation"
                            placeholder="Présentez votre expérience, vos compétences et pourquoi vous êtes le profil idéal…"
                            rows="5"
                        ></textarea>
                    </div>
                    <div class="mk-field">
                        <label class="mk-label">Tarif proposé (FCFA)</label>
                        <input
                            class="mk-input"
                            type="text"
                            v-model="candidature.tarif"
                            placeholder="Ex : 180 000 FCFA"
                        />
                    </div>
                    <div class="mk-field">
                        <label class="mk-label">Disponibilité</label>
                        <input
                            class="mk-input"
                            type="text"
                            v-model="candidature.disponibilite"
                            placeholder="Ex : Disponible à partir du 20 mars 2026"
                        />
                    </div>
                </div>
                <div class="mk-modal-footer">
                    <button class="btn btn-outline" @click="showModal = false">
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary btn-lg"
                        :disabled="!candidature.motivation.trim()"
                        @click="submitCandidature"
                    >
                        {{
                            candidatureSubmitted
                                ? "✅ Candidature envoyée !"
                                : "Envoyer ma candidature →"
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
         CTA
    ═══════════════════════════════════════════ -->
        <div class="cta-final">
            <div class="cta-inner reveal">
                <h2>Vous êtes une entreprise ? 🏢</h2>
                <p>
                    Publiez vos appels d'offres et trouvez les meilleurs
                    prestataires et Talents du Bénin.
                </p>
                <div class="cta-btns">
                    <a class="btn btn-dark btn-lg" :href="routes.register"
                        >Créer un compte entreprise →</a
                    >
                    <a class="btn btn-ghost btn-lg" :href="routes.login"
                        >Se connecter</a
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MarketComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                register: "/register",
                login: "/login",
            }),
        },
    },

    data() {
        return {
            activeTab: "list",
            searchQuery: "",
            filterDomain: "",
            filterBudget: "",
            filterLocation: "",
            filterTag: "",
            sortBy: "date",
            showModal: false,
            selectedAO: null,
            formSubmitted: false,
            candidatureSubmitted: false,

            stats: {
                active: "24",
                domains: "12",
                companies: "38",
                talents: "120+",
            },

            domains: [
                "Électricité",
                "Plomberie",
                "Menuiserie",
                "Climatisation",
                "Maçonnerie",
                "Peinture",
                "Informatique",
                "Génie Civil",
                "Nettoyage industriel",
                "Maintenance",
                "Serrurerie",
                "Autre",
            ],

            villes: [
                "Cotonou",
                "Porto-Novo",
                "Parakou",
                "Abomey-Calavi",
                "Bohicon",
                "Natitingou",
                "Ouidah",
                "Lokossa",
                "Djougou",
            ],

            activeTags: [
                "Urgent",
                "Nouveau",
                "Talent requis",
                "En cours",
                "Télétravail possible",
            ],

            form: {
                title: "",
                domain: "",
                location: "",
                duration: "",
                budget: "",
                deadline: "",
                profileType: "",
                description: "",
                requirements: "",
            },

            candidature: { motivation: "", tarif: "", disponibilite: "" },

            publishTips: [
                {
                    icon: "📝",
                    text: "Soyez précis dans le titre : indiquez le domaine et l'ampleur du projet.",
                },
                {
                    icon: "📍",
                    text: "Précisez la ville et les conditions d'accès si nécessaire.",
                },
                {
                    icon: "💰",
                    text: "Un budget indicatif attire plus de candidatures qualifiées.",
                },
                {
                    icon: "📅",
                    text: "Donnez un délai de candidature raisonnable (minimum 5 jours).",
                },
                {
                    icon: "🎯",
                    text: "Listez clairement les compétences et certifications requises.",
                },
            ],

            processSteps: [
                {
                    title: "Remplir le formulaire",
                    desc: "Tous les champs obligatoires doivent être complétés.",
                },
                {
                    title: "Soumission admin",
                    desc: "L'équipe Resotravo examine votre offre sous 24h.",
                },
                {
                    title: "Mise en ligne",
                    desc: "Votre AO est visible par tous les prestataires et Talents.",
                },
                {
                    title: "Réception des candidatures",
                    desc: "Consultez et échangez via la messagerie intégrée.",
                },
            ],

            appelsOffres: [
                {
                    id: 1,
                    domainIcon: "⚡",
                    domain: "Électricité",
                    title: "Rénovation électrique complète — Immeuble R+4 Cotonou",
                    desc: "Mise aux normes complète de l'installation électrique d'un immeuble de 4 étages, remplacement du tableau général, câblage neuf.",
                    location: "Cotonou",
                    duration: "3 semaines",
                    budget: "650 000 FCFA",
                    deadline: "25 mars 2026",
                    company: "SOTRAC Immobilier",
                    candidatures: 7,
                    urgency: "urgent",
                    urgencyLabel: "🔴 Urgent",
                    tags: ["Urgent"],
                    profileType: "prestataire",
                },
                {
                    id: 2,
                    domainIcon: "🏗️",
                    domain: "Génie Civil",
                    title: "Construction d'un entrepôt 500m² — Zone industrielle Calavi",
                    desc: "Construction d'un entrepôt de stockage de 500m², dalle béton, charpente métallique, toiture, portails coulissants.",
                    location: "Abomey-Calavi",
                    duration: "2 mois",
                    budget: "4 500 000 FCFA",
                    deadline: "1 avril 2026",
                    company: "LogisBénin SARL",
                    candidatures: 12,
                    urgency: "normal",
                    urgencyLabel: "🟢 Ouvert",
                    tags: ["Talent requis"],
                    profileType: "talent",
                },
                {
                    id: 3,
                    domainIcon: "❄️",
                    domain: "Climatisation",
                    title: "Installation climatisation — Bureaux open space 200m²",
                    desc: "Fourniture et installation d'un système de climatisation centralisé pour un plateau de bureaux de 200m² avec 30 postes de travail.",
                    location: "Cotonou",
                    duration: "1 semaine",
                    budget: "380 000 FCFA",
                    deadline: "20 mars 2026",
                    company: "Groupe ATLAS BJ",
                    candidatures: 4,
                    urgency: "new",
                    urgencyLabel: "🔵 Nouveau",
                    tags: ["Nouveau"],
                    profileType: "prestataire",
                },
                {
                    id: 4,
                    domainIcon: "🖥️",
                    domain: "Informatique",
                    title: "Développement application mobile — Gestion de stock",
                    desc: "Conception et développement d'une application mobile Android/iOS pour la gestion de stock en temps réel, avec module de reporting.",
                    location: "Cotonou",
                    duration: "6 semaines",
                    budget: "1 200 000 FCFA",
                    deadline: "10 avril 2026",
                    company: "Commerce Express BJ",
                    candidatures: 9,
                    urgency: "normal",
                    urgencyLabel: "🟢 Ouvert",
                    tags: ["Talent requis", "Télétravail possible"],
                    profileType: "talent",
                },
                {
                    id: 5,
                    domainIcon: "🧹",
                    domain: "Nettoyage industriel",
                    title: "Nettoyage industriel mensuel — Usine agroalimentaire",
                    desc: "Prestation mensuelle de nettoyage industriel profond d'une usine agroalimentaire (halls de production, zones de stockage, vestiaires).",
                    location: "Parakou",
                    duration: "1 jour / mois",
                    budget: "95 000 FCFA/mois",
                    deadline: "31 mars 2026",
                    company: "AgroNord SA",
                    candidatures: 3,
                    urgency: "normal",
                    urgencyLabel: "🟢 Ouvert",
                    tags: [],
                    profileType: "prestataire",
                },
                {
                    id: 6,
                    domainIcon: "🎨",
                    domain: "Peinture",
                    title: "Peinture intérieure & extérieure — Résidence 12 appartements",
                    desc: "Travaux de peinture complète (apprêt + 2 couches) pour 12 appartements neufs + façades extérieures. Peinture fournie par le client.",
                    location: "Porto-Novo",
                    duration: "10 jours",
                    budget: "280 000 FCFA",
                    deadline: "28 mars 2026",
                    company: "Résidence Les Palmiers",
                    candidatures: 6,
                    urgency: "urgent",
                    urgencyLabel: "🔴 Urgent",
                    tags: ["Urgent"],
                    profileType: "prestataire",
                },
            ],

            myCandidatures: [
                {
                    domainIcon: "⚡",
                    title: "Rénovation électrique — Immeuble R+4",
                    company: "SOTRAC Immobilier",
                    location: "Cotonou",
                    date: "14 mars 2026",
                    status: "En cours d'examen",
                    statusIcon: "🔄",
                    statusClass: "pending",
                },
                {
                    domainIcon: "🎨",
                    title: "Peinture intérieure 12 appartements",
                    company: "Résidence Les Palmiers",
                    location: "Porto-Novo",
                    date: "10 mars 2026",
                    status: "Sélectionné",
                    statusIcon: "✅",
                    statusClass: "selected",
                },
            ],
        };
    },

    computed: {
        filteredAOs() {
            let list = this.appelsOffres;
            if (this.searchQuery)
                list = list.filter(
                    (a) =>
                        a.title
                            .toLowerCase()
                            .includes(this.searchQuery.toLowerCase()) ||
                        a.desc
                            .toLowerCase()
                            .includes(this.searchQuery.toLowerCase()),
                );
            if (this.filterDomain)
                list = list.filter((a) => a.domain === this.filterDomain);
            if (this.filterLocation)
                list = list.filter((a) => a.location === this.filterLocation);
            if (this.filterTag)
                list = list.filter((a) => a.tags.includes(this.filterTag));
            if (this.filterBudget === "low")
                list = list.filter(
                    (a) => parseInt(a.budget.replace(/\D/g, "")) < 100000,
                );
            if (this.filterBudget === "mid")
                list = list.filter((a) => {
                    const v = parseInt(a.budget.replace(/\D/g, ""));
                    return v >= 100000 && v <= 500000;
                });
            if (this.filterBudget === "high")
                list = list.filter(
                    (a) => parseInt(a.budget.replace(/\D/g, "")) > 500000,
                );
            if (this.sortBy === "candidatures")
                list = [...list].sort(
                    (a, b) => b.candidatures - a.candidatures,
                );
            return list;
        },

        formValid() {
            return (
                this.form.title &&
                this.form.domain &&
                this.form.location &&
                this.form.duration &&
                this.form.deadline &&
                this.form.profileType &&
                this.form.description
            );
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.reObserveReveal();
        });
    },

    methods: {
        openAO(ao) {
            this.selectedAO = ao;
            this.showModal = true;
            this.candidatureSubmitted = false;
            this.candidature = { motivation: "", tarif: "", disponibilite: "" };
        },

        postuler(ao) {
            this.selectedAO = ao;
            this.showModal = true;
            this.candidatureSubmitted = false;
            this.candidature = { motivation: "", tarif: "", disponibilite: "" };
        },

        submitCandidature() {
            if (!this.candidature.motivation.trim()) return;
            this.candidatureSubmitted = true;
            this.myCandidatures.unshift({
                domainIcon: this.selectedAO.domainIcon,
                title: this.selectedAO.title,
                company: this.selectedAO.company,
                location: this.selectedAO.location,
                date: new Date().toLocaleDateString("fr-FR"),
                status: "En cours d'examen",
                statusIcon: "🔄",
                statusClass: "pending",
            });
            setTimeout(() => {
                this.showModal = false;
            }, 2000);
        },

        submitForm() {
            if (!this.formValid) return;
            this.formSubmitted = true;
        },

        resetForm() {
            this.form = {
                title: "",
                domain: "",
                location: "",
                duration: "",
                budget: "",
                deadline: "",
                profileType: "",
                description: "",
                requirements: "",
            };
            this.formSubmitted = false;
        },

        reObserveReveal() {
            if (!("IntersectionObserver" in window)) return;
            setTimeout(() => {
                const io = new IntersectionObserver(
                    (entries) =>
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add("revealed");
                                io.unobserve(e.target);
                            }
                        }),
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" },
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)",
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },
    },
};
</script>

<style scoped>
/* ── HERO MARCHÉ ── */
.mk-hero {
    background: var(--dk2);
    color: #fff;
    padding: 52px 4% 44px;
    position: relative;
    overflow: hidden;
}
.mk-hero-glow {
    position: absolute;
    top: -200px;
    right: -150px;
    width: 550px;
    height: 550px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(249, 115, 22, 0.13) 0%,
        transparent 68%
    );
    pointer-events: none;
}
.mk-hero-glow2 {
    position: absolute;
    bottom: -100px;
    left: -80px;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(252, 211, 77, 0.05) 0%,
        transparent 70%
    );
    pointer-events: none;
}
.mk-hero-dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(
        rgba(255, 255, 255, 0.035) 1px,
        transparent 1px
    );
    background-size: 24px 24px;
    pointer-events: none;
}
.mk-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 760px;
}

.mk-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(249, 115, 22, 0.15);
    border: 1px solid rgba(249, 115, 22, 0.3);
    color: var(--am);
    padding: 6px 15px;
    border-radius: 99px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
}
.bdot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    animation: blink 1.4s infinite;
    flex-shrink: 0;
}
.mk-hero h1 {
    font-size: clamp(30px, 6vw, 54px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}
.mk-hero-desc {
    font-size: 16px;
    color: #b8ada7;
    line-height: 1.75;
    margin-bottom: 26px;
    font-weight: 400;
}
.mk-hero-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 34px;
}
.mk-hero-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 480px) {
    .mk-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 26px;
    }
}

/* ── TABS ── */
.mk-tabs-bar {
    background: var(--wh);
    border-bottom: 1px solid var(--grl);
    position: sticky;
    top: 64px;
    z-index: 100;
    padding: 0 4%;
}
.mk-tabs {
    display: flex;
    gap: 0;
    overflow-x: auto;
}
.mk-tab {
    padding: 14px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s;
}
.mk-tab:hover {
    color: var(--dk);
}
.mk-tab.active {
    color: var(--or);
    border-bottom-color: var(--or);
}

/* ── SECTION ── */
.mk-section {
    padding: 36px 4% 52px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ── FILTRES ── */
.mk-filters {
    background: var(--wh);
    border-radius: 14px;
    padding: 20px;
    border: 1.5px solid var(--grl);
    margin-bottom: 24px;
}
.mk-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}
.mk-select {
    padding: 10px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    outline: none;
    background: var(--cr);
    color: var(--dk);
    cursor: pointer;
    transition: border-color 0.2s;
}
.mk-select:focus {
    border-color: var(--or);
}
.mk-filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}
.mk-ftag {
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    background: var(--cr2);
    border: 1px solid var(--grl);
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
}
.mk-ftag:hover,
.mk-ftag.active {
    background: var(--or);
    color: #fff;
    border-color: var(--or);
}
.mk-results-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.mk-count {
    font-size: 14px;
    font-weight: 600;
    color: var(--gr);
}
.mk-sort {
    font-size: 13px;
}

/* ── AO GRID ── */
.mk-ao-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}
.mk-ao-card {
    background: var(--wh);
    border-radius: 16px;
    padding: 22px 20px;
    border: 1.5px solid var(--grl);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.22, 0.68, 0, 1.2);
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    overflow: hidden;
}
.mk-ao-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.04), transparent);
    opacity: 0;
    transition: opacity 0.25s;
}
.mk-ao-card:hover {
    border-color: var(--or);
    box-shadow: 0 8px 32px rgba(249, 115, 22, 0.12);
    transform: translateY(-3px);
}
.mk-ao-card:hover::after {
    opacity: 1;
}
.mk-ao-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.mk-ao-domain-badge {
    background: var(--or3);
    color: var(--or);
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
}
.mk-ao-urgency {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 99px;
}
.mk-ao-urgency.urgent {
    background: #fef2f2;
    color: #dc2626;
}
.mk-ao-urgency.new {
    background: #eff6ff;
    color: #2563eb;
}
.mk-ao-urgency.normal {
    background: #f0fdf4;
    color: #16a34a;
}
.mk-ao-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk);
    line-height: 1.4;
}
.mk-ao-desc {
    font-size: 13px;
    color: var(--gr);
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.mk-ao-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 12.5px;
    color: var(--gr);
}
.mk-ao-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}
.mk-ao-company {
    display: flex;
    align-items: center;
    gap: 8px;
}
.mk-ao-company-av {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--or), var(--am));
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mk-ao-company-name {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--dk);
}
.mk-ao-deadline {
    font-size: 11px;
    color: var(--gr);
}
.mk-ao-cands {
    text-align: right;
}
.mk-ao-cands span:first-child {
    font-size: 18px;
    font-weight: 800;
    color: var(--or);
    display: block;
}
.mk-ao-cands-lbl {
    font-size: 10.5px;
    color: var(--gr);
}
.mk-ao-btn {
    width: 100%;
    margin-top: 4px;
    padding: 10px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.mk-ao-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}

/* ── EMPTY ── */
.mk-empty {
    text-align: center;
    padding: 60px 20px;
}
.mk-empty-icon {
    font-size: 48px;
    margin-bottom: 14px;
}
.mk-empty-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 8px;
}
.mk-empty-desc {
    font-size: 14px;
    color: var(--gr);
    margin-bottom: 20px;
}

/* ── PUBLISH FORM ── */
.mk-publish-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}
@media (min-width: 900px) {
    .mk-publish-layout {
        grid-template-columns: 2fr 1fr;
    }
}
.mk-publish-form {
    background: var(--wh);
    border-radius: 16px;
    padding: 28px 24px;
    border: 1.5px solid var(--grl);
}
.mk-form-header {
    margin-bottom: 24px;
}
.mk-form-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 6px;
}
.mk-form-header p {
    font-size: 13.5px;
    color: var(--gr);
}
.mk-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 600px) {
    .mk-form-grid {
        grid-template-columns: 1fr;
    }
}
.mk-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mk-field-full {
    grid-column: 1/-1;
}
.mk-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.mk-req {
    color: var(--or);
}
.mk-input {
    padding: 11px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
    background: var(--cr);
    color: var(--dk);
}
.mk-input:focus {
    border-color: var(--or);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
}
.mk-textarea {
    resize: vertical;
    min-height: 100px;
}
.mk-form-notice {
    background: var(--or3);
    border: 1px solid rgba(249, 115, 22, 0.3);
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 13px;
    color: var(--dk);
    margin-bottom: 20px;
}
.mk-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* ── TIPS ── */
.mk-tips-card,
.mk-process-card {
    background: var(--wh);
    border-radius: 14px;
    padding: 22px;
    border: 1.5px solid var(--grl);
    margin-bottom: 16px;
}
.mk-tips-card h4,
.mk-process-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 14px;
}
.mk-tips-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 11px;
}
.mk-tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    font-size: 13px;
    color: var(--gr);
    line-height: 1.55;
}
.mk-tip-icon {
    font-size: 15px;
    flex-shrink: 0;
    margin-top: 1px;
}
.mk-process-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}
.mk-proc-num {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: var(--or);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.mk-proc-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 2px;
}
.mk-proc-desc {
    font-size: 12px;
    color: var(--gr);
}

/* ── CANDIDATURES ── */
.mk-cands-header {
    margin-bottom: 20px;
}
.mk-cands-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.mk-cands-header p {
    font-size: 14px;
    color: var(--gr);
}
.mk-cands-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.mk-cand-item {
    background: var(--wh);
    border-radius: 14px;
    padding: 18px 20px;
    border: 1.5px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    transition: all 0.2s;
}
.mk-cand-item:hover {
    border-color: var(--or);
    box-shadow: 0 4px 18px rgba(249, 115, 22, 0.1);
}
.mk-cand-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mk-cand-domain {
    font-size: 28px;
}
.mk-cand-title {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--dk);
}
.mk-cand-meta {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}
.mk-cand-right {
    text-align: right;
}
.mk-cand-date {
    font-size: 12px;
    color: var(--gr);
    margin-bottom: 6px;
}
.mk-cand-status {
    display: inline-block;
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
}
.mk-cand-status.pending {
    background: #fef9c3;
    color: #a16207;
}
.mk-cand-status.selected {
    background: #dcfce7;
    color: #16a34a;
}
.mk-cand-status.rejected {
    background: #fef2f2;
    color: #dc2626;
}

/* ── MODAL ── */
.mk-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: fadeIn 0.2s ease;
}
.mk-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: fadeUp 0.3s ease;
}
.mk-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
}
.mk-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.mk-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: var(--gr);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s;
}
.mk-modal-close:hover {
    background: var(--cr);
}
.mk-modal-body {
    padding: 20px 22px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.mk-modal-ao-info {
    background: var(--cr);
    border-radius: 10px;
    padding: 12px 15px;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.mk-modal-ao-info strong {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.mk-modal-ao-info span {
    font-size: 12.5px;
    color: var(--gr);
}
.mk-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 16px 22px;
    border-top: 1px solid var(--grl);
    flex-shrink: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.2;
    }
}
</style>
