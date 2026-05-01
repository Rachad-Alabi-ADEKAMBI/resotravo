<template>
    <div class="msg-wrap">
        <!-- -------------- TOPBAR -------------- -->
        <div class="msg-topbar">
            <div class="msg-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="msg-page-title">Messages</div>
                    <div class="msg-page-sub">
                        <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="msg-topbar-right">
                <!-- Cloche notifications -->
                <div class="msg-notif-wrap" ref="notifWrap">
                    <button class="msg-notif-btn" @click="toggleNotif">
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
                        <span
                            class="msg-notif-badge"
                            v-if="unreadNotifCount > 0"
                        >
                            {{ unreadNotifCount }}
                        </span>
                    </button>
                    <div class="msg-notif-dropdown" v-if="notifOpen">
                        <div class="msg-notif-header">
                            <span>Notifications</span>
                            <button
                                class="msg-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadNotifCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="msg-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="msg-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="msg-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="msg-notif-dot" v-if="!n.read"></div>
                            <div class="msg-notif-content">
                                <div class="msg-notif-title">{{ n.title }}</div>
                                <div class="msg-notif-body">{{ n.body }}</div>
                                <div class="msg-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Avatar avec photo de profil -->
                <div class="msg-avatar">
                    <img
                        v-if="photoUrl"
                        :src="photoUrl"
                        :alt="user.name"
                        class="msg-avatar-img"
                    />
                    <span v-else>{{ userInitials }}</span>
                </div>
            </div>
        </div>

        <!-- -------------- BODY -------------- -->
        <div class="msg-body">
            <!-- ---- COLONNE GAUCHE — liste conversations ---- -->
            <div
                class="msg-left"
                :class="{ 'msg-left-hidden': activeConv }"
            >
                <!-- Recherche -->
                <div class="msg-search-wrap">
                    <span class="msg-search-icon">&#128269;</span>
                    <input
                        class="msg-search"
                        type="text"
                        v-model="search"
                        placeholder="Rechercher…"
                    />
                </div>

                <!-- Loader -->
                <div class="msg-conv-list" v-if="convLoading">
                    <div class="msg-skeleton" v-for="n in 5" :key="n"></div>
                </div>

                <!-- Erreur -->
                <div class="msg-state-block" v-else-if="convError">
                    <div class="msg-state-icon">&#9888;</div>
                    <div class="msg-state-text">{{ convError }}</div>
                    <button class="msg-btn-ghost" @click="fetchConversations">
                        Réessayer
                    </button>
                </div>

                <!-- Vide -->
                <div
                    class="msg-state-block"
                    v-else-if="filteredConvs.length === 0"
                >
                    <div class="msg-state-icon">&#128172;</div>
                    <div class="msg-state-title">Aucune conversation</div>
                    <div class="msg-state-sub">
                        Vos échanges avec vos interlocuteurs apparaîtront ici.
                    </div>
                </div>

                <!-- Liste -->
                <div class="msg-conv-list" v-else>
                    <div
                        class="msg-conv-item"
                        v-for="c in filteredConvs"
                        :key="c.id"
                        :class="{
                            'msg-conv-active': activeConv?.id === c.id,
                            'msg-conv-unread': c.unread_count > 0,
                        }"
                        @click.prevent.stop="selectConversation(c)"
                        @keydown.enter.prevent="selectConversation(c)"
                        role="button"
                        tabindex="0"
                    >
                        <div class="msg-conv-av" :class="{ 'has-photo': c.other_photo_url }">
                            <img
                                v-if="c.other_photo_url"
                                :src="c.other_photo_url"
                                :alt="c.other_name"
                                class="msg-other-avatar-img"
                            />
                            <span v-else>{{ initials(c.other_name) }}</span>
                            <span
                                class="msg-conv-badge"
                                v-if="c.unread_count > 0"
                            >
                                {{ c.unread_count > 9 ? "9+" : c.unread_count }}
                            </span>
                        </div>
                        <div class="msg-conv-info">
                            <div class="msg-conv-name">{{ c.other_name }}</div>
                            <div
                                class="msg-conv-mission"
                                v-if="c.mission_service"
                            >
                                <span aria-hidden="true">&#128736;</span> {{ c.mission_service }}
                                <span v-if="c.mission_id" class="msg-mission-id"
                                    >#{{ c.mission_id }}</span
                                >
                                <span
                                    class="msg-mission-chip"
                                    :class="missionChipClass(c.mission_status)"
                                >
                                    {{ missionStatusLabel(c.mission_status) }}
                                </span>
                            </div>
                            <div
                                class="msg-conv-address"
                                v-if="c.mission_address"
                            >
                                <span aria-hidden="true">&#128205;</span> {{ c.mission_address }}
                            </div>
                            <div class="msg-conv-last">
                                {{ c.last_message ?? "Aucun message" }}
                            </div>
                        </div>
                        <div class="msg-conv-time">
                            {{ c.last_message_at ?? "" }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ---- COLONNE DROITE — zone chat ---- -->
            <div class="msg-right" :class="{ 'msg-right-active': activeConv }">
                <!-- État vide — aucune conversation sélectionnée -->
                <div class="msg-no-conv" v-if="!activeConv">
                    <div class="msg-no-conv-icon">&#128172;</div>
                    <div class="msg-no-conv-title">
                        Sélectionnez une conversation
                    </div>
                    <div class="msg-no-conv-sub">
                        Choisissez une conversation à gauche pour afficher les
                        messages.
                    </div>
                </div>

                <!-- -- Chat actif -- -->
                <div class="msg-chat-panel" v-else>
                    <!-- Header chat -->
                    <div class="msg-chat-header">
                        <button
                            class="msg-back-btn"
                            v-if="isMobile"
                            @click="activeConv = null"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="15 18 9 12 15 6" />
                            </svg>
                        </button>
                        <div class="msg-chat-av" :class="{ 'has-photo': activeConv.other_photo_url }">
                            <img
                                v-if="activeConv.other_photo_url"
                                :src="activeConv.other_photo_url"
                                :alt="activeConv.other_name"
                                class="msg-other-avatar-img"
                            />
                            <span v-else>{{ initials(activeConv.other_name) }}</span>
                        </div>
                        <div class="msg-chat-info">
                            <div class="msg-chat-name">
                                {{ activeConv.other_name }}
                            </div>
                            <div
                                class="msg-chat-mission"
                                v-if="activeConv.mission_service"
                            >
                                <span aria-hidden="true">&#128736;</span> {{ activeConv.mission_service }}
                                <span
                                    v-if="activeConv.mission_id"
                                    class="msg-mission-id"
                                    >#{{ activeConv.mission_id }}</span
                                >
                                <span
                                    class="msg-mission-chip"
                                    :class="
                                        missionChipClass(
                                            activeConv.mission_status
                                        )
                                    "
                                >
                                    {{
                                        missionStatusLabel(
                                            activeConv.mission_status
                                        )
                                    }}
                                </span>
                            </div>
                            <div
                                class="msg-chat-address"
                                v-if="activeConv.mission_address"
                            >
                                <span aria-hidden="true">&#128205;</span> {{ activeConv.mission_address }}
                            </div>
                        </div>
                        <div
                            class="msg-masked-badge"
                            title="Numéro masqué — utilisez la messagerie"
                        >
                            Numéro masqué
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="msg-messages" ref="messagesEl">
                        <div class="msg-chat-loading" v-if="chatLoading">
                            <div class="msg-spinner"></div>
                            <span>Chargement…</span>
                        </div>

                        <div class="msg-chat-error" v-else-if="chatError">
                            <div style="font-size: 28px">&#9888;</div>
                            <div style="font-weight: 600; margin: 6px 0 4px">
                                Erreur de chargement
                            </div>
                            <div
                                style="
                                    font-size: 13px;
                                    color: #888;
                                    margin-bottom: 12px;
                                "
                            >
                                {{ chatError }}
                            </div>
                            <button
                                class="msg-btn-ghost"
                                @click="openConversation(activeConv, true)"
                            >
                                Réessayer
                            </button>
                        </div>

                        <div
                            class="msg-chat-empty"
                            v-else-if="messages.length === 0"
                        >
                            <div>&#128172;</div>
                            <div>Aucun message. Envoyez le premier !</div>
                        </div>

                        <template v-else>
                            <template
                                v-for="group in groupedMessages"
                                :key="group.date"
                            >
                                <div class="msg-date-sep">
                                    <span>{{ group.date }}</span>
                                </div>
                                <div
                                    class="msg-row"
                                    v-for="m in group.msgs"
                                    :key="m.id"
                                    :class="{
                                        'msg-row-mine': m.sender_id === user.id,
                                        'msg-row-system': m.type === 'system',
                                    }"
                                >
                                    <div
                                        class="msg-bubble"
                                        :class="{
                                            'msg-bubble-mine':
                                                m.sender_id === user.id,
                                            'msg-bubble-system':
                                                m.type === 'system',
                                        }"
                                    >
                                        <div
                                            class="msg-sender-name"
                                            v-if="
                                                m.sender_id !== user.id &&
                                                m.type !== 'system'
                                            "
                                        >
                                            {{
                                                m.sender?.name ??
                                                activeConv.other_name
                                            }}
                                        </div>
                                        <!-- Texte -->
                                        <div
                                            class="msg-body-text"
                                            v-if="m.body"
                                        >
                                            {{ m.body }}
                                        </div>
                                        <!-- Image -->
                                        <div
                                            class="msg-img-wrap"
                                            v-if="
                                                m.type === 'image' &&
                                                m.attachment_url
                                            "
                                        >
                                            <img
                                                :src="m.attachment_url"
                                                :alt="m.attachment_name"
                                                @click="
                                                    lightboxUrl =
                                                        m.attachment_url
                                                "
                                            />
                                        </div>
                                        <!-- Audio -->
                                        <div
                                            class="msg-audio-wrap"
                                            v-if="
                                                m.type === 'audio' &&
                                                m.attachment_url
                                            "
                                        >
                                            <span>Audio</span>
                                            <audio
                                                controls
                                                :src="m.attachment_url"
                                                preload="metadata"
                                            ></audio>
                                        </div>
                                        <!-- Fichier -->
                                        <a
                                            class="msg-file-row"
                                            v-if="
                                                m.type === 'file' &&
                                                m.attachment_url
                                            "
                                            :href="m.attachment_url"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <span>Fichier</span>
                                            <span class="msg-file-name">{{
                                                m.attachment_name
                                            }}</span>
                                            <span>?</span>
                                        </a>
                                        <!-- Meta -->
                                        <div class="msg-meta">
                                            <span class="msg-time">{{
                                                formatTime(m.created_at)
                                            }}</span>
                                            <span
                                                class="msg-tick msg-tick-read"
                                                v-if="
                                                    m.sender_id === user.id &&
                                                    m.id < lastMsgId
                                                "
                                                >Lu</span
                                            >
                                            <span
                                                class="msg-tick"
                                                v-else-if="
                                                    m.sender_id === user.id
                                                "
                                                >?</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>

                    <!-- Preview pièce jointe -->
                    <div class="msg-attach-preview" v-if="attachPreview">
                        <div class="msg-preview-inner">
                            <img
                                v-if="attachPreview.isImage"
                                :src="attachPreview.url"
                            />
                            <div
                                v-else-if="attachPreview.isAudio"
                                class="msg-preview-file"
                            >
                                Audio : {{ attachPreview.name }}
                            </div>
                            <div v-else class="msg-preview-file">
                                Fichier : {{ attachPreview.name }}
                            </div>
                        </div>
                        <button class="msg-preview-remove" @click="clearAttach">
                            &#215;
                        </button>
                    </div>

                    <!-- Barre de saisie -->
                    <div class="msg-input-bar">
                        <div
                            class="msg-attach-btn"
                            @click="$refs.fileInput.click()"
                            title="Joindre un fichier"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"
                                />
                            </svg>
                        </div>
                        <input
                            type="file"
                            style="display: none"
                            accept="image/*,.pdf,.doc,.docx,audio/*,.mp3,.wav,.ogg,.m4a,.aac"
                            ref="fileInput"
                            @change="onFileSelect"
                        />
                        <textarea
                            class="msg-input"
                            ref="inputEl"
                            v-model="draft"
                            placeholder="Votre message…"
                            rows="1"
                            @keydown.enter.exact.prevent="send"
                            @keydown.enter.shift.exact="draft += '\n'"
                            @input="autoResize"
                        ></textarea>
                        <button
                            class="msg-send-btn"
                            @click="send"
                            :disabled="
                                (!draft.trim() && !pendingFile) || sending
                            "
                        >
                            <div class="msg-mini-spinner" v-if="sending"></div>
                            <svg v-else viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <div
            class="msg-lightbox"
            v-if="lightboxUrl"
            @click="lightboxUrl = null"
        >
            <img :src="lightboxUrl" />
        </div>
    </div>
</template>

<script>
export default {
    name: "MessagesComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
        openConversationId: { type: Number, default: null },
    },

    data() {
        return {
            // Conversations
            conversations: [],
            convLoading: true,
            convError: null,
            search: "",

            // Chat actif
            activeConv: null,
            messages: [],
            chatLoading: false,
            chatError: null,
            sending: false,
            draft: "",
            pendingFile: null,
            attachPreview: null,
            lastMsgId: 0,
            pollInterval: null,
            lightboxUrl: null,

            isMobile: window.innerWidth < 768,
            sidebarOpen: false,

            // Notifications
            notifications: [],
            unreadNotifCount: 0,
            notifOpen: false,
            notifLoading: false,

            // Photo de profil
            photoUrl: null,
        };
    },

    computed: {
        userInitials() {
            return (
                (this.user.name ?? "")
                    .split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) || "--"
            );
        },
        totalUnread() {
            return this.conversations.reduce(
                (s, c) => s + (c.unread_count ?? 0),
                0
            );
        },
        filteredConvs() {
            if (!this.search.trim()) return this.conversations;
            const q = this.search.toLowerCase();
            return this.conversations.filter(
                (c) =>
                    c.other_name?.toLowerCase().includes(q) ||
                    c.mission_service?.toLowerCase().includes(q) ||
                    c.last_message?.toLowerCase().includes(q)
            );
        },
        groupedMessages() {
            const map = new Map();
            for (const m of this.messages) {
                const key = this.formatDateLabel(new Date(m.created_at));
                if (!map.has(key)) map.set(key, []);
                map.get(key).push(m);
            }
            return Array.from(map.entries()).map(([date, msgs]) => ({
                date,
                msgs,
            }));
        },
    },

    methods: {
        // -- Initiales --------------------------------------------
        initials(name) {
            return (name ?? "--")
                .split(" ")
                .map((w) => w[0])
                .join("")
                .toUpperCase()
                .slice(0, 2);
        },

        // -- Fetch conversations -----------------------------------
        async fetchConversations() {
            this.convLoading = true;
            this.convError = null;
            try {
                const res = await fetch(this.routes.conversations_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("HTTP " + res.status);
                this.conversations = await res.json();

                // Ouvrir directement si openConversationId passé
                if (this.openConversationId) {
                    const c = this.conversations.find(
                        (c) => c.id === this.openConversationId
                    );
                    if (c) this.selectConversation(c);
                }
            } catch (e) {
                this.convError = "Impossible de charger les conversations.";
            } finally {
                this.convLoading = false;
            }
        },

        // -- Selectionner une conversation --------------------------
        selectConversation(conv) {
            if (!conv) return;
            this.activeConv = conv;
            this.$nextTick(() => this.openConversation(conv, true));
        },

        // -- Ouvrir une conversation -------------------------------
        async openConversation(conv, force = false) {
            if (!conv) return;
            if (!force && this.activeConv?.id === conv.id && !this.chatError) return;

            this.stopPolling();
            this.messages = [];
            this.chatError = null;
            this.lastMsgId = 0;
            this.draft = "";
            this.chatLoading = true;

            const abort = new AbortController();
            const timeoutId = setTimeout(() => abort.abort(), 10000); // 10s max
            try {
                const url = this.routes.conversations_messages.replace(
                    "{id}",
                    conv.id
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                    signal: abort.signal,
                });
                console.log(
                    "[MessagesComponent] fetchMessages status:",
                    res.status
                );

                // Lire le texte brut d'abord pour diagnostiquer sans bloquer
                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch {
                    console.error(
                        "[MessagesComponent] Réponse non-JSON reçue — probablement une redirection ou erreur HTML. Début:",
                        rawText.slice(0, 200)
                    );
                    throw new Error(
                        "Le serveur a retourné une page HTML au lieu de JSON (statut " +
                            res.status +
                            "). Vérifiez la route et l'authentification."
                    );
                }

                if (!res.ok)
                    throw new Error(
                        "HTTP " + res.status + " : " + (data?.message ?? "")
                    );
                console.log("[MessagesComponent] fetchMessages data:", data);
                const msgs = (data.messages ?? [])
                    .slice()
                    .sort((a, b) => a.id - b.id);
                this.messages = msgs;
                this.lastMsgId = msgs.at(-1)?.id ?? 0;

                // Marquer comme lu
                conv.unread_count = 0;
                this.markRead(conv.id);

                await this.scrollBottomAfterRender(true);
            } catch (e) {
                if (e.name === "AbortError") {
                    console.error(
                        "[MessagesComponent] Timeout : le serveur n'a pas répondu en 10s"
                    );
                    this.chatError =
                        "Le serveur ne répond pas. Vérifiez la route de messagerie.";
                } else {
                    console.error(
                        "[MessagesComponent] openConversation error:",
                        e.message
                    );
                    this.chatError =
                        e.message || "Impossible de charger les messages.";
                }
            } finally {
                clearTimeout(timeoutId);
                this.chatLoading = false;
                setTimeout(() => this.scrollBottom(true), 150);
                this.startPolling();
            }
        },

        // -- Polling -----------------------------------------------
        startPolling() {
            this.pollInterval = setInterval(() => this.poll(), 3000);
        },
        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },
        async poll() {
            if (!this.activeConv || this.sending) return;
            try {
                const url =
                    this.routes.conversations_messages.replace(
                        "{id}",
                        this.activeConv.id
                    ) + `?after_id=${this.lastMsgId}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.messages?.length > 0) {
                    const existing = new Set(this.messages.map((m) => m.id));
                    const newMsgs = data.messages.filter(
                        (m) => !existing.has(m.id)
                    );
                    if (newMsgs.length > 0) {
                        this.messages.push(...newMsgs);
                        this.lastMsgId = newMsgs.at(-1).id;
                        await this.scrollBottomAfterRender();
                    }
                }
            } catch {
                /* silencieux */
            }
        },

        // -- Envoi message -----------------------------------------
        async send() {
            if (this.pendingFile) {
                await this.sendAttachment();
                return;
            }
            if (!this.draft.trim() || this.sending) return;

            this.sending = true;
            const body = this.draft.trim();
            this.draft = "";
            this.$nextTick(() => this.autoResize());

            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.conversations_send.replace(
                    "{id}",
                    this.activeConv.id
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ body }),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.messages.push(data.message);
                this.lastMsgId = data.message.id;
                // Mettre à jour le dernier message dans la liste
                const c = this.conversations.find(
                    (c) => c.id === this.activeConv.id
                );
                if (c) {
                    c.last_message = body;
                    c.last_message_at = "À l'instant";
                }
                await this.scrollBottomAfterRender(true);
            } catch {
                this.draft = body; // restaurer le draft si erreur
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.inputEl?.focus());
            }
        },

        // -- Envoi pièce jointe ------------------------------------
        async sendAttachment() {
            if (!this.pendingFile || this.sending) return;
            this.sending = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.conversations_attach.replace(
                    "{id}",
                    this.activeConv.id
                );
                const form = new FormData();
                form.append("file", this.pendingFile);
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: form,
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.messages.push(data.message);
                this.lastMsgId = data.message.id;
                this.clearAttach();
                await this.scrollBottomAfterRender(true);
            } catch {
                /* silencieux */
            } finally {
                this.sending = false;
            }
        },

        // -- Fichier -----------------------------------------------
        onFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.pendingFile = file;
            const isImage = file.type.startsWith("image/");
            const isAudio = file.type.startsWith("audio/");
            this.attachPreview = {
                name: file.name,
                isImage,
                isAudio,
                url: isImage ? URL.createObjectURL(file) : null,
            };
        },
        clearAttach() {
            this.pendingFile = null;
            this.attachPreview = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = "";
        },

        // -- Marquer comme lu --------------------------------------
        async markRead(convId) {
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                await fetch(
                    this.routes.conversations_read.replace("{id}", convId),
                    {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            Accept: "application/json",
                        },
                    }
                );
            } catch {
                /* silencieux */
            }
        },

        // -- Scroll ------------------------------------------------
        scrollBottom(force = false) {
            const el = this.$refs.messagesEl;
            if (!el) return;
            const nearBottom =
                el.scrollHeight - el.scrollTop - el.clientHeight < 200;
            if (force || nearBottom) {
                el.scrollTop = el.scrollHeight;
            }
        },
        async scrollBottomAfterRender(force = false) {
            await this.$nextTick();
            // Double RAF pour s'assurer que le DOM est complètement rendu
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.scrollBottom(force);
                });
            });
        },

        // -- Textarea auto-resize ----------------------------------
        autoResize() {
            const el = this.$refs.inputEl;
            if (!el) return;
            el.style.height = "auto";
            el.style.height = Math.min(el.scrollHeight, 120) + "px";
        },

        // -- Formatage ---------------------------------------------
        formatTime(dt) {
            if (!dt) return "";
            return new Date(dt).toLocaleTimeString("fr-FR", {
                hour: "2-digit",
                minute: "2-digit",
            });
        },
        formatDateLabel(date) {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            if (date.toDateString() === today.toDateString())
                return "Aujourd'hui";
            if (date.toDateString() === yesterday.toDateString()) return "Hier";
            return date.toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        },

        missionStatusLabel(status) {
            const map = {
                pending: "En attente",
                assigned: "Assignée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                in_progress: "En cours",
                quote_submitted: "Devis soumis",
                order_placed: "Commande",
                awaiting_confirm: "Att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return map[status] ?? status ?? "-";
        },
        missionChipClass(status) {
            if (["completed", "closed"].includes(status)) return "chip-green";
            if (status === "cancelled") return "chip-red";
            if (status === "pending") return "chip-amber";
            return "chip-blue";
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
        },

        // -- Notifications -----------------------------------------
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadNotifCount = data.unread_count ?? 0;
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
            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;
            try {
                await fetch(this.routes.notifications_all, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf,
                    },
                });
                this.notifications.forEach((n) => (n.read = true));
                this.unreadNotifCount = 0;
            } catch {}
        },
        openNotif(n) {
            n.read = true;
            this.unreadNotifCount = Math.max(0, this.unreadNotifCount - 1);
            this.notifOpen = false;
        },
        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            ) {
                this.notifOpen = false;
            }
        },

        // -- Photo de profil ---------------------------------------
        loadPhoto() {
            const url = this.routes.profile_photo;
            if (!url) return;
            const img = new Image();
            img.onload = () => {
                this.photoUrl = url;
            };
            img.onerror = () => {
                this.photoUrl = null;
            };
            img.src = url + "?t=" + Date.now();
        },
    },

    mounted() {
        this.fetchConversations();
        this.fetchNotifications();
        this.loadPhoto();
        window.addEventListener("resize", () => {
            this.isMobile = window.innerWidth < 768;
        });
        document.addEventListener("click", this.handleClickOutside);
    },
    beforeUnmount() {
        this.stopPolling();
        document.removeEventListener("click", this.handleClickOutside);
    },
};
</script>

<style scoped>
/* -------------------------------------------
   VARIABLES & BASE
------------------------------------------- */
.msg-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grl: #e8ddd4;
    --wh: #fff;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: #f8f4f0;
    height: 100vh; /* hauteur fixe — empêche le scroll global */
    overflow: hidden; /* AUCUN scroll sur le wrapper */
    display: flex;
    flex-direction: column;
}

/* -------------------------------------------
   TOPBAR
------------------------------------------- */
.msg-topbar {
    background: var(--wh);
    border-bottom: 2px solid var(--grl);
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    position: sticky;
    top: 0;
    z-index: 50;
    flex-shrink: 0;
}
.msg-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.msg-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.msg-page-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--dk);
}
.msg-page-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 1px;
}
.msg-unread-pill {
    background: var(--or);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
}
.msg-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.msg-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* -- NOTIFICATIONS -- */
.msg-notif-wrap {
    position: relative;
}
.msg-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 8px;
    transition: background 0.15s;
}
.msg-notif-btn:hover {
    background: #f5f0eb;
}
.msg-notif-btn svg {
    width: 22px;
    height: 22px;
}
.msg-notif-badge {
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
    padding: 0 3px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.msg-notif-dropdown {
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
.msg-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
}
.msg-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.msg-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.msg-notif-item:hover,
.msg-notif-item.unread {
    background: var(--or3);
}
.msg-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.msg-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.msg-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.msg-notif-ago {
    font-size: 11px;
    color: var(--gr);
    margin-top: 3px;
}
.msg-notif-loading,
.msg-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--gr);
    text-align: center;
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
}
.ad-burger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
}

/* -------------------------------------------
   BODY SPLIT VIEW
------------------------------------------- */
.msg-body {
    display: flex;
    flex: 1;
    min-height: 0; /* CRUCIAL pour que flex respecte overflow des enfants */
    overflow: hidden;
}

/* -- COLONNE GAUCHE -- */
.msg-left {
    width: 340px;
    flex-shrink: 0;
    background: var(--wh);
    border-right: 2px solid var(--grl);
    display: flex;
    flex-direction: column;
    overflow: hidden; /* la liste enfant scrolle, pas la colonne entière */
    min-height: 0;
    height: 100%; /* remplit toute la hauteur du body */
}

/* Recherche */
.msg-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
}
.msg-search-icon {
    font-size: 15px;
    color: var(--gr);
}
.msg-search {
    flex: 1;
    border: none;
    outline: none;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    background: transparent;
}

/* Liste conversations */
.msg-conv-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}
.msg-conv-item {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 13px 14px;
    cursor: pointer;
    border-bottom: 1px solid var(--grl);
    transition: background 0.15s;
    position: relative;
}
.msg-conv-item:hover {
    background: #faf7f4;
}
.msg-conv-active {
    background: var(--or3) !important;
    border-left: 3px solid var(--or);
}
.msg-conv-unread .msg-conv-name {
    font-weight: 800;
}
.msg-conv-unread .msg-conv-last {
    color: var(--dk);
    font-weight: 600;
}

.msg-conv-av {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}
.msg-conv-av.has-photo,
.msg-chat-av.has-photo {
    background: #fff;
}
.msg-other-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.msg-conv-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    min-width: 17px;
    height: 17px;
    border-radius: 99px;
    padding: 0 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
}
.msg-conv-info {
    flex: 1;
    min-width: 0;
}
.msg-conv-name {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.msg-conv-mission {
    font-size: 11px;
    color: var(--gr);
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.msg-conv-last {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.msg-conv-address {
    font-size: 11px;
    color: var(--gr);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.msg-mission-id {
    font-size: 10px;
    font-weight: 700;
    color: var(--or);
    background: var(--or3);
    padding: 1px 6px;
    border-radius: 99px;
}
.msg-conv-time {
    font-size: 11px;
    color: var(--gr);
    white-space: nowrap;
    flex-shrink: 0;
}

/* Chips statut mission */
.msg-mission-chip {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 99px;
    white-space: nowrap;
}
.chip-green {
    background: #dcfce7;
    color: #15803d;
}
.chip-red {
    background: #fee2e2;
    color: #b91c1c;
}
.chip-amber {
    background: #fef3c7;
    color: #92400e;
}
.chip-blue {
    background: #dbeafe;
    color: #1d4ed8;
}

/* -- ÉTAT VIDE / ERREUR -- */
.msg-state-block {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    text-align: center;
}
.msg-state-icon {
    font-size: 36px;
}
.msg-state-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.msg-state-sub {
    font-size: 12.5px;
    color: var(--gr);
    line-height: 1.6;
}
.msg-state-text {
    font-size: 13px;
    color: var(--gr);
}
.msg-btn-ghost {
    margin-top: 8px;
    padding: 7px 16px;
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    background: none;
    font-family: "Poppins", sans-serif;
    font-size: 12.5px;
    cursor: pointer;
    color: var(--gr);
    transition: border-color 0.15s;
}
.msg-btn-ghost:hover {
    border-color: var(--or);
    color: var(--or);
}

/* Skeletons */
.msg-skeleton {
    height: 68px;
    margin: 0 14px 1px;
    background: linear-gradient(
        90deg,
        var(--grl) 25%,
        #f0ebe5 50%,
        var(--grl) 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 10px;
}
@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* -- COLONNE DROITE -- */
.msg-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden; /* le scroll est uniquement sur .msg-messages */
    background: #f8f4f0;
    min-width: 0;
    min-height: 0;
    height: 100%; /* remplit toute la hauteur du body */
}
.msg-chat-panel {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

/* Pas de conv sélectionnée */
.msg-no-conv {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-align: center;
    padding: 40px;
}
.msg-no-conv-icon {
    font-size: 56px;
    opacity: 0.25;
}
.msg-no-conv-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--dk);
}
.msg-no-conv-sub {
    font-size: 13px;
    color: var(--gr);
    line-height: 1.6;
    max-width: 320px;
}

/* Header chat */
.msg-chat-header {
    background: var(--wh);
    border-bottom: 2px solid var(--grl);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.msg-back-btn {
    background: none;
    border: none;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    flex-shrink: 0;
}
.msg-back-btn svg {
    width: 20px;
    height: 20px;
    stroke: var(--dk);
}
.msg-back-btn:hover {
    background: var(--grl);
}
.msg-chat-av {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.msg-chat-info {
    flex: 1;
    min-width: 0;
}
.msg-chat-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.msg-chat-mission {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.msg-chat-address {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
}
.msg-masked-badge {
    font-size: 11.5px;
    font-weight: 600;
    background: #f1f5f9;
    color: #64748b;
    padding: 4px 10px;
    border-radius: 99px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Zone messages */
.msg-messages {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    scroll-behavior: smooth;
}
.msg-chat-loading,
.msg-chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--gr);
    font-size: 13.5px;
}
.msg-spinner {
    width: 28px;
    height: 28px;
    border: 3px solid var(--grl);
    border-top-color: var(--or);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Séparateur date */
.msg-date-sep {
    text-align: center;
    position: relative;
    margin: 12px 0 6px;
}
.msg-date-sep span {
    background: #e9e2db;
    color: var(--gr);
    font-size: 11px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 99px;
}

/* Rows & Bubbles */
.msg-row {
    display: flex;
}
.msg-row-mine {
    justify-content: flex-end;
}
.msg-row-system {
    justify-content: center;
}
.msg-bubble {
    max-width: 72%;
    padding: 9px 13px;
    background: var(--wh);
    border: 1px solid var(--grl);
    border-radius: 16px 16px 16px 4px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
}
.msg-bubble-mine {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border: none;
    border-radius: 16px 16px 4px 16px;
}
.msg-bubble-system {
    background: #fef3c7;
    color: #92400e;
    border: none;
    font-size: 12px;
    border-radius: 8px;
    max-width: 90%;
    text-align: center;
}
.msg-sender-name {
    font-size: 11px;
    font-weight: 700;
    color: var(--or);
    margin-bottom: 3px;
}
.msg-bubble-mine .msg-sender-name {
    color: rgba(255, 255, 255, 0.8);
}
.msg-body-text {
    white-space: pre-wrap;
}
.msg-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 3px;
}
.msg-time {
    font-size: 10px;
    opacity: 0.6;
}
.msg-tick {
    font-size: 10px;
    opacity: 0.6;
}
.msg-tick-read {
    opacity: 1;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 700;
}

/* Audio */
.msg-audio-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
}
.msg-audio-wrap audio {
    max-width: 220px;
    height: 36px;
}

/* Images & fichiers */
.msg-img-wrap img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    cursor: pointer;
    display: block;
    transition: opacity 0.15s;
}
.msg-img-wrap img:hover {
    opacity: 0.9;
}
.msg-file-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
}
.msg-file-name {
    flex: 1;
    font-size: 12.5px;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Preview pièce jointe */
.msg-attach-preview {
    border-top: 1px solid var(--grl);
    padding: 8px 14px;
    background: #faf7f5;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.msg-preview-inner {
    flex: 1;
    min-width: 0;
}
.msg-preview-inner img {
    max-height: 64px;
    border-radius: 6px;
    display: block;
}
.msg-preview-file {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--dk);
}
.msg-preview-remove {
    background: #fee2e2;
    border: none;
    color: #dc2626;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Barre saisie */
.msg-input-bar {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 14px;
    border-top: 1px solid var(--grl);
    background: var(--wh);
    flex-shrink: 0;
}
.msg-attach-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f5f0eb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
    flex-shrink: 0;
    align-self: flex-end;
}
.msg-attach-btn:hover {
    background: var(--or3);
}
.msg-attach-btn svg {
    width: 18px;
    height: 18px;
    color: var(--gr);
}
.msg-input {
    flex: 1;
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    padding: 9px 13px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    resize: none;
    min-height: 38px;
    max-height: 120px;
    transition: border 0.15s;
    line-height: 1.4;
    overflow-y: auto;
    background: var(--wh);
}
.msg-input:focus {
    outline: none;
    border-color: var(--or);
}
.msg-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--or), var(--or2));
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.18s;
    align-self: flex-end;
}
.msg-send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}
.msg-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.msg-send-btn svg {
    width: 18px;
    height: 18px;
    fill: #fff;
}
.msg-mini-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

/* Lightbox */
.msg-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 20px;
}
.msg-lightbox img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
}

/* -------------------------------------------
   RESPONSIVE MOBILE
------------------------------------------- */
@media (max-width: 767px) {
    .msg-topbar {
        height: 64px;
        padding: 0 12px;
        gap: 8px;
    }
    .msg-topbar-left {
        flex: 1;
        min-width: 0;
        gap: 8px;
    }
    .msg-topbar-left > div {
        min-width: 0;
    }
    .msg-page-title,
    .msg-page-sub {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .msg-page-title {
        font-size: 15px;
    }
    .msg-topbar-right {
        flex-shrink: 0;
        gap: 8px;
    }
    .msg-notif-btn {
        padding: 4px;
    }
    .msg-avatar {
        width: 34px;
        height: 34px;
    }
    .msg-left {
        width: 100%;
        border-right: none;
    }
    .msg-left-hidden {
        display: none;
    }
    .msg-right {
        display: none;
        width: 100%;
    }
    .msg-right-active {
        display: flex;
    }
    .msg-masked-badge {
        display: none;
    }
    .msg-bubble {
        max-width: 88%;
    }
}

/* -- NOTIFICATIONS -- */
.msg-notif-wrap {
    position: relative;
}
.msg-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 8px;
    transition: background 0.15s;
}
.msg-notif-btn:hover {
    background: #f5f0eb;
}
.msg-notif-btn svg {
    width: 22px;
    height: 22px;
}
.msg-notif-badge {
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
    padding: 0 3px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.msg-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 300px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
}
.msg-notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
}
.msg-notif-read-all {
    background: none;
    border: none;
    font-size: 12px;
    color: var(--or);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.msg-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.msg-notif-item:hover,
.msg-notif-item.unread {
    background: var(--or3);
}
.msg-notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 5px;
}
.msg-notif-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.msg-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.msg-notif-ago {
    font-size: 11px;
    color: #8a7d78;
    margin-top: 3px;
}
.msg-notif-loading,
.msg-notif-empty {
    padding: 16px;
    font-size: 13px;
    color: var(--gr);
    text-align: center;
}

/* -- AVATAR PHOTO -- */
.msg-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}
.msg-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
</style>


