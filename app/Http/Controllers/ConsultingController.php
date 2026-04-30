<?php

namespace App\Http\Controllers;

use App\Models\ConsultingMessage;
use App\Models\ConsultingTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConsultingController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // FRONT — Endpoints utilisateur
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /consulting/tickets
     * Liste les tickets de l'utilisateur connecté.
     */
    public function userIndex(): JsonResponse
    {
        $tickets = ConsultingTicket::where('user_id', Auth::id())
            ->with(['lastMessage'])
            ->withCount(['messages as unread_count' => fn($q) =>
                $q->whereIn('sender_type', ['ia', 'agent'])->where('is_read', false)
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn(ConsultingTicket $t) => $this->formatTicket($t));

        return response()->json($tickets);
    }

    /**
     * POST /consulting/tickets
     * Crée un nouveau ticket + premier message + réponse IA immédiate.
     */
    public function userStore(Request $request): JsonResponse
    {
        $request->validate([
            'subject'       => 'nullable|string|max:100',
            'first_message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        $ticket = ConsultingTicket::create([
            'user_id'          => $user->id,
            'subject'          => $request->subject ?? mb_substr($request->first_message, 0, 80),
            'status'           => 'open',
            'ia_message_count' => 1,
            'first_message_at' => now(),
        ]);

        // Message utilisateur
        $userMessage = ConsultingMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $user->id,
            'sender_type' => 'user',
            'body'        => $request->first_message,
            'is_read'     => true,
        ]);

        // Réponse IA
        $iaReply = $this->generateIAReply($request->first_message);
        $iaMessage = ConsultingMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => null,
            'sender_type' => 'ia',
            'body'        => $iaReply,
            'is_read'     => false,
        ]);

        $ticket->touch();

        return response()->json([
            'ticket'   => $this->formatTicket($ticket->fresh()),
            'messages' => [
                $this->formatMessage($userMessage),
                $this->formatMessage($iaMessage),
            ],
        ], 201);
    }

    /**
     * GET /consulting/tickets/{ticket}/messages
     * Messages d'un ticket (utilisateur propriétaire uniquement).
     */
    public function userMessages(ConsultingTicket $ticket): JsonResponse
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        // Marquer les messages IA/agent comme lus
        $ticket->messages()
            ->whereIn('sender_type', ['ia', 'agent'])
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $ticket->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => $this->formatMessage($m));

        return response()->json([
            'ticket'   => $this->formatTicket($ticket->fresh()),
            'messages' => $messages,
        ]);
    }

    /**
     * POST /consulting/tickets/{ticket}/send
     * L'utilisateur envoie un message. L'IA répond automatiquement si < 3 messages.
     */
    public function userSend(Request $request, ConsultingTicket $ticket): JsonResponse
    {
        abort_if($ticket->user_id !== Auth::id(), 403);
        abort_if(in_array($ticket->status, ['resolved', 'closed']), 422, 'Ce ticket est fermé.');

        $request->validate(['body' => 'required|string|max:2000']);

        $user = Auth::user();

        // Message utilisateur
        $userMessage = ConsultingMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $user->id,
            'sender_type' => 'user',
            'body'        => $request->body,
            'is_read'     => true,
        ]);

        $ticket->increment('ia_message_count');
        $ticket->touch();

        $iaMessage = null;
        $updatedTicket = $ticket->fresh();

        // Réponse IA si pas encore d'agent humain ET < 3 messages IA
        if (!$ticket->human_assigned && $ticket->ia_message_count <= 3) {
            $iaReply = $this->generateIAReply($request->body);
            $iaMessage = ConsultingMessage::create([
                'ticket_id'   => $ticket->id,
                'sender_id'   => null,
                'sender_type' => 'ia',
                'body'        => $iaReply,
                'is_read'     => false,
            ]);
        }

        // Transfert automatique après 3 messages IA
        if (!$ticket->human_assigned && $ticket->ia_message_count >= 3) {
            $ticket->update(['status' => 'pending_human', 'human_requested' => true]);
            $updatedTicket = $ticket->fresh();
        }

        return response()->json([
            'ticket'       => $this->formatTicket($updatedTicket),
            'user_message' => $this->formatMessage($userMessage),
            'ia_message'   => $iaMessage ? $this->formatMessage($iaMessage) : null,
        ], 201);
    }

    /**
     * PATCH /consulting/tickets/{ticket}/request-human
     * L'utilisateur demande explicitement un agent humain.
     */
    public function userRequestHuman(ConsultingTicket $ticket): JsonResponse
    {
        abort_if($ticket->user_id !== Auth::id(), 403);

        $ticket->update([
            'human_requested' => true,
            'status'          => 'pending_human',
        ]);

        return response()->json(['success' => true, 'status' => $ticket->status]);
    }

    // ══════════════════════════════════════════════════════════════
    // IA — Génération de réponse locale (remplaçable par GPT etc.)
    // ══════════════════════════════════════════════════════════════

    private function generateIAReply(string $text): string
    {
        $t = mb_strtolower($text);

        if (str_contains($t, 'plomb'))
            return "Pour un problème de plomberie, créez une demande depuis votre tableau de bord : un plombier certifié vous sera assigné en moins de 5 minutes dans votre zone.";

        if (str_contains($t, 'electr') || str_contains($t, 'disjonct') || str_contains($t, 'courant'))
            return "Pour un problème électrique, nos électriciens accrédités sont disponibles 7j/7. Créez une demande et le prestataire le plus proche sera notifié immédiatement.";

        if (str_contains($t, 'paiement') || str_contains($t, 'momo') || str_contains($t, 'payer'))
            return "Le paiement via MTN MoMo est déclenché uniquement après votre confirmation de fin des travaux. Vous ne payez qu'une fois satisfait — vous êtes 100% protégé.";

        if (str_contains($t, 'appel') || str_contains($t, 'offre') || str_contains($t, 'ao'))
            return "Pour publier un appel d'offres, rendez-vous dans la section « Appels d'offres ». Décrivez votre besoin, fixez un budget et une deadline. La publication est validée par notre équipe sous 24h.";

        if (str_contains($t, 'prestataire') || str_contains($t, 'inscrire') || str_contains($t, 'devenir'))
            return "Pour devenir prestataire Mesotravo, inscrivez-vous et uploadez vos 6 documents (CNI, casier judiciaire, diplômes…). Notre équipe vérifie votre dossier sous 48h et vous attribue vos accréditations.";

        if (str_contains($t, 'clim') || str_contains($t, 'froid') || str_contains($t, 'frigori'))
            return "Pour la climatisation et le froid, nos frigoristes accrédités interviennent pour l'installation, la réparation et la maintenance de vos équipements.";

        if (str_contains($t, 'accr') || str_contains($t, 'certif') || str_contains($t, 'badge'))
            return "L'accréditation DOMICILE permet d'intervenir chez les particuliers. L'accréditation ENTREPRISE est délivrée après vérification complémentaire. Un prestataire peut cumuler les deux badges.";

        if (str_contains($t, 'délai') || str_contains($t, 'rapide') || str_contains($t, 'temps'))
            return "Notre algorithme géolocalisé attribue un prestataire certifié en moins de 5 minutes. À Cotonou, le délai moyen est de 3 minutes.";

        if (str_contains($t, 'prix') || str_contains($t, 'tarif') || str_contains($t, 'coût') || str_contains($t, 'combien'))
            $rateDiag  = \App\Models\Setting::get('commission_diagnostic', 10);
            $rateLabor = \App\Models\Setting::get('commission_main_oeuvre', 10);
            return "Les tarifs sont fixés par les prestataires via un devis détaillé que vous approuvez avant tout travail. La commission Mesotravo est de {$rateDiag}% sur le diagnostic et {$rateLabor}% sur la main d'oeuvre, incluse dans le total affiché.";

        return "Je comprends votre demande. Pour une assistance plus précise, je vous recommande de décrire davantage votre situation. Si besoin, demandez à parler à un agent humain en cliquant sur « Parler à un humain ».";
    }

    // ══════════════════════════════════════════════════════════════

    /**
     * GET /admin/consulting
     * Retourne tous les tickets avec stats pour le tableau de bord.
     */
    public function adminIndex(): JsonResponse
    {
        $tickets = ConsultingTicket::with(['user', 'agent', 'lastMessage'])
            ->withCount(['messages', 'messages as unread_count' => fn($q) =>
                $q->where('sender_type', 'user')->where('is_read', false)
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn(ConsultingTicket $t) => $this->formatTicket($t));

        return response()->json($tickets);
    }

    /**
     * GET /admin/consulting/{ticket}/messages
     * Retourne tous les messages d'un ticket + marque les messages user comme lus.
     */
    public function adminMessages(ConsultingTicket $ticket): JsonResponse
    {
        // Marquer les messages utilisateur comme lus
        $ticket->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $ticket->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(fn(ConsultingMessage $m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender?->name ?? ($m->sender_type === 'ia' ? 'Agent IA' : 'Agent'),
                'body'        => $m->body,
                'is_read'     => $m->is_read,
                'created_at'  => $m->created_at?->format('d/m/Y H:i'),
                'ago'         => $m->created_at?->locale('fr')->diffForHumans(),
            ]);

        return response()->json([
            'ticket'   => $this->formatTicket($ticket->fresh(['user', 'agent'])),
            'messages' => $messages,
        ]);
    }

    /**
     * POST /admin/consulting/{ticket}/reply
     * L'agent répond à un ticket.
     */
    public function adminReply(Request $request, ConsultingTicket $ticket): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $agent = Auth::user();

        // Assigner l'agent si pas encore fait
        if (!$ticket->human_assigned) {
            $ticket->update([
                'agent_id'       => $agent->id,
                'human_assigned' => true,
                'status'         => 'in_progress',
                'assigned_at'    => now(),
            ]);
        }

        $message = ConsultingMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $agent->id,
            'sender_type' => 'agent',
            'body'        => $request->body,
            'is_read'     => false,
        ]);

        $ticket->touch(); // updated_at → remonte en haut de la liste

        return response()->json([
            'message' => [
                'id'          => $message->id,
                'sender_type' => 'agent',
                'sender_name' => $agent->name,
                'body'        => $message->body,
                'is_read'     => false,
                'created_at'  => $message->created_at->format('d/m/Y H:i'),
                'ago'         => $message->created_at->locale('fr')->diffForHumans(),
            ],
        ], 201);
    }

    /**
     * PATCH /admin/consulting/{ticket}/status
     * Changer le statut d'un ticket.
     */
    public function adminUpdateStatus(Request $request, ConsultingTicket $ticket): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'resolved' || $request->status === 'closed') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return response()->json(['success' => true, 'status' => $ticket->status]);
    }

    /**
     * PATCH /admin/consulting/{ticket}/assign
     * Assigner le ticket à l'agent connecté.
     */
    public function adminAssign(ConsultingTicket $ticket): JsonResponse
    {
        $agent = Auth::user();

        $ticket->update([
            'agent_id'       => $agent->id,
            'human_assigned' => true,
            'status'         => 'in_progress',
            'assigned_at'    => now(),
        ]);

        return response()->json(['success' => true, 'agent_name' => $agent->name]);
    }

    /**
     * PATCH /admin/consulting/{ticket}/note
     * Sauvegarder une note interne admin.
     */
    public function adminNote(Request $request, ConsultingTicket $ticket): JsonResponse
    {
        $request->validate(['note' => 'nullable|string|max:1000']);
        $ticket->update(['admin_note' => $request->note]);

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════

    private function formatMessage(ConsultingMessage $m): array
    {
        return [
            'id'          => $m->id,
            'sender_type' => $m->sender_type,
            'sender_name' => $m->sender?->name ?? ($m->sender_type === 'ia' ? 'Agent IA' : 'Agent Mesotravo'),
            'body'        => $m->body,
            'is_read'     => $m->is_read,
            'created_at'  => $m->created_at?->format('d/m/Y H:i'),
            'ago'         => $m->created_at?->locale('fr')->diffForHumans(),
        ];
    }

    private function formatTicket(ConsultingTicket $t): array
    {
        return [
            'id'               => $t->id,
            'subject'          => $t->subject ?? 'Sans sujet',
            'category'         => $t->category,
            'status'           => $t->status,
            'status_label'     => $t->status_label,
            'ia_message_count' => $t->ia_message_count,
            'human_requested'  => $t->human_requested,
            'human_assigned'   => $t->human_assigned,
            'admin_note'       => $t->admin_note,
            'rating'           => $t->rating,
            'messages_count'   => $t->messages_count ?? $t->messages()->count(),
            'unread_count'     => $t->unread_count ?? 0,
            'user_name'        => $t->user?->name ?? 'Utilisateur supprimé',
            'user_email'       => $t->user?->email ?? '—',
            'agent_name'       => $t->agent?->name ?? null,
            'last_message'     => $t->lastMessage?->body
                                    ? mb_substr($t->lastMessage->body, 0, 80)
                                    : null,
            'last_message_type'=> $t->lastMessage?->sender_type ?? null,
            'created_at_label' => $t->created_at?->format('d/m/Y H:i'),
            'updated_at_ago'   => $t->updated_at?->locale('fr')->diffForHumans(),
            'assigned_at_label'=> $t->assigned_at?->format('d/m/Y H:i'),
            'resolved_at_label'=> $t->resolved_at?->format('d/m/Y H:i'),
        ];
    }
}