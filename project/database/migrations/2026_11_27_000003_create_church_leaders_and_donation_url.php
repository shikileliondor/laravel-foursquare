<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Équipe pastorale et lien de don propres à chaque église.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_leaders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('church_id')->constrained('churches')->cascadeOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['church_id', 'is_published', 'display_order']);
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->string('donation_url')->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn('donation_url');
        });

        Schema::dropIfExists('church_leaders');
    }
};
