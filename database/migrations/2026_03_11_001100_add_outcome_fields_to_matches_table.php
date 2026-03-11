<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_game_matches', function (Blueprint $table) {
            $table->boolean('host_done')->default(false);
            $table->boolean('guest_done')->default(false);
            $table->boolean('host_forfeit')->default(false);
            $table->boolean('guest_forfeit')->default(false);
            $table->string('host_result')->nullable();
            $table->string('guest_result')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('challenge_game_matches', function (Blueprint $table) {
            $table->dropColumn([
                'host_done','guest_done','host_forfeit','guest_forfeit','host_result','guest_result','expires_at','ended_at'
            ]);
        });
    }
};
