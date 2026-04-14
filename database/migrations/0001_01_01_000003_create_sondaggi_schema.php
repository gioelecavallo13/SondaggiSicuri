<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sondaggi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titolo', 255);
            $table->text('descrizione')->nullable();
            $table->unsignedInteger('autore_id');
            $table->boolean('is_pubblico')->default(true);
            $table->timestamp('data_creazione')->useCurrent();
            $table->index(['autore_id', 'data_creazione'], 'idx_sondaggi_autore_data');
            $table->foreign('autore_id')->references('id')->on('utenti')->cascadeOnDelete();
        });

        Schema::create('domande', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sondaggio_id');
            $table->string('testo', 500);
            $table->enum('tipo', ['singola', 'multipla']);
            $table->unsignedInteger('ordine')->default(1);
            $table->index(['sondaggio_id', 'ordine'], 'idx_domande_sondaggio_ordine');
            $table->foreign('sondaggio_id')->references('id')->on('sondaggi')->cascadeOnDelete();
        });

        Schema::create('opzioni', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domanda_id');
            $table->string('testo', 255);
            $table->unsignedInteger('ordine')->default(1);
            $table->index(['domanda_id', 'ordine'], 'idx_opzioni_domanda_ordine');
            $table->foreign('domanda_id')->references('id')->on('domande')->cascadeOnDelete();
        });

        Schema::create('risposte', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('utente_id')->nullable();
            $table->unsignedInteger('sondaggio_id');
            $table->char('client_id', 36)->nullable();
            $table->char('session_fingerprint', 64)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('data_compilazione')->useCurrent();
            $table->unique(['sondaggio_id', 'utente_id'], 'uk_risposte_sondaggio_utente');
            $table->index(['sondaggio_id', 'data_compilazione'], 'idx_risposte_sondaggio_data');
            $table->index('utente_id', 'idx_risposte_utente');
            $table->index(['sondaggio_id', 'client_id'], 'idx_risposte_client');
            $table->index(['sondaggio_id', 'session_fingerprint'], 'idx_risposte_fingerprint');
            $table->foreign('utente_id')->references('id')->on('utenti')->nullOnDelete();
            $table->foreign('sondaggio_id')->references('id')->on('sondaggi')->cascadeOnDelete();
        });

        Schema::create('survey_submit_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sondaggio_id');
            $table->char('ip_hash', 64);
            $table->timestamp('attempted_at')->useCurrent();
            $table->index(['sondaggio_id', 'ip_hash', 'attempted_at'], 'idx_attempts_sondaggio_ip_time');
            $table->foreign('sondaggio_id')->references('id')->on('sondaggi')->cascadeOnDelete();
        });

        Schema::create('dettaglio_risposte', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('risposta_id');
            $table->unsignedInteger('domanda_id');
            $table->unsignedInteger('opzione_id');
            $table->index(['domanda_id', 'opzione_id'], 'idx_dettaglio_domanda_opzione');
            $table->unique(['risposta_id', 'domanda_id', 'opzione_id'], 'uk_risposta_domanda_opzione');
            $table->foreign('risposta_id')->references('id')->on('risposte')->cascadeOnDelete();
            $table->foreign('domanda_id')->references('id')->on('domande')->cascadeOnDelete();
            $table->foreign('opzione_id')->references('id')->on('opzioni')->cascadeOnDelete();
        });

        Schema::create('contatti', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 120);
            $table->string('email', 190);
            $table->text('messaggio');
            $table->timestamp('data_invio')->useCurrent();
            $table->index('data_invio', 'idx_contatti_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dettaglio_risposte');
        Schema::dropIfExists('survey_submit_attempts');
        Schema::dropIfExists('risposte');
        Schema::dropIfExists('opzioni');
        Schema::dropIfExists('domande');
        Schema::dropIfExists('contatti');
        Schema::dropIfExists('sondaggi');
    }
};
