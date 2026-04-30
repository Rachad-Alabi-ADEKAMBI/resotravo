<template>
    <div class="amis-wrap">
        <!-- -------------- TOPBAR -------------- -->
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
                    <div class="amis-page-title">Gestion des Talents</div>
                    <div class="amis-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <div class="amis-count-pill">
                    {{ totalFiltered }} dossier{{
                        totalFiltered > 1 ? "s" : ""
                    }}
                </div>
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
                            unreadCount
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
                <div class="amis-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- -------------- CONTENU -------------- -->
        <div class="amis-content">
            <!-- -- STATS -- -->
            <div class="at-stats-row">
                <div class="at-stat" v-for="s in statCards" :key="s.label">
                    <div class="at-stat-icon">{{ s.icon }}</div>
                    <div class="at-stat-body">
                        <div class="at-stat-val">{{ s.value }}</div>
                        <div class="at-stat-lbl">{{ s.label }}</div>
                    </div>
                </div>
            </div>

            <!-- -- FILTRES -- -->
            <div class="amis-filters-bar">
                <div class="amis-filters-left">
                    <div class="amis-search-wrap">
                        <span class="amis-search-icon">??</span>
                        <input
                            class="amis-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher nom, email, domaine…"
                            @input="currentPage = 1"
                        />
                    </div>
                </div>
                <div class="amis-filters-right">
                    <select
                        class="amis-select"
                        v-model="filterDomain"
                        @change="currentPage = 1"
                    >
                        <option value="">Tous les domaines</option>
                        <option
                            v-for="d in availableDomains"
                            :key="d"
                            :value="d"
                        >
                            {{ d }}
                        </option>
                    </select>
                    <select
                        class="amis-select"
                        v-model="filterAvailability"
                        @change="currentPage = 1"
                    >
                        <option value="">Toutes disponibilités</option>
                        <option value="immediate">
                            Disponible immédiatement
                        </option>
                        <option value="parttime">Temps partiel</option>
                        <option value="mission">Mission spécifique</option>
                    </select>
                </div>
            </div>

            <!-- -- TABS -- -->
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

            <!-- -- LOADER -- -->
            <div class="ac-grid" v-if="loading">
                <div class="ac-skeleton" v-for="n in 6" :key="n"></div>
            </div>

            <!-- -- ERREUR -- -->
            <div class="amis-alert-error" v-else-if="loadError">
                ?? {{ loadError }}
                <button
                    class="amis-btn amis-btn-ghost amis-btn-sm"
                    @click="fetchApplications"
                >
                    Réessayer
                </button>
            </div>

            <!-- -- VIDE -- -->
            <div class="amis-empty" v-else-if="pagedApplications.length === 0">
                <div class="amis-empty-icon">??</div>
                <div class="amis-empty-title">Aucun dossier trouvé</div>
                <div class="amis-empty-sub">
                    Modifiez vos filtres ou revenez plus tard.
                </div>
            </div>

            <!-- -- GRILLE CARTES -- -->
            <div class="ac-grid" v-else>
                <div
                    class="ac-card"
                    v-for="a in pagedApplications"
                    :key="a.id"
                    :class="{ 'ac-card-pending': a.status === 'pending' }"
                    @click="openDetail(a)"
                >
                    <!-- En-tête -->
                    <div class="ac-card-head">
                        <div
                            class="ac-avatar"
                            :style="{ background: avatarColor(a.id) }"
                        >
                            {{ initials(a.full_name) }}
                        </div>
                        <div class="ac-card-headinfo">
                            <div class="ac-card-name">{{ a.full_name }}</div>
                            <div class="ac-card-specialty">{{ a.title }}</div>
                            <div class="ac-card-city">
                                {{ a.exp }} ans d'exp.
                            </div>
                        </div>
                        <span
                            class="amis-badge"
                            :class="badgeClass(a.status)"
                            >{{ statusLabel(a.status) }}</span
                        >
                    </div>

                    <!-- Stats -->
                    <div class="ac-card-stats">
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                a.level ?? "—"
                            }}</span>
                            <span class="ac-cstat-lbl">Niveau</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{ a.exp }} ans</span>
                            <span class="ac-cstat-lbl">Exp.</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{ a.created_at }}</span>
                            <span class="ac-cstat-lbl">Soumis</span>
                        </div>
                    </div>

                    <!-- Chips domaine + disponibilité -->
                    <div class="ac-card-chips">
                        <span class="at-domain-chip">{{ a.domain }}</span>
                        <span class="ac-chip ac-chip-avail">{{
                            a.availability
                        }}</span>
                    </div>

                    <!-- Motif refus -->
                    <div
                        class="at-reject-inline"
                        v-if="a.status === 'rejected' && a.reject_reason"
                    >
                        ?? <em>{{ a.reject_reason }}</em>
                    </div>

                    <!-- Actions rapides -->
                    <div class="ac-card-actions" @click.stop>
                        <button
                            class="amis-btn amis-btn-green amis-btn-xs"
                            v-if="a.status === 'pending'"
                            @click.stop="quickApprove(a)"
                            :disabled="actionLoading === a.id"
                        >
                            ? Approuver
                        </button>
                        <button
                            class="amis-btn amis-btn-red amis-btn-xs"
                            v-if="a.status === 'pending'"
                            @click.stop="openRejectModal(a)"
                        >
                            ? Refuser
                        </button>
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-xs"
                            v-if="a.status === 'approved'"
                            @click.stop="openSuspendModal(a)"
                        >
                            ?? Suspendre
                        </button>
                        <button
                            class="amis-btn amis-btn-orange amis-btn-xs"
                            v-if="a.status === 'rejected'"
                            @click.stop="quickApprove(a)"
                            :disabled="actionLoading === a.id"
                        >
                            ?? Ré-approuver
                        </button>
                    </div>
                </div>
            </div>

            <!-- -- PAGINATION -- -->
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
                    {{ totalFiltered }} dossier{{
                        totalFiltered > 1 ? "s" : ""
                    }}
                </span>
            </div>
        </div>
        <!-- end amis-content -->

        <!-- -------------- MODAL DOSSIER COMPLET -------------- -->
        <div
            class="amis-modal-overlay"
            v-if="activeApp"
            @click.self="closeDetail"
        >
            <div class="amis-modal amis-modal-xl">
                <div class="amis-modal-header">
                    <div>
                        <h3>
                            {{ activeApp.full_name }}
                            <span
                                class="amis-badge"
                                :class="badgeClass(activeApp.status)"
                                style="margin-left: 8px"
                            >
                                {{ statusLabel(activeApp.status) }}
                            </span>
                        </h3>
                        <div class="amis-modal-sub">
                            Dossier #{{ activeApp.id }} · Soumis le
                            {{ activeApp.created_at }}
                        </div>
                    </div>
                    <button class="amis-modal-close" @click="closeDetail">
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <div class="amis-detail-grid">
                        <div class="amis-detail-col">
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    ?? Informations personnelles
                                </div>
                                <div class="amis-detail-row">
                                    <span>Nom complet</span
                                    ><strong>{{ activeApp.full_name }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Email</span
                                    ><strong>{{ activeApp.email }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Téléphone</span
                                    ><strong>{{ activeApp.phone }}</strong>
                                </div>
                            </div>
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    ?? Profil professionnel
                                </div>
                                <div class="amis-detail-row">
                                    <span>Titre</span
                                    ><strong>{{ activeApp.title }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Domaine</span
                                    ><strong>{{ activeApp.domain }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Niveau</span
                                    ><strong>{{ activeApp.level }}</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Expérience</span
                                    ><strong>{{ activeApp.exp }} ans</strong>
                                </div>
                                <div class="amis-detail-row">
                                    <span>Disponibilité</span
                                    ><strong>{{
                                        activeApp.availability
                                    }}</strong>
                                </div>
                            </div>
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    ?? À propos
                                </div>
                                <p class="at-bio">{{ activeApp.bio }}</p>
                            </div>
                            <div
                                class="amis-detail-section"
                                v-if="activeApp.linkedin || activeApp.portfolio"
                            >
                                <div class="amis-detail-section-title">
                                    ?? Liens
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeApp.linkedin"
                                >
                                    <span>LinkedIn</span>
                                    <a
                                        :href="activeApp.linkedin"
                                        target="_blank"
                                        class="at-link"
                                        >{{ activeApp.linkedin }}</a
                                    >
                                </div>
                                <div
                                    class="amis-detail-row"
                                    v-if="activeApp.portfolio"
                                >
                                    <span>Portfolio</span>
                                    <a
                                        :href="activeApp.portfolio"
                                        target="_blank"
                                        class="at-link"
                                        >{{ activeApp.portfolio }}</a
                                    >
                                </div>
                            </div>
                            <div
                                class="at-reject-box"
                                v-if="
                                    activeApp.status === 'rejected' &&
                                    activeApp.reject_reason
                                "
                            >
                                ?? <strong>Motif du refus :</strong>
                                {{ activeApp.reject_reason }}
                            </div>
                        </div>
                        <div class="amis-detail-col">
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    ?? Diplômes
                                </div>
                                <div
                                    class="at-diploma-item"
                                    v-for="(d, i) in activeApp.diplomas"
                                    :key="i"
                                >
                                    <div class="at-diploma-title">
                                        ?? {{ d.title }}
                                    </div>
                                    <div class="at-diploma-meta">
                                        {{ d.school }} · {{ d.year }}
                                    </div>
                                </div>
                                <div
                                    class="at-empty-sub"
                                    v-if="!activeApp.diplomas?.length"
                                >
                                    Aucun diplôme renseigné.
                                </div>
                            </div>
                            <div
                                class="amis-detail-section"
                                v-if="activeApp.certifications?.length"
                            >
                                <div class="amis-detail-section-title">
                                    ?? Certifications
                                </div>
                                <div class="at-skills-wrap">
                                    <span
                                        class="at-skill"
                                        v-for="(
                                            c, i
                                        ) in activeApp.certifications"
                                        :key="i"
                                        >{{ c }}</span
                                    >
                                </div>
                            </div>
                            <div class="amis-detail-section">
                                <div class="amis-detail-section-title">
                                    ?? Expériences professionnelles
                                </div>
                                <div
                                    class="at-exp-item"
                                    v-for="(e, i) in activeApp.experiences"
                                    :key="i"
                                >
                                    <div class="at-exp-dot"></div>
                                    <div>
                                        <div class="at-exp-role">
                                            {{ e.role }}
                                        </div>
                                        <div class="at-exp-meta">
                                            {{ e.company }} · {{ e.period }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="at-empty-sub"
                                    v-if="!activeApp.experiences?.length"
                                >
                                    Aucune expérience renseignée.
                                </div>
                            </div>
                            <div class="amis-detail-section at-actions-section">
                                <div class="amis-detail-section-title">
                                    ?? Actions administrateur
                                </div>
                                <div class="at-actions-row">
                                    <button
                                        class="amis-btn amis-btn-green"
                                        v-if="activeApp.status === 'pending'"
                                        @click="quickApprove(activeApp)"
                                        :disabled="
                                            actionLoading === activeApp.id
                                        "
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="
                                                actionLoading === activeApp.id
                                            "
                                        ></div>
                                        <span v-else
                                            >? Approuver le profil</span
                                        >
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-red"
                                        v-if="activeApp.status === 'pending'"
                                        @click="openRejectModal(activeApp)"
                                    >
                                        ? Refuser
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-ghost"
                                        v-if="activeApp.status === 'approved'"
                                        @click="openSuspendModal(activeApp)"
                                    >
                                        ?? Suspendre le talent
                                    </button>
                                    <button
                                        class="amis-btn amis-btn-orange"
                                        v-if="activeApp.status === 'rejected'"
                                        @click="quickApprove(activeApp)"
                                        :disabled="
                                            actionLoading === activeApp.id
                                        "
                                    >
                                        <div
                                            class="amis-spinner"
                                            v-if="
                                                actionLoading === activeApp.id
                                            "
                                        ></div>
                                        <span v-else>?? Ré-approuver</span>
                                    </button>
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

        <!-- -------------- MODAL REFUS -------------- -->
        <div
            class="amis-modal-overlay"
            v-if="rejectModal.visible"
            @click.self="rejectModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>Refuser le dossier</h3></div>
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
                        Vous êtes sur le point de refuser la demande de
                        <strong>{{ rejectModal.app?.full_name }}</strong
                        >. Le candidat sera notifié avec le motif indiqué.
                    </p>
                    <label class="amis-form-label"
                        >Motif du refus
                        <span style="color: var(--or)">*</span></label
                    >
                    <textarea
                        class="amis-textarea"
                        v-model="rejectModal.reason"
                        rows="3"
                        placeholder="Ex : Niveau d'études insuffisant, dossier incomplet…"
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
                        <span v-else>? Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- MODAL SUSPENSION -------------- -->
        <div
            class="amis-modal-overlay"
            v-if="suspendModal.visible"
            @click.self="suspendModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>Suspendre le talent</h3></div>
                    <button
                        class="amis-modal-close"
                        @click="suspendModal.visible = false"
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
                        Suspendre
                        <strong>{{ suspendModal.app?.full_name }}</strong>
                        masquera son profil de l'Espace Talents et désactivera
                        son compte.
                    </p>
                    <label class="amis-form-label"
                        >Motif de la suspension
                        <span style="color: var(--or)">*</span></label
                    >
                    <textarea
                        class="amis-textarea"
                        v-model="suspendModal.reason"
                        rows="3"
                        placeholder="Ex : Comportement inapproprié, fausses informations…"
                    ></textarea>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="suspendModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-red"
                        @click="confirmSuspend"
                        :disabled="
                            !suspendModal.reason.trim() || suspendModal.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="suspendModal.loading"
                        ></div>
                        <span v-else>?? Confirmer la suspension</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- TOASTS -------------- -->
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
    name: "AdminTalentComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            applications: [],
            loading: false,
            loadError: null,

            search: "",
            filterDomain: "",
            filterAvailability: "",
            activeTab: "all",

            currentPage: 1,
            perPage: 6,

            activeApp: null,
            actionLoading: null,

            rejectModal: {
                visible: false,
                app: null,
                reason: "",
                loading: false,
            },
            suspendModal: {
                visible: false,
                app: null,
                reason: "",
                loading: false,
            },

            sidebarOpen: false,
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,
            toasts: [],
            toastId: 0,
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
                    .slice(0, 2) ?? "AD"
            );
        },

        tabs() {
            return [
                { key: "all", label: "Tous" },
                { key: "pending", label: "? En attente" },
                { key: "approved", label: "? Approuvés" },
                { key: "rejected", label: "? Refusés" },
            ];
        },

        availableDomains() {
            return [...new Set(this.applications.map((a) => a.domain))].sort();
        },

        statCards() {
            return [
                {
                    icon: "??",
                    value: this.applications.length,
                    label: "Total dossiers",
                },
                {
                    icon: "?",
                    value: this.applications.filter(
                        (a) => a.status === "pending"
                    ).length,
                    label: "En attente",
                },
                {
                    icon: "?",
                    value: this.applications.filter(
                        (a) => a.status === "approved"
                    ).length,
                    label: "Talents actifs",
                },
                {
                    icon: "?",
                    value: this.applications.filter(
                        (a) => a.status === "rejected"
                    ).length,
                    label: "Refusés",
                },
            ];
        },

        filteredApplications() {
            let list = [...this.applications];
            if (this.activeTab !== "all")
                list = list.filter((a) => a.status === this.activeTab);
            if (this.filterDomain)
                list = list.filter((a) => a.domain === this.filterDomain);
            if (this.filterAvailability)
                list = list.filter(
                    (a) => a.availability_raw === this.filterAvailability
                );
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (a) =>
                        a.full_name?.toLowerCase().includes(q) ||
                        a.email?.toLowerCase().includes(q) ||
                        a.domain?.toLowerCase().includes(q) ||
                        a.title?.toLowerCase().includes(q)
                );
            }
            return list;
        },

        totalFiltered() {
            return this.filteredApplications.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },

        pagedApplications() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredApplications.slice(start, start + this.perPage);
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
        async fetchApplications() {
            this.loading = true;
            this.loadError = null;
            try {
                const res = await fetch(this.routes.talents_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur " + res.status);
                this.applications = await res.json();
            } catch {
                this.loadError =
                    "Impossible de charger les dossiers. Réessayez.";
            } finally {
                this.loading = false;
            }
        },

        async quickApprove(app) {
            this.actionLoading = app.id;
            try {
                await this.updateStatus(app, "approved", null);
                this.showToast(
                    `? ${app.full_name} approuvé. Compte créé.`,
                    "success"
                );
                if (this.activeApp?.id === app.id) this.closeDetail();
            } catch {
                this.showToast("Erreur lors de l'approbation.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        openRejectModal(app) {
            this.rejectModal = {
                visible: true,
                app,
                reason: "",
                loading: false,
            };
        },
        openSuspendModal(app) {
            this.suspendModal = {
                visible: true,
                app,
                reason: "",
                loading: false,
            };
        },

        async confirmReject() {
            if (!this.rejectModal.reason.trim()) return;
            this.rejectModal.loading = true;
            try {
                await this.updateStatus(
                    this.rejectModal.app,
                    "rejected",
                    this.rejectModal.reason
                );
                this.showToast("Dossier refusé.", "error");
                this.rejectModal.visible = false;
                if (this.activeApp?.id === this.rejectModal.app.id)
                    this.closeDetail();
            } catch {
                this.showToast("Erreur lors du refus.", "error");
            } finally {
                this.rejectModal.loading = false;
            }
        },

        async confirmSuspend() {
            if (!this.suspendModal.reason.trim()) return;
            this.suspendModal.loading = true;
            try {
                await this.updateStatus(
                    this.suspendModal.app,
                    "rejected",
                    this.suspendModal.reason
                );
                this.showToast("Talent suspendu.", "error");
                this.suspendModal.visible = false;
                if (this.activeApp?.id === this.suspendModal.app.id)
                    this.closeDetail();
            } catch {
                this.showToast("Erreur lors de la suspension.", "error");
            } finally {
                this.suspendModal.loading = false;
            }
        },

        async updateStatus(app, status, reason) {
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            const url = this.routes.talents_status.replace(
                "{application}",
                app.id
            );
            const res = await fetch(url, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: JSON.stringify({ status, reject_reason: reason }),
            });
            if (!res.ok) throw new Error("Erreur " + res.status);
            const data = await res.json();
            const idx = this.applications.findIndex((a) => a.id === app.id);
            if (idx !== -1) {
                this.applications.splice(idx, 1, {
                    ...this.applications[idx],
                    status,
                    status_label: this.statusLabel(status),
                    reject_reason: reason,
                });
            }
            return data;
        },

        openDetail(app) {
            this.activeApp = app;
        },
        closeDetail() {
            this.activeApp = null;
        },

        countByTab(key) {
            if (key === "all") return this.applications.length;
            return this.applications.filter((a) => a.status === key).length;
        },

        statusLabel(status) {
            return (
                {
                    pending: "En attente",
                    approved: "Approuvé",
                    rejected: "Refusé",
                }[status] ?? status
            );
        },
        badgeClass(status) {
            return (
                { pending: "warning", approved: "done", rejected: "cancelled" }[
                    status
                ] ?? ""
            );
        },

        avatarColor(id) {
            const colors = [
                "linear-gradient(135deg,#F97316,#EA580C)",
                "linear-gradient(135deg,#3b82f6,#1d4ed8)",
                "linear-gradient(135deg,#10b981,#059669)",
                "linear-gradient(135deg,#8b5cf6,#6d28d9)",
                "linear-gradient(135deg,#f59e0b,#d97706)",
                "linear-gradient(135deg,#ef4444,#dc2626)",
            ];
            return colors[id % colors.length];
        },

        initials(name) {
            return (
                name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "TL"
            );
        },

        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications_index, {
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
                await fetch(this.routes.notifications_read_all, {
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
                })
            );
        },
    },

    mounted() {
        this.fetchApplications();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000
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
    overflow-x: hidden;
}
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
    gap: 6px;
    flex-shrink: 0;
}
@media (min-width: 480px) {
    .amis-topbar-right {
        gap: 10px;
    }
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
    display: none;
}
@media (min-width: 480px) {
    .amis-count-pill {
        display: inline-block;
    }
}
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
/* -- FILTRES -- */
.amis-filters-bar {
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 14px;
}
@media (min-width: 600px) {
    .amis-filters-bar {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        padding: 12px 16px;
        gap: 10px;
    }
}
.amis-filters-left {
    width: 100%;
}
@media (min-width: 600px) {
    .amis-filters-left {
        flex: 1;
        min-width: 180px;
    }
}
.amis-filters-right {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    width: 100%;
}
@media (min-width: 600px) {
    .amis-filters-right {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        width: auto;
    }
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
    font-size: 13px;
    color: var(--dk);
    width: 100%;
    padding: 9px 0;
}
.amis-select {
    border: 1.5px solid var(--grl);
    background: #f8f4f0;
    border-radius: 8px;
    padding: 7px 8px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    color: var(--dk);
    outline: none;
    cursor: pointer;
    width: 100%;
    min-width: 0;
}
@media (min-width: 600px) {
    .amis-select {
        font-size: 13px;
        padding: 8px 12px;
        width: auto;
    }
}
.amis-select:focus {
    border-color: var(--or);
}
/* -- TABS -- */
.amis-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 20px;
}
@media (max-width: 480px) {
    .amis-tabs {
        grid-template-columns: repeat(2, 1fr);
    }
}
.amis-tab {
    padding: 7px 6px;
    background: none;
    border: none;
    border-radius: 8px;
    font-family: "Poppins", sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.18s;
    text-align: center;
    overflow: hidden;
}
@media (min-width: 600px) {
    .amis-tab {
        font-size: 13px;
        padding: 8px 12px;
    }
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
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 700;
    flex-shrink: 0;
}
@media (min-width: 600px) {
    .amis-tab-count {
        font-size: 11px;
        padding: 1px 7px;
    }
}
.amis-tab.active .amis-tab-count {
    background: var(--or);
    color: #fff;
}
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
.ac-skeleton {
    height: 220px;
    background: linear-gradient(
        90deg,
        var(--grl) 25%,
        #f0ebe5 50%,
        var(--grl) 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 14px;
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
.ac-card {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ac-card:hover {
    border-color: var(--or);
    box-shadow: 0 6px 24px rgba(249, 115, 22, 0.1);
    transform: translateY(-2px);
}
.ac-card-pending {
    border-left: 4px solid var(--am);
}
.ac-card-head {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: wrap;
}
.ac-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
}
.ac-card-headinfo {
    flex: 1;
    min-width: 0;
}
.ac-card-name {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-card-specialty {
    font-size: 12.5px;
    color: var(--or);
    font-weight: 600;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-card-city {
    font-size: 12px;
    color: var(--gr);
    margin-top: 1px;
}
.ac-card-stats {
    display: flex;
    gap: 0;
    border: 1px solid var(--grl);
    border-radius: 10px;
    overflow: hidden;
}
.ac-cstat {
    flex: 1;
    text-align: center;
    padding: 8px 4px;
    border-right: 1px solid var(--grl);
}
.ac-cstat:last-child {
    border-right: none;
}
.ac-cstat-val {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-cstat-lbl {
    display: block;
    font-size: 9.5px;
    color: var(--gr);
    margin-top: 1px;
}
.ac-card-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ac-chip {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
    background: var(--grl);
    color: var(--gr);
}
.ac-chip-avail {
    background: #dcfce7;
    color: #15803d;
}
.at-domain-chip {
    background: var(--or3);
    color: var(--or);
    border-radius: 6px;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 700;
}
.at-reject-inline {
    font-size: 12px;
    color: #dc2626;
    background: #fef2f2;
    border-radius: 6px;
    padding: 6px 10px;
}
.ac-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding-top: 4px;
    border-top: 1px solid var(--grl);
}
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
.amis-alert-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 13.5px;
    color: var(--red);
    display: flex;
    align-items: center;
    gap: 12px;
}
.amis-empty {
    text-align: center;
    padding: 52px 24px;
}
.amis-empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}
.amis-empty-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 6px;
}
.amis-empty-sub {
    font-size: 13.5px;
    color: var(--gr);
}
.amis-badge {
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 700;
}
.amis-badge.pending {
    background: #fef9c3;
    color: #a16207;
}
.amis-badge.warning {
    background: #fff7ed;
    color: #c2410c;
}
.amis-badge.done {
    background: #dcfce7;
    color: #15803d;
}
.amis-badge.cancelled {
    background: #fee2e2;
    color: #b91c1c;
}
.amis-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.18s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.amis-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.amis-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.amis-btn-ghost:hover {
    background: #ddd4c8;
}
.amis-btn-orange {
    background: var(--or);
    color: #fff;
}
.amis-btn-orange:hover {
    background: var(--or2);
}
.amis-btn-red {
    background: #dc2626;
    color: #fff;
}
.amis-btn-red:hover {
    background: #b91c1c;
}
.amis-btn-green {
    background: #16a34a;
    color: #fff;
}
.amis-btn-green:hover {
    background: #15803d;
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
.at-bio {
    font-size: 13px;
    color: var(--dk);
    line-height: 1.7;
    margin: 0;
}
.at-link {
    color: var(--or);
    font-size: 12.5px;
    word-break: break-all;
}
.at-reject-box {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    color: #dc2626;
}
.at-diploma-item {
    background: var(--wh);
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 8px;
    border: 1px solid var(--grl);
}
.at-diploma-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.at-diploma-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.at-skills-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.at-skill {
    background: var(--or3);
    color: var(--or);
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 600;
}
.at-exp-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 10px;
}
.at-exp-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.at-exp-role {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.at-exp-meta {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
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
.amis-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    margin-bottom: 6px;
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


