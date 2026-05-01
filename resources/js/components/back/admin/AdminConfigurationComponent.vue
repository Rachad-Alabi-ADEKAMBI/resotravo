<template>
    <div class="acfg-wrap">
        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• TOPBAR â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="acfg-topbar">
            <div class="acfg-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="acfg-page-title">Configuration</div>
                    <div class="acfg-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="acfg-topbar-right">
                <!-- Cloche notifications -->
                <div class="acfg-notif-wrap" ref="notifWrap">
                    <button class="acfg-notif-btn" @click="toggleNotif">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <span class="acfg-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <div class="acfg-notif-dropdown" v-if="notifOpen">
                        <div class="acfg-notif-header">
                            <span>Notifications</span>
                            <button class="acfg-notif-read-all" @click="markAllRead" v-if="unreadCount > 0">
                                Tout lire
                            </button>
                        </div>
                        <div class="acfg-notif-loading" v-if="notifLoading">Chargement...</div>
                        <div class="acfg-notif-empty" v-else-if="notifications.length === 0">
                            Aucune notification
                        </div>
                        <div class="acfg-notif-item" v-for="n in notifications" :key="n.id"
                            :class="{ unread: !n.read }" @click="openNotif(n)">
                            <div class="acfg-notif-dot" v-if="!n.read"></div>
                            <div class="acfg-notif-content">
                                <div class="acfg-notif-title">{{ n.title }}</div>
                                <div class="acfg-notif-body">{{ n.body }}</div>
                                <div class="acfg-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="acfg-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• CONTENU â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="acfg-content">
            <!-- Loader -->
            <div class="acfg-loader" v-if="loading">
                <div class="acfg-spinner"></div>
                <p>Chargement de la configuration...</p>
            </div>

            <!-- Erreur -->
            <div class="acfg-alert-error" v-else-if="loadError">
                {{ loadError }}
                <button class="acfg-btn acfg-btn-ghost" @click="fetchSettings">Reessayer</button>
            </div>

            <!-- Formulaire -->
            <div v-else class="acfg-form-zone">
                <!-- â”€â”€ Frais de diagnostic â”€â”€ -->
                <div class="acfg-section">
                    <div class="acfg-section-header">
                        <div class="acfg-section-icon">&#128269;</div>
                        <div>
                            <h2 class="acfg-section-title">Frais de diagnostic</h2>
                            <p class="acfg-section-desc">
                                Montant fixe du diagnostic applique automatiquement a tous les devis prestataires.
                            </p>
                        </div>
                    </div>
                    <div class="acfg-fields">
                        <div class="acfg-field">
                            <label class="acfg-label" for="diagnostic_fee">
                                Montant du diagnostic
                            </label>
                            <p class="acfg-hint">
                                Ce montant sera automatiquement pre-rempli dans le devis du prestataire et soumis au client.
                            </p>
                            <div class="acfg-input-group">
                                <input
                                    id="diagnostic_fee"
                                    type="number"
                                    class="acfg-input"
                                    v-model.number="form.diagnostic_fee"
                                    min="0"
                                    step="500"
                                />
                                <span class="acfg-input-suffix">FCFA</span>
                            </div>
                        </div>
                    </div>
                    <div class="acfg-actions">
                        <button class="acfg-btn acfg-btn-green" :disabled="saving" @click="saveSettings">
                            <span v-if="saving" class="acfg-btn-spinner"></span>
                            {{ saving ? "Enregistrement..." : "Enregistrer" }}
                        </button>
                        <transition name="acfg-fade">
                            <span class="acfg-save-ok" v-if="saveSuccess">Enregistre avec succes</span>
                        </transition>
                        <span class="acfg-save-err" v-if="saveError">{{ saveError }}</span>
                    </div>
                </div>

                <!-- â”€â”€ Taux de commission â”€â”€ -->
                <div class="acfg-section">
                    <div class="acfg-section-header">
                        <div class="acfg-section-icon">%</div>
                        <div>
                            <h2 class="acfg-section-title">Taux de commission</h2>
                            <p class="acfg-section-desc">
                                Definissez le pourcentage preleve par Mesotravo sur chaque type de prestation.
                            </p>
                        </div>
                    </div>

                    <div class="acfg-fields">
                        <div class="acfg-field">
                            <label class="acfg-label" for="commission_diagnostic">
                                Commission sur le diagnostic
                            </label>
                            <p class="acfg-hint">
                                Pourcentage preleve sur le montant du diagnostic.
                            </p>
                            <div class="acfg-input-group">
                                <input
                                    id="commission_diagnostic"
                                    type="number"
                                    class="acfg-input"
                                    v-model.number="form.commission_diagnostic"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                                <span class="acfg-input-suffix">%</span>
                            </div>
                        </div>

                        <div class="acfg-field">
                            <label class="acfg-label" for="commission_main_oeuvre">
                                Commission sur la main d'oeuvre
                            </label>
                            <p class="acfg-hint">
                                Pourcentage preleve sur le montant de la main d'oeuvre.
                            </p>
                            <div class="acfg-input-group">
                                <input
                                    id="commission_main_oeuvre"
                                    type="number"
                                    class="acfg-input"
                                    v-model.number="form.commission_main_oeuvre"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                />
                                <span class="acfg-input-suffix">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="acfg-actions">
                        <button
                            class="acfg-btn acfg-btn-green"
                            :disabled="saving"
                            @click="saveSettings"
                        >
                            <span v-if="saving" class="acfg-btn-spinner"></span>
                            {{ saving ? "Enregistrement..." : "Enregistrer" }}
                        </button>
                        <transition name="acfg-fade">
                            <span class="acfg-save-ok" v-if="saveSuccess">
                                Enregistre avec succes
                            </span>
                        </transition>
                        <span class="acfg-save-err" v-if="saveError">{{ saveError }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminConfigurationComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            loading: false,
            loadError: null,
            saving: false,
            saveSuccess: false,
            saveError: null,

            form: {
                diagnostic_fee: 5000,
                commission_diagnostic: 10,
                commission_main_oeuvre: 10,
            },

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
        this.fetchSettings();
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

        async fetchSettings() {
            this.loading = true;
            this.loadError = null;
            try {
                const res = await fetch(this.routes.settings_index);
                if (!res.ok) throw new Error("Erreur serveur");
                const data = await res.json();
                this.form.diagnostic_fee        = parseFloat(data.diagnostic_fee)        || 5000;
                this.form.commission_diagnostic = parseFloat(data.commission_diagnostic) || 10;
                this.form.commission_main_oeuvre= parseFloat(data.commission_main_oeuvre)|| 10;
            } catch (e) {
                this.loadError = "Impossible de charger la configuration.";
            } finally {
                this.loading = false;
            }
        },

        async saveSettings() {
            this.saving = true;
            this.saveSuccess = false;
            this.saveError = null;

            try {
                const res = await fetch(this.routes.settings_update, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        diagnostic_fee:        this.form.diagnostic_fee,
                        commission_diagnostic: this.form.commission_diagnostic,
                        commission_main_oeuvre:this.form.commission_main_oeuvre,
                    }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => null);
                    throw new Error(
                        err?.message || "Erreur lors de la sauvegarde."
                    );
                }

                this.saveSuccess = true;
                setTimeout(() => (this.saveSuccess = false), 3000);
            } catch (e) {
                this.saveError = e.message;
                setTimeout(() => (this.saveError = null), 5000);
            } finally {
                this.saving = false;
            }
        },

        /* â”€â”€ Notifications â”€â”€ */
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications);
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {
                // silently fail
            } finally {
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
                await fetch(this.routes.notifications_all, { method: "PATCH",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    }
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
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   ADMIN CONFIGURATION
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

.acfg-wrap {
    min-height: 100vh;
    background: #f7f7fa;
}

/* â”€â”€ TOPBAR â”€â”€ */
.acfg-topbar {
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
.acfg-topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.acfg-topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.acfg-page-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
}
.acfg-page-sub {
    font-size: 13px;
    color: #888;
}

/* â”€â”€ AVATAR â”€â”€ */
.acfg-avatar {
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
}

/* â”€â”€ NOTIFICATIONS â”€â”€ */
.acfg-notif-wrap {
    position: relative;
}
.acfg-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    position: relative;
    padding: 6px;
    color: #555;
}
.acfg-notif-btn svg {
    width: 22px;
    height: 22px;
}
.acfg-notif-badge {
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
.acfg-notif-dropdown {
    position: absolute;
    right: 0;
    top: 42px;
    width: 340px;
    max-height: 400px;
    overflow-y: auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 100;
    border: 1px solid #ececf1;
}
.acfg-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-weight: 700;
    font-size: 14px;
}
.acfg-notif-read-all {
    background: none;
    border: none;
    color: var(--or, #e67e22);
    font-size: 12px;
    cursor: pointer;
    font-weight: 600;
}
.acfg-notif-loading,
.acfg-notif-empty {
    padding: 24px;
    text-align: center;
    color: #999;
    font-size: 13px;
}
.acfg-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f8f8f8;
    transition: background 0.15s;
}
.acfg-notif-item:hover {
    background: #fafafa;
}
.acfg-notif-item.unread {
    background: #fff8f0;
}
.acfg-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or, #e67e22);
    flex-shrink: 0;
    margin-top: 6px;
}
.acfg-notif-title {
    font-weight: 600;
    font-size: 13px;
    color: #1a1a2e;
}
.acfg-notif-body {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}
.acfg-notif-ago {
    font-size: 11px;
    color: #aaa;
    margin-top: 4px;
}

/* â”€â”€ CONTENT AREA â”€â”€ */
.acfg-content {
    padding: 32px;
    max-width: 720px;
}

/* â”€â”€ LOADER â”€â”€ */
.acfg-loader {
    text-align: center;
    padding: 60px 0;
    color: #888;
}
.acfg-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid #ececf1;
    border-top-color: var(--or, #e67e22);
    border-radius: 50%;
    animation: acfg-spin 0.7s linear infinite;
    margin: 0 auto 12px;
}
@keyframes acfg-spin {
    to {
        transform: rotate(360deg);
    }
}

/* â”€â”€ ERROR â”€â”€ */
.acfg-alert-error {
    background: #fff5f5;
    border: 1px solid #fecaca;
    color: #b91c1c;
    padding: 16px 20px;
    border-radius: 10px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* â”€â”€ SECTION â”€â”€ */
.acfg-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #ececf1;
    padding: 28px 32px;
}
.acfg-section-header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 28px;
}
.acfg-section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: var(--or, #e67e22);
    font-size: 20px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.acfg-section-title {
    font-size: 17px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 4px;
}
.acfg-section-desc {
    font-size: 13px;
    color: #888;
    margin: 0;
}

/* â”€â”€ FIELDS â”€â”€ */
.acfg-fields {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.acfg-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.acfg-label {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
}
.acfg-hint {
    font-size: 12px;
    color: #999;
    margin: 0 0 4px;
}
.acfg-input-group {
    display: flex;
    align-items: center;
    border: 1.5px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
    max-width: 200px;
    transition: border-color 0.2s;
}
.acfg-input-group:focus-within {
    border-color: var(--or, #e67e22);
}
.acfg-select-group {
    display: flex;
    align-items: center;
    border: 1.5px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
    max-width: 260px;
    transition: border-color 0.2s;
}
.acfg-select-group:focus-within {
    border-color: var(--or, #e67e22);
}
.acfg-input {
    border: none;
    outline: none;
    padding: 10px 14px;
    font-size: 16px;
    font-weight: 600;
    width: 100%;
    background: transparent;
    color: #1a1a2e;
}
.acfg-input::-webkit-inner-spin-button,
.acfg-input::-webkit-outer-spin-button {
    opacity: 1;
}
.acfg-input-suffix {
    padding: 10px 14px;
    background: #f7f7fa;
    color: #888;
    font-weight: 700;
    font-size: 15px;
    border-left: 1.5px solid #ddd;
}

/* â”€â”€ ACTIONS â”€â”€ */
.acfg-actions {
    margin-top: 28px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.acfg-btn {
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.acfg-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.acfg-btn-orange {
    background: var(--or, #e67e22);
    color: #fff;
}
.acfg-btn-orange:hover:not(:disabled) {
    filter: brightness(1.08);
}
.acfg-btn-green {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
}
.acfg-btn-green:hover:not(:disabled) {
    filter: brightness(1.06);
}
.acfg-btn-ghost {
    background: transparent;
    color: var(--or, #e67e22);
    border: 1.5px solid var(--or, #e67e22);
}
.acfg-btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: acfg-spin 0.6s linear infinite;
}

.acfg-save-ok {
    font-size: 13px;
    color: #16a34a;
    font-weight: 600;
}
.acfg-save-err {
    font-size: 13px;
    color: #dc2626;
    font-weight: 600;
}

/* â”€â”€ FADE TRANSITION â”€â”€ */
.acfg-fade-enter-active,
.acfg-fade-leave-active {
    transition: opacity 0.3s;
}
.acfg-fade-enter-from,
.acfg-fade-leave-to {
    opacity: 0;
}

/* â”€â”€ BURGER (shared) â”€â”€ */
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

/* â”€â”€ RESPONSIVE â”€â”€ */
@media (max-width: 899px) {
    .ad-burger {
        display: flex;
    }
    .acfg-topbar {
        padding: 0 16px;
    }
    .acfg-content {
        padding: 20px 16px;
    }
    .acfg-section {
        padding: 20px;
    }
}
</style>
