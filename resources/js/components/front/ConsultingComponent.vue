<template>
    <div class="consulting-page">
        <!-- ═══════════ HERO ═══════════ -->
        <section class="ac-hero">
            <div class="ac-hero-glow"></div>
            <div class="ac-hero-glow2"></div>
            <div class="ac-hero-dots"></div>
            <div class="ac-hero-inner">
                <div class="ac-badge au">
                    <span class="bdot"></span>
                    Gratuit pour tous les inscrits · Disponible 24h/24
                </div>
                <h1 class="au1">
                    Allô Conseils<br />
                    <span class="hl">votre assistant</span><br />
                    Mesotravo
                </h1>
                <p class="ac-hero-desc au2">
                    Posez vos questions et obtenez une orientation immédiate.<br />
                    En cas de besoin, un agent humain Mesotravo prend le relais.
                </p>
                <div class="ac-hero-steps au3">
                    <div class="ac-step-pill">
                        <span class="ac-step-num">1</span>
                        <span>Agent IA répond (3 premiers messages)</span>
                    </div>
                    <div class="ac-step-arrow">&rarr;</div>
                    <div class="ac-step-pill">
                        <span class="ac-step-num">2</span>
                        <span>Transfert agent humain si besoin</span>
                    </div>
                    <div class="ac-step-arrow">&rarr;</div>
                    <div class="ac-step-pill">
                        <span class="ac-step-num">3</span>
                        <span>Historique conservé dans votre espace</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ CHAT ═══════════ -->
        <section class="ac-chat-section" id="chat">
            <div class="ac-chat-layout">
                <!-- ── Sidebar gauche ── -->
                <div class="ac-sidebar">
                    <div class="ac-sidebar-header">
                        <h3>Mes conversations</h3>
                        <button class="ac-new-btn" @click="startNewTicket">
                            + Nouveau
                        </button>
                    </div>

                    <!-- Loader tickets -->
                    <div class="ac-conv-loading" v-if="ticketsLoading">
                        <div class="ac-conv-skel" v-for="n in 3" :key="n"></div>
                    </div>

                    <!-- Non connecté -->
                    <div class="ac-conv-guest" v-else-if="!isAuthenticated">
                        <div class="ac-conv-guest-icon">🔒</div>
                        <p>Connectez-vous pour consulter vos conversations.</p>
                        <a
                            class="ac-new-btn"
                            :href="routes.login"
                            style="
                                display: inline-block;
                                text-align: center;
                                text-decoration: none;
                                margin-top: 8px;
                            "
                            >Se connecter</a
                        >
                    </div>

                    <!-- Liste tickets -->
                    <div class="ac-conv-list" v-else>
                        <div
                            class="ac-conv-item"
                            v-for="ticket in tickets"
                            :key="ticket.id"
                            :class="{
                                active: activeTicket?.id === ticket.id,
                                unread: ticket.unread_count > 0,
                            }"
                            @click="openTicket(ticket)"
                        >
                            <div class="ac-conv-icon">
                                {{ categoryIcon(ticket.category) }}
                            </div>
                            <div class="ac-conv-info">
                                <div class="ac-conv-title">
                                    {{
                                        ticket.subject ||
                                        "Nouvelle conversation"
                                    }}
                                </div>
                                <div class="ac-conv-preview">
                                    {{ ticket.last_message || "Aucun message" }}
                                </div>
                            </div>
                            <div class="ac-conv-meta">
                                <span
                                    class="ac-conv-badge"
                                    v-if="ticket.unread_count > 0"
                                    >{{ ticket.unread_count }}</span
                                >
                                <span
                                    class="ac-conv-status"
                                    :class="'status-' + ticket.status"
                                    >{{ ticket.status_label }}</span
                                >
                            </div>
                        </div>

                        <div class="ac-conv-empty" v-if="tickets.length === 0">
                            <p>
                                Aucune conversation.<br />Commencez ci-dessous !
                            </p>
                        </div>
                    </div>

                    <!-- Agent card -->
                    <div class="ac-agent-card">
                        <div
                            class="ac-agent-avatar"
                            :class="{ human: activeTicket?.human_assigned }"
                        >
                            {{ activeTicket?.human_assigned ? "👨‍💼" : "🤖" }}
                        </div>
                        <div>
                            <div class="ac-agent-name">
                                {{
                                    activeTicket?.human_assigned
                                        ? "Agent Mesotravo"
                                        : "Agent IA Mesotravo"
                                }}
                            </div>
                            <div class="ac-agent-status">
                                <span
                                    class="ac-online-dot"
                                    :class="{
                                        human: activeTicket?.human_assigned,
                                    }"
                                ></span>
                                En ligne
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Chat principal ── -->
                <div class="ac-chat-main">
                    <!-- En-tête -->
                    <div class="ac-chat-header">
                        <div class="ac-chat-header-left">
                            <div class="ac-chat-avatar">
                                {{ activeTicket?.human_assigned ? "👨‍💼" : "🤖" }}
                            </div>
                            <div>
                                <div class="ac-chat-name">
                                    {{
                                        activeTicket?.human_assigned
                                            ? "Agent Mesotravo"
                                            : "Agent IA Mesotravo"
                                    }}
                                </div>
                                <div class="ac-chat-mode">
                                    <span
                                        class="ac-online-dot"
                                        :class="{
                                            human: activeTicket?.human_assigned,
                                        }"
                                    ></span>
                                    <span v-if="activeTicket?.human_assigned"
                                        >Agent humain · Historique
                                        transmis</span
                                    >
                                    <span v-else-if="activeTicket"
                                        >Message
                                        {{ activeTicket.ia_message_count }}/3 ·
                                        IA automatique</span
                                    >
                                    <span v-else
                                        >Démarrez une conversation</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="ac-chat-header-right">
                            <button
                                class="ac-transfer-btn"
                                v-if="
                                    activeTicket &&
                                    !activeTicket.human_assigned &&
                                    !activeTicket.human_requested
                                "
                                @click="requestHuman"
                                :disabled="transferLoading"
                            >
                                <span v-if="transferLoading">...</span>
                                <span v-else>Parler à un humain</span>
                            </button>
                            <div
                                class="ac-human-badge"
                                v-else-if="
                                    activeTicket?.human_requested &&
                                    !activeTicket?.human_assigned
                                "
                            >
                                ⏳ En attente d'un agent
                            </div>
                            <div
                                class="ac-human-badge ac-human-badge-active"
                                v-else-if="activeTicket?.human_assigned"
                            >
                                ✅ Agent connecté
                            </div>
                            <span
                                class="ac-ticket-status"
                                v-if="activeTicket"
                                :class="'status-' + activeTicket.status"
                            >
                                {{ activeTicket.status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Zone messages -->
                    <div class="ac-messages" ref="messagesContainer">
                        <!-- Écran d'accueil (pas de ticket actif) -->
                        <div class="ac-welcome" v-if="!activeTicket">
                            <div class="ac-welcome-icon">💬</div>
                            <h3>{{ userName ? userName : "Bienvenue" }} !</h3>
                            <p>
                                Je suis l'assistant Mesotravo. Comment puis-je
                                vous aider ?
                            </p>
                            <div class="ac-suggestions">
                                <button
                                    class="ac-suggestion"
                                    v-for="s in suggestions"
                                    :key="s"
                                    @click="startWithSuggestion(s)"
                                >
                                    {{ s }}
                                </button>
                            </div>
                        </div>

                        <!-- Messages du ticket actif -->
                        <template v-else>
                            <!-- Message de bienvenue si vide -->
                            <div
                                class="ac-msg ac-msg-bot"
                                v-if="
                                    activeMessages.length === 0 &&
                                    !messagesLoading
                                "
                            >
                                <div class="ac-msg-avatar">🤖</div>
                                <div class="ac-msg-bubble">
                                    <p>
                                        Bonjour ! Je suis l'assistant Mesotravo.
                                        Comment puis-je vous aider ?
                                    </p>
                                    <div class="ac-suggestions">
                                        <button
                                            class="ac-suggestion"
                                            v-for="s in suggestions"
                                            :key="s"
                                            @click="sendSuggestion(s)"
                                        >
                                            {{ s }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Loader messages -->
                            <div
                                class="ac-msg-loading-wrap"
                                v-if="messagesLoading"
                            >
                                <div
                                    class="ac-msg-skel"
                                    v-for="n in 3"
                                    :key="n"
                                ></div>
                            </div>

                            <!-- Messages réels -->
                            <template v-else>
                                <div
                                    v-for="msg in activeMessages"
                                    :key="msg.id"
                                    class="ac-msg"
                                    :class="
                                        msg.sender_type === 'user'
                                            ? 'ac-msg-user'
                                            : 'ac-msg-bot'
                                    "
                                >
                                    <!-- Bot / Agent -->
                                    <template v-if="msg.sender_type !== 'user'">
                                        <div class="ac-msg-avatar">
                                            {{
                                                msg.sender_type === "ia"
                                                    ? "🤖"
                                                    : "👨‍💼"
                                            }}
                                        </div>
                                        <div
                                            class="ac-msg-bubble"
                                            :class="{
                                                'ac-msg-bubble-human':
                                                    msg.sender_type === 'agent',
                                            }"
                                        >
                                            <div
                                                class="ac-msg-sender"
                                                v-if="
                                                    msg.sender_type === 'agent'
                                                "
                                            >
                                                {{
                                                    msg.sender_name ||
                                                    "Agent Mesotravo"
                                                }}
                                            </div>
                                            {{ msg.body }}
                                            <div class="ac-msg-time">
                                                {{ msg.ago }}
                                            </div>
                                        </div>
                                    </template>
                                    <!-- User -->
                                    <template v-else>
                                        <div
                                            class="ac-msg-bubble ac-msg-bubble-user"
                                        >
                                            {{ msg.body }}
                                            <div class="ac-msg-time">
                                                {{ msg.ago }}
                                            </div>
                                        </div>
                                        <div
                                            class="ac-msg-avatar ac-msg-avatar-user"
                                        >
                                            👤
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Typing indicator -->
                            <div class="ac-msg ac-msg-bot" v-if="isTyping">
                                <div class="ac-msg-avatar">🤖</div>
                                <div class="ac-msg-bubble ac-typing">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>

                            <!-- Notice transfert -->
                            <div
                                class="ac-transfer-notice"
                                v-if="showTransferNotice"
                            >
                                <div class="ac-transfer-inner">
                                    <span
                                        >⏳ Transfert vers un agent
                                        humain...</span
                                    >
                                    <span>Tout l'historique est transmis.</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Barre de saisie -->
                    <div class="ac-input-bar">
                        <!-- Non connecté -->
                        <div class="ac-guest-prompt" v-if="!isAuthenticated">
                            <p>
                                <a :href="routes.login">Connectez-vous</a> ou
                                <a :href="routes.register">créez un compte</a>
                                pour enregistrer vos conversations.
                            </p>
                        </div>

                        <!-- Ticket résolu -->
                        <div
                            class="ac-resolved-bar"
                            v-else-if="
                                activeTicket &&
                                ['resolved', 'closed'].includes(
                                    activeTicket.status
                                )
                            "
                        >
                            <span
                                >🔒 Ce ticket est
                                {{
                                    activeTicket.status === "resolved"
                                        ? "résolu"
                                        : "fermé"
                                }}.</span
                            >
                            <button class="ac-new-btn" @click="startNewTicket">
                                Nouvelle conversation
                            </button>
                        </div>

                        <!-- Saisie normale -->
                        <template v-else>
                            <div class="ac-input-wrap">
                                <input
                                    class="ac-input"
                                    type="text"
                                    v-model="userInput"
                                    @keyup.enter="sendMessage"
                                    placeholder="Posez votre question..."
                                    :disabled="isTyping || sendLoading"
                                    ref="inputField"
                                />
                                <button
                                    class="ac-send-btn"
                                    @click="sendMessage"
                                    :disabled="
                                        !userInput.trim() ||
                                        isTyping ||
                                        sendLoading
                                    "
                                >
                                    <span v-if="!sendLoading && !isTyping"
                                        >&rarr;</span
                                    >
                                    <span v-else>...</span>
                                </button>
                            </div>
                            <div class="ac-input-hint">
                                <span
                                    v-if="
                                        activeTicket &&
                                        !activeTicket.human_assigned
                                    "
                                >
                                    {{
                                        Math.max(
                                            0,
                                            3 -
                                                (activeTicket.ia_message_count ||
                                                    0)
                                        )
                                    }}
                                    message(s) avant transfert automatique
                                </span>
                                <span v-else-if="activeTicket?.human_assigned">
                                    Vous êtes en ligne avec un agent Mesotravo
                                </span>
                                <span v-else
                                    >Appuyez sur Entrée pour envoyer</span
                                >
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ COMMENT ÇA MARCHE ═══════════ -->
        <section class="sec sec-cr" id="comment-conseils">
            <div class="sec-tag reveal">Comment ça marche</div>
            <div class="sec-title reveal reveal-d1">Un conseil en 3 étapes</div>
            <div class="sec-sub reveal reveal-d2">
                Simple, rapide et gratuit pour tous les utilisateurs inscrits.
            </div>
            <div class="ac-how-grid">
                <div
                    class="ac-how-card reveal"
                    v-for="(step, i) in howSteps"
                    :key="i"
                    :class="'reveal-d' + (i + 1)"
                >
                    <div class="ac-how-num">{{ i + 1 }}</div>
                    <div class="ac-how-icon">{{ step.icon }}</div>
                    <h4>{{ step.title }}</h4>
                    <p>{{ step.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ═══════════ CAS D'USAGE ═══════════ -->
        <section class="sec">
            <div class="sec-tag reveal">Exemples de questions</div>
            <div class="sec-title reveal reveal-d1">
                Allô Conseils répond à tout
            </div>
            <div class="sec-sub reveal reveal-d2">
                Orientation, choix du bon prestataire, aide à la rédaction d'un
                appel d'offres...
            </div>
            <div class="ac-usecases-grid">
                <div
                    class="ac-usecase reveal"
                    v-for="(uc, i) in useCases"
                    :key="i"
                    :class="'reveal-d' + ((i % 4) + 1)"
                    @click="startWithSuggestion(uc.question)"
                >
                    <div class="ac-usecase-icon">{{ uc.icon }}</div>
                    <div class="ac-usecase-q">{{ uc.question }}</div>
                    <div class="ac-usecase-tag">{{ uc.tag }}</div>
                    <div class="ac-usecase-arrow">
                        Poser cette question &rarr;
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ CTA ═══════════ -->
        <div class="cta-final">
            <div class="cta-inner reveal">
                <h2>Besoin d'un conseil ?</h2>
                <p>
                    Allô Conseils est gratuit et accessible à tout moment depuis
                    votre espace personnel.
                </p>
                <div class="cta-btns">
                    <a class="btn btn-dark btn-lg" :href="routes.register"
                        >Créer un compte gratuit &rarr;</a
                    >
                    <a class="btn btn-ghost btn-lg" :href="routes.login"
                        >Se connecter</a
                    >
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div class="ac-toast" :class="{ visible: toastVisible }">
            {{ toastMsg }}
        </div>
    </div>
</template>

<script>
export default {
    name: "ConsultingComponent",

    props: {
        routes: {
            type: Object,
            default: () => ({
                register: "/register",
                login: "/login",
                tickets_store: "/consulting/tickets",
                tickets_index: "/consulting/tickets",
                tickets_messages: "/consulting/tickets/{ticket}/messages",
                tickets_send: "/consulting/tickets/{ticket}/send",
                tickets_human: "/consulting/tickets/{ticket}/request-human",
            }),
        },
        auth: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            // État auth
            isAuthenticated: !!this.auth,
            userName: this.auth?.name ?? null,

            // Tickets
            tickets: [],
            ticketsLoading: false,
            activeTicket: null,

            // Messages du ticket actif
            activeMessages: [],
            messagesLoading: false,

            // Saisie
            userInput: "",
            sendLoading: false,
            isTyping: false,

            // Transfert agent humain
            transferLoading: false,
            showTransferNotice: false,

            // Polling
            pollInterval: null,

            // Toast
            toastVisible: false,
            toastMsg: "",

            suggestions: [
                "Quel prestataire pour réparer mon disjoncteur ?",
                "Comment fonctionne le paiement MoMo ?",
                "Je veux publier un appel d'offres",
                "Quel est le délai pour trouver un prestataire ?",
            ],

            howSteps: [
                {
                    icon: "💬",
                    title: "Posez votre question",
                    desc: "Décrivez votre besoin. L'agent IA comprend et répond immédiatement.",
                },
                {
                    icon: "🤖",
                    title: "Réponse instantanée",
                    desc: "L'IA traite vos 3 premiers messages et vous oriente vers le bon service.",
                },
                {
                    icon: "🧑",
                    title: "Transfert agent humain",
                    desc: "Après 3 messages ou sur demande, un agent Mesotravo prend le relais.",
                },
            ],

            useCases: [
                {
                    icon: "🔧",
                    question: "Quel prestataire pour réparer mon disjoncteur ?",
                    tag: "Orientation",
                },
                {
                    icon: "📋",
                    question:
                        "Comment rédiger un appel d'offres pour mon entreprise ?",
                    tag: "Appels d'offres",
                },
                {
                    icon: "👷",
                    question:
                        "Comment devenir prestataire certifié sur Mesotravo ?",
                    tag: "Inscription",
                },
                {
                    icon: "💸",
                    question:
                        "Comment fonctionne le paiement après les travaux ?",
                    tag: "Paiement",
                },
                {
                    icon: "📍",
                    question: "Puis-je choisir moi-même mon prestataire ?",
                    tag: "Géolocalisation",
                },
                {
                    icon: "🛡️",
                    question: "C'est quoi l'accréditation ENTREPRISE ?",
                    tag: "Accréditation",
                },
                {
                    icon: "⭐",
                    question:
                        "Comment noter un prestataire après une intervention ?",
                    tag: "Notation",
                },
                {
                    icon: "🌿",
                    question: "Est-ce que Mesotravo couvre le jardinage ?",
                    tag: "Services",
                },
            ],
        };
    },

    mounted() {
        this.$nextTick(() => this.reObserveReveal());
        if (this.isAuthenticated) this.fetchTickets();
    },

    beforeUnmount() {
        clearInterval(this.pollInterval);
    },

    methods: {
        // ── Tickets ────────────────────────────────────────────────

        async fetchTickets() {
            this.ticketsLoading = true;
            try {
                const res = await fetch(this.routes.tickets_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.tickets = data.data ?? data;
            } catch {
            } finally {
                this.ticketsLoading = false;
            }
        },

        async openTicket(ticket) {
            this.activeTicket = ticket;
            this.messagesLoading = true;
            this.activeMessages = [];
            clearInterval(this.pollInterval);
            this.scrollToBottom();

            try {
                const url = this.routes.tickets_messages.replace(
                    "{ticket}",
                    ticket.id
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeMessages = data.messages ?? [];
                this.activeTicket = data.ticket ?? ticket;
                // Mettre à jour dans la liste
                const idx = this.tickets.findIndex((t) => t.id === ticket.id);
                if (idx !== -1) this.tickets.splice(idx, 1, this.activeTicket);
            } catch {
                this.showToast("Impossible de charger les messages.");
            } finally {
                this.messagesLoading = false;
                this.$nextTick(() => this.scrollToBottom());
            }

            // Polling toutes les 10s pour les réponses agent
            this.pollInterval = setInterval(() => this.pollMessages(), 10000);
        },

        async pollMessages() {
            if (!this.activeTicket) return;
            try {
                const url = this.routes.tickets_messages.replace(
                    "{ticket}",
                    this.activeTicket.id
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.messages?.length !== this.activeMessages.length) {
                    this.activeMessages = data.messages;
                    this.activeTicket = data.ticket ?? this.activeTicket;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch {}
        },

        // ── Nouveau ticket ─────────────────────────────────────────

        startNewTicket() {
            this.activeTicket = null;
            this.activeMessages = [];
            clearInterval(this.pollInterval);
            this.$nextTick(() => this.$refs.inputField?.focus());
        },

        startWithSuggestion(text) {
            this.startNewTicket();
            this.userInput = text;
            this.$nextTick(() => {
                document
                    .getElementById("chat")
                    ?.scrollIntoView({ behavior: "smooth" });
                setTimeout(() => this.sendMessage(), 300);
            });
        },

        sendSuggestion(text) {
            this.userInput = text;
            this.sendMessage();
        },

        // ── Envoyer message ────────────────────────────────────────

        async sendMessage() {
            const text = this.userInput.trim();
            if (!text || this.sendLoading || this.isTyping) return;
            this.userInput = "";
            this.sendLoading = true;
            this.isTyping = true;

            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            try {
                // Créer le ticket si pas de ticket actif
                if (!this.activeTicket) {
                    if (!this.isAuthenticated) {
                        // Mode invité : simulation locale uniquement
                        this.handleGuestMessage(text);
                        return;
                    }
                    const res = await fetch(this.routes.tickets_store, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                        },
                        body: JSON.stringify({
                            subject: text.slice(0, 80),
                            first_message: text,
                        }),
                    });
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    this.activeTicket = data.ticket;
                    this.tickets.unshift(data.ticket);
                    this.activeMessages = data.messages ?? [];
                    this.isTyping = false;
                    this.sendLoading = false;
                    this.$nextTick(() => this.scrollToBottom());
                    // Polling
                    clearInterval(this.pollInterval);
                    this.pollInterval = setInterval(
                        () => this.pollMessages(),
                        10000
                    );
                    return;
                }

                // Envoyer message sur ticket existant
                const url = this.routes.tickets_send.replace(
                    "{ticket}",
                    this.activeTicket.id
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({ body: text }),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();

                // Ajouter le message utilisateur
                this.activeMessages.push(data.user_message);

                // Mettre à jour le ticket (compteur IA, statut…)
                if (data.ticket) {
                    this.activeTicket = data.ticket;
                    const idx = this.tickets.findIndex(
                        (t) => t.id === data.ticket.id
                    );
                    if (idx !== -1) this.tickets.splice(idx, 1, data.ticket);
                }

                // Réponse IA incluse dans la réponse API
                if (data.ia_message) {
                    setTimeout(() => {
                        this.activeMessages.push(data.ia_message);
                        this.isTyping = false;
                        this.$nextTick(() => this.scrollToBottom());
                        // Vérifier si transfert automatique
                        if (data.ticket?.status === "pending_human") {
                            this.showTransferBanner();
                        }
                    }, 1200);
                } else {
                    this.isTyping = false;
                }

                this.$nextTick(() => this.scrollToBottom());
            } catch {
                this.showToast("Erreur lors de l'envoi. Réessayez.");
                this.isTyping = false;
                this.userInput = text; // remettre le texte
            } finally {
                this.sendLoading = false;
            }
        },

        // Mode invité : simulation locale
        handleGuestMessage(text) {
            this.activeTicket = {
                id: null,
                ia_message_count: 1,
                human_assigned: false,
                human_requested: false,
                status: "open",
                status_label: "Nouveau",
            };
            this.activeMessages.push({
                id: Date.now(),
                sender_type: "user",
                body: text,
                ago: "À l'instant",
            });
            setTimeout(() => {
                this.activeMessages.push({
                    id: Date.now() + 1,
                    sender_type: "ia",
                    body: this.getLocalIAReply(text),
                    ago: "À l'instant",
                });
                this.isTyping = false;
                this.sendLoading = false;
                this.$nextTick(() => this.scrollToBottom());
            }, 1400);
        },

        getLocalIAReply(text) {
            const t = text.toLowerCase();
            if (t.includes("plomb"))
                return "Pour un problème de plomberie, créez une demande : un plombier certifié sera assigné en moins de 5 minutes dans votre zone.";
            if (t.includes("electr") || t.includes("disj"))
                return "Pour un problème électrique, nos électriciens accrédités sont disponibles. Créez une demande et le plus proche sera notifié.";
            if (t.includes("paiement") || t.includes("momo"))
                return "Le paiement MTN MoMo est déclenché uniquement après votre confirmation de fin des travaux. Vous êtes 100% protégé.";
            if (t.includes("appel") || t.includes("offre"))
                return "Pour publier un appel d'offres, rendez-vous dans la section Appels d'offres. La publication sera validée par l'admin avant mise en ligne.";
            if (t.includes("prestataire") || t.includes("inscrire"))
                return "Pour devenir prestataire, inscrivez-vous et uploadez vos 6 documents requis. Votre dossier sera vérifié sous 48h.";
            return "Je comprends votre demande. Connectez-vous pour accéder à notre service complet et être mis en relation avec un agent si nécessaire.";
        },

        // ── Demander un agent humain ───────────────────────────────

        async requestHuman() {
            if (!this.activeTicket?.id) return;
            this.transferLoading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.tickets_human.replace(
                    "{ticket}",
                    this.activeTicket.id
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                });
                if (!res.ok) throw new Error();
                this.activeTicket.human_requested = true;
                this.activeTicket.status = "pending_human";
                this.activeTicket.status_label = "En attente agent";
                this.showTransferBanner();
            } catch {
                this.showToast("Erreur lors de la demande.");
            } finally {
                this.transferLoading = false;
            }
        },

        showTransferBanner() {
            this.showTransferNotice = true;
            this.scrollToBottom();
            setTimeout(() => {
                this.showTransferNotice = false;
            }, 4000);
        },

        // ── Helpers ────────────────────────────────────────────────

        categoryIcon(cat) {
            const map = {
                Orientation: "🔧",
                Paiement: "💸",
                Inscription: "👷",
                "Appels d'offres": "📋",
                Géolocalisation: "📍",
                Accréditation: "🛡️",
                Notation: "⭐",
                Services: "🌿",
            };
            return map[cat] ?? "💬";
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        showToast(msg) {
            this.toastMsg = msg;
            this.toastVisible = true;
            setTimeout(() => {
                this.toastVisible = false;
            }, 3500);
        },

        reObserveReveal() {
            if (!("IntersectionObserver" in window)) return;
            setTimeout(() => {
                const io = new IntersectionObserver(
                    (entries) =>
                        entries.forEach((e) => {
                            if (e.isIntersecting) {
                                e.target.classList.add("revealed");
                                io.unobserve(e.target);
                            }
                        }),
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)"
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },
    },
};
</script>

<style scoped>
/* ═══ VARIABLES & BASE ═══ */
.consulting-page {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --dk2: #110d0b;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #ffffff;
    --cr: #faf7f4;
    font-family: "Poppins", sans-serif;
}

/* ═══ HERO ═══ */
.ac-hero {
    background: var(--dk2);
    color: #fff;
    padding: 52px 4% 44px;
    position: relative;
    overflow: hidden;
}
.ac-hero-glow {
    position: absolute;
    top: -200px;
    right: -150px;
    width: 550px;
    height: 550px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(249, 115, 22, 0.13) 0%,
        transparent 68%
    );
    pointer-events: none;
}
.ac-hero-glow2 {
    position: absolute;
    bottom: -100px;
    left: -80px;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(252, 211, 77, 0.05) 0%,
        transparent 70%
    );
    pointer-events: none;
}
.ac-hero-dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(
        rgba(255, 255, 255, 0.035) 1px,
        transparent 1px
    );
    background-size: 24px 24px;
    pointer-events: none;
}
.ac-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 760px;
}
.ac-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(249, 115, 22, 0.15);
    border: 1px solid rgba(249, 115, 22, 0.3);
    color: var(--am);
    padding: 6px 15px;
    border-radius: 99px;
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 18px;
}
.bdot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--or);
    animation: blink 1.4s infinite;
    flex-shrink: 0;
}
@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.2;
    }
}
.ac-hero h1 {
    font-size: clamp(30px, 6vw, 54px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}
.hl {
    color: var(--or);
}
.ac-hero-desc {
    font-size: 16px;
    color: #b8ada7;
    line-height: 1.75;
    margin-bottom: 28px;
    font-weight: 400;
}
.ac-hero-steps {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.ac-step-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 13px;
    color: #ccc;
    font-weight: 500;
}
.ac-step-num {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: var(--or);
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ac-step-arrow {
    font-size: 18px;
    color: var(--or);
}
@media (max-width: 600px) {
    .ac-step-arrow {
        display: none;
    }
}

/* ═══ CHAT SECTION ═══ */
.ac-chat-section {
    padding: 44px 4%;
    background: var(--cr);
}
.ac-chat-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 20px;
    max-width: 1100px;
    margin: 0 auto;
    height: 620px;
}
@media (max-width: 768px) {
    .ac-chat-layout {
        grid-template-columns: 1fr;
        height: auto;
    }
    .ac-sidebar {
        display: none;
    }
}

/* ── Sidebar ── */
.ac-sidebar {
    background: var(--wh);
    border-radius: 16px;
    border: 1.5px solid var(--grl);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ac-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
}
.ac-sidebar-header h3 {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ac-new-btn {
    background: var(--or);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 5px 11px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.ac-new-btn:hover {
    background: var(--or2);
}

.ac-conv-list {
    flex: 1;
    overflow-y: auto;
}
.ac-conv-loading {
    padding: 10px;
}
.ac-conv-skel {
    height: 60px;
    border-radius: 8px;
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

.ac-conv-guest {
    padding: 20px 14px;
    text-align: center;
    color: var(--gr);
    font-size: 13px;
    line-height: 1.6;
}
.ac-conv-guest-icon {
    font-size: 32px;
    margin-bottom: 8px;
}
.ac-conv-empty {
    padding: 20px 14px;
    text-align: center;
    color: var(--gr);
    font-size: 12.5px;
    line-height: 1.6;
}

.ac-conv-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 14px;
    cursor: pointer;
    border-bottom: 1px solid var(--grl);
    transition: background 0.15s;
}
.ac-conv-item:hover {
    background: var(--or3);
}
.ac-conv-item.active {
    background: var(--or3);
    border-left: 3px solid var(--or);
}
.ac-conv-item.unread {
    background: #fff8f0;
}
.ac-conv-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}
.ac-conv-info {
    flex: 1;
    min-width: 0;
}
.ac-conv-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ac-conv-preview {
    font-size: 11.5px;
    color: var(--gr);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}
.ac-conv-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
    flex-shrink: 0;
}
.ac-conv-badge {
    background: var(--or);
    color: #fff;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
}
.ac-conv-status {
    font-size: 9.5px;
    font-weight: 600;
    border-radius: 99px;
    padding: 1px 6px;
}
.status-open {
    background: #fef9c3;
    color: #713f12;
}
.status-pending_human {
    background: #fee2e2;
    color: #991b1b;
}
.status-in_progress {
    background: #dbeafe;
    color: #1e40af;
}
.status-resolved,
.status-closed {
    background: #dcfce7;
    color: #166534;
}
.status-ai_handled {
    background: var(--grl);
    color: var(--gr);
}

.ac-agent-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-top: 1px solid var(--grl);
    flex-shrink: 0;
}
.ac-agent-avatar {
    font-size: 26px;
    line-height: 1;
}
.ac-agent-name {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--dk);
}
.ac-agent-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--gr);
    margin-top: 2px;
}
.ac-online-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    animation: pulse-green 2s infinite;
}
.ac-online-dot.human {
    background: var(--or);
    animation: pulse-orange 2s infinite;
}
@keyframes pulse-green {
    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0);
    }
}
@keyframes pulse-orange {
    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0);
    }
}

/* ── Chat main ── */
.ac-chat-main {
    background: var(--wh);
    border-radius: 16px;
    border: 1.5px solid var(--grl);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ac-chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--grl);
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 8px;
}
.ac-chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ac-chat-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ac-chat-avatar {
    font-size: 26px;
    line-height: 1;
}
.ac-chat-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--dk);
}
.ac-chat-mode {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
}

.ac-transfer-btn {
    background: var(--or3);
    border: 1.5px solid var(--or);
    color: var(--or);
    border-radius: 8px;
    padding: 6px 13px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.ac-transfer-btn:hover:not(:disabled) {
    background: var(--or);
    color: #fff;
}
.ac-transfer-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.ac-human-badge {
    font-size: 12px;
    font-weight: 600;
    color: var(--gr);
    padding: 5px 10px;
    background: var(--grl);
    border-radius: 8px;
}
.ac-human-badge-active {
    background: #dcfce7;
    color: #166534;
}
.ac-ticket-status {
    font-size: 11px;
    font-weight: 700;
    border-radius: 99px;
    padding: 3px 9px;
}

/* ── Messages ── */
.ac-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ac-welcome {
    text-align: center;
    margin: auto;
    padding: 20px;
}
.ac-welcome-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.ac-welcome h3 {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 8px;
}
.ac-welcome p {
    font-size: 14px;
    color: var(--gr);
    margin-bottom: 16px;
    line-height: 1.6;
}

.ac-msg {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}
.ac-msg-bot {
    flex-direction: row;
}
.ac-msg-user {
    flex-direction: row-reverse;
}
.ac-msg-avatar {
    font-size: 22px;
    line-height: 1;
    flex-shrink: 0;
    margin-bottom: 2px;
}
.ac-msg-avatar-user {
    font-size: 22px;
    line-height: 1;
    flex-shrink: 0;
    margin-bottom: 2px;
}
.ac-msg-bubble {
    max-width: 78%;
    padding: 11px 15px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.6;
    color: var(--dk);
    position: relative;
}
.ac-msg-bot .ac-msg-bubble {
    background: #f0e9e4;
    border-bottom-left-radius: 4px;
}
.ac-msg-user .ac-msg-bubble {
    background: var(--or);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ac-msg-bubble-human {
    background: #fff3e0 !important;
    border-left: 3px solid var(--or);
}
.ac-msg-bubble-user {
    background: var(--or);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ac-msg-sender {
    font-size: 11px;
    font-weight: 700;
    color: var(--or);
    margin-bottom: 4px;
}
.ac-msg-time {
    font-size: 10.5px;
    opacity: 0.55;
    margin-top: 4px;
    text-align: right;
}
.ac-msg-user .ac-msg-time {
    color: rgba(255, 255, 255, 0.7);
}

.ac-msg-loading-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ac-msg-skel {
    height: 52px;
    border-radius: 12px;
    background: linear-gradient(90deg, #f0e9e4 25%, #e8ddd4 50%, #f0e9e4 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

/* Typing */
.ac-typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 14px 18px !important;
}
.ac-typing span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--grm);
    animation: typing-bounce 0.8s infinite ease-in-out;
}
.ac-typing span:nth-child(2) {
    animation-delay: 0.15s;
}
.ac-typing span:nth-child(3) {
    animation-delay: 0.3s;
}
@keyframes typing-bounce {
    0%,
    80%,
    100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-6px);
    }
}

/* Transfer notice */
.ac-transfer-notice {
    display: flex;
    justify-content: center;
}
.ac-transfer-inner {
    background: var(--cr);
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 12.5px;
    color: var(--gr);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    text-align: center;
}

/* Suggestions */
.ac-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 12px;
}
.ac-suggestion {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12.5px;
    color: var(--gr);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    font-weight: 500;
    text-align: left;
}
.ac-suggestion:hover {
    background: var(--or);
    color: #fff;
    border-color: var(--or);
}

/* ── Input bar ── */
.ac-input-bar {
    padding: 14px 16px;
    border-top: 1px solid var(--grl);
    background: var(--wh);
    flex-shrink: 0;
}
.ac-guest-prompt {
    text-align: center;
    font-size: 13px;
    color: var(--gr);
    padding: 4px 0;
}
.ac-guest-prompt a {
    color: var(--or);
    font-weight: 600;
}
.ac-resolved-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 13px;
    color: var(--gr);
}
.ac-input-wrap {
    display: flex;
    gap: 8px;
    margin-bottom: 6px;
}
.ac-input {
    flex: 1;
    padding: 11px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    font-family: "Poppins", sans-serif;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
    background: var(--cr);
    color: var(--dk);
}
.ac-input:focus {
    border-color: var(--or);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
}
.ac-input::placeholder {
    color: var(--grm);
}
.ac-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.ac-send-btn {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.ac-send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.35);
}
.ac-send-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.ac-input-hint {
    font-size: 11.5px;
    color: var(--grm);
}

/* ═══ SECTIONS ═══ */
.sec {
    padding: 64px 4%;
}
.sec-cr {
    background: var(--cr);
}
.sec-tag {
    display: inline-block;
    background: var(--or3);
    color: var(--or);
    border-radius: 99px;
    padding: 5px 14px;
    font-size: 12.5px;
    font-weight: 700;
    margin-bottom: 14px;
}
.sec-title {
    font-size: clamp(22px, 4vw, 36px);
    font-weight: 900;
    color: var(--dk);
    line-height: 1.2;
    margin-bottom: 12px;
}
.sec-sub {
    font-size: 15px;
    color: var(--gr);
    line-height: 1.7;
    margin-bottom: 36px;
}

.ac-how-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}
.ac-how-card {
    background: var(--wh);
    border-radius: 14px;
    padding: 28px 22px;
    border: 1.5px solid var(--grl);
    text-align: center;
    transition: all 0.25s;
}
.ac-how-card:hover {
    border-color: var(--or);
    box-shadow: 0 8px 28px rgba(249, 115, 22, 0.1);
    transform: translateY(-3px);
}
.ac-how-num {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}
.ac-how-icon {
    font-size: 32px;
    margin-bottom: 12px;
}
.ac-how-card h4 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--dk);
}
.ac-how-card p {
    font-size: 13px;
    color: var(--gr);
    line-height: 1.65;
}

.ac-usecases-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (min-width: 600px) {
    .ac-usecases-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
.ac-usecase {
    background: var(--wh);
    border-radius: 14px;
    padding: 18px 16px;
    border: 1.5px solid var(--grl);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.22, 0.68, 0, 1.2);
}
.ac-usecase:hover {
    border-color: var(--or);
    box-shadow: 0 8px 24px rgba(249, 115, 22, 0.12);
    transform: translateY(-3px);
}
.ac-usecase-icon {
    font-size: 26px;
    margin-bottom: 10px;
}
.ac-usecase-q {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    line-height: 1.5;
    margin-bottom: 8px;
}
.ac-usecase-tag {
    display: inline-block;
    background: var(--or3);
    color: var(--or);
    border-radius: 99px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}
.ac-usecase-arrow {
    font-size: 12px;
    color: var(--or);
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s, transform 0.2s;
    transform: translateX(-4px);
}
.ac-usecase:hover .ac-usecase-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ═══ CTA ═══ */
.cta-final {
    background: var(--dk2);
    padding: 64px 4%;
    text-align: center;
    color: #fff;
}
.cta-inner h2 {
    font-size: clamp(22px, 4vw, 36px);
    font-weight: 900;
    margin-bottom: 12px;
}
.cta-inner p {
    font-size: 15px;
    color: #b8ada7;
    margin-bottom: 28px;
}
.cta-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 13px 26px;
    border-radius: 10px;
    font-family: "Poppins", sans-serif;
    font-size: 14.5px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-dark {
    background: var(--or);
    color: #fff;
}
.btn-dark:hover {
    background: var(--or2);
    transform: translateY(-1px);
}
.btn-ghost {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border: 1.5px solid rgba(255, 255, 255, 0.18);
}
.btn-ghost:hover {
    background: rgba(255, 255, 255, 0.14);
}

/* ═══ REVEAL ANIMATIONS ═══ */
.reveal,
.reveal-left,
.reveal-right {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.55s ease, transform 0.55s ease;
}
.reveal-left {
    transform: translateX(-28px);
}
.reveal-right {
    transform: translateX(28px);
}
.revealed {
    opacity: 1;
    transform: none;
}
.reveal-d1 {
    transition-delay: 0.1s;
}
.reveal-d2 {
    transition-delay: 0.2s;
}
.reveal-d3 {
    transition-delay: 0.3s;
}
.au {
    animation: fadeUp 0.6s ease both;
}
.au1 {
    animation: fadeUp 0.65s 0.1s ease both;
}
.au2 {
    animation: fadeUp 0.65s 0.2s ease both;
}
.au3 {
    animation: fadeUp 0.65s 0.3s ease both;
}
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

/* ═══ TOAST ═══ */
.ac-toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--dk);
    color: #fff;
    padding: 12px 22px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    opacity: 0;
    transition: all 0.3s;
    pointer-events: none;
    z-index: 999;
    white-space: nowrap;
}
.ac-toast.visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>
