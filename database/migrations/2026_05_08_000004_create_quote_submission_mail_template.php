<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('admin_mail_templates')
            ->where('name', 'Soumission devis')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('admin_mail_templates')->insert([
            'name' => 'Soumission devis',
            'subject' => 'Votre devis Mesotravo est disponible - Mission #{mission_id}',
            'body' => '<p>Bonjour {first_name},</p>'
                . '<p>Un devis vient d&apos;etre soumis pour votre mission <strong>{service}</strong>.</p>'
                . '<p>Prestataire : <strong>{contractor_name}</strong><br>Montant total : <strong>{amount} FCFA</strong></p>'
                . '<p>Le devis est joint a cet email en PDF. Vous pouvez aussi le consulter et l&apos;approuver depuis votre espace client.</p>'
                . '<p><a href="{url}">Consulter mon devis</a></p>',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('admin_mail_templates')
            ->where('name', 'Soumission devis')
            ->where('subject', 'Votre devis Mesotravo est disponible - Mission #{mission_id}')
            ->delete();
    }
};
