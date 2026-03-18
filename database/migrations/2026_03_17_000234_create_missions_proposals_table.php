<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : mission_proposals
     *
     * Logique métier (CDC Resotravo) :
     * ─ L'admin peut proposer une mission à PLUSIEURS prestataires disponibles simultanément.
     * ─ Chaque prestataire reçoit une ligne dans cette table avec status = 'pending'.
     * ─ Le premier à accepter → son statut passe à 'accepted', les autres → 'superseded'.
     * ─ Si un prestataire ne répond pas sous 5 min (expires_at) → 'expired'.
     * ─ Si un prestataire refuse → 'rejected'.
     */
    public function up(): void
    {
        Schema::create('mission_proposals', function (Blueprint $table) {

            $table->id();

            // ── Relations ─────────────────────────────────────────
            $table->foreignId('mission_id')
                ->constrained('missions')
                ->cascadeOnDelete();

            $table->foreignId('contractor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // ── Statut de la proposition ──────────────────────────
            // pending    : envoyée, le prestataire n'a pas encore répondu
            // accepted   : le prestataire a accepté (→ il devient l'assigné de la mission)
            // rejected   : le prestataire a refusé
            // superseded : un autre prestataire a accepté avant lui
            // expired    : le prestataire n'a pas répondu avant expires_at
            $table->enum('status', ['pending', 'accepted', 'rejected', 'superseded', 'expired'])
                ->default('pending');

            // ── Horodatages métier ────────────────────────────────
            $table->timestamp('proposed_at')->useCurrent();         // quand la proposition a été envoyée
            $table->timestamp('expires_at')->nullable();            // deadline de réponse (proposed_at + 5 min)
            $table->timestamp('responded_at')->nullable();          // quand le prestataire a répondu

            // ── Raison du refus (optionnel) ───────────────────────
            $table->string('reject_reason')->nullable();

            $table->timestamps();

            // ── Contrainte unicité ────────────────────────────────
            // Un prestataire ne peut pas avoir deux propositions "actives" (pending/accepted)
            // pour la même mission en même temps
            $table->unique(['mission_id', 'contractor_id']);

            // ── Index de performance ──────────────────────────────
            $table->index(['mission_id', 'status']);
            $table->index(['contractor_id', 'status']);
            $table->index('expires_at'); // pour le job d'expiration automatique
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_proposals');
    }
};