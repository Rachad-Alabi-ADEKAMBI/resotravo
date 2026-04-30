<template>
    <div class="clm-wrap">
        <!-- -------------- TOPBAR -------------- -->
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
                    <div class="clm-page-title">Mes commandes</div>
                    <div class="clm-page-sub">
                        <strong>{{ user.name }}</strong>
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
                            unreadCount
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
                    + Nouvelle commande
                </button>
            </div>
        </div>

        <!-- -------------- CONTENU -------------- -->
        <div class="clm-content">
            <!-- Filtres -->
            <div class="clm-filters">
                <div class="clm-search-wrap">
                    <span class="clm-search-icon">??</span>
                    <input
                        class="clm-search"
                        type="text"
                        v-model="search"
                        @input="currentPage = 1"
                        placeholder="Rechercher une mission�"
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
                ?? {{ error }}
                <button
                    class="clm-btn clm-btn-ghost clm-btn-sm"
                    @click="fetchMissions"
                >
                    R�essayer
                </button>
            </div>

            <!-- Vide -->
            <div class="clm-empty" v-else-if="totalFiltered === 0">
                <div class="clm-empty-icon">??</div>
                <div class="clm-empty-title">Aucune mission</div>
                <div class="clm-empty-sub">
                    Publiez votre premi�re mission pour trouver un prestataire
                    certifi�.
                </div>
                <button
                    class="clm-btn clm-btn-orange"
                    @click="openNewMission"
                    style="margin-top: 16px"
                >
                    + Cr�er une mission
                </button>
            </div>

            <!-- Liste missions -->
            <div class="clm-list" v-else>
                <div
                    class="clm-item"
                    v-for="m in pagedMissions"
                    :key="m.id"
                    :class="{ 'has-unread': unreadByMission[m.id] }"
                    @click="openMission(m)"
                >
                    <!-- Bande unread gauche -->
                    <div
                        class="clm-unread-stripe"
                        v-if="unreadByMission[m.id]"
                    ></div>

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
                                    m.location_type === "business" ? "??" : "??"
                                }}
                            </span>
                        </div>
                        <div class="clm-item-meta">
                            {{
                                m.contractor && m.contractor.name
                                    ? "?? " + m.contractor.name
                                    : m.contractor
                                    ? "?? Prestataire assign�"
                                    : "? Attribution en cours�"
                            }}
                            � {{ formatDateTime(m.created_at) }}
                        </div>
                        <div class="clm-item-addr">?? {{ m.address }}</div>
                        <div class="clm-item-reservation" v-if="m.reservation">
                            &#128197; Planifi&eacute; le
                            {{
                                formatReservationDate(m.reservation.day)
                            }}
                            &agrave; {{ m.reservation.time?.substring(0, 5) }}
                        </div>
                        <!-- Miniatures images -->
                        <div
                            class="clm-item-imgs"
                            v-if="m.images && m.images.length"
                        >
                            <img
                                v-for="(url, i) in m.images.slice(0, 5)"
                                :key="i"
                                :src="url"
                                class="clm-item-img-thumb"
                                @click.stop="missionLightbox = url"
                            />
                            <span
                                class="clm-item-imgs-more"
                                v-if="m.images.length > 5"
                                >+{{ m.images.length - 5 }}</span
                            >
                        </div>
                        <!-- Badge non-lu chat -->
                        <div
                            class="clm-msg-unread"
                            v-if="unreadByMission[m.id]"
                        >
                            ?? {{ unreadByMission[m.id] }} message{{
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
                        <!-- Bouton Lire message � ouvre direct le chat sans ouvrir le panel -->
                        <button
                            class="clm-read-msg-btn"
                            v-if="unreadByMission[m.id]"
                            @click.stop="openChat(m.id)"
                            :title="
                                'Ouvrir la conversation � ' +
                                unreadByMission[m.id] +
                                ' non lu(s)'
                            "
                        >
                            <span class="clm-read-msg-dot">{{
                                unreadByMission[m.id]
                            }}</span>
                            Lire
                        </button>
                    </div>
                </div>
            </div>

            <!-- -- PAGINATION -- -->
            <div class="clm-pagination" v-if="totalPages > 1">
                <button
                    class="clm-page-btn"
                    @click="currentPage = 1"
                    :disabled="currentPage === 1"
                >
                    �
                </button>
                <button
                    class="clm-page-btn"
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                >
                    �
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
                    �
                </button>
                <button
                    class="clm-page-btn"
                    @click="currentPage = totalPages"
                    :disabled="currentPage === totalPages"
                >
                    �
                </button>
                <span class="clm-page-info">
                    {{ (currentPage - 1) * perPage + 1 }}�{{
                        Math.min(currentPage * perPage, totalFiltered)
                    }}
                    sur {{ totalFiltered }}
                </span>
            </div>
        </div>

        <!-- -------------- PANEL D�TAIL MISSION -------------- -->
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
                            Mission #{{ activeMission.id }} �
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
                    <!-- Barre lin�aire -->
                    <div class="clm-workflow-track">
                        <div
                            class="clm-workflow-fill"
                            :style="{
                                width: (stepOf(activeMission) / 12) * 100 + '%',
                            }"
                        ></div>
                    </div>
                    <!-- Fil d'�tapes avec labels -->
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
                                <div class="clm-section-title">?? D�tails</div>
                                <div class="clm-row">
                                    <span>Type</span
                                    ><strong>{{
                                        activeMission.location_type ===
                                        "business"
                                            ? "?? Entreprise"
                                            : "?? Domicile"
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Adresse</span
                                    ><strong>{{
                                        activeMission.address
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Mode</span
                                    ><strong>{{
                                        activeMission.latitude &&
                                        activeMission.longitude
                                            ? "?? G�olocalisation"
                                            : "?? Saisie manuelle"
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Description</span
                                    ><strong>{{
                                        activeMission.description ?? "�"
                                    }}</strong>
                                </div>
                                <div
                                    class="clm-row clm-row-images"
                                    v-if="
                                        activeMission.images &&
                                        activeMission.images.length
                                    "
                                >
                                    <span>Photos</span>
                                    <div class="clm-mission-images">
                                        <img
                                            v-for="(
                                                url, i
                                            ) in activeMission.images"
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
                                    <span>Accept�e le</span>
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
                                    ?? Prestataire
                                </div>
                                <div class="clm-row">
                                    <span>Nom</span
                                    ><strong>{{
                                        activeMission.contractor.name
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Sp�cialit�</span
                                    ><strong>{{
                                        activeMission.contractor.specialty
                                    }}</strong>
                                </div>
                                <div class="clm-row">
                                    <span>Note</span
                                    ><strong
                                        >?
                                        {{
                                            activeMission.contractor
                                                .average_rating ?? "�"
                                        }}</strong
                                    >
                                </div>
                                <div class="clm-row">
                                    <span>Contact</span
                                    ><strong class="clm-masked"
                                        >?? Num�ro masqu� � utilisez la
                                        messagerie</strong
                                    >
                                </div>
                            </div>

                            <div class="clm-section clm-section-pending" v-else>
                                <div class="clm-pending-icon">?</div>
                                <div class="clm-pending-title">
                                    Attribution en cours
                                </div>
                                <div class="clm-pending-sub">
                                    Un prestataire certifi� va �tre attribu�
                                    automatiquement sous peu.
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="clm-actions-col">
                            <!-- -- Devis soumis � EN ATTENTE D'APPROBATION -- -->
                            <div
                                class="clm-action-block clm-action-quote"
                                v-if="
                                    activeMission.status ===
                                        'quote_submitted' &&
                                    activeMission.quote &&
                                    activeMission.quote.status !== 'rejected'
                                "
                            >
                                <!-- En-t�te devis -->
                                <div class="clm-quote-header">
                                    <div class="clm-quote-header-left">
                                        <div class="clm-action-quote-icon">
                                            ??
                                        </div>
                                        <div>
                                            <div class="clm-action-quote-title">
                                                Devis � approuver
                                            </div>
                                            <div
                                                class="clm-quote-version"
                                                v-if="
                                                    activeMission.quote
                                                        .version > 1
                                                "
                                            >
                                                R�vision v{{
                                                    activeMission.quote.version
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        class="clm-btn-download"
                                        @click="downloadQuotePdf(activeMission)"
                                        title="T�l�charger PDF"
                                    >
                                        ? PDF
                                    </button>
                                </div>

                                <!-- Diagnostic -->
                                <div
                                    class="clm-quote-diag"
                                    v-if="activeMission.quote.diagnosis"
                                >
                                    <div class="clm-quote-diag-label">
                                        ?? Diagnostic
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
                                        <span class="ql-desc">D�signation</span>
                                        <span class="ql-qty">Qt�</span>
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
                                                        item.type
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
                                                item.quantity * item.unit_price
                                            )
                                        }}</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="clm-quote-total-row">
                                    <span>Total devis</span>
                                    <strong class="clm-quote-total-amount">{{
                                        formatPrice(
                                            activeMission.quote.amount_incl_tax
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
                                        ? Refuser
                                    </button>
                                    <button
                                        class="clm-btn clm-btn-green"
                                        @click="approveQuote(activeMission)"
                                        :disabled="actionLoading"
                                    >
                                        <div
                                            class="clm-spinner"
                                            v-if="actionLoading"
                                        ></div>
                                        <span v-else>? Approuver</span>
                                    </button>
                                </div>
                            </div>

                            <!-- -- Devis refus� � bloc informatif -- -->
                            <div
                                class="clm-action-block clm-action-quote-rejected"
                                v-if="
                                    activeMission.status ===
                                        'quote_submitted' &&
                                    activeMission.quote &&
                                    activeMission.quote.status === 'rejected'
                                "
                            >
                                <div class="clm-qr-icon">?</div>
                                <div class="clm-qr-title">Devis refus�</div>
                                <div
                                    class="clm-qr-reason"
                                    v-if="activeMission.reported_issue"
                                >
                                    Motif transmis :
                                    <em
                                        >�
                                        {{ activeMission.reported_issue }} �</em
                                    >
                                </div>
                                <div class="clm-qr-sub">
                                    Le prestataire a �t� notifi�. Il peut vous
                                    soumettre un nouveau devis r�vis� ou
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
                                <div class="clm-action-confirm-icon">??</div>
                                <div class="clm-action-confirm-title">
                                    Travaux termin�s
                                </div>
                                <div class="clm-action-confirm-sub">
                                    Le prestataire a marqu� l'intervention comme
                                    termin�e. Confirmez-vous la fin des travaux
                                    ?
                                </div>
                                <div class="clm-action-row">
                                    <button
                                        class="clm-btn clm-btn-ghost"
                                        @click="openSignalProblem"
                                    >
                                        ?? Signaler un probl�me
                                    </button>
                                    <button
                                        class="clm-btn clm-btn-green"
                                        @click="
                                            confirmCompletion(activeMission)
                                        "
                                        :disabled="actionLoading"
                                    >
                                        <div
                                            class="clm-spinner"
                                            v-if="actionLoading"
                                        ></div>
                                        <span v-else>? Confirmer la fin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Paiement MoMo � visible d�s completed, m�me apr�s avis -->
                            <div
                                class="clm-action-block clm-action-pay"
                                v-if="
                                    !activeMission.paid_at &&
                                    (activeMission.payment_unlocked ||
                                        activeMission.status === 'completed')
                                "
                            >
                                <div class="clm-action-pay-icon">??</div>
                                <div class="clm-action-pay-title">
                                    Paiement requis
                                </div>
                                <div class="clm-action-pay-sub">
                                    Choisissez votre r�seau et proc�dez au
                                    paiement mobile pour cl�turer la mission.
                                </div>
                                <button
                                    class="clm-btn clm-btn-orange clm-btn-full"
                                    @click="openMomoModal(activeMission)"
                                >
                                    ?? Payer via Mobile Money ?
                                </button>
                            </div>

                            <!-- Re�u � mission pay�e -->
                            <div
                                class="clm-action-block clm-action-receipt"
                                v-if="activeMission.paid_at"
                            >
                                <div class="clm-action-pay-icon">??</div>
                                <div class="clm-action-pay-title">
                                    Mission cl�tur�e &amp; pay�e
                                </div>
                                <div class="clm-action-pay-sub">
                                    Le paiement a �t� confirm�.
                                </div>
                                <a
                                    :href="routes.receipt.replace('{id}', activeMission.id)"
                                    target="_blank"
                                    class="clm-btn clm-btn-green clm-btn-full"
                                    style="text-decoration:none;display:block;text-align:center"
                                >
                                    ?? T�l�charger le re�u
                                </a>
                            </div>

                            <!-- Facture disponible d�s que le devis est approuv� -->
                            <div
                                class="clm-action-block clm-action-invoice"
                                v-if="['order_placed','awaiting_confirm','completed','closed'].includes(activeMission.status)"
                            >
                                <div class="clm-invoice-icon">??</div>
                                <div class="clm-invoice-title">Facture disponible</div>
                                <div class="clm-invoice-sub">Votre devis a �t� approuv�. T�l�chargez votre facture.</div>
                                <a
                                    :href="routes.invoice.replace('{id}', activeMission.id)"
                                    target="_blank"
                                    class="clm-btn clm-btn-green clm-btn-full"
                                    style="text-decoration:none;display:block;text-align:center"
                                >
                                    ?? T�l�charger la facture
                                </a>
                            </div>

                            <!-- Avis prestataire (statut completed) -->
                            <div
                                class="clm-action-block clm-action-review"
                                v-if="activeMission.status === 'completed'"
                            >
                                <div class="clm-review-header">
                                    <span class="clm-review-icon">?</span>
                                    <div>
                                        <div class="clm-review-title">
                                            �valuez le prestataire
                                        </div>
                                        <div class="clm-review-sub">
                                            Votre avis aide la communaut�
                                            Mesotravo
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
                                        ?
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
                                    placeholder="D�crivez votre exp�rience (optionnel)�"
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
                                        >? Soumettre l'avis et cl�turer la
                                        mission</span
                                    >
                                </button>
                                <div class="clm-review-skip">
                                    <button
                                        class="clm-link"
                                        @click="closeMissionOnly(activeMission)"
                                        :disabled="reviewModal.loading"
                                    >
                                        Passer et cl�turer sans avis ?
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
                                                activeMission.status
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

                            <!-- -- Banni�re s�curit� : prestataire en route / sur place -- -->
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
                                <div class="clm-security-icon">???</div>
                                <div class="clm-security-body">
                                    <div class="clm-security-title">
                                        Recommandations de s�curit�
                                    </div>
                                    <ul class="clm-security-list">
                                        <li>
                                            ? V�rifiez l'identit� du
                                            prestataire � son arriv�e
                                        </li>
                                        <li>
                                            ?? Contr�lez son badge
                                            <strong
                                                >Profil Certifi�
                                                Mesotravo</strong
                                            >
                                        </li>
                                        <li>
                                            ?? N'effectuez aucun paiement en
                                            dehors de la plateforme
                                        </li>
                                        <li>
                                            ?? Tout contact passe par la
                                            messagerie int�gr�e
                                        </li>
                                    </ul>
                                    <div class="clm-security-note">
                                        Tout paiement hors-plateforme d�gage
                                        Mesotravo de toute responsabilit�.
                                    </div>
                                </div>
                            </div>

                            <!-- -- Devis approuv� � consulter apr�s approbation -- -->
                            <div
                                class="clm-action-block clm-action-quote-approved"
                                v-if="
                                    activeMission.quote &&
                                    activeMission.quote.status !== 'draft' &&
                                    activeMission.status !== 'quote_submitted'
                                "
                            >
                                <div class="clm-qa-header">
                                    <div class="clm-qa-left">
                                        <span class="clm-qa-icon">??</span>
                                        <div>
                                            <div class="clm-qa-title">
                                                Devis
                                                <span
                                                    v-if="
                                                        activeMission.quote
                                                            .version > 1
                                                    "
                                                    >v{{
                                                        activeMission.quote
                                                            .version
                                                    }}</span
                                                >
                                            </div>
                                            <div class="clm-qa-amount">
                                                {{
                                                    formatPrice(
                                                        activeMission.quote
                                                            .amount_incl_tax
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        class="clm-btn-download"
                                        @click="downloadQuotePdf(activeMission)"
                                        title="Consulter / T�l�charger PDF"
                                    >
                                        ? PDF
                                    </button>
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
                                ?? Contacter le prestataire
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

        <!-- -------------- MODAL NOUVELLE MISSION -------------- -->
        <div
            class="clm-modal-overlay"
            v-if="showNewMission"
            @click.self="showNewMission = false"
        >
            <div class="clm-modal clm-modal-lg">
                <div class="clm-modal-header">
                    <h3>? Nouvelle commande</h3>
                    <button
                        class="clm-modal-close"
                        @click="showNewMission = false"
                    >
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body">
                    <!-- Quand souhaitez-vous le service ? -->
                    <div class="clm-field">
                        <label
                            >Quand souhaitez-vous le service ?
                            <span class="clm-req">*</span></label
                        >
                        <div class="clm-radio-row">
                            <label
                                class="clm-radio-opt"
                                :class="{
                                    selected: form.schedule_type === 'now',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="form.schedule_type"
                                    value="now"
                                />
                                &#9889; Maintenant
                            </label>
                            <label
                                class="clm-radio-opt"
                                :class="{
                                    selected: form.schedule_type === 'later',
                                }"
                            >
                                <input
                                    type="radio"
                                    v-model="form.schedule_type"
                                    value="later"
                                />
                                &#128197; Planifier pour plus tard
                            </label>
                        </div>
                    </div>
                    <!-- Date & Heure si planifi� -->
                    <div
                        class="clm-field"
                        v-if="form.schedule_type === 'later'"
                        style="display: flex; gap: 12px"
                    >
                        <div style="flex: 1">
                            <label>Date <span class="clm-req">*</span></label>
                            <input
                                class="clm-input"
                                type="date"
                                v-model="form.reservation_day"
                                :min="todayDate"
                            />
                        </div>
                        <div style="flex: 1">
                            <label>Heure <span class="clm-req">*</span></label>
                            <input
                                class="clm-input"
                                type="time"
                                v-model="form.reservation_time"
                            />
                        </div>
                    </div>
                    <div class="clm-field">
                        <label>Prestation <span class="clm-req">*</span></label>
                        <select class="clm-input" v-model="form.service">
                            <option value="">S�lectionner�</option>
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
                            placeholder="D�crivez votre besoin en d�tail (min. 20 caract�res)�"
                        ></textarea>
                        <div
                            class="clm-char-count"
                            :class="{ warn: form.description.length < 20 }"
                        >
                            {{ form.description.length }} / 2000
                        </div>
                    </div>
                    <!-- Adresse d'intervention -->
                    <div class="clm-field">
                        <label>Adresse <span class="clm-req">*</span></label>
                        <div class="clm-addr-modes">
                            <label
                                class="clm-addr-mode-label"
                                :class="{ active: address_mode === 'manual' }"
                            >
                                <input
                                    type="radio"
                                    v-model="address_mode"
                                    value="manual"
                                />
                                ?? Saisir manuellement
                            </label>
                            <label
                                class="clm-addr-mode-label"
                                :class="{ active: address_mode === 'geo' }"
                            >
                                <input
                                    type="radio"
                                    v-model="address_mode"
                                    value="geo"
                                />
                                ?? G�olocalisation
                            </label>
                        </div>
                    </div>

                    <!-- Saisie manuelle -->
                    <div class="clm-field" v-if="address_mode === 'manual'">
                        <input
                            class="clm-input"
                            type="text"
                            v-model="form.address"
                            placeholder="Ex : Akpakpa, Cotonou"
                        />
                    </div>

                    <!-- G�olocalisation : carte -->
                    <div v-if="address_mode === 'geo'" class="clm-field">
                        <button
                            class="clm-btn-geo"
                            type="button"
                            @click="openMapModal"
                            v-if="!geoOk"
                        >
                            ??? Ouvrir la carte
                        </button>
                        <div class="clm-geo-ok" v-if="geoOk">
                            <span>??</span>
                            <span class="clm-geo-ok-addr">{{
                                form.address
                            }}</span>
                            <button class="clm-geo-reset" @click="resetGeo">
                                ?
                            </button>
                        </div>
                    </div>
                    <!-- Images optionnelles -->
                    <div class="clm-field">
                        <label>Photos (optionnel, max 5 � 10 Mo)</label>
                        <div class="clm-img-upload-row">
                            <label
                                class="clm-img-add-btn"
                                title="Ajouter des photos"
                            >
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    style="display: none"
                                    ref="imgInput"
                                    @change="onImagesSelect"
                                />
                                <span>+ Photo</span>
                            </label>
                            <div
                                class="clm-img-thumb"
                                v-for="(img, i) in imagesPreviews"
                                :key="i"
                            >
                                <img :src="img.url" :alt="img.name" />
                                <button
                                    class="clm-img-remove"
                                    @click.prevent="removeImage(i)"
                                >
                                    ?
                                </button>
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
                        class="clm-btn clm-btn-green"
                        @click="showPublishConfirm = true"
                        :disabled="submitting"
                    >
                        Publier la mission ?
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- MODAL CONFIRMATION PUBLICATION -------------- -->
        <div class="clm-modal-overlay" v-if="showPublishConfirm" @click.self="showPublishConfirm = false">
            <div class="clm-modal" style="max-width:480px;width:95vw">
                <div class="clm-modal-header">
                    <h3>?? Informations importantes</h3>
                    <button class="clm-modal-close" @click="showPublishConfirm = false">&#215;</button>
                </div>
                <div class="clm-modal-body" style="text-align:center;padding:28px 24px">
                    <div style="font-size:2.5rem;margin-bottom:16px">??</div>
                    <p style="font-size:1rem;font-weight:700;color:#1c1412;margin-bottom:12px">
                        Frais de diagnostic
                    </p>
                    <p style="font-size:0.9rem;color:#4b5563;line-height:1.6;margin-bottom:20px">
                        Une fois le prestataire arriv� chez vous, des <strong>frais de diagnostic de
                        {{ diagnosticFee.toLocaleString('fr-FR') }} FCFA</strong> vous seront factur�s
                        avant qu'il commence l'intervention.
                    </p>
                    <p style="font-size:0.82rem;color:#9ca3af;margin-bottom:24px">
                        Ces frais couvrent le d�placement et l'�valuation initiale de la mission.
                    </p>
                    <div style="display:flex;gap:12px;justify-content:center">
                        <button class="clm-btn clm-btn-ghost" @click="showPublishConfirm = false">Annuler</button>
                        <button class="clm-btn clm-btn-green" @click="showPublishConfirm = false; submitMission()" :disabled="submitting">
                            <div class="clm-spinner" v-if="submitting"></div>
                            <span v-else>J'ai compris, publier ?</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- -------------- MODAL CARTE LEAFLET -------------- -->
        <div
            class="clm-modal-overlay"
            v-if="showMapModal"
            @click.self="closeMapModal"
        >
            <div class="clm-modal" style="max-width: 660px; width: 95vw">
                <div class="clm-modal-header">
                    <h3>??? Choisir la position</h3>
                    <button class="clm-modal-close" @click="closeMapModal">
                        &#215;
                    </button>
                </div>
                <div class="clm-modal-body" style="padding-bottom: 0">
                    <div class="clm-map-search">
                        <input
                            class="clm-input clm-map-search-input"
                            type="text"
                            v-model="mapSearch"
                            placeholder="Rechercher une adresse, un quartier�"
                            @keydown.enter.prevent="searchOnMap"
                        />
                        <button
                            class="clm-btn clm-btn-orange clm-map-search-btn"
                            type="button"
                            @click="searchOnMap"
                        >
                            ?? Rechercher
                        </button>
                    </div>
                    <p
                        style="
                            font-size: 12.5px;
                            color: var(--grm);
                            margin-bottom: 8px;
                        "
                    >
                        Cliquez sur la carte ou faites glisser le marqueur pour
                        ajuster la position.
                    </p>
                    <div
                        id="clm-leaflet-map"
                        style="
                            height: 440px;
                            width: 100%;
                            border-radius: 10px;
                            overflow: hidden;
                        "
                    ></div>
                    <div
                        v-if="mapAddress"
                        style="
                            margin-top: 10px;
                            font-size: 13px;
                            color: var(--dk);
                        "
                    >
                        ?? <strong>{{ mapAddress }}</strong>
                    </div>
                    <div
                        v-if="geoLoading"
                        style="
                            margin-top: 8px;
                            font-size: 13px;
                            color: var(--grm);
                        "
                    >
                        ? Localisation en cours�
                    </div>
                </div>
                <div class="clm-modal-footer">
                    <button
                        class="clm-btn clm-btn-ghost"
                        @click="closeMapModal"
                    >
                        Annuler
                    </button>
                    <button
                        class="clm-btn clm-btn-green"
                        @click="validatePosition"
                        :disabled="!mapLat"
                    >
                        ? Valider cette position
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- MODAL SIGNALEMENT -------------- -->
        <div
            class="clm-modal-overlay"
            v-if="signalModal.visible"
            @click.self="signalModal.visible = false"
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <h3>?? Signaler un probl�me</h3>
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
                        D�crivez le probl�me rencontr�. Un agent Mesotravo vous
                        contactera sous 24h.
                    </p>
                    <label class="clm-form-label"
                        >Motif <span class="clm-req">*</span></label
                    >
                    <textarea
                        class="clm-input clm-textarea"
                        v-model="signalModal.reason"
                        rows="3"
                        placeholder="Ex : Travaux non termin�s, mat�riaux d�fectueux�"
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
                        <span v-else>?? Signaler</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- MODAL REFUS DEVIS -------------- -->
        <div
            class="clm-modal-overlay"
            v-if="rejectQuoteModal.visible"
            @click.self="rejectQuoteModal.visible = false"
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <h3>? Refuser le devis</h3>
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
                        Il pourra le r�viser et vous le soumettre � nouveau.
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
                            >Pr�cisez votre motif
                            <span class="clm-req">*</span></label
                        >
                        <textarea
                            class="clm-input clm-textarea"
                            v-model="rejectQuoteModal.customReason"
                            rows="3"
                            placeholder="Ex : Le prix des pi�ces semble trop �lev�"
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
                        <span v-else>? Confirmer le refus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- -------------- CHAT MODAL -------------- -->
        <mission-chat-modal
            v-if="chatMissionId"
            :mission-id="chatMissionId"
            :current-user-id="user.id ?? 0"
            :routes="routes"
            @close="onChatClose"
            @unread="onChatUnread($event)"
        />

        <!-- Lightbox images mission -->
        <div
            class="clm-lightbox"
            v-if="missionLightbox"
            @click="missionLightbox = null"
        >
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

        <!-- -------------- MODAL PAIEMENT MOMO -------------- -->
        <div
            class="clm-modal-overlay"
            v-if="momoModal.visible"
            @click.self="
                momoModal.step === 'form' && (momoModal.visible = false)
            "
        >
            <div class="clm-modal">
                <div class="clm-modal-header">
                    <div>
                        <h3>?? Paiement Mobile Money</h3>
                        <div class="clm-modal-sub">
                            Mission #{{ momoModal.mission?.id }} �
                            {{ formatPrice(momoModal.mission?.total_amount) }}
                        </div>
                    </div>
                    <button
                        class="clm-modal-close"
                        @click="momoModal.visible = false"
                        v-if="momoModal.step !== 'polling'"
                    >
                        &#215;
                    </button>
                </div>

                <!-- -- Step: form -- -->
                <template v-if="momoModal.step === 'form'">
                    <div class="clm-modal-body">
                        <div class="clm-momo-step-hint">
                            ?? Choisissez d'abord votre r�seau, puis entrez votre num�ro de t�l�phone.
                        </div>
                        <div class="clm-field">
                            <label class="clm-form-label">
                                1. R�seau Mobile Money <span class="clm-req">*</span>
                            </label>
                            <select class="clm-input" v-model="momoModal.network">
                                <option value="" disabled>-- S�lectionnez votre r�seau --</option>
                                <option v-for="net in momoNetworks" :key="net.value" :value="net.value">
                                    {{ net.label }}
                                </option>
                            </select>
                        </div>
                        <div class="clm-field">
                            <label class="clm-form-label">
                                2. Num�ro de t�l�phone <span class="clm-req">*</span>
                            </label>
                            <input
                                class="clm-input"
                                type="tel"
                                v-model="momoModal.phone"
                                placeholder="Ex : 97 12 34 56"
                                maxlength="20"
                                :disabled="!momoModal.network"
                            />
                            <div class="clm-momo-hint" v-if="momoModal.network">
                                Entrez le num�ro associ� � votre compte {{ momoNetworks.find(n => n.value === momoModal.network)?.label }}.
                            </div>
                            <div class="clm-momo-hint" v-else>
                                S�lectionnez d'abord votre r�seau ci-dessus.
                            </div>
                        </div>
                        <div
                            class="clm-momo-recap"
                            v-if="momoModal.network && momoModal.phone"
                        >
                            <div class="clm-momo-recap-row">
                                <span>R�seau</span>
                                <strong>{{
                                    momoNetworks.find(
                                        (n) => n.value === momoModal.network
                                    )?.label
                                }}</strong>
                            </div>
                            <div class="clm-momo-recap-row">
                                <span>Num�ro</span>
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
                            ?? Vous recevrez une demande de paiement sur votre
                            t�l�phone. Confirmez avec votre code PIN.
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
                            <div
                                class="clm-spinner"
                                v-if="momoModal.loading"
                            ></div>
                            <span v-else>? Confirmer le paiement</span>
                        </button>
                    </div>
                </template>

                <!-- -- Step: polling -- -->
                <template v-if="momoModal.step === 'polling'">
                    <div class="clm-modal-body clm-momo-waiting">
                        <div class="clm-momo-pulse">??</div>
                        <div class="clm-momo-wait-title">
                            Confirmez sur votre t�l�phone
                        </div>
                        <p
                            style="
                                color: #6b7280;
                                font-size: 13.5px;
                                margin-bottom: 12px;
                            "
                        >
                            Ouvrez le menu Mobile Money et saisissez votre code
                            PIN pour valider le paiement de
                            <strong>{{
                                formatPrice(momoModal.mission?.total_amount)
                            }}</strong
                            >.
                        </p>
                        <div class="clm-momo-countdown">
                            ? Expiration dans {{ momoModal.pollSecondsLeft }}s
                        </div>

                        <div class="clm-momo-ussd-hint">
                            Vous ne voyez pas la requ�te sur votre t�l�phone
                            ?<br />
                            Tapez ce code
                            <strong>*880*2*1*0190003626#</strong> sur votre
                            t�l�phone pour payer, puis cliquez sur le bouton
                            ci-dessous.
                        </div>
                        <button
                            class="clm-btn clm-btn-orange"
                            style="margin-top: 12px; width: 100%"
                            @click="momoModal.step = 'manual_pending'"
                        >
                            ? Paiement effectu�
                        </button>
                    </div>
                </template>

                <!-- -- Step: manual_pending -- -->
                <template v-if="momoModal.step === 'manual_pending'">
                    <div class="clm-modal-body clm-momo-success">
                        <div class="clm-momo-success-icon">??</div>
                        <div class="clm-momo-success-title">
                            Merci pour votre commande !
                        </div>
                        <p
                            style="
                                color: #6b7280;
                                font-size: 13.5px;
                                margin-bottom: 16px;
                            "
                        >
                            Nous allons proc�der � la v�rification de votre
                            paiement.<br />
                            Vous serez notifi� d�s la confirmation.
                        </p>
                    </div>
                    <div class="clm-modal-footer">
                        <button
                            class="clm-btn clm-btn-ghost"
                            @click="momoModal.visible = false"
                        >
                            Fermer
                        </button>
                    </div>
                </template>

                <!-- -- Step: success -- -->
                <template v-if="momoModal.step === 'success'">
                    <div class="clm-modal-body clm-momo-success">
                        <div class="clm-momo-success-icon">??</div>
                        <div class="clm-momo-success-title">
                            Paiement confirm� !
                        </div>
                        <p
                            style="
                                color: #6b7280;
                                font-size: 13.5px;
                                margin-bottom: 16px;
                            "
                        >
                            La mission est cl�tur�e avec succ�s. Merci pour
                            votre confiance !
                        </p>
                        <a
                            v-if="momoModal.receiptUrl"
                            :href="momoModal.receiptUrl"
                            target="_blank"
                            class="clm-btn clm-btn-orange"
                            style="text-decoration: none; display: inline-block"
                        >
                            ?? T�l�charger le re�u
                        </a>
                    </div>
                    <div class="clm-modal-footer">
                        <button
                            class="clm-btn clm-btn-ghost"
                            @click="momoModal.visible = false"
                        >
                            Fermer
                        </button>
                    </div>
                </template>

                <!-- -- Step: failed -- -->
                <template v-if="momoModal.step === 'failed'">
                    <div class="clm-modal-body clm-momo-error">
                        <div class="clm-momo-error-icon">?</div>
                        <div class="clm-momo-error-title">Paiement �chou�</div>
                        <p
                            style="
                                color: #6b7280;
                                font-size: 13.5px;
                                margin-bottom: 16px;
                            "
                        >
                            {{
                                momoModal.errorMessage ||
                                "Le paiement a �t� refus� ou a expir�."
                            }}
                        </p>
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
                            @click="momoModal.step = 'form'"
                        >
                            ?? R�essayer
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- -------------- POPUP POST-MISSION CLIENT -------------- -->
    <div
        class="clm-modal-overlay"
        v-if="completionPopup.visible"
        @click.self="closeCompletionPopup"
    >
        <div class="clm-modal clm-modal-completion">
            <div class="clm-completion-header">
                <div class="clm-completion-icon">??</div>
                <h3 class="clm-completion-title">Merci pour votre confiance !</h3>
                <p class="clm-completion-sub">
                    Votre intervention a bien �t� confirm�e. Aidez-nous � am�liorer Mesotravo.
                </p>
            </div>

            <!-- Section 1 : �valuer le prestataire -->
            <div class="clm-completion-section">
                <div class="clm-completion-section-title">
                    <span>?</span> �valuez votre prestataire
                </div>
                <div v-if="!completionPopup.reviewDone">
                    <div class="clm-stars-wrap" style="justify-content:center; margin-bottom:10px">
                        <button
                            class="clm-star"
                            v-for="n in 5"
                            :key="n"
                            :class="{ active: n <= completionPopup.rating }"
                            @click="completionPopup.rating = n"
                            type="button"
                        >?</button>
                        <span class="clm-star-label" v-if="completionPopup.rating">
                            {{ starLabel(completionPopup.rating) }}
                        </span>
                    </div>
                    <textarea
                        class="clm-review-textarea"
                        v-model="completionPopup.comment"
                        placeholder="Un commentaire sur la prestation ? (optionnel)"
                        rows="2"
                        maxlength="500"
                    ></textarea>
                    <button
                        class="clm-btn clm-btn-green clm-btn-full"
                        style="margin-top:8px"
                        @click="submitReviewFromPopup"
                        :disabled="!completionPopup.rating || completionPopup.loadingReview"
                    >
                        <div class="clm-spinner" v-if="completionPopup.loadingReview"></div>
                        <span v-else>? Envoyer mon avis</span>
                    </button>
                </div>
                <div class="clm-completion-done-badge" v-else>
                    ? Avis envoy� � merci !
                </div>
            </div>

            <!-- Section 2 : Recommander -->
            <div class="clm-completion-section">
                <div class="clm-completion-section-title">
                    <span>??</span> Recommandez Mesotravo
                </div>
                <p class="clm-completion-section-body">
                    Parlez de Mesotravo � vos proches, coll�gues et voisins.
                    Ensemble, construisons le r�seau de confiance des artisans au B�nin ! ????
                </p>
                <div class="clm-share-btns">
                    <a
                        href="https://wa.me/?text=Je%20recommande%20Mesotravo%20pour%20trouver%20des%20prestataires%20certifi%C3%A9s%20%3A%20mesotravo.bj"
                        target="_blank"
                        class="clm-share-btn clm-share-wa"
                    >
                        <span>??</span> WhatsApp
                    </a>
                    <a
                        href="https://www.facebook.com/sharer/sharer.php?u=mesotravo.bj"
                        target="_blank"
                        class="clm-share-btn clm-share-fb"
                    >
                        <span>??</span> Facebook
                    </a>
                </div>
            </div>

            <!-- Section 3 : Suggestions -->
            <div class="clm-completion-section">
                <div class="clm-completion-section-title">
                    <span>??</span> Une suggestion pour nous ?
                </div>
                <div v-if="!completionPopup.suggestionSent">
                    <textarea
                        class="clm-review-textarea"
                        v-model="completionPopup.suggestionText"
                        placeholder="Comment pouvons-nous am�liorer votre exp�rience ?"
                        rows="3"
                        maxlength="2000"
                    ></textarea>
                    <button
                        class="clm-btn clm-btn-orange clm-btn-full"
                        style="margin-top:8px"
                        @click="sendSuggestion"
                        :disabled="!completionPopup.suggestionText.trim() || completionPopup.loadingSuggestion"
                    >
                        <div class="clm-spinner" v-if="completionPopup.loadingSuggestion"></div>
                        <span v-else>?? Envoyer ma suggestion</span>
                    </button>
                </div>
                <div class="clm-completion-done-badge" v-else>
                    ? Suggestion envoy�e � merci !
                </div>
            </div>

            <div class="clm-modal-footer" style="border-top: 1.5px solid var(--grl); margin-top: 4px;">
                <button class="clm-btn clm-btn-ghost" @click="closeCompletionPopup">
                    Fermer
                </button>
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
        diagnosticFee: { type: Number, default: 5000 },
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
            address_mode: "manual",
            showMapModal: false,
            geoLoading: false,
            geoOk: false,
            mapAddress: "",
            mapSearch: "",
            mapLat: null,
            mapLng: null,
            leafletMap: null,
            leafletMarker: null,
            form: {
                schedule_type: "now",
                reservation_day: "",
                reservation_time: "",
                location_type: "residential",
                service: "",
                description: "",
                address: "",
                latitude: null,
                longitude: null,
            },

            showPublishConfirm: false,

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
                polling: false,
                step: "form",
                pollSecondsLeft: 120,
                receiptUrl: null,
                errorMessage: "",
                _pollInterval: null,
            },
            momoNetworks: [
                {
                    value: "mtn", label: "MTN B�nin",
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 36" width="72" height="36"><rect width="72" height="36" rx="6" fill="#FFCC00"/><text x="36" y="24" font-family="Arial Black,Arial" font-weight="900" font-size="16" fill="#000" text-anchor="middle" letter-spacing="1">MTN</text></svg>`,
                },
                {
                    value: "moov", label: "Moov B�nin",
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 36" width="72" height="36"><rect width="72" height="36" rx="6" fill="#005BAA"/><text x="36" y="22" font-family="Arial,sans-serif" font-weight="700" font-size="13" fill="#fff" text-anchor="middle">moov</text><text x="36" y="33" font-family="Arial,sans-serif" font-size="8" fill="#7fc4f5" text-anchor="middle">africa</text></svg>`,
                },
                {
                    value: "celtiis", label: "Celtiis",
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 36" width="72" height="36"><rect width="72" height="36" rx="6" fill="#E8500A"/><text x="36" y="24" font-family="Arial,sans-serif" font-weight="700" font-size="13" fill="#fff" text-anchor="middle">Celtiis</text></svg>`,
                },
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
                { value: "price_too_high", label: "?? Prix trop �lev�" },
                {
                    value: "wrong_parts",
                    label: "?? Pi�ces incorrectes ou inutiles",
                },
                {
                    value: "labor_too_high",
                    label: "??? Main d'�uvre trop ch�re",
                },
                {
                    value: "missing_items",
                    label: "?? Des �l�ments manquent au devis",
                },
                { value: "other", label: "?? Autre motif" },
            ],

            toasts: [],
            toastId: 0,
            sidebarOpen: false,

            // Pagination
            currentPage: 1,
            perPage: 10,

            services: [
                { value: "plomberie", label: "Plomberie", icon: "??" },
                { value: "electricite", label: "�lectricit�", icon: "?" },
                { value: "menuiserie", label: "Menuiserie", icon: "??" },
                { value: "ferronnerie", label: "Ferronnerie", icon: "??" },
                { value: "frigoriste", label: "Frigoriste", icon: "??" },
                { value: "mecanique", label: "M�canique auto", icon: "??" },
                { value: "nettoyage", label: "Nettoyage", icon: "??" },
                { value: "peinture", label: "Peinture", icon: "??" },
                { value: "maintenance", label: "Maintenance", icon: "???" },
                { value: "autre", label: "Autre", icon: "?" },
            ],

            tabs: [
                { key: "all", label: "Toutes" },
                { key: "active", label: "?? En cours" },
                { key: "pending", label: "? En attente" },
                { key: "closed", label: "? Termin�es" },
                { key: "cancelled", label: "? Annul�es" },
            ],

            workflowSteps: [
                { step: 1, label: "En attente" },
                { step: 2, label: "Propos�e" },
                { step: 3, label: "Accept�e" },
                { step: 4, label: "Contact" },
                { step: 5, label: "En route" },
                { step: 6, label: "Suivi" },
                { step: 7, label: "Sur place" },
                { step: 8, label: "Devis soumis" },
                { step: 9, label: "Commande" },
                { step: 10, label: "Att. confirmation" },
                { step: 11, label: "Termin�e" },
                { step: 12, label: "Cl�tur�e" },
            ],

            actionableStatuses: [
                "quote_submitted",
                "awaiting_confirm",
                "completed",
            ],

            completionPopup: {
                visible: false,
                mission: null,
                rating: 0,
                comment: "",
                suggestionText: "",
                loadingReview: false,
                loadingSuggestion: false,
                suggestionSent: false,
                reviewDone: false,
            },
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
        isActionable() {
            const m = this.activeMission;
            if (!m) return false;
            if (m.status === "quote_submitted") return true;
            return ["awaiting_confirm", "completed"].includes(m.status);
        },
        isCompany() {
            return this.clientProfile?.account_type === "company";
        },
        todayDate() {
            return new Date().toISOString().split("T")[0];
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
                    this.activeStatuses.includes(m.status)
                );
            else if (this.activeTab === "pending")
                list = list.filter((m) => m.status === "pending");
            else if (this.activeTab === "closed")
                list = list.filter((m) =>
                    ["completed", "closed"].includes(m.status)
                );
            else if (this.activeTab === "cancelled")
                list = list.filter((m) => m.status === "cancelled");
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                list = list.filter(
                    (m) =>
                        m.service?.toLowerCase().includes(q) ||
                        m.address?.toLowerCase().includes(q) ||
                        m.contractor?.name?.toLowerCase().includes(q)
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
        // -- Missions ---------------------------------------------
        async fetchMissions() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(this.routes.missions_index, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.missions = Array.isArray(data) ? data : data.data ?? [];
            } catch {
                this.error = "Impossible de charger les missions.";
            } finally {
                this.loading = false;
            }
        },

        openMission(m) {
            this.activeMission = { ...m };
            this.showScrollBtn = false;
            // Apr�s rendu, v�rifier si le panel est scrollable
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
                    'meta[name="csrf-token"]'
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
                    this.routes.missions_status
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
                this.showToast("Erreur r�seau.", "error");
                return null;
            } finally {
                this.actionLoading = false;
            }
        },

        async confirmCompletion(m) {
            const updated = await this.updateMissionStatus(m, "completed");
            if (!updated) return;
            this.reviewModal = { rating: 0, comment: "", loading: false };
            this.completionPopup = {
                visible: true,
                mission: updated,
                rating: 0,
                comment: "",
                suggestionText: "",
                loadingReview: false,
                loadingSuggestion: false,
                suggestionSent: false,
                reviewDone: false,
            };
        },

        async submitReviewFromPopup() {
            if (!this.completionPopup.rating) return;
            this.completionPopup.loadingReview = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
                const reviewUrl = (this.routes.reviews_store ?? "/client/missions") + "/" + this.completionPopup.mission.id + "/review";
                await fetch(reviewUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, Accept: "application/json" },
                    body: JSON.stringify({ rating: this.completionPopup.rating, comment: this.completionPopup.comment.trim() }),
                });
                this.reviewModal.rating = this.completionPopup.rating;
                this.reviewModal.comment = this.completionPopup.comment;
                this.completionPopup.reviewDone = true;
                this.showToast("Avis envoy�, merci !", "success");
            } catch {
                this.showToast("Erreur lors de l'envoi de l'avis.", "error");
            } finally {
                this.completionPopup.loadingReview = false;
            }
        },

        async sendSuggestion() {
            const msg = this.completionPopup.suggestionText.trim();
            if (!msg) return;
            this.completionPopup.loadingSuggestion = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
                await fetch(this.routes.suggestions ?? "/client/suggestions", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, Accept: "application/json" },
                    body: JSON.stringify({ message: msg }),
                });
                this.completionPopup.suggestionSent = true;
                this.showToast("Suggestion envoy�e, merci !", "success");
            } catch {
                this.showToast("Erreur lors de l'envoi de la suggestion.", "error");
            } finally {
                this.completionPopup.loadingSuggestion = false;
            }
        },

        closeCompletionPopup() {
            this.completionPopup.visible = false;
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
                    "error"
                );
                this.reviewModal.loading = false;
            }
        },

        async closeMissionOnly(m) {
            this.reviewModal.loading = true;
            await this.updateMissionStatus(m, "closed");
            this.reviewModal = { rating: 0, comment: "", loading: false };
            this.showToast(
                "Mission cl�tur�e. Merci de votre confiance !",
                "success"
            );
        },

        openMomoModal(mission) {
            if (this.momoModal._pollInterval) {
                clearInterval(this.momoModal._pollInterval);
            }
            this.momoModal = {
                visible: true,
                mission,
                network: "",
                phone: "",
                loading: false,
                polling: false,
                step: "form",
                pollSecondsLeft: 120,
                receiptUrl: null,
                errorMessage: "",
                _pollInterval: null,
            };
        },

        async submitMomo() {
            if (!this.momoModal.network || !this.momoModal.phone.trim()) return;
            this.momoModal.loading = true;
            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;
            const url = this.routes.payment_initiate.replace(
                "{id}",
                this.momoModal.mission.id
            );
            try {
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        phone: this.momoModal.phone.trim(),
                        network: this.momoModal.network,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.momoModal.step = "failed";
                    this.momoModal.errorMessage =
                        data.message || "Erreur lors de l'initiation.";
                    return;
                }
                if (data.status === "SUCCESSFUL") {
                    this.onMomoSuccess(data);
                } else {
                    this.momoModal.step = "polling";
                    this.momoModal.pollSecondsLeft = 120;
                    this.startMomoPolling();
                }
            } catch {
                this.momoModal.step = "failed";
                this.momoModal.errorMessage =
                    "Erreur r�seau. Veuillez r�essayer.";
            } finally {
                this.momoModal.loading = false;
                this.momoModal.polling = this.momoModal.step === "polling";
            }
        },

        startMomoPolling() {
            const interval = setInterval(async () => {
                if (!this.momoModal.polling) {
                    clearInterval(interval);
                    return;
                }
                this.momoModal.pollSecondsLeft = Math.max(
                    0,
                    this.momoModal.pollSecondsLeft - 3
                );
                if (this.momoModal.pollSecondsLeft <= 0) {
                    clearInterval(interval);
                    this.momoModal.polling = false;
                    this.momoModal.step = "failed";
                    this.momoModal.errorMessage = "D�lai d�pass�. R�essayez.";
                    return;
                }
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                const url = this.routes.payment_status.replace(
                    "{id}",
                    this.momoModal.mission.id
                );
                try {
                    const res = await fetch(url, {
                        headers: {
                            Accept: "application/json",
                            "X-CSRF-TOKEN": csrf,
                        },
                    });
                    const data = await res.json();
                    if (data.status === "SUCCESSFUL") {
                        clearInterval(interval);
                        this.momoModal.polling = false;
                        this.onMomoSuccess(data);
                    } else if (data.status === "FAILED") {
                        clearInterval(interval);
                        this.momoModal.polling = false;
                        this.momoModal.step = "failed";
                        this.momoModal.errorMessage =
                            data.message || "Paiement refus� ou expir�.";
                    }
                } catch {
                    /* r�seau � continuer */
                }
            }, 3000);
            this.momoModal._pollInterval = interval;
        },

        onMomoSuccess(data) {
            this.momoModal.step = "success";
            this.momoModal.receiptUrl = data.receipt_url ?? null;
            if (data.mission) {
                const idx = this.missions.findIndex(
                    (m) => m.id === data.mission.id
                );
                if (idx !== -1)
                    this.$set(this.missions, idx, {
                        ...this.missions[idx],
                        ...data.mission,
                    });
                if (this.activeMission?.id === data.mission.id) {
                    this.activeMission = {
                        ...this.activeMission,
                        ...data.mission,
                    };
                }
            }
            this.showToast(
                "?? Paiement confirm� ! Mission cl�tur�e.",
                "success"
            );
        },

        starLabel(n) {
            return (
                [
                    "",
                    "?? Mauvais",
                    "?? Passable",
                    "?? Correct",
                    "?? Bien",
                    "?? Excellent !",
                ][n] ?? ""
            );
        },

        async approveQuote(m) {
            await this.updateMissionStatus(m, "order_placed");
            this.showToast(
                "Devis approuv� ! L'intervention va d�marrer.",
                "success"
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
                    (r) => r.value === this.rejectQuoteModal.reason
                )?.label ?? "";
            const reason =
                this.rejectQuoteModal.reason === "other"
                    ? this.rejectQuoteModal.customReason.trim()
                    : label;
            this.rejectQuoteModal.loading = true;
            // PATCH mission status ? quote_submitted (reste en quote_submitted) avec rejection_reason
            await this.updateMissionStatus(
                this.rejectQuoteModal.mission,
                "quote_submitted",
                { reported_issue: reason }
            );
            this.rejectQuoteModal.visible = false;
            this.showToast("Devis refus�. Le prestataire va le r�viser.", "");
            this.rejectQuoteModal.loading = false;
        },

        downloadQuotePdf(mission) {
            const quote = mission.quote;
            if (!quote) return;

            const typeLabels = {
                diagnostic: "Diagnostic",
                part: "Fourniture",
                labor: "Main d'�uvre",
                travel: "D�placement",
                other: "Autre",
            };
            const typeBg = {
                diagnostic: "#f97316",
                part: "#3b82f6",
                labor: "#10b981",
                travel: "#8b5cf6",
                other: "#6b7280",
            };

            const lines = (quote.items ?? [])
                .map((i) => {
                    const total =
                        parseFloat(i.quantity) * parseFloat(i.unit_price);
                    const tl = typeLabels[i.type] ?? i.type;
                    const bg = typeBg[i.type] ?? "#6b7280";
                    return `<tr>
                        <td><span style="background:${bg};color:#fff;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:700;margin-right:8px;">${tl}</span>${
                        i.description
                    }</td>
                        <td style="text-align:center;">${i.quantity}</td>
                        <td style="text-align:right;">${this.formatPrice(
                            i.unit_price
                        )}</td>
                        <td style="text-align:right;font-weight:700;">${this.formatPrice(
                            total
                        )}</td>
                    </tr>`;
                })
                .join("");

            const contractorName = mission.contractor?.name ?? "";
            const html = `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devis #${quote.id} � Mission #${mission.id}</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Arial,sans-serif;color:#1c1412;padding:32px 40px;max-width:800px;margin:0 auto}
  .print-btn{display:block;margin:0 auto 24px;padding:10px 28px;background:#f97316;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;letter-spacing:.3px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;padding-bottom:18px;border-bottom:2px solid #f97316}
  .brand{font-size:24px;font-weight:900;color:#f97316;letter-spacing:-.5px}
  .brand em{color:#1c1412;font-style:normal}
  .sub-brand{font-size:11px;color:#7c6a5a;margin-top:3px}
  .meta{font-size:12px;color:#7c6a5a;text-align:right;line-height:1.8}
  .meta strong{color:#1c1412;font-size:14px;display:block}
  .section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#7c6a5a;margin:20px 0 8px}
  .info-row{font-size:13px;color:#7c6a5a;margin-bottom:4px}
  .info-row strong{color:#1c1412}
  .diag-box{background:#fff7ed;border-left:3px solid #f97316;padding:10px 14px;font-size:13px;color:#7c6a5a;margin:16px 0;border-radius:0 6px 6px 0}
  .diag-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#f97316;margin-bottom:5px}
  table{width:100%;border-collapse:collapse;margin:10px 0}
  thead th{background:#f8f4f0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#7c6a5a;padding:10px 12px;text-align:left}
  thead th:nth-child(2){text-align:center}
  thead th:nth-child(3),thead th:nth-child(4){text-align:right}
  tbody td{padding:9px 12px;border-bottom:1px solid #f0e9e4;font-size:13px;vertical-align:middle}
  tbody td:nth-child(2){text-align:center}
  tbody td:nth-child(3),tbody td:nth-child(4){text-align:right}
  tfoot td{padding:14px 12px;font-weight:700;font-size:15px;border-top:2px solid #f97316}
  tfoot td:last-child{text-align:right;color:#f97316}
  .note{margin-top:28px;padding:12px 16px;background:#f8f4f0;border-radius:6px;font-size:11px;color:#7c6a5a;line-height:1.6}
  .footer{margin-top:24px;text-align:center;font-size:10px;color:#b0a09a;padding-top:14px;border-top:1px solid #e8ddd4}
  @media print{.print-btn{display:none!important}}
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">? Enregistrer en PDF</button>
<div class="header">
  <div>
    <div class="brand">Meso<em>Travo</em></div>
    <div class="sub-brand">Plateforme de mise en relation</div>
  </div>
  <div class="meta">
    <strong>Devis n�${quote.id}${
                quote.version > 1 ? " � R�vision v" + quote.version : ""
            }</strong>
    Mission #${mission.id} � ${mission.service}<br>
    �mis le ${new Date().toLocaleDateString("fr-FR")}
  </div>
</div>

<div class="section-label">D�tails de l'intervention</div>
<div class="info-row">?? Adresse : <strong>${mission.address}</strong></div>
${
    contractorName
        ? `<div class="info-row">?? Prestataire : <strong>${contractorName}</strong></div>`
        : ""
}

${
    quote.diagnosis
        ? `<div class="diag-box"><div class="diag-label">?? Diagnostic</div>${quote.diagnosis}</div>`
        : ""
}

<div class="section-label">Lignes du devis</div>
<table>
  <thead><tr>
    <th>D�signation</th><th>Qt�</th><th>Prix unitaire</th><th>Total</th>
  </tr></thead>
  <tbody>${lines}</tbody>
  <tfoot><tr>
    <td colspan="3">Total TTC</td>
    <td>${this.formatPrice(quote.amount_incl_tax)}</td>
  </tr></tfoot>
</table>

<div class="note">?? Ce devis est soumis � approbation via la plateforme Mesotravo. Aucun paiement hors-plateforme n'est autoris� ni conseill�.</div>
<div class="footer">Mesotravo.com � Plateforme de mise en relation artisans &amp; particuliers</div>
</body></html>`;

            const win = window.open("", "_blank", "width=860,height=720");
            if (win) {
                win.document.write(html);
                win.document.close();
            }
        },

        quoteItemTypeLabel(type) {
            return (
                {
                    diagnostic: "Diagnostic",
                    part: "Fourniture",
                    labor: "M.O.",
                    travel: "D�pl.",
                    other: "Autre",
                }[type] ?? type
            );
        },

        // -- Signalement -------------------------------------------
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
                }
            );
            this.signalModal.visible = false;
            this.showToast(
                "Probl�me signal�. Un agent va vous contacter.",
                "success"
            );
            this.signalModal.loading = false;
        },

        // -- Nouvelle mission --------------------------------------
        openNewMission() {
            this.form = {
                schedule_type: "now",
                reservation_day: "",
                reservation_time: "",
                location_type: "residential",
                service: "",
                description: "",
                address: this.clientProfile?.address ?? "",
                latitude: null,
                longitude: null,
            };
            this.formError = "";
            this.geoOk = false;
            this.address_mode = "manual";
            this.mapAddress = "";
            this.mapLat = null;
            this.mapLng = null;
            this.imageFiles = [];
            this.imagesPreviews = [];
            this.showNewMission = true;
        },
        openMapModal() {
            this.mapAddress = "";
            this.mapSearch = "";
            this.mapLat = null;
            this.mapLng = null;
            this.showMapModal = true;
        },

        closeMapModal() {
            this.showMapModal = false;
            this.destroyMap();
        },

        initLeafletMap() {
            const L = window.L;
            if (!L) return;
            const el = document.getElementById("clm-leaflet-map");
            if (!el) return;
            if (this.leafletMap) {
                this.leafletMap.remove();
                this.leafletMap = null;
            }
            const defaultCenter = [6.3654, 2.4183]; // Cotonou
            const map = L.map(el).setView(defaultCenter, 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution:
                    '� <a href="https://www.openstreetmap.org">OpenStreetMap</a>',
            }).addTo(map);
            this.leafletMap = map;
            map.on("click", (e) => {
                this.placeMapMarker(e.latlng.lat, e.latlng.lng);
            });
            if (navigator.geolocation) {
                this.geoLoading = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.geoLoading = false;
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.setView([lat, lng], 16);
                        this.placeMapMarker(lat, lng);
                    },
                    () => {
                        this.geoLoading = false;
                    },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            }
        },

        destroyMap() {
            if (this.leafletMap) {
                this.leafletMap.remove();
                this.leafletMap = null;
            }
            this.leafletMarker = null;
        },

        async placeMapMarker(lat, lng) {
            const L = window.L;
            if (!L || !this.leafletMap) return;
            this.mapLat = lat;
            this.mapLng = lng;
            if (this.leafletMarker) {
                this.leafletMarker.setLatLng([lat, lng]);
            } else {
                this.leafletMarker = L.marker([lat, lng], {
                    draggable: true,
                }).addTo(this.leafletMap);
                this.leafletMarker.on("dragend", (e) => {
                    const pos = e.target.getLatLng();
                    this.placeMapMarker(pos.lat, pos.lng);
                });
            }
            try {
                const res = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=fr`
                );
                const data = await res.json();
                const a = data.address ?? {};
                const parts = [
                    a.road ?? a.pedestrian ?? a.path ?? "",
                    a.neighbourhood ??
                        a.suburb ??
                        a.quarter ??
                        a.hamlet ??
                        a.village ??
                        "",
                    a.city ?? a.town ?? a.municipality ?? a.county ?? "",
                    a.country ?? "",
                ].filter(Boolean);
                this.mapAddress =
                    parts.length >= 2
                        ? parts.join(", ")
                        : data.display_name ??
                          `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            } catch {
                this.mapAddress = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }
        },

        validatePosition() {
            if (!this.mapLat) return;
            this.form.latitude = this.mapLat;
            this.form.longitude = this.mapLng;
            this.form.address =
                this.mapAddress ||
                `${this.mapLat.toFixed(5)}, ${this.mapLng.toFixed(5)}`;
            this.geoOk = true;
            this.closeMapModal();
        },

        resetGeo() {
            this.form.latitude = null;
            this.form.longitude = null;
            this.form.address = "";
            this.geoOk = false;
            this.mapAddress = "";
            this.mapLat = null;
            this.mapLng = null;
            this.openMapModal();
        },

        async searchOnMap() {
            if (!this.mapSearch.trim()) return;
            try {
                const res = await fetch(
                    `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(
                        this.mapSearch
                    )}&format=json&limit=1&accept-language=fr`
                );
                const data = await res.json();
                if (!data.length) {
                    this.showToast(
                        "Aucun r�sultat pour cette adresse.",
                        "error"
                    );
                    return;
                }
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                if (this.leafletMap) this.leafletMap.setView([lat, lng], 16);
                this.placeMapMarker(lat, lng);
            } catch {
                this.showToast("Erreur lors de la recherche.", "error");
            }
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
            if (this.form.schedule_type === "later") {
                if (!this.form.reservation_day) {
                    this.formError = "Veuillez choisir une date.";
                    return;
                }
                if (!this.form.reservation_time) {
                    this.formError = "Veuillez choisir une heure.";
                    return;
                }
            }
            if (!this.form.service) {
                this.formError = "S�lectionnez un type de prestation.";
                return;
            }
            if (this.form.description.trim().length < 20) {
                this.formError =
                    "Description trop courte (min. 20 caract�res).";
                return;
            }
            if (!this.form.address.trim()) {
                this.formError = "Adresse obligatoire.";
                return;
            }
            this.submitting = true;
            try {
                const csrf = document.querySelector(
                    'meta[name="csrf-token"]'
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
                        : data.message ?? "Erreur.";
                    return;
                }
                this.missions.unshift(data.mission);
                this.showNewMission = false;
                this.showToast(
                    "Mission publi�e ! Un prestataire sera attribu� rapidement.",
                    "success"
                );
            } catch {
                this.formError = "Erreur r�seau.";
            } finally {
                this.submitting = false;
            }
        },

        // -- Chat --------------------------------------------------
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

        // -- Notifications -----------------------------------------
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
                    'meta[name="csrf-token"]'
                )?.content;
                await fetch(
                    this.routes.notifications_read.replace("{id}", n.id),
                    {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrf,
                            Accept: "application/json",
                        },
                    }
                );
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            this.notifOpen = false;
        },
        async markAllRead() {
            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;
            await fetch(this.routes.notifications_all, {
                method: "PATCH",
                headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            });
            this.notifications.forEach((n) => (n.read = true));
            this.unreadCount = 0;
        },

        // -- Helpers affichage -------------------------------------
        countByTab(key) {
            if (key === "all") return this.missions.length;
            if (key === "active")
                return this.missions.filter((m) =>
                    this.activeStatuses.includes(m.status)
                ).length;
            if (key === "pending")
                return this.missions.filter((m) => m.status === "pending")
                    .length;
            if (key === "closed")
                return this.missions.filter((m) =>
                    ["completed", "closed"].includes(m.status)
                ).length;
            if (key === "cancelled")
                return this.missions.filter((m) => m.status === "cancelled")
                    .length;
            return 0;
        },
        statusInfoIcon(s) {
            const m = {
                pending: "?",
                assigned: "??",
                accepted: "?",
                on_the_way: "??",
                tracking: "??",
                in_progress: "??",
                order_placed: "??",
                completed: "??",
                closed: "??",
                cancelled: "?",
            };
            return m[s] ?? "??";
        },
        statusInfoTitle(s) {
            const m = {
                pending: "En attente d'attribution",
                assigned: "Prestataire attribu�",
                accepted: "Prestataire en route bient�t",
                on_the_way: "Prestataire en route",
                tracking: "Suivi GPS actif",
                in_progress: "Intervention en cours",
                order_placed: "Travaux d�marr�s",
                completed: "Termin�e � paiement requis",
                closed: "Mission cl�tur�e",
                cancelled: "Mission annul�e",
            };
            return m[s] ?? s;
        },
        statusInfoSub(s) {
            const m = {
                pending:
                    "Un prestataire certifi� sera attribu� automatiquement.",
                assigned: "Le prestataire va bient�t vous contacter.",
                accepted: "Il vous contactera avant de se d�placer.",
                on_the_way: "Votre prestataire arrive.",
                in_progress: "L'intervention est en cours chez vous.",
                order_placed: "Les travaux sont en cours.",
                completed: "Confirmez la fin pour d�clencher le paiement.",
                closed: "Merci de votre confiance !",
                cancelled: "Contactez le support pour plus d'informations.",
            };
            return m[s] ?? "";
        },
        labelOf(m) {
            const map = {
                pending: "En attente",
                assigned: "Assign�e",
                accepted: "Accept�e",
                contact_made: "Contact �tabli",
                on_the_way: "En route",
                tracking: "Suivi",
                in_progress: "Sur place",
                quote_submitted: "Devis soumis",
                order_placed: "Commande pass�e",
                awaiting_confirm: "Att. confirmation",
                completed: "Termin�e",
                closed: "Cl�tur�e",
                cancelled: "Annul�e",
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
                    plomberie: "??",
                    electricite: "?",
                    menuiserie: "??",
                    ferronnerie: "??",
                    frigoriste: "??",
                    mecanique: "??",
                    nettoyage: "??",
                    peinture: "??",
                    maintenance: "???",
                }[s?.toLowerCase()] ?? "??"
            );
        },
        // Retourne le num�ro d'�tape en parsant step ou en d�duisant depuis status
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
            if (!iso) return "�";
            return new Date(iso).toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        },
        formatDateTime(iso) {
            if (!iso) return "�";
            const d = new Date(iso);
            return (
                d.toLocaleDateString("fr-FR", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                }) +
                " � " +
                d.toLocaleTimeString("fr-FR", {
                    hour: "2-digit",
                    minute: "2-digit",
                })
            );
        },
        formatReservationDate(dateStr) {
            if (!dateStr) return "";
            const d = new Date(dateStr);
            return d.toLocaleDateString("fr-FR", {
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        },
        formatPrice(a) {
            if (!a) return "�";
            return (
                new Intl.NumberFormat("fr-FR").format(Math.round(a)) + " FCFA"
            );
        },
        wip(f) {
            this.showToast(f + " � bient�t disponible.", "");
        },
        onGlobalUnreadUpdate(evt) {
            const byMission = evt.detail?.by_mission ?? {};
            // Ne pas �craser le compteur de la conversation ouverte (d�j� lue)
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
                })
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

    watch: {
        showMapModal(val) {
            if (val) {
                this.$nextTick(() => this.initLeafletMap());
            } else {
                this.destroyMap();
            }
        },
        address_mode(val) {
            if (val === "geo") this.openMapModal();
        },
    },

    mounted() {
        this.fetchMissions();
        this.fetchNotifications();
        this.notifInterval = setInterval(
            () => this.fetchNotifications(),
            30000
        );
        document.addEventListener("click", this.handleClickOutside);
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
        window.addEventListener("rt-unread-update", this.onGlobalUnreadUpdate);
    },
    beforeUnmount() {
        this.destroyMap();
        clearInterval(this.notifInterval);
        document.removeEventListener("click", this.handleClickOutside);
        window.removeEventListener(
            "rt-unread-update",
            this.onGlobalUnreadUpdate
        );
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
    position: relative;
    overflow: hidden;
}
.clm-item:hover {
    border-color: var(--am);
    transform: translateY(-1px);
}
.clm-item.has-unread {
    background: #fffbf7;
    border-color: #fed7aa;
}
/* Bande orange � gauche pour messages non lus */
.clm-unread-stripe {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #f97316, #ea580c);
    border-radius: 4px 0 0 4px;
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
.clm-item-reservation {
    font-size: 12.5px;
    color: #f97316;
    margin-top: 2px;
    font-weight: 600;
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
/* Bouton Lire message */
.clm-read-msg-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border: none;
    border-radius: 99px;
    padding: 4px 10px;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.18s, transform 0.12s;
    box-shadow: 0 2px 6px rgba(249, 115, 22, 0.35);
    font-family: "Poppins", sans-serif;
}
.clm-read-msg-btn:hover {
    opacity: 0.88;
    transform: scale(1.04);
}
.clm-read-msg-dot {
    background: #fff;
    color: #f97316;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    min-width: 17px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
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
/* ACTION QUOTE � nouveau bloc d�taill� */
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
.clm-action-quote-approved {
    border-color: #86efac;
    background: #f0fdf4;
    padding: 12px 14px;
}
.clm-qa-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.clm-qa-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.clm-qa-icon {
    font-size: 22px;
    flex-shrink: 0;
}
.clm-qa-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.clm-qa-amount {
    font-size: 14px;
    font-weight: 800;
    color: #16a34a;
    margin-top: 1px;
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

/* -- Avis -- */
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
    transition: color 0.15s, transform 0.12s;
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

/* -- Adresse g�olocalis�e -- */
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
    overflow: hidden;
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
    flex: 1;
    overflow-y: auto;
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
/* -- Barre de recherche carte -- */
.clm-map-search {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}
.clm-map-search-input {
    flex: 1;
    margin-bottom: 0 !important;
}
.clm-map-search-btn {
    flex-shrink: 0;
    padding: 10px 16px;
    font-size: 13px;
}

/* -- S�lecteur mode adresse -- */
.clm-addr-modes,
.clm-geo-methods {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 6px;
}
.clm-addr-mode-label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1.5px solid var(--grl);
    border-radius: 10px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--gr);
    background: var(--wh);
    transition: all 0.15s;
    user-select: none;
}
.clm-addr-mode-label input[type="radio"] {
    accent-color: var(--or);
}
.clm-addr-mode-label.active,
.clm-addr-mode-label:has(input:checked) {
    border-color: var(--or);
    color: var(--or);
    background: var(--or3);
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
    background: rgba(0, 0, 0, 0.6);
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
/* Miniatures dans la card liste */
.clm-item-imgs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}
.clm-item-img-thumb {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: opacity 0.15s;
}
.clm-item-img-thumb:hover {
    opacity: 0.8;
}
.clm-item-imgs-more {
    width: 44px;
    height: 44px;
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #555;
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
    background: rgba(0, 0, 0, 0.85);
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

/* -- MoMo form -- */
.clm-momo-step-hint {
    background: #eff6ff;
    border: 1.5px solid #bfdbfe;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #1d4ed8;
    font-weight: 600;
    margin-bottom: 16px;
    line-height: 1.5;
}
.clm-momo-hint {
    font-size: 12px;
    color: var(--gr, #7c6a5a);
    margin-top: 4px;
    font-style: italic;
}

/* -- MoMo modal states -- */
.clm-momo-waiting {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 32px 24px;
}
.clm-momo-pulse {
    font-size: 52px;
    animation: clm-momo-bounce 1.2s infinite;
    margin-bottom: 16px;
}
@keyframes clm-momo-bounce {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.18);
    }
}
.clm-momo-wait-title {
    font-size: 17px;
    font-weight: 700;
    color: #1c1412;
    margin-bottom: 8px;
}
.clm-momo-countdown {
    background: #fef3c7;
    color: #92400e;
    border-radius: 8px;
    padding: 7px 16px;
    font-size: 13px;
    font-weight: 600;
}
.clm-momo-ussd-hint {
    margin-top: 14px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12.5px;
    color: #166534;
    line-height: 1.6;
    text-align: center;
}
.clm-momo-ussd-hint strong {
    color: #ea580c;
    font-size: 13.5px;
    letter-spacing: 0.5px;
}
.clm-momo-success {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 32px 24px;
}
.clm-momo-success-icon {
    font-size: 52px;
    margin-bottom: 14px;
}
.clm-momo-success-title {
    font-size: 18px;
    font-weight: 800;
    color: #15803d;
    margin-bottom: 8px;
}
.clm-momo-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 32px 24px;
}
.clm-momo-error-icon {
    font-size: 48px;
    margin-bottom: 14px;
}
.clm-momo-error-title {
    font-size: 17px;
    font-weight: 700;
    color: #dc2626;
    margin-bottom: 8px;
}

/* -- Invoice action block -- */
.clm-action-invoice {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
}
.clm-invoice-icon { font-size: 32px; margin-bottom: 8px }
.clm-invoice-title { font-size: 15px; font-weight: 700; color: #15803d; margin-bottom: 4px }
.clm-invoice-sub { font-size: 13px; color: #4A3F3A; margin-bottom: 14px; line-height: 1.5 }

/* -- Receipt action block -- */
.clm-action-receipt {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
}

/* -- POPUP POST-MISSION CLIENT -- */
.clm-modal-completion {
    max-width: 520px;
    width: 95vw;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    padding: 0;
}
.clm-completion-header {
    text-align: center;
    padding: 28px 28px 16px;
    border-bottom: 1.5px solid var(--grl, #e8ddd4);
}
.clm-completion-icon {
    font-size: 52px;
    margin-bottom: 10px;
}
.clm-completion-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--dk, #1c1412);
    margin-bottom: 6px;
}
.clm-completion-sub {
    font-size: 13.5px;
    color: var(--gr, #7c6a5a);
    line-height: 1.6;
    margin: 0;
}
.clm-completion-section {
    padding: 18px 24px;
    border-bottom: 1px solid var(--grl, #e8ddd4);
}
.clm-completion-section:last-of-type {
    border-bottom: none;
}
.clm-completion-section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--dk, #1c1412);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.clm-completion-section-body {
    font-size: 13.5px;
    color: var(--gr, #7c6a5a);
    line-height: 1.6;
    margin-bottom: 12px;
}
.clm-completion-done-badge {
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13.5px;
    color: #16a34a;
    font-weight: 600;
    text-align: center;
}
.clm-share-btns {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.clm-share-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 700;
    text-decoration: none;
    transition: opacity 0.2s;
}
.clm-share-btn:hover { opacity: 0.85; }
.clm-share-wa {
    background: #22c55e;
    color: #fff;
}
.clm-share-fb {
    background: #3b82f6;
    color: #fff;
}
</style>
