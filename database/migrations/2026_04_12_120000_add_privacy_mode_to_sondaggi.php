<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sondaggi', function (Blueprint $table) {
            $table->string('privacy_mode', 32)
                ->default('identified_full')
                ->after('access_token');
        });

        DB::table('sondaggi')->whereNull('privacy_mode')->update(['privacy_mode' => 'identified_full']);
    }

    public function down(): void
    {
        Schema::table('sondaggi', function (Blueprint $table) {
            $table->dropColumn('privacy_mode');
        });
    }
};
