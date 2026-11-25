<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `SENT` ne dit pas combien de telephones ont recu la notification : un envoi
     * dont tous les tokens etaient perimes reste SENT, a juste titre. Ces trois
     * compteurs rendent le resultat lisible dans le panel.
     */
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->unsignedInteger('delivered_count')->default(0)->after('retry_count');
            $table->unsignedInteger('pruned_count')->default(0)->after('delivered_count');
            $table->unsignedInteger('failed_count')->default(0)->after('pruned_count');
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropColumn(['delivered_count', 'pruned_count', 'failed_count']);
        });
    }
};
