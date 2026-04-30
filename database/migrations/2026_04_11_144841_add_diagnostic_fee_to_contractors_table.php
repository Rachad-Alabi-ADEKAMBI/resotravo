<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->decimal('diagnostic_fee', 10, 2)->default(0)->after('radius_km')
                  ->comment('Montant souhaité par le prestataire pour le diagnostic (FCFA)');
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('diagnostic_fee');
        });
    }
};
