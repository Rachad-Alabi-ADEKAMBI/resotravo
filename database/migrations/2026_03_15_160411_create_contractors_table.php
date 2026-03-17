<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Personal info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->string('city')->default('Cotonou');
            $table->string('profile_picture')->nullable();

            // Professional info
            $table->string('specialty');
            $table->json('specialties')->nullable();
            $table->string('intervention_zone');
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->text('bio')->nullable();

            // Accreditation
            $table->enum('accreditation', ['none', 'home', 'business', 'both'])->default('none');

            // Availability
            $table->boolean('available')->default(true);
            $table->time('start_time')->default('08:00');
            $table->time('end_time')->default('18:00');
            $table->json('working_days')->nullable();

            // Stats
            $table->unsignedInteger('total_missions')->default(0);
            $table->unsignedInteger('completed_missions')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);

            // GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('radius_km')->default(10);

            $table->timestamps();
            $table->index('specialty');
            $table->index('city');
            $table->index('available');
            $table->index('accreditation');
            $table->index('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};