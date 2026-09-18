<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->foreignUuid('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('button_text')->nullable();
            $table->string('link_type')->default('NONE');
            $table->foreignUuid('news_id')->nullable()->constrained('news')->nullOnDelete();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
