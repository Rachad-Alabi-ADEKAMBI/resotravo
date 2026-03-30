<template>
    <div class="rv-wrap">
        <!-- ══════════════════════════════════
             TOPBAR
        ══════════════════════════════════ -->
        <div class="rv-topbar">
            <div class="rv-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="rv-page-title">Mes revenus</div>
                    <div class="rv-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="rv-topbar-right">
                <!-- Filtre période -->
                <select
                    class="rv-period-select"
                    v-model="period"
                    @change="fetchRevenus"
                >
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>

                <!-- Cloche notifications -->
                <div class="rv-notif-wrap" ref="notifWrap">
                    <button class="rv-notif-btn" @click="toggleNotif">
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
                        <span class="rv-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
                    </button>
                    <div class="rv-notif-dropdown" v-if="notifOpen">
                        <div class="rv-notif-header">
                            <span>Notifications</span>
                            <button
                                class="rv-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="rv-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="rv-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="rv-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="rv-notif-dot" v-if="!n.read"></div>
                            <div class="rv-notif-content">
                                <div class="rv-notif-title">{{ n.title }}</div>
                                <div class="rv-notif-body">{{ n.body }}</div>
                                <div class="rv-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rv-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════════════════════════
             CONTENU
        ══════════════════════════════════ -->
        <div class="rv-content">
            <!-- ── KPIs ── -->
            <div class="rv-kpis">
                <div class="rv-kpi rv-kpi--green">
                    <div class="rv-kpi-icon">💰</div>
                    <div class="rv-kpi-val">
                        <span v-if="loading" class="rv-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.total_revenus)
                        }}</span>
                    </div>
                    <div class="rv-kpi-label">Revenus totaux</div>
                </div>
                <div class="rv-kpi rv-kpi--orange">
                    <div class="rv-kpi-icon">✅</div>
                    <div class="rv-kpi-val">
                        <span v-if="loading" class="rv-skeleton"></span>
                        <span v-else>{{ stats.missions_terminees }}</span>
                    </div>
                    <div class="rv-kpi-label">Missions terminées</div>
                </div>
                <div class="rv-kpi rv-kpi--blue">
                    <div class="rv-kpi-icon">📊</div>
                    <div class="rv-kpi-val">
                        <span v-if="loading" class="rv-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.revenu_moyen)
                        }}</span>
                    </div>
                    <div class="rv-kpi-label">Revenu moyen / mission</div>
                </div>
                <div class="rv-kpi rv-kpi--purple">
                    <div class="rv-kpi-icon">⏳</div>
                    <div class="rv-kpi-val">
                        <span v-if="loading" class="rv-skeleton"></span>
                        <span v-else>{{ formatPrice(stats.en_attente) }}</span>
                    </div>
                    <div class="rv-kpi-label">En attente de paiement</div>
                </div>
            </div>

            <!-- ── TABLEAU MISSIONS ── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <h3>📋 Historique des missions</h3>
                    <div class="rv-search-wrap">
                        <span>🔍</span>
                        <input
                            class="rv-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher un service, client…"
                        />
                    </div>
                </div>

                <!-- Loader -->
                <div class="rv-loading" v-if="loading">
                    <div class="rv-skeleton-row" v-for="n in 5" :key="n"></div>
                </div>

                <!-- Erreur -->
                <div class="rv-alert-error" v-else-if="error">
                    ⚠️ {{ error }}
                    <button class="rv-btn-retry" @click="fetchRevenus">
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div class="rv-empty" v-else-if="filteredMissions.length === 0">
                    <div class="rv-empty-icon">📭</div>
                    <div class="rv-empty-title">Aucune mission trouvée</div>
                    <div class="rv-empty-sub">
                        Vos missions terminées apparaîtront ici.
                    </div>
                </div>

                <!-- Tableau -->
                <div class="rv-table-wrap" v-else>
                    <table class="rv-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in filteredMissions" :key="m.id">
                                <td data-label="Service">
                                    <div class="rv-service-name">
                                        {{ m.service }}
                                    </div>
                                </td>
                                <td data-label="Client" class="rv-client-name">
                                    {{ m.client_name ?? "—" }}
                                </td>
                                <td data-label="Date" class="rv-date">
                                    {{
                                        formatDate(
                                            m.completed_at ?? m.created_at,
                                        )
                                    }}
                                </td>
                                <td data-label="Montant">
                                    <span class="rv-amount">{{
                                        m.total_amount
                                            ? formatPrice(m.total_amount)
                                            : "—"
                                    }}</span>
                                </td>
                                <td data-label="Statut">
                                    <span
                                        class="rv-badge"
                                        :class="badgeClass(m.status)"
                                    >
                                        {{ statusLabel(m.status) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- fin rv-card -->

            <!-- Pagination — fixée en bas de page sur desktop -->
            <div class="rv-pagination" v-if="missions.length > 0">
                <div class="rv-pagination-info">
                    {{ paginationInfo }}
                </div>
                <div class="rv-pagination-controls">
                    <button
                        class="rv-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage = 1"
                        title="Première page"
                    >
                        «
                    </button>
                    <button
                        class="rv-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        title="Page précédente"
                    >
                        ‹
                    </button>
                    <template v-for="p in visiblePages" :key="p">
                        <span v-if="p === '...'" class="rv-page-dots">…</span>
                        <button
                            v-else
                            class="rv-page-btn"
                            :class="{
                                'rv-page-btn--active': p === currentPage,
                            }"
                            @click="currentPage = p"
                        >
                            {{ p }}
                        </button>
                    </template>
                    <button
                        class="rv-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                        title="Page suivante"
                    >
                        ›
                    </button>
                    <button
                        class="rv-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage = totalPages"
                        title="Dernière page"
                    >
                        »
                    </button>
                </div>
                <div class="rv-pagination-perpage">
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="rv-perpage-select"
                    >
                        <option :value="5">5 / page</option>
                        <option :value="10">10 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                    </select>
                </div>
            </div>
        </div>
        <!-- fin rv-content -->
    </div>
</template>

<script>
export default {
    name: "ContractorRevenusComponent",

    props: {
        user: {
            type: Object,
            required: true,
            // { id, name, email, role, status }
        },
        routes: {
            type: Object,
            default: () => ({}),
            // { revenus_index, notifications, notifications_all }
        },
    },

    data() {
        return {
            // ── Données ─────────────────────────────────
            missions: [],
            stats: {
                total_revenus: 0,
                missions_terminees: 0,
                revenu_moyen: 0,
                en_attente: 0,
            },
            loading: true,
            error: "",

            // ── Filtres ──────────────────────────────────
            period: "month",
            search: "",
            currentPage: 1,
            perPage: 10,

            // ── Notifications ────────────────────────────
            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
        };
    },

    computed: {
        greeting() {
            const h = new Date().getHours();
            if (h < 12) return "Bonjour";
            if (h < 18) return "Bon après-midi";
            return "Bonsoir";
        },

        userInitials() {
            return (this.user.name || "")
                .split(" ")
                .map((w) => w[0])
                .join("")
                .toUpperCase()
                .slice(0, 2);
        },

        filteredMissions() {
            const q = this.search.toLowerCase();
            const filtered = q
                ? this.missions.filter(
                      (m) =>
                          (m.service ?? "").toLowerCase().includes(q) ||
                          (m.client_name ?? "").toLowerCase().includes(q),
                  )
                : this.missions;

            const start = (this.currentPage - 1) * this.perPage;
            return filtered.slice(start, start + this.perPage);
        },

        totalPages() {
            const q = this.search.toLowerCase();
            const count = q
                ? this.missions.filter(
                      (m) =>
                          (m.service ?? "").toLowerCase().includes(q) ||
                          (m.client_name ?? "").toLowerCase().includes(q),
                  ).length
                : this.missions.length;
            return Math.max(1, Math.ceil(count / this.perPage));
        },

        paginationInfo() {
            const q = this.search.toLowerCase();
            const total = q
                ? this.missions.filter(
                      (m) =>
                          (m.service ?? "").toLowerCase().includes(q) ||
                          (m.client_name ?? "").toLowerCase().includes(q),
                  ).length
                : this.missions.length;
            const from = Math.min(
                (this.currentPage - 1) * this.perPage + 1,
                total,
            );
            const to = Math.min(this.currentPage * this.perPage, total);
            return total === 0
                ? "Aucun résultat"
                : `${from}–${to} sur ${total} mission${total > 1 ? "s" : ""}`;
        },

        visiblePages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7)
                return Array.from({ length: total }, (_, i) => i + 1);

            const pages = [];
            // Toujours afficher la 1ère page
            pages.push(1);

            if (current > 3) pages.push("...");

            // Pages autour de la page courante
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);

            if (current < total - 2) pages.push("...");

            // Toujours afficher la dernière page
            pages.push(total);

            return pages;
        },
    },

    mounted() {
        this.fetchRevenus();
        this.fetchNotifications();
        document.addEventListener("click", this.handleOutsideClick);
    },

    beforeUnmount() {
        document.removeEventListener("click", this.handleOutsideClick);
    },

    methods: {
        // ── Sidebar mobile ────────────────────────────────────────
        emitToggleSidebar() {
            const sidebar = document.getElementById("ab-sidebar");
            const isOpen = sidebar?.classList.contains("open");
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: !isOpen },
                }),
            );
        },

        // ── Fetch revenus ─────────────────────────────────────────
        async fetchRevenus() {
            this.loading = true;
            this.error = "";
            try {
                const url =
                    (this.routes.revenus_index ?? "/contractor/revenus") +
                    `?period=${this.period}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur serveur");
                const data = await res.json();

                this.missions = data.missions ?? [];
                this.stats = {
                    total_revenus: data.total_revenus ?? 0,
                    missions_terminees: data.missions_terminees ?? 0,
                    revenu_moyen: data.revenu_moyen ?? 0,
                    en_attente: data.en_attente ?? 0,
                };
                this.currentPage = 1;
            } catch (e) {
                this.error =
                    "Impossible de charger les revenus. Vérifiez votre connexion.";
            } finally {
                this.loading = false;
            }
        },

        // ── Formatage ─────────────────────────────────────────────
        formatPrice(val) {
            if (!val && val !== 0) return "—";
            return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "XOF",
                minimumFractionDigits: 0,
            }).format(val);
        },

        formatDate(dateStr) {
            if (!dateStr) return "—";
            return new Date(dateStr).toLocaleDateString("fr-FR", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        },

        statusLabel(status) {
            return (
                {
                    completed: "Terminée",
                    closed: "Clôturée",
                    pending: "En attente",
                    active: "En cours",
                    cancelled: "Annulée",
                }[status] ?? status
            );
        },

        badgeClass(status) {
            return (
                {
                    completed: "rv-badge--green",
                    closed: "rv-badge--green",
                    pending: "rv-badge--orange",
                    active: "rv-badge--blue",
                    cancelled: "rv-badge--red",
                }[status] ?? ""
            );
        },

        // ── Notifications ─────────────────────────────────────────
        async fetchNotifications() {
            if (!this.routes.notifications) return;
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.notifications = (data.notifications ?? data ?? []).map(
                    (n) => ({
                        ...n,
                        ago: this.timeAgo(n.created_at),
                    }),
                );
                this.unreadCount = this.notifications.filter(
                    (n) => !n.read,
                ).length;
            } catch (_) {
            } finally {
                this.notifLoading = false;
            }
        },

        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },

        async markAllRead() {
            if (!this.routes.notifications_all) return;
            const csrf =
                document.querySelector('meta[name="csrf-token"]')?.content ??
                "";
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications = this.notifications.map((n) => ({
                ...n,
                read: true,
            }));
            this.unreadCount = 0;
        },

        openNotif(n) {
            if (!n.read) {
                const csrf =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "";
                fetch(`/notifications/${n.id}/read`, {
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

        handleOutsideClick(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },

        timeAgo(dateStr) {
            if (!dateStr) return "";
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if (diff < 60) return "À l'instant";
            if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
            if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
            return `Il y a ${Math.floor(diff / 86400)} j`;
        },
    },
};
</script>

<style scoped>
/* ══════════════════════════════════════
   LAYOUT
══════════════════════════════════════ */
.rv-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* ══════════════════════════════════════
   TOPBAR
══════════════════════════════════════ */
.rv-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border-bottom: 1.5px solid #ede8e3;
    padding: 0 28px;
    height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
    flex-shrink: 0;
}
.rv-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.rv-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rv-page-title {
    font-size: 17px;
    font-weight: 700;
    color: #1c1412;
    line-height: 1.2;
}
.rv-page-sub {
    font-size: 12.5px;
    color: #8a7d78;
    margin-top: 1px;
}
.rv-page-sub strong {
    color: #4a3f3a;
}

/* Burger */
.ad-burger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 36px;
    height: 36px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 8px;
}
.ad-burger span {
    display: block;
    width: 20px;
    height: 2px;
    background: #4a3f3a;
    border-radius: 2px;
}
@media (max-width: 899px) {
    .ad-burger {
        display: flex !important;
    }
}

/* Filtre période */
.rv-period-select {
    height: 36px;
    border: 1.5px solid #e8ddd4;
    border-radius: 9px;
    padding: 0 12px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #4a3f3a;
    background: #faf8f6;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
}
.rv-period-select:focus {
    border-color: #f97316;
}

/* Avatar */
.rv-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    user-select: none;
}

/* Notifications */
.rv-notif-wrap {
    position: relative;
}
.rv-notif-btn {
    position: relative;
    background: none;
    border: none;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.15s;
    color: #4a3f3a;
}
.rv-notif-btn:hover {
    background: #fef3e2;
}
.rv-notif-btn svg {
    width: 20px;
    height: 20px;
}
.rv-notif-badge {
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
.rv-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: #fff;
    border: 1.5px solid #e8ddd4;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
    max-height: 420px;
    overflow-y: auto;
}
.rv-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #e8ddd4;
    font-size: 13px;
    font-weight: 700;
    color: #1c1412;
    position: sticky;
    top: 0;
    background: #fff;
}
.rv-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: #f97316;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.rv-notif-loading,
.rv-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: #4a3f3a;
}
.rv-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.rv-notif-item:last-child {
    border-bottom: none;
}
.rv-notif-item:hover {
    background: #faf7f5;
}
.rv-notif-item.unread {
    background: #fff8f5;
}
.rv-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f97316;
    flex-shrink: 0;
    margin-top: 5px;
}
.rv-notif-content {
    flex: 1;
    min-width: 0;
}
.rv-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: #1c1412;
}
.rv-notif-body {
    font-size: 12px;
    color: #4a3f3a;
    margin-top: 2px;
    line-height: 1.4;
}
.rv-notif-ago {
    font-size: 11px;
    color: #8a7d78;
    margin-top: 4px;
}

/* ══════════════════════════════════════
   CONTENU
══════════════════════════════════════ */
.rv-content {
    flex: 1;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── KPIs ── */
.rv-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.rv-kpi {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.rv-kpi-icon {
    font-size: 24px;
}
.rv-kpi-val {
    font-size: 22px;
    font-weight: 800;
    color: #1c1412;
    line-height: 1;
}
.rv-kpi-label {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
}

.rv-kpi--green {
    border-left: 4px solid #22c55e;
}
.rv-kpi--orange {
    border-left: 4px solid #f97316;
}
.rv-kpi--blue {
    border-left: 4px solid #3b82f6;
}
.rv-kpi--purple {
    border-left: 4px solid #8b5cf6;
}

/* Skeleton KPI */
.rv-skeleton {
    display: inline-block;
    width: 100px;
    height: 22px;
    background: linear-gradient(90deg, #ede8e3 25%, #f8f4f0 50%, #ede8e3 75%);
    background-size: 200% 100%;
    animation: rv-shimmer 1.2s infinite;
    border-radius: 6px;
}
@keyframes rv-shimmer {
    to {
        background-position: -200% 0;
    }
}

/* ── Carte tableau ── */
.rv-card {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 18px;
    overflow: hidden;
}
.rv-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1.5px solid #f5f0eb;
    gap: 16px;
    flex-wrap: wrap;
}
.rv-card-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
}

.rv-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #faf8f6;
    border: 1.5px solid #e8ddd4;
    border-radius: 9px;
    padding: 0 12px;
    height: 36px;
}
.rv-search {
    border: none;
    background: none;
    outline: none;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: #1c1412;
    width: 200px;
}
.rv-search::placeholder {
    color: #b5a9a3;
}

/* Skeleton rows */
.rv-loading {
    padding: 16px 22px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.rv-skeleton-row {
    height: 44px;
    background: linear-gradient(90deg, #f5f0eb 25%, #faf8f6 50%, #f5f0eb 75%);
    background-size: 200% 100%;
    animation: rv-shimmer 1.2s infinite;
    border-radius: 8px;
}

/* Erreur */
.rv-alert-error {
    margin: 20px 22px;
    padding: 14px 16px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    border-radius: 10px;
    font-size: 13.5px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 12px;
}
.rv-btn-retry {
    background: none;
    border: 1.5px solid #dc2626;
    color: #dc2626;
    border-radius: 7px;
    padding: 4px 12px;
    font-size: 12px;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    font-weight: 600;
}

/* Vide */
.rv-empty {
    padding: 48px 22px;
    text-align: center;
}
.rv-empty-icon {
    font-size: 40px;
    margin-bottom: 12px;
}
.rv-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
}
.rv-empty-sub {
    font-size: 13px;
    color: #8a7d78;
    margin-top: 4px;
}

/* ── Tableau ── */
.rv-table-wrap {
    overflow-x: auto;
}
.rv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.rv-table thead tr {
    background: #faf8f6;
}
.rv-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #8a7d78;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    border-bottom: 1.5px solid #f0ebe5;
}
.rv-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f5f0eb;
    color: #1c1412;
    vertical-align: middle;
}
.rv-table tbody tr:last-child td {
    border-bottom: none;
}
.rv-table tbody tr:hover td {
    background: #faf8f6;
}

.rv-service-name {
    font-weight: 600;
    color: #1c1412;
}
.rv-client-name {
    color: #4a3f3a;
}
.rv-date {
    color: #8a7d78;
    white-space: nowrap;
    font-size: 13px;
}
.rv-amount {
    font-weight: 700;
    color: #1c1412;
}

/* Badges statut */
.rv-badge {
    padding: 3px 11px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.rv-badge--green {
    background: #f0fdf4;
    color: #15803d;
}
.rv-badge--orange {
    background: #fff7ed;
    color: #ea580c;
}
.rv-badge--blue {
    background: #eff6ff;
    color: #1d4ed8;
}
.rv-badge--red {
    background: #fef2f2;
    color: #dc2626;
}

/* ── Tableau responsive (mobile) ── */
@media (max-width: 640px) {
    .rv-table thead {
        display: none;
    }

    .rv-table tbody tr {
        display: block;
        background: #fff;
        border: 1.5px solid #ede8e3;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 4px 0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    .rv-table tbody tr:last-child {
        margin-bottom: 0;
    }

    .rv-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-bottom: 1px solid #f5f0eb;
        font-size: 13px;
    }
    .rv-table td:last-child {
        border-bottom: none;
    }

    /* Data label à gauche */
    .rv-table td::before {
        content: attr(data-label);
        font-size: 11px;
        font-weight: 700;
        color: #8a7d78;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        flex-shrink: 0;
        margin-right: 12px;
    }

    .rv-table tbody tr:hover td {
        background: transparent;
    }
    .rv-table-wrap {
        overflow-x: unset;
    }
}

/* ── Pagination — sticky en bas sur desktop ── */
.rv-pagination {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #fff;
    border-top: 1.5px solid #ede8e3;
    box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 20px;
    flex-wrap: wrap;
    margin-top: auto;
}
.rv-pagination-info {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
    white-space: nowrap;
}
.rv-pagination-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}
.rv-page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border-radius: 8px;
    border: 1.5px solid #e8ddd4;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #4a3f3a;
    font-family: "Poppins", sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.rv-page-btn:hover:not(:disabled) {
    border-color: #f97316;
    color: #f97316;
    background: #fff7ed;
}
.rv-page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.rv-page-btn--active {
    background: #f97316 !important;
    border-color: #f97316 !important;
    color: #fff !important;
    font-weight: 700;
}
.rv-page-dots {
    font-size: 13px;
    color: #8a7d78;
    padding: 0 4px;
    user-select: none;
}
.rv-pagination-perpage {
    display: flex;
    align-items: center;
}
.rv-perpage-select {
    height: 32px;
    border: 1.5px solid #e8ddd4;
    border-radius: 8px;
    padding: 0 10px;
    font-family: "Poppins", sans-serif;
    font-size: 12.5px;
    font-weight: 600;
    color: #4a3f3a;
    background: #faf8f6;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
}
.rv-perpage-select:focus {
    border-color: #f97316;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .rv-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
    .rv-content {
        padding: 16px;
    }
    .rv-topbar {
        padding: 0 16px;
    }
}
@media (max-width: 560px) {
    .rv-kpis {
        grid-template-columns: 1fr;
    }
    .rv-period-select {
        display: none;
    }
}
</style>
