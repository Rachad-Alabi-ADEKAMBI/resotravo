<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionQuote;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MissionQuoteController extends Controller
{
    /**
     * POST /contractor/missions/{mission}/quote
     *
     * Upsert le devis (draft ou submitted).
     * Si action=submit → passe le devis en submitted + mission en quote_submitted.
     *
     * Body attendu :
     * {
     *   "action": "draft" | "submit",
     *   "diagnosis": "Texte du diagnostic (optionnel)",
     *   "items": [
     *     { "type": "part",  "description": "Robinet", "quantity": 2, "unit_price": 5000 },
     *     { "type": "labor", "description": "Main d'œuvre", "quantity": 1, "unit_price": 15000 }
     *   ]
     * }
     */
    public function upsert(Request $request, Mission $mission): JsonResponse
    {
        $user       = Auth::user();
        $contractor = $mission->contractor;

        // Seul le prestataire assigné peut créer/modifier le devis
        if (!$contractor || $contractor->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Statuts autorisant la saisie/révision du devis
        $allowedStatuses = [
            Mission::STATUS_IN_PROGRESS,
            Mission::STATUS_QUOTE_SUBMITTED,
            Mission::STATUS_ORDER_PLACED, // révision après refus client
        ];

        if (!in_array($mission->status, $allowedStatuses)) {
            return response()->json([
                'message' => "Impossible de soumettre un devis pour une mission en statut « {$mission->status_label} ».",
            ], 422);
        }

        $data = $request->validate([
            'action'               => 'required|in:draft,submit',
            'diagnosis'            => 'nullable|string|max:2000',
            'items'                => 'required|array|min:1',
            'items.*.type'         => 'required|in:part,labor,diagnostic,travel,other',
            'items.*.description'  => 'required|string|max:255',
            'items.*.quantity'     => 'required|numeric|min:0.01|max:9999',
            'items.*.unit_price'   => 'required|numeric|min:0|max:99999999',
        ]);

        DB::transaction(function () use ($mission, $data) {
            // Récupère le devis existant ou en crée un nouveau
            $quote = $mission->quote ?? new MissionQuote(['mission_id' => $mission->id, 'version' => 1]);

            // Si c'est une révision d'un devis déjà soumis, on incrémente la version
            if ($quote->exists && in_array($quote->status, [
                MissionQuote::STATUS_SUBMITTED,
                MissionQuote::STATUS_REJECTED,
            ])) {
                $quote->version = ($quote->version ?? 1) + 1;
            }

            $newStatus = $data['action'] === 'submit'
                ? MissionQuote::STATUS_SUBMITTED
                : MissionQuote::STATUS_DRAFT;

            $quote->fill([
                'diagnosis' => $data['diagnosis'] ?? null,
                'status'    => $newStatus,
            ]);
            $quote->save();

            // Remplace toutes les lignes
            $quote->items()->delete();

            foreach ($data['items'] as $item) {
                $quote->items()->create([
                    'type'        => $item['type'],
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                ]);
            }

            // Recalcule les totaux
            $quote->recalculateTotals();

            // Si soumission → met à jour le total de la mission et change le statut
            if ($data['action'] === 'submit') {
                $mission->update(['total_amount' => $quote->fresh()->amount_incl_tax]);

                if ($mission->status === Mission::STATUS_IN_PROGRESS) {
                    $mission->transitionTo(Mission::STATUS_QUOTE_SUBMITTED);
                }

                // Notifier le client
                $mission->client?->user?->notify(new AppNotification(
                    event: 'quote.submitted',
                    title: 'Nouveau devis à approuver',
                    body:  "Un devis de " . number_format($quote->fresh()->amount_incl_tax, 0, ',', ' ') . " FCFA a été soumis pour « {$mission->service} ».",
                    url:   "/client/missions/{$mission->id}",
                    icon:  'file-text',
                    extra: ['mission_id' => $mission->id],
                ));
            }
        });

        $mission->load(['client', 'contractor', 'quote.items']);

        return response()->json([
            'message' => $data['action'] === 'submit' ? 'Devis soumis au client.' : 'Brouillon enregistré.',
            'mission' => $this->formatMission($mission),
            'quote'   => $mission->quote->load('items'),
        ]);
    }

    // ── Helper (même logique que MissionController::formatMission) ──

    private function formatMission(Mission $mission): array
    {
        $client     = $mission->client;
        $contractor = $mission->contractor;

        return [
            'id'               => $mission->id,
            'status'           => $mission->status,
            'status_label'     => $mission->status_label,
            'step'             => $mission->step,
            'service'          => $mission->service,
            'address'          => $mission->address,
            'description'      => $mission->description,
            'location_type'    => $mission->location_type,
            'total_amount'     => $mission->total_amount,
            'commission'       => $mission->commission,
            'payment_unlocked' => $mission->paymentUnlocked(),
            'dispute_open'     => $mission->dispute_open,
            'created_at'       => $mission->created_at->toISOString(),
            'client' => $client ? [
                'id'      => $client->id,
                'user_id' => $client->user_id,
                'name'    => trim("{$client->first_name} {$client->last_name}"),
            ] : null,
            'contractor' => $contractor ? [
                'id'      => $contractor->id,
                'user_id' => $contractor->user_id,
                'name'    => trim("{$contractor->first_name} {$contractor->last_name}"),
            ] : null,
            'quote' => $mission->quote ? [
                'id'              => $mission->quote->id,
                'status'          => $mission->quote->status,
                'diagnosis'       => $mission->quote->diagnosis,
                'amount_excl_tax' => $mission->quote->amount_excl_tax,
                'amount_incl_tax' => $mission->quote->amount_incl_tax,
                'version'         => $mission->quote->version,
                'items'           => $mission->quote->items->map(fn($i) => [
                    'id'          => $i->id,
                    'type'        => $i->type,
                    'description' => $i->description,
                    'quantity'    => $i->quantity,
                    'unit_price'  => $i->unit_price,
                    'line_total'  => $i->line_total,
                ])->toArray(),
            ] : null,
        ];
    }
}