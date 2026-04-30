<template>
    <div class="rc-page">
        <!-- ══════════════ SIDEBAR ══════════════ -->
        <div class="rc-sidebar">
            <div class="rc-sidebar-logo">
                <div class="rc-logo-mark">M</div>
                <span class="rc-logo-name">MESO<em>TRAVO</em></span>
            </div>
            <span class="rc-pro-badge">PRESTATAIRE</span>
            <h2 class="rc-sidebar-title">Inscription Prestataire</h2>
            <p class="rc-sidebar-sub">
                Rejoignez le réseau de prestataires certifiés Mesotravo et
                commencez à recevoir des missions.
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

            <div class="rc-side-note">
                <strong>📋 Logique de certification</strong><br />
                Vous pouvez vous inscrire et explorer les missions dès
                maintenant. Vous ne pourrez accepter des missions qu'après
                certification de votre dossier.
            </div>

            <div class="rc-sidebar-footer">
                Déjà inscrit ? <a :href="routes.login">Se connecter</a>
            </div>
        </div>

        <!-- ══════════════ MAIN ══════════════ -->
        <div class="rc-main">
            <div class="rc-card">
                <!-- Bouton Google -->
                <a :href="routes.google_auth" class="rc-google-btn">
                    <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.14 0 5.95 1.08 8.17 2.85l6.1-6.1C34.46 3.39 29.5 1.5 24 1.5 14.81 1.5 6.97 6.96 3.13 14.72l7.11 5.52C12.04 14.1 17.56 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24c0-1.64-.15-3.22-.42-4.75H24v9h12.7c-.55 2.98-2.19 5.5-4.66 7.19l7.2 5.59C43.32 37.09 46.5 30.97 46.5 24z"/><path fill="#FBBC05" d="M10.24 28.24A14.44 14.44 0 0 1 9.5 24c0-1.47.25-2.89.7-4.23l-7.11-5.52A22.46 22.46 0 0 0 1.5 24c0 3.68.88 7.15 2.44 10.22l6.3-5.98z"/><path fill="#34A853" d="M24 46.5c5.5 0 10.13-1.82 13.51-4.94l-7.2-5.59c-1.84 1.24-4.2 1.97-6.31 1.97-6.44 0-11.96-4.6-13.76-10.74l-6.3 5.98C6.97 41.04 14.81 46.5 24 46.5z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
                    S'inscrire avec Google
                </a>
                <div class="rc-or-divider"><span>ou avec un email</span></div>

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

                <!-- ── ÉTAPE 1 : Informations personnelles ── -->
                <div v-if="step === 1">
                    <div class="rc-step-title">Informations personnelles</div>
                    <div class="rc-step-desc">
                        Votre profil public sera vu par les clients. Soyez
                        précis.
                    </div>

                    <div class="rc-form-grid">
                        <div class="rc-field">
                            <label>Prénom <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.first_name }"
                                type="text"
                                v-model="form.first_name"
                                placeholder="Kokou"
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
                                placeholder="Mensah"
                                autocomplete="family-name"
                            />
                            <div class="rc-err" v-if="errors.last_name">
                                {{ errors.last_name }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Téléphone <span class="req">*</span></label>
                            <div class="rc-phone-wrap">
                                <span class="rc-phone-prefix">+229 01</span>
                                <input
                                    class="rc-input rc-phone-input"
                                    :class="{ err: errors.phone }"
                                    type="tel"
                                    v-model="form.phone"
                                    placeholder="97 XX XX XX"
                                    autocomplete="tel"
                                    maxlength="8"
                                    @input="form.phone = form.phone.replace(/\D/g, '').slice(0, 8)"
                                />
                            </div>
                            <div class="rc-err" v-if="errors.phone">
                                {{ errors.phone }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Email <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.email }"
                                type="email"
                                v-model="form.email"
                                placeholder="kokou@email.com"
                                autocomplete="email"
                            />
                            <div class="rc-err" v-if="errors.email">
                                {{ errors.email }}
                            </div>
                        </div>

                        <div class="rc-field rc-span2">
                            <label
                                >Zone d'intervention
                                <span class="req">*</span></label
                            >
                            <input
                                class="rc-input"
                                :class="{ err: errors.intervention_zone }"
                                type="text"
                                v-model="form.intervention_zone"
                                placeholder="ex: Cotonou, Akpakpa, Cadjehoun..."
                            />
                            <div class="rc-err" v-if="errors.intervention_zone">
                                {{ errors.intervention_zone }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label
                                >Années d'expérience
                                <span class="req">*</span></label
                            >
                            <input
                                class="rc-input"
                                :class="{ err: errors.experience_years }"
                                type="number"
                                v-model.number="form.experience_years"
                                min="0"
                                max="60"
                                placeholder="5"
                            />
                            <div class="rc-err" v-if="errors.experience_years">
                                {{ errors.experience_years }}
                            </div>
                        </div>

                        <div class="rc-field">
                            <label>Ville <span class="req">*</span></label>
                            <input
                                class="rc-input"
                                :class="{ err: errors.city }"
                                type="text"
                                v-model="form.city"
                                placeholder="Cotonou"
                            />
                            <div class="rc-err" v-if="errors.city">
                                {{ errors.city }}
                            </div>
                        </div>

                        <div class="rc-field rc-span2">
                            <label
                                >Bio / À propos
                                <span class="rc-optional"
                                    >(optionnel)</span
                                ></label
                            >
                            <textarea
                                class="rc-input rc-textarea"
                                v-model="form.bio"
                                placeholder="Décrivez votre expérience, vos atouts..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="rc-btn-row">
                        <button class="rc-btn rc-btn-primary" @click="next">
                            Continuer →
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2 : Spécialité ── -->
                <div v-if="step === 2">
                    <div class="rc-step-title">Spécialité</div>
                    <div class="rc-step-desc">
                        Sélectionnez votre domaine de compétence principal.
                    </div>

                    <!-- Spécialité principale -->
                    <div class="rc-field" style="margin-bottom: 20px">
                        <label
                            >Spécialité
                            <span class="req">*</span></label
                        >
                        <select
                            class="rc-input"
                            v-model="form.specialty"
                            :class="{ err: errors.specialty }"
                        >
                            <option value="">
                                Sélectionner votre spécialité...
                            </option>
                            <option
                                v-for="s in specialtyList"
                                :key="s"
                                :value="s"
                            >
                                {{ s }}
                            </option>
                        </select>
                        <div class="rc-err" v-if="errors.specialty">
                            {{ errors.specialty }}
                        </div>
                    </div>

                    <div class="rc-add-note">
                        🆕 Votre métier n'est pas listé ?
                        <a :href="routes.contact ?? '#'"
                            >Proposer un nouveau domaine</a
                        >
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

                    <!-- Force -->
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
                    <div class="rc-cgu-block">
                        <div v-if="form.cgv" class="rc-cgu-accepted">
                            <span class="rc-cgu-check">✓</span>
                            <span
                                >CGU acceptées —
                                <button
                                    type="button"
                                    class="rc-cgu-reread"
                                    @click="openCguModal"
                                >
                                    Relire
                                </button></span
                            >
                        </div>
                        <button
                            v-else
                            type="button"
                            class="rc-btn-cgu"
                            @click="openCguModal"
                        >
                            📜 Lire et accepter les CGU
                        </button>
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
                    <h2>Dossier soumis avec succès !</h2>
                    <p>
                        Bienvenue <strong>{{ form.first_name }}</strong> !<br />
                        Votre compte a été créé. Votre dossier sera vérifié sous
                        <strong>24-48h</strong>.<br />
                        Vous serez notifié par <strong>email et SMS</strong> dès
                        validation.
                    </p>

                    <div class="rc-success-info">
                        <div class="rc-success-info-title">
                            En attendant, vous pouvez :
                        </div>
                        <div class="rc-success-info-list">
                            <div>✅ Consulter les commandes disponibles</div>
                            <div>✅ Compléter votre profil public</div>
                            <div>✅ Explorer les zones d'intervention</div>
                            <div>
                                ⏳ Accepter des missions (après certification)
                            </div>
                        </div>
                    </div>

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

        <!-- ══════════════ MODAL CGU ══════════════ -->
        <div
            class="rc-modal-overlay"
            v-if="cguModal.visible"
            @click.self="closeCguModal"
        >
            <div class="rc-modal rc-modal-cgu">
                <div class="rc-modal-cgu-header">
                    <div
                        class="rc-modal-icon"
                        style="margin-bottom: 0; font-size: 28px"
                    >
                        📜
                    </div>
                    <h3 class="rc-modal-title" style="margin-bottom: 0">
                        Conditions Générales d'Utilisation
                    </h3>
                    <p
                        class="rc-modal-cgu-hint"
                        v-if="!cguModal.scrolledToBottom"
                    >
                        ↓ Faites défiler jusqu'en bas pour accepter
                    </p>
                    <p class="rc-modal-cgu-hint rc-modal-cgu-hint--ok" v-else>
                        ✓ Vous avez lu les CGU
                    </p>
                </div>

                <!-- Zone scrollable -->
                <div
                    class="rc-modal-cgu-body"
                    ref="cguScrollBox"
                    @scroll="onCguScroll"
                >
                    <div class="rc-cgu-content">
                        <div class="legal-intro-modal">
                            <p>
                                Les présentes Conditions Générales d'Utilisation
                                (CGU) régissent l'utilisation de la plateforme
                                <strong>Mesotravo</strong>, accessible à
                                l'adresse <strong>mesotravo.bj</strong>.
                            </p>
                            <p>
                                En créant un compte ou en utilisant nos
                                services, vous acceptez sans réserve
                                l'intégralité des présentes CGU.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">01</span> Objet et champ
                                d'application
                            </h4>
                            <p>
                                Mesotravo est une plateforme numérique de mise
                                en relation entre des Clients (particuliers et
                                entreprises), des Prestataires (artisans et
                                ouvriers spécialisés) et des Talents
                                (professionnels qualifiés BAC+3 minimum).
                                Mesotravo agit en qualité d'intermédiaire et ne
                                peut être considéré comme partie aux contrats
                                conclus entre Clients et Prestataires ou
                                Talents.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4><span class="cgu-num">02</span> Définitions</h4>
                            <p>
                                <strong>Plateforme</strong> : le site web et les
                                services Mesotravo accessibles via
                                mesotravo.bj.<br />
                                <strong>Client</strong> : toute personne
                                physique ou morale inscrite en qualité de
                                client.<br />
                                <strong>Prestataire</strong> : tout
                                professionnel inscrit pour proposer ses
                                services.<br />
                                <strong>Mission</strong> : toute intervention
                                commandée par un Client via la plateforme.<br />
                                <strong>Commission</strong> : rémunération de
                                Mesotravo fixée à 10% du montant de chaque
                                mission réalisée.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">03</span> Inscription et
                                accès à la plateforme
                            </h4>
                            <p>
                                L'inscription est ouverte à toute personne
                                physique majeure ou morale disposant d'une
                                adresse email valide. L'utilisateur s'engage à
                                fournir des informations exactes, complètes et à
                                les maintenir à jour. La création d'un compte
                                est gratuite.
                            </p>
                            <p>
                                Chaque utilisateur est seul responsable de la
                                confidentialité de ses identifiants. En cas de
                                perte ou de compromission, l'utilisateur doit
                                contacter immédiatement Mesotravo.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">04</span> Accréditation
                                des prestataires
                            </h4>
                            <p>
                                Tout prestataire souhaitant accepter des
                                missions doit soumettre : CNI ou passeport,
                                photo d'identité, attestation de résidence
                                (moins de 3 mois), casier judiciaire (Bulletin
                                n°3), copie du CIP et diplôme ou attestation de
                                qualification.
                            </p>
                            <p>
                                <strong>Accréditation DOMICILE</strong> :
                                accordée automatiquement après validation du
                                dossier.
                                <strong>Accréditation ENTREPRISE</strong> :
                                délivrée exclusivement par décision de
                                l'administrateur après vérification
                                complémentaire.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">05</span> Fonctionnement
                                des missions
                            </h4>
                            <p>
                                Chaque intervention suit un workflow en 12
                                étapes validées par le client à chaque jalon
                                clé. Le paiement est déclenché uniquement après
                                confirmation de fin des travaux par le client.
                                Aucun paiement en dehors de la plateforme n'est
                                autorisé.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">06</span> Paiement et
                                commission
                            </h4>
                            <p>
                                Le paiement s'effectue exclusivement via MTN
                                MoMo. Mesotravo prélève une commission de 10%
                                sur chaque prestation réalisée. Le prestataire
                                perçoit 90% du montant du devis approuvé. Tout
                                paiement hors-plateforme dégage Mesotravo de
                                toute responsabilité.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">07</span> Responsabilités
                            </h4>
                            <p>
                                Mesotravo est un intermédiaire de mise en
                                relation. La responsabilité des interventions
                                incombe exclusivement aux prestataires.
                                Mesotravo ne garantit pas les résultats des
                                interventions mais s'engage à mettre en relation
                                des prestataires vérifiés et certifiés.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">08</span> Propriété
                                intellectuelle
                            </h4>
                            <p>
                                L'ensemble des contenus de la plateforme (logo,
                                textes, interface) est la propriété exclusive de
                                Mesotravo. Toute reproduction sans autorisation
                                est interdite.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">09</span> Protection des
                                données
                            </h4>
                            <p>
                                Les données des utilisateurs sont collectées et
                                traitées dans le respect de la réglementation en
                                vigueur. Elles sont chiffrées et ne sont jamais
                                revendues à des tiers. Vous disposez d'un droit
                                d'accès, de rectification et de suppression de
                                vos données.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">10</span> Résiliation et
                                sanctions
                            </h4>
                            <p>
                                Mesotravo se réserve le droit de suspendre ou
                                supprimer tout compte en cas de violation des
                                CGU, de fausses informations, de comportement
                                frauduleux ou de paiement hors plateforme.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">11</span> Modification des
                                CGU
                            </h4>
                            <p>
                                Mesotravo se réserve le droit de modifier les
                                présentes CGU à tout moment. Les utilisateurs
                                seront informés par email et/ou notification. La
                                poursuite de l'utilisation vaut acceptation des
                                nouvelles CGU.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4>
                                <span class="cgu-num">12</span> Droit applicable
                                et litiges
                            </h4>
                            <p>
                                Les présentes CGU sont soumises au droit
                                béninois. Tout litige sera soumis à la
                                compétence exclusive des juridictions du Bénin.
                                Mesotravo peut jouer un rôle de médiateur sous
                                2h après signalement.
                            </p>
                        </div>

                        <div class="cgu-article">
                            <h4><span class="cgu-num">13</span> Contact</h4>
                            <p>
                                🌐 <strong>mesotravo.bj</strong><br />
                                ✉️ contact@mesotravo.bj<br />
                                📞 +229 01 96 66 36 44<br />
                                💬 WhatsApp : +229 01 96 66 36 44<br />
                                📍 Cotonou, Bénin
                            </p>
                        </div>

                        <div class="cgu-last-update">
                            Version 2.0 — Mars 2026
                        </div>
                    </div>
                </div>

                <!-- Barre de progression du scroll -->
                <div class="rc-cgu-scroll-bar">
                    <div
                        class="rc-cgu-scroll-fill"
                        :style="{ width: cguModal.scrollPct + '%' }"
                    ></div>
                </div>

                <!-- Boutons -->
                <div class="rc-modal-cgu-footer">
                    <button
                        type="button"
                        class="rc-btn rc-btn-secondary"
                        @click="closeCguModal"
                        style="flex: 1"
                    >
                        Fermer
                    </button>
                    <button
                        type="button"
                        class="rc-btn rc-btn-primary"
                        :disabled="!cguModal.scrolledToBottom"
                        @click="acceptCgu"
                        style="flex: 2"
                    >
                        ✓ Oui, j'ai lu et j'accepte
                    </button>
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
                    <p class="rc-modal-msg">{{ errorModal.message }}</p>
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
    name: "RegisterContractorComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                login: "/login",
                dashboard: "/dashboard",
                cgu: "/cgu",
                policy: "/policy",
                contact: "#",
            }),
        },
    },

    data() {
        return {
            step: 1,
            totalSteps: 3,
            loading: false,
            showPwd: false,
            showPwd2: false,

            sidebarSteps: [
                "Informations personnelles",
                "Spécialité",
                "Mot de passe",
            ],

            form: {
                first_name: "",
                last_name: "",
                phone: "",
                email: "",
                intervention_zone: "",
                experience_years: "",
                city: "",
                bio: "",
                specialty: "",
                specialties: [],
                password: "",
                password_confirmation: "",
                cgv: false,
            },

            errors: {},

            cguModal: {
                visible: false,
                scrolledToBottom: false,
                scrollPct: 0,
            },

            errorModal: {
                visible: false,
                title: "",
                message: "",
                errors: [],
            },

            specialtyList: [
                "Plomberie",
                "Électricité",
                "Menuiserie",
                "Ferronnerie",
                "Climatisation",
                "Mécanique Auto",
                "Vulcanisation",
                "Maintenance Générale",
                "Nettoyage",
                "Peinture",
                "Carrelage",
                "Jardinage",
                "Informatique",
                "Maçonnerie",
                "Soudure",
                "Sécurité / Surveillance",
                "Couture / Confection",
                "Autre",
            ],
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
        /* ── Modal CGU ── */
        openCguModal() {
            this.cguModal.visible = true;
            this.cguModal.scrolledToBottom = false;
            this.cguModal.scrollPct = 0;
            this.$nextTick(() => {
                const box = this.$refs.cguScrollBox;
                if (box) box.scrollTop = 0;
            });
        },

        closeCguModal() {
            this.cguModal.visible = false;
        },

        acceptCgu() {
            this.form.cgv = true;
            this.cguModal.visible = false;
            if (this.errors.cgv) delete this.errors.cgv;
        },

        onCguScroll(e) {
            const el = e.target;
            const scrolled = el.scrollTop + el.clientHeight;
            const total = el.scrollHeight;
            const pct = Math.min(100, Math.round((scrolled / total) * 100));
            this.cguModal.scrollPct = pct;
            if (total - scrolled <= 40) {
                this.cguModal.scrolledToBottom = true;
            }
        },

        /* ── Spécialités secondaires ── */
        toggleSpec(s) {
            if (s === this.form.specialty) return; // pas de doublon avec la principale
            const idx = this.form.specialties.indexOf(s);
            if (idx > -1) this.form.specialties.splice(idx, 1);
            else this.form.specialties.push(s);
        },

        /* ── Validation par étape ── */
        validate() {
            this.errors = {};

            if (this.step === 1) {
                if (!this.form.first_name.trim())
                    this.errors.first_name = "First name is required.";
                if (!this.form.last_name.trim())
                    this.errors.last_name = "Last name is required.";
                if (this.form.phone.replace(/\D/g, "").length !== 8)
                    this.errors.phone = "Le numéro doit contenir exactement 8 chiffres.";
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email))
                    this.errors.email = "Invalid email address.";
                if (!this.form.intervention_zone.trim())
                    this.errors.intervention_zone =
                        "Intervention zone is required.";
                if (
                    this.form.experience_years < 0 ||
                    this.form.experience_years > 60
                )
                    this.errors.experience_years =
                        "Experience must be between 0 and 60 years.";
                if (!this.form.city.trim())
                    this.errors.city = "City is required.";
            }

            if (this.step === 2) {
                if (!this.form.specialty)
                    this.errors.specialty =
                        "Please select your main specialty.";
            }

            if (this.step === 3) {
                if (this.form.password.length < 8)
                    this.errors.password = "Minimum 8 characters.";
                else if (!/[A-Z]/.test(this.form.password))
                    this.errors.password =
                        "At least one uppercase letter required.";
                else if (!/[0-9]/.test(this.form.password))
                    this.errors.password = "At least one number required.";
                if (this.form.password !== this.form.password_confirmation)
                    this.errors.password_confirmation =
                        "Passwords do not match.";
                if (!this.form.cgv)
                    this.errors.cgv =
                        "You must accept the terms and conditions.";
            }

            const valid = Object.keys(this.errors).length === 0;
            console.log(
                `[RegisterContractorComponent] validate() step ${this.step} →`,
                valid ? "OK" : "ERRORS",
                this.errors
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

        /* ── Soumission ── */
        async submit() {
            if (!this.validate()) return;

            const route = "/register/contractor/store";
            const payload = {
                first_name: this.form.first_name,
                last_name: this.form.last_name,
                phone: "01" + this.form.phone.replace(/\D/g, ""),
                email: this.form.email,
                intervention_zone: this.form.intervention_zone,
                experience_years: this.form.experience_years,
                city: this.form.city,
                bio: this.form.bio,
                specialty: this.form.specialty,
                specialties: this.form.specialties,
                password: this.form.password,
                password_confirmation: this.form.password_confirmation,
            };

            console.log(
                "[RegisterContractorComponent] ── REQUÊTE ──────────────────────"
            );
            console.log("[RegisterContractorComponent] Route   :", route);
            console.log("[RegisterContractorComponent] Méthode :", "POST");
            console.log("[RegisterContractorComponent] Payload :", {
                ...payload,
                password: "***",
                password_confirmation: "***",
            });
            console.log(
                "[RegisterContractorComponent] ────────────────────────────────"
            );

            this.loading = true;

            try {
                const response = await window.axios.post(route, payload);

                console.log(
                    "[RegisterContractorComponent] ── RÉPONSE ──────────────────────"
                );
                console.log(
                    "[RegisterContractorComponent] Status :",
                    response.status
                );
                console.log(
                    "[RegisterContractorComponent] Data   :",
                    response.data
                );
                console.log(
                    "[RegisterContractorComponent] ────────────────────────────────"
                );

                this.step = 4;
            } catch (err) {
                const status = err?.response?.status;
                const data = err?.response?.data;

                console.error(
                    "[RegisterContractorComponent] ── ERREUR ───────────────────────"
                );
                console.error(
                    "[RegisterContractorComponent] Status  :",
                    status
                );
                console.error("[RegisterContractorComponent] Data    :", data);
                console.error(
                    "[RegisterContractorComponent] Message :",
                    err?.message
                );
                console.error(
                    "[RegisterContractorComponent] ────────────────────────────────"
                );

                this.errorModal.visible = true;
                this.errorModal.errors = [];

                if (status === 422 && data?.errors) {
                    this.errorModal.title = "Données invalides";
                    this.errorModal.message =
                        data?.message ||
                        "Veuillez corriger les erreurs suivantes :";
                    this.errorModal.errors = Object.values(data.errors).flat();
                } else if (status === 409) {
                    this.errorModal.title = "Compte déjà existant";
                    this.errorModal.message =
                        data?.message ||
                        "Un compte avec cet email existe déjà.";
                } else if (status === 500) {
                    this.errorModal.title = "Erreur serveur";
                    this.errorModal.message =
                        "Une erreur interne est survenue. Veuillez réessayer.";
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
    margin-bottom: 20px;
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

.rc-pro-badge {
    display: inline-block;
    background: var(--or, #f97316);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}
.rc-sidebar-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 8px;
}
.rc-sidebar-sub {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 28px;
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
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
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
    font-size: 13px;
    font-weight: 500;
    color: #aaa;
}
.rc-step.active .rc-step-label,
.rc-step.done .rc-step-label {
    color: #fff;
    font-weight: 600;
}

.rc-side-note {
    margin-top: 20px;
    background: rgba(252, 211, 77, 0.08);
    border: 1px solid rgba(252, 211, 77, 0.25);
    border-radius: 10px;
    padding: 14px;
    font-size: 12px;
    color: #fcd34d;
    line-height: 1.6;
}

.rc-sidebar-footer {
    margin-top: 20px;
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
    max-width: 660px;
}
@media (max-width: 480px) {
    .rc-card {
        padding: 24px 18px;
    }
}

/* GOOGLE BTN */
.rc-google-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 11px 16px;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    font-size: 0.92rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    margin-bottom: 14px;
    transition: background 0.2s, border-color 0.2s;
}
.rc-google-btn:hover { background: #f9fafb; border-color: #d1d5db; }
.rc-or-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}
.rc-or-divider::before,
.rc-or-divider::after {
    content: '';
    flex: 1;
    border-top: 1px solid #e5e7eb;
}
.rc-or-divider span { font-size: 0.8rem; color: #9ca3af; white-space: nowrap; }

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

/* STEP */
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
.rc-optional {
    font-size: 11px;
    color: var(--grm, #8a7d78);
    font-weight: 400;
}
.rc-span2 {
    grid-column: 1/-1;
}

.rc-input-wrap {
    position: relative;
}
.rc-input {
    width: 100%;
    padding: 11px 44px 11px 14px;
    border: 2px solid var(--grl, #e8ddd4);
    border-radius: 9px;
    font-size: 14px;
    font-family: "Poppins", sans-serif;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
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
/* ── Phone prefix ── */
.rc-phone-wrap {
    display: flex;
    align-items: center;
    gap: 0;
}
.rc-phone-prefix {
    padding: 11px 12px;
    background: var(--grl, #e8ddd4);
    border: 2px solid var(--grl, #e8ddd4);
    border-right: none;
    border-radius: 9px 0 0 9px;
    font-size: 14px;
    font-weight: 600;
    color: var(--dk, #1c1412);
    white-space: nowrap;
    font-family: "Poppins", sans-serif;
}
.rc-phone-input {
    border-radius: 0 9px 9px 0 !important;
    letter-spacing: 1px;
}
.rc-textarea {
    min-height: 80px;
    resize: vertical;
    padding: 11px 14px;
}
.rc-err {
    font-size: 12px;
    color: #ef4444;
    margin-top: 2px;
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

/* FORCE MDP */
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

/* TAGS */
.rc-tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
}
.rc-tag {
    padding: 7px 15px;
    border: 2px solid var(--grl, #e8ddd4);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--gr, #7c6a5a);
}
.rc-tag:hover {
    border-color: var(--or, #f97316);
    color: var(--or, #f97316);
}
.rc-tag.selected {
    background: var(--or, #f97316);
    border-color: var(--or, #f97316);
    color: #fff;
}
.rc-tag.disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.rc-add-note {
    margin-top: 16px;
    padding: 12px 16px;
    background: var(--cr, #fef3e2);
    border-radius: 10px;
    font-size: 13px;
    color: var(--gr, #7c6a5a);
}
.rc-add-note a {
    color: var(--or, #f97316);
    font-weight: 600;
    text-decoration: none;
}
.rc-add-note a:hover {
    text-decoration: underline;
}

/* CGU */
.rc-cgu-block {
    margin-bottom: 16px;
}
.rc-btn-cgu {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 12px 18px;
    border-radius: 10px;
    border: 2px dashed var(--or, #f97316);
    background: var(--or3, #fff7ed);
    color: var(--or, #f97316);
    font-size: 14px;
    font-weight: 700;
    font-family: "Poppins", sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    justify-content: center;
}
.rc-btn-cgu:hover {
    background: rgba(249, 115, 22, 0.12);
    transform: translateY(-1px);
}
.rc-cgu-accepted {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    font-size: 13.5px;
    color: #16a34a;
    font-weight: 600;
}
.rc-cgu-check {
    font-size: 16px;
}
.rc-cgu-reread {
    background: none;
    border: none;
    color: var(--or, #f97316);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    font-family: "Poppins", sans-serif;
}

/* MODAL CGU */
.rc-modal-cgu {
    max-width: 860px;
    padding: 0;
    display: flex;
    flex-direction: column;
    max-height: 94vh;
    overflow: hidden;
}
.rc-modal-cgu-header {
    padding: 24px 28px 16px;
    border-bottom: 1.5px solid var(--grl, #e8ddd4);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    text-align: center;
}
.rc-modal-cgu-hint {
    font-size: 12.5px;
    color: var(--grm, #8a7d78);
    margin: 0;
    font-style: italic;
}
.rc-modal-cgu-hint--ok {
    color: #16a34a;
    font-style: normal;
    font-weight: 600;
}
.rc-modal-cgu-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 28px;
    scroll-behavior: smooth;
}
.rc-modal-cgu-body::-webkit-scrollbar {
    width: 6px;
}
.rc-modal-cgu-body::-webkit-scrollbar-track {
    background: var(--grl, #e8ddd4);
    border-radius: 99px;
}
.rc-modal-cgu-body::-webkit-scrollbar-thumb {
    background: var(--or, #f97316);
    border-radius: 99px;
}
.rc-cgu-scroll-bar {
    height: 4px;
    background: var(--grl, #e8ddd4);
    flex-shrink: 0;
}
.rc-cgu-scroll-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or, #f97316), var(--or2, #ea580c));
    transition: width 0.1s;
    border-radius: 0 99px 99px 0;
}
.rc-modal-cgu-footer {
    padding: 16px 28px 24px;
    display: flex;
    gap: 12px;
    border-top: 1.5px solid var(--grl, #e8ddd4);
}

/* Contenu CGU dans le modal */
.legal-intro-modal {
    background: var(--or3, #fff7ed);
    border: 1px solid rgba(249, 115, 22, 0.2);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 13.5px;
    color: var(--dk, #1c1412);
    line-height: 1.7;
}
.legal-intro-modal p {
    margin-bottom: 6px;
}
.legal-intro-modal p:last-child {
    margin-bottom: 0;
}
.cgu-article {
    padding: 16px 0;
    border-bottom: 1px solid var(--grl, #e8ddd4);
}
.cgu-article:last-of-type {
    border-bottom: none;
}
.cgu-article h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 8px;
}
.cgu-num {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: linear-gradient(
        135deg,
        var(--or, #f97316),
        var(--or2, #ea580c)
    );
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cgu-article p {
    font-size: 13.5px;
    color: var(--gr, #7c6a5a);
    line-height: 1.75;
}
.cgu-last-update {
    text-align: center;
    font-size: 12px;
    color: var(--grm, #8a7d78);
    padding: 16px 0 4px;
    font-style: italic;
}

/* BOUTONS */
.rc-btn-row {
    display: flex;
    gap: 12px;
    margin-top: 24px;
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
    font-size: 64px;
    margin-bottom: 20px;
}
.rc-success h2 {
    font-size: 24px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 12px;
}
.rc-success p {
    font-size: 14.5px;
    color: var(--gr, #7c6a5a);
    line-height: 1.8;
    margin-bottom: 20px;
}
.rc-success strong {
    color: var(--dk, #1c1412);
}
.rc-success-info {
    background: var(--cr, #fef3e2);
    border: 2px solid var(--grl, #e8ddd4);
    border-radius: 12px;
    padding: 18px;
    text-align: left;
    margin-bottom: 24px;
}
.rc-success-info-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk, #1c1412);
    margin-bottom: 10px;
}
.rc-success-info-list {
    font-size: 13px;
    color: var(--gr, #7c6a5a);
    line-height: 2.2;
}

/* MODAL ERREUR */
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
