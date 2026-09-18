<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('pastor_name')->nullable();
            $table->string('address')->nullable();
            $table->string('commune')->nullable()->index();
            $table->string('quartier')->nullable();
            $table->string('secretariat_phone')->nullable();
            $table->string('official_whatsapp')->nullable();
            $table->string('official_email')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('main_service_day')->nullable();
            $table->string('main_service_time')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
