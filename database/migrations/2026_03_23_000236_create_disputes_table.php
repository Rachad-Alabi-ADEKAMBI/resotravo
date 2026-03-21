<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            // Mission concernée
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();

            // Parties — sans FK explicite pour éviter conflits de type MySQL
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('contractor_id')->nullable();

            // Admin
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('subject');
            $table->text('description');

            $table->enum('status', [
                'open', 'in_progress', 'resolved_client', 'resolved_contractor', 'closed',
            ])->default('open');

            $table->enum('verdict', ['client', 'contractor', 'shared'])->nullable();
            $table->text('verdict_note')->nullable();
            $table->boolean('contractor_suspended')->default(false);

            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->enum('sender_role', ['admin', 'client', 'contractor']);
            $table->text('body')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('dispute_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->enum('uploader_role', ['admin', 'client', 'contractor']);
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_attachments');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
    }
};