<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Conversations ─────────────────────────────────────────
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // type : mission = échange prestataire↔client lié à une mission
            //        support  = Allo Conseils IA + agent humain
            $table->enum('type', ['mission', 'support'])->default('mission');

            // Lié à une mission (nullable pour le support)
            $table->foreignId('mission_id')
                ->nullable()
                ->constrained('missions')
                ->nullOnDelete();

            // Titre affiché dans la liste des conversations
            $table->string('title')->nullable();

            // Dernier message (pour affichage rapide dans la liste)
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->index(['mission_id']);
            $table->index(['type', 'last_message_at']);
        });

        // ── Participants ──────────────────────────────────────────
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Rôle dans la conversation : client, contractor, admin, support_agent
            $table->string('role')->default('client');

            // Date de dernière lecture pour les statuts lu/non-lu
            $table->timestamp('last_read_at')->nullable();

            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
        });

        // ── Messages ──────────────────────────────────────────────
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Corps du message (texte)
            $table->text('body')->nullable();

            // Pièce jointe (chemin storage)
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable(); // nom original
            $table->string('attachment_type')->nullable(); // image | file

            // Type de message : text | image | file | system
            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};