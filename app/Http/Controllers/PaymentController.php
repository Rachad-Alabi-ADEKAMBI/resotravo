<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Mission;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\MtnMomoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private MtnMomoService $momo) {}

    // ══════════════════════════════════════════════════════════════
    // POST /client/missions/{mission}/payment
    // Initier le paiement MoMo pour une mission terminée
    // ══════════════════════════════════════════════════════════════

    public function initiate(Request $request, Mission $mission): JsonResponse
    {
        $user   = Auth::user();
        $client = Client::where('user_id', $user->id)->firstOrFail();

        if ($mission->client_id !== $client->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($mission->status !== Mission::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'La mission doit être "Terminée" pour procéder au paiement.',
            ], 422);
        }

        if ($mission->paid_at) {
            return response()->json([
                'message'     => 'Cette mission a déjà été payée.',
                'receipt_url' => route('client.missions.receipt', $mission),
            ], 422);
        }

        $data = $request->validate([
            'phone'   => 'required|string|max:20',
            'network' => 'required|in:mtn,moov,celtiis',
        ]);

        if (!$mission->total_amount || (float) $mission->total_amount <= 0) {
            return response()->json(['message' => 'Montant de la mission invalide.'], 422);
        }

        $result = $this->momo->requestToPay(
            phone:       $data['phone'],
            amount:      (float) $mission->total_amount,
            externalId:  'mission-' . $mission->id . '-' . Str::random(6),
            description: "Paiement mission #{$mission->id} — {$mission->service}",
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'] ?? "Erreur lors de l'initiation du paiement.",
            ], 422);
        }

        // Persist reference ID for polling
        $mission->update(['momo_transaction_id' => $result['reference_id']]);

        // Sandbox simulation → clôturer immédiatement
        if (!empty($result['simulated'])) {
            return $this->completePayment($mission, $result['reference_id']);
        }

        return response()->json([
            'status'       => 'PENDING',
            'reference_id' => $result['reference_id'],
            'message'      => 'Demande envoyée. Confirmez sur votre téléphone avec votre code PIN.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /client/missions/{mission}/payment/status
    // Vérifier le statut du paiement (polling)
    // ══════════════════════════════════════════════════════════════

    public function checkStatus(Mission $mission): JsonResponse
    {
        $user   = Auth::user();
        $client = Client::where('user_id', $user->id)->firstOrFail();

        if ($mission->client_id !== $client->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        // Déjà payé
        if ($mission->paid_at) {
            return response()->json([
                'status'      => 'SUCCESSFUL',
                'receipt_url' => route('client.missions.receipt', $mission),
            ]);
        }

        if (!$mission->momo_transaction_id) {
            return response()->json(['status' => 'PENDING']);
        }

        $momoStatus = $this->momo->checkPaymentStatus($mission->momo_transaction_id);

        if ($momoStatus === 'SUCCESSFUL') {
            return $this->completePayment($mission, $mission->momo_transaction_id);
        }

        if ($momoStatus === 'FAILED') {
            return response()->json([
                'status'  => 'FAILED',
                'message' => 'Paiement refusé ou expiré. Réessayez.',
            ]);
        }

        return response()->json(['status' => 'PENDING']);
    }

    // ══════════════════════════════════════════════════════════════
    // GET /client/missions/{mission}/invoice
    // Afficher / télécharger la facture (devis approuvé)
    // ══════════════════════════════════════════════════════════════

    public function invoice(Mission $mission)
    {
        $user = Auth::user();

        if ($user->role === 'client') {
            $client = Client::where('user_id', $user->id)->firstOrFail();
            abort_unless($mission->client_id === $client->id, 403);
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        $allowedStatuses = [
            Mission::STATUS_ORDER_PLACED,
            Mission::STATUS_AWAITING_CONFIRM,
            Mission::STATUS_COMPLETED,
            Mission::STATUS_CLOSED,
        ];
        abort_unless(in_array($mission->status, $allowedStatuses), 404, 'Facture non disponible.');

        $mission->load(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        return view('receipts.invoice', compact('mission'));
    }

    // ══════════════════════════════════════════════════════════════
    // GET /client/missions/{mission}/receipt
    // Afficher / télécharger le reçu
    // ══════════════════════════════════════════════════════════════

    public function receipt(Mission $mission)
    {
        $user = Auth::user();

        // Accès : client propriétaire ou admin
        if ($user->role === 'client') {
            $client = Client::where('user_id', $user->id)->firstOrFail();
            abort_unless($mission->client_id === $client->id, 403);
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        abort_unless($mission->paid_at, 404, 'Reçu non disponible : mission non payée.');

        $mission->load(['client.user', 'contractor.user', 'quote.items', 'reservation']);

        return view('receipts.mission', compact('mission'));
    }

    // ══════════════════════════════════════════════════════════════
    // Envoi de la facture par email (appelé depuis MissionController)
    // ══════════════════════════════════════════════════════════════

    public static function sendInvoiceEmail(Mission $mission): void
    {
        $clientUser = $mission->client?->user;
        if (!$clientUser?->email) return;

        $invoiceUrl    = route('client.missions.invoice', $mission);
        $logoUrl       = asset('images/logo_mesotravo.png');
        $clientName    = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: $clientUser->name;
        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: '—';

        try {
            Mail::send('emails.invoice', compact('mission', 'invoiceUrl', 'logoUrl', 'clientName', 'contractorName'), function ($m) use ($clientUser, $mission) {
                $m->to($clientUser->email, $clientUser->name)
                  ->subject("📄 Votre facture Mesotravo — Mission #{$mission->id}");
            });
        } catch (\Throwable) {
            // Ne pas bloquer le flux si l'email échoue
        }
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE
    // ══════════════════════════════════════════════════════════════

    private function completePayment(Mission $mission, string $referenceId): JsonResponse
    {
        // Transition completed → closed
        if ($mission->status !== Mission::STATUS_CLOSED) {
            try {
                $mission->transitionTo(Mission::STATUS_CLOSED);
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $mission->calculateCommission();

        $mission->update([
            'momo_transaction_id' => $referenceId,
            'paid_at'             => now(),
        ]);

        // Compteurs
        $mission->contractor?->increment('total_missions');
        $mission->contractor?->increment('completed_missions');
        $mission->client?->increment('completed_missions');
        $mission->client?->increment('total_missions');

        $fresh = $mission->fresh()->load(['client.user', 'contractor.user', 'quote.items', 'reservation']);
        $this->sendPaymentNotifications($fresh);
        $this->sendReceiptEmail($fresh);

        $receiptUrl = route('client.missions.receipt', $mission);

        return response()->json([
            'status'      => 'SUCCESSFUL',
            'message'     => '🎉 Paiement confirmé ! Mission clôturée.',
            'receipt_url' => $receiptUrl,
            'mission'     => [
                'id'                  => $mission->id,
                'status'              => $mission->status,
                'step'                => $mission->step,
                'status_label'        => $mission->status_label,
                'paid_at'             => $mission->paid_at?->toISOString(),
                'total_amount'        => $mission->total_amount,
                'commission'          => $mission->commission,
                'momo_transaction_id' => $mission->momo_transaction_id,
            ],
        ]);
    }

    private function sendReceiptEmail(Mission $mission): void
    {
        $clientUser = $mission->client?->user;
        if (!$clientUser?->email) return;

        $receiptUrl    = route('client.missions.receipt', $mission);
        $logoUrl       = asset('images/logo_mesotravo.png');
        $clientName    = trim(($mission->client->first_name ?? '') . ' ' . ($mission->client->last_name ?? '')) ?: $clientUser->name;
        $contractorName = trim(($mission->contractor->first_name ?? '') . ' ' . ($mission->contractor->last_name ?? '')) ?: '—';

        try {
            Mail::send('emails.receipt', compact('mission', 'receiptUrl', 'logoUrl', 'clientName', 'contractorName'), function ($m) use ($clientUser, $mission) {
                $m->to($clientUser->email, $clientUser->name)
                  ->subject("🧾 Votre reçu de paiement Mesotravo — Mission #{$mission->id}");
            });
        } catch (\Throwable) {
            // Ne pas bloquer le flux si l'email échoue
        }
    }

    private function sendPaymentNotifications(Mission $mission): void
    {
        $service        = $mission->service;
        $commission     = $mission->commission;
        $net            = $mission->total_amount
            ? round((float) $mission->total_amount - (float) ($commission ?? 0))
            : null;
        $clientUser     = $mission->client?->user;
        $contractorUser = $mission->contractor?->user;
        $extra          = ['mission_id' => $mission->id];

        $clientUser?->notify(new AppNotification(
            event: 'payment.sent',
            title: 'Paiement confirmé — Reçu disponible',
            body:  "Votre paiement de " . number_format((float) $mission->total_amount, 0, ',', ' ')
                   . " FCFA pour « {$service} » a été effectué. Votre reçu est disponible.",
            url:   '/client/missions',
            icon:  'credit-card',
            extra: array_merge($extra, ['amount' => $mission->total_amount]),
        ));

        $contractorUser?->notify(new AppNotification(
            event: 'payment.received',
            title: 'Paiement reçu',
            body:  'Vous avez reçu '
                   . ($net ? number_format($net, 0, ',', ' ') . ' FCFA' : 'votre paiement')
                   . " pour « {$service} ».",
            url:   '/contractor/missions',
            icon:  'credit-card',
            extra: array_merge($extra, ['net' => $net]),
        ));

        User::where('role', 'admin')->each(fn(User $admin) =>
            $admin->notify(new AppNotification(
                event: 'payment.commission',
                title: 'Commission encaissée',
                body:  'Commission de '
                       . ($commission ? number_format((float) $commission, 0, ',', ' ') . ' FCFA' : '10%')
                       . " sur « {$service} ».",
                url:   '/admin/missions',
                icon:  'trending-up',
                extra: array_merge($extra, ['commission' => $commission]),
            ))
        );
    }
}
