<template>
    <div class="rc-page">
        <!-- ══════════════ SIDEBAR ══════════════ -->
        <div class="rc-sidebar">
            <div class="rc-sidebar-logo">
                <div class="rc-logo-mark">R</div>
                <span class="rc-logo-name">RESO<em>TRAVO</em></span>
            </div>
            <h2 class="rc-sidebar-title">Inscription Client</h2>
            <p class="rc-sidebar-sub">
                Créez votre compte gratuitement et accédez à des centaines de
                prestataires certifiés.
            </p>
            <div class="rc-steps-list">
                <div
                    class="rc-step"
                    v-for="(label, i) in sidebarSteps"
                    :key="i"
                    :class="{ active: step === i + 1, done: step > i + 1 }"
                >
                    <div class="rc-step-dot">
                        {{ step > i + 1 ? "✓" : i + 1 }}
                    </div>
                    <div class="rc-step-label">{{ label }}</div>
                </div>
            </div>
            <div class="rc-sidebar-footer">
                Déjà inscrit ? <a :href="routes.login">Se connecter</a>
            </div>
        </div>

        <!-- ══════════════ MAIN ══════════════ -->
        <div class="rc-main">
            <div class="rc-card">
                <!-- Barre de progression -->
                <div class="rc-progress">
                    <div class="rc-progress-track">
                        <div
                            class="rc-progress-fill"
                            :style="{ width: (step / totalSteps) * 100 + '%' }"
                        ></div>
                    </div>
                    <div class="rc-progress-label">
                        Étape {{ step }} sur {{ totalSteps }}
                    </div>
                </div>

                <!-- ── ÉTAPE 1 : Type de compte ── -->
                <div v-if="step === 1">
                    <div class="rc-step-title">Quel type de compte ?</div>
                    <div class="rc-step-desc">
                        Choisissez votre profil pour personnaliser votre
                        expérience.
                    </div>

                    <div class="rc-type-grid">
                        <div
                            class="rc-type-card"
                            :class="{
                                selected: form.account_type === 'individual',
                            }"
                            @click="form.account_type = 'individual'"
                        >
                            <div class="rc-type-icon">🏠</div>
                            <h4>Particulier</h4>
                            <p>
                                Interventions à domicile, gestion personnelle.
                            </p>
                        </div>
                        <div
                            class="rc-type-card"
                            :class="{
                                selected: form.account_type === 'company',
                            }"
                            @click="form.account_type = 'company'"
                        >
                            <div class="rc-type-icon">🏢</div>
                            <h4>Entreprise</h4>
                            <p>
                                Missions professionnelles, appels d'offres,
                                Espace Talents.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rc-alert rc-alert-info"
                        v-if="form.account_type === 'company'"
                    >
                        💡 Les entreprises peuvent publier des appels d'offres
                        et accéder à l'Espace Talents.
                    </div>
                    <div class="rc-err" v-if="errors.account_type">
                        {{ errors.account_type }}
                    </div>

                    <div class="rc-btn-row">
                        <button class="rc-btn rc-btn-primary" @click="next">
                            Continuer →
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2 : Informations personnelles ── -->
                <div v-if="step === 2">
                    <div class="rc-step-title">Vos informations</div>
                    <div class="rc-step-desc">
                        Renseignez vos coordonnées pour créer votre compte.
                    </div>

                    <div class="rc-form-grid">
                        <div class="rc-field">
                            <label>Prénom <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.first_name }"
                                type="text"
                                v-model="form.first_name"
                                placeholder="Jean"
                                autocomplete="given-name"
                            />
                            <div class="rc-err" v-if="errors.first_name">
                                {{ errors.first_name }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Nom <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.last_name }"
                                type="text"
                                v-model="form.last_name"
                                placeholder="Dupont"
                                autocomplete="family-name"
                            />
                            <div class="rc-err" v-if="errors.last_name">
                                {{ errors.last_name }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Email <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.email }"
                                type="email"
                                v-model="form.email"
                                placeholder="jean@email.com"
                                autocomplete="email"
                            />
                            <div class="rc-err" v-if="errors.email">
                                {{ errors.email }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Téléphone <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.phone }"
                                type="tel"
                                v-model="form.phone"
                                placeholder="+229 96 XX XX XX"
                                autocomplete="tel"
                            />
                            <div class="rc-err" v-if="errors.phone">
                                {{ errors.phone }}
                            </div>
                        </div>

                        <div
                            class="rc-field rc-span2"
                            v-if="form.account_type === 'company'"
                        >
                            <label
                                >Nom de l'entreprise
                                <span class="req">*</span></label
                            >
                            <input
                                class="rc-input"
                                :class="{ err: errors.company_name }"
                                type="text"
                                v-model="form.company_name"
                                placeholder="SARL MonEntreprise"
                            />
                            <div class="rc-err" v-if="errors.company_name">
                                {{ errors.company_name }}
                            </div>
                        </div>

                        <div class="rc-field rc-span2">
                            <label
                                >Adresse principale
                                <span class="req">*</span></label
                            >
                            <input
                                class="rc-input"
                                :class="{ err: errors.address }"
                                type="text"
                                v-model="form.address"
                                placeholder="Akpakpa, Cotonou"
                                autocomplete="street-address"
                            />
                            <div class="rc-err" v-if="errors.address">
                                {{ errors.address }}
                            </div>
                        </div>
                    </div>

                    <div class="rc-btn-row">
                        <button class="rc-btn rc-btn-secondary" @click="prev">
                            ← Retour
                        </button>
                        <button class="rc-btn rc-btn-primary" @click="next">
                            Continuer →
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 3 : Mot de passe ── -->
                <div v-if="step === 3">
                    <div class="rc-step-title">Créez votre mot de passe</div>
                    <div class="rc-step-desc">
                        Minimum 8 caractères, une majuscule et un chiffre.
                    </div>

                    <!-- Mot de passe -->
                    <div class="rc-field" style="margin-bottom: 18px">
                        <label>Mot de passe <span class="req">*</span></label>
                        <div class="rc-input-wrap">
                            <input
                                class="rc-input"
                                :class="{ err: errors.password }"
                                :type="showPwd ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="••••••••"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="rc-eye"
                                @click="showPwd = !showPwd"
                            >
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
                        <div class="rc-err" v-if="errors.password">
                            {{ errors.password }}
                        </div>
                    </div>

                    <!-- Force du mot de passe -->
                    <div class="rc-pwd-strength" v-if="form.password">
                        <div class="rc-pwd-track">
                            <div
                                class="rc-pwd-fill"
                                :style="{
                                    width: pwdStrength.pct + '%',
                                    background: pwdStrength.color,
                                }"
                            ></div>
                        </div>
                        <div
                            class="rc-pwd-label"
                            :style="{ color: pwdStrength.color }"
                        >
                            {{ pwdStrength.label }}
                        </div>
                    </div>

                    <!-- Confirmation -->
                    <div class="rc-field" style="margin-bottom: 20px">
                        <label
                            >Confirmer le mot de passe
                            <span class="req">*</span></label
                        >
                        <div class="rc-input-wrap">
                            <input
                                class="rc-input"
                                :class="{ err: errors.password_confirmation }"
                                :type="showPwd2 ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                placeholder="••••••••"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="rc-eye"
                                @click="showPwd2 = !showPwd2"
                            >
                                <svg
                                    v-if="showPwd2"
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
                        <div class="rc-err" v-if="errors.password_confirmation">
                            {{ errors.password_confirmation }}
                        </div>
                    </div>

                    <!-- CGU -->
                    <div class="rc-checkbox-row">
                        <input type="checkbox" id="cgv" v-model="form.cgv" />
                        <label for="cgv">
                            J'accepte les
                            <a :href="routes.cgu" target="_blank"
                                >Conditions Générales d'Utilisation</a
                            >
                            et la
                            <a :href="routes.policy" target="_blank"
                                >Politique de Confidentialité</a
                            >
                            de Resotravo.
                        </label>
                    </div>
                    <div
                        class="rc-err"
                        v-if="errors.cgv"
                        style="margin-bottom: 14px"
                    >
                        {{ errors.cgv }}
                    </div>

                    <div class="rc-btn-row">
                        <button class="rc-btn rc-btn-secondary" @click="prev">
                            ← Retour
                        </button>
                        <button
                            class="rc-btn rc-btn-primary"
                            @click="submit"
                            :disabled="loading"
                        >
                            <div class="rc-spinner" v-if="loading"></div>
                            <span v-else>Créer mon compte →</span>
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 4 : Succès ── -->
                <div v-if="step === 4" class="rc-success">
                    <div class="rc-success-icon">🎉</div>
                    <h2>Compte créé avec succès !</h2>
                    <p>
                        Bienvenue sur Resotravo,
                        <strong>{{ form.first_name }}</strong> !<br />
                        Vous pouvez maintenant rechercher et commander des
                        interventions.
                    </p>
                    <a
                        class="rc-btn rc-btn-primary"
                        :href="routes.dashboard"
                        style="text-decoration: none"
                    >
                        Accéder à mon espace →
                    </a>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL ERREUR ══════════════ -->
        <div
            class="rc-modal-overlay"
            v-if="errorModal.visible"
            @click.self="errorModal.visible = false"
        >
            <div class="rc-modal">
                <div class="rc-modal-icon">⚠️</div>
                <h3 class="rc-modal-title">{{ errorModal.title }}</h3>
                <div class="rc-modal-body">
                    <!-- Message principal -->
                    <p class="rc-modal-msg">{{ errorModal.message }}</p>
                    <!-- Détail des erreurs de validation Laravel (422) -->
                    <ul
                        class="rc-modal-errors"
                        v-if="errorModal.errors.length > 0"
                    >
                        <li v-for="(e, i) in errorModal.errors" :key="i">
                            {{ e }}
                        </li>
                    </ul>
                </div>
                <button
                    class="rc-btn rc-btn-primary"
                    @click="errorModal.visible = false"
                    style="width: 100%; margin-top: 4px"
                >
                    Compris →
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "RegisterClientComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                login: "/login",
                dashboard: "/dashboard",
                cgu: "/cgu",
                policy: "/policy",
            }),
        },
    },

    data() {
        return {
            step: 1,
            totalSteps: 3, // OTP supprimé — 3 étapes : type → infos → mdp
            loading: false,
            showPwd: false,
            showPwd2: false,

            sidebarSteps: [
                "Type de compte",
                "Informations personnelles",
                "Mot de passe",
            ],

            form: {
                account_type: "",
                first_name: "",
                last_name: "",
                email: "",
                phone: "",
                address: "",
                company_name: "",
                password: "",
                password_confirmation: "",
                cgv: true,
            },

            errors: {},

            errorModal: {
                visible: false,
                title: "",
                message: "",
                errors: [],
            },
        };
    },

    computed: {
        pwdStrength() {
            const p = this.form.password;
            if (!p) return { pct: 0, color: "#ccc", label: "" };
            let score = 0;
            if (p.length >= 8) score++;
            if (/[A-Z]/.test(p)) score++;
            if (/[0-9]/.test(p)) score++;
            if (/[^A-Za-z0-9]/.test(p)) score++;
            const map = [
                { pct: 20, color: "#ef4444", label: "Très faible" },
                { pct: 40, color: "#f97316", label: "Faible" },
                { pct: 65, color: "#eab308", label: "Moyen" },
                { pct: 85, color: "#22c55e", label: "Fort" },
                { pct: 100, color: "#16a34a", label: "Très fort" },
            ];
            return map[Math.min(score, 4)];
        },
    },

    methods: {
        /* ── Validation par étape ── */
        validate() {
            this.errors = {};

            if (this.step === 1) {
                if (!this.form.account_type)
                    this.errors.account_type =
                        "Veuillez choisir un type de compte.";
            }

            if (this.step === 2) {
                if (!this.form.first_name.trim())
                    this.errors.first_name = "Prénom requis.";
                if (!this.form.last_name.trim())
                    this.errors.last_name = "Nom requis.";
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email))
                    this.errors.email = "Email invalide.";
                if (this.form.phone.replace(/\D/g, "").length < 8)
                    this.errors.phone = "Numéro de téléphone invalide.";
                if (!this.form.address.trim())
                    this.errors.address = "Adresse requise.";
                if (
                    this.form.account_type === "company" &&
                    !this.form.company_name.trim()
                )
                    this.errors.company_name = "Company name is required.";
            }

            if (this.step === 3) {
                if (this.form.password.length < 8)
                    this.errors.password = "Minimum 8 caractères.";
                else if (!/[A-Z]/.test(this.form.password))
                    this.errors.password = "Au moins une majuscule requise.";
                else if (!/[0-9]/.test(this.form.password))
                    this.errors.password = "Au moins un chiffre requis.";
                if (this.form.password !== this.form.password_confirmation)
                    this.errors.password_confirmation =
                        "Les mots de passe ne correspondent pas.";
                if (!this.form.cgv)
                    this.errors.cgv =
                        "Vous devez accepter les conditions générales.";
            }

            const valid = Object.keys(this.errors).length === 0;
            console.log(
                `[RegisterClientComponent] validate() step ${this.step} →`,
                valid ? "OK" : "ERREURS",
                this.errors,
            );
            return valid;
        },

        /* ── Navigation ── */
        next() {
            if (!this.validate()) return;
            if (this.step < this.totalSteps) this.step++;
        },

        prev() {
            if (this.step > 1) this.step--;
        },

        /* ── Soumission finale (étape 3) ── */
        async submit() {
            if (!this.validate()) return;

            const route = "/register/client/store";
            const payload = {
                account_type: this.form.account_type,
                first_name: this.form.first_name,
                last_name: this.form.last_name,
                email: this.form.email,
                phone: this.form.phone,
                address: this.form.address,
                company_name:
                    this.form.account_type === "company"
                        ? this.form.company_name
                        : null,
                password: this.form.password,
                password_confirmation: this.form.password_confirmation,
            };

            console.log(
                "[RegisterClientComponent] ── REQUÊTE ──────────────────────",
            );
            console.log("[RegisterClientComponent] Route   :", route);
            console.log("[RegisterClientComponent] Méthode :", "POST");
            console.log("[RegisterClientComponent] Payload :", {
                ...payload,
                password: "***",
                password_confirmation: "***",
            });
            console.log(
                "[RegisterClientComponent] ────────────────────────────────",
            );

            this.loading = true;

            try {
                const response = await window.axios.post(route, payload);

                console.log(
                    "[RegisterClientComponent] ── RÉPONSE ──────────────────────",
                );
                console.log(
                    "[RegisterClientComponent] Status :",
                    response.status,
                );
                console.log(
                    "[RegisterClientComponent] Data   :",
                    response.data,
                );
                console.log(
                    "[RegisterClientComponent] ────────────────────────────────",
                );

                // Succès → passer à l'étape 4
                this.step = 4;
            } catch (err) {
                const status = err?.response?.status;
                const data = err?.response?.data;

                console.error(
                    "[RegisterClientComponent] ── ERREUR ───────────────────────",
                );
                console.error("[RegisterClientComponent] Status  :", status);
                console.error("[RegisterClientComponent] Data    :", data);
                console.error(
                    "[RegisterClientComponent] Message :",
                    err?.message,
                );
                console.error(
                    "[RegisterClientComponent] ────────────────────────────────",
                );

                // Construire le modal d'erreur
                this.errorModal.visible = true;
                this.errorModal.errors = [];

                if (status === 422 && data?.errors) {
                    // Erreurs de validation Laravel — on les liste toutes
                    this.errorModal.title = "Données invalides";
                    this.errorModal.message =
                        data?.message ||
                        "Veuillez corriger les erreurs suivantes :";
                    this.errorModal.errors = Object.values(data.errors).flat();
                } else if (status === 409) {
                    this.errorModal.title = "Compte déjà existant";
                    this.errorModal.message =
                        data?.message ||
                        "Un compte avec cet email existe déjà. Connectez-vous.";
                } else if (status === 500) {
                    this.errorModal.title = "Erreur serveur";
                    this.errorModal.message =
                        "Une erreur interne est survenue. Veuillez réessayer dans quelques instants.";
                } else {
                    this.errorModal.title = "Erreur";
                    this.errorModal.message =
                        data?.message ||
                        err?.message ||
                        "Une erreur inattendue est survenue.";
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
/* PAGE */
.rc-page {
    display: flex;
    min-height: 100vh;
    background: var(--cr);
    font-family: "Poppins", sans-serif;
}

/* ── SIDEBAR ── */
.rc-sidebar {
    width: 280px;
    flex-shrink: 0;
    background: var(--dk2, #110d0b);
    color: #fff;
    padding: 36px 28px;
    display: flex;
    flex-direction: column;
    gap: 0;
}
@media (max-width: 768px) {
    .rc-sidebar {
        display: none;
    }
}

.rc-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 32px;
}
.rc-logo-mark {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: linear-gradient(
        135deg,
        var(--or, #f97316),
        var(--or2, #ea580c)
    );
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    font-size: 16px;
}
.rc-logo-name {
    font-size: 16px;
    font-weight: 800;
    color: #fff;
}
.rc-logo-name em {
    font-style: normal;
    color: var(--or, #f97316);
}

.rc-sidebar-title {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 8px;
}
.rc-sidebar-sub {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 32px;
    line-height: 1.6;
}

.rc-steps-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}
.rc-step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: 10px;
    transition: all 0.2s;
}
.rc-step.active {
    background: var(--or, #f97316);
}
.rc-step.done {
    background: rgba(249, 115, 22, 0.15);
}
.rc-step-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}
.rc-step.active .rc-step-dot {
    background: #fff;
    color: var(--or, #f97316);
}
.rc-step.done .rc-step-dot {
    background: var(--or, #f97316);
    color: #fff;
}
.rc-step-label {
    font-size: 13.5px;
    font-weight: 500;
    color: #aaa;
}
.rc-step.active .rc-step-label,
.rc-step.done .rc-step-label {
    color: #fff;
    font-weight: 600;
}

.rc-sidebar-footer {
    margin-top: 24px;
    font-size: 13px;
    color: #666;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 16px;
}
.rc-sidebar-footer a {
    color: var(--or, #f97316);
    font-weight: 600;
    text-decoration: none;
}
.rc-sidebar-footer a:hover {
    text-decoration: underline;
}

/* ── MAIN ── */
.rc-main {
    flex: 1;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 20px;
    overflow-y: auto;
}
@media (min-width: 900px) {
    .rc-main {
        padding: 48px 40px;
    }
}

.rc-card {
    background: var(--wh, #fff);
    border-radius: 20px;
    box-shadow: 0 6px 32px rgba(0, 0, 0, 0.08);
    padding: 36px 32px;
    width: 100%;
    max-width: 620px;
}
@media (max-width: 480px) {
    .rc-card {
        padding: 24px 18px;
    }
}

/* PROGRESS */
.rc-progress {
    margin-bottom: 28px;
}
.rc-progress-track {
    background: var(--grl, #e8ddd4);
    border-radius: 99px;
    height: 6px;
    overflow: hidden;
    margin-bottom: 8px;
}
.rc-progress-fill {
    height: 100%;
    background: var(--or, #f97316);
    border-radius: 99px;
    transition: width 0.4s ease;
}
.rc-progress-label {
    font-size: 13px;
    color: var(--gr, #7c6a5a);
    font-weight: 500;
}

/* STEP HEADER */
.rc-step-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 6px;
}
.rc-step-desc {
    font-size: 14px;
    color: var(--gr, #7c6a5a);
    margin-bottom: 24px;
    line-height: 1.6;
}

/* TYPE CARDS */
.rc-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
@media (max-width: 400px) {
    .rc-type-grid {
        grid-template-columns: 1fr;
    }
}

.rc-type-card {
    border: 2px solid var(--grl, #e8ddd4);
    border-radius: 14px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.rc-type-card:hover {
    border-color: var(--or, #f97316);
}
.rc-type-card.selected {
    border-color: var(--or, #f97316);
    background: #fff7ed;
}
.rc-type-icon {
    font-size: 32px;
    margin-bottom: 10px;
}
.rc-type-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk, #1c1412);
    margin-bottom: 4px;
}
.rc-type-card p {
    font-size: 12px;
    color: var(--gr, #7c6a5a);
}

/* ALERT */
.rc-alert {
    padding: 12px 16px;
    border-radius: 9px;
    font-size: 13.5px;
    margin-bottom: 16px;
}
.rc-alert-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
}

/* FORM */
.rc-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 4px;
}
@media (max-width: 540px) {
    .rc-form-grid {
        grid-template-columns: 1fr;
    }
}

.rc-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.rc-field label {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk, #1c1412);
}
.rc-field .req {
    color: var(--or, #f97316);
}
.rc-span2 {
    grid-column: 1 / -1;
}

.rc-input-wrap {
    position: relative;
}
.rc-input {
    width: 100%;
    padding: 12px 44px 12px 14px;
    border: 2px solid var(--grl, #e8ddd4);
    border-radius: 9px;
    font-size: 14.5px;
    font-family: "Poppins", sans-serif;
    outline: none;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
    color: var(--dk, #1c1412);
    background: var(--cr, #fef3e2);
}
.rc-input:focus {
    border-color: var(--or, #f97316);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
}
.rc-input.err {
    border-color: #ef4444;
}
.rc-input::placeholder {
    color: var(--grm, #8a7d78);
}

/* OEIL */
.rc-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--grm, #8a7d78);
    padding: 4px;
    display: flex;
    align-items: center;
    transition: color 0.18s;
}
.rc-eye:hover {
    color: var(--or, #f97316);
}
.rc-eye svg {
    width: 18px;
    height: 18px;
}

/* FORCE MOT DE PASSE */
.rc-pwd-strength {
    margin-bottom: 18px;
}
.rc-pwd-track {
    background: var(--grl, #e8ddd4);
    height: 6px;
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 4px;
}
.rc-pwd-fill {
    height: 100%;
    border-radius: 99px;
    transition: all 0.3s;
}
.rc-pwd-label {
    font-size: 12px;
    font-weight: 600;
}

/* CGU */
.rc-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13.5px;
    color: var(--gr, #7c6a5a);
    margin-bottom: 16px;
    line-height: 1.5;
}
.rc-checkbox-row input {
    width: 16px;
    height: 16px;
    accent-color: var(--or, #f97316);
    flex-shrink: 0;
    margin-top: 2px;
}
.rc-checkbox-row a {
    color: var(--or, #f97316);
    font-weight: 600;
    text-decoration: none;
}
.rc-checkbox-row a:hover {
    text-decoration: underline;
}

/* ERREUR */
.rc-err {
    font-size: 12px;
    color: #ef4444;
    margin-top: 2px;
}

/* BOUTONS */
.rc-btn-row {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}
.rc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.rc-btn-primary {
    background: linear-gradient(
        135deg,
        var(--or, #f97316),
        var(--or2, #ea580c)
    );
    color: #fff;
    flex: 1;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}
.rc-btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
}
.rc-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.rc-btn-secondary {
    background: var(--grl, #e8ddd4);
    color: var(--dk, #1c1412);
}
.rc-btn-secondary:hover {
    background: #d5c9c0;
}

/* SPINNER */
.rc-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rc-spin 0.7s linear infinite;
}
@keyframes rc-spin {
    to {
        transform: rotate(360deg);
    }
}

/* SUCCÈS */
.rc-success {
    text-align: center;
    padding: 16px 0;
}
.rc-success-icon {
    font-size: 70px;
    margin-bottom: 20px;
}
.rc-success h2 {
    font-size: 24px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 12px;
}
.rc-success p {
    font-size: 15px;
    color: var(--gr, #7c6a5a);
    line-height: 1.7;
    margin-bottom: 28px;
}
.rc-success strong {
    color: var(--dk, #1c1412);
}

/* ── MODAL ERREUR ── */
.rc-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 13, 11, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: rc-fade-in 0.2s ease;
}
@keyframes rc-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.rc-modal {
    background: #fff;
    border-radius: 20px;
    padding: 36px 32px;
    max-width: 440px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: rc-slide-up 0.25s ease;
}
@keyframes rc-slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.rc-modal-icon {
    font-size: 44px;
    text-align: center;
    margin-bottom: 14px;
}
.rc-modal-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    text-align: center;
    margin-bottom: 12px;
}
.rc-modal-body {
    margin-bottom: 20px;
}
.rc-modal-msg {
    font-size: 14px;
    color: var(--gr, #7c6a5a);
    line-height: 1.6;
    margin-bottom: 10px;
}

.rc-modal-errors {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.rc-modal-errors li {
    font-size: 13.5px;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 8px 12px;
}
.rc-modal-errors li::before {
    content: "✕ ";
    font-weight: 700;
}
</style>
