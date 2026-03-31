<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionQuoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TalentController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ConsultingController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
// PAGES PUBLIQUES (front)
// ══════════════════════════════════════════════════════════════════

Route::get('/', function () {
    $services = \App\Models\Service::where('status', 'active')
        ->where('is_visible', true)
        ->orderBy('sort_order')
        ->get(['id', 'name', 'icon', 'description'])
        ->map(fn($s) => [
            'name' => $s->name,
            'icon' => $s->icon ?: '🔧',
            'desc' => $s->description,
        ]);

    return view('pages.front.home', compact('services'));
})->name('home');
Route::get('/consulting', function () {
    $user = Auth::user();
    return view('pages.front.consulting', [
        'auth'   => $user ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role] : null,
        'routes' => [
            'register'         => route('register.client'),
            'login'            => route('login'),
            'tickets_store'    => route('consulting.tickets.store'),
            'tickets_index'    => route('consulting.tickets.index'),
            'tickets_messages' => url('/consulting/tickets/{ticket}/messages'),
            'tickets_send'     => url('/consulting/tickets/{ticket}/send'),
            'tickets_human'    => url('/consulting/tickets/{ticket}/request-human'),
        ],
    ]);
})->name('consulting');

Route::get('/talent', function () {
    return view('pages.front.talent', [
        'routes' => [
            'talents_list'  => route('talents.list'),
            'talents_apply' => route('talents.apply'),
            'market'        => route('market'),
            'register'      => route('register.client'),
        ],
    ]);
})->name('talent');

// ── Talents (publics) ─────────────────────────────────────────
Route::prefix('talent')->name('talents.')->group(function () {
    Route::get ('/list',  [TalentController::class, 'index']) ->name('list');
    Route::post('/apply', [TalentController::class, 'store']) ->name('apply');
});

Route::get('/market', function () {
    $user = Auth::user();
    return view('pages.front.market', [
        'auth'   => $user ? ['id' => $user->id, 'role' => $user->role, 'name' => $user->name] : null,
        'routes' => [
            'tenders_index'   => route('tenders.index'),
            'tenders_stats'   => route('tenders.stats'),
            'tenders_store'   => route('client.tenders.store'),
            'tenders_apply'   => url('/tenders/{id}/apply'),
            'my_applications' => route('tenders.my-applications'),
            'my_tenders'      => route('client.tenders.mine'),
            'login'           => route('login'),
            'register'        => route('register.client'),
        ],
    ]);
})->name('market');

// ── Appels d'offres (publics) ────────────────────────────────
Route::prefix('tenders')->name('tenders.')->group(function () {
    Route::get('/',      [TenderController::class, 'index']) ->name('index');
    Route::get('/stats', [TenderController::class, 'stats']) ->name('stats');
});

Route::get('/cgu',    fn() => view('pages.front.cgu'))   ->name('cgu');
Route::get('/policy', fn() => view('pages.front.policy'))->name('policy');

// ── Services (public — page d'accueil) ───────────────────────────
Route::get('/services/public', [ServiceController::class, 'publicIndex'])->name('services.public');

// ── Inscription ──────────────────────────────────────────────────
Route::get ('/register/client',           fn() => view('pages.front.registerClient'))     ->name('register.client');
Route::get ('/register/contractor',       fn() => view('pages.front.registerContractor')) ->name('register.contractor');
Route::post('/register/client/store',     [UserController::class, 'storeClient'])         ->name('register.client.store');
Route::post('/register/contractor/store', [UserController::class, 'storeContractor'])     ->name('register.contractor.store');

// ══════════════════════════════════════════════════════════════════
// AUTHENTIFIÉ
// ══════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {

    // ── Photo de profil de l'utilisateur connecté ──────────────────
    Route::get('/profile/photo', function () {
        $user     = Auth::user();
        $type     = $user->role === 'client' ? 'photo_profil' : 'photo';
        $document = \App\Models\Document::where('user_id', $user->id)
            ->where('type', $type)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if (!$document || !\Illuminate\Support\Facades\Storage::disk($document->disk)->exists($document->path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk($document->disk)->response($document->path);
    })->name('profile.photo');

    // ── Photo de profil par user_id (URL unique par utilisateur) ───
    // Corrige le bug : chaque utilisateur a sa propre URL, plus de confusion de cache
    Route::get('/profile/photo/{userId}', function ($userId) {
        $targetUser = \App\Models\User::findOrFail($userId);
        $type       = $targetUser->role === 'client' ? 'photo_profil' : 'photo';
        $document   = \App\Models\Document::where('user_id', $targetUser->id)
            ->where('type', $type)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if (!$document || !\Illuminate\Support\Facades\Storage::disk($document->disk)->exists($document->path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk($document->disk)->response($document->path);
    })->name('profile.photo.user');

    // ── Allo Conseils — tickets utilisateur ──────────────────────
    Route::prefix('consulting/tickets')->name('consulting.tickets.')->group(function () {
        Route::get   ('/',                       [ConsultingController::class, 'userIndex'])        ->name('index');
        Route::post  ('/',                       [ConsultingController::class, 'userStore'])        ->name('store');
        Route::get   ('/{ticket}/messages',      [ConsultingController::class, 'userMessages'])     ->name('messages');
        Route::post  ('/{ticket}/send',          [ConsultingController::class, 'userSend'])         ->name('send');
        Route::patch ('/{ticket}/request-human', [ConsultingController::class, 'userRequestHuman']) ->name('request-human');
    });

    // ── Appels d'offres — candidature & mes candidatures (tous rôles connectés) ──
    Route::prefix('tenders')->name('tenders.')->group(function () {
        Route::post('/{tender}/apply',   [TenderController::class, 'apply'])          ->name('apply');
        Route::get ('/my-applications',  [TenderController::class, 'myApplications']) ->name('my-applications');
    });

    // ── Redirection dashboard selon le rôle ─────────────────────
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['admin', 'client', 'contractor', 'talent'])) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['role' => 'Rôle non reconnu. Veuillez vous reconnecter.']);
        }

        return redirect()->intended(match ($user->role) {
            'admin'      => route('admin.dashboard'),
            'client'     => route('client.dashboard'),
            'contractor' => route('contractor.dashboard'),
            'talent'     => route('talent.dashboard'),
        });
    })->name('dashboard');

    // ── Profil Breeze ────────────────────────────────────────────
    Route::get   ('/profile',          [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch ('/profile',          [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile',          [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Mise à jour du mot de passe (Breeze) — utilisée par ParametersComponent
    Route::patch ('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ── Documents (tous rôles) ───────────────────────────────────
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get   ('/',                    [DocumentController::class, 'index'])   ->name('index');
        Route::post  ('/upload',              [DocumentController::class, 'upload'])  ->name('upload');
        Route::delete('/{document}',          [DocumentController::class, 'destroy']) ->name('destroy');
        Route::post  ('/{document}/approve',  [DocumentController::class, 'approve']) ->name('approve');
        Route::post  ('/{document}/reject',   [DocumentController::class, 'reject'])  ->name('reject');
        Route::get   ('/{document}/download', [DocumentController::class, 'download'])->name('download');
    });

    // ── Notifications (tous rôles) ───────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get  ('/',          [NotificationController::class, 'index'])      ->name('index');
        Route::patch('/read-all',  [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::patch('/{id}/read', [NotificationController::class, 'markRead'])   ->name('read');
    });

    // ── Messagerie (tous rôles) ──────────────────────────────────
    Route::prefix('conversations')->name('conversations.')->group(function () {
        Route::get  ('/',                          [MessageController::class, 'index'])                 ->name('index');
        Route::post ('/mission/{mission}',         [MessageController::class, 'findOrCreateForMission'])->name('mission');
        Route::get  ('/{conversation}/messages',   [MessageController::class, 'messages'])              ->name('messages');
        Route::post ('/{conversation}/messages',   [MessageController::class, 'send'])                  ->name('send');
        Route::post ('/{conversation}/attachment', [MessageController::class, 'sendAttachment'])        ->name('attachment');
        Route::patch('/{conversation}/read',       [MessageController::class, 'read'])                  ->name('read');
    });

    Route::get('/unread-messages', [MessageController::class, 'unreadSummary'])->name('unread-messages');

    // ── Messagerie — pages dédiées (split view) ─────────────────
    Route::get('/client/messages', function (Illuminate\Http\Request $request) {
        $user = Auth::user();
        return view('pages.back.client.messages', [
            'active'             => 'messages',
            'user'               => $user,
            'openConversationId' => (int) $request->query('conversation', 0) ?: null,
            'routes' => [
                'conversations_index'    => '/conversations',
                'conversations_messages' => '/conversations/{id}/messages',
                'conversations_send'     => '/conversations/{id}/messages',
                'conversations_attach'   => '/conversations/{id}/attachment',
                'conversations_read'     => '/conversations/{id}/read',
                'notifications'          => '/notifications',
                'notifications_all'      => '/notifications/read-all',
                'profile_photo'          => '/profile/photo',
            ],
        ]);
    })->middleware('role:client')->name('client.messages');

    Route::get('/contractor/messages', function (Illuminate\Http\Request $request) {
        $user = Auth::user();
        return view('pages.back.contractor.messages', [
            'active'             => 'messages',
            'user'               => $user,
            'openConversationId' => (int) $request->query('conversation', 0) ?: null,
            'routes' => [
                'conversations_index'    => '/conversations',
                'conversations_messages' => '/conversations/{id}/messages',
                'conversations_send'     => '/conversations/{id}/messages',
                'conversations_attach'   => '/conversations/{id}/attachment',
                'conversations_read'     => '/conversations/{id}/read',
                'notifications'          => '/notifications',
                'notifications_all'      => '/notifications/read-all',
                'profile_photo'          => '/profile/photo',
            ],
        ]);
    })->middleware('role:contractor')->name('contractor.messages');

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {

        // ── Dashboard ────────────────────────────────────────────
        Route::get('/dashboard', function () {
            $user = Auth::user();
            return view('pages.back.admin.dashboard', [
                'active' => 'dashboard',
                'user'   => $user,
                'routes' => [
                    // Données
                    'missions_index'     => route('admin.missions.index'),
                    'contractors_index'  => route('admin.contractors.index'),
                    'clients_index'      => route('admin.clients.index'),
                    'talents_index'      => route('admin.talents.index'),
                    'disputes_index'     => route('admin.disputes.index'),
                    'tickets_index'      => route('admin.consulting.index'),
                    'tenders_index'      => route('admin.tenders.admin-index'),
                    'validation_index'   => route('admin.documents.dossiers'),
                    // Navigation
                    'missions_page'      => route('admin.missions'),
                    'contractors_page'   => route('admin.contractors.page'),
                    'clients_page'       => route('admin.clients.page'),
                    'talents_page'       => route('admin.talents.page'),
                    'disputes_page'      => route('admin.disputes.page'),
                    'consulting_page'    => route('admin.consulting.page'),
                    'tenders_page'       => route('admin.tenders.admin-page'),
                    'services_page'      => route('admin.services.page'),
                    'validation_page'    => route('admin.validation'),
                    'accreditation_page' => route('admin.accreditation'),
                    // Notifications
                    'notifications'      => route('notifications.index'),
                    'notifications_all'  => route('notifications.read-all'),
                ],
            ]);
        })->name('dashboard');

        // ── Messages admin ───────────────────────────────────────
        Route::get('/messages', function () {
            $user = Auth::user();
            return view('pages.back.admin.messages', [
                'active' => 'messages',
                'user'   => $user,
                'routes' => [
                    'conversations_index'    => '/conversations',
                    'conversations_messages' => '/conversations/{id}/messages',
                    'conversations_send'     => '/conversations/{id}/messages',
                    'conversations_attach'   => '/conversations/{id}/attachment',
                    'conversations_read'     => '/conversations/{id}/read',
                    'notifications'          => '/notifications',
                    'notifications_all'      => '/notifications/read-all',
                    'profile_photo'          => '/profile/photo',
                ],
            ]);
        })->name('messages');

        // ── Paramètres admin ─────────────────────────────────────
        Route::get('/parameters', function () {
            $user = Auth::user();
            return view('pages.back.admin.parameters', [
                'active' => 'parameters',
                'user'   => $user,
                'routes' => [
                    'update_password'   => route('profile.password'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('parameters');

        // ── Vues ─────────────────────────────────────────────────
        Route::get('/missions',      [AdminController::class, 'missions'])      ->name('missions');
        Route::get('/accreditation', [AdminController::class, 'accreditation']) ->name('accreditation');
        Route::get('/validation',    fn() => view('pages.back.admin.validation_documents'))->name('validation');

        // ── Revenus & Finances ────────────────────────────────────
        Route::get('/revenus', function () {
            $user = Auth::user();
            return view('pages.back.admin.revenus', [
                'active' => 'revenus',
                'user'   => $user,
                'routes' => [
                    'revenus_index'     => route('admin.revenus.data'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('revenus');

        Route::get('/revenus/data', function (\Illuminate\Http\Request $request) {
            $period = $request->get('period', 'month');
            $role   = $request->get('role', '');

            $dateFrom = match($period) {
                'week'    => now()->startOfWeek(),
                'month'   => now()->startOfMonth(),
                'quarter' => now()->startOfQuarter(),
                'year'    => now()->startOfYear(),
                default   => now()->startOfMonth(),
            };

            $missions = \App\Models\Mission::whereIn('status', ['completed', 'closed'])
                ->where('updated_at', '>=', $dateFrom)
                ->with(['client.user', 'contractor.user'])
                ->latest()
                ->get()
                ->map(fn($m) => [
                    'id'              => $m->id,
                    'service'         => $m->service,
                    'client_name'     => $m->client?->user?->name     ?? '—',
                    'contractor_name' => $m->contractor?->user?->name ?? '—',
                    'total_amount'    => $m->total_amount,
                    'status'          => $m->status,
                    'completed_at'    => $m->updated_at,
                    'created_at'      => $m->created_at,
                ]);

            $volumeTotal        = $missions->sum('total_amount');
            $count              = $missions->count();
            $commissionsPercues = round($volumeTotal * 0.10);
            $versesPrestataires = round($volumeTotal * 0.90);
            $enAttente          = \App\Models\Mission::where('status', 'active')->sum('total_amount');

            return response()->json([
                'missions'            => $missions,
                'volume_total'        => $volumeTotal,
                'commissions_percues' => $commissionsPercues,
                'missions_cloturees'  => $count,
                'panier_moyen'        => $count > 0 ? round($volumeTotal / $count) : 0,
                'en_attente'          => $enAttente,
                'verses_prestataires' => $versesPrestataires,
            ]);
        })->name('revenus.data');

        Route::get  ('/documents/dossiers',          [DocumentController::class, 'dossiers'])        ->name('documents.dossiers');
        Route::patch('/documents/{document}/status', [DocumentController::class, 'updateStatus'])    ->name('documents.status');
        Route::patch('/users/{user}/status',         [DocumentController::class, 'updateUserStatus'])->name('users.status');

        // ── Contractors ──────────────────────────────────────────
        Route::get   ('/contractors',                              [ContractorController::class, 'adminIndex'])          ->name('contractors.index');
        Route::get   ('/contractors/pending',                      [ContractorController::class, 'adminPending'])        ->name('contractors.pending');
        Route::get   ('/contractors/available',                    [ContractorController::class, 'adminAvailable'])      ->name('contractors.available');
        Route::patch ('/contractors/{contractor}/status',          [ContractorController::class, 'updateStatut'])        ->name('contractors.status');
        Route::patch ('/contractors/{contractor}/accreditation',   [ContractorController::class, 'updateAccreditation']) ->name('contractors.accreditation');

        // ── Missions ─────────────────────────────────────────────
        Route::get  ('/missions/list',              [MissionController::class, 'adminIndex'])   ->name('missions.index');
        Route::get  ('/missions/{mission}',         [MissionController::class, 'adminShow'])    ->name('missions.show');
        Route::patch('/missions/{mission}/status',  [MissionController::class, 'adminStatus'])  ->name('missions.status');
        Route::post ('/missions/{mission}/propose', [MissionController::class, 'adminPropose']) ->name('missions.propose');

        // ── Talents ──────────────────────────────────────────────
        Route::get('/talents/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.talents', [
                'active' => 'talents',
                'auth'   => ['id' => $user->id, 'role' => $user->role, 'name' => $user->name],
                'routes' => [
                    'talents_index'          => route('admin.talents.index'),
                    'talents_status'         => url('/admin/talents/{application}/status'),
                    'notifications_index'    => route('notifications.index'),
                    'notifications_read_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('talents.page');
        Route::get  ('/talents',                      [TalentController::class, 'adminIndex'])        ->name('talents.index');
        Route::patch('/talents/{application}/status', [TalentController::class, 'adminUpdateStatus']) ->name('talents.status');

        // ── Contractors (page admin) ──────────────────────────────
        Route::get('/contractors/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.contractors', [
                'active' => 'contractors',
                'auth'   => ['id' => $user->id, 'role' => $user->role, 'name' => $user->name],
                'routes' => [
                    'contractors_index'         => route('admin.contractors.index'),
                    'contractors_status'        => url('/admin/contractors/{contractor}/status'),
                    'contractors_accreditation' => url('/admin/contractors/{contractor}/accreditation'),
                    'missions_pending'          => route('admin.missions.index') . '?status=pending',
                    'missions_propose'          => url('/admin/missions/{mission}/propose'),
                    'notifications_index'       => route('notifications.index'),
                    'notifications_read_all'    => route('notifications.read-all'),
                ],
            ]);
        })->name('contractors.page');

        // ── Clients (page admin) ──────────────────────────────────
        Route::get('/clients/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.clients', [
                'active' => 'clients',
                'auth'   => ['id' => $user->id, 'role' => $user->role, 'name' => $user->name],
                'routes' => [
                    'clients_index'          => route('admin.clients.index'),
                    'clients_status'         => url('/admin/users/{user}/status'),
                    'notifications_index'    => route('notifications.index'),
                    'notifications_read_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('clients.page');

        Route::get('/clients', function () {
            $clients = \App\Models\User::where('role', 'client')
                ->with(['client'])
                ->latest()
                ->get()
                ->map(function ($u) {
                    $clientId = $u->client?->id;

                    $missionsCount     = $clientId ? \App\Models\Mission::where('client_id', $clientId)->count() : 0;
                    $missionsCompleted = $clientId ? \App\Models\Mission::where('client_id', $clientId)->whereIn('status', ['completed', 'closed'])->count() : 0;
                    $totalSpent        = $clientId ? \App\Models\Mission::where('client_id', $clientId)->where('status', 'closed')->whereNotNull('total_amount')->sum('total_amount') : 0;
                    $recentMissions    = $clientId ? \App\Models\Mission::where('client_id', $clientId)->latest()->take(3)->get()->map(fn($m) => [
                        'id'           => $m->id,
                        'service'      => $m->service,
                        'status_label' => $m->status_label,
                        'created_at'   => $m->created_at?->format('d/m/Y'),
                    ])->toArray() : [];

                    return [
                        'id'                 => $u->id,
                        'name'               => $u->name,
                        'email'              => $u->email,
                        'status'             => $u->status ?? 'active',
                        'phone'              => $u->client?->phone        ?? null,
                        'city'               => $u->client?->city         ?? null,
                        'address'            => $u->client?->address      ?? null,
                        'account_type'       => $u->client?->account_type ?? 'individual',
                        'company_name'       => $u->client?->company_name ?? null,
                        'missions_count'     => $missionsCount,
                        'missions_completed' => $missionsCompleted,
                        'total_spent'        => $totalSpent > 0 ? $totalSpent : null,
                        'created_at_label'   => $u->created_at?->format('d/m/Y'),
                        'recent_missions'    => $recentMissions,
                    ];
                });
            return response()->json($clients);
        })->name('clients.index');

        Route::patch('/users/{user}/status', function (\Illuminate\Http\Request $request, \App\Models\User $user) {
            $request->validate(['status' => 'required|in:active,suspended']);
            $user->update(['status' => $request->status]);
            return response()->json(['success' => true, 'status' => $request->status]);
        })->name('users.status');

        // ── Appels d'offres (page admin) ─────────────────────────
        Route::get('/tenders/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.market', [
                'active' => 'tenders',
                'user'   => $user,
                'routes' => [
                    'tenders_index'  => route('admin.tenders.admin-index'),
                    'tenders_status' => url('/admin/tenders/{id}/status'),
                ],
            ]);
        })->name('tenders.admin-page');
        Route::get  ('/tenders',                 [TenderController::class, 'adminIndex'])        ->name('tenders.admin-index');
        Route::patch('/tenders/{tender}/status', [TenderController::class, 'adminUpdateStatus']) ->name('tenders.status');

        // ── Services (catalogue) ──────────────────────────────────
        Route::get('/services/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.services', [
                'active' => 'services',
                'user'   => $user,
                'routes' => [
                    'services_index'     => route('admin.services.index'),
                    'services_store'     => route('admin.services.store'),
                    'services_update'    => url('/admin/services/{service}'),
                    'services_status'    => url('/admin/services/{service}/status'),
                    'notifications'      => route('notifications.index'),
                    'notifications_read' => url('/notifications/{id}/read'),
                    'notifications_all'  => route('notifications.read-all'),
                ],
            ]);
        })->name('services.page');
        Route::get   ('/services',                  [ServiceController::class, 'adminIndex'])   ->name('services.index');
        Route::post  ('/services',                  [ServiceController::class, 'store'])        ->name('services.store');
        Route::put   ('/services/{service}',        [ServiceController::class, 'update'])       ->name('services.update');
        Route::patch ('/services/{service}/status', [ServiceController::class, 'updateStatus']) ->name('services.status');
        Route::delete('/services/{service}',        [ServiceController::class, 'destroy'])      ->name('services.destroy');

        // ── Allo Conseils ─────────────────────────────────────────
        Route::get('/consulting/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.consulting', [
                'active' => 'consulting',
                'user'   => $user,
            ]);
        })->name('consulting.page');
        Route::get  ('/consulting',                       [ConsultingController::class, 'adminIndex'])        ->name('consulting.index');
        Route::get  ('/consulting/{ticket}/messages',     [ConsultingController::class, 'adminMessages'])     ->name('consulting.messages');
        Route::post ('/consulting/{ticket}/reply',        [ConsultingController::class, 'adminReply'])        ->name('consulting.reply');
        Route::patch('/consulting/{ticket}/status',       [ConsultingController::class, 'adminUpdateStatus']) ->name('consulting.status');
        Route::patch('/consulting/{ticket}/assign',       [ConsultingController::class, 'adminAssign'])       ->name('consulting.assign');
        Route::patch('/consulting/{ticket}/note',         [ConsultingController::class, 'adminNote'])         ->name('consulting.note');

        // ── Litiges ───────────────────────────────────────────────
        Route::get('/disputes/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.disputes', [
                'active' => 'disputes',
                'user'   => $user,
                'routes' => [
                    'disputes_index'      => route('admin.disputes.index'),
                    'disputes_show'       => url('/admin/disputes/{dispute}'),
                    'disputes_store'      => route('admin.disputes.store'),
                    'disputes_message'    => url('/admin/disputes/{dispute}/message'),
                    'disputes_attachment' => url('/admin/disputes/{dispute}/attachment'),
                    'disputes_verdict'    => url('/admin/disputes/{dispute}/verdict'),
                    'disputes_close'      => url('/admin/disputes/{dispute}/close'),
                    'disputes_missions'   => route('admin.disputes.missions'),
                    'notifications'       => route('notifications.index'),
                    'notifications_all'   => route('notifications.read-all'),
                ],
            ]);
        })->name('disputes.page');
        // IMPORTANT : routes statiques AVANT les routes avec {dispute}
        Route::get  ('/disputes',                      [DisputeController::class, 'adminIndex'])            ->name('disputes.index');
        Route::post ('/disputes',                      [DisputeController::class, 'adminStore'])            ->name('disputes.store');
        Route::get  ('/disputes/missions',             [DisputeController::class, 'adminMissions'])         ->name('disputes.missions');
        Route::get  ('/disputes/{dispute}',            [DisputeController::class, 'adminShow'])             ->name('disputes.show');
        Route::post ('/disputes/{dispute}/message',    [DisputeController::class, 'adminSendMessage'])      ->name('disputes.message');
        Route::post ('/disputes/{dispute}/attachment', [DisputeController::class, 'adminUploadAttachment']) ->name('disputes.attachment');
        Route::post ('/disputes/{dispute}/verdict',    [DisputeController::class, 'adminVerdict'])          ->name('disputes.verdict');
        Route::post ('/disputes/{dispute}/close',      [DisputeController::class, 'adminClose'])            ->name('disputes.close');
    });

    // ══════════════════════════════════════════════════════════════
    // CLIENT
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {

        // ── Dashboard ────────────────────────────────────────────
        Route::get('/dashboard', function () {
            $user        = Auth::user();
            $docProgress = $user->documentProgress();
            return view('pages.back.client.dashboard', [
                'active'             => 'dashboard',
                'user'               => $user,
                'docProgressData'    => $docProgress,
                'userStatus'         => $user->status,
                'completedMissions'  => $user->client
                    ? \App\Models\Mission::where('client_id', $user->client->id)->where('status', 'closed')->count()
                    : 0,
                'routes'          => [
                    'missions_index'           => route('client.missions.index'),
                    'missions_page'            => route('client.missions.page'),
                    'missions_store'           => route('client.missions.store'),
                    'missions_status'          => '/client/missions/{id}/status',
                    'documents_upload'         => route('documents.upload'),
                    'documents_index'          => route('documents.index'),
                    'dossier_page'             => route('client.dossier'),
                    'notifications'            => route('notifications.index'),
                    'notifications_read'       => '/notifications/{id}/read',
                    'notifications_all'        => route('notifications.read-all'),
                    'messages_page'            => route('client.messages'),
                    'paiements_page'           => route('client.paiements'),
                    'parameters_page'          => route('client.parameters'),
                    'conversations'            => '/conversations',
                    'conversations_mission'    => '/conversations/mission/{id}',
                    'conversations_messages'   => '/conversations/{id}/messages',
                    'conversations_send'       => '/conversations/{id}/messages',
                    'conversations_attachment' => '/conversations/{id}/attachment',
                    'conversations_read'       => '/conversations/{id}/read',
                    'unread_summary'           => '/unread-messages',
                ],
            ]);
        })->name('dashboard');

        // ── Dossier ───────────────────────────────────────────────
        Route::get('/dossier', function () {
            $user = Auth::user();
            return view('pages.back.client.dossier', [
                'active' => 'dossier',
                'user'   => $user,
                'routes' => [
                    'documents_index'   => route('documents.index'),
                    'documents_upload'  => route('documents.upload'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('dossier');

        // ── Paiements client ──────────────────────────────────────
        Route::get('/paiements', function () {
            $user = Auth::user();
            return view('pages.back.client.paiements', [
                'active' => 'paiements',
                'user'   => $user,
                'routes' => [
                    'paiements_index'   => route('client.paiements.index'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('paiements');

        // ── API paiements client (données JSON) ───────────────────
        Route::get('/paiements/data', function (\Illuminate\Http\Request $request) {
            $user    = Auth::user();
            $client  = \App\Models\Client::where('user_id', $user->id)->first();
            $period  = $request->get('period', 'month');

            $dateFrom = match($period) {
                'week'    => now()->startOfWeek(),
                'month'   => now()->startOfMonth(),
                'quarter' => now()->startOfQuarter(),
                'year'    => now()->startOfYear(),
                default   => now()->startOfMonth(),
            };

            $missions = \App\Models\Mission::where('client_id', $client?->id)
                ->whereIn('status', ['completed', 'closed'])
                ->where('updated_at', '>=', $dateFrom)
                ->with('contractor.user')
                ->latest()
                ->get()
                ->map(fn($m) => [
                    'id'              => $m->id,
                    'service'         => $m->service,
                    'contractor_name' => $m->contractor?->user?->name ?? '—',
                    'total_amount'    => $m->total_amount,
                    'status'          => $m->status,
                    'completed_at'    => $m->updated_at,
                    'created_at'      => $m->created_at,
                ]);

            $total     = $missions->sum('total_amount');
            $count     = $missions->count();
            $enAttente = \App\Models\Mission::where('client_id', $client?->id)
                ->where('status', 'active')
                ->sum('total_amount');

            return response()->json([
                'missions'            => $missions,
                'total_depenses'      => $total,
                'missions_terminees'  => $count,
                'depense_moyenne'     => $count > 0 ? round($total / $count) : 0,
                'en_attente'          => $enAttente,
            ]);
        })->name('paiements.index');

        // ── Paramètres client ─────────────────────────────────────
        Route::get('/parameters', function () {
            $user = Auth::user();
            return view('pages.back.client.parameters', [
                'active' => 'parameters',
                'user'   => $user,
                'routes' => [
                    'update_password'   => route('profile.password'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('parameters');

        // ── Missions ──────────────────────────────────────────────
        Route::get('/missions/page', function () {
            $user        = Auth::user();
            $client      = \App\Models\Client::where('user_id', $user->id)->first();
            $docProgress = $user->documentProgress();
            $routes = [
                'missions_index'           => route('client.missions.index'),
                'missions_store'           => route('client.missions.store'),
                'missions_status'          => '/client/missions/{id}/status',
                'reviews_store'            => '/client/missions',
                'notifications'            => '/notifications',
                'notifications_read'       => '/notifications/{id}/read',
                'notifications_all'        => '/notifications/read-all',
                'conversations'            => '/conversations',
                'conversations_mission'    => '/conversations/mission/{id}',
                'conversations_messages'   => '/conversations/{id}/messages',
                'conversations_send'       => '/conversations/{id}/messages',
                'conversations_attachment' => '/conversations/{id}/attachment',
                'conversations_read'       => '/conversations/{id}/read',
                'unread_summary'           => '/unread-messages',
                'dossier_page'             => route('client.dossier'),
            ];
            return view('pages.back.client.missions', compact('user', 'client', 'routes', 'docProgress'));
        })->name('missions.page');

        Route::get  ('/missions',                  [MissionController::class, 'index'])       ->name('missions.index');
        Route::post ('/missions',                  [MissionController::class, 'store'])       ->name('missions.store');
        Route::get  ('/missions/{mission}',        [MissionController::class, 'show'])        ->name('missions.show');
        Route::patch('/missions/{mission}/status', [MissionController::class, 'updateStatus'])->name('missions.status');

        // ── Avis prestataire ──────────────────────────────────────
        Route::post('/missions/{mission}/review', function (\Illuminate\Http\Request $request, \App\Models\Mission $mission) {
            $request->validate([
                'rating'  => 'required|integer|between:1,5',
                'comment' => 'nullable|string|max:500',
            ]);

            // Vérifier que c'est bien le client de cette mission
            $user   = Auth::user();
            $client = \App\Models\Client::where('user_id', $user->id)->firstOrFail();
            if ($mission->client_id !== $client->id) {
                abort(403);
            }

            // Mettre à jour la note moyenne du prestataire
            $contractor = $mission->contractor;
            if ($contractor) {
                $totalReviews  = $contractor->reviews_count ?? 0;
                $currentRating = $contractor->average_rating ?? 0;
                $newCount      = $totalReviews + 1;
                $newRating     = round((($currentRating * $totalReviews) + $request->rating) / $newCount, 2);
                $contractor->update([
                    'average_rating' => $newRating,
                    'reviews_count'  => $newCount,
                ]);
            }

            return response()->json(['success' => true]);
        })->name('missions.review');

        // ── Appels d'offres — publication + mes AO (client uniquement) ────
        Route::post('/tenders',      [TenderController::class, 'store'])     ->name('tenders.store');
        Route::get ('/tenders/mine', [TenderController::class, 'myTenders']) ->name('tenders.mine');
    });

    // ══════════════════════════════════════════════════════════════
    // CONTRACTOR (Prestataire)
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:contractor')->prefix('contractor')->name('contractor.')->group(function () {

        // ── Dashboard ────────────────────────────────────────────
        Route::get('/dashboard', [ContractorController::class, 'dashboardIndex'])->name('dashboard');

        // ── Dossier ───────────────────────────────────────────────
        Route::get('/dossier', function () {
            $user        = Auth::user();
            $docProgress = $user->documentProgress();
            return view('pages.back.contractor.dossier', [
                'active' => 'dossier',
                'user'   => $user,
                'routes' => [
                    'documents_index'   => route('documents.index'),
                    'documents_upload'  => route('documents.upload'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('dossier');

        // ── Revenus ───────────────────────────────────────────────
        Route::get('/revenus', function () {
            $user = Auth::user();
            return view('pages.back.contractor.revenus', [
                'active' => 'revenus',
                'user'   => $user,
                'routes' => [
                    'revenus_index'     => route('contractor.revenus.index'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('revenus');

        // ── API revenus (données JSON pour le composant) ──────────
        Route::get('/revenus/data', function (\Illuminate\Http\Request $request) {
            $user        = Auth::user();
            $contractor  = $user->contractor;
            $period      = $request->get('period', 'month');

            $dateFrom = match($period) {
                'week'    => now()->startOfWeek(),
                'month'   => now()->startOfMonth(),
                'quarter' => now()->startOfQuarter(),
                'year'    => now()->startOfYear(),
                default   => now()->startOfMonth(),
            };

            $missions = \App\Models\Mission::where('contractor_id', $contractor?->id)
                ->whereIn('status', ['completed', 'closed'])
                ->where('updated_at', '>=', $dateFrom)
                ->with('client.user')
                ->latest()
                ->get()
                ->map(fn($m) => [
                    'id'           => $m->id,
                    'service'      => $m->service,
                    'client_name'  => $m->client?->user?->name ?? '—',
                    'total_amount' => $m->total_amount,
                    'status'       => $m->status,
                    'completed_at' => $m->updated_at,
                    'created_at'   => $m->created_at,
                ]);

            $total     = $missions->sum('total_amount');
            $count     = $missions->count();
            $enAttente = \App\Models\Mission::where('contractor_id', $contractor?->id)
                ->where('status', 'active')
                ->sum('total_amount');

            return response()->json([
                'missions'            => $missions,
                'total_revenus'       => $total,
                'missions_terminees'  => $count,
                'revenu_moyen'        => $count > 0 ? round($total / $count) : 0,
                'en_attente'          => $enAttente,
            ]);
        })->name('revenus.index');

        // ── Accréditation contractor ─────────────────────────────────
        Route::get('/accreditation', function () {
            $user       = Auth::user();
            $contractor = $user->contractor;
            return view('pages.back.contractor.accreditation', [
                'active'     => 'accreditation',
                'user'       => $user,
                'contractor' => $contractor,
                'routes'     => [
                    'accreditation_request' => route('contractor.accreditation.request'),
                    'dossier_page'          => route('contractor.dossier'),
                    'notifications'         => route('notifications.index'),
                    'notifications_read'    => url('/notifications/{id}/read'),
                    'notifications_all'     => route('notifications.read-all'),
                ],
            ]);
        })->name('accreditation');

        // ── API demande accréditation entreprise ──────────────────
        Route::post('/accreditation/request', function (\Illuminate\Http\Request $request) {
            $user    = Auth::user();
            $message = $request->input('message', '');
            \App\Models\User::where('role', 'admin')->each(function ($admin) use ($user, $message) {
                $admin->notifications()->create([
                    'title' => '🏅 Demande accréditation Entreprise',
                    'body'  => $user->name . ' demande l\'accréditation Entreprise.' . ($message ? ' Message : ' . $message : ''),
                    'url'   => route('admin.accreditation'),
                ]);
            });
            return response()->json(['success' => true]);
        })->name('accreditation.request');

        // ── Paramètres contractor ─────────────────────────────────
        Route::get('/parameters', function () {
            $user = Auth::user();
            return view('pages.back.contractor.parameters', [
                'active' => 'parameters',
                'user'   => $user,
                'routes' => [
                    'update_password'   => route('profile.password'),
                    'notifications'     => route('notifications.index'),
                    'notifications_all' => route('notifications.read-all'),
                ],
            ]);
        })->name('parameters');

        // ── Profil ────────────────────────────────────────────────
        Route::get  ('/profil',        [ContractorController::class, 'show'])               ->name('profil.show');
        Route::post ('/profil',        [ContractorController::class, 'store'])              ->name('profil.store');
        Route::put  ('/profil',        [ContractorController::class, 'update'])             ->name('profil.update');
        Route::post ('/photo',         [ContractorController::class, 'updatePhoto'])        ->name('photo');
        Route::patch('/disponibilite', [ContractorController::class, 'updateDisponibilite'])->name('disponibilite');
        Route::patch('/position',      [ContractorController::class, 'updatePosition'])     ->name('position');

        // ── Missions ──────────────────────────────────────────────
        Route::get('/missions/page', function () {
            $user = Auth::user()->load('contractor');
            return view('pages.back.contractor.missions', ['user' => $user]);
        })->name('missions.page');

        Route::get  ('/missions',                  [MissionController::class, 'index'])        ->name('missions.index');
        Route::get  ('/missions/{mission}',        [MissionController::class, 'show'])         ->name('missions.show');
        Route::patch('/missions/{mission}/status', [MissionController::class, 'updateStatus']) ->name('missions.status');

        // ── Devis ─────────────────────────────────────────────────
        Route::post('/missions/{mission}/quote', [MissionQuoteController::class, 'upsert'])->name('missions.quote.store');
    });

    // ══════════════════════════════════════════════════════════════
    // TALENT
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:talent')->prefix('talent')->name('talent.')->group(function () {
        Route::get('/dashboard', fn() => view('pages.back.talent.dashboard'))->name('dashboard');
    });

});

require __DIR__ . '/auth.php';