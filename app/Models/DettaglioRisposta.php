<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DettaglioRisposta extends Model
{
    protected $table = 'dettaglio_risposte';

    public $timestamps = false;

    protected $fillable = [
        'risposta_id',
        'domanda_id',
        'opzione_id',
    ];

    public function risposta(): BelongsTo
    {
        return $this->belongsTo(Risposta::class, 'risposta_id');
    }

    public function domanda(): BelongsTo
    {
        return $this->belongsTo(Domanda::class, 'domanda_id');
    }

    public function opzione(): BelongsTo
    {
        return $this->belongsTo(Opzione::class, 'opzione_id');
    }
}
