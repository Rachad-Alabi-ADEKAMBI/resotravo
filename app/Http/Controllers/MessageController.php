<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * GET /conversations
     * Liste toutes les conversations de l'utilisateur connecté.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['participants', 'mission'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn($c) => $this->formatConversation($c, $user->id));

        return response()->json($conversations);
    }

    /**
     * POST /conversations/mission/{mission}
     * Récupère ou crée la conversation liée à une mission.
     * Ajoute l'utilisateur comme participant si pas encore dedans.
     */
    public function findOrCreateForMission(Mission $mission): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est client ou prestataire de la mission
        $clientUserId     = $mission->client?->user_id;
        $contractorUserId = $mission->contractor?->user_id;

        if ($user->id !== $clientUserId && $user->id !== $contractorUserId && $user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $conversation = Conversation::forMission($mission);

        // S'assurer que l'utilisateur est bien participant (cas prestataire ajouté après)
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            $role = match ($user->role) {
                'contractor' => 'contractor',
                'client'     => 'client',
                default      => 'admin',
            };
            $conversation->participants()->attach($user->id, ['role' => $role]);
        }

        // Charger les messages récents
        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        // Marquer comme lus
        $this->markRead($conversation, $user->id);

        return response()->json([
            'conversation' => $this->formatConversation($conversation, $user->id),
            'messages'     => $messages->map->toArray(),
        ]);
    }

    /**
     * GET /conversations/{conversation}/messages
     * Polling — retourne les messages après un certain ID (pour ne pas tout recharger).
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $afterId = $request->query('after_id', 0);

        $messages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->get()
            ->map->toArray();

        // Marquer comme lus si on poll
        if ($messages->count() > 0) {
            $this->markRead($conversation, $user->id);
        }

        return response()->json([
            'messages'     => $messages,
            'unread_count' => 0, // déjà marqué comme lu
        ]);
    }

    /**
     * POST /conversations/{conversation}/messages
     * Envoyer un message texte.
     */
    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $request->body,
            'type'            => 'text',
        ]);

        $message->load('sender');

        // Mettre à jour le résumé de la conversation
        $conversation->update([
            'last_message'    => mb_substr($request->body, 0, 100),
            'last_message_at' => now(),
        ]);

        return response()->json([
            'message' => $message->toArray(),
        ], 201);
    }

    /**
     * POST /conversations/{conversation}/attachment
     * Envoyer une photo ou un fichier.
     */
    public function sendAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx',
        ]);

        $file      = $request->file('file');
        $mime      = $file->getMimeType();
        $isImage   = str_starts_with($mime, 'image/');
        $type      = $isImage ? 'image' : 'file';
        $path      = $file->store("conversations/{$conversation->id}", 'public');

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => null,
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_type' => $type,
            'type'            => $type,
        ]);

        $message->load('sender');

        $conversation->update([
            'last_message'    => $isImage ? '📷 Photo' : '📎 ' . $file->getClientOriginalName(),
            'last_message_at' => now(),
        ]);

        return response()->json([
            'message' => $message->toArray(),
        ], 201);
    }

    /**
     * PATCH /conversations/{conversation}/read
     * Marquer tous les messages comme lus.
     */
    public function read(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();
        $this->markRead($conversation, $user->id);

        return response()->json(['success' => true]);
    }

    // ── Helpers privés ───────────────────────────────────────────

    private function markRead(Conversation $conversation, int $userId): void
    {
        $conversation->participants()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    private function formatConversation(Conversation $c, int $userId): array
    {
        // Trouver l'autre participant (pas soi-même)
        $other = $c->participants->first(fn($p) => $p->id !== $userId);

        return [
            'id'              => $c->id,
            'type'            => $c->type,
            'mission_id'      => $c->mission_id,
            'title'           => $c->title ?? ($c->mission?->service ?? 'Conversation'),
            'last_message'    => $c->last_message,
            'last_message_at' => $c->last_message_at?->locale('fr')->diffForHumans(),
            'unread_count'    => $c->unreadCountFor($userId),
            'other_name'      => $other?->name ?? '—',
            'other_role'      => $other?->role ?? '',
            'mission_service' => $c->mission?->service,
            'mission_address' => $c->mission?->address,
            'mission_status'  => $c->mission?->status,
        ];
    }
}