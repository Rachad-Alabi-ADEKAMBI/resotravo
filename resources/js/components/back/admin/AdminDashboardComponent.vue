<template>
    <div class="amis-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="amis-topbar">
            <div class="amis-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="amis-page-title">Tableau de bord</div>
                    <div class="amis-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong> —
                        {{ today }}
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <!-- Notifications -->
                <div class="amis-notif-wrap" ref="notifWrap">
                    <button class="amis-notif-btn" @click="toggleNotif">
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
                        <span class="amis-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
                    </button>
                    <div class="amis-notif-dropdown" v-if="notifOpen">
                        <div class="amis-notif-header">
                            <span>Notifications</span>
                            <button
                                class="amis-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="amis-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="amis-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="amis-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="amis-notif-dot" v-if="!n.read"></div>
                            <div class="amis-notif-content">
                                <div class="amis-notif-title">
                                    {{ n.title }}
                                </div>
                                <div class="amis-notif-body">{{ n.body }}</div>
                                <div class="amis-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="amis-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="adb-content">
            <!-- Loader global -->
            <div class="adb-loader" v-if="loading">
                <div class="adb-skeleton-grid">
                    <div class="adb-skel" v-for="n in 8" :key="n"></div>
                </div>
            </div>

            <template v-else>
                <!-- ══ LIGNE 1 : KPIs globaux ══ -->
                <div class="adb-kpi-row">
                    <a class="adb-kpi" :href="routes.missions_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #f97316,
                                    #ea580c
                                );
                            "
                        >
                            📋
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.missions_total }}
                            </div>
                            <div class="adb-kpi-lbl">Missions totales</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.contractors_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #3b82f6,
                                    #1d4ed8
                                );
                            "
                        >
                            👷
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.contractors_total }}
                            </div>
                            <div class="adb-kpi-lbl">Prestataires</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.clients_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #10b981,
                                    #059669
                                );
                            "
                        >
                            👤
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.clients_total }}
                            </div>
                            <div class="adb-kpi-lbl">Clients</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.talents_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #8b5cf6,
                                    #6d28d9
                                );
                            "
                        >
                            ⭐
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.talents_total }}
                            </div>
                            <div class="adb-kpi-lbl">Talents</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.tenders_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #f59e0b,
                                    #d97706
                                );
                            "
                        >
                            📝
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.tenders_total }}
                            </div>
                            <div class="adb-kpi-lbl">Appels d'offres</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.disputes_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #ef4444,
                                    #dc2626
                                );
                            "
                        >
                            ⚖️
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.disputes_open }}
                            </div>
                            <div class="adb-kpi-lbl">Litiges ouverts</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <a class="adb-kpi" :href="routes.consulting_page">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #14b8a6,
                                    #0d9488
                                );
                            "
                        >
                            💬
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ stats.tickets_pending }}
                            </div>
                            <div class="adb-kpi-lbl">Tickets en attente</div>
                        </div>
                        <div class="adb-kpi-arrow">→</div>
                    </a>
                    <div class="adb-kpi adb-kpi-revenue">
                        <div
                            class="adb-kpi-icon"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #22c55e,
                                    #16a34a
                                );
                            "
                        >
                            💰
                        </div>
                        <div class="adb-kpi-body">
                            <div class="adb-kpi-val">
                                {{ formatPrice(stats.revenue_total) }}
                            </div>
                            <div class="adb-kpi-lbl">Commissions totales</div>
                        </div>
                    </div>
                </div>

                <!-- ══ LIGNE 2 : Alertes urgentes ══ -->
                <div class="adb-alerts-row" v-if="hasAlerts">
                    <div
                        class="adb-alert adb-alert-red"
                        v-if="stats.validation_pending > 0"
                    >
                        <span class="adb-alert-icon">🛡️</span>
                        <div class="adb-alert-body">
                            <div class="adb-alert-title">
                                {{ stats.validation_pending }} dossier{{
                                    stats.validation_pending > 1 ? "s" : ""
                                }}
                                en attente de validation
                            </div>
                            <div class="adb-alert-sub">
                                Prestataires et talents en attente d'approbation
                            </div>
                        </div>
                        <a class="adb-alert-btn" :href="routes.validation_page"
                            >Traiter →</a
                        >
                    </div>
                    <div
                        class="adb-alert adb-alert-orange"
                        v-if="stats.missions_pending > 0"
                    >
                        <span class="adb-alert-icon">📋</span>
                        <div class="adb-alert-body">
                            <div class="adb-alert-title">
                                {{ stats.missions_pending }} mission{{
                                    stats.missions_pending > 1 ? "s" : ""
                                }}
                                sans prestataire
                            </div>
                            <div class="adb-alert-sub">
                                Missions en attente d'attribution manuelle
                            </div>
                        </div>
                        <a class="adb-alert-btn" :href="routes.missions_page"
                            >Voir →</a
                        >
                    </div>
                    <div
                        class="adb-alert adb-alert-red"
                        v-if="stats.disputes_open > 0"
                    >
                        <span class="adb-alert-icon">⚖️</span>
                        <div class="adb-alert-body">
                            <div class="adb-alert-title">
                                {{ stats.disputes_open }} litige{{
                                    stats.disputes_open > 1 ? "s" : ""
                                }}
                                ouvert{{ stats.disputes_open > 1 ? "s" : "" }}
                            </div>
                            <div class="adb-alert-sub">
                                Nécessitent une intervention admin
                            </div>
                        </div>
                        <a class="adb-alert-btn" :href="routes.disputes_page"
                            >Traiter →</a
                        >
                    </div>
                    <div
                        class="adb-alert adb-alert-blue"
                        v-if="stats.tickets_pending > 0"
                    >
                        <span class="adb-alert-icon">💬</span>
                        <div class="adb-alert-body">
                            <div class="adb-alert-title">
                                {{ stats.tickets_pending }} ticket{{
                                    stats.tickets_pending > 1 ? "s" : ""
                                }}
                                Allo Conseils en attente
                            </div>
                            <div class="adb-alert-sub">
                                Utilisateurs en attente d'un agent humain
                            </div>
                        </div>
                        <a class="adb-alert-btn" :href="routes.consulting_page"
                            >Répondre →</a
                        >
                    </div>
                    <div
                        class="adb-alert adb-alert-orange"
                        v-if="stats.tenders_pending > 0"
                    >
                        <span class="adb-alert-icon">📝</span>
                        <div class="adb-alert-body">
                            <div class="adb-alert-title">
                                {{ stats.tenders_pending }} appel{{
                                    stats.tenders_pending > 1 ? "s" : ""
                                }}
                                d'offres à valider
                            </div>
                            <div class="adb-alert-sub">
                                Publications en attente d'approbation
                            </div>
                        </div>
                        <a class="adb-alert-btn" :href="routes.tenders_page"
                            >Valider →</a
                        >
                    </div>
                </div>

                <!-- ══ LIGNE 3 : Grille principale 3 colonnes ══ -->
                <div class="adb-main-grid">
                    <!-- ── Missions récentes ── -->
                    <div class="adb-card">
                        <div class="adb-card-header">
                            <div class="adb-card-title">
                                📋 Missions récentes
                            </div>
                            <a
                                class="adb-card-link"
                                :href="routes.missions_page"
                                >Tout voir →</a
                            >
                        </div>
                        <div class="adb-card-stats">
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #f97316"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.missions_pending
                                }}</span>
                                <span class="adb-mini-lbl">En attente</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #3b82f6"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.missions_active
                                }}</span>
                                <span class="adb-mini-lbl">En cours</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #22c55e"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.missions_completed
                                }}</span>
                                <span class="adb-mini-lbl">Terminées</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #ef4444"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.missions_cancelled
                                }}</span>
                                <span class="adb-mini-lbl">Annulées</span>
                            </div>
                        </div>
                        <div class="adb-list" v-if="recentMissions.length > 0">
                            <div
                                class="adb-list-item"
                                v-for="m in recentMissions"
                                :key="m.id"
                            >
                                <div class="adb-list-icon">📋</div>
                                <div class="adb-list-body">
                                    <div class="adb-list-title">
                                        {{ m.service }}
                                    </div>
                                    <div class="adb-list-meta">
                                        {{ m.client_name }} · {{ m.created_at }}
                                    </div>
                                </div>
                                <span
                                    class="adb-status-chip"
                                    :class="missionStatusClass(m.status)"
                                    >{{ m.status_label }}</span
                                >
                            </div>
                        </div>
                        <div class="adb-empty" v-else>
                            Aucune mission récente
                        </div>
                    </div>

                    <!-- ── Prestataires & Validation ── -->
                    <div class="adb-card">
                        <div class="adb-card-header">
                            <div class="adb-card-title">👷 Prestataires</div>
                            <a
                                class="adb-card-link"
                                :href="routes.contractors_page"
                                >Tout voir →</a
                            >
                        </div>
                        <div class="adb-card-stats">
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #22c55e"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.contractors_approved
                                }}</span>
                                <span class="adb-mini-lbl">Certifiés</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #f97316"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.contractors_pending
                                }}</span>
                                <span class="adb-mini-lbl">En attente</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #3b82f6"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.contractors_available
                                }}</span>
                                <span class="adb-mini-lbl">Disponibles</span>
                            </div>
                            <div class="adb-mini-stat">
                                <span
                                    class="adb-mini-dot"
                                    style="background: #ef4444"
                                ></span>
                                <span class="adb-mini-val">{{
                                    stats.contractors_suspended
                                }}</span>
                                <span class="adb-mini-lbl">Suspendus</span>
                            </div>
                        </div>
                        <div
                            class="adb-card-header"
                            style="
                                padding-top: 14px;
                                border-top: 1px solid var(--grl);
                                margin-top: 4px;
                            "
                        >
                            <div class="adb-card-title">
                                🛡️ Dossiers à valider
                            </div>
                            <a
                                class="adb-card-link"
                                :href="routes.validation_page"
                                >Traiter →</a
                            >
                        </div>
                        <div class="adb-list" v-if="pendingDossiers.length > 0">
                            <div
                                class="adb-list-item"
                                v-for="d in pendingDossiers"
                                :key="d.id"
                            >
                                <div
                                    class="adb-list-avatar"
                                    :style="{ background: avatarColor(d.id) }"
                                >
                                    {{ initials(d.name) }}
                                </div>
                                <div class="adb-list-body">
                                    <div class="adb-list-title">
                                        {{ d.name }}
                                    </div>
                                    <div class="adb-list-meta">
                                        {{
                                            d.role === "contractor"
                                                ? "👷 Prestataire"
                                                : "⭐ Talent"
                                        }}
                                        · {{ d.created_at }}
                                    </div>
                                </div>
                                <span class="adb-status-chip adb-chip-warning"
                                    >En attente</span
                                >
                            </div>
                        </div>
                        <div class="adb-empty" v-else>
                            ✅ Aucun dossier en attente
                        </div>
                    </div>

                    <!-- ── Activité : litiges + tickets ── -->
                    <div class="adb-card">
                        <div class="adb-card-header">
                            <div class="adb-card-title">⚖️ Litiges récents</div>
                            <a
                                class="adb-card-link"
                                :href="routes.disputes_page"
                                >Tout voir →</a
                            >
                        </div>
                        <div class="adb-list" v-if="recentDisputes.length > 0">
                            <div
                                class="adb-list-item"
                                v-for="d in recentDisputes"
                                :key="d.id"
                            >
                                <div class="adb-list-icon">⚖️</div>
                                <div class="adb-list-body">
                                    <div class="adb-list-title">
                                        {{ d.subject }}
                                    </div>
                                    <div class="adb-list-meta">
                                        {{ d.mission_service }} ·
                                        {{ d.opened_ago }}
                                    </div>
                                </div>
                                <span
                                    class="adb-status-chip"
                                    :class="disputeStatusClass(d.status)"
                                    >{{ d.status_label }}</span
                                >
                            </div>
                        </div>
                        <div class="adb-empty" v-else>
                            ✅ Aucun litige en cours
                        </div>

                        <div
                            class="adb-card-header"
                            style="
                                padding-top: 14px;
                                border-top: 1px solid var(--grl);
                                margin-top: 4px;
                            "
                        >
                            <div class="adb-card-title">
                                💬 Tickets Allo Conseils
                            </div>
                            <a
                                class="adb-card-link"
                                :href="routes.consulting_page"
                                >Tout voir →</a
                            >
                        </div>
                        <div class="adb-list" v-if="recentTickets.length > 0">
                            <div
                                class="adb-list-item"
                                v-for="t in recentTickets"
                                :key="t.id"
                            >
                                <div class="adb-list-icon">💬</div>
                                <div class="adb-list-body">
                                    <div class="adb-list-title">
                                        {{ t.subject }}
                                    </div>
                                    <div class="adb-list-meta">
                                        {{ t.user_name }} ·
                                        {{ t.updated_at_ago }}
                                    </div>
                                </div>
                                <span
                                    class="adb-status-chip"
                                    :class="ticketStatusClass(t.status)"
                                    >{{ t.status_label }}</span
                                >
                            </div>
                        </div>
                        <div class="adb-empty" v-else>
                            ✅ Aucun ticket en attente
                        </div>
                    </div>
                </div>

                <!-- ══ LIGNE 4 : Accès rapides ══ -->
                <div class="adb-shortcuts">
                    <div class="adb-section-title">Accès rapides</div>
                    <div class="adb-shortcuts-grid">
                        <a class="adb-shortcut" :href="routes.validation_page">
                            <span class="adb-shortcut-icon">🛡️</span>
                            <span>Validation dossiers</span>
                            <span
                                class="adb-shortcut-badge"
                                v-if="stats.validation_pending > 0"
                                >{{ stats.validation_pending }}</span
                            >
                        </a>
                        <a
                            class="adb-shortcut"
                            :href="routes.accreditation_page"
                        >
                            <span class="adb-shortcut-icon">🏅</span>
                            <span>Accréditations</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.contractors_page">
                            <span class="adb-shortcut-icon">👷</span>
                            <span>Prestataires</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.clients_page">
                            <span class="adb-shortcut-icon">👤</span>
                            <span>Clients</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.talents_page">
                            <span class="adb-shortcut-icon">⭐</span>
                            <span>Talents</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.missions_page">
                            <span class="adb-shortcut-icon">📋</span>
                            <span>Missions</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.tenders_page">
                            <span class="adb-shortcut-icon">📝</span>
                            <span>Appels d'offres</span>
                            <span
                                class="adb-shortcut-badge"
                                v-if="stats.tenders_pending > 0"
                                >{{ stats.tenders_pending }}</span
                            >
                        </a>
                        <a class="adb-shortcut" :href="routes.services_page">
                            <span class="adb-shortcut-icon">🔧</span>
                            <span>Catalogue services</span>
                        </a>
                        <a class="adb-shortcut" :href="routes.consulting_page">
                            <span class="adb-shortcut-icon">💬</span>
                            <span>Allo Conseils</span>
                            <span
                                class="adb-shortcut-badge adb-badge-blue"
                                v-if="stats.tickets_pending > 0"
                                >{{ stats.tickets_pending }}</span
                            >
                        </a>
                        <a class="adb-shortcut" :href="routes.disputes_page">
                            <span class="adb-shortcut-icon">⚖️</span>
                            <span>Litiges</span>
                            <span
                                class="adb-shortcut-badge"
                                v-if="stats.disputes_open > 0"
                                >{{ stats.disputes_open }}</span
                            >
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- ══════════════ TOASTS ══════════════ -->
        <div class="amis-toast-container">
            <div
                class="amis-toast"
                v-for="t in toasts"
                :key="t.id"
                :class="t.type"
            >
                {{ t.message }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminDashboardComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            loading: true,

            stats: {
                // Missions
                missions_total: 0,
                missions_pending: 0,
                missions_active: 0,
                missions_completed: 0,
                missions_cancelled: 0,
                // Prestataires
                contractors_total: 0,
                contractors_approved: 0,
                contractors_pending: 0,
                contractors_available: 0,
                contractors_suspended: 0,
                // Clients
                clients_total: 0,
                // Talents
                talents_total: 0,
                // Dossiers validation
                validation_pending: 0,
                // Appels d'offres
                tenders_total: 0,
                tenders_pending: 0,
                // Litiges
                disputes_open: 0,
                // Tickets
                tickets_pending: 0,
                // Revenue
                revenue_total: 0,
            },

            recentMissions: [],
            pendingDossiers: [],
            recentDisputes: [],
            recentTickets: [],

            sidebarOpen: false,
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,
            toasts: [],
            toastId: 0,
        };
    },

    computed: {
        greeting() {
            const h = new Date().getHours();
            return h < 12 ? "Bonjour" : h < 18 ? "Bon après-midi" : "Bonsoir";
        },
        today() {
            return new Date().toLocaleDateString("fr-FR", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        },
        userInitials() {
            return (
                this.user.name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "AD"
            );
        },
        hasAlerts() {
            return (
                this.stats.validation_pending > 0 ||
                this.stats.missions_pending > 0 ||
                this.stats.disputes_open > 0 ||
                this.stats.tickets_pending > 0 ||
                this.stats.tenders_pending > 0
            );
        },
    },

    methods: {
        async fetchAll() {
            this.loading = true;
            const h = { Accept: "application/json" };
            try {
                const [
                    missions,
                    contractors,
                    clients,
                    talents,
                    disputes,
                    tickets,
                    tenders,
                    dossiers,
                ] = await Promise.all([
                    fetch(this.routes.missions_index, { headers: h }).then(
                        (r) => r.json(),
                    ),
                    fetch(this.routes.contractors_index, { headers: h }).then(
                        (r) => r.json(),
                    ),
                    fetch(this.routes.clients_index, { headers: h }).then((r) =>
                        r.json(),
                    ),
                    fetch(this.routes.talents_index, { headers: h }).then((r) =>
                        r.json(),
                    ),
                    fetch(this.routes.disputes_index, { headers: h }).then(
                        (r) => r.json(),
                    ),
                    fetch(this.routes.tickets_index, { headers: h }).then((r) =>
                        r.json(),
                    ),
                    fetch(this.routes.tenders_index, { headers: h }).then((r) =>
                        r.json(),
                    ),
                    fetch(this.routes.validation_index, { headers: h }).then(
                        (r) => r.json(),
                    ),
                ]);

                const missionsArr = missions.data ?? missions;
                const contractorsArr = contractors.data ?? contractors;
                const clientsArr = clients.data ?? clients;
                const talentsArr = talents.data ?? talents;
                const disputesArr = disputes.data ?? disputes;
                const ticketsArr = tickets.data ?? tickets;
                const tendersArr = tenders.data ?? tenders;
                const dossiersArr = dossiers.data ?? dossiers;

                // ── Missions ──
                this.stats.missions_total = missionsArr.length;
                this.stats.missions_pending = missionsArr.filter(
                    (m) => m.status === "pending",
                ).length;
                this.stats.missions_active = missionsArr.filter(
                    (m) =>
                        ![
                            "pending",
                            "completed",
                            "closed",
                            "cancelled",
                        ].includes(m.status),
                ).length;
                this.stats.missions_completed = missionsArr.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                ).length;
                this.stats.missions_cancelled = missionsArr.filter(
                    (m) => m.status === "cancelled",
                ).length;
                this.stats.revenue_total = missionsArr.reduce(
                    (s, m) => s + (parseFloat(m.commission) || 0),
                    0,
                );
                this.recentMissions = missionsArr.slice(0, 6);

                // ── Prestataires ──
                this.stats.contractors_total = contractorsArr.length;
                this.stats.contractors_approved = contractorsArr.filter(
                    (c) => c.status === "approved",
                ).length;
                this.stats.contractors_pending = contractorsArr.filter(
                    (c) => c.status === "pending",
                ).length;
                this.stats.contractors_available = contractorsArr.filter(
                    (c) => c.available,
                ).length;
                this.stats.contractors_suspended = contractorsArr.filter(
                    (c) => c.status === "suspended",
                ).length;

                // ── Clients ──
                this.stats.clients_total = clientsArr.length;

                // ── Talents ──
                this.stats.talents_total = talentsArr.length;

                // ── Validation dossiers en attente ──
                const pending = dossiersArr.filter
                    ? dossiersArr.filter((d) => d.status === "pending")
                    : [];
                this.stats.validation_pending = pending.length;
                this.pendingDossiers = pending.slice(0, 5).map((d) => ({
                    id: d.id,
                    name: d.name ?? d.contractor_name ?? d.talent_name ?? "—",
                    role: d.role ?? "contractor",
                    created_at: d.created_at_label ?? d.created_at ?? "—",
                }));

                // ── Appels d'offres ──
                this.stats.tenders_total = tendersArr.length;
                this.stats.tenders_pending = tendersArr.filter(
                    (t) => t.status === "pending",
                ).length;

                // ── Litiges ──
                this.stats.disputes_open = disputesArr.filter((d) =>
                    ["open", "in_progress"].includes(d.status),
                ).length;
                this.recentDisputes = disputesArr
                    .filter(
                        (d) =>
                            ![
                                "resolved_client",
                                "resolved_contractor",
                                "closed",
                            ].includes(d.status),
                    )
                    .slice(0, 4);

                // ── Tickets Allo Conseils ──
                this.stats.tickets_pending = ticketsArr.filter(
                    (t) => t.status === "pending_human",
                ).length;
                this.recentTickets = ticketsArr
                    .filter((t) => !["resolved", "closed"].includes(t.status))
                    .slice(0, 4);
            } catch (e) {
                console.error("Dashboard fetch error:", e);
                this.showToast(
                    "Erreur lors du chargement des données.",
                    "error",
                );
            } finally {
                this.loading = false;
            }
        },

        // ── Helpers statuts ──────────────────────────────────────────
        missionStatusClass(status) {
            const map = {
                pending: "adb-chip-warning",
                assigned: "adb-chip-info",
                accepted: "adb-chip-info",
                on_the_way: "adb-chip-info",
                in_progress: "adb-chip-info",
                quote_submitted: "adb-chip-info",
                order_placed: "adb-chip-info",
                awaiting_confirm: "adb-chip-info",
                completed: "adb-chip-success",
                closed: "adb-chip-success",
                cancelled: "adb-chip-danger",
            };
            return map[status] ?? "adb-chip-info";
        },

        disputeStatusClass(status) {
            return (
                {
                    open: "adb-chip-danger",
                    in_progress: "adb-chip-info",
                    resolved_client: "adb-chip-success",
                    resolved_contractor: "adb-chip-success",
                    closed: "adb-chip-neutral",
                }[status] ?? ""
            );
        },

        ticketStatusClass(status) {
            return (
                {
                    open: "adb-chip-warning",
                    pending_human: "adb-chip-danger",
                    in_progress: "adb-chip-info",
                    resolved: "adb-chip-success",
                    closed: "adb-chip-neutral",
                }[status] ?? ""
            );
        },

        formatPrice(amount) {
            if (!amount) return "0 FCFA";
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(amount)) +
                " FCFA"
            );
        },

        avatarColor(id) {
            const colors = [
                "linear-gradient(135deg,#F97316,#EA580C)",
                "linear-gradient(135deg,#3b82f6,#1d4ed8)",
                "linear-gradient(135deg,#10b981,#059669)",
                "linear-gradient(135deg,#8b5cf6,#6d28d9)",
            ];
            return colors[id % colors.length];
        },

        initials(name) {
            return (
                name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "??"
            );
        },

        // ── Notifications ────────────────────────────────────────────
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {
            } finally {
                this.notifLoading = false;
            }
        },
        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },
        async markAllRead() {
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                await fetch(this.routes.notifications_all, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                });
                this.notifications.forEach((n) => (n.read = true));
                this.unreadCount = 0;
            } catch {}
        },
        openNotif(n) {
            n.read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.notifOpen = false;
        },
        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            )
                this.notifOpen = false;
        },
        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 4000);
        },
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },
    },

    mounted() {
        this.fetchAll();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000,
        );
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
.amis-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #ffffff;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: #f8f4f0;
    min-height: 100vh;
}

/* ── Topbar ── */
.amis-topbar {
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
    .amis-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.amis-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.amis-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.amis-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.amis-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    display: none;
}
@media (min-width: 480px) {
    .amis-page-sub {
        display: block;
    }
}
.amis-page-sub strong {
    color: var(--dk);
}
.amis-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
}
.ad-burger {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 4px;
    flex-shrink: 0;
}
.ad-burger span {
    display: block;
    width: 20px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
}

/* ── Notifications ── */
.amis-notif-wrap {
    position: relative;
}
.amis-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    transition: color 0.18s;
    border-radius: 8px;
}
.amis-notif-btn:hover {
    color: var(--or);
}
.amis-notif-btn svg {
    width: 22px;
    height: 22px;
}
.amis-notif-badge {
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
.amis-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 300px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 100;
    overflow: hidden;
}
.amis-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
}
.amis-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.amis-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.amis-notif-item:hover,
.amis-notif-item.unread {
    background: var(--or3);
}
.amis-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.amis-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.amis-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.amis-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}
.amis-notif-loading,
.amis-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--gr);
    text-align: center;
}

/* ── Content ── */
.adb-content {
    padding: 20px 24px;
    max-width: 1400px;
    margin: 0 auto;
}
@media (max-width: 600px) {
    .adb-content {
        padding: 14px;
    }
}

/* ── Loader ── */
.adb-loader {
    padding: 20px 0;
}
.adb-skeleton-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
@media (max-width: 900px) {
    .adb-skeleton-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.adb-skel {
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(90deg, #f0e9e4 25%, #e8ddd4 50%, #f0e9e4 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* ── KPI row ── */
.adb-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 1200px) {
    .adb-kpi-row {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 800px) {
    .adb-kpi-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
.adb-kpi {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--dk);
    transition: all 0.2s;
    cursor: pointer;
}
.adb-kpi:hover {
    border-color: var(--or);
    box-shadow: 0 4px 18px rgba(249, 115, 22, 0.1);
    transform: translateY(-2px);
}
.adb-kpi-revenue {
    cursor: default;
}
.adb-kpi-revenue:hover {
    transform: none;
    border-color: var(--grl);
    box-shadow: none;
}
.adb-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.adb-kpi-body {
    flex: 1;
    min-width: 0;
}
.adb-kpi-val {
    font-size: 22px;
    font-weight: 900;
    color: var(--dk);
    line-height: 1;
}
.adb-kpi-lbl {
    font-size: 11px;
    color: var(--gr);
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.adb-kpi-arrow {
    font-size: 16px;
    color: var(--gr);
    flex-shrink: 0;
    transition: transform 0.2s;
}
.adb-kpi:hover .adb-kpi-arrow {
    transform: translateX(3px);
    color: var(--or);
}

/* ── Alerts ── */
.adb-alerts-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}
.adb-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1.5px solid;
    flex-wrap: wrap;
}
.adb-alert-red {
    background: #fef2f2;
    border-color: #fecaca;
}
.adb-alert-orange {
    background: #fff7ed;
    border-color: #fed7aa;
}
.adb-alert-blue {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.adb-alert-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.adb-alert-body {
    flex: 1;
    min-width: 160px;
}
.adb-alert-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.adb-alert-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.adb-alert-btn {
    background: var(--or);
    color: #fff;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.18s;
    flex-shrink: 0;
}
.adb-alert-btn:hover {
    background: var(--or2);
}

/* ── Main grid ── */
.adb-main-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 1100px) {
    .adb-main-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 700px) {
    .adb-main-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Card ── */
.adb-card {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.adb-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.adb-card-title {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
}
.adb-card-link {
    font-size: 12px;
    color: var(--or);
    font-weight: 600;
    text-decoration: none;
}
.adb-card-link:hover {
    text-decoration: underline;
}

/* ── Mini stats ── */
.adb-card-stats {
    display: flex;
    gap: 0;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 14px;
}
.adb-mini-stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border-right: 1px solid var(--grl);
}
.adb-mini-stat:last-child {
    border-right: none;
}
.adb-mini-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-bottom: 4px;
    flex-shrink: 0;
}
.adb-mini-val {
    font-size: 16px;
    font-weight: 900;
    color: var(--dk);
    line-height: 1;
}
.adb-mini-lbl {
    font-size: 10px;
    color: var(--gr);
    margin-top: 2px;
    text-align: center;
}

/* ── List ── */
.adb-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.adb-list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--grl);
}
.adb-list-item:last-child {
    border-bottom: none;
}
.adb-list-icon {
    font-size: 18px;
    flex-shrink: 0;
}
.adb-list-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    flex-shrink: 0;
}
.adb-list-body {
    flex: 1;
    min-width: 0;
}
.adb-list-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.adb-list-meta {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.adb-empty {
    text-align: center;
    font-size: 12.5px;
    color: var(--gr);
    padding: 12px 0;
    font-style: italic;
}

/* ── Status chips ── */
.adb-status-chip {
    font-size: 10.5px;
    font-weight: 700;
    border-radius: 6px;
    padding: 2px 8px;
    white-space: nowrap;
    flex-shrink: 0;
}
.adb-chip-warning {
    background: #fef9c3;
    color: #713f12;
}
.adb-chip-info {
    background: #dbeafe;
    color: #1e40af;
}
.adb-chip-success {
    background: #dcfce7;
    color: #166534;
}
.adb-chip-danger {
    background: #fee2e2;
    color: #991b1b;
}
.adb-chip-neutral {
    background: #f3f4f6;
    color: #6b7280;
}

/* ── Shortcuts ── */
.adb-section-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
}
.adb-shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
@media (max-width: 1000px) {
    .adb-shortcuts-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 700px) {
    .adb-shortcuts-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 480px) {
    .adb-shortcuts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.adb-shortcut {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 14px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--dk);
    font-size: 13px;
    font-weight: 600;
    transition: all 0.18s;
    position: relative;
}
.adb-shortcut:hover {
    border-color: var(--or);
    background: var(--or3);
    color: var(--or);
}
.adb-shortcut-icon {
    font-size: 18px;
    flex-shrink: 0;
}
.adb-shortcut-badge {
    background: #ef4444;
    color: #fff;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    margin-left: auto;
    flex-shrink: 0;
}
.adb-badge-blue {
    background: #3b82f6;
}

/* ── Toasts ── */
.amis-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 9999;
}
.amis-toast {
    background: var(--dk);
    color: #fff;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.18);
    animation: fadeUp 0.25s ease;
    max-width: 320px;
}
.amis-toast.success {
    background: #16a34a;
}
.amis-toast.error {
    background: #dc2626;
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
