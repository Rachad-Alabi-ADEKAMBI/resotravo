<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Table : appels d'offres ───────────────────────────────
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();

            // Auteur (entreprise cliente)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Contenu
            $table->string('title');
            $table->string('domain');
            $table->string('location');
            $table->string('duration');
            $table->string('budget')->nullable();          // texte libre : "250 000 FCFA"
            $table->date('deadline');
            $table->enum('profile_type', ['prestataire', 'talent', 'both']);
            $table->text('description');
            $table->text('requirements')->nullable();

            // Métadonnées
            $table->enum('status', ['pending', 'published', 'rejected', 'closed'])
                  ->default('pending');
            $table->string('reject_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('candidatures_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deadline']);
            $table->index('domain');
        });

        // ── Table : candidatures aux AO ───────────────────────────
        Schema::create('tender_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // prestataire ou talent

            $table->text('motivation');
            $table->string('tarif')->nullable();
            $table->string('disponibilite')->nullable();

            $table->enum('status', ['pending', 'reviewed', 'selected', 'rejected'])
                  ->default('pending');

            $table->timestamps();

            // Un utilisateur ne peut postuler qu'une fois par AO
            $table->unique(['tender_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_applications');
        Schema::dropIfExists('tenders');
    }
};