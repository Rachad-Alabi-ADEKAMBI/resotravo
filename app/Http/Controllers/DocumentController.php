<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * GET /documents
     * List authenticated user's documents.
     */
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())
            ->orderBy('type')
            ->get()
            ->keyBy('type');

        $required = Document::requiredFor(Auth::user()->role);

        $result = collect($required)->map(function ($type) use ($documents) {
            $doc = $documents->get($type);
            return [
                'type'          => $type,
                'label'         => Document::$types[$type]['label'] ?? $type,
                'icon'          => Document::$types[$type]['icon']  ?? '📎',
                'status'        => $doc ? $doc->status       : 'missing',
                'status_label'  => $doc ? $doc->status_label : 'Manquant',
                'filename'      => $doc?->filename,
                'reject_reason' => $doc?->reject_reason,
                'id'            => $doc?->id,
            ];
        })->values();

        return response()->json([
            'documents' => $result,
            'progress'  => Auth::user()->documentProgress(),
        ]);
    }

    /**
     * POST /documents/upload
     * Upload a document.
     */
    public function upload(Request $request)
    {
        $allowed = implode(',', array_keys(Document::$types));

        $request->validate([
            'type' => ['required', "in:{$allowed}"],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $user = Auth::user();
        $type = $request->type;

        // Check the type is required for this role
        $required = Document::requiredFor($user->role);
        if (!empty($required) && !in_array($type, $required)) {
            return response()->json([
                'message' => 'This document type is not required for your role.',
            ], 422);
        }

        // Delete old file if exists
        $existing = Document::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->path);
        }

        $path = $request->file('file')->store("documents/{$user->id}", 'private');

        $document = Document::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            [
                'filename'      => $request->file('file')->getClientOriginalName(),
                'path'          => $path,
                'disk'          => 'private',
                'status'        => 'pending',
                'reject_reason' => null,
                'reviewed_by'   => null,
                'reviewed_at'   => null,
            ]
        );

        return response()->json([
            'message'  => 'Document uploaded successfully.',
            'document' => $document,
            'progress' => $user->documentProgress(),
        ]);
    }

    /**
     * POST /documents/{document}/approve
     * Admin: approve a document.
     */
    public function approve(Document $document)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $document->update([
            'status'        => 'approved',
            'reviewed_by'   => Auth::id(),
            'reviewed_at'   => now(),
            'reject_reason' => null,
        ]);

        // Certify user if all required documents are approved
        if ($document->user->isCertified()) {
            $document->user->update(['status' => 'approved']);
        }

        return response()->json([
            'message'  => 'Document approved.',
            'progress' => $document->user->documentProgress(),
        ]);
    }

    /**
     * POST /documents/{document}/reject
     * Admin: reject a document.
     */
    public function reject(Request $request, Document $document)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document->update([
            'status'        => 'rejected',
            'reviewed_by'   => Auth::id(),
            'reviewed_at'   => now(),
            'reject_reason' => $request->reason,
        ]);

        // Revert user certification
        $document->user->update(['status' => 'pending']);

        return response()->json(['message' => 'Document rejected.']);
    }

    /**
     * GET /documents/{document}/download
     * Admin: secure stream download.
     */
    public function download(Document $document)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        abort_unless(
            Storage::disk($document->disk)->exists($document->path),
            404,
            'File not found.'
        );

        return Storage::disk($document->disk)
            ->download($document->path, $document->filename);
    }

    /**
     * DELETE /documents/{document}
     */
    public function destroy(Document $document)
    {
        $user = Auth::user();

        abort_unless(
            $user->role === 'admin' || $document->user_id === $user->id,
            403
        );

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }

    /**
     * GET /admin/documents/dossiers
     * Liste tous les utilisateurs avec leurs documents pour l'admin.
     */
    public function dossiers()
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $users = \App\Models\User::with(['documents', 'contractor', 'client'])
            ->whereIn('role', ['contractor', 'client', 'talent'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($user) {
                $required = Document::requiredFor($user->role);
                $docs     = $user->documents->keyBy('type');

                $documents = collect($required)->map(function ($type) use ($docs) {
                    $doc = $docs->get($type);
                    return [
                        'id'            => $doc?->id,
                        'type'          => $type,
                        'label'         => Document::$types[$type]['label'] ?? $type,
                        'icon'          => Document::$types[$type]['icon']  ?? '📎',
                        'filename'      => $doc?->filename,
                        'status'        => $doc ? $doc->status : 'missing',
                        'reject_reason' => $doc?->reject_reason,
                    ];
                })->values();

                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'status'     => $user->status,
                    'created_at' => $user->created_at->toISOString(),
                    'contractor' => $user->contractor ? [
                        'specialty'         => $user->contractor->specialty,
                        'intervention_zone' => $user->contractor->intervention_zone,
                        'experience_years'  => $user->contractor->experience_years,
                    ] : null,
                    'documents'  => $documents,
                ];
            });

        return response()->json($users);
    }

    /**
     * PATCH /admin/users/{user}/status
     * Mettre à jour le statut d'un utilisateur (admin).
     * Déclenche aussi la certification automatique.
     */
    public function updateUserStatus(\App\Models\User $user, \Illuminate\Http\Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,incomplete,review'],
        ]);

        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'User status updated.',
            'status'  => $user->status,
            'certified' => $user->isCertified(),
        ]);
    }
}