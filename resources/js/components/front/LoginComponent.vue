<template>
    <div class="login-page">
        <div class="lc-card">
            <!-- Logo -->
            <a class="lc-logo" :href="routes.home">
                <div class="lc-logo-mark">M</div>
                <span class="lc-logo-name">MESO<em>TRAVO</em></span>
            </a>

            <div class="lc-icon">🔐</div>
            <h1 class="lc-title">Bon retour !</h1>
            <p class="lc-subtitle">Connectez-vous à votre espace Mesotravo</p>

            <!-- ── FORMULAIRE ── -->
            <div v-if="step === 'login'">
                <div class="lc-alert lc-alert-error" v-if="loginError">
                    ⚠️ {{ loginError }}
                </div>

                <form @submit.prevent="handleLogin" novalidate>
                    <!-- Email -->
                    <div class="lc-field">
                        <label class="lc-label">Email ou téléphone</label>
                        <input
                            class="lc-input"
                            :class="{ error: errors.email }"
                            type="text"
                            v-model="form.email"
                            placeholder="votre@email.com"
                            autocomplete="email"
                        />
                        <div class="lc-error" v-if="errors.email">
                            {{ errors.email }}
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="lc-field">
                        <label class="lc-label">Mot de passe</label>
                        <div class="lc-input-wrap">
                            <input
                                class="lc-input"
                                :class="{ error: errors.password }"
                                :type="showPwd ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                class="lc-eye"
                                @click="togglePwd"
                                :title="showPwd ? 'Masquer' : 'Afficher'"
                            >
                                <!-- Oeil ouvert -->
                                <svg
                                    v-if="showPwd"
                                    xmlns="http://www.w3.org/2000/svg"
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
                                <!-- Oeil barré -->
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"
                                    />
                                    <path
                                        d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"
                                    />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        <div class="lc-error" v-if="errors.password">
                            {{ errors.password }}
                        </div>
                    </div>

                    <!-- Se souvenir de moi + Mot de passe oublié -->
                    <div class="lc-row-remember">
                        <label class="lc-remember-label">
                            <input
                                type="checkbox"
                                class="lc-remember-check"
                                v-model="form.remember"
                            />
                            <span class="lc-remember-box">
                                <svg v-if="form.remember" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </span>
                            Se souvenir de moi
                        </label>
                        <a class="lc-forgot" :href="routes.forgot">Mot de passe oublié ?</a>
                    </div>

                    <button
                        class="lc-btn lc-btn-primary"
                        type="submit"
                        :disabled="loading"
                    >
                        <div class="lc-spinner" v-if="loading"></div>
                        <span v-else>Se connecter →</span>
                    </button>

                    <button
                        type="button"
                        class="lc-btn lc-btn-google"
                        @click="handleGoogle"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 48 48"
                            width="20"
                            height="20"
                        >
                            <path
                                fill="#EA4335"
                                d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"
                            />
                            <path
                                fill="#4285F4"
                                d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"
                            />
                            <path
                                fill="#FBBC05"
                                d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"
                            />
                            <path
                                fill="#34A853"
                                d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"
                            />
                        </svg>
                        Continuer avec Google
                    </button>
                </form>

                <div class="lc-divider">Pas encore de compte ?</div>

                <!-- Deux boutons d'inscription -->
                <div class="lc-register-btns">
                    <a
                        class="lc-btn lc-btn-register"
                        :href="routes.registerClient"
                    >
                        🔍 Inscription Client
                    </a>
                    <a
                        class="lc-btn lc-btn-register-outline"
                        :href="routes.registerContractor"
                    >
                        👷 Inscription Prestataire
                    </a>
                </div>
            </div>

            <!-- ── SUCCÈS ── -->
            <div v-if="step === 'success'" class="lc-success">
                <div class="lc-success-icon">✅</div>
                <h2>Connexion réussie !</h2>
                <p>
                    Bienvenue, <strong>{{ form.email }}</strong>
                </p>
                <a
                    class="lc-btn lc-btn-primary"
                    :href="routes.dashboard"
                    style="
                        text-decoration: none;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        margin-top: 8px;
                    "
                >
                    Accéder à mon espace →
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "LoginComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                home: "/",
                forgot: "/forgot-password",
                dashboard: "/dashboard",
                registerClient: "/register/client",
                registerContractor: "/register/contractor",
            }),
        },
    },

    data() {
        return {
            step: "login", // 'login' | 'success'
            showPwd: false,
            loading: false,
            loginError: "",
            form: {
                email: "",
                password: "",
                remember: false,
            },
            errors: {},
        };
    },

    methods: {
        /* ── Afficher / masquer le mot de passe ── */
        togglePwd() {
            this.showPwd = !this.showPwd;
            console.log(
                "[LoginComponent] togglePwd →",
                this.showPwd ? "visible" : "masqué"
            );
        },

        /* ── Validation du formulaire ── */
        validate() {
            this.errors = {};
            console.log("[LoginComponent] validate — form:", {
                email: this.form.email,
                password: "***",
            });

            if (!this.form.email.trim()) {
                this.errors.email = "Champ requis";
            } else if (
                !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email) &&
                !/^\+?\d{8,15}$/.test(this.form.email)
            ) {
                this.errors.email = "Email ou téléphone invalide";
            }

            if (!this.form.password.trim()) {
                this.errors.password = "Champ requis";
            } else if (this.form.password.length < 6) {
                this.errors.password = "Minimum 6 caractères";
            }

            const valid = Object.keys(this.errors).length === 0;
            console.log(
                "[LoginComponent] validate →",
                valid ? "OK" : "ERREURS",
                this.errors
            );
            return valid;
        },

        /* ── Soumission du formulaire ── */
        async handleLogin() {
            console.log("[LoginComponent] handleLogin — début");
            this.loginError = "";

            if (!this.validate()) {
                console.warn(
                    "[LoginComponent] handleLogin — validation échouée, abandon"
                );
                return;
            }

            /* ── Payload envoyé ── */
            const payload = {
                email: this.form.email,
                password: this.form.password,
                remember: this.form.remember,
            };
            const route = "/login";

            console.log(
                "[LoginComponent] ── REQUÊTE ─────────────────────────"
            );
            console.log("[LoginComponent] Route   :", route);
            console.log("[LoginComponent] Méthode :", "POST");
            console.log("[LoginComponent] Payload :", {
                ...payload,
                password: "***",
            });
            console.log(
                "[LoginComponent] ────────────────────────────────────"
            );

            this.loading = true;

            try {
                const response = await window.axios.post(route, payload);

                console.log(
                    "[LoginComponent] ── RÉPONSE SERVEUR ─────────────"
                );
                console.log("[LoginComponent] Status  :", response.status);
                console.log("[LoginComponent] Data    :", response.data);
                console.log(
                    "[LoginComponent] Redirect:",
                    response.data?.redirect ?? this.routes.dashboard
                );
                console.log(
                    "[LoginComponent] ────────────────────────────────"
                );

                /* Laravel renvoie { redirect: '/dashboard' } après connexion réussie.
                   On laisse le backend décider de la destination. */
                const redirectUrl =
                    response.data?.redirect ?? this.routes.dashboard;
                console.log(
                    "[LoginComponent] handleLogin — connexion réussie, redirection vers:",
                    redirectUrl
                );

                this.step = "success";

                // Redirection après 800ms pour laisser le temps d'afficher le succès
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 800);
            } catch (err) {
                console.error(
                    "[LoginComponent] ── ERREUR SERVEUR ─────────────"
                );
                console.error(
                    "[LoginComponent] Status :",
                    err?.response?.status
                );
                console.error("[LoginComponent] Data   :", err?.response?.data);
                console.error("[LoginComponent] Message:", err?.message);
                console.error(
                    "[LoginComponent] ─────────────────────────────────"
                );

                this.loginError =
                    err?.response?.data?.message ??
                    err?.response?.data?.errors?.email?.[0] ??
                    "Identifiants incorrects. Veuillez réessayer.";
            } finally {
                this.loading = false;
                console.log("[LoginComponent] handleLogin — loading = false");
            }
        },

        /* ── Connexion Google ── */
        handleGoogle() {
            console.log("[LoginComponent] handleGoogle — déclenchement OAuth");
            // TODO: window.location.href = '/auth/google';
            alert(
                "Connexion Google — intégration OAuth disponible en production."
            );
        },

        /* ── Utilitaire délai ── */
        delay(ms) {
            return new Promise((resolve) => setTimeout(resolve, ms));
        },
    },
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    background: var(--cr);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

/* CARD */
.lc-card {
    background: var(--wh);
    border-radius: 20px;
    box-shadow: 0 8px 48px rgba(0, 0, 0, 0.1);
    padding: 48px 40px;
    width: 100%;
    max-width: 440px;
}
@media (max-width: 480px) {
    .lc-card {
        padding: 32px 22px;
    }
}

/* LOGO */
.lc-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    text-decoration: none;
    margin-bottom: 28px;
}
.lc-logo-mark {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    font-size: 17px;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.lc-logo-name {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk);
    letter-spacing: -0.5px;
}
.lc-logo-name em {
    font-style: normal;
    color: var(--or);
}

/* ICON + TITRE */
.lc-icon {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: var(--or3);
    border: 2px solid var(--or);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin: 0 auto 18px;
}
.lc-title {
    text-align: center;
    font-size: 24px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 6px;
}
.lc-subtitle {
    text-align: center;
    font-size: 14px;
    color: var(--gr);
    margin-bottom: 28px;
    line-height: 1.6;
}

/* ALERT */
.lc-alert {
    padding: 11px 15px;
    border-radius: 9px;
    font-size: 13.5px;
    margin-bottom: 18px;
}
.lc-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

/* CHAMPS */
.lc-field {
    margin-bottom: 18px;
}
.lc-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    margin-bottom: 6px;
}
.lc-input-wrap {
    position: relative;
}
.lc-input {
    width: 100%;
    padding: 12px 44px 12px 14px;
    border: 2px solid var(--grl);
    border-radius: 9px;
    font-size: 14.5px;
    font-family: "Poppins", sans-serif;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    color: var(--dk);
    background: var(--cr);
}
.lc-input:focus {
    border-color: var(--or);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
}
.lc-input.error {
    border-color: #ef4444;
}
.lc-input::placeholder {
    color: var(--grm);
}
.lc-error {
    font-size: 12px;
    color: #ef4444;
    margin-top: 4px;
}

/* OEIL */
.lc-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--grm);
    padding: 4px;
    display: flex;
    align-items: center;
    transition: color 0.18s;
}
.lc-eye:hover {
    color: var(--or);
}
.lc-eye svg {
    width: 18px;
    height: 18px;
}

/* SE SOUVENIR DE MOI + MOT DE PASSE OUBLIÉ */
.lc-row-remember {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: -6px;
    margin-bottom: 20px;
    gap: 8px;
}
.lc-remember-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    user-select: none;
}
.lc-remember-check {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.lc-remember-box {
    width: 18px;
    height: 18px;
    border-radius: 5px;
    border: 2px solid var(--grl);
    background: var(--cr);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.18s, background 0.18s;
}
.lc-remember-check:checked ~ .lc-remember-box,
.lc-remember-label:has(input:checked) .lc-remember-box {
    border-color: var(--or);
    background: var(--or);
}
.lc-remember-box svg {
    width: 11px;
    height: 11px;
    stroke: #fff;
    display: none;
}
.lc-remember-label:has(input:checked) .lc-remember-box svg {
    display: block;
}
.lc-forgot {
    font-size: 13px;
    color: var(--or);
    text-decoration: none;
    font-weight: 600;
    transition: opacity 0.18s;
    white-space: nowrap;
}
.lc-forgot:hover {
    opacity: 0.75;
}

/* BOUTONS */
.lc-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
    margin-bottom: 10px;
}
.lc-btn-primary {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}
.lc-btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
}
.lc-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.lc-btn-google {
    background: #fff;
    color: var(--dk);
    border: 2px solid var(--grl);
}
.lc-btn-google:hover {
    border-color: var(--gr);
    background: #f9f9f7;
}

/* SPINNER */
.lc-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: lc-spin 0.7s linear infinite;
    flex-shrink: 0;
}
@keyframes lc-spin {
    to {
        transform: rotate(360deg);
    }
}

/* DIVIDER */
.lc-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0 14px;
    color: var(--gr);
    font-size: 13px;
    font-weight: 600;
}
.lc-divider::before,
.lc-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--grl);
}

/* BOUTONS D'INSCRIPTION */
.lc-register-btns {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.lc-btn-register {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
    background: var(--or3);
    color: var(--or);
    border: 1.5px solid var(--or);
}
.lc-btn-register:hover {
    background: var(--or);
    color: #fff;
    transform: translateY(-1px);
}

.lc-btn-register-outline {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
    background: transparent;
    color: var(--dk);
    border: 1.5px solid var(--grl);
}
.lc-btn-register-outline:hover {
    border-color: var(--dk);
    background: var(--cr);
    transform: translateY(-1px);
}

/* SUCCÈS */
.lc-success {
    text-align: center;
}
.lc-success-icon {
    font-size: 64px;
    margin-bottom: 18px;
}
.lc-success h2 {
    font-size: 22px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 8px;
}
.lc-success p {
    font-size: 14px;
    color: var(--gr);
    margin-bottom: 24px;
}
.lc-success strong {
    color: var(--dk);
}
</style>
