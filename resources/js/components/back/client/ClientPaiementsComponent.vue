<template>
    <div class="cp-wrap">
        <!-- ----------------------------------
             TOPBAR
        ---------------------------------- -->
        <div class="cp-topbar">
            <div class="cp-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="cp-page-title">Mes paiements</div>
                    <div class="cp-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="cp-topbar-right">
                <!-- Filtre période -->
                <select
                    class="cp-period-select"
                    v-model="period"
                    @change="fetchPaiements"
                >
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>

                <!-- Cloche notifications -->
                <div class="cp-notif-wrap" ref="notifWrap">
                    <button class="cp-notif-btn" @click="toggleNotif">
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
                        <span class="cp-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount
                        }}</span>
                    </button>
                    <div class="cp-notif-dropdown" v-if="notifOpen">
                        <div class="cp-notif-header">
                            <span>Notifications</span>
                            <button
                                class="cp-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="cp-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="cp-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="cp-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="cp-notif-dot" v-if="!n.read"></div>
                            <div class="cp-notif-content">
                                <div class="cp-notif-title">{{ n.title }}</div>
                                <div class="cp-notif-body">{{ n.body }}</div>
                                <div class="cp-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cp-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ----------------------------------
             CONTENU
        ---------------------------------- -->
        <div class="cp-content">
            <!-- -- KPIs -- -->
            <div class="cp-kpis">
                <div class="cp-kpi cp-kpi--blue">
                    <div class="cp-kpi-icon">??</div>
                    <div class="cp-kpi-val">
                        <span v-if="loading" class="cp-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.total_depenses)
                        }}</span>
                    </div>
                    <div class="cp-kpi-label">Total dépensé</div>
                </div>
                <div class="cp-kpi cp-kpi--orange">
                    <div class="cp-kpi-icon">?</div>
                    <div class="cp-kpi-val">
                        <span v-if="loading" class="cp-skeleton"></span>
                        <span v-else>{{ stats.missions_terminees }}</span>
                    </div>
                    <div class="cp-kpi-label">Missions terminées</div>
                </div>
                <div class="cp-kpi cp-kpi--green">
                    <div class="cp-kpi-icon">??</div>
                    <div class="cp-kpi-val">
                        <span v-if="loading" class="cp-skeleton"></span>
                        <span v-else>{{
                            formatPrice(stats.depense_moyenne)
                        }}</span>
                    </div>
                    <div class="cp-kpi-label">Dépense moyenne / mission</div>
                </div>
                <div class="cp-kpi cp-kpi--red">
                    <div class="cp-kpi-icon">?</div>
                    <div class="cp-kpi-val">
                        <span v-if="loading" class="cp-skeleton"></span>
                        <span v-else>{{ formatPrice(stats.en_attente) }}</span>
                    </div>
                    <div class="cp-kpi-label">En attente de validation</div>
                </div>
            </div>

            <!-- -- TABLEAU PAIEMENTS -- -->
            <div class="cp-card">
                <div class="cp-card-header">
                    <h3>?? Historique des paiements</h3>
                    <div class="cp-search-wrap">
                        <span>??</span>
                        <input
                            class="cp-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher un service, prestataire…"
                        />
                    </div>
                </div>

                <!-- Loader -->
                <div class="cp-loading" v-if="loading">
                    <div class="cp-skeleton-row" v-for="n in 5" :key="n"></div>
                </div>

                <!-- Erreur -->
                <div class="cp-alert-error" v-else-if="error">
                    ?? {{ error }}
                    <button class="cp-btn-retry" @click="fetchPaiements">
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div class="cp-empty" v-else-if="filteredMissions.length === 0">
                    <div class="cp-empty-icon">??</div>
                    <div class="cp-empty-title">Aucun paiement trouvé</div>
                    <div class="cp-empty-sub">
                        Vos paiements apparaîtront ici après la fin de vos
                        missions.
                    </div>
                </div>

                <!-- Tableau -->
                <div class="cp-table-wrap" v-else>
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Prestataire</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in filteredMissions" :key="m.id">
                                <td data-label="Service">
                                    <div class="cp-service-name">
                                        {{ m.service }}
                                    </div>
                                </td>
                                <td
                                    data-label="Prestataire"
                                    class="cp-contractor-name"
                                >
                                    {{ m.contractor_name ?? "—" }}
                                </td>
                                <td data-label="Date" class="cp-date">
                                    {{
                                        formatDate(
                                            m.completed_at ?? m.created_at,
                                        )
                                    }}
                                </td>
                                <td data-label="Montant">
                                    <span class="cp-amount">{{
                                        m.total_amount
                                            ? formatPrice(m.total_amount)
                                            : "—"
                                    }}</span>
                                </td>
                                <td data-label="Statut">
                                    <span
                                        class="cp-badge"
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
            <!-- fin cp-card -->

            <!-- Pagination — sticky en bas de page -->
            <div class="cp-pagination" v-if="missions.length > 0">
                <div class="cp-pagination-info">{{ paginationInfo }}</div>
                <div class="cp-pagination-controls">
                    <button
                        class="cp-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage = 1"
                        title="Première page"
                    >
                        «
                    </button>
                    <button
                        class="cp-page-btn"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        title="Page précédente"
                    >
                        ‹
                    </button>
                    <template v-for="p in visiblePages" :key="p">
                        <span v-if="p === '...'" class="cp-page-dots">…</span>
                        <button
                            v-else
                            class="cp-page-btn"
                            :class="{
                                'cp-page-btn--active': p === currentPage,
                            }"
                            @click="currentPage = p"
                        >
                            {{ p }}
                        </button>
                    </template>
                    <button
                        class="cp-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                        title="Page suivante"
                    >
                        ›
                    </button>
                    <button
                        class="cp-page-btn"
                        :disabled="currentPage === totalPages"
                        @click="currentPage = totalPages"
                        title="Dernière page"
                    >
                        »
                    </button>
                </div>
                <div class="cp-pagination-perpage">
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="cp-perpage-select"
                    >
                        <option :value="5">5 / page</option>
                        <option :value="10">10 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                    </select>
                </div>
            </div>
        </div>
        <!-- fin cp-content -->
    </div>
</template>

<script>
export default {
    name: "ClientPaiementsComponent",

    props: {
        user: {
            type: Object,
            required: true,
        },
        routes: {
            type: Object,
            default: () => ({}),
            // { paiements_index, notifications, notifications_all }
        },
    },

    data() {
        return {
            missions: [],
            stats: {
                total_depenses: 0,
                missions_terminees: 0,
                depense_moyenne: 0,
                en_attente: 0,
            },
            loading: true,
            error: "",
            period: "month",
            search: "",
            currentPage: 1,
            perPage: 10,

            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
        };
    },

    computed: {

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
                          (m.contractor_name ?? "").toLowerCase().includes(q),
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
                          (m.contractor_name ?? "").toLowerCase().includes(q),
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
                          (m.contractor_name ?? "").toLowerCase().includes(q),
                  ).length
                : this.missions.length;
            const from = Math.min(
                (this.currentPage - 1) * this.perPage + 1,
                total,
            );
            const to = Math.min(this.currentPage * this.perPage, total);
            return total === 0
                ? "Aucun résultat"
                : `${from}–${to} sur ${total} paiement${total > 1 ? "s" : ""}`;
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

    mounted() {
        this.fetchPaiements();
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

        async fetchPaiements() {
            this.loading = true;
            this.error = "";
            try {
                const url =
                    (this.routes.paiements_index ?? "/client/paiements/data") +
                    `?period=${this.period}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur serveur");
                const data = await res.json();

                this.missions = data.missions ?? [];
                this.stats = {
                    total_depenses: data.total_depenses ?? 0,
                    missions_terminees: data.missions_terminees ?? 0,
                    depense_moyenne: data.depense_moyenne ?? 0,
                    en_attente: data.en_attente ?? 0,
                };
                this.currentPage = 1;
            } catch (e) {
                this.error =
                    "Impossible de charger les paiements. Vérifiez votre connexion.";
            } finally {
                this.loading = false;
            }
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
                    completed: "Payée",
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
                    completed: "cp-badge--green",
                    closed: "cp-badge--green",
                    pending: "cp-badge--orange",
                    active: "cp-badge--blue",
                    cancelled: "cp-badge--red",
                }[status] ?? ""
            );
        },

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
                this.unreadCount =
                    data.unread_count ??
                    this.notifications.filter((n) => !n.read).length;
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
.cp-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* -- Topbar -- */
.cp-topbar {
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
.cp-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.cp-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cp-page-title {
    font-size: 17px;
    font-weight: 700;
    color: #1c1412;
    line-height: 1.2;
}
.cp-page-sub {
    font-size: 12.5px;
    color: #8a7d78;
    margin-top: 1px;
}
.cp-page-sub strong {
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

.cp-period-select {
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
.cp-period-select:focus {
    border-color: #f97316;
}

.cp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
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
.cp-notif-wrap {
    position: relative;
}
.cp-notif-btn {
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
.cp-notif-btn:hover {
    background: #fef3e2;
}
.cp-notif-btn svg {
    width: 20px;
    height: 20px;
}
.cp-notif-badge {
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
.cp-notif-dropdown {
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
.cp-notif-header {
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
.cp-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: #f97316;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.cp-notif-loading,
.cp-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: #4a3f3a;
}
.cp-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.cp-notif-item:last-child {
    border-bottom: none;
}
.cp-notif-item:hover {
    background: #faf7f5;
}
.cp-notif-item.unread {
    background: #fff8f5;
}
.cp-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f97316;
    flex-shrink: 0;
    margin-top: 5px;
}
.cp-notif-content {
    flex: 1;
    min-width: 0;
}
.cp-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: #1c1412;
}
.cp-notif-body {
    font-size: 12px;
    color: #4a3f3a;
    margin-top: 2px;
    line-height: 1.4;
}
.cp-notif-ago {
    font-size: 11px;
    color: #8a7d78;
    margin-top: 4px;
}

/* -- Contenu -- */
.cp-content {
    flex: 1;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* -- KPIs -- */
.cp-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.cp-kpi {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cp-kpi-icon {
    font-size: 24px;
}
.cp-kpi-val {
    font-size: 22px;
    font-weight: 800;
    color: #1c1412;
    line-height: 1;
}
.cp-kpi-label {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
}
.cp-kpi--blue {
    border-left: 4px solid #3b82f6;
}
.cp-kpi--orange {
    border-left: 4px solid #f97316;
}
.cp-kpi--green {
    border-left: 4px solid #22c55e;
}
.cp-kpi--red {
    border-left: 4px solid #ef4444;
}

.cp-skeleton {
    display: inline-block;
    width: 100px;
    height: 22px;
    background: linear-gradient(90deg, #ede8e3 25%, #f8f4f0 50%, #ede8e3 75%);
    background-size: 200% 100%;
    animation: cp-shimmer 1.2s infinite;
    border-radius: 6px;
}
@keyframes cp-shimmer {
    to {
        background-position: -200% 0;
    }
}

/* -- Carte -- */
.cp-card {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 18px;
    overflow: hidden;
}
.cp-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1.5px solid #f5f0eb;
    gap: 16px;
    flex-wrap: wrap;
}
.cp-card-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
}
.cp-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #faf8f6;
    border: 1.5px solid #e8ddd4;
    border-radius: 9px;
    padding: 0 12px;
    height: 36px;
}
.cp-search {
    border: none;
    background: none;
    outline: none;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: #1c1412;
    width: 200px;
}
.cp-search::placeholder {
    color: #b5a9a3;
}

.cp-loading {
    padding: 16px 22px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.cp-skeleton-row {
    height: 44px;
    background: linear-gradient(90deg, #f5f0eb 25%, #faf8f6 50%, #f5f0eb 75%);
    background-size: 200% 100%;
    animation: cp-shimmer 1.2s infinite;
    border-radius: 8px;
}

.cp-alert-error {
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
.cp-btn-retry {
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

.cp-empty {
    padding: 48px 22px;
    text-align: center;
}
.cp-empty-icon {
    font-size: 40px;
    margin-bottom: 12px;
}
.cp-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #1c1412;
}
.cp-empty-sub {
    font-size: 13px;
    color: #8a7d78;
    margin-top: 4px;
}

/* -- Tableau -- */
.cp-table-wrap {
    overflow-x: auto;
}
.cp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
.cp-table thead tr {
    background: #faf8f6;
}
.cp-table th {
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
.cp-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f5f0eb;
    color: #1c1412;
    vertical-align: middle;
}
.cp-table tbody tr:last-child td {
    border-bottom: none;
}
.cp-table tbody tr:hover td {
    background: #faf8f6;
}
.cp-service-name {
    font-weight: 600;
    color: #1c1412;
}
.cp-contractor-name {
    color: #4a3f3a;
}
.cp-date {
    color: #8a7d78;
    white-space: nowrap;
    font-size: 13px;
}
.cp-amount {
    font-weight: 700;
    color: #1c1412;
}

.cp-badge {
    padding: 3px 11px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.cp-badge--green {
    background: #f0fdf4;
    color: #15803d;
}
.cp-badge--orange {
    background: #fff7ed;
    color: #ea580c;
}
.cp-badge--blue {
    background: #eff6ff;
    color: #1d4ed8;
}
.cp-badge--red {
    background: #fef2f2;
    color: #dc2626;
}

/* -- Tableau responsive mobile -- */
@media (max-width: 640px) {
    .cp-table thead {
        display: none;
    }
    .cp-table tbody tr {
        display: block;
        background: #fff;
        border: 1.5px solid #ede8e3;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 4px 0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }
    .cp-table tbody tr:last-child {
        margin-bottom: 0;
    }
    .cp-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-bottom: 1px solid #f5f0eb;
        font-size: 13px;
    }
    .cp-table td:last-child {
        border-bottom: none;
    }
    .cp-table td::before {
        content: attr(data-label);
        font-size: 11px;
        font-weight: 700;
        color: #8a7d78;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        flex-shrink: 0;
        margin-right: 12px;
    }
    .cp-table tbody tr:hover td {
        background: transparent;
    }
    .cp-table-wrap {
        overflow-x: unset;
    }
}

/* -- Pagination sticky bas -- */
.cp-pagination {
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
.cp-pagination-info {
    font-size: 12.5px;
    color: #8a7d78;
    font-weight: 500;
    white-space: nowrap;
}
.cp-pagination-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}
.cp-page-btn {
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
.cp-page-btn:hover:not(:disabled) {
    border-color: #f97316;
    color: #f97316;
    background: #fff7ed;
}
.cp-page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.cp-page-btn--active {
    background: #f97316 !important;
    border-color: #f97316 !important;
    color: #fff !important;
    font-weight: 700;
}
.cp-page-dots {
    font-size: 13px;
    color: #8a7d78;
    padding: 0 4px;
    user-select: none;
}
.cp-pagination-perpage {
    display: flex;
    align-items: center;
}
.cp-perpage-select {
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
.cp-perpage-select:focus {
    border-color: #f97316;
}

/* -- Responsive global -- */
@media (max-width: 900px) {
    .cp-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
    .cp-content {
        padding: 16px;
    }
    .cp-topbar {
        padding: 0 16px;
    }
}
@media (max-width: 560px) {
    .cp-kpis {
        grid-template-columns: 1fr;
    }
    .cp-period-select {
        display: none;
    }
}
</style>

