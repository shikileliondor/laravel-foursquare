<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->foreignUuid('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('scope_type')->default('NATIONAL')->index();
            $table->foreignUuid('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignUuid('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('organizer_name')->nullable();
            $table->timestamp('start_at')->index();
            $table->timestamp('end_at');
            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();
            $table->string('commune')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('official_whatsapp')->nullable();
            $table->string('priority')->default('NORMAL');
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('DRAFT')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
