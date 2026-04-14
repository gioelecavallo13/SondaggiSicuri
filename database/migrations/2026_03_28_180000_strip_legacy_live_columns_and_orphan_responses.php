<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('risposte as r')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('dettaglio_risposte as d')
                    ->whereColumn('d.risposta_id', 'r.id');
            })
            ->delete();

        $sondaggiLegacy = [
            'mostra_risultati',
            'realtime_attivo',
            'mostra_votanti',
            'mostra_dati_votanti',
            'consenso_dati_votanti_full',
            'consenso_dati_votanti_full_at',
        ];
        $toDropSondaggi = array_values(array_filter(
            $sondaggiLegacy,
            fn (string $c): bool => Schema::hasColumn('sondaggi', $c)
        ));
        if ($toDropSondaggi !== []) {
            Schema::table('sondaggi', function (Blueprint $table) use ($toDropSondaggi): void {
                $table->dropColumn($toDropSondaggi);
            });
        }

        $risposteLegacy = ['voter_display_name', 'voter_email'];
        $toDropRisposte = array_values(array_filter(
            $risposteLegacy,
            fn (string $c): bool => Schema::hasColumn('risposte', $c)
        ));
        if ($toDropRisposte !== []) {
            Schema::table('risposte', function (Blueprint $table) use ($toDropRisposte): void {
                $table->dropColumn($toDropRisposte);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
