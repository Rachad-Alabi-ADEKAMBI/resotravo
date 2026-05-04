<template>
    <div class="amail-wrap">
        <div class="amail-topbar">
            <div class="amail-topbar-left">
                <button
                    class="ad-burger"
                    type="button"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <h1>{{ showHistory ? "Historique des mails" : "Nouveau message" }}</h1>
                    <p>{{ user.name }}</p>
                </div>
            </div>
            <div class="amail-top-actions">
                <div class="amail-notif-wrap" ref="notifWrap">
                    <button class="amail-notif-btn" type="button" @click="toggleNotif" aria-label="Notifications">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <span class="amail-notif-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
                    </button>
                    <div class="amail-notif-dropdown" v-if="notifOpen">
                        <div class="amail-notif-header">
                            <span>Notifications</span>
                            <button class="amail-notif-read-all" type="button" @click="markAllRead" v-if="unreadCount > 0">
                                Tout lire
                            </button>
                        </div>
                        <div class="amail-notif-empty" v-if="notifLoading">Chargement...</div>
                        <div class="amail-notif-empty" v-else-if="notifications.length === 0">Aucune notification</div>
                        <div
                            class="amail-notif-item"
                            v-for="notif in notifications"
                            :key="notif.id"
                            :class="{ unread: !notif.read }"
                            @click="openNotif(notif)"
                        >
                            <div class="amail-notif-dot" v-if="!notif.read"></div>
                            <div>
                                <div class="amail-notif-title">{{ notif.title }}</div>
                                <div class="amail-notif-body">{{ notif.body }}</div>
                                <div class="amail-notif-ago">{{ notif.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="amail-avatar">{{ userInitials }}</div>
            </div>
        </div>

        <div class="amail-page-actions">
            <button class="amail-btn amail-action-btn" type="button" @click="toggleHistory">
                <svg v-if="showHistory" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                    <path d="M3 3v6h6" />
                    <path d="M12 7v5l3 2" />
                </svg>
                <span>{{ showHistory ? "Nouveau message" : "Historique" }}</span>
            </button>
            <button class="amail-btn amail-action-btn" type="button" @click="showHistory ? fetchHistory() : loadData()">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12a9 9 0 0 1-15.5 6.2" />
                    <path d="M3 12A9 9 0 0 1 18.5 5.8" />
                    <path d="M18 2v5h-5" />
                    <path d="M6 22v-5h5" />
                </svg>
                <span>Actualiser</span>
            </button>
        </div>

        <section v-if="showHistory" class="amail-panel amail-history">
            <div v-if="historyLoading" class="amail-muted">Chargement...</div>
            <div v-else-if="history.length === 0" class="amail-muted">Aucun envoi enregistré.</div>
            <div v-else class="amail-history-list">
                <div v-for="item in history" :key="item.id" class="amail-history-item">
                    <div>
                        <strong>{{ item.subject }}</strong>
                        <small>
                            {{ item.created_at }} - {{ item.sent_count }}/{{ item.recipients_count }} envoyé(s)
                            <span v-if="item.failed_count">- {{ item.failed_count }} échec(s)</span>
                        </small>
                        <small v-if="item.manual_recipients?.length">
                            {{ item.manual_recipients.join(", ") }}
                        </small>
                    </div>
                    <span>{{ recipientModeLabel(item.recipient_mode) }}</span>
                </div>
            </div>
        </section>

        <template v-else>
            <section class="amail-panel amail-recipients">
                <div class="amail-panel-head">
                    <h2>Destinataires</h2>
                    <div class="amail-summary">{{ recipientCount }} destinataire(s) cible(s)</div>
                </div>

                <div class="amail-recipient-grid">
                    <div>
                        <label>Mode d'envoi</label>
                        <select v-model="form.recipient_mode" class="amail-input">
                            <option value="all">Tous les utilisateurs</option>
                            <option value="roles">Par rôle</option>
                            <option value="selected">Sélection manuelle</option>
                            <option value="manual">Email manuel</option>
                        </select>
                        <div v-if="form.recipient_mode === 'manual'" class="amail-manual-recipient">
                            <label>Email du destinataire</label>
                            <textarea
                                v-model="manualRecipientsText"
                                class="amail-input amail-textarea"
                                rows="2"
                                placeholder="email@exemple.com"
                            ></textarea>
                            <div class="amail-hint">Une adresse par ligne, ou separees par des virgules.</div>
                        </div>
                    </div>

                    <div v-if="form.recipient_mode === 'roles'" class="amail-checks">
                        <label v-for="role in roles" :key="role.value">
                            <input type="checkbox" :value="role.value" v-model="form.roles" />
                            {{ role.label }}
                        </label>
                    </div>

                    <div v-if="form.recipient_mode === 'selected'">
                        <label>Rechercher</label>
                        <input
                            v-model="search"
                            class="amail-input"
                            type="search"
                            placeholder="Nom, email, rôle..."
                            @input="filterUsers"
                        />
                    </div>

                </div>

                <div v-if="form.recipient_mode === 'selected'" class="amail-users">
                    <label v-for="recipient in filteredUsers" :key="recipient.id" class="amail-user">
                        <input type="checkbox" :value="recipient.id" v-model="form.user_ids" />
                        <span>
                            <strong>{{ recipient.name }}</strong>
                            <small>{{ recipient.email }} - {{ roleLabel(recipient.role) }}</small>
                        </span>
                    </label>
                </div>
            </section>

            <div class="amail-grid">
                <section class="amail-panel">
                    <div class="amail-panel-head">
                        <h2>Message</h2>
                        <div class="amail-template-actions">
                            <button class="amail-btn amail-btn-template" type="button" @click="openTemplateForm">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6" />
                                    <path d="M12 11v6" />
                                    <path d="M9 14h6" />
                                </svg>
                                <span>Nouveau template</span>
                            </button>
                        </div>
                    </div>

                    <div class="amail-compose-choice">
                        <button
                            class="amail-choice-btn"
                            :class="{ active: composeMode === 'template' }"
                            type="button"
                            @click="setComposeMode('template')"
                        >
                            Utiliser un template
                        </button>
                        <button
                            class="amail-choice-btn"
                            :class="{ active: composeMode === 'manual' }"
                            type="button"
                            @click="setComposeMode('manual')"
                        >
                            Rediger manuellement
                        </button>
                    </div>

                    <div v-if="composeMode === 'template'" class="amail-template-select">
                        <label>Template a utiliser</label>
                        <div class="amail-template-select-row">
                            <select v-model="selectedTemplateId" class="amail-input" @change="applyTemplate">
                                <option value="">Choisir un template</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                            <button
                                class="amail-btn amail-btn-light small"
                                type="button"
                                :disabled="!selectedTemplateId"
                                @click="openTemplateEdit"
                            >
                                Modifier
                            </button>
                        </div>
                    </div>

                    <div v-if="false && templateOpen" class="amail-template-box">
                        <label>Nom du template</label>
                        <input v-model="templateForm.name" class="amail-input" type="text" maxlength="120" />
                        <div class="amail-hint">Le template sauvegarde le contenu ci-dessous. Le sujet reste optionnel et pourra etre defini au moment de l'envoi.</div>
                        <label class="amail-checkline">
                            <input type="checkbox" v-model="templateForm.is_default" />
                            Template par défaut
                        </label>
                        <div class="amail-template-actions">
                            <button class="amail-btn amail-btn-primary small" type="button" :disabled="savingTemplate" @click="saveTemplate">
                                {{ savingTemplate ? "Enregistrement..." : "Enregistrer le template" }}
                            </button>
                            <button class="amail-btn amail-btn-light small" type="button" @click="templateOpen = false">Annuler</button>
                        </div>
                    </div>

                    <label>Sujet</label>
                    <input ref="subjectInput" v-model="form.subject" class="amail-input" type="text" maxlength="180" />
                    <div class="amail-variable-picker amail-variable-picker-compact">
                        <span>Variables sujet</span>
                        <button
                            v-for="variable in templateVariables"
                            :key="`subject-${variable.value}`"
                            type="button"
                            @click="insertSubjectVariable(variable.value)"
                        >
                            {{ variable.label }}
                        </button>
                    </div>

                    <label>Contenu</label>
                    <div class="amail-toolbar">
                        <select v-model="selectedFont" @change="formatValue('fontName', selectedFont)">
                            <option v-for="font in fonts" :key="font" :value="font">{{ font }}</option>
                        </select>
                        <select v-model="selectedSize" @change="formatValue('fontSize', selectedSize)">
                            <option value="2">Petit</option>
                            <option value="3">Normal</option>
                            <option value="4">Grand</option>
                            <option value="5">Titre</option>
                            <option value="6">Très grand</option>
                        </select>
                        <button type="button" title="Gras" @click="format('bold')"><strong>B</strong></button>
                        <button type="button" title="Italique" @click="format('italic')"><em>I</em></button>
                        <button type="button" title="Souligné" @click="format('underline')"><u>U</u></button>
                        <button type="button" title="Liste" @click="format('insertUnorderedList')">Liste</button>
                        <button type="button" class="amail-align-btn" title="Aligner à gauche" @click="applyAlignment('left')">
                            <span></span><span></span><span></span>
                        </button>
                        <button type="button" class="amail-align-btn amail-align-center" title="Centrer" @click="applyAlignment('center')">
                            <span></span><span></span><span></span>
                        </button>
                        <button type="button" class="amail-align-btn amail-align-right" title="Aligner à droite" @click="applyAlignment('right')">
                            <span></span><span></span><span></span>
                        </button>
                        <label class="amail-color">
                            Texte
                            <input type="color" v-model="textColor" @input="formatValue('foreColor', textColor)" />
                        </label>
                        <label class="amail-color">
                            Fond
                            <input type="color" v-model="backgroundColor" @input="formatValue('hiliteColor', backgroundColor)" />
                        </label>
                        <label class="amail-color">
                            Bouton
                            <input type="color" v-model="buttonColor" @input="applyButtonColor" />
                        </label>
                        <button type="button" title="Lien" @click="insertLink">Lien</button>
                        <button type="button" title="Bouton avec lien" @click="insertButtonLink">Bouton lien</button>
                        <button type="button" title="Image" @click="$refs.imageInput.click()">Image</button>
                    </div>

                    <div v-if="selectedImage" class="amail-image-tools">
                        <strong>Image sélectionnée</strong>
                        <label>
                            Largeur
                            <input v-model.number="imageWidth" type="number" min="50" max="1200" step="10" @input="resizeSelectedImage" />
                        </label>
                        <button class="amail-btn amail-btn-light small" type="button" @click="clearImageSize">Taille auto</button>
                        <span class="amail-tool-hint">Glissez le coin bas droit de l'image pour la redimensionner.</span>
                    </div>

                    <div v-if="selectedButton" class="amail-image-tools">
                        <strong>Bouton sélectionné</strong>
                        <label>
                            Couleur
                            <input v-model="buttonColor" type="color" @input="applyButtonColor" />
                        </label>
                    </div>

                    <input ref="imageInput" type="file" accept="image/*" hidden @change="uploadImage" />
                    <div class="amail-variable-picker">
                        <span>Variables</span>
                        <button
                            v-for="variable in templateVariables"
                            :key="variable.value"
                            type="button"
                            @click="insertMessageVariable(variable.value)"
                        >
                            {{ variable.label }}
                        </button>
                    </div>
                    <div
                        ref="editor"
                        class="amail-editor"
                        contenteditable="true"
                        @click="handleEditorClick"
                        @mousedown="handleEditorMouseDown"
                        @input="syncBody"
                        @blur="syncBody"
                    ></div>

                    <div class="amail-hint">Cliquez sur une variable pour l'ajouter au contenu.</div>

                    <label>Pièces jointes</label>
                    <input class="amail-input" type="file" multiple @change="setAttachments" />
                    <div class="amail-attachments" v-if="attachments.length">
                        <span v-for="file in attachments" :key="file.name">{{ file.name }}</span>
                    </div>
                    <button class="amail-btn amail-btn-success amail-send-btn" type="button" :disabled="sending" @click="sendMail">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22 2 11 13" />
                            <path d="M22 2 15 22 11 13 2 9z" />
                        </svg>
                        <span>{{ sending ? "Envoi..." : "Envoyer" }}</span>
                    </button>
                    <p v-if="message" class="amail-success">{{ message }}</p>
                    <p v-if="error" class="amail-error">{{ error }}</p>
                </section>

                <aside class="amail-panel amail-send-panel">
                    <h2>Envoi</h2>
                    <p class="amail-muted">
                        Vérifiez le message, les destinataires et les pièces jointes avant l'envoi.
                    </p>
                </aside>
            </div>
        </template>

        <div v-if="templateOpen" class="amail-modal-backdrop" @click.self="closeTemplateForm">
            <section class="amail-modal" role="dialog" aria-modal="true" aria-label="Nouveau template">
                <div class="amail-modal-head">
                    <h2>{{ editingTemplateId ? "Modifier le template" : "Nouveau template" }}</h2>
                    <button class="amail-modal-close" type="button" @click="closeTemplateForm" aria-label="Fermer">x</button>
                </div>

                <label>Nom du template</label>
                <input v-model="templateForm.name" class="amail-input" type="text" maxlength="120" />

                <label>Sujet</label>
                <input ref="templateSubjectInput" v-model="templateForm.subject" class="amail-input" type="text" maxlength="180" />
                <div class="amail-variable-picker amail-variable-picker-compact">
                    <span>Variables sujet</span>
                    <button
                        v-for="variable in templateVariables"
                        :key="`template-subject-${variable.value}`"
                        type="button"
                        @click="insertTemplateSubjectVariable(variable.value)"
                    >
                        {{ variable.label }}
                    </button>
                </div>

                <label>Contenu du template</label>
                <div class="amail-toolbar">
                    <select v-model="selectedTemplateFont" @change="formatTemplateValue('fontName', selectedTemplateFont)">
                        <option v-for="font in fonts" :key="font" :value="font">{{ font }}</option>
                    </select>
                    <select v-model="selectedTemplateSize" @change="formatTemplateValue('fontSize', selectedTemplateSize)">
                        <option value="2">Petit</option>
                        <option value="3">Normal</option>
                        <option value="4">Grand</option>
                        <option value="5">Titre</option>
                        <option value="6">TrÃ¨s grand</option>
                    </select>
                    <button type="button" title="Gras" @click="formatTemplate('bold')"><strong>B</strong></button>
                    <button type="button" title="Italique" @click="formatTemplate('italic')"><em>I</em></button>
                    <button type="button" title="SoulignÃ©" @click="formatTemplate('underline')"><u>U</u></button>
                    <button type="button" title="Liste" @click="formatTemplate('insertUnorderedList')">Liste</button>
                    <label class="amail-color">
                        Texte
                        <input type="color" v-model="textColor" @input="formatTemplateValue('foreColor', textColor)" />
                    </label>
                    <label class="amail-color">
                        Fond
                        <input type="color" v-model="backgroundColor" @input="formatTemplateValue('hiliteColor', backgroundColor)" />
                    </label>
                    <label class="amail-color">
                        Bouton
                        <input type="color" v-model="buttonColor" />
                    </label>
                    <button type="button" title="Lien" @click="insertTemplateLink">Lien</button>
                    <button type="button" title="Bouton avec lien" @click="insertTemplateButtonLink">Bouton lien</button>
                    <button type="button" title="Image" @click="$refs.templateImageInput.click()">Image</button>
                </div>
                <input ref="templateImageInput" type="file" accept="image/*" hidden @change="uploadTemplateImage" />
                <div class="amail-variable-picker">
                    <span>Variables</span>
                    <button
                        v-for="variable in templateVariables"
                        :key="variable.value"
                        type="button"
                        @click="insertTemplateVariable(variable.value)"
                    >
                        {{ variable.label }}
                    </button>
                </div>
                <div
                    ref="templateEditor"
                    class="amail-editor amail-template-editor"
                    contenteditable="true"
                    @input="syncTemplateBody"
                    @blur="syncTemplateBody"
                ></div>
                <div class="amail-hint">Cliquez sur une variable pour l'ajouter au contenu.</div>

                <div class="amail-modal-actions">
                    <button class="amail-btn amail-btn-light small amail-icon-btn" type="button" @click="closeTemplateForm">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                        <span>Annuler</span>
                    </button>
                    <button class="amail-btn amail-btn-success small amail-icon-btn" type="button" :disabled="savingTemplate" @click="saveTemplate">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <path d="M17 21v-8H7v8" />
                            <path d="M7 3v5h8" />
                        </svg>
                        <span>{{ savingTemplate ? "Enregistrement..." : (editingTemplateId ? "Mettre a jour" : "Sauvegarder") }}</span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminMailComponent",

    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            users: [],
            filteredUsers: [],
            templates: [],
            history: [],
            selectedTemplateId: "",
            composeMode: "",
            search: "",
            manualRecipientsText: "",
            sending: false,
            savingTemplate: false,
            historyLoading: false,
            showHistory: false,
            templateOpen: false,
            editingTemplateId: null,
            sidebarOpen: false,
            notifications: [],
            notifOpen: false,
            notifLoading: false,
            unreadCount: 0,
            message: "",
            error: "",
            attachments: [],
            selectedFont: "Arial",
            selectedSize: "3",
            selectedTemplateFont: "Arial",
            selectedTemplateSize: "3",
            textColor: "#111827",
            backgroundColor: "#ffffff",
            buttonColor: "#f97316",
            selectedImage: null,
            selectedButton: null,
            imageWidth: 320,
            imageResize: {
                active: false,
                image: null,
                startX: 0,
                startWidth: 0,
            },
            fonts: [
                "Arial",
                "Verdana",
                "Tahoma",
                "Trebuchet MS",
                "Georgia",
                "Times New Roman",
                "Courier New",
                "Impact",
                "Comic Sans MS",
                "Lucida Console",
                "Palatino Linotype",
                "Garamond",
                "Calibri",
                "Cambria",
                "Poppins",
                "Roboto",
            ],
            roles: [
                { value: "client", label: "Clients" },
                { value: "contractor", label: "Prestataires" },
                { value: "talent", label: "Talents" },
            ],
            templateVariables: [
                { value: "{name}", label: "Nom complet" },
                { value: "{first_name}", label: "Prenom" },
                { value: "{last_name}", label: "Nom" },
                { value: "{email}", label: "Email" },
                { value: "{role}", label: "Role" },
                { value: "{url}", label: "Lien" },
                { value: "{current_date}", label: "Date du jour" },
            ],
            templateForm: {
                name: "",
                subject: "",
                body: "",
            },
            form: {
                subject: "",
                body: "",
                recipient_mode: "all",
                roles: [],
                user_ids: [],
            },
        };
    },

    computed: {
        userInitials() {
            return (this.user.name || "")
                .split(" ")
                .map((part) => part[0])
                .join("")
                .toUpperCase()
                .slice(0, 2);
        },

        recipientCount() {
            if (this.form.recipient_mode === "manual") return this.manualRecipients.length;
            if (this.form.recipient_mode === "selected") return this.form.user_ids.length;
            if (this.form.recipient_mode === "roles") {
                return this.users.filter((user) => this.form.roles.includes(user.role)).length;
            }
            return this.users.length;
        },

        manualRecipients() {
            return Array.from(
                new Set(
                    this.manualRecipientsText
                        .split(/[\s,;]+/)
                        .map((email) => email.trim().toLowerCase())
                        .filter(Boolean)
                )
            );
        },
    },

    mounted() {
        this.loadData();
        this.fetchHistory();
        this.fetchNotifications();
        document.addEventListener("click", this.closeNotifOutside);
    },

    beforeUnmount() {
        document.removeEventListener("click", this.closeNotifOutside);
        this.stopImageResize();
    },

    methods: {
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                })
            );
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        toggleHistory() {
            this.showHistory = !this.showHistory;
            if (this.showHistory) this.fetchHistory();
        },

        async fetchNotifications() {
            if (!this.routes.notifications) return;
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, { headers: { Accept: "application/json" } });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {
                this.notifications = [];
            } finally {
                this.notifLoading = false;
            }
        },

        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },

        closeNotifOutside(event) {
            if (this.$refs.notifWrap && !this.$refs.notifWrap.contains(event.target)) {
                this.notifOpen = false;
            }
        },

        async markAllRead() {
            if (!this.routes.notifications_all) return;
            try {
                await fetch(this.routes.notifications_all, {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                });
                this.notifications.forEach((notif) => (notif.read = true));
                this.unreadCount = 0;
            } catch {}
        },

        openNotif(notif) {
            if (notif.link) window.location.href = notif.link;
        },

        async loadData() {
            this.error = "";
            try {
                const [usersRes, templatesRes] = await Promise.all([
                    fetch(this.routes.users, { headers: { Accept: "application/json" } }),
                    fetch(this.routes.templates_index, { headers: { Accept: "application/json" } }),
                ]);
                if (!usersRes.ok || !templatesRes.ok) throw new Error("Chargement impossible.");
                this.users = await usersRes.json();
                this.templates = await templatesRes.json();
                this.filterUsers();
            } catch (e) {
                this.error = e.message || "Impossible de charger les données.";
            }
        },

        async fetchHistory() {
            this.historyLoading = true;
            try {
                const res = await fetch(this.routes.history, { headers: { Accept: "application/json" } });
                if (!res.ok) throw new Error();
                this.history = await res.json();
            } catch {
                this.history = [];
            } finally {
                this.historyLoading = false;
            }
        },

        filterUsers() {
            const q = this.search.trim().toLowerCase();
            this.filteredUsers = !q
                ? this.users
                : this.users.filter((user) => [user.name, user.email, user.role].join(" ").toLowerCase().includes(q));
        },

        setComposeMode(mode) {
            this.composeMode = mode;
            if (mode === "manual") {
                this.selectedTemplateId = "";
            }
        },

        applyTemplate() {
            const template = this.templates.find((item) => String(item.id) === String(this.selectedTemplateId));
            if (!template) return;
            this.form.subject = template.subject || "";
            this.setEditorBody(template.body || "");
        },

        openTemplateForm() {
            this.editingTemplateId = null;
            this.templateOpen = true;
            this.templateForm.name = "";
            this.templateForm.subject = "";
            this.templateForm.body = "";
            this.$nextTick(() => {
                if (this.$refs.templateEditor) this.$refs.templateEditor.innerHTML = "";
            });
        },

        openTemplateEdit() {
            const template = this.templates.find((item) => String(item.id) === String(this.selectedTemplateId));
            if (!template) return;

            this.editingTemplateId = template.id;
            this.templateOpen = true;
            this.templateForm.name = template.name || "";
            this.templateForm.subject = template.subject || "";
            this.templateForm.body = template.body || "";
            this.$nextTick(() => {
                if (this.$refs.templateEditor) this.$refs.templateEditor.innerHTML = this.templateForm.body;
            });
        },

        closeTemplateForm() {
            if (this.savingTemplate) return;
            this.templateOpen = false;
            this.editingTemplateId = null;
        },

        async saveTemplate() {
            this.syncTemplateBody();
            this.message = "";
            this.error = "";
            if (!this.templateForm.name.trim() || !this.templateForm.body.trim()) {
                this.error = "Nom et contenu sont obligatoires pour creer un template.";
                return;
            }

            this.savingTemplate = true;
            try {
                const isEditing = Boolean(this.editingTemplateId);
                const url = isEditing
                    ? this.routes.templates_update.replace("{template}", this.editingTemplateId)
                    : this.routes.templates_store;
                const res = await fetch(url, {
                    method: isEditing ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                    body: JSON.stringify({
                        name: this.templateForm.name,
                        subject: this.templateForm.subject,
                        body: this.templateForm.body,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || "Création impossible.");
                if (isEditing) {
                    const index = this.templates.findIndex((item) => String(item.id) === String(data.id));
                    if (index !== -1) this.templates.splice(index, 1, data);
                } else {
                    this.templates.unshift(data);
                }
                this.selectedTemplateId = data.id;
                this.templateOpen = false;
                this.editingTemplateId = null;
                this.message = "Template créé.";
            } catch (e) {
                this.error = e.message || "Erreur pendant la création du template.";
            } finally {
                this.savingTemplate = false;
            }
        },

        focusEditor() {
            this.$refs.editor?.focus();
        },

        format(command) {
            this.focusEditor();
            document.execCommand(command, false, null);
            this.syncBody();
        },

        formatValue(command, value) {
            this.focusEditor();
            document.execCommand(command, false, value);
            this.syncBody();
        },

        insertMessageVariable(variable) {
            this.focusEditor();
            document.execCommand("insertText", false, variable);
            this.syncBody();
        },

        insertTextAtInputCursor(refName, currentValue, variable, setter) {
            const input = this.$refs[refName];
            const value = currentValue || "";
            if (!input || typeof input.selectionStart !== "number") {
                setter(`${value}${variable}`);
                return;
            }

            const start = input.selectionStart;
            const end = input.selectionEnd;
            const nextValue = `${value.slice(0, start)}${variable}${value.slice(end)}`;
            setter(nextValue);
            this.$nextTick(() => {
                input.focus();
                const cursor = start + variable.length;
                input.setSelectionRange(cursor, cursor);
            });
        },

        insertSubjectVariable(variable) {
            this.insertTextAtInputCursor("subjectInput", this.form.subject, variable, (value) => {
                this.form.subject = value;
            });
        },

        insertTemplateSubjectVariable(variable) {
            this.insertTextAtInputCursor("templateSubjectInput", this.templateForm.subject, variable, (value) => {
                this.templateForm.subject = value;
            });
        },

        focusTemplateEditor() {
            this.$refs.templateEditor?.focus();
        },

        syncTemplateBody() {
            this.templateForm.body = this.$refs.templateEditor?.innerHTML || "";
        },

        formatTemplate(command) {
            this.focusTemplateEditor();
            document.execCommand(command, false, null);
            this.syncTemplateBody();
        },

        formatTemplateValue(command, value) {
            this.focusTemplateEditor();
            document.execCommand(command, false, value);
            this.syncTemplateBody();
        },

        insertTemplateVariable(variable) {
            this.focusTemplateEditor();
            document.execCommand("insertText", false, variable);
            this.syncTemplateBody();
        },

        applyAlignment(alignment) {
            if (this.selectedImage) {
                this.alignSelectedImage(alignment);
                return;
            }

            if (this.selectedButton) {
                this.alignInlineElement(this.selectedButton, alignment);
                return;
            }

            const command =
                {
                    left: "justifyLeft",
                    center: "justifyCenter",
                    right: "justifyRight",
                }[alignment] || "justifyLeft";
            this.format(command);
        },

        alignSelectedImage(alignment) {
            const img = this.selectedImage;
            if (!img) return;

            img.style.display = "block";
            img.style.float = "none";

            if (alignment === "center") {
                img.style.marginLeft = "auto";
                img.style.marginRight = "auto";
            } else if (alignment === "right") {
                img.style.marginLeft = "auto";
                img.style.marginRight = "0";
            } else {
                img.style.marginLeft = "0";
                img.style.marginRight = "auto";
            }

            this.syncBody();
        },

        alignInlineElement(element, alignment) {
            if (!element) return;
            const wrapper =
                element.parentElement?.classList?.contains("amail-align-wrap")
                    ? element.parentElement
                    : null;
            const target = wrapper || element;
            target.style.textAlign = alignment;
            this.syncBody();
        },

        applyButtonColor() {
            if (!this.selectedButton) return;
            this.selectedButton.style.background = this.buttonColor;
            this.syncBody();
        },

        insertLink() {
            const url = window.prompt("URL du lien");
            if (!url) return;
            this.focusEditor();
            document.execCommand("createLink", false, url);
            this.syncBody();
        },

        insertButtonLink() {
            const label = window.prompt("Texte du bouton", "Voir le détail");
            if (!label) return;
            const url = window.prompt("URL du bouton");
            if (!url) return;
            const html = `<div class="amail-align-wrap" style="text-align:left;"><a href="${this.escapeHtml(url)}" target="_blank" rel="noopener" data-amail-button="true" style="display:inline-block;background:${this.escapeHtml(this.buttonColor)};color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">${this.escapeHtml(label)}</a></div>`;
            this.focusEditor();
            document.execCommand("insertHTML", false, html);
            this.syncBody();
        },

        insertTemplateLink() {
            const url = window.prompt("URL du lien");
            if (!url) return;
            this.focusTemplateEditor();
            document.execCommand("createLink", false, url);
            this.syncTemplateBody();
        },

        insertTemplateButtonLink() {
            const label = window.prompt("Texte du bouton", "Voir le detail");
            if (!label) return;
            const url = window.prompt("URL du bouton");
            if (!url) return;
            const html = `<div class="amail-align-wrap" style="text-align:left;"><a href="${this.escapeHtml(url)}" target="_blank" rel="noopener" data-amail-button="true" style="display:inline-block;background:${this.escapeHtml(this.buttonColor)};color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">${this.escapeHtml(label)}</a></div>`;
            this.focusTemplateEditor();
            document.execCommand("insertHTML", false, html);
            this.syncTemplateBody();
        },

        async uploadImage(event) {
            const file = event.target.files?.[0];
            event.target.value = "";
            if (!file) return;

            const payload = new FormData();
            payload.append("image", file);
            try {
                const res = await fetch(this.routes.upload_image, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                    body: payload,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || "Upload impossible.");
                this.focusEditor();
                document.execCommand("insertHTML", false, `<img src="${data.url}" style="max-width:100%;width:320px;height:auto;" alt="">`);
                this.syncBody();
            } catch (e) {
                this.error = e.message || "Impossible d'ajouter l'image.";
            }
        },

        async uploadTemplateImage(event) {
            const file = event.target.files?.[0];
            event.target.value = "";
            if (!file) return;

            const payload = new FormData();
            payload.append("image", file);
            try {
                const res = await fetch(this.routes.upload_image, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                    body: payload,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || "Upload impossible.");
                this.focusTemplateEditor();
                document.execCommand("insertHTML", false, `<img src="${data.url}" style="max-width:100%;width:320px;height:auto;" alt="">`);
                this.syncTemplateBody();
            } catch (e) {
                this.error = e.message || "Impossible d'ajouter l'image.";
            }
        },

        handleEditorClick(event) {
            if (event.target?.tagName === "IMG") {
                this.selectedImage = event.target;
                this.selectedButton = null;
                this.imageWidth = parseInt(event.target.style.width || event.target.width || 320, 10);
                this.markSelectedImage();
            } else if (event.target?.closest?.('a[data-amail-button="true"]')) {
                this.selectedImage = null;
                this.clearSelectedImageMark();
                this.selectedButton = event.target.closest('a[data-amail-button="true"]');
                this.buttonColor = this.rgbToHex(this.selectedButton.style.backgroundColor) || this.buttonColor;
            } else {
                this.clearSelectedImageMark();
                this.selectedImage = null;
                this.selectedButton = null;
            }
        },

        handleEditorMouseDown(event) {
            if (event.target?.tagName !== "IMG") return;

            const img = event.target;
            const rect = img.getBoundingClientRect();
            const nearResizeCorner =
                event.clientX >= rect.right - 18 &&
                event.clientY >= rect.bottom - 18;

            this.selectedImage = img;
            this.selectedButton = null;
            this.imageWidth = parseInt(img.style.width || img.width || 320, 10);
            this.markSelectedImage();

            if (!nearResizeCorner) return;

            event.preventDefault();
            this.imageResize = {
                active: true,
                image: img,
                startX: event.clientX,
                startWidth: this.imageWidth,
            };
            document.addEventListener("mousemove", this.onImageResizeMove);
            document.addEventListener("mouseup", this.stopImageResize);
        },

        onImageResizeMove(event) {
            if (!this.imageResize.active || !this.imageResize.image) return;
            const nextWidth = Math.min(
                1200,
                Math.max(
                    50,
                    this.imageResize.startWidth +
                        event.clientX -
                        this.imageResize.startX
                )
            );
            this.selectedImage = this.imageResize.image;
            this.imageWidth = nextWidth;
            this.resizeSelectedImage();
        },

        stopImageResize() {
            document.removeEventListener("mousemove", this.onImageResizeMove);
            document.removeEventListener("mouseup", this.stopImageResize);
            this.imageResize.active = false;
            this.imageResize.image = null;
        },

        markSelectedImage() {
            this.clearSelectedImageMark();
            if (this.selectedImage) {
                this.selectedImage.classList.add("amail-selected-image");
            }
        },

        clearSelectedImageMark() {
            this.$refs.editor
                ?.querySelectorAll(".amail-selected-image")
                .forEach((img) => img.classList.remove("amail-selected-image"));
        },

        resizeSelectedImage() {
            if (!this.selectedImage || !this.imageWidth) return;
            this.selectedImage.style.width = `${this.imageWidth}px`;
            this.selectedImage.style.maxWidth = "100%";
            this.selectedImage.style.height = "auto";
            this.syncBody();
        },

        clearImageSize() {
            if (!this.selectedImage) return;
            this.selectedImage.style.width = "";
            this.selectedImage.style.maxWidth = "100%";
            this.selectedImage.style.height = "auto";
            this.syncBody();
        },

        setAttachments(event) {
            this.attachments = Array.from(event.target.files || []);
        },

        syncBody() {
            this.form.body = this.$refs.editor?.innerHTML || "";
        },

        setEditorBody(html) {
            this.form.body = html;
            this.$nextTick(() => {
                if (this.$refs.editor) this.$refs.editor.innerHTML = html;
            });
        },

        async sendMail() {
            this.syncBody();
            this.message = "";
            this.error = "";

            if (!this.form.subject.trim() || !this.form.body.trim()) {
                this.error = "Le sujet et le contenu sont obligatoires.";
                return;
            }
            if (this.recipientCount === 0) {
                this.error = "Aucun destinataire sélectionné.";
                return;
            }
            if (this.form.recipient_mode === "manual" && this.manualRecipients.some((email) => !this.isValidEmail(email))) {
                this.error = "Une adresse email manuelle est invalide.";
                return;
            }

            const payload = new FormData();
            payload.append("subject", this.form.subject);
            payload.append("body", this.form.body);
            payload.append("recipient_mode", this.form.recipient_mode);
            this.form.roles.forEach((role) => payload.append("roles[]", role));
            this.form.user_ids.forEach((id) => payload.append("user_ids[]", id));
            this.manualRecipients.forEach((email) => payload.append("manual_recipients[]", email));
            this.attachments.forEach((file) => payload.append("attachments[]", file));

            this.sending = true;
            try {
                const res = await fetch(this.routes.send, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                    body: payload,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || "Envoi impossible.");
                this.message = data.message || "Mail envoyé.";
                this.fetchHistory();
            } catch (e) {
                this.error = e.message || "Erreur pendant l'envoi.";
            } finally {
                this.sending = false;
            }
        },

        roleLabel(role) {
            return this.roles.find((item) => item.value === role)?.label || role;
        },

        recipientModeLabel(mode) {
            return { all: "Tous", roles: "Par rôle", selected: "Sélection", manual: "Email manuel" }[mode] || mode;
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        escapeHtml(value) {
            return String(value)
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;");
        },

        rgbToHex(value) {
            const match = String(value || "").match(
                /rgba?\((\d+),\s*(\d+),\s*(\d+)/i
            );
            if (!match) return "";
            return (
                "#" +
                [match[1], match[2], match[3]]
                    .map((part) => Number(part).toString(16).padStart(2, "0"))
                    .join("")
            );
        },
    },
};
</script>

<style scoped>
.amail-wrap { min-height: 100vh; background: #f7f7fa; }
.amail-topbar, .amail-panel { background: #fff; border: 1px solid #ececf1; border-radius: 12px; }
.amail-topbar { display: flex; align-items: center; justify-content: space-between; padding: 0 32px; height: 64px; margin-bottom: 0; gap: 16px; border: 0; border-bottom: 1px solid #ececf1; border-radius: 0; position: sticky; top: 0; z-index: 50; }
.amail-topbar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
.amail-topbar h1, .amail-panel h2 { margin: 0; color: #1a1a2e; }
.amail-topbar h1 { font-size: 22px; }
.amail-topbar p, .amail-muted { margin: 4px 0 0; color: #777; }
.amail-top-actions, .amail-template-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.amail-page-actions { display: flex; gap: 12px; flex-wrap: wrap; padding: 24px 28px 0; }
.amail-action-btn, .amail-btn-template { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #fed7aa; background: #fff7ed; color: #9a3412; box-shadow: 0 6px 16px rgba(249, 115, 22, .12); }
.amail-action-btn:hover, .amail-btn-template:hover { background: #ffedd5; border-color: #fdba74; }
.amail-action-btn svg, .amail-btn-template svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex: 0 0 auto; }
.amail-btn-template { background: linear-gradient(135deg, #f97316, #ea580c); border-color: #ea580c; color: #fff; box-shadow: 0 8px 20px rgba(234, 88, 12, .22); }
.amail-btn-template:hover { background: linear-gradient(135deg, #ea580c, #c2410c); border-color: #c2410c; }
.amail-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--or, #f97316); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex: 0 0 auto; }
.amail-notif-wrap { position: relative; }
.amail-notif-btn { width: 34px; height: 34px; border: 0; border-radius: 10px; background: transparent; color: #555; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
.amail-notif-btn:hover { background: #fafafa; color: #333; }
.amail-notif-btn svg { width: 20px; height: 20px; }
.amail-notif-badge { position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.amail-notif-dropdown { position: absolute; right: 0; top: 46px; width: 340px; max-height: 420px; overflow-y: auto; background: #fff; border: 1px solid #ececf1; border-radius: 12px; box-shadow: 0 12px 34px rgba(0,0,0,.14); z-index: 100; }
.amail-notif-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #f1f1f1; font-weight: 800; color: #1a1a2e; }
.amail-notif-read-all { border: 0; background: transparent; color: #ea580c; font-weight: 700; cursor: pointer; font-size: 12px; }
.amail-notif-empty { padding: 22px; text-align: center; color: #777; font-size: 13px; }
.amail-notif-item { display: flex; gap: 10px; padding: 12px 16px; border-bottom: 1px solid #f7f7f7; cursor: pointer; }
.amail-notif-item:hover { background: #fafafa; }
.amail-notif-item.unread { background: #fff8f0; }
.amail-notif-dot { width: 8px; height: 8px; margin-top: 6px; border-radius: 50%; background: #f97316; flex: 0 0 auto; }
.amail-notif-title { font-size: 13px; font-weight: 800; color: #1a1a2e; }
.amail-notif-body { font-size: 12px; color: #666; margin-top: 2px; }
.amail-notif-ago { font-size: 11px; color: #999; margin-top: 4px; }
.amail-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; padding: 24px 28px 28px; }
.amail-panel { padding: 24px; }
.amail-send-panel { display: flex; flex-direction: column; min-height: 280px; }
.amail-history { margin: 24px 28px 28px; }
.amail-recipients { margin: 24px 28px 0; }
.amail-panel-head { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 18px; flex-wrap: wrap; }
.amail-panel label { display: block; margin: 14px 0 6px; font-size: 13px; font-weight: 700; color: #333; }
.amail-recipient-grid { display: grid; grid-template-columns: 260px 1fr; gap: 20px; align-items: end; }
.amail-input, .amail-panel-head select, .amail-toolbar select { width: 100%; border: 1px solid #ddd; border-radius: 10px; padding: 10px 12px; font: inherit; outline: none; background: #fff; }
.amail-manual-recipient { margin-top: 12px; max-width: 360px; }
.amail-textarea { resize: vertical; min-height: 44px; max-height: 78px; }
.amail-panel-head select { width: 220px; }
.amail-toolbar select { width: auto; max-width: 180px; }
.amail-input:focus, .amail-panel-head select:focus, .amail-editor:focus, .amail-toolbar select:focus { border-color: var(--or, #e67e22); }
.amail-compose-choice { display: flex; gap: 10px; flex-wrap: wrap; padding: 12px; margin-bottom: 16px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; }
.amail-choice-btn { border: 1px solid #ddd; border-radius: 10px; background: #fff; color: #374151; padding: 10px 14px; font-weight: 800; cursor: pointer; }
.amail-choice-btn.active { border-color: #f97316; background: #fff7ed; color: #9a3412; box-shadow: 0 6px 16px rgba(249, 115, 22, .12); }
.amail-template-select { max-width: 420px; margin-bottom: 16px; }
.amail-template-select-row { display: flex; align-items: center; gap: 10px; }
.amail-template-select-row select { min-width: 0; }
.amail-toolbar { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; border: 1px solid #ddd; border-bottom: 0; border-radius: 10px 10px 0 0; background: #fafafa; }
.amail-toolbar button { border: 1px solid #ddd; background: #fff; border-radius: 8px; padding: 8px 10px; cursor: pointer; font-weight: 700; }
.amail-align-btn {
    width: 38px;
    height: 36px;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
}
.amail-align-btn span {
    display: block;
    height: 2px;
    border-radius: 2px;
    background: #374151;
}
.amail-align-btn span:nth-child(1) { width: 18px; }
.amail-align-btn span:nth-child(2) { width: 24px; }
.amail-align-btn span:nth-child(3) { width: 14px; }
.amail-align-center { align-items: center; }
.amail-align-right { align-items: flex-end; }
.amail-color { display: inline-flex !important; align-items: center; gap: 6px; margin: 0 !important; padding: 6px 8px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
.amail-color input { width: 30px; height: 26px; border: 0; padding: 0; background: transparent; }
.amail-image-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid #fed7aa; background: #fff7ed; }
.amail-image-tools label { display: flex !important; align-items: center; gap: 8px; margin: 0; }
.amail-image-tools input { width: 100px; border: 1px solid #ddd; border-radius: 8px; padding: 7px; }
.amail-tool-hint { color: #9a3412; font-size: 12px; }
.amail-editor { min-height: 360px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; padding: 14px; outline: none; overflow: auto; background: #fff; }
.amail-editor :deep(img) { max-width: 100%; height: auto; cursor: nwse-resize; outline-offset: 3px; }
.amail-editor :deep(img:hover) { outline: 2px solid #f97316; }
.amail-editor :deep(.amail-selected-image) { outline: 2px solid #f97316; }
.amail-editor :deep(a[data-amail-button="true"]) { cursor: pointer; }
.amail-hint, .amail-summary { margin-top: 12px; color: #777; font-size: 13px; }
.amail-template-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 14px; margin-bottom: 18px; }
.amail-modal-backdrop { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(17, 24, 39, .55); }
.amail-modal { width: min(920px, 100%); max-height: calc(100vh - 48px); overflow: auto; background: #fff; border-radius: 12px; border: 1px solid #ececf1; padding: 22px; box-shadow: 0 24px 70px rgba(0,0,0,.24); }
.amail-modal::-webkit-scrollbar { width: 8px; }
.amail-modal::-webkit-scrollbar-track { background: #f8f4f0; }
.amail-modal::-webkit-scrollbar-thumb { background: var(--or, #f97316); border-radius: 99px; }
.amail-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; }
.amail-modal label { margin-top: 22px; }
.amail-modal .amail-input { margin-top: 6px; margin-bottom: 14px; }
.amail-modal .amail-toolbar { margin-top: 10px; margin-bottom: 14px; }
.amail-modal-close { width: 34px; height: 34px; border: 0; border-radius: 8px; background: #f3f4f6; color: #374151; cursor: pointer; font-weight: 800; }
.amail-template-editor { min-height: 300px; }
.amail-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
.amail-variable-picker { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 14px 0 12px; margin-top: 6px; }
.amail-variable-picker-compact { padding: 8px 0 4px; margin-top: 0; }
.amail-variable-picker span { font-size: 13px; font-weight: 800; color: #333; margin-right: 2px; }
.amail-variable-picker button { border: 1px solid #fed7aa; border-radius: 999px; background: #fff7ed; color: #9a3412; padding: 7px 10px; font-size: 12px; font-weight: 800; cursor: pointer; }
.amail-variable-picker button:hover { background: #ffedd5; }
.amail-checkline { display: flex !important; align-items: center; gap: 8px; }
.amail-checks { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 8px; }
.amail-checks label, .amail-user { display: flex; align-items: flex-start; gap: 14px; }
.amail-user input[type="checkbox"] { flex: 0 0 auto; width: 16px; height: 16px; margin: 3px 0 0 8px; }
.amail-user span { display: block; min-width: 0; padding-left: 2px; }
.amail-users { max-height: 260px; overflow: auto; border: 1px solid #eee; border-radius: 10px; padding: 8px; margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 6px; }
.amail-user { padding: 8px; border-radius: 8px; }
.amail-user:hover { background: #fafafa; }
.amail-user small { display: block; color: #777; }
.amail-attachments { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.amail-attachments span { background: #f3f4f6; border-radius: 999px; padding: 6px 10px; font-size: 12px; }
.amail-history-list { display: grid; gap: 10px; }
.amail-history-item { display: flex; justify-content: space-between; gap: 14px; border: 1px solid #eee; border-radius: 10px; padding: 12px; }
.amail-history-item small { display: block; color: #777; margin-top: 4px; }
.amail-btn { border: 0; border-radius: 10px; padding: 11px 18px; font-weight: 700; cursor: pointer; }
.amail-btn.small { padding: 9px 14px; }
.amail-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.amail-icon-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.amail-icon-btn svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex: 0 0 auto; }
.amail-btn-primary { width: 100%; margin-top: 18px; color: #fff; background: linear-gradient(135deg, #f97316, #ea580c); }
.amail-btn-primary.small { width: auto; margin-top: 0; }
.amail-btn-success { width: 100%; margin-top: 18px; color: #fff; background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 8px 22px rgba(34, 197, 94, .26); }
.amail-btn-success.small { width: auto; margin-top: 0; }
.amail-btn-success:hover:not(:disabled) { background: linear-gradient(135deg, #16a34a, #15803d); }
.amail-send-btn { align-self: stretch; }
.amail-send-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.amail-send-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.amail-btn-ghost, .amail-btn-light { color: #ea580c; background: #fff7ed; }
.amail-success, .amail-error { margin: 14px 0 0; font-size: 14px; font-weight: 700; }
.amail-success { color: #15803d; }
.amail-error { color: #b91c1c; }
@media (max-width: 900px) {
    .amail-grid, .amail-recipient-grid { grid-template-columns: 1fr; }
    .amail-topbar { align-items: center; padding: 0 16px; }
    .amail-top-actions { justify-content: flex-end; }
    .amail-page-actions { padding: 18px 16px 0; }
    .amail-grid { padding: 18px 16px 22px; }
    .amail-history { margin: 18px 16px 22px; }
    .amail-recipients { margin: 18px 16px 0; }
    .amail-notif-dropdown { right: -48px; width: min(320px, calc(100vw - 32px)); }
}
</style>
