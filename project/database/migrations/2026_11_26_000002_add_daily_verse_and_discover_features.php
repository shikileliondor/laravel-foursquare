<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_verses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('text');
            $table->string('reference');
            $table->string('bible_version');
            $table->date('publication_date')->unique();
            $table->timestamp('notification_created_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('discover_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->foreignUuid('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignUuid('illustration_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order');
            $table->string('status')->default('DRAFT');
            $table->timestamps();
        });

        Schema::create('national_leaders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('position');
            $table->foreignUuid('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        $now = now();
        foreach ([
            ['history', 'Notre histoire'],
            ['vision-missions', 'Notre vision et nos missions'],
            ['doctrine', 'Notre doctrine'],
            ['national-office', 'Le bureau national'],
            ['organization', 'Notre organisation'],
            ['districts-churches', 'Nos districts et églises'],
        ] as $order => [$key, $title]) {
            DB::table('discover_sections')->insert([
                'id' => (string) Str::uuid(),
                'key' => $key,
                'title' => $title,
                'display_order' => $order + 1,
                'status' => 'DRAFT',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->foreignUuid('daily_verse_id')->nullable()->unique()->after('event_id')
                ->constrained('daily_verses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_verse_id');
        });
        Schema::dropIfExists('national_leaders');
        Schema::dropIfExists('discover_sections');
        Schema::dropIfExists('daily_verses');
    }
};
