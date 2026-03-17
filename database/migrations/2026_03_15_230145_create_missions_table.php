<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();

            // ── Stakeholders ─────────────────────────────────────────
            // Both clients and contractors tables use user_id as their
            // link to the users table. Missions store the FK to each
            // pivot table's primary key (id), not user_id directly.
            $table->foreignId('client_id')
                  ->constrained('clients')   // → clients.id
                  ->cascadeOnDelete();

            $table->foreignId('contractor_id')
                  ->nullable()
                  ->constrained('contractors') // → contractors.id
                  ->nullOnDelete();

            // ── Status — 12-step workflow ────────────────────────────
            $table->enum('status', [
                'pending',          // 1  Client creates the request
                'assigned',         // 2  System assigns nearest contractor
                'accepted',         // 3  Contractor accepts
                'contact_made',     // 4  Contractor contacted the client
                'on_the_way',       // 5  GPS on, contractor en route
                'tracking',         // 6  Client tracking on map
                'in_progress',      // 7  Contractor on site, GPS off
                'quote_submitted',  // 8  Quote sent by contractor
                'order_placed',     // 9  Client approves quote (no payment yet)
                'awaiting_confirm', // 10 Contractor completes the work
                'completed',        // 11 Client confirms end of work
                'closed',           // 12 MoMo payment done, invoice generated
            ])->default('pending');

            // ── Request details ──────────────────────────────────────
            $table->string('service');
            $table->string('address');
            $table->decimal('latitude',  10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description');
            $table->json('availabilities')->nullable();

            // ── Location type ────────────────────────────────────────
            // 'business' requires contractor.accreditation = business|both
            // Validated against client.account_type on store()
            $table->enum('location_type', ['residential', 'business'])
                  ->default('residential');

            // ── Geographic privacy (from clients.min_distance_m) ─────
            // Copied from the client's preference at mission creation.
            // Contractors within this radius are excluded from assignment.
            $table->unsignedInteger('min_distance_m')->default(0);

            // ── Timestamps for key workflow steps ────────────────────
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // ── Payment ──────────────────────────────────────────────
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('commission',   10, 2)->nullable(); // 10% Resotravo
            $table->string('momo_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('invoice_path')->nullable();

            // ── Dispute ──────────────────────────────────────────────
            $table->text('reported_issue')->nullable();
            $table->boolean('dispute_open')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};