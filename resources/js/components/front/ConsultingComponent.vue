<template>
    <div class="consulting-page">
        <!-- HERO -->
        <section class="ac-hero">
            <div class="ac-hero-glow"></div>
            <div class="ac-hero-glow2"></div>
            <div class="ac-hero-dots"></div>
            <div class="ac-hero-inner">
                <div class="ac-badge au">
                    <span class="bdot"></span>
                    Gratuit pour tous les inscrits - Disponible 24h/24
                </div>
                <h1 class="au1">
                    Allo Conseils<br />
                    <span class="hl">votre assistant</span><br />
                    Resotravo
                </h1>
                <p class="ac-hero-desc au2">
                    Posez vos questions et obtenez une orientation immediate par
                    notre agent IA.<br />
                    En cas de besoin, un agent humain Resotravo prend le relais.
                </p>
                <div class="ac-hero-steps au3">
                    <div class="ac-step-pill">
                        <span class="ac-step-num">1</span>
                        <span>Agent IA repond (3 premiers messages)</span>
                    </div>
                    <div class="ac-step-arrow">&rarr;</div>
                    <div class="ac-step-pill">
                        <span class="ac-step-num">2</span>
                        <span>Transfert agent humain si besoin</span>
                    </div>
                    <div class="ac-step-arrow">&rarr;</div>
                    <div class="ac-step-pill">
                        <span class="ac-step-num">3</span>
                        <span>Historique conserve dans votre espace</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- CHAT -->
        <section class="ac-chat-section">
            <div class="ac-chat-layout">
                <!-- Sidebar -->
                <div class="ac-sidebar">
                    <div class="ac-sidebar-header">
                        <h3>Mes conversations</h3>
                        <button
                            class="ac-new-btn"
                            @click="startNewConversation"
                        >
                            + Nouvelle
                        </button>
                    </div>
                    <div class="ac-conv-list">
                        <div
                            class="ac-conv-item"
                            v-for="(conv, i) in conversations"
                            :key="i"
                            :class="{ active: activeConv === i }"
                            @click="activeConv = i"
                        >
                            <div class="ac-conv-icon">{{ conv.icon }}</div>
                            <div class="ac-conv-info">
                                <div class="ac-conv-title">
                                    {{ conv.title }}
                                </div>
                                <div class="ac-conv-preview">
                                    {{ conv.preview }}
                                </div>
                            </div>
                            <div class="ac-conv-badge" v-if="conv.unread">
                                {{ conv.unread }}
                            </div>
                        </div>
                    </div>
                    <div class="ac-agent-card reveal">
                        <div class="ac-agent-avatar">&#x1F916;</div>
                        <div>
                            <div class="ac-agent-name">Agent IA Resotravo</div>
                            <div class="ac-agent-status">
                                <span class="ac-online-dot"></span> En ligne
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat principal -->
                <div class="ac-chat-main">
                    <div class="ac-chat-header">
                        <div class="ac-chat-header-left">
                            <div class="ac-chat-avatar">
                                <span v-if="isHumanAgent">&#x1F464;</span>
                                <span v-else>&#x1F916;</span>
                            </div>
                            <div>
                                <div class="ac-chat-name">
                                    {{
                                        isHumanAgent
                                            ? "Agent Resotravo"
                                            : "Agent IA Resotravo"
                                    }}
                                </div>
                                <div class="ac-chat-mode">
                                    <span
                                        class="ac-online-dot"
                                        :class="{ human: isHumanAgent }"
                                    ></span>
                                    <span v-if="isHumanAgent"
                                        >Agent humain - Historique
                                        transmis</span
                                    >
                                    <span v-else
                                        >Message {{ messageCount }}/3 - IA
                                        automatique</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="ac-chat-header-right">
                            <button
                                class="ac-transfer-btn"
                                v-if="!isHumanAgent"
                                @click="requestHuman"
                            >
                                Parler a un humain
                            </button>
                            <div class="ac-human-badge" v-else>
                                Agent humain connecte
                            </div>
                        </div>
                    </div>

                    <div class="ac-messages" ref="messagesContainer">
                        <!-- Bienvenue -->
                        <div class="ac-msg ac-msg-bot">
                            <div class="ac-msg-avatar">&#x1F916;</div>
                            <div class="ac-msg-bubble">
                                <p>
                                    Bonjour ! Je suis l'assistant Resotravo.
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

                        <!-- Messages -->
                        <template v-for="(msg, i) in messages" :key="i">
                            <div
                                class="ac-msg ac-msg-user"
                                v-if="msg.from === 'user'"
                            >
                                <div class="ac-msg-bubble ac-msg-bubble-user">
                                    {{ msg.text }}
                                </div>
                                <div class="ac-msg-avatar ac-msg-avatar-user">
                                    &#x1F464;
                                </div>
                            </div>
                            <div class="ac-msg ac-msg-bot" v-else>
                                <div class="ac-msg-avatar">&#x1F916;</div>
                                <div
                                    class="ac-msg-bubble"
                                    :class="{
                                        'ac-msg-bubble-human': msg.isHuman,
                                    }"
                                >
                                    <div
                                        class="ac-msg-sender"
                                        v-if="msg.isHuman"
                                    >
                                        Agent Resotravo
                                    </div>
                                    {{ msg.text }}
                                </div>
                            </div>
                        </template>

                        <!-- Typing -->
                        <div class="ac-msg ac-msg-bot" v-if="isTyping">
                            <div class="ac-msg-avatar">&#x1F916;</div>
                            <div class="ac-msg-bubble ac-typing">
                                <span></span><span></span><span></span>
                            </div>
                        </div>

                        <!-- Transfert notice -->
                        <div
                            class="ac-transfer-notice"
                            v-if="showTransferNotice"
                        >
                            <div class="ac-transfer-inner">
                                <span>Transfert vers un agent humain...</span>
                                <span>Tout l'historique est transmis.</span>
                            </div>
                        </div>
                    </div>

                    <div class="ac-input-bar">
                        <div class="ac-input-wrap">
                            <input
                                class="ac-input"
                                type="text"
                                v-model="userInput"
                                @keyup.enter="sendMessage"
                                placeholder="Posez votre question..."
                                :disabled="isTyping"
                            />
                            <button
                                class="ac-send-btn"
                                @click="sendMessage"
                                :disabled="!userInput.trim() || isTyping"
                            >
                                <span v-if="!isTyping">&rarr;</span>
                                <span v-else>...</span>
                            </button>
                        </div>
                        <div class="ac-input-hint">
                            <span v-if="!isHumanAgent"
                                >{{ 3 - messageCount }} message(s) avant
                                transfert automatique</span
                            >
                            <span v-else
                                >Vous etes en ligne avec un agent
                                Resotravo</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- COMMENT CA MARCHE -->
        <section class="sec sec-cr" id="comment-conseils">
            <div class="sec-tag reveal">Comment ca marche</div>
            <div class="sec-title reveal reveal-d1">Un conseil en 3 etapes</div>
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

        <!-- CAS D'USAGE -->
        <section class="sec">
            <div class="sec-tag reveal">Exemples de questions</div>
            <div class="sec-title reveal reveal-d1">
                Allo Conseils repond a tout
            </div>
            <div class="sec-sub reveal reveal-d2">
                Orientation, choix du bon prestataire, aide a la redaction d'un
                appel d'offres...
            </div>
            <div class="ac-usecases-grid">
                <div
                    class="ac-usecase reveal"
                    v-for="(uc, i) in useCases"
                    :key="i"
                    :class="'reveal-d' + ((i % 4) + 1)"
                    @click="sendSuggestion(uc.question)"
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

        <!-- CTA -->
        <div class="cta-final">
            <div class="cta-inner reveal">
                <h2>Besoin d'un conseil ?</h2>
                <p>
                    Allo Conseils est gratuit et accessible a tout moment depuis
                    votre espace personnel.
                </p>
                <div class="cta-btns">
                    <a class="btn btn-dark btn-lg" :href="routes.register"
                        >Creer un compte gratuit &rarr;</a
                    >
                    <a class="btn btn-ghost btn-lg" :href="routes.login"
                        >Se connecter</a
                    >
                </div>
            </div>
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
            }),
        },
    },

    data() {
        return {
            userInput: "",
            messages: [],
            isTyping: false,
            isHumanAgent: false,
            messageCount: 0,
            showTransferNotice: false,
            activeConv: 0,

            conversations: [
                {
                    icon: "🔧",
                    title: "Probleme plomberie",
                    preview: "Quel prestataire pour...",
                    unread: 0,
                },
                {
                    icon: "⚡",
                    title: "Question electricite",
                    preview: "Mon disjoncteur saute...",
                    unread: 2,
                },
                {
                    icon: "📋",
                    title: "Appel d'offres",
                    preview: "Comment rediger mon AO...",
                    unread: 0,
                },
            ],

            suggestions: [
                "Quel prestataire pour reparer mon disjoncteur ?",
                "Comment fonctionne le paiement MTN MoMo ?",
                "Je veux publier un appel d'offres",
                "Quel est le delai pour trouver un artisan ?",
            ],

            howSteps: [
                {
                    icon: "💬",
                    title: "Posez votre question",
                    desc: "Decrivez votre besoin. L'agent IA comprend et repond immediatement.",
                },
                {
                    icon: "🤖",
                    title: "Reponse instantanee",
                    desc: "L'IA traite vos 3 premiers messages et vous oriente vers le bon service.",
                },
                {
                    icon: "🧑",
                    title: "Transfert agent humain",
                    desc: "Apres 3 messages ou sur demande, un agent Resotravo prend le relais.",
                },
            ],

            useCases: [
                {
                    icon: "🔧",
                    question: "Quel prestataire pour reparer mon disjoncteur ?",
                    tag: "Orientation",
                },
                {
                    icon: "📋",
                    question:
                        "Comment rediger un appel d'offres pour mon entreprise ?",
                    tag: "Appels d'offres",
                },
                {
                    icon: "👷",
                    question:
                        "Comment devenir prestataire certifie sur Resotravo ?",
                    tag: "Inscription",
                },
                {
                    icon: "💸",
                    question:
                        "Comment fonctionne le paiement apres les travaux ?",
                    tag: "Paiement",
                },
                {
                    icon: "📍",
                    question: "Puis-je choisir moi-meme mon artisan ?",
                    tag: "Geolocalisation",
                },
                {
                    icon: "🛡️",
                    question: "C'est quoi l'accreditation ENTREPRISE ?",
                    tag: "Accreditation",
                },
                {
                    icon: "⭐",
                    question:
                        "Comment noter un prestataire apres une intervention ?",
                    tag: "Notation",
                },
                {
                    icon: "🌿",
                    question: "Est-ce que Resotravo couvre le jardinage ?",
                    tag: "Services",
                },
            ],

            iaResponses: {
                default:
                    "Je comprends votre demande. Laissez-moi vous orienter vers la meilleure solution sur Resotravo.",
                plomb: "Pour un probleme de plomberie, creez une demande : un plombier certifie sera assigne en moins de 5 minutes dans votre zone.",
                elec: "Pour un probleme electrique, nos electriciens accredites sont disponibles. Creez une demande et le plus proche sera notifie.",
                paiement:
                    "Le paiement MTN MoMo est declenche uniquement apres votre confirmation de fin des travaux. Vous etes 100% protege.",
                offre: "Pour publier un appel d'offres, allez dans la section Appels d'offres. La publication sera validee par l'admin avant mise en ligne.",
                presta: "Pour devenir prestataire, inscrivez-vous et uploadez vos 6 documents requis. Votre dossier sera verifie sous 48h.",
            },
        };
    },

    mounted() {
        this.$nextTick(() => {
            this.reObserveReveal();
        });
    },

    methods: {
        sendMessage() {
            const text = this.userInput.trim();
            if (!text) return;
            this.messages.push({ from: "user", text });
            this.userInput = "";
            this.messageCount++;
            this.scrollToBottom();
            this.isTyping = true;
            setTimeout(() => {
                this.isTyping = false;
                if (this.messageCount >= 3 && !this.isHumanAgent) {
                    this.triggerHumanTransfer();
                } else {
                    const reply = this.getIAReply(text);
                    this.messages.push({
                        from: "bot",
                        text: reply,
                        isHuman: this.isHumanAgent,
                    });
                    this.scrollToBottom();
                }
            }, 1400);
        },

        sendSuggestion(text) {
            this.userInput = text;
            this.sendMessage();
            const el = document.querySelector(".ac-chat-section");
            if (el) el.scrollIntoView({ behavior: "smooth" });
        },

        getIAReply(text) {
            const t = text.toLowerCase();
            if (t.includes("plomb")) return this.iaResponses.plomb;
            if (t.includes("electr") || t.includes("disjonct"))
                return this.iaResponses.elec;
            if (t.includes("paiement") || t.includes("momo"))
                return this.iaResponses.paiement;
            if (t.includes("appel") || t.includes("offre"))
                return this.iaResponses.offre;
            if (t.includes("prestataire") || t.includes("inscrire"))
                return this.iaResponses.presta;
            return this.iaResponses.default;
        },

        requestHuman() {
            this.triggerHumanTransfer();
        },

        triggerHumanTransfer() {
            this.showTransferNotice = true;
            this.scrollToBottom();
            setTimeout(() => {
                this.isHumanAgent = true;
                this.showTransferNotice = false;
                this.messages.push({
                    from: "bot",
                    isHuman: true,
                    text: "Bonjour ! Je suis Sarah, agent Resotravo. J'ai bien recu l'historique. Comment puis-je vous aider ?",
                });
                this.scrollToBottom();
            }, 2000);
        },

        startNewConversation() {
            this.messages = [];
            this.messageCount = 0;
            this.isHumanAgent = false;
            this.showTransferNotice = false;
            this.userInput = "";
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
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
                    { threshold: 0.08, rootMargin: "0px 0px -30px 0px" },
                );
                document
                    .querySelectorAll(
                        ".reveal:not(.revealed),.reveal-left:not(.revealed),.reveal-right:not(.revealed)",
                    )
                    .forEach((el) => io.observe(el));
            }, 150);
        },
    },
};
</script>

<style scoped>
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
    height: 600px;
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
    padding: 16px 14px;
    border-bottom: 1px solid var(--grl);
}
.ac-sidebar-header h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk);
}
.ac-new-btn {
    background: var(--or);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 5px 10px;
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
    padding: 8px;
}
.ac-conv-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.18s;
    margin-bottom: 4px;
}
.ac-conv-item:hover,
.ac-conv-item.active {
    background: var(--cr);
}
.ac-conv-item.active {
    background: var(--or3);
}
.ac-conv-icon {
    font-size: 20px;
    flex-shrink: 0;
}
.ac-conv-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
}
.ac-conv-preview {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 1px;
}
.ac-conv-badge {
    margin-left: auto;
    background: var(--or);
    color: #fff;
    border-radius: 99px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
.ac-agent-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    border-top: 1px solid var(--grl);
    background: var(--cr);
}
.ac-agent-avatar {
    font-size: 28px;
}
.ac-agent-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ac-agent-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
}
.ac-online-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    animation: pulse-dot 2s infinite;
}
.ac-online-dot.human {
    background: #3b82f6;
}
@keyframes pulse-dot {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}

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
    background: var(--wh);
    flex-shrink: 0;
}
.ac-chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ac-chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--or3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    border: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.ac-chat-name {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--dk);
}
.ac-chat-mode {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
}
.ac-transfer-btn {
    background: var(--cr);
    border: 1.5px solid var(--grl);
    color: var(--dk);
    border-radius: 8px;
    padding: 7px 13px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.ac-transfer-btn:hover {
    border-color: var(--or);
    color: var(--or);
}
.ac-human-badge {
    background: #dcfce7;
    color: #16a34a;
    border-radius: 8px;
    padding: 7px 13px;
    font-size: 12.5px;
    font-weight: 600;
}

.ac-messages {
    flex: 1;
    overflow-y: auto;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #fafaf8;
}
.ac-msg {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.ac-msg-bot {
    justify-content: flex-start;
}
.ac-msg-user {
    justify-content: flex-end;
}
.ac-msg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: var(--or3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.ac-msg-avatar-user {
    background: var(--dk);
    color: #fff;
    font-size: 14px;
}
.ac-msg-bubble {
    max-width: 72%;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px 14px 14px 4px;
    padding: 12px 15px;
    font-size: 14px;
    color: var(--dk);
    line-height: 1.65;
}
.ac-msg-bubble-user {
    background: var(--dk);
    color: #fff;
    border-color: var(--dk);
    border-radius: 14px 14px 4px 14px;
}
.ac-msg-bubble-human {
    border-color: #3b82f6;
    background: #eff6ff;
}
.ac-msg-sender {
    font-size: 11px;
    font-weight: 700;
    color: #3b82f6;
    margin-bottom: 4px;
}
.ac-suggestions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 10px;
}
.ac-suggestion {
    background: var(--cr);
    border: 1px solid var(--grl);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12.5px;
    color: var(--gr);
    text-align: left;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    font-weight: 500;
}
.ac-suggestion:hover {
    background: var(--or);
    color: #fff;
    border-color: var(--or);
}
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

.ac-input-bar {
    padding: 14px 16px;
    border-top: 1px solid var(--grl);
    background: var(--wh);
    flex-shrink: 0;
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
    transition:
        opacity 0.2s,
        transform 0.2s;
    transform: translateX(-4px);
}
.ac-usecase:hover .ac-usecase-arrow {
    opacity: 1;
    transform: translateX(0);
}
</style>
