<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute 'diagnostic' aux valeurs ENUM du type de ligne de devis.
     */
    public function up(): void
    {
        // MySQL permet de modifier un ENUM directement via ALTER TABLE
        \DB::statement("ALTER TABLE `mission_quote_items` MODIFY COLUMN `type` ENUM('part','labor','travel','other','diagnostic') NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE `mission_quote_items` MODIFY COLUMN `type` ENUM('part','labor','travel','other') NOT NULL");
    }
};
