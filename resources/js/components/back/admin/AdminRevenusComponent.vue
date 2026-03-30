<template>
    <div class="ar-wrap">
        <!-- ══════════════════════════════════
             TOPBAR
        ══════════════════════════════════ -->
        <div class="ar-topbar">
            <div class="ar-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="ar-page-title">Revenus & Finances</div>
                    <div class="ar-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="ar-topbar-right">
                <!-- Filtre période -->
                <select
                    class="ar-period-select"
                    v-model="period"
                    @change="fetchRevenus"
                >
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>

                <!-- Export CSV -->
                <button
                    class="ar-btn-export"
                    @click="exportCSV"
                    title="Exporter en CSV"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    <span>Export CSV</span>
                </button>

                <!-- Cloche notifications -->
                <div class="ar-notif-wrap" ref="notifWrap">
                    <button class="ar-notif-btn" @click="toggleNotif">
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
                        <span class="ar-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
                    </button>
                    <div class="ar-notif-dropdown" v-if="notifOpen">
                        <div class="ar-notif-header">
                            <span>Notifications</span>
                            <button
                                class="ar-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="ar-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="ar-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="ar-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="ar-notif-dot" v-if="!n.read"></div>
                            <div class="ar-notif-content">
                                <div class="ar-notif-title">{{ n.title }}</div>
                                <div class="ar-notif-body">{{ n.body }}</div>
                                <div class="ar-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ar-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════════════════════════
             CONTENU
        ══════════════════════════════════ -->
        <div class="ar-content">
            <!-- ── KPIs ── -->
            <div class="ar-kpis">
                <div class="ar-kpi ar-kpi--green">
                    <div class="ar-kpi-icon">💰</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.volume_total)
                        }}</span>
                    </div>
                    <div class="ar-kpi-label">Volume total traité</div>
                </div>
                <div class="ar-kpi ar-kpi--orange">
                    <div class="ar-kpi-icon">🏦</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.commissions_percues)
                        }}</span>
                    </div>
                    <div class="ar-kpi-label">Commissions perçues (10%)</div>
                </div>
                <div class="ar-kpi ar-kpi--blue">
                    <div class="ar-kpi-icon">✅</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{ stats.missions_cloturees }}</span>
                    </div>
                    <div class="ar-kpi-label">Missions clôturées</div>
                </div>
                <div class="ar-kpi ar-kpi--purple">
                    <div class="ar-kpi-icon">📊</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.panier_moyen)
                        }}</span>
                    </div>
                    <div class="ar-kpi-label">Panier moyen / mission</div>
                </div>
                <div class="ar-kpi ar-kpi--red">
                    <div class="ar-kpi-icon">⏳</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{ formatPrice(stats.en_attente) }}</span>
                    </div>
                    <div class="ar-kpi-label">Paiements en attente</div>
                </div>
                <div class="ar-kpi ar-kpi--teal">
                    <div class="ar-kpi-icon">👷</div>
                    <div class="ar-kpi-val">
                        <span v-if="loading" class="ar-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.verses_prestataires)
                        }}</span>
                    </div>
                    <div class="ar-kpi-label">
                        Versés aux prestataires (90%)
                    </div>
                </div>
            </div>

            <!-- ── FILTRES AVANCÉS ── -->
            <div class="ar-filters-bar">
                <div class="ar-search-wrap">
                    <span>🔍</span>
                    <input
                        class="ar-search"
                        type="text"
                        v-model="search"
                        placeholder="Service, client, prestataire, ref…"
                    />
                </div>
                <div class="ar-filter-group">
                    <select class="ar-filter-select" v-model="filterRole">
                        <option value="">Tous les rôles</option>
                        <option value="client">Côté client</option>
                        <option value="contractor">Côté prestataire</option>
                    </select>
                    <select class="ar-filter-select" v-model="filterStatus">
                        <option value="">Tous les statuts</option>
                        <option value="closed">Clôturée</option>
                        <option value="completed">Terminée</option>
                        <option value="pending">En attente</option>
                        <option value="active">En cours</option>
                        <option value="cancelled">Annulée</option>
                    </select>
                </div>
            </div>

            <!-- ── TABLEAU TRANSACTIONS ── -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <h3>💳 Historique des transactions</h3>
                    <div class="ar-card-count" v-if="!loading">
                        {{ filteredCount }} transaction{{
                            filteredCount > 1 ? "s" : ""
                        }}
                    </div>
                </div>

                <!-- Loader -->
                <div class="ar-loading" v-if="loading">
                    <div class="ar-skeleton-row" v-for="n in 6" :key="n"></div>
                </div>

                <!-- Erreur -->
                <div class="ar-alert-error" v-else-if="error">
                    ⚠️ {{ error }}
                    <button class="ar-btn-retry" @click="fetchRevenus">
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div class="ar-empty" v-else-if="filteredMissions.length === 0">
                    <div class="ar-empty-icon">📭</div>
                    <div class="ar-empty-title">Aucune transaction trouvée</div>
                    <div class="ar-empty-sub">
                        Modifiez vos filtres ou la période sélectionnée.
                    </div>
                </div>

                <!-- Tableau -->
                <div class="ar-table-wrap" v-else>
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Réf. mission</th>
                                <th>Service</th>
                                <th>Client</th>
                                <th>Prestataire</th>
                                <th>Date</th>
                                <th>Montant total</th>
                                <th>Commission (10%)</th>
                                <th>Versé presta (90%)</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in filteredMissions" :key="m.id">
                                <td data-label="Réf.">
                                    <span class="ar-ref">#{{ m.id }}</span>
                                </td>
                                <td data-label="Service">
                                    <div class="ar-service-name">
                                        {{ m.service }}
                                    </div>
                                </td>
                                <td data-label="Client" class="ar-person-name">
                                    {{ m.client_name ?? "—" }}
                                </td>
                                <td
                                    data-label="Prestataire"
                                    class="ar-person-name"
                                >
                                    {{ m.contractor_name ?? "—" }}
                                </td>
                                <td data-label="Date" class="ar-date">
                                    {{
                                        formatDate(
                                            m.completed_at ?? m.created_at,
                                        )
                                    }}
                                </td>
                                <td data-label="Montant total">
                                    <span class="ar-amount">
                                        {{
                                            m.total_amount
                                                ? formatPrice(m.total_amount)
                                                : "—"
                                        }}
                                    </span>
                                </td>
                                <td data-label="Commission">
                                    <span
                                        class="ar-commission"
                                        v-if="m.total_amount"
                                    >
                                        {{ formatPrice(m.total_amount * 0.1) }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td data-label="Versé prestataire">
                                    <span
                                        class="ar-payout"
                                        v-if="m.total_amount"
                                    >
                                        {{ formatPrice(m.total_amount * 0.9) }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td data-label="Statut">
                                    <span
                                        class="ar-badge"
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
            <!-- fin ar-card -->

            <!-- Pagination sticky bas -->
            <div class="ar-pagination" v-if="allFilteredCount > 0">
                <div class="ar-pagination-info">{{ paginationInfo }}</div>
                <div class="ar-pagination-controls">
                    <button
                        class="ar-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage = 1"
                        title="Première page"
                    >
                        «
                    </button>
                    <button
                        class="ar-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        title="Page précédente"
                    >
                        ‹
                    </button>
                    <template v-for="p in visiblePages" :key="p">
                        <span v-if="p === '...'" class="ar-page-dots">…</span>
                        <button
                            v-else
                            class="ar-page-btn"
                            :class="{
                                'ar-page-btn--active': p === currentPage,
                            }"
                            @click="currentPage = p"
                        >
                            {{ p }}
                        </button>
                    </template>
                    <button
                        class="ar-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                        title="Page suivante"
                    >
                        ›
                    </button>
                    <button
                        class="ar-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage = totalPages"
                        title="Dernière page"
                    >
                        »
                    </button>
                </div>
                <div class="ar-pagination-perpage">
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="ar-perpage-select"
                    >
                        <option :value="10">10 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                        <option :value="100">100 / page</option>
                    </select>
                </div>
            </div>
        </div>
        <!-- fin ar-content -->
    </div>
</template>

<script>
export default {
    name: "AdminRevenusComponent",

    props: {
        user: {
            type: Object,
            required: true,
        },
        routes: {
            type: Object,
            default: () => ({}),
            // { revenus_index, notifications, notifications_all }
        },
    },

    data() {
        return {
            missions: [],
            stats: {
                volume_total: 0,
                commissions_percues: 0,
                missions_cloturees: 0,
                panier_moyen: 0,
                en_attente: 0,
                verses_prestataires: 0,
            },
            loading: true,
            error: "",
            period: "month",
            search: "",
            filterRole: "",
            filterStatus: "",
            currentPage: 1,
            perPage: 25,

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

        allFiltered() {
            const q = this.search.toLowerCase();
            return this.missions.filter((m) => {
                const matchSearch =
                    !q ||
                    (m.service ?? "").toLowerCase().includes(q) ||
                    (m.client_name ?? "").toLowerCase().includes(q) ||
                    (m.contractor_name ?? "").toLowerCase().includes(q) ||
                    String(m.id).includes(q);

                const matchStatus =
                    !this.filterStatus || m.status === this.filterStatus;

                // filterRole : pas de champ direct sur la mission, on filtre selon ce
                // que l'API renvoie (côté admin on a toutes les missions)
                // On garde juste le filtre search + status ici ; filterRole
                // est passé en paramètre API dans fetchRevenus.
                return matchSearch && matchStatus;
            });
        },

        allFilteredCount() {
            return this.allFiltered.length;
        },

        filteredCount() {
            return this.allFiltered.length;
        },

        filteredMissions() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.allFiltered.slice(start, start + this.perPage);
        },

        totalPages() {
            return Math.max(
                1,
                Math.ceil(this.allFiltered.length / this.perPage),
            );
        },

        paginationInfo() {
            const total = this.allFiltered.length;
            const from = Math.min(
                (this.currentPage - 1) * this.perPage + 1,
                total,
            );
            const to = Math.min(this.currentPage * this.perPage, total);
            return total === 0
                ? "Aucun résultat"
                : `${from}–${to} sur ${total} transaction${total > 1 ? "s" : ""}`;
        },

        visiblePages() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7)
                return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [1];
            if (current > 3) pages.push("...");
            const start = Math.max(2, current - 1);
            const end = Math.min(total - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (current < total - 2) pages.push("...");
            pages.push(total);
            return pages;
        },
    },

    watch: {
        search() {
            this.currentPage = 1;
        },
        filterRole() {
            this.currentPage = 1;
            this.fetchRevenus();
        },
        filterStatus() {
            this.currentPage = 1;
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
        emitToggleSidebar() {
            const sidebar = document.getElementById("ab-sidebar");
            const isOpen = sidebar?.classList.contains("open");
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: !isOpen },
                }),
            );
        },

        async fetchRevenus() {
            this.loading = true;
            this.error = "";
            try {
                const base = this.routes.revenus_index ?? "/admin/revenus/data";
                const url = `${base}?period=${this.period}&role=${this.filterRole}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur serveur");
                const data = await res.json();

                this.missions = data.missions ?? [];
                this.stats = {
                    volume_total: data.volume_total ?? 0,
                    commissions_percues: data.commissions_percues ?? 0,
                    missions_cloturees: data.missions_cloturees ?? 0,
                    panier_moyen: data.panier_moyen ?? 0,
                    en_attente: data.en_attente ?? 0,
                    verses_prestataires: data.verses_prestataires ?? 0,
                };
                this.currentPage = 1;
            } catch (e) {
                this.error =
                    "Impossible de charger les données financières. Vérifiez votre connexion.";
            } finally {
                this.loading = false;
            }
        },

        exportCSV() {
            const rows = this.allFiltered;
            if (!rows.length) return;

            const headers = [
                "Ref",
                "Service",
                "Client",
                "Prestataire",
                "Date",
                "Montant total",
                "Commission (10%)",
                "Versé presta (90%)",
                "Statut",
            ];
            const lines = rows.map((m) =>
                [
                    `#${m.id}`,
                    `"${(m.service ?? "").replace(/"/g, '""')}"`,
                    `"${(m.client_name ?? "").replace(/"/g, '""')}"`,
                    `"${(m.contractor_name ?? "").replace(/"/g, '""')}"`,
                    this.formatDate(m.completed_at ?? m.created_at),
                    m.total_amount ?? 0,
                    m.total_amount ? (m.total_amount * 0.1).toFixed(0) : 0,
                    m.total_amount ? (m.total_amount * 0.9).toFixed(0) : 0,
                    this.statusLabel(m.status),
                ].join(";"),
            );

            const csv = [headers.join(";"), ...lines].join("\n");
            const blob = new Blob(["\uFEFF" + csv], {
                type: "text/csv;charset=utf-8;",
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `resotravo_revenus_${this.period}_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        },

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
                    completed: "ar-badge--green",
                    closed: "ar-badge--green",
                    pending: "ar-badge--orange",
                    active: "ar-badge--blue",
                    cancelled: "ar-badge--red",
                }[status] ?? ""
            );
        },

        /* ── Notifications ── */
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
.ar-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* ── Topbar ── */
.ar-topbar {
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
.ar-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.ar-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ar-page-title {
    font-size: 17px;
    font-weight: 700;
    color: #1c1412;
    line-height: 1.2;
}
.ar-page-sub {
    font-size: 12.5px;
    color: #8a7d78;
    margin-top: 1px;
}
.ar-page-sub strong {
    color: #4a3f3a;
}

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

.ar-period-select {
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
.ar-period-select:focus {
    border-color: #f97316;
}

/* Bouton export */
.ar-btn-export {
    display: flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    padding: 0 14px;
    border-radius: 9px;
    border: 1.5px solid #e8ddd4;
    background: #faf8f6;
    color: #4a3f3a;
    font-size: 13px;
    font-weight: 600;
    font-family: "Poppins", sans-serif;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.ar-btn-export svg {
    width: 15px;
    height: 15px;
}
.ar-btn-export:hover {
    border-color: #f97316;
    color: #f97316;
    background: #fff7ed;
}
@media (max-width: 640px) {
    .ar-btn-export span {
        display: none;
    }
    .ar-btn-export {
        padding: 0 10px;
    }
}

.ar-avatar {
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

/* ── Notifications ── */
.ar-notif-wrap {
    position: relative;
}
.ar-notif-btn {
    position: relative;
    width: 36px;
    height: 36px;
    border-radius: 9px;
    border: 1.5px solid #e8ddd4;
    background: #faf8f6;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.15s;
}
.ar-notif-btn svg {
    width: 18px;
    height: 18px;
    color: #4a3f3a;
}
.ar-notif-btn:hover {
    border-color: #f97316;
}
.ar-notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 17px;
    height: 17px;
    border-radius: 99px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    border: 2px solid #fff;
}
.ar-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    z-index: 200;
    overflow: hidden;
}
.ar-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px 10px;
    font-size: 13px;
    font-weight: 700;
    color: #1c1412;
    border-bottom: 1px solid #f0ebe5;
}
.ar-notif-read-all {
    font-size: 12px;
    color: #f97316;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-family: "Poppins", sans-serif;
}
.ar-notif-loading,
.ar-notif-empty {
    padding: 20px 16px;
    font-size: 13px;
    color: #8a7d78;
    text-align: center;
}
.ar-notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f5f0eb;
    transition: background 0.15s;
}
.ar-notif-item:last-child {
    border-bottom: none;
}
.ar-notif-item:hover {
    background: #faf8f6;
}
.ar-notif-item.unread {
    background: #fff7ed;
}
.ar-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f97316;
    flex-shrink: 0;
    margin-top: 5px;
}
.ar-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: #1c1412;
    margin-bottom: 2px;
}
.ar-notif-body {
    font-size: 12.5px;
    color: #4a3f3a;
    line-height: 1.5;
}
.ar-notif-ago {
    font-size: 11.5px;
    color: #8a7d78;
    margin-top: 3px;
}

/* ── Contenu principal ── */
.ar-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    overflow-y: auto;
}

/* ── KPIs ── */
.ar-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.ar-kpi {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    border: 1.5px solid #f0ebe5;
    display: flex;
    flex-direction: column;
    gap: 6px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: box-shadow 0.2s;
}
.ar-kpi:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}
.ar-kpi-icon {
    font-size: 26px;
}
.ar-kpi-val {
    font-size: 22px;
    font-weight: 800;
    color: #1c1412;
    line-height: 1.2;
}
.ar-kpi-label {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
}

.ar-kpi--green {
    border-left: 4px solid #22c55e;
}
.ar-kpi--orange {
    border-left: 4px solid #f97316;
}
.ar-kpi--blue {
    border-left: 4px solid #3b82f6;
}
.ar-kpi--purple {
    border-left: 4px solid #a855f7;
}
.ar-kpi--red {
    border-left: 4px solid #ef4444;
}
.ar-kpi--teal {
    border-left: 4px solid #14b8a6;
}

/* ── Skeleton ── */
.ar-skeleton {
    display: inline-block;
    width: 90px;
    height: 24px;
    background: linear-gradient(90deg, #f0ebe5 25%, #faf8f6 50%, #f0ebe5 75%);
    background-size: 200% 100%;
    animation: ar-shimmer 1.4s infinite;
    border-radius: 6px;
}
@keyframes ar-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* ── Filtres ── */
.ar-filters-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ar-search-wrap {
    flex: 1;
    min-width: 200px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1.5px solid #e8ddd4;
    border-radius: 10px;
    padding: 0 12px;
    height: 40px;
    transition: border-color 0.15s;
}
.ar-search-wrap:focus-within {
    border-color: #f97316;
}
.ar-search {
    border: none;
    outline: none;
    background: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: #1c1412;
    width: 100%;
}
.ar-search::placeholder {
    color: #b8ada7;
}
.ar-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ar-filter-select {
    height: 40px;
    border: 1.5px solid #e8ddd4;
    border-radius: 10px;
    padding: 0 12px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #4a3f3a;
    background: #fff;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
}
.ar-filter-select:focus {
    border-color: #f97316;
}

/* ── Card tableau ── */
.ar-card {
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid #f0ebe5;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    flex: 1;
}
.ar-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 14px;
    border-bottom: 1.5px solid #f0ebe5;
    gap: 16px;
    flex-wrap: wrap;
}
.ar-card-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
    margin: 0;
}
.ar-card-count {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
}

/* Loader skeleton rows */
.ar-loading {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ar-skeleton-row {
    height: 44px;
    border-radius: 8px;
    background: linear-gradient(90deg, #f0ebe5 25%, #faf8f6 50%, #f0ebe5 75%);
    background-size: 200% 100%;
    animation: ar-shimmer 1.4s infinite;
}

/* Erreur */
.ar-alert-error {
    margin: 20px;
    padding: 14px 18px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    border-radius: 10px;
    color: #dc2626;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.ar-btn-retry {
    background: none;
    border: 1.5px solid #dc2626;
    color: #dc2626;
    border-radius: 8px;
    padding: 4px 12px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    margin-left: auto;
}

/* Vide */
.ar-empty {
    padding: 48px 20px;
    text-align: center;
}
.ar-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.ar-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
}
.ar-empty-sub {
    font-size: 13px;
    color: #8a7d78;
    margin-top: 4px;
}

/* ── Tableau ── */
.ar-table-wrap {
    overflow-x: auto;
}
.ar-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.ar-table thead tr {
    background: #faf8f6;
}
.ar-table th {
    padding: 12px 14px;
    text-align: left;
    font-size: 11.5px;
    font-weight: 700;
    color: #8a7d78;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    border-bottom: 1.5px solid #f0ebe5;
}
.ar-table td {
    padding: 13px 14px;
    border-bottom: 1px solid #f5f0eb;
    color: #1c1412;
    vertical-align: middle;
}
.ar-table tbody tr:last-child td {
    border-bottom: none;
}
.ar-table tbody tr:hover td {
    background: #faf8f6;
}

.ar-ref {
    font-size: 12px;
    font-weight: 700;
    color: #8a7d78;
}
.ar-service-name {
    font-weight: 600;
    color: #1c1412;
}
.ar-person-name {
    color: #4a3f3a;
}
.ar-date {
    color: #8a7d78;
    white-space: nowrap;
    font-size: 13px;
}
.ar-amount {
    font-weight: 700;
    color: #1c1412;
}
.ar-commission {
    font-weight: 700;
    color: #f97316;
}
.ar-payout {
    font-weight: 700;
    color: #14b8a6;
}

/* Badges */
.ar-badge {
    padding: 3px 11px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.ar-badge--green {
    background: #f0fdf4;
    color: #15803d;
}
.ar-badge--orange {
    background: #fff7ed;
    color: #ea580c;
}
.ar-badge--blue {
    background: #eff6ff;
    color: #1d4ed8;
}
.ar-badge--red {
    background: #fef2f2;
    color: #dc2626;
}

/* Responsive mobile tableau */
@media (max-width: 900px) {
    .ar-table thead {
        display: none;
    }
    .ar-table tbody tr {
        display: block;
        background: #fff;
        border: 1.5px solid #ede8e3;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 4px 0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }
    .ar-table tbody tr:last-child {
        margin-bottom: 0;
    }
    .ar-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-bottom: 1px solid #f5f0eb;
        font-size: 13px;
    }
    .ar-table td:last-child {
        border-bottom: none;
    }
    .ar-table td::before {
        content: attr(data-label);
        font-size: 11px;
        font-weight: 700;
        color: #8a7d78;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        flex-shrink: 0;
        margin-right: 12px;
    }
    .ar-table tbody tr:hover td {
        background: transparent;
    }
    .ar-table-wrap {
        overflow-x: unset;
    }
}

/* ── Pagination sticky bas ── */
.ar-pagination {
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
.ar-pagination-info {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
    white-space: nowrap;
}
.ar-pagination-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}
.ar-page-btn {
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
.ar-page-btn:hover:not(:disabled) {
    border-color: #f97316;
    color: #f97316;
    background: #fff7ed;
}
.ar-page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.ar-page-btn--active {
    background: #f97316 !important;
    border-color: #f97316 !important;
    color: #fff !important;
    font-weight: 700;
}
.ar-page-dots {
    font-size: 13px;
    color: #8a7d78;
    padding: 0 4px;
    user-select: none;
}
.ar-pagination-perpage {
    display: flex;
    align-items: center;
}
.ar-perpage-select {
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
.ar-perpage-select:focus {
    border-color: #f97316;
}

/* ── Responsive global ── */
@media (max-width: 900px) {
    .ar-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
    .ar-content {
        padding: 16px;
    }
    .ar-topbar {
        padding: 0 16px;
    }
}
@media (max-width: 560px) {
    .ar-kpis {
        grid-template-columns: 1fr;
    }
    .ar-period-select {
        display: none;
    }
}
</style>
