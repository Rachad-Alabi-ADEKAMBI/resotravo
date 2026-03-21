<template>
    <div class="amk-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="amk-topbar">
            <div class="amk-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="amk-page-title">Appels d'offres</div>
                    <div class="amk-page-sub">Validation et gestion des AO</div>
                </div>
            </div>
            <div class="amk-topbar-right">
                <div class="amk-stat-pills">
                    <div class="amk-stat-pill warn">
                        <span>{{ counts.pending }}</span> en attente
                    </div>
                    <div class="amk-stat-pill ok">
                        <span>{{ counts.published }}</span> publiés
                    </div>
                    <div class="amk-stat-pill neutral">
                        <span>{{ counts.total }}</span> total
                    </div>
                </div>
                <div class="amk-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="amk-content">
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
                            placeholder="Rechercher titre, entreprise, domaine…"
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
                        v-model="filterProfile"
                        @change="currentPage = 1"
                    >
                        <option value="">Tous les profils</option>
                        <option value="prestataire">👷 Prestataire</option>
                        <option value="talent">⭐ Talent</option>
                        <option value="both">👷⭐ Les deux</option>
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
                        setTab(t.key);
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
            <div class="amis-alert-error" v-else-if="error">
                ⚠️ {{ error }}
                <button
                    class="amis-btn amis-btn-ghost amis-btn-sm"
                    @click="fetchTenders"
                >
                    Réessayer
                </button>
            </div>

            <!-- ── VIDE ── -->
            <div class="amis-empty" v-else-if="pagedTenders.length === 0">
                <div class="amis-empty-icon">📋</div>
                <div class="amis-empty-title">
                    Aucun appel d'offres{{
                        activeTab !== "all" ? " dans cette catégorie" : ""
                    }}
                </div>
                <div class="amis-empty-sub">
                    Les nouvelles soumissions apparaîtront ici.
                </div>
            </div>

            <!-- ── GRILLE CARTES ── -->
            <div class="ac-grid" v-else>
                <div
                    class="ac-card"
                    v-for="t in pagedTenders"
                    :key="t.id"
                    :class="{ 'ac-card-pending': t.status === 'pending' }"
                    @click="openTender(t)"
                >
                    <!-- En-tête -->
                    <div class="ac-card-head">
                        <div class="amk-domain-icon">{{ t.domainIcon }}</div>
                        <div class="ac-card-headinfo">
                            <div class="ac-card-name">{{ t.title }}</div>
                            <div class="ac-card-specialty">
                                🏢 {{ t.company }}
                            </div>
                            <div class="ac-card-city">📍 {{ t.location }}</div>
                        </div>
                        <span
                            class="amis-badge"
                            :class="badgeClass(t.status)"
                            >{{ statusLabel(t.status) }}</span
                        >
                    </div>

                    <!-- Stats -->
                    <div class="ac-card-stats">
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                t.candidatures ?? 0
                            }}</span>
                            <span class="ac-cstat-lbl">Candidatures</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                t.budget || "—"
                            }}</span>
                            <span class="ac-cstat-lbl">Budget</span>
                        </div>
                        <div class="ac-cstat">
                            <span class="ac-cstat-val">{{
                                t.deadline || "—"
                            }}</span>
                            <span class="ac-cstat-lbl">Limite</span>
                        </div>
                    </div>

                    <!-- Chips -->
                    <div class="ac-card-chips">
                        <span class="at-domain-chip">{{ t.domain }}</span>
                        <span
                            class="amk-profile-chip"
                            :class="t.profile_type"
                            >{{ profileTypeLabel(t.profile_type) }}</span
                        >
                        <span class="ac-chip">⏱️ {{ t.duration }}</span>
                    </div>

                    <!-- Motif rejet -->
                    <div
                        class="at-reject-inline"
                        v-if="t.status === 'rejected' && t.reject_reason"
                    >
                        💬 <em>{{ t.reject_reason }}</em>
                    </div>

                    <!-- Actions rapides -->
                    <div class="ac-card-actions" @click.stop>
                        <button
                            class="amis-btn amis-btn-green amis-btn-xs"
                            v-if="t.status === 'pending'"
                            @click.stop="quickPublish(t)"
                            :disabled="actionLoading === t.id + '_published'"
                        >
                            <div
                                class="amis-spinner"
                                v-if="actionLoading === t.id + '_published'"
                            ></div>
                            <span v-else>✅ Publier</span>
                        </button>
                        <button
                            class="amis-btn amis-btn-red amis-btn-xs"
                            v-if="t.status === 'pending'"
                            @click.stop="openRejectModalFor(t)"
                        >
                            ❌ Rejeter
                        </button>
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-xs"
                            v-if="t.status === 'published'"
                            @click.stop="quickClose(t)"
                            :disabled="actionLoading === t.id + '_closed'"
                        >
                            <div
                                class="amis-spinner amis-spinner-dark"
                                v-if="actionLoading === t.id + '_closed'"
                            ></div>
                            <span v-else>🔒 Clôturer</span>
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
                    {{ totalFiltered }} AO
                </span>
            </div>
        </div>
        <!-- end amk-content -->

        <!-- ══════════════ PANEL DÉTAIL ══════════════ -->
        <div
            class="amk-panel-overlay"
            v-if="activeTender"
            @click.self="activeTender = null"
        >
            <div class="amk-panel">
                <div class="amk-panel-header">
                    <div>
                        <h2>{{ activeTender.title }}</h2>
                        <div class="amk-panel-sub">
                            AO #{{ activeTender.id }} ·
                            {{ formatDate(activeTender.created_at) }}
                        </div>
                    </div>
                    <div class="amk-panel-header-right">
                        <span
                            class="amis-badge"
                            :class="badgeClass(activeTender.status)"
                            >{{ statusLabel(activeTender.status) }}</span
                        >
                        <button
                            class="amk-panel-close"
                            @click="activeTender = null"
                        >
                            &#215;
                        </button>
                    </div>
                </div>

                <div class="amk-panel-body">
                    <div class="amk-section">
                        <div class="amk-section-title">📋 Détails</div>
                        <div class="amk-row">
                            <span>Domaine</span
                            ><strong
                                >{{ activeTender.domainIcon }}
                                {{ activeTender.domain }}</strong
                            >
                        </div>
                        <div class="amk-row">
                            <span>Localisation</span
                            ><strong>{{ activeTender.location }}</strong>
                        </div>
                        <div class="amk-row">
                            <span>Durée</span
                            ><strong>{{ activeTender.duration }}</strong>
                        </div>
                        <div class="amk-row">
                            <span>Budget</span
                            ><strong>{{ activeTender.budget || "—" }}</strong>
                        </div>
                        <div class="amk-row">
                            <span>Date limite</span
                            ><strong>{{ activeTender.deadline }}</strong>
                        </div>
                        <div class="amk-row">
                            <span>Profil</span
                            ><strong>{{
                                profileTypeLabel(activeTender.profile_type)
                            }}</strong>
                        </div>
                        <div class="amk-row">
                            <span>Candidatures</span
                            ><strong class="amk-val-orange">{{
                                activeTender.candidatures
                            }}</strong>
                        </div>
                    </div>
                    <div class="amk-section">
                        <div class="amk-section-title">🏢 Entreprise</div>
                        <div class="amk-row">
                            <span>Nom</span
                            ><strong>{{ activeTender.company }}</strong>
                        </div>
                    </div>
                    <div class="amk-section">
                        <div class="amk-section-title">📝 Description</div>
                        <p class="amk-description">
                            {{ activeTender.description }}
                        </p>
                    </div>
                    <div class="amk-section" v-if="activeTender.requirements">
                        <div class="amk-section-title">🎯 Exigences</div>
                        <p class="amk-description">
                            {{ activeTender.requirements }}
                        </p>
                    </div>
                    <div
                        class="amk-section amk-section-rejected"
                        v-if="
                            activeTender.status === 'rejected' &&
                            activeTender.reject_reason
                        "
                    >
                        <div class="amk-section-title">❌ Motif de rejet</div>
                        <p class="amk-description">
                            {{ activeTender.reject_reason }}
                        </p>
                    </div>

                    <!-- Actions panel -->
                    <div
                        class="amk-action-block amk-action-pending"
                        v-if="activeTender.status === 'pending'"
                    >
                        <div class="amk-action-icon">⏳</div>
                        <div>
                            <div class="amk-action-title">
                                En attente de validation
                            </div>
                            <div class="amk-action-sub">
                                Examinez les informations avant de publier ou
                                rejeter.
                            </div>
                        </div>
                        <div class="amk-action-row">
                            <button
                                class="amk-btn amk-btn-red"
                                @click="openRejectModal"
                                :disabled="!!actionLoading"
                            >
                                ✗ Rejeter
                            </button>
                            <button
                                class="amk-btn amk-btn-green"
                                @click="updateStatus('published')"
                                :disabled="!!actionLoading"
                            >
                                <div
                                    class="amk-spinner"
                                    v-if="actionLoading === 'published'"
                                ></div>
                                <span v-else>✓ Publier</span>
                            </button>
                        </div>
                    </div>
                    <div
                        class="amk-action-block amk-action-published"
                        v-if="activeTender.status === 'published'"
                    >
                        <div class="amk-action-icon">✅</div>
                        <div>
                            <div class="amk-action-title">
                                AO publié et visible
                            </div>
                            <div class="amk-action-sub">
                                Actuellement visible par tous les prestataires
                                et Talents.
                            </div>
                        </div>
                        <button
                            class="amk-btn amk-btn-ghost amk-btn-full"
                            @click="updateStatus('closed')"
                            :disabled="!!actionLoading"
                        >
                            <div
                                class="amk-spinner amk-spinner-dark"
                                v-if="actionLoading === 'closed'"
                            ></div>
                            <span v-else>🔒 Clôturer l'AO</span>
                        </button>
                    </div>
                    <div
                        class="amk-action-block amk-action-rejected"
                        v-if="activeTender.status === 'rejected'"
                    >
                        <div class="amk-action-icon">❌</div>
                        <div>
                            <div class="amk-action-title">AO rejeté</div>
                            <div class="amk-action-sub">
                                Cet appel d'offres a été rejeté et n'est pas
                                visible.
                            </div>
                        </div>
                    </div>
                    <div
                        class="amk-action-block amk-action-closed"
                        v-if="activeTender.status === 'closed'"
                    >
                        <div class="amk-action-icon">🔒</div>
                        <div>
                            <div class="amk-action-title">AO clôturé</div>
                            <div class="amk-action-sub">
                                Cet appel d'offres est clôturé et n'accepte plus
                                de candidatures.
                            </div>
                        </div>
                    </div>
                    <div
                        class="amis-alert-error"
                        v-if="actionError"
                        style="margin-top: 12px"
                    >
                        ⚠️ {{ actionError }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REJET ══════════════ -->
        <div
            class="amk-modal-overlay"
            v-if="rejectModal.visible"
            @click.self="rejectModal.visible = false"
        >
            <div class="amk-modal">
                <div class="amk-modal-header">
                    <h3>✗ Rejeter l'appel d'offres</h3>
                    <button
                        class="amk-modal-close"
                        @click="rejectModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amk-modal-body">
                    <p class="amk-modal-info">
                        L'entreprise sera notifiée du rejet avec votre motif.
                        Elle pourra soumettre un nouvel AO corrigé.
                    </p>
                    <div class="amk-reject-options">
                        <label
                            class="amk-radio-opt"
                            v-for="opt in rejectOptions"
                            :key="opt.value"
                            :class="{
                                selected: rejectModal.reason === opt.value,
                            }"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="rejectModal.reason"
                            />
                            <span>{{ opt.label }}</span>
                        </label>
                    </div>
                    <textarea
                        class="amk-input amk-textarea"
                        v-if="rejectModal.reason === 'other'"
                        v-model="rejectModal.customReason"
                        placeholder="Précisez le motif de rejet…"
                        rows="3"
                    ></textarea>
                    <div class="amis-alert-error" v-if="rejectModal.error">
                        ⚠️ {{ rejectModal.error }}
                    </div>
                </div>
                <div class="amk-modal-footer">
                    <button
                        class="amk-btn amk-btn-ghost"
                        @click="rejectModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amk-btn amk-btn-red"
                        @click="confirmReject"
                        :disabled="!rejectModal.reason || rejectModal.loading"
                    >
                        <div
                            class="amk-spinner"
                            v-if="rejectModal.loading"
                        ></div>
                        <span v-else>✗ Confirmer le rejet</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="amk-toast-container">
            <div
                class="amk-toast"
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
    name: "AdminMarketComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            tenders: [],
            loading: true,
            error: null,
            activeTab: "pending",
            search: "",
            filterDomain: "",
            filterProfile: "",

            // Pagination
            currentPage: 1,
            perPage: 6,

            activeTender: null,
            actionLoading: false,
            actionError: null,

            rejectModal: {
                visible: false,
                reason: "",
                customReason: "",
                loading: false,
                error: "",
                targetTender: null, // pour l'action rapide depuis la carte
            },

            rejectOptions: [
                {
                    value: "incomplete",
                    label: "📋 Informations incomplètes ou insuffisantes",
                },
                {
                    value: "inappropriate",
                    label: "🚫 Contenu inapproprié ou hors charte",
                },
                {
                    value: "domain",
                    label: "🔧 Domaine non pris en charge par Resotravo",
                },
                { value: "duplicate", label: "🔁 Doublon d'un AO déjà publié" },
                {
                    value: "budget",
                    label: "💰 Budget irréaliste ou non précisé",
                },
                { value: "other", label: "✏️ Autre motif" },
            ],

            toasts: [],
            toastId: 0,
            sidebarOpen: false,

            tabs: [
                { key: "pending", label: "⏳ En attente" },
                { key: "published", label: "✅ Publiés" },
                { key: "rejected", label: "❌ Rejetés" },
                { key: "closed", label: "🔒 Clôturés" },
                { key: "all", label: "Tous" },
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
                    .slice(0, 2) ?? "AD"
            );
        },

        counts() {
            return {
                pending: this.tenders.filter((t) => t.status === "pending")
                    .length,
                published: this.tenders.filter((t) => t.status === "published")
                    .length,
                total: this.tenders.length,
            };
        },

        availableDomains() {
            return [
                ...new Set(this.tenders.map((t) => t.domain).filter(Boolean)),
            ].sort();
        },

        statCards() {
            return [
                { icon: "📋", value: this.tenders.length, label: "Total AO" },
                {
                    icon: "⏳",
                    value: this.tenders.filter((t) => t.status === "pending")
                        .length,
                    label: "En attente",
                },
                {
                    icon: "✅",
                    value: this.tenders.filter((t) => t.status === "published")
                        .length,
                    label: "Publiés",
                },
                {
                    icon: "🔒",
                    value: this.tenders.filter((t) => t.status === "closed")
                        .length,
                    label: "Clôturés",
                },
            ];
        },

        filteredTenders() {
            let list = [...this.tenders];
            if (this.activeTab !== "all")
                list = list.filter((t) => t.status === this.activeTab);
            if (this.filterDomain)
                list = list.filter((t) => t.domain === this.filterDomain);
            if (this.filterProfile)
                list = list.filter(
                    (t) => t.profile_type === this.filterProfile,
                );
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (t) =>
                        t.title?.toLowerCase().includes(q) ||
                        t.company?.toLowerCase().includes(q) ||
                        t.domain?.toLowerCase().includes(q) ||
                        t.location?.toLowerCase().includes(q),
                );
            }
            return list;
        },

        totalFiltered() {
            return this.filteredTenders.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },

        pagedTenders() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredTenders.slice(start, start + this.perPage);
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
        // ── Data ─────────────────────────────────────────────────
        async fetchTenders() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(this.routes.tenders_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.tenders = Array.isArray(data) ? data : (data.data ?? []);
            } catch {
                this.error = "Impossible de charger les appels d'offres.";
            } finally {
                this.loading = false;
            }
        },

        setTab(key) {
            this.activeTab = key;
            if (this.tenders.length === 0) this.fetchTenders();
        },

        openTender(t) {
            this.activeTender = { ...t };
            this.actionError = null;
        },

        // ── Actions rapides depuis la carte ───────────────────────
        async quickPublish(t) {
            this.actionLoading = t.id + "_published";
            try {
                await this.patchStatus(t.id, "published", null);
                this.showToast(
                    "✅ AO publié et visible par les prestataires.",
                    "success",
                );
            } catch {
                this.showToast("Erreur lors de la publication.", "error");
            } finally {
                this.actionLoading = false;
            }
        },

        async quickClose(t) {
            this.actionLoading = t.id + "_closed";
            try {
                await this.patchStatus(t.id, "closed", null);
                this.showToast("🔒 AO clôturé avec succès.", "success");
            } catch {
                this.showToast("Erreur lors de la clôture.", "error");
            } finally {
                this.actionLoading = false;
            }
        },

        openRejectModalFor(t) {
            this.rejectModal = {
                visible: true,
                reason: "",
                customReason: "",
                loading: false,
                error: "",
                targetTender: t,
            };
        },

        // ── Actions depuis le panel détail ────────────────────────
        async updateStatus(status, rejectReason = null) {
            this.actionLoading = status;
            this.actionError = null;
            try {
                const updated = await this.patchStatus(
                    this.activeTender.id,
                    status,
                    rejectReason,
                );
                this.activeTender = { ...updated };
                this.showToast(this.actionSuccessMessage(status), "success");
            } catch (e) {
                this.actionError =
                    e.message ?? "Erreur réseau. Veuillez réessayer.";
            } finally {
                this.actionLoading = false;
            }
        },

        async patchStatus(tenderId, status, rejectReason) {
            const csrf = document.querySelector(
                'meta[name="csrf-token"]',
            )?.content;
            const url = this.routes.tenders_status.replace("{id}", tenderId);
            const res = await fetch(url, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
                body: JSON.stringify({ status, reject_reason: rejectReason }),
            });
            const data = await res.json();
            if (!res.ok)
                throw new Error(data.message ?? "Une erreur est survenue.");
            this.updateTenderInList(data.tender);
            return data.tender;
        },

        actionSuccessMessage(status) {
            return (
                {
                    published: "✅ AO publié et visible par les prestataires.",
                    rejected: "❌ AO rejeté. L'entreprise a été notifiée.",
                    closed: "🔒 AO clôturé avec succès.",
                }[status] ?? "Statut mis à jour."
            );
        },

        // ── Rejet modal ───────────────────────────────────────────
        openRejectModal() {
            this.rejectModal = {
                visible: true,
                reason: "",
                customReason: "",
                loading: false,
                error: "",
                targetTender: null,
            };
        },

        async confirmReject() {
            const reason =
                this.rejectModal.reason === "other"
                    ? this.rejectModal.customReason.trim()
                    : (this.rejectOptions.find(
                          (o) => o.value === this.rejectModal.reason,
                      )?.label ?? "");

            if (this.rejectModal.reason === "other" && !reason) {
                this.rejectModal.error = "Veuillez préciser le motif.";
                return;
            }

            this.rejectModal.loading = true;
            this.rejectModal.error = "";

            // Cible = tender du panel ou tender de la carte
            const targetId =
                this.rejectModal.targetTender?.id ?? this.activeTender?.id;

            try {
                const updated = await this.patchStatus(
                    targetId,
                    "rejected",
                    reason,
                );
                if (this.activeTender?.id === targetId)
                    this.activeTender = { ...updated };
                this.rejectModal.visible = false;
                this.showToast(
                    "❌ AO rejeté. L'entreprise a été notifiée.",
                    "success",
                );
            } catch {
                this.rejectModal.error = "Erreur réseau. Veuillez réessayer.";
            } finally {
                this.rejectModal.loading = false;
            }
        },

        // ── Helpers ───────────────────────────────────────────────
        updateTenderInList(updated) {
            const idx = this.tenders.findIndex((t) => t.id === updated.id);
            if (idx !== -1)
                this.tenders.splice(idx, 1, {
                    ...this.tenders[idx],
                    ...updated,
                });
        },

        countByTab(key) {
            if (key === "all") return this.tenders.length;
            return this.tenders.filter((t) => t.status === key).length;
        },

        statusLabel(status) {
            return (
                {
                    pending: "En attente",
                    published: "Publié",
                    rejected: "Rejeté",
                    closed: "Clôturé",
                }[status] ?? status
            );
        },

        badgeClass(status) {
            return (
                {
                    pending: "warning",
                    published: "done",
                    rejected: "cancelled",
                    closed: "neutral",
                }[status] ?? ""
            );
        },

        profileTypeLabel(type) {
            return (
                {
                    prestataire: "👷 Prestataire",
                    talent: "⭐ Talent",
                    both: "👷⭐ Les deux",
                }[type] ?? type
            );
        },

        formatDate(iso) {
            if (!iso) return "—";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
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
                }),
            );
        },
    },

    mounted() {
        this.fetchTenders();
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
    },

    beforeUnmount() {
        window.removeEventListener("ab-sidebar-close", () => {});
    },
};
</script>

<style scoped>
.amk-wrap {
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

/* ── TOPBAR ── */
.amk-topbar {
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
    .amk-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.amk-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.amk-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.amk-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.amk-page-sub {
    font-size: 11px;
    color: var(--gr);
    display: none;
}
@media (min-width: 480px) {
    .amk-page-sub {
        display: block;
    }
}
.amk-avatar {
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
.amk-stat-pills {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.amk-stat-pill {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
}
.amk-stat-pill span {
    font-size: 15px;
    font-weight: 800;
}
.amk-stat-pill.warn {
    background: #fef9c3;
    color: #a16207;
}
.amk-stat-pill.ok {
    background: #dcfce7;
    color: #15803d;
}
.amk-stat-pill.neutral {
    background: var(--grl);
    color: var(--gr);
}
@media (max-width: 520px) {
    .amk-stat-pills {
        display: none;
    }
}

/* ── CONTENT ── */
.amk-content {
    padding: 16px;
    max-width: 1200px;
    margin: 0 auto;
}
@media (min-width: 600px) {
    .amk-content {
        padding: 24px;
    }
}

/* ── STATS ── */
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

/* ── FILTRES ── */
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

/* ── TABS ── */
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
    font-weight: 700;
}
.amis-tab.active .amis-tab-count {
    background: var(--or);
    color: #fff;
}

/* ── GRILLE ── */
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
    height: 240px;
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

/* ── CARTE AO ── */
.ac-card {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 16px;
    padding: 18px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 12px;
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
    gap: 12px;
}
.amk-domain-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--or3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.ac-card-headinfo {
    flex: 1;
    min-width: 0;
}
.ac-card-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.ac-card-specialty {
    font-size: 12px;
    color: var(--or);
    font-weight: 600;
    margin-top: 2px;
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
    font-size: 12px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-cstat-lbl {
    display: block;
    font-size: 10.5px;
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
.at-domain-chip {
    background: var(--or3);
    color: var(--or);
    border-radius: 6px;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 700;
}
.amk-profile-chip {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 99px;
}
.amk-profile-chip.prestataire {
    background: #dbeafe;
    color: #1d4ed8;
}
.amk-profile-chip.talent {
    background: #f3e8ff;
    color: #7c3aed;
}
.amk-profile-chip.both {
    background: #d1fae5;
    color: #065f46;
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

/* ── ERREUR / VIDE ── */
.amis-alert-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 13.5px;
    color: #dc2626;
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

/* ── BADGES ── */
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
.amis-badge.neutral {
    background: var(--grl);
    color: var(--gr);
}

/* ── BOUTONS ── */
.amis-btn,
.amk-btn {
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
.amis-btn:disabled,
.amk-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.amis-btn-ghost,
.amk-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.amis-btn-ghost:hover,
.amk-btn-ghost:hover {
    background: #ddd4c8;
}
.amk-btn-green {
    background: #16a34a;
    color: #fff;
}
.amk-btn-green:hover {
    background: #15803d;
}
.amk-btn-red {
    background: #dc2626;
    color: #fff;
}
.amk-btn-red:hover {
    background: #b91c1c;
}
.amk-btn-full {
    width: 100%;
    justify-content: center;
}
.amis-btn-sm,
.amk-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}
.amis-btn-xs {
    padding: 4px 10px;
    font-size: 11.5px;
    border-radius: 6px;
}

/* ── SPINNER ── */
.amis-spinner,
.amk-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
.amk-spinner-dark {
    border: 2px solid rgba(0, 0, 0, 0.15);
    border-top-color: var(--dk);
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── PANEL DÉTAIL ── */
.amk-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 200;
    display: flex;
    align-items: stretch;
    justify-content: flex-end;
}
.amk-panel {
    width: 100%;
    max-width: 480px;
    background: var(--wh);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    animation: slideIn 0.25s ease;
}
@keyframes slideIn {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}
.amk-panel-header {
    padding: 18px 20px;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amk-panel-header h2 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
    line-height: 1.3;
    margin-bottom: 4px;
}
.amk-panel-sub {
    font-size: 12px;
    color: var(--gr);
}
.amk-panel-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.amk-panel-close {
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
.amk-panel-close:hover {
    background: var(--grl);
}
.amk-panel-body {
    padding: 18px 20px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.amk-section {
    background: #f8f4f0;
    border-radius: 10px;
    padding: 14px;
}
.amk-section-rejected {
    background: #fef2f2;
    border: 1px solid #fca5a5;
}
.amk-section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
}
.amk-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    padding: 5px 0;
    border-bottom: 1px solid var(--grl);
}
.amk-row:last-child {
    border-bottom: none;
}
.amk-row span {
    color: var(--gr);
    flex-shrink: 0;
}
.amk-row strong {
    color: var(--dk);
    font-weight: 600;
    text-align: right;
}
.amk-val-orange {
    color: var(--or) !important;
}
.amk-description {
    font-size: 13px;
    color: var(--dk);
    line-height: 1.7;
    margin: 0;
}
.amk-action-block {
    background: #f8f4f0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.amk-action-pending {
    border: 1.5px solid var(--am);
}
.amk-action-published {
    border: 1.5px solid #86efac;
}
.amk-action-rejected {
    border: 1.5px solid #fca5a5;
    background: #fef2f2;
}
.amk-action-closed {
    border: 1.5px solid var(--grl);
}
.amk-action-icon {
    font-size: 24px;
}
.amk-action-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.amk-action-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}
.amk-action-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── MODAL REJET ── */
.amk-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.amk-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}
.amk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amk-modal-header h3 {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.amk-modal-close {
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
}
.amk-modal-close:hover {
    background: var(--grl);
}
.amk-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.amk-modal-info {
    font-size: 13.5px;
    color: var(--gr);
    line-height: 1.6;
    margin: 0;
}
.amk-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 14px 20px;
    border-top: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amk-reject-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.amk-radio-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    cursor: pointer;
    font-size: 13.5px;
    transition: all 0.18s;
}
.amk-radio-opt input {
    accent-color: var(--or);
    flex-shrink: 0;
}
.amk-radio-opt.selected {
    border-color: var(--or);
    background: var(--or3);
}
.amk-radio-opt:hover {
    border-color: var(--or);
}
.amk-input {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    padding: 10px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    background: #f8f4f0;
    box-sizing: border-box;
}
.amk-input:focus {
    border-color: var(--or);
    background: #fff;
}
.amk-textarea {
    resize: vertical;
    min-height: 80px;
}

/* ── TOASTS ── */
.amk-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 9999;
}
.amk-toast {
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
.amk-toast.success {
    background: #16a34a;
}
.amk-toast.error {
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
