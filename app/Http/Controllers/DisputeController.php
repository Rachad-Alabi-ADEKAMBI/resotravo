<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\DisputeAttachment;
use App\Models\DisputeMessage;
use App\Models\Mission;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // LISTE
    // ══════════════════════════════════════════════════════════════

    public function adminIndex(): JsonResponse
    {
        try {
            $disputes = Dispute::orderByDesc('opened_at')
                ->get()
                ->map(fn(Dispute $d) => [
                    'id'                => $d->id,
                    'subject'           => $d->subject,
                    'description'       => $d->description,
                    'status'            => $d->status,
                    'status_label'      => $d->status_label,
                    'is_resolved'       => $d->is_resolved,
                    'verdict'           => $d->verdict,
                    'verdict_label'     => $d->verdict_label,
                    'verdict_note'      => $d->verdict_note,
                    'contractor_suspended' => $d->contractor_suspended,
                    'mission_id'        => $d->mission_id,
                    'mission_service'   => '—',
                    'mission_status'    => '—',
                    'client_name'       => '—',
                    'client_email'      => '—',
                    'contractor_name'   => '—',
                    'contractor_email'  => '—',
                    'admin_name'        => '—',
                    'messages_count'    => 0,
                    'attachments_count' => 0,
                    'opened_at_label'   => $d->opened_at?->format('d/m/Y H:i'),
                    'opened_ago'        => $d->opened_at?->locale('fr')->diffForHumans(),
                    'resolved_at_label' => $d->resolved_at?->format('d/m/Y H:i'),
                ]);

            return response()->json($disputes);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file'  => basename($e->getFile()),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // DÉTAIL (messages + pièces jointes)
    // ══════════════════════════════════════════════════════════════

    public function adminShow(Dispute $dispute): JsonResponse
    {
        $dispute->load(['mission.client.user', 'mission.contractor.user', 'admin', 'attachments.uploader']);

        $messages = $dispute->messages()
            ->with('sender')
            ->get()
            ->map(fn(DisputeMessage $m) => [
                'id'          => $m->id,
                'sender_role' => $m->sender_role,
                'sender_name' => $m->sender?->name ?? ucfirst($m->sender_role),
                'body'        => $m->body,
                'is_internal' => $m->is_internal,
                'created_at'  => $m->created_at?->format('d/m/Y H:i'),
                'ago'         => $m->created_at?->locale('fr')->diffForHumans(),
            ]);

        $attachments = $dispute->attachments->map(fn(DisputeAttachment $a) => [
            'id'             => $a->id,
            'filename'       => $a->filename,
            'url'            => $a->url,
            'mime_type'      => $a->mime_type,
            'is_image'       => $a->is_image,
            'size_formatted' => $a->size_formatted,
            'uploader_role'  => $a->uploader_role,
            'uploader_name'  => $a->uploader?->name ?? ucfirst($a->uploader_role),
            'created_at'     => $a->created_at?->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'dispute'     => $this->formatDispute($dispute),
            'messages'    => $messages,
            'attachments' => $attachments,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // CRÉER UN LITIGE (admin uniquement)
    // ══════════════════════════════════════════════════════════════

    public function adminStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mission_id'  => 'required|exists:missions,id',
            'subject'     => 'required|string|max:150',
            'description' => 'required|string|max:2000',
        ]);

        $mission = Mission::with(['client', 'contractor'])->findOrFail($data['mission_id']);

        // Vérifier qu'il n'y a pas déjà un litige ouvert sur cette mission
        $existing = Dispute::where('mission_id', $mission->id)
            ->whereNotIn('status', ['resolved_client', 'resolved_contractor', 'closed'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Un litige est déjà ouvert sur cette mission.'], 422);
        }

        $dispute = Dispute::create([
            'mission_id'    => $mission->id,
            'client_id'     => $mission->client_id,
            'contractor_id' => $mission->contractor_id,
            'admin_id'      => Auth::id(),
            'subject'       => $data['subject'],
            'description'   => $data['description'],
            'status'        => 'open',
            'opened_at'     => now(),
        ]);

        // Message automatique de création
        DisputeMessage::create([
            'dispute_id'  => $dispute->id,
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => "Litige ouvert par l'administrateur. Motif : {$data['description']}",
            'is_internal' => false,
        ]);

        // ── Notifications aux deux parties ────────────────────────
        $dispute->loadMissing(['client.user', 'contractor.user']);

        $notifTitle = "⚖️ Litige ouvert — #{$dispute->id}";
        $notifBody  = "Un litige a été ouvert sur votre mission « {$mission->service} ». Objet : {$data['subject']}";

        $clientUser = $dispute->client?->user;
        if ($clientUser) {
            $clientUser->notify(new AppNotification(
                event: 'dispute.opened',
                title: $notifTitle,
                body:  $notifBody,
                url:   '/',
                icon:  'scale',
                extra: ['dispute_id' => $dispute->id, 'mission_id' => $mission->id],
            ));
        }

        $contractorUser = $dispute->contractor?->user;
        if ($contractorUser) {
            $contractorUser->notify(new AppNotification(
                event: 'dispute.opened',
                title: $notifTitle,
                body:  $notifBody,
                url:   '/',
                icon:  'scale',
                extra: ['dispute_id' => $dispute->id, 'mission_id' => $mission->id],
            ));
        }

        return response()->json($this->formatDispute($dispute->load(['mission', 'client.user', 'contractor.user', 'admin'])), 201);
    }

    // ══════════════════════════════════════════════════════════════
    // ENVOYER UN MESSAGE
    // ══════════════════════════════════════════════════════════════

    public function adminSendMessage(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'body'        => 'required|string|max:2000',
            'is_internal' => 'boolean',
        ]);

        $message = DisputeMessage::create([
            'dispute_id'  => $dispute->id,
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => $request->body,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Prendre en charge si pas encore fait
        if ($dispute->status === 'open') {
            $dispute->update(['status' => 'in_progress', 'admin_id' => Auth::id()]);
        }

        return response()->json([
            'message' => [
                'id'          => $message->id,
                'sender_role' => 'admin',
                'sender_name' => Auth::user()->name,
                'body'        => $message->body,
                'is_internal' => $message->is_internal,
                'created_at'  => $message->created_at->format('d/m/Y H:i'),
                'ago'         => $message->created_at->locale('fr')->diffForHumans(),
            ],
            'dispute_status' => $dispute->fresh()->status,
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // UPLOADER UNE PIÈCE JOINTE
    // ══════════════════════════════════════════════════════════════

    public function adminUploadAttachment(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx',
        ]);

        $file = $request->file('file');
        $path = $file->store("disputes/{$dispute->id}", 'public');

        $attachment = DisputeAttachment::create([
            'dispute_id'    => $dispute->id,
            'uploader_id'   => Auth::id(),
            'uploader_role' => 'admin',
            'filename'      => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'id'             => $attachment->id,
            'filename'       => $attachment->filename,
            'url'            => $attachment->url,
            'mime_type'      => $attachment->mime_type,
            'is_image'       => $attachment->is_image,
            'size_formatted' => $attachment->size_formatted,
            'uploader_role'  => 'admin',
            'uploader_name'  => Auth::user()->name,
            'created_at'     => $attachment->created_at->format('d/m/Y H:i'),
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════
    // RENDRE UN VERDICT
    // ══════════════════════════════════════════════════════════════

    public function adminVerdict(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate([
            'verdict'            => ['required', Rule::in(['client', 'contractor', 'shared'])],
            'verdict_note'       => 'nullable|string|max:2000',
            'suspend_contractor' => 'boolean',
        ]);

        $statusMap = [
            'client'     => 'resolved_client',
            'contractor' => 'resolved_contractor',
            'shared'     => 'closed',
        ];

        $dispute->update([
            'verdict'      => $request->verdict,
            'verdict_note' => $request->verdict_note,
            'status'       => $statusMap[$request->verdict],
            'resolved_at'  => now(),
        ]);

        // Suspendre le prestataire si demandé
        if ($request->boolean('suspend_contractor') && $dispute->contractor?->user_id) {
            User::where('id', $dispute->contractor->user_id)->update(['status' => 'suspended']);
            $dispute->update(['contractor_suspended' => true]);
        }

        // Message automatique de verdict
        $verdictLabel = Dispute::VERDICT_LABELS[$request->verdict];
        $noteText     = $request->verdict_note ? " — {$request->verdict_note}" : "";

        DisputeMessage::create([
            'dispute_id'  => $dispute->id,
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => "✅ Verdict rendu : {$verdictLabel}{$noteText}",
            'is_internal' => false,
        ]);

        // ── Notifications aux deux parties ────────────────────────
        $dispute->loadMissing(['client.user', 'contractor.user']);

        $notifTitle = "⚖️ Verdict rendu — Litige #{$dispute->id}";
        $notifBody  = "Verdict : {$verdictLabel}." . ($request->verdict_note ? " {$request->verdict_note}" : "");
        $notifUrl   = "/";

        // Notifier le client
        $clientUser = $dispute->client?->user;
        if ($clientUser) {
            $clientUser->notify(new AppNotification(
                event: 'dispute.verdict',
                title: $notifTitle,
                body:  $notifBody . ($request->verdict === 'client'
                    ? " La décision est en votre faveur."
                    : " La décision n'est pas en votre faveur."),
                url:   $notifUrl,
                icon:  'scale',
                extra: ['dispute_id' => $dispute->id, 'verdict' => $request->verdict],
            ));
        }

        // Notifier le prestataire
        $contractorUser = $dispute->contractor?->user;
        if ($contractorUser) {
            $suspendedNote = $request->boolean('suspend_contractor')
                ? " Votre compte a été suspendu suite à ce verdict."
                : "";

            $contractorUser->notify(new AppNotification(
                event: 'dispute.verdict',
                title: $notifTitle,
                body:  $notifBody . ($request->verdict === 'contractor'
                    ? " La décision est en votre faveur."
                    : " La décision n'est pas en votre faveur.") . $suspendedNote,
                url:   $notifUrl,
                icon:  'scale',
                extra: ['dispute_id' => $dispute->id, 'verdict' => $request->verdict],
            ));
        }

        return response()->json($this->formatDispute($dispute->fresh(['mission', 'client.user', 'contractor.user', 'admin'])));
    }

    // ══════════════════════════════════════════════════════════════
    // CLÔTURER SANS VERDICT
    // ══════════════════════════════════════════════════════════════

    public function adminClose(Request $request, Dispute $dispute): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $dispute->update([
            'status'      => 'closed',
            'resolved_at' => now(),
        ]);

        $reasonText = $request->reason ? " Motif : {$request->reason}" : "";

        DisputeMessage::create([
            'dispute_id'  => $dispute->id,
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => "🔒 Litige clôturé sans verdict.{$reasonText}",
            'is_internal' => false,
        ]);

        // ── Notifications aux deux parties ────────────────────────
        $dispute->loadMissing(['client.user', 'contractor.user']);

        $notifTitle = "🔒 Litige #{$dispute->id} clôturé";
        $notifBody  = "Le litige « {$dispute->subject} » a été clôturé par l'administration.{$reasonText}";

        $clientUser = $dispute->client?->user;
        if ($clientUser) {
            $clientUser->notify(new AppNotification(
                event: 'dispute.closed',
                title: $notifTitle,
                body:  $notifBody,
                url:   '/',
                icon:  'lock',
                extra: ['dispute_id' => $dispute->id],
            ));
        }

        $contractorUser = $dispute->contractor?->user;
        if ($contractorUser) {
            $contractorUser->notify(new AppNotification(
                event: 'dispute.closed',
                title: $notifTitle,
                body:  $notifBody,
                url:   '/',
                icon:  'lock',
                extra: ['dispute_id' => $dispute->id],
            ));
        }

        return response()->json(['success' => true, 'status' => 'closed']);
    }

    // ══════════════════════════════════════════════════════════════
    // MISSIONS DISPONIBLES (pour le sélecteur de création)
    // ══════════════════════════════════════════════════════════════

    public function adminMissions(Request $request): JsonResponse
    {
        $q = $request->query('q', '');

        $missions = Mission::with(['client.user', 'contractor.user'])
            ->whereNotNull('contractor_id')
            ->whereIn('status', ['completed', 'closed', 'awaiting_confirm'])
            ->when($q, fn($query) => $query->where('service', 'like', "%{$q}%"))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn(Mission $m) => [
                'id'             => $m->id,
                'service'        => $m->service,
                'status_label'   => $m->status_label,
                'client_name'    => $m->client?->user?->name ?? '—',
                'contractor_name'=> $m->contractor?->user?->name ?? '—',
                'created_at'     => $m->created_at?->format('d/m/Y'),
            ]);

        return response()->json($missions);
    }

    // ══════════════════════════════════════════════════════════════
    // HELPER
    // ══════════════════════════════════════════════════════════════

    private function formatDispute(Dispute $d): array
    {
        return [
            'id'                   => $d->id,
            'subject'              => $d->subject,
            'description'          => $d->description,
            'status'               => $d->status,
            'status_label'         => $d->status_label,
            'is_resolved'          => $d->is_resolved,
            'verdict'              => $d->verdict,
            'verdict_label'        => $d->verdict_label,
            'verdict_note'         => $d->verdict_note,
            'contractor_suspended' => $d->contractor_suspended,
            'mission_id'           => $d->mission_id,
            'mission_service'      => $d->mission?->service ?? '—',
            'mission_status'       => $d->mission?->status_label ?? '—',
            // Client : Dispute->client est un Client, Client->user est un User
            'client_name'          => $d->client?->user?->name ?? ($d->client?->first_name ? $d->client->first_name . ' ' . $d->client->last_name : '—'),
            'client_email'         => $d->client?->user?->email ?? '—',
            // Contractor : Dispute->contractor est un Contractor, Contractor->user est un User
            'contractor_name'      => $d->contractor?->user?->name ?? ($d->contractor?->first_name ? $d->contractor->first_name . ' ' . $d->contractor->last_name : '—'),
            'contractor_email'     => $d->contractor?->user?->email ?? '—',
            'admin_name'           => $d->admin?->name ?? '—',
            'messages_count'       => $d->messages_count ?? 0,
            'attachments_count'    => $d->attachments_count ?? 0,
            'opened_at_label'      => $d->opened_at?->format('d/m/Y H:i'),
            'opened_ago'           => $d->opened_at?->locale('fr')->diffForHumans(),
            'resolved_at_label'    => $d->resolved_at?->format('d/m/Y H:i'),
        ];
    }
}