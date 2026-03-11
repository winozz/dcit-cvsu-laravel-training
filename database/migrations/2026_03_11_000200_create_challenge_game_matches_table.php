<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_game_matches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->foreignId('host_player_id')->constrained('players');
            $table->foreignId('guest_player_id')->nullable()->constrained('players');
            $table->enum('status', ['waiting', 'active', 'finished'])->default('waiting');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_game_matches');
    }
};
