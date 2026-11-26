<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_consent_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('action_id')->unique();
            $table->uuid('subject_id');
            $table->string('purpose', 50);
            $table->string('action', 20);
            $table->string('policy_version', 50);
            $table->string('platform', 20);
            // Conserver le contexte historique même si le contenu lié est supprimé.
            $table->uuid('church_id')->nullable()->index();
            $table->uuid('event_id')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_id', 'purpose', 'occurred_at']);
        });

        Schema::create('privacy_data_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 32)->unique();
            $table->string('type', 30);
            $table->string('full_name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->uuid('event_id')->nullable()->index();
            $table->text('details')->nullable();
            $table->string('privacy_notice_version', 50);
            $table->string('status', 20)->default('RECEIVED');
            // Suivi par l'administrateur : qui a traité la demande et comment.
            $table->text('resolution_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_data_requests');
        Schema::dropIfExists('privacy_consent_events');
    }
};
