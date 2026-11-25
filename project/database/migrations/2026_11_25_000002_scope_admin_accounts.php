<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un compte n'a plus de rôle par défaut : l'ancien défaut SUPER_ADMIN donnait
 * tous les droits à n'importe quel inscrit. Un administrateur de district ou
 * d'église est rattaché à son périmètre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->default(null)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('district_id')->nullable()->after('role')
                ->constrained('districts')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->after('district_id')
                ->constrained('churches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('church_id');
            $table->dropConstrainedForeignId('district_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable(false)->default('SUPER_ADMIN')->change();
        });
    }
};
