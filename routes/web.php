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
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
// PAGES PUBLIQUES (front)
// ══════════════════════════════════════════════════════════════════

Route::get('/',           fn() => view('pages.front.home'))->name('home');
Route::get('/consulting', fn() => view('pages.front.consulting'))->name('consulting');
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
    Route::get('/',       [TenderController::class, 'index']) ->name('index');
    Route::get('/stats',  [TenderController::class, 'stats']) ->name('stats');
});
Route::get('/cgu',        fn() => view('pages.front.cgu'))->name('cgu');
Route::get('/policy',     fn() => view('pages.front.policy'))->name('policy');

// ── Inscription ──────────────────────────────────────────────────
Route::get ('/register/client',           fn() => view('pages.front.registerClient'))     ->name('register.client');
Route::get ('/register/contractor',       fn() => view('pages.front.registerContractor')) ->name('register.contractor');
Route::post('/register/client/store',     [UserController::class, 'storeClient'])         ->name('register.client.store');
Route::post('/register/contractor/store', [UserController::class, 'storeContractor'])     ->name('register.contractor.store');

// ══════════════════════════════════════════════════════════════════
// AUTHENTIFIÉ
// ══════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {

    // ── Appels d'offres — candidature & mes candidatures (tous rôles connectés) ──
    Route::prefix('tenders')->name('tenders.')->group(function () {
        Route::post('/{tender}/apply',    [TenderController::class, 'apply'])          ->name('apply');
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
    Route::get   ('/profile', [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch ('/profile', [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
        Route::get  ('/',                                  [MessageController::class, 'index'])                ->name('index');
        Route::post ('/mission/{mission}',                 [MessageController::class, 'findOrCreateForMission'])->name('mission');
        Route::get  ('/{conversation}/messages',           [MessageController::class, 'messages'])             ->name('messages');
        Route::post ('/{conversation}/messages',           [MessageController::class, 'send'])                 ->name('send');
        Route::post ('/{conversation}/attachment',         [MessageController::class, 'sendAttachment'])       ->name('attachment');
        Route::patch('/{conversation}/read',               [MessageController::class, 'read'])                 ->name('read');
    });

    // Vues messagerie dédiées
    Route::get('/contractor/messagerie', fn() => view('pages.back.contractor.messaging'))->middleware('role:contractor')->name('contractor.messaging');
    Route::get('/client/messagerie',     fn() => view('pages.back.client.messaging'))    ->middleware('role:client')    ->name('client.messaging');

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {

        // ── Vues ────────────────────────────────────────────────
        Route::get('/dashboard',      [AdminController::class, 'dashboard'])     ->name('dashboard');
        Route::get('/missions',       [AdminController::class, 'missions'])      ->name('missions');
        Route::get('/accreditation',  [AdminController::class, 'accreditation']) ->name('accreditation');
        Route::get('/validation',     fn() => view('pages.back.admin.validation_documents'))->name('validation');

        // ── Stats (AdminDashboardComponent) ─────────────────────
        Route::get('/stats', [AdminController::class, 'stats'])->name('stats');

        // ── Documents ────────────────────────────────────────────
        Route::get  ('/documents/dossiers',           [DocumentController::class, 'dossiers'])       ->name('documents.dossiers');
        Route::patch('/documents/{document}/status',  [DocumentController::class, 'updateStatus'])   ->name('documents.status');
        Route::patch('/users/{user}/status',          [DocumentController::class, 'updateUserStatus'])->name('users.status');

        // ── Contractors ──────────────────────────────────────────
        Route::get('/contractors',                              [ContractorController::class, 'adminIndex'])          ->name('contractors.index');
        Route::get('/contractors/pending',                      [ContractorController::class, 'adminPending'])        ->name('contractors.pending');
        Route::get('/contractors/available',                    [ContractorController::class, 'adminAvailable'])      ->name('contractors.available');
        Route::patch('/contractors/{contractor}/status',        [ContractorController::class, 'updateStatut'])        ->name('contractors.status');
        Route::patch('/contractors/{contractor}/accreditation', [ContractorController::class, 'updateAccreditation']) ->name('contractors.accreditation');

        // ── Missions ─────────────────────────────────────────────
        Route::get  ('/missions/list',               [MissionController::class, 'adminIndex'])   ->name('missions.index');
        Route::get  ('/missions/{mission}',          [MissionController::class, 'adminShow'])    ->name('missions.show');
        Route::patch('/missions/{mission}/status',   [MissionController::class, 'adminStatus'])  ->name('missions.status');
        Route::post ('/missions/{mission}/propose',  [MissionController::class, 'adminPropose']) ->name('missions.propose');

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
                    'contractors_index'       => route('admin.contractors.index'),
                    'contractors_status'      => url('/admin/contractors/{contractor}/status'),
                    'contractors_accreditation' => url('/admin/contractors/{contractor}/accreditation'),
                    'missions_pending'        => route('admin.missions.index') . '?status=pending',
                    'missions_propose'        => url('/admin/missions/{mission}/propose'),
                    'notifications_index'     => route('notifications.index'),
                    'notifications_read_all'  => route('notifications.read-all'),
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
        Route::get('/tenders/page', function () {
            $user = Auth::user();
            return view('pages.back.admin.market', [
                'active' => 'tenders',
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
        })->name('tenders.page');
        Route::get  ('/tenders',                     [TenderController::class, 'adminIndex'])        ->name('tenders.index');
        Route::patch('/tenders/{tender}/status',     [TenderController::class, 'adminUpdateStatus']) ->name('tenders.status');
    });

    // ══════════════════════════════════════════════════════════════
    // CLIENT
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {

        Route::get('/dashboard', [ClientController::class, 'index'])->name('dashboard');

        Route::get('/missions/page', function () {
            $user   = Auth::user();
            $client = \App\Models\Client::where('user_id', $user->id)->first();
            return view('pages.back.client.missions', compact('user', 'client'));
        })->name('missions.page');

        Route::get  ('/missions',                  [MissionController::class, 'index'])       ->name('missions.index');
        Route::post ('/missions',                  [MissionController::class, 'store'])       ->name('missions.store');
        Route::get  ('/missions/{mission}',        [MissionController::class, 'show'])        ->name('missions.show');
        Route::patch('/missions/{mission}/status', [MissionController::class, 'updateStatus'])->name('missions.status');

        // ── Appels d'offres — publication + mes AO (client uniquement) ────
        Route::post('/tenders',      [TenderController::class, 'store'])     ->name('tenders.store');
        Route::get ('/tenders/mine', [TenderController::class, 'myTenders']) ->name('tenders.mine');
    });

    // ══════════════════════════════════════════════════════════════
    // CONTRACTOR (Prestataire)
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:contractor')->prefix('contractor')->name('contractor.')->group(function () {

        Route::get  ('/dashboard',     [ContractorController::class, 'dashboardIndex'])     ->name('dashboard');
        Route::get  ('/profil',        [ContractorController::class, 'show'])               ->name('profil.show');
        Route::post ('/profil',        [ContractorController::class, 'store'])              ->name('profil.store');
        Route::put  ('/profil',        [ContractorController::class, 'update'])             ->name('profil.update');
        Route::post ('/photo',         [ContractorController::class, 'updatePhoto'])        ->name('photo');
        Route::patch('/disponibilite', [ContractorController::class, 'updateDisponibilite'])->name('disponibilite');
        Route::patch('/position',      [ContractorController::class, 'updatePosition'])     ->name('position');

        // Page missions dédiée (ContractorMissionComponent)
        Route::get('/missions/page', function () {
            $user = Auth::user()->load('contractor');
            return view('pages.back.contractor.missions', ['user' => $user]);
        })->name('missions.page');

        // Missions API
        Route::get  ('/missions',                  [MissionController::class, 'index'])             ->name('missions.index');
        Route::get  ('/missions/{mission}',        [MissionController::class, 'show'])              ->name('missions.show');
        Route::patch('/missions/{mission}/status', [MissionController::class, 'updateStatus'])      ->name('missions.status');

        // ── Devis ────────────────────────────────────────────────
        Route::post('/missions/{mission}/quote',   [MissionQuoteController::class, 'upsert'])       ->name('missions.quote.store');
    });

    // ══════════════════════════════════════════════════════════════
    // TALENT
    // ══════════════════════════════════════════════════════════════

    Route::middleware('role:talent')->prefix('talent')->name('talent.')->group(function () {
        Route::get('/dashboard', fn() => view('pages.back.talent.dashboard'))->name('dashboard');
    });

});

require __DIR__ . '/auth.php';  