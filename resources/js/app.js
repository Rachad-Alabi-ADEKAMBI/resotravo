import "./bootstrap";

import { createApp, defineAsyncComponent, nextTick } from "vue";
import { initIconMode } from "./icon-mode";

const app = createApp({});
const appRoot = document.getElementById("mesotravo-app");
const pageLoader = document.getElementById("Mesotravo-loader");
const loaderStartedAt = Date.now();
let appMounted = false;
let pendingAsyncComponents = 0;

const revealApp = () => {
    if (appRoot) appRoot.style.visibility = "visible";
    if (pageLoader) pageLoader.classList.add("hidden");
    document.body.classList.remove("mesotravo-page-loading");
};

const revealAppWhenReady = () => {
    if (!appMounted || pendingAsyncComponents > 0) return;

    const elapsed = Date.now() - loaderStartedAt;
    const delay = Math.max(0, 250 - elapsed);

    window.setTimeout(() => {
        if (pendingAsyncComponents > 0) return;

        revealApp();
    }, delay);
};

const revealAfterPaint = () => {
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(revealAppWhenReady);
    });
};

const registerAsyncComponent = (name, loader) => {
    app.component(
        name,
        defineAsyncComponent({
            loader: () => {
                pendingAsyncComponents += 1;

                return loader().finally(() => {
                    pendingAsyncComponents = Math.max(0, pendingAsyncComponents - 1);
                    revealAfterPaint();
                });
            },
            delay: 80,
            timeout: 30000,
        }),
    );
};

registerAsyncComponent("example-component", () => import("./components/ExampleComponent.vue"));
registerAsyncComponent("home-component", () => import("./components/front/HomeComponent.vue"));
registerAsyncComponent("consulting-component", () => import("./components/front/ConsultingComponent.vue"));
registerAsyncComponent("market-component", () => import("./components/front/MarketComponent.vue"));
registerAsyncComponent("talent-component", () => import("./components/front/TalentComponent.vue"));
registerAsyncComponent("login-component", () => import("./components/front/LoginComponent.vue"));
registerAsyncComponent("register-client-component", () => import("./components/front/RegisterClientComponent.vue"));
registerAsyncComponent("register-contractor-component", () => import("./components/front/RegisterContractorComponent.vue"));
registerAsyncComponent("admin-dashboard-component", () => import("./components/back/admin/AdminDashboardComponent.vue"));
registerAsyncComponent("admin-mission-component", () => import("./components/back/admin/AdminMissionComponent.vue"));
registerAsyncComponent("admin-accreditation-component", () => import("./components/back/admin/AdminAccreditationComponent.vue"));
registerAsyncComponent("admin-market-component", () => import("./components/back/admin/AdminMarketComponent.vue"));
registerAsyncComponent("admin-talent-component", () => import("./components/back/admin/AdminTalentComponent.vue"));
registerAsyncComponent("admin-contractors-component", () => import("./components/back/admin/AdminContractorsComponent.vue"));
registerAsyncComponent("admin-clients-component", () => import("./components/back/admin/AdminClientsComponent.vue"));
registerAsyncComponent("admin-services-component", () => import("./components/back/admin/AdminServicesComponent.vue"));
registerAsyncComponent("admin-consulting-component", () => import("./components/back/admin/AdminConsultingComponent.vue"));
registerAsyncComponent("admin-disputes-component", () => import("./components/back/admin/AdminDisputesComponent.vue"));
registerAsyncComponent("admin-revenus-component", () => import("./components/back/admin/AdminRevenusComponent.vue"));
registerAsyncComponent("admin-configuration-component", () => import("./components/back/admin/AdminConfigurationComponent.vue"));
registerAsyncComponent("admin-mail-component", () => import("./components/back/admin/AdminMailComponent.vue"));
registerAsyncComponent("mission-chat-modal", () => import("./components/MissionChatModal.vue"));
registerAsyncComponent("contractor-mission-component", () => import("./components/back/contractor/ContractorMissionComponent.vue"));
registerAsyncComponent("contractor-revenus-component", () => import("./components/back/contractor/ContractorRevenusComponent.vue"));
registerAsyncComponent("client-paiements-component", () => import("./components/back/client/ClientPaiementsComponent.vue"));
registerAsyncComponent("client-mission-component", () => import("./components/back/client/ClientMissionComponent.vue"));
registerAsyncComponent("validation-documents-component", () => import("./components/back/admin/ValidationDocumentsComponent.vue"));
registerAsyncComponent("client-dashboard-component", () => import("./components/back/client/ClientDashboardComponent.vue"));
registerAsyncComponent("contractor-dashboard-component", () => import("./components/back/contractor/ContractorDashboardComponent.vue"));
registerAsyncComponent("dossier-component", () => import("./components/back/shared/DossierComponent.vue"));
registerAsyncComponent("parameters-component", () => import("./components/back/shared/ParametersComponent.vue"));
registerAsyncComponent("contractor-obligations-component", () => import("./components/back/contractor/ContractorObligationsComponent.vue"));
registerAsyncComponent("contractor-accreditation-component", () => import("./components/back/contractor/ContractorAccreditationComponent.vue"));
registerAsyncComponent("messages-component", () => import("./components/back/shared/MessagesComponent.vue"));

if (pageLoader) pageLoader.classList.remove("hidden");
if (appRoot) {
    appRoot.style.visibility = "hidden";
    app.mount(appRoot);
}
appMounted = true;
initIconMode();
nextTick(revealAfterPaint);

window.setTimeout(() => {
    if (document.body.classList.contains("mesotravo-page-loading")) {
        revealApp();
    }
}, 15000);

window.addEventListener("beforeunload", function () {
    if (pageLoader) pageLoader.classList.remove("hidden");
    if (appRoot) appRoot.style.visibility = "hidden";
    document.body.classList.add("mesotravo-page-loading");
});

// Initialiser Capacitor uniquement dans l'app native (pas dans le navigateur)
import("@capacitor/core")
    .then(({ Capacitor }) => {
        if (Capacitor.isNativePlatform()) {
            if (window.location.pathname === "/") {
                window.location.replace("/login");
            }
            import("./mobile").then(({ initMobile }) =>
                initMobile().catch(() => {})
            );
        }
    })
    .catch(() => {});
