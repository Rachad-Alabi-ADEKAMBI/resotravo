<template>
    <div class="pm-wrap">
        <!-- ══════════════════════════════════
             TOPBAR
        ══════════════════════════════════ -->
        <div class="pm-topbar">
            <div class="pm-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="pm-page-title">Paramètres</div>
                    <div class="pm-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="pm-topbar-right">
                <!-- Cloche notifications -->
                <div class="pm-notif-wrap" ref="notifWrap">
                    <button class="pm-notif-btn" @click="toggleNotif">
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
                        <span class="pm-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount
                        }}</span>
                    </button>
                    <div class="pm-notif-dropdown" v-if="notifOpen">
                        <div class="pm-notif-header">
                            <span>Notifications</span>
                            <button
                                class="pm-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="pm-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="pm-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="pm-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="pm-notif-dot" v-if="!n.read"></div>
                            <div class="pm-notif-content">
                                <div class="pm-notif-title">{{ n.title }}</div>
                                <div class="pm-notif-body">{{ n.body }}</div>
                                <div class="pm-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Avatar -->
                <div class="pm-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════════════════════════
             CONTENU
        ══════════════════════════════════ -->
        <div class="pm-content">
            <!-- ── En-tête section ── -->
            <div class="pm-section-header">
                <div class="pm-section-icon">
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="11"
                            rx="2"
                            ry="2"
                        />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <div>
                    <div class="pm-section-title">Sécurité du compte</div>
                    <div class="pm-section-sub">
                        Modifiez votre mot de passe de connexion
                    </div>
                </div>
            </div>

            <!-- ── Carte mot de passe ── -->
            <div class="pm-card">
                <!-- Alerte succès -->
                <transition name="pm-fade">
                    <div class="pm-alert pm-alert--success" v-if="successMsg">
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        {{ successMsg }}
                    </div>
                </transition>

                <!-- Alerte erreur globale -->
                <transition name="pm-fade">
                    <div class="pm-alert pm-alert--error" v-if="globalError">
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        {{ globalError }}
                    </div>
                </transition>

                <form @submit.prevent="submitPassword" autocomplete="off">
                    <!-- Mot de passe actuel -->
                    <div
                        class="pm-field"
                        :class="{ 'pm-field--error': errors.current_password }"
                    >
                        <label class="pm-label">Mot de passe actuel</label>
                        <div class="pm-input-wrap">
                            <svg
                                class="pm-input-icon"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="3"
                                    y="11"
                                    width="18"
                                    height="11"
                                    rx="2"
                                    ry="2"
                                />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input
                                class="pm-input"
                                :type="show.current ? 'text' : 'password'"
                                v-model="form.current_password"
                                placeholder="Votre mot de passe actuel"
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                class="pm-eye"
                                @click="show.current = !show.current"
                                tabindex="-1"
                            >
                                <svg
                                    v-if="!show.current"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                    />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg
                                    v-else
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                                    />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        <div
                            class="pm-field-error"
                            v-if="errors.current_password"
                        >
                            {{ errors.current_password[0] }}
                        </div>
                    </div>

                    <!-- Nouveau mot de passe -->
                    <div
                        class="pm-field"
                        :class="{ 'pm-field--error': errors.password }"
                    >
                        <label class="pm-label">Nouveau mot de passe</label>
                        <div class="pm-input-wrap">
                            <svg
                                class="pm-input-icon"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                                />
                            </svg>
                            <input
                                class="pm-input"
                                :type="show.password ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="Minimum 8 caractères"
                                autocomplete="new-password"
                                @input="checkStrength"
                            />
                            <button
                                type="button"
                                class="pm-eye"
                                @click="show.password = !show.password"
                                tabindex="-1"
                            >
                                <svg
                                    v-if="!show.password"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                    />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg
                                    v-else
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                                    />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        <!-- Jauge de force -->
                        <div class="pm-strength" v-if="form.password">
                            <div class="pm-strength-bars">
                                <div
                                    class="pm-strength-bar"
                                    :class="strengthClass(1)"
                                ></div>
                                <div
                                    class="pm-strength-bar"
                                    :class="strengthClass(2)"
                                ></div>
                                <div
                                    class="pm-strength-bar"
                                    :class="strengthClass(3)"
                                ></div>
                                <div
                                    class="pm-strength-bar"
                                    :class="strengthClass(4)"
                                ></div>
                            </div>
                            <span
                                class="pm-strength-label"
                                :class="'pm-strength-label--' + strengthLevel"
                                >{{ strengthLabel }}</span
                            >
                        </div>
                        <div class="pm-field-error" v-if="errors.password">
                            {{ errors.password[0] }}
                        </div>
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div
                        class="pm-field"
                        :class="{
                            'pm-field--error':
                                errors.password_confirmation || confirmMismatch,
                        }"
                    >
                        <label class="pm-label"
                            >Confirmer le nouveau mot de passe</label
                        >
                        <div class="pm-input-wrap">
                            <svg
                                class="pm-input-icon"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                                />
                            </svg>
                            <input
                                class="pm-input"
                                :type="show.confirm ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                placeholder="Répétez le nouveau mot de passe"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="pm-eye"
                                @click="show.confirm = !show.confirm"
                                tabindex="-1"
                            >
                                <svg
                                    v-if="!show.confirm"
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                    />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg
                                    v-else
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                                    />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        <div class="pm-field-error" v-if="confirmMismatch">
                            Les mots de passe ne correspondent pas.
                        </div>
                        <div
                            class="pm-field-error"
                            v-else-if="errors.password_confirmation"
                        >
                            {{ errors.password_confirmation[0] }}
                        </div>
                    </div>

                    <!-- Bouton submit -->
                    <div class="pm-actions">
                        <button
                            type="submit"
                            class="pm-btn-submit success-action"
                            :disabled="loading || confirmMismatch"
                        >
                            <span v-if="loading" class="pm-spinner"></span>
                            <span v-else class="pm-btn-submit-content">
                                <svg
                                    width="15"
                                    height="15"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"
                                    />
                                    <polyline points="17 21 17 13 7 13 7 21" />
                                    <polyline points="7 3 7 8 15 8" />
                                </svg>
                                Enregistrer le mot de passe
                            </span>
                        </button>
                        <button
                            type="button"
                            class="pm-btn-cancel"
                            @click="resetForm"
                            :disabled="loading"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Carte infos compte (lecture seule) ── -->
            <div class="pm-info-card">
                <div class="pm-info-card-title">Informations du compte</div>
                <div class="pm-info-row">
                    <span class="pm-info-label">Nom</span>
                    <span class="pm-info-val">{{ user.name }}</span>
                </div>
                <div class="pm-info-row">
                    <span class="pm-info-label">Email</span>
                    <span class="pm-info-val">{{ user.email }}</span>
                </div>
                <div class="pm-info-row">
                    <span class="pm-info-label">Rôle</span>
                    <span
                        class="pm-badge-role"
                        :class="'pm-badge-role--' + user.role"
                        >{{ roleLabel }}</span
                    >
                </div>
                <div class="pm-info-row" v-if="user.status">
                    <span class="pm-info-label">Statut</span>
                    <span
                        class="pm-badge-status"
                        :class="'pm-badge-status--' + statusClass"
                    >
                        {{ statusLabel }}
                    </span>
                </div>
            </div>
        </div>
        <!-- fin pm-content -->
    </div>
</template>

<script>
export default {
    name: "ParametersComponent",

    props: {
        /**
         * Objet utilisateur connecté
         * { id, name, email, role, status }
         * Partagé par : client, contractor, admin
         */
        user: {
            type: Object,
            required: true,
        },
        /**
         * URLs des routes Laravel
         * {
         *   notifications:      '/notifications',
         *   notifications_all:  '/notifications/read-all',
         *   update_password:    '/profile/password'   ← route PATCH Breeze
         * }
         */
        routes: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            // ── Formulaire ──────────────────────────────
            form: {
                current_password: "",
                password: "",
                password_confirmation: "",
            },
            errors: {},
            successMsg: "",
            globalError: "",
            loading: false,

            // ── Visibilité mots de passe ─────────────────
            show: { current: false, password: false, confirm: false },

            // ── Force du mot de passe (1-4) ─────────────
            strength: 0,

            // ── Notifications ────────────────────────────
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

        roleLabel() {
            return (
                {
                    client: "Client",
                    contractor: "Prestataire",
                    admin: "Administrateur",
                    talent: "Talent",
                }[this.user.role] || this.user.role
            );
        },

        statusLabel() {
            return (
                {
                    active: "Actif",
                    approved: "Approuvé",
                    pending: "En attente de validation",
                    suspended: "Suspendu",
                    rejected: "Refusé",
                }[this.user.status] || this.user.status
            );
        },

        statusClass() {
            if (["active", "approved"].includes(this.user.status)) {
                return "active";
            }
            if (this.user.status === "pending") return "pending";
            return "suspended";
        },

        confirmMismatch() {
            return (
                this.form.password_confirmation.length > 0 &&
                this.form.password !== this.form.password_confirmation
            );
        },

        strengthLevel() {
            if (this.strength <= 1) return "weak";
            if (this.strength === 2) return "fair";
            if (this.strength === 3) return "good";
            return "strong";
        },

        strengthLabel() {
            return {
                weak: "Faible",
                fair: "Moyen",
                good: "Bon",
                strong: "Fort",
            }[this.strengthLevel];
        },
    },

    mounted() {
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

        // ── Soumission du formulaire ──────────────────────────────
        async submitPassword() {
            this.errors = {};
            this.successMsg = "";
            this.globalError = "";

            if (this.confirmMismatch) return;

            if (
                !this.form.current_password ||
                !this.form.password ||
                !this.form.password_confirmation
            ) {
                this.globalError = "Veuillez remplir tous les champs.";
                return;
            }

            this.loading = true;
            try {
                const csrf =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "";
                // Route PATCH Breeze : PUT /profile/password
                // Adaptez si vous avez une route personnalisée via this.routes.update_password
                const url = this.routes.update_password ?? "/profile/password";

                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(this.form),
                });

                if (res.status === 422) {
                    const data = await res.json();
                    this.errors = data.errors ?? {};
                    this.globalError = data.message ?? "Erreur de validation.";
                    return;
                }

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    this.globalError =
                        data.message ?? "Une erreur est survenue.";
                    return;
                }

                this.successMsg = "Mot de passe mis à jour avec succès !";
                this.resetForm();
                // Effacer le message de succès après 4 s
                setTimeout(() => {
                    this.successMsg = "";
                }, 4000);
            } catch {
                this.globalError =
                    "Impossible de contacter le serveur. Vérifiez votre connexion.";
            } finally {
                this.loading = false;
            }
        },

        resetForm() {
            this.form = {
                current_password: "",
                password: "",
                password_confirmation: "",
            };
            this.errors = {};
            this.globalError = "";
            this.strength = 0;
            this.show = { current: false, password: false, confirm: false };
        },

        // ── Jauge de force du mot de passe ────────────────────────
        checkStrength() {
            const p = this.form.password;
            let score = 0;
            if (p.length >= 8) score++;
            if (p.length >= 12) score++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
            if (/[0-9]/.test(p)) score++;
            if (/[^A-Za-z0-9]/.test(p)) score++;
            this.strength = Math.min(4, score);
        },

        strengthClass(bar) {
            if (this.strength < bar) return "";
            return (
                {
                    1: "pm-bar--weak",
                    2: "pm-bar--fair",
                    3: "pm-bar--good",
                    4: "pm-bar--strong",
                }[this.strength] ?? ""
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
                this.unreadCount =
                    data.unread_count ??
                    this.notifications.filter((n) => !n.read).length;
            } catch (_) {
                // silencieux
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
/* ══════════════════════════════════════
   LAYOUT GÉNÉRAL
══════════════════════════════════════ */
.pm-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* ══════════════════════════════════════
   TOPBAR
══════════════════════════════════════ */
.pm-topbar {
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
.pm-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.pm-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pm-page-title {
    font-size: 17px;
    font-weight: 700;
    color: #1c1412;
    line-height: 1.2;
}
.pm-page-sub {
    font-size: 12.5px;
    color: #8a7d78;
    margin-top: 1px;
}
.pm-page-sub strong {
    color: #4a3f3a;
}

/* ── Burger mobile ── */
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
    transition: all 0.2s;
}
@media (max-width: 899px) {
    .ad-burger {
        display: flex !important;
    }
}

/* ── Avatar initiales ── */
.pm-avatar {
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
.pm-notif-wrap {
    position: relative;
}
.pm-notif-btn {
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
.pm-notif-btn:hover {
    background: #fef3e2;
}
.pm-notif-btn svg {
    width: 20px;
    height: 20px;
}
.pm-notif-badge {
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
.pm-notif-dropdown {
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
.pm-notif-header {
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
.pm-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: #f97316;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.pm-notif-loading,
.pm-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: #4a3f3a;
}
.pm-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.pm-notif-item:last-child {
    border-bottom: none;
}
.pm-notif-item:hover {
    background: #faf7f5;
}
.pm-notif-item.unread {
    background: #fff8f5;
}
.pm-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f97316;
    flex-shrink: 0;
    margin-top: 5px;
}
.pm-notif-content {
    flex: 1;
    min-width: 0;
}
.pm-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: #1c1412;
}
.pm-notif-body {
    font-size: 12px;
    color: #4a3f3a;
    margin-top: 2px;
    line-height: 1.4;
}
.pm-notif-ago {
    font-size: 11px;
    color: #8a7d78;
    margin-top: 4px;
}

/* ══════════════════════════════════════
   CONTENU PRINCIPAL
══════════════════════════════════════ */
.pm-content {
    flex: 1;
    padding: 32px 28px;
    max-width: 680px;
    width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── En-tête de section ── */
.pm-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
}
.pm-section-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #fff7ed, #fef3e2);
    border: 1.5px solid #fed7aa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f97316;
    flex-shrink: 0;
}
.pm-section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1c1412;
}
.pm-section-sub {
    font-size: 13px;
    color: #8a7d78;
    margin-top: 2px;
}

/* ══════════════════════════════════════
   CARTE FORMULAIRE
══════════════════════════════════════ */
.pm-card {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 18px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Alertes ── */
.pm-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    line-height: 1.5;
}
.pm-alert svg {
    flex-shrink: 0;
    margin-top: 1px;
}
.pm-alert--success {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    color: #15803d;
}
.pm-alert--error {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
}

/* ── Champ ── */
.pm-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.pm-label {
    font-size: 13px;
    font-weight: 600;
    color: #1c1412;
}

.pm-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.pm-input-icon {
    position: absolute;
    left: 13px;
    color: #8a7d78;
    pointer-events: none;
    flex-shrink: 0;
}

.pm-input {
    width: 100%;
    height: 46px;
    border: 1.5px solid #e8ddd4;
    border-radius: 11px;
    padding: 0 46px 0 40px;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    color: #1c1412;
    background: #faf8f6;
    outline: none;
    transition:
        border-color 0.18s,
        box-shadow 0.18s,
        background 0.18s;
}
.pm-input::placeholder {
    color: #b5a9a3;
}
.pm-input:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
}
.pm-field--error .pm-input {
    border-color: #f87171;
}
.pm-field--error .pm-input:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

/* ── Bouton œil ── */
.pm-eye {
    position: absolute;
    right: 11px;
    background: none;
    border: none;
    cursor: pointer;
    color: #8a7d78;
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 6px;
    transition: color 0.15s;
}
.pm-eye:hover {
    color: #f97316;
}

/* ── Erreur champ ── */
.pm-field-error {
    font-size: 12px;
    color: #dc2626;
    font-weight: 500;
}

/* ── Jauge de force ── */
.pm-strength {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 2px;
}
.pm-strength-bars {
    display: flex;
    gap: 4px;
    flex: 1;
}
.pm-strength-bar {
    height: 4px;
    flex: 1;
    border-radius: 99px;
    background: #e8ddd4;
    transition: background 0.25s;
}
.pm-bar--weak {
    background: #ef4444;
}
.pm-bar--fair {
    background: #f59e0b;
}
.pm-bar--good {
    background: #3b82f6;
}
.pm-bar--strong {
    background: #22c55e;
}
.pm-strength-label {
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.pm-strength-label--weak {
    color: #ef4444;
}
.pm-strength-label--fair {
    color: #f59e0b;
}
.pm-strength-label--good {
    color: #3b82f6;
}
.pm-strength-label--strong {
    color: #22c55e;
}

/* ── Actions ── */
.pm-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 16px;
}

.pm-btn-submit {
    flex: 1;
    height: 46px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    border: none;
    border-radius: 11px;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition:
        opacity 0.18s,
        transform 0.15s;
}
.pm-btn-submit-content {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    line-height: 1;
}
.pm-btn-submit:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}
.pm-btn-submit:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
}

.pm-btn-cancel {
    height: 46px;
    padding: 0 20px;
    background: transparent;
    border: 1.5px solid #e8ddd4;
    border-radius: 11px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: #4a3f3a;
    cursor: pointer;
    transition:
        border-color 0.15s,
        color 0.15s;
}
.pm-btn-cancel:hover:not(:disabled) {
    border-color: #f97316;
    color: #f97316;
}
.pm-btn-cancel:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Spinner ── */
.pm-spinner {
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(255, 255, 255, 0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: pm-spin 0.7s linear infinite;
}
@keyframes pm-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ══════════════════════════════════════
   CARTE INFOS COMPTE (lecture seule)
══════════════════════════════════════ */
.pm-info-card {
    background: #fff;
    border: 1.5px solid #ede8e3;
    border-radius: 14px;
    overflow: hidden;
}
.pm-info-card-title {
    padding: 14px 20px 10px;
    font-size: 13px;
    font-weight: 700;
    color: #8a7d78;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #f5f0eb;
}
.pm-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 20px;
    border-bottom: 1px solid #f5f0eb;
    gap: 12px;
}
.pm-info-row:last-child {
    border-bottom: none;
}
.pm-info-label {
    font-size: 13px;
    color: #8a7d78;
    font-weight: 500;
    flex-shrink: 0;
}
.pm-info-val {
    font-size: 13.5px;
    color: #1c1412;
    font-weight: 600;
    text-align: right;
    word-break: break-all;
}

/* Badges rôle */
.pm-badge-role {
    padding: 3px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}
.pm-badge-role--client {
    background: #eff6ff;
    color: #1d4ed8;
}
.pm-badge-role--contractor {
    background: #fff7ed;
    color: #ea580c;
}
.pm-badge-role--admin {
    background: #f0fdf4;
    color: #15803d;
}
.pm-badge-role--talent {
    background: #fdf4ff;
    color: #9333ea;
}

/* Badges statut */
.pm-badge-status {
    padding: 3px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}
.pm-badge-status--active {
    background: #f0fdf4;
    color: #15803d;
}
.pm-badge-status--pending {
    background: #fffbeb;
    color: #b45309;
}
.pm-badge-status--suspended {
    background: #fef2f2;
    color: #dc2626;
}

/* ══════════════════════════════════════
   TRANSITIONS
══════════════════════════════════════ */
.pm-fade-enter-active,
.pm-fade-leave-active {
    transition:
        opacity 0.3s,
        transform 0.3s;
}
.pm-fade-enter-from,
.pm-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* ══════════════════════════════════════
   RESPONSIVE MOBILE
══════════════════════════════════════ */
@media (max-width: 599px) {
    .pm-topbar {
        padding: 0 16px;
    }
    .pm-content {
        padding: 20px 16px;
    }
    .pm-card {
        padding: 20px 16px;
    }
    .pm-actions {
        flex-direction: column;
    }
    .pm-btn-submit,
    .pm-btn-cancel {
        width: 100%;
        flex: none;
    }
    .pm-notif-dropdown {
        width: 290px;
        right: -55px;
    }
}
</style>
