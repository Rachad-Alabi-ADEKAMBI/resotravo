<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Ex : "Plomberie"
            $table->string('slug')->unique();                // Ex : "plomberie"
            $table->string('icon', 10)->default('🔨');      // Emoji stocké en UTF-8
            $table->text('description')->nullable();         // Courte description affichée sur la home
            $table->string('category')->nullable();          // Ex : "Artisanat", "Technique"
            $table->enum('accreditation', ['domicile', 'entreprise', 'both'])->default('both');
            $table->boolean('admin_only')->default(false);   // Géré exclusivement par l'admin (ex : Nettoyage)
            $table->boolean('is_suggestion')->default(false);// Issu d'une suggestion utilisateur
            $table->boolean('is_visible')->default(true);    // Affiché sur la page d'accueil
            $table->integer('sort_order')->default(0);       // Ordre d'affichage
            $table->enum('status', ['active', 'pending', 'archived', 'rejected'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};