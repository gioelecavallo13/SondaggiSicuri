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
        Schema::table('sondaggi', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->after('is_pubblico');
        });

        $generateUniqueToken = function (): string {
            do {
                $token = Str::random(48);
            } while (DB::table('sondaggi')->where('access_token', $token)->exists());

            return $token;
        };

        DB::table('sondaggi')->orderBy('id')->chunkById(100, function ($rows) use ($generateUniqueToken): void {
            foreach ($rows as $row) {
                if ($row->access_token !== null && $row->access_token !== '') {
                    continue;
                }
                DB::table('sondaggi')->where('id', $row->id)->update([
                    'access_token' => $generateUniqueToken(),
                ]);
            }
        });

        Schema::table('sondaggi', function (Blueprint $table) {
            $table->unique('access_token');
        });

        Schema::table('sondaggi', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sondaggi', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
        });

        Schema::table('sondaggi', function (Blueprint $table) {
            $table->dropColumn('access_token');
        });
    }
};
