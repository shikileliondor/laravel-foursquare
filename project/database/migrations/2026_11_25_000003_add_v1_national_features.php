<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V1 nationale : catégories, fiche église complète, inscriptions aux
 * événements et paramètres de la plateforme. Uniquement des ajouts.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['news_categories', 'event_categories'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('news', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->after('cover_media_id')
                ->constrained('news_categories')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->after('cover_media_id')
                ->constrained('event_categories')->nullOnDelete();
            $table->boolean('registration_enabled')->default(false)->after('status');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->text('service_times')->nullable()->after('main_service_time');
            $table->string('facebook_url')->nullable()->after('official_email');
            $table->string('youtube_url')->nullable()->after('facebook_url');
            $table->string('instagram_url')->nullable()->after('youtube_url');
            $table->string('tiktok_url')->nullable()->after('instagram_url');
            $table->string('website_url')->nullable()->after('tiktok_url');
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->foreignUuid('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('church_name')->nullable();
            $table->timestamps();

            // Une personne (un numéro) s'inscrit une fois par événement.
            $table->unique(['event_id', 'phone']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('event_registrations');

        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['city', 'service_times', 'facebook_url', 'youtube_url', 'instagram_url', 'tiktok_url', 'website_url']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('registration_enabled');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('news_categories');
    }
};
