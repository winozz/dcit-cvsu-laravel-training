<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_game_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('game_slug')->index();
            $table->string('status', 40)->default('depleted');
            $table->json('used_words')->nullable();
            $table->json('found_words')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // intentionally left blank per project convention
    }
};
