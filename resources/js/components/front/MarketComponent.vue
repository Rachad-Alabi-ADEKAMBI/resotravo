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
                    Chaque offre est validée par l'équipe Mesotravo avant
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
                    v-if="isContractorOrTalent"
                    class="mk-tab"
                    :class="{ active: activeTab === 'myCandidatures' }"
                    @click="activeTab = 'myCandidatures'"
                >
                    📁 Mes candidatures
                </button>
                <button
                    v-if="isClient"
                    class="mk-tab"
                    :class="{ active: activeTab === 'myTenders' }"
                    @click="activeTab = 'myTenders'"
                >
                    🗂️ Mes appels d'offres
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
                    class="mk-ao-card"
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
                        <span>💰 {{ formatBudget(ao.budget) }}</span>
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
                    <button
                        class="mk-ao-btn"
                        :class="{
                            'mk-ao-btn-disabled':
                                isClient && ao.user_id === auth?.id,
                        }"
                        :disabled="isClient && ao.user_id === auth?.id"
                        @click.stop="postuler(ao)"
                    >
                        {{
                            isClient && ao.user_id === auth?.id
                                ? "🔒 Votre AO"
                                : "Postuler →"
                        }}
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
                            Mesotravo avant mise en ligne.
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
                        l'administrateur Mesotravo avant publication. Délai
                        habituel : 24h.
                    </div>

                    <!-- Erreur -->
                    <div class="mk-form-error" v-if="formError">
                        ⚠️ {{ formError }}
                    </div>

                    <!-- Non connecté -->
                    <div class="mk-form-error mk-form-warn" v-if="isGuest">
                        🔒 Vous devez être connecté en tant que client pour
                        publier un appel d'offres.
                        <a
                            :href="routes.login"
                            style="
                                color: var(--or);
                                font-weight: 700;
                                margin-left: 6px;
                            "
                            >Se connecter →</a
                        >
                    </div>

                    <div class="mk-form-actions">
                        <button class="btn btn-outline" @click="resetForm">
                            Annuler
                        </button>
                        <button
                            class="btn btn-primary btn-lg"
                            :disabled="!formValid || submitting || isGuest"
                            @click="submitForm"
                        >
                            <span v-if="submitting">⏳ Envoi en cours…</span>
                            <span v-else-if="formSubmitted"
                                >✅ Soumis — En attente de validation</span
                            >
                            <span v-else>Soumettre l'appel d'offres →</span>
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
         TAB 4 — MES APPELS D'OFFRES (client)
    ═══════════════════════════════════════════ -->
        <section class="mk-section" v-show="activeTab === 'myTenders'">
            <div class="mk-cands-header reveal">
                <h2>🗂️ Mes appels d'offres</h2>
                <p>Suivez l'état de vos appels d'offres publiés.</p>
            </div>

            <!-- Loading -->
            <div class="mk-empty" v-if="myTendersLoading">
                <div class="mk-empty-icon">⏳</div>
                <div class="mk-empty-title">Chargement…</div>
            </div>

            <!-- Erreur -->
            <div class="mk-form-error" v-else-if="myTendersError">
                ⚠️ {{ myTendersError }}
            </div>

            <div class="mk-cands-list" v-else-if="myTenders.length > 0">
                <div
                    class="mk-cand-item"
                    v-for="(t, i) in myTenders"
                    :key="t.id"
                >
                    <div class="mk-cand-left">
                        <div class="mk-cand-domain">
                            {{ t.domainIcon ?? "📋" }}
                        </div>
                        <div>
                            <div class="mk-cand-title">{{ t.title }}</div>
                            <div class="mk-cand-meta">
                                {{ t.domain }} · {{ t.location }} · 💰
                                {{ formatBudget(t.budget) }}
                            </div>
                            <div class="mk-cand-meta" style="margin-top: 3px">
                                📅 Limite : {{ t.deadline }} &nbsp;·&nbsp;
                                <strong>{{
                                    t.candidatures_count ?? t.candidatures ?? 0
                                }}</strong>
                                candidature(s)
                            </div>
                        </div>
                    </div>
                    <div class="mk-cand-right">
                        <div class="mk-cand-date">
                            Publié le {{ t.created_at_label ?? t.created_at }}
                        </div>
                        <div
                            class="mk-cand-status"
                            :class="{
                                pending: t.status === 'pending',
                                selected:
                                    t.status === 'published' ||
                                    t.status === 'approved',
                                rejected:
                                    t.status === 'rejected' ||
                                    t.status === 'closed',
                            }"
                        >
                            <span v-if="t.status === 'pending'"
                                >⏳ En attente de validation</span
                            >
                            <span
                                v-else-if="
                                    t.status === 'published' ||
                                    t.status === 'approved'
                                "
                                >✅ Publié</span
                            >
                            <span v-else-if="t.status === 'rejected'"
                                >❌ Refusé</span
                            >
                            <span v-else-if="t.status === 'closed'"
                                >🔒 Clôturé</span
                            >
                            <span v-else>{{ t.status }}</span>
                        </div>
                        <!-- Raison du refus -->
                        <div
                            class="mk-reject-reason"
                            v-if="t.status === 'rejected' && t.reject_reason"
                        >
                            💬 <strong>Motif :</strong> {{ t.reject_reason }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mk-empty" v-else>
                <div class="mk-empty-icon">🗂️</div>
                <div class="mk-empty-title">Aucun appel d'offres</div>
                <div class="mk-empty-desc">
                    Vous n'avez pas encore publié d'appel d'offres.
                </div>
                <button class="btn btn-primary" @click="activeTab = 'publish'">
                    Publier un AO →
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
                    <h3>
                        {{
                            isOwnTender
                                ? "📋 Votre appel d'offres"
                                : "📩 Postuler à cet appel d'offres"
                        }}
                    </h3>
                    <button class="mk-modal-close" @click="showModal = false">
                        ✕
                    </button>
                </div>
                <div class="mk-modal-body" v-if="selectedAO">
                    <div class="mk-modal-ao-info">
                        <strong>{{ selectedAO.title }}</strong>
                        <span
                            >{{ selectedAO.company }} ·
                            {{ selectedAO.location }} · 💰
                            {{ formatBudget(selectedAO.budget) }}</span
                        >
                    </div>
                    <!-- Vue proprio : infos de l'AO uniquement -->
                    <template v-if="isOwnTender">
                        <div class="mk-modal-own-info">
                            <div class="mk-modal-own-row">
                                <span class="mk-modal-own-lbl">📋 Domaine</span
                                ><span>{{ selectedAO.domain }}</span>
                            </div>
                            <div class="mk-modal-own-row">
                                <span class="mk-modal-own-lbl">⏱️ Durée</span
                                ><span>{{ selectedAO.duration }}</span>
                            </div>
                            <div class="mk-modal-own-row">
                                <span class="mk-modal-own-lbl">📅 Limite</span
                                ><span>{{ selectedAO.deadline }}</span>
                            </div>
                            <div class="mk-modal-own-row">
                                <span class="mk-modal-own-lbl"
                                    >👥 Candidatures</span
                                ><span>{{ selectedAO.candidatures ?? 0 }}</span>
                            </div>
                            <div class="mk-modal-own-row">
                                <span class="mk-modal-own-lbl">🔖 Statut</span
                                ><span>{{ selectedAO.urgencyLabel }}</span>
                            </div>
                        </div>
                        <div
                            class="mk-form-error mk-form-warn"
                            style="margin-top: 0"
                        >
                            🔒 Vous êtes l'auteur de cet appel d'offres. Vous ne
                            pouvez pas y postuler.
                        </div>
                    </template>
                    <!-- Vue candidat : formulaire complet -->
                    <template v-else>
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
                    </template>
                </div>
                <div class="mk-modal-footer">
                    <button class="btn btn-outline" @click="showModal = false">
                        {{ isOwnTender ? "Fermer" : "Annuler" }}
                    </button>
                    <button
                        v-if="!isOwnTender"
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
                register: "/register/client",
                login: "/login",
                tenders_index: "/tenders",
                tenders_stats: "/tenders/stats",
                tenders_store: "/client/tenders",
                tenders_apply: "/tenders/{id}/apply",
                my_applications: "/tenders/my-applications",
                my_tenders: "/client/tenders/mine",
            }),
        },
        // Utilisateur connecté (null si visiteur)
        auth: {
            type: Object,
            default: () => null,
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

            // API data
            appelsOffres: [],
            myCandidatures: [],
            myTenders: [],
            aoLoading: false,
            aoError: null,
            candsLoading: false,
            myTendersLoading: false,
            myTendersError: null,
            submitting: false,
            formError: "",
            applyError: "",
            applyLoading: false,

            stats: { active: "…", domains: "…", companies: "…", talents: "…" },

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
                    desc: "L'équipe Mesotravo examine votre offre sous 24h.",
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
        };
    },

    computed: {
        isClient() {
            return this.auth?.role === "client";
        },
        isContractorOrTalent() {
            return (
                this.auth?.role === "contractor" || this.auth?.role === "talent"
            );
        },
        isGuest() {
            return !this.auth;
        },
        filteredAOs() {
            let list = [...this.appelsOffres];
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(
                    (a) =>
                        a.title.toLowerCase().includes(q) ||
                        a.description.toLowerCase().includes(q)
                );
            }
            if (this.filterDomain)
                list = list.filter((a) => a.domain === this.filterDomain);
            if (this.filterLocation)
                list = list.filter((a) => a.location === this.filterLocation);
            if (this.filterTag)
                list = list.filter((a) =>
                    (a.tags ?? []).includes(this.filterTag)
                );
            if (this.filterBudget === "low")
                list = list.filter(
                    (a) =>
                        parseInt((a.budget ?? "0").replace(/\D/g, "")) < 100000
                );
            if (this.filterBudget === "mid")
                list = list.filter((a) => {
                    const v = parseInt((a.budget ?? "0").replace(/\D/g, ""));
                    return v >= 100000 && v <= 500000;
                });
            if (this.filterBudget === "high")
                list = list.filter(
                    (a) =>
                        parseInt((a.budget ?? "0").replace(/\D/g, "")) > 500000
                );
            if (this.sortBy === "candidatures")
                list = [...list].sort(
                    (a, b) => b.candidatures - a.candidatures
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
        alreadyApplied() {
            if (!this.selectedAO) return false;
            return this.myCandidatures.some(
                (c) => c.tender_id === this.selectedAO.id
            );
        },
        isOwnTender() {
            if (!this.selectedAO || !this.auth) return false;
            return this.selectedAO.user_id === this.auth.id;
        },
    },

    watch: {
        // Charger les candidatures quand l'onglet est ouvert
        activeTab(val) {
            if (val === "myCandidatures" && this.myCandidatures.length === 0) {
                this.fetchMyCandidatures();
            }
            if (val === "myTenders") {
                this.fetchMyTenders();
            }
            this.$nextTick(() => {
                this.reObserveReveal();
                // Forcer reveal immédiat sur les éléments déjà chargés
                setTimeout(() => {
                    document
                        .querySelectorAll(".mk-section .reveal:not(.revealed)")
                        .forEach((el) => el.classList.add("revealed"));
                }, 100);
            });
        },
        sortBy() {
            this.fetchAOs();
        },
    },

    mounted() {
        this.fetchStats();
        this.fetchAOs();
        if (this.isClient) {
            this.fetchMyTenders();
        }
        this.$nextTick(() => this.reObserveReveal());
    },

    methods: {
        // ── API calls ─────────────────────────────────────────────

        async fetchStats() {
            try {
                const res = await fetch(this.routes.tenders_stats, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.stats = {
                    active: data.active ?? "0",
                    domains: data.domains ?? "0",
                    companies: data.companies ?? "0",
                    talents: data.talents ?? "0",
                };
            } catch {
                /* silencieux */
            }
        },

        async fetchAOs() {
            this.aoLoading = true;
            this.aoError = null;
            try {
                const params = new URLSearchParams();
                if (this.filterDomain) params.set("domain", this.filterDomain);
                if (this.filterLocation)
                    params.set("location", this.filterLocation);
                if (this.searchQuery) params.set("search", this.searchQuery);
                if (this.filterBudget) params.set("budget", this.filterBudget);
                if (this.sortBy === "candidatures")
                    params.set("sort", "candidatures");

                const url =
                    this.routes.tenders_index +
                    (params.toString() ? "?" + params : "");
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.appelsOffres = Array.isArray(data)
                    ? data
                    : data.data ?? [];
                // Forcer révélation des éléments reveal après rendu Vue
                this.$nextTick(() => {
                    setTimeout(() => {
                        document
                            .querySelectorAll(
                                ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)"
                            )
                            .forEach((el) => el.classList.add("revealed"));
                    }, 80);
                });
            } catch {
                this.aoError = "Impossible de charger les appels d'offres.";
            } finally {
                this.aoLoading = false;
            }
        },

        async fetchMyCandidatures() {
            if (this.isGuest) return;
            this.candsLoading = true;
            try {
                const res = await fetch(this.routes.my_applications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.myCandidatures = Array.isArray(data) ? data : [];
            } catch {
                /* silencieux */
            } finally {
                this.candsLoading = false;
            }
        },

        async fetchMyTenders() {
            this.myTendersLoading = true;
            this.myTendersError = null;
            try {
                const res = await fetch(this.routes.my_tenders, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("HTTP " + res.status);
                const data = await res.json();
                this.myTenders = Array.isArray(data) ? data : data.data ?? [];
            } catch (e) {
                console.error("[fetchMyTenders]", e);
                this.myTendersError =
                    "Impossible de charger vos appels d'offres. (" +
                    e.message +
                    ")";
            } finally {
                this.myTendersLoading = false;
            }
        },

        // ── AO interactions ───────────────────────────────────────

        openAO(ao) {
            this.selectedAO = ao;
            this.showModal = true;
            this.candidatureSubmitted = false;
            this.applyError = "";
            this.candidature = { motivation: "", tarif: "", disponibilite: "" };
        },

        postuler(ao) {
            if (this.isGuest) {
                window.location.href = this.routes.login;
                return;
            }
            this.openAO(ao);
        },

        async submitCandidature() {
            if (!this.candidature.motivation.trim()) return;
            if (this.isGuest) {
                window.location.href = this.routes.login;
                return;
            }

            this.applyLoading = true;
            this.applyError = "";
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.tenders_apply.replace(
                    "{id}",
                    this.selectedAO.id
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(this.candidature),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.applyError =
                        data.message ?? "Une erreur est survenue.";
                    return;
                }
                this.candidatureSubmitted = true;

                // Ajouter à mes candidatures localement
                this.myCandidatures.unshift(data.application);

                // Incrémenter le compteur local
                const idx = this.appelsOffres.findIndex(
                    (a) => a.id === this.selectedAO.id
                );
                if (idx !== -1) this.appelsOffres[idx].candidatures++;

                setTimeout(() => {
                    this.showModal = false;
                }, 2000);
            } catch {
                this.applyError = "Erreur réseau. Veuillez réessayer.";
            } finally {
                this.applyLoading = false;
            }
        },

        async submitForm() {
            console.log("[submitForm]", {
                formValid: this.formValid,
                isGuest: this.isGuest,
                isClient: this.isClient,
                auth: this.auth,
                form: this.form,
                route: this.routes.tenders_store,
            });

            if (!this.formValid) return;
            if (this.isGuest) {
                window.location.href = this.routes.login;
                return;
            }
            if (!this.isClient) {
                this.formError =
                    "Seuls les clients peuvent publier un appel d'offres.";
                return;
            }

            this.submitting = true;
            this.formError = "";
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const res = await fetch(this.routes.tenders_store, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        title: this.form.title,
                        domain: this.form.domain,
                        location: this.form.location,
                        duration: this.form.duration,
                        budget: this.form.budget,
                        deadline: this.form.deadline,
                        profile_type: this.form.profileType,
                        description: this.form.description,
                        requirements: this.form.requirements,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.formError = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : data.message ?? "Erreur.";
                    return;
                }
                this.formSubmitted = true;

                // Ajouter immédiatement à la liste locale + forcer rechargement
                const newTender = data.tender ?? data;
                if (newTender && newTender.id) {
                    this.myTenders.unshift(newTender);
                } else {
                    // Si l'API ne retourne pas le tender, forcer un rechargement complet
                    this.myTenders = [];
                }

                // Basculer vers "Mes AO" après 1.5s
                setTimeout(() => {
                    this.resetForm();
                    this.activeTab = "myTenders";
                }, 1500);
            } catch {
                this.formError = "Erreur réseau. Veuillez réessayer.";
            } finally {
                this.submitting = false;
            }
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
            this.formError = "";
        },

        formatBudget(val) {
            if (!val) return "—";
            const num = parseInt(String(val).replace(/\D/g, ""));
            if (isNaN(num)) return val;
            return num.toLocaleString("fr-FR") + " F CFA";
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
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)"
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },
    },
};
</script>
<style scoped>
/* ════════════════════════════════════════════
   VARIABLES & BASE
════════════════════════════════════════════ */
.market-page {
    width: 100%;
    overflow-x: hidden;
}

/* ── HERO ── */
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
    font-size: clamp(24px, 6vw, 54px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}
.mk-hero-desc {
    font-size: 15px;
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
    scrollbar-width: none;
}
.mk-tabs::-webkit-scrollbar {
    display: none;
}
.mk-tab {
    padding: 13px 16px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s;
    flex-shrink: 0;
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
    padding: 28px 4% 52px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ── FILTRES ── */
.mk-filters {
    background: var(--wh);
    border-radius: 14px;
    padding: 16px;
    border: 1.5px solid var(--grl);
    margin-bottom: 24px;
}
.mk-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.search-input {
    flex: 1;
    min-width: 180px;
    padding: 10px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    outline: none;
    background: var(--cr);
    color: var(--dk);
    transition: border-color 0.2s;
}
.search-input:focus {
    border-color: var(--or);
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
@media (max-width: 600px) {
    .mk-select {
        width: 100%;
    }
    .search-input {
        width: 100%;
    }
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
    flex-wrap: wrap;
    gap: 10px;
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
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
}
@media (max-width: 480px) {
    .mk-ao-grid {
        grid-template-columns: 1fr;
    }
}
.mk-ao-card {
    background: var(--wh);
    border-radius: 16px;
    padding: 18px 16px;
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
    gap: 8px;
    flex-wrap: wrap;
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
    gap: 8px;
    font-size: 12.5px;
    color: var(--gr);
}
.mk-ao-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
    gap: 8px;
    flex-wrap: wrap;
}
.mk-ao-company {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
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
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
}
.mk-ao-deadline {
    font-size: 11px;
    color: var(--gr);
}
.mk-ao-cands {
    text-align: right;
    flex-shrink: 0;
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
.mk-ao-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}
.mk-ao-btn.mk-ao-btn-disabled,
.mk-ao-btn:disabled {
    background: var(--grl);
    color: var(--gr);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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
    padding: 24px 20px;
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
    gap: 14px;
    margin-bottom: 20px;
}
@media (max-width: 640px) {
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
    width: 100%;
    box-sizing: border-box;
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
    flex-wrap: wrap;
}
@media (max-width: 480px) {
    .mk-form-actions {
        flex-direction: column;
    }
    .mk-form-actions .btn {
        width: 100%;
    }
}
.mk-form-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 10px;
    padding: 11px 14px;
    font-size: 13px;
    color: #dc2626;
    font-weight: 600;
    margin-bottom: 4px;
}
.mk-form-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #92400e;
}

/* ── TIPS / PROCESS ── */
.mk-publish-tips {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.mk-tips-card,
.mk-process-card {
    background: var(--wh);
    border-radius: 14px;
    padding: 20px;
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

/* ── CANDIDATURES / MES AO ── */
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
    padding: 16px 18px;
    border: 1.5px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
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
    flex: 1;
    min-width: 0;
}
.mk-cand-domain {
    font-size: 28px;
    flex-shrink: 0;
}
.mk-cand-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 280px;
}
.mk-cand-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.mk-cand-right {
    text-align: right;
    flex-shrink: 0;
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
@media (max-width: 560px) {
    .mk-cand-item {
        flex-direction: column;
        align-items: flex-start;
    }
    .mk-cand-right {
        text-align: left;
        width: 100%;
    }
    .mk-cand-title {
        max-width: 100%;
        white-space: normal;
    }
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
    padding: 16px 20px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
}
.mk-modal-header h3 {
    font-size: 15px;
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
    padding: 18px 20px;
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
    padding: 14px 20px;
    border-top: 1px solid var(--grl);
    flex-shrink: 0;
    flex-wrap: wrap;
}
@media (max-width: 480px) {
    .mk-modal-footer {
        flex-direction: column;
    }
    .mk-modal-footer .btn {
        width: 100%;
    }
}

/* ── CTA ── */
.cta-final {
    background: var(--dk2);
    padding: 52px 4%;
    text-align: center;
}
.cta-inner {
    max-width: 600px;
    margin: 0 auto;
}
.cta-inner h2 {
    font-size: clamp(22px, 4vw, 32px);
    font-weight: 800;
    color: #fff;
    margin-bottom: 12px;
}
.cta-inner p {
    font-size: 15px;
    color: #b8ada7;
    margin-bottom: 26px;
    line-height: 1.7;
}
.cta-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}
@media (max-width: 480px) {
    .cta-btns {
        flex-direction: column;
        align-items: stretch;
    }
    .cta-btns .btn {
        width: 100%;
        text-align: center;
    }
}

/* ── REVEAL ── */
.reveal,
.reveal-left,
.reveal-right {
    opacity: 0;
    transform: translateY(22px);
    transition: opacity 0.55s ease, transform 0.55s ease;
}
.reveal-left {
    transform: translateX(-22px);
}
.reveal-right {
    transform: translateX(22px);
}
.revealed {
    opacity: 1 !important;
    transform: none !important;
}
.reveal-d1 {
    transition-delay: 0.05s;
}
.reveal-d2 {
    transition-delay: 0.12s;
}
.reveal-d3 {
    transition-delay: 0.19s;
}
.reveal-d4 {
    transition-delay: 0.26s;
}

/* ── MODAL PROPRIO ── */
.mk-modal-own-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: var(--cr);
    border-radius: 10px;
    padding: 14px 16px;
    border: 1.5px solid var(--grl);
}
.mk-modal-own-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13.5px;
    gap: 10px;
}
.mk-modal-own-lbl {
    font-weight: 600;
    color: var(--gr);
    flex-shrink: 0;
}

/* ── RAISON REFUS ── */
.mk-reject-reason {
    margin-top: 8px;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 12px;
    color: #dc2626;
    line-height: 1.5;
}

/* ── ANIMATIONS ── */
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

/* ── HSTAT ── */
.hstat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.hstat .num {
    font-size: clamp(22px, 4vw, 32px);
    font-weight: 900;
    color: var(--or);
}
.hstat .lbl {
    font-size: 12px;
    color: #b8ada7;
    font-weight: 500;
}
.hl {
    color: var(--or);
}
</style>
