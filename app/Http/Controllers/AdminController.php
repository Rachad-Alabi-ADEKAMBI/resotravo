<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * GET /admin/stats
     * Retourne toutes les stats réelles pour le tableau de bord admin.
     */
    public function stats()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        // ── Utilisateurs ──────────────────────────────────────────────
        $totalUsers       = User::whereIn('role', ['client', 'contractor', 'talent'])->count();
        $totalClients     = User::where('role', 'client')->count();
        $totalContractors = User::where('role', 'contractor')->count();
        $totalTalents     = User::where('role', 'talent')->count();

        $certifiedContractors = User::where('role', 'contractor')->where('status', 'approved')->count();
        $pendingContractors   = User::where('role', 'contractor')->where('status', 'pending')->count();
        $rejectedContractors  = User::where('role', 'contractor')->where('status', 'rejected')->count();

        // Nouveaux inscrits ce mois
        $newUsersThisMonth = User::whereIn('role', ['client', 'contractor', 'talent'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at',  now()->year)
            ->count();

        $newUsersLastMonth = User::whereIn('role', ['client', 'contractor', 'talent'])
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at',  now()->subMonth()->year)
            ->count();

        $userGrowth = $newUsersLastMonth > 0
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100)
            : 0;

        // ── Documents ─────────────────────────────────────────────────
        $docsTotal    = Document::count();
        $docsPending  = Document::where('status', 'pending')->count();
        $docsApproved = Document::where('status', 'approved')->count();
        $docsRejected = Document::where('status', 'rejected')->count();

        // ── Inscriptions récentes (derniers 10) ───────────────────────
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

        // ── Inscriptions par jour (7 derniers jours) ──────────────────
        $registrationsByDay = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'label'   => $date->locale('fr')->isoFormat('ddd'),
                'date'    => $date->toDateString(),
                'clients'     => User::where('role', 'client')
                    ->whereDate('created_at', $date)->count(),
                'contractors' => User::where('role', 'contractor')
                    ->whereDate('created_at', $date)->count(),
                'talents'     => User::where('role', 'talent')
                    ->whereDate('created_at', $date)->count(),
            ];
        })->values();

        // ── Inscriptions par semaine (4 dernières semaines) ───────────
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

        // ── Inscriptions par mois (3 derniers mois) ───────────────────
        $registrationsByMonth = collect(range(2, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'label'       => $date->locale('fr')->isoFormat('MMM'),
                'clients'     => User::where('role', 'client')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'contractors' => User::where('role', 'contractor')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
                'talents'     => User::where('role', 'talent')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
            ];
        })->values();

        return response()->json([
            // Utilisateurs
            'users' => [
                'total'       => $totalUsers,
                'clients'     => $totalClients,
                'contractors' => $totalContractors,
                'talents'     => $totalTalents,
                'certified'   => $certifiedContractors,
                'pending'     => $pendingContractors,
                'rejected'    => $rejectedContractors,
                'new_month'   => $newUsersThisMonth,
                'growth'      => $userGrowth,
            ],
            // Documents
            'documents' => [
                'total'    => $docsTotal,
                'pending'  => $docsPending,
                'approved' => $docsApproved,
                'rejected' => $docsRejected,
            ],
            // Activité récente
            'recent_users' => $recentUsers,
            // Graphiques
            'charts' => [
                '7j'  => $registrationsByDay,
                '30j' => $registrationsByWeek,
                '90j' => $registrationsByMonth,
            ],
        ]);
    }
}