<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Personal info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->string('city')->default('Cotonou');
            $table->string('profile_picture')->nullable();

            // Account type
            $table->enum('account_type', ['individual', 'company'])->default('individual');
            $table->string('company_name')->nullable();
            $table->string('business_sector')->nullable();

            // Privacy setting
            $table->unsignedInteger('min_distance_m')->default(0);

            // Stats
            $table->unsignedInteger('total_missions')->default(0);
            $table->unsignedInteger('completed_missions')->default(0);
            $table->json('saved_addresses')->nullable();

            $table->timestamps();
            $table->index('city');
            $table->index('account_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};