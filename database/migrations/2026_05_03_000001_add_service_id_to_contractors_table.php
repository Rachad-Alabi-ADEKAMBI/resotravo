<?php

use App\Models\Contractor;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->foreignId('service_id')
                ->nullable()
                ->after('user_id')
                ->constrained('services')
                ->nullOnDelete();
        });

        Contractor::whereNull('service_id')
            ->get()
            ->each(function (Contractor $contractor) {
                $service = Service::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($contractor->specialty))])->first();

                if ($service) {
                    $contractor->update(['service_id' => $service->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
