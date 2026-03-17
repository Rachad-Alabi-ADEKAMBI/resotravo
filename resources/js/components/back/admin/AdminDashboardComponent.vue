<template>
    <div class="ad-wrap">
        <!-- ══════════════════════════════════
             TOPBAR
        ══════════════════════════════════ -->
        <div class="ad-topbar">
            <div class="ad-topbar-left">
                <button
                    class="ad-burger"
                    @click="emitToggleSidebar"
                    aria-label="Menu"
                >
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <div class="ad-page-title">Tableau de bord</div>
                    <div class="ad-page-sub">
                        {{ today }} &mdash; Bienvenue, {{ admin.name }}
                    </div>
                </div>
            </div>
            <div class="ad-topbar-right">
                <div class="ad-notif-btn" @click="showNotifs = !showNotifs">
                    &#x1F514;
                    <span class="ad-notif-badge">{{
                        urgentActions.length
                    }}</span>
                    <!-- Dropdown notifs -->
                    <div
                        class="ad-notif-dropdown"
                        v-if="showNotifs"
                        @click.stop
                    >
                        <div class="ad-notif-header">Alertes urgentes</div>
                        <div
                            class="ad-notif-item"
                            v-for="a in urgentActions"
                            :key="a.label"
                        >
                            <span>{{ a.icon }}</span>
                            <div>
                                <div class="ad-notif-lbl">{{ a.label }}</div>
                                <div class="ad-notif-val">{{ a.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="ad-btn ad-btn-orange" @click="exportReport">
                    &#8659; Exporter
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════
             CONTENU
        ══════════════════════════════════ -->
        <div class="ad-content">
            <!-- ── STATS PRINCIPALES ── -->
            <div class="ad-section-title">Vue d'ensemble</div>
            <div class="ad-stats-grid">
                <div
                    class="ad-kpi"
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    :class="kpi.color"
                >
                    <div class="ad-kpi-icon">{{ kpi.icon }}</div>
                    <div class="ad-kpi-val">{{ kpi.value }}</div>
                    <div class="ad-kpi-label">{{ kpi.label }}</div>
                    <div
                        class="ad-kpi-trend"
                        :class="kpi.trend > 0 ? 'up' : 'down'"
                    >
                        {{ kpi.trend > 0 ? "↑" : "↓" }}
                        {{ Math.abs(kpi.trend) }}% ce mois
                    </div>
                </div>
            </div>

            <!-- ── LIGNE MILIEU : Activité + Actions urgentes ── -->
            <div class="ad-mid-grid">
                <!-- Activité récente -->
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3>&#x1F4CA; Activite recente</h3>
                        <div class="ad-period-tabs">
                            <button
                                :class="{ active: period === '7j' }"
                                @click="period = '7j'"
                            >
                                7j
                            </button>
                            <button
                                :class="{ active: period === '30j' }"
                                @click="period = '30j'"
                            >
                                30j
                            </button>
                            <button
                                :class="{ active: period === '90j' }"
                                @click="period = '90j'"
                            >
                                90j
                            </button>
                        </div>
                    </div>
                    <!-- Barres simulees -->
                    <div class="ad-chart-bars">
                        <div
                            class="ad-bar-group"
                            v-for="(d, i) in chartData[period]"
                            :key="i"
                        >
                            <div class="ad-bars">
                                <div
                                    class="ad-bar missions"
                                    :style="{
                                        height:
                                            (d.missions / maxChart) * 100 + '%',
                                    }"
                                    :title="d.missions + ' missions'"
                                ></div>
                                <div
                                    class="ad-bar revenus"
                                    :style="{
                                        height:
                                            (d.revenus / maxChart) * 100 + '%',
                                    }"
                                    :title="d.revenus + ' FCFA'"
                                ></div>
                            </div>
                            <div class="ad-bar-label">{{ d.label }}</div>
                        </div>
                    </div>
                    <div class="ad-chart-legend">
                        <span class="missions">&#9632; Missions</span>
                        <span class="revenus">&#9632; Revenus (x100 FCFA)</span>
                    </div>
                </div>

                <!-- Actions urgentes -->
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3>&#x26A0;&#xFE0F; Actions requises</h3>
                        <span class="ad-badge-count">{{
                            urgentActions.length
                        }}</span>
                    </div>
                    <div class="ad-actions-list">
                        <div
                            class="ad-action-item"
                            v-for="a in urgentActions"
                            :key="a.label"
                        >
                            <div class="ad-action-left">
                                <div
                                    class="ad-action-dot"
                                    :class="a.level"
                                ></div>
                                <div>
                                    <div class="ad-action-label">
                                        {{ a.label }}
                                    </div>
                                    <div class="ad-action-sub">{{ a.sub }}</div>
                                </div>
                            </div>
                            <div class="ad-action-right">
                                <div class="ad-action-val" :class="a.level">
                                    {{ a.value }}
                                </div>
                                <button
                                    class="ad-action-btn"
                                    @click="handleAction(a)"
                                >
                                    {{ a.cta }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── LIGNE BAS : Répartition + Missions + Revenus ── -->
            <div class="ad-bottom-grid">
                <!-- Répartition utilisateurs -->
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3>&#x1F465; Utilisateurs</h3>
                        <div class="ad-total-badge">{{ totalUsers }} total</div>
                    </div>
                    <div class="ad-donut-wrap">
                        <svg class="ad-donut" viewBox="0 0 100 100">
                            <circle
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="#f0ebe5"
                                stroke-width="14"
                            />
                            <circle
                                class="ad-donut-seg clients"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="#F97316"
                                stroke-width="14"
                                :stroke-dasharray="donutDash(userRatio.clients)"
                                :stroke-dashoffset="donutOffset(0)"
                            />
                            <circle
                                class="ad-donut-seg contractors"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="#3b82f6"
                                stroke-width="14"
                                :stroke-dasharray="
                                    donutDash(userRatio.contractors)
                                "
                                :stroke-dashoffset="
                                    donutOffset(userRatio.clients)
                                "
                            />
                            <circle
                                class="ad-donut-seg talents"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="#8b5cf6"
                                stroke-width="14"
                                :stroke-dasharray="donutDash(userRatio.talents)"
                                :stroke-dashoffset="
                                    donutOffset(
                                        userRatio.clients +
                                            userRatio.contractors,
                                    )
                                "
                            />
                            <text
                                x="50"
                                y="47"
                                text-anchor="middle"
                                font-size="13"
                                font-weight="900"
                                fill="#1C1412"
                            >
                                {{ totalUsers }}
                            </text>
                            <text
                                x="50"
                                y="58"
                                text-anchor="middle"
                                font-size="6"
                                fill="#7C6A5A"
                            >
                                users
                            </text>
                        </svg>
                        <div class="ad-donut-legend">
                            <div class="ad-dleg">
                                <span class="dot clients"></span> Clients
                                <strong>{{ stats.clients }}</strong>
                            </div>
                            <div class="ad-dleg">
                                <span class="dot contractors"></span>
                                Prestataires
                                <strong>{{ stats.contractors }}</strong>
                            </div>
                            <div class="ad-dleg">
                                <span class="dot talents"></span> Talents
                                <strong>{{ stats.talents }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Missions par statut -->
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3>&#x1F4CB; Missions</h3>
                        <div class="ad-total-badge">
                            {{ totalMissions }} total
                        </div>
                    </div>
                    <div class="ad-mission-bars">
                        <div
                            class="ad-mbar-item"
                            v-for="m in missionStats"
                            :key="m.label"
                        >
                            <div class="ad-mbar-top">
                                <span>{{ m.icon }} {{ m.label }}</span>
                                <strong>{{ m.value }}</strong>
                            </div>
                            <div class="ad-mbar-track">
                                <div
                                    class="ad-mbar-fill"
                                    :class="m.color"
                                    :style="{
                                        width:
                                            (m.value / totalMissions) * 100 +
                                            '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenus & Finance -->
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3>&#x1F4B0; Finance</h3>
                    </div>
                    <div class="ad-finance-list">
                        <div
                            class="ad-finance-item"
                            v-for="f in financeStats"
                            :key="f.label"
                        >
                            <div class="ad-finance-icon">{{ f.icon }}</div>
                            <div class="ad-finance-info">
                                <div class="ad-finance-label">
                                    {{ f.label }}
                                </div>
                                <div class="ad-finance-val" :class="f.color">
                                    {{ f.value }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ad-finance-note">
                        Commission Resotravo : 10% sur chaque mission
                    </div>
                </div>
            </div>

            <!-- ── DERNIÈRES ACTIVITÉS ── -->
            <div class="ad-card" style="margin-top: 0">
                <div class="ad-card-header">
                    <h3>&#x1F552; Dernieres activites</h3>
                    <button
                        class="ad-btn ad-btn-ghost ad-btn-sm"
                        @click="refreshActivity"
                    >
                        &#8635; Actualiser
                    </button>
                </div>
                <div class="ad-activity-list">
                    <div
                        class="ad-activity-item"
                        v-for="(a, i) in recentActivity"
                        :key="i"
                    >
                        <div class="ad-activity-dot" :class="a.type"></div>
                        <div class="ad-activity-icon">{{ a.icon }}</div>
                        <div class="ad-activity-info">
                            <div class="ad-activity-text">{{ a.text }}</div>
                            <div class="ad-activity-time">{{ a.time }}</div>
                        </div>
                        <span class="ad-activity-badge" :class="a.type">{{
                            a.badge
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /content -->

        <!-- TOASTS -->
        <div class="ad-toast-container">
            <div
                class="ad-toast"
                :class="t.type"
                v-for="t in toasts"
                :key="t.id"
            >
                {{ t.message }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminDashboardComponent",

    props: {
        admin: {
            type: Object,
            default: () => ({ name: "Administrateur", role: "admin" }),
        },
    },

    data() {
        const now = new Date();
        const days = [
            "Dimanche",
            "Lundi",
            "Mardi",
            "Mercredi",
            "Jeudi",
            "Vendredi",
            "Samedi",
        ];
        const months = [
            "janvier",
            "février",
            "mars",
            "avril",
            "mai",
            "juin",
            "juillet",
            "août",
            "septembre",
            "octobre",
            "novembre",
            "décembre",
        ];

        return {
            today: `${days[now.getDay()]} ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`,
            period: "7j",
            showNotifs: false,
            toasts: [],
            toastId: 0,
            sidebarOpen: false,
            loading: true,

            /* ── Données réelles chargées via API ── */
            stats: {
                clients: 0,
                contractors: 0,
                talents: 0,
                certified: 0,
                pending: 0,
            },
            docStats: { total: 0, pending: 0, approved: 0, rejected: 0 },
            recentActivity: [],
            chartData: { "7j": [], "30j": [], "90j": [] },

            /* ── KPIs (mis à jour après loadStats) ── */
            kpis: [
                {
                    icon: "👤",
                    label: "Utilisateurs inscrits",
                    value: "…",
                    trend: 0,
                    color: "",
                },
                {
                    icon: "👷",
                    label: "Prestataires certifiés",
                    value: "…",
                    trend: 0,
                    color: "orange",
                },
                {
                    icon: "📋",
                    label: "Missions ce mois",
                    value: "—",
                    trend: 0,
                    color: "green",
                },
                {
                    icon: "💸",
                    label: "Revenus ce mois (FCFA)",
                    value: "—",
                    trend: 0,
                    color: "",
                },
                {
                    icon: "⏳",
                    label: "Dossiers en attente",
                    value: "…",
                    trend: 0,
                    color: "red",
                },
                {
                    icon: "📄",
                    label: "Documents à valider",
                    value: "…",
                    trend: 0,
                    color: "",
                },
                {
                    icon: "📝",
                    label: "Appels d'offres actifs",
                    value: "—",
                    trend: 0,
                    color: "",
                },
                {
                    icon: "💬",
                    label: "Tickets Allo Conseils",
                    value: "—",
                    trend: 0,
                    color: "",
                },
            ],

            /* ── Missions (pas encore en base — fictif) ── */
            missionStats: [
                { icon: "✅", label: "Terminées", value: 0, color: "green" },
                { icon: "🔄", label: "En cours", value: 0, color: "orange" },
                { icon: "⏳", label: "En attente", value: 0, color: "amber" },
                { icon: "❌", label: "Annulées", value: 0, color: "red" },
            ],

            /* ── Finance (pas encore en base — fictif) ── */
            financeStats: [
                {
                    icon: "💰",
                    label: "Volume total traité",
                    value: "—",
                    color: "",
                },
                {
                    icon: "📈",
                    label: "Commission Resotravo",
                    value: "—",
                    color: "green",
                },
                {
                    icon: "🏦",
                    label: "Paiements en attente",
                    value: "—",
                    color: "orange",
                },
                {
                    icon: "🧾",
                    label: "Factures générées",
                    value: "—",
                    color: "",
                },
            ],

            /* ── Actions urgentes (mises à jour après loadStats) ── */
            urgentActions: [],
        };
    },

    computed: {
        totalUsers() {
            return (
                this.stats.clients + this.stats.contractors + this.stats.talents
            );
        },
        totalMissions() {
            return this.missionStats.reduce((s, m) => s + m.value, 0);
        },

        userRatio() {
            const t = this.totalUsers || 1;
            return {
                clients: Math.round((this.stats.clients / t) * 100) || 0,
                contractors:
                    Math.round((this.stats.contractors / t) * 100) || 0,
                talents: Math.round((this.stats.talents / t) * 100) || 0,
            };
        },

        maxChart() {
            const data = this.chartData[this.period];
            if (!data || data.length === 0) return 1;
            return Math.max(
                ...data.map((d) =>
                    Math.max(
                        d.clients || 0,
                        d.contractors || 0,
                        d.talents || 0,
                        d.missions || 0,
                        d.revenus || 0,
                        1,
                    ),
                ),
            );
        },
    },

    methods: {
        /* ── Charger les stats réelles depuis l'API ── */
        async loadStats() {
            console.log("[AdminDashboard] loadStats — GET /admin/stats");
            try {
                const response = await window.axios.get("/admin/stats");
                const d = response.data;

                console.log("[AdminDashboard] loadStats — réponse:", d);

                // Utilisateurs
                this.stats = {
                    clients: d.users.clients,
                    contractors: d.users.contractors,
                    talents: d.users.talents,
                    certified: d.users.certified,
                    pending: d.users.pending,
                };

                // Documents
                this.docStats = d.documents;

                // KPIs réels
                this.kpis[0].value = d.users.total.toLocaleString("fr-FR");
                this.kpis[0].trend = d.users.growth;
                this.kpis[1].value = d.users.certified.toLocaleString("fr-FR");
                this.kpis[4].value = d.users.pending.toLocaleString("fr-FR");
                this.kpis[5].value =
                    d.documents.pending.toLocaleString("fr-FR");

                // Actions urgentes réelles
                this.urgentActions = [];
                if (d.users.pending > 0) {
                    this.urgentActions.push({
                        icon: "🛡️",
                        label: "Dossiers à valider",
                        sub: "Prestataires en attente",
                        value: String(d.users.pending),
                        level: "high",
                        cta: "Traiter",
                    });
                }
                if (d.documents.pending > 0) {
                    this.urgentActions.push({
                        icon: "📄",
                        label: "Documents à examiner",
                        sub: "En attente de vérification",
                        value: String(d.documents.pending),
                        level: "medium",
                        cta: "Examiner",
                    });
                }
                if (d.users.rejected > 0) {
                    this.urgentActions.push({
                        icon: "❌",
                        label: "Dossiers refusés",
                        sub: "À notifier ou ré-examiner",
                        value: String(d.users.rejected),
                        level: "low",
                        cta: "Voir",
                    });
                }

                // Activité récente : inscriptions réelles
                this.recentActivity = d.recent_users.map((u) => ({
                    type:
                        u.status === "approved"
                            ? "success"
                            : u.status === "rejected"
                              ? "warning"
                              : "info",
                    icon:
                        u.role === "contractor"
                            ? "👷"
                            : u.role === "client"
                              ? "👤"
                              : "⭐",
                    text: `Nouvelle inscription — ${u.name} · ${u.role === "contractor" ? (u.specialty ?? "Prestataire") : u.role === "client" ? "Client" : "Talent"}`,
                    time: u.created_at,
                    badge:
                        u.role === "contractor"
                            ? "Inscription"
                            : u.role === "client"
                              ? "Client"
                              : "Talent",
                }));

                // Graphiques : remplacer missions/revenus par clients/contractors/talents
                this.chartData = {
                    "7j": d.charts["7j"].map((p) => ({
                        label: p.label,
                        missions: p.clients + p.contractors + p.talents,
                        revenus: p.contractors,
                    })),
                    "30j": d.charts["30j"].map((p) => ({
                        label: p.label,
                        missions: p.clients + p.contractors + p.talents,
                        revenus: p.contractors,
                    })),
                    "90j": d.charts["90j"].map((p) => ({
                        label: p.label,
                        missions: p.clients + p.contractors + p.talents,
                        revenus: p.contractors,
                    })),
                };
            } catch (err) {
                console.error(
                    "[AdminDashboard] loadStats — erreur:",
                    err?.response?.status,
                    err?.response?.data ?? err.message,
                );
                this.showToast(
                    "Impossible de charger les statistiques.",
                    "error",
                );
            } finally {
                this.loading = false;
            }
        },

        /* ── Donut SVG helpers ── */
        donutDash(pct) {
            const p = isNaN(pct) || !pct ? 0 : pct;
            const circ = 2 * Math.PI * 38;
            return `${(p / 100) * circ} ${circ}`;
        },
        donutOffset(prevPct) {
            const p = isNaN(prevPct) || !prevPct ? 0 : prevPct;
            const circ = 2 * Math.PI * 38;
            return -(p / 100) * circ;
        },

        /* ── Actions ── */
        handleAction(a) {
            this.showToast(`Redirection vers : ${a.label}`, "info");
            this.showNotifs = false;
        },

        refreshActivity() {
            this.showToast("Activites actualisees.", "success");
        },

        emitToggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.dispatchEvent(
                new CustomEvent("ab-sidebar-toggle", {
                    detail: { open: this.sidebarOpen },
                }),
            );
        },

        /* ── Export ── */
        exportReport() {
            const lines = [
                "RESOTRAVO — Rapport global",
                `Genere le : ${this.today}`,
                "",
                "== UTILISATEURS ==",
                `Clients : ${this.stats.clients}`,
                `Prestataires certifies : ${this.stats.contractors}`,
                `Talents : ${this.stats.talents}`,
                `Total : ${this.totalUsers}`,
                "",
                "== MISSIONS ==",
                ...this.missionStats.map((m) => `${m.label} : ${m.value}`),
                `Total : ${this.totalMissions}`,
                "",
                "== FINANCE ==",
                ...this.financeStats.map((f) => `${f.label} : ${f.value}`),
                "",
                "== KPIs ==",
                ...this.kpis.map(
                    (k) =>
                        `${k.label} : ${k.value} (${k.trend > 0 ? "+" : ""}${k.trend}%)`,
                ),
                "",
                "== ACTIONS URGENTES ==",
                ...this.urgentActions.map((a) => `${a.label} : ${a.value}`),
            ];

            const blob = new Blob([lines.join("\n")], {
                type: "text/plain;charset=utf-8",
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `resotravo_rapport_${new Date().toISOString().slice(0, 10)}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showToast("Rapport exporte avec succes.", "success");
        },

        /* ── Toast ── */
        showToast(message, type = "") {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },
    },

    mounted() {
        window.addEventListener("ab-sidebar-close", () => {
            this.sidebarOpen = false;
        });
        document.addEventListener("click", () => {
            this.showNotifs = false;
        });
        this.loadStats();
    },
};
</script>

<style scoped>
/* WRAP */
.ad-wrap {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #f8f4f0;
    font-family: "Poppins", sans-serif;
}

/* TOPBAR */
.ad-topbar {
    background: var(--wh);
    border-bottom: 2px solid var(--grl);
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    position: sticky;
    top: 0;
    z-index: 40;
    gap: 8px;
}
@media (min-width: 600px) {
    .ad-topbar {
        padding: 0 24px;
        gap: 12px;
        height: 64px;
    }
}
.ad-topbar-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.ad-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.ad-page-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--dk);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ad-page-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: none;
}
@media (min-width: 480px) {
    .ad-page-sub {
        display: block;
    }
}

/* BURGER — géré exclusivement par back.blade.php */
.ad-burger {
    background: none;
    border: none;
    cursor: pointer;
    flex-direction: column;
    gap: 5px;
    padding: 4px;
    flex-shrink: 0;
}
.ad-burger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--dk);
    border-radius: 2px;
}

/* NOTIF */
.ad-notif-btn {
    position: relative;
    background: var(--cr);
    border: 2px solid var(--grl);
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 17px;
    flex-shrink: 0;
}
.ad-notif-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 99px;
    min-width: 16px;
    text-align: center;
}
.ad-notif-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: var(--wh);
    border: 2px solid var(--grl);
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    min-width: 280px;
    z-index: 200;
    overflow: hidden;
}
.ad-notif-header {
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    background: #faf7f4;
    border-bottom: 1px solid var(--grl);
}
.ad-notif-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    border-bottom: 1px solid var(--grl);
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
}
.ad-notif-item:last-child {
    border-bottom: none;
}
.ad-notif-item:hover {
    background: var(--cr);
}
.ad-notif-lbl {
    font-weight: 600;
    color: var(--dk);
}
.ad-notif-val {
    font-size: 12px;
    color: var(--gr);
    margin-top: 1px;
}

/* BTNS */
.ad-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: "Poppins", sans-serif;
    transition: all 0.2s;
}
.ad-btn-sm {
    padding: 5px 11px;
    font-size: 12px;
}
.ad-btn-orange {
    background: var(--or);
    color: #fff;
}
.ad-btn-orange:hover {
    background: var(--or2);
    transform: translateY(-1px);
}
.ad-btn-ghost {
    background: var(--grl);
    color: var(--dk);
}
.ad-btn-ghost:hover {
    background: #d5c9c0;
}

/* CONTENT */
.ad-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
@media (min-width: 600px) {
    .ad-content {
        padding: 20px;
        gap: 18px;
    }
}
@media (min-width: 900px) {
    .ad-content {
        padding: 28px;
        gap: 20px;
    }
}

/* SECTION TITLE */
.ad-section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--gr);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: -6px;
}

/* KPIs */
.ad-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
@media (min-width: 480px) {
    .ad-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
@media (min-width: 700px) {
    .ad-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (min-width: 1100px) {
    .ad-stats-grid {
        grid-template-columns: repeat(8, 1fr);
    }
}

.ad-kpi {
    background: var(--wh);
    border-radius: 14px;
    padding: 14px;
    border: 2px solid var(--grl);
    position: relative;
    overflow: hidden;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
@media (min-width: 600px) {
    .ad-kpi {
        padding: 18px 16px;
    }
}
.ad-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}
.ad-kpi::before {
    content: "";
    position: absolute;
    top: -20px;
    right: -20px;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    opacity: 0.06;
    background: var(--or);
}
.ad-kpi.orange {
    border-top: 3px solid var(--or);
}
.ad-kpi.green {
    border-top: 3px solid #22c55e;
}
.ad-kpi.red {
    border-top: 3px solid #ef4444;
}
.ad-kpi-icon {
    font-size: 18px;
    margin-bottom: 6px;
}
.ad-kpi-val {
    font-size: 20px;
    font-weight: 900;
    color: var(--dk);
    letter-spacing: -0.5px;
    line-height: 1;
}
@media (min-width: 600px) {
    .ad-kpi-val {
        font-size: 22px;
    }
}
.ad-kpi-label {
    font-size: 11px;
    color: var(--gr);
    margin-top: 4px;
    line-height: 1.3;
}
.ad-kpi-trend {
    font-size: 10px;
    font-weight: 700;
    margin-top: 5px;
}
.ad-kpi-trend.up {
    color: #16a34a;
}
.ad-kpi-trend.down {
    color: #dc2626;
}

/* CARD */
.ad-card {
    background: var(--wh);
    border-radius: 14px;
    padding: 16px;
    border: 2px solid var(--grl);
}
@media (min-width: 600px) {
    .ad-card {
        padding: 20px;
        border-radius: 16px;
    }
}
.ad-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 10px;
    flex-wrap: wrap;
}
.ad-card-header h3 {
    font-size: 14px;
    font-weight: 800;
    color: var(--dk);
}
.ad-badge-count {
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 99px;
}
.ad-total-badge {
    background: var(--cr);
    color: var(--gr);
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 99px;
    border: 1px solid var(--grl);
}

/* MID GRID */
.ad-mid-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 900px) {
    .ad-mid-grid {
        grid-template-columns: 3fr 2fr;
    }
}

/* CHART BARS */
.ad-period-tabs {
    display: flex;
    gap: 4px;
}
.ad-period-tabs button {
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    border: 1.5px solid var(--grl);
    background: none;
    color: var(--gr);
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    transition: all 0.18s;
}
.ad-period-tabs button.active {
    background: var(--or);
    border-color: var(--or);
    color: #fff;
}
.ad-chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    height: 130px;
    padding: 0 2px;
}
.ad-bar-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex: 1;
}
.ad-bars {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 110px;
    width: 100%;
}
.ad-bar {
    flex: 1;
    border-radius: 3px 3px 0 0;
    transition: height 0.4s ease;
    min-height: 4px;
}
.ad-bar.missions {
    background: var(--or);
}
.ad-bar.revenus {
    background: #3b82f6;
    opacity: 0.7;
}
.ad-bar-label {
    font-size: 10px;
    color: var(--gr);
    font-weight: 500;
}
.ad-chart-legend {
    display: flex;
    gap: 12px;
    margin-top: 10px;
    font-size: 11.5px;
    color: var(--gr);
}
.ad-chart-legend .missions {
    color: var(--or);
    font-weight: 600;
}
.ad-chart-legend .revenus {
    color: #3b82f6;
    font-weight: 600;
}

/* ACTIONS LIST */
.ad-actions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ad-action-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 0;
    border-bottom: 1px solid var(--grl);
    flex-wrap: wrap;
}
.ad-action-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.ad-action-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.ad-action-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.ad-action-dot.high {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}
.ad-action-dot.medium {
    background: var(--or);
}
.ad-action-dot.low {
    background: #22c55e;
}
.ad-action-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--dk);
}
.ad-action-sub {
    font-size: 11px;
    color: var(--gr);
    margin-top: 1px;
}
.ad-action-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.ad-action-val {
    font-size: 15px;
    font-weight: 900;
}
.ad-action-val.high {
    color: #ef4444;
}
.ad-action-val.medium {
    color: var(--or);
}
.ad-action-val.low {
    color: #22c55e;
}
.ad-action-btn {
    padding: 5px 11px;
    border-radius: 7px;
    border: 1.5px solid var(--grl);
    background: var(--cr);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
    color: var(--dk);
    transition: all 0.18s;
    white-space: nowrap;
}
.ad-action-btn:hover {
    border-color: var(--or);
    color: var(--or);
}

/* BOTTOM GRID */
.ad-bottom-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 600px) {
    .ad-bottom-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (min-width: 1100px) {
    .ad-bottom-grid {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

/* DONUT */
.ad-donut-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.ad-donut {
    width: 100px;
    height: 100px;
    flex-shrink: 0;
    transform: rotate(-90deg);
}
.ad-donut-seg {
    transition: stroke-dasharray 0.6s ease;
}
.ad-donut-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-width: 120px;
}
.ad-dleg {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--gr);
}
.ad-dleg strong {
    color: var(--dk);
    font-weight: 800;
    margin-left: auto;
}
.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot.clients {
    background: var(--or);
}
.dot.contractors {
    background: #3b82f6;
}
.dot.talents {
    background: #8b5cf6;
}

/* MISSION BARS */
.ad-mission-bars {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ad-mbar-top {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--gr);
    margin-bottom: 5px;
}
.ad-mbar-top strong {
    color: var(--dk);
    font-weight: 800;
}
.ad-mbar-track {
    height: 8px;
    background: var(--grl);
    border-radius: 99px;
    overflow: hidden;
}
.ad-mbar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
}
.ad-mbar-fill.green {
    background: #22c55e;
}
.ad-mbar-fill.orange {
    background: var(--or);
}
.ad-mbar-fill.amber {
    background: #fcd34d;
}
.ad-mbar-fill.red {
    background: #ef4444;
}
.ad-mbar-fill.blue {
    background: #3b82f6;
}

/* FINANCE */
.ad-finance-list {
    display: flex;
    flex-direction: column;
    gap: 9px;
    margin-bottom: 14px;
}
.ad-finance-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 12px;
    background: var(--cr);
    border-radius: 10px;
}
.ad-finance-icon {
    font-size: 18px;
    flex-shrink: 0;
}
.ad-finance-label {
    font-size: 12px;
    color: var(--gr);
}
.ad-finance-val {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--dk);
    margin-top: 2px;
}
.ad-finance-val.green {
    color: #16a34a;
}
.ad-finance-val.orange {
    color: var(--or);
}
.ad-finance-note {
    font-size: 11.5px;
    color: var(--grm);
    text-align: center;
    padding-top: 8px;
    border-top: 1px solid var(--grl);
}

/* ACTIVITY */
.ad-activity-list {
    display: flex;
    flex-direction: column;
}
.ad-activity-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 0;
    border-bottom: 1px solid var(--grl);
}
.ad-activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.ad-activity-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.ad-activity-dot.success {
    background: #22c55e;
}
.ad-activity-dot.info {
    background: #3b82f6;
}
.ad-activity-dot.warning {
    background: var(--or);
}
.ad-activity-icon {
    font-size: 17px;
    flex-shrink: 0;
}
.ad-activity-info {
    flex: 1;
    min-width: 0;
}
.ad-activity-text {
    font-size: 13px;
    color: var(--dk);
    font-weight: 500;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ad-activity-time {
    font-size: 11px;
    color: var(--grm);
    margin-top: 2px;
}
.ad-activity-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    flex-shrink: 0;
    display: none;
}
@media (min-width: 500px) {
    .ad-activity-badge {
        display: inline-flex;
    }
}
.ad-activity-badge.success {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.ad-activity-badge.info {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.ad-activity-badge.warning {
    background: #fff7ed;
    color: var(--or);
    border: 1px solid #fed7aa;
}

/* TOASTS */
.ad-toast-container {
    position: fixed;
    bottom: 20px;
    right: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 999;
    max-width: calc(100vw - 32px);
}
.ad-toast {
    background: var(--dk);
    color: #fff;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    min-width: 200px;
    animation: ad-slide 0.3s ease;
}
.ad-toast.success {
    background: #16a34a;
}
.ad-toast.info {
    background: #2563eb;
}
.ad-toast.error {
    background: #dc2626;
}
@keyframes ad-slide {
    from {
        transform: translateY(14px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
