<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_meditations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->longText('content');
            $table->string('author')->nullable();
            $table->string('scripture_reference')->nullable();
            $table->foreignUuid('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->date('publication_date')->unique();
            $table->timestamp('notification_created_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->foreignUuid('daily_meditation_id')->nullable()->unique()->after('daily_verse_id')
                ->constrained('daily_meditations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_meditation_id');
        });
        Schema::dropIfExists('daily_meditations');
    }
};
