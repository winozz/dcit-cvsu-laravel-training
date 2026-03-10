<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_game_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('game_slug')->index();
            $table->string('category', 80)->nullable();
            $table->string('word')->nullable();
            $table->unsignedTinyInteger('tries')->default(0);
            $table->unsignedTinyInteger('max_tries')->default(5);
            $table->boolean('won')->default(false);
            $table->json('correct')->nullable();
            $table->json('wrong')->nullable();
            $table->json('used_words')->nullable();
            $table->json('found_words')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // intentionally blank per project convention
    }
};
