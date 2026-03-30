<template>
    <div class="chat-overlay" @click.self="$emit('close')">
        <div class="chat-modal">
            <!-- ── HEADER ── -->
            <div class="chat-header">
                <div class="chat-header-left">
                    <div class="chat-av">
                        {{ otherInitials }}
                    </div>
                    <div>
                        <div class="chat-other-name">
                            {{ conversation?.other_name ?? "…" }}
                        </div>
                        <div
                            class="chat-mission-title"
                            v-if="conversation?.mission_service"
                        >
                            📋 {{ conversation.mission_service }}
                            <span
                                class="chat-mission-status"
                                v-if="conversation.mission_status"
                            >
                                ·
                                {{
                                    missionStatusLabel(
                                        conversation.mission_status,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="chat-typing" v-if="false">
                            est en train d'écrire…
                        </div>
                    </div>
                </div>
                <div class="chat-header-right">
                    <div
                        class="chat-masked-badge"
                        title="Numéro masqué — utilisez la messagerie"
                    >
                        🔒 Numéro masqué
                    </div>
                    <button class="chat-close-btn" @click="$emit('close')">
                        &#215;
                    </button>
                </div>
            </div>

            <!-- ── MESSAGES ── -->
            <div class="chat-messages" ref="messagesEl">
                <div class="chat-loading" v-if="loading">
                    <div class="chat-spinner-lg"></div>
                    <span>Chargement…</span>
                </div>

                <div class="chat-empty" v-else-if="messages.length === 0">
                    <div class="chat-empty-icon">💬</div>
                    <div>Aucun message pour l'instant.</div>
                    <div class="chat-empty-sub">
                        Envoyez le premier message !
                    </div>
                </div>

                <template v-else>
                    <!-- Groupe par date -->
                    <template
                        v-for="group in groupedMessages"
                        :key="group.date"
                    >
                        <div class="chat-date-sep">
                            <span>{{ group.date }}</span>
                        </div>
                        <div
                            class="chat-row"
                            v-for="m in group.msgs"
                            :key="m.id"
                            :class="{
                                'chat-row-mine': m.sender_id === currentUserId,
                                'chat-row-system': m.type === 'system',
                            }"
                        >
                            <div
                                class="chat-bubble"
                                :class="{
                                    'chat-bubble-mine':
                                        m.sender_id === currentUserId,
                                    'chat-bubble-system': m.type === 'system',
                                }"
                            >
                                <!-- Nom expéditeur (messages reçus) -->
                                <div
                                    class="chat-sender-name"
                                    v-if="
                                        m.sender_id !== currentUserId &&
                                        m.type !== 'system'
                                    "
                                >
                                    {{ m.sender_name }}
                                </div>
                                <!-- Texte -->
                                <div class="chat-body" v-if="m.body">
                                    {{ m.body }}
                                </div>
                                <!-- Image -->
                                <div
                                    class="chat-attachment-img"
                                    v-if="
                                        m.type === 'image' && m.attachment_url
                                    "
                                >
                                    <img
                                        :src="m.attachment_url"
                                        :alt="m.attachment_name"
                                        @click="openImage(m.attachment_url)"
                                    />
                                </div>
                                <!-- Audio -->
                                <div
                                    class="chat-attachment-audio"
                                    v-if="m.type === 'audio' && m.attachment_url"
                                >
                                    <span class="chat-file-icon">🎵</span>
                                    <audio controls :src="m.attachment_url" preload="metadata"></audio>
                                </div>
                                <!-- Fichier -->
                                <a
                                    class="chat-attachment-file"
                                    v-if="m.type === 'file' && m.attachment_url"
                                    :href="m.attachment_url"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <span class="chat-file-icon">📎</span>
                                    <span class="chat-file-name">{{
                                        m.attachment_name
                                    }}</span>
                                    <span class="chat-file-dl">↓</span>
                                </a>
                                <!-- Heure + statut -->
                                <div class="chat-meta">
                                    <span class="chat-time">{{
                                        formatTime(m.created_at)
                                    }}</span>
                                    <span
                                        class="chat-tick chat-tick-sent"
                                        v-if="
                                            m.sender_id === currentUserId &&
                                            m.id < lastMessageId
                                        "
                                        >✓✓</span
                                    >
                                    <span
                                        class="chat-tick"
                                        v-else-if="
                                            m.sender_id === currentUserId
                                        "
                                        >✓</span
                                    >
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>

            <!-- ── PREVIEW PIÈCE JOINTE ── -->
            <div class="chat-attachment-preview" v-if="attachmentPreview">
                <div class="chat-preview-content">
                    <img
                        v-if="attachmentPreview.isImage"
                        :src="attachmentPreview.url"
                    />
                    <div v-else-if="attachmentPreview.isAudio" class="chat-preview-file">
                        <span>🎵</span> {{ attachmentPreview.name }}
                    </div>
                    <div v-else class="chat-preview-file">
                        <span>📎</span> {{ attachmentPreview.name }}
                    </div>
                </div>
                <button class="chat-preview-remove" @click="clearAttachment">
                    &#215;
                </button>
            </div>

            <!-- ── BARRE DE SAISIE ── -->
            <div class="chat-input-bar">
                <div
                    class="chat-attach-btn"
                    title="Joindre une photo ou un fichier"
                    @click="$refs.fileInput.click()"
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
                    @change="onFileSelect"
                    ref="fileInput"
                />

                <textarea
                    class="chat-input"
                    ref="inputEl"
                    v-model="draft"
                    placeholder="Votre message…"
                    rows="1"
                    @keydown.enter.exact.prevent="send"
                    @keydown.enter.shift.exact="draft += '\n'"
                    @input="autoResize"
                ></textarea>

                <button
                    class="chat-send-btn"
                    @click="send"
                    :disabled="(!draft.trim() && !pendingFile) || sending"
                >
                    <div class="chat-mini-spinner" v-if="sending"></div>
                    <svg v-else viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Lightbox image -->
        <div
            class="chat-lightbox"
            v-if="lightboxUrl"
            @click="lightboxUrl = null"
        >
            <img :src="lightboxUrl" />
        </div>
    </div>
</template>

<script>
export default {
    name: "MissionChatModal",

    props: {
        missionId: { type: Number, required: true },
        currentUserId: { type: Number, required: true },
        routes: { type: Object, required: true },
        // routes doit contenir :
        // conversations_mission : '/conversations/mission/{id}'
        // conversations_messages: '/conversations/{id}/messages'
        // conversations_send    : '/conversations/{id}/messages'
        // conversations_attach  : '/conversations/{id}/attachment'
        // conversations_read    : '/conversations/{id}/read'
    },

    emits: ["close", "unread"],

    data() {
        return {
            conversation: null,
            messages: [],
            loading: true,
            sending: false,
            draft: "",
            pendingFile: null,
            attachmentPreview: null,
            lightboxUrl: null,
            pollInterval: null,
            lastMessageId: 0,
            otherReadAt: null,
            unreadFromOther: 0,
        };
    },

    computed: {
        otherInitials() {
            const name = this.conversation?.other_name ?? "";
            return (
                name
                    .split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) || "??"
            );
        },

        groupedMessages() {
            // Retourne un tableau [{date, msgs}] trié — objet {} ne garantit pas l'ordre
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
        // ── Init ─────────────────────────────────────────────────
        async init() {
            console.log(
                "[Chat] init — missionId:",
                this.missionId,
                "| currentUserId:",
                this.currentUserId,
            );
            console.log("[Chat] routes:", this.routes);
            this.loading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.conversations_mission.replace(
                    "{id}",
                    this.missionId,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({}),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    console.error("[Chat] init HTTP", res.status, ":", err);
                    throw new Error("HTTP " + res.status);
                }
                const data = await res.json();
                this.conversation = data.conversation;
                // Trier ASC par id pour garantir l'ordre chronologique
                const msgs = (data.messages ?? [])
                    .slice()
                    .sort((a, b) => a.id - b.id);
                this.messages = msgs;
                this.lastMessageId = msgs.at(-1)?.id ?? 0;
                await this.$nextTick();
                await this.scrollBottom(true);
            } catch (e) {
                console.error("[Chat] init error:", e);
            } finally {
                this.loading = false;
                // Scroll après que loading=false laisse Vue rendre les messages
                setTimeout(() => this.scrollBottom(), 100);
            }
        },

        // ── Polling ───────────────────────────────────────────────
        async poll() {
            if (!this.conversation || this.sending) return; // ne pas polluer pendant un envoi
            try {
                const url =
                    this.routes.conversations_messages.replace(
                        "{id}",
                        this.conversation.id,
                    ) + `?after_id=${this.lastMessageId}`;
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.messages?.length > 0) {
                    // Dédupliquer — évite le doublon si le message arrive pendant l'envoi
                    const existingIds = new Set(this.messages.map((m) => m.id));
                    const newMsgs = data.messages.filter(
                        (m) => !existingIds.has(m.id),
                    );
                    if (newMsgs.length > 0) {
                        const fromOther = newMsgs.filter(
                            (m) => m.sender_id !== this.currentUserId,
                        );
                        this.messages.push(...newMsgs);
                        this.lastMessageId = newMsgs.at(-1).id;
                        await this.scrollBottom();
                        // Notification title si fenêtre non active
                        if (fromOther.length > 0 && document.hidden) {
                            this.unreadFromOther += fromOther.length;
                            this.$emit("unread", this.unreadFromOther);
                        }
                    }
                }
            } catch {
                /* silencieux */
            }
        },

        // ── Envoi texte ───────────────────────────────────────────
        async send() {
            console.log(
                "[Chat] send — draft:",
                this.draft,
                "| conversation:",
                this.conversation?.id,
                "| pendingFile:",
                !!this.pendingFile,
            );
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
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.conversations_send.replace(
                    "{id}",
                    this.conversation.id,
                );
                console.log("[Chat] POST", url, "| body:", body);
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ body }),
                });
                const data = await res.json();
                console.log("[Chat] send response HTTP", res.status, ":", data);
                if (!res.ok) {
                    console.error("[Chat] send error:", data);
                    this.draft = body;
                    return;
                }
                this.messages.push(data.message);
                this.lastMessageId = data.message.id;
                await this.scrollBottom();
            } catch (e) {
                console.error("[Chat] send JS error:", e);
                this.draft = body;
            } finally {
                this.sending = false;
            }
        },

        // ── Envoi fichier ─────────────────────────────────────────
        onFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.pendingFile = file;
            const isImage = file.type.startsWith("image/");
            const isAudio = file.type.startsWith("audio/");
            if (isImage) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    this.attachmentPreview = {
                        url: ev.target.result,
                        name: file.name,
                        isImage: true,
                        isAudio: false,
                    };
                };
                reader.readAsDataURL(file);
            } else {
                this.attachmentPreview = {
                    url: null,
                    name: file.name,
                    isImage: false,
                    isAudio,
                };
            }
        },

        clearAttachment() {
            this.pendingFile = null;
            this.attachmentPreview = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = "";
        },

        async sendAttachment() {
            if (!this.pendingFile || this.sending) return;
            this.sending = true;
            const formData = new FormData();
            formData.append("file", this.pendingFile);
            const file = this.pendingFile;
            this.clearAttachment();
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.conversations_attach.replace(
                    "{id}",
                    this.conversation.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: formData,
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    console.error("[Chat] init HTTP", res.status, ":", err);
                    throw new Error("HTTP " + res.status);
                }
            } catch (e) {
                console.error("[Chat] attachment error:", e);
            } finally {
                this.sending = false;
            }
        },

        // ── Helpers ───────────────────────────────────────────────
        isReadByOther(message) {
            // Considéré "lu" si l'autre participant a une last_read_at après le message
            return false; // simplifié MVP — à améliorer avec last_read_at du participant
        },

        openImage(url) {
            this.lightboxUrl = url;
        },

        async scrollBottom(instant = false) {
            // Attendre que Vue ait fini de rendre le DOM
            await this.$nextTick();
            const tryScroll = (attempts = 5) => {
                const el = this.$refs.messagesEl;
                if (!el) return;
                el.scrollTop = el.scrollHeight + 9999;
                // Réessayer plusieurs fois pour couvrir les images/lazy renders
                if (attempts > 0) {
                    setTimeout(() => tryScroll(attempts - 1), 40);
                }
            };
            tryScroll();
        },

        autoResize() {
            const el = this.$refs.inputEl;
            if (!el) return;
            el.style.height = "auto";
            el.style.height = Math.min(el.scrollHeight, 120) + "px";
        },

        formatTime(iso) {
            return new Date(iso).toLocaleTimeString("fr-FR", {
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
            });
        },

        missionStatusLabel(s) {
            const map = {
                pending: "En attente",
                assigned: "Proposée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi en cours",
                in_progress: "En cours",
                quote_submitted: "Devis soumis",
                order_placed: "Devis approuvé",
                awaiting_confirm: "Att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return map[s] ?? s;
        },
    },

    async mounted() {
        await this.init();
        this.pollInterval = setInterval(() => this.poll(), 3000);
        this.$nextTick(() => this.$refs.inputEl?.focus());
        this._onFocus = () => {
            this.unreadFromOther = 0;
            this.$emit("unread", 0);
        };
        window.addEventListener("focus", this._onFocus);
    },

    beforeUnmount() {
        clearInterval(this.pollInterval);
        window.removeEventListener("focus", this._onFocus);
    },
};
</script>

<style scoped>
/* ── OVERLAY ── */
.chat-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

/* ── MODAL ── */
.chat-modal {
    background: #fff;
    border-radius: 18px;
    width: 100%;
    max-width: 520px;
    height: 82vh;
    max-height: 680px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    animation: chat-slide-up 0.25s ease;
    overflow: hidden;
    font-family: "Poppins", sans-serif;
}
@keyframes chat-slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ── HEADER ── */
.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    flex-shrink: 0;
}
.chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.chat-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.chat-av {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.chat-other-name {
    font-size: 14.5px;
    font-weight: 800;
    color: #fff;
}
.chat-mission-title {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.85);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-mission-status {
    color: rgba(255, 255, 255, 0.7);
}
.chat-typing {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.8);
    font-style: italic;
    margin-top: 2px;
}
.chat-masked-badge {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border-radius: 99px;
    padding: 3px 10px;
    font-size: 10.5px;
    font-weight: 700;
    white-space: nowrap;
}
.chat-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.35);
}

/* ── MESSAGES ── */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 16px 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    background: #f8f4f0;
}
.chat-loading {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #78716c;
    font-size: 13px;
}
.chat-spinner-lg {
    width: 28px;
    height: 28px;
    border: 3px solid #e8ddd4;
    border-top-color: #f97316;
    border-radius: 50%;
    animation: chat-spin 0.7s linear infinite;
}
@keyframes chat-spin {
    to {
        transform: rotate(360deg);
    }
}
.chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #78716c;
    font-size: 13px;
    text-align: center;
}
.chat-empty-icon {
    font-size: 40px;
}
.chat-empty-sub {
    font-size: 12px;
    color: #8a7d78;
}

/* Date séparateur */
.chat-date-sep {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 14px 0 8px;
    font-size: 11.5px;
    color: #8a7d78;
    font-weight: 600;
}
.chat-date-sep span {
    background: #e8ddd4;
    border-radius: 99px;
    padding: 2px 12px;
    white-space: nowrap;
}

/* Bulles */
.chat-row {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 6px;
    width: 100%;
}
.chat-row-mine {
    justify-content: flex-end;
}
.chat-row-system {
    justify-content: center;
}

.chat-bubble {
    max-width: 72%;
    padding: 9px 12px;
    border-radius: 16px 16px 16px 4px;
    background: #fff;
    color: #1c1412;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
}
.chat-bubble-mine {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    border-radius: 16px 16px 4px 16px;
}
.chat-bubble-system {
    background: #fef3c7;
    color: #92400e;
    font-size: 12px;
    border-radius: 8px;
    max-width: 90%;
    text-align: center;
}
.chat-sender-name {
    font-size: 11px;
    font-weight: 700;
    color: #f97316;
    margin-bottom: 3px;
}
.chat-bubble-mine .chat-sender-name {
    color: rgba(255, 255, 255, 0.8);
}
.chat-body {
    white-space: pre-wrap;
}
.chat-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 3px;
}
.chat-time {
    font-size: 10px;
    opacity: 0.6;
}
.chat-tick {
    font-size: 10px;
    opacity: 0.6;
}
.chat-tick-sent {
    opacity: 1;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 700;
}

/* Audio */
.chat-attachment-audio {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
}
.chat-attachment-audio audio {
    max-width: 220px;
    height: 36px;
}

/* Pièce jointe */
.chat-attachment-img img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    cursor: pointer;
    display: block;
    transition: opacity 0.15s;
}
.chat-attachment-img img:hover {
    opacity: 0.9;
}
.chat-attachment-file {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.chat-attachment-file:hover {
    background: rgba(0, 0, 0, 0.14);
}
.chat-file-icon {
    font-size: 18px;
}
.chat-file-name {
    flex: 1;
    font-size: 12.5px;
    font-weight: 600;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.chat-file-dl {
    font-size: 11px;
    opacity: 0.7;
    white-space: nowrap;
}

/* ── PREVIEW PIÈCE JOINTE ── */
.chat-attachment-preview {
    border-top: 1px solid #e8ddd4;
    padding: 8px 12px;
    background: #faf7f5;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.chat-preview-content {
    flex: 1;
    min-width: 0;
}
.chat-preview-content img {
    max-height: 64px;
    border-radius: 6px;
    display: block;
}
.chat-preview-file {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #1c1412;
}
.chat-preview-remove {
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

/* ── SAISIE ── */
.chat-input-bar {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 14px;
    border-top: 1px solid #e8ddd4;
    background: #fff;
    flex-shrink: 0;
}
.chat-attach-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    flex-shrink: 0;
    background: #f5f0eb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
    align-self: flex-end;
}
.chat-attach-btn:hover {
    background: #fff7ed;
}
.chat-attach-btn svg {
    width: 18px;
    height: 18px;
    color: #7c6a5a;
}
.chat-input {
    flex: 1;
    border: 1.5px solid #e8ddd4;
    border-radius: 12px;
    padding: 9px 13px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: #1c1412;
    resize: none;
    min-height: 38px;
    max-height: 120px;
    transition: border 0.15s;
    line-height: 1.4;
    overflow-y: auto;
}
.chat-input:focus {
    outline: none;
    border-color: #f97316;
}
.chat-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    flex-shrink: 0;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.18s;
    align-self: flex-end;
}
.chat-send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}
.chat-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.chat-send-btn svg {
    width: 18px;
    height: 18px;
    fill: #fff;
}
.chat-mini-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: chat-spin 0.7s linear infinite;
}

/* ── LIGHTBOX ── */
.chat-lightbox {
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
.chat-lightbox img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
}

/* ── RESPONSIVE ── */
@media (max-width: 540px) {
    .chat-overlay {
        padding: 0;
        align-items: flex-end;
    }
    .chat-modal {
        max-width: 100%;
        border-radius: 18px 18px 0 0;
        height: 90vh;
        max-height: 90vh;
    }
    .chat-masked-badge {
        display: none;
    }
}
</style>
