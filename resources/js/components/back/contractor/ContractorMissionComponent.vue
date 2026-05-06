<template>
    <div class="ctm-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="ctm-topbar">
            <div class="ctm-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="ctm-page-title">Mes missions</div>
                    <div class="ctm-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="ctm-topbar-right">
                <!-- Cloche notifications -->
                <div class="ctm-notif-wrap" ref="notifWrap">
                    <button class="ctm-notif-btn" @click="toggleNotif">
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
                        <span class="ctm-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount
                        }}</span>
                    </button>
                    <div class="ctm-notif-dropdown" v-if="notifOpen">
                        <div class="ctm-notif-header">
                            <span>Notifications</span>
                            <button
                                class="ctm-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="ctm-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="ctm-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="ctm-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="ctm-notif-dot" v-if="!n.read"></div>
                            <div class="ctm-notif-content">
                                <div class="ctm-notif-title">{{ n.title }}</div>
                                <div class="ctm-notif-body">{{ n.body }}</div>
                                <div class="ctm-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ctm-avatar" :class="{ 'has-photo': user.photo_url }">
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        :alt="user.name"
                        class="ctm-avatar-img"
                    />
                    <span v-else>{{ userInitials }}</span>
                </div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="ctm-content">

            <!-- ── Bannière accès limité ── -->
            <div class="ctm-pending-banner" v-if="showAvailableMissions">
                <div class="ctm-pending-banner-icon">🔒</div>
                <div>
                    <div class="ctm-pending-banner-title" v-if="userStatus === 'pending'">Dossier en cours de vérification</div>
                    <div class="ctm-pending-banner-title" v-else>Accréditation requise</div>
                    <div class="ctm-pending-banner-sub">{{ availableMissionsMessage }}</div>
                </div>
            </div>

            <!-- ── Aperçu missions disponibles ── -->
            <template v-if="showAvailableMissions">
                <div class="ctm-available-section">
                    <div class="ctm-available-title">
                        Missions disponibles sur la plateforme
                        <span class="ctm-tab-count">{{ availableMissions.length }}</span>
                    </div>
                    <div class="ctm-loading" v-if="availableLoading">
                        <div class="ctm-skeleton-row" v-for="n in 3" :key="n"></div>
                    </div>
                    <div class="ctm-empty" v-else-if="availableMissions.length === 0">
                        <div class="ctm-empty-icon">📭</div>
                        <div class="ctm-empty-title">Aucune mission disponible pour le moment</div>
                    </div>
                    <div class="ctm-list" v-else>
                        <div
                            class="ctm-item ctm-item-locked"
                            v-for="m in availableMissions"
                            :key="m.id"
                        >
                            <div class="ctm-item-icon">{{ serviceIcon(m.service) }}</div>
                            <div class="ctm-item-body">
                                <div class="ctm-item-title">
                                    {{ m.service }}
                                    <span class="ctm-type-chip" :class="m.location_type">{{
                                        m.location_type === "business" ? "🏢" : "🏠"
                                    }}</span>
                                </div>
                                <div class="ctm-item-addr">📍 {{ m.address }}</div>
                                <div class="ctm-item-meta">{{ formatDate(m.created_at) }}</div>
                                <div class="ctm-item-imgs" v-if="m.images && m.images.length">
                                    <img v-for="(url, i) in m.images.slice(0, 4)" :key="i" :src="url" class="ctm-item-img-thumb" />
                                    <span class="ctm-item-imgs-more" v-if="m.images.length > 4">+{{ m.images.length - 4 }}</span>
                                </div>
                            </div>
                            <div class="ctm-item-right">
                                <span class="ctm-badge ctm-badge-locked">🔒 Documents à vérifier</span>
                                <button
                                    type="button"
                                    class="ctm-btn ctm-btn-disabled"
                                    disabled
                                >
                                    Postuler indisponible
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ── Vue normale (prestataire validé) ── -->
            <template v-else>
                <!-- Filtres + recherche -->
                <div class="ctm-filters">
                    <div class="ctm-search-wrap">
                        <span class="ctm-search-icon">🔍</span>
                        <input
                            class="ctm-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher une mission…"
                        />
                    </div>
                    <div class="ctm-tabs">
                        <button
                            class="ctm-tab"
                            v-for="t in tabs"
                            :key="t.key"
                            :class="{ active: activeTab === t.key }"
                            @click="activeTab = t.key"
                        >
                            {{ t.label }}
                            <span class="ctm-tab-count">{{
                                countByTab(t.key)
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Loader -->
                <div class="ctm-loading" v-if="loading">
                    <div class="ctm-skeleton-row" v-for="n in 5" :key="n"></div>
                </div>

                <!-- Erreur -->
                <div class="ctm-alert-error" v-else-if="error">
                    ⚠️ {{ error }}
                    <button
                        class="ctm-btn ctm-btn-ghost ctm-btn-sm"
                        @click="fetchMissions"
                    >
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div class="ctm-empty" v-else-if="filteredMissions.length === 0">
                    <div class="ctm-empty-icon">📭</div>
                    <div class="ctm-empty-title">
                        Aucune mission{{
                            activeTab !== "all" ? " dans cette catégorie" : ""
                        }}
                    </div>
                    <div class="ctm-empty-sub">
                        Restez disponible pour recevoir de nouvelles missions.
                    </div>
                </div>

                <!-- Liste -->
                <div class="ctm-list" v-else>
                    <div
                        class="ctm-item"
                        v-for="m in filteredMissions"
                        :key="m.id"
                        @click="openMission(m)"
                        :class="{ 'ctm-item-urgent': m.status === 'assigned' }"
                    >
                        <div class="ctm-item-icon">
                            {{ serviceIcon(m.service) }}
                        </div>
                        <div class="ctm-item-body">
                            <div class="ctm-item-title">
                                {{ m.service }}
                                <span
                                    class="ctm-type-chip"
                                    :class="m.location_type"
                                    >{{
                                        m.location_type === "business" ? "🏢" : "🏠"
                                    }}</span
                                >
                            </div>
                            <div class="ctm-item-meta">
                                👤 {{ m.client?.name ?? "—" }} ·
                                {{ formatDate(m.created_at) }}
                            </div>
                            <div class="ctm-item-addr">📍 {{ m.address }}</div>
                            <div class="ctm-item-imgs" v-if="m.images && m.images.length">
                                <img v-for="(url, i) in m.images.slice(0, 4)" :key="i" :src="url" class="ctm-item-img-thumb" @click.stop="window.open(url, '_blank')" />
                                <span class="ctm-item-imgs-more" v-if="m.images.length > 4">+{{ m.images.length - 4 }}</span>
                            </div>
                            <div
                                class="ctm-msg-unread"
                                v-if="unreadByMission[m.id]"
                            >
                                💬 {{ unreadByMission[m.id] }} message{{
                                    unreadByMission[m.id] > 1 ? "s" : ""
                                }}
                                non lu{{ unreadByMission[m.id] > 1 ? "s" : "" }}
                            </div>
                        </div>
                        <div class="ctm-item-right">
                            <span class="ctm-badge" :class="badgeClass(m.status)">{{
                                labelOf(m)
                            }}</span>
                            <div class="ctm-item-price" v-if="m.total_amount">
                                {{ formatPrice(m.total_amount * 0.9) }}
                            </div>
                            <div class="ctm-item-date">
                                {{ formatDate(m.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ══════════════ PANEL DÉTAIL MISSION ══════════════ -->
        <div
            class="ctm-panel-overlay"
            v-if="activeMission"
            @click.self="activeMission = null"
        >
            <div class="ctm-panel">
                <!-- Header panel -->
                <div class="ctm-panel-header">
                    <div>
                        <h2>{{ activeMission.service }}</h2>
                        <div class="ctm-panel-sub">
                            Mission #{{ activeMission.id }} ·
                            {{ formatDateTime(activeMission.created_at) }}
                        </div>
                    </div>
                    <div class="ctm-panel-header-right">
                        <span
                            class="ctm-badge"
                            :class="badgeClass(activeMission.status)"
                            >{{ labelOf(activeMission) }}</span
                        >
                        <button
                            class="ctm-panel-close"
                            @click="activeMission = null"
                        >
                            &#215;
                        </button>
                    </div>
                </div>

                <!-- Barre de progression -->
                <div class="ctm-workflow">
                    <div class="ctm-workflow-track">
                        <div
                            class="ctm-workflow-fill"
                            :style="{
                                width: (stepOf(activeMission) / 12) * 100 + '%',
                            }"
                        ></div>
                    </div>
                    <div class="ctm-wf-steps">
                        <div
                            class="ctm-wf-step"
                            v-for="s in workflowSteps"
                            :key="s.step"
                            :class="{
                                'wf-done': stepOf(activeMission) > s.step,
                                'wf-current': stepOf(activeMission) === s.step,
                                'wf-pending': stepOf(activeMission) < s.step,
                            }"
                        >
                            <div class="ctm-wf-dot"></div>
                            <div class="ctm-wf-label">{{ s.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="ctm-panel-body">
                    <div class="ctm-panel-cols">
                        <!-- Colonne infos -->
                        <div class="ctm-info-col">
                            <div class="ctm-section">
                                <div class="ctm-section-title">📋 Détails</div>
                                <div class="ctm-row">
                                    <span>Type</span
                                    ><strong>{{
                                        activeMission.location_type ===
                                        "business"
                                            ? "🏢 Entreprise"
                                            : "🏠 Domicile"
                                    }}</strong>
                                </div>
                                <div class="ctm-row">
                                    <span>Adresse</span
                                    ><strong>{{
                                        activeMission.address
                                    }}</strong>
                                </div>
                                <div class="ctm-row">
                                    <span>Description</span
                                    ><strong>{{
                                        activeMission.description ?? "—"
                                    }}</strong>
                                </div>
                                <div
                                    class="ctm-row"
                                    v-if="activeMission.total_amount"
                                >
                                    <span>Votre part (90%)</span>
                                    <strong class="ctm-val-green">{{
                                        formatPrice(
                                            activeMission.total_amount * 0.9
                                        )
                                    }}</strong>
                                </div>
                            </div>

                            <div
                                class="ctm-distance-block"
                                v-if="activeMission.status === 'assigned' && activeMission.latitude && activeMission.longitude"
                            >
                                <div v-if="contractorGeo.loading" class="ctm-distance-loading">
                                    <span class="ctm-spinner"></span>
                                    Localisation en cours...
                                </div>
                                <div v-else-if="missionDistance !== null" class="ctm-distance-value">
                                    📍
                                    <template v-if="missionDistance < 1">
                                        Vous êtes à <strong>{{ Math.round(missionDistance * 1000) }} m</strong> du client
                                    </template>
                                    <template v-else>
                                        Vous êtes à <strong>{{ missionDistance.toFixed(1) }} km</strong> du client
                                    </template>
                                </div>
                                <div v-else-if="contractorGeo.error === 'denied'" class="ctm-distance-error">
                                    🔒 Activez la géolocalisation dans les paramètres de votre navigateur.
                                </div>
                                <div v-else-if="contractorGeo.error" class="ctm-distance-error">
                                    Impossible d'obtenir votre position.
                                    <button class="ctm-retry-btn" @click="requestGeoAndComputeDistance">Réessayer</button>
                                </div>
                            </div>

                            <div
                                class="ctm-section"
                                v-if="activeMission.client"
                            >
                                <div class="ctm-section-title">👤 Client</div>
                                <div class="ctm-row">
                                    <span>Nom</span>
                                    <strong>
                                        {{ activeMission.client.name }}
                                        <span
                                            v-if="
                                                activeMission.client.is_verified
                                            "
                                            class="ctm-verified-badge"
                                            title="Identité vérifiée"
                                            >✅ Identité vérifiée</span
                                        >
                                    </strong>
                                </div>
                                <div class="ctm-row">
                                    <span>Contact</span
                                    ><strong class="ctm-masked"
                                        >🔒 Numéro masqué</strong
                                    >
                                </div>
                            </div>

                            <!-- Résumé devis existant -->
                            <div
                                class="ctm-section ctm-quote-summary"
                                v-if="
                                    activeMission.quote &&
                                    activeMission.quote.status !== 'draft'
                                "
                            >
                                <div class="ctm-section-title">
                                    📄 Devis
                                    {{
                                        activeMission.quote.version > 1
                                            ? "v" + activeMission.quote.version
                                            : ""
                                    }}
                                </div>
                                <div
                                    class="ctm-row"
                                    v-for="item in activeMission.quote.items"
                                    :key="item.id"
                                >
                                    <span>{{ item.description }}</span>
                                    <strong>{{
                                        formatPrice(
                                            item.quantity * item.unit_price
                                        )
                                    }}</strong>
                                </div>
                                <div class="ctm-row ctm-quote-total">
                                    <span>Total</span>
                                    <strong class="ctm-val-green">{{
                                        formatPrice(
                                            activeMission.quote.amount_incl_tax
                                        )
                                    }}</strong>
                                </div>
                                <div class="ctm-quote-summary-footer">
                                    <div
                                        class="ctm-quote-status-badge"
                                        :class="effectiveQuoteStatus(activeMission)"
                                    >
                                        {{
                                            quoteStatusLabel(
                                                effectiveQuoteStatus(activeMission)
                                            )
                                        }}
                                    </div>
                                    <button
                                        class="ctm-quote-pdf-btn"
                                        @click="downloadQuotePdf(activeMission)"
                                        title="Consulter / Télécharger PDF"
                                    >
                                        ⬇ PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne actions -->
                        <div class="ctm-actions-col">
                            <!-- Proposée : accepter / refuser -->
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
                                        @click="
                                            updateStatus(
                                                activeMission,
                                                'accepted'
                                            )
                                        "
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

                            <!-- Acceptée / Contact établi : en route -->
                            <div
                                class="ctm-action-block"
                                v-if="
                                    ['accepted', 'contact_made'].includes(
                                        activeMission.status
                                    )
                                "
                            >
                                <p>
                                    Prêt à partir ? Informez le client en cliquant sur le bouton ci-dessous.
                                </p>
                                <button
                                    class="ctm-btn ctm-btn-orange ctm-btn-full"
                                    @click="
                                        updateStatus(
                                            activeMission,
                                            'on_the_way'
                                        )
                                    "
                                    :disabled="actionLoading"
                                >
                                    <div
                                        class="ctm-spinner"
                                        v-if="actionLoading"
                                    ></div>
                                    <span v-else>🚗 Je suis en route →</span>
                                </button>
                            </div>

                            <!-- En route / tracking : arrivé -->
                            <div
                                class="ctm-action-block"
                                v-if="
                                    ['on_the_way', 'tracking'].includes(
                                        activeMission.status
                                    )
                                "
                            >
                                <p>📍 Êtes-vous arrivé sur place ?</p>
                                <button
                                    class="ctm-btn ctm-btn-orange ctm-btn-full"
                                    @click="
                                        updateStatus(
                                            activeMission,
                                            'in_progress'
                                        )
                                    "
                                    :disabled="actionLoading"
                                >
                                    <div
                                        class="ctm-spinner"
                                        v-if="actionLoading"
                                    ></div>
                                    <span v-else
                                        >📍 Je suis arrivé sur place</span
                                    >
                                </button>
                            </div>

                            <!-- ── En cours / devis soumis rejeté : saisir le devis ── -->
                            <div
                                class="ctm-action-block ctm-action-quote"
                                v-if="
                                    activeMission.status === 'in_progress' ||
                                    (activeMission.status ===
                                        'quote_submitted' &&
                                        activeMission.quote?.status ===
                                            'rejected')
                                "
                            >
                                <div class="ctm-action-quote-icon">📄</div>
                                <div class="ctm-action-quote-title">
                                    {{
                                        activeMission.quote?.status ===
                                        "rejected"
                                            ? "Devis refusé par le client"
                                            : "Saisir le devis"
                                    }}
                                </div>
                                <div
                                    class="ctm-action-quote-sub"
                                    v-if="
                                        activeMission.quote?.status ===
                                            'rejected' &&
                                        activeMission.reported_issue
                                    "
                                >
                                    ❌ Motif :
                                    <em
                                        >«
                                        {{ activeMission.reported_issue }} »</em
                                    >
                                </div>
                                <div
                                    class="ctm-action-quote-sub"
                                    v-else-if="
                                        activeMission.quote?.status ===
                                        'rejected'
                                    "
                                >
                                    ❌ Le client a refusé votre devis. Vous
                                    pouvez le réviser ou abandonner la mission.
                                </div>
                                <div class="ctm-action-quote-sub" v-else>
                                    Diagnostiquez la situation et détaillez les
                                    pièces nécessaires.
                                </div>
                                <button
                                    class="ctm-btn ctm-btn-orange ctm-btn-full"
                                    @click="openQuoteModal(activeMission)"
                                    style="margin-bottom: 8px"
                                >
                                    📄
                                    {{
                                        activeMission.quote?.status ===
                                        "rejected"
                                            ? "Réviser le devis →"
                                            : "Saisir le devis →"
                                    }}
                                </button>
                                <button
                                    v-if="
                                        activeMission.quote?.status ===
                                        'rejected'
                                    "
                                    class="ctm-btn ctm-btn-ghost ctm-btn-full"
                                    @click="openAbandonModal(activeMission)"
                                    :disabled="actionLoading"
                                >
                                    🚪 Abandonner la mission
                                </button>
                            </div>

                            <!-- Devis soumis en attente d'approbation -->
                            <div
                                class="ctm-action-block ctm-action-wait"
                                v-if="
                                    activeMission.status ===
                                        'quote_submitted' &&
                                    activeMission.quote?.status !== 'rejected'
                                "
                            >
                                <div class="ctm-action-wait-icon">⏳</div>
                                <div>
                                    <div class="ctm-action-wait-title">
                                        Devis en attente d'approbation
                                    </div>
                                    <div class="ctm-action-wait-sub">
                                        Le client doit approuver votre devis
                                        pour démarrer les travaux.
                                    </div>
                                </div>
                            </div>

                            <!-- Commande passée : travaux terminés -->
                            <div
                                class="ctm-action-block"
                                v-if="activeMission.status === 'order_placed'"
                            >
                                <p>
                                    🔨 Le client a approuvé votre devis. Marquez
                                    les travaux comme terminés lorsque tout est
                                    fini.
                                </p>
                                <button
                                    class="ctm-btn ctm-btn-orange ctm-btn-full"
                                    @click="
                                        updateStatus(
                                            activeMission,
                                            'awaiting_confirm'
                                        )
                                    "
                                    :disabled="actionLoading"
                                >
                                    <div
                                        class="ctm-spinner"
                                        v-if="actionLoading"
                                    ></div>
                                    <span v-else>✓ Travaux terminés</span>
                                </button>
                            </div>

                            <!-- Att. confirmation -->
                            <div
                                class="ctm-action-block ctm-action-wait"
                                v-if="
                                    activeMission.status === 'awaiting_confirm'
                                "
                            >
                                <div class="ctm-action-wait-icon">⏳</div>
                                <div>
                                    <div class="ctm-action-wait-title">
                                        En attente du client
                                    </div>
                                    <div class="ctm-action-wait-sub">
                                        Le client doit confirmer la fin des
                                        travaux pour déclencher votre paiement.
                                    </div>
                                </div>
                            </div>

                            <!-- Terminée / clôturée -->
                            <div
                                class="ctm-action-block ctm-action-done"
                                v-if="
                                    ['completed', 'closed'].includes(
                                        activeMission.status
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
                                                activeMission.total_amount * 0.9
                                            )
                                        }}
                                        a été effectué.
                                    </div>
                                </div>
                            </div>

                            <!-- Chat -->
                            <button
                                class="ctm-btn ctm-btn-chat ctm-btn-full"
                                @click="openChat(activeMission.id)"
                                v-if="
                                    [
                                        'accepted',
                                        'contact_made',
                                        'on_the_way',
                                        'tracking',
                                        'in_progress',
                                        'quote_submitted',
                                        'order_placed',
                                        'awaiting_confirm',
                                    ].includes(activeMission.status)
                                "
                            >
                                💬 Contacter le client
                                <span
                                    class="ctm-chat-badge"
                                    v-if="unreadByMission[activeMission.id]"
                                    >{{
                                        unreadByMission[activeMission.id]
                                    }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REFUS ══════════════ -->
        <div
            class="ctm-modal-overlay"
            v-if="refuseModal.visible"
            @click.self="refuseModal.visible = false"
        >
            <div class="ctm-modal">
                <div class="ctm-modal-header">
                    <h3>Refuser la mission</h3>
                    <button
                        class="ctm-modal-close"
                        @click="refuseModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="ctm-modal-body">
                    <p class="ctm-refuse-info">
                        ⚠️ La mission sera réattribuée à un autre prestataire.
                    </p>
                    <div class="ctm-refuse-options">
                        <label
                            class="ctm-radio-opt"
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
                        class="ctm-input"
                        type="text"
                        v-model="refuseModal.customReason"
                        v-if="refuseModal.reason === 'other'"
                        placeholder="Précisez votre motif…"
                    />
                </div>
                <div class="ctm-modal-footer">
                    <button
                        class="ctm-btn ctm-btn-ghost"
                        @click="refuseModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="ctm-btn ctm-btn-red"
                        @click="confirmRefuse"
                        :disabled="!refuseModal.reason || refuseModal.loading"
                    >
                        <div
                            class="ctm-spinner"
                            v-if="refuseModal.loading"
                        ></div>
                        <span v-else>✗ Confirmer le refus</span>
                    </button>
                </div>
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
                    <div class="ctm-modal-header-inner">
                        <div class="ctm-modal-header-icon">📋</div>
                        <div>
                            <h3>{{ quoteModal.isRevision ? "Réviser le devis" : "Saisir le devis" }}</h3>
                            <div class="ctm-modal-subtitle">
                                Mission #{{ quoteModal.mission?.id }} · {{ quoteModal.mission?.service }}
                            </div>
                        </div>
                    </div>
                    <button class="ctm-modal-close" @click="closeQuoteModal">&#215;</button>
                </div>

                <div class="ctm-modal-body ctm-modal-body-quote">

                    <!-- ── 1. DIAGNOSTIC ── -->
                    <div class="ctm-qblock">
                        <div class="ctm-qblock-title">
                            🔍 Diagnostic
                            <span class="ctm-commission-tag">{{ commissionDiagnostic }}% Mesotravo</span>
                        </div>
                        <div class="ctm-diag-amount-row">
                            <div class="ctm-diag-amount-val">
                                {{ formatPrice(quoteModal.diagnostic_fee || 0) }}
                            </div>
                            <div class="ctm-diag-amount-hint">
                                Montant défini par l'administrateur
                            </div>
                        </div>
                        <div class="ctm-qfield" style="margin-top:6px">
                            <label class="ctm-qlabel">Notes <span class="ctm-optional">(optionnel)</span></label>
                            <textarea
                                class="ctm-qtextarea"
                                v-model="quoteModal.diagnosis"
                                placeholder="Décrivez ce que vous avez constaté sur place…"
                                rows="2"
                            ></textarea>
                        </div>
                    </div>

                    <!-- ── 2. PIÈCES & MATÉRIAUX ── -->
                    <div class="ctm-qblock">
                        <div class="ctm-qblock-head">
                            <div class="ctm-qblock-title">
                                🔩 Pièces & matériaux
                            </div>
                            <button class="ctm-add-line-btn" @click="addPartLine">
                                + Ajouter
                            </button>
                        </div>

                        <div class="ctm-parts-table">
                            <div class="ctm-parts-empty" v-if="quoteModal.partLines.length === 0">
                                Aucune pièce — cliquez « + Ajouter »
                            </div>

                            <!-- Card mobile par ligne -->
                            <div
                                class="ctm-part-card"
                                v-for="(line, idx) in quoteModal.partLines"
                                :key="line._id"
                            >
                                <div class="ctm-part-card-top">
                                    <input
                                        class="ctm-qinput ctm-part-desc"
                                        type="text"
                                        v-model="line.description"
                                        placeholder="Nom de la pièce / matériau…"
                                    />
                                    <button class="ctm-del-btn" @click="removePartLine(idx)" title="Supprimer">✕</button>
                                </div>
                                <div class="ctm-part-card-bottom">
                                    <div class="ctm-part-field">
                                        <label class="ctm-qlabel">Qté</label>
                                        <input class="ctm-qinput" type="number" v-model.number="line.quantity" min="0.01" step="1" placeholder="1" />
                                    </div>
                                    <div class="ctm-part-field">
                                        <label class="ctm-qlabel">Prix unit. (FCFA)</label>
                                        <input class="ctm-qinput" type="number" v-model.number="line.unit_price" min="0" step="100" placeholder="0" />
                                    </div>
                                    <div class="ctm-part-field ctm-part-total-field">
                                        <label class="ctm-qlabel">Total</label>
                                        <div class="ctm-parts-total">{{ formatPrice(lineTotal(line)) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── 3. MAIN D'ŒUVRE ── -->
                    <div class="ctm-qblock">
                        <div class="ctm-qblock-title">
                            🛠️ Main d'œuvre
                            <span class="ctm-commission-tag">{{ commissionMainOeuvre }}% Mesotravo</span>
                        </div>
                        <div class="ctm-labor-row">
                            <div class="ctm-qfield ctm-labor-desc">
                                <label class="ctm-qlabel">Description</label>
                                <input
                                    class="ctm-qinput"
                                    type="text"
                                    v-model="quoteModal.labor.description"
                                    placeholder="Ex : Pose et raccordement…"
                                />
                            </div>
                            <div class="ctm-qfield ctm-labor-amount">
                                <label class="ctm-qlabel">Montant (FCFA)</label>
                                <input
                                    class="ctm-qinput"
                                    type="number"
                                    v-model.number="quoteModal.labor.unit_price"
                                    min="0"
                                    step="100"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ── 4. RÉCAPITULATIF ── -->
                    <div class="ctm-recap">
                        <div class="ctm-recap-row" v-if="quoteModal.diagnostic_fee > 0">
                            <span>Diagnostic</span>
                            <div class="ctm-recap-vals">
                                <span class="ctm-recap-net-line">Votre part : {{ formatPrice((quoteModal.diagnostic_fee || 0) * (1 - commissionDiagnostic / 100)) }}</span>
                                <strong>{{ formatPrice(quoteModal.diagnostic_fee || 0) }}</strong>
                            </div>
                        </div>
                        <div class="ctm-recap-row" v-if="partsSubtotal > 0">
                            <span>Pièces & matériaux</span>
                            <div class="ctm-recap-vals">
                                <strong>{{ formatPrice(partsSubtotal) }}</strong>
                            </div>
                        </div>
                        <div class="ctm-recap-row" v-if="(quoteModal.labor.unit_price || 0) > 0">
                            <span>Main d'œuvre</span>
                            <div class="ctm-recap-vals">
                                <span class="ctm-recap-net-line">Votre part : {{ formatPrice((quoteModal.labor.unit_price || 0) * (1 - commissionMainOeuvre / 100)) }}</span>
                                <strong>{{ formatPrice(quoteModal.labor.unit_price || 0) }}</strong>
                            </div>
                        </div>
                        <div class="ctm-recap-divider"></div>
                        <div class="ctm-recap-total">
                            <span>Total devis</span>
                            <strong>{{ formatPrice(quoteTotal) }}</strong>
                        </div>
                        <div class="ctm-recap-net-total">
                            💰 Votre net estimé :
                            <strong>{{ formatPrice(contractorNet) }}</strong>
                        </div>
                    </div>

                    <!-- Erreur -->
                    <div class="ctm-quote-error" v-if="quoteModal.error">
                        ⚠️ {{ quoteModal.error }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="ctm-modal-footer ctm-modal-footer-quote">
                    <button class="ctm-btn ctm-btn-ghost" @click="closeQuoteModal">
                        Annuler
                    </button>
                    <button
                        class="ctm-btn ctm-btn-ghost ctm-btn-draft success-action"
                        @click="submitQuote('draft')"
                        :disabled="quoteModal.loading || !quoteIsValid"
                    >
                        <div class="ctm-spinner ctm-spinner-dark" v-if="quoteModal.loading === 'draft'"></div>
                        <span v-else>💾 Brouillon</span>
                    </button>
                    <button
                        class="ctm-btn ctm-btn-green"
                        @click="submitQuote('submit')"
                        :disabled="quoteModal.loading || !quoteIsValid"
                    >
                        <div class="ctm-spinner" v-if="quoteModal.loading === 'submit'"></div>
                        <span v-else>📤 Soumettre</span>
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
                        Mesotravo.
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
                            font-family: 'Poppins', sans-serif;
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

        <!-- ══════════════ CHAT MODAL ══════════════ -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- TOASTS -->
        <div class="ctm-toast-container">
            <div
                class="ctm-toast"
                v-for="t in toasts"
                :key="t.id"
                :class="t.type"
            >
                {{ t.message }}
            </div>
        </div>
    </div>

    <!-- ══════════════ POPUP ACCRÉDITATION ENTREPRISE ══════════════ -->
    <div
        class="ctm-modal-overlay"
        v-if="accreditationPopup.visible"
        @click.self="accreditationPopup.visible = false"
    >
        <div class="ctm-modal ctm-modal-accred">
            <button class="ctm-modal-close ctm-modal-accred-close" @click="accreditationPopup.visible = false">&#215;</button>
            <div class="ctm-accred-icon">🏅</div>
            <h3 class="ctm-accred-title">
                {{ accreditationPopup.remaining === 0 ? 'Vous êtes éligible !' : 'Mission terminée !' }}
            </h3>
            <p class="ctm-accred-sub">
                Vous avez désormais
                <strong>{{ accreditationPopup.completedCount }} mission{{ accreditationPopup.completedCount > 1 ? 's' : '' }} terminée{{ accreditationPopup.completedCount > 1 ? 's' : '' }}</strong>
                sur Mesotravo.
            </p>
            <div class="ctm-accred-progress-wrap">
                <div class="ctm-accred-progress-track">
                    <div
                        class="ctm-accred-progress-fill"
                        :style="{ width: Math.min(100, (accreditationPopup.completedCount / 5) * 100) + '%' }"
                    ></div>
                </div>
                <div class="ctm-accred-progress-label">
                    {{ Math.min(accreditationPopup.completedCount, 5) }} / 5 missions
                </div>
            </div>
            <div class="ctm-accred-msg" v-if="accreditationPopup.remaining > 0">
                Il vous reste encore
                <strong>{{ accreditationPopup.remaining }} mission{{ accreditationPopup.remaining > 1 ? 's' : '' }}</strong>
                pour pouvoir demander l'accréditation <strong>Entreprise</strong>.
                Continuez sur votre lancée ! 🚀
            </div>
            <div class="ctm-accred-msg ctm-accred-msg--ok" v-else>
                🎉 Félicitations ! Vous pouvez maintenant demander l'accréditation
                <strong>Entreprise</strong> depuis votre espace accréditation.
            </div>
            <div class="ctm-modal-footer" style="margin-top: 20px;">
                <button class="ctm-btn ctm-btn-ghost" @click="accreditationPopup.visible = false">
                    Fermer
                </button>
                <a
                    v-if="accreditationPopup.remaining === 0 && routes.accreditation_page"
                    :href="routes.accreditation_page"
                    class="ctm-btn ctm-btn-orange"
                    style="text-decoration:none"
                >
                    🏅 Demander l'accréditation →
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import MissionChatModal from "../../MissionChatModal.vue";

export default {
    name: "ContractorMissionComponent",
    components: { MissionChatModal },

    props: {
        user:                 { type: Object,  required: true },
        routes:               { type: Object,  required: true },
        diagnosticFee:        { type: Number,  default: 0 },
        commissionDiagnostic: { type: Number,  default: 10 },
        commissionMainOeuvre: { type: Number,  default: 10 },
    },

    computed: {
        userStatus() {
            return this.user.status ?? 'pending';
        },
        showAvailableMissions() {
            if (this.userStatus !== 'approved') return true;
            if (!this.user.documents_verified) return true;
            if (this.userStatus === 'approved' && (this.user.accreditation ?? 'none') === 'none') return true;
            return false;
        },
        availableMissionsMessage() {
            if (this.userStatus !== 'approved' || !this.user.documents_verified) {
                return 'Vous pouvez consulter les missions proposées par les clients, mais vous devez faire vérifier vos documents avant de pouvoir postuler.';
            }
            return 'Vous devez obtenir des accréditations pour postuler aux missions.';
        },
    },

    data() {
        return {
            missions: [],
            loading: true,
            error: null,
            availableMissions: [],
            availableLoading: false,
            activeTab: "all",
            search: "",
            activeMission: null,
            actionLoading: false,

            chatMissionId: null,
            chatUnread: 0,
            unreadByMission: {},

            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            refuseModal: {
                visible: false,
                mission: null,
                reason: "",
                customReason: "",
                loading: false,
            },
            refuseOptions: [
                { value: "zone", label: "📍 Hors de ma zone" },
                { value: "specialty", label: "🔧 Hors de ma spécialité" },
                { value: "busy", label: "📆 Déjà occupé" },
                { value: "distance", label: "🚗 Trop loin" },
                { value: "other", label: "✏️ Autre motif" },
            ],

            // ── Quote modal ──────────────────────────────────────
            quoteModal: {
                visible: false,
                mission: null,
                isRevision: false,
                diagnosis: "",
                diagnostic_fee: 0,
                partLines: [],
                labor: {
                    description: "Main d'œuvre",
                    unit_price: 0,
                },
                loading: false, // 'draft' | 'submit' | false
                error: null,
                _lineId: 0,
            },

            abandonModal: {
                visible: false,
                mission: null,
                reason: "",
                loading: false,
            },

            accreditationPopup: {
                visible: false,
                completedCount: 0,
                remaining: 0,
            },

            toasts: [],
            toastId: 0,
            sidebarOpen: false,

            contractorGeo: {
                latitude: null,
                longitude: null,
                loading: false,
                error: null,
            },
            missionDistance: null,

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "🔄 En cours" },
                { key: "assigned", label: "📬 Proposées" },
                { key: "closed", label: "✅ Terminées" },
                { key: "cancelled", label: "❌ Annulées" },
            ],

            workflowSteps: [
                { step: 1, label: "En attente" },
                { step: 2, label: "Proposée" },
                { step: 3, label: "Acceptée" },
                { step: 4, label: "Contact établi" },
                { step: 5, label: "En route" },
                { step: 6, label: "Suivi" },
                { step: 7, label: "Sur place" },
                { step: 8, label: "Devis soumis" },
                { step: 9, label: "Commande passée" },
                { step: 10, label: "Att. confirmation" },
                { step: 11, label: "Terminée" },
                { step: 12, label: "Clôturée" },
            ],
        };
    },

    computed: {
        userInitials() {
            return (
                this.user.name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "PR"
            );
        },
        activeStatuses() {
            return [
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
        },
        filteredMissions() {
            let list = [...this.missions];
            if (this.activeTab === "active")
                list = list.filter((m) =>
                    this.activeStatuses.includes(m.status)
                );
            else if (this.activeTab === "assigned")
                list = list.filter((m) => m.status === "assigned");
            else if (this.activeTab === "closed")
                list = list.filter((m) =>
                    ["completed", "closed"].includes(m.status)
                );
            else if (this.activeTab === "cancelled")
                list = list.filter((m) => m.status === "cancelled");
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (m) =>
                        m.service?.toLowerCase().includes(q) ||
                        m.address?.toLowerCase().includes(q) ||
                        m.client?.name?.toLowerCase().includes(q)
                );
            }
            return list;
        },

        // ── Quote computed ──────────────────────────────────────
        partsSubtotal() {
            return this.quoteModal.partLines.reduce(
                (sum, l) => sum + this.lineTotal(l),
                0
            );
        },
        quoteTotal() {
            return (
                (Number(this.quoteModal.diagnostic_fee) || 0) +
                this.partsSubtotal +
                (Number(this.quoteModal.labor.unit_price) || 0)
            );
        },
        // Part nette prestataire : 70% diagnostic + 95% fournitures + 85% MO
        contractorNet() {
            const diagRate  = 1 - this.commissionDiagnostic / 100;
            const laborRate = 1 - this.commissionMainOeuvre / 100;
            const diag  = (Number(this.quoteModal.diagnostic_fee) || 0) * diagRate;
            const parts = this.partsSubtotal; // pas de commission sur les pièces
            const labor = (Number(this.quoteModal.labor.unit_price) || 0) * laborRate;
            return diag + parts + labor;
        },
        quoteIsValid() {
            const hasDiag     = (Number(this.quoteModal.diagnostic_fee) || 0) > 0;
            const hasValidPart = this.quoteModal.partLines.some(
                (l) => l.description?.trim() && l.quantity > 0 && l.unit_price >= 0
            );
            const hasLabor =
                (Number(this.quoteModal.labor.unit_price) || 0) > 0 &&
                this.quoteModal.labor.description?.trim();
            return hasDiag || hasValidPart || hasLabor;
        },
    },

    methods: {
        // ── Data ─────────────────────────────────────────────────
        syncActiveMission() {
            if (!this.activeMission?.id) return;
            const refreshed = this.missions.find(
                (mission) => mission.id === this.activeMission.id
            );
            this.activeMission = refreshed
                ? { ...this.activeMission, ...refreshed }
                : null;
        },

        async fetchMissions({ silent = false } = {}) {
            if (!silent) this.loading = true;
            if (!silent) this.error = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.missions = Array.isArray(data) ? data : data.data ?? [];
                this.syncActiveMission();
                this.checkAccreditationPopup();
            } catch {
                if (!silent) this.error = "Impossible de charger les missions.";
            } finally {
                if (!silent) this.loading = false;
            }
        },

        checkAccreditationPopup() {
            const accreditation = this.user.accreditation ?? 'none';
            if (['business', 'both'].includes(accreditation)) return;

            const completedMissions = this.missions.filter(m =>
                ['completed', 'closed'].includes(m.status)
            );
            const completedCount = completedMissions.length;
            if (completedCount === 0) return;

            const storageKey = 'meso_ack_comp_missions';
            const acknowledged = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const newCompleted = completedMissions.filter(m => !acknowledged.includes(m.id));

            if (newCompleted.length === 0) return;

            const newAcknowledged = [...acknowledged, ...newCompleted.map(m => m.id)];
            localStorage.setItem(storageKey, JSON.stringify(newAcknowledged));

            this.accreditationPopup = {
                visible: true,
                completedCount,
                remaining: Math.max(0, 5 - completedCount),
            };
        },

        async fetchAvailableMissions({ silent = false } = {}) {
            if (!this.routes.missions_available) return;
            if (!silent) this.availableLoading = true;
            try {
                const res = await fetch(this.routes.missions_available, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                this.availableMissions = await res.json();
            } catch {
                this.availableMissions = [];
            } finally {
                if (!silent) this.availableLoading = false;
            }
        },

        startMissionPolling() {
            this.stopMissionPolling();
            this.missionPollInterval = setInterval(() => {
                if (this.showAvailableMissions) {
                    this.fetchAvailableMissions({ silent: true });
                    return;
                }
                this.fetchMissions({ silent: true });
            }, 5000);
        },

        stopMissionPolling() {
            if (!this.missionPollInterval) return;
            clearInterval(this.missionPollInterval);
            this.missionPollInterval = null;
        },

        // ── Mission actions ───────────────────────────────────────
        openMission(m) {
            this.activeMission = { ...m };
            this.missionDistance = null;
            this.requestGeoAndComputeDistance();
        },

        async updateStatus(mission, status) {
            this.actionLoading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    mission.id
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
                this.updateMissionInList(data.mission);
                this.activeMission = { ...data.mission };
                this.showToast("Statut mis à jour.", "success");
            } catch {
                this.showToast("Erreur réseau.", "error");
            } finally {
                this.actionLoading = false;
            }
        },

        // ── Quote modal ───────────────────────────────────────────
        openQuoteModal(mission) {
            const existingQuote = mission.quote;
            const isRevision =
                existingQuote &&
                ["submitted", "rejected"].includes(existingQuote.status);

            const existingDiagFee = existingQuote?.items?.find(
                (i) => i.type === "diagnostic"
            )?.unit_price ?? this.diagnosticFee ?? 0;

            this.quoteModal = {
                visible: true,
                mission: { ...mission },
                isRevision,
                diagnosis: existingQuote?.diagnosis ?? "",
                diagnostic_fee: Number(existingDiagFee),
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
                    ? existingQuote.items.filter((i) => i.type === "part").length
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

            // Diagnostic
            const diagFee = Number(this.quoteModal.diagnostic_fee) || 0;
            if (diagFee > 0) {
                items.push({
                    type:        "diagnostic",
                    description: "Diagnostic",
                    quantity:    1,
                    unit_price:  diagFee,
                });
            }

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
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.missions_quote_store.replace(
                    "{id}",
                    this.quoteModal.mission.id
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
                    "success"
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

        downloadQuotePdf(mission) {
            const quote = mission.quote;
            if (!quote) return;

            const typeLabels = {
                diagnostic: "Diagnostic",
                part: "Fourniture",
                labor: "Main d'œuvre",
                travel: "Déplacement",
                other: "Autre",
            };
            const typeBg = {
                diagnostic: "#f97316",
                part: "#3b82f6",
                labor: "#10b981",
                travel: "#8b5cf6",
                other: "#6b7280",
            };

            const lines = (quote.items ?? []).map((i) => {
                const total = parseFloat(i.quantity) * parseFloat(i.unit_price);
                const tl  = typeLabels[i.type] ?? i.type;
                const bg  = typeBg[i.type] ?? "#6b7280";
                return `<tr>
                    <td><span style="background:${bg};color:#fff;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;margin-right:8px;">${tl}</span>${i.description}</td>
                    <td style="text-align:center;">${i.quantity}</td>
                    <td style="text-align:right;">${this.formatPrice(i.unit_price)}</td>
                    <td style="text-align:right;font-weight:700;">${this.formatPrice(total)}</td>
                </tr>`;
            }).join("");

            const clientName = mission.client?.name ?? '';
            const html = `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devis #${quote.id} — Mission #${mission.id}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Arial,sans-serif;color:#1c1412;padding:32px 40px;max-width:800px;margin:0 auto}
  .print-btn{display:block;margin:0 auto 24px;padding:10px 28px;background:#f97316;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;letter-spacing:.3px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;padding-bottom:18px;border-bottom:2px solid #f97316}
  .brand{font-size:24px;font-weight:900;color:#f97316;letter-spacing:-.5px}
  .brand em{color:#1c1412;font-style:normal}
  .sub-brand{font-size:11px;color:#7c6a5a;margin-top:3px}
  .meta{font-size:12px;color:#7c6a5a;text-align:right;line-height:1.8}
  .meta strong{color:#1c1412;font-size:14px;display:block}
  .section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#7c6a5a;margin:20px 0 8px}
  .info-row{font-size:13px;color:#7c6a5a;margin-bottom:4px}
  .info-row strong{color:#1c1412}
  .diag-box{background:#fff7ed;border-left:3px solid #f97316;padding:10px 14px;font-size:13px;color:#7c6a5a;margin:16px 0;border-radius:0 6px 6px 0}
  .diag-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#f97316;margin-bottom:5px}
  table{width:100%;border-collapse:collapse;margin:10px 0}
  thead th{background:#f8f4f0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#7c6a5a;padding:10px 12px;text-align:left}
  thead th:nth-child(2){text-align:center}
  thead th:nth-child(3),thead th:nth-child(4){text-align:right}
  tbody td{padding:9px 12px;border-bottom:1px solid #f0e9e4;font-size:13px;vertical-align:middle}
  tbody td:nth-child(2){text-align:center}
  tbody td:nth-child(3),tbody td:nth-child(4){text-align:right}
  tfoot td{padding:14px 12px;font-weight:700;font-size:15px;border-top:2px solid #f97316}
  tfoot td:last-child{text-align:right;color:#f97316}
  .note{margin-top:28px;padding:12px 16px;background:#f8f4f0;border-radius:6px;font-size:11px;color:#7c6a5a;line-height:1.6}
  .footer{margin-top:24px;text-align:center;font-size:10px;color:#b0a09a;padding-top:14px;border-top:1px solid #e8ddd4}
  @media print{.print-btn{display:none!important}}
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">⬇ Enregistrer en PDF</button>
<div class="header">
  <div>
    <div class="brand">Meso<em>Travo</em></div>
    <div class="sub-brand">Plateforme de mise en relation</div>
  </div>
  <div class="meta">
    <strong>Devis n°${quote.id}${quote.version > 1 ? ' — Révision v' + quote.version : ''}</strong>
    Mission #${mission.id} · ${mission.service}<br>
    Émis le ${new Date().toLocaleDateString('fr-FR')}
  </div>
</div>

<div class="section-label">Détails de l'intervention</div>
<div class="info-row">📍 Adresse : <strong>${mission.address}</strong></div>
${clientName ? `<div class="info-row">👤 Client : <strong>${clientName}</strong></div>` : ''}

${quote.diagnosis ? `<div class="diag-box"><div class="diag-label">🔍 Diagnostic</div>${quote.diagnosis}</div>` : ''}

<div class="section-label">Lignes du devis</div>
<table>
  <thead><tr>
    <th>Désignation</th><th>Qté</th><th>Prix unitaire</th><th>Total</th>
  </tr></thead>
  <tbody>${lines}</tbody>
  <tfoot><tr>
    <td colspan="3">Total TTC</td>
    <td>${this.formatPrice(quote.amount_incl_tax)}</td>
  </tr></tfoot>
</table>

<div class="note">⚠️ Ce devis est soumis à approbation via la plateforme Mesotravo. Aucun paiement hors-plateforme n'est autorisé ni conseillé.</div>
<div class="footer">Mesotravo.com · Plateforme de mise en relation artisans &amp; particuliers</div>
</body></html>`;

            const win = window.open('', '_blank', 'width=860,height=720');
            if (win) { win.document.write(html); win.document.close(); }
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

        // ── Abandon mission ───────────────────────────────────────
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
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    this.abandonModal.mission.id
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
                    (m) => m.id !== this.abandonModal.mission.id
                );
                this.abandonModal.visible = false;
                this.activeMission = null;
                this.showToast(
                    "Mission abandonnée. L'équipe Mesotravo a été notifiée.",
                    "success"
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
                    : this.refuseOptions.find(
                          (o) => o.value === this.refuseModal.reason
                      )?.label ?? "";
            this.refuseModal.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.missions_status.replace(
                    "{id}",
                    this.refuseModal.mission.id
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
                    (m) => m.id !== this.refuseModal.mission.id
                );
                this.refuseModal.visible = false;
                this.activeMission = null;
                this.showToast(
                    "Mission refusée. Elle sera réattribuée automatiquement.",
                    "success"
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
                    'meta[name="csrf-token"]'
                )?.content;
                await fetch(
                    this.routes.notifications_read.replace("{id}", n.id),
                    {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            Accept: "application/json",
                        },
                    }
                );
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

        // ── Helpers ───────────────────────────────────────────────
        updateMissionInList(updated) {
            const idx = this.missions.findIndex((m) => m.id === updated.id);
            if (idx !== -1)
                this.missions.splice(idx, 1, {
                    ...this.missions[idx],
                    ...updated,
                });
        },
        countByTab(key) {
            if (key === "all") return this.missions.length;
            if (key === "active")
                return this.missions.filter((m) =>
                    this.activeStatuses.includes(m.status)
                ).length;
            if (key === "assigned")
                return this.missions.filter((m) => m.status === "assigned")
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
        labelOf(m) {
            const map = {
                pending: "En attente",
                assigned: "Proposée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi",
                in_progress: "Sur place",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "Att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return m.status_label ?? map[m.status] ?? m.status;
        },
        badgeClass(s) {
            const m = {
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
            return m[s] ?? "";
        },
        serviceIcon(s) {
            return (
                {
                    plomberie: "🔧",
                    electricite: "⚡",
                    menuiserie: "🪵",
                    ferronnerie: "⚙️",
                    frigoriste: "❄️",
                    mecanique: "🚗",
                    nettoyage: "🧹",
                    peinture: "🎨",
                    maintenance: "🛠️",
                }[s?.toLowerCase()] ?? "🔨"
            );
        },
        stepOf(m) {
            if (m.step !== undefined && m.step !== null)
                return parseInt(m.step) || 1;
            const map = {
                pending: 1,
                assigned: 2,
                accepted: 3,
                contact_made: 4,
                on_the_way: 5,
                tracking: 6,
                in_progress: 7,
                quote_submitted: 8,
                order_placed: 9,
                awaiting_confirm: 10,
                completed: 11,
                closed: 12,
                cancelled: 0,
            };
            return map[m.status] ?? 1;
        },
        formatDate(iso) {
            if (!iso) return "—";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },
        formatDateTime(iso) {
            if (!iso) return "—";
            const d = new Date(iso);
            return (
                d.toLocaleDateString("fr-FR", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                }) +
                " à " +
                d.toLocaleTimeString("fr-FR", {
                    hour: "2-digit",
                    minute: "2-digit",
                })
            );
        },
        formatPrice(a) {
            if (!a && a !== 0) return "—";
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(a)) + " FCFA"
            );
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
        haversineKm(lat1, lng1, lat2, lng2) {
            const R = 6371;
            const dLat = ((lat2 - lat1) * Math.PI) / 180;
            const dLng = ((lng2 - lng1) * Math.PI) / 180;
            const a =
                Math.sin(dLat / 2) ** 2 +
                Math.cos((lat1 * Math.PI) / 180) *
                    Math.cos((lat2 * Math.PI) / 180) *
                    Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        async requestGeoAndComputeDistance() {
            const m = this.activeMission;
            if (!m?.latitude || !m?.longitude) return;

            const compute = (lat, lng) => {
                this.missionDistance = this.haversineKm(
                    lat, lng,
                    parseFloat(m.latitude),
                    parseFloat(m.longitude)
                );
                this.contractorGeo.error = null;
            };

            if (this.contractorGeo.latitude !== null && this.contractorGeo.longitude !== null) {
                compute(this.contractorGeo.latitude, this.contractorGeo.longitude);
                return;
            }

            if (!navigator.geolocation) {
                this.contractorGeo.error = 'unavailable';
                return;
            }

            this.contractorGeo.loading = true;
            this.contractorGeo.error = null;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.contractorGeo.latitude = pos.coords.latitude;
                    this.contractorGeo.longitude = pos.coords.longitude;
                    this.contractorGeo.loading = false;
                    compute(pos.coords.latitude, pos.coords.longitude);
                },
                (err) => {
                    this.contractorGeo.loading = false;
                    const map = { 1: 'denied', 2: 'unavailable', 3: 'timeout' };
                    this.contractorGeo.error = map[err.code] ?? 'unavailable';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
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
        if (this.showAvailableMissions) {
            this.fetchAvailableMissions();
        } else {
            this.fetchMissions();
        }
        this.startMissionPolling();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000
        );
        document.addEventListener("click", this.handleClickOutside);
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
        window.addEventListener("rt-unread-update", this.onGlobalUnreadUpdate);
    },
    beforeUnmount() {
        this.stopMissionPolling();
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
.ctm-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #fff;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: #f8f4f0;
    min-height: 100vh;
}

/* ── (Tous les styles existants conservés intégralement) ── */

.ctm-topbar {
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
    .ctm-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.ctm-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.ctm-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ctm-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-page-sub {
    font-size: 11px;
    color: var(--gr);
    display: none;
}
@media (min-width: 480px) {
    .ctm-page-sub {
        display: block;
    }
}
.ctm-page-sub strong {
    color: var(--dk);
}
.ctm-avatar {
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
    overflow: hidden;
}
.ctm-avatar.has-photo {
    background: #fff;
}
.ctm-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
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

.ctm-notif-wrap {
    position: relative;
}
.ctm-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    transition: color 0.18s;
    border-radius: 8px;
}
.ctm-notif-btn:hover {
    color: var(--or);
}
.ctm-notif-btn svg {
    width: 22px;
    height: 22px;
}
.ctm-notif-badge {
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
.ctm-notif-dropdown {
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
.ctm-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ctm-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.ctm-notif-loading,
.ctm-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.ctm-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.ctm-notif-item:hover {
    background: #faf7f5;
}
.ctm-notif-item.unread {
    background: #fff8f5;
}
.ctm-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.ctm-notif-content {
    flex: 1;
    min-width: 0;
}
.ctm-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ctm-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.ctm-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

.ctm-content {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 1100px;
    margin: 0 auto;
    width: 100%;
}
@media (min-width: 600px) {
    .ctm-content {
        padding: 24px;
    }
}

.ctm-filters {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.ctm-search-wrap {
    position: relative;
}
.ctm-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
}
.ctm-search {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 9px 14px 9px 34px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: border 0.15s;
    box-sizing: border-box;
}
.ctm-search:focus {
    outline: none;
    border-color: var(--or);
}
.ctm-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.ctm-tab {
    padding: 7px 14px;
    border-radius: 9px;
    border: 1.5px solid transparent;
    background: var(--grl);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
    display: flex;
    align-items: center;
    gap: 5px;
}
.ctm-tab:hover {
    background: var(--or3);
    color: var(--or);
}
.ctm-tab.active {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
}
.ctm-tab-count {
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
.ctm-tab.active .ctm-tab-count {
    background: rgba(255, 255, 255, 0.25);
}

.ctm-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ctm-skeleton-row {
    height: 76px;
    background: var(--grl);
    border-radius: 14px;
    animation: ctm-pulse 1.2s infinite;
}
@keyframes ctm-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}
.ctm-alert-error {
    background: #fee2e2;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ctm-empty {
    text-align: center;
    padding: 56px 24px;
    background: var(--wh);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.ctm-empty-icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.ctm-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-empty-sub {
    font-size: 13px;
    color: var(--gr);
    margin-top: 4px;
}
.ctm-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ctm-item {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.18s;
}
.ctm-item:hover {
    border-color: var(--am);
    transform: translateY(-1px);
}
.ctm-item-urgent {
    border-left: 4px solid var(--or);
}
.ctm-item-icon {
    font-size: 26px;
    flex-shrink: 0;
}
.ctm-item-body {
    flex: 1;
    min-width: 0;
}
.ctm-item-title {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 8px;
}
.ctm-item-meta,
.ctm-item-addr {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}
.ctm-item-imgs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}
.ctm-item-img-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.08);
    transition: opacity 0.15s;
}
.ctm-item-img-thumb:hover { opacity: 0.8; }
.ctm-item-imgs-more {
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
.ctm-msg-unread {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    color: var(--or);
    background: var(--or3);
    border-radius: 99px;
    padding: 2px 9px;
    margin-top: 5px;
}
.ctm-item-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.ctm-item-price {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ctm-item-date {
    font-size: 11px;
    color: var(--grm);
}
.ctm-type-chip {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 99px;
    background: var(--grl);
    color: var(--gr);
}

.ctm-badge {
    padding: 4px 11px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    background: #f5f0eb;
    color: var(--dk);
}
.ctm-badge.pending {
    background: #fef3c7;
    color: #d97706;
}
.ctm-badge.warning {
    background: #ffedd5;
    color: var(--or2);
}
.ctm-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.ctm-badge.done {
    background: #dcfce7;
    color: #16a34a;
}
.ctm-badge.cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.ctm-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
@media (max-width: 600px) {
    .ctm-panel-overlay {
        align-items: flex-end;
    }
}
.ctm-panel {
    background: var(--wh);
    width: 100%;
    max-width: 520px;
    height: 100vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    box-shadow: -8px 0 40px rgba(0, 0, 0, 0.15);
    animation: ctm-slide-in 0.25s ease;
}
@keyframes ctm-slide-in {
    from {
        transform: translateX(40px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
@media (max-width: 600px) {
    .ctm-panel {
        height: 90vh;
        max-width: 100%;
        border-radius: 18px 18px 0 0;
    }
}
.ctm-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 2px solid var(--grl);
    position: sticky;
    top: 0;
    background: var(--wh);
    z-index: 1;
}
.ctm-panel-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-panel-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.ctm-panel-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ctm-panel-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--gr);
}

.ctm-workflow {
    padding: 14px 20px 16px;
    border-bottom: 2px solid var(--grl);
    background: #faf7f5;
}
.ctm-workflow-track {
    height: 5px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 14px;
}
.ctm-workflow-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.5s;
}
.ctm-wf-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px 4px;
    position: relative;
}
@media (min-width: 400px) {
    .ctm-wf-steps {
        grid-template-columns: repeat(6, 1fr);
    }
}
.ctm-wf-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    position: relative;
}
.ctm-wf-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 7px;
    left: calc(50% + 8px);
    width: calc(100% - 16px);
    height: 2px;
    background: var(--grl);
}
.ctm-wf-step.wf-done:not(:last-child)::after {
    background: var(--or);
}
.ctm-wf-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2.5px solid var(--grl);
    background: var(--wh);
    transition: all 0.25s;
    flex-shrink: 0;
    z-index: 1;
}
.ctm-wf-step.wf-done .ctm-wf-dot {
    background: var(--or);
    border-color: var(--or);
}
.ctm-wf-step.wf-current .ctm-wf-dot {
    background: var(--or);
    border-color: var(--or2);
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
    transform: scale(1.25);
}
.ctm-wf-label {
    font-size: 9px;
    font-weight: 700;
    color: var(--grm);
    text-align: center;
    line-height: 1.2;
    max-width: 48px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ctm-wf-step.wf-done .ctm-wf-label {
    color: var(--or2);
}
.ctm-wf-step.wf-current .ctm-wf-label {
    color: var(--or);
    font-weight: 800;
}

.ctm-panel-body {
    padding: 20px 24px;
    flex: 1;
}
.ctm-panel-cols {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
/* Panel toujours en colonne — trop étroit pour 2 colonnes */
.ctm-panel-cols {
    flex-direction: column;
}
.ctm-section {
    background: var(--grl);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 14px;
}
.ctm-section-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.ctm-verified-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    color: #15803d;
    background: #dcfce7;
    border: 1px solid #86efac;
    border-radius: 99px;
    padding: 1px 8px;
    margin-left: 6px;
    vertical-align: middle;
}
.ctm-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid #d5c9c0;
    font-size: 13px;
    gap: 12px;
}
.ctm-row:last-child {
    border-bottom: none;
}
.ctm-row span {
    color: var(--gr);
    flex-shrink: 0;
}
.ctm-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}
.ctm-val-green {
    color: #16a34a;
}
.ctm-masked {
    font-size: 12px;
    color: var(--gr);
    font-style: italic;
}

/* Quote summary in panel */
.ctm-quote-summary .ctm-quote-total {
    border-top: 2px solid #d5c9c0;
    margin-top: 4px;
    padding-top: 8px;
}
.ctm-quote-status-badge {
    display: inline-block;
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 99px;
    background: var(--or3);
    color: var(--or2);
}
.ctm-quote-status-badge.approved {
    background: #dcfce7;
    color: #16a34a;
}
.ctm-quote-status-badge.rejected {
    background: #fee2e2;
    color: #dc2626;
}
.ctm-quote-status-badge.submitted {
    background: #fef3c7;
    color: #d97706;
}

.ctm-action-block {
    background: var(--grl);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border: 1.5px solid var(--grl);
}
.ctm-action-block p {
    font-size: 13.5px;
    color: var(--gr);
    margin-bottom: 12px;
    line-height: 1.6;
}
.ctm-action-row {
    display: flex;
    gap: 8px;
}
.ctm-action-row .ctm-btn {
    flex: 1;
    justify-content: center;
}
.ctm-action-new {
    border-color: var(--am);
    background: #fffbeb;
    text-align: center;
}
.ctm-action-new-icon {
    font-size: 32px;
    margin-bottom: 8px;
}
.ctm-action-new-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.ctm-action-new-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-bottom: 14px;
}

/* Action block devis */
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
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
}
.ctm-btn-green:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(34, 197, 94, 0.4);
}
.ctm-btn-green:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

/* ── MODAL DEVIS — mobile-first ── */
.ctm-modal-quote {
    max-width: 600px;
}
.ctm-modal-header-inner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex: 1;
    min-width: 0;
}
.ctm-modal-header-icon {
    font-size: 26px;
    flex-shrink: 0;
    margin-top: 2px;
}
.ctm-modal-body-quote {
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
@media (min-width: 480px) {
    .ctm-modal-body-quote {
        padding: 18px 22px;
        gap: 14px;
    }
}
.ctm-modal-footer-quote {
    gap: 8px;
    flex-wrap: wrap;
}
.ctm-modal-footer-quote .ctm-btn {
    flex: 1;
    min-width: 100px;
    justify-content: center;
}

/* ── Blocs section ── */
.ctm-qblock {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ctm-qblock-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}
.ctm-qblock-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ctm-commission-tag {
    font-size: 10px;
    font-weight: 700;
    background: #fff7ed;
    color: var(--or2);
    border: 1px solid #fed7aa;
    border-radius: 99px;
    padding: 1px 7px;
}
.ctm-add-line-btn {
    font-size: 12px;
    font-weight: 700;
    color: var(--or);
    background: var(--or3);
    border: 1.5px solid #fed7aa;
    border-radius: 8px;
    padding: 5px 12px;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.15s;
    white-space: nowrap;
    flex-shrink: 0;
}
.ctm-add-line-btn:hover { background: #ffedd5; }

/* ── Champs ── */
.ctm-qfield {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.ctm-qlabel {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--gr);
}
.ctm-optional {
    font-weight: 400;
    color: var(--grm);
}
.ctm-qinput {
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    width: 100%;
    box-sizing: border-box;
    transition: border 0.15s;
    background: #f8f4f0;
}
.ctm-qinput:focus {
    outline: none;
    border-color: var(--or);
    background: #fff;
}
.ctm-qtextarea {
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    resize: vertical;
    width: 100%;
    box-sizing: border-box;
    background: #f8f4f0;
    transition: border 0.15s;
}
.ctm-qtextarea:focus {
    outline: none;
    border-color: var(--or);
    background: #fff;
}

/* ── Diagnostic (readonly) ── */
.ctm-diag-amount-row {
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 10px 14px;
}
.ctm-diag-amount-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--or);
}
.ctm-diag-amount-hint {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}

/* ── Pièces — card mobile ── */
.ctm-parts-table {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ctm-parts-empty {
    font-size: 12.5px;
    color: var(--grm);
    text-align: center;
    padding: 12px;
    background: #f8f4f0;
    border-radius: 8px;
}
/* Card par ligne pièce */
.ctm-part-card {
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ctm-part-card-top {
    display: flex;
    align-items: center;
    gap: 8px;
}
.ctm-part-desc {
    flex: 1;
    min-width: 0;
}
.ctm-part-card-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 8px;
    align-items: flex-end;
}
.ctm-part-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.ctm-parts-total {
    font-size: 13px;
    font-weight: 800;
    color: var(--or);
    white-space: nowrap;
    padding-bottom: 8px;
}
.ctm-del-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: #fee2e2;
    color: #dc2626;
    cursor: pointer;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s;
    padding: 0;
}
.ctm-del-btn:hover { background: #fca5a5; }

/* ── Main d'œuvre ── */
.ctm-labor-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
@media (min-width: 400px) {
    .ctm-labor-row {
        flex-direction: row;
        align-items: flex-end;
    }
    .ctm-labor-desc { flex: 1; }
    .ctm-labor-amount { width: 130px; flex-shrink: 0; }
}

/* ── Récapitulatif ── */
.ctm-recap {
    background: linear-gradient(135deg, #fff7ed, #fffbf5);
    border: 2px solid #fed7aa;
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ctm-recap-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    font-size: 13px;
    color: var(--gr);
    gap: 8px;
}
.ctm-recap-row > span:first-child { flex-shrink: 0; }
.ctm-recap-vals {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1px;
}
.ctm-recap-vals strong {
    font-weight: 700;
    color: var(--dk);
    font-size: 13px;
}
.ctm-recap-net-line {
    font-size: 10.5px;
    color: #16a34a;
    font-weight: 600;
}
.ctm-recap-divider {
    height: 2px;
    background: #fed7aa;
    border-radius: 99px;
}
.ctm-recap-total {
    display: flex;
    justify-content: space-between;
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.ctm-recap-net-total {
    font-size: 12px;
    color: var(--gr);
    text-align: right;
}
.ctm-recap-net-total strong {
    color: #16a34a;
    font-weight: 700;
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

/* ── Bannière dossier en attente ── */
.ctm-pending-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
}
.ctm-pending-banner-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}
.ctm-pending-banner-title {
    font-weight: 700;
    color: #92400e;
    margin-bottom: 4px;
}
.ctm-pending-banner-sub {
    font-size: 0.88rem;
    color: #78350f;
    line-height: 1.4;
}

/* ── Section aperçu missions disponibles ── */
.ctm-available-section {
    margin-top: 4px;
}
.ctm-available-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--ct, #555);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Mission verrouillée */
.ctm-item-locked {
    opacity: 0.65;
    cursor: default;
    filter: blur(0.3px);
}
.ctm-badge-locked {
    background: #e5e7eb;
    color: #6b7280;
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 20px;
    white-space: nowrap;
}
.ctm-btn-disabled {
    background: #e5e7eb;
    color: #6b7280;
    cursor: not-allowed;
    box-shadow: none;
}
.ctm-btn-disabled:hover {
    transform: none;
    box-shadow: none;
}

/* ── Distance block ── */
.ctm-distance-block {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 10px 14px;
    margin: 10px 0;
    font-size: 0.9rem;
    color: #1e40af;
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
}
.ctm-distance-value strong {
    color: #ea580c;
    font-weight: 700;
}
.ctm-distance-loading {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
}
.ctm-distance-error {
    color: #b91c1c;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ctm-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid #cbd5e1;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: ctm-spin 0.7s linear infinite;
    display: inline-block;
    flex-shrink: 0;
}
@keyframes ctm-spin { to { transform: rotate(360deg); } }
.ctm-retry-btn {
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 0.8rem;
    cursor: pointer;
}
.ctm-retry-btn:hover { background: #1e40af; }

/* ── POPUP ACCRÉDITATION ENTREPRISE ── */
.ctm-modal-accred {
    max-width: 460px;
    text-align: center;
    position: relative;
    padding: 36px 32px 28px;
}
.ctm-modal-accred-close {
    position: absolute;
    top: 14px;
    right: 16px;
}
.ctm-accred-icon {
    font-size: 52px;
    margin-bottom: 12px;
}
.ctm-accred-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 8px;
}
.ctm-accred-sub {
    font-size: 14px;
    color: var(--gr, #7c6a5a);
    margin-bottom: 16px;
    line-height: 1.6;
}
.ctm-accred-progress-wrap {
    margin-bottom: 16px;
}
.ctm-accred-progress-track {
    background: var(--grl, #e8ddd4);
    height: 10px;
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 6px;
}
.ctm-accred-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or, #f97316), var(--or2, #ea580c));
    border-radius: 99px;
    transition: width 0.5s ease;
}
.ctm-accred-progress-label {
    font-size: 12px;
    color: var(--gr, #7c6a5a);
    font-weight: 600;
}
.ctm-accred-msg {
    background: var(--cr, #fef3e2);
    border: 1.5px solid var(--grl, #e8ddd4);
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: var(--dk, #1c1412);
    line-height: 1.6;
    text-align: left;
}
.ctm-accred-msg--ok {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
}
</style>
