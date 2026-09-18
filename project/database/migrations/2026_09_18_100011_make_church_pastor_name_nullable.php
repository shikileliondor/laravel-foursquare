<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('pastor_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Des églises peuvent légitimement ne pas avoir de pasteur renseigné.
    }
};
