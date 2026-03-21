import './bootstrap'

import { createApp } from 'vue'

import ExampleComponent               from './components/ExampleComponent.vue'
import HomeComponent                  from './components/front/HomeComponent.vue'
import ConsultingComponent            from './components/front/ConsultingComponent.vue'
import MarketComponent                from './components/front/MarketComponent.vue'
import TalentComponent                from './components/front/TalentComponent.vue'
import LoginComponent                 from './components/front/LoginComponent.vue'
import RegisterClientComponent        from './components/front/RegisterClientComponent.vue'
import RegisterContractorComponent    from './components/front/RegisterContractorComponent.vue'
import AdminDashboardComponent        from './components/back/admin/AdminDashboardComponent.vue'
import AdminMissionComponent          from './components/back/admin/AdminMissionComponent.vue'
import AdminAccreditationComponent    from './components/back/admin/AdminAccreditationComponent.vue'
import AdminMarketComponent           from './components/back/admin/AdminMarketComponent.vue'
import AdminTalentComponent           from './components/back/admin/AdminTalentComponent.vue'
import AdminContractorsComponent      from './components/back/admin/AdminContractorsComponent.vue'
import AdminClientsComponent          from './components/back/admin/AdminClientsComponent.vue'
import MissionChatModal               from './components/MissionChatModal.vue'
import ContractorMissionComponent     from './components/back/contractor/ContractorMissionComponent.vue'
import ClientMissionComponent         from './components/back/client/ClientMissionComponent.vue'
import ValidationDocumentsComponent   from './components/back/admin/ValidationDocumentsComponent.vue'
import ClientDashboardComponent       from './components/back/client/ClientDashboardComponent.vue'
import ContractorDashboardComponent   from './components/back/contractor/ContractorDashboardComponent.vue'

const app = createApp({})

app.component('example-component',              ExampleComponent)
app.component('home-component',                 HomeComponent)
app.component('consulting-component',           ConsultingComponent)
app.component('market-component',               MarketComponent)
app.component('talent-component',               TalentComponent)
app.component('login-component',                LoginComponent)
app.component('register-client-component',      RegisterClientComponent)
app.component('register-contractor-component',  RegisterContractorComponent)
app.component('admin-dashboard-component',      AdminDashboardComponent)
app.component('admin-mission-component',        AdminMissionComponent)
app.component('admin-accreditation-component',  AdminAccreditationComponent)
app.component('admin-market-component',         AdminMarketComponent)
app.component('admin-talent-component',       AdminTalentComponent)
app.component('admin-contractors-component', AdminContractorsComponent)
app.component('admin-clients-component',     AdminClientsComponent)
app.component('mission-chat-modal',             MissionChatModal)
app.component('contractor-mission-component',   ContractorMissionComponent)
app.component('client-mission-component',       ClientMissionComponent)
app.component('validation-documents-component', ValidationDocumentsComponent)
app.component('client-dashboard-component',     ClientDashboardComponent)
app.component('contractor-dashboard-component', ContractorDashboardComponent)

app.mount('#resotravo-app')