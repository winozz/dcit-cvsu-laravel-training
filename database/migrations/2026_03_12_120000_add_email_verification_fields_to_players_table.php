<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable()->after('id');
            $table->string('email')->nullable()->after('username');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('email_verification_code')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code');
        });

        DB::table('players')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($players): void {
                foreach ($players as $player) {
                    DB::table('players')
                        ->where('id', $player->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        Schema::table('players', function (Blueprint $table) {
            $table->unique('public_id');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropUnique(['email']);
            $table->dropColumn([
                'public_id',
                'email',
                'email_verified_at',
                'email_verification_code',
                'email_verification_expires_at',
            ]);
        });
    }
};
