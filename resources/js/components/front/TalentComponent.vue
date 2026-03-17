<template>
    <div class="talent-page">
        <!-- HERO -->
        <section class="tl-hero">
            <div class="tl-hero-glow"></div>
            <div class="tl-hero-glow2"></div>
            <div class="tl-hero-dots"></div>

            <div class="tl-hero-inner">
                <div class="tl-badge au">
                    <span class="bdot"></span>
                    Experts BAC+3 minimum - Valides par Resotravo
                </div>
                <h1 class="au1">
                    Espace Talents<br />
                    <span class="hl">les experts qu'il</span><br />
                    vous faut
                </h1>
                <p class="tl-hero-desc au2">
                    Ingénieurs, experts techniques et professionnels
                    qualifies.<br />
                    Chaque profil est verifie et valide manuellement par
                    l'equipe Resotravo.
                </p>
                <div class="tl-hero-btns au3">
                    <button
                        class="btn btn-primary btn-lg"
                        @click="activeTab = 'search'"
                    >
                        Trouver un Talent &rarr;
                    </button>
                    <button
                        class="btn btn-ghost btn-lg"
                        @click="activeTab = 'register'"
                    >
                        Devenir Talent
                    </button>
                </div>
                <div class="tl-hero-stats au4">
                    <div class="hstat">
                        <span class="num">120+</span>
                        <span class="lbl">Talents valides</span>
                    </div>
                    <div class="hstat">
                        <span class="num">18</span>
                        <span class="lbl">Domaines couverts</span>
                    </div>
                    <div class="hstat">
                        <span class="num">BAC+3</span>
                        <span class="lbl">Niveau minimum</span>
                    </div>
                    <div class="hstat">
                        <span class="num">100%</span>
                        <span class="lbl">Profils verifies</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- TABS -->
        <div class="tl-tabs-bar">
            <div class="tl-tabs">
                <button
                    class="tl-tab"
                    :class="{ active: activeTab === 'search' }"
                    @click="activeTab = 'search'"
                >
                    Trouver un Talent
                </button>
                <button
                    class="tl-tab"
                    :class="{ active: activeTab === 'register' }"
                    @click="activeTab = 'register'"
                >
                    Devenir Talent
                </button>
            </div>
        </div>

        <!-- TAB 1 : RECHERCHE DE TALENTS -->
        <section class="tl-section" v-show="activeTab === 'search'">
            <!-- Filtres -->
            <div class="tl-filters reveal">
                <div class="tl-filter-row">
                    <input
                        class="search-input"
                        type="text"
                        v-model="searchQuery"
                        placeholder="Rechercher par nom, domaine, competence..."
                    />
                    <select class="tl-select" v-model="filterDomain">
                        <option value="">Tous les domaines</option>
                        <option v-for="d in domains" :key="d" :value="d">
                            {{ d }}
                        </option>
                    </select>
                    <select class="tl-select" v-model="filterLevel">
                        <option value="">Tous les niveaux</option>
                        <option value="bac3">BAC+3</option>
                        <option value="bac4">BAC+4 / BAC+5</option>
                        <option value="bac5plus">BAC+5 et plus</option>
                    </select>
                    <select class="tl-select" v-model="filterAvailability">
                        <option value="">Toutes disponibilites</option>
                        <option value="immediate">
                            Disponible immediatement
                        </option>
                        <option value="parttime">Temps partiel</option>
                        <option value="mission">Mission specifique</option>
                    </select>
                </div>
                <div class="tl-filter-tags">
                    <span
                        class="tl-ftag"
                        v-for="tag in domainTags"
                        :key="tag.label"
                        :class="{ active: filterDomain === tag.label }"
                        @click="
                            filterDomain =
                                filterDomain === tag.label ? '' : tag.label
                        "
                    >
                        {{ tag.icon }} {{ tag.label }}
                    </span>
                </div>
            </div>

            <div class="tl-results-header">
                <span class="tl-count"
                    >{{ filteredTalents.length }} talent(s) trouve(s)</span
                >
                <select class="tl-select tl-sort" v-model="sortBy">
                    <option value="score">Trier par note</option>
                    <option value="exp">Trier par experience</option>
                    <option value="name">Trier par nom</option>
                </select>
            </div>

            <!-- Grille de talents -->
            <div class="tl-grid">
                <div
                    class="tl-card reveal"
                    :class="'reveal-d' + ((i % 3) + 1)"
                    v-for="(talent, i) in filteredTalents"
                    :key="talent.id"
                    @click="openProfile(talent)"
                >
                    <!-- Badge valide -->
                    <div class="tl-card-validated">Valide Resotravo</div>

                    <!-- Avatar + infos principales -->
                    <div class="tl-card-top">
                        <div
                            class="tl-avatar"
                            :style="{ background: talent.color }"
                        >
                            {{ talent.name[0] }}
                        </div>
                        <div class="tl-card-info">
                            <div class="tl-card-name">{{ talent.name }}</div>
                            <div class="tl-card-title">{{ talent.title }}</div>
                            <div class="tl-card-domain">
                                <span class="tl-domain-badge"
                                    >{{ talent.domainIcon }}
                                    {{ talent.domain }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="tl-card-meta">
                        <span>{{ talent.level }}</span>
                        <span>{{ talent.exp }} ans exp.</span>
                        <span>{{ talent.location }}</span>
                    </div>

                    <!-- Competences -->
                    <div class="tl-skills">
                        <span
                            class="tl-skill"
                            v-for="skill in talent.skills.slice(0, 3)"
                            :key="skill"
                        >
                            {{ skill }}
                        </span>
                        <span
                            class="tl-skill tl-skill-more"
                            v-if="talent.skills.length > 3"
                        >
                            +{{ talent.skills.length - 3 }}
                        </span>
                    </div>

                    <!-- Score et disponibilite -->
                    <div class="tl-card-footer">
                        <div class="tl-score">
                            <span class="tl-stars"
                                >&#9733;&#9733;&#9733;&#9733;&#9733;</span
                            >
                            <span class="tl-score-val"
                                >{{ talent.score }}/5</span
                            >
                        </div>
                        <div class="tl-avail" :class="talent.availClass">
                            {{ talent.availability }}
                        </div>
                    </div>

                    <button
                        class="tl-contact-btn"
                        @click.stop="contactTalent(talent)"
                    >
                        Contacter &rarr;
                    </button>
                </div>
            </div>

            <div class="tl-empty" v-if="filteredTalents.length === 0">
                <div class="tl-empty-icon">&#x1F50D;</div>
                <div class="tl-empty-title">Aucun talent trouve</div>
                <div class="tl-empty-desc">
                    Essayez d'autres filtres ou revenez plus tard.
                </div>
            </div>
        </section>

        <!-- MODAL PROFIL COMPLET -->
        <div
            class="tl-modal-overlay"
            v-if="showProfile"
            @click.self="showProfile = false"
        >
            <div class="tl-modal" v-if="selectedTalent">
                <div class="tl-modal-header">
                    <div
                        class="tl-modal-avatar"
                        :style="{ background: selectedTalent.color }"
                    >
                        {{ selectedTalent.name[0] }}
                    </div>
                    <div class="tl-modal-meta">
                        <h2>{{ selectedTalent.name }}</h2>
                        <div class="tl-card-title">
                            {{ selectedTalent.title }}
                        </div>
                        <div class="tl-modal-badges">
                            <span class="tl-domain-badge"
                                >{{ selectedTalent.domainIcon }}
                                {{ selectedTalent.domain }}</span
                            >
                            <span class="tl-validated-badge"
                                >Valide Resotravo</span
                            >
                        </div>
                    </div>
                    <button class="tl-modal-close" @click="showProfile = false">
                        &#x2715;
                    </button>
                </div>

                <div class="tl-modal-body">
                    <!-- Stats rapides -->
                    <div class="tl-modal-stats">
                        <div class="tl-mstat">
                            <span class="tl-mstat-val">{{
                                selectedTalent.level
                            }}</span>
                            <span class="tl-mstat-lbl">Niveau</span>
                        </div>
                        <div class="tl-mstat">
                            <span class="tl-mstat-val"
                                >{{ selectedTalent.exp }} ans</span
                            >
                            <span class="tl-mstat-lbl">Experience</span>
                        </div>
                        <div class="tl-mstat">
                            <span class="tl-mstat-val"
                                >{{ selectedTalent.score }}/5</span
                            >
                            <span class="tl-mstat-lbl">Note</span>
                        </div>
                        <div class="tl-mstat">
                            <span class="tl-mstat-val">{{
                                selectedTalent.location
                            }}</span>
                            <span class="tl-mstat-lbl">Localisation</span>
                        </div>
                    </div>

                    <!-- A propos -->
                    <div class="tl-modal-section">
                        <h4>A propos</h4>
                        <p>{{ selectedTalent.bio }}</p>
                    </div>

                    <!-- Competences -->
                    <div class="tl-modal-section">
                        <h4>Competences</h4>
                        <div class="tl-skills">
                            <span
                                class="tl-skill"
                                v-for="skill in selectedTalent.skills"
                                :key="skill"
                            >
                                {{ skill }}
                            </span>
                        </div>
                    </div>

                    <!-- Diplomes -->
                    <div class="tl-modal-section">
                        <h4>Diplomes et certifications</h4>
                        <div class="tl-diplomas">
                            <div
                                class="tl-diploma"
                                v-for="d in selectedTalent.diplomas"
                                :key="d.title"
                            >
                                <div class="tl-diploma-icon">&#x1F393;</div>
                                <div>
                                    <div class="tl-diploma-title">
                                        {{ d.title }}
                                    </div>
                                    <div class="tl-diploma-school">
                                        {{ d.school }} - {{ d.year }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Experiences -->
                    <div class="tl-modal-section">
                        <h4>Experiences professionnelles</h4>
                        <div class="tl-experiences">
                            <div
                                class="tl-exp-item"
                                v-for="e in selectedTalent.experiences"
                                :key="e.company"
                            >
                                <div class="tl-exp-dot"></div>
                                <div>
                                    <div class="tl-exp-role">{{ e.role }}</div>
                                    <div class="tl-exp-company">
                                        {{ e.company }}
                                    </div>
                                    <div class="tl-exp-period">
                                        {{ e.period }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disponibilite -->
                    <div class="tl-modal-section">
                        <h4>Disponibilite</h4>
                        <div
                            class="tl-avail"
                            :class="selectedTalent.availClass"
                            style="display: inline-flex"
                        >
                            {{ selectedTalent.availability }}
                        </div>
                    </div>
                </div>

                <div class="tl-modal-footer">
                    <button
                        class="btn btn-outline"
                        @click="showProfile = false"
                    >
                        Fermer
                    </button>
                    <button
                        class="btn btn-primary btn-lg"
                        @click="contactTalent(selectedTalent)"
                    >
                        Envoyer un message &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CONTACT -->
        <div
            class="tl-modal-overlay"
            v-if="showContact"
            @click.self="showContact = false"
        >
            <div class="tl-modal tl-modal-sm">
                <div class="tl-modal-header">
                    <h3>
                        Contacter {{ contactTarget ? contactTarget.name : "" }}
                    </h3>
                    <button class="tl-modal-close" @click="showContact = false">
                        &#x2715;
                    </button>
                </div>
                <div class="tl-modal-body">
                    <div class="mk-field">
                        <label class="mk-label"
                            >Objet de la mission
                            <span class="mk-req">*</span></label
                        >
                        <input
                            class="mk-input"
                            type="text"
                            v-model="contactForm.subject"
                            placeholder="Ex : Etude de faisabilite installation solaire"
                        />
                    </div>
                    <div class="mk-field">
                        <label class="mk-label"
                            >Description de votre besoin
                            <span class="mk-req">*</span></label
                        >
                        <textarea
                            class="mk-input mk-textarea"
                            v-model="contactForm.message"
                            placeholder="Decrivez votre projet, le type de mission, la duree prevue..."
                            rows="5"
                        ></textarea>
                    </div>
                    <div class="mk-field">
                        <label class="mk-label">Budget indicatif (FCFA)</label>
                        <input
                            class="mk-input"
                            type="text"
                            v-model="contactForm.budget"
                            placeholder="Ex : 300 000 FCFA"
                        />
                    </div>
                    <div class="mk-field">
                        <label class="mk-label">Date de debut souhaitee</label>
                        <input
                            class="mk-input"
                            type="date"
                            v-model="contactForm.startDate"
                        />
                    </div>
                </div>
                <div class="tl-modal-footer">
                    <button
                        class="btn btn-outline"
                        @click="showContact = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary btn-lg"
                        :disabled="!contactForm.subject || !contactForm.message"
                        @click="submitContact"
                    >
                        {{
                            contactSent
                                ? "Message envoye !"
                                : "Envoyer le message &rarr;"
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 2 : DEVENIR TALENT -->
        <section class="tl-section" v-show="activeTab === 'register'">
            <div class="tl-register-layout">
                <!-- Formulaire -->
                <div class="tl-register-form reveal">
                    <div class="tl-form-header">
                        <h2>Rejoindre l'Espace Talents</h2>
                        <p>
                            Votre profil sera examine et valide manuellement par
                            l'equipe Resotravo sous 48h.
                        </p>
                    </div>

                    <!-- ETAPE 1 : Infos personnelles -->
                    <div
                        class="tl-form-step"
                        :class="{ active: formStep === 1 }"
                    >
                        <div class="tl-step-header">
                            <div class="tl-step-num">1</div>
                            <h3>Informations personnelles</h3>
                        </div>
                        <div class="tl-form-grid" v-show="formStep === 1">
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Nom <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="text"
                                    v-model="talentForm.nom"
                                    placeholder="Votre nom"
                                />
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Prenom <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="text"
                                    v-model="talentForm.prenom"
                                    placeholder="Votre prenom"
                                />
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Email <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="email"
                                    v-model="talentForm.email"
                                    placeholder="votre@email.com"
                                />
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Telephone
                                    <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="tel"
                                    v-model="talentForm.phone"
                                    placeholder="+229 XX XX XX XX"
                                />
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Titre professionnel
                                    <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="text"
                                    v-model="talentForm.title"
                                    placeholder="Ex : Ingenieur en Genie Civil"
                                />
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Domaine d'expertise
                                    <span class="mk-req">*</span></label
                                >
                                <select
                                    class="mk-input"
                                    v-model="talentForm.domain"
                                >
                                    <option value="">Selectionner...</option>
                                    <option
                                        v-for="d in domains"
                                        :key="d"
                                        :value="d"
                                    >
                                        {{ d }}
                                    </option>
                                </select>
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Niveau d'etudes
                                    <span class="mk-req">*</span></label
                                >
                                <select
                                    class="mk-input"
                                    v-model="talentForm.level"
                                >
                                    <option value="">Selectionner...</option>
                                    <option value="Licence (BAC+3)">
                                        Licence (BAC+3)
                                    </option>
                                    <option value="Master 1 (BAC+4)">
                                        Master 1 (BAC+4)
                                    </option>
                                    <option value="Master 2 (BAC+5)">
                                        Master 2 (BAC+5)
                                    </option>
                                    <option value="Doctorat (BAC+8)">
                                        Doctorat (BAC+8)
                                    </option>
                                    <option value="Ingenieur (BAC+5)">
                                        Ingenieur (BAC+5)
                                    </option>
                                </select>
                            </div>
                            <div class="mk-field">
                                <label class="mk-label"
                                    >Annees d'experience
                                    <span class="mk-req">*</span></label
                                >
                                <input
                                    class="mk-input"
                                    type="number"
                                    v-model="talentForm.exp"
                                    placeholder="Ex : 5"
                                    min="0"
                                />
                            </div>
                            <div class="mk-field mk-field-full">
                                <label class="mk-label"
                                    >Biographie / Presentation
                                    <span class="mk-req">*</span></label
                                >
                                <textarea
                                    class="mk-input mk-textarea"
                                    v-model="talentForm.bio"
                                    placeholder="Presentez-vous, votre parcours, vos points forts..."
                                    rows="4"
                                ></textarea>
                            </div>
                            <div class="mk-field mk-field-full">
                                <button
                                    class="btn btn-primary btn-lg"
                                    style="width: 100%"
                                    :disabled="!step1Valid"
                                    @click="formStep = 2"
                                >
                                    Continuer &rarr;
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ETAPE 2 : Diplomes et certifications -->
                    <div
                        class="tl-form-step"
                        :class="{ active: formStep === 2 }"
                    >
                        <div class="tl-step-header">
                            <div class="tl-step-num">2</div>
                            <h3>Diplomes et certifications</h3>
                        </div>
                        <div v-show="formStep === 2">
                            <p class="tl-step-info">
                                Maximum 3 diplomes et 5 certifications.
                            </p>

                            <div class="tl-diploma-list">
                                <div
                                    class="tl-diploma-item"
                                    v-for="(d, i) in talentForm.diplomas"
                                    :key="i"
                                >
                                    <div class="tl-form-grid">
                                        <div class="mk-field">
                                            <label class="mk-label"
                                                >Intitule du diplome</label
                                            >
                                            <input
                                                class="mk-input"
                                                type="text"
                                                v-model="d.title"
                                                placeholder="Ex : Master Genie Electrique"
                                            />
                                        </div>
                                        <div class="mk-field">
                                            <label class="mk-label"
                                                >Etablissement</label
                                            >
                                            <input
                                                class="mk-input"
                                                type="text"
                                                v-model="d.school"
                                                placeholder="Ex : UAC Abomey-Calavi"
                                            />
                                        </div>
                                        <div class="mk-field">
                                            <label class="mk-label"
                                                >Annee</label
                                            >
                                            <input
                                                class="mk-input"
                                                type="number"
                                                v-model="d.year"
                                                placeholder="2020"
                                            />
                                        </div>
                                        <div
                                            class="mk-field"
                                            style="
                                                display: flex;
                                                align-items: flex-end;
                                            "
                                        >
                                            <button
                                                class="tl-remove-btn"
                                                @click="removeDiploma(i)"
                                                v-if="
                                                    talentForm.diplomas.length >
                                                    1
                                                "
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button
                                class="tl-add-btn"
                                @click="addDiploma"
                                v-if="talentForm.diplomas.length < 3"
                            >
                                + Ajouter un diplome
                            </button>

                            <div class="tl-cert-list" style="margin-top: 20px">
                                <label
                                    class="mk-label"
                                    style="display: block; margin-bottom: 8px"
                                    >Certifications (max 5)</label
                                >
                                <div
                                    class="tl-cert-item"
                                    v-for="(c, i) in talentForm.certifications"
                                    :key="i"
                                    style="
                                        display: flex;
                                        gap: 8px;
                                        margin-bottom: 8px;
                                    "
                                >
                                    <input
                                        class="mk-input"
                                        type="text"
                                        v-model="talentForm.certifications[i]"
                                        :placeholder="
                                            'Certification ' + (i + 1)
                                        "
                                        style="flex: 1"
                                    />
                                    <button
                                        class="tl-remove-btn"
                                        @click="removeCert(i)"
                                        v-if="
                                            talentForm.certifications.length > 1
                                        "
                                    >
                                        x
                                    </button>
                                </div>
                                <button
                                    class="tl-add-btn"
                                    @click="addCert"
                                    v-if="talentForm.certifications.length < 5"
                                >
                                    + Ajouter une certification
                                </button>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    gap: 10px;
                                    margin-top: 24px;
                                "
                            >
                                <button
                                    class="btn btn-outline"
                                    @click="formStep = 1"
                                >
                                    Retour
                                </button>
                                <button
                                    class="btn btn-primary btn-lg"
                                    style="flex: 1"
                                    @click="formStep = 3"
                                >
                                    Continuer &rarr;
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ETAPE 3 : Experiences et liens -->
                    <div
                        class="tl-form-step"
                        :class="{ active: formStep === 3 }"
                    >
                        <div class="tl-step-header">
                            <div class="tl-step-num">3</div>
                            <h3>Experiences professionnelles</h3>
                        </div>
                        <div v-show="formStep === 3">
                            <div
                                class="tl-exp-form"
                                v-for="(e, i) in talentForm.experiences"
                                :key="i"
                            >
                                <div class="tl-form-grid">
                                    <div class="mk-field">
                                        <label class="mk-label">Poste</label>
                                        <input
                                            class="mk-input"
                                            type="text"
                                            v-model="e.role"
                                            placeholder="Ex : Ingenieur electrique"
                                        />
                                    </div>
                                    <div class="mk-field">
                                        <label class="mk-label"
                                            >Entreprise</label
                                        >
                                        <input
                                            class="mk-input"
                                            type="text"
                                            v-model="e.company"
                                            placeholder="Ex : SBEE"
                                        />
                                    </div>
                                    <div class="mk-field">
                                        <label class="mk-label">Periode</label>
                                        <input
                                            class="mk-input"
                                            type="text"
                                            v-model="e.period"
                                            placeholder="Ex : 2020 - 2024"
                                        />
                                    </div>
                                    <div
                                        class="mk-field"
                                        style="
                                            display: flex;
                                            align-items: flex-end;
                                        "
                                    >
                                        <button
                                            class="tl-remove-btn"
                                            @click="removeExp(i)"
                                            v-if="
                                                talentForm.experiences.length >
                                                1
                                            "
                                        >
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button class="tl-add-btn" @click="addExp">
                                + Ajouter une experience
                            </button>

                            <div class="tl-form-grid" style="margin-top: 20px">
                                <div class="mk-field">
                                    <label class="mk-label"
                                        >LinkedIn (optionnel)</label
                                    >
                                    <input
                                        class="mk-input"
                                        type="url"
                                        v-model="talentForm.linkedin"
                                        placeholder="https://linkedin.com/in/..."
                                    />
                                </div>
                                <div class="mk-field">
                                    <label class="mk-label"
                                        >Portfolio (optionnel)</label
                                    >
                                    <input
                                        class="mk-input"
                                        type="url"
                                        v-model="talentForm.portfolio"
                                        placeholder="https://..."
                                    />
                                </div>
                                <div class="mk-field">
                                    <label class="mk-label"
                                        >Disponibilite
                                        <span class="mk-req">*</span></label
                                    >
                                    <select
                                        class="mk-input"
                                        v-model="talentForm.availability"
                                    >
                                        <option value="">
                                            Selectionner...
                                        </option>
                                        <option value="immediate">
                                            Disponible immediatement
                                        </option>
                                        <option value="parttime">
                                            Temps partiel
                                        </option>
                                        <option value="mission">
                                            Mission specifique uniquement
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div
                                class="mk-form-notice"
                                style="margin-top: 20px"
                            >
                                Votre profil sera examine par l'equipe Resotravo
                                sous 48h. Vous recevrez une notification par
                                email et SMS.
                            </div>

                            <div
                                style="
                                    display: flex;
                                    gap: 10px;
                                    margin-top: 20px;
                                "
                            >
                                <button
                                    class="btn btn-outline"
                                    @click="formStep = 2"
                                >
                                    Retour
                                </button>
                                <button
                                    class="btn btn-primary btn-lg"
                                    style="flex: 1"
                                    :disabled="talentFormSubmitted"
                                    @click="submitTalentForm"
                                >
                                    {{
                                        talentFormSubmitted
                                            ? "Dossier envoye - En attente de validation"
                                            : "Soumettre mon profil Talent &rarr;"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avantages -->
                <div class="tl-register-side reveal reveal-right">
                    <div class="tl-benefits-card">
                        <h4>Pourquoi rejoindre l'Espace Talents ?</h4>
                        <div
                            class="tl-benefit"
                            v-for="b in benefits"
                            :key="b.title"
                        >
                            <div class="tl-benefit-icon">{{ b.icon }}</div>
                            <div>
                                <div class="tl-benefit-title">
                                    {{ b.title }}
                                </div>
                                <div class="tl-benefit-desc">{{ b.desc }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tl-process-card">
                        <h4>Processus de validation</h4>
                        <div
                            class="tl-proc-step"
                            v-for="(s, i) in processSteps"
                            :key="i"
                        >
                            <div class="tl-proc-num">{{ i + 1 }}</div>
                            <div>
                                <div class="tl-proc-title">{{ s.title }}</div>
                                <div class="tl-proc-desc">{{ s.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CE QUE DISENT LES ENTREPRISES -->
        <section class="sec sec-cr" v-show="activeTab === 'search'">
            <div class="sec-tag reveal">Avis entreprises</div>
            <div class="sec-title reveal reveal-d1">
                Ils font confiance aux Talents Resotravo
            </div>
            <div class="sec-sub reveal reveal-d2">
                Des experts selectionnes avec soin pour chaque mission.
            </div>
            <div class="tl-reviews-grid">
                <div
                    class="tl-review reveal"
                    :class="'reveal-d' + ((i % 3) + 1)"
                    v-for="(r, i) in reviews"
                    :key="r.company"
                >
                    <div class="tl-review-stars">
                        &#9733;&#9733;&#9733;&#9733;&#9733;
                    </div>
                    <p class="tl-review-text">"{{ r.text }}"</p>
                    <div class="tl-review-author">
                        <div class="tl-review-av">{{ r.company[0] }}</div>
                        <div>
                            <div class="tl-review-company">{{ r.company }}</div>
                            <div class="tl-review-role">{{ r.role }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <div class="cta-final">
            <div class="cta-inner reveal">
                <h2>Vous etes une entreprise ?</h2>
                <p>
                    Publiez un appel d'offres et trouvez les meilleurs Talents
                    du Benin pour vos projets.
                </p>
                <div class="cta-btns">
                    <a class="btn btn-dark btn-lg" :href="routes.market"
                        >Voir les appels d'offres &rarr;</a
                    >
                    <a class="btn btn-ghost btn-lg" :href="routes.register"
                        >Creer un compte entreprise</a
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TalentComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                register: "/register",
                login: "/login",
                market: "/appels-offres",
            }),
        },
    },

    data() {
        return {
            activeTab: "search",
            searchQuery: "",
            filterDomain: "",
            filterLevel: "",
            filterAvailability: "",
            sortBy: "score",
            showProfile: false,
            showContact: false,
            selectedTalent: null,
            contactTarget: null,
            contactSent: false,
            formStep: 1,
            talentFormSubmitted: false,

            contactForm: {
                subject: "",
                message: "",
                budget: "",
                startDate: "",
            },

            talentForm: {
                nom: "",
                prenom: "",
                email: "",
                phone: "",
                title: "",
                domain: "",
                level: "",
                exp: "",
                bio: "",
                linkedin: "",
                portfolio: "",
                availability: "",
                diplomas: [{ title: "", school: "", year: "" }],
                certifications: [""],
                experiences: [{ role: "", company: "", period: "" }],
            },

            domains: [
                "Genie Civil",
                "Genie Electrique",
                "Genie Informatique",
                "Genie Mecanique",
                "Architecture",
                "Urbanisme",
                "Energie Solaire",
                "Telecommunications",
                "Finance",
                "Juridique",
                "Sante",
                "Agriculture",
                "Environnement",
                "Management de projet",
                "Audit & Controle",
                "Marketing digital",
                "Logistique",
                "Ressources humaines",
            ],

            domainTags: [
                { icon: "⚡", label: "Genie Electrique" },
                { icon: "🏗️", label: "Genie Civil" },
                { icon: "💻", label: "Genie Informatique" },
                { icon: "☀️", label: "Energie Solaire" },
                { icon: "📐", label: "Architecture" },
                { icon: "📡", label: "Telecommunications" },
                { icon: "💰", label: "Finance" },
            ],

            talents: [
                {
                    id: 1,
                    name: "Dr. Kokou Mensah",
                    title: "Ingenieur en Genie Civil",
                    domain: "Genie Civil",
                    domainIcon: "🏗️",
                    level: "Doctorat",
                    exp: 12,
                    location: "Cotonou",
                    score: 4.9,
                    color: "linear-gradient(135deg, #F97316, #EA580C)",
                    availability: "Disponible",
                    availClass: "avail-yes",
                    skills: [
                        "BIM",
                        "Beton arme",
                        "Gestion chantier",
                        "AutoCAD",
                        "Estimation",
                    ],
                    bio: "Docteur en Genie Civil avec 12 ans d'experience dans la conception et supervision de projets de construction au Benin et en Afrique de l'Ouest. Specialiste en structures beton arme et gestion de grands chantiers.",
                    diplomas: [
                        {
                            title: "Doctorat Genie Civil",
                            school: "UAC Abomey-Calavi",
                            year: 2015,
                        },
                        {
                            title: "Master Gestion de Projets",
                            school: "ENAM Benin",
                            year: 2011,
                        },
                    ],
                    experiences: [
                        {
                            role: "Directeur Technique",
                            company: "SOTRA BTP",
                            period: "2018 - Present",
                        },
                        {
                            role: "Chef de Projet",
                            company: "Agence BENIN-INFRA",
                            period: "2015 - 2018",
                        },
                    ],
                },
                {
                    id: 2,
                    name: "Ing. Adjovi Ahossou",
                    title: "Ingenieure en Genie Electrique",
                    domain: "Genie Electrique",
                    domainIcon: "⚡",
                    level: "Ingenieur (BAC+5)",
                    exp: 8,
                    location: "Cotonou",
                    score: 4.8,
                    color: "linear-gradient(135deg, #3b82f6, #1d4ed8)",
                    availability: "Temps partiel",
                    availClass: "avail-partial",
                    skills: [
                        "Tableaux electriques",
                        "HTA/HTB",
                        "SCADA",
                        "Energie solaire",
                        "Audit energetique",
                    ],
                    bio: "Ingenieure diplome avec 8 ans d'experience dans les installations electriques industrielles et les energies renouvelables. Experte en audit energetique et projets solaires.",
                    diplomas: [
                        {
                            title: "Ingenieur Genie Electrique",
                            school: "EPAC UAC",
                            year: 2016,
                        },
                    ],
                    experiences: [
                        {
                            role: "Ingenieure Electrique",
                            company: "SBEE",
                            period: "2016 - Present",
                        },
                    ],
                },
                {
                    id: 3,
                    name: "M. Rachid Biokou",
                    title: "Expert en Systemes d'Information",
                    domain: "Genie Informatique",
                    domainIcon: "💻",
                    level: "Master 2 (BAC+5)",
                    exp: 7,
                    location: "Cotonou",
                    score: 4.7,
                    color: "linear-gradient(135deg, #10b981, #059669)",
                    availability: "Disponible",
                    availClass: "avail-yes",
                    skills: [
                        "DevOps",
                        "Cloud AWS",
                        "Architecture SI",
                        "Laravel",
                        "Vue.js",
                    ],
                    bio: "Expert en systemes d'information et developpement logiciel. Specialiste en architecture cloud et deploiement d'applications web et mobiles a fort trafic.",
                    diplomas: [
                        {
                            title: "Master Informatique",
                            school: "IMSP Porto-Novo",
                            year: 2017,
                        },
                    ],
                    experiences: [
                        {
                            role: "CTO",
                            company: "StartupBJ",
                            period: "2020 - Present",
                        },
                        {
                            role: "Developpeur Senior",
                            company: "Freelance",
                            period: "2017 - 2020",
                        },
                    ],
                },
                {
                    id: 4,
                    name: "Mme Fatoumata Sow",
                    title: "Architecte DPLG",
                    domain: "Architecture",
                    domainIcon: "📐",
                    level: "Master 2 (BAC+5)",
                    exp: 10,
                    location: "Porto-Novo",
                    score: 4.9,
                    color: "linear-gradient(135deg, #8b5cf6, #6d28d9)",
                    availability: "Mission specifique",
                    availClass: "avail-mission",
                    skills: [
                        "SketchUp",
                        "Revit",
                        "Architecture bioclimatique",
                        "Permis de construire",
                        "Suivi chantier",
                    ],
                    bio: "Architecte diplomee avec 10 ans de pratique en conception architecturale et suivi de chantiers residentiels et commerciaux. Specialiste en architecture bioclimatique adaptee au climat tropical.",
                    diplomas: [
                        {
                            title: "DPLG Architecture",
                            school: "EPAC UAC",
                            year: 2014,
                        },
                    ],
                    experiences: [
                        {
                            role: "Architecte chef de projets",
                            company: "Cabinet ARCH-BJ",
                            period: "2014 - Present",
                        },
                    ],
                },
                {
                    id: 5,
                    name: "Ing. Serge Houngbo",
                    title: "Expert Energie Solaire",
                    domain: "Energie Solaire",
                    domainIcon: "☀️",
                    level: "Ingenieur (BAC+5)",
                    exp: 6,
                    location: "Abomey-Calavi",
                    score: 4.8,
                    color: "linear-gradient(135deg, #f59e0b, #d97706)",
                    availability: "Disponible",
                    availClass: "avail-yes",
                    skills: [
                        "Dimensionnement solaire",
                        "Onduleurs",
                        "PVSyst",
                        "Raccordement reseau",
                        "Maintenance",
                    ],
                    bio: "Ingenieur specialiste des systemes solaires photovoltaiques avec 6 ans d'experience. A supervise plus de 50 installations solaires au Benin et au Togo.",
                    diplomas: [
                        {
                            title: "Ingenieur Energetique",
                            school: "EPAC UAC",
                            year: 2018,
                        },
                    ],
                    experiences: [
                        {
                            role: "Responsable Projets Solaires",
                            company: "GreenEnergy BJ",
                            period: "2018 - Present",
                        },
                    ],
                },
                {
                    id: 6,
                    name: "Dr. Aissatou Barry",
                    title: "Expert en Management de Projet",
                    domain: "Management de projet",
                    domainIcon: "📋",
                    level: "Doctorat",
                    exp: 15,
                    location: "Cotonou",
                    score: 5.0,
                    color: "linear-gradient(135deg, #ef4444, #dc2626)",
                    availability: "Temps partiel",
                    availClass: "avail-partial",
                    skills: [
                        "PMP",
                        "PRINCE2",
                        "Agile/Scrum",
                        "MS Project",
                        "Gestion des risques",
                    ],
                    bio: "Docteur en management avec certification PMP et PRINCE2. 15 ans d'experience dans la gestion de projets d'envergure en Afrique de l'Ouest pour des institutions internationales.",
                    diplomas: [
                        {
                            title: "Doctorat Management",
                            school: "Universite Paris Dauphine",
                            year: 2009,
                        },
                        {
                            title: "MBA",
                            school: "EDHEC Business School",
                            year: 2006,
                        },
                    ],
                    experiences: [
                        {
                            role: "Directrice de Projets",
                            company: "PNUD Benin",
                            period: "2015 - Present",
                        },
                        {
                            role: "Chef de Projet Senior",
                            company: "Banque Mondiale",
                            period: "2009 - 2015",
                        },
                    ],
                },
            ],

            benefits: [
                {
                    icon: "🎯",
                    title: "Visibilite maximale",
                    desc: "Votre profil visible par toutes les entreprises inscrites sur Resotravo.",
                },
                {
                    icon: "🏅",
                    title: "Badge valide Resotravo",
                    desc: "Un gage de confiance qui vous distingue des autres profils en ligne.",
                },
                {
                    icon: "📋",
                    title: "Acces aux appels d'offres",
                    desc: "Candidatez directement aux missions publiees par les entreprises.",
                },
                {
                    icon: "💬",
                    title: "Messagerie integree",
                    desc: "Echangez directement avec les entreprises sans divulguer vos coordonnees.",
                },
                {
                    icon: "💰",
                    title: "Fixer vos tarifs",
                    desc: "Proposez vos honoraires librement lors de chaque candidature.",
                },
            ],

            processSteps: [
                {
                    title: "Remplir le formulaire",
                    desc: "Informations personnelles, diplomes, experiences, competences.",
                },
                {
                    title: "Examen du dossier",
                    desc: "L'equipe Resotravo verifie chaque profil sous 48h.",
                },
                {
                    title: "Validation et publication",
                    desc: "Votre profil est publie avec le badge Valide Resotravo.",
                },
                {
                    title: "Recevez des missions",
                    desc: "Les entreprises vous contactent via la messagerie integree.",
                },
            ],

            reviews: [
                {
                    text: "Le talent que nous avons contacte via Resotravo a livre un travail exceptionnel. Tres professionnel et parfaitement qualifie.",
                    company: "SOTRAC Immobilier",
                    role: "Directeur des Travaux",
                },
                {
                    text: "Grace a Resotravo, nous avons trouve en 2 jours un ingenieur solaire pour notre projet. Profil verifie, pas de mauvaises surprises.",
                    company: "GreenPower BJ",
                    role: "Responsable Projets",
                },
                {
                    text: "L'Espace Talents est exactement ce dont nous avions besoin pour trouver des experts locaux de haut niveau rapidement.",
                    company: "Groupe ATLAS BJ",
                    role: "DRH",
                },
            ],
        };
    },

    computed: {
        filteredTalents() {
            let list = this.talents;
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(
                    (t) =>
                        t.name.toLowerCase().includes(q) ||
                        t.title.toLowerCase().includes(q) ||
                        t.domain.toLowerCase().includes(q) ||
                        t.skills.some((s) => s.toLowerCase().includes(q)),
                );
            }
            if (this.filterDomain)
                list = list.filter((t) => t.domain === this.filterDomain);
            if (this.filterLevel === "bac3")
                list = list.filter(
                    (t) =>
                        t.level.includes("BAC+3") ||
                        t.level.includes("Licence"),
                );
            if (this.filterLevel === "bac4")
                list = list.filter(
                    (t) =>
                        t.level.includes("BAC+4") ||
                        t.level.includes("Master 1"),
                );
            if (this.filterLevel === "bac5plus")
                list = list.filter((t) => t.exp >= 5);
            if (this.filterAvailability === "immediate")
                list = list.filter((t) => t.availClass === "avail-yes");
            if (this.filterAvailability === "parttime")
                list = list.filter((t) => t.availClass === "avail-partial");
            if (this.filterAvailability === "mission")
                list = list.filter((t) => t.availClass === "avail-mission");
            if (this.sortBy === "score")
                list = [...list].sort((a, b) => b.score - a.score);
            if (this.sortBy === "exp")
                list = [...list].sort((a, b) => b.exp - a.exp);
            if (this.sortBy === "name")
                list = [...list].sort((a, b) => a.name.localeCompare(b.name));
            return list;
        },

        step1Valid() {
            const f = this.talentForm;
            return (
                f.nom &&
                f.prenom &&
                f.email &&
                f.phone &&
                f.title &&
                f.domain &&
                f.level &&
                f.exp &&
                f.bio
            );
        },
    },

    mounted() {
        this.$nextTick(() => {
            this.reObserveReveal();
        });
    },

    methods: {
        openProfile(talent) {
            this.selectedTalent = talent;
            this.showProfile = true;
        },

        contactTalent(talent) {
            this.contactTarget = talent;
            this.showContact = true;
            this.showProfile = false;
            this.contactSent = false;
            this.contactForm = {
                subject: "",
                message: "",
                budget: "",
                startDate: "",
            };
        },

        submitContact() {
            if (!this.contactForm.subject || !this.contactForm.message) return;
            this.contactSent = true;
            setTimeout(() => {
                this.showContact = false;
            }, 2000);
        },

        submitTalentForm() {
            this.talentFormSubmitted = true;
        },

        addDiploma() {
            if (this.talentForm.diplomas.length < 3)
                this.talentForm.diplomas.push({
                    title: "",
                    school: "",
                    year: "",
                });
        },
        removeDiploma(i) {
            this.talentForm.diplomas.splice(i, 1);
        },
        addCert() {
            if (this.talentForm.certifications.length < 5)
                this.talentForm.certifications.push("");
        },
        removeCert(i) {
            this.talentForm.certifications.splice(i, 1);
        },
        addExp() {
            this.talentForm.experiences.push({
                role: "",
                company: "",
                period: "",
            });
        },
        removeExp(i) {
            if (this.talentForm.experiences.length > 1)
                this.talentForm.experiences.splice(i, 1);
        },

        reObserveReveal() {
            if (!("IntersectionObserver" in window)) return;
            setTimeout(() => {
                const io = new IntersectionObserver(
                    (entries) =>
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add("revealed");
                                io.unobserve(e.target);
                            }
                        }),
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" },
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)",
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },
    },
};
</script>

<style scoped>
/* HERO */
.tl-hero {
    background: var(--dk2);
    color: #fff;
    padding: 52px 4% 44px;
    position: relative;
    overflow: hidden;
}
.tl-hero-glow {
    position: absolute;
    top: -200px;
    right: -150px;
    width: 550px;
    height: 550px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(249, 115, 22, 0.13) 0%,
        transparent 68%
    );
    pointer-events: none;
}
.tl-hero-glow2 {
    position: absolute;
    bottom: -100px;
    left: -80px;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(252, 211, 77, 0.05) 0%,
        transparent 70%
    );
    pointer-events: none;
}
.tl-hero-dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(
        rgba(255, 255, 255, 0.035) 1px,
        transparent 1px
    );
    background-size: 24px 24px;
    pointer-events: none;
}
.tl-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 760px;
}
.tl-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(249, 115, 22, 0.15);
    border: 1px solid rgba(249, 115, 22, 0.3);
    color: var(--am);
    padding: 6px 15px;
    border-radius: 99px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
}
.bdot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    animation: blink 1.4s infinite;
    flex-shrink: 0;
}
@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.2;
    }
}
.tl-hero h1 {
    font-size: clamp(30px, 6vw, 54px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}
.tl-hero-desc {
    font-size: 16px;
    color: #b8ada7;
    line-height: 1.75;
    margin-bottom: 26px;
    font-weight: 400;
}
.tl-hero-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 34px;
}
.tl-hero-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 480px) {
    .tl-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 26px;
    }
}

/* TABS */
.tl-tabs-bar {
    background: var(--wh);
    border-bottom: 1px solid var(--grl);
    position: sticky;
    top: 64px;
    z-index: 100;
    padding: 0 4%;
}
.tl-tabs {
    display: flex;
    gap: 0;
    overflow-x: auto;
}
.tl-tab {
    padding: 14px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s;
}
.tl-tab:hover {
    color: var(--dk);
}
.tl-tab.active {
    color: var(--or);
    border-bottom-color: var(--or);
}

/* SECTION */
.tl-section {
    padding: 36px 4% 52px;
    max-width: 1200px;
    margin: 0 auto;
}

/* FILTRES */
.tl-filters {
    background: var(--wh);
    border-radius: 14px;
    padding: 20px;
    border: 1.5px solid var(--grl);
    margin-bottom: 20px;
}
.tl-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}
.tl-select {
    padding: 10px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    outline: none;
    background: var(--cr);
    color: var(--dk);
    cursor: pointer;
    transition: border-color 0.2s;
}
.tl-select:focus {
    border-color: var(--or);
}
.tl-filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}
.tl-ftag {
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    background: var(--cr2);
    border: 1px solid var(--grl);
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
}
.tl-ftag:hover,
.tl-ftag.active {
    background: var(--or);
    color: #fff;
    border-color: var(--or);
}
.tl-results-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.tl-count {
    font-size: 14px;
    font-weight: 600;
    color: var(--gr);
}
.tl-sort {
    font-size: 13px;
}

/* GRILLE TALENTS */
.tl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}
.tl-card {
    background: var(--wh);
    border-radius: 16px;
    padding: 22px 20px;
    border: 1.5px solid var(--grl);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.22, 0.68, 0, 1.2);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.tl-card:hover {
    border-color: var(--or);
    box-shadow: 0 8px 32px rgba(249, 115, 22, 0.12);
    transform: translateY(-3px);
}
.tl-card-validated {
    position: absolute;
    top: 14px;
    right: 14px;
    background: var(--or3);
    color: var(--or);
    border-radius: 99px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 700;
}
.tl-card-top {
    display: flex;
    align-items: center;
    gap: 14px;
}
.tl-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    font-size: 22px;
    flex-shrink: 0;
}
.tl-card-name {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.tl-card-title {
    font-size: 13px;
    color: var(--gr);
    margin-top: 2px;
}
.tl-domain-badge {
    display: inline-block;
    background: var(--cr);
    border: 1px solid var(--grl);
    border-radius: 99px;
    padding: 3px 10px;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--dk);
    margin-top: 4px;
}
.tl-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: var(--gr);
}
.tl-card-meta span {
    background: var(--cr2);
    border-radius: 6px;
    padding: 3px 8px;
}

/* Competences */
.tl-skills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.tl-skill {
    background: var(--or3);
    color: var(--or);
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 11.5px;
    font-weight: 600;
}
.tl-skill-more {
    background: var(--cr);
    color: var(--gr);
}

/* Footer carte */
.tl-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tl-score {
    display: flex;
    align-items: center;
    gap: 5px;
}
.tl-stars {
    color: var(--am);
    font-size: 12px;
}
.tl-score-val {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.tl-avail {
    display: flex;
    align-items: center;
    gap: 4px;
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
}
.avail-yes {
    background: #dcfce7;
    color: #16a34a;
}
.avail-partial {
    background: #fef9c3;
    color: #a16207;
}
.avail-mission {
    background: #eff6ff;
    color: #2563eb;
}

.tl-contact-btn {
    width: 100%;
    padding: 10px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.tl-contact-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.3);
}

/* EMPTY */
.tl-empty {
    text-align: center;
    padding: 60px 20px;
}
.tl-empty-icon {
    font-size: 48px;
    margin-bottom: 14px;
}
.tl-empty-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 8px;
}
.tl-empty-desc {
    font-size: 14px;
    color: var(--gr);
}

/* MODAL */
.tl-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.tl-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 640px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: fadeUp 0.3s ease;
}
.tl-modal-sm {
    max-width: 500px;
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.tl-modal-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 22px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
}
.tl-modal-avatar {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    font-size: 24px;
    flex-shrink: 0;
}
.tl-modal-meta {
    flex: 1;
}
.tl-modal-meta h2 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.tl-modal-badges {
    display: flex;
    gap: 8px;
    margin-top: 6px;
    flex-wrap: wrap;
}
.tl-validated-badge {
    background: #dcfce7;
    color: #16a34a;
    border-radius: 99px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 700;
}
.tl-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: var(--gr);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s;
    flex-shrink: 0;
}
.tl-modal-close:hover {
    background: var(--cr);
}
.tl-modal-body {
    padding: 20px 22px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.tl-modal-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
@media (max-width: 500px) {
    .tl-modal-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
.tl-mstat {
    background: var(--cr);
    border-radius: 10px;
    padding: 12px;
    text-align: center;
}
.tl-mstat-val {
    display: block;
    font-size: 15px;
    font-weight: 800;
    color: var(--or);
}
.tl-mstat-lbl {
    display: block;
    font-size: 11px;
    color: var(--gr);
    margin-top: 2px;
}
.tl-modal-section h4 {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 10px;
}
.tl-modal-section p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.7;
}
.tl-diplomas {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.tl-diploma {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.tl-diploma-icon {
    font-size: 20px;
}
.tl-diploma-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.tl-diploma-school {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.tl-experiences {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.tl-exp-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.tl-exp-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.tl-exp-role {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.tl-exp-company {
    font-size: 12.5px;
    color: var(--gr);
}
.tl-exp-period {
    font-size: 12px;
    color: var(--grm);
}
.tl-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 16px 22px;
    border-top: 1px solid var(--grl);
    flex-shrink: 0;
}

/* REGISTER FORM */
.tl-register-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}
@media (min-width: 900px) {
    .tl-register-layout {
        grid-template-columns: 2fr 1fr;
    }
}
.tl-register-form {
    background: var(--wh);
    border-radius: 16px;
    padding: 28px 24px;
    border: 1.5px solid var(--grl);
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.tl-form-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 6px;
}
.tl-form-header p {
    font-size: 13.5px;
    color: var(--gr);
}
.tl-form-step {
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    overflow: hidden;
}
.tl-form-step.active {
    border-color: var(--or);
}
.tl-step-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: var(--cr);
    cursor: default;
}
.tl-step-num {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: var(--or);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tl-step-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk);
}
.tl-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding: 18px;
}
@media (max-width: 600px) {
    .tl-form-grid {
        grid-template-columns: 1fr;
    }
}
.tl-step-info {
    font-size: 13px;
    color: var(--gr);
    padding: 10px 18px 0;
}
.tl-diploma-list,
.tl-cert-list,
.tl-exp-form {
    padding: 0 18px;
}
.tl-diploma-item {
    background: var(--cr);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 10px;
}
.tl-add-btn {
    background: none;
    border: 1.5px dashed var(--grl);
    border-radius: 8px;
    padding: 8px 16px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    width: calc(100% - 36px);
    margin: 0 18px;
}
.tl-add-btn:hover {
    border-color: var(--or);
    color: var(--or);
}
.tl-remove-btn {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
    padding: 6px 12px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    color: #dc2626;
    cursor: pointer;
    transition: all 0.18s;
}
.tl-remove-btn:hover {
    background: #dc2626;
    color: #fff;
}

/* SIDE AVANTAGES */
.tl-benefits-card,
.tl-process-card {
    background: var(--wh);
    border-radius: 14px;
    padding: 22px;
    border: 1.5px solid var(--grl);
    margin-bottom: 16px;
}
.tl-benefits-card h4,
.tl-process-card h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 16px;
}
.tl-benefit {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 14px;
}
.tl-benefit-icon {
    font-size: 20px;
    flex-shrink: 0;
}
.tl-benefit-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.tl-benefit-desc {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}
.tl-proc-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}
.tl-proc-num {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: var(--or);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.tl-proc-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 2px;
}
.tl-proc-desc {
    font-size: 12px;
    color: var(--gr);
}

/* REVIEWS */
.tl-reviews-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
@media (min-width: 600px) {
    .tl-reviews-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 900px) {
    .tl-reviews-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.tl-review {
    background: var(--wh);
    border-radius: 14px;
    padding: 22px;
    border: 1px solid var(--grl);
    transition: all 0.22s;
}
.tl-review:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
.tl-review-stars {
    font-size: 14px;
    color: var(--am);
    margin-bottom: 10px;
}
.tl-review-text {
    font-size: 14px;
    color: var(--dk);
    line-height: 1.75;
    margin-bottom: 16px;
    font-style: italic;
}
.tl-review-author {
    display: flex;
    align-items: center;
    gap: 10px;
}
.tl-review-av {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--am));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    flex-shrink: 0;
}
.tl-review-company {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.tl-review-role {
    font-size: 12px;
    color: var(--gr);
}

/* SHARED with MarketComponent */
.mk-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mk-field-full {
    grid-column: 1/-1;
}
.mk-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.mk-req {
    color: var(--or);
}
.mk-input {
    padding: 11px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
    background: var(--cr);
    color: var(--dk);
}
.mk-input:focus {
    border-color: var(--or);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
}
.mk-textarea {
    resize: vertical;
    min-height: 100px;
}
.mk-form-notice {
    background: var(--or3);
    border: 1px solid rgba(249, 115, 22, 0.3);
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 13px;
    color: var(--dk);
}
</style>
