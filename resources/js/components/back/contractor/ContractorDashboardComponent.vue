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
                            <button
                                class="ctr-btn ctr-btn-ghost ctr-btn-sm"
                                @click="wip('Modifier mon profil')"
                            >
                                Modifier
                            </button>
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

                    <!-- Actions rapides -->
                    <div class="ctr-card">
                        <div class="ctr-card-header">
                            <h3>⚡ Actions rapides</h3>
                        </div>
                        <div class="ctr-quick-actions">
                            <button
                                class="ctr-quick-btn"
                                @click="scrollToDocs"
                                v-if="userStatus === 'pending'"
                            >
                                <span>📄</span> Déposer mes documents
                            </button>
                            <button
                                class="ctr-quick-btn"
                                @click="wip('Mes revenus')"
                            >
                                <span>💰</span> Mes revenus
                            </button>
                            <button
                                class="ctr-quick-btn"
                                @click="wip('Mes avis')"
                            >
                                <span>⭐</span> Mes avis clients
                            </button>
                            <button
                                class="ctr-quick-btn"
                                @click="wip('Messagerie')"
                            >
                                <span>💬</span> Messagerie
                            </button>
                            <button
                                class="ctr-quick-btn"
                                @click="wip('Mes factures')"
                            >
                                <span>🧾</span> Mes factures
                            </button>
                            <button
                                class="ctr-quick-btn"
                                @click="wip('Mon accréditation')"
                            >
                                <span>🏅</span> Mon accréditation
                            </button>
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

                    <!-- ── ACTION : Accepter ou refuser la mission ── -->
                    <div
                        class="ctr-action-block ctr-action-assigned"
                        v-if="activeMission.status === 'assigned'"
                    >
                        <div class="ctr-action-assigned-header">
                            <div class="ctr-action-assigned-icon">📬</div>
                            <div>
                                <div class="ctr-action-title">
                                    Nouvelle mission proposée
                                </div>
                                <div class="ctr-action-sub">
                                    Vous avez 5 minutes pour accepter ou refuser
                                    cette mission.
                                </div>
                            </div>
                        </div>
                        <div class="ctr-action-row">
                            <button
                                class="ctr-btn ctr-btn-red"
                                @click="openRefuseModal(activeMission)"
                                :disabled="actionLoading"
                            >
                                ✗ Refuser
                            </button>
                            <button
                                class="ctr-btn ctr-btn-green"
                                @click="updateStatus(activeMission, 'accepted')"
                                :disabled="actionLoading"
                            >
                                <div
                                    class="ctr-spinner"
                                    v-if="actionLoading"
                                ></div>
                                <span v-else>✓ Accepter la mission</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action : marquer en route -->
                    <div
                        class="ctr-action-block"
                        v-if="activeMission.status === 'accepted'"
                    >
                        <p>
                            🚗 Vous êtes prêt à partir ? Activez le suivi GPS
                            pour que le client puisse vous suivre.
                        </p>
                        <button
                            class="ctr-btn ctr-btn-orange"
                            style="width: 100%"
                            @click="updateStatus(activeMission, 'on_the_way')"
                            :disabled="actionLoading"
                        >
                            <div class="ctr-spinner" v-if="actionLoading"></div>
                            <span v-else>Je suis en route →</span>
                        </button>
                    </div>

                    <!-- Action : marquer arrivé -->
                    <div
                        class="ctr-action-block"
                        v-if="activeMission.status === 'tracking'"
                    >
                        <p>📍 Êtes-vous arrivé sur place ?</p>
                        <button
                            class="ctr-btn ctr-btn-orange"
                            style="width: 100%"
                            @click="updateStatus(activeMission, 'in_progress')"
                            :disabled="actionLoading"
                        >
                            <div class="ctr-spinner" v-if="actionLoading"></div>
                            <span v-else>Je suis arrivé sur place</span>
                        </button>
                    </div>

                    <!-- Action : soumettre un devis -->
                    <div
                        class="ctr-action-block"
                        v-if="activeMission.status === 'in_progress'"
                    >
                        <p>
                            📄 Vous pouvez maintenant soumettre votre devis au
                            client.
                        </p>
                        <button
                            class="ctr-btn ctr-btn-orange"
                            style="width: 100%"
                            @click="wip('Saisir le devis')"
                        >
                            Saisir le devis →
                        </button>
                    </div>

                    <!-- Action : marquer travaux terminés -->
                    <div
                        class="ctr-action-block"
                        v-if="activeMission.status === 'order_placed'"
                    >
                        <p>
                            🔨 Marquez les travaux comme terminés lorsque tout
                            est fini.
                        </p>
                        <button
                            class="ctr-btn ctr-btn-orange"
                            style="width: 100%"
                            @click="
                                updateStatus(activeMission, 'awaiting_confirm')
                            "
                            :disabled="actionLoading"
                        >
                            <div class="ctr-spinner" v-if="actionLoading"></div>
                            <span v-else>Travaux terminés ✓</span>
                        </button>
                    </div>
                </div>
                <div class="ctr-modal-footer">
                    <button
                        class="ctr-btn ctr-btn-ghost"
                        @click="activeMission = null"
                    >
                        Fermer
                    </button>
                    <button
                        class="ctr-btn ctr-btn-ghost"
                        @click="wip('Messagerie')"
                        v-if="
                            [
                                'accepted',
                                'contact_made',
                                'on_the_way',
                                'tracking',
                                'in_progress',
                                'order_placed',
                            ].includes(activeMission.status)
                        "
                    >
                        💬 Contacter le client
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
                missions_show: "/contractor/missions/{id}",
                missions_status: "/contractor/missions/{id}/status",
                notifications: "/notifications",
                notifications_read: "/notifications/{id}/read",
                notifications_all: "/notifications/read-all",
                availability: "/contractor/disponibilite",
                documents_upload: "/documents/upload",
                documents_index: "/documents",
                profil: "/contractor/profil",
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
            toasts: [],
            toastId: 0,
            actionLoading: false,
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
            if (this.tab === "all") return this.missions;
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
            if (this.tab === "active")
                return this.missions.filter((m) => active.includes(m.status));
            if (this.tab === "assigned")
                return this.missions.filter((m) => m.status === "assigned");
            if (this.tab === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                );
            if (this.tab === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled");
            return this.missions;
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
                label: "Casier judiciaire vierge",
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
</style>
