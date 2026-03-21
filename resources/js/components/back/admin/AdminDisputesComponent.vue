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
                    <div class="amis-page-title">Litiges</div>
                    <div class="amis-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="amis-topbar-right">
                <div class="amis-count-pill">
                    {{ totalFiltered }} litige{{ totalFiltered > 1 ? "s" : "" }}
                </div>
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
                            <span>Notifications</span
                            ><button
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
                <button
                    class="amis-btn amis-btn-orange"
                    @click="openCreateModal"
                >
                    ⚖️ Ouvrir un litige
                </button>
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
            <!-- ── LISTE LITIGES ── -->
            <div class="ac-tickets-panel">
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
                            placeholder="Mission, client, prestataire…"
                            @input="currentPage = 1"
                        />
                    </div>
                    <select
                        class="amis-select ac-select-full"
                        v-model="filterStatus"
                        @change="currentPage = 1"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="open">Ouvert</option>
                        <option value="in_progress">En cours</option>
                        <option value="resolved_client">Résolu — Client</option>
                        <option value="resolved_contractor">
                            Résolu — Prestataire
                        </option>
                        <option value="closed">Clôturé</option>
                    </select>
                </div>

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
                        {{ t.label
                        }}<span class="amis-tab-count">{{
                            countByTab(t.key)
                        }}</span>
                    </button>
                </div>

                <div v-if="loading" class="ac-ticket-list">
                    <div
                        class="ac-ticket-skeleton"
                        v-for="n in 5"
                        :key="n"
                    ></div>
                </div>
                <div class="amis-alert-error" v-else-if="loadError">
                    ⚠️ {{ loadError }}
                    <button
                        class="amis-btn amis-btn-ghost amis-btn-sm"
                        @click="fetchDisputes"
                    >
                        Réessayer
                    </button>
                </div>
                <div class="amis-empty" v-else-if="pagedDisputes.length === 0">
                    <div class="amis-empty-icon">⚖️</div>
                    <div class="amis-empty-title">Aucun litige trouvé</div>
                    <div class="amis-empty-sub">
                        Modifiez vos filtres ou ouvrez un nouveau litige.
                    </div>
                </div>

                <div class="ac-ticket-list" v-else>
                    <div
                        class="ac-ticket-item"
                        v-for="d in pagedDisputes"
                        :key="d.id"
                        :class="{ active: activeDispute?.id === d.id }"
                        @click="openDispute(d)"
                    >
                        <div class="ac-ticket-head">
                            <div class="ac-ticket-user">
                                ⚖️ #{{ d.id }} · {{ d.mission_service }}
                            </div>
                            <span
                                class="amis-badge"
                                :class="badgeClass(d.status)"
                                >{{ d.status_label }}</span
                            >
                        </div>
                        <div class="ac-ticket-subject">{{ d.subject }}</div>
                        <div class="ac-ticket-meta">
                            <span>👤 {{ d.client_name }}</span>
                            <span>👷 {{ d.contractor_name }}</span>
                            <span>{{ d.opened_ago }}</span>
                            <span v-if="d.attachments_count > 0"
                                >📎 {{ d.attachments_count }}</span
                            >
                        </div>
                    </div>
                </div>

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

            <!-- ── DÉTAIL LITIGE ── -->
            <div class="ac-chat-panel">
                <div class="ac-chat-empty" v-if="!activeDispute">
                    <div class="ac-chat-empty-icon">⚖️</div>
                    <div class="ac-chat-empty-title">
                        Sélectionnez un litige
                    </div>
                    <div class="ac-chat-empty-sub">
                        Cliquez sur un litige à gauche pour afficher les
                        détails.
                    </div>
                </div>

                <template v-else>
                    <!-- Header -->
                    <div class="ac-chat-header">
                        <div class="ac-chat-header-left">
                            <div class="ac-chat-avatar">⚖️</div>
                            <div>
                                <div class="ac-chat-name">
                                    {{ activeDispute.subject }}
                                </div>
                                <div class="ac-chat-sub">
                                    Mission #{{ activeDispute.mission_id }} ·
                                    {{ activeDispute.mission_service }} · Ouvert
                                    {{ activeDispute.opened_ago }}
                                </div>
                            </div>
                        </div>
                        <div class="ac-chat-header-right">
                            <span
                                class="amis-badge"
                                :class="badgeClass(activeDispute.status)"
                                >{{ activeDispute.status_label }}</span
                            >
                            <button
                                class="amis-btn amis-btn-orange amis-btn-sm"
                                v-if="!activeDispute.is_resolved"
                                @click="verdictModal.visible = true"
                            >
                                ⚖️ Rendre un verdict
                            </button>
                            <button
                                class="amis-btn amis-btn-ghost amis-btn-sm"
                                v-if="!activeDispute.is_resolved"
                                @click="closeModal.visible = true"
                            >
                                🔒 Clôturer
                            </button>
                        </div>
                    </div>

                    <!-- Infos parties -->
                    <div class="ad-parties-bar">
                        <div class="ad-party">
                            <span class="ad-party-role">👤 Client</span>
                            <span class="ad-party-name">{{
                                activeDispute.client_name
                            }}</span>
                            <span class="ad-party-email">{{
                                activeDispute.client_email
                            }}</span>
                        </div>
                        <div class="ad-party-vs">VS</div>
                        <div class="ad-party">
                            <span class="ad-party-role">👷 Prestataire</span>
                            <span class="ad-party-name">{{
                                activeDispute.contractor_name
                            }}</span>
                            <span class="ad-party-email">{{
                                activeDispute.contractor_email
                            }}</span>
                        </div>
                        <div
                            class="ad-party ad-party-verdict"
                            v-if="activeDispute.verdict"
                        >
                            <span class="ad-party-role">⚖️ Verdict</span>
                            <span class="ad-party-name">{{
                                activeDispute.verdict_label
                            }}</span>
                            <span
                                class="ad-party-email"
                                v-if="activeDispute.verdict_note"
                                >{{ activeDispute.verdict_note }}</span
                            >
                            <span
                                class="ad-suspended-chip"
                                v-if="activeDispute.contractor_suspended"
                                >🔒 Prestataire suspendu</span
                            >
                        </div>
                    </div>

                    <!-- Tabs détail -->
                    <div class="ad-detail-tabs">
                        <button
                            class="ad-dtab"
                            :class="{ active: detailTab === 'messages' }"
                            @click="detailTab = 'messages'"
                        >
                            💬 Messages
                            <span class="amis-tab-count">{{
                                activeMessages.length
                            }}</span>
                        </button>
                        <button
                            class="ad-dtab"
                            :class="{ active: detailTab === 'attachments' }"
                            @click="detailTab = 'attachments'"
                        >
                            📎 Pièces jointes
                            <span class="amis-tab-count">{{
                                activeAttachments.length
                            }}</span>
                        </button>
                        <button
                            class="ad-dtab"
                            :class="{ active: detailTab === 'mission' }"
                            @click="detailTab = 'mission'"
                        >
                            📋 Mission
                        </button>
                    </div>

                    <!-- Loader -->
                    <div class="ac-msg-loading" v-if="detailLoading">
                        <div
                            class="amis-spinner"
                            style="
                                border-color: rgba(0, 0, 0, 0.15);
                                border-top-color: var(--or);
                            "
                        ></div>
                    </div>

                    <template v-else>
                        <!-- ── TAB MESSAGES ── -->
                        <div
                            class="ac-messages-wrap"
                            ref="messagesContainer"
                            v-if="detailTab === 'messages'"
                        >
                            <div
                                v-for="msg in activeMessages"
                                :key="msg.id"
                                class="ad-msg-row"
                                :class="[
                                    'role-' + msg.sender_role,
                                    { internal: msg.is_internal },
                                ]"
                            >
                                <div class="ad-msg-avatar">
                                    {{
                                        msg.sender_role === "admin"
                                            ? "👨‍💼"
                                            : msg.sender_role === "client"
                                              ? "👤"
                                              : "👷"
                                    }}
                                </div>
                                <div class="ad-msg-content">
                                    <div class="ad-msg-meta">
                                        <span class="ad-msg-name">{{
                                            msg.sender_name
                                        }}</span>
                                        <span
                                            class="ad-msg-role-chip"
                                            :class="'chip-' + msg.sender_role"
                                            >{{
                                                {
                                                    admin: "Admin",
                                                    client: "Client",
                                                    contractor: "Prestataire",
                                                }[msg.sender_role]
                                            }}</span
                                        >
                                        <span
                                            class="ad-internal-chip"
                                            v-if="msg.is_internal"
                                            >🔐 Interne</span
                                        >
                                        <span class="ad-msg-time">{{
                                            msg.ago
                                        }}</span>
                                    </div>
                                    <div
                                        class="ad-msg-bubble"
                                        :class="'bubble-' + msg.sender_role"
                                    >
                                        {{ msg.body }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="ac-msg-empty"
                                v-if="activeMessages.length === 0"
                            >
                                Aucun message pour ce litige.
                            </div>
                        </div>

                        <!-- ── TAB PIÈCES JOINTES ── -->
                        <div
                            class="ad-attachments-wrap"
                            v-else-if="detailTab === 'attachments'"
                        >
                            <div class="ad-attach-grid">
                                <div
                                    class="ad-attach-item"
                                    v-for="a in activeAttachments"
                                    :key="a.id"
                                >
                                    <a
                                        :href="a.url"
                                        target="_blank"
                                        class="ad-attach-preview"
                                    >
                                        <img
                                            :src="a.url"
                                            v-if="a.is_image"
                                            class="ad-attach-img"
                                            :alt="a.filename"
                                        />
                                        <div class="ad-attach-file-icon" v-else>
                                            📄
                                        </div>
                                    </a>
                                    <div class="ad-attach-info">
                                        <div class="ad-attach-name">
                                            {{ a.filename }}
                                        </div>
                                        <div class="ad-attach-meta">
                                            {{ a.uploader_name }} ·
                                            {{ a.size_formatted }} ·
                                            {{ a.created_at }}
                                        </div>
                                        <span
                                            class="ad-msg-role-chip"
                                            :class="'chip-' + a.uploader_role"
                                            >{{
                                                {
                                                    admin: "Admin",
                                                    client: "Client",
                                                    contractor: "Prestataire",
                                                }[a.uploader_role]
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div
                                class="ac-msg-empty"
                                v-if="activeAttachments.length === 0"
                            >
                                Aucune pièce jointe.
                            </div>

                            <!-- Upload -->
                            <div
                                class="ad-upload-bar"
                                v-if="!activeDispute.is_resolved"
                            >
                                <label class="ad-upload-btn">
                                    <input
                                        type="file"
                                        accept="image/*,.pdf,.doc,.docx"
                                        @change="uploadFile"
                                        ref="fileInput"
                                        style="display: none"
                                    />
                                    <span v-if="uploadLoading"
                                        >⏳ Envoi...</span
                                    >
                                    <span v-else
                                        >📎 Ajouter une pièce jointe</span
                                    >
                                </label>
                                <span class="ad-upload-hint"
                                    >JPG, PNG, PDF, DOC — max 10 Mo</span
                                >
                            </div>
                        </div>

                        <!-- ── TAB MISSION ── -->
                        <div
                            class="ad-mission-wrap"
                            v-else-if="detailTab === 'mission'"
                        >
                            <div class="ad-mission-grid">
                                <div class="amis-detail-section">
                                    <div class="amis-detail-section-title">
                                        📋 Informations mission
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Service</span
                                        ><strong>{{
                                            activeDispute.mission_service
                                        }}</strong>
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Statut</span
                                        ><strong>{{
                                            activeDispute.mission_status
                                        }}</strong>
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Client</span
                                        ><strong>{{
                                            activeDispute.client_name
                                        }}</strong>
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Prestataire</span
                                        ><strong>{{
                                            activeDispute.contractor_name
                                        }}</strong>
                                    </div>
                                </div>
                                <div class="amis-detail-section">
                                    <div class="amis-detail-section-title">
                                        ⚖️ Informations litige
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Ouvert le</span
                                        ><strong>{{
                                            activeDispute.opened_at_label
                                        }}</strong>
                                    </div>
                                    <div class="amis-detail-row">
                                        <span>Géré par</span
                                        ><strong>{{
                                            activeDispute.admin_name
                                        }}</strong>
                                    </div>
                                    <div
                                        class="amis-detail-row"
                                        v-if="activeDispute.resolved_at_label"
                                    >
                                        <span>Résolu le</span
                                        ><strong>{{
                                            activeDispute.resolved_at_label
                                        }}</strong>
                                    </div>
                                    <div
                                        class="amis-detail-row"
                                        v-if="activeDispute.verdict"
                                    >
                                        <span>Verdict</span
                                        ><strong>{{
                                            activeDispute.verdict_label
                                        }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="amis-detail-section"
                                style="margin: 0 16px 16px"
                            >
                                <div class="amis-detail-section-title">
                                    📝 Description du litige
                                </div>
                                <p
                                    style="
                                        font-size: 13.5px;
                                        color: var(--dk);
                                        line-height: 1.7;
                                        margin: 0;
                                    "
                                >
                                    {{ activeDispute.description }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- Zone de réponse -->
                    <div
                        class="ac-reply-bar"
                        v-if="
                            detailTab === 'messages' &&
                            !activeDispute.is_resolved
                        "
                    >
                        <div class="ad-reply-options">
                            <label class="ad-internal-toggle">
                                <input
                                    type="checkbox"
                                    v-model="replyInternal"
                                />
                                <span
                                    >🔐 Note interne (non visible par les
                                    parties)</span
                                >
                            </label>
                        </div>
                        <textarea
                            class="ac-reply-input"
                            v-model="replyText"
                            rows="3"
                            :placeholder="
                                replyInternal
                                    ? 'Note interne visible uniquement par les admins…'
                                    : 'Message visible par le client et le prestataire…'
                            "
                            @keydown.ctrl.enter="sendMessage"
                        ></textarea>
                        <div class="ac-reply-actions">
                            <span class="ac-reply-hint"
                                >Ctrl+Entrée pour envoyer</span
                            >
                            <button
                                class="amis-btn amis-btn-orange"
                                @click="sendMessage"
                                :disabled="
                                    !replyText.trim() ||
                                    actionLoading === 'message'
                                "
                            >
                                <div
                                    class="amis-spinner"
                                    v-if="actionLoading === 'message'"
                                ></div>
                                <span v-else>{{
                                    replyInternal
                                        ? "🔐 Ajouter note"
                                        : "Envoyer →"
                                }}</span>
                            </button>
                        </div>
                    </div>
                    <div
                        class="ac-reply-closed"
                        v-else-if="
                            detailTab === 'messages' &&
                            activeDispute.is_resolved
                        "
                    >
                        <span
                            >🔒 Ce litige est
                            {{
                                activeDispute.status_label.toLowerCase()
                            }}.</span
                        >
                    </div>
                </template>
            </div>
        </div>

        <!-- ══════════════ MODAL CRÉER LITIGE ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="createModal.visible"
            @click.self="createModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div>
                        <h3>⚖️ Ouvrir un litige</h3>
                        <div class="amis-modal-sub">
                            Sélectionnez la mission concernée
                        </div>
                    </div>
                    <button
                        class="amis-modal-close"
                        @click="createModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <div class="asf-field">
                        <label class="amis-form-label"
                            >Mission concernée
                            <span style="color: var(--or)">*</span></label
                        >
                        <div
                            class="amis-search-wrap"
                            style="margin-bottom: 8px"
                        >
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
                                v-model="missionSearch"
                                placeholder="Rechercher une mission…"
                                @input="searchMissions"
                            />
                        </div>
                        <div
                            class="ad-mission-list"
                            v-if="missionResults.length > 0"
                        >
                            <div
                                class="ad-mission-option"
                                v-for="m in missionResults"
                                :key="m.id"
                                :class="{
                                    selected: createModal.mission_id === m.id,
                                }"
                                @click="selectMission(m)"
                            >
                                <div class="ad-mission-opt-title">
                                    #{{ m.id }} — {{ m.service }}
                                </div>
                                <div class="ad-mission-opt-meta">
                                    👤 {{ m.client_name }} · 👷
                                    {{ m.contractor_name }} · {{ m.created_at }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="ad-mission-selected"
                            v-if="createModal.selectedMission"
                        >
                            ✅ Mission sélectionnée :
                            <strong
                                >#{{ createModal.mission_id }} —
                                {{
                                    createModal.selectedMission.service
                                }}</strong
                            >
                        </div>
                    </div>
                    <div class="asf-field">
                        <label class="amis-form-label"
                            >Objet du litige
                            <span style="color: var(--or)">*</span></label
                        >
                        <input
                            class="amis-input"
                            type="text"
                            v-model="createModal.subject"
                            placeholder="Ex : Travaux non conformes, paiement contesté…"
                        />
                    </div>
                    <div class="asf-field">
                        <label class="amis-form-label"
                            >Description
                            <span style="color: var(--or)">*</span></label
                        >
                        <textarea
                            class="amis-textarea"
                            v-model="createModal.description"
                            rows="4"
                            placeholder="Décrivez les faits en détail…"
                        ></textarea>
                    </div>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="createModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-orange"
                        @click="submitCreate"
                        :disabled="
                            !createModal.mission_id ||
                            !createModal.subject.trim() ||
                            !createModal.description.trim() ||
                            createModal.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="createModal.loading"
                        ></div>
                        <span v-else>⚖️ Ouvrir le litige</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL VERDICT ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="verdictModal.visible"
            @click.self="verdictModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div>
                        <h3>⚖️ Rendre un verdict</h3>
                        <div class="amis-modal-sub">
                            Litige #{{ activeDispute?.id }} —
                            {{ activeDispute?.subject }}
                        </div>
                    </div>
                    <button
                        class="amis-modal-close"
                        @click="verdictModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <div class="asf-field">
                        <label class="amis-form-label"
                            >Verdict
                            <span style="color: var(--or)">*</span></label
                        >
                        <div class="ad-verdict-options">
                            <label
                                class="ad-verdict-opt"
                                :class="{
                                    selected: verdictModal.verdict === 'client',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="verdictModal.verdict"
                                    value="client"
                                />
                                <span class="ad-verdict-icon">👤</span>
                                <div>
                                    <strong>En faveur du client</strong>
                                    <p>
                                        Le client a raison, le prestataire est
                                        en tort.
                                    </p>
                                </div>
                            </label>
                            <label
                                class="ad-verdict-opt"
                                :class="{
                                    selected:
                                        verdictModal.verdict === 'contractor',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="verdictModal.verdict"
                                    value="contractor"
                                />
                                <span class="ad-verdict-icon">👷</span>
                                <div>
                                    <strong>En faveur du prestataire</strong>
                                    <p>
                                        Le prestataire a bien exécuté sa
                                        mission.
                                    </p>
                                </div>
                            </label>
                            <label
                                class="ad-verdict-opt"
                                :class="{
                                    selected: verdictModal.verdict === 'shared',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="verdictModal.verdict"
                                    value="shared"
                                />
                                <span class="ad-verdict-icon">⚖️</span>
                                <div>
                                    <strong>Responsabilité partagée</strong>
                                    <p>
                                        Les deux parties ont une part de
                                        responsabilité.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="asf-field">
                        <label class="amis-form-label"
                            >Explication du verdict</label
                        >
                        <textarea
                            class="amis-textarea"
                            v-model="verdictModal.note"
                            rows="3"
                            placeholder="Justifiez votre décision…"
                        ></textarea>
                    </div>
                    <div
                        class="asf-field"
                        v-if="
                            verdictModal.verdict === 'client' ||
                            verdictModal.verdict === 'shared'
                        "
                    >
                        <label class="asf-checkbox">
                            <input
                                type="checkbox"
                                v-model="verdictModal.suspend_contractor"
                            />
                            <span>🔒 Suspendre le compte du prestataire</span>
                        </label>
                    </div>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="verdictModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-orange"
                        @click="submitVerdict"
                        :disabled="
                            !verdictModal.verdict || verdictModal.loading
                        "
                    >
                        <div
                            class="amis-spinner"
                            v-if="verdictModal.loading"
                        ></div>
                        <span v-else>✅ Confirmer le verdict</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL CLÔTURE ══════════════ -->
        <div
            class="amis-modal-overlay"
            v-if="closeModal.visible"
            @click.self="closeModal.visible = false"
        >
            <div class="amis-modal">
                <div class="amis-modal-header">
                    <div><h3>🔒 Clôturer le litige</h3></div>
                    <button
                        class="amis-modal-close"
                        @click="closeModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="amis-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            margin-bottom: 14px;
                        "
                    >
                        Clôturer ce litige sans rendre de verdict formel. Les
                        parties seront notifiées.
                    </p>
                    <label class="amis-form-label">Motif (optionnel)</label>
                    <textarea
                        class="amis-textarea"
                        v-model="closeModal.reason"
                        rows="3"
                        placeholder="Ex : Accord amiable trouvé entre les parties…"
                    ></textarea>
                </div>
                <div class="amis-modal-footer">
                    <button
                        class="amis-btn amis-btn-ghost"
                        @click="closeModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="amis-btn amis-btn-red"
                        @click="submitClose"
                        :disabled="closeModal.loading"
                    >
                        <div
                            class="amis-spinner"
                            v-if="closeModal.loading"
                        ></div>
                        <span v-else>🔒 Clôturer</span>
                    </button>
                </div>
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
    name: "AdminDisputesComponent",
    props: {
        user: { type: Object, required: true },
        routes: { type: Object, required: true },
    },
    data() {
        return {
            disputes: [],
            loading: false,
            loadError: null,

            search: "",
            filterStatus: "",
            activeTab: "all",
            currentPage: 1,
            perPage: 12,

            activeDispute: null,
            activeMessages: [],
            activeAttachments: [],
            detailLoading: false,
            detailTab: "messages",

            replyText: "",
            replyInternal: false,
            actionLoading: null,
            uploadLoading: false,

            // Modals
            createModal: {
                visible: false,
                loading: false,
                mission_id: null,
                selectedMission: null,
                subject: "",
                description: "",
            },
            verdictModal: {
                visible: false,
                loading: false,
                verdict: "",
                note: "",
                suspend_contractor: false,
            },
            closeModal: { visible: false, loading: false, reason: "" },

            // Recherche mission
            missionSearch: "",
            missionResults: [],
            missionSearchTimer: null,

            sidebarOpen: false,
            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,
            toasts: [],
            toastId: 0,
        };
    },

    computed: {
        greeting() {
            const h = new Date().getHours();
            return h < 12 ? "Bonjour" : h < 18 ? "Bon après-midi" : "Bonsoir";
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
                { key: "open", label: "Ouverts" },
                { key: "in_progress", label: "En cours" },
                { key: "resolved_client", label: "Résolu Client" },
                { key: "resolved_contractor", label: "Résolu Prest." },
                { key: "closed", label: "Clôturés" },
            ];
        },
        statCards() {
            return [
                {
                    dotClass: "dot-gray",
                    value: this.disputes.length,
                    label: "Total",
                },
                {
                    dotClass: "dot-red",
                    value: this.disputes.filter((d) => d.status === "open")
                        .length,
                    label: "Ouverts",
                },
                {
                    dotClass: "dot-blue",
                    value: this.disputes.filter(
                        (d) => d.status === "in_progress",
                    ).length,
                    label: "En cours",
                },
                {
                    dotClass: "dot-green",
                    value: this.disputes.filter((d) =>
                        ["resolved_client", "resolved_contractor"].includes(
                            d.status,
                        ),
                    ).length,
                    label: "Résolus",
                },
                {
                    dotClass: "dot-gray",
                    value: this.disputes.filter((d) => d.status === "closed")
                        .length,
                    label: "Clôturés",
                },
            ];
        },
        filteredDisputes() {
            let list = [...this.disputes];
            if (this.activeTab !== "all")
                list = list.filter((d) => d.status === this.activeTab);
            if (this.filterStatus)
                list = list.filter((d) => d.status === this.filterStatus);
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (d) =>
                        d.subject?.toLowerCase().includes(q) ||
                        d.client_name?.toLowerCase().includes(q) ||
                        d.contractor_name?.toLowerCase().includes(q) ||
                        d.mission_service?.toLowerCase().includes(q),
                );
            }
            return list;
        },
        totalFiltered() {
            return this.filteredDisputes.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },
        pagedDisputes() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredDisputes.slice(start, start + this.perPage);
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
        async fetchDisputes() {
            this.loading = true;
            this.loadError = null;
            try {
                const res = await fetch(this.routes.disputes_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.disputes = data.data ?? data;
            } catch {
                this.loadError = "Impossible de charger les litiges.";
            } finally {
                this.loading = false;
            }
        },

        async openDispute(d) {
            this.activeDispute = d;
            this.detailLoading = true;
            this.activeMessages = [];
            this.activeAttachments = [];
            this.detailTab = "messages";
            this.replyText = "";
            this.replyInternal = false;
            try {
                const url = this.routes.disputes_show.replace(
                    "{dispute}",
                    d.id,
                );
                const res = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeDispute = data.dispute;
                this.activeMessages = data.messages;
                this.activeAttachments = data.attachments;
                const idx = this.disputes.findIndex((x) => x.id === d.id);
                if (idx !== -1) this.disputes.splice(idx, 1, data.dispute);
                this.$nextTick(() => this.scrollMessages());
            } catch {
                this.showToast("Erreur lors du chargement.", "error");
            } finally {
                this.detailLoading = false;
            }
        },

        // ── Messages ───────────────────────────────────────────────
        async sendMessage() {
            if (!this.replyText.trim()) return;
            this.actionLoading = "message";
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.disputes_message.replace(
                    "{dispute}",
                    this.activeDispute.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({
                        body: this.replyText,
                        is_internal: this.replyInternal,
                    }),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeMessages.push(data.message);
                if (data.dispute_status)
                    this.activeDispute.status = data.dispute_status;
                this.replyText = "";
                this.showToast("Message envoyé.", "success");
                this.$nextTick(() => this.scrollMessages());
            } catch {
                this.showToast("Erreur lors de l'envoi.", "error");
            } finally {
                this.actionLoading = null;
            }
        },

        // ── Upload pièce jointe ────────────────────────────────────
        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.uploadLoading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const form = new FormData();
                form.append("file", file);
                const url = this.routes.disputes_attachment.replace(
                    "{dispute}",
                    this.activeDispute.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: form,
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeAttachments.push(data);
                this.activeDispute.attachments_count =
                    (this.activeDispute.attachments_count ?? 0) + 1;
                this.showToast("📎 Pièce jointe ajoutée.", "success");
            } catch {
                this.showToast("Erreur lors de l'upload.", "error");
            } finally {
                this.uploadLoading = false;
                if (this.$refs.fileInput) this.$refs.fileInput.value = "";
            }
        },

        // ── Créer litige ───────────────────────────────────────────
        openCreateModal() {
            this.createModal = {
                visible: true,
                loading: false,
                mission_id: null,
                selectedMission: null,
                subject: "",
                description: "",
            };
            this.missionSearch = "";
            this.missionResults = [];
            this.searchMissions();
        },

        async searchMissions() {
            clearTimeout(this.missionSearchTimer);
            this.missionSearchTimer = setTimeout(async () => {
                try {
                    const url =
                        this.routes.disputes_missions +
                        "?q=" +
                        encodeURIComponent(this.missionSearch);
                    const res = await fetch(url, {
                        headers: { Accept: "application/json" },
                    });
                    if (!res.ok) return;
                    this.missionResults = await res.json();
                } catch {}
            }, 300);
        },

        selectMission(m) {
            this.createModal.mission_id = m.id;
            this.createModal.selectedMission = m;
            this.missionResults = [];
        },

        async submitCreate() {
            if (
                !this.createModal.mission_id ||
                !this.createModal.subject.trim() ||
                !this.createModal.description.trim()
            )
                return;
            this.createModal.loading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const res = await fetch(this.routes.disputes_store, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({
                        mission_id: this.createModal.mission_id,
                        subject: this.createModal.subject,
                        description: this.createModal.description,
                    }),
                });
                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.message ?? "Erreur");
                }
                const saved = await res.json();
                this.disputes.unshift(saved);
                this.createModal.visible = false;
                this.showToast("⚖️ Litige ouvert.", "success");
                this.openDispute(saved);
            } catch (e) {
                this.showToast(
                    e.message ?? "Erreur lors de la création.",
                    "error",
                );
            } finally {
                this.createModal.loading = false;
            }
        },

        // ── Verdict ────────────────────────────────────────────────
        async submitVerdict() {
            if (!this.verdictModal.verdict) return;
            this.verdictModal.loading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.disputes_verdict.replace(
                    "{dispute}",
                    this.activeDispute.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({
                        verdict: this.verdictModal.verdict,
                        verdict_note: this.verdictModal.note,
                        suspend_contractor:
                            this.verdictModal.suspend_contractor,
                    }),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.activeDispute = data;
                const idx = this.disputes.findIndex((x) => x.id === data.id);
                if (idx !== -1) this.disputes.splice(idx, 1, data);
                this.verdictModal.visible = false;
                this.showToast("✅ Verdict rendu.", "success");
                await this.openDispute(data);
            } catch {
                this.showToast("Erreur lors du verdict.", "error");
            } finally {
                this.verdictModal.loading = false;
            }
        },

        // ── Clôture ────────────────────────────────────────────────
        async submitClose() {
            this.closeModal.loading = true;
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            try {
                const url = this.routes.disputes_close.replace(
                    "{dispute}",
                    this.activeDispute.id,
                );
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({ reason: this.closeModal.reason }),
                });
                if (!res.ok) throw new Error();
                this.activeDispute.status = "closed";
                this.activeDispute.status_label = "Clôturé";
                this.activeDispute.is_resolved = true;
                const idx = this.disputes.findIndex(
                    (x) => x.id === this.activeDispute.id,
                );
                if (idx !== -1) {
                    this.disputes[idx].status = "closed";
                    this.disputes[idx].status_label = "Clôturé";
                }
                this.closeModal.visible = false;
                this.showToast("🔒 Litige clôturé.", "success");
                await this.openDispute(this.activeDispute);
            } catch {
                this.showToast("Erreur lors de la clôture.", "error");
            } finally {
                this.closeModal.loading = false;
            }
        },

        // ── Helpers ────────────────────────────────────────────────
        countByTab(key) {
            if (key === "all") return this.disputes.length;
            return this.disputes.filter((d) => d.status === key).length;
        },
        badgeClass(status) {
            return (
                {
                    open: "urgent",
                    in_progress: "info",
                    resolved_client: "done",
                    resolved_contractor: "done",
                    closed: "cancelled",
                }[status] ?? ""
            );
        },
        scrollMessages() {
            const el = this.$refs.messagesContainer;
            if (el) el.scrollTop = el.scrollHeight;
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
        this.fetchDisputes();
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

/* ── Notifs ── */
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

/* ── Stats band ── */
.ac-stats-band {
    display: flex;
    align-items: center;
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

/* ── Layout ── */
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

/* ── Panel gauche ── */
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
.amis-tabs {
    display: flex;
    overflow-x: auto;
    padding: 0 12px;
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

/* ── Ticket list ── */
.ac-ticket-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}
.ac-ticket-skeleton {
    height: 80px;
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
.ac-ticket-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}
.ac-ticket-user {
    font-size: 12px;
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
.ac-ticket-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 11px;
    color: var(--grm);
}
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

/* ── Panel droit ── */
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
    font-size: 28px;
    line-height: 1;
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

/* ── Parties bar ── */
.ad-parties-bar {
    display: flex;
    align-items: stretch;
    gap: 0;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
    background: #faf7f4;
    flex-wrap: wrap;
}
.ad-party {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 10px 16px;
    flex: 1;
    min-width: 140px;
    border-right: 1px solid var(--grl);
}
.ad-party:last-child {
    border-right: none;
}
.ad-party-vs {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 900;
    color: var(--gr);
    flex-shrink: 0;
}
.ad-party-role {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--grm);
}
.ad-party-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ad-party-email {
    font-size: 11px;
    color: var(--gr);
}
.ad-party-verdict {
    background: #fff7ed;
    border-left: 3px solid var(--or);
}
.ad-suspended-chip {
    display: inline-block;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 99px;
    padding: 1px 8px;
    font-size: 10.5px;
    font-weight: 700;
    margin-top: 2px;
}

/* ── Detail tabs ── */
.ad-detail-tabs {
    display: flex;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
    background: var(--wh);
}
.ad-dtab {
    padding: 10px 16px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-family: "Poppins", sans-serif;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.18s;
}
.ad-dtab.active {
    color: var(--or);
    border-bottom-color: var(--or);
}

/* ── Messages ── */
.ac-messages-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ac-msg-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}
.ad-msg-row {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.ad-msg-row.internal {
    background: #fffdf0;
    border-radius: 10px;
    padding: 8px;
    border: 1px dashed #f0c040;
}
.ad-msg-avatar {
    font-size: 22px;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 2px;
}
.ad-msg-content {
    flex: 1;
    min-width: 0;
}
.ad-msg-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}
.ad-msg-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--dk);
}
.ad-msg-time {
    font-size: 11px;
    color: var(--grm);
}
.ad-msg-role-chip {
    font-size: 10px;
    font-weight: 700;
    border-radius: 99px;
    padding: 1px 7px;
}
.chip-admin {
    background: #fef3c7;
    color: #92400e;
}
.chip-client {
    background: #dbeafe;
    color: #1e40af;
}
.chip-contractor {
    background: #dcfce7;
    color: #166534;
}
.ad-internal-chip {
    background: #fef9c3;
    color: #713f12;
    font-size: 10px;
    font-weight: 700;
    border-radius: 99px;
    padding: 1px 7px;
}
.ad-msg-bubble {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13.5px;
    line-height: 1.6;
    max-width: 600px;
    word-break: break-word;
}
.bubble-admin {
    background: #fff7ed;
    border-left: 3px solid var(--or);
}
.bubble-client {
    background: #f0e9e4;
}
.bubble-contractor {
    background: #f0fdf4;
}
.ac-msg-empty {
    text-align: center;
    color: var(--gr);
    font-size: 13px;
    font-style: italic;
    margin: auto;
}

/* ── Attachments ── */
.ad-attachments-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}
.ad-attach-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.ad-attach-item {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.18s;
}
.ad-attach-item:hover {
    border-color: var(--or);
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.1);
}
.ad-attach-preview {
    display: block;
    height: 110px;
    background: #f8f4f0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ad-attach-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ad-attach-file-icon {
    font-size: 40px;
}
.ad-attach-info {
    padding: 10px 12px;
}
.ad-attach-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--dk);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}
.ad-attach-meta {
    font-size: 11px;
    color: var(--gr);
    margin-bottom: 4px;
}
.ad-upload-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-top: 1px dashed var(--grl);
}
.ad-upload-btn {
    background: var(--or3);
    border: 1.5px solid var(--or);
    color: var(--or);
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.ad-upload-btn:hover {
    background: var(--or);
    color: #fff;
}
.ad-upload-hint {
    font-size: 11.5px;
    color: var(--grm);
}

/* ── Mission tab ── */
.ad-mission-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}
.ad-mission-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}
@media (max-width: 640px) {
    .ad-mission-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Reply bar ── */
.ac-reply-bar {
    padding: 12px 16px;
    border-top: 1.5px solid var(--grl);
    background: var(--wh);
    flex-shrink: 0;
}
.ad-reply-options {
    margin-bottom: 8px;
}
.ad-internal-toggle {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12.5px;
    color: var(--gr);
    cursor: pointer;
}
.ad-internal-toggle input {
    accent-color: var(--or);
    width: 14px;
    height: 14px;
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
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 10px;
}

/* ── Modals ── */
.amis-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.amis-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}
.amis-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 18px 20px;
    border-bottom: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amis-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.amis-modal-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 4px;
}
.amis-modal-close {
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
.amis-modal-close:hover {
    background: var(--grl);
}
.amis-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    flex: 1;
}
.amis-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 14px 20px;
    border-top: 1.5px solid var(--grl);
    flex-shrink: 0;
}
.amis-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--dk);
    margin-bottom: 6px;
}
.amis-input {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    padding: 9px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    background: #f8f4f0;
    box-sizing: border-box;
}
.amis-input:focus {
    border-color: var(--or);
    background: #fff;
}
.amis-textarea {
    width: 100%;
    border: 1.5px solid var(--grl);
    border-radius: 9px;
    padding: 10px 14px;
    font-family: "Poppins", sans-serif;
    font-size: 13.5px;
    color: var(--dk);
    outline: none;
    resize: vertical;
    background: #f8f4f0;
    box-sizing: border-box;
}
.amis-textarea:focus {
    border-color: var(--or);
    background: #fff;
}
.asf-field {
    margin-bottom: 14px;
}
.asf-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--dk);
    cursor: pointer;
}
.asf-checkbox input {
    accent-color: var(--or);
    width: 15px;
    height: 15px;
}

/* ── Sélecteur mission ── */
.ad-mission-list {
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
    max-height: 200px;
    overflow-y: auto;
}
.ad-mission-option {
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.15s;
    border-bottom: 1px solid var(--grl);
}
.ad-mission-option:last-child {
    border-bottom: none;
}
.ad-mission-option:hover,
.ad-mission-option.selected {
    background: var(--or3);
}
.ad-mission-opt-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ad-mission-opt-meta {
    font-size: 11.5px;
    color: var(--gr);
    margin-top: 2px;
}
.ad-mission-selected {
    background: #dcfce7;
    color: #166534;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
}

/* ── Verdict options ── */
.ad-verdict-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 4px;
}
.ad-verdict-opt {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.18s;
}
.ad-verdict-opt input {
    display: none;
}
.ad-verdict-opt.selected {
    border-color: var(--or);
    background: var(--or3);
}
.ad-verdict-opt:hover {
    border-color: var(--or);
}
.ad-verdict-icon {
    font-size: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}
.ad-verdict-opt strong {
    font-size: 13.5px;
    color: var(--dk);
    display: block;
    margin-bottom: 3px;
}
.ad-verdict-opt p {
    font-size: 12px;
    color: var(--gr);
    margin: 0;
}

/* ── Detail sections ── */
.amis-detail-section {
    background: #f8f4f0;
    border-radius: 10px;
    padding: 14px;
}
.amis-detail-section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 10px;
}
.amis-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    padding: 5px 0;
    border-bottom: 1px solid var(--grl);
}
.amis-detail-row:last-child {
    border-bottom: none;
}
.amis-detail-row span {
    color: var(--gr);
    flex-shrink: 0;
}
.amis-detail-row strong {
    color: var(--dk);
    font-weight: 600;
    text-align: right;
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
.amis-badge.info {
    background: #dbeafe;
    color: #1e40af;
}
.amis-badge.urgent {
    background: #fee2e2;
    color: #991b1b;
}
.amis-badge.cancelled {
    background: #f3f4f6;
    color: #6b7280;
}
.amis-badge.warning {
    background: #fef9c3;
    color: #713f12;
}

/* ── Buttons ── */
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
