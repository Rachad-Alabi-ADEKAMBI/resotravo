<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'preferred_place')) {
                $table->string('preferred_place')->nullable()->after('address');
            }

            if (!Schema::hasColumn('clients', 'preferred_place_latitude')) {
                $table->decimal('preferred_place_latitude', 10, 7)->nullable()->after('preferred_place');
            }

            if (!Schema::hasColumn('clients', 'preferred_place_longitude')) {
                $table->decimal('preferred_place_longitude', 10, 7)->nullable()->after('preferred_place_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'preferred_place_longitude')) {
                $table->dropColumn('preferred_place_longitude');
            }

            if (Schema::hasColumn('clients', 'preferred_place_latitude')) {
                $table->dropColumn('preferred_place_latitude');
            }

            if (Schema::hasColumn('clients', 'preferred_place')) {
                $table->dropColumn('preferred_place');
            }
        });
    }
};
