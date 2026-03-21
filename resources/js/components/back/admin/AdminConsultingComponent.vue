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
                    <div class="amis-page-title">Allo Conseils</div>
                    <div class="amis-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <div class="amis-count-pill">
                    {{ totalFiltered }} ticket{{ totalFiltered > 1 ? "s" : "" }}
                </div>
                <!-- Notifications -->
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
                <div class="amis-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <!-- ══════════════ BANDE STATS ══════════════ -->
        <div class="ac-stats-band">
            <div class="ac-stat-item" v-for="s in statCards" :key="s.label">
                <div class="ac-stat-dot" :class="s.dotClass"></div>
                <span class="ac-stat-val">{{ s.value }}</span>
                <span class="ac-stat-lbl">{{ s.label }}</span>
            </div>
        </div>

        <!-- ══════════════ LAYOUT SPLIT ══════════════ -->
        <div class="ac-layout">
            <!-- ── COLONNE GAUCHE : liste des tickets ── -->
            <div class="ac-tickets-panel">
                <!-- Filtres -->
                <div class="ac-panel-filters">
                    <div class="amis-search-wrap">
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input
                            class="amis-search"
                            type="text"
                            v-model="search"
                            placeholder="Rechercher…"
                            @input="currentPage = 1"
                        />
                    </div>
                    <select
                        class="amis-select ac-select-full"
                        v-model="filterStatus"
                        @change="currentPage = 1"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="open">Nouveau</option>
                        <option value="pending_human">En attente agent</option>
                        <option value="in_progress">En cours</option>
                        <option value="resolved">Résolu</option>
                        <option value="closed">Fermé</option>
                    </select>
                </div>

                <!-- Tabs -->
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
                        <span class="amis-tab-count">{{
                            countByTab(t.key)
                        }}</span>
                    </button>
                </div>

                <!-- Loader -->
                <div v-if="loading" class="ac-ticket-list">
                    <div
                        class="ac-ticket-skeleton"
                        v-for="n in 5"
                        :key="n"
                    ></div>
                </div>

                <!-- Erreur -->
                <div class="amis-alert-error" v-else-if="loadError">
                    ⚠️ {{ loadError }}
                    <button
                        class="amis-btn amis-btn-ghost amis-btn-sm"
                        @click="fetchTickets"
                    >
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div class="amis-empty" v-else-if="pagedTickets.length === 0">
                    <div class="amis-empty-icon">💬</div>
                    <div class="amis-empty-title">Aucun ticket trouvé</div>
                    <div class="amis-empty-sub">Modifiez vos filtres.</div>
                </div>

                <!-- Liste tickets -->
                <div class="ac-ticket-list" v-else>
                    <div
                        class="ac-ticket-item"
                        v-for="t in pagedTickets"
                        :key="t.id"
                        :class="{
                            active: activeTicket?.id === t.id,
                            unread: t.unread_count > 0,
                        }"
                        @click="openTicket(t)"
                    >
                        <div class="ac-ticket-head">
                            <div class="ac-ticket-user">{{ t.user_name }}</div>
                            <span
                                class="amis-badge"
                                :class="badgeClass(t.status)"
                                >{{ t.status_label }}</span
                            >
                        </div>
                        <div class="ac-ticket-subject">{{ t.subject }}</div>
                        <div class="ac-ticket-preview" v-if="t.last_message">
                            <span class="ac-preview-who">
                                {{
                                    t.last_message_type === "user"
                                        ? "👤"
                                        : t.last_message_type === "ia"
                                          ? "🤖"
                                          : "👨‍💼"
                                }}
                            </span>
                            {{ t.last_message }}
                        </div>
                        <div class="ac-ticket-meta">
                            <span>{{ t.updated_at_ago }}</span>
                            <span v-if="t.agent_name"
                                >👨‍💼 {{ t.agent_name }}</span
                            >
                            <span
                                v-if="t.human_requested && !t.human_assigned"
                                class="ac-urgent-chip"
                                >⚠️ Humain demandé</span
                            >
                            <span
                                class="ac-unread-chip"
                                v-if="t.unread_count > 0"
                                >{{ t.unread_count }} non lu{{
                                    t.unread_count > 1 ? "s" : ""
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="ac-pagination" v-if="totalFiltered > perPage">
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
                </div>
            </div>

            <!-- ── COLONNE DROITE : détail / messagerie ── -->
            <div class="ac-chat-panel">
                <!-- Placeholder vide -->
                <div class="ac-chat-empty" v-if="!activeTicket">
                    <div class="ac-chat-empty-icon">💬</div>
                    <div class="ac-chat-empty-title">
                        Sélectionnez un ticket
                    </div>
                    <div class="ac-chat-empty-sub">
                        Cliquez sur un ticket à gauche pour afficher la
                        conversation.
                    </div>
                </div>

                <!-- Conversation ouverte -->
                <template v-else>
                    <!-- En-tête ticket -->
                    <div class="ac-chat-header">
                        <div class="ac-chat-header-left">
                            <div class="ac-chat-avatar">
                                {{ activeTicket.user_name[0] }}
                            </div>
                            <div>
                                <div class="ac-chat-name">
                                    {{ activeTicket.user_name }}
                                </div>
                                <div class="ac-chat-sub">
                                    {{ activeTicket.user_email }} · Ticket #{{
                                        activeTicket.id
                                    }}
                                </div>
                            </div>
                        </div>
                        <div class="ac-chat-header-right">
                            <span
                                class="amis-badge"
                                :class="badgeClass(activeTicket.status)"
                            >
                                {{ activeTicket.status_label }}
                            </span>
                            <button
                                class="amis-btn amis-btn-orange amis-btn-sm"
                                v-if="
                                    !activeTicket.human_assigned &&
                                    !['resolved', 'closed'].includes(
                                        activeTicket.status,
                                    )
                                "
                                @click="assignToMe"
                                :disabled="actionLoading === 'assign'"
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'assign'"
                                ></div>
                                <span v-else>👋 Prendre en charge</span>
                            </button>
                            <button
                                class="amis-btn amis-btn-green amis-btn-sm"
                                v-if="
                                    !['resolved', 'closed'].includes(
                                        activeTicket.status,
                                    )
                                "
                                @click="closeTicket('resolved')"
                                :disabled="actionLoading === 'resolve'"
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'resolve'"
                                ></div>
                                <span v-else>✅ Résoudre</span>
                            </button>
                            <button
                                class="amis-btn amis-btn-red amis-btn-sm"
                                v-if="
                                    !['resolved', 'closed'].includes(
                                        activeTicket.status,
                                    )
                                "
                                @click="closeTicket('closed')"
                                :disabled="actionLoading === 'close'"
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'close'"
                                ></div>
                                <span v-else>🔒 Clôturer</span>
                            </button>
                            <button
                                class="amis-btn amis-btn-ghost amis-btn-sm"
                                v-if="
                                    ['resolved', 'closed'].includes(
                                        activeTicket.status,
                                    )
                                "
                                @click="closeTicket('in_progress')"
                                :disabled="actionLoading === 'reopen'"
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'reopen'"
                                ></div>
                                <span v-else>🔄 Rouvrir</span>
                            </button>
                        </div>
                    </div>

                    <!-- Infos ticket -->
                    <div class="ac-ticket-info-bar">
                        <span v-if="activeTicket.category"
                            >🏷️ {{ activeTicket.category }}</span
                        >
                        <span>📅 {{ activeTicket.created_at_label }}</span>
                        <span v-if="activeTicket.agent_name"
                            >👨‍💼 {{ activeTicket.agent_name }}</span
                        >
                        <span
                            v-if="
                                activeTicket.human_requested &&
                                !activeTicket.human_assigned
                            "
                            class="ac-urgent-chip"
                            >⚠️ Agent humain demandé</span
                        >
                        <span v-if="activeTicket.rating">{{
                            "⭐".repeat(activeTicket.rating)
                        }}</span>
                    </div>

                    <!-- Messages -->
                    <div class="ac-messages-wrap" ref="messagesContainer">
                        <div class="ac-msg-loading" v-if="messagesLoading">
                            <div
                                class="amis-spinner"
                                style="
                                    border-color: rgba(0, 0, 0, 0.15);
                                    border-top-color: var(--or);
                                "
                            ></div>
                        </div>
                        <template v-else>
                            <div
                                v-for="msg in activeMessages"
                                :key="msg.id"
                                class="ac-msg-row"
                                :class="'ac-msg-' + msg.sender_type"
                            >
                                <div class="ac-msg-avatar-sm">
                                    {{
                                        msg.sender_type === "user"
                                            ? "👤"
                                            : msg.sender_type === "ia"
                                              ? "🤖"
                                              : "👨‍💼"
                                    }}
                                </div>
                                <div class="ac-msg-content">
                                    <div class="ac-msg-sender-name">
                                        {{ msg.sender_name }}
                                        <span class="ac-msg-time">{{
                                            msg.ago
                                        }}</span>
                                    </div>
                                    <div
                                        class="ac-msg-bubble"
                                        :class="'bubble-' + msg.sender_type"
                                    >
                                        {{ msg.body }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="ac-msg-empty"
                                v-if="activeMessages.length === 0"
                            >
                                Aucun message dans ce ticket.
                            </div>
                        </template>
                    </div>

                    <!-- Zone de réponse -->
                    <div
                        class="ac-reply-bar"
                        v-if="
                            activeTicket.status !== 'resolved' &&
                            activeTicket.status !== 'closed'
                        "
                    >
                        <textarea
                            class="ac-reply-input"
                            v-model="replyText"
                            rows="3"
                            placeholder="Votre réponse à l'utilisateur…"
                            @keydown.ctrl.enter="sendReply"
                        ></textarea>
                        <div class="ac-reply-actions">
                            <span class="ac-reply-hint"
                                >Ctrl+Entrée pour envoyer</span
                            >
                            <button
                                class="amis-btn amis-btn-orange"
                                @click="sendReply"
                                :disabled="
                                    !replyText.trim() ||
                                    actionLoading === 'reply'
                                "
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'reply'"
                                ></div>
                                <span v-else>Envoyer →</span>
                            </button>
                        </div>
                    </div>
                    <div class="ac-reply-closed" v-else>
                        <span
                            >🔒 Ticket
                            {{
                                activeTicket.status === "resolved"
                                    ? "résolu"
                                    : "clôturé"
                            }}
                            — répondre n'est plus possible.</span
                        >
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-sm"
                            @click="closeTicket('in_progress')"
                            :disabled="actionLoading === 'reopen'"
                        >
                            <div
                                class="amis-spinner"
                                v-if="actionLoading === 'reopen'"
                            ></div>
                            <span v-else>🔄 Rouvrir</span>
                        </button>
                    </div>

                    <!-- Note interne -->
                    <div class="ac-note-section">
                        <div class="ac-note-label">
                            📝 Note interne (non visible par l'utilisateur)
                        </div>
                        <textarea
                            class="ac-note-input"
                            v-model="noteText"
                            rows="2"
                            placeholder="Ajouter une note interne…"
                        ></textarea>
                        <button
                            class="amis-btn amis-btn-ghost amis-btn-sm"
                            @click="saveNote"
                            :disabled="actionLoading === 'note'"
                        >
                            <div
                                class="amis-spinner"
                                v-if="actionLoading === 'note'"
                            ></div>
                            <span v-else>💾 Sauvegarder la note</span>
                        </button>
                    </div>
                </template>
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
    name: "AdminConsultingComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            tickets: [],
            loading: false,
            loadError: null,

            // Filtres
            search: "",
            filterStatus: "",
            activeTab: "all",
            currentPage: 1,
            perPage: 12,

            // Ticket actif
            activeTicket: null,
            activeMessages: [],
            messagesLoading: false,
            replyText: "",
            noteText: "",
            actionLoading: null,

            sidebarOpen: false,

            // Notifications
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            // Polling messages
            messageInterval: null,

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
                { key: "open", label: "Nouveaux" },
                { key: "pending_human", label: "En attente" },
                { key: "in_progress", label: "En cours" },
                { key: "resolved", label: "Résolus" },
            ];
        },

        statCards() {
            return [
                {
                    dotClass: "dot-gray",
                    value: this.tickets.length,
                    label: "Total tickets",
                },
                {
                    dotClass: "dot-red",
                    value: this.tickets.filter(
                        (t) => t.status === "pending_human",
                    ).length,
                    label: "En attente agent",
                },
                {
                    dotClass: "dot-blue",
                    value: this.tickets.filter(
                        (t) => t.status === "in_progress",
                    ).length,
                    label: "En cours",
                },
                {
                    dotClass: "dot-green",
                    value: this.tickets.filter(
                        (t) => t.status === "resolved" || t.status === "closed",
                    ).length,
                    label: "Résolus",
                },
            ];
        },

        filteredTickets() {
            let list = [...this.tickets];

            if (this.activeTab === "open")
                list = list.filter((t) => t.status === "open");
            else if (this.activeTab === "pending_human")
                list = list.filter((t) => t.status === "pending_human");
            else if (this.activeTab === "in_progress")
                list = list.filter((t) => t.status === "in_progress");
            else if (this.activeTab === "resolved")
                list = list.filter((t) =>
                    ["resolved", "closed"].includes(t.status),
                );

            if (this.filterStatus)
                list = list.filter((t) => t.status === this.filterStatus);

            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (t) =>
                        t.user_name?.toLowerCase().includes(q) ||
                        t.subject?.toLowerCase().includes(q) ||
                        t.user_email?.toLowerCase().includes(q) ||
                        t.last_message?.toLowerCase().includes(q),
                );
            }

            return list;
        },

        totalFiltered() {
            return this.filteredTickets.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },
        pagedTickets() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredTickets.slice(start, start + this.perPage);
        },
        visiblePages() {
            const pages = [],
                delta = 2;
            for (
                let i = Math.max(1, this.currentPage - delta);
                i <= Math.min(this.totalPages, this.currentPage + delta);
                i++
            )
                pages.push(i);
            return pages;
        },
    },

    methods: {
        // ── Fetch ──────────────────────────────────────────────────
        async fetchTickets() {
            this.loading = true;
            this.loadError = null;
            try {
                const res = await fetch(this.routes.tickets_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Erreur " + res.status);
                const data = await res.json();
                this.tickets = data.data ?? data;
            } catch {
                this.loadError =
                    "Impossible de charger les tickets. Réessayez.";
            } finally {
                this.loading = false;
            }
        },

        async openTicket(ticket) {
            this.activeTicket = ticket;
            this.replyText = "";
            this.noteText = ticket.admin_note ?? "";
            this.messagesLoading = true;
            clearInterval(this.messageInterval);
            try {
                const url = this.routes.tickets_messages.replace(
                    "{ticket}",
                    ticket.id,
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeMessages = data.messages;
                this.activeTicket = data.ticket;
                // Mettre à jour le ticket dans la liste
                const idx = this.tickets.findIndex((t) => t.id === ticket.id);
                if (idx !== -1) this.tickets.splice(idx, 1, data.ticket);
                this.$nextTick(() => this.scrollMessages());
            } catch {
                this.showToast(
                    "Erreur lors du chargement des messages.",
                    "error",
                );
            } finally {
                this.messagesLoading = false;
            }
            // Polling léger toutes les 15s
            this.messageInterval = setInterval(
                () => this.pollMessages(),
                15000,
            );
        },

        async pollMessages() {
            if (!this.activeTicket) return;
            try {
                const url = this.routes.tickets_messages.replace(
                    "{ticket}",
                    this.activeTicket.id,
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.messages.length !== this.activeMessages.length) {
                    this.activeMessages = data.messages;
                    this.$nextTick(() => this.scrollMessages());
                }
            } catch {}
        },

        // ── Répondre ───────────────────────────────────────────────
        async sendReply() {
            if (!this.replyText.trim()) return;
            this.actionLoading = "reply";
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.tickets_reply.replace(
                    "{ticket}",
                    this.activeTicket.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({ body: this.replyText }),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeMessages.push(data.message);
                this.replyText = "";
                // Mettre à jour le statut si nécessaire
                if (
                    this.activeTicket.status === "open" ||
                    this.activeTicket.status === "pending_human"
                ) {
                    this.activeTicket.status = "in_progress";
                    this.activeTicket.status_label = "En cours";
                    this.activeTicket.human_assigned = true;
                }
                this.showToast("✅ Réponse envoyée.", "success");
                this.$nextTick(() => this.scrollMessages());
            } catch {
                this.showToast("Erreur lors de l'envoi.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        // ── Assigner ───────────────────────────────────────────────
        async assignToMe() {
            this.actionLoading = "assign";
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.tickets_assign.replace(
                    "{ticket}",
                    this.activeTicket.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeTicket.human_assigned = true;
                this.activeTicket.status = "in_progress";
                this.activeTicket.status_label = "En cours";
                this.activeTicket.agent_name = data.agent_name;
                this.showToast("✅ Ticket pris en charge.", "success");
            } catch {
                this.showToast("Erreur lors de l'assignation.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        // ── Clôturer / Résoudre / Rouvrir ─────────────────────────
        async closeTicket(newStatus) {
            const loadingKey =
                newStatus === "resolved"
                    ? "resolve"
                    : newStatus === "closed"
                      ? "close"
                      : "reopen";
            this.actionLoading = loadingKey;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.tickets_status.replace(
                    "{ticket}",
                    this.activeTicket.id,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({ status: newStatus }),
                });
                if (!res.ok) throw new Error();

                const labelMap = {
                    open: "Nouveau",
                    in_progress: "En cours",
                    resolved: "Résolu",
                    closed: "Clôturé",
                };
                this.activeTicket.status = newStatus;
                this.activeTicket.status_label =
                    labelMap[newStatus] ?? newStatus;

                // Mettre à jour dans la liste gauche
                const idx = this.tickets.findIndex(
                    (t) => t.id === this.activeTicket.id,
                );
                if (idx !== -1) {
                    this.tickets[idx].status = newStatus;
                    this.tickets[idx].status_label =
                        labelMap[newStatus] ?? newStatus;
                }

                const msgs = {
                    resolved: "✅ Ticket marqué comme résolu.",
                    closed: "🔒 Ticket clôturé.",
                    in_progress: "🔄 Ticket rouvert.",
                };
                this.showToast(
                    msgs[newStatus] ?? "Statut mis à jour.",
                    "success",
                );
            } catch {
                this.showToast(
                    "Erreur lors de la mise à jour du statut.",
                    "error",
                );
            } finally {
                this.actionLoading = null;
            }
        },

        // ── Statut (conservé pour compatibilité interne) ───────────
        async updateStatus() {
            await this.closeTicket(this.activeTicket.status);
        },

        // ── Note interne ───────────────────────────────────────────
        async saveNote() {
            this.actionLoading = "note";
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.tickets_note.replace(
                    "{ticket}",
                    this.activeTicket.id,
                );
                await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({ note: this.noteText }),
                });
                this.activeTicket.admin_note = this.noteText;
                this.showToast("📝 Note sauvegardée.", "success");
            } catch {
                this.showToast("Erreur lors de la sauvegarde.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        // ── Helpers ────────────────────────────────────────────────
        countByTab(key) {
            if (key === "all") return this.tickets.length;
            if (key === "open")
                return this.tickets.filter((t) => t.status === "open").length;
            if (key === "pending_human")
                return this.tickets.filter((t) => t.status === "pending_human")
                    .length;
            if (key === "in_progress")
                return this.tickets.filter((t) => t.status === "in_progress")
                    .length;
            if (key === "resolved")
                return this.tickets.filter((t) =>
                    ["resolved", "closed"].includes(t.status),
                ).length;
            return 0;
        },

        badgeClass(status) {
            return (
                {
                    open: "warning",
                    pending_human: "urgent",
                    in_progress: "info",
                    resolved: "done",
                    closed: "cancelled",
                    ai_handled: "ghost",
                }[status] ?? ""
            );
        },

        scrollMessages() {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
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
        this.fetchTickets();
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
        clearInterval(this.messageInterval);
        document.removeEventListener("click", this.handleClickOutside);
    },
};
</script>

<style scoped>
.amis-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
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
    display: flex;
    flex-direction: column;
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

/* ── Layout split ── */
.ac-layout {
    display: flex;
    flex: 1;
    height: calc(100vh - 64px - 45px);
    overflow: hidden;
}
@media (max-width: 899px) {
    .ac-layout {
        flex-direction: column;
        height: auto;
        overflow: visible;
    }
}

/* ── Panel gauche : liste tickets ── */
.ac-tickets-panel {
    width: 380px;
    flex-shrink: 0;
    background: var(--wh);
    border-right: 1.5px solid var(--grl);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
@media (max-width: 1100px) {
    .ac-tickets-panel {
        width: 320px;
    }
}
@media (max-width: 899px) {
    .ac-tickets-panel {
        width: 100%;
        height: auto;
        border-right: none;
        border-bottom: 2px solid var(--grl);
    }
}

/* ── Bande stats horizontale sous la topbar ── */
.ac-stats-band {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--wh);
    border-bottom: 1.5px solid var(--grl);
    padding: 0 24px;
    flex-shrink: 0;
    overflow-x: auto;
}
.ac-stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px 12px 0;
    margin-right: 20px;
    border-right: 1px solid var(--grl);
    white-space: nowrap;
}
.ac-stat-item:last-child {
    border-right: none;
    margin-right: 0;
}
.ac-stat-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-gray {
    background: #9ca3af;
}
.dot-red {
    background: #ef4444;
}
.dot-blue {
    background: #3b82f6;
}
.dot-green {
    background: #22c55e;
}
.ac-stat-val {
    font-size: 18px;
    font-weight: 900;
    color: var(--dk);
    line-height: 1;
}
.ac-stat-lbl {
    font-size: 12px;
    color: var(--gr);
    font-weight: 500;
}

/* Filtres panel gauche */
.ac-panel-filters {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px;
    flex-shrink: 0;
    border-bottom: 1px solid var(--grl);
}
.ac-select-full {
    width: 100%;
}
.amis-search-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 0 10px;
    flex: 1;
    min-width: 160px;
}
.amis-search-icon {
    font-size: 12px;
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
    padding: 8px 0;
}
.amis-select {
    border: 1.5px solid var(--grl);
    background: #f8f4f0;
    border-radius: 8px;
    padding: 7px 10px;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    color: var(--dk);
    outline: none;
    cursor: pointer;
}
.amis-select:focus {
    border-color: var(--or);
}

/* Tabs */
.amis-tabs {
    display: flex;
    overflow-x: auto;
    padding: 0 12px;
    gap: 0;
    flex-shrink: 0;
    border-bottom: 1.5px solid var(--grl);
}
.amis-tab {
    padding: 8px 12px;
    background: none;
    border: none;
    border-radius: 0;
    font-family: "Poppins", sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 4px;
    border-bottom: 2px solid transparent;
    transition: all 0.18s;
}
.amis-tab.active {
    color: var(--or);
    border-bottom-color: var(--or);
}
.amis-tab-count {
    background: var(--grl);
    border-radius: 99px;
    padding: 1px 6px;
    font-size: 10px;
    color: var(--gr);
}
.amis-tab.active .amis-tab-count {
    background: var(--or);
    color: #fff;
}

/* Liste tickets */
.ac-ticket-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}
.ac-ticket-skeleton {
    height: 88px;
    border-radius: 10px;
    background: linear-gradient(90deg, #f0e9e4 25%, #e8ddd4 50%, #f0e9e4 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    margin-bottom: 6px;
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.ac-ticket-item {
    padding: 12px;
    border-radius: 10px;
    border: 1.5px solid var(--grl);
    margin-bottom: 6px;
    cursor: pointer;
    transition: all 0.18s;
    background: var(--wh);
}
.ac-ticket-item:hover {
    border-color: var(--or);
    background: var(--or3);
}
.ac-ticket-item.active {
    border-color: var(--or);
    background: var(--or3);
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.15);
}
.ac-ticket-item.unread {
    border-left: 3px solid var(--or);
}

.ac-ticket-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}
.ac-ticket-user {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ac-ticket-subject {
    font-size: 12.5px;
    color: var(--dk);
    font-weight: 500;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-ticket-preview {
    font-size: 12px;
    color: var(--gr);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.ac-preview-who {
    flex-shrink: 0;
}
.ac-ticket-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 11px;
    color: var(--grm);
}
.ac-urgent-chip {
    background: #fee2e2;
    color: #991b1b;
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 10.5px;
    font-weight: 700;
}
.ac-unread-chip {
    background: var(--or);
    color: #fff;
    border-radius: 99px;
    padding: 1px 7px;
    font-size: 10.5px;
    font-weight: 700;
}

/* Pagination */
.ac-pagination {
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 10px 12px;
    justify-content: center;
    flex-shrink: 0;
    border-top: 1px solid var(--grl);
}
.ac-page-btn {
    min-width: 30px;
    height: 30px;
    border-radius: 7px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-family: "Poppins", sans-serif;
    font-size: 12px;
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

/* ── Panel droit : chat ── */
.ac-chat-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

.ac-chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: var(--gr);
}
.ac-chat-empty-icon {
    font-size: 52px;
}
.ac-chat-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.ac-chat-empty-sub {
    font-size: 13px;
}

.ac-chat-header {
    padding: 14px 20px;
    border-bottom: 1.5px solid var(--grl);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--wh);
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 10px;
}
.ac-chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.ac-chat-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ac-chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
}
.ac-chat-name {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.ac-chat-sub {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
}

.ac-ticket-info-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 8px 20px;
    background: #f8f4f0;
    border-bottom: 1px solid var(--grl);
    font-size: 12px;
    color: var(--gr);
    flex-shrink: 0;
}

.ac-messages-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.ac-msg-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}
.ac-msg-row {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.ac-msg-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--grl);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.ac-msg-content {
    flex: 1;
    min-width: 0;
}
.ac-msg-sender-name {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--gr);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ac-msg-time {
    font-size: 11px;
    font-weight: 400;
    color: var(--grm);
}
.ac-msg-bubble {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13.5px;
    line-height: 1.6;
    max-width: 520px;
    word-break: break-word;
}
.bubble-user {
    background: #f0e9e4;
    color: var(--dk);
    border-bottom-left-radius: 4px;
}
.bubble-ia {
    background: #eff6ff;
    color: #1e3a5f;
    border-bottom-left-radius: 4px;
}
.bubble-agent {
    background: linear-gradient(135deg, var(--or3), #fef3e2);
    color: var(--dk);
    border-left: 3px solid var(--or);
    border-bottom-left-radius: 4px;
}
.ac-msg-user .ac-msg-row {
    flex-direction: row-reverse;
}
.ac-msg-empty {
    text-align: center;
    color: var(--gr);
    font-size: 13px;
    font-style: italic;
    margin: auto;
}

.ac-reply-bar {
    padding: 12px 16px;
    border-top: 1.5px solid var(--grl);
    background: var(--wh);
    flex-shrink: 0;
}
.ac-reply-input {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    resize: none;
    background: #f8f4f0;
    box-sizing: border-box;
    margin-bottom: 8px;
}
.ac-reply-input:focus {
    border-color: var(--or);
    background: #fff;
}
.ac-reply-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ac-reply-hint {
    font-size: 11px;
    color: var(--grm);
}
.ac-reply-closed {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f8f4f0;
    border-top: 1.5px solid var(--grl);
    font-size: 13px;
    color: var(--gr);
    gap: 10px;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.ac-note-section {
    padding: 10px 16px 14px;
    border-top: 1px dashed var(--grl);
    background: #fffdf7;
    flex-shrink: 0;
}
.ac-note-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--gr);
    margin-bottom: 6px;
}
.ac-note-input {
    width: 100%;
    border: 1.5px solid #f0e0a0;
    border-radius: 9px;
    padding: 8px 12px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: var(--dk);
    outline: none;
    resize: none;
    background: #fffdf0;
    box-sizing: border-box;
    margin-bottom: 6px;
}
.ac-note-input:focus {
    border-color: #f0c040;
    background: #fff;
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
.amis-badge.urgent {
    background: #fee2e2;
    color: #991b1b;
}
.amis-badge.info {
    background: #dbeafe;
    color: #1e40af;
}
.amis-badge.cancelled {
    background: #f3f4f6;
    color: #6b7280;
}
.amis-badge.ghost {
    background: var(--grl);
    color: var(--gr);
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
    background: #16a34a;
    color: #fff;
}
.amis-btn-green:hover {
    background: #15803d;
}
.amis-btn-red {
    background: #dc2626;
    color: #fff;
}
.amis-btn-red:hover {
    background: #b91c1c;
}
.amis-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}
.amis-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

/* ── Empty / Error ── */
.amis-empty {
    text-align: center;
    padding: 40px 20px;
}
.amis-empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}
.amis-empty-title {
    font-size: 15px;
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
    border-radius: 10px;
    padding: 12px 16px;
    color: #dc2626;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px;
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
