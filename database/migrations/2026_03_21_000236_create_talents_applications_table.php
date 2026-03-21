<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_applications', function (Blueprint $table) {
            $table->id();

            // Informations personnelles
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('phone');

            // Profil professionnel
            $table->string('title');           // Titre professionnel (ex: Ingénieur en Génie Civil)
            $table->string('domain');          // Domaine d'expertise
            $table->string('level');           // Niveau d'études (BAC+3, Master, Doctorat…)
            $table->unsignedTinyInteger('exp');// Années d'expérience
            $table->text('bio');               // À propos

            // Disponibilité
            $table->enum('availability', ['immediate', 'parttime', 'mission'])->default('immediate');

            // Liens optionnels
            $table->string('linkedin')->nullable();
            $table->string('portfolio')->nullable();

            // Données JSON (diplômes, certifications, expériences)
            $table->json('diplomas');          // [{title, school, year}] — max 3
            $table->json('certifications');    // ["cert1", "cert2"…]    — max 5
            $table->json('experiences');       // [{role, company, period}]

            // Statut de la demande
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reject_reason')->nullable();

            // Si approuvé → lié à un user créé par l'admin
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_applications');
    }
};