<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opzione extends Model
{
    protected $table = 'opzioni';

    public $timestamps = false;

    protected $fillable = [
        'domanda_id',
        'testo',
        'ordine',
    ];

    public function domanda(): BelongsTo
    {
        return $this->belongsTo(Domanda::class, 'domanda_id');
    }
}
