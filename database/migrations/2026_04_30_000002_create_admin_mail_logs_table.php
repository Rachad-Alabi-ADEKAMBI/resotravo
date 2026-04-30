<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->string('recipient_mode', 30);
            $table->json('roles')->nullable();
            $table->json('user_ids')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->json('failed')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_mail_logs');
    }
};
