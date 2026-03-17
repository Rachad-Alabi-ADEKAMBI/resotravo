<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Quote header ─────────────────────────────────────────────
        Schema::create('mission_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mission_id')
                  ->constrained('missions')
                  ->cascadeOnDelete();

            // Contractor's note after diagnosis on site
            $table->text('diagnosis')->nullable();

            // Totals — calculated from line items
            $table->decimal('amount_excl_tax', 10, 2)->default(0);
            $table->decimal('amount_incl_tax', 10, 2)->default(0);

            $table->enum('status', [
                'draft',     // Being filled in by contractor
                'submitted', // Sent to client for review
                'approved',  // Client approved (mission moves to order_placed)
                'rejected',  // Client rejected with a reason
                'revised',   // Contractor submitted a new version
            ])->default('draft');

            // Client's comment when rejecting the quote
            $table->text('rejection_reason')->nullable();

            // Incremented each time the contractor submits a revision
            $table->unsignedTinyInteger('version')->default(1);

            $table->timestamps();
        });

        // ── Quote line items ─────────────────────────────────────────
        Schema::create('mission_quote_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                  ->constrained('mission_quotes')
                  ->cascadeOnDelete();

            $table->enum('type', [
                'part',    // Spare part / material
                'labor',   // Labor / man-hours
                'travel',  // Travel / call-out fee
                'other',   // Miscellaneous
            ]);

            $table->string('description');                          // e.g. "PVC pipe 50mm"
            $table->decimal('quantity',   8, 2)->default(1);
            $table->decimal('unit_price', 10, 2);

            // Stored generated column — auto-computed by the DB
            $table->decimal('total', 10, 2)->storedAs('quantity * unit_price');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_quote_items');
        Schema::dropIfExists('mission_quotes');
    }
};