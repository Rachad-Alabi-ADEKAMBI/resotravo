<template>
    <div class="ctr-wrap">
        <!-- TOPBAR -->
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
                    <div class="ctr-page-title">Mon dossier</div>
                    <div class="ctr-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="ctr-topbar-right">
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
                        <span class="ctr-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
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
                <div class="ctr-avatar">{{ initials }}</div>
            </div>
        </div>

        <!-- CONTENU -->
        <div class="ctr-content">
            <!-- Statut global -->
            <div class="dos-status-banner" :class="bannerClass">
                <div class="dos-banner-icon">{{ bannerIcon }}</div>
                <div>
                    <div class="dos-banner-title">{{ bannerTitle }}</div>
                    <div class="dos-banner-sub">{{ bannerSub }}</div>
                </div>
                <!-- Badge certifié (contractor uniquement) -->
                <span
                    class="dos-certified-badge"
                    v-if="isContractor && userStatus === 'approved'"
                    >🏅 Profil certifié</span
                >
            </div>

            <!-- Progression (contractor seulement) -->
            <div class="dos-progress-card" v-if="isContractor">
                <div class="dos-progress-header">
                    <span class="dos-progress-label"
                        >Progression du dossier</span
                    >
                    <span class="dos-progress-count"
                        >{{ docProgress.approved }}/{{
                            docProgress.total
                        }}
                        validés</span
                    >
                </div>
                <div class="dos-progress-track">
                    <div
                        class="dos-progress-fill"
                        :style="{ width: docProgress.percentage + '%' }"
                    ></div>
                </div>
                <div class="dos-progress-note">
                    Le badge <strong>Profil Certifié</strong> est accordé après
                    validation de <strong>tous</strong> vos documents par
                    l'équipe Resotravo.
                </div>
            </div>

            <!-- Note client -->
            <div class="dos-note-client" v-if="isClient">
                <span>ℹ️</span>
                <span
                    >Votre pièce d'identité et votre photo sont nécessaires pour
                    sécuriser les interventions à votre domicile ou
                    entreprise.</span
                >
            </div>

            <!-- Documents -->
            <div class="dos-docs-grid">
                <div
                    class="dos-doc-card"
                    v-for="doc in documents"
                    :key="doc.type"
                    :class="'status-' + doc.status"
                >
                    <div class="dos-doc-header">
                        <span class="dos-doc-icon">{{ doc.icon }}</span>
                        <div class="dos-doc-info">
                            <div class="dos-doc-label">{{ doc.label }}</div>
                            <div class="dos-doc-filename" v-if="doc.filename">
                                📎 {{ doc.filename }}
                            </div>
                        </div>
                        <span
                            class="dos-doc-badge"
                            :class="'badge-' + doc.status"
                            >{{ statusLabel(doc.status) }}</span
                        >
                    </div>

                    <!-- Motif de refus -->
                    <div
                        class="dos-reject-reason"
                        v-if="doc.status === 'rejected' && doc.reject_reason"
                    >
                        ❌ Motif : {{ doc.reject_reason }}
                    </div>

                    <!-- Actions upload -->
                    <div
                        class="dos-doc-actions"
                        v-if="
                            userStatus !== 'approved' ||
                            doc.status === 'rejected'
                        "
                    >
                        <label
                            class="dos-upload-btn"
                            :class="{
                                uploading: doc.uploading,
                                'btn-replace':
                                    doc.status === 'pending' ||
                                    doc.status === 'approved',
                            }"
                        >
                            <div
                                class="ctr-mini-spinner"
                                v-if="doc.uploading"
                            ></div>
                            <span v-else>
                                {{
                                    doc.status === "missing"
                                        ? "+ Déposer"
                                        : doc.status === "rejected"
                                          ? "↻ Remplacer"
                                          : doc.status === "pending"
                                            ? "↻ Modifier"
                                            : "↻"
                                }}
                            </span>
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

            <!-- Note format -->
            <div class="dos-format-note">
                ℹ️ Formats acceptés : <strong>PDF, JPG, PNG</strong> — 5 Mo max
                par document. Délai de vérification :
                <strong>24-48h</strong> après soumission.
            </div>
        </div>

        <!-- Toasts -->
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
    name: "DossierComponent",
    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },
    data() {
        return {
            documents: [],
            docProgress: { approved: 0, total: 0, percentage: 0 },
            userStatus: this.user.status ?? "pending",
            loading: false,
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,
            toasts: [],
            toastId: 0,
            sidebarOpen: false,
        };
    },
    computed: {
        greeting() {
            const h = new Date().getHours();
            return h < 12 ? "Bonjour" : h < 18 ? "Bon après-midi" : "Bonsoir";
        },
        initials() {
            return (
                this.user.name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "??"
            );
        },
        isContractor() {
            return this.user.role === "contractor";
        },
        isClient() {
            return this.user.role === "client";
        },
        bannerClass() {
            if (this.userStatus === "approved") return "banner-approved";
            if (this.userStatus === "rejected") return "banner-rejected";
            const allUploaded =
                this.documents.length > 0 &&
                this.documents.every((d) => d.status !== "missing");
            return allUploaded ? "banner-review" : "banner-pending";
        },
        bannerIcon() {
            if (this.userStatus === "approved") return "✅";
            if (this.userStatus === "rejected") return "❌";
            const allUploaded = this.documents.every(
                (d) => d.status !== "missing",
            );
            return allUploaded ? "⏳" : "📂";
        },
        bannerTitle() {
            if (this.userStatus === "approved")
                return this.isContractor
                    ? "Profil certifié Resotravo"
                    : "Identité vérifiée";
            if (this.userStatus === "rejected") return "Dossier refusé";
            const allUploaded = this.documents.every(
                (d) => d.status !== "missing",
            );
            if (allUploaded) return "Dossier en cours de vérification";
            return "Dossier incomplet";
        },
        bannerSub() {
            if (this.userStatus === "approved")
                return this.isContractor
                    ? "Vous pouvez accepter des missions et apparaître dans les recherches."
                    : "Votre identité a été validée par l'équipe Resotravo.";
            if (this.userStatus === "rejected")
                return "Certains documents ont été refusés. Corrigez-les et re-soumettez.";
            const allUploaded = this.documents.every(
                (d) => d.status !== "missing",
            );
            if (allUploaded)
                return "L'équipe Resotravo vérifie vos documents. Délai : 24-48h.";
            return "Déposez tous vos documents pour activer votre compte.";
        },
    },
    methods: {
        statusLabel(s) {
            return (
                {
                    missing: "Manquant",
                    pending: "En attente",
                    approved: "Validé",
                    rejected: "Refusé",
                }[s] ?? s
            );
        },

        async fetchDocuments() {
            this.loading = true;
            try {
                const res = await fetch(this.routes.documents_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.documents = (data.documents ?? []).map((d) => ({
                    ...d,
                    uploading: false,
                }));
                this.docProgress = data.progress ?? {
                    approved: 0,
                    total: this.documents.length,
                    percentage: 0,
                };
            } catch {
                this.showToast("Erreur lors du chargement.", "error");
            } finally {
                this.loading = false;
            }
        },

        async uploadDocument(doc, event) {
            const file = event.target.files[0];
            if (!file) return;
            doc.uploading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            const form = new FormData();
            form.append("type", doc.type);
            form.append("file", file);
            try {
                const res = await fetch(this.routes.documents_upload, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: form,
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                doc.status = "pending";
                doc.filename = file.name;
                this.docProgress = data.progress ?? this.docProgress;
                this.showToast(
                    "✅ Document déposé. Vérification sous 24-48h.",
                    "success",
                );
            } catch {
                this.showToast(
                    "Erreur lors de l'envoi. Vérifiez le format (PDF/JPG/PNG, max 5 Mo).",
                    "error",
                );
            } finally {
                doc.uploading = false;
                event.target.value = "";
            }
        },

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
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
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
        showToast(m, t = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message: m, type: t });
            setTimeout(() => {
                this.toasts = this.toasts.filter((x) => x.id !== id);
            }, 5000);
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
        this.fetchDocuments();
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
.ctr-wrap {
    --ctr-or: #f97316;
    --ctr-or2: #ea580c;
    --ctr-or3: #fff7ed;
    --ctr-dk: #1c1412;
    --ctr-gr: #7c6a5a;
    --ctr-grm: #8a7d78;
    --ctr-grl: #e8ddd4;
    --ctr-wh: #ffffff;
    font-family: "Poppins", sans-serif;
    color: var(--ctr-dk);
    background: #f8f4f0;
    min-height: 100vh;
}
.ctr-topbar {
    background: var(--ctr-wh);
    border-bottom: 2px solid var(--ctr-grl);
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 40;
    gap: 10px;
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
    color: var(--ctr-dk);
}
.ctr-page-sub {
    font-size: 11px;
    color: var(--ctr-gr);
    margin-top: 1px;
}
.ctr-page-sub strong {
    color: var(--ctr-dk);
}
.ctr-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--ctr-or), var(--ctr-or2));
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
    background: var(--ctr-dk);
    border-radius: 2px;
}
/* Notifs */
.ctr-notif-wrap {
    position: relative;
}
.ctr-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ctr-gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 8px;
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
    width: 300px;
    background: var(--ctr-wh);
    border: 1.5px solid var(--ctr-grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 100;
    overflow: hidden;
}
.ctr-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--ctr-grl);
    font-size: 13px;
    font-weight: 700;
}
.ctr-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--ctr-or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.ctr-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--ctr-grl);
}
.ctr-notif-item:hover,
.ctr-notif-item.unread {
    background: var(--ctr-or3);
}
.ctr-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--ctr-or);
    flex-shrink: 0;
    margin-top: 5px;
}
.ctr-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ctr-dk);
}
.ctr-notif-body {
    font-size: 12px;
    color: var(--ctr-gr);
    margin-top: 2px;
}
.ctr-notif-ago {
    font-size: 11px;
    color: var(--ctr-grm);
    margin-top: 3px;
}
.ctr-notif-loading,
.ctr-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--ctr-gr);
    text-align: center;
}
/* Content */
.ctr-content {
    padding: 24px;
    max-width: 860px;
    margin: 0 auto;
}
/* Status Banner */
.dos-status-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 20px;
    border: 1.5px solid;
    flex-wrap: wrap;
}
.banner-pending {
    background: #fff7ed;
    border-color: #fed7aa;
}
.banner-review {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.banner-approved {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.banner-rejected {
    background: #fef2f2;
    border-color: #fecaca;
}
.dos-banner-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.dos-banner-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--ctr-dk);
}
.dos-banner-sub {
    font-size: 13px;
    color: var(--ctr-gr);
    margin-top: 3px;
    line-height: 1.5;
}
.dos-certified-badge {
    margin-left: auto;
    background: linear-gradient(135deg, var(--ctr-or), var(--ctr-or2));
    color: #fff;
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}
/* Progress */
.dos-progress-card {
    background: var(--ctr-wh);
    border: 1.5px solid var(--ctr-grl);
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
.dos-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--ctr-dk);
}
.dos-progress-count {
    color: var(--ctr-or);
    font-weight: 800;
}
.dos-progress-track {
    height: 8px;
    background: var(--ctr-grl);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 10px;
}
.dos-progress-fill {
    height: 100%;
    background: linear-gradient(135deg, var(--ctr-or), var(--ctr-or2));
    border-radius: 99px;
    transition: width 0.5s;
}
.dos-progress-note {
    font-size: 12px;
    color: var(--ctr-gr);
    line-height: 1.6;
}
/* Note client */
.dos-note-client {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    color: #1e40af;
    margin-bottom: 20px;
    line-height: 1.6;
}
/* Docs grid */
.dos-docs-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 16px;
}
.dos-doc-card {
    background: var(--ctr-wh);
    border: 1.5px solid var(--ctr-grl);
    border-radius: 14px;
    padding: 16px;
    transition: border-color 0.18s;
}
.dos-doc-card.status-missing {
    border-color: #fed7aa;
    background: #fff7ed;
}
.dos-doc-card.status-pending {
    border-color: #bfdbfe;
    background: #f8faff;
}
.dos-doc-card.status-approved {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.dos-doc-card.status-rejected {
    border-color: #fecaca;
    background: #fef2f2;
}
.dos-doc-header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.dos-doc-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.dos-doc-info {
    flex: 1;
    min-width: 0;
}
.dos-doc-label {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--ctr-dk);
    line-height: 1.4;
}
.dos-doc-filename {
    font-size: 12px;
    color: var(--ctr-gr);
    margin-top: 3px;
}
.dos-doc-badge {
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    padding: 3px 10px;
    flex-shrink: 0;
}
.badge-missing {
    background: #fef3c7;
    color: #92400e;
}
.badge-pending {
    background: #dbeafe;
    color: #1e40af;
}
.badge-approved {
    background: #dcfce7;
    color: #166534;
}
.badge-rejected {
    background: #fee2e2;
    color: #991b1b;
}
.dos-reject-reason {
    margin-top: 10px;
    font-size: 12.5px;
    color: #dc2626;
    background: #fef2f2;
    border-radius: 8px;
    padding: 8px 12px;
    border: 1px solid #fecaca;
}
.dos-doc-actions {
    margin-top: 12px;
}
.dos-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--ctr-or);
    color: #fff;
    border-radius: 8px;
    padding: 7px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.dos-upload-btn:hover {
    background: var(--ctr-or2);
}
.dos-upload-btn.btn-replace {
    background: var(--ctr-grl);
    color: var(--ctr-dk);
}
.dos-upload-btn.btn-replace:hover {
    background: #ddd0c6;
}
.dos-upload-btn.uploading {
    opacity: 0.6;
    cursor: not-allowed;
}
/* Format note */
.dos-format-note {
    font-size: 12.5px;
    color: var(--ctr-gr);
    background: #f8f4f0;
    border-radius: 10px;
    padding: 12px 14px;
    border: 1px solid var(--ctr-grl);
    line-height: 1.6;
}
/* Spinner */
.ctr-mini-spinner {
    width: 12px;
    height: 12px;
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
/* Toasts */
.ctr-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 9999;
}
.ctr-toast {
    background: var(--ctr-dk);
    color: #fff;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.18);
    animation: fadeUp 0.25s ease;
    max-width: 340px;
}
.ctr-toast.success {
    background: #16a34a;
}
.ctr-toast.error {
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
