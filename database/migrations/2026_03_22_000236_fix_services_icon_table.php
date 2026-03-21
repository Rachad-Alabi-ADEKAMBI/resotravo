<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Les emojis composés peuvent dépasser 10 bytes en UTF-8
            // Ex: 🏗️ = 7 bytes, 🛡️ = 7 bytes, certains combos encore plus
            $table->string('icon', 20)->default('🔨')->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('icon', 10)->default('🔨')->change();
        });
    }
};