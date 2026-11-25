<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime le niveau Zone : chaque église est rattachée directement à un district.
 *
 * - chaque église prend le district de sa zone ;
 * - les actualités, événements et notifications visant une zone visent son district ;
 * - les administrateurs ZONE_ADMIN deviennent DISTRICT_ADMIN ;
 * - la table zones est conservée sous le nom zones_archive (plus aucun code ne
 *   s'en sert) : la supprimer une fois la migration validée en production.
 *
 * Irréversible : en cas de problème, restaurer la sauvegarde de la base faite
 * avant `php artisan migrate`.
 */
return new class extends Migration
{
    private const CONTENT_TABLES = ['news', 'events'];

    public function up(): void
    {
        // Vérifié avant toute modification : sur MySQL, une migration
        // interrompue ne s'annule pas.
        $this->assertEveryChurchHasAZone();

        Schema::table('churches', function (Blueprint $table) {
            $table->foreignUuid('district_id')->nullable()->after('zone_id')
                ->constrained('districts')->restrictOnDelete();
        });

        foreach (DB::table('zones')->get(['id', 'district_id']) as $zone) {
            DB::table('churches')->where('zone_id', $zone->id)
                ->update(['district_id' => $zone->district_id]);

            foreach (self::CONTENT_TABLES as $table) {
                DB::table($table)->where('scope_type', 'ZONE')->where('zone_id', $zone->id)
                    ->update(['scope_type' => 'DISTRICT', 'district_id' => $zone->district_id]);
            }

            DB::table('push_notifications')->where('audience', 'ZONE')->where('zone_id', $zone->id)
                ->update(['audience' => 'DISTRICT', 'district_id' => $zone->district_id]);

            // Le district d'un appareil suit toujours celui de son église.
            DB::table('devices')
                ->whereIn('church_id', DB::table('churches')->select('id')->where('zone_id', $zone->id))
                ->update(['district_id' => $zone->district_id]);
        }

        $this->retireTargetsOfDeletedZones();

        DB::table('users')->where('role', 'ZONE_ADMIN')->update(['role' => 'DISTRICT_ADMIN']);

        Schema::table('churches', function (Blueprint $table) {
            $table->uuid('district_id')->nullable(false)->change();
        });

        foreach (['churches', 'news', 'events', 'push_notifications', 'devices'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('zone_id');
            });
        }

        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
        });

        Schema::rename('zones', 'zones_archive');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migration irréversible : restaurez la sauvegarde de la base faite avant la migration.',
        );
    }

    private function assertEveryChurchHasAZone(): void
    {
        $orphans = DB::table('churches')
            ->leftJoin('zones', 'zones.id', '=', 'churches.zone_id')
            ->whereNull('zones.id')
            ->pluck('churches.name');

        if ($orphans->isNotEmpty()) {
            throw new RuntimeException(
                'Églises sans zone valide, district introuvable : '.$orphans->implode(', ')
                .'. Rattachez-les à une zone existante puis relancez la migration.',
            );
        }
    }

    /**
     * Ce qui visait une zone déjà supprimée n'a plus de district connu.
     * Rien n'est effacé, mais rien ne doit partir vers une mauvaise audience :
     * les contenus sont archivés, les notifications non envoyées passent en
     * échec avec une explication.
     */
    private function retireTargetsOfDeletedZones(): void
    {
        foreach (self::CONTENT_TABLES as $table) {
            $ids = DB::table($table)->where('scope_type', 'ZONE')->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            DB::table($table)->whereIn('id', $ids)->whereNotIn('status', ['ARCHIVED', 'CANCELLED'])
                ->update(['status' => 'ARCHIVED']);
            DB::table($table)->whereIn('id', $ids)
                ->update(['scope_type' => 'NATIONAL', 'district_id' => null, 'church_id' => null]);

            Log::warning("Suppression des zones : {$table} archivés faute de zone", ['ids' => $ids->all()]);
        }

        $ids = DB::table('push_notifications')->where('audience', 'ZONE')->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('push_notifications')->whereIn('id', $ids)->where('status', '!=', 'SENT')->update([
            'status' => 'FAILED',
            'last_error' => 'La zone visée a été supprimée : choisissez une nouvelle audience avant de renvoyer.',
        ]);
        DB::table('push_notifications')->whereIn('id', $ids)
            ->update(['audience' => 'DISTRICT', 'district_id' => null, 'church_id' => null]);

        Log::warning('Suppression des zones : notifications sans zone', ['ids' => $ids->all()]);
    }
};
