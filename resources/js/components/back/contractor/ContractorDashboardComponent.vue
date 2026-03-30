<template>
    <div class="ctr-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="ctr-topbar">
            <div class="ctr-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="ctr-page-title">Mon espace prestataire</div>
                    <div class="ctr-page-sub">
                        {{ greeting }},
                        <strong>{{ contractor.full_name }}</strong>
                    </div>
                </div>
            </div>
            <div class="ctr-topbar-right">
                <!-- Toggle disponibilité -->
                <button
                    class="ctr-dispo-btn"
                    :class="{ available: contractorProfile.available }"
                    @click="toggleAvailability"
                    :disabled="availLoading"
                >
                    <span class="ctr-dispo-dot"></span>
                    {{
                        contractorProfile.available
                            ? "Disponible"
                            : "Indisponible"
                    }}
                </button>

                <!-- Cloche notifications -->
                <div class="ctr-notif-wrap" ref="notifWrap">
                    <button class="ctr-notif-btn" @click="toggleNotif">
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
                        <span class="ctr-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount > 9 ? "9+" : unreadCount }}
                        </span>
                    </button>
                    <div class="ctr-notif-dropdown" v-if="notifOpen">
                        <div class="ctr-notif-header">
                            <span>Notifications</span>
                            <button
                                class="ctr-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="ctr-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="ctr-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="ctr-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="ctr-notif-dot" v-if="!n.read"></div>
                            <div class="ctr-notif-content">
                                <div class="ctr-notif-title">{{ n.title }}</div>
                                <div class="ctr-notif-body">{{ n.body }}</div>
                                <div class="ctr-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ctr-avatar">{{ contractor.initials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="ctr-content">
            <!-- Bannière dossier en attente -->
            <div class="ctr-banner-pending" v-if="userStatus === 'pending'">
                <div class="ctr-banner-icon">⏳</div>
                <div>
                    <div class="ctr-banner-title">
                        Dossier en cours de validation
                    </div>
                    <div class="ctr-banner-sub">
                        Votre dossier a été soumis. L'équipe Resotravo le
                        vérifie sous 24-48h. Déposez tous vos documents pour
                        accélérer la validation.
                    </div>
                </div>
                <button
                    class="ctr-btn ctr-btn-orange ctr-btn-sm"
                    @click="scrollToDocs"
                >
                    Mes documents →
                </button>
            </div>

            <!-- Bannière documents incomplets -->
            <div
                class="ctr-banner-warning"
                v-if="
                    userStatus === 'pending' &&
                    docProgress.approved < docProgress.total
                "
            >
                <div class="ctr-banner-icon">📂</div>
                <div>
                    <div class="ctr-banner-title">
                        {{ docProgress.total - docProgress.approved }}
                        document(s) manquant(s) ou refusé(s)
                    </div>
                    <div class="ctr-banner-sub">
                        Le badge <strong>Profil Certifié</strong> sera accordé
                        uniquement après validation de <strong>tous</strong> vos
                        documents.
                    </div>
                </div>
            </div>

            <!-- Bannière profil certifié -->
            <div class="ctr-banner-certified" v-if="userStatus === 'approved'">
                <div class="ctr-banner-icon">✅</div>
                <div>
                    <div class="ctr-banner-title">
                        Profil certifié Resotravo
                    </div>
                    <div class="ctr-banner-sub">
                        Vous pouvez accepter des missions et apparaître dans les
                        recherches clients.
                        <span
                            v-if="
                                contractorProfile.accreditation &&
                                contractorProfile.accreditation !== 'none'
                            "
                        >
                            · Accréditation :
                            <strong>{{ accreditationLabel }}</strong>
                        </span>
                    </div>
                </div>
                <span class="ctr-badge-certified">🏅 Certifié</span>
            </div>

            <!-- KPIs -->
            <div class="ctr-kpis">
                <div
                    class="ctr-kpi"
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    :class="kpi.color"
                >
                    <div class="ctr-kpi-icon">{{ kpi.icon }}</div>
                    <div class="ctr-kpi-val">
                        <span
                            v-if="missionsLoading"
                            class="ctr-skeleton-val"
                        ></span>
                        <span v-else>{{ kpi.value }}</span>
                    </div>
                    <div class="ctr-kpi-label">{{ kpi.label }}</div>
                </div>
            </div>

            <!-- GRILLE MILIEU -->
            <div class="ctr-mid-grid">
                <!-- Missions -->
                <div class="ctr-card ctr-card-main">
                    <div class="ctr-card-header">
                        <h3>📋 Mes missions</h3>
                        <div class="ctr-tabs">
                            <button
                                class="ctr-tab"
                                v-for="t in tabs"
                                :key="t.key"
                                :class="{ active: tab === t.key }"
                                @click="tab = t.key"
                            >
                                {{ t.label }}
                                <span class="ctr-tab-count">{{
                                    countByTab(t.key)
                                }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="ctr-loading" v-if="missionsLoading">
                        <div
                            class="ctr-skeleton-row"
                            v-for="n in 3"
                            :key="n"
                        ></div>
                    </div>
                    <div class="ctr-alert-error" v-else-if="missionsError">
                        ⚠️ {{ missionsError }}
                        <button
                            class="ctr-btn ctr-btn-ghost ctr-btn-sm"
                            @click="fetchMissions"
                        >
                            Réessayer
                        </button>
                    </div>
                    <div
                        class="ctr-empty"
                        v-else-if="filteredMissions.length === 0"
                    >
                        <div class="ctr-empty-icon">📭</div>
                        <div class="ctr-empty-title">
                            Aucune mission{{
                                tab !== "all" ? " " + tabLabel : ""
                            }}
                        </div>
                        <div
                            class="ctr-empty-sub"
                            v-if="userStatus === 'pending'"
                        >
                            Votre dossier est en cours de validation
                        </div>
                        <div
                            class="ctr-empty-sub"
                            v-else-if="!contractorProfile.available"
                        >
                            Vous êtes indisponible — activez votre disponibilité
                            pour recevoir des missions
                        </div>
                    </div>

                    <div class="ctr-mission-list" v-else>
                        <div
                            class="ctr-mission-item"
                            v-for="m in filteredMissions"
                            :key="m.id"
                            @click="openMission(m)"
                        >
                            <div class="ctr-mission-left">
                                <div class="ctr-mission-icon">
                                    {{ serviceIcon(m.service) }}
                                </div>
                                <div>
                                    <div class="ctr-mission-title">
                                        {{ m.service }}
                                    </div>
                                    <div class="ctr-mission-meta">
                                        {{ m.client ? m.client.name : "—" }} ·
                                        {{ formatDate(m.created_at) }}
                                    </div>
                                    <div class="ctr-mission-addr">
                                        📍 {{ m.address }}
                                    </div>
                                </div>
                            </div>
                            <div class="ctr-mission-right">
                                <span
                                    class="ctr-badge"
                                    :class="badgeClass(m.status)"
                                    >{{ labelOf(m) }}</span
                                >
                                <div class="ctr-mission-price">
                                    {{
                                        m.total_amount
                                            ? formatPrice(m.total_amount * 0.9)
                                            : "—"
                                    }}
                                </div>
                                <div
                                    class="ctr-msg-unread"
                                    v-if="unreadByMission[m.id]"
                                >
                                    💬
                                    <span
                                        >{{ unreadByMission[m.id] }} non lu{{
                                            unreadByMission[m.id] > 1 ? "s" : ""
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel droit -->
                <div class="ctr-side-col">
                    <!-- Profil rapide -->
                    <div class="ctr-card">
                        <div class="ctr-card-header">
                            <h3>👤 Mon profil</h3>
                        </div>
                        <div class="ctr-profile-info">
                            <div
                                class="ctr-profile-av"
                                :class="{
                                    certified: userStatus === 'approved',
                                }"
                            >
                                {{ contractor.initials }}
                                <span
                                    class="ctr-certified-dot"
                                    v-if="userStatus === 'approved'"
                                    >✓</span
                                >
                            </div>
                            <div>
                                <div class="ctr-profile-name">
                                    {{ contractor.full_name }}
                                </div>
                                <div class="ctr-profile-spec">
                                    {{ contractorProfile.specialty }}
                                </div>
                                <div class="ctr-profile-meta">
                                    📞 {{ contractorProfile.phone }}
                                </div>
                                <div class="ctr-profile-meta">
                                    📍 {{ contractorProfile.intervention_zone }}
                                </div>
                                <div
                                    class="ctr-profile-meta"
                                    v-if="contractorProfile.average_rating > 0"
                                >
                                    ⭐ {{ contractorProfile.average_rating }} /
                                    5 ({{ contractorProfile.reviews_count }}
                                    avis)
                                </div>
                            </div>
                        </div>

                        <!-- Accréditation -->
                        <div
                            class="ctr-accred-row"
                            v-if="
                                contractorProfile.accreditation &&
                                contractorProfile.accreditation !== 'none'
                            "
                        >
                            <span class="ctr-accred-badge"
                                >🏅 {{ accreditationLabel }}</span
                            >
                        </div>
                        <div
                            class="ctr-accred-row ctr-accred-none"
                            v-else-if="userStatus === 'approved'"
                        >
                            <span class="ctr-accred-none-text"
                                >⚠️ Aucune accréditation — contactez
                                l'admin</span
                            >
                        </div>

                        <!-- Progression dossier -->
                        <div
                            class="ctr-doc-progress"
                            v-if="userStatus === 'pending'"
                        >
                            <div class="ctr-doc-progress-label">
                                Documents : {{ docProgress.approved }}/{{
                                    docProgress.total
                                }}
                                validés
                            </div>
                            <div class="ctr-doc-track">
                                <div
                                    class="ctr-doc-fill"
                                    :style="{
                                        width: docProgress.percentage + '%',
                                    }"
                                ></div>
                            </div>
                            <div class="ctr-doc-progress-note">
                                Le badge <strong>Certifié</strong> nécessite
                                {{ docProgress.total }} documents validés par
                                l'admin.
                            </div>
                        </div>
                    </div>

                    <!-- ── DISPONIBILITÉ & HORAIRES ── -->
                    <div class="ctr-card">
                        <div class="ctr-card-header">
                            <h3>📅 Disponibilité</h3>
                            <button
                                class="ctr-btn ctr-btn-ghost ctr-btn-sm"
                                @click="openScheduleModal"
                            >
                                ✏️ Gérer mes horaires
                            </button>
                        </div>
                        <div class="ctr-dispo-info">
                            <div class="ctr-dispo-row">
                                <span>Statut</span>
                                <span
                                    class="ctr-dispo-status"
                                    :class="{
                                        available: contractorProfile.available,
                                    }"
                                >
                                    {{
                                        contractorProfile.available
                                            ? "🟢 Disponible"
                                            : "🔴 Indisponible"
                                    }}
                                </span>
                            </div>
                            <div
                                class="ctr-dispo-row"
                                v-if="contractorProfile.start_time"
                            >
                                <span>Horaires</span>
                                <strong
                                    >{{ contractorProfile.start_time }} —
                                    {{ contractorProfile.end_time }}</strong
                                >
                            </div>
                            <div class="ctr-dispo-row">
                                <span>Rayon</span>
                                <strong
                                    >{{
                                        contractorProfile.radius_km ?? 10
                                    }}
                                    km</strong
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="ctr-card" ref="docsSection">
                <div class="ctr-card-header">
                    <h3>📂 Mes documents</h3>
                    <div class="ctr-doc-header-right">
                        <span
                            class="ctr-doc-summary"
                            :class="{
                                complete: docProgress.percentage === 100,
                            }"
                        >
                            {{ docProgress.approved }}/{{ docProgress.total }}
                            validés
                        </span>
                        <span
                            class="ctr-certified-pill"
                            v-if="userStatus === 'approved'"
                            >🏅 Profil certifié</span
                        >
                    </div>
                </div>

                <div
                    class="ctr-docs-certified"
                    v-if="userStatus === 'approved'"
                >
                    <div class="ctr-docs-certified-icon">✅</div>
                    <div>
                        <div class="ctr-docs-certified-title">
                            Tous vos documents ont été validés par Resotravo !
                        </div>
                        <div class="ctr-docs-certified-sub">
                            Votre profil est certifié. Vous pouvez accepter des
                            missions.
                        </div>
                    </div>
                </div>

                <div
                    class="ctr-docs-badge-info"
                    v-if="userStatus !== 'approved'"
                >
                    ℹ️ Le badge <strong>Profil Certifié</strong> est accordé
                    uniquement après que l'équipe Resotravo a validé
                    <strong>l'ensemble</strong> de vos documents.
                </div>

                <div
                    class="ctr-docs-progress-bar"
                    v-if="userStatus !== 'approved'"
                >
                    <div class="ctr-docs-pb-track">
                        <div
                            class="ctr-docs-pb-fill"
                            :style="{ width: docProgress.percentage + '%' }"
                        ></div>
                    </div>
                    <div class="ctr-docs-pb-label">
                        {{ docProgress.percentage }}% validé par l'admin
                    </div>
                </div>

                <div class="ctr-docs-list">
                    <div
                        class="ctr-doc-item"
                        v-for="doc in localDocuments"
                        :key="doc.type"
                        :class="doc.status"
                    >
                        <div class="ctr-doc-icon">{{ doc.icon }}</div>
                        <div class="ctr-doc-info">
                            <div class="ctr-doc-name">{{ doc.label }}</div>
                            <div
                                class="ctr-doc-status-label"
                                :class="doc.status"
                            >
                                {{ docStatusLabel(doc.status) }}
                            </div>
                            <div
                                class="ctr-doc-reject-reason"
                                v-if="
                                    doc.status === 'rejected' &&
                                    doc.reject_reason
                                "
                            >
                                Motif : {{ doc.reject_reason }}
                            </div>
                            <div class="ctr-doc-filename" v-if="doc.filename">
                                📎 {{ doc.filename }}
                            </div>
                        </div>
                        <div class="ctr-doc-actions">
                            <label
                                class="ctr-btn ctr-btn-orange ctr-btn-sm ctr-upload-label"
                                v-if="
                                    doc.status === 'missing' ||
                                    doc.status === 'rejected'
                                "
                                :class="{ loading: doc.uploading }"
                            >
                                <div
                                    class="ctr-mini-spinner"
                                    v-if="doc.uploading"
                                ></div>
                                <span v-else>{{
                                    doc.status === "missing"
                                        ? "+ Déposer"
                                        : "↻ Remplacer"
                                }}</span>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    style="display: none"
                                    @change="uploadDocument(doc, $event)"
                                    :disabled="doc.uploading"
                                />
                            </label>
                            <label
                                class="ctr-btn ctr-btn-ghost ctr-btn-sm ctr-upload-label"
                                v-if="
                                    doc.status === 'pending' ||
                                    doc.status === 'approved'
                                "
                                :class="{ loading: doc.uploading }"
                            >
                                <div
                                    class="ctr-mini-spinner"
                                    v-if="doc.uploading"
                                ></div>
                                <span v-else>↻</span>
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

                <div class="ctr-docs-note" v-if="userStatus === 'pending'">
                    ℹ️ Formats acceptés : PDF, JPG, PNG — 5 Mo max par document.
                    Délai de vérification : <strong>24-48h</strong> après
                    soumission complète.
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL MISSION ══════════════ -->
        <div
            class="ctr-modal-overlay"
            v-if="activeMission"
            @click.self="activeMission = null"
        >
            <div class="ctr-modal">
                <div class="ctr-modal-header">
                    <div>
                        <h3>{{ activeMission.service }}</h3>
                        <div class="ctr-modal-sub">
                            Mission #{{ activeMission.id }} ·
                            {{ formatDate(activeMission.created_at) }}
                        </div>
                    </div>
                    <button
                        class="ctr-modal-close"
                        @click="activeMission = null"
                    >
                        &#215;
                    </button>
                </div>
                <div class="ctr-modal-body">
                    <div class="ctr-detail-row">
                        <span>Statut</span>
                        <span
                            class="ctr-badge"
                            :class="badgeClass(activeMission.status)"
                            >{{ labelOf(activeMission) }}</span
                        >
                    </div>
                    <div class="ctr-detail-row">
                        <span>Client</span
                        ><strong>{{
                            activeMission.client
                                ? activeMission.client.name
                                : "—"
                        }}</strong>
                    </div>
                    <div class="ctr-detail-row">
                        <span>Adresse</span
                        ><strong>{{ activeMission.address }}</strong>
                    </div>
                    <div class="ctr-detail-row">
                        <span>Description</span
                        ><strong>{{ activeMission.description }}</strong>
                    </div>
                    <!-- Photos de la mission -->
                    <div class="ctr-detail-row ctr-detail-photos" v-if="activeMission.images && activeMission.images.length">
                        <span>Photos</span>
                        <div class="ctr-dash-images">
                            <img
                                v-for="(url, i) in activeMission.images"
                                :key="i"
                                :src="url"
                                @click="dashLightbox = url"
                                class="ctr-dash-img"
                            />
                        </div>
                    </div>
                    <div
                        class="ctr-detail-row"
                        v-if="activeMission.total_amount"
                    >
                        <span>Votre part (90%)</span>
                        <strong>{{
                            formatPrice(activeMission.total_amount * 0.9)
                        }}</strong>
                    </div>
                    <div
                        class="ctr-detail-row"
                        v-if="activeMission.location_type"
                    >
                        <span>Type</span>
                        <strong>{{
                            activeMission.location_type === "business"
                                ? "🏢 Entreprise"
                                : "🏠 Domicile"
                        }}</strong>
                    </div>

                    <!-- Barre progression -->

                    <!-- ── ACTION : Accepter ou refuser ── -->
                    <div
                        class="ctm-action-block ctm-action-new"
                        v-if="activeMission.status === 'assigned'"
                    >
                        <div class="ctm-action-new-icon">📬</div>
                        <div class="ctm-action-new-title">
                            Nouvelle mission proposée
                        </div>
                        <div class="ctm-action-new-sub">
                            Vous avez 5 minutes pour répondre.
                        </div>
                        <div class="ctm-action-row">
                            <button
                                class="ctm-btn ctm-btn-red"
                                @click="openRefuseModal(activeMission)"
                                :disabled="actionLoading"
                            >
                                ✗ Refuser
                            </button>
                            <button
                                class="ctm-btn ctm-btn-green"
                                @click="updateStatus(activeMission, 'accepted')"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="ctm-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>✓ Accepter</span>
                            </button>
                        </div>
                    </div>

                    <!-- ── En route ── -->
                    <div
                        class="ctm-action-block"
                        v-if="
                            ['accepted', 'contact_made'].includes(
                                activeMission.status,
                            )
                        "
                    >
                        <p>
                            🚗 Prêt à partir ? Activez le suivi pour que le
                            client vous voie.
                        </p>
                        <button
                            class="ctm-btn ctm-btn-orange ctm-btn-full"
                            @click="updateStatus(activeMission, 'on_the_way')"
                            :disabled="actionLoading"
                        >
                            <div class="ctm-spinner" v-if="actionLoading"></div>
                            <span v-else>🚗 Je suis en route →</span>
                        </button>
                    </div>

                    <!-- ── Arrivé sur place ── -->
                    <div
                        class="ctm-action-block"
                        v-if="
                            ['on_the_way', 'tracking'].includes(
                                activeMission.status,
                            )
                        "
                    >
                        <p>📍 Êtes-vous arrivé sur place ?</p>
                        <button
                            class="ctm-btn ctm-btn-orange ctm-btn-full"
                            @click="updateStatus(activeMission, 'in_progress')"
                            :disabled="actionLoading"
                        >
                            <div class="ctm-spinner" v-if="actionLoading"></div>
                            <span v-else>📍 Je suis arrivé sur place</span>
                        </button>
                    </div>

                    <!-- ── Devis (saisie / révision) ── -->
                    <div
                        class="ctm-action-block ctm-action-quote"
                        v-if="
                            activeMission.status === 'in_progress' ||
                            (activeMission.status === 'quote_submitted' &&
                                activeMission.quote?.status === 'rejected')
                        "
                    >
                        <div class="ctm-action-quote-icon">📄</div>
                        <div class="ctm-action-quote-title">
                            {{
                                activeMission.quote?.status === "rejected"
                                    ? "Devis refusé par le client"
                                    : "Saisir le devis"
                            }}
                        </div>
                        <div
                            class="ctm-action-quote-sub"
                            v-if="
                                activeMission.quote?.status === 'rejected' &&
                                activeMission.reported_issue
                            "
                        >
                            ❌ Motif :
                            <em>« {{ activeMission.reported_issue }} »</em>
                        </div>
                        <div
                            class="ctm-action-quote-sub"
                            v-else-if="
                                activeMission.quote?.status === 'rejected'
                            "
                        >
                            ❌ Le client a refusé votre devis. Vous pouvez le
                            réviser ou abandonner.
                        </div>
                        <div class="ctm-action-quote-sub" v-else>
                            Diagnostiquez la situation et détaillez les pièces
                            nécessaires.
                        </div>
                        <button
                            class="ctm-btn ctm-btn-orange ctm-btn-full"
                            @click="openQuoteModal(activeMission)"
                            style="margin-bottom: 8px"
                        >
                            📄
                            {{
                                activeMission.quote?.status === "rejected"
                                    ? "Réviser le devis →"
                                    : "Saisir le devis →"
                            }}
                        </button>
                        <button
                            v-if="activeMission.quote?.status === 'rejected'"
                            class="ctm-btn ctm-btn-ghost ctm-btn-full"
                            @click="openAbandonModal(activeMission)"
                            :disabled="actionLoading"
                        >
                            🚪 Abandonner la mission
                        </button>
                    </div>

                    <!-- ── Devis soumis en attente ── -->
                    <div
                        class="ctm-action-block ctm-action-wait"
                        v-if="
                            activeMission.status === 'quote_submitted' &&
                            activeMission.quote?.status !== 'rejected'
                        "
                    >
                        <div class="ctm-action-wait-icon">⏳</div>
                        <div>
                            <div class="ctm-action-wait-title">
                                Devis en attente d'approbation
                            </div>
                            <div class="ctm-action-wait-sub">
                                Le client doit approuver votre devis pour
                                démarrer les travaux.
                            </div>
                        </div>
                    </div>

                    <!-- ── Travaux terminés ── -->
                    <div
                        class="ctm-action-block"
                        v-if="activeMission.status === 'order_placed'"
                    >
                        <p>
                            🔨 Le client a approuvé votre devis. Marquez les
                            travaux comme terminés.
                        </p>
                        <button
                            class="ctm-btn ctm-btn-orange ctm-btn-full"
                            @click="
                                updateStatus(activeMission, 'awaiting_confirm')
                            "
                            :disabled="actionLoading"
                        >
                            <div class="ctm-spinner" v-if="actionLoading"></div>
                            <span v-else>✓ Travaux terminés</span>
                        </button>
                    </div>

                    <!-- ── Att. confirmation ── -->
                    <div
                        class="ctm-action-block ctm-action-wait"
                        v-if="activeMission.status === 'awaiting_confirm'"
                    >
                        <div class="ctm-action-wait-icon">⏳</div>
                        <div>
                            <div class="ctm-action-wait-title">
                                En attente du client
                            </div>
                            <div class="ctm-action-wait-sub">
                                Le client doit confirmer la fin des travaux pour
                                déclencher votre paiement.
                            </div>
                        </div>
                    </div>

                    <!-- ── Terminée / clôturée ── -->
                    <div
                        class="ctm-action-block ctm-action-done"
                        v-if="
                            ['completed', 'closed'].includes(
                                activeMission.status,
                            )
                        "
                    >
                        <div class="ctm-action-done-icon">🎉</div>
                        <div>
                            <div class="ctm-action-done-title">
                                Mission
                                {{
                                    activeMission.status === "closed"
                                        ? "clôturée"
                                        : "terminée"
                                }}
                            </div>
                            <div
                                class="ctm-action-done-sub"
                                v-if="activeMission.status === 'closed'"
                            >
                                Votre paiement de
                                {{
                                    formatPrice(
                                        activeMission.total_amount * 0.9,
                                    )
                                }}
                                a été effectué.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ctr-modal-footer">
                    <button
                        class="ctm-btn ctm-btn-ghost"
                        @click="activeMission = null"
                    >
                        Fermer
                    </button>
                    <button
                        class="ctm-btn ctm-btn-chat"
                        @click="chatMissionId = activeMission.id"
                        v-if="activeMission.status !== 'pending'"
                    >
                        💬 Messages
                        <span class="ctr-chat-badge" v-if="unreadByMission[activeMission.id] > 0">{{
                            unreadByMission[activeMission.id]
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REFUS MISSION ══════════════ -->
        <div
            class="ctr-modal-overlay"
            v-if="refuseModal.visible"
            @click.self="refuseModal.visible = false"
        >
            <div class="ctr-modal">
                <div class="ctr-modal-header">
                    <div>
                        <h3>Refuser la mission</h3>
                        <div class="ctr-modal-sub">
                            {{ refuseModal.mission?.service }}
                        </div>
                    </div>
                    <button
                        class="ctr-modal-close"
                        @click="refuseModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="ctr-modal-body">
                    <p class="ctr-refuse-info">
                        ⚠️ En refusant cette mission, elle sera reattribuée à un
                        autre prestataire disponible. Cette action est
                        irréversible.
                    </p>
                    <label class="ctr-form-label"
                        >Motif du refus
                        <span class="ctr-required">*</span></label
                    >
                    <div class="ctr-refuse-options">
                        <label
                            class="ctr-radio-opt"
                            v-for="opt in refuseOptions"
                            :key="opt.value"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="refuseModal.reason"
                            />
                            <span>{{ opt.label }}</span>
                        </label>
                    </div>
                    <input
                        class="ctr-input"
                        type="text"
                        v-model="refuseModal.customReason"
                        v-if="refuseModal.reason === 'other'"
                        placeholder="Précisez votre motif…"
                    />
                </div>
                <div class="ctr-modal-footer">
                    <button
                        class="ctr-btn ctr-btn-ghost"
                        @click="refuseModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="ctr-btn ctr-btn-red"
                        @click="confirmRefuse"
                        :disabled="!refuseModal.reason || refuseModal.loading"
                    >
                        <div
                            class="ctr-spinner"
                            v-if="refuseModal.loading"
                        ></div>
                        <span v-else>✗ Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL HORAIRES ══════════════ -->
        <div
            class="ctr-modal-overlay"
            v-if="scheduleModal.visible"
            @click.self="scheduleModal.visible = false"
        >
            <div class="ctr-modal">
                <div class="ctr-modal-header">
                    <div>
                        <h3>⏰ Gérer mes horaires</h3>
                        <div class="ctr-modal-sub">
                            Définissez vos créneaux de disponibilité
                        </div>
                    </div>
                    <button
                        class="ctr-modal-close"
                        @click="scheduleModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="ctr-modal-body">
                    <!-- Toggle disponibilité -->
                    <div class="ctr-schedule-toggle-row">
                        <div>
                            <div class="ctr-schedule-label">
                                Statut de disponibilité
                            </div>
                            <div class="ctr-schedule-sub">
                                Activez pour recevoir des missions
                            </div>
                        </div>
                        <button
                            class="ctr-toggle-btn"
                            :class="{ on: scheduleModal.available }"
                            @click="
                                scheduleModal.available =
                                    !scheduleModal.available
                            "
                        >
                            <span class="ctr-toggle-knob"></span>
                        </button>
                    </div>

                    <!-- Horaires -->
                    <div class="ctr-schedule-section">
                        <div class="ctr-schedule-section-title">
                            Horaires de travail
                        </div>
                        <div class="ctr-time-row">
                            <div class="ctr-time-field">
                                <label class="ctr-form-label"
                                    >Heure de début</label
                                >
                                <input
                                    type="time"
                                    class="ctr-input"
                                    v-model="scheduleModal.start_time"
                                />
                            </div>
                            <div class="ctr-time-sep">—</div>
                            <div class="ctr-time-field">
                                <label class="ctr-form-label"
                                    >Heure de fin</label
                                >
                                <input
                                    type="time"
                                    class="ctr-input"
                                    v-model="scheduleModal.end_time"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Jours de travail -->
                    <div class="ctr-schedule-section">
                        <div class="ctr-schedule-section-title">
                            Jours travaillés
                        </div>
                        <div class="ctr-days-grid">
                            <label
                                class="ctr-day-chip"
                                v-for="d in weekDays"
                                :key="d.value"
                                :class="{
                                    active: scheduleModal.working_days.includes(
                                        d.value,
                                    ),
                                }"
                            >
                                <input
                                    type="checkbox"
                                    :value="d.value"
                                    v-model="scheduleModal.working_days"
                                    style="display: none"
                                />
                                {{ d.label }}
                            </label>
                        </div>
                    </div>

                    <!-- Rayon d'intervention -->
                    <div class="ctr-schedule-section">
                        <div class="ctr-schedule-section-title">
                            Rayon d'intervention
                        </div>
                        <div class="ctr-radius-row">
                            <input
                                type="range"
                                min="1"
                                max="50"
                                v-model.number="scheduleModal.radius_km"
                                class="ctr-range"
                            />
                            <span class="ctr-radius-val"
                                >{{ scheduleModal.radius_km }} km</span
                            >
                        </div>
                    </div>
                </div>
                <div class="ctr-modal-footer">
                    <button
                        class="ctr-btn ctr-btn-ghost"
                        @click="scheduleModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="ctr-btn ctr-btn-orange"
                        @click="saveSchedule"
                        :disabled="scheduleModal.loading"
                    >
                        <div
                            class="ctr-spinner"
                            v-if="scheduleModal.loading"
                        ></div>
                        <span v-else>💾 Enregistrer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ CHAT MODAL ══════════════ -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- Lightbox photos mission -->
        <div class="ctr-dash-lightbox" v-if="dashLightbox" @click="dashLightbox = null">
            <img :src="dashLightbox" />
        </div>

        <!-- WIP MODAL -->
        <div
            class="ctr-wip-overlay"
            v-if="wipVisible"
            @click="wipVisible = false"
        >
            <div class="ctr-wip-modal" @click.stop>
                <div class="ctr-wip-icon">🚧</div>
                <h3>Fonctionnalité en cours de développement</h3>
                <p class="ctr-wip-feature">{{ wipFeature }}</p>
                <p>Cette section sera disponible très prochainement.</p>
                <button
                    class="ctr-btn ctr-btn-orange"
                    @click="wipVisible = false"
                    style="width: 100%"
                >
                    Compris →
                </button>
            </div>
        </div>

        <!-- ══════════════ MODAL DEVIS ══════════════ -->
        <div
            class="ctm-modal-overlay"
            v-if="quoteModal.visible"
            @click.self="closeQuoteModal"
        >
            <div class="ctm-modal ctm-modal-quote">
                <!-- Header -->
                <div class="ctm-modal-header">
                    <div>
                        <h3>
                            {{
                                quoteModal.isRevision
                                    ? "Réviser le devis"
                                    : "Saisir le devis"
                            }}
                        </h3>
                        <div class="ctm-modal-subtitle">
                            Mission #{{ quoteModal.mission?.id }} ·
                            {{ quoteModal.mission?.service }}
                        </div>
                    </div>
                    <button class="ctm-modal-close" @click="closeQuoteModal">
                        &#215;
                    </button>
                </div>

                <div class="ctm-modal-body ctm-modal-body-quote">
                    <!-- Diagnostic -->
                    <div class="ctm-quote-field">
                        <label class="ctm-quote-label"
                            >🔍 Diagnostic
                            <span class="ctm-optional">(optionnel)</span></label
                        >
                        <textarea
                            class="ctm-quote-textarea"
                            v-model="quoteModal.diagnosis"
                            placeholder="Décrivez ce que vous avez constaté sur place…"
                            rows="3"
                        ></textarea>
                    </div>

                    <!-- Lignes pièces/matériaux -->
                    <div class="ctm-quote-section">
                        <div class="ctm-quote-section-header">
                            <span class="ctm-quote-section-title"
                                >🔩 Pièces & matériaux</span
                            >
                            <button
                                class="ctm-add-line-btn"
                                @click="addPartLine"
                            >
                                + Ajouter une ligne
                            </button>
                        </div>

                        <div class="ctm-quote-table-head">
                            <span class="col-desc">Désignation</span>
                            <span class="col-qty">Qté</span>
                            <span class="col-price">Prix unit. (FCFA)</span>
                            <span class="col-total">Total</span>
                            <span class="col-del"></span>
                        </div>

                        <div
                            v-if="quoteModal.partLines.length === 0"
                            class="ctm-quote-empty-lines"
                        >
                            Aucune pièce ajoutée. Cliquez sur « + Ajouter une
                            ligne ».
                        </div>

                        <div
                            class="ctm-quote-line"
                            v-for="(line, idx) in quoteModal.partLines"
                            :key="line._id"
                        >
                            <input
                                class="ctm-quote-input col-desc"
                                type="text"
                                v-model="line.description"
                                placeholder="Ex : Robinet chromé DN15…"
                            />
                            <input
                                class="ctm-quote-input col-qty"
                                type="number"
                                v-model.number="line.quantity"
                                min="0.01"
                                step="1"
                                placeholder="1"
                            />
                            <input
                                class="ctm-quote-input col-price"
                                type="number"
                                v-model.number="line.unit_price"
                                min="0"
                                step="100"
                                placeholder="0"
                            />
                            <div class="col-total ctm-quote-line-total">
                                {{ formatPrice(lineTotal(line)) }}
                            </div>
                            <button
                                class="ctm-quote-del-btn"
                                @click="removePartLine(idx)"
                                title="Supprimer"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Main d'œuvre -->
                    <div class="ctm-quote-section">
                        <div class="ctm-quote-section-header">
                            <span class="ctm-quote-section-title"
                                >🛠️ Main d'œuvre</span
                            >
                        </div>
                        <div class="ctm-quote-labor-row">
                            <div class="ctm-quote-field ctm-field-flex">
                                <label class="ctm-quote-label"
                                    >Description</label
                                >
                                <input
                                    class="ctm-quote-input"
                                    type="text"
                                    v-model="quoteModal.labor.description"
                                    placeholder="Ex : Pose et raccordement robinet…"
                                />
                            </div>
                            <div class="ctm-quote-field ctm-field-amount">
                                <label class="ctm-quote-label"
                                    >Montant (FCFA)</label
                                >
                                <input
                                    class="ctm-quote-input"
                                    type="number"
                                    v-model.number="quoteModal.labor.unit_price"
                                    min="0"
                                    step="100"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Total récapitulatif -->
                    <div class="ctm-quote-recap">
                        <div class="ctm-quote-recap-row">
                            <span>Pièces & matériaux</span>
                            <strong>{{ formatPrice(partsSubtotal) }}</strong>
                        </div>
                        <div class="ctm-quote-recap-row">
                            <span>Main d'œuvre</span>
                            <strong>{{
                                formatPrice(quoteModal.labor.unit_price || 0)
                            }}</strong>
                        </div>
                        <div class="ctm-quote-recap-total">
                            <span>Total devis</span>
                            <strong>{{ formatPrice(quoteTotal) }}</strong>
                        </div>
                        <div class="ctm-quote-recap-net">
                            Votre part (90%) :
                            <strong>{{ formatPrice(quoteTotal * 0.9) }}</strong>
                        </div>
                    </div>

                    <!-- Erreur -->
                    <div class="ctm-quote-error" v-if="quoteModal.error">
                        ⚠️ {{ quoteModal.error }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="ctm-modal-footer ctm-modal-footer-quote">
                    <button
                        class="ctm-btn ctm-btn-ghost"
                        @click="closeQuoteModal"
                    >
                        Annuler
                    </button>
                    <button
                        class="ctm-btn ctm-btn-ghost ctm-btn-draft"
                        @click="submitQuote('draft')"
                        :disabled="quoteModal.loading || !quoteIsValid"
                    >
                        <div
                            class="ctm-spinner ctm-spinner-dark"
                            v-if="quoteModal.loading === 'draft'"
                        ></div>
                        <span v-else>💾 Enregistrer brouillon</span>
                    </button>
                    <button
                        class="ctm-btn ctm-btn-orange"
                        @click="submitQuote('submit')"
                        :disabled="quoteModal.loading || !quoteIsValid"
                    >
                        <div
                            class="ctm-spinner"
                            v-if="quoteModal.loading === 'submit'"
                        ></div>
                        <span v-else>📤 Soumettre au client</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL ABANDON MISSION ══════════════ -->
        <div
            class="ctm-modal-overlay"
            v-if="abandonModal.visible"
            @click.self="abandonModal.visible = false"
        >
            <div class="ctm-modal">
                <div class="ctm-modal-header">
                    <h3>🚪 Abandonner la mission</h3>
                    <button
                        class="ctm-modal-close"
                        @click="abandonModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="ctm-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            margin-bottom: 14px;
                            line-height: 1.6;
                        "
                    >
                        Vous confirmez ne plus pouvoir réaliser cette mission
                        suite au refus du devis. Elle sera signalée à l'équipe
                        Resotravo.
                    </p>
                    <label
                        class="ctm-form-label"
                        style="
                            font-size: 13px;
                            font-weight: 700;
                            color: var(--dk);
                            display: block;
                            margin-bottom: 6px;
                        "
                    >
                        Motif <span style="color: #dc2626">*</span>
                    </label>
                    <textarea
                        class="ctm-input"
                        style="
                            width: 100%;
                            border: 2px solid var(--grl);
                            border-radius: 9px;
                            padding: 9px 13px;
                            font-size: 13px;
                            font-family: &quot;Poppins&quot;, sans-serif;
                            resize: vertical;
                            box-sizing: border-box;
                        "
                        v-model="abandonModal.reason"
                        rows="3"
                        placeholder="Ex : Désaccord sur le prix des pièces, hors de mes compétences…"
                    ></textarea>
                </div>
                <div class="ctm-modal-footer">
                    <button
                        class="ctm-btn ctm-btn-ghost"
                        @click="abandonModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="ctm-btn ctm-btn-red"
                        @click="confirmAbandon"
                        :disabled="
                            !abandonModal.reason.trim() || abandonModal.loading
                        "
                    >
                        <div
                            class="ctm-spinner"
                            v-if="abandonModal.loading"
                        ></div>
                        <span v-else>🚪 Confirmer l'abandon</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="ctr-toast-container">
            <div
                class="ctr-toast"
                :class="t.type"
                v-for="t in toasts"
                :key="t.id"
            >
                {{ t.message }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ContractorDashboardComponent",

    props: {
        user: {
            type: Object,
            default: () => ({
                name: "",
                email: "",
                role: "contractor",
                status: "pending",
            }),
        },
        contractorProfile: {
            type: Object,
            default: () => ({
                first_name: "",
                last_name: "",
                phone: "",
                city: "Cotonou",
                specialty: "",
                specialties: [],
                intervention_zone: "",
                experience_years: 0,
                bio: "",
                accreditation: "none",
                available: true,
                start_time: "08:00",
                end_time: "18:00",
                radius_km: 10,
                total_missions: 0,
                completed_missions: 0,
                average_rating: 0,
                reviews_count: 0,
            }),
        },
        docProgressData: {
            type: Object,
            default: () => ({ approved: 0, total: 5, percentage: 0 }),
        },
        documentsData: { type: Array, default: () => [] },
        routes: {
            type: Object,
            default: () => ({
                missions_index: "/contractor/missions",
                missions_quote_store: "/contractor/missions/{id}/quote",
                missions_show: "/contractor/missions/{id}",
                missions_status: "/contractor/missions/{id}/status",
                notifications: "/notifications",
                notifications_read: "/notifications/{id}/read",
                notifications_all: "/notifications/read-all",
                availability: "/contractor/disponibilite",
                documents_upload: "/documents/upload",
                documents_index: "/documents",
                profil: "/contractor/profil",
                conversations_mission: "/conversations/mission/{id}",
                conversations_messages: "/conversations/{id}/messages",
                conversations_send: "/conversations/{id}/messages",
                conversations_attach: "/conversations/{id}/attachment",
                conversations_read: "/conversations/{id}/read",
            }),
        },
    },

    data() {
        return {
            tab: "all",
            activeMission: null,
            wipVisible: false,
            wipFeature: "",
            sidebarOpen: false,
            chatMissionId: null,
            chatUnread: 0,
            unreadByMission: {}, // { missionId: count }  // ID mission dont le chat est ouvert
            dashLightbox: null,
            toasts: [],
            toastId: 0,
            actionLoading: false,

            // ── Quote modal ──────────────────────────────────────
            quoteModal: {
                visible: false,
                mission: null,
                isRevision: false,
                diagnosis: "",
                partLines: [],
                labor: { description: "Main d'œuvre", unit_price: 0 },
                loading: false,
                error: null,
                _lineId: 0,
            },
            abandonModal: {
                visible: false,
                mission: null,
                reason: "",
                loading: false,
            },
            availLoading: false,
            userStatus: "",

            missions: [],
            missionsLoading: true,
            missionsError: null,

            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
            notifInterval: null,

            localDocuments: [],
            localDocProgress: null,

            // Modal refus mission
            refuseModal: {
                visible: false,
                mission: null,
                reason: "",
                customReason: "",
                loading: false,
            },
            refuseOptions: [
                { value: "zone", label: "📍 Hors de ma zone d'intervention" },
                { value: "specialty", label: "🔧 Hors de ma spécialité" },
                { value: "busy", label: "📆 Je suis déjà occupé" },
                {
                    value: "distance",
                    label: "🚗 Trop loin de ma position actuelle",
                },
                { value: "other", label: "✏️ Autre motif" },
            ],

            // Modal horaires
            scheduleModal: {
                visible: false,
                available: true,
                start_time: "08:00",
                end_time: "18:00",
                working_days: [],
                radius_km: 10,
                loading: false,
            },
            weekDays: [
                { value: "monday", label: "Lun" },
                { value: "tuesday", label: "Mar" },
                { value: "wednesday", label: "Mer" },
                { value: "thursday", label: "Jeu" },
                { value: "friday", label: "Ven" },
                { value: "saturday", label: "Sam" },
                { value: "sunday", label: "Dim" },
            ],

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "En cours" },
                { key: "assigned", label: "Proposées" },
                { key: "closed", label: "Terminées" },
                { key: "cancelled", label: "Annulées" },
            ],
        };
    },

    computed: {
        contractor() {
            const p = this.contractorProfile;
            return {
                ...p,
                full_name:
                    `${p.first_name} ${p.last_name}`.trim() || this.user.name,
                initials:
                    (
                        (p.first_name?.[0] ?? "") + (p.last_name?.[0] ?? "")
                    ).toUpperCase() || "PR",
            };
        },

        greeting() {
            const h = new Date().getHours();
            if (h < 12) return "Bonjour";
            if (h < 18) return "Bon après-midi";
            return "Bonsoir";
        },

        accreditationLabel() {
            return (
                {
                    none: "Aucune",
                    home: "🏠 Domicile",
                    business: "🏢 Entreprise",
                    both: "🏠 Domicile + 🏢 Entreprise",
                }[this.contractorProfile.accreditation] ?? "Aucune"
            );
        },

        docProgress() {
            return this.localDocProgress ?? this.docProgressData;
        },

        kpis() {
            const c = this.contractorProfile;
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
                    icon: "📋",
                    label: "Missions totales",
                    value: c.total_missions,
                    color: "",
                },
                {
                    icon: "✅",
                    label: "Terminées",
                    value: c.completed_missions,
                    color: "green",
                },
                {
                    icon: "🔄",
                    label: "En cours",
                    value: this.missions.filter((m) =>
                        active.includes(m.status),
                    ).length,
                    color: "orange",
                },
                {
                    icon: "⭐",
                    label: "Note moyenne",
                    value:
                        c.average_rating > 0 ? c.average_rating + "/5" : "N/A",
                    color: "",
                },
            ];
        },

        filteredMissions() {
            let list;
            if (this.tab === "all") {
                list = this.missions;
            } else {
                const active = [
                    "assigned", "accepted", "contact_made", "on_the_way",
                    "tracking", "in_progress", "quote_submitted", "order_placed",
                    "awaiting_confirm",
                ];
                if (this.tab === "active")
                    list = this.missions.filter((m) => active.includes(m.status));
                else if (this.tab === "assigned")
                    list = this.missions.filter((m) => m.status === "assigned");
                else if (this.tab === "closed")
                    list = this.missions.filter((m) => ["completed", "closed"].includes(m.status));
                else if (this.tab === "cancelled")
                    list = this.missions.filter((m) => m.status === "cancelled");
                else
                    list = this.missions;
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

        // ── Quote computed ────────────────────────────────────────
        partsSubtotal() {
            return this.quoteModal.partLines.reduce(
                (sum, l) =>
                    sum +
                    (Number(l.quantity) || 0) * (Number(l.unit_price) || 0),
                0,
            );
        },
        quoteTotal() {
            return (
                this.partsSubtotal +
                (Number(this.quoteModal.labor.unit_price) || 0)
            );
        },
        quoteIsValid() {
            const hasValidPart = this.quoteModal.partLines.some(
                (l) =>
                    l.description?.trim() &&
                    l.quantity > 0 &&
                    l.unit_price >= 0,
            );
            const hasLabor =
                (Number(this.quoteModal.labor.unit_price) || 0) > 0 &&
                this.quoteModal.labor.description?.trim();
            return hasValidPart || hasLabor;
        },
    },

    methods: {
        // ── Missions ──────────────────────────────────────────────
        async fetchMissions() {
            this.missionsLoading = true;
            this.missionsError = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                this.missions = Array.isArray(data) ? data : (data.data ?? []);
            } catch {
                this.missionsError = "Impossible de charger les missions.";
            } finally {
                this.missionsLoading = false;
            }
        },

        async updateStatus(mission, status) {
            this.actionLoading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    mission.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ status }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message ?? "Erreur.", "error");
                    return;
                }
                const idx = this.missions.findIndex((m) => m.id === mission.id);
                if (idx !== -1) this.missions.splice(idx, 1, data.mission);
                this.activeMission = data.mission;
                this.showToast("Statut mis à jour.", "success");
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.actionLoading = false;
            }
        },

        // ── Refus mission ─────────────────────────────────────────
        openRefuseModal(mission) {
            this.refuseModal = {
                visible: true,
                mission,
                reason: "",
                customReason: "",
                loading: false,
            };
        },

        async confirmRefuse() {
            const reason =
                this.refuseModal.reason === "other"
                    ? this.refuseModal.customReason
                    : (this.refuseOptions.find(
                          (o) => o.value === this.refuseModal.reason,
                      )?.label ?? this.refuseModal.reason);

            this.refuseModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    this.refuseModal.mission.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        status: "proposal_rejected",
                        reported_issue: reason,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message ?? "Erreur.", "error");
                    return;
                }
                // Retirer la mission de la liste — elle n'est plus visible pour ce prestataire
                this.missions = this.missions.filter(
                    (m) => m.id !== this.refuseModal.mission.id,
                );
                this.refuseModal.visible = false;
                this.activeMission = null;
                this.showToast(
                    "Mission refusée. Elle sera réattribuée automatiquement.",
                    "success",
                );
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.refuseModal.loading = false;
            }
        },

        // ── Horaires ──────────────────────────────────────────────
        openScheduleModal() {
            this.scheduleModal = {
                visible: true,
                available: this.contractorProfile.available,
                start_time: (
                    this.contractorProfile.start_time ?? "08:00"
                ).substring(0, 5),
                end_time: (
                    this.contractorProfile.end_time ?? "18:00"
                ).substring(0, 5),
                working_days: [...(this.contractorProfile.working_days ?? [])],
                radius_km: this.contractorProfile.radius_km ?? 10,
                loading: false,
            };
        },

        async saveSchedule() {
            this.scheduleModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const payload = {
                    available: this.scheduleModal.available,
                    start_time: this.scheduleModal.start_time,
                    end_time: this.scheduleModal.end_time,
                    working_days: this.scheduleModal.working_days,
                    radius_km: this.scheduleModal.radius_km,
                };
                console.log("[Horaires] URL    :", this.routes.availability);
                console.log(
                    "[Horaires] Payload:",
                    JSON.stringify(payload, null, 2),
                );
                const res = await fetch(this.routes.availability, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                console.log("[Horaires] HTTP", res.status, "— Réponse:", data);
                if (!res.ok) {
                    if (data.errors) {
                        console.error(
                            "[Horaires] Erreurs validation:",
                            data.errors,
                        );
                        const msg = Object.entries(data.errors)
                            .map(([k, v]) => k + ": " + v.join(", "))
                            .join(" | ");
                        this.showToast("Validation : " + msg, "error");
                    } else {
                        this.showToast(
                            data.message ?? "Erreur " + res.status,
                            "error",
                        );
                    }
                    return;
                }
                // Mise à jour locale réactive
                this.contractorProfile.available = this.scheduleModal.available;
                this.contractorProfile.start_time =
                    this.scheduleModal.start_time;
                this.contractorProfile.end_time = this.scheduleModal.end_time;
                this.contractorProfile.working_days =
                    this.scheduleModal.working_days;
                this.contractorProfile.radius_km = this.scheduleModal.radius_km;
                this.scheduleModal.visible = false;
                this.showToast(
                    "✅ Horaires mis à jour avec succès.",
                    "success",
                );
            } catch (e) {
                console.error("[Horaires] Erreur JS:", e);
                this.showToast("Erreur lors de la mise à jour.", "error");
            } finally {
                this.scheduleModal.loading = false;
            }
        },

        // ── Disponibilité rapide (topbar) ─────────────────────────
        async toggleAvailability() {
            const newVal = !this.contractorProfile.available;
            this.availLoading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const res = await fetch(this.routes.availability, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ available: newVal }),
                });
                const data = await res.json();
                console.log(
                    "[Toggle dispo] HTTP",
                    res.status,
                    "— Réponse:",
                    data,
                );
                if (!res.ok) {
                    if (data.errors) {
                        console.error(
                            "[Toggle dispo] Erreurs validation:",
                            data.errors,
                        );
                        const msg = Object.entries(data.errors)
                            .map(([k, v]) => k + ": " + v.join(", "))
                            .join(" | ");
                        this.showToast("Validation : " + msg, "error");
                    } else {
                        this.showToast(
                            data.message ?? "Erreur " + res.status,
                            "error",
                        );
                    }
                    return;
                }
                this.contractorProfile.available = newVal;
                this.showToast(
                    newVal
                        ? "Vous êtes maintenant disponible."
                        : "Vous êtes maintenant indisponible.",
                    "success",
                );
            } catch (e) {
                console.error("[Toggle dispo] Erreur JS:", e);
                this.showToast("Erreur lors de la mise à jour.", "error");
            } finally {
                this.availLoading = false;
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
                const url = this.routes.notifications_read.replace(
                    "{id}",
                    n.id,
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
                'meta[name="csrf-token"]',
            )?.content;
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications.forEach((n) => (n.read = true));
            this.unreadCount = 0;
        },

        // ── Documents ─────────────────────────────────────────────
        async uploadDocument(doc, event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                this.showToast(
                    "Fichier trop volumineux. Maximum 5 Mo.",
                    "error",
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
                    'meta[name="csrf-token"]',
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
                    "success",
                );
                await this.refreshDocProgress();
            } catch (err) {
                doc.uploading = false;
                doc.filename = null;
                event.target.value = "";
                this.showToast(
                    err.message ?? "Erreur lors de l'upload.",
                    "error",
                );
            }
        },

        async refreshDocProgress() {
            try {
                const res = await fetch(this.routes.documents_index, {
                    headers: { Accept: "application/json" },
                });
                const docs = await res.json();
                this.localDocuments.forEach((localDoc) => {
                    const srv = docs.find((d) => d.type === localDoc.type);
                    if (srv) {
                        localDoc.status = srv.status;
                        localDoc.reject_reason = srv.reject_reason;
                        localDoc.filename = srv.filename;
                    }
                });
                const approved = this.localDocuments.filter(
                    (d) => d.status === "approved",
                ).length;
                const total = this.localDocuments.length;
                this.localDocProgress = {
                    approved,
                    total,
                    percentage:
                        total > 0 ? Math.round((approved / total) * 100) : 0,
                };
                await this.checkCertification();
            } catch {
                /* silencieux */
            }
        },

        async checkCertification() {
            try {
                const res = await fetch(this.routes.profil, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                if (
                    data?.status === "approved" &&
                    this.userStatus !== "approved"
                ) {
                    this.userStatus = "approved";
                    this.showToast(
                        "🎉 Votre profil est maintenant certifié !",
                        "success",
                    );
                }
            } catch {
                /* silencieux */
            }
        },

        // ── Helpers ───────────────────────────────────────────────
        countByTab(key) {
            if (key === "all") return this.missions.length;
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
            if (key === "active")
                return this.missions.filter((m) => active.includes(m.status))
                    .length;
            if (key === "assigned")
                return this.missions.filter((m) => m.status === "assigned")
                    .length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },

        labelOf(mission) {
            if (mission.status_label && mission.status_label !== mission.status)
                return mission.status_label;
            const map = {
                pending: "En attente",
                assigned: "Proposée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi en cours",
                in_progress: "Intervention en cours",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "En att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
            };
            return map[mission.status] ?? mission.status;
        },

        badgeClass(status) {
            const map = {
                pending: "pending",
                assigned: "warning",
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

        docStatusLabel(s) {
            return (
                {
                    approved: "✅ Validé par l'admin",
                    pending: "⏳ En attente de vérification",
                    rejected: "❌ Refusé",
                    missing: "📎 À déposer",
                }[s] ?? s
            );
        },

        serviceIcon(service) {
            const icons = {
                plomberie: "🔧",
                electricite: "⚡",
                menuiserie: "🪵",
                ferronnerie: "⚙️",
                frigoriste: "❄️",
                mecanique: "🚗",
                nettoyage: "🧹",
                peinture: "🎨",
                maintenance: "🛠️",
            };
            return icons[service?.toLowerCase()] ?? "🔨";
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
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(amount)) +
                " FCFA"
            );
        },

        openMission(m) {
            this.activeMission = m;
        },
        scrollToDocs() {
            this.$refs.docsSection?.scrollIntoView({ behavior: "smooth" });
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

        // ── Abandon mission ───────────────────────────────────────

        // ── Quote modal ───────────────────────────────────────────
        openQuoteModal(mission) {
            const existingQuote = mission.quote;
            const isRevision =
                existingQuote &&
                ["submitted", "rejected"].includes(existingQuote.status);

            this.quoteModal = {
                visible: true,
                mission: { ...mission },
                isRevision,
                diagnosis: existingQuote?.diagnosis ?? "",
                labor: {
                    description: "Main d'œuvre",
                    unit_price:
                        existingQuote?.items?.find((i) => i.type === "labor")
                            ?.unit_price ?? 0,
                },
                // Pré-remplir les lignes si révision
                partLines: existingQuote?.items
                    ? existingQuote.items
                          .filter((i) => i.type === "part")
                          .map((i, idx) => ({
                              _id: idx,
                              description: i.description,
                              quantity: Number(i.quantity),
                              unit_price: Number(i.unit_price),
                          }))
                    : [],
                loading: false,
                error: null,
                _lineId: existingQuote?.items
                    ? existingQuote.items.filter((i) => i.type === "part")
                          .length
                    : 0,
            };
        },

        closeQuoteModal() {
            this.quoteModal.visible = false;
        },

        addPartLine() {
            this.quoteModal.partLines.push({
                _id: this.quoteModal._lineId++,
                description: "",
                quantity: 1,
                unit_price: 0,
            });
        },

        removePartLine(idx) {
            this.quoteModal.partLines.splice(idx, 1);
        },

        lineTotal(line) {
            return (
                (Number(line.quantity) || 0) * (Number(line.unit_price) || 0)
            );
        },

        async submitQuote(action) {
            this.quoteModal.error = null;
            this.quoteModal.loading = action;

            // Construire les items
            const items = [];

            for (const l of this.quoteModal.partLines) {
                if (!l.description?.trim()) {
                    this.quoteModal.error =
                        "Chaque pièce doit avoir une désignation.";
                    this.quoteModal.loading = false;
                    return;
                }
                if (!l.quantity || l.quantity <= 0) {
                    this.quoteModal.error =
                        "La quantité doit être supérieure à 0.";
                    this.quoteModal.loading = false;
                    return;
                }
                items.push({
                    type: "part",
                    description: l.description.trim(),
                    quantity: l.quantity,
                    unit_price: l.unit_price || 0,
                });
            }

            if ((Number(this.quoteModal.labor.unit_price) || 0) > 0) {
                items.push({
                    type: "labor",
                    description:
                        this.quoteModal.labor.description?.trim() ||
                        "Main d'œuvre",
                    quantity: 1,
                    unit_price: Number(this.quoteModal.labor.unit_price),
                });
            }

            if (items.length === 0) {
                this.quoteModal.error =
                    "Ajoutez au moins une pièce ou un montant de main d'œuvre.";
                this.quoteModal.loading = false;
                return;
            }

            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_quote_store.replace(
                    "{id}",
                    this.quoteModal.mission.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        action,
                        diagnosis: this.quoteModal.diagnosis?.trim() || null,
                        items,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quoteModal.error =
                        data.message ?? "Une erreur est survenue.";
                    return;
                }

                // Mettre à jour la mission dans la liste et le panel
                this.updateMissionInList(data.mission);
                this.activeMission = { ...data.mission };

                this.closeQuoteModal();
                this.showToast(
                    action === "submit"
                        ? "✅ Devis soumis au client !"
                        : "💾 Brouillon enregistré.",
                    "success",
                );
            } catch {
                this.quoteModal.error = "Erreur réseau. Veuillez réessayer.";
            } finally {
                this.quoteModal.loading = false;
            }
        },

        quoteStatusLabel(status) {
            const map = {
                draft: "📝 Brouillon",
                submitted: "⏳ En attente d'approbation",
                approved: "✅ Approuvé par le client",
                rejected: "❌ Refusé",
                revised: "🔄 Révisé",
            };
            return map[status] ?? status;
        },

        // Retourne le statut effectif du devis en tenant compte du statut de la mission.
        // Quand le client valide, la mission passe à order_placed mais quote.status
        // peut rester "submitted" — on force "approved" dans ce cas.
        effectiveQuoteStatus(mission) {
            const approvedMissionStatuses = [
                "order_placed",
                "awaiting_confirm",
                "completed",
                "closed",
            ];
            if (
                mission.quote?.status === "submitted" &&
                approvedMissionStatuses.includes(mission.status)
            ) {
                return "approved";
            }
            return mission.quote?.status ?? "draft";
        },

        openAbandonModal(mission) {
            this.abandonModal = {
                visible: true,
                mission,
                reason: "",
                loading: false,
            };
        },
        async confirmAbandon() {
            this.abandonModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    this.abandonModal.mission.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        status: "cancelled",
                        reported_issue: this.abandonModal.reason,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message ?? "Erreur.", "error");
                    return;
                }
                this.missions = this.missions.filter(
                    (m) => m.id !== this.abandonModal.mission.id,
                );
                this.abandonModal.visible = false;
                this.activeMission = null;
                this.showToast(
                    "Mission abandonnée. L'équipe Resotravo a été notifiée.",
                    "success",
                );
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.abandonModal.loading = false;
            }
        },

        // ── Refus ─────────────────────────────────────────────────
        openRefuseModal(m) {
            this.refuseModal = {
                visible: true,
                mission: m,
                reason: "",
                customReason: "",
                loading: false,
            };
        },
        async confirmRefuse() {
            const reason =
                this.refuseModal.reason === "other"
                    ? this.refuseModal.customReason
                    : (this.refuseOptions.find(
                          (o) => o.value === this.refuseModal.reason,
                      )?.label ?? "");
            this.refuseModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    this.refuseModal.mission.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        status: "proposal_rejected",
                        reported_issue: reason,
                    }),
                });
                if (!res.ok) {
                    const d = await res.json();
                    this.showToast(d.message ?? "Erreur.", "error");
                    return;
                }
                this.missions = this.missions.filter(
                    (m) => m.id !== this.refuseModal.mission.id,
                );
                this.refuseModal.visible = false;
                this.activeMission = null;
                this.showToast(
                    "Mission refusée. Elle sera réattribuée automatiquement.",
                    "success",
                );
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.refuseModal.loading = false;
            }
        },

        // ── Chat ──────────────────────────────────────────────────
        openChat(missionId) {
            this.chatMissionId = missionId;
        },
        onChatClose() {
            this.chatMissionId = null;
            this.chatUnread = 0;
        },
        onChatUnread(count) {
            this.chatUnread = count;
            if (this.chatMissionId) {
                this.unreadByMission =
                    count > 0
                        ? {
                              ...this.unreadByMission,
                              [this.chatMissionId]: count,
                          }
                        : (() => {
                              const c = { ...this.unreadByMission };
                              delete c[this.chatMissionId];
                              return c;
                          })();
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

        // ── Helpers ───────────────────────────────────────────────
        updateMissionInList(updated) {
            const idx = this.missions.findIndex((m) => m.id === updated.id);
            if (idx !== -1)
                this.missions.splice(idx, 1, {
                    ...this.missions[idx],
                    ...updated,
                });
        },

        updateMissionInList(updated) {
            const idx = this.missions.findIndex((m) => m.id === updated.id);
            if (idx !== -1)
                this.missions.splice(idx, 1, {
                    ...this.missions[idx],
                    ...updated,
                });
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            )
                this.notifOpen = false;
        },
    },

    mounted() {
        this.userStatus = this.user.status;

        const defaultDocs = [
            {
                type: "cip",
                label: "CIP / CNI / Passeport",
                icon: "🪪",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
            {
                type: "photo",
                label: "Photo d'identité professionnelle",
                icon: "📷",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
            {
                type: "attestation",
                label: "Attestation de résidence",
                icon: "📄",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
            {
                type: "casier",
                label: "Casier judiciaire",
                icon: "⚖️",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
            {
                type: "diplome",
                label: "Diplôme ou attestation de qualification",
                icon: "🎓",
                status: "missing",
                uploading: false,
                filename: null,
                reject_reason: null,
            },
        ];
        this.localDocuments = defaultDocs.map((def) => {
            const srv = this.documentsData.find((d) => d.type === def.type);
            return srv ? { ...def, ...srv, uploading: false } : def;
        });
        this.localDocProgress = { ...this.docProgressData };

        this.fetchMissions();
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
.ctr-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* TOPBAR */
.ctr-topbar {
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
    .ctr-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.ctr-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.ctr-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ctr-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ctr-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    display: none;
}
@media (min-width: 480px) {
    .ctr-page-sub {
        display: block;
    }
}
.ctr-page-sub strong {
    color: var(--dk);
}

/* Avatar */
.ctr-avatar {
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

/* Dispo btn */
.ctr-dispo-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 7px 14px;
    border-radius: 99px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    color: var(--gr);
}
.ctr-dispo-btn.available {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}
.ctr-dispo-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.ctr-dispo-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--grm);
    flex-shrink: 0;
}
.ctr-dispo-btn.available .ctr-dispo-dot {
    background: #22c55e;
}

/* Notifications */
.ctr-notif-wrap {
    position: relative;
}
.ctr-notif-btn {
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
.ctr-notif-btn:hover {
    color: var(--or);
}
.ctr-notif-btn svg {
    width: 22px;
    height: 22px;
}
.ctr-notif-badge {
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
.ctr-notif-dropdown {
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
}
.ctr-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ctr-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.ctr-notif-loading,
.ctr-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.ctr-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--cr);
    cursor: pointer;
    transition: background 0.15s;
    position: relative;
}
.ctr-notif-item:hover {
    background: var(--cr);
}
.ctr-notif-item.unread {
    background: #fff8f5;
}
.ctr-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.ctr-notif-content {
    flex: 1;
    min-width: 0;
}
.ctr-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ctr-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.ctr-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

/* CONTENT */
.ctr-content {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1280px;
    margin: 0 auto;
    width: 100%;
}
@media (min-width: 600px) {
    .ctr-content {
        padding: 24px;
    }
}

/* BANNIÈRES */
.ctr-banner-pending,
.ctr-banner-certified,
.ctr-banner-warning {
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
}
.ctr-banner-pending {
    background: #fef3c7;
    border: 1.5px solid #fcd34d;
}
.ctr-banner-warning {
    background: #fee2e2;
    border: 1.5px solid #fca5a5;
}
.ctr-banner-certified {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
}
.ctr-banner-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.ctr-banner-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-banner-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}
.ctr-badge-certified {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
    align-self: center;
}

/* KPIs */
.ctr-kpis {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 600px) {
    .ctr-kpis {
        grid-template-columns: repeat(4, 1fr);
    }
}
.ctr-kpi {
    background: var(--wh);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-left: 4px solid transparent;
}
.ctr-kpi.green {
    border-left-color: #22c55e;
}
.ctr-kpi.orange {
    border-left-color: var(--or);
}
.ctr-kpi-icon {
    font-size: 20px;
}
.ctr-kpi-val {
    font-size: 22px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-kpi-label {
    font-size: 11.5px;
    color: var(--gr);
    font-weight: 600;
}
.ctr-skeleton-val {
    display: inline-block;
    width: 50px;
    height: 20px;
    background: var(--grl);
    border-radius: 6px;
    animation: ctr-pulse 1.2s infinite;
}
@keyframes ctr-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}

/* MID GRID */
.ctr-mid-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
@media (min-width: 900px) {
    .ctr-mid-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
    }
}
.ctr-side-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* CARD */
.ctr-card {
    background: var(--wh);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.ctr-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.ctr-card-header h3 {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk);
}

/* TABS */
.ctr-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.ctr-tab {
    padding: 6px 12px;
    border-radius: 8px;
    border: 1.5px solid transparent;
    background: var(--grl);
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
    display: flex;
    align-items: center;
    gap: 5px;
}
.ctr-tab:hover {
    background: var(--or3);
    color: var(--or);
}
.ctr-tab.active {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border-color: transparent;
}
.ctr-tab-count {
    background: rgba(0, 0, 0, 0.12);
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
.ctr-tab.active .ctr-tab-count {
    background: rgba(255, 255, 255, 0.25);
}

/* MISSIONS */
.ctr-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ctr-skeleton-row {
    height: 64px;
    background: var(--grl);
    border-radius: 12px;
    animation: ctr-pulse 1.2s infinite;
}
.ctr-alert-error {
    background: #fee2e2;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ctr-empty {
    text-align: center;
    padding: 40px 20px;
}
.ctr-empty-icon {
    font-size: 36px;
    margin-bottom: 8px;
}
.ctr-empty-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-empty-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 4px;
}
.ctr-mission-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ctr-mission-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    cursor: pointer;
    transition: all 0.18s;
}
.ctr-mission-item:hover {
    border-color: var(--am);
    background: var(--or3);
}
.ctr-mission-left {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex: 1;
    min-width: 0;
}
.ctr-mission-icon {
    font-size: 24px;
    flex-shrink: 0;
}
.ctr-mission-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.ctr-mission-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.ctr-mission-addr {
    font-size: 12px;
    color: var(--gr);
    margin-top: 1px;
}
.ctr-mission-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.ctr-mission-price {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--dk);
}

/* BADGES */
.ctr-badge {
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
}
.ctr-badge.pending {
    background: #fef3c7;
    color: #d97706;
}
.ctr-badge.warning {
    background: #ffedd5;
    color: var(--or2);
}
.ctr-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.ctr-badge.done {
    background: #dcfce7;
    color: #16a34a;
}
.ctr-badge.cancelled {
    background: #fee2e2;
    color: #dc2626;
}

/* PROFIL */
.ctr-profile-info {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 12px;
}
.ctr-profile-av {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or3), #fde68a);
    color: var(--or2);
    font-weight: 800;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
}
.ctr-profile-av.certified {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
}
.ctr-certified-dot {
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
.ctr-profile-name {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-profile-spec {
    font-size: 12.5px;
    color: var(--or);
    font-weight: 600;
}
.ctr-profile-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}

/* ACCRÉDITATION */
.ctr-accred-row {
    padding: 10px 0 0;
    border-top: 1px solid var(--grl);
    margin-top: 4px;
}
.ctr-accred-badge {
    background: linear-gradient(135deg, #eff6ff, #f0fdf4);
    color: #1d4ed8;
    border: 1.5px solid #bfdbfe;
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 700;
    display: inline-block;
}
.ctr-accred-none {
}
.ctr-accred-none-text {
    font-size: 12px;
    color: #d97706;
    font-weight: 600;
}

/* DOC PROGRESS */
.ctr-doc-progress {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--grl);
}
.ctr-doc-progress-label {
    font-size: 12px;
    color: var(--gr);
    margin-bottom: 6px;
}
.ctr-doc-track {
    height: 6px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
}
.ctr-doc-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s;
}
.ctr-doc-progress-note {
    font-size: 11px;
    color: var(--grm);
    margin-top: 5px;
}

/* DISPONIBILITÉ */
.ctr-dispo-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.ctr-dispo-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    color: var(--gr);
}
.ctr-dispo-row:last-child {
    border-bottom: none;
}
.ctr-dispo-row strong {
    font-weight: 700;
    color: var(--dk);
}
.ctr-dispo-status {
    font-weight: 700;
}
.ctr-dispo-status.available {
    color: #15803d;
}

/* ACTIONS RAPIDES */
.ctr-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ctr-quick-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
    text-align: left;
}
.ctr-quick-btn:hover {
    border-color: var(--am);
    background: var(--or3);
    color: var(--or);
}

/* DOCUMENTS */
.ctr-doc-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ctr-doc-summary {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    background: var(--grl);
    padding: 4px 10px;
    border-radius: 99px;
}
.ctr-doc-summary.complete {
    background: #dcfce7;
    color: #16a34a;
}
.ctr-certified-pill {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border-radius: 99px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 700;
}
.ctr-docs-certified {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f0fdf4;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 14px;
    border: 1.5px solid #bbf7d0;
}
.ctr-docs-certified-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.ctr-docs-certified-title {
    font-size: 14px;
    font-weight: 800;
    color: #15803d;
}
.ctr-docs-certified-sub {
    font-size: 12.5px;
    color: #166534;
    margin-top: 3px;
}
.ctr-docs-badge-info {
    background: #fef9c3;
    border: 1.5px solid #fde047;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 12.5px;
    color: #854d0e;
    margin-bottom: 12px;
}
.ctr-docs-progress-bar {
    margin-bottom: 14px;
}
.ctr-docs-pb-track {
    height: 8px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
}
.ctr-docs-pb-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s;
}
.ctr-docs-pb-label {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 5px;
    text-align: right;
}
.ctr-docs-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 6px;
}
.ctr-doc-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
}
.ctr-doc-item.approved {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.ctr-doc-item.rejected {
    border-color: #fca5a5;
    background: #fff1f2;
}
.ctr-doc-item.pending {
    border-color: #fde68a;
    background: #fffbeb;
}
.ctr-doc-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.ctr-doc-info {
    flex: 1;
    min-width: 0;
}
.ctr-doc-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.ctr-doc-status-label {
    font-size: 12px;
    margin-top: 2px;
}
.ctr-doc-status-label.approved {
    color: #16a34a;
}
.ctr-doc-status-label.pending {
    color: #d97706;
}
.ctr-doc-status-label.rejected {
    color: #dc2626;
}
.ctr-doc-status-label.missing {
    color: var(--gr);
}
.ctr-doc-reject-reason {
    font-size: 11.5px;
    color: #dc2626;
    margin-top: 3px;
}
.ctr-doc-filename {
    font-size: 11.5px;
    color: var(--grm);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ctr-doc-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}
.ctr-docs-note {
    font-size: 12px;
    color: var(--grm);
    margin-top: 12px;
    background: var(--grl);
    border-radius: 8px;
    padding: 10px 12px;
}

/* MODAL */
.ctr-modal-overlay {
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
.ctr-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 500px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: ctr-slide-up 0.25s ease;
}
@keyframes ctr-slide-up {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.ctr-modal-header {
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
.ctr-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.ctr-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.ctr-modal-body {
    padding: 20px 24px;
    flex: 1;
}
.ctr-modal-footer {
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
.ctr-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13.5px;
    gap: 12px;
}
.ctr-detail-row:last-child {
    border-bottom: none;
}
.ctr-detail-photos {
    align-items: flex-start;
}
.ctr-dash-images {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}
.ctr-dash-img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: opacity .15s;
}
.ctr-dash-img:hover { opacity: .85; }
.ctr-chat-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #f97316;
    font-size: 11px;
    font-weight: 800;
    border-radius: 99px;
    padding: 0 6px;
    min-width: 18px;
    height: 18px;
    margin-left: 6px;
}
.ctr-dash-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.88);
    z-index: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 20px;
}
.ctr-dash-lightbox img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
}
.ctr-detail-row span:first-child {
    color: var(--gr);
    flex-shrink: 0;
}
.ctr-detail-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}

/* ACTION BLOCK */
.ctr-step-bar {
    height: 6px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
    margin: 16px 0 6px;
}
.ctr-step-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s;
}
.ctr-step-label {
    font-size: 12px;
    color: var(--gr);
    text-align: center;
    margin-bottom: 16px;
}
.ctr-action-block {
    background: #faf7f5;
    border-radius: 12px;
    padding: 16px;
    margin-top: 12px;
    border: 1.5px solid var(--grl);
}
.ctr-action-block p {
    font-size: 13.5px;
    color: var(--gr);
    margin-bottom: 12px;
    line-height: 1.6;
}
.ctr-action-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.ctr-action-row .ctr-btn {
    flex: 1;
    justify-content: center;
}

/* ACTION ASSIGNED (accepter/refuser) */
.ctr-action-assigned {
    border-color: #fde68a;
    background: #fffbeb;
}
.ctr-action-assigned-header {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 14px;
}
.ctr-action-assigned-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.ctr-action-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.ctr-action-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}

/* MODAL REFUS */
.ctr-refuse-info {
    background: #fee2e2;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    color: #dc2626;
    margin-bottom: 16px;
    line-height: 1.6;
}
.ctr-refuse-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}
.ctr-radio-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--dk);
    background: var(--grl);
    padding: 10px 14px;
    border-radius: 10px;
    border: 1.5px solid transparent;
    transition: all 0.15s;
}
.ctr-radio-opt:hover {
    border-color: var(--am);
    background: var(--or3);
}
.ctr-radio-opt input[type="radio"] {
    accent-color: var(--or);
}

/* MODAL HORAIRES */
.ctr-schedule-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--grl);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 16px;
}
.ctr-schedule-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.ctr-schedule-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.ctr-toggle-btn {
    width: 46px;
    height: 26px;
    border-radius: 99px;
    background: #d1d5db;
    border: none;
    cursor: pointer;
    position: relative;
    transition: background 0.2s;
    flex-shrink: 0;
}
.ctr-toggle-btn.on {
    background: var(--or);
}
.ctr-toggle-knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.2s;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}
.ctr-toggle-btn.on .ctr-toggle-knob {
    transform: translateX(20px);
}
.ctr-schedule-section {
    margin-bottom: 16px;
}
.ctr-schedule-section-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.ctr-time-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}
.ctr-time-field {
    flex: 1;
}
.ctr-time-sep {
    font-size: 18px;
    font-weight: 700;
    color: var(--gr);
    padding-bottom: 8px;
}
.ctr-days-grid {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.ctr-day-chip {
    padding: 7px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
}
.ctr-day-chip.active {
    background: var(--or);
    color: #fff;
    border-color: var(--or2);
}
.ctr-radius-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ctr-range {
    flex: 1;
    accent-color: var(--or);
}
.ctr-radius-val {
    font-size: 14px;
    font-weight: 800;
    color: var(--or);
    min-width: 50px;
    text-align: right;
}

/* FORM */
.ctr-form-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 6px;
    display: block;
}
.ctr-required {
    color: #dc2626;
}
.ctr-input {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: border 0.15s;
    box-sizing: border-box;
}
.ctr-input:focus {
    outline: none;
    border-color: var(--or);
}

/* BOUTONS */
.ctr-btn {
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
.ctr-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.ctr-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.ctr-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.ctr-btn-ghost:hover {
    background: #d5c9c0;
}
.ctr-btn-red {
    background: #ef4444;
    color: #fff;
}
.ctr-btn-red:hover:not(:disabled) {
    background: #dc2626;
}
.ctr-btn-green {
    background: #22c55e;
    color: #fff;
}
.ctr-btn-green:hover:not(:disabled) {
    background: #16a34a;
}
.ctr-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}
.ctr-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}
.ctr-upload-label {
    cursor: pointer;
}
.ctr-upload-label.loading {
    opacity: 0.7;
    pointer-events: none;
}
.ctr-mini-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ctr-spin 0.7s linear infinite;
}

/* SPINNER */
.ctr-chat-btn-notif {
    position: relative;
}
.ctr-chat-badge {
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
.ctr-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ctr-spin 0.7s linear infinite;
}
@keyframes ctr-spin {
    to {
        transform: rotate(360deg);
    }
}

/* WIP */
.ctr-wip-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 13, 11, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.ctr-wip-modal {
    background: #fff;
    border-radius: 20px;
    padding: 36px 30px;
    max-width: 380px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: ctr-slide-up 0.25s ease;
}
.ctr-wip-icon {
    font-size: 48px;
    margin-bottom: 14px;
    animation: ctr-bounce 0.6s ease infinite alternate;
}
@keyframes ctr-bounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-6px);
    }
}
.ctr-wip-modal h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 8px;
}
.ctr-wip-feature {
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
.ctr-wip-modal p {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.6;
    margin-bottom: 20px;
}

/* TOASTS */
.ctr-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.ctr-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: ctr-slide-up 0.3s ease;
}
.ctr-toast.success {
    background: #16a34a;
}
.ctr-toast.error {
    background: #dc2626;
}

@media (max-width: 480px) {
    .ctr-dispo-btn {
        display: none;
    }
    .ctr-modal {
        max-height: 100vh;
        border-radius: 18px 18px 0 0;
    }
    .ctr-modal-overlay {
        align-items: flex-end;
        padding: 0;
    }
}

.ctm-action-quote {
    border-color: #fed7aa;
    background: #fff7ed;
    text-align: center;
}
.ctm-action-quote-icon {
    font-size: 32px;
    margin-bottom: 8px;
}
.ctm-action-quote-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.ctm-action-quote-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-bottom: 14px;
    line-height: 1.5;
}

.ctm-action-wait {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-color: #bfdbfe;
    background: #eff6ff;
}
.ctm-action-wait-icon {
    font-size: 24px;
    flex-shrink: 0;
}
.ctm-action-wait-title {
    font-size: 14px;
    font-weight: 800;
    color: #1d4ed8;
}
.ctm-action-wait-sub {
    font-size: 12.5px;
    color: #3b82f6;
    margin-top: 3px;
}
.ctm-action-done {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.ctm-action-done-icon {
    font-size: 24px;
    flex-shrink: 0;
}
.ctm-action-done-title {
    font-size: 14px;
    font-weight: 800;
    color: #15803d;
}
.ctm-action-done-sub {
    font-size: 12.5px;
    color: #16a34a;
    margin-top: 3px;
}

.ctm-btn {
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
    justify-content: center;
}
.ctm-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.ctm-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
}
.ctm-btn-green {
    background: #22c55e;
    color: #fff;
}
.ctm-btn-green:hover:not(:disabled) {
    background: #16a34a;
}
.ctm-btn-red {
    background: #ef4444;
    color: #fff;
}
.ctm-btn-red:hover:not(:disabled) {
    background: #dc2626;
}
.ctm-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.ctm-btn-ghost:hover {
    background: #d5c9c0;
}
.ctm-btn-draft {
    border: 1.5px solid var(--or);
    color: var(--or);
    background: transparent;
}
.ctm-btn-draft:hover:not(:disabled) {
    background: var(--or3);
}
.ctm-btn-chat {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
    position: relative;
}
.ctm-btn-chat:hover {
    transform: translateY(-1px);
}
.ctm-btn-full {
    width: 100%;
}
.ctm-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}
.ctm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}
.ctm-chat-badge {
    position: absolute;
    top: -7px;
    right: -7px;
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
.ctm-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ctm-spin 0.7s linear infinite;
}
.ctm-spinner-dark {
    border-color: rgba(0, 0, 0, 0.15);
    border-top-color: var(--or);
}
@keyframes ctm-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── MODAL COMMUN ── */
.ctm-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.ctm-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 460px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: ctm-modal-in 0.2s ease;
}
@keyframes ctm-modal-in {
    from {
        transform: scale(0.97) translateY(10px);
        opacity: 0;
    }
    to {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}
.ctm-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 2px solid var(--grl);
}
.ctm-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-modal-subtitle {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.ctm-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.ctm-modal-body {
    padding: 18px 22px;
}
.ctm-modal-footer {
    padding: 14px 22px;
    border-top: 2px solid var(--grl);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
}

/* ── MODAL DEVIS (overrides + extensions) ── */
.ctm-modal-quote {
    max-width: 620px;
}
.ctm-modal-body-quote {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.ctm-modal-footer-quote {
    gap: 8px;
    flex-wrap: wrap;
}
.ctm-modal-footer-quote .ctm-btn {
    flex: 1;
    min-width: 120px;
}

.ctm-quote-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ctm-quote-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
}
.ctm-optional {
    font-weight: 400;
    color: var(--grm);
}
.ctm-quote-textarea {
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 10px 13px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    resize: vertical;
    transition: border 0.15s;
}
.ctm-quote-textarea:focus {
    outline: none;
    border-color: var(--or);
}

.ctm-quote-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ctm-quote-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ctm-quote-section-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-add-line-btn {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--or);
    background: var(--or3);
    border: 1.5px solid #fed7aa;
    border-radius: 8px;
    padding: 5px 12px;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.15s;
}
.ctm-add-line-btn:hover {
    background: #ffedd5;
}

.ctm-quote-table-head {
    display: grid;
    grid-template-columns: 1fr 60px 110px 90px 28px;
    gap: 6px;
    padding: 0 2px;
}
.ctm-quote-table-head span {
    font-size: 11px;
    font-weight: 700;
    color: var(--grm);
}

.ctm-quote-empty-lines {
    font-size: 13px;
    color: var(--grm);
    text-align: center;
    padding: 14px;
    background: var(--grl);
    border-radius: 10px;
}

.ctm-quote-line {
    display: grid;
    grid-template-columns: 1fr 60px 110px 90px 28px;
    gap: 6px;
    align-items: center;
}
.ctm-quote-input {
    border: 2px solid var(--grl);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    width: 100%;
    box-sizing: border-box;
    transition: border 0.15s;
}
.ctm-quote-input:focus {
    outline: none;
    border-color: var(--or);
}
.ctm-quote-line-total {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    text-align: right;
    white-space: nowrap;
}
.ctm-quote-del-btn {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: #fee2e2;
    color: #dc2626;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    flex-shrink: 0;
    transition: background 0.15s;
}
.ctm-quote-del-btn:hover {
    background: #fca5a5;
}

.ctm-quote-labor-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.ctm-field-flex {
    flex: 1;
    min-width: 160px;
}
.ctm-field-amount {
    width: 150px;
}

.ctm-quote-recap {
    background: linear-gradient(135deg, #fff7ed, #fffbf5);
    border: 2px solid #fed7aa;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ctm-quote-recap-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--gr);
}
.ctm-quote-recap-row strong {
    color: var(--dk);
    font-weight: 700;
}
.ctm-quote-recap-total {
    display: flex;
    justify-content: space-between;
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    border-top: 2px solid #fed7aa;
    padding-top: 10px;
    margin-top: 4px;
}
.ctm-quote-recap-net {
    font-size: 12px;
    color: var(--gr);
    text-align: right;
}
.ctm-quote-recap-net strong {
    color: #16a34a;
}

.ctm-quote-error {
    background: #fee2e2;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #dc2626;
    font-weight: 600;
}

/* ── MODAL REFUS ── */
.ctm-refuse-info {
    background: #fef9c3;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #854d0e;
    margin-bottom: 14px;
}
.ctm-refuse-options {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 12px;
}
.ctm-radio-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--dk);
    background: var(--grl);
    padding: 9px 13px;
    border-radius: 9px;
    border: 1.5px solid transparent;
    transition: all 0.15s;
}
.ctm-radio-opt:hover {
    border-color: var(--am);
}
.ctm-radio-opt input[type="radio"] {
    accent-color: var(--or);
}
.ctm-input {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 9px;
    padding: 9px 13px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    box-sizing: border-box;
}
.ctm-input:focus {
    outline: none;
    border-color: var(--or);
}

/* ── TOASTS ── */
.ctm-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
}
.ctm-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
}
.ctm-toast.success {
    background: #16a34a;
}
.ctm-toast.error {
    background: #dc2626;
}
</style>
