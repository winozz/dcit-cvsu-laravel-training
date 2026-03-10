<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('challenge_game_items', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->string('category', 80)->index();
            $table->string('clue')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1); // 1–5 scale
            $table->unsignedTinyInteger('max_tries')->default(5);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_played')->default(0);
            $table->unsignedInteger('times_solved')->default(0);
            $table->timestamps();

            $table->unique(['word', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank as requested (no down migration).
    }
};
