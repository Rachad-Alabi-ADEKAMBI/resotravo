<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'commission_diagnostic',  'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'commission_main_oeuvre',  'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_icon_mode',  'value' => 'current', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
