<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_tickets', function (Blueprint $table) {
            $table->id();

            // Utilisateur à l'origine du ticket (nullable = non connecté)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Agent humain assigné (null = pas encore pris en charge)
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('subject')->nullable();          // Résumé / titre du ticket
            $table->string('category')->nullable();          // Ex : Orientation, Paiement, Inscription…

            $table->enum('status', [
                'open',         // Nouveau ticket, en attente de traitement
                'ai_handled',   // Géré uniquement par l'IA (≤ 3 messages)
                'pending_human',// IA a épuisé ses 3 messages, en attente d'agent
                'in_progress',  // Agent humain a pris en charge
                'resolved',     // Clôturé / résolu
                'closed',       // Fermé sans résolution
            ])->default('open');

            $table->unsignedTinyInteger('ia_message_count')->default(0); // Compteur messages IA (max 3)
            $table->boolean('human_requested')->default(false);          // L'utilisateur a demandé un humain
            $table->boolean('human_assigned')->default(false);           // Agent humain assigné

            $table->text('admin_note')->nullable();          // Note interne de l'admin
            $table->integer('rating')->nullable();           // Satisfaction 1-5 (optionnelle)

            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });

        Schema::create('consulting_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('consulting_tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('sender_type', ['user', 'ia', 'agent'])->default('user');
            $table->text('body');
            $table->boolean('is_read')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_messages');
        Schema::dropIfExists('consulting_tickets');
    }
};