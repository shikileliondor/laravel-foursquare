<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lien de don national fourni par l'organisation. Ajouté seulement s'il n'est
 * pas déjà réglé : une valeur saisie dans le panel n'est jamais écrasée.
 */
return new class extends Migration
{
    private const URL = 'https://pay.wave.com/m/M_ci_XF7Fts2YKKpD/c/ci/';

    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key' => 'donation_url',
            'value' => self::URL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'donation_url')->where('value', self::URL)->delete();
    }
};
