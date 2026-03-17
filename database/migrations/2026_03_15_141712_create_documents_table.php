<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Propriétaire du document
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Type de document (cip, photo, attestation, casier, diplome, diplome_sup, cv, certification)
            $table->string('type');

            // Fichier
            $table->string('filename');           // nom original ex: CIN_Mensah.pdf
            $table->string('path');               // chemin stockage ex: documents/42/cip.pdf
            $table->string('disk')->default('private'); // jamais public

            // Statut de validation
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            // Motif de refus (rempli par l'admin)
            $table->string('reject_reason')->nullable();

            // Qui a traité + quand
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Un user ne peut soumettre qu'un seul doc par type
            $table->unique(['user_id', 'type']);

            // Index pour les requêtes fréquentes
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};