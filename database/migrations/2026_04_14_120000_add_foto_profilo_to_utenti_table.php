<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('utenti', 'foto_profilo')) {
            return;
        }

        Schema::table('utenti', function (Blueprint $table): void {
            $table->string('foto_profilo', 255)->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('utenti', 'foto_profilo')) {
            return;
        }

        Schema::table('utenti', function (Blueprint $table): void {
            $table->dropColumn('foto_profilo');
        });
    }
};
