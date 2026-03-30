<template>
    <div class="cac-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="cac-topbar">
            <div class="cac-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="cac-page-title">Mon accréditation</div>
                    <div class="cac-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="cac-topbar-right">
                <!-- Cloche notifications -->
                <div class="cac-notif-wrap" ref="notifWrap">
                    <button class="cac-notif-btn" @click="toggleNotif">
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
                        <span class="cac-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount > 9 ? "9+" : unreadCount }}
                        </span>
                    </button>
                    <div class="cac-notif-dropdown" v-if="notifOpen">
                        <div class="cac-notif-header">
                            <span>Notifications</span>
                            <button
                                class="cac-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="cac-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="cac-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="cac-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="cac-notif-dot" v-if="!n.read"></div>
                            <div class="cac-notif-content">
                                <div class="cac-notif-title">{{ n.title }}</div>
                                <div class="cac-notif-body">{{ n.body }}</div>
                                <div class="cac-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Avatar -->
                <div class="cac-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="cac-content">
            <!-- ── BANNIÈRE STATUT ACCRÉDITATION ── -->
            <div class="cac-banner" :class="bannerClass">
                <div class="cac-banner-icon">{{ bannerIcon }}</div>
                <div class="cac-banner-body">
                    <div class="cac-banner-title">{{ bannerTitle }}</div>
                    <div class="cac-banner-sub">{{ bannerSub }}</div>
                </div>
                <span
                    class="cac-badge"
                    :class="accredBadgeClass(accreditation)"
                >
                    {{ accredLabel(accreditation) }}
                </span>
            </div>

            <!-- ── CARTES TYPES D'ACCRÉDITATION ── -->
            <div class="cac-cards-grid">
                <!-- Domicile -->
                <div
                    class="cac-card"
                    :class="{
                        'cac-card-active': hasHome,
                        'cac-card-inactive': !hasHome,
                    }"
                >
                    <div class="cac-card-icon">🏠</div>
                    <div class="cac-card-body">
                        <div class="cac-card-title">Accréditation Domicile</div>
                        <div class="cac-card-desc">
                            Autorise les interventions chez les particuliers.
                            Accordée automatiquement après validation de votre
                            dossier.
                        </div>
                    </div>
                    <div class="cac-card-status">
                        <span
                            class="cac-status-chip"
                            :class="hasHome ? 'chip-active' : 'chip-inactive'"
                        >
                            {{ hasHome ? "✓ Active" : "✗ Inactive" }}
                        </span>
                    </div>
                </div>

                <!-- Entreprise -->
                <div
                    class="cac-card"
                    :class="{
                        'cac-card-active': hasBusiness,
                        'cac-card-inactive': !hasBusiness,
                    }"
                >
                    <div class="cac-card-icon">🏢</div>
                    <div class="cac-card-body">
                        <div class="cac-card-title">
                            Accréditation Entreprise
                        </div>
                        <div class="cac-card-desc">
                            Autorise les interventions en milieu professionnel.
                            Délivrée exclusivement par l'équipe Resotravo après
                            vérification complémentaire.
                        </div>
                    </div>
                    <div class="cac-card-status">
                        <span
                            class="cac-status-chip"
                            :class="
                                hasBusiness ? 'chip-active' : 'chip-inactive'
                            "
                        >
                            {{ hasBusiness ? "✓ Active" : "✗ Inactive" }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── CE QUE ÇA CHANGE ── -->
            <div class="cac-section">
                <div class="cac-section-title">
                    Ce que votre accréditation vous permet
                </div>
                <div class="cac-perms-list">
                    <div
                        class="cac-perm-item"
                        :class="hasHome ? 'perm-ok' : 'perm-no'"
                    >
                        <span class="cac-perm-icon">{{
                            hasHome ? "✅" : "🔒"
                        }}</span>
                        <div>
                            <div class="cac-perm-title">
                                Missions particuliers (Domicile)
                            </div>
                            <div class="cac-perm-sub">
                                Recevoir et accepter des missions chez des
                                clients particuliers.
                            </div>
                        </div>
                    </div>
                    <div
                        class="cac-perm-item"
                        :class="hasBusiness ? 'perm-ok' : 'perm-no'"
                    >
                        <span class="cac-perm-icon">{{
                            hasBusiness ? "✅" : "🔒"
                        }}</span>
                        <div>
                            <div class="cac-perm-title">
                                Missions entreprises
                            </div>
                            <div class="cac-perm-sub">
                                Accéder aux missions publiées par des
                                entreprises et y répondre.
                            </div>
                        </div>
                    </div>
                    <div
                        class="cac-perm-item"
                        :class="hasBusiness ? 'perm-ok' : 'perm-no'"
                    >
                        <span class="cac-perm-icon">{{
                            hasBusiness ? "✅" : "🔒"
                        }}</span>
                        <div>
                            <div class="cac-perm-title">
                                Appels d'offres entreprises
                            </div>
                            <div class="cac-perm-sub">
                                Candidater aux appels d'offres publiés par des
                                entreprises.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── DEMANDER L'ACCREDITATION ENTREPRISE ── -->
            <div
                class="cac-section"
                v-if="!hasBusiness && contractorStatus === 'approved'"
            >
                <div class="cac-section-title">
                    Obtenir l'accréditation Entreprise
                </div>
                <div class="cac-request-card">
                    <div class="cac-request-icon">🏅</div>
                    <div class="cac-request-body">
                        <div class="cac-request-title">
                            Demande d'accréditation Entreprise
                        </div>
                        <div class="cac-request-desc">
                            Pour obtenir l'accréditation Entreprise, envoyez une
                            demande à l'équipe Resotravo. Une vérification
                            complémentaire sera effectuée avant attribution.
                        </div>
                        <button
                            class="cac-btn cac-btn-orange"
                            @click="openRequestModal"
                            :disabled="requestSent"
                        >
                            {{
                                requestSent
                                    ? "✓ Demande envoyée"
                                    : "📩 Faire une demande"
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── PROFIL NON CERTIFIÉ ── -->
            <div class="cac-section" v-if="contractorStatus !== 'approved'">
                <div class="cac-alert-info">
                    <span class="cac-alert-icon">ℹ️</span>
                    <div>
                        <div class="cac-alert-title">
                            Dossier en attente de validation
                        </div>
                        <div class="cac-alert-sub">
                            Les accréditations sont attribuées après validation
                            complète de votre dossier par l'équipe Resotravo.
                            <a
                                :href="routes.dossier_page"
                                class="cac-alert-link"
                                >Compléter mon dossier →</a
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL DEMANDE ══════════════ -->
        <div
            class="cac-modal-overlay"
            v-if="requestModal.visible"
            @click.self="requestModal.visible = false"
        >
            <div class="cac-modal">
                <div class="cac-modal-header">
                    <div>
                        <h3>Demande d'accréditation Entreprise</h3>
                        <div class="cac-modal-sub">
                            Votre demande sera examinée par l'équipe Resotravo
                        </div>
                    </div>
                    <button
                        class="cac-modal-close"
                        @click="requestModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cac-modal-body">
                    <div class="cac-explain-item">
                        <span class="cac-explain-icon">🏢</span>
                        <div>
                            <div class="cac-explain-title">
                                Accréditation Entreprise
                            </div>
                            <div class="cac-explain-sub">
                                Cette accréditation vous permet d'intervenir en
                                milieu professionnel et d'accéder aux appels
                                d'offres publiés par les entreprises.
                            </div>
                        </div>
                    </div>
                    <div class="cac-field">
                        <label class="cac-label">Message (optionnel)</label>
                        <textarea
                            class="cac-textarea"
                            v-model="requestModal.message"
                            rows="3"
                            placeholder="Expliquez pourquoi vous souhaitez cette accréditation, vos expériences en milieu professionnel…"
                        ></textarea>
                    </div>
                </div>
                <div class="cac-modal-footer">
                    <button
                        class="cac-btn cac-btn-ghost"
                        @click="requestModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="cac-btn cac-btn-orange"
                        @click="submitRequest"
                        :disabled="requestModal.loading"
                    >
                        <div
                            class="cac-spinner"
                            v-if="requestModal.loading"
                        ></div>
                        <span v-else>📩 Envoyer la demande</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="cac-toast-container">
            <div
                class="cac-toast"
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
    name: "ContractorAccreditationComponent",

    props: {
        user: { type: Object, required: true },
        contractor: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            // Accréditation locale (mise à jour optimiste)
            accreditation: this.contractor.accreditation ?? "none",
            contractorStatus: this.contractor.status ?? "pending",
            requestSent: false,

            // Notifications
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            // Modal demande
            requestModal: {
                visible: false,
                message: "",
                loading: false,
            },

            toasts: [],
            toastId: 0,
            sidebarOpen: false,
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
            return (
                (this.user.name ?? "")
                    .split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) || "PR"
            );
        },
        hasHome() {
            return ["home", "both"].includes(this.accreditation);
        },
        hasBusiness() {
            return ["business", "both"].includes(this.accreditation);
        },

        bannerClass() {
            if (this.accreditation === "both") return "banner-both";
            if (this.accreditation === "home") return "banner-home";
            if (this.accreditation === "business") return "banner-business";
            return "banner-none";
        },
        bannerIcon() {
            if (this.accreditation === "both") return "🏅";
            if (this.accreditation === "home") return "🏠";
            if (this.accreditation === "business") return "🏢";
            return "⚠️";
        },
        bannerTitle() {
            if (this.accreditation === "both")
                return "Accréditation Domicile + Entreprise";
            if (this.accreditation === "home")
                return "Accréditation Domicile active";
            if (this.accreditation === "business")
                return "Accréditation Entreprise active";
            return "Aucune accréditation attribuée";
        },
        bannerSub() {
            if (this.accreditation === "both")
                return "Vous pouvez intervenir chez les particuliers et les entreprises.";
            if (this.accreditation === "home")
                return "Vous pouvez intervenir chez les particuliers.";
            if (this.accreditation === "business")
                return "Vous pouvez intervenir en milieu professionnel.";
            return this.contractorStatus === "approved"
                ? "Votre dossier est validé mais aucune accréditation n'a encore été attribuée."
                : "Complétez et faites valider votre dossier pour obtenir une accréditation.";
        },
    },

    methods: {
        accredLabel(val) {
            return (
                {
                    none: "🚫 Aucune",
                    home: "🏠 Domicile",
                    business: "🏢 Entreprise",
                    both: "🏅 Dom. + Entr.",
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

        // ── Modal demande ─────────────────────────────────────────
        openRequestModal() {
            this.requestModal = { visible: true, message: "", loading: false };
        },
        async submitRequest() {
            this.requestModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const res = await fetch(this.routes.accreditation_request, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        message: this.requestModal.message,
                    }),
                });
                if (!res.ok) throw new Error();
                this.requestSent = true;
                this.requestModal.visible = false;
                this.showToast(
                    "✅ Demande envoyée ! L'équipe Resotravo va examiner votre dossier.",
                    "success",
                );
            } catch {
                this.showToast(
                    "Erreur lors de l'envoi de la demande.",
                    "error",
                );
            } finally {
                this.requestModal.loading = false;
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
                    'meta[name="csrf-token"]',
                )?.content;
                await fetch(
                    this.routes.notifications_read.replace("{id}", n.id),
                    {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            Accept: "application/json",
                        },
                    },
                );
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

        // ── Sidebar ───────────────────────────────────────────────
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },

        // ── Toast ─────────────────────────────────────────────────
        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 4000);
        },
    },

    mounted() {
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
.cac-wrap {
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
.cac-topbar {
    background: var(--wh);
    border-bottom: 2px solid var(--grl);
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    position: sticky;
    top: 0;
    z-index: 50;
}
.cac-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cac-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cac-page-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.cac-page-sub {
    font-size: 12px;
    color: var(--gr);
}
.cac-page-sub strong {
    color: var(--dk);
}

.cac-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
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
}
.ad-burger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
}

/* ── NOTIFICATIONS ── */
.cac-notif-wrap {
    position: relative;
}
.cac-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 8px;
    transition: background 0.15s;
}
.cac-notif-btn:hover {
    background: var(--or3);
}
.cac-notif-btn svg {
    width: 22px;
    height: 22px;
}
.cac-notif-badge {
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
    padding: 0 3px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cac-notif-dropdown {
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
.cac-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
}
.cac-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.cac-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.cac-notif-item:hover,
.cac-notif-item.unread {
    background: var(--or3);
}
.cac-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.cac-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.cac-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cac-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}
.cac-notif-loading,
.cac-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--gr);
    text-align: center;
}

/* ── CONTENU ── */
.cac-content {
    max-width: 820px;
    margin: 0 auto;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── BANNIÈRE ── */
.cac-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    border-radius: 16px;
    border: 1.5px solid;
    flex-wrap: wrap;
}
.banner-none {
    background: #fef3c7;
    border-color: #fde68a;
}
.banner-home {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.banner-business {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.banner-both {
    background: var(--or3);
    border-color: #fed7aa;
}
.cac-banner-icon {
    font-size: 36px;
    flex-shrink: 0;
}
.cac-banner-body {
    flex: 1;
    min-width: 0;
}
.cac-banner-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.cac-banner-sub {
    font-size: 13px;
    color: var(--gr);
    margin-top: 4px;
    line-height: 1.5;
}

/* ── BADGES ACCRÉDITATION ── */
.cac-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 99px;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-none {
    background: #fee2e2;
    color: #b91c1c;
}
.badge-home {
    background: #dbeafe;
    color: #1d4ed8;
}
.badge-business {
    background: #dcfce7;
    color: #15803d;
}
.badge-both {
    background: var(--or3);
    color: var(--or2);
    border: 1.5px solid #fed7aa;
}

/* ── CARTES ── */
.cac-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (max-width: 600px) {
    .cac-cards-grid {
        grid-template-columns: 1fr;
    }
}

.cac-card {
    background: var(--wh);
    border-radius: 16px;
    border: 2px solid var(--grl);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: box-shadow 0.2s;
}
.cac-card-active {
    border-color: #22c55e;
    box-shadow: 0 4px 16px rgba(34, 197, 94, 0.1);
}
.cac-card-inactive {
    opacity: 0.75;
}
.cac-card-icon {
    font-size: 32px;
}
.cac-card-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.cac-card-desc {
    font-size: 12.5px;
    color: var(--gr);
    line-height: 1.6;
}
.cac-card-status {
    margin-top: auto;
}
.cac-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}
.chip-active {
    background: #dcfce7;
    color: #15803d;
}
.chip-inactive {
    background: #f1f5f9;
    color: #64748b;
}

/* ── SECTIONS ── */
.cac-section {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.cac-section-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
    padding-bottom: 8px;
    border-bottom: 2px solid var(--grl);
}

/* ── PERMISSIONS ── */
.cac-perms-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.cac-perm-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
}
.perm-ok {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.perm-no {
    opacity: 0.7;
}
.cac-perm-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 1px;
}
.cac-perm-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.cac-perm-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
    line-height: 1.5;
}

/* ── DEMANDE ENTREPRISE ── */
.cac-request-card {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}
.cac-request-icon {
    font-size: 32px;
    flex-shrink: 0;
}
.cac-request-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 6px;
}
.cac-request-desc {
    font-size: 12.5px;
    color: var(--gr);
    line-height: 1.6;
    margin-bottom: 14px;
}

/* ── ALERTE INFO ── */
.cac-alert-info {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 12px;
    padding: 16px 18px;
}
.cac-alert-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cac-alert-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e40af;
    margin-bottom: 4px;
}
.cac-alert-sub {
    font-size: 12.5px;
    color: #3b82f6;
    line-height: 1.6;
}
.cac-alert-link {
    color: var(--or);
    font-weight: 700;
    text-decoration: none;
}
.cac-alert-link:hover {
    text-decoration: underline;
}

/* ── MODAL ── */
.cac-modal-overlay {
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
.cac-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 480px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: cac-slide-up 0.25s ease;
}
@keyframes cac-slide-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.cac-modal-header {
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
.cac-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.cac-modal-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}
.cac-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
}
.cac-modal-body {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.cac-modal-footer {
    padding: 14px 24px;
    border-top: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
}

/* ── EXPLAIN ── */
.cac-explain-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: var(--wh);
    border-radius: 10px;
    padding: 12px 14px;
    border: 1.5px solid var(--grl);
}
.cac-explain-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cac-explain-title {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
}
.cac-explain-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
    line-height: 1.5;
}

/* ── FIELD ── */
.cac-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cac-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.cac-textarea {
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    resize: vertical;
    min-height: 80px;
    transition: border 0.15s;
}
.cac-textarea:focus {
    outline: none;
    border-color: var(--or);
}

/* ── BOUTONS ── */
.cac-btn {
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
.cac-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.cac-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.cac-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.cac-btn-ghost:hover {
    background: #d5c9c0;
}
.cac-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* ── SPINNER ── */
.cac-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cac-spin 0.7s linear infinite;
}
@keyframes cac-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── TOASTS ── */
.cac-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
}
.cac-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 220px;
    animation: cac-slide-up 0.3s ease;
}
.cac-toast.success {
    background: #16a34a;
}
.cac-toast.error {
    background: #dc2626;
}

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
    .cac-content {
        padding: 12px;
    }
    .cac-topbar {
        padding: 0 12px;
    }
    .cac-request-card {
        flex-direction: column;
    }
}
</style>
