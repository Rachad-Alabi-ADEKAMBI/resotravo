<template>
    <div class="cd-wrap">
        <!-- ══════════════════════════════════
             TOPBAR
        ══════════════════════════════════ -->
        <div class="cd-topbar">
            <div class="cd-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="cd-page-title">Mon espace client</div>
                    <div class="cd-page-sub">
                        {{ greeting }},
                        <strong>{{ client.display_name }}</strong>
                    </div>
                </div>
            </div>
            <div class="cd-topbar-right">
                <!-- Cloche notifications -->
                <div class="cd-notif-wrap" ref="notifWrap">
                    <button class="cd-notif-btn" @click="toggleNotif">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"
                            />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <span class="cd-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
                    </button>
                    <div class="cd-notif-dropdown" v-if="notifOpen">
                        <div class="cd-notif-header">
                            <span>Notifications</span>
                            <button
                                class="cd-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="cd-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="cd-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="cd-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="cd-notif-dot" v-if="!n.read"></div>
                            <div class="cd-notif-content">
                                <div class="cd-notif-title">{{ n.title }}</div>
                                <div class="cd-notif-body">{{ n.body }}</div>
                                <div class="cd-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cd-avatar">{{ client.initials }}</div>
                <button class="cd-btn cd-btn-orange" @click="openNewMission">
                    ＋ Nouvelle mission
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════
             CONTENU
        ══════════════════════════════════ -->
        <div class="cd-content">
            <!-- ── Bannière compte entreprise ── -->
            <div
                class="cd-banner-company"
                v-if="client.account_type === 'company'"
            >
                🏢 <strong>{{ client.company_name }}</strong> — Compte
                entreprise
                <span class="cd-banner-badge"
                    >Appels d'offres & Espace Talents disponibles</span
                >
            </div>

            <!-- ── KPIs ── -->
            <div class="cd-kpis">
                <div
                    class="cd-kpi"
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    :class="kpi.color"
                >
                    <div class="cd-kpi-icon">{{ kpi.icon }}</div>
                    <div class="cd-kpi-val">
                        <span
                            v-if="missionsLoading"
                            class="cd-skeleton-val"
                        ></span>
                        <span v-else>{{ kpi.value }}</span>
                    </div>
                    <div class="cd-kpi-label">{{ kpi.label }}</div>
                </div>
            </div>

            <!-- ── LIGNE MILIEU ── -->
            <div class="cd-mid-grid">
                <!-- Missions en cours -->
                <div class="cd-card cd-card-main">
                    <div class="cd-card-header">
                        <h3>📋 Missions en cours</h3>
                        <button
                            class="cd-btn cd-btn-ghost cd-btn-sm"
                            @click="tab = 'all'"
                        >
                            Voir tout
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="cd-tabs">
                        <button
                            class="cd-tab"
                            v-for="t in tabs"
                            :key="t.key"
                            :class="{ active: tab === t.key }"
                            @click="tab = t.key"
                        >
                            {{ t.label }}
                            <span class="cd-tab-count">{{
                                countByTab(t.key)
                            }}</span>
                        </button>
                    </div>

                    <!-- Loader -->
                    <div class="cd-loading" v-if="missionsLoading">
                        <div
                            class="cd-skeleton-row"
                            v-for="n in 3"
                            :key="n"
                        ></div>
                    </div>

                    <!-- Erreur -->
                    <div class="cd-alert-error" v-else-if="missionsError">
                        ⚠️ {{ missionsError }}
                        <button
                            class="cd-btn cd-btn-ghost cd-btn-sm"
                            @click="fetchMissions"
                        >
                            Réessayer
                        </button>
                    </div>

                    <!-- Vide -->
                    <div
                        class="cd-empty"
                        v-else-if="filteredMissions.length === 0"
                    >
                        <div class="cd-empty-icon">📭</div>
                        <div class="cd-empty-title">
                            Aucune mission{{
                                tab !== "all" ? " " + tabLabel : ""
                            }}
                        </div>
                        <button
                            class="cd-btn cd-btn-orange"
                            @click="openNewMission"
                            style="margin-top: 12px"
                        >
                            Créer une mission
                        </button>
                    </div>

                    <!-- Liste -->
                    <div class="cd-mission-list" v-else>
                        <div
                            class="cd-mission-item"
                            v-for="m in filteredMissions"
                            :key="m.id"
                            @click="openMission(m)"
                        >
                            <div class="cd-mission-left">
                                <div class="cd-mission-icon">
                                    {{ serviceIcon(m.service) }}
                                </div>
                                <div>
                                    <div class="cd-mission-title">
                                        {{ m.service }}
                                    </div>
                                    <div class="cd-mission-meta">
                                        {{
                                            m.contractor
                                                ? m.contractor.name
                                                : "Attribution en cours..."
                                        }}
                                        · {{ formatDate(m.created_at) }}
                                    </div>
                                </div>
                            </div>
                            <div class="cd-mission-right">
                                <span
                                    class="cd-badge"
                                    :class="badgeClass(m.status)"
                                >
                                    {{ labelOf(m) }}
                                </span>
                                <div class="cd-mission-price">
                                    {{
                                        m.total_amount
                                            ? formatPrice(m.total_amount)
                                            : "—"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel droit -->
                <div class="cd-side-col">
                    <!-- Profil rapide -->
                    <div class="cd-card">
                        <div class="cd-card-header">
                            <h3>👤 Mon profil</h3>
                            <button
                                class="cd-btn cd-btn-ghost cd-btn-sm"
                                @click="wip('Mon profil')"
                            >
                                Modifier
                            </button>
                        </div>
                        <div class="cd-profile-info">
                            <div class="cd-profile-av">
                                {{ client.initials }}
                            </div>
                            <div>
                                <div class="cd-profile-name">
                                    {{ client.display_name }}
                                </div>
                                <div class="cd-profile-meta">
                                    {{ client.email }}
                                </div>
                                <div class="cd-profile-meta">
                                    {{ client.phone }}
                                </div>
                                <div class="cd-profile-meta">
                                    📍 {{ client.city }}
                                </div>
                            </div>
                        </div>
                        <div class="cd-account-type">
                            <span
                                class="cd-type-badge"
                                :class="client.account_type"
                            >
                                {{
                                    client.account_type === "company"
                                        ? "🏢 Entreprise"
                                        : "🏠 Particulier"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="cd-card">
                        <div class="cd-card-header">
                            <h3>⚡ Actions rapides</h3>
                        </div>
                        <div class="cd-quick-actions">
                            <button
                                class="cd-quick-btn"
                                @click="openNewMission"
                            >
                                <span>➕</span> Nouvelle mission
                            </button>
                            <button
                                class="cd-quick-btn"
                                @click="wip('Rechercher un prestataire')"
                            >
                                <span>🔍</span> Trouver un prestataire
                            </button>
                            <button
                                class="cd-quick-btn"
                                @click="wip('Publier un appel d\'offres')"
                                v-if="client.account_type === 'company'"
                            >
                                <span>📝</span> Publier un appel d'offres
                            </button>
                            <button
                                class="cd-quick-btn"
                                @click="wip('Messagerie')"
                            >
                                <span>💬</span> Messagerie
                            </button>
                            <button
                                class="cd-quick-btn"
                                @click="wip('Mes paiements')"
                            >
                                <span>💸</span> Mes paiements
                            </button>
                            <button
                                class="cd-quick-btn"
                                @click="wip('Mes factures')"
                            >
                                <span>🧾</span> Mes factures
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Prestataires recommandés ── -->
            <div class="cd-card">
                <div class="cd-card-header">
                    <h3>⭐ Prestataires recommandés près de vous</h3>
                    <button
                        class="cd-btn cd-btn-ghost cd-btn-sm"
                        @click="wip('Tous les prestataires')"
                    >
                        Voir tout
                    </button>
                </div>
                <div class="cd-contractors-grid">
                    <div
                        class="cd-contractor-card"
                        v-for="c in contractors"
                        :key="c.id"
                        @click="wip('Profil prestataire')"
                    >
                        <div
                            class="cd-contractor-av"
                            :style="{ background: c.color }"
                        >
                            {{ c.initials }}
                        </div>
                        <div class="cd-contractor-name">{{ c.name }}</div>
                        <div class="cd-contractor-spec">{{ c.specialty }}</div>
                        <div class="cd-contractor-rating">
                            ⭐ {{ c.rating }} <span>({{ c.reviews }})</span>
                        </div>
                        <div class="cd-contractor-zone">📍 {{ c.zone }}</div>
                        <button
                            class="cd-btn cd-btn-orange"
                            style="
                                width: 100%;
                                margin-top: 10px;
                                font-size: 12px;
                                padding: 8px;
                            "
                            @click.stop="wip('Contacter le prestataire')"
                        >
                            Contacter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════
             MODAL DÉTAIL MISSION
        ══════════════════════════════════ -->
        <div
            class="cd-modal-overlay"
            v-if="activeMission"
            @click.self="activeMission = null"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <div>
                        <h3>{{ activeMission.service }}</h3>
                        <div class="cd-modal-sub">
                            Mission #{{ activeMission.id }} ·
                            {{ formatDate(activeMission.created_at) }}
                        </div>
                    </div>
                    <button
                        class="cd-modal-close"
                        @click="activeMission = null"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cd-modal-body">
                    <div class="cd-detail-row">
                        <span>Statut</span>
                        <span
                            class="cd-badge"
                            :class="badgeClass(activeMission.status)"
                        >
                            {{ labelOf(activeMission) }}
                        </span>
                    </div>
                    <div class="cd-detail-row">
                        <span>Étape</span>
                        <strong>{{ activeMission.step }} / 12</strong>
                    </div>
                    <div class="cd-detail-row">
                        <span>Prestataire</span>
                        <strong>{{
                            activeMission.contractor
                                ? activeMission.contractor.name
                                : "Attribution en cours..."
                        }}</strong>
                    </div>
                    <div class="cd-detail-row" v-if="activeMission.contractor">
                        <span>Téléphone</span>
                        <strong>{{ activeMission.contractor.phone }}</strong>
                    </div>
                    <div class="cd-detail-row">
                        <span>Adresse</span>
                        <strong>{{ activeMission.address }}</strong>
                    </div>
                    <div class="cd-detail-row">
                        <span>Description</span>
                        <strong>{{ activeMission.description }}</strong>
                    </div>
                    <div
                        class="cd-detail-row"
                        v-if="activeMission.total_amount"
                    >
                        <span>Montant</span>
                        <strong>{{
                            formatPrice(activeMission.total_amount)
                        }}</strong>
                    </div>
                    <div class="cd-detail-row" v-if="activeMission.accepted_at">
                        <span>Acceptée le</span>
                        <strong>{{
                            formatDate(activeMission.accepted_at)
                        }}</strong>
                    </div>

                    <!-- Timeline step progress -->
                    <div class="cd-step-bar">
                        <div
                            class="cd-step-fill"
                            :style="{
                                width: (activeMission.step / 12) * 100 + '%',
                            }"
                        ></div>
                    </div>
                    <div class="cd-step-label">
                        Étape {{ activeMission.step }} sur 12 —
                        {{ labelOf(activeMission) }}
                    </div>

                    <!-- Action : confirmer fin des travaux (étape 11) -->
                    <div
                        class="cd-action-block"
                        v-if="activeMission.status === 'awaiting_confirm'"
                    >
                        <p>
                            ✅ Le prestataire a terminé l'intervention.
                            Confirmez-vous la fin des travaux ?
                        </p>
                        <div class="cd-action-row">
                            <button
                                class="cd-btn cd-btn-ghost"
                                @click="signalProblem"
                            >
                                ⚠️ Signaler un problème
                            </button>
                            <button
                                class="cd-btn cd-btn-orange"
                                @click="confirmCompletion(activeMission)"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="cd-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>Confirmer la fin ✓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action : approuver le devis (étape 8→9) -->
                    <div
                        class="cd-action-block cd-action-quote"
                        v-if="
                            activeMission.status === 'quote_submitted' &&
                            activeMission.quote
                        "
                    >
                        <p>
                            📄 Le prestataire a soumis un devis de
                            <strong>{{
                                formatPrice(activeMission.quote.amount_incl_tax)
                            }}</strong
                            >.
                        </p>
                        <div class="cd-action-row">
                            <button
                                class="cd-btn cd-btn-red"
                                @click="rejectQuote(activeMission)"
                                :disabled="actionLoading"
                            >
                                Refuser
                            </button>
                            <button
                                class="cd-btn cd-btn-orange"
                                @click="approveQuote(activeMission)"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="cd-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>Approuver le devis ✓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Paiement débloqué (étape 11 → 12) -->
                    <div
                        class="cd-action-block cd-action-pay"
                        v-if="activeMission.payment_unlocked"
                    >
                        <p>
                            💸 Les travaux sont confirmés. Procédez au paiement
                            MoMo pour clôturer la mission.
                        </p>
                        <button
                            class="cd-btn cd-btn-orange"
                            style="width: 100%"
                            @click="wip('Paiement MoMo')"
                        >
                            Payer via MTN MoMo →
                        </button>
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="activeMission = null"
                    >
                        Fermer
                    </button>
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="wip('Messagerie mission')"
                        v-if="
                            [
                                'accepted',
                                'contact_made',
                                'on_the_way',
                                'tracking',
                                'in_progress',
                            ].includes(activeMission.status)
                        "
                    >
                        💬 Contacter
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════
             MODAL NOUVELLE MISSION
        ══════════════════════════════════ -->
        <div
            class="cd-modal-overlay"
            v-if="showNewMission"
            @click.self="showNewMission = false"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <h3>➕ Nouvelle mission</h3>
                    <button
                        class="cd-modal-close"
                        @click="showNewMission = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cd-modal-body">
                    <div class="cd-field">
                        <label>Type de lieu <span class="req">*</span></label>
                        <div class="cd-radio-row">
                            <label
                                class="cd-radio-opt"
                                :class="{
                                    selected:
                                        newMissionForm.location_type ===
                                        'residential',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="newMissionForm.location_type"
                                    value="residential"
                                />
                                🏠 Domicile
                            </label>
                            <label
                                class="cd-radio-opt"
                                :class="{
                                    selected:
                                        newMissionForm.location_type ===
                                        'business',
                                    disabled:
                                        client.account_type === 'individual',
                                }"
                                v-if="client.account_type === 'company'"
                            >
                                <input
                                    type="radio"
                                    v-model="newMissionForm.location_type"
                                    value="business"
                                />
                                🏢 Entreprise
                            </label>
                        </div>
                    </div>
                    <div class="cd-field">
                        <label
                            >Type de prestation
                            <span class="req">*</span></label
                        >
                        <select
                            class="cd-input"
                            v-model="newMissionForm.service"
                        >
                            <option value="">Sélectionner...</option>
                            <option
                                v-for="s in services"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.icon }} {{ s.label }}
                            </option>
                        </select>
                    </div>
                    <div class="cd-field">
                        <label>Description <span class="req">*</span></label>
                        <textarea
                            class="cd-input cd-textarea"
                            v-model="newMissionForm.description"
                            placeholder="Décrivez votre besoin en détail (min. 20 caractères)..."
                            rows="4"
                        ></textarea>
                        <div
                            class="cd-char-count"
                            :class="{
                                warn: newMissionForm.description.length < 20,
                            }"
                        >
                            {{ newMissionForm.description.length }} / 2000
                        </div>
                    </div>
                    <div class="cd-field">
                        <label
                            >Adresse d'intervention
                            <span class="req">*</span></label
                        >
                        <input
                            class="cd-input"
                            type="text"
                            v-model="newMissionForm.address"
                            :placeholder="
                                clientProfile.address || 'Ex : Akpakpa, Cotonou'
                            "
                        />
                    </div>

                    <!-- Géolocalisation -->
                    <button
                        class="cd-btn-geo"
                        type="button"
                        @click="askGeoConsent"
                        :disabled="geoLoading"
                        v-if="!geoOk"
                    >
                        <div
                            class="cd-spinner cd-spinner-sm"
                            v-if="geoLoading"
                        ></div>
                        <span v-else>📍</span>
                        {{
                            geoLoading
                                ? "Localisation..."
                                : "Utiliser ma position actuelle"
                        }}
                    </button>
                    <div class="cd-geo-confirmed" v-if="geoOk">
                        <span style="font-size: 18px">📍</span>
                        <div>
                            <div class="cd-geo-ok-title">
                                Position enregistrée
                            </div>
                            <div class="cd-geo-ok-sub">
                                Vos coordonnées GPS ont été ajoutées à la
                                mission.
                            </div>
                        </div>
                        <button
                            type="button"
                            class="cd-geo-reset"
                            @click="resetGeo"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="cd-geo-blocked" v-if="geoBlocked">
                        ⚠️ Accès refusé. Activez la géolocalisation dans votre
                        navigateur ou continuez avec votre adresse.
                    </div>
                    <div class="cd-err" v-if="missionError">
                        {{ missionError }}
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="showNewMission = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="cd-btn cd-btn-orange"
                        @click="submitMission"
                        :disabled="loading"
                    >
                        <div class="cd-spinner" v-if="loading"></div>
                        <span v-else>Publier la mission →</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CONSENTEMENT GÉOLOCALISATION -->
        <div
            class="cd-modal-overlay"
            v-if="showGeoConsent"
            @click.self="showGeoConsent = false"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <h3>📍 Activer la géolocalisation</h3>
                    <button
                        class="cd-modal-close"
                        @click="showGeoConsent = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cd-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            margin-bottom: 14px;
                        "
                    >
                        Resotravo souhaite accéder à votre position GPS pour :
                    </p>
                    <ul class="cd-geo-reasons">
                        <li>📌 Identifier les prestataires les plus proches</li>
                        <li>⚡ Réduire le délai d'attribution automatique</li>
                        <li>🗺️ Permettre le suivi GPS en temps réel</li>
                    </ul>
                    <div class="cd-geo-privacy-note">
                        🔒 Vos coordonnées sont uniquement utilisées pour cette
                        mission et ne sont jamais partagées en dehors de
                        Resotravo.
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="showGeoConsent = false"
                    >
                        Non merci
                    </button>
                    <button class="cd-btn cd-btn-orange" @click="geolocate">
                        📍 Autoriser
                    </button>
                </div>
            </div>
        </div>

        <!-- WIP MODAL -->
        <div
            class="cd-wip-overlay"
            v-if="wipVisible"
            @click="wipVisible = false"
        >
            <div class="cd-wip-modal" @click.stop>
                <div class="cd-wip-icon">🚧</div>
                <h3>Fonctionnalité en cours de développement</h3>
                <p class="cd-wip-feature">{{ wipFeature }}</p>
                <p>Cette section sera disponible très prochainement.</p>
                <button
                    class="cd-btn cd-btn-orange"
                    @click="wipVisible = false"
                    style="width: 100%"
                >
                    Compris →
                </button>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="cd-toast-container">
            <div
                class="cd-toast"
                :class="t.type"
                v-for="t in toasts"
                :key="t.id"
            >
                {{ t.message }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ClientDashboardComponent",

    props: {
        user: {
            type: Object,
            default: () => ({ name: "Client", email: "", role: "client" }),
        },
        clientProfile: {
            type: Object,
            default: () => ({
                first_name: "",
                last_name: "",
                phone: "",
                address: "",
                city: "Cotonou",
                account_type: "individual",
                company_name: null,
            }),
        },
        routes: {
            type: Object,
            default: () => ({
                missions_index: "/client/missions",
                missions_store: "/client/missions",
                missions_status: "/client/missions/{id}/status",
                notifications: "/notifications",
                notifications_read: "/notifications/{id}/read",
                notifications_all: "/notifications/read-all",
            }),
        },
    },

    data() {
        return {
            // UI state
            tab: "all",
            activeMission: null,
            showNewMission: false,
            wipVisible: false,
            wipFeature: "",
            loading: false,
            actionLoading: false,
            missionError: "",
            sidebarOpen: false,
            toasts: [],
            toastId: 0,

            // Géolocalisation
            geoLoading: false,
            geoOk: false,
            geoBlocked: false,
            showGeoConsent: false,

            // Notifications
            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
            notifInterval: null,

            // Missions (chargées depuis l'API)
            missions: [],
            missionsLoading: true,
            missionsError: null,

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "En cours" }, // statuts actifs groupés
                { key: "pending", label: "En attente" },
                { key: "closed", label: "Terminées" },
                { key: "cancelled", label: "Annulées" },
            ],

            // Formulaire nouvelle mission
            newMissionForm: {
                location_type: "residential",
                service: "",
                description: "",
                address: "",
            },

            services: [
                { value: "plomberie", label: "Plomberie", icon: "🔧" },
                { value: "electricite", label: "Électricité", icon: "⚡" },
                { value: "menuiserie", label: "Menuiserie", icon: "🪵" },
                { value: "ferronnerie", label: "Ferronnerie", icon: "⚙️" },
                { value: "frigoriste", label: "Frigoriste", icon: "❄️" },
                { value: "mecanique", label: "Mécanique auto", icon: "🚗" },
                { value: "nettoyage", label: "Nettoyage", icon: "🧹" },
                { value: "peinture", label: "Peinture", icon: "🎨" },
                { value: "maintenance", label: "Maintenance", icon: "🛠️" },
                { value: "autre", label: "Autre", icon: "➕" },
            ],

            // Prestataires recommandés (statiques pour l'instant)
            contractors: [
                {
                    id: 1,
                    initials: "KM",
                    name: "Kokou Mensah",
                    specialty: "Plombier",
                    rating: 4.9,
                    reviews: 38,
                    zone: "Akpakpa",
                    color: "#F97316",
                },
                {
                    id: 2,
                    initials: "AD",
                    name: "Aminata Diallo",
                    specialty: "Électricienne",
                    rating: 4.8,
                    reviews: 24,
                    zone: "Porto-Novo",
                    color: "#8b5cf6",
                },
                {
                    id: 3,
                    initials: "PH",
                    name: "Pascal Hounkpe",
                    specialty: "Menuisier",
                    rating: 4.7,
                    reviews: 52,
                    zone: "Parakou",
                    color: "#22c55e",
                },
                {
                    id: 4,
                    initials: "MO",
                    name: "Mariam Ouédraogo",
                    specialty: "Peintre",
                    rating: 4.6,
                    reviews: 17,
                    zone: "Calavi",
                    color: "#f59e0b",
                },
            ],
        };
    },

    computed: {
        client() {
            const p = this.clientProfile;
            const full = `${p.first_name} ${p.last_name}`.trim();
            return {
                ...p,
                display_name:
                    p.account_type === "company" && p.company_name
                        ? p.company_name
                        : full,
                initials:
                    (
                        (p.first_name?.[0] ?? "") + (p.last_name?.[0] ?? "")
                    ).toUpperCase() || "CL",
                email: this.user.email,
            };
        },

        greeting() {
            const h = new Date().getHours();
            if (h < 12) return "Bonjour";
            if (h < 18) return "Bon après-midi";
            return "Bonsoir";
        },

        kpis() {
            const active = [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
            return [
                {
                    icon: "📋",
                    label: "Missions totales",
                    value: this.missions.length,
                    color: "",
                },
                {
                    icon: "🔄",
                    label: "En cours",
                    value: this.missions.filter((m) =>
                        active.includes(m.status),
                    ).length,
                    color: "orange",
                },
                {
                    icon: "⏳",
                    label: "En attente",
                    value: this.missions.filter((m) => m.status === "pending")
                        .length,
                    color: "",
                },
                {
                    icon: "✅",
                    label: "Terminées",
                    value: this.missions.filter((m) =>
                        ["completed", "closed"].includes(m.status),
                    ).length,
                    color: "green",
                },
            ];
        },

        filteredMissions() {
            if (this.tab === "all") return this.missions;
            const activeStatuses = [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
            if (this.tab === "active")
                return this.missions.filter((m) =>
                    activeStatuses.includes(m.status),
                );
            if (this.tab === "pending")
                return this.missions.filter((m) => m.status === "pending");
            if (this.tab === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                );
            if (this.tab === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled");
            return this.missions;
        },

        tabLabel() {
            return (
                this.tabs
                    .find((t) => t.key === this.tab)
                    ?.label?.toLowerCase() ?? ""
            );
        },
    },

    methods: {
        // ── Chargement des missions ─────────────────────────────
        async fetchMissions() {
            this.missionsLoading = true;
            this.missionsError = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                // Laravel paginate → data.data, sinon tableau direct
                this.missions = Array.isArray(data) ? data : (data.data ?? []);
            } catch (e) {
                this.missionsError =
                    "Impossible de charger les missions. Vérifiez votre connexion.";
                console.error("[ClientDashboard] fetchMissions error:", e);
            } finally {
                this.missionsLoading = false;
            }
        },

        // ── Créer une mission ───────────────────────────────────
        openNewMission() {
            this.newMissionForm = {
                location_type: "residential",
                service: "",
                description: "",
                address: this.clientProfile.address ?? "",
                latitude: null,
                longitude: null,
            };
            this.missionError = "";
            this.geoOk = false;
            this.geoBlocked = false;
            this.showNewMission = true;
        },

        // ── Géolocalisation ──────────────────────────────────────
        askGeoConsent() {
            if (this.geoOk) return;
            this.showGeoConsent = true;
        },

        geolocate() {
            this.showGeoConsent = false;
            if (!navigator.geolocation) {
                this.geoBlocked = true;
                return;
            }
            this.geoLoading = true;
            this.geoBlocked = false;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.newMissionForm.latitude = pos.coords.latitude;
                    this.newMissionForm.longitude = pos.coords.longitude;
                    this.geoLoading = false;
                    this.geoOk = true;
                },
                (err) => {
                    this.geoLoading = false;
                    this.geoBlocked = err.code === 1;
                },
                { timeout: 10000, maximumAge: 60000 },
            );
        },

        resetGeo() {
            this.newMissionForm.latitude = null;
            this.newMissionForm.longitude = null;
            this.geoOk = false;
            this.geoBlocked = false;
        },

        async submitMission() {
            if (!this.newMissionForm.service) {
                this.missionError =
                    "Veuillez sélectionner un type de prestation.";
                return;
            }
            if (this.newMissionForm.description.trim().length < 20) {
                this.missionError =
                    "La description doit faire au moins 20 caractères.";
                return;
            }
            if (!this.newMissionForm.address.trim()) {
                this.missionError = "L'adresse d'intervention est obligatoire.";
                return;
            }
            this.missionError = "";
            this.loading = true;

            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const res = await fetch(this.routes.missions_store, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(this.newMissionForm),
                });

                const data = await res.json();

                if (!res.ok) {
                    if (res.status === 422) {
                        const firstError = Object.values(
                            data.errors ?? {},
                        )[0]?.[0];
                        this.missionError =
                            firstError ?? "Erreur de validation.";
                    } else {
                        this.missionError =
                            data.message ?? "Une erreur est survenue.";
                    }
                    return;
                }

                // Ajouter la nouvelle mission en tête de liste
                this.missions.unshift(data.mission);
                this.showNewMission = false;
                this.showToast(
                    "Mission publiée ! Un prestataire va être attribué.",
                    "success",
                );
            } catch (e) {
                this.missionError = "Erreur réseau. Veuillez réessayer.";
                console.error("[ClientDashboard] submitMission error:", e);
            } finally {
                this.loading = false;
            }
        },

        // ── Transitions de statut ───────────────────────────────

        async updateMissionStatus(mission, status, extra = {}) {
            this.actionLoading = true;
            try {
                const url = this.routes.missions_status.replace(
                    "{id}",
                    mission.id,
                );
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ status, ...extra }),
                });

                const data = await res.json();
                if (!res.ok) {
                    this.showToast(
                        data.message ?? "Erreur lors de la mise à jour.",
                        "error",
                    );
                    return;
                }

                // Mettre à jour la mission dans la liste locale
                const idx = this.missions.findIndex((m) => m.id === mission.id);
                if (idx !== -1) this.missions.splice(idx, 1, data.mission);

                // Mettre à jour la modal si ouverte
                if (this.activeMission?.id === mission.id) {
                    this.activeMission = data.mission;
                }

                return data.mission;
            } catch (e) {
                this.showToast("Erreur réseau.", "error");
                console.error("[ClientDashboard] updateStatus error:", e);
            } finally {
                this.actionLoading = false;
            }
        },

        async confirmCompletion(mission) {
            await this.updateMissionStatus(mission, "completed");
            this.showToast(
                "Fin des travaux confirmée. Vous pouvez maintenant procéder au paiement.",
                "success",
            );
        },

        async approveQuote(mission) {
            await this.updateMissionStatus(mission, "order_placed");
            this.showToast(
                "Devis approuvé ! L'intervention va démarrer.",
                "success",
            );
        },

        async rejectQuote(mission) {
            const reason = prompt("Motif du refus (optionnel) :");
            // On repasse à quote_submitted avec le motif — le prestataire verra le refus
            await this.updateMissionStatus(mission, "quote_submitted", {
                reported_issue: reason ?? "",
            });
            this.showToast("Devis refusé. Le prestataire en sera informé.", "");
        },

        signalProblem() {
            this.wip("Signaler un problème");
        },

        // ── Helpers d'affichage ─────────────────────────────────
        openMission(m) {
            this.activeMission = m;
        },

        countByTab(key) {
            if (key === "all") return this.missions.length;
            const activeStatuses = [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
            if (key === "active")
                return this.missions.filter((m) =>
                    activeStatuses.includes(m.status),
                ).length;
            if (key === "pending")
                return this.missions.filter((m) => m.status === "pending")
                    .length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },

        badgeClass(status) {
            const map = {
                pending: "pending",
                assigned: "active",
                accepted: "active",
                contact_made: "active",
                on_the_way: "active",
                tracking: "active",
                in_progress: "active",
                quote_submitted: "warning",
                order_placed: "warning",
                awaiting_confirm: "warning",
                completed: "done",
                closed: "done",
                cancelled: "cancelled",
            };
            return map[status] ?? "";
        },

        serviceIcon(service) {
            const icons = {
                plomberie: "🔧",
                electricite: "⚡",
                menuiserie: "🪵",
                ferronnerie: "⚙️",
                frigoriste: "❄️",
                mecanique: "🚗",
                nettoyage: "🧹",
                peinture: "🎨",
                maintenance: "🛠️",
            };
            return icons[service] ?? "🔨";
        },

        formatDate(iso) {
            if (!iso) return "—";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },

        formatPrice(amount) {
            if (!amount) return "—";
            return new Intl.NumberFormat("fr-FR").format(amount) + " FCFA";
        },

        wip(feature) {
            this.wipFeature = feature;
            this.wipVisible = true;
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },

        // ── Traduction statut en français ────────────────────────
        // Utilise status_label si l'API le retourne, sinon fallback local.
        // Corrige le problème "pending" affiché ou qui disparaît au rechargement.
        labelOf(mission) {
            if (
                mission.status_label &&
                mission.status_label !== mission.status
            ) {
                return mission.status_label;
            }
            const map = {
                pending: "En attente",
                assigned: "Assignée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi en cours",
                in_progress: "Intervention en cours",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "En att. de confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return map[mission.status] ?? mission.status;
        },

        // ── "Il y a X" en français (sans dépendre de Laravel) ───
        agoFr(isoDate) {
            if (!isoDate) return "";
            const diff = Math.floor((Date.now() - new Date(isoDate)) / 1000);
            if (diff < 60) return "À l'instant";
            if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
            if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
            if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;
            return new Date(isoDate).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
            });
        },

        // ── Notifications ────────────────────────────────────────
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.notifications = (data.notifications ?? []).map((n) => ({
                    ...n,
                    ago: this.agoFr(n.created_at),
                }));
                this.unreadCount = data.unread_count ?? 0;
            } catch {
                /* silencieux */
            } finally {
                this.notifLoading = false;
            }
        },

        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },

        async openNotif(n) {
            if (!n.read) {
                const url = this.routes.notifications_read.replace(
                    "{id}",
                    n.id,
                );
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                });
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            this.notifOpen = false;
            if (n.url) window.location.href = n.url;
        },

        async markAllRead() {
            const csrf = document.querySelector(
                'meta[name="csrf-token"]',
            )?.content;
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications.forEach((n) => (n.read = true));
            this.unreadCount = 0;
        },

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },
    },

    mounted() {
        this.newMissionForm.address = this.clientProfile.address ?? "";

        this.fetchMissions();
        this.fetchNotifications();

        // Polling notifications toutes les 30s
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000,
        );

        // Fermer dropdown au clic extérieur
        document.addEventListener("click", this.handleClickOutside);

        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
    },

    beforeUnmount() {
        clearInterval(this.notifInterval);
        document.removeEventListener("click", this.handleClickOutside);
    },
};
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════
   Variables
══════════════════════════════════════════════════════════════ */
:root {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #ffffff;
    --cr: #faf7f5;
}

.cd-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* TOPBAR */
.cd-topbar {
    background: var(--wh);
    border-bottom: 2px solid var(--grl);
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    position: sticky;
    top: 0;
    z-index: 40;
    gap: 10px;
}
@media (min-width: 600px) {
    .cd-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.cd-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.cd-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.cd-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cd-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: none;
}
@media (min-width: 480px) {
    .cd-page-sub {
        display: block;
    }
}
.cd-page-sub strong {
    color: var(--dk);
}

/* AVATAR */
.cd-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
}

/* BURGER */
.ad-burger {
    background: none;
    border: none;
    cursor: pointer;
    flex-direction: column;
    gap: 5px;
    padding: 4px;
    flex-shrink: 0;
    display: flex;
}
.ad-burger span {
    display: block;
    width: 20px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
    transition: all 0.2s;
}

/* CONTENT */
.cd-content {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1280px;
    margin: 0 auto;
    width: 100%;
}
@media (min-width: 600px) {
    .cd-content {
        padding: 24px;
    }
}

/* BANNER */
.cd-banner-company {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 12px 18px;
    font-size: 13.5px;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.cd-banner-badge {
    background: #dbeafe;
    border-radius: 99px;
    padding: 2px 12px;
    font-size: 12px;
    font-weight: 600;
}

/* KPIs */
.cd-kpis {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 768px) {
    .cd-kpis {
        grid-template-columns: repeat(4, 1fr);
    }
}
.cd-kpi {
    background: var(--wh);
    border-radius: 14px;
    padding: 18px;
    border: 1.5px solid var(--grl);
}
.cd-kpi.orange {
    border-color: #fed7aa;
    background: #fff7ed;
}
.cd-kpi.green {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.cd-kpi-icon {
    font-size: 22px;
    margin-bottom: 6px;
}
.cd-kpi-val {
    font-size: 28px;
    font-weight: 800;
    color: var(--dk);
}
.cd-kpi-label {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}

/* SKELETON */
.cd-skeleton-val {
    display: inline-block;
    width: 48px;
    height: 28px;
    background: var(--grl);
    border-radius: 6px;
    animation: cd-pulse 1.4s ease infinite;
}
.cd-skeleton-row {
    height: 56px;
    background: var(--grl);
    border-radius: 10px;
    margin-bottom: 10px;
    animation: cd-pulse 1.4s ease infinite;
}
@keyframes cd-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}

/* MID GRID */
.cd-mid-grid {
    display: grid;
    gap: 20px;
}
@media (min-width: 1024px) {
    .cd-mid-grid {
        grid-template-columns: 1fr 320px;
    }
}
.cd-side-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* CARD */
.cd-card {
    background: var(--wh);
    border-radius: 16px;
    border: 1.5px solid var(--grl);
    overflow: hidden;
}
.cd-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px 0;
}
.cd-card-header h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}

/* TABS */
.cd-tabs {
    display: flex;
    gap: 4px;
    padding: 12px 18px 0;
    flex-wrap: wrap;
}
.cd-tab {
    background: none;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    color: var(--gr);
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.cd-tab.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.cd-tab-count {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 11px;
}
.cd-tab.active .cd-tab-count {
    background: rgba(255, 255, 255, 0.3);
}

/* ALERT ERROR */
.cd-alert-error {
    margin: 16px 18px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* MISSION LIST */
.cd-loading {
    padding: 16px 18px;
}
.cd-mission-list {
    padding: 8px 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cd-mission-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.15s;
    gap: 10px;
}
.cd-mission-item:hover {
    background: #faf7f5;
}
.cd-mission-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.cd-mission-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cd-mission-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
}
.cd-mission-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-mission-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.cd-mission-price {
    font-size: 12px;
    color: var(--gr);
    font-weight: 600;
}

/* EMPTY */
.cd-empty {
    padding: 40px 20px;
    text-align: center;
}
.cd-empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}
.cd-empty-title {
    font-size: 14px;
    color: var(--gr);
    font-weight: 600;
}

/* BADGES */
.cd-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.cd-badge.pending {
    background: #fef9c3;
    color: #a16207;
}
.cd-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.cd-badge.warning {
    background: #fff7ed;
    color: #c2410c;
}
.cd-badge.done {
    background: #dcfce7;
    color: #15803d;
}
.cd-badge.cancelled {
    background: #f1f5f9;
    color: #64748b;
}

/* PROFIL */
.cd-profile-info {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
}
.cd-profile-av {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
}
.cd-profile-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.cd-profile-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-account-type {
    padding: 0 18px 16px;
}
.cd-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}
.cd-type-badge.individual {
    background: #f0fdf4;
    color: #15803d;
}
.cd-type-badge.company {
    background: #eff6ff;
    color: #1d4ed8;
}

/* QUICK ACTIONS */
.cd-quick-actions {
    padding: 12px 18px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cd-quick-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--cr);
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: all 0.18s;
    text-align: left;
}
.cd-quick-btn:hover {
    border-color: var(--or);
    background: #fff8f5;
}

/* CONTRACTORS GRID */
.cd-contractors-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 14px 18px 18px;
}
@media (min-width: 768px) {
    .cd-contractors-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
.cd-contractor-card {
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.18s;
}
.cd-contractor-card:hover {
    border-color: var(--or);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
}
.cd-contractor-av {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    margin: 0 auto 8px;
}
.cd-contractor-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.cd-contractor-spec {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-contractor-rating {
    font-size: 12px;
    color: var(--or);
    font-weight: 700;
    margin-top: 4px;
}
.cd-contractor-rating span {
    color: var(--gr);
    font-weight: 500;
}
.cd-contractor-zone {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}

/* STEP BAR */
.cd-step-bar {
    background: var(--grl);
    height: 6px;
    border-radius: 99px;
    overflow: hidden;
    margin: 16px 0 4px;
}
.cd-step-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--or), var(--or2));
    transition: width 0.4s ease;
}
.cd-step-label {
    font-size: 12px;
    color: var(--grm);
    font-weight: 600;
    margin-bottom: 12px;
}

/* ACTION BLOCKS */
.cd-action-block {
    background: #faf7f5;
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 14px 16px;
    margin-top: 14px;
}
.cd-action-quote {
    background: #fff8f5;
    border-color: #fed7aa;
}
.cd-action-pay {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.cd-action-block p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.5;
    margin-bottom: 12px;
}
.cd-action-block strong {
    color: var(--dk);
}
.cd-action-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* FORM */
.cd-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
}
.cd-field label {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.req {
    color: var(--or);
}
.cd-input {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid var(--grl);
    border-radius: 9px;
    font-size: 14px;
    font-family: "Poppins", sans-serif;
    outline: none;
    transition: border-color 0.2s;
    color: var(--dk);
    background: var(--cr);
    box-sizing: border-box;
}
.cd-input:focus {
    border-color: var(--or);
    background: #fff;
}
.cd-textarea {
    min-height: 90px;
    resize: vertical;
}
.cd-char-count {
    font-size: 11.5px;
    color: var(--grm);
    text-align: right;
    margin-top: 3px;
}
.cd-char-count.warn {
    color: #f59e0b;
}
.cd-err {
    font-size: 12px;
    color: #ef4444;
    margin-top: 2px;
}

/* RADIO ROW */
.cd-radio-row {
    display: flex;
    gap: 10px;
}
.cd-radio-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
    color: var(--dk);
}
.cd-radio-opt input {
    accent-color: var(--or);
}
.cd-radio-opt.selected {
    border-color: var(--or);
    background: #fff8f5;
}

/* BUTTONS */
.cd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
}
.cd-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.cd-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.cd-btn-orange:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.cd-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.cd-btn-ghost:hover {
    background: #d5c9c0;
}
.cd-btn-red {
    background: #ef4444;
    color: #fff;
}
.cd-btn-red:hover {
    background: #dc2626;
}
.cd-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}

/* SPINNER */
.cd-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cd-spin 0.7s linear infinite;
}
@keyframes cd-spin {
    to {
        transform: rotate(360deg);
    }
}

/* MODAL */
.cd-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.cd-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 520px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: cd-slide-up 0.25s ease;
}
@keyframes cd-slide-up {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.cd-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 24px 16px;
    border-bottom: 2px solid var(--grl);
    position: sticky;
    top: 0;
    background: var(--wh);
    border-radius: 18px 18px 0 0;
    z-index: 1;
}
.cd-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.cd-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.cd-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.cd-modal-body {
    padding: 20px 24px;
    flex: 1;
}
.cd-modal-footer {
    padding: 14px 24px;
    border-top: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
    flex-wrap: wrap;
}
.cd-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13.5px;
    gap: 12px;
}
.cd-detail-row:last-child {
    border-bottom: none;
}
.cd-detail-row span:first-child {
    color: var(--gr);
    flex-shrink: 0;
}
.cd-detail-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}

/* WIP */
.cd-wip-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 13, 11, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: cd-fade-in 0.2s ease;
}
@keyframes cd-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.cd-wip-modal {
    background: #fff;
    border-radius: 20px;
    padding: 36px 30px;
    max-width: 380px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: cd-slide-up 0.25s ease;
}
.cd-wip-icon {
    font-size: 48px;
    margin-bottom: 14px;
    animation: cd-bounce 0.6s ease infinite alternate;
}
@keyframes cd-bounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-6px);
    }
}
.cd-wip-modal h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 8px;
}
.cd-wip-feature {
    display: inline-block;
    background: var(--or3);
    color: var(--or);
    border: 1.5px solid var(--am);
    border-radius: 99px;
    padding: 3px 14px;
    font-size: 12.5px;
    font-weight: 700;
    margin-bottom: 10px;
}
.cd-wip-modal p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.6;
    margin-bottom: 20px;
}

/* TOASTS */
.cd-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.cd-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: cd-slide-up 0.3s ease;
}
.cd-toast.success {
    background: #16a34a;
}
.cd-toast.error {
    background: #dc2626;
}

/* ── Notifications cloche ── */
.cd-notif-wrap {
    position: relative;
}
.cd-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    transition: color 0.18s;
}
.cd-notif-btn:hover {
    color: var(--or);
}
.cd-notif-btn svg {
    width: 22px;
    height: 22px;
}
.cd-notif-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 99px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
}
.cd-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
    max-height: 420px;
    overflow-y: auto;
}
.cd-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    position: sticky;
    top: 0;
    background: var(--wh);
}
.cd-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.cd-notif-loading,
.cd-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.cd-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.cd-notif-item:last-child {
    border-bottom: none;
}
.cd-notif-item:hover {
    background: #faf7f5;
}
.cd-notif-item.unread {
    background: #fff8f5;
}
.cd-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.cd-notif-content {
    flex: 1;
    min-width: 0;
}
.cd-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.cd-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.cd-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

/* ── Géolocalisation ── */
.cd-btn-geo {
    display: flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: 1.5px dashed var(--grm);
    border-radius: 10px;
    padding: 11px 16px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    margin-bottom: 14px;
    width: 100%;
    justify-content: center;
}
.cd-btn-geo:hover:not(:disabled) {
    border-color: var(--or);
    color: var(--or);
}
.cd-btn-geo:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.cd-spinner-sm {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(249, 115, 22, 0.3);
    border-top-color: var(--or);
    border-radius: 50%;
    animation: cd-spin 0.7s linear infinite;
    flex-shrink: 0;
}
@keyframes cd-spin {
    to {
        transform: rotate(360deg);
    }
}
.cd-geo-confirmed {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 10px;
    padding: 11px 14px;
    margin-bottom: 14px;
}
.cd-geo-ok-title {
    font-size: 13px;
    font-weight: 700;
    color: #15803d;
}
.cd-geo-ok-sub {
    font-size: 11.5px;
    color: #16a34a;
    margin-top: 2px;
}
.cd-geo-reset {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    color: #16a34a;
    font-size: 15px;
    padding: 2px 6px;
    border-radius: 5px;
}
.cd-geo-reset:hover {
    background: #dcfce7;
}
.cd-geo-blocked {
    font-size: 12.5px;
    color: #92400e;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
}

/* ── Modal géo consentement ── */
.cd-geo-reasons {
    list-style: none;
    padding: 0;
    margin: 0 0 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cd-geo-reasons li {
    font-size: 13.5px;
    color: var(--gr);
    padding: 8px 12px;
    background: #faf7f5;
    border-radius: 8px;
}
.cd-geo-privacy-note {
    font-size: 12.5px;
    color: var(--grm);
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 10px 14px;
    line-height: 1.5;
}
</style>
