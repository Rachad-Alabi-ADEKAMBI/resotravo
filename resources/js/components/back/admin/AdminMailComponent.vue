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
            <button class="amail-btn amail-btn-ghost" type="button" @click="toggleHistory">
                {{ showHistory ? "Nouveau message" : "Historique" }}
            </button>
            <button class="amail-btn amail-btn-ghost" type="button" @click="showHistory ? fetchHistory() : loadData()">
                Actualiser
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
                        </select>
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
                            <select v-model="selectedTemplateId" @change="applyTemplate">
                                <option value="">Template</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                            <button class="amail-btn amail-btn-light" type="button" @click="openTemplateForm">
                                Nouveau template
                            </button>
                        </div>
                    </div>

                    <div v-if="templateOpen" class="amail-template-box">
                        <label>Nom du template</label>
                        <input v-model="templateForm.name" class="amail-input" type="text" maxlength="120" />
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
                    <input v-model="form.subject" class="amail-input" type="text" maxlength="180" />

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
                        <label class="amail-color">
                            Texte
                            <input type="color" v-model="textColor" @input="formatValue('foreColor', textColor)" />
                        </label>
                        <label class="amail-color">
                            Fond
                            <input type="color" v-model="backgroundColor" @input="formatValue('hiliteColor', backgroundColor)" />
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
                    </div>

                    <input ref="imageInput" type="file" accept="image/*" hidden @change="uploadImage" />
                    <div
                        ref="editor"
                        class="amail-editor"
                        contenteditable="true"
                        @click="handleEditorClick"
                        @input="syncBody"
                        @blur="syncBody"
                    ></div>

                    <div class="amail-hint">Variables disponibles : {name}, {email}, {role}</div>

                    <label>Pièces jointes</label>
                    <input class="amail-input" type="file" multiple @change="setAttachments" />
                    <div class="amail-attachments" v-if="attachments.length">
                        <span v-for="file in attachments" :key="file.name">{{ file.name }}</span>
                    </div>
                    <button class="amail-btn amail-btn-success amail-send-btn" type="button" :disabled="sending" @click="sendMail">
                        {{ sending ? "Envoi..." : "Envoyer" }}
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
            search: "",
            sending: false,
            savingTemplate: false,
            historyLoading: false,
            showHistory: false,
            templateOpen: false,
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
            textColor: "#111827",
            backgroundColor: "#ffffff",
            selectedImage: null,
            imageWidth: 320,
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
            templateForm: {
                name: "",
                is_default: false,
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
            if (this.form.recipient_mode === "selected") return this.form.user_ids.length;
            if (this.form.recipient_mode === "roles") {
                return this.users.filter((user) => this.form.roles.includes(user.role)).length;
            }
            return this.users.length;
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

        applyTemplate() {
            const template = this.templates.find((item) => String(item.id) === String(this.selectedTemplateId));
            if (!template) return;
            this.form.subject = template.subject || "";
            this.setEditorBody(template.body || "");
        },

        openTemplateForm() {
            this.templateOpen = true;
            this.templateForm.name = "";
            this.templateForm.is_default = false;
        },

        async saveTemplate() {
            this.syncBody();
            this.message = "";
            this.error = "";
            if (!this.templateForm.name.trim() || !this.form.subject.trim() || !this.form.body.trim()) {
                this.error = "Nom, sujet et contenu sont obligatoires pour créer un template.";
                return;
            }

            this.savingTemplate = true;
            try {
                const res = await fetch(this.routes.templates_store, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf(),
                    },
                    body: JSON.stringify({
                        name: this.templateForm.name,
                        subject: this.form.subject,
                        body: this.form.body,
                        is_default: this.templateForm.is_default,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || "Création impossible.");
                this.templates.unshift(data);
                this.selectedTemplateId = data.id;
                this.templateOpen = false;
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
            const html = `<a href="${this.escapeHtml(url)}" target="_blank" rel="noopener" style="display:inline-block;background:#f97316;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">${this.escapeHtml(label)}</a>`;
            this.focusEditor();
            document.execCommand("insertHTML", false, html);
            this.syncBody();
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

        handleEditorClick(event) {
            if (event.target?.tagName === "IMG") {
                this.selectedImage = event.target;
                this.imageWidth = parseInt(event.target.style.width || event.target.width || 320, 10);
            } else {
                this.selectedImage = null;
            }
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

            const payload = new FormData();
            payload.append("subject", this.form.subject);
            payload.append("body", this.form.body);
            payload.append("recipient_mode", this.form.recipient_mode);
            this.form.roles.forEach((role) => payload.append("roles[]", role));
            this.form.user_ids.forEach((id) => payload.append("user_ids[]", id));
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
            return { all: "Tous", roles: "Par rôle", selected: "Sélection" }[mode] || mode;
        },

        escapeHtml(value) {
            return String(value)
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;");
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
.amail-panel-head select { width: 220px; }
.amail-toolbar select { width: auto; max-width: 180px; }
.amail-input:focus, .amail-panel-head select:focus, .amail-editor:focus, .amail-toolbar select:focus { border-color: var(--or, #e67e22); }
.amail-toolbar { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; border: 1px solid #ddd; border-bottom: 0; border-radius: 10px 10px 0 0; background: #fafafa; }
.amail-toolbar button { border: 1px solid #ddd; background: #fff; border-radius: 8px; padding: 8px 10px; cursor: pointer; font-weight: 700; }
.amail-color { display: inline-flex !important; align-items: center; gap: 6px; margin: 0 !important; padding: 6px 8px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
.amail-color input { width: 30px; height: 26px; border: 0; padding: 0; background: transparent; }
.amail-image-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid #fed7aa; background: #fff7ed; }
.amail-image-tools label { display: flex !important; align-items: center; gap: 8px; margin: 0; }
.amail-image-tools input { width: 100px; border: 1px solid #ddd; border-radius: 8px; padding: 7px; }
.amail-editor { min-height: 360px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; padding: 14px; outline: none; overflow: auto; background: #fff; }
.amail-editor :deep(img) { max-width: 100%; height: auto; cursor: pointer; outline-offset: 3px; }
.amail-editor :deep(img:hover) { outline: 2px solid #f97316; }
.amail-hint, .amail-summary { margin-top: 12px; color: #777; font-size: 13px; }
.amail-template-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 14px; margin-bottom: 18px; }
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
.amail-btn-primary { width: 100%; margin-top: 18px; color: #fff; background: linear-gradient(135deg, #f97316, #ea580c); }
.amail-btn-primary.small { width: auto; margin-top: 0; }
.amail-btn-success { width: 100%; margin-top: 18px; color: #fff; background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 8px 22px rgba(34, 197, 94, .26); }
.amail-btn-success:hover:not(:disabled) { background: linear-gradient(135deg, #16a34a, #15803d); }
.amail-send-btn { align-self: stretch; }
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
