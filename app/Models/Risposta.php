<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Risposta extends Model
{
    protected $table = 'risposte';

    const CREATED_AT = 'data_compilazione';

    const UPDATED_AT = null;

    protected $fillable = [
        'utente_id',
        'sondaggio_id',
        'client_id',
        'session_fingerprint',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'data_compilazione' => 'datetime',
        ];
    }

    public function sondaggio(): BelongsTo
    {
        return $this->belongsTo(Sondaggio::class, 'sondaggio_id');
    }

    public function utente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utente_id');
    }

    public function dettagli(): HasMany
    {
        return $this->hasMany(DettaglioRisposta::class, 'risposta_id');
    }
}
