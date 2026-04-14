<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('slug', 140)->unique();
            $table->timestamps();
        });

        Schema::create('sondaggio_tag', function (Blueprint $table) {
            $table->unsignedInteger('sondaggio_id');
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['sondaggio_id', 'tag_id']);
            $table->foreign('sondaggio_id')->references('id')->on('sondaggi')->cascadeOnDelete();
        });

        Schema::table('sondaggi', function (Blueprint $table) {
            $table->timestamp('data_scadenza')->nullable()->after('is_pubblico');
        });
    }

    public function down(): void
    {
        Schema::table('sondaggi', function (Blueprint $table) {
            $table->dropColumn('data_scadenza');
        });

        Schema::dropIfExists('sondaggio_tag');
        Schema::dropIfExists('tags');
    }
};
