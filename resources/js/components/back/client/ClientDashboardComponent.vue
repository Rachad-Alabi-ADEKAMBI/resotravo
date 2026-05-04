<template>
    <div class="cd-wrap">
        <!-- ----------------------------------
             TOPBAR
        ---------------------------------- -->
        <div class="cd-topbar">
            <div class="cd-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="cd-page-title">Mon espace client</div>
                    <div class="cd-page-sub">
                        <strong>{{ client.display_name }}</strong>
                    </div>
                </div>
            </div>
            <div class="cd-topbar-right">
                <!-- Cloche notifications -->
                <div class="cd-notif-wrap" ref="notifWrap">
                    <button class="cd-notif-btn" @click="toggleNotif">
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
                        <span class="cd-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount
                        }}</span>
                    </button>
                    <div class="cd-notif-dropdown" v-if="notifOpen">
                        <div class="cd-notif-header">
                            <span>Notifications</span>
                            <button
                                class="cd-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="cd-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="cd-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="cd-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="cd-notif-dot" v-if="!n.read"></div>
                            <div class="cd-notif-content">
                                <div class="cd-notif-title">{{ n.title }}</div>
                                <div class="cd-notif-body">{{ n.body }}</div>
                                <div class="cd-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cd-avatar">{{ client.initials }}</div>
                <button class="cd-btn cd-btn-orange" @click="openNewMission">
                    + Nouvelle commande
                </button>
            </div>
        </div>

        <!-- ----------------------------------
             CONTENU
        ---------------------------------- -->
        <div class="cd-content">
            <!-- -- Badge Identité vérifiée -- -->
            <div class="cd-banner-verified" v-if="isIdentityVerified">
                <span class="cd-verified-icon">&#10003;</span>
                <div>
                    <div class="cd-verified-title">Identité vérifiée</div>
                    <div class="cd-verified-sub">
                        Votre compte bénéficie du badge de confiance Mesotravo.
                    </div>
                </div>
            </div>

            <!-- -- Bannière obtenir le badge (si pas encore vérifié) -- -->
            <div class="cd-banner-pending" v-else-if="!isIdentityVerified">
                <div class="cd-banner-pending-left">
                    <div class="cd-banner-pending-icon">
                        {{
                            isApproved
                                ? clientProfile.completed_missions >= 1
                                    ? "OK"
                                    : "DOC"
                                : docProgress.percentage === 0
                                ? "DOC"
                                : docProgress.percentage < 100
                                ? "..."
                                : "OK"
                        }}
                    </div>
                    <div>
                        <div class="cd-banner-pending-title">
                            {{
                                isApproved
                                    ? clientProfile.completed_missions >= 1
                                        ? "Identité vérifiée"
                                        : "Plus qu'une mission pour obtenir le badge Identité vérifiée"
                                    : docProgress.percentage < 100
                                    ? "Obtenez le badge Identité vérifiée"
                                    : "Dossier en cours de vérification"
                            }}
                        </div>
                        <div class="cd-banner-pending-sub">
                            {{
                                isApproved
                                    ? "Complétez 1 mission pour obtenir le badge !"
                                    : docProgress.percentage < 100
                                    ? "Déposez votre pièce d'identité et une photo de profil, puis complétez 1 mission pour obtenir le badge."
                                    : "Vos documents ont été reçus. La validation prend généralement moins de 24h."
                            }}
                        </div>
                        <!-- Progression documents si pas encore soumis -->
                        <div class="cd-banner-progress-wrap" v-if="!isApproved">
                            <div class="cd-banner-progress-track">
                                <div
                                    class="cd-banner-progress-fill"
                                    :style="{
                                        width: docProgress.percentage + '%',
                                    }"
                                ></div>
                            </div>
                            <span class="cd-banner-progress-label">
                                {{ docProgress.approved }}/{{
                                    docProgress.total
                                }}
                                documents validés
                            </span>
                        </div>
                        <!-- Progression missions si documents OK -->
                        <div class="cd-banner-progress-wrap" v-else>
                            <div class="cd-banner-progress-track">
                                <div
                                    class="cd-banner-progress-fill cd-banner-progress-missions"
                                    :style="{
                                        width: (clientProfile.completed_missions ?? 0) >= 1 ? '100%' : '0%',
                                    }"
                                ></div>
                            </div>
                            <span class="cd-banner-progress-label">
                                {{ clientProfile.completed_missions ?? 0 }}/1
                                mission terminée
                            </span>
                        </div>
                    </div>
                </div>
                <a
                    v-if="!isApproved"
                    :href="routes.dossier_page"
                    class="cd-btn cd-btn-orange cd-banner-btn"
                >
                    {{
                        docProgress.percentage < 100
                            ? "Déposer mes documents"
                            : "Voir mon dossier"
                    }}
                </a>
            </div>

            <!-- -- Bannière compte entreprise -- -->
            <div
                class="cd-banner-company"
                v-if="client.account_type === 'company'"
            >
                <strong>{{ client.company_name }}</strong> — Compte
                entreprise
                <span class="cd-banner-badge"
                    >Appels d'offres & Espace Talents disponibles</span
                >
            </div>

            <!-- -- KPIs -- -->
            <div class="cd-kpis">
                <div
                    class="cd-kpi"
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    :class="kpi.color"
                >
                    <div class="cd-kpi-val">
                        <span
                            v-if="missionsLoading"
                            class="cd-skeleton-val"
                        ></span>
                        <template v-else>
                            <span class="cd-kpi-icon" v-html="kpi.icon"></span>
                            <span>{{ kpi.value }}</span>
                        </template>
                    </div>
                    <div class="cd-kpi-label">{{ kpi.label }}</div>
                </div>
            </div>

            <!-- -- LIGNE MILIEU -- -->
            <div class="cd-mid-grid">
                <!-- Missions en cours -->
                <div class="cd-card cd-card-main">
                    <div class="cd-card-header">
                        <h3>Missions en cours</h3>
                        <button
                            class="cd-btn cd-btn-ghost cd-btn-sm"
                            @click="tab = 'all'"
                        >
                            Voir tout
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="cd-tabs">
                        <button
                            class="cd-tab"
                            v-for="t in tabs"
                            :key="t.key"
                            :class="{ active: tab === t.key }"
                            @click="tab = t.key"
                        >
                            {{ t.label }}
                            <span class="cd-tab-count">{{
                                countByTab(t.key)
                            }}</span>
                        </button>
                    </div>

                    <!-- Loader -->
                    <div class="cd-loading" v-if="missionsLoading">
                        <div
                            class="cd-skeleton-row"
                            v-for="n in 3"
                            :key="n"
                        ></div>
                    </div>

                    <!-- Erreur -->
                    <div class="cd-alert-error" v-else-if="missionsError">
                        {{ missionsError }}
                        <button
                            class="cd-btn cd-btn-ghost cd-btn-sm"
                            @click="fetchMissions"
                        >
                            Réessayer
                        </button>
                    </div>

                    <!-- Vide -->
                    <div
                        class="cd-empty"
                        v-else-if="filteredMissions.length === 0"
                    >
                        <div class="cd-empty-icon">0</div>
                        <div class="cd-empty-title">
                            Aucune mission{{
                                tab !== "all" ? " " + tabLabel : ""
                            }}
                        </div>
                        <button
                            class="cd-btn cd-btn-orange"
                            @click="openNewMission"
                            style="margin-top: 12px"
                        >
                            Créer une mission
                        </button>
                    </div>

                    <!-- Liste -->
                    <div class="cd-mission-list" v-else>
                        <div
                            class="cd-mission-item"
                            v-for="m in filteredMissions"
                            :key="m.id"
                            :class="{ 'has-unread': unreadByMission[m.id] }"
                            @click="openMission(m)"
                        >
                            <!-- Bande unread -->
                            <div
                                class="cd-unread-stripe"
                                v-if="unreadByMission[m.id]"
                            ></div>

                            <div class="cd-mission-left">
                                <div class="cd-mission-icon">
                                    {{ serviceIcon(m.service) }}
                                </div>
                                <div class="cd-mission-info">
                                    <div class="cd-mission-title">
                                        {{ m.service }}
                                    </div>
                                    <div class="cd-mission-meta">
                                        {{
                                            m.contractor
                                                ? m.contractor.name
                                                : "Attribution en cours..."
                                        }}
                                        · {{ formatDate(m.created_at) }}
                                    </div>
                                    <!-- Miniatures images -->
                                    <div class="cd-mission-imgs" v-if="m.images && m.images.length">
                                        <img v-for="(url, i) in m.images.slice(0, 4)" :key="i" :src="url" class="cd-mission-img-thumb" @click.stop="dashLightbox = url" />
                                        <span class="cd-mission-imgs-more" v-if="m.images.length > 4">+{{ m.images.length - 4 }}</span>
                                    </div>
                                    <!-- Badge non lu inline sous le titre -->
                                    <div
                                        class="cd-msg-unread"
                                        v-if="unreadByMission[m.id]"
                                    >
                                        {{ unreadByMission[m.id] }} message{{
                                            unreadByMission[m.id] > 1 ? "s" : ""
                                        }}
                                        non lu{{
                                            unreadByMission[m.id] > 1 ? "s" : ""
                                        }}
                                    </div>
                                </div>
                            </div>
                            <div class="cd-mission-right">
                                <span
                                    class="cd-badge"
                                    :class="badgeClass(m.status)"
                                >
                                    {{ labelOf(m) }}
                                </span>
                                <div
                                    class="cd-mission-price"
                                    v-if="m.total_amount"
                                >
                                    {{ formatPrice(m.total_amount) }}
                                </div>
                                <!-- Bouton Lire message — stoppe la propagation pour ouvrir direct le chat -->
                                <button
                                    class="cd-read-msg-btn"
                                    v-if="unreadByMission[m.id]"
                                    @click.stop="chatMissionId = m.id"
                                    :title="
                                        'Ouvrir la conversation — ' +
                                        unreadByMission[m.id] +
                                        ' non lu(s)'
                                    "
                                >
                                    <span class="cd-read-msg-dot">{{
                                        unreadByMission[m.id]
                                    }}</span>
                                    Lire
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel droit -->
                <div class="cd-side-col">
                    <!-- Profil rapide -->
                    <div class="cd-card">
                        <div class="cd-card-header">
                            <h3>Mon profil</h3>
                            <a
                                class="cd-btn cd-btn-ghost cd-btn-sm"
                                :href="routes.parameters_page"
                            >
                                Modifier
                            </a>
                        </div>
                        <div class="cd-profile-info">
                            <div class="cd-profile-av" :class="{ 'has-photo': client.profile_picture }">
                                <img
                                    v-if="client.profile_picture"
                                    :src="client.profile_picture"
                                    :alt="client.display_name"
                                    class="cd-profile-img"
                                />
                                <span v-else>
                                {{ client.initials }}
                                </span>
                            </div>
                            <div>
                                <div class="cd-profile-name">
                                    {{ client.display_name }}
                                </div>
                                <div class="cd-profile-meta">
                                    {{ client.email }}
                                </div>
                                <div class="cd-profile-meta">
                                    {{ client.phone }}
                                </div>
                                <div class="cd-profile-meta">
                                    {{ client.city }}
                                </div>
                            </div>
                        </div>
                        <div class="cd-account-type">
                            <span
                                class="cd-type-badge"
                                :class="client.account_type"
                            >
                                {{
                                    client.account_type === "company"
                                        ? "Entreprise"
                                        : "Particulier"
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- -- Section Documents -- -->
            <div class="cd-card cd-docs-card" ref="docsSection">
                <div class="cd-card-header">
                    <h3>Mes documents</h3>
                    <div class="cd-doc-header-right">
                        <span
                            class="cd-doc-summary"
                            :class="{
                                complete: docProgress.percentage === 100,
                            }"
                        >
                            {{ docProgress.approved }}/{{ docProgress.total }}
                            validés
                        </span>
                        <span
                            class="cd-certified-pill"
                            v-if="isIdentityVerified"
                        >
                            Identité vérifiée
                        </span>
                    </div>
                </div>

                <!-- Tous validés -->
                <div class="cd-docs-certified" v-if="isIdentityVerified">
                    <div class="cd-docs-certified-icon">&#10003;</div>
                    <div>
                        <div class="cd-docs-certified-title">
                            Vos documents ont été validés par Mesotravo !
                        </div>
                        <div class="cd-docs-certified-sub">
                            Votre identité est vérifiée. Continuez à publier des
                            missions pour obtenir le badge.
                        </div>
                    </div>
                </div>

                <!-- Info badge si pas encore validé -->
                <div class="cd-docs-badge-info" v-if="!isIdentityVerified">
                    Déposez votre pièce d'identité et une photo de profil
                    pour obtenir le badge <strong>Identité vérifiée</strong>.
                </div>

                <!-- Barre de progression -->
                <div class="cd-docs-progress-bar" v-if="!isIdentityVerified">
                    <div class="cd-docs-pb-track">
                        <div
                            class="cd-docs-pb-fill"
                            :style="{ width: docProgress.percentage + '%' }"
                        ></div>
                    </div>
                    <div class="cd-docs-pb-label">
                        {{ docProgress.percentage }}% validé par l'admin
                    </div>
                </div>

                <!-- Liste des documents -->
                <div class="cd-docs-list">
                    <div
                        class="cd-doc-item"
                        v-for="doc in localDocuments"
                        :key="doc.type"
                        :class="doc.status"
                    >
                        <div class="cd-doc-icon">{{ doc.icon }}</div>
                        <div class="cd-doc-info">
                            <div class="cd-doc-name">{{ doc.label }}</div>
                            <div
                                class="cd-doc-status-label"
                                :class="doc.status"
                            >
                                {{ docStatusLabel(doc.status) }}
                            </div>
                            <div
                                class="cd-doc-reject-reason"
                                v-if="
                                    doc.status === 'rejected' &&
                                    doc.reject_reason
                                "
                            >
                                Motif : {{ doc.reject_reason }}
                            </div>
                            <div class="cd-doc-filename" v-if="doc.filename">
                                {{ doc.filename }}
                            </div>
                        </div>
                        <div class="cd-doc-actions">
                            <!-- Déposer ou remplacer si manquant/refusé -->
                            <label
                                class="cd-btn cd-btn-orange cd-btn-sm cd-upload-label"
                                v-if="
                                    doc.status === 'missing' ||
                                    doc.status === 'rejected'
                                "
                                :class="{ loading: doc.uploading }"
                            >
                                <div
                                    class="cd-mini-spinner"
                                    v-if="doc.uploading"
                                ></div>
                                <span v-else>{{
                                    doc.status === "missing"
                                        ? "+ Déposer"
                                        : "Remplacer"
                                }}</span>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    style="display: none"
                                    @change="uploadDocument(doc, $event)"
                                    :disabled="doc.uploading"
                                />
                            </label>
                            <!-- Remplacer si en attente ou approuvé -->
                            <label
                                class="cd-btn cd-btn-ghost cd-btn-sm cd-upload-label"
                                v-if="
                                    doc.status === 'pending' ||
                                    doc.status === 'approved'
                                "
                                :class="{ loading: doc.uploading }"
                            >
                                <div
                                    class="cd-mini-spinner cd-mini-spinner-dark"
                                    v-if="doc.uploading"
                                ></div>
                                <span v-else>Remplacer</span>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    style="display: none"
                                    @change="uploadDocument(doc, $event)"
                                    :disabled="doc.uploading"
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="cd-docs-note">
                    Formats acceptés : PDF, JPG, PNG — 5 Mo max par document.
                    Délai de vérification : <strong>24-48h</strong>.
                </div>
            </div>
        </div>

        <!-- ----------------------------------
             MODAL DÉTAIL MISSION
        ---------------------------------- -->
        <div
            class="cd-modal-overlay"
            v-if="activeMission"
            @click.self="activeMission = null"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <div>
                        <h3>{{ activeMission.service }}</h3>
                        <div class="cd-modal-sub">
                            Mission #{{ activeMission.id }} ·
                            {{ formatDate(activeMission.created_at) }}
                        </div>
                    </div>
                    <button
                        class="cd-modal-close"
                        @click="activeMission = null"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cd-modal-body">
                    <div class="cd-detail-row">
                        <span>Statut</span>
                        <span
                            class="cd-badge"
                            :class="badgeClass(activeMission.status)"
                        >
                            {{ labelOf(activeMission) }}
                        </span>
                    </div>
                    <div class="cd-detail-row">
                        <span>Prestataire</span>
                        <strong>{{
                            activeMission.contractor
                                ? activeMission.contractor.name
                                : "Attribution en cours..."
                        }}</strong>
                    </div>
                    <div class="cd-detail-row" v-if="activeMission.contractor">
                        <span>Contact</span>
                        <strong class="cd-masked-phone"
                            >Numéro masqué — utilisez la messagerie</strong
                        >
                    </div>
                    <div class="cd-detail-row">
                        <span>Adresse</span>
                        <strong>{{ activeMission.address }}</strong>
                    </div>
                    <div class="cd-detail-row">
                        <span>Mode adresse</span>
                        <strong>{{ activeMission.latitude && activeMission.longitude ? 'Géolocalisation' : 'Saisie manuelle' }}</strong>
                    </div>
                    <div class="cd-detail-row">
                        <span>Description</span>
                        <strong>{{ activeMission.description }}</strong>
                    </div>
                    <!-- Photos de la mission -->
                    <div
                        class="cd-detail-row cd-detail-photos"
                        v-if="
                            activeMission.images && activeMission.images.length
                        "
                    >
                        <span>Photos</span>
                        <div class="cd-dash-images">
                            <img
                                v-for="(url, i) in activeMission.images"
                                :key="i"
                                :src="url"
                                @click="dashLightbox = url"
                                class="cd-dash-img"
                            />
                        </div>
                    </div>
                    <div
                        class="cd-detail-row"
                        v-if="activeMission.total_amount"
                    >
                        <span>Montant</span>
                        <strong>{{
                            formatPrice(activeMission.total_amount)
                        }}</strong>
                    </div>
                    <div class="cd-detail-row" v-if="activeMission.accepted_at">
                        <span>Acceptée le</span>
                        <strong>{{
                            formatDate(activeMission.accepted_at)
                        }}</strong>
                    </div>

                    <!-- Action : confirmer fin des travaux (étape 11) -->
                    <div
                        class="cd-action-block"
                        v-if="activeMission.status === 'awaiting_confirm'"
                    >
                        <p>
                            Le prestataire a terminé l'intervention.
                            Confirmez-vous la fin des travaux ?
                        </p>
                        <div class="cd-action-row">
                            <button
                                class="cd-btn cd-btn-ghost"
                                @click="signalProblem"
                            >
                                Signaler un problème
                            </button>
                            <button
                                class="cd-btn cd-btn-orange"
                                @click="confirmCompletion(activeMission)"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="cd-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>Confirmer la fin ?</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action : approuver le devis (étape 8?9) -->
                    <div
                        class="cd-action-block cd-action-quote"
                        v-if="
                            activeMission.status === 'quote_submitted' &&
                            activeMission.quote
                        "
                    >
                        <p>
                            Le prestataire a soumis un devis de
                            <strong>{{
                                formatPrice(activeMission.quote.amount_incl_tax)
                            }}</strong
                            >.
                        </p>
                        <div class="cd-action-row">
                            <button
                                class="cd-btn cd-btn-red"
                                @click="rejectQuote(activeMission)"
                                :disabled="actionLoading"
                            >
                                Refuser
                            </button>
                            <button
                                class="cd-btn cd-btn-orange"
                                @click="approveQuote(activeMission)"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="cd-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>Approuver le devis ?</span>
                            </button>
                        </div>
                    </div>

                    <!-- Paiement débloqué (étape 11 ? 12) -->
                    <div
                        class="cd-action-block cd-action-pay"
                        v-if="
                            activeMission.payment_unlocked ||
                            activeMission.status === 'completed'
                        "
                    >
                        <p>
                            Les travaux sont confirmés. Procédez au paiement
                            Mobile Money pour clôturer la mission.
                        </p>
                        <button
                            class="cd-btn cd-btn-orange"
                            style="width: 100%"
                            @click="openMomoModal(activeMission)"
                        >
                            Payer via Mobile Money
                        </button>
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="activeMission = null"
                    >
                        Fermer
                    </button>
                    <button
                        class="cd-btn cd-btn-orange cd-chat-btn-notif"
                        @click="chatMissionId = activeMission.id"
                        v-if="activeMission.status !== 'pending'"
                    >
                        Messages
                        <span
                            class="cd-chat-badge"
                            v-if="unreadByMission[activeMission.id] > 0"
                            >{{ unreadByMission[activeMission.id] }}</span
                        >
                    </button>
                </div>
            </div>
        </div>

        <!-- ----------------------------------
             MODAL NOUVELLE MISSION
        ---------------------------------- -->
        <div
            class="cd-modal-overlay"
            v-if="showNewMission"
            @click.self="showNewMission = false"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <h3>Nouvelle commande</h3>
                    <button
                        class="cd-modal-close"
                        @click="showNewMission = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="cd-modal-body">
                    <!-- Quand souhaitez-vous le service ? -->
                    <div class="cd-field">
                        <label>Quand souhaitez-vous le service ? <span class="req">*</span></label>
                        <div class="cd-radio-row">
                            <label class="cd-radio-opt" :class="{ selected: newMissionForm.schedule_type === 'now' }">
                                <input type="radio" v-model="newMissionForm.schedule_type" value="now" />
                                &#9889; Maintenant
                            </label>
                            <label class="cd-radio-opt" :class="{ selected: newMissionForm.schedule_type === 'later' }">
                                <input type="radio" v-model="newMissionForm.schedule_type" value="later" />
                                &#128197; Planifier pour plus tard
                            </label>
                        </div>
                    </div>
                    <!-- Date & Heure si planifié -->
                    <div class="cd-field" v-if="newMissionForm.schedule_type === 'later'" style="display:flex;gap:12px">
                        <div style="flex:1">
                            <label>Date <span class="req">*</span></label>
                            <input class="cd-input" type="date" v-model="newMissionForm.reservation_day" :min="todayDate" />
                        </div>
                        <div style="flex:1">
                            <label>Heure <span class="req">*</span></label>
                            <input class="cd-input" type="time" v-model="newMissionForm.reservation_time" />
                        </div>
                    </div>
                    <div class="cd-field">
                        <label
                            >Type de prestation
                            <span class="req">*</span></label
                        >
                        <select
                            class="cd-input"
                            v-model="newMissionForm.service"
                        >
                            <option value="">Sélectionner...</option>
                            <option
                                v-for="s in services"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.icon }} {{ s.label }}
                            </option>
                        </select>
                    </div>
                    <div class="cd-field">
                        <label>Description <span class="req">*</span></label>
                        <textarea
                            class="cd-input cd-textarea"
                            v-model="newMissionForm.description"
                            placeholder="Décrivez votre besoin en détail (min. 20 caractères)..."
                            rows="4"
                        ></textarea>
                        <div
                            class="cd-char-count"
                            :class="{
                                warn: newMissionForm.description.length < 20,
                            }"
                        >
                            {{ newMissionForm.description.length }} / 2000
                        </div>
                    </div>
                    <!-- Adresse d'intervention -->
                    <div class="cd-field">
                        <label>Adresse d'intervention <span class="req">*</span></label>
                        <div class="cd-addr-modes">
                            <label class="cd-addr-mode-label" :class="{ active: address_mode === 'manual' }">
                                <input type="radio" v-model="address_mode" value="manual" />
                                Saisir manuellement
                            </label>
                            <label class="cd-addr-mode-label" :class="{ active: address_mode === 'geo' }">
                                <input type="radio" v-model="address_mode" value="geo" />
                                Géolocalisation
                            </label>
                        </div>
                    </div>

                    <!-- Saisie manuelle -->
                    <div class="cd-field" v-if="address_mode === 'manual'">
                        <input
                            class="cd-input"
                            type="text"
                            v-model="newMissionForm.address"
                            :placeholder="clientProfile.address || 'Ex : Akpakpa, Cotonou'"
                        />
                    </div>

                    <!-- Géolocalisation : carte -->
                    <div v-if="address_mode === 'geo'" class="cd-field">
                        <button
                            class="cd-btn-geo"
                            type="button"
                            @click="openMapModal"
                            v-if="!geoOk"
                        >
                            Ouvrir la carte
                        </button>
                        <div class="cd-geo-confirmed" v-if="geoOk">
                            <span style="font-size:18px">&#10003;</span>
                            <div>
                                <div class="cd-geo-ok-title">Position enregistrée</div>
                                <div class="cd-geo-ok-sub">{{ newMissionForm.address }}</div>
                            </div>
                            <button type="button" class="cd-geo-reset" @click="resetGeo">Modifier</button>
                        </div>
                    </div>

                    <!-- Photos -->
                    <div class="cd-field">
                        <label>Photos <span class="cd-field-hint">(optionnel, max 5 × 10 Mo)</span></label>
                        <div class="cd-img-upload-row">
                            <label
                                class="cd-img-add-btn"
                                :class="{ disabled: imageFiles.length >= 5 }"
                                :title="imageFiles.length >= 5 ? 'Maximum 5 photos' : 'Ajouter des photos'"
                            >
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    style="display:none"
                                    ref="imgInput"
                                    @change="onImagesSelect"
                                    :disabled="imageFiles.length >= 5"
                                />
                                <span>+ Photo</span>
                            </label>
                            <div
                                class="cd-img-thumb"
                                v-for="(img, i) in imagesPreviews"
                                :key="i"
                            >
                                <img :src="img.url" :alt="img.name" />
                                <button class="cd-img-remove" @click.prevent="removeImage(i)">x</button>
                            </div>
                        </div>
                        <div class="cd-img-count" v-if="imageFiles.length > 0">
                            {{ imageFiles.length }} / 5 photo{{ imageFiles.length > 1 ? 's' : '' }} sélectionnée{{ imageFiles.length > 1 ? 's' : '' }}
                        </div>
                    </div>

                    <div class="cd-err" v-if="missionError">
                        {{ missionError }}
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="showNewMission = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="cd-btn cd-btn-orange"
                        @click="confirmSubmitMission"
                        :disabled="loading"
                    >
                        <div class="cd-spinner" v-if="loading"></div>
                        <span v-else>Publier la mission ?</span>
                    </button>
                </div>
            </div>
        </div>

        <div
            class="cd-modal-overlay"
            v-if="showPublishConfirm"
            @click.self="showPublishConfirm = false"
        >
            <div class="cd-modal" style="max-width: 500px; width: 95vw">
                <div class="cd-modal-header">
                    <h3>Information importante</h3>
                    <button
                        class="cd-modal-close"
                        @click="showPublishConfirm = false"
                    >
                        x
                    </button>
                </div>
                <div class="cd-modal-body" style="text-align: center">
                    <div style="font-size: 2.4rem; margin-bottom: 14px">🧾</div>
                    <p style="font-weight: 800; margin-bottom: 10px">
                        Frais de diagnostic minimum
                    </p>
                    <p style="color: #4b5563; line-height: 1.6">
                        Une fois le prestataire arrivé chez vous, vous devrez
                        payer au minimum les frais de diagnostic de
                        <strong>{{ diagnosticFee.toLocaleString("fr-FR") }} FCFA</strong>,
                        même si vous ne souhaitez pas poursuivre la mission.
                    </p>
                </div>
                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="showPublishConfirm = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="cd-btn cd-btn-orange"
                        @click="showPublishConfirm = false; submitMission()"
                        :disabled="loading"
                    >
                        <div class="cd-spinner" v-if="loading"></div>
                        <span v-else>J'ai compris, publier</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CARTE LEAFLET -->
        <div
            class="cd-modal-overlay"
            v-if="showMapModal"
            @click.self="closeMapModal"
        >
            <div class="cd-modal" style="max-width:860px;width:96vw">
                <div class="cd-modal-header">
                    <h3>Choisir la position</h3>
                    <button class="cd-modal-close" @click="closeMapModal">&#215;</button>
                </div>
                <div class="cd-modal-body" style="padding-bottom:0">
                    <div class="cd-map-search">
                        <input
                            class="cd-input cd-map-search-input"
                            type="text"
                            v-model="mapSearch"
                            placeholder="Rechercher une adresse, un quartier…"
                            @keydown.enter.prevent="searchOnMap"
                        />
                        <button class="cd-btn cd-btn-orange cd-map-search-btn" type="button" @click="searchOnMap">
                            Rechercher
                        </button>
                    </div>
                    <p style="font-size:12.5px;color:var(--grm);margin-bottom:8px">
                        Cliquez sur la carte ou faites glisser le marqueur pour ajuster la position.
                    </p>
                    <div id="cd-leaflet-map" style="height:440px;width:100%;border-radius:10px;overflow:hidden"></div>
                    <div v-if="mapAddress" style="margin-top:10px;font-size:13px;color:var(--dk)">
                        <strong>{{ mapAddress }}</strong>
                    </div>
                    <div v-if="geoLoading" style="margin-top:8px;font-size:13px;color:var(--grm)">
                        Localisation en cours…
                    </div>
                </div>
                <div class="cd-modal-footer">
                    <button class="cd-btn cd-btn-ghost" @click="closeMapModal">Annuler</button>
                    <button class="cd-btn cd-btn-orange success-action" @click="validatePosition" :disabled="!mapLat">
                        Valider cette position
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- CHAT MODAL -------------- -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- Lightbox photos mission -->
        <div
            class="cd-dash-lightbox"
            v-if="dashLightbox"
            @click="dashLightbox = null"
        >
            <img :src="dashLightbox" />
        </div>

        <!-- WIP MODAL -->
        <div
            class="cd-wip-overlay"
            v-if="wipVisible"
            @click="wipVisible = false"
        >
            <div class="cd-wip-modal" @click.stop>
                <div class="cd-wip-icon">...</div>
                <h3>Fonctionnalité en cours de développement</h3>
                <p class="cd-wip-feature">{{ wipFeature }}</p>
                <p>Cette section sera disponible très prochainement.</p>
                <button
                    class="cd-btn cd-btn-orange"
                    @click="wipVisible = false"
                    style="width: 100%"
                >
                    Compris
                </button>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="cd-toast-container">
            <div
                class="cd-toast"
                :class="t.type"
                v-for="t in toasts"
                :key="t.id"
            >
                {{ t.message }}
            </div>
        </div>

        <!-- -------------- MODAL PAIEMENT MOMO -------------- -->
        <div
            class="cd-modal-overlay"
            v-if="momoModal.visible"
            @click.self="!momoModal.polling && (momoModal.visible = false)"
        >
            <div class="cd-modal">
                <div class="cd-modal-header">
                    <div>
                        <h3>Paiement Mobile Money</h3>
                        <div class="cd-modal-sub">
                            Mission #{{ momoModal.mission?.id }} —
                            {{ formatPrice(momoModal.mission?.total_amount) }}
                        </div>
                    </div>
                    <button
                        class="cd-modal-close"
                        @click="momoModal.visible = false"
                        :disabled="momoModal.polling"
                    >&#215;</button>
                </div>

                <!-- -- Saisie réseau + numéro -- -->
                <div class="cd-modal-body" v-if="momoModal.step === 'form'">
                    <div class="cd-field">
                        <label class="cd-label">Réseau Mobile Money <span class="req">*</span></label>
                        <div class="clm-momo-networks">
                            <button
                                type="button"
                                class="clm-momo-network"
                                v-for="net in momoNetworks"
                                :key="net.value"
                                :class="{ active: momoModal.network === net.value }"
                                @click="momoModal.network = net.value"
                            >
                                <span class="clm-momo-net-icon">{{ net.icon }}</span>
                                <span class="clm-momo-net-label">{{ net.label }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="cd-field">
                        <label class="cd-label">Numéro de téléphone <span class="req">*</span></label>
                        <input
                            class="cd-input"
                            type="tel"
                            v-model="momoModal.phone"
                            placeholder="Ex : 97 12 34 56"
                            maxlength="20"
                        />
                        <div class="clm-momo-hint">
                            Numéro associé à votre compte {{ momoModal.network ? momoNetworks.find(n => n.value === momoModal.network)?.label : 'Mobile Money' }}.
                        </div>
                    </div>
                    <div class="clm-momo-recap" v-if="momoModal.network && momoModal.phone">
                        <div class="clm-momo-recap-row">
                            <span>Réseau</span>
                            <strong>{{ momoNetworks.find(n => n.value === momoModal.network)?.label }}</strong>
                        </div>
                        <div class="clm-momo-recap-row">
                            <span>Numéro</span>
                            <strong>{{ momoModal.phone }}</strong>
                        </div>
                        <div class="clm-momo-recap-row">
                            <span>Montant</span>
                            <strong class="clm-momo-amount">{{ formatPrice(momoModal.mission?.total_amount) }}</strong>
                        </div>
                    </div>
                    <div class="clm-momo-warning">
                        Une demande de paiement sera envoyée sur votre téléphone. Confirmez avec votre code PIN.
                    </div>
                </div>

                <!-- -- Attente confirmation téléphone -- -->
                <div class="cd-modal-body cd-momo-waiting" v-if="momoModal.step === 'polling'">
                    <div class="cd-momo-pulse">...</div>
                    <div class="cd-momo-wait-title">Confirmez sur votre téléphone</div>
                    <div class="cd-momo-wait-sub">
                        Une demande de paiement de <strong>{{ formatPrice(momoModal.mission?.total_amount) }}</strong>
                        a été envoyée au <strong>{{ momoModal.phone }}</strong>.<br>
                        Entrez votre code PIN MoMo pour valider.
                    </div>
                    <div class="cd-momo-wait-timer">{{ momoModal.pollSecondsLeft }}s restantes</div>
                </div>

                <!-- -- Succès -- -->
                <div class="cd-modal-body cd-momo-success" v-if="momoModal.step === 'success'">
                    <div class="cd-momo-success-icon">&#10003;</div>
                    <div class="cd-momo-success-title">Paiement confirmé !</div>
                    <div class="cd-momo-success-sub">
                        Mission clôturée avec succès.<br>
                        Montant payé : <strong>{{ formatPrice(momoModal.mission?.total_amount) }}</strong>
                    </div>
                    <a
                        v-if="momoModal.receiptUrl"
                        :href="momoModal.receiptUrl"
                        target="_blank"
                        class="cd-btn cd-btn-orange"
                        style="margin-top:16px;display:inline-flex;align-items:center;gap:8px;text-decoration:none"
                    >
                        Télécharger le reçu
                    </a>
                </div>

                <!-- -- Échec -- -->
                <div class="cd-modal-body cd-momo-error" v-if="momoModal.step === 'failed'">
                    <div class="cd-momo-error-icon">!</div>
                    <div class="cd-momo-error-title">Paiement échoué</div>
                    <div class="cd-momo-error-sub">{{ momoModal.errorMessage || 'Le paiement a été refusé ou a expiré.' }}</div>
                    <button class="cd-btn cd-btn-ghost" style="margin-top:16px" @click="momoModal.step = 'form'">
                        Réessayer
                    </button>
                </div>

                <div class="cd-modal-footer">
                    <button
                        class="cd-btn cd-btn-ghost"
                        @click="momoModal.visible = false"
                        :disabled="momoModal.polling"
                    >
                        {{ momoModal.step === 'success' ? 'Fermer' : 'Annuler' }}
                    </button>
                    <button
                        v-if="momoModal.step === 'form'"
                        class="cd-btn cd-btn-orange"
                        @click="submitMomo"
                        :disabled="!momoModal.network || !momoModal.phone.trim() || momoModal.loading"
                    >
                        <div class="cd-spinner" v-if="momoModal.loading"></div>
                        <span v-else>Confirmer le paiement</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ----------------------------------
             CHAT MODAL (ouvert depuis liste ou détail mission)
        ---------------------------------- -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- Lightbox images -->
        <div
            class="cd-lightbox"
            v-if="dashLightbox"
            @click="dashLightbox = null"
        >
            <img :src="dashLightbox" />
        </div>

        <!-- TOASTS -->
        <div class="cd-toast-container">
            <div
                class="cd-toast"
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
import MissionChatModal from "../../MissionChatModal.vue";

function normalizeServices(services) {
    return (Array.isArray(services) ? services : [])
        .filter((s) => s?.label || s?.name)
        .map((s) => {
            const name = s.label ?? s.name;
            return {
                value: s.value ?? name,
                label: name,
                icon: s.icon ?? "",
            };
        })
        .sort((a, b) =>
            a.label.localeCompare(b.label, "fr", { sensitivity: "base" })
        );
}

export default {
    name: "ClientDashboardComponent",
    components: { MissionChatModal },

    props: {
        user: {
            type: Object,
            default: () => ({
                id: 0,
                name: "Client",
                email: "",
                role: "client",
            }),
        },
        clientProfile: {
            type: Object,
            default: () => ({
                first_name: "",
                last_name: "",
                phone: "",
                address: "",
                city: "Cotonou",
                profile_picture: null,
                account_type: "individual",
                company_name: null,
            }),
        },
        routes: {
            type: Object,
            default: () => ({
                missions_index: "/client/missions",
                missions_store: "/client/missions",
                services_public: "/services/public",
                missions_status: "/client/missions/{id}/status",
                notifications: "/notifications",
                notifications_read: "/notifications/{id}/read",
                notifications_all: "/notifications/read-all",
                conversations_mission: "/conversations/mission/{id}",
                conversations_messages: "/conversations/{id}/messages",
                conversations_send: "/conversations/{id}/messages",
                conversations_attach: "/conversations/{id}/attachment",
                conversations_read: "/conversations/{id}/read",
            }),
        },
        initialServices: {
            type: Array,
            default: () => [],
        },
        docProgressData: {
            type: Object,
            default: () => ({ approved: 0, total: 2, percentage: 0 }),
        },
        userStatus: {
            type: String,
            default: "pending",
        },
        diagnosticFee: {
            type: Number,
            default: 5000,
        },
    },

    data() {
        return {
            // UI state
            tab: "all",
            activeMission: null,
            showNewMission: false,
            showPublishConfirm: false,
            wipVisible: false,
            wipFeature: "",
            loading: false,
            actionLoading: false,
            missionError: "",
            imageFiles: [],
            imagesPreviews: [],
            sidebarOpen: false,
            chatMissionId: null,
            chatUnread: 0,
            unreadByMission: {}, // { missionId: count }
            dashLightbox: null,
            // Paiement MoMo
            momoModal: {
                visible: false,
                mission: null,
                network: "",
                phone: "",
                loading: false,
                polling: false,
                step: "form",         // form | polling | success | failed
                pollSecondsLeft: 120,
                receiptUrl: null,
                errorMessage: "",
            },
            momoNetworks: [
                { value: "mtn", label: "MTN Bénin", icon: "" },
                { value: "moov", label: "Moov Bénin", icon: "" },
                { value: "celtiis", label: "Celtiis", icon: "" },
            ],

            toasts: [],
            toastId: 0,

            // Adresse / géolocalisation
            address_mode: "manual",
            showMapModal: false,
            geoLoading: false,
            geoOk: false,
            mapAddress: "",
            mapSearch: "",
            mapLat: null,
            mapLng: null,
            leafletMap: null,
            leafletMarker: null,

            // Notifications
            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
            notifInterval: null,

            // Missions (chargées depuis l'API)
            missions: [],
            missionsLoading: true,
            missionsError: null,

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "En cours" }, // statuts actifs groupés
                { key: "pending", label: "En attente" },
                { key: "closed", label: "Terminées" },
                { key: "cancelled", label: "Annulées" },
            ],

            // Formulaire nouvelle mission
            newMissionForm: {
                schedule_type: "now",
                reservation_day: "",
                reservation_time: "",
                location_type: "residential",
                service: "",
                description: "",
                address: "",
            },

            services: normalizeServices(this.initialServices).length
                ? normalizeServices(this.initialServices)
                : [
                { value: "plomberie", label: "Plomberie", icon: "" },
                { value: "electricite", label: "Électricité", icon: "" },
                { value: "menuiserie", label: "Menuiserie", icon: "" },
                { value: "ferronnerie", label: "Ferronnerie", icon: "" },
                { value: "frigoriste", label: "Frigoriste", icon: "" },
                { value: "mecanique", label: "Mécanique auto", icon: "" },
                { value: "nettoyage", label: "Nettoyage", icon: "" },
                { value: "peinture", label: "Peinture", icon: "" },
                { value: "maintenance", label: "Maintenance", icon: "" },
            ],

            // Prestataires recommandés (statiques pour l'instant)
            contractors: [
                {
                    id: 1,
                    initials: "KM",
                    name: "Kokou Mensah",
                    specialty: "Plombier",
                    rating: 4.9,
                    reviews: 38,
                    zone: "Akpakpa",
                    color: "#F97316",
                },
                {
                    id: 2,
                    initials: "AD",
                    name: "Aminata Diallo",
                    specialty: "Électricienne",
                    rating: 4.8,
                    reviews: 24,
                    zone: "Porto-Novo",
                    color: "#8b5cf6",
                },
                {
                    id: 3,
                    initials: "PH",
                    name: "Pascal Hounkpe",
                    specialty: "Menuisier",
                    rating: 4.7,
                    reviews: 52,
                    zone: "Parakou",
                    color: "#22c55e",
                },
                {
                    id: 4,
                    initials: "MO",
                    name: "Mariam Ouédraogo",
                    specialty: "Peintre",
                    rating: 4.6,
                    reviews: 17,
                    zone: "Calavi",
                    color: "#f59e0b",
                },
            ],
        };
    },

    computed: {
        client() {
            const p = this.clientProfile;
            const full = `${p.first_name} ${p.last_name}`.trim();
            return {
                ...p,
                display_name:
                    p.account_type === "company" && p.company_name
                        ? p.company_name
                        : full,
                initials:
                    (
                        (p.first_name?.[0] ?? "") + (p.last_name?.[0] ?? "")
                    ).toUpperCase() || "CL",
                email: this.user.email,
            };
        },

        todayDate() {
            return new Date().toISOString().split("T")[0];
        },
        isApproved() {
            return this.userStatus === "approved";
        },
        isIdentityVerified() {
            return (
                this.isApproved &&
                (this.clientProfile?.completed_missions ?? 0) >= 1
            );
        },
        docProgress() {
            return (
                this.localDocProgress ??
                this.docProgressData ?? { approved: 0, total: 2, percentage: 0 }
            );
        },

        kpis() {
            const active = [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
            return [
                {
                    icon: "&#128203;",
                    label: "Missions totales",
                    value: this.missions.length,
                    color: "",
                },
                {
                    icon: "&#128736;",
                    label: "En cours",
                    value: this.missions.filter((m) =>
                        active.includes(m.status)
                    ).length,
                    color: "orange",
                },
                {
                    icon: "&#9203;",
                    label: "En attente",
                    value: this.missions.filter((m) => m.status === "pending")
                        .length,
                    color: "",
                },
                {
                    icon: "&#10003;",
                    label: "Terminées",
                    value: this.missions.filter((m) =>
                        ["completed", "closed"].includes(m.status)
                    ).length,
                    color: "green",
                },
            ];
        },

        filteredMissions() {
            let list;
            if (this.tab === "all") {
                list = this.missions;
            } else {
                const activeStatuses = [
                    "assigned",
                    "accepted",
                    "contact_made",
                    "on_the_way",
                    "tracking",
                    "in_progress",
                    "quote_submitted",
                    "order_placed",
                    "awaiting_confirm",
                ];
                if (this.tab === "active")
                    list = this.missions.filter((m) =>
                        activeStatuses.includes(m.status)
                    );
                else if (this.tab === "pending")
                    list = this.missions.filter((m) => m.status === "pending");
                else if (this.tab === "closed")
                    list = this.missions.filter((m) =>
                        ["completed", "closed"].includes(m.status)
                    );
                else if (this.tab === "cancelled")
                    list = this.missions.filter(
                        (m) => m.status === "cancelled"
                    );
                else list = this.missions;
            }
            return list.slice(0, 5);
        },

        tabLabel() {
            return (
                this.tabs
                    .find((t) => t.key === this.tab)
                    ?.label?.toLowerCase() ?? ""
            );
        },
    },

    methods: {
        async fetchServices() {
            try {
                const res = await fetch(
                    this.routes.services_public ?? "/services/public",
                    { headers: { Accept: "application/json" } }
                );
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                const services = normalizeServices(data);
                if (services.length) this.services = services;
            } catch (e) {
                console.error("[ClientDashboard] fetchServices error:", e);
            }
        },

        validateNewMissionForm() {
            if (this.newMissionForm.schedule_type === "later") {
                if (!this.newMissionForm.reservation_day) {
                    this.missionError = "Veuillez choisir une date.";
                    return false;
                }
                if (!this.newMissionForm.reservation_time) {
                    this.missionError = "Veuillez choisir une heure.";
                    return false;
                }
            }
            if (!this.newMissionForm.service) {
                this.missionError =
                    "Veuillez sélectionner un type de prestation.";
                return false;
            }
            if (this.newMissionForm.description.trim().length < 20) {
                this.missionError =
                    "La description doit faire au moins 20 caractères.";
                return false;
            }
            if (!this.newMissionForm.address.trim()) {
                this.missionError = "L'adresse d'intervention est obligatoire.";
                return false;
            }
            this.missionError = "";
            return true;
        },

        confirmSubmitMission() {
            if (!this.validateNewMissionForm()) return;
            this.showPublishConfirm = true;
        },

        // -- Chargement des missions -----------------------------
        async fetchMissions() {
            this.missionsLoading = true;
            this.missionsError = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                // Laravel paginate ? data.data, sinon tableau direct
                this.missions = Array.isArray(data) ? data : data.data ?? [];
            } catch (e) {
                this.missionsError =
                    "Impossible de charger les missions. Vérifiez votre connexion.";
                console.error("[ClientDashboard] fetchMissions error:", e);
            } finally {
                this.missionsLoading = false;
            }
        },

        // -- Créer une mission -----------------------------------
        openNewMission() {
            this.newMissionForm = {
                schedule_type: "now",
                reservation_day: "",
                reservation_time: "",
                location_type: "residential",
                service: "",
                description: "",
                address: this.clientProfile.address ?? "",
                latitude: null,
                longitude: null,
            };
            this.missionError = "";
            this.geoOk = false;
            this.address_mode = "manual";
            this.mapAddress = "";
            this.mapLat = null;
            this.mapLng = null;
            this.imageFiles = [];
            this.imagesPreviews = [];
            this.showNewMission = true;
        },

        onImagesSelect(e) {
            const files = Array.from(e.target.files);
            const remaining = 5 - this.imageFiles.length;
            files.slice(0, remaining).forEach((file) => {
                this.imageFiles.push(file);
                this.imagesPreviews.push({ url: URL.createObjectURL(file), name: file.name });
            });
            if (this.$refs.imgInput) this.$refs.imgInput.value = "";
        },
        removeImage(index) {
            this.imageFiles.splice(index, 1);
            this.imagesPreviews.splice(index, 1);
        },

        // -- Adresse / Géolocalisation --------------------------------------
        openMapModal() {
            this.mapAddress = "";
            this.mapSearch = "";
            this.mapLat = null;
            this.mapLng = null;
            this.showMapModal = true;
        },

        closeMapModal() {
            this.showMapModal = false;
            this.destroyMap();
        },

        initLeafletMap() {
            const L = window.L;
            if (!L) return;
            const el = document.getElementById("cd-leaflet-map");
            if (!el) return;
            if (this.leafletMap) { this.leafletMap.remove(); this.leafletMap = null; }
            const defaultCenter = [6.3654, 2.4183]; // Cotonou
            const map = L.map(el).setView(defaultCenter, 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: '© <a href="https://www.openstreetmap.org">OpenStreetMap</a>',
            }).addTo(map);
            this.leafletMap = map;
            map.on("click", (e) => {
                this.placeMapMarker(e.latlng.lat, e.latlng.lng);
            });
            if (navigator.geolocation) {
                this.geoLoading = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.geoLoading = false;
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.setView([lat, lng], 16);
                        this.placeMapMarker(lat, lng);
                    },
                    () => { this.geoLoading = false; },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            }
        },

        destroyMap() {
            if (this.leafletMap) { this.leafletMap.remove(); this.leafletMap = null; }
            this.leafletMarker = null;
        },

        async placeMapMarker(lat, lng) {
            const L = window.L;
            if (!L || !this.leafletMap) return;
            this.mapLat = lat;
            this.mapLng = lng;
            if (this.leafletMarker) {
                this.leafletMarker.setLatLng([lat, lng]);
            } else {
                this.leafletMarker = L.marker([lat, lng], { draggable: true }).addTo(this.leafletMap);
                this.leafletMarker.on("dragend", (e) => {
                    const pos = e.target.getLatLng();
                    this.placeMapMarker(pos.lat, pos.lng);
                });
            }
            try {
                const res = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=fr`
                );
                const data = await res.json();
                const a = data.address ?? {};
                const parts = [
                    a.road ?? a.pedestrian ?? a.path ?? "",
                    a.neighbourhood ?? a.suburb ?? a.quarter ?? a.hamlet ?? a.village ?? "",
                    a.city ?? a.town ?? a.municipality ?? a.county ?? "",
                    a.country ?? "",
                ].filter(Boolean);
                this.mapAddress = parts.length >= 2 ? parts.join(", ") : (data.display_name ?? `${lat.toFixed(5)}, ${lng.toFixed(5)}`);
            } catch {
                this.mapAddress = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }
        },

        validatePosition() {
            if (!this.mapLat) return;
            this.newMissionForm.latitude = this.mapLat;
            this.newMissionForm.longitude = this.mapLng;
            this.newMissionForm.address = this.mapAddress || `${this.mapLat.toFixed(5)}, ${this.mapLng.toFixed(5)}`;
            this.geoOk = true;
            this.closeMapModal();
        },

        resetGeo() {
            this.newMissionForm.latitude = null;
            this.newMissionForm.longitude = null;
            this.newMissionForm.address = "";
            this.geoOk = false;
            this.mapAddress = "";
            this.mapLat = null;
            this.mapLng = null;
            this.openMapModal();
        },

        async searchOnMap() {
            if (!this.mapSearch.trim()) return;
            try {
                const res = await fetch(
                    `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(this.mapSearch)}&format=json&limit=1&accept-language=fr`
                );
                const data = await res.json();
                if (!data.length) {
                    this.showToast("Aucun résultat pour cette adresse.", "error");
                    return;
                }
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                if (this.leafletMap) this.leafletMap.setView([lat, lng], 16);
                this.placeMapMarker(lat, lng);
            } catch {
                this.showToast("Erreur lors de la recherche.", "error");
            }
        },

        async submitMission() {
            if (!this.validateNewMissionForm()) return;
            this.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const fd = new FormData();
                Object.entries(this.newMissionForm).forEach(([k, v]) => {
                    if (v === null || v === undefined) return;
                    fd.append(k, v);
                });
                this.imageFiles.forEach((f) => fd.append("images[]", f));
                const res = await fetch(this.routes.missions_store, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: fd,
                });

                const data = await res.json();

                if (!res.ok) {
                    if (res.status === 422) {
                        const firstError = Object.values(
                            data.errors ?? {}
                        )[0]?.[0];
                        this.missionError =
                            firstError ?? "Erreur de validation.";
                    } else {
                        this.missionError =
                            data.message ?? "Une erreur est survenue.";
                    }
                    return;
                }

                this.missions.unshift(data.mission ?? data);
                this.showNewMission = false;
                this.showToast("Mission publiée avec succès.", "success");
                this.fetchMissions();
            } catch (e) {
                this.missionError = "Erreur réseau.";
                console.error("[ClientDashboard] submitMission error:", e);
            } finally {
                this.loading = false;
            }
        },

        async submitMissionOld() {
            if (this.newMissionForm.schedule_type === "later") {
                if (!this.newMissionForm.reservation_day) {
                    this.missionError = "Veuillez choisir une date.";
                    return;
                }
                if (!this.newMissionForm.reservation_time) {
                    this.missionError = "Veuillez choisir une heure.";
                    return;
                }
            }
            if (!this.newMissionForm.service) {
                this.missionError =
                    "Veuillez sélectionner un type de prestation.";
                return;
            }
            if (this.newMissionForm.description.trim().length < 20) {
                this.missionError =
                    "La description doit faire au moins 20 caractères.";
                return;
            }
            if (!this.newMissionForm.address.trim()) {
                this.missionError = "L'adresse d'intervention est obligatoire.";
                return;
            }
            this.missionError = "";
            this.loading = true;

            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const fd = new FormData();
                Object.entries(this.newMissionForm).forEach(([k, v]) => {
                    if (v === null || v === undefined) return;
                    fd.append(k, v);
                });
                this.imageFiles.forEach((f) => fd.append("images[]", f));
                const res = await fetch(this.routes.missions_store, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: fd,
                });

                const data = await res.json();

                if (!res.ok) {
                    if (res.status === 422) {
                        const firstError = Object.values(
                            data.errors ?? {}
                        )[0]?.[0];
                        this.missionError =
                            firstError ?? "Erreur de validation.";
                    } else {
                        this.missionError =
                            data.message ?? "Une erreur est survenue.";
                    }
                    return;
                }

                // Ajouter la nouvelle mission en tête de liste
                this.missions.unshift(data.mission);
                this.showNewMission = false;
                this.showToast(
                    "Mission publiée ! Un prestataire va être attribué.",
                    "success"
                );
            } catch (e) {
                this.missionError = "Erreur réseau. Veuillez réessayer.";
                console.error("[ClientDashboard] submitMission error:", e);
            } finally {
                this.loading = false;
            }
        },

        // -- Transitions de statut -------------------------------

        async updateMissionStatus(mission, status, extra = {}) {
            this.actionLoading = true;
            try {
                const url = this.routes.missions_status.replace(
                    "{id}",
                    mission.id
                );
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ status, ...extra }),
                });

                const data = await res.json();
                if (!res.ok) {
                    this.showToast(
                        data.message ?? "Erreur lors de la mise à jour.",
                        "error"
                    );
                    return;
                }

                // Mettre à jour la mission dans la liste locale
                const idx = this.missions.findIndex((m) => m.id === mission.id);
                if (idx !== -1) this.missions.splice(idx, 1, data.mission);

                // Mettre à jour la modal si ouverte
                if (this.activeMission?.id === mission.id) {
                    this.activeMission = data.mission;
                }

                return data.mission;
            } catch (e) {
                this.showToast("Erreur réseau.", "error");
                console.error("[ClientDashboard] updateStatus error:", e);
            } finally {
                this.actionLoading = false;
            }
        },

        async confirmCompletion(mission) {
            await this.updateMissionStatus(mission, "completed");
            this.showToast(
                "Fin des travaux confirmée. Vous pouvez maintenant procéder au paiement.",
                "success"
            );
        },

        async approveQuote(mission) {
            await this.updateMissionStatus(mission, "order_placed");
            this.showToast(
                "Devis approuvé ! L'intervention va démarrer.",
                "success"
            );
        },

        async rejectQuote(mission) {
            const reason = prompt("Motif du refus (optionnel) :");
            // On repasse à quote_submitted avec le motif — le prestataire verra le refus
            await this.updateMissionStatus(mission, "quote_submitted", {
                reported_issue: reason ?? "",
            });
            this.showToast("Devis refusé. Le prestataire en sera informé.", "");
        },

        signalProblem() {
            this.wip("Signaler un problème");
        },

        // -- Helpers d'affichage ---------------------------------
        openMission(m) {
            this.activeMission = m;
        },

        countByTab(key) {
            if (key === "all") return this.missions.length;
            const activeStatuses = [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
            if (key === "active")
                return this.missions.filter((m) =>
                    activeStatuses.includes(m.status)
                ).length;
            if (key === "pending")
                return this.missions.filter((m) => m.status === "pending")
                    .length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status)
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },

        badgeClass(status) {
            const map = {
                pending: "pending",
                assigned: "active",
                accepted: "active",
                contact_made: "active",
                on_the_way: "active",
                tracking: "active",
                in_progress: "active",
                quote_submitted: "warning",
                order_placed: "warning",
                awaiting_confirm: "warning",
                completed: "done",
                closed: "done",
                cancelled: "cancelled",
            };
            return map[status] ?? "";
        },

        serviceIcon(service) {
            const icons = {
                plomberie: "",
                electricite: "",
                menuiserie: "",
                ferronnerie: "",
                frigoriste: "",
                mecanique: "",
                nettoyage: "",
                peinture: "",
                maintenance: "",
            };
            return icons[service] ?? "";
        },

        formatDate(iso) {
            if (!iso) return "—";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },

        formatPrice(amount) {
            if (!amount) return "—";
            return new Intl.NumberFormat("fr-FR").format(amount) + " FCFA";
        },

        wip(feature) {
            this.wipFeature = feature;
            this.wipVisible = true;
        },

        onChatClose() {
            this.chatMissionId = null;
            this.chatUnread = 0;
        },

        onChatUnread(count) {
            this.chatUnread = count;
            if (this.chatMissionId) {
                this.$set
                    ? this.$set(this.unreadByMission, this.chatMissionId, count)
                    : (this.unreadByMission = {
                          ...this.unreadByMission,
                          [this.chatMissionId]: count,
                      });
            }
            if (count === 0 && this.chatMissionId) {
                const copy = { ...this.unreadByMission };
                delete copy[this.chatMissionId];
                this.unreadByMission = copy;
            }
        },

        openMomoModal(mission) {
            this.momoModal = {
                visible: true,
                mission,
                network: "",
                phone: "",
                loading: false,
                polling: false,
                step: "form",
                pollSecondsLeft: 120,
                receiptUrl: null,
                errorMessage: "",
            };
        },

        async submitMomo() {
            if (!this.momoModal.network || !this.momoModal.phone.trim()) return;
            this.momoModal.loading = true;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const url  = this.routes.payment_initiate.replace('{id}', this.momoModal.mission.id);
            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ phone: this.momoModal.phone.trim(), network: this.momoModal.network }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.momoModal.step = 'failed';
                    this.momoModal.errorMessage = data.message || 'Erreur lors de l\'initiation.';
                    return;
                }
                if (data.status === 'SUCCESSFUL') {
                    this.onMomoSuccess(data);
                } else {
                    this.momoModal.step = 'polling';
                    this.momoModal.pollSecondsLeft = 120;
                    this.startMomoPolling();
                }
            } catch {
                this.momoModal.step = 'failed';
                this.momoModal.errorMessage = 'Erreur réseau. Veuillez réessayer.';
            } finally {
                this.momoModal.loading = false;
                this.momoModal.polling = this.momoModal.step === 'polling';
            }
        },

        startMomoPolling() {
            const statusUrl = this.routes.payment_status.replace('{id}', this.momoModal.mission.id);
            const interval  = setInterval(async () => {
                if (!this.momoModal.visible || this.momoModal.step !== 'polling') {
                    clearInterval(interval);
                    return;
                }
                this.momoModal.pollSecondsLeft = Math.max(0, this.momoModal.pollSecondsLeft - 3);
                if (this.momoModal.pollSecondsLeft <= 0) {
                    clearInterval(interval);
                    this.momoModal.step    = 'failed';
                    this.momoModal.polling = false;
                    this.momoModal.errorMessage = 'Délai expiré. Réessayez.';
                    return;
                }
                try {
                    const res  = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (data.status === 'SUCCESSFUL') {
                        clearInterval(interval);
                        this.onMomoSuccess(data);
                    } else if (data.status === 'FAILED') {
                        clearInterval(interval);
                        this.momoModal.step         = 'failed';
                        this.momoModal.polling      = false;
                        this.momoModal.errorMessage = data.message || 'Paiement refusé.';
                    }
                } catch { /* silently retry */ }
            }, 3000);
        },

        onMomoSuccess(data) {
            this.momoModal.step       = 'success';
            this.momoModal.polling    = false;
            this.momoModal.receiptUrl = data.receipt_url || null;
            if (data.mission) {
                const idx = this.missions.findIndex(m => m.id === data.mission.id);
                if (idx !== -1) this.missions.splice(idx, 1, { ...this.missions[idx], ...data.mission });
                if (this.activeMission?.id === data.mission.id) {
                    this.activeMission = { ...this.activeMission, ...data.mission };
                }
            }
            this.showToast('Paiement confirmé ! Mission clôturée.', 'success');
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
        },

        // -- Traduction statut en français ------------------------
        // Utilise status_label si l'API le retourne, sinon fallback local.
        // Corrige le problème "pending" affiché ou qui disparaît au rechargement.
        labelOf(mission) {
            if (
                mission.status_label &&
                mission.status_label !== mission.status
            ) {
                return mission.status_label;
            }
            const map = {
                pending: "En attente",
                assigned: "Assignée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi en cours",
                in_progress: "Intervention en cours",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "En att. de confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return map[mission.status] ?? mission.status;
        },

        // -- "Il y a X" en français (sans dépendre de Laravel) ---
        agoFr(isoDate) {
            if (!isoDate) return "";
            const diff = Math.floor((Date.now() - new Date(isoDate)) / 1000);
            if (diff < 60) return "À l'instant";
            if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
            if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
            if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;
            return new Date(isoDate).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
            });
        },

        // -- Notifications ----------------------------------------
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.notifications = (data.notifications ?? []).map((n) => ({
                    ...n,
                    ago: this.agoFr(n.created_at),
                }));
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
                const url = this.routes.notifications_read.replace(
                    "{id}",
                    n.id
                );
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
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

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },

        docStatusLabel(s) {
            return (
                {
                    approved: "Validé",
                    pending: "En attente de validation",
                    rejected: "Refusé",
                    missing: "— Non déposé",
                }[s] ?? s
            );
        },

        async uploadDocument(doc, event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                this.showToast(
                    "Fichier trop volumineux. Maximum 5 Mo.",
                    "error"
                );
                event.target.value = "";
                return;
            }
            doc.uploading = true;
            doc.filename = file.name;
            const formData = new FormData();
            formData.append("file", file);
            formData.append("type", doc.type);
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const res = await fetch(this.routes.documents_upload, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: formData,
                });
                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message ?? "Erreur upload.");
                }
                doc.status = "pending";
                doc.uploading = false;
                this.showToast(
                    `${doc.label} déposé. En attente de vérification admin.`,
                    "success"
                );
                await this.refreshDocProgress();
            } catch (err) {
                doc.uploading = false;
                doc.filename = null;
                event.target.value = "";
                this.showToast(
                    err.message ?? "Erreur lors de l'upload.",
                    "error"
                );
            }
        },

        async refreshDocProgress() {
            try {
                const res = await fetch(this.routes.documents_index, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                // L'API retourne { documents: [...], progress: {...} }
                const docs = data.documents ?? [];
                this.localDocuments.forEach((localDoc) => {
                    const srv = docs.find((d) => d.type === localDoc.type);
                    if (srv) {
                        localDoc.status = srv.status;
                        localDoc.reject_reason = srv.reject_reason;
                        localDoc.filename = srv.filename;
                    }
                });
                // Utiliser la progression retournée par le serveur
                if (data.progress) {
                    this.localDocProgress = data.progress;
                } else {
                    const approved = this.localDocuments.filter(
                        (d) => d.status === "approved"
                    ).length;
                    const total = this.localDocuments.length;
                    this.localDocProgress = {
                        approved,
                        total,
                        percentage:
                            total > 0
                                ? Math.round((approved / total) * 100)
                                : 0,
                    };
                }
            } catch {
                /* silencieux */
            }
        },

        onGlobalUnreadUpdate(evt) {
            const byMission = evt.detail?.by_mission ?? {};
            if (this.chatMissionId) {
                const updated = { ...byMission };
                delete updated[this.chatMissionId];
                this.unreadByMission = updated;
            } else {
                this.unreadByMission = byMission;
            }
        },
    },

    watch: {
        showMapModal(val) {
            if (val) {
                this.$nextTick(() => this.initLeafletMap());
            } else {
                this.destroyMap();
            }
        },
        address_mode(val) {
            if (val === "geo") this.openMapModal();
        },
    },

    mounted() {
        this.newMissionForm.address = this.clientProfile.address ?? "";

        // Init documents client
        const defaultDocs = [
            {
                type: "cni",
                label: "Pièce d'identité valide (CNI / Passeport)",
                icon: "",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
            {
                type: "photo_profil",
                label: "Photo de profil",
                icon: "",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
        ];
        this.localDocuments = defaultDocs;
        this.localDocProgress = { ...this.docProgressData };
        this.refreshDocProgress();

        this.fetchServices();
        this.fetchMissions();
        this.fetchNotifications();

        // Polling notifications toutes les 30s
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000
        );

        // Fermer dropdown au clic extérieur
        document.addEventListener("click", this.handleClickOutside);

        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });

        // Écouter les mises à jour de messages non lus (même event que MissionComponent)
        window.addEventListener("rt-unread-update", this.onGlobalUnreadUpdate);
    },

    beforeUnmount() {
        this.destroyMap();
        clearInterval(this.notifInterval);
        document.removeEventListener("click", this.handleClickOutside);
        window.removeEventListener(
            "rt-unread-update",
            this.onGlobalUnreadUpdate
        );
    },
};
</script>

<style scoped>
/* --------------------------------------------------------------
   Variables
-------------------------------------------------------------- */
:root {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #ffffff;
    --cr: #faf7f5;
}

.cd-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* TOPBAR */
.cd-topbar {
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
    .cd-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.cd-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.cd-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.cd-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cd-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: none;
}
@media (min-width: 480px) {
    .cd-page-sub {
        display: block;
    }
}
.cd-page-sub strong {
    color: var(--dk);
}

/* AVATAR */
.cd-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
}

/* BURGER */
.ad-burger {
    background: none;
    border: none;
    cursor: pointer;
    flex-direction: column;
    gap: 5px;
    padding: 4px;
    flex-shrink: 0;
    display: flex;
}
.ad-burger span {
    display: block;
    width: 20px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
    transition: all 0.2s;
}

/* CONTENT */
.cd-content {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1280px;
    margin: 0 auto;
    width: 100%;
}
@media (min-width: 600px) {
    .cd-content {
        padding: 24px;
    }
}

/* BANNER */
.cd-banner-company {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 12px 18px;
    font-size: 13.5px;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.cd-banner-badge {
    background: #dbeafe;
    border-radius: 99px;
    padding: 2px 12px;
    font-size: 12px;
    font-weight: 600;
}

/* KPIs */
.cd-kpis {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 768px) {
    .cd-kpis {
        grid-template-columns: repeat(4, 1fr);
    }
}
.cd-kpi {
    background: var(--wh);
    border-radius: 14px;
    padding: 18px;
    border: 1.5px solid var(--grl);
}
.cd-kpi.orange {
    border-color: #fed7aa;
    background: #fff7ed;
}
.cd-kpi.green {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.cd-kpi-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    margin-right: 10px;
    border-radius: 8px;
    background: rgba(249, 115, 22, 0.1);
    color: var(--or);
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
}
.cd-kpi-val {
    font-size: 28px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
}
.cd-kpi-label {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}

/* SKELETON */
.cd-skeleton-val {
    display: inline-block;
    width: 48px;
    height: 28px;
    background: var(--grl);
    border-radius: 6px;
    animation: cd-pulse 1.4s ease infinite;
}
.cd-skeleton-row {
    height: 56px;
    background: var(--grl);
    border-radius: 10px;
    margin-bottom: 10px;
    animation: cd-pulse 1.4s ease infinite;
}
@keyframes cd-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}

/* MID GRID */
.cd-mid-grid {
    display: grid;
    gap: 20px;
}
@media (min-width: 1024px) {
    .cd-mid-grid {
        grid-template-columns: 1fr 320px;
    }
}
.cd-side-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* CARD */
.cd-card {
    background: var(--wh);
    border-radius: 16px;
    border: 1.5px solid var(--grl);
    overflow: hidden;
}
.cd-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px 0;
}
.cd-card-header h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}

/* TABS */
.cd-tabs {
    display: flex;
    gap: 4px;
    padding: 12px 18px 0;
    flex-wrap: wrap;
}
.cd-tab {
    background: none;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    color: var(--gr);
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.cd-tab.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.cd-tab-count {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 11px;
}
.cd-tab.active .cd-tab-count {
    background: rgba(255, 255, 255, 0.3);
}

/* ALERT ERROR */
.cd-alert-error {
    margin: 16px 18px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* MISSION LIST */
.cd-loading {
    padding: 16px 18px;
}
.cd-mission-list {
    padding: 8px 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.cd-mission-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 12px 10px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    gap: 10px;
    border: 1.5px solid transparent;
    /* mobile: min touch target */
    min-height: 56px;
    position: relative;
}
.cd-mission-item:hover {
    background: #faf7f5;
}
.cd-mission-left {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.cd-mission-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cd-mission-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
}
.cd-mission-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-mission-imgs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}
.cd-mission-img-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.08);
    transition: opacity 0.15s;
}
.cd-mission-img-thumb:hover { opacity: 0.8; }
.cd-mission-imgs-more {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    background: rgba(0,0,0,0.07);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #555;
}
.cd-mission-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.cd-mission-price {
    font-size: 12px;
    color: var(--gr);
    font-weight: 600;
}

/* EMPTY */
.cd-empty {
    padding: 40px 20px;
    text-align: center;
}
.cd-empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}
.cd-empty-title {
    font-size: 14px;
    color: var(--gr);
    font-weight: 600;
}

/* BADGES */
.cd-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.cd-badge.pending {
    background: #fef9c3;
    color: #a16207;
}
.cd-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.cd-badge.warning {
    background: #fff7ed;
    color: #c2410c;
}
.cd-badge.done {
    background: #dcfce7;
    color: #15803d;
}
.cd-badge.cancelled {
    background: #f1f5f9;
    color: #64748b;
}

/* PROFIL */
.cd-profile-info {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
}
.cd-profile-av {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
    overflow: hidden;
}
.cd-profile-av.has-photo {
    background: #fff;
}
.cd-profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.cd-profile-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.cd-profile-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-account-type {
    padding: 0 18px 16px;
}
.cd-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}
.cd-type-badge.individual {
    background: #f0fdf4;
    color: #15803d;
}
.cd-type-badge.company {
    background: #eff6ff;
    color: #1d4ed8;
}

/* QUICK ACTIONS */
.cd-quick-actions {
    padding: 12px 18px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cd-quick-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--cr);
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: all 0.18s;
    text-align: left;
}
.cd-quick-btn:hover {
    border-color: var(--or);
    background: #fff8f5;
}

/* CONTRACTORS GRID */
.cd-contractors-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 14px 18px 18px;
}
@media (min-width: 768px) {
    .cd-contractors-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
.cd-contractor-card {
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.18s;
}
.cd-contractor-card:hover {
    border-color: var(--or);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
}
.cd-contractor-av {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    margin: 0 auto 8px;
}
.cd-contractor-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.cd-contractor-spec {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-contractor-rating {
    font-size: 12px;
    color: var(--or);
    font-weight: 700;
    margin-top: 4px;
}
.cd-contractor-rating span {
    color: var(--gr);
    font-weight: 500;
}
.cd-contractor-zone {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}

/* STEP BAR */
.cd-step-bar {
    background: var(--grl);
    height: 6px;
    border-radius: 99px;
    overflow: hidden;
    margin: 16px 0 4px;
}
.cd-step-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--or), var(--or2));
    transition: width 0.4s ease;
}
.cd-step-label {
    font-size: 12px;
    color: var(--grm);
    font-weight: 600;
    margin-bottom: 12px;
}

/* ACTION BLOCKS */
.cd-action-block {
    background: #faf7f5;
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 14px 16px;
    margin-top: 14px;
}
.cd-action-quote {
    background: #fff8f5;
    border-color: #fed7aa;
}
.cd-action-pay {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.cd-action-block p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.5;
    margin-bottom: 12px;
}
.cd-action-block strong {
    color: var(--dk);
}
.cd-action-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* FORM */
.cd-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
}
.cd-field label {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.cd-field-hint {
    font-weight: 400;
    color: var(--gr);
    font-size: 12px;
}
.req {
    color: var(--or);
}
/* Upload images */
.cd-img-upload-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.cd-img-add-btn {
    width: 64px;
    height: 64px;
    border: 2px dashed var(--or);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: var(--or);
    transition: background 0.15s;
    flex-shrink: 0;
}
.cd-img-add-btn:hover { background: var(--or3); }
.cd-img-add-btn.disabled { opacity: 0.4; cursor: not-allowed; }
.cd-img-thumb {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}
.cd-img-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cd-img-remove {
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}
.cd-img-count {
    font-size: 11px;
    color: var(--gr);
    margin-top: 2px;
}
.cd-input {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid var(--grl);
    border-radius: 9px;
    font-size: 14px;
    font-family: "Poppins", sans-serif;
    outline: none;
    transition: border-color 0.2s;
    color: var(--dk);
    background: var(--cr);
    box-sizing: border-box;
}
.cd-input:focus {
    border-color: var(--or);
    background: #fff;
}
.cd-textarea {
    min-height: 90px;
    resize: vertical;
}
.cd-char-count {
    font-size: 11.5px;
    color: var(--grm);
    text-align: right;
    margin-top: 3px;
}
.cd-char-count.warn {
    color: #f59e0b;
}
.cd-err {
    font-size: 12px;
    color: #ef4444;
    margin-top: 2px;
}

/* RADIO ROW */
.cd-radio-row {
    display: flex;
    gap: 10px;
}
.cd-radio-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
    color: var(--dk);
}
.cd-radio-opt input {
    accent-color: var(--or);
}
.cd-radio-opt.selected {
    border-color: var(--or);
    background: #fff8f5;
}

/* BUTTONS */
.cd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
}
.cd-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.cd-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.cd-btn-orange:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.cd-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.cd-btn-ghost:hover {
    background: #d5c9c0;
}
.cd-btn-red {
    background: #ef4444;
    color: #fff;
}
.cd-btn-red:hover {
    background: #dc2626;
}
.cd-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}

/* SPINNER */
.cd-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cd-spin 0.7s linear infinite;
}
@keyframes cd-spin {
    to {
        transform: rotate(360deg);
    }
}

/* MODAL */
.cd-modal-overlay {
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
.cd-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 520px;
    max-height: 92vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: cd-slide-up 0.25s ease;
}
@keyframes cd-slide-up {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.cd-modal-header {
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
.cd-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.cd-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.cd-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.cd-modal-body {
    padding: 20px 24px;
    flex: 1;
    overflow-y: auto;
}
.cd-modal-footer {
    padding: 14px 24px;
    border-top: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
    flex-wrap: wrap;
}
.cd-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13.5px;
    gap: 12px;
}
.cd-detail-row:last-child {
    border-bottom: none;
}
.cd-detail-photos {
    align-items: flex-start;
}
.cd-dash-images {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}
.cd-dash-img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: opacity 0.15s;
}
.cd-dash-img:hover {
    opacity: 0.85;
}
.cd-dash-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.88);
    z-index: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 20px;
}
.cd-dash-lightbox img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
}
.cd-detail-row span:first-child {
    color: var(--gr);
    flex-shrink: 0;
}
.cd-detail-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}

/* WIP */
.cd-wip-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 13, 11, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: cd-fade-in 0.2s ease;
}
@keyframes cd-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.cd-wip-modal {
    background: #fff;
    border-radius: 20px;
    padding: 36px 30px;
    max-width: 380px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: cd-slide-up 0.25s ease;
}
.cd-wip-icon {
    font-size: 48px;
    margin-bottom: 14px;
    animation: cd-bounce 0.6s ease infinite alternate;
}
@keyframes cd-bounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-6px);
    }
}
.cd-wip-modal h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 8px;
}
.cd-wip-feature {
    display: inline-block;
    background: var(--or3);
    color: var(--or);
    border: 1.5px solid var(--am);
    border-radius: 99px;
    padding: 3px 14px;
    font-size: 12.5px;
    font-weight: 700;
    margin-bottom: 10px;
}
.cd-wip-modal p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.6;
    margin-bottom: 20px;
}

/* TOASTS */
.cd-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.cd-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: cd-slide-up 0.3s ease;
}
.cd-toast.success {
    background: #16a34a;
}
.cd-toast.error {
    background: #dc2626;
}

/* -- Notifications cloche -- */
.cd-notif-wrap {
    position: relative;
}
.cd-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    transition: color 0.18s;
}
.cd-notif-btn:hover {
    color: var(--or);
}
.cd-notif-btn svg {
    width: 22px;
    height: 22px;
}
.cd-notif-badge {
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
.cd-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
    max-height: 420px;
    overflow-y: auto;
}
.cd-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    position: sticky;
    top: 0;
    background: var(--wh);
}
.cd-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.cd-notif-loading,
.cd-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.cd-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.cd-notif-item:last-child {
    border-bottom: none;
}
.cd-notif-item:hover {
    background: #faf7f5;
}
.cd-notif-item.unread {
    background: #fff8f5;
}
.cd-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.cd-notif-content {
    flex: 1;
    min-width: 0;
}
.cd-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.cd-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.cd-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

/* -- Barre de recherche carte -- */
.cd-map-search {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}
.cd-map-search-input {
    flex: 1;
    margin-bottom: 0 !important;
}
.cd-map-search-btn {
    flex-shrink: 0;
    padding: 10px 16px;
    font-size: 13px;
}

/* -- Sélecteur mode adresse -- */
.cd-addr-modes, .cd-geo-methods {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.cd-addr-mode-label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--gr);
    background: var(--wh);
    transition: all 0.15s;
    user-select: none;
}
.cd-addr-mode-label input[type="radio"] {
    accent-color: var(--or);
}
.cd-addr-mode-label.active,
.cd-addr-mode-label:has(input:checked) {
    border-color: var(--or);
    color: var(--or);
    background: var(--or3);
}

/* -- Géolocalisation -- */
.cd-btn-geo {
    display: flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: 1.5px dashed var(--grm);
    border-radius: 10px;
    padding: 11px 16px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    margin-bottom: 14px;
    width: 100%;
    justify-content: center;
}
.cd-btn-geo:hover:not(:disabled) {
    border-color: var(--or);
    color: var(--or);
}
.cd-btn-geo:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.cd-spinner-sm {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(249, 115, 22, 0.3);
    border-top-color: var(--or);
    border-radius: 50%;
    animation: cd-spin 0.7s linear infinite;
    flex-shrink: 0;
}
@keyframes cd-spin {
    to {
        transform: rotate(360deg);
    }
}
.cd-geo-confirmed {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 10px;
    padding: 11px 14px;
    margin-bottom: 14px;
}
.cd-geo-ok-title {
    font-size: 13px;
    font-weight: 700;
    color: #15803d;
}
.cd-geo-ok-sub {
    font-size: 11.5px;
    color: #16a34a;
    margin-top: 2px;
}
.cd-geo-reset {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    color: #16a34a;
    font-size: 15px;
    padding: 2px 6px;
    border-radius: 5px;
}
.cd-geo-reset:hover {
    background: #dcfce7;
}
.cd-geo-blocked {
    font-size: 12.5px;
    color: #92400e;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
}

/* -- Modal géo consentement -- */
.cd-geo-reasons {
    list-style: none;
    padding: 0;
    margin: 0 0 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cd-geo-reasons li {
    font-size: 13.5px;
    color: var(--gr);
    padding: 8px 12px;
    background: #faf7f5;
    border-radius: 8px;
}
.cd-geo-privacy-note {
    font-size: 12.5px;
    color: var(--grm);
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 10px 14px;
    line-height: 1.5;
}
.cd-masked-phone {
    font-size: 12px;
    color: #7c6a5a;
    font-style: italic;
}
.cd-msg-unread {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    color: #f97316;
    background: #fff7ed;
    border-radius: 99px;
    padding: 2px 8px;
    margin-top: 4px;
    width: fit-content;
}

/* -- Bande unread gauche -- */
.cd-unread-stripe {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #f97316, #ea580c);
    border-radius: 4px 0 0 4px;
}
.cd-mission-item.has-unread {
    background: #fffbf7;
    border-color: #fed7aa;
}

/* -- Info col dans mission-left -- */
.cd-mission-info {
    min-width: 0;
    flex: 1;
}

/* -- Bouton Lire message -- */
.cd-read-msg-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border: none;
    border-radius: 99px;
    padding: 4px 10px;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.18s, transform 0.12s;
    margin-top: 4px;
    box-shadow: 0 2px 6px rgba(249, 115, 22, 0.35);
    font-family: "Poppins", sans-serif;
}
.cd-read-msg-btn:hover {
    opacity: 0.88;
    transform: scale(1.04);
}
.cd-read-msg-dot {
    background: #fff;
    color: #f97316;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    min-width: 17px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
}
.cd-chat-btn-notif {
    position: relative;
}
.cd-chat-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: #fff;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

/* -- Badge Identité vérifiée -- */
.cd-banner-verified {
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1.5px solid #86efac;
    border-radius: 14px;
    padding: 14px 20px;
    margin-bottom: 20px;
}
.cd-verified-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.cd-verified-title {
    font-size: 14.5px;
    font-weight: 800;
    color: #15803d;
}
.cd-verified-sub {
    font-size: 12.5px;
    color: #166534;
    margin-top: 2px;
}
.cd-banner-progress-missions {
    background: linear-gradient(90deg, #f97316, #ea580c) !important;
}

/* -- Bannière compte en attente de validation -- */
.cd-banner-pending {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1.5px solid #fde68a;
    border-left: 4px solid var(--am);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}
.cd-banner-pending-left {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex: 1;
    min-width: 0;
}
.cd-banner-pending-icon {
    font-size: 28px;
    flex-shrink: 0;
    margin-top: 2px;
}
.cd-banner-pending-title {
    font-size: 14px;
    font-weight: 800;
    color: #92400e;
    margin-bottom: 4px;
    line-height: 1.4;
}
.cd-banner-pending-sub {
    font-size: 12.5px;
    color: #78350f;
    line-height: 1.6;
}
.cd-banner-progress-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}
.cd-banner-progress-track {
    flex: 1;
    height: 6px;
    background: #fde68a;
    border-radius: 99px;
    overflow: hidden;
    max-width: 200px;
}
.cd-banner-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s ease;
}
.cd-banner-progress-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #92400e;
    white-space: nowrap;
}
.cd-banner-btn {
    flex-shrink: 0;
    white-space: nowrap;
    text-decoration: none;
    padding: 10px 18px;
}
@media (max-width: 640px) {
    .cd-banner-pending {
        flex-direction: column;
    }
    .cd-banner-btn {
        width: 100%;
        justify-content: center;
    }
}

/* -- MODAL PAIEMENT MOMO -- */
.clm-momo-networks {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}
.clm-momo-network {
    flex: 1;
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border: 2px solid var(--grl);
    border-radius: 12px;
    background: var(--wh);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
}
.clm-momo-network:hover {
    border-color: var(--or);
}
.clm-momo-network.active {
    border-color: var(--or);
    background: var(--or3);
}
.clm-momo-net-icon {
    font-size: 26px;
}
.clm-momo-net-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--dk);
}
.clm-momo-hint {
    font-size: 12px;
    color: var(--gr);
    margin-top: 6px;
    line-height: 1.5;
}
.clm-momo-recap {
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.clm-momo-recap-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: var(--gr);
}
.clm-momo-recap-row strong {
    color: var(--dk);
}
.clm-momo-amount {
    color: var(--or);
    font-size: 15px;
}
.clm-momo-warning {
    font-size: 12.5px;
    color: #92400e;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 14px;
    line-height: 1.5;
}

/* -- Lightbox -- */
.cd-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
}
.cd-lightbox img {
    max-width: 92vw;
    max-height: 88vh;
    border-radius: 10px;
    object-fit: contain;
}

/* -- Toasts -- */
.cd-toast-container {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 9999;
    pointer-events: none;
}
.cd-toast {
    background: var(--dk);
    color: #fff;
    border-radius: 12px;
    padding: 12px 20px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22);
    animation: cd-toast-in 0.3s ease;
    white-space: nowrap;
}
.cd-toast.success {
    background: #16a34a;
}
.cd-toast.error {
    background: #dc2626;
}
@keyframes cd-toast-in {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* --------------------------------------------------------------
   SECTION DOCUMENTS CLIENT
-------------------------------------------------------------- */
.cd-docs-card {
    margin-top: 20px;
}
.cd-doc-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.cd-doc-summary {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    background: var(--grl);
    padding: 4px 10px;
    border-radius: 99px;
}
.cd-doc-summary.complete {
    background: #dcfce7;
    color: #16a34a;
}
.cd-certified-pill {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
}
.cd-docs-certified {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f0fdf4;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 14px;
    border: 1.5px solid #bbf7d0;
}
.cd-docs-certified-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cd-docs-certified-title {
    font-size: 14px;
    font-weight: 800;
    color: #15803d;
}
.cd-docs-certified-sub {
    font-size: 12.5px;
    color: #166534;
    margin-top: 3px;
}

.cd-docs-badge-info {
    background: #fef9c3;
    border: 1.5px solid #fde047;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 12.5px;
    color: #854d0e;
    margin-bottom: 12px;
}
.cd-docs-progress-bar {
    margin-bottom: 14px;
}
.cd-docs-pb-track {
    height: 8px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
}
.cd-docs-pb-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s;
}
.cd-docs-pb-label {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 5px;
    text-align: right;
}
.cd-docs-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 6px;
}
.cd-doc-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    transition: border-color 0.15s, background 0.15s;
}
.cd-doc-item.approved {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.cd-doc-item.rejected {
    border-color: #fca5a5;
    background: #fff1f2;
}
.cd-doc-item.pending {
    border-color: #fde68a;
    background: #fffbeb;
}
.cd-doc-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.cd-doc-info {
    flex: 1;
    min-width: 0;
}
.cd-doc-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.cd-doc-status-label {
    font-size: 12px;
    margin-top: 2px;
}
.cd-doc-status-label.approved {
    color: #16a34a;
}
.cd-doc-status-label.pending {
    color: #d97706;
}
.cd-doc-status-label.rejected {
    color: #dc2626;
}
.cd-doc-status-label.missing {
    color: var(--gr);
}
.cd-doc-reject-reason {
    font-size: 11.5px;
    color: #dc2626;
    margin-top: 3px;
}
.cd-doc-filename {
    font-size: 11.5px;
    color: var(--grm);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cd-doc-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}
.cd-docs-note {
    font-size: 12px;
    color: var(--grm);
    margin-top: 12px;
    background: var(--grl);
    border-radius: 8px;
    padding: 10px 12px;
}
.cd-upload-label {
    cursor: pointer;
}
.cd-upload-label.loading {
    opacity: 0.7;
    pointer-events: none;
}
.cd-mini-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cd-spin 0.7s linear infinite;
}
.cd-mini-spinner-dark {
    border-color: rgba(0, 0, 0, 0.15);
    border-top-color: var(--or);
}
@keyframes cd-spin {
    to {
        transform: rotate(360deg);
    }
}

/* --------------------------------------------------------------
   MOBILE FIXES — 350 px et plus, zéro débordement horizontal
-------------------------------------------------------------- */

/* Verrou global : rien ne peut dépasser la largeur de l'écran */
.cd-wrap {
    overflow-x: hidden;
    max-width: 100vw;
}
.cd-wrap *,
.cd-wrap *::before,
.cd-wrap *::after {
    box-sizing: border-box;
    min-width: 0; /* autorise les flex-items à rétrécir sous leur contenu */
}

/* -- TOPBAR --------------------------------------------------- */
@media (max-width: 599px) {
    .cd-topbar {
        padding: 0 10px;
        gap: 6px;
        height: 56px;
    }
    .cd-topbar-left {
        gap: 8px;
    }
    .cd-topbar-right {
        gap: 6px;
        flex-shrink: 0;
    }
    /* Titre tronqué */
    .cd-page-title {
        font-size: 13px;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    /* Bouton "+ Nouvelle mission" ? icône ronde seulement */
    .cd-topbar-right .cd-btn.cd-btn-orange {
        width: 36px;
        height: 36px;
        min-width: 36px;
        padding: 0;
        border-radius: 50%;
        font-size: 0; /* cache le texte */
        overflow: hidden;
    }
    .cd-topbar-right .cd-btn.cd-btn-orange::before {
        content: "+";
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
        color: #fff;
    }
    /* Avatar légèrement plus petit */
    .cd-avatar {
        width: 30px;
        height: 30px;
        font-size: 11px;
    }
}

/* -- CONTENU -------------------------------------------------- */
@media (max-width: 599px) {
    .cd-content {
        padding: 12px 10px;
        gap: 12px;
        width: 100%;
    }
}

/* -- BANNIÈRE PENDING ----------------------------------------- */
.cd-banner-pending {
    word-break: break-word;
    overflow-wrap: break-word;
}
.cd-banner-pending-title,
.cd-banner-pending-sub {
    word-break: break-word;
    overflow-wrap: break-word;
}
@media (max-width: 599px) {
    .cd-banner-pending {
        flex-direction: column;
        padding: 12px;
        gap: 10px;
    }
    .cd-banner-pending-left {
        gap: 10px;
        width: 100%;
    }
    .cd-banner-pending-icon {
        font-size: 22px;
        flex-shrink: 0;
    }
    .cd-banner-pending-title {
        font-size: 12.5px;
        line-height: 1.4;
    }
    .cd-banner-pending-sub {
        font-size: 11.5px;
        line-height: 1.5;
    }
    .cd-banner-progress-wrap {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-top: 8px;
    }
    .cd-banner-progress-track {
        max-width: 100%;
        width: 100%;
    }
    .cd-banner-btn {
        width: 100%;
        text-align: center;
        justify-content: center;
        font-size: 13px;
        padding: 10px 14px;
    }
}

/* -- BANNIÈRE VÉRIFIÉE ---------------------------------------- */
@media (max-width: 599px) {
    .cd-banner-verified {
        padding: 12px;
        gap: 10px;
    }
    .cd-verified-title {
        font-size: 13px;
    }
    .cd-verified-sub {
        font-size: 11.5px;
    }
}

/* -- KPIs ----------------------------------------------------- */
@media (max-width: 599px) {
    .cd-kpis {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .cd-kpi {
        padding: 12px 10px;
        border-radius: 12px;
    }
    .cd-kpi-icon {
        width: 24px;
        height: 24px;
        margin-right: 8px;
        font-size: 14px;
    }
    .cd-kpi-val {
        font-size: 22px;
    }
    .cd-kpi-label {
        font-size: 11px;
    }
}

/* -- LISTE MISSIONS ------------------------------------------- */
@media (max-width: 599px) {
    .cd-mission-list {
        padding: 6px 8px 10px;
    }
    .cd-mission-item {
        padding: 10px 8px;
        gap: 8px;
    }
    .cd-mission-title {
        font-size: 12.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        /* largeur calculée par rapport à la colonne gauche disponible */
        max-width: calc(100vw - 180px);
    }
    .cd-mission-meta {
        font-size: 11px;
    }
    .cd-mission-icon {
        font-size: 18px;
    }
}

/* -- CARDS ---------------------------------------------------- */
@media (max-width: 599px) {
    .cd-card-header {
        padding: 12px 14px 0;
    }
    .cd-card-header h3 {
        font-size: 13px;
    }
}

/* -- TABS ----------------------------------------------------- */
@media (max-width: 599px) {
    .cd-tabs {
        padding: 10px 12px 0;
        gap: 4px;
    }
    .cd-tab {
        font-size: 11.5px;
        padding: 4px 9px;
    }
}

/* -- NOTIFICATIONS dropdown ----------------------------------- */
@media (max-width: 599px) {
    .cd-notif-dropdown {
        right: -60px; /* re-centre si elle dépassait à droite */
        max-width: calc(100vw - 20px);
        left: auto;
    }
}
</style>
