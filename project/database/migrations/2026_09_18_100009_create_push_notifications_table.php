<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('GENERAL');
            $table->foreignUuid('news_id')->nullable()->constrained('news')->nullOnDelete();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('audience')->default('ALL');
            $table->foreignUuid('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignUuid('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignUuid('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('status')->default('DRAFT')->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
