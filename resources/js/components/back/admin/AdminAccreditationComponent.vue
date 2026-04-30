<template>
    <div class="aac-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="aac-topbar">
            <div class="aac-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="aac-page-title">
                        Accréditations prestataires
                    </div>
                    <div class="aac-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="aac-topbar-right">
                <!-- Pill alerte -->
                <div class="aac-count-pill" v-if="noneCount > 0">
                    ⚠️ {{ noneCount }} sans accréditation
                </div>

                <!-- Cloche notifications -->
                <div class="aac-notif-wrap" ref="notifWrap">
                    <button class="aac-notif-btn" @click="toggleNotif">
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
                        <span class="aac-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <!-- Dropdown -->
                    <div class="aac-notif-dropdown" v-if="notifOpen">
                        <div class="aac-notif-header">
                            <span>Notifications</span>
                            <button
                                class="aac-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="aac-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="aac-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="aac-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="aac-notif-dot" v-if="!n.read"></div>
                            <div class="aac-notif-content">
                                <div class="aac-notif-title">{{ n.title }}</div>
                                <div class="aac-notif-body">{{ n.body }}</div>
                                <div class="aac-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avatar -->
                <div class="aac-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="aac-content">
            <!-- TABS -->
            <div class="aac-tabs">
                <button
                    class="aac-tab"
                    v-for="t in tabs"
                    :key="t.key"
                    :class="{ active: activeTab === t.key }"
                    @click="activeTab = t.key"
                >
                    {{ t.icon }} {{ t.label }}
                    <span class="aac-tab-count">{{ t.count }}</span>
                </button>
            </div>

            <!-- BARRE DE RECHERCHE -->
            <div class="aac-search-bar">
                <span class="aac-search-icon">🔍</span>
                <input
                    class="aac-search"
                    type="text"
                    v-model="search"
                    placeholder="Rechercher par nom, spécialité, ville…"
                />
                <select class="aac-select" v-model="filterSpec">
                    <option value="">Toutes spécialités</option>
                    <option v-for="s in specialties" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>
            </div>

            <!-- LOADER -->
            <div class="aac-loading" v-if="loading">
                <div class="aac-skeleton-row" v-for="n in 5" :key="n"></div>
            </div>

            <!-- ERREUR -->
            <div class="aac-alert-error" v-else-if="error">
                ⚠️ {{ error }}
                <button
                    class="aac-btn aac-btn-ghost aac-btn-sm"
                    @click="fetchContractors"
                >
                    Réessayer
                </button>
            </div>

            <!-- VIDE -->
            <div class="aac-empty" v-else-if="filteredContractors.length === 0">
                <div class="aac-empty-icon">✅</div>
                <div class="aac-empty-title">
                    {{
                        activeTab === "none"
                            ? "Tous les prestataires ont une accréditation !"
                            : "Aucun prestataire trouvé"
                    }}
                </div>
            </div>

            <!-- LISTE -->
            <div class="aac-list" v-else>
                <div
                    class="aac-item"
                    v-for="c in filteredContractors"
                    :key="c.id"
                    :class="{ 'aac-item-none': c.accreditation === 'none' }"
                >
                    <!-- Avatar -->
                    <div
                        class="aac-av"
                        :class="{ certified: c.status === 'approved' }"
                    >
                        {{ initials(c) }}
                        <span
                            class="aac-certified-dot"
                            v-if="c.status === 'approved'"
                            >✓</span
                        >
                    </div>

                    <!-- Infos -->
                    <div class="aac-info">
                        <div class="aac-name">
                            {{ c.first_name }} {{ c.last_name }}
                        </div>
                        <div class="aac-meta">
                            {{ c.specialty }} · {{ c.city ?? "Cotonou" }}
                        </div>
                        <div class="aac-meta" v-if="c.intervention_zone">
                            📍 {{ c.intervention_zone }}
                        </div>
                    </div>

                    <!-- Accréditation actuelle -->
                    <div class="aac-current">
                        <div class="aac-current-lbl">Actuelle</div>
                        <span
                            class="aac-badge aac-badge-current"
                            :class="accredBadgeClass(c.accreditation)"
                        >
                            {{ accredLabel(c.accreditation) }}
                        </span>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="aac-actions">
                        <button
                            v-for="opt in accredOptions.filter(
                                (o) => o.value !== c.accreditation
                            )"
                            :key="opt.value"
                            class="aac-btn aac-btn-action"
                            :class="opt.btnClass"
                            @click="openModal(c, opt.value)"
                        >
                            {{ opt.icon }} {{ opt.short }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL CONFIRMATION ══════════════ -->
        <div
            class="aac-modal-overlay"
            v-if="modal.visible"
            @click.self="modal.visible = false"
        >
            <div class="aac-modal">
                <div class="aac-modal-header">
                    <div>
                        <h3>Modifier l'accréditation</h3>
                        <div class="aac-modal-sub">
                            {{ modal.contractor?.first_name }}
                            {{ modal.contractor?.last_name }}
                        </div>
                    </div>
                    <button
                        class="aac-modal-close"
                        @click="modal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="aac-modal-body">
                    <!-- Transition visuelle -->
                    <div class="aac-transition-row">
                        <div class="aac-transition-from">
                            <div class="aac-transition-lbl">
                                Accréditation actuelle
                            </div>
                            <span
                                class="aac-badge"
                                :class="
                                    accredBadgeClass(
                                        modal.contractor?.accreditation
                                    )
                                "
                            >
                                {{
                                    accredLabel(modal.contractor?.accreditation)
                                }}
                            </span>
                        </div>
                        <div class="aac-transition-arrow">→</div>
                        <div class="aac-transition-to">
                            <div class="aac-transition-lbl">
                                Nouvelle accréditation
                            </div>
                            <span
                                class="aac-badge"
                                :class="accredBadgeClass(modal.newValue)"
                            >
                                {{ accredLabel(modal.newValue) }}
                            </span>
                        </div>
                    </div>

                    <!-- Explications -->
                    <div class="aac-accred-explain">
                        <div
                            class="aac-explain-item"
                            v-if="['home', 'both'].includes(modal.newValue)"
                        >
                            <span class="aac-explain-icon">🏠</span>
                            <div>
                                <div class="aac-explain-title">
                                    Accréditation Domicile
                                </div>
                                <div class="aac-explain-sub">
                                    Autorise les interventions chez les
                                    particuliers.
                                </div>
                            </div>
                        </div>
                        <div
                            class="aac-explain-item"
                            v-if="['business', 'both'].includes(modal.newValue)"
                        >
                            <span class="aac-explain-icon">🏢</span>
                            <div>
                                <div class="aac-explain-title">
                                    Accréditation Entreprise
                                </div>
                                <div class="aac-explain-sub">
                                    Autorise les interventions en milieu
                                    professionnel. Vérification complémentaire
                                    requise.
                                </div>
                            </div>
                        </div>
                        <div
                            class="aac-explain-item aac-explain-warning"
                            v-if="modal.newValue === 'none'"
                        >
                            <span class="aac-explain-icon">⚠️</span>
                            <div>
                                <div class="aac-explain-title">
                                    Retrait des accréditations
                                </div>
                                <div class="aac-explain-sub">
                                    Le prestataire ne pourra plus accéder aux
                                    missions jusqu'à une nouvelle attribution.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="aac-modal-footer">
                    <button
                        class="aac-btn aac-btn-ghost"
                        @click="modal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="aac-btn aac-btn-green"
                        @click="confirmAccred"
                        :disabled="modal.loading"
                    >
                        <div class="aac-spinner" v-if="modal.loading"></div>
                        <span v-else>✓ Confirmer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="aac-toast-container">
            <div
                class="aac-toast"
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
    name: "AdminAccreditationComponent",
    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            contractors: [],
            loading: false,
            error: null,
            activeTab: "none",
            search: "",
            filterSpec: "",

            // Notifications
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            modal: {
                visible: false,
                contractor: null,
                newValue: "none",
                loading: false,
            },

            toasts: [],
            toastId: 0,
            sidebarOpen: false,

            accredOptions: [
                {
                    value: "none",
                    icon: "🚫",
                    short: "Aucune",
                    label: "🚫 Aucune",
                    btnClass: "ghost",
                    badgeClass: "none",
                },
                {
                    value: "home",
                    icon: "🏠",
                    short: "Domicile",
                    label: "🏠 Domicile",
                    btnClass: "blue",
                    badgeClass: "home",
                },
                {
                    value: "business",
                    icon: "🏢",
                    short: "Entreprise",
                    label: "🏢 Entreprise",
                    btnClass: "green",
                    badgeClass: "business",
                },
                {
                    value: "both",
                    icon: "🏅",
                    short: "Dom. + Entr.",
                    label: "🏅 Dom. + Entr.",
                    btnClass: "orange",
                    badgeClass: "both",
                },
            ],
        };
    },

    computed: {

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

        tabs() {
            return [
                {
                    key: "none",
                    icon: "⚠️",
                    label: "Sans accréditation",
                    count: this.noneCount,
                },
                {
                    key: "all",
                    icon: "👷",
                    label: "Tous",
                    count: this.contractors.length,
                },
            ];
        },

        noneCount() {
            return this.contractors.filter((c) => c.accreditation === "none")
                .length;
        },

        specialties() {
            return [
                ...new Set(
                    this.contractors.map((c) => c.specialty).filter(Boolean)
                ),
            ].sort();
        },

        filteredContractors() {
            let list = [...this.contractors];

            if (this.activeTab === "none") {
                list = list.filter((c) => c.accreditation === "none");
            }

            if (this.filterSpec) {
                list = list.filter((c) => c.specialty === this.filterSpec);
            }

            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (c) =>
                        c.first_name?.toLowerCase().includes(q) ||
                        c.last_name?.toLowerCase().includes(q) ||
                        c.specialty?.toLowerCase().includes(q) ||
                        c.city?.toLowerCase().includes(q)
                );
            }

            return list;
        },
    },

    methods: {
        // ── Fetch ─────────────────────────────────────────────────
        async fetchContractors() {
            this.loading = true;
            this.error = null;
            try {
                // On charge uniquement les certifiés (approved)
                const url =
                    this.routes.contractors_index +
                    "?status=approved&per_page=200";
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                this.contractors = Array.isArray(data) ? data : data.data ?? [];
            } catch {
                this.error = "Impossible de charger les prestataires.";
            } finally {
                this.loading = false;
            }
        },

        // ── Modal ─────────────────────────────────────────────────
        openModal(contractor, newValue) {
            this.modal = {
                visible: true,
                contractor: { ...contractor },
                newValue,
                loading: false,
            };
        },

        async confirmAccred() {
            this.modal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.contractors_accreditation.replace(
                    "{id}",
                    this.modal.contractor.contractor_id ??
                        this.modal.contractor.id
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        accreditation: this.modal.newValue,
                    }),
                });

                const data = await res.json();
                if (!res.ok) {
                    this.showToast(
                        data.message ?? "Erreur lors de la mise à jour.",
                        "error"
                    );
                    return;
                }

                // Mettre à jour localement
                const idx = this.contractors.findIndex(
                    (c) => c.id === this.modal.contractor.id
                );
                if (idx !== -1) {
                    this.contractors[idx] = {
                        ...this.contractors[idx],
                        accreditation: this.modal.newValue,
                    };
                }

                this.modal.visible = false;
                const label = this.accredLabel(this.modal.newValue);
                this.showToast(
                    `🏅 ${this.modal.contractor.first_name} ${this.modal.contractor.last_name} — accréditation "${label}" attribuée.`,
                    "success"
                );
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.modal.loading = false;
            }
        },

        // ── Notifications ─────────────────────────────────────────
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.notifications = data.notifications ?? [];
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
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.notifications_read.replace(
                    "{id}",
                    n.id
                );
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
        },

        async markAllRead() {
            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications.forEach((n) => (n.read = true));
            this.unreadCount = 0;
        },

        // ── Helpers ───────────────────────────────────────────────
        initials(c) {
            return (
                (
                    (c.first_name?.[0] ?? "") + (c.last_name?.[0] ?? "")
                ).toUpperCase() || "PR"
            );
        },

        accredLabel(val) {
            return (
                {
                    none: "🚫 Aucune",
                    home: "🏠 Domicile",
                    business: "🏢 Entreprise",
                    both: "🏅 Domicile + Entreprise",
                }[val] ?? "—"
            );
        },

        accredBadgeClass(val) {
            return (
                {
                    none: "badge-none",
                    home: "badge-home",
                    business: "badge-business",
                    both: "badge-both",
                }[val] ?? ""
            );
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 4000);
        },

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
        },
    },

    mounted() {
        this.fetchContractors();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000
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
.aac-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
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

/* ── TOPBAR ── */
.aac-topbar {
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
    .aac-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.aac-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.aac-topbar-right {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
@media (min-width: 480px) {
    .aac-topbar-right {
        gap: 10px;
    }
}
.aac-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.aac-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    display: none;
}
@media (min-width: 480px) {
    .aac-page-sub {
        display: block;
    }
}
.aac-page-sub strong {
    color: var(--dk);
}

/* Avatar */
.aac-avatar {
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

/* Burger */
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

/* ── NOTIFICATIONS ── */
.aac-notif-wrap {
    position: relative;
}
.aac-notif-btn {
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
.aac-notif-btn:hover {
    color: var(--or);
}
.aac-notif-btn svg {
    width: 22px;
    height: 22px;
}
.aac-notif-badge {
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
.aac-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: min(320px, calc(100vw - 32px));
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
}
.aac-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.aac-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.aac-notif-loading,
.aac-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.aac-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
    position: relative;
}
.aac-notif-item:hover {
    background: #faf7f5;
}
.aac-notif-item.unread {
    background: #fff8f5;
}
.aac-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.aac-notif-content {
    flex: 1;
    min-width: 0;
}
.aac-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.aac-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.aac-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}
.aac-count-pill {
    background: #fef3c7;
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 700;
    color: #d97706;
    border: 1.5px solid var(--am);
    display: none;
}
@media (min-width: 480px) {
    .aac-count-pill {
        display: inline-flex;
        align-items: center;
    }
}

/* ── CONTENT ── */
.aac-content {
    padding: 12px;
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
@media (min-width: 640px) {
    .aac-content {
        padding: 24px;
        gap: 16px;
    }
}

/* ── TABS ── */
.aac-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 2px;
}
.aac-tabs::-webkit-scrollbar {
    display: none;
}
@media (min-width: 480px) {
    .aac-tabs {
        flex-wrap: wrap;
        overflow-x: visible;
    }
}
.aac-tab {
    padding: 8px 14px;
    border-radius: 10px;
    border: 2px solid transparent;
    background: var(--wh);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: "Poppins", sans-serif;
    white-space: nowrap;
    flex-shrink: 0;
}
@media (min-width: 480px) {
    .aac-tab {
        padding: 9px 18px;
        font-size: 13.5px;
        gap: 8px;
    }
}
.aac-tab:hover {
    background: var(--or3);
    color: var(--or);
}
.aac-tab.active {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
}
.aac-tab-count {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
    font-size: 11px;
    font-weight: 800;
    min-width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}
.aac-tab.active .aac-tab-count {
    background: rgba(255, 255, 255, 0.25);
}

/* ── SEARCH BAR ── */
.aac-search-bar {
    background: var(--wh);
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    flex-wrap: wrap;
}
.aac-search-icon {
    font-size: 14px;
    color: var(--gr);
    flex-shrink: 0;
}
.aac-search {
    flex: 1;
    min-width: 120px;
    border: 2px solid var(--grl);
    border-radius: 9px;
    padding: 8px 12px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: border 0.15s;
}
.aac-search:focus {
    outline: none;
    border-color: var(--or);
}
.aac-select {
    border: 2px solid var(--grl);
    border-radius: 9px;
    padding: 8px 12px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: var(--wh);
    cursor: pointer;
    width: 100%;
}
@media (min-width: 480px) {
    .aac-select {
        width: auto;
    }
}
.aac-select:focus {
    outline: none;
    border-color: var(--or);
}

/* ── LIST ── */
.aac-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.aac-item {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    border: 2px solid transparent;
    transition: all 0.18s;
    flex-wrap: wrap;
}
@media (min-width: 640px) {
    .aac-item {
        align-items: center;
        padding: 16px 18px;
        gap: 14px;
    }
}
.aac-item:hover {
    border-color: var(--am);
}
.aac-item.aac-item-none {
    border-left: 4px solid #f59e0b;
}

/* Avatar */
.aac-av {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--or3), #fde68a);
    color: var(--or2);
    font-weight: 800;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.aac-av.certified {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
}
.aac-certified-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: #16a34a;
    color: #fff;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--wh);
}

/* Infos */
.aac-info {
    flex: 1;
    min-width: 0;
}
.aac-name {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
}
@media (min-width: 480px) {
    .aac-name {
        font-size: 14.5px;
    }
}
.aac-meta {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}

/* Accréditation actuelle */
.aac-current {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.aac-current-lbl {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gr, #7c6a5a);
}

/* Badges accréditation — version liste (petite, pastel) */
.aac-badge {
    padding: 5px 13px;
    border-radius: 99px;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
}
.aac-badge.badge-none {
    background: #f1f5f9;
    color: #64748b;
}
.aac-badge.badge-home {
    background: #eff6ff;
    color: #1d4ed8;
}
.aac-badge.badge-business {
    background: #f0fdf4;
    color: #15803d;
}
.aac-badge.badge-both {
    background: linear-gradient(135deg, #eff6ff, #f0fdf4);
    color: #1e40af;
    border: 1.5px solid #bfdbfe;
}

/* Badge "actuelle" — version grande, couleur pleine */
.aac-badge.aac-badge-current {
    padding: 7px 16px;
    font-size: 13.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.aac-badge.aac-badge-current.badge-none {
    background: #64748b;
    color: #fff;
    box-shadow: 0 2px 8px rgba(100,116,139,0.35);
}
.aac-badge.aac-badge-current.badge-home {
    background: #1d4ed8;
    color: #fff;
    box-shadow: 0 2px 10px rgba(29,78,216,0.4);
}
.aac-badge.aac-badge-current.badge-business {
    background: #15803d;
    color: #fff;
    box-shadow: 0 2px 10px rgba(21,128,61,0.4);
}
.aac-badge.aac-badge-current.badge-both {
    background: linear-gradient(135deg, #1d4ed8, #15803d);
    color: #fff;
    border: none;
    box-shadow: 0 2px 10px rgba(29,78,216,0.35);
}

/* Boutons d'action */
.aac-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    flex-shrink: 0;
    width: 100%;
}
@media (min-width: 640px) {
    .aac-actions {
        width: auto;
        justify-content: flex-end;
    }
}
.aac-btn-action {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    font-family: "Poppins", sans-serif;
    transition: all 0.15s;
    white-space: nowrap;
}
.aac-btn-action.ghost {
    background: var(--grl);
    color: var(--gr);
    border-color: #d5c9c0;
}
.aac-btn-action.ghost:hover {
    background: #e5e0db;
}
.aac-btn-action.blue {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.aac-btn-action.blue:hover {
    background: #dbeafe;
}
.aac-btn-action.green {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}
.aac-btn-action.green:hover {
    background: #dcfce7;
}
.aac-btn-action.orange {
    background: var(--or3);
    color: var(--or2);
    border-color: var(--am);
}
.aac-btn-action.orange:hover {
    background: #fed7aa;
}

/* ── LOADING / EMPTY ── */
.aac-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.aac-skeleton-row {
    height: 72px;
    background: #e5e0db;
    border-radius: 14px;
    animation: aac-pulse 1.2s infinite;
}
@keyframes aac-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}
.aac-alert-error {
    background: #fee2e2;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
}
.aac-empty {
    text-align: center;
    padding: 56px 24px;
    background: var(--wh);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.aac-empty-icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.aac-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}

/* ── MODAL ── */
.aac-modal-overlay {
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
.aac-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 480px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: aac-slide-up 0.25s ease;
}
@keyframes aac-slide-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.aac-modal-header {
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
.aac-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.aac-modal-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}
.aac-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
}
.aac-modal-body {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.aac-modal-footer {
    padding: 14px 24px;
    border-top: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
}

/* Transition visuelle */
.aac-transition-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    background: var(--grl);
    border-radius: 12px;
    padding: 18px 16px;
    flex-wrap: wrap;
}
.aac-transition-from,
.aac-transition-to {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.aac-transition-lbl {
    font-size: 11px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.aac-transition-arrow {
    font-size: 22px;
    color: var(--or);
    font-weight: 800;
}

/* Explications */
.aac-accred-explain {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.aac-explain-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: var(--wh);
    border-radius: 10px;
    padding: 12px 14px;
    border: 1.5px solid var(--grl);
}
.aac-explain-item.aac-explain-warning {
    border-color: #fca5a5;
    background: #fff1f2;
}
.aac-explain-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.aac-explain-title {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
}
.aac-explain-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
    line-height: 1.5;
}

/* ── BOUTONS ── */
.aac-btn {
    padding: 9px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.aac-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.aac-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
}
.aac-btn-green {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
}
.aac-btn-green:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(34, 197, 94, 0.4);
}
.aac-btn-green:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.aac-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.aac-btn-ghost:hover {
    background: #d5c9c0;
}
.aac-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}
.aac-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* ── SPINNER ── */
.aac-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: aac-spin 0.7s linear infinite;
}
@keyframes aac-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── TOASTS ── */
.aac-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.aac-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 220px;
    animation: aac-slide-up 0.3s ease;
}
.aac-toast.success {
    background: #16a34a;
}
.aac-toast.error {
    background: #dc2626;
}

/* ── RESPONSIVE ── */
@media (max-width: 479px) {
    /* Topbar très petit écran */
    .aac-topbar {
        padding: 0 10px;
        height: 54px;
    }
    .aac-page-title {
        font-size: 13px;
    }
    .aac-avatar {
        width: 32px;
        height: 32px;
        font-size: 11px;
    }

    /* Item : badge accréditation remonté à droite du nom */
    .aac-item {
        gap: 10px;
    }
    .aac-current {
        order: -1;
        margin-left: auto;
    }

    /* Boutons action plus compacts */
    .aac-btn-action {
        font-size: 11.5px;
        padding: 5px 10px;
    }
}

@media (max-width: 639px) {
    .aac-topbar {
        padding: 0 12px;
        height: 56px;
    }
    .aac-page-title {
        font-size: 13.5px;
    }
    .aac-item {
        gap: 10px;
    }
    .aac-current {
        order: -1;
        margin-left: auto;
    }

    /* Modal adaptée mobile */
    .aac-modal {
        border-radius: 14px;
        max-height: 96vh;
    }
    .aac-modal-header {
        padding: 16px 16px 12px;
    }
    .aac-modal-body {
        padding: 16px;
    }
    .aac-modal-footer {
        padding: 12px 16px;
        flex-wrap: wrap;
    }
    .aac-modal-footer .aac-btn {
        flex: 1;
        justify-content: center;
    }

    /* Transition visuelle dans la modal */
    .aac-transition-row {
        gap: 10px;
        padding: 14px 12px;
    }
    .aac-transition-arrow {
        font-size: 18px;
    }

    /* Toast pleine largeur */
    .aac-toast-container {
        bottom: 16px;
        right: 12px;
        left: 12px;
    }
    .aac-toast {
        min-width: unset;
        width: 100%;
    }
}

@media (min-width: 640px) and (max-width: 899px) {
    .aac-content {
        padding: 16px;
    }
}

/* Grands écrans : item en ligne compacte */
@media (min-width: 900px) {
    .aac-item {
        flex-wrap: nowrap;
    }
    .aac-actions {
        margin-left: auto;
    }
}
</style>


