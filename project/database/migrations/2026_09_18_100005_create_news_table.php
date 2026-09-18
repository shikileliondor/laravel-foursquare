<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('content');
            $table->foreignUuid('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('scope_type')->default('NATIONAL')->index();
            $table->foreignUuid('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignUuid('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('priority')->default('NORMAL');
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('DRAFT')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
