<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Document;
use App\Models\Mission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // VUES
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /admin/dashboard  →  admin.dashboard
     */
    public function dashboard()
    {
        $user  = Auth::user();
        $stats = $this->buildStats();

        return view('pages.back.admin.dashboard', compact('user', 'stats'));
    }

    /**
     * GET /admin/missions  →  admin.missions
     */
    public function missions()
    {
        $user = Auth::user();
        return view('pages.back.admin.missions', compact('user'));
    }

    /**
     * GET /admin/accreditation  →  admin.accreditation
     */
    public function accreditation()
    {
        $user = Auth::user();
        return view('pages.back.admin.accreditation', compact('user'));
    }

    // ══════════════════════════════════════════════════════════════
    // API JSON
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /admin/stats  →  admin.stats
     * Retourne toutes les stats réelles pour AdminDashboardComponent.
     */
    public function stats()
    {
        return response()->json($this->buildStats());
    }

    // ══════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — construction des stats complètes
    // ══════════════════════════════════════════════════════════════

    private function buildStats(): array
    {
        // ── Utilisateurs ──────────────────────────────────────────
        $totalUsers       = User::whereIn('role', ['client', 'contractor', 'talent'])->count();
        $totalClients     = User::where('role', 'client')->count();
        $totalContractors = User::where('role', 'contractor')->count();
        $totalTalents     = User::where('role', 'talent')->count();

        $approvedContractors = User::where('role', 'contractor')->where('status', 'approved')->count();
        $pendingContractors  = User::where('role', 'contractor')->where('status', 'pending')->count();
        $rejectedContractors = User::where('role', 'contractor')->where('status', 'rejected')->count();

        $newUsersThisMonth = User::whereIn('role', ['client', 'contractor', 'talent'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $newUsersLastMonth = User::whereIn('role', ['client', 'contractor', 'talent'])
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $userGrowth = $newUsersLastMonth > 0
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100)
            : 0;

        // ── Documents ─────────────────────────────────────────────
        $docsTotal    = Document::count();
        $docsPending  = Document::where('status', 'pending')->count();
        $docsApproved = Document::where('status', 'approved')->count();
        $docsRejected = Document::where('status', 'rejected')->count();

        // ── Missions ──────────────────────────────────────────────
        $activeStatuses = [
            'assigned', 'accepted', 'contact_made', 'on_the_way',
            'tracking', 'in_progress', 'quote_submitted', 'order_placed', 'awaiting_confirm',
        ];

        $totalMissions    = Mission::count();
        $activeMissions   = Mission::whereIn('status', $activeStatuses)->count();
        $closedMissions   = Mission::whereIn('status', ['completed', 'closed'])->count();
        $pendingMissions  = Mission::where('status', 'pending')->count();
        $cancelledMissions= Mission::where('status', 'cancelled')->count();

        // ── Finances ──────────────────────────────────────────────
        $paidMissions      = Mission::where('status', 'closed')->count();
        $totalAmount       = Mission::where('status', 'closed')->sum('total_amount');
        $totalCommission   = Mission::where('status', 'closed')->sum('commission') ?: $totalAmount * ((float) Setting::get('commission_main_oeuvre', 10) / 100);

        $monthlyAmount     = Mission::where('status', 'closed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('total_amount');
        $monthlyCommission = Mission::where('status', 'closed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('commission') ?: $monthlyAmount * ((float) Setting::get('commission_main_oeuvre', 10) / 100);

        // ── Accréditations ────────────────────────────────────────
        $accredResidential = Contractor::whereIn('accreditation', ['home', 'both'])->count();
        $accredBusiness    = Contractor::whereIn('accreditation', ['business', 'both'])->count();

        // ── Taux de complétion ────────────────────────────────────
        $startedMissions = Mission::whereNotIn('status', ['pending'])->count();
        $completionRate  = $startedMissions > 0
            ? (int) round($closedMissions / $startedMissions * 100)
            : 0;

        // ── Note moyenne ──────────────────────────────────────────
        $averageRating = round((float) Contractor::avg('average_rating'), 1);

        // ── Inscriptions récentes (derniers 10) ───────────────────
        $recentUsers = User::whereIn('role', ['client', 'contractor', 'talent'])
            ->with(['contractor', 'client'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'status'     => $u->status,
                'specialty'  => $u->contractor?->specialty ?? null,
                'created_at' => $u->created_at->diffForHumans(),
            ]);

        // ── Graphiques inscriptions ───────────────────────────────
        $registrationsByDay = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'label'       => $date->locale('fr')->isoFormat('ddd'),
                'date'        => $date->toDateString(),
                'clients'     => User::where('role', 'client')->whereDate('created_at', $date)->count(),
                'contractors' => User::where('role', 'contractor')->whereDate('created_at', $date)->count(),
                'talents'     => User::where('role', 'talent')->whereDate('created_at', $date)->count(),
            ];
        })->values();

        $registrationsByWeek = collect(range(3, 0))->map(function ($weeksAgo) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end   = now()->subWeeks($weeksAgo)->endOfWeek();
            return [
                'label'       => 'S' . $start->weekOfYear,
                'clients'     => User::where('role', 'client')->whereBetween('created_at', [$start, $end])->count(),
                'contractors' => User::where('role', 'contractor')->whereBetween('created_at', [$start, $end])->count(),
                'talents'     => User::where('role', 'talent')->whereBetween('created_at', [$start, $end])->count(),
            ];
        })->values();

        $registrationsByMonth = collect(range(2, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'label'       => $date->locale('fr')->isoFormat('MMM'),
                'clients'     => User::where('role', 'client')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'contractors' => User::where('role', 'contractor')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'talents'     => User::where('role', 'talent')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
            ];
        })->values();

        // ── Résultat final ────────────────────────────────────────
        return [
            // ── Clés pour AdminDashboardComponent KPIs ──
            'pending_contractors'  => $pendingContractors,
            'approved_contractors' => $approvedContractors,
            'total_contractors'    => $totalContractors,
            'total_clients'        => $totalClients,
            'active_missions'      => $activeMissions,
            'pending_missions'     => $pendingMissions,
            'closed_missions'      => $closedMissions,
            'cancelled_missions'   => $cancelledMissions,
            'total_missions'       => $totalMissions,
            'paid_missions'        => $paidMissions,
            'total_amount'         => $totalAmount,
            'total_commission'     => $totalCommission,
            'monthly_commission'   => $monthlyCommission,
            'average_rating'       => $averageRating,
            'completion_rate'      => $completionRate,
            'accred_residential'   => $accredResidential,
            'accred_business'      => $accredBusiness,

            // ── Clés existantes (ancienne structure) ──
            'users' => [
                'total'       => $totalUsers,
                'clients'     => $totalClients,
                'contractors' => $totalContractors,
                'talents'     => $totalTalents,
                'certified'   => $approvedContractors,
                'pending'     => $pendingContractors,
                'rejected'    => $rejectedContractors,
                'new_month'   => $newUsersThisMonth,
                'growth'      => $userGrowth,
            ],
            'documents' => [
                'total'    => $docsTotal,
                'pending'  => $docsPending,
                'approved' => $docsApproved,
                'rejected' => $docsRejected,
            ],
            'recent_users' => $recentUsers,
            'charts' => [
                '7j'  => $registrationsByDay,
                '30j' => $registrationsByWeek,
                '90j' => $registrationsByMonth,
            ],
        ];
    }
}