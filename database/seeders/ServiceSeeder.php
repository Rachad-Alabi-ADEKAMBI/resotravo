<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Service::seedData() as $data) {
            Service::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                array_merge([
                    'accreditation' => 'both',
                    'admin_only'    => false,
                    'is_suggestion' => false,
                    'is_visible'    => true,
                    'status'        => 'active',
                ], $data)
            );
        }

        $this->command->info('✅ ' . Service::count() . ' services insérés.');
    }
}