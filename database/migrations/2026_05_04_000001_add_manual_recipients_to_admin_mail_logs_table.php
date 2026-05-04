<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_mail_logs', function (Blueprint $table) {
            $table->json('manual_recipients')->nullable()->after('user_ids');
        });
    }

    public function down(): void
    {
        Schema::table('admin_mail_logs', function (Blueprint $table) {
            $table->dropColumn('manual_recipients');
        });
    }
};
