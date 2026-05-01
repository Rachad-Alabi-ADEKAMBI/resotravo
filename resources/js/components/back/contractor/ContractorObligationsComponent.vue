<template>
    <div class="cob-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="cob-topbar">
            <div class="cob-topbar-left">
                <button class="ad-burger" @click="emitToggleSidebar" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="cob-page-title">Obligations fiscales</div>
                    <div class="cob-page-sub"><strong>{{ user.name }}</strong></div>
                </div>
            </div>
            <div class="cob-topbar-right">
                <!-- Cloche notifications -->
                <div class="cob-notif-wrap" ref="notifWrap">
                    <button class="cob-notif-btn" @click="toggleNotif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <span class="cob-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <div class="cob-notif-dropdown" v-if="notifOpen">
                        <div class="cob-notif-header">
                            <span>Notifications</span>
                            <button class="cob-notif-read-all" @click="markAllRead" v-if="unreadCount > 0">
                                Tout lire
                            </button>
                        </div>
                        <div class="cob-notif-loading" v-if="notifLoading">Chargement...</div>
                        <div class="cob-notif-empty" v-else-if="notifications.length === 0">
                            Aucune notification
                        </div>
                        <div class="cob-notif-item" v-for="n in notifications" :key="n.id"
                            :class="{ unread: !n.read }" @click="openNotif(n)">
                            <div class="cob-notif-dot" v-if="!n.read"></div>
                            <div class="cob-notif-content">
                                <div class="cob-notif-title">{{ n.title }}</div>
                                <div class="cob-notif-body">{{ n.body }}</div>
                                <div class="cob-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cob-avatar" :class="{ 'has-photo': user.photo_url }">
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        :alt="user.name"
                        class="cob-avatar-img"
                    />
                    <span v-else>{{ userInitials }}</span>
                </div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="cob-content">

            <!-- ── Info banner ── -->
            <div class="cob-info">
                <div class="cob-info-icon">📋</div>
                <div>
                    <strong>Pourquoi renseigner votre IFU ?</strong>
                    <p>
                        L'Identifiant Fiscal Unique (IFU) est un numero attribue par l'administration fiscale
                        beninoise a toute personne physique ou morale exercant une activite economique.
                        Il est obligatoire pour exercer legalement et permet a Mesotravo de se conformer
                        aux exigences fiscales en vigueur.
                    </p>
                </div>
            </div>

            <!-- ── Formulaire IFU ── -->
            <div class="cob-section">
                <div class="cob-section-header">
                    <div class="cob-section-icon">🏛️</div>
                    <div>
                        <h2 class="cob-section-title">Identifiant Fiscal Unique (IFU)</h2>
                        <p class="cob-section-desc">
                            Saisissez votre numero IFU tel qu'il figure sur votre attestation fiscale.
                        </p>
                    </div>
                </div>

                <div class="cob-field">
                    <label class="cob-label" for="ifu">Numero IFU</label>
                    <div class="cob-input-wrap">
                        <input
                            id="ifu"
                            type="text"
                            class="cob-input"
                            v-model="form.ifu"
                            placeholder="Ex : 3201XXXXXXXX"
                            maxlength="50"
                        />
                    </div>
                    <p class="cob-hint">
                        Generalement compose de 13 chiffres. Vous le trouverez sur votre attestation IFU delivree par la DGI.
                    </p>
                </div>

                <!-- Status badge -->
                <div class="cob-status" v-if="contractor.ifu">
                    <span class="cob-status-badge cob-status-ok">IFU renseigne</span>
                </div>
                <div class="cob-status" v-else>
                    <span class="cob-status-badge cob-status-pending">IFU non renseigne</span>
                </div>

                <div class="cob-actions">
                    <button
                        class="cob-btn cob-btn-orange success-action"
                        :disabled="saving || !form.ifu?.trim()"
                        @click="saveIfu"
                    >
                        <span v-if="saving" class="cob-btn-spinner"></span>
                        {{ saving ? "Enregistrement..." : "Enregistrer" }}
                    </button>
                    <transition name="cob-fade">
                        <span class="cob-save-ok" v-if="saveSuccess">Enregistre avec succes</span>
                    </transition>
                    <span class="cob-save-err" v-if="saveError">{{ saveError }}</span>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "ContractorObligationsComponent",

    props: {
        user:       { type: Object, required: true },
        contractor: { type: Object, required: true },
        routes:     { type: Object, required: true },
    },

    data() {
        return {
            form: {
                ifu: this.contractor.ifu || "",
            },
            saving: false,
            saveSuccess: false,
            saveError: null,

            // Sidebar
            sidebarOpen: false,

            // Notifications
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
    },

    mounted() {
        this.fetchNotifications();
        document.addEventListener("click", this.closeNotifOutside);
    },

    beforeUnmount() {
        document.removeEventListener("click", this.closeNotifOutside);
    },

    methods: {
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
        },

        async saveIfu() {
            this.saving = true;
            this.saveSuccess = false;
            this.saveError = null;

            try {
                const res = await fetch(this.routes.ifu_update, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ ifu: this.form.ifu.trim() }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => null);
                    throw new Error(err?.message || "Erreur lors de la sauvegarde.");
                }

                this.contractor.ifu = this.form.ifu.trim();
                this.saveSuccess = true;
                setTimeout(() => (this.saveSuccess = false), 3000);
            } catch (e) {
                this.saveError = e.message;
                setTimeout(() => (this.saveError = null), 5000);
            } finally {
                this.saving = false;
            }
        },

        /* ── Notifications ── */
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications);
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {} finally {
                this.notifLoading = false;
            }
        },
        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },
        closeNotifOutside(e) {
            if (this.$refs.notifWrap && !this.$refs.notifWrap.contains(e.target)) {
                this.notifOpen = false;
            }
        },
        async markAllRead() {
            try {
                await fetch(this.routes.notifications_all, {
                    method: "PATCH",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                this.notifications.forEach((n) => (n.read = true));
                this.unreadCount = 0;
            } catch {}
        },
        openNotif(n) {
            if (n.link) window.location.href = n.link;
        },
    },
};
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   CONTRACTOR OBLIGATIONS FISCALES
   ═══════════════════════════════════════════════ */

.cob-wrap {
    min-height: 100vh;
    background: #f7f7fa;
}

/* ── TOPBAR ── */
.cob-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    height: 64px;
    background: #fff;
    border-bottom: 1px solid #ececf1;
    position: sticky;
    top: 0;
    z-index: 50;
}
.cob-topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.cob-topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.cob-page-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
}
.cob-page-sub {
    font-size: 13px;
    color: #888;
}

/* ── AVATAR ── */
.cob-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--or, #e67e22);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    overflow: hidden;
    flex-shrink: 0;
}
.cob-avatar.has-photo {
    background: #fff;
}
.cob-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* ── NOTIFICATIONS ── */
.cob-notif-wrap { position: relative; }
.cob-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    position: relative;
    padding: 6px;
    color: #555;
}
.cob-notif-btn svg { width: 22px; height: 22px; }
.cob-notif-badge {
    position: absolute;
    top: 0;
    right: -2px;
    background: #e74c3c;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}
.cob-notif-dropdown {
    position: absolute;
    right: 0;
    top: 42px;
    width: 340px;
    max-height: 400px;
    overflow-y: auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    z-index: 100;
    border: 1px solid #ececf1;
}
.cob-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-weight: 700;
    font-size: 14px;
}
.cob-notif-read-all {
    background: none;
    border: none;
    color: var(--or, #e67e22);
    font-size: 12px;
    cursor: pointer;
    font-weight: 600;
}
.cob-notif-loading,
.cob-notif-empty {
    padding: 24px;
    text-align: center;
    color: #999;
    font-size: 13px;
}
.cob-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f8f8f8;
    transition: background .15s;
}
.cob-notif-item:hover { background: #fafafa; }
.cob-notif-item.unread { background: #fff8f0; }
.cob-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or, #e67e22);
    flex-shrink: 0;
    margin-top: 6px;
}
.cob-notif-title { font-weight: 600; font-size: 13px; color: #1a1a2e; }
.cob-notif-body { font-size: 12px; color: #666; margin-top: 2px; }
.cob-notif-ago { font-size: 11px; color: #aaa; margin-top: 4px; }

/* ── CONTENT ── */
.cob-content {
    padding: 32px;
    max-width: 720px;
}

/* ── INFO BANNER ── */
.cob-info {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    background: #eef6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 24px;
}
.cob-info-icon {
    font-size: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}
.cob-info strong {
    font-size: 14px;
    color: #1a1a2e;
}
.cob-info p {
    font-size: 13px;
    color: #555;
    margin: 6px 0 0;
    line-height: 1.6;
}

/* ── SECTION ── */
.cob-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #ececf1;
    padding: 28px 32px;
}
.cob-section-header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 24px;
}
.cob-section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cob-section-title {
    font-size: 17px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 4px;
}
.cob-section-desc {
    font-size: 13px;
    color: #888;
    margin: 0;
}

/* ── FIELD ── */
.cob-field {
    margin-bottom: 20px;
}
.cob-label {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    display: block;
    margin-bottom: 6px;
}
.cob-input-wrap {
    max-width: 360px;
}
.cob-input {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #ddd;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    letter-spacing: 1px;
    transition: border-color .2s;
    outline: none;
}
.cob-input:focus {
    border-color: var(--or, #e67e22);
}
.cob-input::placeholder {
    font-weight: 400;
    color: #bbb;
    letter-spacing: 0;
}
.cob-hint {
    font-size: 12px;
    color: #999;
    margin: 6px 0 0;
}

/* ── STATUS ── */
.cob-status {
    margin-bottom: 20px;
}
.cob-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}
.cob-status-ok {
    background: #ecfdf5;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.cob-status-ok::before {
    content: "✓";
}
.cob-status-pending {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #fed7aa;
}
.cob-status-pending::before {
    content: "⚠";
}

/* ── ACTIONS ── */
.cob-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}
.cob-btn {
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all .2s;
}
.cob-btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}
.cob-btn-orange {
    background: var(--or, #e67e22);
    color: #fff;
}
.cob-btn-orange:hover:not(:disabled) {
    filter: brightness(1.08);
}
.cob-btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cob-spin .6s linear infinite;
}
@keyframes cob-spin {
    to { transform: rotate(360deg); }
}

.cob-save-ok {
    font-size: 13px;
    color: #16a34a;
    font-weight: 600;
}
.cob-save-err {
    font-size: 13px;
    color: #dc2626;
    font-weight: 600;
}

/* ── FADE TRANSITION ── */
.cob-fade-enter-active,
.cob-fade-leave-active { transition: opacity .3s; }
.cob-fade-enter-from,
.cob-fade-leave-to { opacity: 0; }

/* ── BURGER ── */
.ad-burger {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
}
.ad-burger span {
    width: 20px;
    height: 2px;
    background: #1a1a2e;
    border-radius: 2px;
}

/* ── RESPONSIVE ── */
@media (max-width: 899px) {
    .ad-burger { display: flex; }
    .cob-topbar {
        height: auto;
        min-height: 84px;
        padding: 14px 16px 12px;
        align-items: center;
    }
    .cob-content { padding: 20px 16px; }
    .cob-section { padding: 20px; }
    .cob-info { flex-direction: column; gap: 8px; }
}
</style>
