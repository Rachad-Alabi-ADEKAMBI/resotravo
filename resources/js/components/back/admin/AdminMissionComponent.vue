<template>
    <div class="amis-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="amis-topbar">
            <div class="amis-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="amis-page-title">Gestion des missions</div>
                    <div class="amis-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <!-- Pill compteur -->
                <div class="amis-count-pill">
                    {{ filteredMissions.length }} mission{{
                        filteredMissions.length > 1 ? "s" : ""
                    }}
                </div>

                <!-- Cloche notifications -->
                <div class="amis-notif-wrap" ref="notifWrap">
                    <button class="amis-notif-btn" @click="toggleNotif">
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
                        <span class="amis-notif-badge" v-if="unreadCount > 0">
                            {{ unreadCount > 9 ? "9+" : unreadCount }}
                        </span>
                    </button>
                    <div class="amis-notif-dropdown" v-if="notifOpen">
                        <div class="amis-notif-header">
                            <span>Notifications</span>
                            <button
                                class="amis-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="amis-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="amis-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="amis-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="amis-notif-dot" v-if="!n.read"></div>
                            <div class="amis-notif-content">
                                <div class="amis-notif-title">
                                    {{ n.title }}
                                </div>
                                <div class="amis-notif-body">{{ n.body }}</div>
                                <div class="amis-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avatar -->
                <div class="amis-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="amis-content">
            <!-- ── FILTRES ── -->
            <div class="amis-filters-bar">
                <div class="amis-filters-left">
                    <div class="amis-search-wrap">
                        <span class="amis-search-icon">🔍</span>
                        <input
                            class="amis-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher client, service, adresse…"
                        />
                    </div>
                </div>
                <div class="amis-filters-right">
                    <select class="amis-select" v-model="filterStatus">
                        <option value="all">Tous les statuts</option>
                        <option value="pending">⏳ En attente</option>
                        <option value="active">🔄 En cours</option>
                        <option value="quote_submitted">📝 Devis soumis</option>
                        <option value="awaiting_confirm">
                            ⌛ Att. confirmation
                        </option>
                        <option value="closed">✅ Clôturées</option>
                        <option value="cancelled">❌ Annulées</option>
                    </select>
                    <select class="amis-select" v-model="filterType">
                        <option value="all">Tous types</option>
                        <option value="residential">🏠 Domicile</option>
                        <option value="business">🏢 Entreprise</option>
                    </select>
                    <select class="amis-select" v-model="filterAssigned">
                        <option value="all">Tous</option>
                        <option value="unassigned">⚠️ Sans prestataire</option>
                        <option value="assigned">👷 Avec prestataire</option>
                    </select>
                </div>
            </div>

            <!-- ── TABS RAPIDES ── -->
            <div class="amis-tabs">
                <button
                    class="amis-tab"
                    v-for="t in tabs"
                    :key="t.key"
                    :class="{ active: activeTab === t.key }"
                    @click="
                        activeTab = t.key;
                        filterStatus = 'all';
                        currentPage = 1;
                    "
                >
                    {{ t.label }}
                    <span class="amis-tab-count">{{ countByTab(t.key) }}</span>
                </button>
            </div>

            <!-- ── LOADER ── -->
            <div class="ac-grid" v-if="missionsLoading">
                <div class="ac-skeleton" v-for="n in 6" :key="n"></div>
            </div>

            <!-- ── ERREUR ── -->
            <div class="amis-alert-error" v-else-if="missionsError">
                ⚠️ {{ missionsError }}
                <button
                    class="amis-btn amis-btn-ghost amis-btn-sm"
                    @click="fetchMissions"
                >
                    Réessayer
                </button>
            </div>

            <!-- ── VIDE ── -->
            <div class="amis-empty" v-else-if="pagedMissions.length === 0">
                <div class="amis-empty-icon">📭</div>
                <div class="amis-empty-title">Aucune mission trouvée</div>
                <div class="amis-empty-sub">
                    Modifiez vos filtres ou actualisez la liste.
                </div>
            </div>

            <!-- ── LISTE MISSIONS ── -->
            <div class="ac-list" v-else>
                <div
                    class="ac-row"
                    v-for="m in pagedMissions"
                    :key="m.id"
                    :class="{
                        'ac-row-urgent':
                            m.status === 'pending' && !m.contractor_id,
                        'ac-row-closed': ['completed', 'closed'].includes(
                            m.status,
                        ),
                        'ac-row-cancelled': m.status === 'cancelled',
                    }"
                    @click="openMission(m)"
                >
                    <!-- Icône -->
                    <div class="ac-row-icon">{{ serviceIcon(m.service) }}</div>

                    <!-- Infos principales -->
                    <div class="ac-row-main">
                        <div class="ac-row-title">
                            {{ m.service }}
                            <span
                                class="amis-type-chip"
                                :class="m.location_type"
                            >
                                {{
                                    m.location_type === "business" ? "🏢" : "🏠"
                                }}
                            </span>
                        </div>
                        <div class="ac-row-meta">
                            <span>📍 {{ m.address }}</span>
                            <span>
                                👤 {{ m.client?.name ?? "—" }}
                                <span
                                    class="amis-company-tag"
                                    v-if="m.client?.account_type === 'company'"
                                    >Entreprise</span
                                >
                            </span>
                        </div>
                    </div>

                    <!-- Prestataire -->
                    <div class="ac-row-contractor">
                        <span v-if="m.contractor">
                            👷 {{ m.contractor.first_name }}
                            {{ m.contractor.last_name }}
                            <span class="amis-contractor-spec"
                                >· {{ m.contractor.specialty }}</span
                            >
                        </span>
                        <span class="ac-no-contractor" v-else
                            >⚠️ Sans prestataire</span
                        >
                    </div>

                    <!-- Progression -->
                    <div class="ac-row-progress">
                        <div class="ac-row-step">
                            Étape {{ m.step ?? "—" }}/12
                        </div>
                        <div class="ac-progress-bar">
                            <div
                                class="ac-progress-fill"
                                :style="{
                                    width: ((m.step ?? 0) / 12) * 100 + '%',
                                }"
                                :class="badgeClass(m.status)"
                            ></div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="ac-row-stats">
                        <span class="ac-row-stat-val">{{
                            formatDate(m.created_at)
                        }}</span>
                        <span class="ac-row-stat-lbl">Créée</span>
                    </div>

                    <!-- Montant -->
                    <div class="ac-row-stats">
                        <span class="ac-row-stat-val">{{
                            m.total_amount ? formatPrice(m.total_amount) : "—"
                        }}</span>
                        <span class="ac-row-stat-lbl">Montant</span>
                    </div>

                    <!-- Badge -->
                    <div class="ac-row-badge">
                        <span
                            class="amis-badge"
                            :class="badgeClass(m.status)"
                            >{{ labelOf(m) }}</span
                        >
                    </div>

                    <!-- Action -->
                    <div class="ac-row-action" @click.stop>
                        <button
                            v-if="canPropose(m)"
                            class="amis-btn amis-btn-orange amis-btn-xs"
                            @click.stop="openProposalPanel(m)"
                        >
                            📤 Proposer
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── PAGINATION ── -->
            <div class="ac-pagination" v-if="totalFiltered > 0">
                <button
                    class="ac-page-btn"
                    @click="currentPage = 1"
                    :disabled="currentPage === 1"
                >
                    «
                </button>
                <button
                    class="ac-page-btn"
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                >
                    ‹
                </button>
                <button
                    class="ac-page-btn"
                    v-for="p in visiblePages"
                    :key="p"
                    :class="{ active: p === currentPage }"
                    @click="currentPage = p"
                >
                    {{ p }}
                </button>
                <button
                    class="ac-page-btn"
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                >
                    ›
                </button>
                <button
                    class="ac-page-btn"
                    @click="currentPage = totalPages"
                    :disabled="currentPage === totalPages"
                >
                    »
                </button>
                <span class="ac-page-info">
                    Page {{ currentPage }}/{{ totalPages }} ·
                    {{ totalFiltered }} mission{{
                        totalFiltered > 1 ? "s" : ""
                    }}
                </span>
            </div>
        </div>
        <!-- end amis-content -->

        <!-- ══════════════ MODAL MISSION DÉTAIL ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="activeMission"
            @click.self="closeModal"
        >
            <div class="amis-modal amis-modal-xl">
                <div class="amis-modal-header">
                    <div>
                        <h3>
                            {{ activeMission.service }}
                            <span
                                class="amis-type-chip"
                                :class="activeMission.location_type"
                            >
                                {{
                                    activeMission.location_type === "business"
                                        ? "🏢 Entreprise"
                                        : "🏠 Domicile"
                                }}
                            </span>
                        </h3>
                        <div class="amis-modal-sub">
                            Mission #{{ activeMission.id }} · Créée le
                            {{ formatDate(activeMission.created_at) }}
                        </div>
                    </div>
                    <button class="amis-modal-close" @click="closeModal">
                        &#215;
                    </button>
                </div>

                <div class="amis-modal-body">
                    <!-- ── Barre de progression étapes ── -->
                    <div class="amis-step-track">
                        <div
                            class="amis-step-fill"
                            :style="{
                                width:
                                    ((activeMission.step ?? 0) / 12) * 100 +
                                    '%',
                            }"
                        ></div>
                    </div>
                    <div class="amis-step-legend">
                        <span>Étape {{ activeMission.step ?? 0 }}/12</span>
                        <span
                            class="amis-badge"
                            :class="badgeClass(activeMission.status)"
                            >{{ labelOf(activeMission) }}</span
                        >
                    </div>

                    <!-- ── Colonnes infos ── -->
                    <div class="amis-detail-grid">
                        <!-- Colonne gauche : infos mission -->
                        <div class="amis-detail-col">
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    📋 Détails de la mission
                                </div>
                                <div class="amis-detail-row">
                                    <span>Service</span
                                    ><strong>{{
                                        activeMission.service
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Adresse</span
                                    ><strong>{{
                                        activeMission.address
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Description</span
                                    ><strong>{{
                                        activeMission.description ?? "—"
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.desired_date"
                                >
                                    <span>Date souhaitée</span
                                    ><strong>{{
                                        formatDate(activeMission.desired_date)
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.total_amount"
                                >
                                    <span>Montant total</span
                                    ><strong>{{
                                        formatPrice(activeMission.total_amount)
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.total_amount"
                                >
                                    <span>Commission (10%)</span>
                                    <strong class="amis-val-green">{{
                                        formatPrice(
                                            activeMission.total_amount * 0.1,
                                        )
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.total_amount"
                                >
                                    <span>Part prestataire (90%)</span>
                                    <strong>{{
                                        formatPrice(
                                            activeMission.total_amount * 0.9,
                                        )
                                    }}</strong>
                                </div>
                            </div>

                            <!-- Client -->
                            <div
                                class="amis-detail-section"
                                v-if="activeMission.client"
                            >
                                <div class="amis-detail-section-title">
                                    👤 Client
                                </div>
                                <div class="amis-detail-row">
                                    <span>Nom</span>
                                    <strong>
                                        {{ activeMission.client.name }}
                                        <span
                                            v-if="activeMission.client.is_verified"
                                            class="amis-verified-badge"
                                            title="Identité vérifiée"
                                        >✅ Identité vérifiée</span>
                                    </strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.client.email"
                                >
                                    <span>Email</span
                                    ><strong>{{
                                        activeMission.client.email
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.client.phone"
                                >
                                    <span>Téléphone</span
                                    ><strong>{{
                                        activeMission.client.phone
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.client.account_type"
                                >
                                    <span>Type</span>
                                    <strong>{{
                                        activeMission.client.account_type ===
                                        "company"
                                            ? "🏢 Entreprise"
                                            : "🏠 Particulier"
                                    }}</strong>
                                </div>
                            </div>

                            <!-- Prestataire assigné -->
                            <div
                                class="amis-detail-section"
                                v-if="activeMission.contractor"
                            >
                                <div class="amis-detail-section-title">
                                    👷 Prestataire assigné
                                </div>
                                <div class="amis-detail-row">
                                    <span>Nom</span>
                                    <strong
                                        >{{
                                            activeMission.contractor.first_name
                                        }}
                                        {{
                                            activeMission.contractor.last_name
                                        }}</strong
                                    >
                                </div>
                                <div class="amis-detail-row">
                                    <span>Spécialité</span>
                                    <strong>{{
                                        activeMission.contractor.specialty
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeMission.contractor.phone"
                                >
                                    <span>Téléphone</span>
                                    <strong>{{
                                        activeMission.contractor.phone
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Note</span>
                                    <strong
                                        >⭐
                                        {{
                                            activeMission.contractor
                                                .average_rating > 0
                                                ? activeMission.contractor
                                                      .average_rating + "/5"
                                                : "N/A"
                                        }}</strong
                                    >
                                </div>
                                <div class="amis-detail-row">
                                    <span>Disponible</span>
                                    <strong>{{
                                        activeMission.contractor.available
                                            ? "🟢 Oui"
                                            : "🔴 Non"
                                    }}</strong>
                                </div>
                            </div>

                            <!-- Aucun prestataire -->
                            <div class="amis-no-contractor-block" v-else>
                                <div class="amis-no-contractor-icon">⚠️</div>
                                <div class="amis-no-contractor-text">
                                    Mission sans prestataire assigné
                                </div>
                                <div class="amis-no-contractor-sub">
                                    Utilisez le panneau ci-contre pour proposer
                                    la mission à des prestataires disponibles.
                                </div>
                            </div>
                        </div>

                        <!-- Colonne droite : proposition de prestataires ── FEATURE PRINCIPALE ── -->
                        <div class="amis-proposal-col">
                            <div class="amis-proposal-panel">
                                <div class="amis-proposal-header">
                                    <div class="amis-proposal-title">
                                        📤 Proposer à des prestataires
                                    </div>
                                    <div class="amis-proposal-sub">
                                        Sélectionnez un ou plusieurs
                                        prestataires disponibles. La mission
                                        sera envoyée à tous simultanément. Le
                                        premier à accepter sera assigné.
                                    </div>
                                </div>

                                <!-- Historique des propositions -->
                                <div
                                    class="amis-proposals-history"
                                    v-if="
                                        activeMission.proposals &&
                                        activeMission.proposals.length > 0
                                    "
                                >
                                    <div class="amis-proposals-history-title">
                                        📨 Propositions envoyées
                                    </div>
                                    <div
                                        class="amis-prop-item"
                                        v-for="p in activeMission.proposals"
                                        :key="p.id"
                                    >
                                        <div class="amis-prop-av">
                                            {{
                                                contractorInitials(p.contractor)
                                            }}
                                        </div>
                                        <div class="amis-prop-info">
                                            <div class="amis-prop-name">
                                                {{ p.contractor?.first_name }}
                                                {{ p.contractor?.last_name }}
                                            </div>
                                            <div class="amis-prop-meta">
                                                {{ p.contractor?.specialty }}
                                            </div>
                                        </div>
                                        <span
                                            class="amis-badge"
                                            :class="
                                                proposalBadgeClass(p.status)
                                            "
                                        >
                                            {{ proposalStatusLabel(p.status) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Recherche prestataires disponibles -->
                                <div class="amis-search-contractors">
                                    <button
                                        class="amis-btn amis-btn-ghost amis-btn-sm"
                                        @click="fetchAvailableContractors"
                                        :disabled="availableLoading"
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="availableLoading"
                                        ></div>
                                        <span v-else
                                            >🔍 Charger les prestataires
                                            disponibles</span
                                        >
                                    </button>
                                    <div
                                        class="amis-avail-filter"
                                        v-if="availableContractors.length > 0"
                                    >
                                        <input
                                            class="amis-search-mini"
                                            type="text"
                                            v-model="contractorSearch"
                                            placeholder="Filtrer par nom ou spécialité…"
                                        />
                                    </div>
                                </div>

                                <!-- Loader -->
                                <div
                                    class="amis-loading amis-loading-sm"
                                    v-if="availableLoading"
                                >
                                    <div
                                        class="amis-skeleton-row-sm"
                                        v-for="n in 3"
                                        :key="n"
                                    ></div>
                                </div>

                                <!-- Liste prestataires disponibles -->
                                <div
                                    class="amis-available-list"
                                    v-else-if="availableContractors.length > 0"
                                >
                                    <div class="amis-avail-select-all">
                                        <label class="amis-check-label">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    allFilteredAvailableSelected
                                                "
                                                @change="toggleSelectAll"
                                                :indeterminate.prop="
                                                    someSelected &&
                                                    !allFilteredAvailableSelected
                                                "
                                            />
                                            <span
                                                >Tout sélectionner ({{
                                                    filteredAvailableContractors.length
                                                }})</span
                                            >
                                        </label>
                                        <span
                                            class="amis-selected-count"
                                            v-if="
                                                selectedContractors.length > 0
                                            "
                                        >
                                            {{ selectedContractors.length }}
                                            sélectionné{{
                                                selectedContractors.length > 1
                                                    ? "s"
                                                    : ""
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="amis-avail-item"
                                        v-for="c in filteredAvailableContractors"
                                        :key="c.id"
                                        :class="{
                                            selected:
                                                selectedContractors.includes(
                                                    c.id,
                                                ),
                                            'already-proposed':
                                                isAlreadyProposed(c.id),
                                        }"
                                        @click="
                                            !isAlreadyProposed(c.id) &&
                                            toggleContractor(c.id)
                                        "
                                    >
                                        <label
                                            class="amis-avail-check"
                                            @click.stop
                                        >
                                            <input
                                                type="checkbox"
                                                :value="c.id"
                                                v-model="selectedContractors"
                                                :disabled="
                                                    isAlreadyProposed(c.id)
                                                "
                                            />
                                        </label>

                                        <div
                                            class="amis-avail-av"
                                            :class="{ available: c.available }"
                                        >
                                            {{ contractorInitials(c) }}
                                            <span class="amis-avail-dot"></span>
                                        </div>

                                        <div class="amis-avail-info">
                                            <div class="amis-avail-name">
                                                {{ c.first_name }}
                                                {{ c.last_name }}
                                            </div>
                                            <div class="amis-avail-meta">
                                                {{ c.specialty }} ·
                                                {{ c.city ?? "Cotonou" }}
                                            </div>
                                            <div class="amis-avail-stats">
                                                ⭐
                                                {{
                                                    c.average_rating > 0
                                                        ? c.average_rating
                                                        : "N/A"
                                                }}
                                                ·
                                                {{ c.completed_missions ?? 0 }}
                                                missions
                                                <span v-if="c.radius_km"
                                                    >· {{ c.radius_km }}km</span
                                                >
                                            </div>
                                        </div>

                                        <div class="amis-avail-right">
                                            <span
                                                class="amis-accred-mini"
                                                v-if="
                                                    [
                                                        'residential',
                                                        'both',
                                                    ].includes(c.accreditation)
                                                "
                                                title="Accrédité Domicile"
                                                >🏠</span
                                            >
                                            <span
                                                class="amis-accred-mini"
                                                v-if="
                                                    [
                                                        'business',
                                                        'both',
                                                    ].includes(c.accreditation)
                                                "
                                                title="Accrédité Entreprise"
                                                >🏢</span
                                            >
                                            <span
                                                class="amis-already-tag"
                                                v-if="isAlreadyProposed(c.id)"
                                                >Déjà proposé</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        class="amis-empty-filter"
                                        v-if="
                                            filteredAvailableContractors.length ===
                                            0
                                        "
                                    >
                                        Aucun prestataire ne correspond à votre
                                        recherche.
                                    </div>
                                </div>

                                <!-- Message si pas encore chargé -->
                                <div
                                    class="amis-avail-placeholder"
                                    v-else-if="!availableLoaded"
                                >
                                    <div class="amis-avail-placeholder-icon">
                                        👷
                                    </div>
                                    <div>
                                        Cliquez sur "Charger les prestataires
                                        disponibles" pour afficher les
                                        prestataires certifiés disponibles.
                                    </div>
                                </div>

                                <!-- Aucun disponible -->
                                <div class="amis-empty" v-else>
                                    <div class="amis-empty-icon">😔</div>
                                    <div class="amis-empty-title">
                                        Aucun prestataire disponible
                                    </div>
                                    <div class="amis-empty-sub">
                                        Tous les prestataires certifiés sont
                                        actuellement indisponibles.
                                    </div>
                                </div>

                                <!-- BOUTON ENVOI PROPOSITIONS -->
                                <div
                                    class="amis-proposal-action"
                                    v-if="selectedContractors.length > 0"
                                >
                                    <div class="amis-proposal-summary">
                                        📤 Envoyer la proposition à
                                        <strong>{{
                                            selectedContractors.length
                                        }}</strong>
                                        prestataire{{
                                            selectedContractors.length > 1
                                                ? "s"
                                                : ""
                                        }}
                                    </div>
                                    <div class="amis-proposal-note">
                                        Le premier à accepter sera assigné. Les
                                        autres seront automatiquement notifiés
                                        du retrait.
                                    </div>
                                    <button
                                        class="amis-btn amis-btn-orange"
                                        style="width: 100%"
                                        @click="sendProposals"
                                        :disabled="proposalLoading"
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="proposalLoading"
                                        ></div>
                                        <span v-else
                                            >📤 Envoyer
                                            {{ selectedContractors.length }}
                                            proposition{{
                                                selectedContractors.length > 1
                                                    ? "s"
                                                    : ""
                                            }}</span
                                        >
                                    </button>
                                </div>
                            </div>

                            <!-- Actions admin sur statut -->
                            <div
                                class="amis-admin-actions"
                                v-if="showAdminActions"
                            >
                                <div class="amis-admin-actions-title">
                                    ⚙️ Actions administrateur
                                </div>
                                <div class="amis-admin-actions-row">
                                    <button
                                        class="amis-btn amis-btn-red amis-btn-sm"
                                        v-if="
                                            !['cancelled', 'closed'].includes(
                                                activeMission.status,
                                            )
                                        "
                                        @click="cancelMission"
                                        :disabled="actionLoading"
                                    >
                                        🚫 Annuler la mission
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-ghost amis-btn-sm"
                                        v-if="
                                            activeMission.status === 'cancelled'
                                        "
                                        @click="reopenMission"
                                        :disabled="actionLoading"
                                    >
                                        🔄 Rouvrir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end modal-body -->

                <div class="amis-modal-footer">
                    <button class="amis-btn amis-btn-ghost" @click="closeModal">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ CONFIRM CANCEL ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="cancelConfirm.visible"
            @click.self="cancelConfirm.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>Annuler la mission</h3></div>
                    <button
                        class="amis-modal-close"
                        @click="cancelConfirm.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            line-height: 1.6;
                        "
                    >
                        Êtes-vous sûr de vouloir annuler la mission
                        <strong>{{ cancelConfirm.mission?.service }}</strong>
                        ({{ cancelConfirm.mission?.client?.name }}) ? Cette
                        action est irréversible et le client sera notifié.
                    </p>
                    <label class="amis-form-label">Motif d'annulation</label>
                    <textarea
                        class="amis-textarea"
                        v-model="cancelConfirm.reason"
                        rows="2"
                        placeholder="Ex : Client injoignable, mission dupliquée…"
                    ></textarea>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="cancelConfirm.visible = false"
                    >
                        Retour
                    </button>
                    <button
                        class="amis-btn amis-btn-red"
                        @click="confirmCancelMission"
                        :disabled="
                            !cancelConfirm.reason.trim() ||
                            cancelConfirm.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="cancelConfirm.loading"
                        ></div>
                        <span v-else>🚫 Confirmer l'annulation</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="amis-toast-container">
            <div
                class="amis-toast"
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
export default {
    name: "AdminMissionComponent",
    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            // Missions
            missions: [],
            missionsLoading: false,
            missionsError: null,

            // Pagination
            currentPage: 1,
            perPage: 6,

            // Filtres
            search: "",
            filterStatus: "all",
            filterType: "all",
            filterAssigned: "all",
            activeTab: "all",

            // Pagination
            currentPage: 1,
            perPage: 6,

            // Mission active
            activeMission: null,
            actionLoading: false,

            // Prestataires disponibles (pour la proposition)
            availableContractors: [],
            availableLoading: false,
            availableLoaded: false,
            contractorSearch: "",

            // Sélection multi-prestataires
            selectedContractors: [],
            proposalLoading: false,

            // Annulation
            cancelConfirm: {
                visible: false,
                mission: null,
                reason: "",
                loading: false,
            },

            sidebarOpen: false,

            // Notifications
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            // Toasts
            toasts: [],
            toastId: 0,
        };
    },

    computed: {
        greeting() {
            const h = new Date().getHours();
            if (h < 12) return "Bonjour";
            if (h < 18) return "Bon après-midi";
            return "Bonsoir";
        },

        userInitials() {
            return (
                this.user.name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "AD"
            );
        },

        tabs() {
            return [
                { key: "all", label: "Toutes" },
                { key: "pending", label: "⏳ En attente" },
                { key: "active", label: "🔄 En cours" },
                { key: "unassigned", label: "⚠️ Sans prestataire" },
                { key: "closed", label: "✅ Clôturées" },
                { key: "cancelled", label: "❌ Annulées" },
            ];
        },

        filteredMissions() {
            let list = [...this.missions];

            // Tab rapide
            if (this.activeTab === "pending")
                list = list.filter((m) => m.status === "pending");
            else if (this.activeTab === "active")
                list = list.filter((m) => this.isActiveStatus(m.status));
            else if (this.activeTab === "unassigned")
                list = list.filter(
                    (m) =>
                        !m.contractor_id &&
                        !["closed", "cancelled", "completed"].includes(
                            m.status,
                        ),
                );
            else if (this.activeTab === "closed")
                list = list.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                );
            else if (this.activeTab === "cancelled")
                list = list.filter((m) => m.status === "cancelled");

            // Filtre statut
            if (this.filterStatus !== "all") {
                if (this.filterStatus === "active")
                    list = list.filter((m) => this.isActiveStatus(m.status));
                else if (this.filterStatus === "closed")
                    list = list.filter((m) =>
                        ["completed", "closed"].includes(m.status),
                    );
                else list = list.filter((m) => m.status === this.filterStatus);
            }

            // Filtre type
            if (this.filterType !== "all")
                list = list.filter((m) => m.location_type === this.filterType);

            // Filtre assigné
            if (this.filterAssigned === "unassigned")
                list = list.filter((m) => !m.contractor_id);
            else if (this.filterAssigned === "assigned")
                list = list.filter((m) => !!m.contractor_id);

            // Recherche
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (m) =>
                        m.service?.toLowerCase().includes(q) ||
                        m.address?.toLowerCase().includes(q) ||
                        m.client?.name?.toLowerCase().includes(q) ||
                        m.description?.toLowerCase().includes(q) ||
                        String(m.id).includes(q),
                );
            }

            return list;
        },

        totalFiltered() {
            return this.filteredMissions.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },

        pagedMissions() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredMissions.slice(start, start + this.perPage);
        },

        visiblePages() {
            const pages = [];
            const delta = 2;
            for (
                let i = Math.max(1, this.currentPage - delta);
                i <= Math.min(this.totalPages, this.currentPage + delta);
                i++
            ) {
                pages.push(i);
            }
            return pages;
        },

        filteredAvailableContractors() {
            if (!this.contractorSearch.trim()) return this.availableContractors;
            const q = this.contractorSearch.toLowerCase();
            return this.availableContractors.filter(
                (c) =>
                    c.first_name?.toLowerCase().includes(q) ||
                    c.last_name?.toLowerCase().includes(q) ||
                    c.specialty?.toLowerCase().includes(q) ||
                    c.city?.toLowerCase().includes(q),
            );
        },

        allFilteredAvailableSelected() {
            const selectable = this.filteredAvailableContractors.filter(
                (c) => !this.isAlreadyProposed(c.id),
            );
            return (
                selectable.length > 0 &&
                selectable.every((c) => this.selectedContractors.includes(c.id))
            );
        },

        someSelected() {
            return (
                this.selectedContractors.length > 0 &&
                !this.allFilteredAvailableSelected
            );
        },

        alreadyProposedIds() {
            if (!this.activeMission?.proposals) return [];
            return this.activeMission.proposals
                .filter((p) => ["pending", "accepted"].includes(p.status))
                .map((p) => p.contractor_id);
        },

        showAdminActions() {
            return (
                this.activeMission &&
                !["closed"].includes(this.activeMission.status)
            );
        },

        totalFiltered() {
            return this.filteredMissions.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },

        pagedMissions() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredMissions.slice(start, start + this.perPage);
        },

        visiblePages() {
            const pages = [];
            const delta = 2;
            for (
                let i = Math.max(1, this.currentPage - delta);
                i <= Math.min(this.totalPages, this.currentPage + delta);
                i++
            ) {
                pages.push(i);
            }
            return pages;
        },
    },

    methods: {
        // ── Fetch ─────────────────────────────────────────────────
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

        async fetchAvailableContractors() {
            if (!this.activeMission) return;
            this.availableLoading = true;
            this.availableLoaded = false;
            this.selectedContractors = [];
            try {
                const params = new URLSearchParams({
                    service: this.activeMission.service ?? "",
                    location_type:
                        this.activeMission.location_type ?? "residential",
                    mission_id: this.activeMission.id,
                });
                const url = `${this.routes.contractors_available}?${params}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.availableContractors = Array.isArray(data)
                    ? data
                    : (data.data ?? []);
                this.availableLoaded = true;
            } catch {
                this.showToast(
                    "Erreur lors du chargement des prestataires.",
                    "error",
                );
            } finally {
                this.availableLoading = false;
            }
        },

        // ── Mission ───────────────────────────────────────────────
        openMission(m) {
            this.activeMission = { ...m, proposals: m.proposals ?? [] };
            this.availableContractors = [];
            this.availableLoaded = false;
            this.selectedContractors = [];
            this.contractorSearch = "";
        },

        closeModal() {
            this.activeMission = null;
            this.availableContractors = [];
            this.availableLoaded = false;
            this.selectedContractors = [];
        },

        // ── PROPOSITION MULTI-PRESTATAIRES ────────────────────────
        openProposalPanel(m) {
            this.openMission(m);
            // Auto-charge les prestataires disponibles si on clique sur le bouton rapide
            this.$nextTick(() => {
                this.fetchAvailableContractors();
            });
        },

        isAlreadyProposed(contractorId) {
            return this.alreadyProposedIds.includes(contractorId);
        },

        toggleContractor(id) {
            const idx = this.selectedContractors.indexOf(id);
            if (idx === -1) this.selectedContractors.push(id);
            else this.selectedContractors.splice(idx, 1);
        },

        toggleSelectAll() {
            const selectable = this.filteredAvailableContractors
                .filter((c) => !this.isAlreadyProposed(c.id))
                .map((c) => c.id);

            if (this.allFilteredAvailableSelected) {
                // Désélectionner tout
                this.selectedContractors = this.selectedContractors.filter(
                    (id) => !selectable.includes(id),
                );
            } else {
                // Sélectionner tout
                const current = new Set(this.selectedContractors);
                selectable.forEach((id) => current.add(id));
                this.selectedContractors = [...current];
            }
        },

        async sendProposals() {
            if (this.selectedContractors.length === 0) return;
            this.proposalLoading = true;

            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_propose.replace(
                    "{id}",
                    this.activeMission.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        contractor_ids: this.selectedContractors,
                    }),
                });

                const data = await res.json();
                if (!res.ok) {
                    this.showToast(
                        data.message ?? "Erreur lors de l'envoi.",
                        "error",
                    );
                    return;
                }

                // Mettre à jour les propositions dans la mission active
                const newProposals = data.proposals ?? [];
                this.activeMission = {
                    ...this.activeMission,
                    status: data.mission?.status ?? this.activeMission.status,
                    proposals: [
                        ...(this.activeMission.proposals ?? []),
                        ...newProposals,
                    ],
                };

                // Mettre à jour dans la liste globale
                const idx = this.missions.findIndex(
                    (m) => m.id === this.activeMission.id,
                );
                if (idx !== -1)
                    this.missions.splice(idx, 1, {
                        ...this.missions[idx],
                        ...data.mission,
                    });

                this.selectedContractors = [];
                const count = newProposals.length;
                this.showToast(
                    `📤 Proposition envoyée à ${count} prestataire${count > 1 ? "s" : ""}. Le premier à accepter sera assigné.`,
                    "success",
                );

                // Recharger la liste des disponibles pour mettre à jour les statuts
                await this.fetchAvailableContractors();
            } catch {
                this.showToast(
                    "Erreur réseau lors de l'envoi des propositions.",
                    "error",
                );
            } finally {
                this.proposalLoading = false;
            }
        },

        // ── Actions admin ─────────────────────────────────────────
        cancelMission() {
            this.cancelConfirm = {
                visible: true,
                mission: this.activeMission,
                reason: "",
                loading: false,
            };
        },

        async confirmCancelMission() {
            this.cancelConfirm.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_cancel.replace(
                    "{id}",
                    this.cancelConfirm.mission.id,
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
                        reason: this.cancelConfirm.reason,
                    }),
                });
                if (!res.ok) throw new Error();
                const updated = await res.json();
                this.updateMissionInList(
                    updated.mission ?? {
                        ...this.cancelConfirm.mission,
                        status: "cancelled",
                    },
                );
                if (this.activeMission?.id === this.cancelConfirm.mission.id) {
                    this.activeMission = {
                        ...this.activeMission,
                        status: "cancelled",
                    };
                }
                this.cancelConfirm.visible = false;
                this.showToast(
                    "Mission annulée. Le client a été notifié.",
                    "success",
                );
            } catch {
                this.showToast("Erreur lors de l'annulation.", "error");
            } finally {
                this.cancelConfirm.loading = false;
            }
        },

        async reopenMission() {
            if (!confirm("Rouvrir cette mission ?")) return;
            this.actionLoading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_cancel.replace(
                    "{id}",
                    this.activeMission.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ status: "pending" }),
                });
                if (!res.ok) throw new Error();
                this.activeMission = {
                    ...this.activeMission,
                    status: "pending",
                    contractor_id: null,
                    contractor: null,
                };
                this.updateMissionInList(this.activeMission);
                this.showToast(
                    "Mission rouverte et repassée en attente.",
                    "success",
                );
            } catch {
                this.showToast("Erreur lors de la réouverture.", "error");
            } finally {
                this.actionLoading = false;
            }
        },

        updateMissionInList(updated) {
            const idx = this.missions.findIndex((m) => m.id === updated.id);
            if (idx !== -1)
                this.missions.splice(idx, 1, {
                    ...this.missions[idx],
                    ...updated,
                });
        },

        // ── Helpers ───────────────────────────────────────────────
        isActiveStatus(status) {
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
            ].includes(status);
        },

        canPropose(m) {
            return (
                (m.status === "pending" || m.status === "assigned") &&
                !["closed", "cancelled", "completed"].includes(m.status)
            );
        },

        countByTab(key) {
            if (key === "all") return this.missions.length;
            if (key === "pending")
                return this.missions.filter((m) => m.status === "pending")
                    .length;
            if (key === "active")
                return this.missions.filter((m) =>
                    this.isActiveStatus(m.status),
                ).length;
            if (key === "unassigned")
                return this.missions.filter(
                    (m) =>
                        !m.contractor_id &&
                        !["closed", "cancelled", "completed"].includes(
                            m.status,
                        ),
                ).length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },

        proposalStatusLabel(s) {
            return (
                {
                    pending: "⏳ En attente",
                    accepted: "✅ Acceptée",
                    rejected: "❌ Refusée",
                    superseded: "⏭ Remplacée",
                    expired: "⏰ Expirée",
                }[s] ?? s
            );
        },

        proposalBadgeClass(s) {
            return (
                {
                    pending: "warning",
                    accepted: "done",
                    rejected: "cancelled",
                    superseded: "",
                    expired: "cancelled",
                }[s] ?? ""
            );
        },

        contractorInitials(c) {
            return (
                (
                    (c?.first_name?.[0] ?? "") + (c?.last_name?.[0] ?? "")
                ).toUpperCase() || "PR"
            );
        },

        labelOf(mission) {
            const map = {
                pending: "En attente",
                assigned: "Proposée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi",
                in_progress: "Intervention",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "Att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return (
                mission.status_label ?? map[mission.status] ?? mission.status
            );
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
            if (!amount && amount !== 0) return "—";
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(amount)) +
                " FCFA"
            );
        },

        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 4000);
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
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

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },
    },

    mounted() {
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
.amis-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #ffffff;
    --green: #16a34a;
    --red: #dc2626;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: #f8f4f0;
    min-height: 100vh;
}

/* ── TOPBAR ── */
.amis-topbar {
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
    .amis-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.amis-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.amis-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.amis-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.amis-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    display: none;
}
@media (min-width: 480px) {
    .amis-page-sub {
        display: block;
    }
}
.amis-page-sub strong {
    color: var(--dk);
}

/* Avatar */
.amis-avatar {
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

/* Pill compteur */
.amis-count-pill {
    background: var(--grl);
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
}

/* ── NOTIFICATIONS ── */
.amis-notif-wrap {
    position: relative;
}
.amis-notif-btn {
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
.amis-notif-btn:hover {
    color: var(--or);
}
.amis-notif-btn svg {
    width: 22px;
    height: 22px;
}
.amis-notif-badge {
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
.amis-notif-dropdown {
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
.amis-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.amis-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.amis-notif-loading,
.amis-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.amis-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
    position: relative;
}
.amis-notif-item:hover {
    background: #faf7f5;
}
.amis-notif-item.unread {
    background: #fff8f5;
}
.amis-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.amis-notif-content {
    flex: 1;
    min-width: 0;
}
.amis-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.amis-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.amis-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

/* ── CONTENT ── */
.amis-content {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ── FILTRES ── */
.amis-filters-bar {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.amis-filters-left {
    flex: 1;
    min-width: 200px;
}
.amis-filters-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.amis-search-wrap {
    position: relative;
}
.amis-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
}
.amis-search {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 9px 14px 9px 34px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: border 0.15s;
    box-sizing: border-box;
    min-width: 240px;
}
.amis-search:focus {
    outline: none;
    border-color: var(--or);
}
.amis-select {
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: var(--wh);
    cursor: pointer;
}
.amis-select:focus {
    outline: none;
    border-color: var(--or);
}

/* ── TABS ── */
.amis-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.amis-tab {
    padding: 8px 16px;
    border-radius: 10px;
    border: 2px solid transparent;
    background: var(--wh);
    font-size: 13px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: "Poppins", sans-serif;
}
.amis-tab:hover {
    background: var(--or3);
    color: var(--or);
}
.amis-tab.active {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border-color: transparent;
}
.amis-tab-count {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
    font-size: 11px;
    font-weight: 800;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}
.amis-tab.active .amis-tab-count {
    background: rgba(255, 255, 255, 0.25);
}

/* ── MISSION LIST ── */
.amis-mission-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.amis-mission-item {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.18s;
    position: relative;
}
.amis-mission-item:hover {
    border-color: var(--am);
    transform: translateY(-1px);
}
.amis-mission-item.urgent {
    border-left: 4px solid var(--red);
}
.amis-urgent-dot {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--red);
    animation: amis-pulse-dot 1.5s infinite;
}
@keyframes amis-pulse-dot {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.4);
    }
}
.amis-mission-left {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    flex: 1;
    min-width: 0;
}
.amis-mission-icon {
    font-size: 26px;
    flex-shrink: 0;
}
.amis-mission-body {
    flex: 1;
    min-width: 0;
}
.amis-mission-title {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.amis-mission-client,
.amis-mission-addr,
.amis-mission-contractor {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}
.amis-mission-contractor {
    font-weight: 600;
    color: var(--dk);
}
.amis-no-contractor {
    color: #d97706 !important;
    font-weight: 700 !important;
}
.amis-company-tag {
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    padding: 1px 8px;
    margin-left: 4px;
}
.amis-contractor-spec {
    color: var(--gr);
    font-weight: 400;
}
.amis-mission-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    flex-shrink: 0;
}
.amis-mission-step,
.amis-mission-date {
    font-size: 11.5px;
    color: var(--gr);
}
.amis-mission-amount {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}
.amis-commission {
    font-size: 11px;
    color: var(--green);
    font-weight: 600;
    margin-left: 4px;
}
.amis-type-chip {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
}
.amis-type-chip.residential {
    background: #eff6ff;
    color: #1d4ed8;
}
.amis-type-chip.business {
    background: #f0fdf4;
    color: #15803d;
}

/* ── BADGES ── */
.amis-badge {
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}
.amis-badge.pending {
    background: #fef3c7;
    color: #d97706;
}
.amis-badge.warning {
    background: #ffedd5;
    color: var(--or2);
}
.amis-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.amis-badge.done {
    background: #dcfce7;
    color: #16a34a;
}
.amis-badge.cancelled {
    background: #fee2e2;
    color: #dc2626;
}

/* ── MODAL ── */
.amis-modal-overlay {
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
.amis-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 520px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: amis-slide-up 0.25s ease;
}
.amis-modal-xl {
    max-width: 960px;
}
@keyframes amis-slide-up {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.amis-modal-header {
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
.amis-modal-header h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.amis-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.amis-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.amis-modal-body {
    padding: 20px 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.amis-modal-footer {
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

/* ── STEP TRACK ── */
.amis-step-track {
    height: 8px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
}
.amis-step-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.4s;
}
.amis-step-legend {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
    font-size: 12.5px;
    color: var(--gr);
}

/* ── DETAIL GRID ── */
.amis-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 720px) {
    .amis-detail-grid {
        grid-template-columns: 1fr;
    }
}
.amis-detail-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.amis-detail-section {
    background: var(--grl);
    border-radius: 12px;
    padding: 14px 16px;
}
.amis-detail-section-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.amis-verified-badge {
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
.amis-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid #e5e0db;
    font-size: 13px;
    gap: 12px;
}
.amis-detail-row:last-child {
    border-bottom: none;
}
.amis-detail-row span:first-child {
    color: var(--gr);
    flex-shrink: 0;
}
.amis-detail-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}
.amis-val-green {
    color: var(--green);
}

/* ── NO CONTRACTOR BLOCK ── */
.amis-no-contractor-block {
    background: #fef3c7;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    border: 2px dashed var(--am);
}
.amis-no-contractor-icon {
    font-size: 28px;
    margin-bottom: 8px;
}
.amis-no-contractor-text {
    font-size: 14px;
    font-weight: 800;
    color: #92400e;
}
.amis-no-contractor-sub {
    font-size: 12px;
    color: #b45309;
    margin-top: 4px;
    line-height: 1.5;
}

/* ── PROPOSAL PANEL (feature principale) ── */
.amis-proposal-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.amis-proposal-panel {
    background: var(--grl);
    border-radius: 14px;
    overflow: hidden;
    border: 2px solid var(--am);
}
.amis-proposal-header {
    padding: 16px 16px 12px;
    background: linear-gradient(135deg, var(--or3), #fff);
    border-bottom: 2px solid var(--am);
}
.amis-proposal-title {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk);
}
.amis-proposal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 4px;
    line-height: 1.55;
}

/* ── HISTORIQUE PROPOSITIONS ── */
.amis-proposals-history {
    padding: 12px 16px;
    background: var(--wh);
    border-bottom: 1px solid var(--grl);
}
.amis-proposals-history-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.amis-prop-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    border-bottom: 1px solid var(--grl);
}
.amis-prop-item:last-child {
    border-bottom: none;
}
.amis-prop-av {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--or3), #fde68a);
    color: var(--or2);
    font-weight: 800;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.amis-prop-info {
    flex: 1;
    min-width: 0;
}
.amis-prop-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.amis-prop-meta {
    font-size: 11.5px;
    color: var(--gr);
}

/* ── SEARCH CONTRACTORS ── */
.amis-search-contractors {
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.amis-search-mini {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 9px;
    padding: 8px 12px;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    box-sizing: border-box;
}
.amis-search-mini:focus {
    outline: none;
    border-color: var(--or);
}

/* ── AVAILABLE LIST ── */
.amis-available-list {
    max-height: 340px;
    overflow-y: auto;
}
.amis-avail-select-all {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    border-bottom: 2px solid var(--grl);
    background: var(--wh);
    font-size: 12.5px;
    font-weight: 700;
}
.amis-check-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.amis-check-label input[type="checkbox"] {
    accent-color: var(--or);
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.amis-selected-count {
    color: var(--or);
    font-size: 12px;
    font-weight: 700;
}
.amis-avail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: var(--wh);
    border-bottom: 1px solid var(--grl);
    cursor: pointer;
    transition: background 0.15s;
}
.amis-avail-item:hover:not(.already-proposed) {
    background: var(--or3);
}
.amis-avail-item.selected {
    background: var(--or3);
}
.amis-avail-item.already-proposed {
    opacity: 0.6;
    cursor: not-allowed;
}
.amis-avail-check input[type="checkbox"] {
    accent-color: var(--or);
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.amis-avail-av {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--grl), #e5e0db);
    color: var(--gr);
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.amis-avail-av.available {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #15803d;
}
.amis-avail-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--green);
    border: 2px solid var(--wh);
}
.amis-avail-info {
    flex: 1;
    min-width: 0;
}
.amis-avail-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.amis-avail-meta {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 1px;
}
.amis-avail-stats {
    font-size: 11px;
    color: var(--or);
    font-weight: 600;
    margin-top: 2px;
}
.amis-avail-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.amis-accred-mini {
    font-size: 14px;
}
.amis-already-tag {
    font-size: 10.5px;
    color: var(--gr);
    font-weight: 700;
    background: var(--grl);
    border-radius: 99px;
    padding: 2px 8px;
}
.amis-empty-filter {
    padding: 20px;
    text-align: center;
    color: var(--gr);
    font-size: 13px;
    background: var(--wh);
}

/* ── PLACEHOLDER ── */
.amis-avail-placeholder {
    padding: 20px 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--gr);
    line-height: 1.5;
}
.amis-avail-placeholder-icon {
    font-size: 28px;
}

/* ── PROPOSAL ACTION ── */
.amis-proposal-action {
    padding: 14px 16px;
    background: var(--or3);
    border-top: 2px solid var(--am);
}
.amis-proposal-summary {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.amis-proposal-note {
    font-size: 11.5px;
    color: var(--gr);
    margin-bottom: 10px;
    line-height: 1.5;
}

/* ── ADMIN ACTIONS ── */
.amis-admin-actions {
    background: var(--wh);
    border-radius: 12px;
    padding: 14px 16px;
    border: 2px solid #fee2e2;
}
.amis-admin-actions-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #dc2626;
    margin-bottom: 10px;
}
.amis-admin-actions-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── LOADING / EMPTY ── */
.amis-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.amis-skeleton-row {
    height: 72px;
    background: #e5e0db;
    border-radius: 14px;
    animation: amis-pulse 1.2s infinite;
}
@keyframes amis-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}
.amis-loading-sm {
    padding: 0 16px;
}
.amis-skeleton-row-sm {
    height: 52px;
    background: #e5e0db;
    border-radius: 10px;
    animation: amis-pulse 1.2s infinite;
}
.amis-alert-error {
    background: #fee2e2;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 13.5px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 10px;
}
.amis-empty {
    text-align: center;
    padding: 48px 24px;
    background: var(--wh);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.amis-empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}
.amis-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.amis-empty-sub {
    font-size: 13px;
    color: var(--gr);
    margin-top: 4px;
}

/* ── FORM ── */
.amis-form-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 6px;
    display: block;
}
.amis-textarea {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    resize: vertical;
    box-sizing: border-box;
}
.amis-textarea:focus {
    outline: none;
    border-color: var(--or);
}

/* ── BUTTONS ── */
.amis-btn {
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
    text-decoration: none;
}
.amis-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.amis-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px rgba(249, 115, 22, 0.4);
}
.amis-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.amis-btn-ghost:hover {
    background: #d5c9c0;
}
.amis-btn-red {
    background: #ef4444;
    color: #fff;
}
.amis-btn-red:hover:not(:disabled) {
    background: #dc2626;
}
.amis-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}
.amis-btn-xs {
    font-size: 11.5px;
    padding: 4px 10px;
    border-radius: 7px;
}
.amis-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* ── SPINNER ── */
.amis-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: amis-spin 0.7s linear infinite;
}
@keyframes amis-spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── TOASTS ── */
.amis-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.amis-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: amis-slide-up 0.3s ease;
}
.amis-toast.success {
    background: #16a34a;
}
.amis-toast.error {
    background: #dc2626;
}

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
    .amis-content {
        padding: 12px;
    }
    .amis-topbar {
        padding: 0 12px;
    }
    .amis-modal-xl {
        max-width: 100%;
    }
    .amis-filters-bar {
        flex-direction: column;
    }
    .amis-filters-right {
        width: 100%;
    }
}

/* ── LISTE MISSIONS ── */
.ac-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    overflow: hidden;
}

.ac-skeleton {
    height: 52px;
    background: linear-gradient(
        90deg,
        var(--grl) 25%,
        #f0ebe5 50%,
        var(--grl) 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.ac-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--wh);
    border-bottom: 1px solid var(--grl);
    cursor: pointer;
    transition: background 0.15s;
}
.ac-row:last-child {
    border-bottom: none;
}
.ac-row:hover {
    background: #fff8f3;
}
.ac-row-urgent {
    border-left: 4px solid var(--am);
}
.ac-row-closed {
    border-left: 4px solid #22c55e;
}
.ac-row-cancelled {
    border-left: 4px solid #ef4444;
    opacity: 0.75;
}

/* Icône */
.ac-row-icon {
    font-size: 22px;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    background: var(--or3);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Infos principales */
.ac-row-main {
    flex: 2;
    min-width: 0;
}
.ac-row-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-row-meta {
    display: flex;
    gap: 12px;
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
    flex-wrap: wrap;
}

/* Prestataire */
.ac-row-contractor {
    flex: 1.5;
    font-size: 12px;
    color: var(--dk);
    font-weight: 500;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Progression */
.ac-row-progress {
    flex: 0 0 120px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.ac-row-step {
    font-size: 11px;
    font-weight: 700;
    color: var(--dk);
}
.ac-progress-bar {
    height: 4px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
    width: 100%;
}
.ac-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: var(--or);
    transition: width 0.4s ease;
}
.ac-progress-fill.done {
    background: #22c55e;
}
.ac-progress-fill.cancelled {
    background: #ef4444;
}
.ac-progress-fill.warning {
    background: var(--am);
}
.ac-progress-fill.active {
    background: #3b82f6;
}

/* Stats date/montant */
.ac-row-stats {
    flex: 0 0 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.ac-row-stat-val {
    font-size: 12px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
}
.ac-row-stat-lbl {
    font-size: 10px;
    color: var(--gr);
}

/* Badge */
.ac-row-badge {
    flex: 0 0 100px;
    display: flex;
    justify-content: center;
}

/* Action */
.ac-row-action {
    flex: 0 0 110px;
    display: flex;
    justify-content: flex-end;
    min-height: 30px;
}

/* Responsive : masquer colonnes secondaires sur mobile */
@media (max-width: 768px) {
    .ac-row-contractor,
    .ac-row-progress,
    .ac-row-stats {
        display: none;
    }
    .ac-row-main {
        flex: 1;
    }
}

/* ── PAGINATION ── */
.ac-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 28px;
    padding-bottom: 12px;
}
.ac-page-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ac-page-btn:hover:not(:disabled) {
    border-color: var(--or);
    color: var(--or);
}
.ac-page-btn.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.ac-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.ac-page-info {
    font-size: 12.5px;
    color: var(--gr);
    margin-left: 8px;
}
</style>
