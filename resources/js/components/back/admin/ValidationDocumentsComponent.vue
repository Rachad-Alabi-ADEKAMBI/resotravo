<template>
    <div class="av-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="av-topbar">
            <div class="av-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="av-page-title">Validation des dossiers</div>
                    <div class="av-page-sub">
                        {{ dossiers.length }} dossier(s) au total
                    </div>
                </div>
            </div>
            <div class="av-topbar-right">
                <div class="av-notif-btn">
                    &#x1F514;
                    <span class="av-notif-badge" v-if="pendingCount > 0">{{
                        pendingCount
                    }}</span>
                </div>
                <button
                    class="av-btn av-btn-orange"
                    @click="exportCSV"
                    :disabled="loading.export"
                >
                    <div class="av-spinner" v-if="loading.export"></div>
                    <span v-else>&#8659; Exporter</span>
                </button>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="av-content">
            <!-- Stats -->
            <div class="av-stats-grid">
                <div class="av-stat red">
                    <div class="av-stat-icon">⏳</div>
                    <div class="av-stat-val">{{ pendingCount }}</div>
                    <div class="av-stat-lbl">En attente</div>
                </div>
                <div class="av-stat orange">
                    <div class="av-stat-icon">🔍</div>
                    <div class="av-stat-val">{{ reviewCount }}</div>
                    <div class="av-stat-lbl">En examen</div>
                </div>
                <div class="av-stat green">
                    <div class="av-stat-icon">✅</div>
                    <div class="av-stat-val">{{ approvedCount }}</div>
                    <div class="av-stat-lbl">Approuvés</div>
                </div>
                <div class="av-stat">
                    <div class="av-stat-icon">❌</div>
                    <div class="av-stat-val">{{ rejectedCount }}</div>
                    <div class="av-stat-lbl">Refusés</div>
                </div>
                <div class="av-stat">
                    <div class="av-stat-icon">📂</div>
                    <div class="av-stat-val">{{ incompleteCount }}</div>
                    <div class="av-stat-lbl">Incomplets</div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="av-toolbar">
                <div class="av-search">
                    <span>🔍</span>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Rechercher nom, email, métier, N° dossier..."
                    />
                </div>
                <div class="av-filter-tabs">
                    <button
                        v-for="f in filters"
                        :key="f.key"
                        class="av-ftab"
                        :class="{ active: activeFilter === f.key }"
                        @click="activeFilter = f.key"
                    >
                        {{ f.label }}
                        <span class="av-ftab-count">{{
                            countByFilter(f.key)
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Loading état initial -->
            <div class="av-loading-state" v-if="loading.init">
                <div class="av-spinner av-spinner-lg"></div>
                <p>Chargement des dossiers...</p>
            </div>

            <!-- Table wrap -->
            <div class="av-table-wrap" v-else>
                <div class="av-table-header">
                    <h3>
                        Dossiers
                        <span class="av-t-count"
                            >({{ filteredDossiers.length }} résultat{{
                                filteredDossiers.length > 1 ? "s" : ""
                            }})</span
                        >
                    </h3>
                    <button
                        class="av-btn av-btn-ghost av-btn-sm"
                        @click="bulkApprove"
                        :disabled="selected.length === 0"
                        :style="{ opacity: selected.length ? 1 : 0.4 }"
                    >
                        ✓ Approuver sélection ({{ selected.length }})
                    </button>
                </div>

                <!-- Empty -->
                <div class="av-empty" v-if="filteredDossiers.length === 0">
                    <div class="av-empty-icon">📭</div>
                    <div class="av-empty-title">Aucun dossier trouvé</div>
                    <div class="av-empty-sub">
                        Modifiez vos filtres ou votre recherche
                    </div>
                </div>

                <!-- Desktop table -->
                <div class="av-table-scroll" v-else>
                    <table class="av-table">
                        <thead>
                            <tr>
                                <th style="width: 40px">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="
                                            selected.length ===
                                                filteredDossiers.length &&
                                            filteredDossiers.length > 0
                                        "
                                        style="accent-color: var(--or)"
                                    />
                                </th>
                                <th>Utilisateur</th>
                                <th>Rôle / Métier</th>
                                <th>Documents</th>
                                <th>Soumis le</th>
                                <th>Statut compte</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in filteredDossiers" :key="d.id">
                                <td @click.stop>
                                    <input
                                        type="checkbox"
                                        :value="d.id"
                                        v-model="selected"
                                        style="accent-color: var(--or)"
                                        @click.stop
                                    />
                                </td>
                                <td>
                                    <div class="av-user-cell">
                                        <div
                                            class="av-user-av"
                                            :style="{ background: d.color }"
                                        >
                                            {{ d.initials }}
                                        </div>
                                        <div>
                                            <div class="av-user-name">
                                                {{ d.name }}
                                            </div>
                                            <div class="av-user-meta">
                                                {{ d.email }}
                                            </div>
                                            <div class="av-user-meta">
                                                N° {{ d.ref }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="av-role-badge" :class="d.role">
                                        {{ roleLabel(d.role) }}
                                    </div>
                                    <div
                                        class="av-user-meta"
                                        v-if="d.specialty"
                                    >
                                        {{ d.specialty }}
                                    </div>
                                    <div class="av-user-meta" v-if="d.zone">
                                        📍 {{ d.zone }}
                                    </div>
                                </td>
                                <td>
                                    <div class="av-doc-dots">
                                        <div
                                            v-for="doc in d.documents"
                                            :key="doc.type"
                                            class="av-doc-dot"
                                            :class="doc.status"
                                            :title="
                                                doc.label +
                                                ' : ' +
                                                docStatusLabel(doc.status)
                                            "
                                        ></div>
                                    </div>
                                    <div class="av-doc-count">
                                        {{
                                            d.documents.filter(
                                                (x) => x.status === "approved",
                                            ).length
                                        }}/{{ d.documents.length }} validés
                                    </div>
                                </td>
                                <td class="av-date">{{ d.created_at }}</td>
                                <td>
                                    <div class="av-status-cell">
                                        <span
                                            class="av-badge"
                                            :class="d.user_status"
                                            >{{
                                                userStatusLabel(d.user_status)
                                            }}</span
                                        >
                                        <span
                                            class="av-certified-badge"
                                            v-if="d.user_status === 'approved'"
                                            >🏅</span
                                        >
                                    </div>
                                </td>
                                <td>
                                    <button
                                        class="av-btn av-btn-blue av-btn-sm"
                                        @click.stop="openDossier(d)"
                                    >
                                        Examiner
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cards mobile -->
                <div class="av-cards-mobile" v-if="filteredDossiers.length > 0">
                    <div
                        class="av-dossier-card"
                        v-for="d in filteredDossiers"
                        :key="'m' + d.id"
                    >
                        <div class="av-dossier-card-top">
                            <div class="av-user-cell">
                                <div
                                    class="av-user-av"
                                    :style="{ background: d.color }"
                                >
                                    {{ d.initials }}
                                </div>
                                <div>
                                    <div class="av-user-name">{{ d.name }}</div>
                                    <div class="av-user-meta">
                                        {{ d.specialty || roleLabel(d.role) }}
                                    </div>
                                </div>
                            </div>
                            <span class="av-badge" :class="d.user_status">{{
                                userStatusLabel(d.user_status)
                            }}</span>
                        </div>
                        <div class="av-dossier-card-meta">
                            <span>N° {{ d.ref }}</span>
                            <span>{{ d.created_at }}</span>
                            <span
                                >{{
                                    d.documents.filter(
                                        (x) => x.status === "approved",
                                    ).length
                                }}/{{ d.documents.length }} docs</span
                            >
                        </div>
                        <button
                            class="av-btn av-btn-blue"
                            style="width: 100%; margin-top: 10px"
                            @click="openDossier(d)"
                        >
                            Examiner le dossier
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /content -->

        <!-- ══════════════ MODAL DOSSIER ══════════════ -->
        <div
            class="av-modal-overlay"
            v-if="activeDossier"
            @click.self="closeDossier"
        >
            <div class="av-modal">
                <div class="av-modal-header">
                    <div>
                        <h3>{{ activeDossier.name }}</h3>
                        <div class="av-modal-sub">
                            {{ roleLabel(activeDossier.role) }}
                            <span v-if="activeDossier.specialty">
                                · {{ activeDossier.specialty }}</span
                            >
                            · N° {{ activeDossier.ref }}
                        </div>
                    </div>
                    <button class="av-modal-close" @click="closeDossier">
                        &#215;
                    </button>
                </div>

                <div class="av-modal-body">
                    <!-- Infos -->
                    <div class="av-detail-grid">
                        <div class="av-detail-section">
                            <h4>Identité</h4>
                            <div class="av-detail-row">
                                <span>Nom</span
                                ><strong>{{ activeDossier.name }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Email</span
                                ><strong>{{ activeDossier.email }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Rôle</span
                                ><strong>{{
                                    roleLabel(activeDossier.role)
                                }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Statut</span>
                                <span
                                    class="av-badge"
                                    :class="activeDossier.user_status"
                                    >{{
                                        userStatusLabel(
                                            activeDossier.user_status,
                                        )
                                    }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="av-detail-section"
                            v-if="activeDossier.role === 'contractor'"
                        >
                            <h4>Profil prestataire</h4>
                            <div class="av-detail-row">
                                <span>Métier</span
                                ><strong>{{ activeDossier.specialty }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Zone</span
                                ><strong>{{ activeDossier.zone }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Expérience</span
                                ><strong>{{ activeDossier.experience }}</strong>
                            </div>
                            <div class="av-detail-row">
                                <span>Inscrit le</span
                                ><strong>{{ activeDossier.created_at }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="av-docs-section">
                        <div class="av-docs-title">
                            Documents soumis
                            <span class="av-docs-count">
                                {{
                                    activeDossier.documents.filter(
                                        (d) => d.status === "approved",
                                    ).length
                                }}/{{ activeDossier.documents.length }}
                            </span>
                        </div>
                        <div class="av-doc-list">
                            <div
                                class="av-doc-item"
                                v-for="doc in activeDossier.documents"
                                :key="doc.type"
                                :class="doc.status"
                            >
                                <div class="av-doc-icon">{{ doc.icon }}</div>
                                <div class="av-doc-info">
                                    <div class="av-doc-name">
                                        {{ doc.label }}
                                    </div>
                                    <div
                                        class="av-doc-file"
                                        v-if="doc.filename"
                                    >
                                        {{ doc.filename }}
                                    </div>
                                    <div
                                        class="av-doc-status approved"
                                        v-if="doc.status === 'approved'"
                                    >
                                        ✅ Validé
                                    </div>
                                    <div
                                        class="av-doc-status rejected"
                                        v-if="doc.status === 'rejected'"
                                    >
                                        ❌ Refusé —
                                        {{
                                            doc.reject_reason || "Non conforme"
                                        }}
                                    </div>
                                    <div
                                        class="av-doc-status pending"
                                        v-if="doc.status === 'pending'"
                                    >
                                        ⏳ En attente d'examen
                                    </div>
                                    <div
                                        class="av-doc-status missing"
                                        v-if="doc.status === 'missing'"
                                    >
                                        📎 Non soumis
                                    </div>
                                </div>
                                <div
                                    class="av-doc-actions"
                                    v-if="doc.status !== 'missing'"
                                >
                                    <button
                                        class="av-doc-btn download"
                                        v-if="doc.id"
                                        @click.stop="previewDoc(doc)"
                                        :disabled="docLoading[doc.id]"
                                    >
                                        <div
                                            class="av-mini-spinner"
                                            v-if="docLoading[doc.id]"
                                        ></div>
                                        <span v-else>&#128065; Voir</span>
                                    </button>
                                    <button
                                        class="av-doc-btn validate"
                                        v-if="
                                            doc.status !== 'approved' && doc.id
                                        "
                                        @click.stop="approveDoc(doc)"
                                        :disabled="docLoading[doc.id]"
                                    >
                                        ✓ Valider
                                    </button>
                                    <button
                                        class="av-doc-btn reject"
                                        v-if="
                                            doc.status !== 'rejected' && doc.id
                                        "
                                        @click.stop="openRejectDoc(doc)"
                                    >
                                        ✕ Refuser
                                    </button>
                                </div>
                                <div class="av-doc-missing-lbl" v-else>
                                    Non soumis
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes admin -->
                    <div>
                        <label class="av-notes-label"
                            >Notes internes (non visibles par
                            l'utilisateur)</label
                        >
                        <textarea
                            class="av-notes-textarea"
                            v-model="activeDossier.notes"
                            placeholder="Observations, motif de refus, suivi..."
                        ></textarea>
                    </div>
                </div>

                <div class="av-modal-footer">
                    <button class="av-btn av-btn-ghost" @click="closeDossier">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL PREVIEW DOCUMENT ══════════════ -->
        <div
            class="av-modal-overlay av-preview-overlay"
            v-if="previewModal.visible"
            @click.self="closePreview"
        >
            <div class="av-modal av-modal-preview">
                <div class="av-modal-header">
                    <div>
                        <h3>{{ previewModal.doc?.label }}</h3>
                        <div class="av-modal-sub">
                            {{ previewModal.doc?.filename }}
                        </div>
                    </div>
                    <div class="av-preview-header-actions">
                        <button
                            class="av-btn av-btn-ghost av-btn-sm"
                            @click="downloadFromPreview"
                        >
                            &#8659; Télécharger
                        </button>
                        <button class="av-modal-close" @click="closePreview">
                            &#215;
                        </button>
                    </div>
                </div>
                <div class="av-preview-body">
                    <!-- Chargement -->
                    <div class="av-preview-loading" v-if="previewModal.loading">
                        <div class="av-spinner av-spinner-lg"></div>
                        <p>Chargement du document...</p>
                    </div>
                    <!-- Erreur -->
                    <div
                        class="av-preview-error"
                        v-else-if="previewModal.error"
                    >
                        <div class="av-preview-error-icon">⚠️</div>
                        <p>{{ previewModal.error }}</p>
                        <button
                            class="av-btn av-btn-orange"
                            @click="downloadFromPreview"
                        >
                            &#8659; Télécharger à la place
                        </button>
                    </div>
                    <!-- PDF viewer -->
                    <iframe
                        v-else-if="previewModal.isPdf && previewModal.url"
                        :src="previewModal.url"
                        class="av-preview-iframe"
                        frameborder="0"
                    ></iframe>
                    <!-- Image viewer -->
                    <div
                        class="av-preview-img-wrap"
                        v-else-if="!previewModal.isPdf && previewModal.url"
                    >
                        <img
                            :src="previewModal.url"
                            class="av-preview-img"
                            alt="Document"
                        />
                    </div>
                </div>
                <!-- Actions rapides depuis la preview -->
                <div
                    class="av-modal-footer"
                    v-if="
                        previewModal.doc &&
                        !previewModal.loading &&
                        !previewModal.error
                    "
                >
                    <button class="av-btn av-btn-ghost" @click="closePreview">
                        Fermer
                    </button>
                    <button
                        class="av-btn av-btn-red"
                        v-if="previewModal.doc.status !== 'rejected'"
                        @click="
                            closePreview();
                            openRejectDoc(previewModal.doc);
                        "
                    >
                        ✕ Refuser
                    </button>
                    <button
                        class="av-btn av-btn-green"
                        v-if="previewModal.doc.status !== 'approved'"
                        @click="approveDocFromPreview"
                    >
                        <div
                            class="av-spinner"
                            v-if="docLoading[previewModal.doc.id]"
                        ></div>
                        <span v-else>✓ Valider</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REFUS DOCUMENT ══════════════ -->
        <div
            class="av-modal-overlay"
            v-if="rejectDocModal.visible"
            @click.self="rejectDocModal.visible = false"
        >
            <div class="av-modal av-modal-sm">
                <div class="av-modal-header">
                    <h3>Refuser : {{ rejectDocModal.doc?.label }}</h3>
                    <button
                        class="av-modal-close"
                        @click="rejectDocModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="av-modal-body">
                    <label class="av-notes-label"
                        >Motif de refus
                        <span style="color: #6366f1">*</span></label
                    >
                    <textarea
                        class="av-notes-textarea"
                        v-model="rejectDocModal.reason"
                        placeholder="Ex: Document illisible, expiré, non conforme..."
                    ></textarea>
                    <div class="av-err" v-if="rejectDocModal.error">
                        {{ rejectDocModal.error }}
                    </div>
                </div>
                <div class="av-modal-footer">
                    <button
                        class="av-btn av-btn-ghost"
                        @click="rejectDocModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="av-btn av-btn-red"
                        @click="confirmRejectDoc"
                        :disabled="loading.rejectDoc"
                    >
                        <div class="av-spinner" v-if="loading.rejectDoc"></div>
                        <span v-else>Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TOASTS -->
        <div class="av-toast-container">
            <div
                class="av-toast"
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
    name: "ValidationDocumentsComponent",

    data() {
        return {
            search: "",
            activeFilter: "all",
            selected: [],
            activeDossier: null,
            sidebarOpen: false,
            toasts: [],
            toastId: 0,

            loading: {
                init: true,
                action: false,
                export: false,
                rejectDoc: false,
            },

            rejectDocModal: {
                visible: false,
                doc: null,
                reason: "",
                error: "",
            },

            filters: [
                { key: "all", label: "Tous" },
                { key: "pending", label: "En attente" },
                { key: "review", label: "En examen" },
                { key: "approved", label: "Approuvés" },
                { key: "rejected", label: "Refusés" },
                { key: "incomplete", label: "Incomplets" },
            ],

            dossiers: [],
            docLoading: {}, // { [docId]: boolean }

            previewModal: {
                visible: false,
                doc: null,
                url: null,
                isPdf: false,
                loading: false,
                error: "",
            },
        };
    },

    computed: {
        pendingCount() {
            return this.dossiers.filter((d) => d.user_status === "pending")
                .length;
        },
        reviewCount() {
            return this.dossiers.filter((d) => d.user_status === "review")
                .length;
        },
        approvedCount() {
            return this.dossiers.filter((d) => d.user_status === "approved")
                .length;
        },
        rejectedCount() {
            return this.dossiers.filter((d) => d.user_status === "rejected")
                .length;
        },
        incompleteCount() {
            return this.dossiers.filter((d) => d.user_status === "incomplete")
                .length;
        },

        filteredDossiers() {
            let list = this.dossiers;

            if (this.activeFilter !== "all") {
                list = list.filter((d) => d.user_status === this.activeFilter);
            }

            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (d) =>
                        d.name.toLowerCase().includes(q) ||
                        d.email.toLowerCase().includes(q) ||
                        d.ref.toLowerCase().includes(q) ||
                        (d.specialty ?? "").toLowerCase().includes(q),
                );
            }

            return list;
        },
    },

    methods: {
        /* ── Labels ── */
        roleLabel(role) {
            return (
                {
                    contractor: "Prestataire",
                    client: "Client",
                    talent: "Talent",
                    admin: "Admin",
                }[role] ?? role
            );
        },

        userStatusLabel(s) {
            return (
                {
                    pending: "En attente",
                    review: "En examen",
                    approved: "Certifié",
                    rejected: "Refusé",
                    incomplete: "Incomplet",
                }[s] ?? s
            );
        },

        docStatusLabel(s) {
            return (
                {
                    approved: "Validé",
                    pending: "En attente",
                    rejected: "Refusé",
                    missing: "Manquant",
                }[s] ?? s
            );
        },

        countByFilter(key) {
            if (key === "all") return this.dossiers.length;
            return this.dossiers.filter((d) => d.user_status === key).length;
        },

        /* ── Charger les dossiers depuis l'API ── */
        async loadDossiers() {
            this.loading.init = true;

            const route = "/admin/documents/dossiers";

            console.log(
                "[ValidationDocuments] ── REQUÊTE ─────────────────────────────",
            );
            console.log("[ValidationDocuments] Route   :", route);
            console.log("[ValidationDocuments] Méthode :", "GET");
            console.log("[ValidationDocuments] Headers :", {
                "X-CSRF-TOKEN":
                    window.axios.defaults.headers.common["X-CSRF-TOKEN"] ??
                    "MANQUANT",
                Accept:
                    window.axios.defaults.headers.common["Accept"] ??
                    "non défini",
            });
            console.log(
                "[ValidationDocuments] ────────────────────────────────────────",
            );

            try {
                const response = await window.axios.get(route);

                console.log(
                    "[ValidationDocuments] ── RÉPONSE ────────────────────────────",
                );
                console.log(
                    "[ValidationDocuments] Status    :",
                    response.status,
                );
                console.log(
                    "[ValidationDocuments] Headers   :",
                    response.headers,
                );
                console.log(
                    "[ValidationDocuments] Data type :",
                    typeof response.data,
                    Array.isArray(response.data)
                        ? `(array, ${response.data.length} items)`
                        : "",
                );
                console.log("[ValidationDocuments] Data      :", response.data);
                console.log(
                    "[ValidationDocuments] ────────────────────────────────────────",
                );

                if (!Array.isArray(response.data)) {
                    console.warn(
                        "[ValidationDocuments] ⚠️ La réponse n'est pas un tableau — vérifiez le controller",
                    );
                    console.warn(
                        "[ValidationDocuments] Type reçu :",
                        typeof response.data,
                    );
                    console.warn(
                        "[ValidationDocuments] Contenu   :",
                        response.data,
                    );
                    this.dossiers = this.fakeDossiers();
                    return;
                }

                if (response.data.length === 0) {
                    console.warn(
                        "[ValidationDocuments] ⚠️ Aucun dossier retourné par l'API",
                    );
                }

                this.dossiers = response.data.map((d, i) => {
                    console.log(`[ValidationDocuments] mapDossier[${i}] —`, d);
                    return this.mapDossier(d);
                });

                console.log(
                    "[ValidationDocuments] Dossiers mappés :",
                    this.dossiers.length,
                    this.dossiers,
                );
            } catch (err) {
                console.error(
                    "[ValidationDocuments] ── ERREUR ────────────────────────────",
                );
                console.error("[ValidationDocuments] Message :", err.message);
                console.error(
                    "[ValidationDocuments] Status  :",
                    err?.response?.status,
                );
                console.error(
                    "[ValidationDocuments] Data    :",
                    err?.response?.data,
                );
                console.error(
                    "[ValidationDocuments] URL     :",
                    err?.config?.url,
                );
                console.error("[ValidationDocuments] Objet   :", err);
                console.error(
                    "[ValidationDocuments] ────────────────────────────────────────",
                );
                console.warn(
                    "[ValidationDocuments] Chargement des données fictives (fallback)",
                );
                this.dossiers = this.fakeDossiers();
            } finally {
                this.loading.init = false;
            }
        },

        /* ── Mapper un dossier API → format local ── */
        mapDossier(d) {
            const name =
                d.name ?? `${d.first_name ?? ""} ${d.last_name ?? ""}`.trim();
            return {
                id: d.id,
                ref: `RT-${String(d.id).padStart(8, "0")}`,
                name,
                initials: name
                    .split(" ")
                    .map((w) => w[0])
                    .join("")
                    .slice(0, 2)
                    .toUpperCase(),
                color: this.colorForRole(d.role),
                email: d.email,
                role: d.role,
                specialty: d.contractor?.specialty ?? null,
                zone: d.contractor?.intervention_zone ?? null,
                experience: d.contractor?.experience_years
                    ? `${d.contractor.experience_years} ans`
                    : null,
                user_status: d.status,
                created_at: new Date(d.created_at).toLocaleDateString("fr-FR"),
                notes: "",
                documents: (d.documents ?? []).map((doc) => ({
                    id: doc.id,
                    type: doc.type,
                    label: doc.label ?? doc.type,
                    icon: doc.icon ?? "📎",
                    filename: doc.filename,
                    status: doc.status,
                    reject_reason: doc.reject_reason,
                })),
            };
        },

        colorForRole(role) {
            return (
                {
                    contractor: "#F97316",
                    client: "#3b82f6",
                    talent: "#8b5cf6",
                    admin: "#6366f1",
                }[role] ?? "#7C6A5A"
            );
        },

        /* ── Ouvrir / fermer modal dossier ── */
        openDossier(d) {
            this.activeDossier = JSON.parse(JSON.stringify(d));
        },
        closeDossier() {
            this.activeDossier = null;
        },

        /* ── Approuver un document ── */
        async approveDoc(doc) {
            this.docLoading[doc.id] = true;

            console.log(
                `[ValidationDocuments] approveDoc — POST /documents/${doc.id}/approve`,
            );

            try {
                const response = await window.axios.post(
                    `/documents/${doc.id}/approve`,
                );
                console.log(
                    "[ValidationDocuments] approveDoc — réponse:",
                    response.data,
                );

                doc.status = "approved";
                this.syncDocToList(doc);
                this.showToast(`Document "${doc.label}" validé.`, "success");

                // Vérifier auto-certification
                this.checkAutoCertification();
            } catch (err) {
                console.error(
                    "[ValidationDocuments] approveDoc — erreur:",
                    err,
                );
                this.showToast("Erreur lors de la validation.", "error");
            } finally {
                this.docLoading[doc.id] = false;
            }
        },

        /* ── Ouvrir modal refus document ── */
        openRejectDoc(doc) {
            this.rejectDocModal = { visible: true, doc, reason: "", error: "" };
        },

        /* ── Confirmer refus document ── */
        async confirmRejectDoc() {
            if (!this.rejectDocModal.reason.trim()) {
                this.rejectDocModal.error =
                    "Le motif de refus est obligatoire.";
                return;
            }

            this.loading.rejectDoc = true;
            const doc = this.rejectDocModal.doc;

            console.log(
                `[ValidationDocuments] rejectDoc — POST /documents/${doc.id}/reject`,
                { reason: this.rejectDocModal.reason },
            );

            try {
                const response = await window.axios.post(
                    `/documents/${doc.id}/reject`,
                    {
                        reason: this.rejectDocModal.reason,
                    },
                );
                console.log(
                    "[ValidationDocuments] rejectDoc — réponse:",
                    response.data,
                );

                doc.status = "rejected";
                doc.reject_reason = this.rejectDocModal.reason;
                this.syncDocToList(doc);

                this.rejectDocModal.visible = false;
                this.showToast(`Document "${doc.label}" refusé.`, "error");
            } catch (err) {
                console.error("[ValidationDocuments] rejectDoc — erreur:", err);
                this.rejectDocModal.error =
                    err?.response?.data?.message ?? "Erreur serveur.";
            } finally {
                this.loading.rejectDoc = false;
            }
        },

        /* ── Prévisualiser un document dans la modal ── */
        async previewDoc(doc) {
            // Réinitialiser le modal
            this.previewModal = {
                visible: true,
                doc,
                url: null,
                isPdf: false,
                loading: true,
                error: "",
            };

            console.log(
                `[ValidationDocuments] previewDoc — GET /documents/${doc.id}/download`,
            );

            try {
                const response = await window.axios.get(
                    `/documents/${doc.id}/download`,
                    {
                        responseType: "blob",
                    },
                );

                const blob = new Blob([response.data], {
                    type: response.headers["content-type"],
                });
                const url = URL.createObjectURL(blob);
                const isPdf =
                    blob.type === "application/pdf" ||
                    (doc.filename ?? "").toLowerCase().endsWith(".pdf");

                this.previewModal.url = url;
                this.previewModal.isPdf = isPdf;
                this.previewModal.loading = false;

                console.log(
                    "[ValidationDocuments] previewDoc — blob type:",
                    blob.type,
                    "| isPdf:",
                    isPdf,
                );
            } catch (err) {
                console.error(
                    "[ValidationDocuments] previewDoc — erreur:",
                    err?.response?.status,
                    err,
                );
                this.previewModal.loading = false;
                this.previewModal.error =
                    "Impossible de charger le document. Essayez de le télécharger.";
            }
        },

        /* ── Fermer le preview et libérer la mémoire ── */
        closePreview() {
            if (this.previewModal.url) {
                URL.revokeObjectURL(this.previewModal.url);
            }
            this.previewModal.visible = false;
            this.previewModal.url = null;
        },

        /* ── Télécharger depuis la preview ── */
        async downloadFromPreview() {
            const doc = this.previewModal.doc;
            if (!doc) return;

            if (this.previewModal.url) {
                const a = document.createElement("a");
                a.href = this.previewModal.url;
                a.download = doc.filename || `document_${doc.id}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                await this.downloadDoc(doc);
            }
        },

        /* ── Valider depuis la preview ── */
        async approveDocFromPreview() {
            const doc = this.previewModal.doc;
            if (!doc) return;
            await this.approveDoc(doc);
            // Mettre à jour le statut dans le modal preview
            this.previewModal.doc = { ...doc, status: "approved" };
        },

        /* ── Télécharger un document (sans preview) ── */
        async downloadDoc(doc) {
            this.docLoading[doc.id] = true;
            console.log(
                `[ValidationDocuments] downloadDoc — GET /documents/${doc.id}/download`,
            );

            try {
                const response = await window.axios.get(
                    `/documents/${doc.id}/download`,
                    {
                        responseType: "blob",
                    },
                );
                const url = URL.createObjectURL(new Blob([response.data]));
                const a = document.createElement("a");
                a.href = url;
                a.download = doc.filename || `document_${doc.id}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (err) {
                console.error(
                    "[ValidationDocuments] downloadDoc — erreur:",
                    err,
                );
                this.showToast("Erreur lors du téléchargement.", "error");
            } finally {
                this.docLoading[doc.id] = false;
            }
        },

        /* ── Approuver le dossier entier ── */
        async approveDossier(d) {
            this.loading.action = true;
            console.log(
                `[ValidationDocuments] approveDossier — user_id: ${d.id}`,
            );

            try {
                // 1. Approuver tous les docs pending
                const pendingDocs = d.documents.filter(
                    (doc) => doc.status === "pending" && doc.id,
                );
                for (const doc of pendingDocs) {
                    await window.axios.post(`/documents/${doc.id}/approve`);
                    doc.status = "approved";
                }

                // 2. Certifier l'utilisateur
                const response = await window.axios.patch(
                    `/admin/users/${d.id}/status`,
                    {
                        status: "approved",
                    },
                );
                console.log(
                    "[ValidationDocuments] approveDossier — réponse:",
                    response.data,
                );

                // 3. Mettre à jour localement
                d.user_status = "approved";
                this.updateDossierInList(d);

                this.showToast(`${d.name} — profil certifié ! 🏅`, "success");
                this.closeDossier();
            } catch (err) {
                console.error(
                    "[ValidationDocuments] approveDossier — erreur:",
                    err,
                );
                this.showToast("Erreur lors de l'approbation.", "error");
            } finally {
                this.loading.action = false;
            }
        },

        /* ── Refuser le dossier ── */
        async rejectDossier(d) {
            this.loading.action = true;
            console.log(
                `[ValidationDocuments] rejectDossier — user_id: ${d.id}`,
            );

            try {
                const response = await window.axios.patch(
                    `/admin/users/${d.id}/status`,
                    {
                        status: "rejected",
                    },
                );
                console.log(
                    "[ValidationDocuments] rejectDossier — réponse:",
                    response.data,
                );

                d.user_status = "rejected";
                this.updateDossierInList(d);

                this.showToast(`Dossier de ${d.name} refusé.`, "error");
                this.closeDossier();
            } catch (err) {
                console.error(
                    "[ValidationDocuments] rejectDossier — erreur:",
                    err,
                );
                this.showToast("Erreur lors du refus.", "error");
            } finally {
                this.loading.action = false;
            }
        },

        /* ── Demander complément ── */
        async requestComplement(d) {
            this.loading.action = true;

            try {
                await window.axios.patch(`/admin/users/${d.id}/status`, {
                    status: "incomplete",
                });

                d.user_status = "incomplete";
                this.updateDossierInList(d);

                this.showToast(`Complément demandé à ${d.name}.`, "");
                this.closeDossier();
            } catch (err) {
                this.showToast("Erreur lors de la mise à jour.", "error");
            } finally {
                this.loading.action = false;
            }
        },

        /* ── Auto-certification : si tous les docs du dossier ouvert sont approved ── */
        checkAutoCertification() {
            if (!this.activeDossier) return;

            const allApproved = this.activeDossier.documents
                .filter((d) => d.status !== "missing")
                .every((d) => d.status === "approved");

            if (allApproved && this.activeDossier.user_status !== "approved") {
                this.activeDossier.user_status = "approved";
                this.updateDossierInList(this.activeDossier);

                // Appel API pour mettre à jour le statut user
                window.axios
                    .patch(`/admin/users/${this.activeDossier.id}/status`, {
                        status: "approved",
                    })
                    .then((r) => {
                        console.log(
                            "[ValidationDocuments] auto-certification ✅ —",
                            this.activeDossier.name,
                        );
                        this.showToast(
                            `🏅 ${this.activeDossier.name} — profil certifié automatiquement !`,
                            "success",
                        );
                    })
                    .catch((err) =>
                        console.error(
                            "[ValidationDocuments] auto-certification — erreur:",
                            err,
                        ),
                    );
            }
        },

        /* ── Approbation en masse ── */
        async bulkApprove() {
            if (!this.selected.length) return;

            for (const id of this.selected) {
                const d = this.dossiers.find((x) => x.id === id);
                if (d) {
                    await window.axios
                        .patch(`/admin/users/${id}/status`, {
                            status: "approved",
                        })
                        .catch((err) =>
                            console.error(
                                "[ValidationDocuments] bulkApprove erreur id:",
                                id,
                                err,
                            ),
                        );
                    d.user_status = "approved";
                }
            }

            this.showToast(
                `${this.selected.length} dossier(s) approuvé(s).`,
                "success",
            );
            this.selected = [];
        },

        /* ── Export CSV ── */
        exportCSV() {
            this.loading.export = true;
            console.log("[ValidationDocuments] exportCSV — génération CSV");

            try {
                const headers = [
                    "N° Dossier",
                    "Nom",
                    "Email",
                    "Rôle",
                    "Métier",
                    "Zone",
                    "Statut",
                    "Inscrit le",
                    "Docs validés",
                ];
                const rows = this.dossiers.map((d) => [
                    d.ref,
                    d.name,
                    d.email,
                    this.roleLabel(d.role),
                    d.specialty ?? "",
                    d.zone ?? "",
                    this.userStatusLabel(d.user_status),
                    d.created_at,
                    `${d.documents.filter((x) => x.status === "approved").length}/${d.documents.length}`,
                ]);

                const csv = [headers, ...rows]
                    .map((r) =>
                        r
                            .map((v) => `"${String(v).replace(/"/g, '""')}"`)
                            .join(","),
                    )
                    .join("\n");

                const blob = new Blob(["\uFEFF" + csv], {
                    type: "text/csv;charset=utf-8",
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `resotravo_dossiers_${new Date().toISOString().slice(0, 10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                this.showToast("Export CSV généré.", "success");
            } finally {
                this.loading.export = false;
            }
        },

        /* ── Sélection tout ── */
        toggleSelectAll(e) {
            this.selected = e.target.checked
                ? this.filteredDossiers.map((d) => d.id)
                : [];
        },

        /* ── Sync doc dans la liste principale ── */
        syncDocToList(doc) {
            const dossier = this.dossiers.find(
                (d) => d.id === this.activeDossier?.id,
            );
            if (!dossier) return;
            const d = dossier.documents.find((x) => x.id === doc.id);
            if (d) {
                d.status = doc.status;
                d.reject_reason = doc.reject_reason;
            }
        },

        updateDossierInList(updated) {
            const idx = this.dossiers.findIndex((d) => d.id === updated.id);
            if (idx !== -1)
                this.dossiers[idx].user_status = updated.user_status;
        },

        /* ── Sidebar ── */
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },

        /* ── Toast ── */
        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 4000);
        },

        /* ── Données fictives fallback ── */
        fakeDossiers() {
            return [
                {
                    id: 1,
                    ref: "RT-00000001",
                    name: "Kokou Mensah",
                    initials: "KM",
                    color: "#F97316",
                    email: "kokou.mensah@email.com",
                    role: "contractor",
                    specialty: "Plombier",
                    zone: "Cotonou · Akpakpa",
                    experience: "8 ans",
                    user_status: "pending",
                    created_at: "12/01/2025",
                    notes: "",
                    documents: [
                        {
                            id: 1,
                            type: "cip",
                            label: "CIP / CNI",
                            icon: "🪪",
                            filename: "CIN_Mensah.pdf",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 2,
                            type: "photo",
                            label: "Photo profil",
                            icon: "📷",
                            filename: "photo_pro.jpg",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 3,
                            type: "attestation",
                            label: "Attestation résidence",
                            icon: "📄",
                            filename: "Attest_exp.pdf",
                            status: "pending",
                            reject_reason: "",
                        },
                        {
                            id: 4,
                            type: "casier",
                            label: "Casier judiciaire",
                            icon: "⚖️",
                            filename: "casier.pdf",
                            status: "pending",
                            reject_reason: "",
                        },
                        {
                            id: 5,
                            type: "diplome",
                            label: "Diplôme",
                            icon: "🎓",
                            filename: "",
                            status: "missing",
                            reject_reason: "",
                        },
                    ],
                },
                {
                    id: 2,
                    ref: "RT-00000002",
                    name: "Aminata Diallo",
                    initials: "AD",
                    color: "#8b5cf6",
                    email: "aminata.diallo@email.com",
                    role: "contractor",
                    specialty: "Électricienne",
                    zone: "Porto-Novo",
                    experience: "5 ans",
                    user_status: "approved",
                    created_at: "10/01/2025",
                    notes: "",
                    documents: [
                        {
                            id: 6,
                            type: "cip",
                            label: "CIP / CNI",
                            icon: "🪪",
                            filename: "CIN_Diallo.pdf",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 7,
                            type: "photo",
                            label: "Photo profil",
                            icon: "📷",
                            filename: "photo_diallo.jpg",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 8,
                            type: "attestation",
                            label: "Attestation résidence",
                            icon: "📄",
                            filename: "Attest_diallo.pdf",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 9,
                            type: "casier",
                            label: "Casier judiciaire",
                            icon: "⚖️",
                            filename: "casier_d.pdf",
                            status: "approved",
                            reject_reason: "",
                        },
                        {
                            id: 10,
                            type: "diplome",
                            label: "Diplôme",
                            icon: "🎓",
                            filename: "Cert_electro.pdf",
                            status: "approved",
                            reject_reason: "",
                        },
                    ],
                },
            ];
        },
    },

    mounted() {
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
        this.loadDossiers();
    },
};
</script>

<style scoped>
.av-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* TOPBAR */
.av-topbar {
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
    .av-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.av-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.av-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.av-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.av-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    display: none;
}
@media (min-width: 480px) {
    .av-page-sub {
        display: block;
    }
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
}
.ad-burger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
}

/* NOTIF */
.av-notif-btn {
    position: relative;
    background: var(--cr);
    border: 2px solid var(--grl);
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 17px;
    flex-shrink: 0;
}
.av-notif-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #6366f1;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 99px;
    min-width: 16px;
    text-align: center;
}

/* BTNS */
.av-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.av-btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}
.av-btn-orange {
    background: var(--or);
    color: #fff;
}
.av-btn-orange:hover:not(:disabled) {
    background: var(--or2);
}
.av-btn-green {
    background: #22c55e;
    color: #fff;
}
.av-btn-green:hover:not(:disabled) {
    background: #16a34a;
}
.av-btn-red {
    background: #6366f1;
    color: #fff;
}
.av-btn-red:hover:not(:disabled) {
    background: #4f46e5;
}
.av-btn-blue {
    background: #3b82f6;
    color: #fff;
}
.av-btn-blue:hover:not(:disabled) {
    background: #2563eb;
}
.av-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.av-btn-ghost:hover {
    background: #d5c9c0;
}
.av-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* SPINNERS */
.av-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: av-spin 0.7s linear infinite;
    flex-shrink: 0;
}
.av-spinner-lg {
    width: 32px;
    height: 32px;
    border-width: 3px;
    border-top-color: var(--or);
}
.av-mini-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: av-spin 0.7s linear infinite;
}
@keyframes av-spin {
    to {
        transform: rotate(360deg);
    }
}

/* CONTENT */
.av-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
@media (min-width: 600px) {
    .av-content {
        padding: 20px;
        gap: 18px;
    }
}
@media (min-width: 900px) {
    .av-content {
        padding: 28px;
        gap: 20px;
    }
}

/* STATS */
.av-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
@media (min-width: 480px) {
    .av-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 900px) {
    .av-stats-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}
.av-stat {
    background: var(--wh);
    border-radius: 12px;
    padding: 10px 12px;
    border: 2px solid var(--grl);
}
@media (min-width: 600px) {
    .av-stat {
        border-radius: 14px;
        padding: 14px 16px;
    }
}
.av-stat.red {
    background: #6366f1;
    border-color: #6366f1;
}
.av-stat.orange {
    background: var(--or);
    border-color: var(--or);
}
.av-stat.green {
    background: #22c55e;
    border-color: #22c55e;
}
.av-stat-icon {
    font-size: 16px;
    margin-bottom: 4px;
}
@media (min-width: 600px) {
    .av-stat-icon {
        font-size: 20px;
        margin-bottom: 6px;
    }
}
.av-stat-val {
    font-size: 18px;
    font-weight: 900;
    color: var(--dk);
    line-height: 1;
}
@media (min-width: 600px) {
    .av-stat-val {
        font-size: 22px;
    }
}
.av-stat.red .av-stat-val,
.av-stat.orange .av-stat-val,
.av-stat.green .av-stat-val {
    color: #fff;
}
.av-stat-lbl {
    font-size: 10px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.3;
}
@media (min-width: 600px) {
    .av-stat-lbl {
        font-size: 11.5px;
    }
}
.av-stat.red .av-stat-lbl,
.av-stat.orange .av-stat-lbl,
.av-stat.green .av-stat-lbl {
    color: rgba(255, 255, 255, 0.85);
}

/* TOOLBAR */
.av-toolbar {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    border: 2px solid var(--grl);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.av-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--cr);
    border: 2px solid var(--grl);
    border-radius: 8px;
    padding: 8px 12px;
    flex: 1;
    min-width: 200px;
}
.av-search input {
    background: none;
    border: none;
    outline: none;
    font-size: 14px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    width: 100%;
}
.av-filter-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.av-ftab {
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    color: var(--gr);
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
    display: flex;
    align-items: center;
    gap: 4px;
}
.av-ftab:hover {
    border-color: var(--or);
    color: var(--or);
}
.av-ftab.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.av-ftab-count {
    font-size: 10px;
    font-weight: 700;
    background: rgba(0, 0, 0, 0.12);
    padding: 1px 5px;
    border-radius: 99px;
}
.av-ftab.active .av-ftab-count {
    background: rgba(255, 255, 255, 0.25);
}

/* LOADING STATE */
.av-loading-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gr);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
}

/* TABLE WRAP */
.av-table-wrap {
    background: var(--wh);
    border-radius: 14px;
    border: 2px solid var(--grl);
    overflow: hidden;
}
.av-table-header {
    padding: 12px 14px;
    border-bottom: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
@media (min-width: 600px) {
    .av-table-header {
        padding: 14px 18px;
    }
}
.av-table-header h3 {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.av-t-count {
    font-size: 13px;
    color: var(--gr);
    font-weight: 500;
}

/* TABLE DESKTOP */
.av-table-scroll {
    overflow-x: auto;
    display: none;
}
@media (min-width: 768px) {
    .av-table-scroll {
        display: block;
    }
}
.av-table {
    width: 100%;
    border-collapse: collapse;
}
.av-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    background: #faf7f4;
    border-bottom: 2px solid var(--grl);
}
.av-table thead th:last-child {
    text-align: right;
}
.av-table tbody tr {
    border-bottom: 1px solid #f0ebe5;
    transition: background 0.15s;
}
.av-table tbody tr:last-child {
    border-bottom: none;
}
.av-table tbody tr:hover {
    background: #fdf9f6;
}
.av-table td {
    padding: 12px 14px;
    font-size: 13.5px;
    vertical-align: middle;
}
.av-table td:last-child {
    text-align: right;
}

/* USER CELL */
.av-user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.av-user-av {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
}
.av-user-name {
    font-weight: 700;
    font-size: 13.5px;
    color: var(--dk);
}
.av-user-meta {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 1px;
}
.av-date {
    font-size: 12.5px;
    color: var(--gr);
}

/* ROLE BADGE */
.av-role-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 99px;
    display: inline-block;
    margin-bottom: 2px;
}
.av-role-badge.contractor {
    background: #fff7ed;
    color: var(--or);
    border: 1px solid #fed7aa;
}
.av-role-badge.client {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.av-role-badge.talent {
    background: #f5f3ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}

/* DOC DOTS */
.av-doc-dots {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    max-width: 120px;
}
.av-doc-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.av-doc-dot.approved {
    background: #22c55e;
}
.av-doc-dot.pending {
    background: #fcd34d;
}
.av-doc-dot.rejected {
    background: #6366f1;
}
.av-doc-dot.missing {
    background: #e5e5e5;
}
.av-doc-count {
    font-size: 11px;
    color: var(--gr);
    margin-top: 3px;
}

/* STATUS CELL */
.av-status-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}
.av-certified-badge {
    font-size: 16px;
}

/* BADGES */
.av-badge {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
}
.av-badge.pending {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
.av-badge.review {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.av-badge.approved {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.av-badge.rejected {
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
}
.av-badge.incomplete {
    background: #f5f5f5;
    color: #666;
    border: 1px solid #e5e5e5;
}

/* CARDS MOBILE */
.av-cards-mobile {
    display: flex;
    flex-direction: column;
    gap: 0;
}
@media (min-width: 768px) {
    .av-cards-mobile {
        display: none;
    }
}
.av-dossier-card {
    padding: 14px 16px;
    border-bottom: 1px solid var(--grl);
    transition: background 0.15s;
}
.av-dossier-card:last-child {
    border-bottom: none;
}
.av-dossier-card:hover {
    background: #fdf9f6;
}
.av-dossier-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}
.av-dossier-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 12px;
    font-size: 11.5px;
    color: var(--gr);
    margin-bottom: 10px;
}
.av-dossier-card-meta span {
    display: flex;
    align-items: center;
    gap: 3px;
}

/* EMPTY */
.av-empty {
    text-align: center;
    padding: 52px 20px;
}
.av-empty-icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.av-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 4px;
}
.av-empty-sub {
    font-size: 13px;
    color: var(--gr);
}

/* MODAL */
.av-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    z-index: 200;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
    animation: av-fade 0.2s ease;
    overflow-y: auto;
}
@media (min-width: 600px) {
    .av-modal-overlay {
        align-items: center;
        padding: 16px;
    }
}
@keyframes av-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.av-modal {
    background: var(--wh);
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-width: 100%;
    max-height: 94dvh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: av-slide 0.25s ease;
}
@media (min-width: 600px) {
    .av-modal {
        border-radius: 18px;
        max-width: 760px;
        max-height: 92vh;
    }
}
.av-modal-sm {
    max-width: 460px;
}
@keyframes av-slide {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.av-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 18px 14px;
    border-bottom: 2px solid var(--grl);
    position: sticky;
    top: 0;
    background: var(--wh);
    border-radius: 20px 20px 0 0;
    z-index: 1;
}
@media (min-width: 600px) {
    .av-modal-header {
        padding: 20px 24px 16px;
        border-radius: 18px 18px 0 0;
    }
}
.av-modal-header h3 {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk);
}
.av-modal-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
}
.av-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
    flex-shrink: 0;
}
.av-modal-body {
    padding: 14px 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
@media (min-width: 600px) {
    .av-modal-body {
        padding: 20px 24px;
        gap: 18px;
    }
}
.av-modal-footer {
    padding: 12px 16px;
    border-top: 2px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background: #faf7f4;
    border-radius: 0;
    flex-wrap: wrap;
}
@media (min-width: 600px) {
    .av-modal-footer {
        padding: 14px 24px;
        border-radius: 0 0 18px 18px;
    }
}

/* DETAIL GRID */
.av-detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
@media (min-width: 520px) {
    .av-detail-grid {
        grid-template-columns: 1fr 1fr;
    }
}
.av-detail-section {
    background: #faf7f4;
    border-radius: 12px;
    padding: 14px 16px;
}
.av-detail-section h4 {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--gr);
    margin-bottom: 10px;
}
.av-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    gap: 10px;
}
.av-detail-row:last-child {
    border-bottom: none;
}
.av-detail-row span:first-child {
    color: var(--gr);
    flex-shrink: 0;
}
.av-detail-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
}

/* DOCS */
.av-docs-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.av-docs-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 8px;
}
.av-docs-count {
    background: var(--or3);
    color: var(--or);
    border: 1px solid var(--am);
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
}
.av-doc-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.av-doc-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 2px solid var(--grl);
    background: var(--wh);
    flex-wrap: wrap;
}
.av-doc-item.approved {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.av-doc-item.rejected {
    border-color: #c7d2fe;
    background: #eef2ff;
}
.av-doc-item.pending {
    border-color: #fde68a;
    background: #fffbeb;
}
.av-doc-icon {
    font-size: 20px;
    flex-shrink: 0;
}
.av-doc-info {
    flex: 1;
    min-width: 0;
}
.av-doc-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.av-doc-file {
    font-size: 11.5px;
    color: var(--grm);
    margin-top: 2px;
}
.av-doc-status {
    font-size: 12px;
    font-weight: 600;
    margin-top: 3px;
}
.av-doc-status.approved {
    color: #16a34a;
}
.av-doc-status.rejected {
    color: #4f46e5;
}
.av-doc-status.pending {
    color: #d97706;
}
.av-doc-status.missing {
    color: var(--grm);
}
.av-doc-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.av-doc-btn {
    border-radius: 7px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    border: 1.5px solid;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 4px;
}
.av-doc-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.av-doc-btn.download {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}
.av-doc-btn.download:hover {
    background: #dbeafe;
}
.av-doc-btn.validate {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
}
.av-doc-btn.validate:hover {
    background: #dcfce7;
}
.av-doc-btn.reject {
    background: #eef2ff;
    border-color: #c7d2fe;
    color: #4f46e5;
}
.av-doc-btn.reject:hover {
    background: #e0e7ff;
}
.av-doc-missing-lbl {
    font-size: 12px;
    color: #999;
    font-style: italic;
    flex-shrink: 0;
}

/* NOTES */
.av-notes-label {
    font-size: 13px;
    font-weight: 700;
    display: block;
    margin-bottom: 7px;
    color: var(--dk);
}
.av-notes-textarea {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid var(--grl);
    border-radius: 9px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    outline: none;
    resize: vertical;
    min-height: 80px;
    transition: border-color 0.2s;
}
.av-notes-textarea:focus {
    border-color: var(--or);
}
.av-err {
    font-size: 12px;
    color: #4f46e5;
    margin-top: 6px;
}

/* TOASTS */
.av-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.av-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 220px;
    animation: av-slide 0.3s ease;
}
.av-toast.success {
    background: #16a34a;
}
.av-toast.error {
    background: #dc2626;
}

/* PREVIEW MODAL */
.av-preview-overlay {
    z-index: 300;
}
.av-modal-preview {
    max-width: 900px;
    height: 90vh;
    display: flex;
    flex-direction: column;
}
.av-preview-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.av-preview-body {
    flex: 1;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a1a1a;
    position: relative;
    min-height: 0;
}
.av-preview-loading,
.av-preview-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    color: #fff;
    font-size: 14px;
    padding: 40px;
    text-align: center;
}
.av-preview-error-icon {
    font-size: 48px;
}
.av-preview-iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #fff;
}
.av-preview-img-wrap {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: auto;
    padding: 16px;
}
.av-preview-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
}
</style>
