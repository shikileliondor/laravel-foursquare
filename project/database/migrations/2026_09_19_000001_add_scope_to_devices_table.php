<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sans rattachement, une notification ne peut viser que ALL : les audiences
     * DISTRICT / ZONE / CHURCH n'ont aucun moyen de retrouver leurs appareils.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignUuid('district_id')->nullable()->after('platform')->constrained('districts')->nullOnDelete();
            $table->foreignUuid('zone_id')->nullable()->after('district_id')->constrained('zones')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->after('zone_id')->constrained('churches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('zone_id');
            $table->dropConstrainedForeignId('church_id');
        });
    }
};
