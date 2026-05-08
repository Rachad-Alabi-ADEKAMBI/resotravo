<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_proposal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('mission_proposal_id')->nullable()->constrained('mission_proposals')->nullOnDelete();
            $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->string('event', 40);
            $table->string('status', 40)->nullable();
            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['mission_id', 'contractor_user_id']);
            $table->index(['mission_proposal_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_proposal_logs');
    }
};
