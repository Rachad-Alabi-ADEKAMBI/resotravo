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
                    <div class="amis-page-title">Catalogue des Services</div>
                    <div class="amis-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <div class="amis-count-pill">
                    {{ totalFiltered }} service{{
                        totalFiltered > 1 ? "s" : ""
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
                        <span class="amis-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
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
                <!-- Bouton Ajouter un service -->
                <button class="amis-btn amis-btn-orange" @click="openAddModal">
                    + Ajouter
                </button>
                <div class="amis-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="amis-content">
            <!-- ── STATS ── -->
            <div class="at-stats-row">
                <div class="at-stat" v-for="s in statCards" :key="s.label">
                    <div class="at-stat-icon">{{ s.icon }}</div>
                    <div class="at-stat-body">
                        <div class="at-stat-val">{{ s.value }}</div>
                        <div class="at-stat-lbl">{{ s.label }}</div>
                    </div>
                </div>
            </div>

            <!-- ── FILTRES ── -->
            <div class="amis-filters-bar">
                <div class="amis-filters-left">
                    <div class="amis-search-wrap">
                        <span class="amis-search-icon">🔍</span>
                        <input
                            class="amis-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher un service, une catégorie…"
                            @input="currentPage = 1"
                        />
                    </div>
                </div>
                <div class="amis-filters-right">
                    <select
                        class="amis-select"
                        v-model="filterCategory"
                        @change="currentPage = 1"
                    >
                        <option value="">Toutes les catégories</option>
                        <option
                            v-for="cat in availableCategories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </select>
                    <select
                        class="amis-select"
                        v-model="filterAccreditation"
                        @change="currentPage = 1"
                    >
                        <option value="">Toutes accréditations</option>
                        <option value="domicile">🏠 Domicile</option>
                        <option value="entreprise">🏢 Entreprise</option>
                        <option value="both">🏠🏢 Les deux</option>
                    </select>
                </div>
            </div>

            <!-- ── TABS ── -->
            <div class="amis-tabs">
                <button
                    class="amis-tab"
                    v-for="t in tabs"
                    :key="t.key"
                    :class="{ active: activeTab === t.key }"
                    @click="
                        activeTab = t.key;
                        currentPage = 1;
                    "
                >
                    {{ t.label }}
                    <span class="amis-tab-count">{{ countByTab(t.key) }}</span>
                </button>
            </div>

            <!-- ── LOADER ── -->
            <div class="ac-grid" v-if="loading">
                <div class="ac-skeleton" v-for="n in 6" :key="n"></div>
            </div>

            <!-- ── ERREUR ── -->
            <div class="amis-alert-error" v-else-if="loadError">
                ⚠️ {{ loadError }}
                <button
                    class="amis-btn amis-btn-ghost amis-btn-sm"
                    @click="fetchServices"
                >
                    Réessayer
                </button>
            </div>

            <!-- ── VIDE ── -->
            <div class="amis-empty" v-else-if="pagedServices.length === 0">
                <div class="amis-empty-icon">🔧</div>
                <div class="amis-empty-title">Aucun service trouvé</div>
                <div class="amis-empty-sub">
                    Modifiez vos filtres ou ajoutez un nouveau service.
                </div>
            </div>

            <!-- ── GRILLE CARTES ── -->
            <div class="ac-grid" v-else>
                <div
                    class="ac-card"
                    v-for="s in pagedServices"
                    :key="s.id"
                    @click="openDetail(s)"
                >
                    <!-- En-tête -->
                    <div class="ac-card-head">
                        <div
                            class="ac-service-icon-wrap"
                            :style="{ background: serviceIconBg(s.slug) }"
                        >
                            <span class="ac-service-icon">{{
                                s.icon || serviceIcon(s.slug)
                            }}</span>
                        </div>
                        <div class="ac-card-headinfo">
                            <div class="ac-card-name">{{ s.name }}</div>
                            <div class="ac-card-email">
                                {{ s.category ?? "Sans catégorie" }}
                            </div>
                        </div>
                        <span class="amis-badge" :class="badgeClass(s.status)">
                            {{ statusLabel(s.status) }}
                        </span>
                    </div>

                    <!-- Stats -->
                    <div class="ac-card-stats">
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                s.contractors_count ?? 0
                            }}</span>
                            <span class="ac-cstat-lbl">Prestataires</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                s.missions_count ?? 0
                            }}</span>
                            <span class="ac-cstat-lbl">Missions</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                s.avg_rating
                                    ? s.avg_rating.toFixed(1) + " ⭐"
                                    : "—"
                            }}</span>
                            <span class="ac-cstat-lbl">Note moy.</span>
                        </div>
                    </div>

                    <!-- Accréditations & chips -->
                    <div class="ac-card-chips">
                        <span
                            class="ac-chip"
                            :class="accredClass(s.accreditation)"
                        >
                            {{ accredLabel(s.accreditation) }}
                        </span>
                        <span class="ac-chip ac-chip-admin" v-if="s.admin_only">
                            🔐 Admin uniquement
                        </span>
                        <span
                            class="ac-chip ac-chip-suggestion"
                            v-if="s.is_suggestion"
                        >
                            💡 Suggestion
                        </span>
                    </div>

                    <!-- Actions rapides -->
                    <div class="ac-card-actions" @click.stop>
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-xs"
                            @click.stop="openEditModal(s)"
                        >
                            ✏️ Modifier
                        </button>
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-xs"
                            v-if="s.status === 'active'"
                            @click.stop="openArchiveModal(s)"
                        >
                            📦 Archiver
                        </button>
                        <button
                            class="amis-btn amis-btn-green amis-btn-xs"
                            v-if="s.status === 'archived'"
                            @click.stop="quickRestore(s)"
                            :disabled="actionLoading === s.id"
                        >
                            🔄 Restaurer
                        </button>
                        <button
                            class="amis-btn amis-btn-orange amis-btn-xs"
                            v-if="s.status === 'pending'"
                            @click.stop="quickApprove(s)"
                            :disabled="actionLoading === s.id"
                        >
                            ✅ Valider
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
                    {{ totalFiltered }} service{{
                        totalFiltered > 1 ? "s" : ""
                    }}
                </span>
            </div>
        </div>
        <!-- end amis-content -->

        <!-- ══════════════ MODAL DÉTAIL SERVICE ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="activeService"
            @click.self="closeDetail"
        >
            <div class="amis-modal amis-modal-xl">
                <div class="amis-modal-header">
                    <div>
                        <h3>
                            {{
                                activeService.icon ||
                                serviceIcon(activeService.slug)
                            }}
                            {{ activeService.name }}
                            <span
                                class="amis-badge"
                                :class="badgeClass(activeService.status)"
                                style="margin-left: 8px"
                            >
                                {{ statusLabel(activeService.status) }}
                            </span>
                        </h3>
                        <div class="amis-modal-sub">
                            Service #{{ activeService.id }} · Catégorie :
                            {{ activeService.category ?? "—" }}
                        </div>
                    </div>
                    <button class="amis-modal-close" @click="closeDetail">
                        &#215;
                    </button>
                </div>

                <div class="amis-modal-body">
                    <div class="amis-detail-grid">
                        <!-- COL GAUCHE -->
                        <div class="amis-detail-col">
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    🔧 Informations générales
                                </div>
                                <div class="amis-detail-row">
                                    <span>Nom</span
                                    ><strong>{{ activeService.name }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Slug</span
                                    ><strong>{{
                                        activeService.slug ?? "—"
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Catégorie</span
                                    ><strong>{{
                                        activeService.category ?? "—"
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Accréditation</span>
                                    <strong>{{
                                        accredLabel(activeService.accreditation)
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Admin uniquement</span>
                                    <strong>{{
                                        activeService.admin_only ? "Oui" : "Non"
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Issu d'une suggestion</span>
                                    <strong>{{
                                        activeService.is_suggestion
                                            ? "Oui"
                                            : "Non"
                                    }}</strong>
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeService.description"
                                >
                                    <span>Description</span>
                                    <strong>{{
                                        activeService.description
                                    }}</strong>
                                </div>
                            </div>

                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    📊 Activité
                                </div>
                                <div class="amis-detail-row">
                                    <span>Prestataires actifs</span>
                                    <strong>{{
                                        activeService.contractors_count ?? 0
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Missions totales</span>
                                    <strong>{{
                                        activeService.missions_count ?? 0
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Missions terminées</span>
                                    <strong>{{
                                        activeService.missions_completed ?? 0
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Note moyenne</span>
                                    <strong>{{
                                        activeService.avg_rating
                                            ? activeService.avg_rating.toFixed(
                                                  1,
                                              ) + " / 5 ⭐"
                                            : "—"
                                    }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Créé le</span>
                                    <strong>{{
                                        activeService.created_at_label ?? "—"
                                    }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- COL DROITE -->
                        <div class="amis-detail-col">
                            <div class="amis-detail-section at-actions-section">
                                <div class="amis-detail-section-title">
                                    ⚡ Actions rapides
                                </div>
                                <div class="at-actions-row">
                                    <button
                                        class="amis-btn amis-btn-ghost"
                                        @click="
                                            openEditModal(activeService);
                                            closeDetail();
                                        "
                                    >
                                        ✏️ Modifier le service
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-ghost"
                                        v-if="activeService.status === 'active'"
                                        @click="openArchiveModal(activeService)"
                                    >
                                        📦 Archiver
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-green"
                                        v-if="
                                            activeService.status === 'archived'
                                        "
                                        @click="quickRestore(activeService)"
                                        :disabled="
                                            actionLoading === activeService.id
                                        "
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="
                                                actionLoading ===
                                                activeService.id
                                            "
                                        ></div>
                                        <span v-else>🔄 Restaurer</span>
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-orange"
                                        v-if="
                                            activeService.status === 'pending'
                                        "
                                        @click="quickApprove(activeService)"
                                        :disabled="
                                            actionLoading === activeService.id
                                        "
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="
                                                actionLoading ===
                                                activeService.id
                                            "
                                        ></div>
                                        <span v-else
                                            >✅ Valider le service</span
                                        >
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-red"
                                        v-if="
                                            activeService.status === 'pending'
                                        "
                                        @click="openRejectModal(activeService)"
                                    >
                                        ❌ Refuser
                                    </button>
                                </div>
                            </div>

                            <!-- Prestataires liés -->
                            <div
                                class="amis-detail-section"
                                v-if="
                                    activeService.contractors &&
                                    activeService.contractors.length
                                "
                            >
                                <div class="amis-detail-section-title">
                                    👷 Prestataires liés
                                </div>
                                <div
                                    class="ac-mission-item"
                                    v-for="ct in activeService.contractors.slice(
                                        0,
                                        5,
                                    )"
                                    :key="ct.id"
                                >
                                    <div class="ac-mission-check">👷</div>
                                    <div>
                                        <div class="ac-mission-title">
                                            {{ ct.name }}
                                        </div>
                                        <div class="ac-mission-meta">
                                            {{ ct.city ?? "—" }} ·
                                            {{ ct.missions_count ?? 0 }}
                                            mission(s)
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="at-empty-sub"
                                    v-if="activeService.contractors.length > 5"
                                >
                                    +
                                    {{ activeService.contractors.length - 5 }}
                                    autres prestataires
                                </div>
                            </div>
                            <div class="amis-detail-section" v-else>
                                <div class="amis-detail-section-title">
                                    👷 Prestataires liés
                                </div>
                                <div class="at-empty-sub">
                                    Aucun prestataire pour ce service.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="closeDetail"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL AJOUT / ÉDITION SERVICE ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="serviceForm.visible"
            @click.self="serviceForm.visible = false"
        >
            <div class="amis-modal amis-modal-form">
                <div class="amis-modal-header">
                    <div>
                        <h3>
                            {{
                                serviceForm.isEdit
                                    ? "✏️ Modifier le service"
                                    : "➕ Nouveau service"
                            }}
                        </h3>
                        <div class="amis-modal-sub" v-if="serviceForm.isEdit">
                            Mise à jour de « {{ serviceForm.data.name }} »
                        </div>
                    </div>
                    <button
                        class="amis-modal-close"
                        @click="serviceForm.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <!-- Nom + Catégorie -->
                    <div class="asf-row">
                        <div class="asf-field">
                            <label class="amis-form-label"
                                >Nom du service
                                <span style="color: var(--or)">*</span></label
                            >
                            <input
                                class="amis-input"
                                type="text"
                                v-model="serviceForm.data.name"
                                placeholder="Ex : Plomberie, Électricité…"
                            />
                        </div>
                        <div class="asf-field">
                            <label class="amis-form-label">Catégorie</label>
                            <input
                                class="amis-input"
                                type="text"
                                v-model="serviceForm.data.category"
                                placeholder="Ex : Artisanat, Technique…"
                            />
                        </div>
                    </div>

                    <!-- Icône picker -->
                    <div class="asf-field">
                        <label class="amis-form-label">
                            Icône <span style="color: var(--or)">*</span>
                            <span
                                style="
                                    color: var(--gr);
                                    font-weight: 400;
                                    margin-left: 6px;
                                "
                            >
                                — Sélectionné :
                                <strong style="font-size: 20px">{{
                                    serviceForm.data.icon || "—"
                                }}</strong>
                            </span>
                        </label>

                        <!-- Recherche dans les icônes -->
                        <div class="asf-icon-search-wrap">
                            <span style="font-size: 13px; color: var(--gr)"
                                >🔍</span
                            >
                            <input
                                class="asf-icon-search"
                                type="text"
                                v-model="iconSearch"
                                placeholder="Filtrer les icônes…"
                            />
                        </div>

                        <!-- Grille d'icônes par catégorie -->
                        <div class="asf-icon-picker">
                            <template
                                v-for="group in filteredIconGroups"
                                :key="group.label"
                            >
                                <div class="asf-icon-group-label">
                                    {{ group.label }}
                                </div>
                                <div class="asf-icon-grid">
                                    <button
                                        type="button"
                                        class="asf-icon-btn"
                                        v-for="item in group.icons"
                                        :key="item.emoji"
                                        :class="{
                                            selected:
                                                serviceForm.data.icon ===
                                                item.emoji,
                                        }"
                                        :title="item.label"
                                        @click="
                                            serviceForm.data.icon = item.emoji
                                        "
                                    >
                                        <span class="asf-icon-emoji">{{
                                            item.emoji
                                        }}</span>
                                        <span class="asf-icon-label">{{
                                            item.label
                                        }}</span>
                                    </button>
                                </div>
                            </template>
                            <div
                                class="asf-icon-empty"
                                v-if="filteredIconGroups.length === 0"
                            >
                                Aucune icône trouvée pour « {{ iconSearch }} »
                            </div>
                        </div>
                    </div>
                    <div class="asf-field">
                        <label class="amis-form-label">Description</label>
                        <textarea
                            class="amis-textarea"
                            v-model="serviceForm.data.description"
                            rows="3"
                            placeholder="Description courte du service…"
                        ></textarea>
                    </div>
                    <div class="asf-row">
                        <div class="asf-field">
                            <label class="amis-form-label"
                                >Accréditation requise</label
                            >
                            <select
                                class="amis-select asf-select-full"
                                v-model="serviceForm.data.accreditation"
                            >
                                <option value="domicile">
                                    🏠 Domicile uniquement
                                </option>
                                <option value="entreprise">
                                    🏢 Entreprise uniquement
                                </option>
                                <option value="both">🏠🏢 Les deux</option>
                            </select>
                        </div>
                        <div class="asf-field asf-checkbox-field">
                            <label class="amis-form-label">Options</label>
                            <label class="asf-checkbox">
                                <input
                                    type="checkbox"
                                    v-model="serviceForm.data.admin_only"
                                />
                                <span>Admin uniquement</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="serviceForm.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-orange"
                        @click="submitServiceForm"
                        :disabled="
                            !serviceForm.data.name.trim() || serviceForm.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="serviceForm.loading"
                        ></div>
                        <span v-else>{{
                            serviceForm.isEdit
                                ? "💾 Enregistrer"
                                : "➕ Créer le service"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL ARCHIVAGE ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="archiveModal.visible"
            @click.self="archiveModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>📦 Archiver le service</h3></div>
                    <button
                        class="amis-modal-close"
                        @click="archiveModal.visible = false"
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
                            margin-bottom: 14px;
                        "
                    >
                        Archiver
                        <strong>{{ archiveModal.service?.name }}</strong>
                        masquera ce service du catalogue public. Les missions en
                        cours ne seront pas affectées.
                    </p>
                    <label class="amis-form-label"
                        >Motif <span style="color: var(--or)">*</span></label
                    >
                    <textarea
                        class="amis-textarea"
                        v-model="archiveModal.reason"
                        rows="3"
                        placeholder="Ex : Service fusionné avec une autre catégorie, demande faible…"
                    ></textarea>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="archiveModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-red"
                        @click="confirmArchive"
                        :disabled="
                            !archiveModal.reason.trim() || archiveModal.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="archiveModal.loading"
                        ></div>
                        <span v-else>📦 Confirmer l'archivage</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REFUS SUGGESTION ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="rejectModal.visible"
            @click.self="rejectModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>❌ Refuser la suggestion</h3></div>
                    <button
                        class="amis-modal-close"
                        @click="rejectModal.visible = false"
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
                            margin-bottom: 14px;
                        "
                    >
                        Refuser <strong>{{ rejectModal.service?.name }}</strong
                        >. L'utilisateur ayant proposé ce service sera notifié.
                    </p>
                    <label class="amis-form-label"
                        >Motif du refus
                        <span style="color: var(--or)">*</span></label
                    >
                    <textarea
                        class="amis-textarea"
                        v-model="rejectModal.reason"
                        rows="3"
                        placeholder="Ex : Doublon avec une catégorie existante, hors périmètre Resotravo…"
                    ></textarea>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="rejectModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-red"
                        @click="confirmReject"
                        :disabled="
                            !rejectModal.reason.trim() || rejectModal.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="rejectModal.loading"
                        ></div>
                        <span v-else>❌ Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ TOASTS ══════════════ -->
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
    name: "AdminServicesComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            services: [],
            loading: false,
            loadError: null,

            // Filtres
            search: "",
            filterCategory: "",
            filterAccreditation: "",
            activeTab: "all",

            // Pagination
            currentPage: 1,
            perPage: 9,

            // Détail
            activeService: null,
            actionLoading: null,

            // Modal formulaire (ajout / édition)
            serviceForm: {
                visible: false,
                isEdit: false,
                loading: false,
                data: {
                    id: null,
                    name: "",
                    icon: "",
                    category: "",
                    description: "",
                    accreditation: "both",
                    admin_only: false,
                },
            },

            iconSearch: "",

            // Modal archivage
            archiveModal: {
                visible: false,
                service: null,
                reason: "",
                loading: false,
            },

            // Modal refus suggestion
            rejectModal: {
                visible: false,
                service: null,
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
                { key: "all", label: "Tous" },
                { key: "active", label: "✅ Actifs" },
                { key: "pending", label: "⏳ En attente" },
                { key: "archived", label: "📦 Archivés" },
                { key: "suggestion", label: "💡 Suggestions" },
            ];
        },

        availableCategories() {
            return [
                ...new Set(
                    this.services.map((s) => s.category).filter(Boolean),
                ),
            ].sort();
        },

        statCards() {
            return [
                {
                    icon: "🔧",
                    value: this.services.filter((s) => s.status === "active")
                        .length,
                    label: "Services actifs",
                },
                {
                    icon: "⏳",
                    value: this.services.filter((s) => s.status === "pending")
                        .length,
                    label: "En attente",
                },
                {
                    icon: "💡",
                    value: this.services.filter((s) => s.is_suggestion).length,
                    label: "Suggestions",
                },
                {
                    icon: "📦",
                    value: this.services.filter((s) => s.status === "archived")
                        .length,
                    label: "Archivés",
                },
            ];
        },

        filteredServices() {
            let list = [...this.services];

            if (this.activeTab === "active")
                list = list.filter((s) => s.status === "active");
            else if (this.activeTab === "pending")
                list = list.filter((s) => s.status === "pending");
            else if (this.activeTab === "archived")
                list = list.filter((s) => s.status === "archived");
            else if (this.activeTab === "suggestion")
                list = list.filter((s) => s.is_suggestion);

            if (this.filterCategory)
                list = list.filter((s) => s.category === this.filterCategory);
            if (this.filterAccreditation)
                list = list.filter(
                    (s) => s.accreditation === this.filterAccreditation,
                );

            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (s) =>
                        s.name?.toLowerCase().includes(q) ||
                        s.category?.toLowerCase().includes(q) ||
                        s.description?.toLowerCase().includes(q),
                );
            }

            return list;
        },

        totalFiltered() {
            return this.filteredServices.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },
        pagedServices() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredServices.slice(start, start + this.perPage);
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

        // ── Catalogue complet d'icônes groupées ───────────────────
        iconGroups() {
            return [
                {
                    label: "🔧 Technique & Énergie",
                    icons: [
                        { emoji: "🚿", label: "Plomberie" },
                        { emoji: "⚡", label: "Électricité" },
                        { emoji: "❄️", label: "Climatisation" },
                        { emoji: "🔥", label: "Chauffage" },
                        { emoji: "💧", label: "Eau / Forage" },
                        { emoji: "🔌", label: "Installation élec." },
                        { emoji: "🔦", label: "Éclairage" },
                        { emoji: "📡", label: "Antenne / TV" },
                        { emoji: "🛜", label: "Réseau / Internet" },
                        { emoji: "⚙️", label: "Mécanique générale" },
                        { emoji: "🏗️", label: "Maintenance" },
                        { emoji: "🛠️", label: "Réparation" },
                    ],
                },
                {
                    label: "🪵 Artisanat & Construction",
                    icons: [
                        { emoji: "🪚", label: "Menuiserie" },
                        { emoji: "🪟", label: "Vitrerie" },
                        { emoji: "🧱", label: "Maçonnerie" },
                        { emoji: "🏠", label: "Construction" },
                        { emoji: "🪜", label: "Échafaudage" },
                        { emoji: "⛏️", label: "Ferronnerie" },
                        { emoji: "🔩", label: "Serrurerie / Métal" },
                        { emoji: "🔐", label: "Serrurerie" },
                        { emoji: "🚪", label: "Portes / Fenêtres" },
                        { emoji: "🛖", label: "Toiture / Charpente" },
                        { emoji: "🪨", label: "Carrelage / Sol" },
                        { emoji: "🧲", label: "Soudure" },
                    ],
                },
                {
                    label: "🎨 Décoration & Finition",
                    icons: [
                        { emoji: "🖌️", label: "Peinture" },
                        { emoji: "🎨", label: "Décoration" },
                        { emoji: "🖼️", label: "Revêtement mural" },
                        { emoji: "🪞", label: "Miroirs / Verre" },
                        { emoji: "🛋️", label: "Ameublement" },
                        { emoji: "💡", label: "Décoration lumineuse" },
                        { emoji: "🏺", label: "Carrelage déco" },
                        { emoji: "🧶", label: "Tissu / Rideaux" },
                    ],
                },
                {
                    label: "🚗 Automobile",
                    icons: [
                        { emoji: "🔩", label: "Mécanique Auto" },
                        { emoji: "🚗", label: "Auto général" },
                        { emoji: "🛞", label: "Vulcanisation" },
                        { emoji: "🔋", label: "Batterie / Électricité auto" },
                        { emoji: "🚘", label: "Carrosserie" },
                        { emoji: "🧴", label: "Lavage auto" },
                        { emoji: "🏍️", label: "Moto" },
                    ],
                },
                {
                    label: "🧹 Entretien & Nettoyage",
                    icons: [
                        { emoji: "🧹", label: "Nettoyage" },
                        { emoji: "🧺", label: "Blanchisserie" },
                        { emoji: "🪣", label: "Nettoyage industriel" },
                        { emoji: "🧻", label: "Entretien ménager" },
                        { emoji: "🫧", label: "Désinfection" },
                        { emoji: "♻️", label: "Collecte déchets" },
                        { emoji: "🌿", label: "Jardinage" },
                        { emoji: "🌳", label: "Espaces verts" },
                        { emoji: "🏊", label: "Piscine" },
                        { emoji: "🪴", label: "Entretien plantes" },
                    ],
                },
                {
                    label: "👷 Services professionnels",
                    icons: [
                        { emoji: "📋", label: "Expertise" },
                        { emoji: "📐", label: "Architecture / Plans" },
                        { emoji: "📏", label: "Géomètre / Mesure" },
                        { emoji: "🔍", label: "Inspection" },
                        { emoji: "🧪", label: "Analyse / Diagnostic" },
                        { emoji: "📦", label: "Déménagement" },
                        { emoji: "🚚", label: "Livraison / Transport" },
                        { emoji: "🔐", label: "Sécurité" },
                        { emoji: "📷", label: "Vidéosurveillance" },
                        { emoji: "💼", label: "Conseil" },
                    ],
                },
                {
                    label: "💻 Informatique & Numérique",
                    icons: [
                        { emoji: "💻", label: "Informatique" },
                        { emoji: "🖥️", label: "Dépannage PC" },
                        { emoji: "📱", label: "Réparation mobile" },
                        { emoji: "🖨️", label: "Imprimante / Bureau" },
                        { emoji: "📷", label: "Photo / Vidéo" },
                        { emoji: "📺", label: "TV / Électronique" },
                        { emoji: "🔊", label: "Sono / Audio" },
                    ],
                },
            ];
        },

        filteredIconGroups() {
            const q = this.iconSearch.toLowerCase().trim();
            if (!q) return this.iconGroups;
            return this.iconGroups
                .map((group) => ({
                    ...group,
                    icons: group.icons.filter((i) =>
                        i.label.toLowerCase().includes(q),
                    ),
                }))
                .filter((group) => group.icons.length > 0);
        },
    },

    methods: {
        // ── Fetch ──────────────────────────────────────────────────
        async fetchServices() {
            this.loading = true;
            this.loadError = null;
            try {
                const res = await fetch(this.routes.services_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur " + res.status);
                const data = await res.json();
                this.services = data.data ?? data;
            } catch {
                this.loadError =
                    "Impossible de charger les services. Réessayez.";
            } finally {
                this.loading = false;
            }
        },

        // ── Actions ────────────────────────────────────────────────
        async quickApprove(s) {
            this.actionLoading = s.id;
            try {
                await this.updateStatus(s, "active");
                this.showToast(`✅ Service « ${s.name} » validé.`, "success");
                if (this.activeService?.id === s.id)
                    this.activeService.status = "active";
            } catch {
                this.showToast("Erreur lors de la validation.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        async quickRestore(s) {
            this.actionLoading = s.id;
            try {
                await this.updateStatus(s, "active");
                this.showToast(`🔄 Service « ${s.name} » restauré.`, "success");
                if (this.activeService?.id === s.id)
                    this.activeService.status = "active";
            } catch {
                this.showToast("Erreur lors de la restauration.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        openArchiveModal(s) {
            this.archiveModal = {
                visible: true,
                service: s,
                reason: "",
                loading: false,
            };
        },

        async confirmArchive() {
            if (!this.archiveModal.reason.trim()) return;
            this.archiveModal.loading = true;
            try {
                await this.updateStatus(this.archiveModal.service, "archived");
                this.showToast("Service archivé.", "error");
                this.archiveModal.visible = false;
                if (this.activeService?.id === this.archiveModal.service.id)
                    this.activeService.status = "archived";
            } catch {
                this.showToast("Erreur lors de l'archivage.", "error");
            } finally {
                this.archiveModal.loading = false;
            }
        },

        openRejectModal(s) {
            this.rejectModal = {
                visible: true,
                service: s,
                reason: "",
                loading: false,
            };
        },

        async confirmReject() {
            if (!this.rejectModal.reason.trim()) return;
            this.rejectModal.loading = true;
            try {
                await this.updateStatus(this.rejectModal.service, "rejected");
                this.showToast("Suggestion refusée.", "error");
                this.rejectModal.visible = false;
                if (this.activeService?.id === this.rejectModal.service.id)
                    this.activeService.status = "rejected";
            } catch {
                this.showToast("Erreur lors du refus.", "error");
            } finally {
                this.rejectModal.loading = false;
            }
        },

        async updateStatus(s, status) {
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            const url = this.routes.services_status.replace("{service}", s.id);
            const res = await fetch(url, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: JSON.stringify({ status }),
            });
            if (!res.ok) throw new Error("Erreur " + res.status);
            const idx = this.services.findIndex((x) => x.id === s.id);
            if (idx !== -1) this.services[idx].status = status;
        },

        // ── Formulaire Ajout / Édition ─────────────────────────────
        openAddModal() {
            this.iconSearch = "";
            this.serviceForm.isEdit = false;
            this.serviceForm.loading = false;
            this.serviceForm.data.id = null;
            this.serviceForm.data.name = "";
            this.serviceForm.data.icon = "";
            this.serviceForm.data.category = "";
            this.serviceForm.data.description = "";
            this.serviceForm.data.accreditation = "both";
            this.serviceForm.data.admin_only = false;
            this.serviceForm.visible = true;
        },

        openEditModal(s) {
            this.iconSearch = "";
            this.serviceForm.isEdit = true;
            this.serviceForm.loading = false;
            this.serviceForm.data.id = s.id;
            this.serviceForm.data.name = s.name;
            this.serviceForm.data.icon = s.icon ?? "";
            this.serviceForm.data.category = s.category ?? "";
            this.serviceForm.data.description = s.description ?? "";
            this.serviceForm.data.accreditation = s.accreditation ?? "both";
            this.serviceForm.data.admin_only = s.admin_only ?? false;
            this.serviceForm.visible = true;
            // Scroll automatique sur l'icône sélectionnée
            this.$nextTick(() => {
                const selected = document.querySelector(
                    ".asf-icon-btn.selected",
                );
                if (selected)
                    selected.scrollIntoView({
                        block: "center",
                        behavior: "smooth",
                    });
            });
        },

        async submitServiceForm() {
            if (!this.serviceForm.data.name.trim()) return;
            this.serviceForm.loading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            try {
                const isEdit = this.serviceForm.isEdit;
                const url = isEdit
                    ? this.routes.services_update.replace(
                          "{service}",
                          this.serviceForm.data.id,
                      )
                    : this.routes.services_store;

                const res = await fetch(url, {
                    method: isEdit ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify(this.serviceForm.data),
                });
                if (!res.ok) throw new Error("Erreur " + res.status);
                const saved = await res.json();

                if (isEdit) {
                    const idx = this.services.findIndex(
                        (x) => x.id === saved.id,
                    );
                    if (idx !== -1) this.services.splice(idx, 1, saved);
                    this.showToast(`✅ Service mis à jour.`, "success");
                } else {
                    this.services.unshift(saved);
                    this.showToast(
                        `✅ Service « ${saved.name} » créé.`,
                        "success",
                    );
                }

                this.serviceForm.visible = false;
            } catch {
                this.showToast("Erreur lors de l'enregistrement.", "error");
            } finally {
                this.serviceForm.loading = false;
            }
        },

        // ── Détail ─────────────────────────────────────────────────
        openDetail(s) {
            this.activeService = s;
        },
        closeDetail() {
            this.activeService = null;
        },

        // ── Helpers ────────────────────────────────────────────────
        countByTab(key) {
            if (key === "all") return this.services.length;
            if (key === "active")
                return this.services.filter((s) => s.status === "active")
                    .length;
            if (key === "pending")
                return this.services.filter((s) => s.status === "pending")
                    .length;
            if (key === "archived")
                return this.services.filter((s) => s.status === "archived")
                    .length;
            if (key === "suggestion")
                return this.services.filter((s) => s.is_suggestion).length;
            return 0;
        },

        statusLabel(status) {
            return (
                {
                    active: "Actif",
                    archived: "Archivé",
                    pending: "En attente",
                    rejected: "Refusé",
                }[status] ?? status
            );
        },

        badgeClass(status) {
            return (
                {
                    active: "done",
                    archived: "cancelled",
                    pending: "warning",
                    rejected: "cancelled",
                }[status] ?? ""
            );
        },

        accredLabel(accred) {
            return (
                {
                    domicile: "🏠 Domicile",
                    entreprise: "🏢 Entreprise",
                    both: "🏠🏢 Les deux",
                }[accred] ?? "—"
            );
        },

        accredClass(accred) {
            return (
                {
                    domicile: "ac-chip-domicile",
                    entreprise: "ac-chip-entreprise",
                    both: "ac-chip-both",
                }[accred] ?? ""
            );
        },

        serviceIcon(slug) {
            const icons = {
                plomberie: "🔧",
                electricite: "⚡",
                menuiserie: "🪵",
                ferronnerie: "⛏️",
                frigoriste: "❄️",
                mecanique: "🚗",
                vulcanisateur: "🔩",
                nettoyage: "🧹",
                peinture: "🎨",
                maintenance: "🛠️",
                jardinage: "🌿",
                maconnerie: "🧱",
            };
            return icons[slug?.toLowerCase()] ?? "🔨";
        },

        serviceIconBg(slug) {
            const bgs = {
                plomberie: "linear-gradient(135deg,#3b82f6,#1d4ed8)",
                electricite: "linear-gradient(135deg,#f59e0b,#d97706)",
                menuiserie: "linear-gradient(135deg,#92400e,#78350f)",
                frigoriste: "linear-gradient(135deg,#06b6d4,#0891b2)",
                mecanique: "linear-gradient(135deg,#64748b,#475569)",
                nettoyage: "linear-gradient(135deg,#10b981,#059669)",
                peinture: "linear-gradient(135deg,#8b5cf6,#6d28d9)",
            };
            return (
                bgs[slug?.toLowerCase()] ??
                "linear-gradient(135deg,#F97316,#EA580C)"
            );
        },

        // ── Notifications ──────────────────────────────────────────
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {
            } finally {
                this.notifLoading = false;
            }
        },

        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },

        async markAllRead() {
            try {
                const csrf = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                await fetch(this.routes.notifications_all, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                });
                this.notifications.forEach((n) => (n.read = true));
                this.unreadCount = 0;
            } catch {}
        },

        openNotif(n) {
            n.read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.notifOpen = false;
        },

        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            )
                this.notifOpen = false;
        },

        // ── Toasts ─────────────────────────────────────────────────
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
    },

    mounted() {
        this.fetchServices();
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

/* ── Topbar ── */
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
.amis-count-pill {
    background: var(--grl);
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
}

/* ── Notifications ── */
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
    width: 300px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 100;
    overflow: hidden;
}
.amis-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
}
.amis-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.amis-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.amis-notif-item:hover,
.amis-notif-item.unread {
    background: var(--or3);
}
.amis-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.amis-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.amis-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.amis-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 3px;
}
.amis-notif-loading,
.amis-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--gr);
    text-align: center;
}

/* ── Content ── */
.amis-content {
    padding: 16px;
    max-width: 1200px;
    margin: 0 auto;
}
@media (min-width: 600px) {
    .amis-content {
        padding: 24px;
    }
}

/* ── Stats ── */
.at-stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (min-width: 600px) {
    .at-stats-row {
        grid-template-columns: repeat(4, 1fr);
    }
}
.at-stat {
    background: var(--wh);
    border-radius: 12px;
    border: 1.5px solid var(--grl);
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.at-stat-icon {
    font-size: 26px;
    flex-shrink: 0;
}
.at-stat-val {
    font-size: 22px;
    font-weight: 900;
    color: var(--dk);
    line-height: 1;
}
.at-stat-lbl {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 3px;
}

/* ── Filtres ── */
.amis-filters-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 14px;
}
.amis-filters-left {
    flex: 1;
    min-width: 200px;
}
.amis-filters-right {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.amis-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 0 12px;
}
.amis-search-icon {
    font-size: 14px;
    color: var(--gr);
    flex-shrink: 0;
}
.amis-search {
    border: none;
    background: none;
    outline: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    width: 100%;
    padding: 9px 0;
}
.amis-select {
    border: 1.5px solid var(--grl);
    background: #f8f4f0;
    border-radius: 8px;
    padding: 8px 12px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: var(--dk);
    outline: none;
    cursor: pointer;
}
.amis-select:focus {
    border-color: var(--or);
}

/* ── Tabs ── */
.amis-tabs {
    display: flex;
    gap: 0;
    overflow-x: auto;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 20px;
}
.amis-tab {
    padding: 8px 16px;
    background: none;
    border: none;
    border-radius: 8px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s;
}
.amis-tab:hover {
    color: var(--dk);
}
.amis-tab.active {
    background: var(--or3);
    color: var(--or);
}
.amis-tab-count {
    background: var(--grl);
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 11px;
    color: var(--gr);
}
.amis-tab.active .amis-tab-count {
    background: var(--or);
    color: #fff;
}

/* ── Grille ── */
.ac-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 640px) {
    .ac-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 1024px) {
    .ac-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* ── Skeleton ── */
.ac-skeleton {
    height: 200px;
    border-radius: 16px;
    background: linear-gradient(90deg, #f0e9e4 25%, #e8ddd4 50%, #f0e9e4 75%);
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

/* ── Carte service ── */
.ac-card {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    padding: 16px;
    cursor: pointer;
    transition:
        box-shadow 0.18s,
        transform 0.18s;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ac-card:hover {
    box-shadow: 0 6px 24px rgba(249, 115, 22, 0.13);
    transform: translateY(-2px);
}

.ac-card-head {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ac-service-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ac-service-icon {
    font-size: 22px;
}
.ac-card-headinfo {
    flex: 1;
    min-width: 0;
}
.ac-card-name {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-card-email {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}

.ac-card-stats {
    display: flex;
    gap: 0;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    overflow: hidden;
}
.ac-cstat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border-right: 1px solid var(--grl);
}
.ac-cstat:last-child {
    border-right: none;
}
.ac-cstat-val {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.ac-cstat-lbl {
    font-size: 10px;
    color: var(--gr);
    margin-top: 2px;
    text-align: center;
}

.ac-card-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ac-chip {
    font-size: 11.5px;
    font-weight: 600;
    border-radius: 6px;
    padding: 3px 8px;
    background: var(--grl);
    color: var(--gr);
}
.ac-chip-domicile {
    background: #dbeafe;
    color: #1e40af;
}
.ac-chip-entreprise {
    background: #fef3c7;
    color: #92400e;
}
.ac-chip-both {
    background: #f0fdf4;
    color: #166534;
}
.ac-chip-admin {
    background: #fce7f3;
    color: #9d174d;
}
.ac-chip-suggestion {
    background: #fef9c3;
    color: #713f12;
}

.ac-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

/* ── Vide / Erreur ── */
.amis-empty {
    text-align: center;
    padding: 60px 20px;
}
.amis-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.amis-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 6px;
}
.amis-empty-sub {
    font-size: 13px;
    color: var(--gr);
}
.amis-alert-error {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    border-radius: 12px;
    padding: 16px 20px;
    color: #dc2626;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ── Pagination ── */
.ac-pagination {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 24px;
    flex-wrap: wrap;
    justify-content: center;
}
.ac-page-btn {
    min-width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.15s;
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
    font-size: 12px;
    color: var(--gr);
    margin-left: 8px;
}

/* ── Badges ── */
.amis-badge {
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    padding: 3px 8px;
    white-space: nowrap;
}
.amis-badge.done {
    background: #dcfce7;
    color: #166534;
}
.amis-badge.warning {
    background: #fef9c3;
    color: #713f12;
}
.amis-badge.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

/* ── Boutons ── */
.amis-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 9px;
    border: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.18s;
}
.amis-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.amis-btn-ghost:hover {
    background: #ddd0c6;
}
.amis-btn-orange {
    background: var(--or);
    color: #fff;
}
.amis-btn-orange:hover {
    background: var(--or2);
}
.amis-btn-green {
    background: var(--green);
    color: #fff;
}
.amis-btn-green:hover {
    background: #15803d;
}
.amis-btn-red {
    background: var(--red);
    color: #fff;
}
.amis-btn-red:hover {
    background: #b91c1c;
}
.amis-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}
.amis-btn-xs {
    padding: 4px 10px;
    font-size: 11.5px;
    border-radius: 6px;
}

/* ── Spinner ── */
.amis-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── Modals ── */
.amis-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
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
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}
.amis-modal-xl {
    max-width: 860px;
}
.amis-modal-form {
    max-width: 660px;
}
.amis-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 18px 20px;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amis-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.amis-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 4px;
}
.amis-modal-close {
    background: none;
    border: none;
    font-size: 20px;
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
.amis-modal-close:hover {
    background: var(--grl);
}
.amis-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    flex: 1;
}
.amis-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 14px 20px;
    border-top: 1.5px solid var(--grl);
    flex-shrink: 0;
}

/* ── Détail grille ── */
.amis-detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 640px) {
    .amis-detail-grid {
        grid-template-columns: 1fr 1fr;
    }
}
.amis-detail-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.amis-detail-section {
    background: #f8f4f0;
    border-radius: 10px;
    padding: 14px;
}
.amis-detail-section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
}
.amis-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    padding: 5px 0;
    border-bottom: 1px solid var(--grl);
}
.amis-detail-row:last-child {
    border-bottom: none;
}
.amis-detail-row span {
    color: var(--gr);
    flex-shrink: 0;
}
.amis-detail-row strong {
    color: var(--dk);
    font-weight: 600;
    text-align: right;
}
.at-empty-sub {
    font-size: 12.5px;
    color: var(--gr);
    font-style: italic;
}
.at-actions-section .amis-detail-section-title {
    margin-bottom: 12px;
}
.at-actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ac-mission-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--grl);
}
.ac-mission-item:last-child {
    border-bottom: none;
}
.ac-mission-check {
    font-size: 18px;
    flex-shrink: 0;
}
.ac-mission-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.ac-mission-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}

/* ── Formulaire service ── */
.amis-input {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    padding: 9px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    background: #f8f4f0;
    box-sizing: border-box;
}
.amis-input:focus {
    border-color: var(--or);
    background: #fff;
}
.amis-textarea {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    padding: 10px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    resize: vertical;
    background: #f8f4f0;
    box-sizing: border-box;
}
.amis-textarea:focus {
    border-color: var(--or);
    background: #fff;
}
.amis-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    margin-bottom: 6px;
}
.asf-field {
    margin-bottom: 14px;
}
.asf-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}
.asf-row .asf-field {
    flex: 1;
    min-width: 160px;
}
.asf-select-full {
    width: 100%;
}
.asf-checkbox-field {
    display: flex;
    flex-direction: column;
}
.asf-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--dk);
    cursor: pointer;
    margin-top: 4px;
}
.asf-checkbox input {
    accent-color: var(--or);
    width: 15px;
    height: 15px;
}

/* ── Icon Picker ── */
.asf-icon-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 0 12px;
    margin-bottom: 10px;
}
.asf-icon-search {
    border: none;
    background: none;
    outline: none;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: var(--dk);
    width: 100%;
    padding: 8px 0;
}
.asf-icon-picker {
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 10px 12px;
    background: #faf7f4;
    max-height: 240px;
    overflow-y: auto;
}
.asf-icon-group-label {
    font-size: 10.5px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 6px 0 4px;
    margin-top: 4px;
}
.asf-icon-group-label:first-child {
    margin-top: 0;
}
.asf-icon-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 4px;
}
.asf-icon-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 7px 6px;
    border-radius: 9px;
    border: 1.5px solid transparent;
    background: var(--wh);
    cursor: pointer;
    transition: all 0.15s;
    min-width: 54px;
}
.asf-icon-btn:hover {
    border-color: var(--or);
    background: var(--or3);
}
.asf-icon-btn.selected {
    border-color: var(--or);
    background: var(--or3);
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.25);
}
.asf-icon-emoji {
    font-size: 22px;
    line-height: 1;
}
.asf-icon-label {
    font-size: 9.5px;
    color: var(--gr);
    font-family: "Poppins", sans-serif;
    text-align: center;
    line-height: 1.2;
    max-width: 52px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.asf-icon-btn.selected .asf-icon-label {
    color: var(--or);
    font-weight: 700;
}
.asf-icon-empty {
    padding: 16px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
    font-style: italic;
}

/* ── Toasts ── */
.amis-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 9999;
}
.amis-toast {
    background: var(--dk);
    color: #fff;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.18);
    animation: fadeUp 0.25s ease;
    max-width: 320px;
}
.amis-toast.success {
    background: #16a34a;
}
.amis-toast.error {
    background: #dc2626;
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
