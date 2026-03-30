<template>
    <div class="clm-wrap">
        <!-- ══════════════ TOPBAR ══════════════ -->
        <div class="clm-topbar">
            <div class="clm-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="clm-page-title">Mes missions</div>
                    <div class="clm-page-sub">
                        {{ greeting }}, <strong>{{ user.name }}</strong>
                    </div>
                </div>
            </div>
            <div class="clm-topbar-right">
                <!-- Cloche notifications -->
                <div class="clm-notif-wrap" ref="notifWrap">
                    <button class="clm-notif-btn" @click="toggleNotif">
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
                        <span class="clm-notif-badge" v-if="unreadCount > 0">{{
                            unreadCount > 9 ? "9+" : unreadCount
                        }}</span>
                    </button>
                    <div class="clm-notif-dropdown" v-if="notifOpen">
                        <div class="clm-notif-header">
                            <span>Notifications</span>
                            <button
                                class="clm-notif-read-all"
                                @click="markAllRead"
                                v-if="unreadCount > 0"
                            >
                                Tout lire
                            </button>
                        </div>
                        <div class="clm-notif-loading" v-if="notifLoading">
                            Chargement...
                        </div>
                        <div
                            class="clm-notif-empty"
                            v-else-if="notifications.length === 0"
                        >
                            Aucune notification
                        </div>
                        <div
                            class="clm-notif-item"
                            v-for="n in notifications"
                            :key="n.id"
                            :class="{ unread: !n.read }"
                            @click="openNotif(n)"
                        >
                            <div class="clm-notif-dot" v-if="!n.read"></div>
                            <div class="clm-notif-content">
                                <div class="clm-notif-title">{{ n.title }}</div>
                                <div class="clm-notif-body">{{ n.body }}</div>
                                <div class="clm-notif-ago">{{ n.ago }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clm-avatar">{{ userInitials }}</div>

                <button class="clm-btn clm-btn-orange" @click="openNewMission">
                    ＋ Nouvelle mission
                </button>
            </div>
        </div>

        <!-- ══════════════ CONTENU ══════════════ -->
        <div class="clm-content">
            <!-- Filtres -->
            <div class="clm-filters">
                <div class="clm-search-wrap">
                    <span class="clm-search-icon">🔍</span>
                    <input
                        class="clm-search"
                        type="text"
                        v-model="search"
                        @input="currentPage = 1"
                        placeholder="Rechercher une mission…"
                    />
                </div>
                <div class="clm-tabs">
                    <button
                        class="clm-tab"
                        v-for="t in tabs"
                        :key="t.key"
                        :class="{ active: activeTab === t.key }"
                        @click="
                            activeTab = t.key;
                            currentPage = 1;
                        "
                    >
                        {{ t.label }}
                        <span class="clm-tab-count">{{
                            countByTab(t.key)
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Loader -->
            <div class="clm-loading" v-if="loading">
                <div class="clm-skeleton-row" v-for="n in 5" :key="n"></div>
            </div>

            <!-- Erreur -->
            <div class="clm-alert-error" v-else-if="error">
                ⚠️ {{ error }}
                <button
                    class="clm-btn clm-btn-ghost clm-btn-sm"
                    @click="fetchMissions"
                >
                    Réessayer
                </button>
            </div>

            <!-- Vide -->
            <div class="clm-empty" v-else-if="totalFiltered === 0">
                <div class="clm-empty-icon">📭</div>
                <div class="clm-empty-title">Aucune mission</div>
                <div class="clm-empty-sub">
                    Publiez votre première mission pour trouver un prestataire
                    certifié.
                </div>
                <button
                    class="clm-btn clm-btn-orange"
                    @click="openNewMission"
                    style="margin-top: 16px"
                >
                    ＋ Créer une mission
                </button>
            </div>

            <!-- Liste missions -->
            <div class="clm-list" v-else>
                <div
                    class="clm-item"
                    v-for="m in pagedMissions"
                    :key="m.id"
                    @click="openMission(m)"
                >
                    <div class="clm-item-icon">
                        {{ serviceIcon(m.service) }}
                    </div>
                    <div class="clm-item-body">
                        <div class="clm-item-title">
                            {{ m.service }}
                            <span
                                class="clm-type-chip"
                                :class="m.location_type"
                            >
                                {{
                                    m.location_type === "business" ? "🏢" : "🏠"
                                }}
                            </span>
                        </div>
                        <div class="clm-item-meta">
                            {{
                                m.contractor && m.contractor.name
                                    ? "👷 " + m.contractor.name
                                    : m.contractor
                                      ? "👷 Prestataire assigné"
                                      : "⏳ Attribution en cours…"
                            }}
                            · {{ formatDateTime(m.created_at) }}
                        </div>
                        <div class="clm-item-addr">📍 {{ m.address }}</div>
                        <!-- Badge non-lu chat -->
                        <div
                            class="clm-msg-unread"
                            v-if="unreadByMission[m.id]"
                        >
                            💬 {{ unreadByMission[m.id] }} message{{
                                unreadByMission[m.id] > 1 ? "s" : ""
                            }}
                            non lu{{ unreadByMission[m.id] > 1 ? "s" : "" }}
                        </div>
                    </div>
                    <div class="clm-item-right">
                        <span class="clm-badge" :class="badgeClass(m.status)">{{
                            labelOf(m)
                        }}</span>
                        <div class="clm-item-price" v-if="m.total_amount">
                            {{ formatPrice(m.total_amount) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── PAGINATION ── -->
            <div class="clm-pagination" v-if="totalPages > 1">
                <button
                    class="clm-page-btn"
                    @click="currentPage = 1"
                    :disabled="currentPage === 1"
                >
                    «
                </button>
                <button
                    class="clm-page-btn"
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                >
                    ‹
                </button>
                <button
                    class="clm-page-btn"
                    v-for="p in visiblePages"
                    :key="p"
                    :class="{ active: p === currentPage }"
                    @click="currentPage = p"
                >
                    {{ p }}
                </button>
                <button
                    class="clm-page-btn"
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                >
                    ›
                </button>
                <button
                    class="clm-page-btn"
                    @click="currentPage = totalPages"
                    :disabled="currentPage === totalPages"
                >
                    »
                </button>
                <span class="clm-page-info">
                    {{ (currentPage - 1) * perPage + 1 }}–{{
                        Math.min(currentPage * perPage, totalFiltered)
                    }}
                    sur {{ totalFiltered }}
                </span>
            </div>
        </div>

        <!-- ══════════════ PANEL DÉTAIL MISSION ══════════════ -->
        <div
            class="clm-panel-overlay"
            v-if="activeMission"
            @click.self="activeMission = null"
        >
            <div class="clm-panel" ref="missionPanel" @scroll="onPanelScroll">
                <!-- Bouton scroll vers le bas -->
                <button
                    class="clm-scroll-btn"
                    v-if="showScrollBtn"
                    @click="scrollPanelBottom"
                    title="Voir les actions"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>

                <div class="clm-panel-header">
                    <div>
                        <h2>{{ activeMission.service }}</h2>
                        <div class="clm-panel-sub">
                            Mission #{{ activeMission.id }} ·
                            {{ formatDateTime(activeMission.created_at) }}
                        </div>
                    </div>
                    <div class="clm-panel-header-right">
                        <span
                            class="clm-badge"
                            :class="badgeClass(activeMission.status)"
                            >{{ labelOf(activeMission) }}</span
                        >
                        <button
                            class="clm-panel-close"
                            @click="activeMission = null"
                        >
                            &#215;
                        </button>
                    </div>
                </div>

                <!-- Barre de progression + fil bullet -->
                <div class="clm-workflow">
                    <!-- Barre linéaire -->
                    <div class="clm-workflow-track">
                        <div
                            class="clm-workflow-fill"
                            :style="{
                                width: (stepOf(activeMission) / 12) * 100 + '%',
                            }"
                        ></div>
                    </div>
                    <!-- Fil d'étapes avec labels -->
                    <div class="clm-wf-steps">
                        <div
                            class="clm-wf-step"
                            v-for="s in workflowSteps"
                            :key="s.step"
                            :class="{
                                'wf-done': stepOf(activeMission) > s.step,
                                'wf-current': stepOf(activeMission) === s.step,
                                'wf-pending': stepOf(activeMission) < s.step,
                            }"
                        >
                            <div class="clm-wf-dot"></div>
                            <div class="clm-wf-label">{{ s.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="clm-panel-body">
                    <div class="clm-panel-cols">
                        <!-- Infos -->
                        <div class="clm-info-col">
                            <div class="clm-section">
                                <div class="clm-section-title">📋 Détails</div>
                                <div class="clm-row">
                                    <span>Type</span
                                    ><strong>{{
                                        activeMission.location_type ===
                                        "business"
                                            ? "🏢 Entreprise"
                                            : "🏠 Domicile"
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Adresse</span
                                    ><strong>{{
                                        activeMission.address
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Description</span
                                    ><strong>{{
                                        activeMission.description ?? "—"
                                    }}</strong>
                                </div>
                                <div class="clm-row clm-row-images" v-if="activeMission.images && activeMission.images.length">
                                    <span>Photos</span>
                                    <div class="clm-mission-images">
                                        <img
                                            v-for="(url, i) in activeMission.images"
                                            :key="i"
                                            :src="url"
                                            @click="missionLightbox = url"
                                            class="clm-mission-img"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="clm-row"
                                    v-if="activeMission.total_amount"
                                >
                                    <span>Montant</span>
                                    <strong>{{
                                        formatPrice(activeMission.total_amount)
                                    }}</strong>
                                </div>
                                <div
                                    class="clm-row"
                                    v-if="activeMission.accepted_at"
                                >
                                    <span>Acceptée le</span>
                                    <strong>{{
                                        formatDate(activeMission.accepted_at)
                                    }}</strong>
                                </div>
                            </div>

                            <div
                                class="clm-section"
                                v-if="activeMission.contractor"
                            >
                                <div class="clm-section-title">
                                    👷 Prestataire
                                </div>
                                <div class="clm-row">
                                    <span>Nom</span
                                    ><strong>{{
                                        activeMission.contractor.name
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Spécialité</span
                                    ><strong>{{
                                        activeMission.contractor.specialty
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Note</span
                                    ><strong
                                        >⭐
                                        {{
                                            activeMission.contractor
                                                .average_rating ?? "—"
                                        }}</strong
                                    >
                                </div>
                                <div class="clm-row">
                                    <span>Contact</span
                                    ><strong class="clm-masked"
                                        >🔒 Numéro masqué — utilisez la
                                        messagerie</strong
                                    >
                                </div>
                            </div>

                            <div class="clm-section clm-section-pending" v-else>
                                <div class="clm-pending-icon">⏳</div>
                                <div class="clm-pending-title">
                                    Attribution en cours
                                </div>
                                <div class="clm-pending-sub">
                                    Un prestataire certifié va être attribué
                                    automatiquement sous peu.
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="clm-actions-col">
                            <!-- ══ Devis soumis — EN ATTENTE D'APPROBATION ══ -->
                            <div
                                class="clm-action-block clm-action-quote"
                                v-if="
                                    activeMission.status ===
                                        'quote_submitted' &&
                                    activeMission.quote &&
                                    activeMission.quote.status !== 'rejected'
                                "
                            >
                                <!-- En-tête devis -->
                                <div class="clm-quote-header">
                                    <div class="clm-quote-header-left">
                                        <div class="clm-action-quote-icon">
                                            📄
                                        </div>
                                        <div>
                                            <div class="clm-action-quote-title">
                                                Devis à approuver
                                            </div>
                                            <div
                                                class="clm-quote-version"
                                                v-if="
                                                    activeMission.quote
                                                        .version > 1
                                                "
                                            >
                                                Révision v{{
                                                    activeMission.quote.version
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        class="clm-btn-download"
                                        @click="downloadQuotePdf(activeMission)"
                                        title="Télécharger PDF"
                                    >
                                        ⬇ PDF
                                    </button>
                                </div>

                                <!-- Diagnostic -->
                                <div
                                    class="clm-quote-diag"
                                    v-if="activeMission.quote.diagnosis"
                                >
                                    <div class="clm-quote-diag-label">
                                        🔍 Diagnostic
                                    </div>
                                    <div class="clm-quote-diag-text">
                                        {{ activeMission.quote.diagnosis }}
                                    </div>
                                </div>

                                <!-- Lignes -->
                                <div
                                    class="clm-quote-lines"
                                    v-if="
                                        activeMission.quote.items &&
                                        activeMission.quote.items.length
                                    "
                                >
                                    <div class="clm-quote-lines-head">
                                        <span class="ql-desc">Désignation</span>
                                        <span class="ql-qty">Qté</span>
                                        <span class="ql-pu">P.U.</span>
                                        <span class="ql-total">Total</span>
                                    </div>
                                    <div
                                        class="clm-quote-line-row"
                                        v-for="item in activeMission.quote
                                            .items"
                                        :key="item.id"
                                    >
                                        <span class="ql-desc">
                                            <span
                                                class="ql-type-badge"
                                                :class="item.type"
                                                >{{
                                                    quoteItemTypeLabel(
                                                        item.type,
                                                    )
                                                }}</span
                                            >
                                            {{ item.description }}
                                        </span>
                                        <span class="ql-qty">{{
                                            item.quantity
                                        }}</span>
                                        <span class="ql-pu">{{
                                            formatPrice(item.unit_price)
                                        }}</span>
                                        <span class="ql-total">{{
                                            formatPrice(
                                                item.quantity * item.unit_price,
                                            )
                                        }}</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="clm-quote-total-row">
                                    <span>Total devis</span>
                                    <strong class="clm-quote-total-amount">{{
                                        formatPrice(
                                            activeMission.quote.amount_incl_tax,
                                        )
                                    }}</strong>
                                </div>

                                <!-- Boutons -->
                                <div
                                    class="clm-action-row"
                                    style="margin-top: 14px"
                                >
                                    <button
                                        class="clm-btn clm-btn-red"
                                        @click="
                                            openRejectQuoteModal(activeMission)
                                        "
                                        :disabled="actionLoading"
                                    >
                                        ✗ Refuser
                                    </button>
                                    <button
                                        class="clm-btn clm-btn-orange"
                                        @click="approveQuote(activeMission)"
                                        :disabled="actionLoading"
                                    >
                                        <div
                                            class="clm-spinner"
                                            v-if="actionLoading"
                                        ></div>
                                        <span v-else>✓ Approuver</span>
                                    </button>
                                </div>
                            </div>

                            <!-- ══ Devis refusé — bloc informatif ══ -->
                            <div
                                class="clm-action-block clm-action-quote-rejected"
                                v-if="
                                    activeMission.status ===
                                        'quote_submitted' &&
                                    activeMission.quote &&
                                    activeMission.quote.status === 'rejected'
                                "
                            >
                                <div class="clm-qr-icon">❌</div>
                                <div class="clm-qr-title">Devis refusé</div>
                                <div
                                    class="clm-qr-reason"
                                    v-if="activeMission.reported_issue"
                                >
                                    Motif transmis :
                                    <em
                                        >«
                                        {{ activeMission.reported_issue }} »</em
                                    >
                                </div>
                                <div class="clm-qr-sub">
                                    Le prestataire a été notifié. Il peut vous
                                    soumettre un nouveau devis révisé ou
                                    abandonner la mission.
                                </div>
                            </div>

                            <!-- Att. confirmation travaux -->
                            <div
                                class="clm-action-block clm-action-confirm"
                                v-if="
                                    activeMission.status === 'awaiting_confirm'
                                "
                            >
                                <div class="clm-action-confirm-icon">🔨</div>
                                <div class="clm-action-confirm-title">
                                    Travaux terminés
                                </div>
                                <div class="clm-action-confirm-sub">
                                    Le prestataire a marqué l'intervention comme
                                    terminée. Confirmez-vous la fin des travaux
                                    ?
                                </div>
                                <div class="clm-action-row">
                                    <button
                                        class="clm-btn clm-btn-ghost"
                                        @click="openSignalProblem"
                                    >
                                        ⚠️ Signaler un problème
                                    </button>
                                    <button
                                        class="clm-btn clm-btn-orange"
                                        @click="
                                            confirmCompletion(activeMission)
                                        "
                                        :disabled="actionLoading"
                                    >
                                        <div
                                            class="clm-spinner"
                                            v-if="actionLoading"
                                        ></div>
                                        <span v-else>✓ Confirmer la fin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Paiement MoMo — visible dès completed, même après avis -->
                            <div
                                class="clm-action-block clm-action-pay"
                                v-if="
                                    activeMission.payment_unlocked ||
                                    activeMission.status === 'completed'
                                "
                            >
                                <div class="clm-action-pay-icon">💸</div>
                                <div class="clm-action-pay-title">
                                    Paiement requis
                                </div>
                                <div class="clm-action-pay-sub">
                                    Choisissez votre réseau et procédez au
                                    paiement mobile pour clôturer la mission.
                                </div>
                                <button
                                    class="clm-btn clm-btn-orange clm-btn-full"
                                    @click="openMomoModal(activeMission)"
                                >
                                    💳 Payer via Mobile Money →
                                </button>
                            </div>

                            <!-- Avis prestataire (statut completed) -->
                            <div
                                class="clm-action-block clm-action-review"
                                v-if="activeMission.status === 'completed'"
                            >
                                <div class="clm-review-header">
                                    <span class="clm-review-icon">⭐</span>
                                    <div>
                                        <div class="clm-review-title">
                                            Évaluez le prestataire
                                        </div>
                                        <div class="clm-review-sub">
                                            Votre avis aide la communauté
                                            Resotravo
                                        </div>
                                    </div>
                                </div>
                                <div class="clm-stars-wrap">
                                    <button
                                        class="clm-star"
                                        v-for="n in 5"
                                        :key="n"
                                        :class="{
                                            active: n <= reviewModal.rating,
                                        }"
                                        @click="reviewModal.rating = n"
                                        type="button"
                                    >
                                        ★
                                    </button>
                                    <span
                                        class="clm-star-label"
                                        v-if="reviewModal.rating"
                                    >
                                        {{ starLabel(reviewModal.rating) }}
                                    </span>
                                </div>
                                <textarea
                                    class="clm-review-textarea"
                                    v-model="reviewModal.comment"
                                    placeholder="Décrivez votre expérience (optionnel)…"
                                    rows="3"
                                    maxlength="500"
                                ></textarea>
                                <div class="clm-review-count">
                                    {{ reviewModal.comment.length }}/500
                                </div>
                                <button
                                    class="clm-btn clm-btn-green clm-btn-full"
                                    @click="submitReviewAndClose(activeMission)"
                                    :disabled="
                                        !reviewModal.rating ||
                                        reviewModal.loading
                                    "
                                >
                                    <div
                                        class="clm-spinner"
                                        v-if="reviewModal.loading"
                                    ></div>
                                    <span v-else
                                        >✓ Soumettre l'avis et clôturer la
                                        mission</span
                                    >
                                </button>
                                <div class="clm-review-skip">
                                    <button
                                        class="clm-link"
                                        @click="closeMissionOnly(activeMission)"
                                        :disabled="reviewModal.loading"
                                    >
                                        Passer et clôturer sans avis →
                                    </button>
                                </div>
                            </div>

                            <!-- Statuts informatifs -->
                            <div class="clm-status-info" v-if="!isActionable">
                                <div class="clm-status-info-icon">
                                    {{ statusInfoIcon(activeMission.status) }}
                                </div>
                                <div>
                                    <div class="clm-status-info-title">
                                        {{
                                            statusInfoTitle(
                                                activeMission.status,
                                            )
                                        }}
                                    </div>
                                    <div class="clm-status-info-sub">
                                        {{
                                            statusInfoSub(activeMission.status)
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- ── Bannière sécurité : prestataire en route / sur place ── -->
                            <div
                                class="clm-security-banner"
                                v-if="
                                    [
                                        'on_the_way',
                                        'tracking',
                                        'in_progress',
                                    ].includes(activeMission.status)
                                "
                            >
                                <div class="clm-security-icon">🛡️</div>
                                <div class="clm-security-body">
                                    <div class="clm-security-title">
                                        Recommandations de sécurité
                                    </div>
                                    <ul class="clm-security-list">
                                        <li>
                                            ✅ Vérifiez l'identité du
                                            prestataire à son arrivée
                                        </li>
                                        <li>
                                            🏅 Contrôlez son badge
                                            <strong
                                                >Profil Certifié
                                                Resotravo</strong
                                            >
                                        </li>
                                        <li>
                                            🔒 N'effectuez aucun paiement en
                                            dehors de la plateforme
                                        </li>
                                        <li>
                                            💬 Tout contact passe par la
                                            messagerie intégrée
                                        </li>
                                    </ul>
                                    <div class="clm-security-note">
                                        Tout paiement hors-plateforme dégage
                                        Resotravo de toute responsabilité.
                                    </div>
                                </div>
                            </div>

                            <!-- Chat -->
                            <button
                                class="clm-btn clm-btn-chat clm-btn-full"
                                @click="openChat(activeMission.id)"
                                v-if="
                                    [
                                        'accepted',
                                        'contact_made',
                                        'on_the_way',
                                        'tracking',
                                        'in_progress',
                                        'quote_submitted',
                                        'order_placed',
                                        'awaiting_confirm',
                                    ].includes(activeMission.status)
                                "
                            >
                                💬 Contacter le prestataire
                                <span
                                    class="clm-chat-badge"
                                    v-if="unreadByMission[activeMission.id]"
                                    >{{
                                        unreadByMission[activeMission.id]
                                    }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL NOUVELLE MISSION ══════════════ -->
        <div
            class="clm-modal-overlay"
            v-if="showNewMission"
            @click.self="showNewMission = false"
        >
            <div class="clm-modal clm-modal-lg">
                <div class="clm-modal-header">
                    <h3>➕ Nouvelle mission</h3>
                    <button
                        class="clm-modal-close"
                        @click="showNewMission = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body">
                    <div class="clm-field">
                        <label
                            >Type de lieu <span class="clm-req">*</span></label
                        >
                        <div class="clm-radio-row">
                            <label
                                class="clm-radio-opt"
                                :class="{
                                    selected:
                                        form.location_type === 'residential',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="form.location_type"
                                    value="residential"
                                />
                                🏠 Domicile
                            </label>
                            <label
                                class="clm-radio-opt"
                                v-if="isCompany"
                                :class="{
                                    selected: form.location_type === 'business',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="form.location_type"
                                    value="business"
                                />
                                🏢 Entreprise
                            </label>
                        </div>
                    </div>
                    <div class="clm-field">
                        <label>Prestation <span class="clm-req">*</span></label>
                        <select class="clm-input" v-model="form.service">
                            <option value="">Sélectionner…</option>
                            <option
                                v-for="s in services"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.icon }} {{ s.label }}
                            </option>
                        </select>
                    </div>
                    <div class="clm-field">
                        <label
                            >Description <span class="clm-req">*</span></label
                        >
                        <textarea
                            class="clm-input clm-textarea"
                            v-model="form.description"
                            rows="4"
                            placeholder="Décrivez votre besoin en détail (min. 20 caractères)…"
                        ></textarea>
                        <div
                            class="clm-char-count"
                            :class="{ warn: form.description.length < 20 }"
                        >
                            {{ form.description.length }} / 2000
                        </div>
                    </div>
                    <div class="clm-field">
                        <label>Adresse <span class="clm-req">*</span></label>
                        <input
                            class="clm-input"
                            type="text"
                            v-model="form.address"
                            placeholder="Ex : Akpakpa, Cotonou"
                        />
                    </div>
                    <!-- Géolocalisation -->
                    <button
                        class="clm-btn-geo"
                        type="button"
                        @click="askGeo"
                        :disabled="geoLoading"
                        v-if="!geoOk"
                    >
                        <div
                            class="clm-spinner clm-spinner-sm"
                            v-if="geoLoading"
                        ></div>
                        <span v-else>📍</span>
                        {{
                            geoLoading
                                ? "Localisation…"
                                : "Utiliser ma position actuelle"
                        }}
                    </button>
                    <div class="clm-geo-ok" v-if="geoOk">
                        <span>📍</span>
                        <span class="clm-geo-ok-addr">{{
                            geoAddress || "Position enregistrée"
                        }}</span>
                        <button class="clm-geo-reset" @click="resetGeo">
                            ✕
                        </button>
                    </div>
                    <!-- Images optionnelles -->
                    <div class="clm-field">
                        <label>Photos (optionnel, max 5 × 10 Mo)</label>
                        <div class="clm-img-upload-row">
                            <label class="clm-img-add-btn" title="Ajouter des photos">
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    style="display:none"
                                    ref="imgInput"
                                    @change="onImagesSelect"
                                />
                                <span>＋ Photo</span>
                            </label>
                            <div
                                class="clm-img-thumb"
                                v-for="(img, i) in imagesPreviews"
                                :key="i"
                            >
                                <img :src="img.url" :alt="img.name" />
                                <button class="clm-img-remove" @click.prevent="removeImage(i)">✕</button>
                            </div>
                        </div>
                    </div>
                    <div class="clm-alert-error" v-if="formError">
                        {{ formError }}
                    </div>
                </div>
                <div class="clm-modal-footer">
                    <button
                        class="clm-btn clm-btn-ghost"
                        @click="showNewMission = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="clm-btn clm-btn-orange"
                        @click="submitMission"
                        :disabled="submitting"
                    >
                        <div class="clm-spinner" v-if="submitting"></div>
                        <span v-else>Publier la mission →</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL SIGNALEMENT ══════════════ -->
        <div
            class="clm-modal-overlay"
            v-if="signalModal.visible"
            @click.self="signalModal.visible = false"
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <h3>⚠️ Signaler un problème</h3>
                    <button
                        class="clm-modal-close"
                        @click="signalModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            margin-bottom: 12px;
                            line-height: 1.6;
                        "
                    >
                        Décrivez le problème rencontré. Un agent Resotravo vous
                        contactera sous 24h.
                    </p>
                    <label class="clm-form-label"
                        >Motif <span class="clm-req">*</span></label
                    >
                    <textarea
                        class="clm-input clm-textarea"
                        v-model="signalModal.reason"
                        rows="3"
                        placeholder="Ex : Travaux non terminés, matériaux défectueux…"
                    ></textarea>
                </div>
                <div class="clm-modal-footer">
                    <button
                        class="clm-btn clm-btn-ghost"
                        @click="signalModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="clm-btn clm-btn-red"
                        @click="confirmSignal"
                        :disabled="
                            !signalModal.reason.trim() || signalModal.loading
                        "
                    >
                        <div
                            class="clm-spinner"
                            v-if="signalModal.loading"
                        ></div>
                        <span v-else>⚠️ Signaler</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ MODAL REFUS DEVIS ══════════════ -->
        <div
            class="clm-modal-overlay"
            v-if="rejectQuoteModal.visible"
            @click.self="rejectQuoteModal.visible = false"
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <h3>✗ Refuser le devis</h3>
                    <button
                        class="clm-modal-close"
                        @click="rejectQuoteModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body">
                    <p
                        style="
                            font-size: 13.5px;
                            color: var(--gr);
                            margin-bottom: 14px;
                            line-height: 1.6;
                        "
                    >
                        Expliquez au prestataire pourquoi vous refusez ce devis.
                        Il pourra le réviser et vous le soumettre à nouveau.
                    </p>
                    <div class="clm-rq-options">
                        <label
                            class="clm-radio-opt"
                            v-for="opt in rejectReasons"
                            :key="opt.value"
                            :class="{
                                selected: rejectQuoteModal.reason === opt.value,
                            }"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="rejectQuoteModal.reason"
                            />
                            <span>{{ opt.label }}</span>
                        </label>
                    </div>
                    <div
                        class="clm-field"
                        v-if="rejectQuoteModal.reason === 'other'"
                        style="margin-top: 10px"
                    >
                        <label class="clm-form-label"
                            >Précisez votre motif
                            <span class="clm-req">*</span></label
                        >
                        <textarea
                            class="clm-input clm-textarea"
                            v-model="rejectQuoteModal.customReason"
                            rows="3"
                            placeholder="Ex : Le prix des pièces semble trop élevé…"
                        ></textarea>
                    </div>
                </div>
                <div class="clm-modal-footer">
                    <button
                        class="clm-btn clm-btn-ghost"
                        @click="rejectQuoteModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="clm-btn clm-btn-red"
                        @click="confirmRejectQuote"
                        :disabled="
                            !rejectQuoteModal.reason ||
                            (rejectQuoteModal.reason === 'other' &&
                                !rejectQuoteModal.customReason.trim()) ||
                            rejectQuoteModal.loading
                        "
                    >
                        <div
                            class="clm-spinner"
                            v-if="rejectQuoteModal.loading"
                        ></div>
                        <span v-else>✗ Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════ CHAT MODAL ══════════════ -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- Lightbox images mission -->
        <div class="clm-lightbox" v-if="missionLightbox" @click="missionLightbox = null">
            <img :src="missionLightbox" />
        </div>

        <!-- TOASTS -->
        <div class="clm-toast-container">
            <div
                class="clm-toast"
                v-for="t in toasts"
                :key="t.id"
                :class="t.type"
            >
                {{ t.message }}
            </div>
        </div>

        <!-- ══════════════ MODAL PAIEMENT MOMO ══════════════ -->
        <div
            class="clm-modal-overlay"
            v-if="momoModal.visible"
            @click.self="momoModal.visible = false"
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <div>
                        <h3>💳 Paiement Mobile Money</h3>
                        <div class="clm-modal-sub">
                            Mission #{{ momoModal.mission?.id }} —
                            {{ formatPrice(momoModal.mission?.total_amount) }}
                        </div>
                    </div>
                    <button
                        class="clm-modal-close"
                        @click="momoModal.visible = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body">
                    <div class="clm-field">
                        <label class="clm-form-label"
                            >Réseau Mobile Money
                            <span class="clm-req">*</span></label
                        >
                        <div class="clm-momo-networks">
                            <button
                                type="button"
                                class="clm-momo-network"
                                v-for="net in momoNetworks"
                                :key="net.value"
                                :class="{
                                    active: momoModal.network === net.value,
                                }"
                                @click="momoModal.network = net.value"
                            >
                                <span class="clm-momo-net-icon">{{
                                    net.icon
                                }}</span>
                                <span class="clm-momo-net-label">{{
                                    net.label
                                }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="clm-field">
                        <label class="clm-form-label"
                            >Numéro de téléphone
                            <span class="clm-req">*</span></label
                        >
                        <input
                            class="clm-input"
                            type="tel"
                            v-model="momoModal.phone"
                            placeholder="Ex : 97 12 34 56"
                            maxlength="20"
                        />
                        <div class="clm-momo-hint">
                            Entrez le numéro associé à votre compte
                            {{
                                momoModal.network
                                    ? momoNetworks.find(
                                          (n) => n.value === momoModal.network,
                                      )?.label
                                    : "Mobile Money"
                            }}.
                        </div>
                    </div>
                    <div
                        class="clm-momo-recap"
                        v-if="momoModal.network && momoModal.phone"
                    >
                        <div class="clm-momo-recap-row">
                            <span>Réseau</span>
                            <strong>{{
                                momoNetworks.find(
                                    (n) => n.value === momoModal.network,
                                )?.label
                            }}</strong>
                        </div>
                        <div class="clm-momo-recap-row">
                            <span>Numéro</span>
                            <strong>{{ momoModal.phone }}</strong>
                        </div>
                        <div class="clm-momo-recap-row">
                            <span>Montant</span>
                            <strong class="clm-momo-amount">{{
                                formatPrice(momoModal.mission?.total_amount)
                            }}</strong>
                        </div>
                    </div>
                    <div class="clm-momo-warning">
                        🔒 Vous recevrez une demande de paiement sur votre
                        téléphone. Confirmez avec votre code PIN.
                    </div>
                </div>
                <div class="clm-modal-footer">
                    <button
                        class="clm-btn clm-btn-ghost"
                        @click="momoModal.visible = false"
                    >
                        Annuler
                    </button>
                    <button
                        class="clm-btn clm-btn-orange"
                        @click="submitMomo"
                        :disabled="
                            !momoModal.network ||
                            !momoModal.phone.trim() ||
                            momoModal.loading
                        "
                    >
                        <div class="clm-spinner" v-if="momoModal.loading"></div>
                        <span v-else>✓ Confirmer le paiement</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MissionChatModal from "../../MissionChatModal.vue";

export default {
    name: "ClientMissionComponent",
    components: { MissionChatModal },

    props: {
        user: { type: Object, required: true },
        clientProfile: { type: Object, default: () => ({}) },
        routes: { type: Object, required: true },
    },

    data() {
        return {
            missions: [],
            loading: true,
            error: null,
            activeTab: "all",
            search: "",
            activeMission: null,
            actionLoading: false,

            chatMissionId: null,
            chatUnread: 0,
            showScrollBtn: false,
            unreadByMission: {},

            notifications: [],
            unreadCount: 0,
            notifOpen: false,
            notifLoading: false,
            notifInterval: null,

            // Nouvelle mission
            showNewMission: false,
            submitting: false,
            formError: "",
            imageFiles: [],
            imagesPreviews: [],
            missionLightbox: null,
            geoLoading: false,
            geoOk: false,
            geoAddress: "",
            form: {
                location_type: "residential",
                service: "",
                description: "",
                address: "",
                latitude: null,
                longitude: null,
            },

            // Signalement
            signalModal: { visible: false, reason: "", loading: false },

            // Avis prestataire
            reviewModal: { rating: 0, comment: "", loading: false },

            // Paiement MoMo
            momoModal: {
                visible: false,
                mission: null,
                network: "",
                phone: "",
                loading: false,
            },
            momoNetworks: [
                { value: "mtn", label: "MTN Bénin", icon: "🟡" },
                { value: "moov", label: "Moov Bénin", icon: "🔵" },
                { value: "celtiis", label: "Celtiis", icon: "🟠" },
            ],

            // Refus devis
            rejectQuoteModal: {
                visible: false,
                mission: null,
                reason: "",
                customReason: "",
                loading: false,
            },
            rejectReasons: [
                { value: "price_too_high", label: "💰 Prix trop élevé" },
                {
                    value: "wrong_parts",
                    label: "🔩 Pièces incorrectes ou inutiles",
                },
                {
                    value: "labor_too_high",
                    label: "🛠️ Main d'œuvre trop chère",
                },
                {
                    value: "missing_items",
                    label: "📋 Des éléments manquent au devis",
                },
                { value: "other", label: "✏️ Autre motif" },
            ],

            toasts: [],
            toastId: 0,
            sidebarOpen: false,

            // Pagination
            currentPage: 1,
            perPage: 10,

            services: [
                { value: "plomberie", label: "Plomberie", icon: "🔧" },
                { value: "electricite", label: "Électricité", icon: "⚡" },
                { value: "menuiserie", label: "Menuiserie", icon: "🪵" },
                { value: "ferronnerie", label: "Ferronnerie", icon: "⚙️" },
                { value: "frigoriste", label: "Frigoriste", icon: "❄️" },
                { value: "mecanique", label: "Mécanique auto", icon: "🚗" },
                { value: "nettoyage", label: "Nettoyage", icon: "🧹" },
                { value: "peinture", label: "Peinture", icon: "🎨" },
                { value: "maintenance", label: "Maintenance", icon: "🛠️" },
                { value: "autre", label: "Autre", icon: "➕" },
            ],

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "🔄 En cours" },
                { key: "pending", label: "⏳ En attente" },
                { key: "closed", label: "✅ Terminées" },
                { key: "cancelled", label: "❌ Annulées" },
            ],

            workflowSteps: [
                { step: 1, label: "En attente" },
                { step: 2, label: "Proposée" },
                { step: 3, label: "Acceptée" },
                { step: 4, label: "Contact" },
                { step: 5, label: "En route" },
                { step: 6, label: "Suivi" },
                { step: 7, label: "Sur place" },
                { step: 8, label: "Devis soumis" },
                { step: 9, label: "Commande" },
                { step: 10, label: "Att. confirmation" },
                { step: 11, label: "Terminée" },
                { step: 12, label: "Clôturée" },
            ],

            actionableStatuses: [
                "quote_submitted",
                "awaiting_confirm",
                "completed",
            ],
        };
    },

    computed: {
        userInitials() {
            return (
                this.user.name
                    ?.split(" ")
                    .map((w) => w[0])
                    .join("")
                    .toUpperCase()
                    .slice(0, 2) ?? "CL"
            );
        },
        greeting() {
            const h = new Date().getHours();
            if (h < 12) return "Bonjour";
            if (h < 18) return "Bon après-midi";
            return "Bonsoir";
        },
        isActionable() {
            const m = this.activeMission;
            if (!m) return false;
            if (m.status === "quote_submitted") return true;
            return ["awaiting_confirm", "completed"].includes(m.status);
        },
        isCompany() {
            return this.clientProfile?.account_type === "company";
        },
        activeStatuses() {
            return [
                "assigned",
                "accepted",
                "contact_made",
                "on_the_way",
                "tracking",
                "in_progress",
                "quote_submitted",
                "order_placed",
                "awaiting_confirm",
            ];
        },
        filteredMissions() {
            let list = [...this.missions];
            if (this.activeTab === "active")
                list = list.filter((m) =>
                    this.activeStatuses.includes(m.status),
                );
            else if (this.activeTab === "pending")
                list = list.filter((m) => m.status === "pending");
            else if (this.activeTab === "closed")
                list = list.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                );
            else if (this.activeTab === "cancelled")
                list = list.filter((m) => m.status === "cancelled");
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (m) =>
                        m.service?.toLowerCase().includes(q) ||
                        m.address?.toLowerCase().includes(q) ||
                        m.contractor?.name?.toLowerCase().includes(q),
                );
            }
            return list;
        },
        totalFiltered() {
            return this.filteredMissions.length;
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
        },
        pagedMissions() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredMissions.slice(start, start + this.perPage);
        },
        visiblePages() {
            const pages = [];
            const delta = 2;
            for (
                let i = Math.max(1, this.currentPage - delta);
                i <= Math.min(this.totalPages, this.currentPage + delta);
                i++
            ) {
                pages.push(i);
            }
            return pages;
        },
    },

    methods: {
        // ── Missions ─────────────────────────────────────────────
        async fetchMissions() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.missions = Array.isArray(data) ? data : (data.data ?? []);
            } catch {
                this.error = "Impossible de charger les missions.";
            } finally {
                this.loading = false;
            }
        },

        openMission(m) {
            this.activeMission = { ...m };
            this.showScrollBtn = false;
            // Après rendu, vérifier si le panel est scrollable
            this.$nextTick(() => {
                const el = this.$refs.missionPanel;
                if (el)
                    this.showScrollBtn = el.scrollHeight - el.clientHeight > 80;
            });
        },

        async updateMissionStatus(mission, status, extra = {}) {
            this.actionLoading = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const url = this.routes.missions_status
                    .replace("{id}", mission.id)
                    .replace("{mission}", mission.id);
                console.log(
                    "[DEBUG] missions_status URL:",
                    url,
                    "| mission.id:",
                    mission.id,
                    "| routes.missions_status:",
                    this.routes.missions_status,
                );
                const res = await fetch(url, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ status, ...extra }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message ?? "Erreur.", "error");
                    return null;
                }
                const idx = this.missions.findIndex((m) => m.id === mission.id);
                if (idx !== -1) this.missions.splice(idx, 1, data.mission);
                this.activeMission = { ...data.mission };
                return data.mission;
            } catch {
                this.showToast("Erreur réseau.", "error");
                return null;
            } finally {
                this.actionLoading = false;
            }
        },

        async confirmCompletion(m) {
            await this.updateMissionStatus(m, "completed");
            this.reviewModal = { rating: 0, comment: "", loading: false };
            this.showToast(
                "Fin des travaux confirmée ! Évaluez le prestataire et procédez au paiement.",
                "success",
            );
        },

        async submitReviewAndClose(m) {
            if (!this.reviewModal.rating) return;
            this.reviewModal.loading = true;
            try {
                const csrf =
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "";
                const reviewUrl =
                    (this.routes.reviews_store ?? "/client/missions") +
                    "/" +
                    m.id +
                    "/review";
                await fetch(reviewUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        rating: this.reviewModal.rating,
                        comment: this.reviewModal.comment.trim(),
                    }),
                });
                await this.closeMissionOnly(m);
            } catch {
                this.showToast(
                    "Erreur lors de la soumission de l'avis.",
                    "error",
                );
                this.reviewModal.loading = false;
            }
        },

        async closeMissionOnly(m) {
            this.reviewModal.loading = true;
            await this.updateMissionStatus(m, "closed");
            this.reviewModal = { rating: 0, comment: "", loading: false };
            this.showToast(
                "Mission clôturée. Merci de votre confiance !",
                "success",
            );
        },

        openMomoModal(mission) {
            this.momoModal = {
                visible: true,
                mission,
                network: "",
                phone: "",
                loading: false,
            };
        },

        async submitMomo() {
            if (!this.momoModal.network || !this.momoModal.phone.trim()) return;
            this.momoModal.loading = true;
            try {
                // Réutiliser updateMissionStatus qui gère déjà l'URL correctement
                const result = await this.updateMissionStatus(
                    this.momoModal.mission,
                    "closed",
                    {
                        momo_network: this.momoModal.network,
                        momo_phone: this.momoModal.phone.trim(),
                    },
                );
                if (result) {
                    this.momoModal.visible = false;
                    this.showToast(
                        "🎉 Paiement confirmé ! Mission clôturée avec succès.",
                        "success",
                    );
                }
            } catch {
                this.showToast(
                    "Erreur lors du paiement. Veuillez réessayer.",
                    "error",
                );
            } finally {
                this.momoModal.loading = false;
            }
        },

        starLabel(n) {
            return (
                [
                    "",
                    "😕 Mauvais",
                    "😐 Passable",
                    "🙂 Correct",
                    "😊 Bien",
                    "🤩 Excellent !",
                ][n] ?? ""
            );
        },

        async approveQuote(m) {
            await this.updateMissionStatus(m, "order_placed");
            this.showToast(
                "Devis approuvé ! L'intervention va démarrer.",
                "success",
            );
        },

        openRejectQuoteModal(m) {
            this.rejectQuoteModal = {
                visible: true,
                mission: m,
                reason: "",
                customReason: "",
                loading: false,
            };
        },

        async confirmRejectQuote() {
            const label =
                this.rejectReasons.find(
                    (r) => r.value === this.rejectQuoteModal.reason,
                )?.label ?? "";
            const reason =
                this.rejectQuoteModal.reason === "other"
                    ? this.rejectQuoteModal.customReason.trim()
                    : label;
            this.rejectQuoteModal.loading = true;
            // PATCH mission status → quote_submitted (reste en quote_submitted) avec rejection_reason
            await this.updateMissionStatus(
                this.rejectQuoteModal.mission,
                "quote_submitted",
                { reported_issue: reason },
            );
            this.rejectQuoteModal.visible = false;
            this.showToast("Devis refusé. Le prestataire va le réviser.", "");
            this.rejectQuoteModal.loading = false;
        },

        downloadQuotePdf(mission) {
            const quote = mission.quote;
            if (!quote) return;

            const lines = (quote.items ?? [])
                .map((i) => {
                    const total =
                        parseFloat(i.quantity) * parseFloat(i.unit_price);
                    const typeLabel =
                        {
                            part: "Pièce",
                            labor: "M.O.",
                            travel: "Dépl.",
                            other: "Autre",
                        }[i.type] ?? "";
                    return `
                    <tr>
                        <td style="padding:7px 10px;border-bottom:1px solid #eee;font-size:13px;">[${typeLabel}] ${i.description}</td>
                        <td style="padding:7px 10px;border-bottom:1px solid #eee;font-size:13px;text-align:center;">${i.quantity}</td>
                        <td style="padding:7px 10px;border-bottom:1px solid #eee;font-size:13px;text-align:right;">${this.formatPrice(i.unit_price)}</td>
                        <td style="padding:7px 10px;border-bottom:1px solid #eee;font-size:13px;text-align:right;font-weight:700;">${this.formatPrice(total)}</td>
                    </tr>`;
                })
                .join("");

            const html = `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
            <title>Devis #${quote.id} — Mission #${mission.id}</title>
            <style>
                body { font-family: Arial, sans-serif; color: #1c1412; max-width: 700px; margin: 40px auto; padding: 0 20px; }
                .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
                .brand { font-size: 22px; font-weight: 900; color: #f97316; }
                .brand span { color: #1c1412; }
                .meta { font-size: 12px; color: #7c6a5a; text-align: right; }
                h2 { font-size: 16px; color: #1c1412; margin: 0 0 4px; }
                .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #7c6a5a; margin: 24px 0 8px; }
                table { width: 100%; border-collapse: collapse; }
                thead td { background: #f8f4f0; font-size: 11px; font-weight: 700; color: #7c6a5a; padding: 8px 10px; text-transform: uppercase; }
                .total-row td { padding: 12px 10px; font-size: 15px; font-weight: 800; border-top: 2px solid #f97316; }
                .diag { background: #fff7ed; border-left: 4px solid #f97316; padding: 10px 14px; font-size: 13px; color: #7c6a5a; border-radius: 4px; margin-bottom: 8px; }
                .footer { margin-top: 40px; font-size: 11px; color: #8a7d78; text-align: center; border-top: 1px solid #e8ddd4; padding-top: 16px; }
            
/* ── PAGINATION ── */
.clm-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 24px;
    padding-bottom: 16px;
}
.clm-page-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1.5px solid var(--grl);
    background: var(--wh);
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.clm-page-btn:hover:not(:disabled) {
    border-color: var(--or);
    color: var(--or);
}
.clm-page-btn.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.clm-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.clm-page-info {
    font-size: 12.5px;
    color: var(--gr);
    margin-left: 8px;
}

/* ── MODAL PAIEMENT MOMO ── */
.clm-momo-networks {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}
.clm-momo-network {
    flex: 1;
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border: 2px solid var(--grl);
    border-radius: 12px;
    background: var(--wh);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
}
.clm-momo-network:hover { border-color: var(--or); }
.clm-momo-network.active {
    border-color: var(--or);
    background: var(--or3);
}
.clm-momo-net-icon  { font-size: 26px; }
.clm-momo-net-label { font-size: 12px; font-weight: 700; color: var(--dk); }
.clm-momo-hint {
    font-size: 12px;
    color: var(--gr);
    margin-top: 6px;
    line-height: 1.5;
}
.clm-momo-recap {
    background: #f8f4f0;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.clm-momo-recap-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: var(--gr);
}
.clm-momo-recap-row strong { color: var(--dk); }
.clm-momo-amount { color: var(--or); font-size: 15px; }
.clm-momo-warning {
    font-size: 12.5px;
    color: #92400e;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 14px;
    line-height: 1.5;
}
</style></head><body>
            <div class="header">
                <div><div class="brand">Reso<span>Travo</span></div><div style="font-size:12px;color:#7c6a5a;margin-top:4px;">Plateforme de mise en relation</div></div>
                <div class="meta">
                    <div><strong>Devis #${quote.id}</strong>${quote.version > 1 ? " — Révision v" + quote.version : ""}</div>
                    <div>Mission #${mission.id} · ${mission.service}</div>
                    <div>Date : ${new Date().toLocaleDateString("fr-FR")}</div>
                </div>
            </div>
            <h2>Détail de l'intervention</h2>
            <p style="font-size:13px;color:#7c6a5a;margin:4px 0 16px;">Adresse : ${mission.address}</p>
            ${quote.diagnosis ? `<div class="diag"><strong>Diagnostic :</strong> ${quote.diagnosis}</div>` : ""}
            <div class="section-title">Lignes du devis</div>
            <table>
                <thead><tr>
                    <td>Désignation</td><td style="text-align:center;">Qté</td>
                    <td style="text-align:right;">Prix unitaire</td><td style="text-align:right;">Total</td>
                </tr></thead>
                <tbody>${lines}</tbody>
                <tfoot><tr class="total-row">
                    <td colspan="3">Total</td>
                    <td style="text-align:right;color:#f97316;">${this.formatPrice(quote.amount_incl_tax)}</td>
                </tr></tfoot>
            </table>
            <div class="footer">Resotravo — Ce devis est soumis à votre approbation via la plateforme. Aucun paiement hors-plateforme n'est autorisé.</div>
            </body></html>`;

            const blob = new Blob([html], { type: "text/html" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `devis-mission-${mission.id}.html`;
            a.click();
            URL.revokeObjectURL(url);
        },

        quoteItemTypeLabel(type) {
            return (
                {
                    part: "Pièce",
                    labor: "M.O.",
                    travel: "Dépl.",
                    other: "Autre",
                }[type] ?? type
            );
        },

        // ── Signalement ───────────────────────────────────────────
        openSignalProblem() {
            this.signalModal = { visible: true, reason: "", loading: false };
        },
        async confirmSignal() {
            if (!this.signalModal.reason.trim() || !this.activeMission) return;
            this.signalModal.loading = true;
            await this.updateMissionStatus(
                this.activeMission,
                "awaiting_confirm",
                {
                    reported_issue: this.signalModal.reason,
                },
            );
            this.signalModal.visible = false;
            this.showToast(
                "Problème signalé. Un agent va vous contacter.",
                "success",
            );
            this.signalModal.loading = false;
        },

        // ── Nouvelle mission ──────────────────────────────────────
        openNewMission() {
            this.form = {
                location_type: "residential",
                service: "",
                description: "",
                address: this.clientProfile?.address ?? "",
                latitude: null,
                longitude: null,
            };
            this.formError = "";
            this.geoOk = false;
            this.imageFiles = [];
            this.imagesPreviews = [];
            this.showNewMission = true;
        },
        askGeo() {
            if (!navigator.geolocation) return;
            this.geoLoading = true;
            this.geoOk = false;
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;
                    this.form.latitude = lat;
                    this.form.longitude = lon;

                    // Reverse geocoding via Nominatim — attendre l'adresse avant d'afficher
                    try {
                        const res = await fetch(
                            `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=fr`,
                            { headers: { "Accept-Language": "fr" } },
                        );
                        const data = await res.json();
                        const a = data.address ?? {};

                        // Construire l'adresse la plus précise possible (adapté Afrique de l'Ouest)
                        const street =
                            a.road ?? a.pedestrian ?? a.path ?? a.footway ?? "";
                        const quarter =
                            a.neighbourhood ??
                            a.suburb ??
                            a.quarter ??
                            a.hamlet ??
                            a.village ??
                            "";
                        const city =
                            a.city ??
                            a.town ??
                            a.municipality ??
                            a.county ??
                            "";
                        const country = a.country ?? "";

                        const parts = [street, quarter, city, country].filter(
                            Boolean,
                        );

                        // Si on a au moins 2 composants on l'utilise, sinon display_name complet
                        this.geoAddress =
                            parts.length >= 2
                                ? parts.join(", ")
                                : (data.display_name ??
                                  `${lat.toFixed(5)}, ${lon.toFixed(5)}`);

                        // Toujours pré-remplir le champ adresse avec la position GPS
                        this.form.address = this.geoAddress;
                    } catch {
                        // Fallback : coordonnées brutes
                        this.geoAddress = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
                        this.form.address = this.geoAddress;
                    }

                    this.geoOk = true; // afficher APRÈS avoir l'adresse
                    this.geoLoading = false;
                },
                () => {
                    this.geoLoading = false;
                    this.showToast(
                        "Géolocalisation refusée ou indisponible.",
                        "error",
                    );
                },
                { timeout: 15000, enableHighAccuracy: true, maximumAge: 0 },
            );
        },
        resetGeo() {
            this.form.latitude = null;
            this.form.longitude = null;
            this.geoOk = false;
            this.geoAddress = "";
        },
        onImagesSelect(e) {
            const files = Array.from(e.target.files);
            const remaining = 5 - this.imageFiles.length;
            files.slice(0, remaining).forEach((file) => {
                this.imageFiles.push(file);
                this.imagesPreviews.push({
                    url: URL.createObjectURL(file),
                    name: file.name,
                });
            });
            if (this.$refs.imgInput) this.$refs.imgInput.value = "";
        },
        removeImage(index) {
            this.imageFiles.splice(index, 1);
            this.imagesPreviews.splice(index, 1);
        },

        async submitMission() {
            this.formError = "";
            if (!this.form.service) {
                this.formError = "Sélectionnez un type de prestation.";
                return;
            }
            if (this.form.description.trim().length < 20) {
                this.formError =
                    "Description trop courte (min. 20 caractères).";
                return;
            }
            if (!this.form.address.trim()) {
                this.formError = "Adresse obligatoire.";
                return;
            }
            this.submitting = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                const fd = new FormData();
                Object.entries(this.form).forEach(([k, v]) => {
                    if (v === null || v === undefined) return;
                    if (Array.isArray(v)) {
                        v.forEach((item) => fd.append(k + "[]", item));
                    } else {
                        fd.append(k, v);
                    }
                });
                this.imageFiles.forEach((f) => fd.append("images[]", f));
                const res = await fetch(this.routes.missions_store, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: fd,
                });
                const data = await res.json();
                if (!res.ok) {
                    this.formError = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data.message ?? "Erreur.");
                    return;
                }
                this.missions.unshift(data.mission);
                this.showNewMission = false;
                this.showToast(
                    "Mission publiée ! Un prestataire sera attribué rapidement.",
                    "success",
                );
            } catch {
                this.formError = "Erreur réseau.";
            } finally {
                this.submitting = false;
            }
        },

        // ── Chat ──────────────────────────────────────────────────
        openChat(id) {
            this.chatMissionId = id;
        },
        onChatClose() {
            this.chatMissionId = null;
            this.chatUnread = 0;
        },
        onChatUnread(count) {
            this.chatUnread = count;
            if (this.chatMissionId) {
                this.unreadByMission =
                    count > 0
                        ? {
                              ...this.unreadByMission,
                              [this.chatMissionId]: count,
                          }
                        : (() => {
                              const c = { ...this.unreadByMission };
                              delete c[this.chatMissionId];
                              return c;
                          })();
            }
        },

        // ── Notifications ─────────────────────────────────────────
        async fetchNotifications() {
            this.notifLoading = true;
            try {
                const res = await fetch(this.routes.notifications, {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                this.notifications = data.notifications ?? [];
                this.unreadCount = data.unread_count ?? 0;
            } catch {
                /* silencieux */
            } finally {
                this.notifLoading = false;
            }
        },
        toggleNotif() {
            this.notifOpen = !this.notifOpen;
            if (this.notifOpen) this.fetchNotifications();
        },
        async openNotif(n) {
            if (!n.read) {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content;
                await fetch(
                    this.routes.notifications_read.replace("{id}", n.id),
                    {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            Accept: "application/json",
                        },
                    },
                );
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            this.notifOpen = false;
        },
        async markAllRead() {
            const csrf = document.querySelector(
                'meta[name="csrf-token"]',
            )?.content;
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications.forEach((n) => (n.read = true));
            this.unreadCount = 0;
        },

        // ── Helpers affichage ─────────────────────────────────────
        countByTab(key) {
            if (key === "all") return this.missions.length;
            if (key === "active")
                return this.missions.filter((m) =>
                    this.activeStatuses.includes(m.status),
                ).length;
            if (key === "pending")
                return this.missions.filter((m) => m.status === "pending")
                    .length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status),
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },
        statusInfoIcon(s) {
            const m = {
                pending: "⏳",
                assigned: "🔍",
                accepted: "✅",
                on_the_way: "🚗",
                tracking: "📡",
                in_progress: "🔨",
                order_placed: "📋",
                completed: "🎉",
                closed: "🏁",
                cancelled: "❌",
            };
            return m[s] ?? "ℹ️";
        },
        statusInfoTitle(s) {
            const m = {
                pending: "En attente d'attribution",
                assigned: "Prestataire attribué",
                accepted: "Prestataire en route bientôt",
                on_the_way: "Prestataire en route",
                tracking: "Suivi GPS actif",
                in_progress: "Intervention en cours",
                order_placed: "Travaux démarrés",
                completed: "Terminée — paiement requis",
                closed: "Mission clôturée",
                cancelled: "Mission annulée",
            };
            return m[s] ?? s;
        },
        statusInfoSub(s) {
            const m = {
                pending:
                    "Un prestataire certifié sera attribué automatiquement.",
                assigned: "Le prestataire va bientôt vous contacter.",
                accepted: "Il vous contactera avant de se déplacer.",
                on_the_way: "Votre prestataire arrive.",
                in_progress: "L'intervention est en cours chez vous.",
                order_placed: "Les travaux sont en cours.",
                completed: "Confirmez la fin pour déclencher le paiement.",
                closed: "Merci de votre confiance !",
                cancelled: "Contactez le support pour plus d'informations.",
            };
            return m[s] ?? "";
        },
        labelOf(m) {
            const map = {
                pending: "En attente",
                assigned: "Assignée",
                accepted: "Acceptée",
                contact_made: "Contact établi",
                on_the_way: "En route",
                tracking: "Suivi",
                in_progress: "Sur place",
                quote_submitted: "Devis soumis",
                order_placed: "Commande passée",
                awaiting_confirm: "Att. confirmation",
                completed: "Terminée",
                closed: "Clôturée",
                cancelled: "Annulée",
            };
            return m.status_label ?? map[m.status] ?? m.status;
        },
        badgeClass(s) {
            const m = {
                pending: "pending",
                assigned: "active",
                accepted: "active",
                contact_made: "active",
                on_the_way: "active",
                tracking: "active",
                in_progress: "active",
                quote_submitted: "warning",
                order_placed: "warning",
                awaiting_confirm: "warning",
                completed: "done",
                closed: "done",
                cancelled: "cancelled",
            };
            return m[s] ?? "";
        },
        serviceIcon(s) {
            return (
                {
                    plomberie: "🔧",
                    electricite: "⚡",
                    menuiserie: "🪵",
                    ferronnerie: "⚙️",
                    frigoriste: "❄️",
                    mecanique: "🚗",
                    nettoyage: "🧹",
                    peinture: "🎨",
                    maintenance: "🛠️",
                }[s?.toLowerCase()] ?? "🔨"
            );
        },
        // Retourne le numéro d'étape en parsant step ou en déduisant depuis status
        onPanelScroll() {
            const el = this.$refs.missionPanel;
            if (!el) return;
            // Afficher le btn si on n'est pas encore en bas (>80px restants)
            this.showScrollBtn =
                el.scrollHeight - el.scrollTop - el.clientHeight > 80;
        },
        scrollPanelBottom() {
            const el = this.$refs.missionPanel;
            if (!el) return;
            el.scrollTo({ top: el.scrollHeight, behavior: "smooth" });
        },
        stepOf(m) {
            if (m.step !== undefined && m.step !== null)
                return parseInt(m.step) || 1;
            const map = {
                pending: 1,
                assigned: 2,
                accepted: 3,
                contact_made: 4,
                on_the_way: 5,
                tracking: 6,
                in_progress: 7,
                quote_submitted: 8,
                order_placed: 9,
                awaiting_confirm: 10,
                completed: 11,
                closed: 12,
                cancelled: 0,
            };
            return map[m.status] ?? 1;
        },
        formatDate(iso) {
            if (!iso) return "—";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },
        formatDateTime(iso) {
            if (!iso) return "—";
            const d = new Date(iso);
            return (
                d.toLocaleDateString("fr-FR", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                }) +
                " à " +
                d.toLocaleTimeString("fr-FR", {
                    hour: "2-digit",
                    minute: "2-digit",
                })
            );
        },
        formatPrice(a) {
            if (!a) return "—";
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(a)) + " FCFA"
            );
        },
        wip(f) {
            this.showToast(f + " — bientôt disponible.", "");
        },
        onGlobalUnreadUpdate(evt) {
            const byMission = evt.detail?.by_mission ?? {};
            // Ne pas écraser le compteur de la conversation ouverte (déjà lue)
            if (this.chatMissionId) {
                const updated = { ...byMission };
                delete updated[this.chatMissionId];
                this.unreadByMission = updated;
            } else {
                this.unreadByMission = byMission;
            }
        },
        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },
        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },
        handleClickOutside(e) {
            if (
                this.$refs.notifWrap &&
                !this.$refs.notifWrap.contains(e.target)
            )
                this.notifOpen = false;
        },
    },

    mounted() {
        this.fetchMissions();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000,
        );
        document.addEventListener("click", this.handleClickOutside);
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
        window.addEventListener("rt-unread-update", this.onGlobalUnreadUpdate);
    },
    beforeUnmount() {
        clearInterval(this.notifInterval);
        document.removeEventListener("click", this.handleClickOutside);
        window.removeEventListener("rt-unread-update", this.onGlobalUnreadUpdate);
    },
};
</script>

<style scoped>
.clm-wrap {
    --or: #f97316;
    --or2: #ea580c;
    --or3: #fff7ed;
    --am: #fbbf24;
    --dk: #1c1412;
    --gr: #7c6a5a;
    --grm: #8a7d78;
    --grl: #e8ddd4;
    --wh: #fff;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    background: #f8f4f0;
    min-height: 100vh;
}

/* TOPBAR */
.clm-topbar {
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
    .clm-topbar {
        height: 64px;
        padding: 0 24px;
    }
}
.clm-topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.clm-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.clm-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
}
.clm-page-sub {
    font-size: 11px;
    color: var(--gr);
    display: none;
}
@media (min-width: 480px) {
    .clm-page-sub {
        display: block;
    }
}
.clm-page-sub strong {
    color: var(--dk);
}
.clm-avatar {
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

/* NOTIF */
.clm-notif-wrap {
    position: relative;
}
.clm-notif-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gr);
    padding: 6px;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 8px;
    transition: color 0.18s;
}
.clm-notif-btn:hover {
    color: var(--or);
}
.clm-notif-btn svg {
    width: 22px;
    height: 22px;
}
.clm-notif-badge {
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
.clm-notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    z-index: 200;
    overflow: hidden;
}
.clm-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.clm-notif-read-all {
    background: none;
    border: none;
    font-size: 11.5px;
    color: var(--or);
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}
.clm-notif-loading,
.clm-notif-empty {
    padding: 24px;
    text-align: center;
    font-size: 13px;
    color: var(--gr);
}
.clm-notif-item {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #faf7f5;
    cursor: pointer;
    transition: background 0.15s;
}
.clm-notif-item:hover {
    background: #faf7f5;
}
.clm-notif-item.unread {
    background: #fff8f5;
}
.clm-notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--or);
    flex-shrink: 0;
    margin-top: 4px;
}
.clm-notif-content {
    flex: 1;
    min-width: 0;
}
.clm-notif-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.clm-notif-body {
    font-size: 12px;
    color: var(--gr);
    margin-top: 2px;
    line-height: 1.4;
}
.clm-notif-ago {
    font-size: 11px;
    color: var(--grm);
    margin-top: 4px;
}

/* CONTENT */
.clm-content {
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 1100px;
    margin: 0 auto;
    width: 100%;
}
@media (min-width: 600px) {
    .clm-content {
        padding: 24px;
    }
}

/* FILTRES */
.clm-filters {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.clm-search-wrap {
    position: relative;
}
.clm-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
}
.clm-search {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 9px 14px 9px 34px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    box-sizing: border-box;
    transition: border 0.15s;
}
.clm-search:focus {
    outline: none;
    border-color: var(--or);
}
.clm-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.clm-tab {
    padding: 7px 14px;
    border-radius: 9px;
    border: 1.5px solid transparent;
    background: var(--grl);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--gr);
    cursor: pointer;
    transition: all 0.18s;
    font-family: "Poppins", sans-serif;
    display: flex;
    align-items: center;
    gap: 5px;
}
.clm-tab:hover {
    background: var(--or3);
    color: var(--or);
}
.clm-tab.active {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
}
.clm-tab-count {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}
.clm-tab.active .clm-tab-count {
    background: rgba(255, 255, 255, 0.25);
}

/* LISTE */
.clm-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.clm-skeleton-row {
    height: 76px;
    background: var(--grl);
    border-radius: 14px;
    animation: clm-pulse 1.2s infinite;
}
@keyframes clm-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}
.clm-alert-error {
    background: #fee2e2;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 8px;
}
.clm-empty {
    text-align: center;
    padding: 56px 24px;
    background: var(--wh);
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.clm-empty-icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.clm-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.clm-empty-sub {
    font-size: 13px;
    color: var(--gr);
    margin-top: 4px;
}
.clm-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.clm-item {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.18s;
}
.clm-item:hover {
    border-color: var(--am);
    transform: translateY(-1px);
}
.clm-item-icon {
    font-size: 26px;
    flex-shrink: 0;
}
.clm-item-body {
    flex: 1;
    min-width: 0;
}
.clm-item-title {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--dk);
    display: flex;
    align-items: center;
    gap: 8px;
}
.clm-item-meta,
.clm-item-addr {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 2px;
}
.clm-msg-unread {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 700;
    color: var(--or);
    background: var(--or3);
    border-radius: 99px;
    padding: 2px 9px;
    margin-top: 5px;
}
.clm-item-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.clm-item-price {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.clm-type-chip {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 99px;
    background: var(--grl);
    color: var(--gr);
}

/* BADGES */
.clm-badge {
    padding: 4px 11px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    background: #f5f0eb;
    color: var(--dk);
}
.clm-badge.pending {
    background: #fef3c7;
    color: #d97706;
}
.clm-badge.warning {
    background: #ffedd5;
    color: var(--or2);
}
.clm-badge.active {
    background: #dbeafe;
    color: #1d4ed8;
}
.clm-badge.done {
    background: #dcfce7;
    color: #16a34a;
}
.clm-badge.cancelled {
    background: #fee2e2;
    color: #dc2626;
}

/* PANEL */
.clm-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
@media (max-width: 600px) {
    .clm-panel-overlay {
        align-items: flex-end;
    }
}
.clm-panel {
    background: var(--wh);
    width: 100%;
    max-width: 520px;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: -8px 0 40px rgba(0, 0, 0, 0.15);
    animation: clm-slide-in 0.25s ease;
    -webkit-overflow-scrolling: touch;
}
@keyframes clm-slide-in {
    from {
        transform: translateX(40px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
@media (max-width: 600px) {
    .clm-panel {
        height: 90vh;
        max-width: 100%;
        border-radius: 18px 18px 0 0;
        animation: clm-slide-up 0.25s ease;
    }
    @keyframes clm-slide-up {
        from {
            transform: translateY(40px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
}
.clm-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 2px solid var(--grl);
    position: sticky;
    top: 0;
    background: var(--wh);
    z-index: 1;
}
.clm-panel-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: var(--dk);
}
.clm-panel-sub {
    font-size: 12px;
    color: var(--gr);
    margin-top: 3px;
}
.clm-panel-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.clm-panel-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--gr);
}

/* WORKFLOW */
.clm-workflow {
    padding: 14px 20px 16px;
    border-bottom: 2px solid var(--grl);
    background: #faf7f5;
}
.clm-workflow-track {
    height: 5px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 14px;
}
.clm-workflow-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--or), var(--or2));
    border-radius: 99px;
    transition: width 0.5s;
}
/* Fil bullet */
.clm-wf-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px 4px;
    position: relative;
}
@media (min-width: 400px) {
    .clm-wf-steps {
        grid-template-columns: repeat(6, 1fr);
    }
}
.clm-wf-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    position: relative;
}
/* Ligne de connexion entre dots */
.clm-wf-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 7px;
    left: calc(50% + 8px);
    width: calc(100% - 16px);
    height: 2px;
    background: var(--grl);
}
.clm-wf-step.wf-done:not(:last-child)::after {
    background: var(--or);
}
.clm-wf-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2.5px solid var(--grl);
    background: var(--wh);
    transition: all 0.25s;
    flex-shrink: 0;
    z-index: 1;
}
.clm-wf-step.wf-done .clm-wf-dot {
    background: var(--or);
    border-color: var(--or);
}
.clm-wf-step.wf-current .clm-wf-dot {
    background: var(--or);
    border-color: var(--or2);
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
    transform: scale(1.25);
}
.clm-wf-label {
    font-size: 9px;
    font-weight: 700;
    color: var(--grm);
    text-align: center;
    line-height: 1.2;
    max-width: 48px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.clm-wf-step.wf-done .clm-wf-label {
    color: var(--or2);
}
.clm-wf-step.wf-current .clm-wf-label {
    color: var(--or);
    font-weight: 800;
}

/* PANEL BODY */
.clm-panel-body {
    padding: 20px 20px 60px;
    flex: 1;
}
.clm-panel-cols {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.clm-info-col,
.clm-actions-col {
    min-width: 0;
    width: 100%;
}
.clm-section {
    background: var(--wh);
    border: 1.5px solid var(--grl);
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 16px;
}
.clm-section-title {
    font-size: 11px;
    font-weight: 800;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.clm-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    gap: 12px;
}
.clm-row:last-child {
    border-bottom: none;
}
.clm-row span {
    color: var(--gr);
    flex-shrink: 0;
    min-width: 80px;
}
.clm-row strong {
    font-weight: 700;
    color: var(--dk);
    text-align: right;
    word-break: break-word;
    overflow-wrap: anywhere;
    max-width: 65%;
}
.clm-masked {
    font-size: 12px;
    color: var(--gr);
    font-style: italic;
}
.clm-section-pending {
    text-align: center;
    padding: 20px 16px;
}
.clm-pending-icon {
    font-size: 28px;
    margin-bottom: 8px;
}
.clm-pending-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.clm-pending-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 4px;
}

/* ACTION BLOCKS */
.clm-action-block {
    background: var(--grl);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border: 1.5px solid var(--grl);
}
.clm-action-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.clm-action-row .clm-btn {
    flex: 1;
    justify-content: center;
}
/* ACTION QUOTE — nouveau bloc détaillé */
.clm-action-quote {
    border-color: var(--am);
    background: #fffbeb;
    padding: 16px;
}
.clm-quote-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.clm-quote-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.clm-action-quote-icon {
    font-size: 26px;
    flex-shrink: 0;
}
.clm-action-quote-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.clm-quote-version {
    font-size: 11px;
    color: var(--or);
    font-weight: 700;
    margin-top: 2px;
}
.clm-btn-download {
    font-size: 12px;
    font-weight: 700;
    color: var(--or2);
    background: #fff;
    border: 1.5px solid #fed7aa;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.15s;
    flex-shrink: 0;
}
.clm-btn-download:hover {
    background: var(--or3);
}
.clm-quote-diag {
    background: #fff;
    border-left: 3px solid var(--or);
    border-radius: 0 8px 8px 0;
    padding: 8px 12px;
    margin-bottom: 10px;
}
.clm-quote-diag-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--or2);
    margin-bottom: 3px;
}
.clm-quote-diag-text {
    font-size: 12.5px;
    color: var(--gr);
    line-height: 1.5;
}
.clm-quote-lines {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
    border: 1px solid #fed7aa;
}
.clm-quote-lines-head {
    display: grid;
    grid-template-columns: 1fr 36px 80px 80px;
    gap: 4px;
    padding: 6px 10px;
    background: #fff7ed;
    font-size: 10px;
    font-weight: 700;
    color: var(--grm);
    text-transform: uppercase;
}
.clm-quote-line-row {
    display: grid;
    grid-template-columns: 1fr 36px 80px 80px;
    gap: 4px;
    padding: 8px 10px;
    border-top: 1px solid #fef3c7;
    font-size: 12.5px;
    align-items: start;
}
.ql-desc {
    color: var(--dk);
    line-height: 1.4;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.ql-qty,
.ql-pu,
.ql-total {
    color: var(--gr);
}
.ql-qty {
    text-align: center;
}
.ql-pu,
.ql-total {
    text-align: right;
}
.ql-total {
    font-weight: 700;
    color: var(--dk);
}
.ql-type-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 99px;
    background: #e8ddd4;
    color: var(--gr);
    width: fit-content;
}
.ql-type-badge.labor {
    background: #dbeafe;
    color: #1d4ed8;
}
.ql-type-badge.travel {
    background: #f0fdf4;
    color: #15803d;
}
.ql-type-badge.other {
    background: #f3f4f6;
    color: #6b7280;
}
.clm-quote-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #fff7ed;
    border-radius: 8px;
    border: 1.5px solid #fed7aa;
    font-size: 13px;
    color: var(--gr);
}
.clm-quote-total-amount {
    font-size: 18px;
    font-weight: 800;
    color: var(--or);
}
.clm-action-quote-rejected {
    border-color: #fca5a5;
    background: #fff5f5;
    text-align: center;
    padding: 20px 16px;
}
.clm-qr-icon {
    font-size: 28px;
    margin-bottom: 8px;
}
.clm-qr-title {
    font-size: 15px;
    font-weight: 800;
    color: #dc2626;
    margin-bottom: 6px;
}
.clm-qr-reason {
    font-size: 13px;
    color: #7f1d1d;
    margin-bottom: 8px;
    background: #fee2e2;
    border-radius: 8px;
    padding: 8px 12px;
}
.clm-qr-sub {
    font-size: 12.5px;
    color: #991b1b;
    line-height: 1.5;
}

.clm-rq-options {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 4px;
}
.clm-action-confirm {
    border-color: #fde68a;
    background: #fffbeb;
    text-align: center;
}
.clm-action-confirm-icon {
    font-size: 32px;
    margin-bottom: 8px;
}
.clm-action-confirm-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    margin-bottom: 4px;
}
.clm-action-confirm-sub {
    font-size: 13px;
    color: var(--gr);
    margin-bottom: 14px;
    line-height: 1.5;
}
.clm-action-pay {
    border-color: #bbf7d0;
    background: #f0fdf4;
    text-align: center;
}
.clm-action-pay-icon {
    font-size: 32px;
    margin-bottom: 8px;
}
.clm-action-pay-title {
    font-size: 15px;
    font-weight: 800;
    color: #15803d;
    margin-bottom: 4px;
}
.clm-action-pay-sub {
    font-size: 13px;
    color: #16a34a;
    margin-bottom: 14px;
}
.clm-status-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: var(--grl);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
}
.clm-status-info-icon {
    font-size: 24px;
    flex-shrink: 0;
}
.clm-status-info-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.clm-status-info-sub {
    font-size: 12.5px;
    color: var(--gr);
    margin-top: 3px;
    line-height: 1.5;
}

/* BUTTONS */
.clm-btn {
    padding: 9px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
}
.clm-btn-orange {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.clm-btn-orange:hover:not(:disabled) {
    transform: translateY(-1px);
}
.clm-btn-red {
    background: #ef4444;
    color: #fff;
}
.clm-btn-red:hover:not(:disabled) {
    background: #dc2626;
}
.clm-btn-green {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.3);
}
.clm-btn-green:hover:not(:disabled) {
    transform: translateY(-1px);
}
.clm-btn-green:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Avis ── */
.clm-action-review {
    border-color: #fde68a;
    background: #fffbeb;
}
.clm-review-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.clm-review-icon {
    font-size: 28px;
    flex-shrink: 0;
}
.clm-review-title {
    font-size: 14px;
    font-weight: 800;
    color: #92400e;
}
.clm-review-sub {
    font-size: 12px;
    color: #a16207;
    margin-top: 2px;
}
.clm-stars-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
}
.clm-star {
    background: none;
    border: none;
    font-size: 28px;
    color: #d1d5db;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition:
        color 0.15s,
        transform 0.12s;
}
.clm-star:hover,
.clm-star.active {
    color: #f59e0b;
    transform: scale(1.15);
}
.clm-star-label {
    font-size: 13px;
    font-weight: 700;
    color: #92400e;
    margin-left: 6px;
}
.clm-review-textarea {
    width: 100%;
    border: 1.5px solid #fcd34d;
    border-radius: 10px;
    padding: 10px 12px;
    font-family: "Poppins", sans-serif;
    font-size: 13px;
    color: #1c1412;
    background: #fff;
    outline: none;
    resize: vertical;
    transition: border-color 0.15s;
    margin-bottom: 4px;
}
.clm-review-textarea:focus {
    border-color: #f59e0b;
}
.clm-review-textarea::placeholder {
    color: #b5a9a3;
}
.clm-review-count {
    font-size: 11px;
    color: #a16207;
    text-align: right;
    margin-bottom: 12px;
}
.clm-review-skip {
    text-align: center;
    margin-top: 8px;
}
.clm-link {
    background: none;
    border: none;
    font-size: 12px;
    color: #8a7d78;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    text-decoration: underline;
    padding: 0;
}
.clm-link:hover {
    color: #4a3f3a;
}
.clm-link:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Adresse géolocalisée ── */
.clm-geo-ok-addr {
    font-size: 12.5px;
    font-weight: 600;
    color: #15803d;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.clm-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.clm-btn-ghost:hover {
    background: #d5c9c0;
}
.clm-btn-chat {
    background: linear-gradient(135deg, var(--or), var(--or2));
    color: #fff;
    position: relative;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
}
.clm-btn-chat:hover {
    transform: translateY(-1px);
}
.clm-btn-full {
    width: 100%;
}
.clm-btn-sm {
    font-size: 12px;
    padding: 6px 12px;
}
.clm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}
.clm-chat-badge {
    position: absolute;
    top: -7px;
    right: -7px;
    background: #ef4444;
    color: #fff;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}
.clm-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: clm-spin 0.7s linear infinite;
}
.clm-spinner-sm {
    width: 12px;
    height: 12px;
    border-width: 2px;
}
@keyframes clm-spin {
    to {
        transform: rotate(360deg);
    }
}

/* MODAL */
.clm-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    z-index: 300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.clm-modal {
    background: var(--wh);
    border-radius: 18px;
    width: 100%;
    max-width: 460px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    animation: clm-modal-in 0.25s ease;
}
.clm-modal-lg {
    max-width: 540px;
}
@keyframes clm-modal-in {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.clm-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 2px solid var(--grl);
}
.clm-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    color: var(--dk);
}
.clm-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: var(--gr);
}
.clm-modal-body {
    padding: 18px 22px;
}
.clm-modal-footer {
    padding: 14px 22px;
    border-top: 2px solid var(--grl);
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    background: #faf7f4;
    border-radius: 0 0 18px 18px;
}
.clm-field {
    margin-bottom: 16px;
}
.clm-field label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 6px;
}
.clm-req {
    color: #dc2626;
}
.clm-form-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
    margin-bottom: 6px;
}
.clm-input {
    width: 100%;
    border: 2px solid var(--grl);
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 13.5px;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    box-sizing: border-box;
    transition: border 0.15s;
}
.clm-input:focus {
    outline: none;
    border-color: var(--or);
}
.clm-textarea {
    resize: vertical;
    min-height: 80px;
}
.clm-char-count {
    font-size: 11.5px;
    color: var(--grm);
    text-align: right;
    margin-top: 4px;
}
.clm-char-count.warn {
    color: #d97706;
}
.clm-radio-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.clm-radio-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 9px 16px;
    background: var(--grl);
    border-radius: 10px;
    border: 2px solid transparent;
    font-size: 13.5px;
    font-weight: 600;
    transition: all 0.15s;
}
.clm-radio-opt:has(input:checked),
.clm-radio-opt.selected {
    border-color: var(--or);
    background: var(--or3);
    color: var(--or2);
}
.clm-radio-opt input {
    accent-color: var(--or);
}
.clm-btn-geo {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border: 2px dashed var(--grl);
    border-radius: 10px;
    background: none;
    font-size: 13px;
    color: var(--gr);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.15s;
    width: 100%;
    margin-top: 4px;
}
.clm-btn-geo:hover {
    border-color: var(--or);
    color: var(--or);
}
.clm-geo-ok {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    color: #15803d;
    margin-top: 4px;
}
.clm-geo-reset {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    font-size: 16px;
    margin-left: auto;
}
.clm-img-upload-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}
.clm-img-add-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border: 2px dashed #e8ddd4;
    border-radius: 10px;
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 700;
    color: #8a7d78;
    transition: border-color 0.15s, color 0.15s;
    flex-shrink: 0;
}
.clm-img-add-btn:hover {
    border-color: #f97316;
    color: #f97316;
}
.clm-img-thumb {
    position: relative;
    width: 72px;
    height: 72px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}
.clm-img-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.clm-img-remove {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 18px;
    height: 18px;
    background: rgba(0,0,0,0.6);
    border: none;
    border-radius: 50%;
    color: #fff;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}
.clm-row-images {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
}
.clm-mission-images {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
}
.clm-mission-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: opacity 0.15s;
}
.clm-mission-img:hover {
    opacity: 0.85;
}
.clm-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 20px;
}
.clm-lightbox img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
}
.clm-scroll-btn {
    position: sticky;
    top: calc(100% - 56px);
    margin: 0 auto -20px;
    z-index: 10;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--or), var(--or2));
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(249, 115, 22, 0.45);
    animation: clm-bounce-down 1.2s ease-in-out infinite;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: all;
}
.clm-scroll-btn svg {
    width: 22px;
    height: 22px;
    stroke: #fff;
}
@keyframes clm-bounce-down {
    0%,
    100% {
        transform: translateX(-50%) translateY(0);
    }
    50% {
        transform: translateX(-50%) translateY(6px);
    }
}
.clm-security-banner {
    display: flex;
    gap: 12px;
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 12px;
}
.clm-security-icon {
    font-size: 24px;
    flex-shrink: 0;
}
.clm-security-body {
    flex: 1;
    min-width: 0;
}
.clm-security-title {
    font-size: 13px;
    font-weight: 800;
    color: #92400e;
    margin-bottom: 8px;
}
.clm-security-list {
    margin: 0 0 8px 0;
    padding-left: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 12.5px;
    color: #78350f;
    line-height: 1.5;
}
.clm-security-list li {
    padding-left: 0;
}
.clm-security-note {
    font-size: 11px;
    color: #b45309;
    font-style: italic;
    padding-top: 6px;
    border-top: 1px solid #fde68a;
    margin-top: 4px;
}

/* TOASTS */
.clm-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
}
.clm-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: clm-modal-in 0.3s ease;
}
.clm-toast.success {
    background: #16a34a;
}
.clm-toast.error {
    background: #dc2626;
}
</style>
