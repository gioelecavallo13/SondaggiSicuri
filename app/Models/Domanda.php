<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domanda extends Model
{
    protected $table = 'domande';

    public $timestamps = false;

    protected $fillable = [
        'sondaggio_id',
        'testo',
        'tipo',
        'ordine',
    ];

    public function sondaggio(): BelongsTo
    {
        return $this->belongsTo(Sondaggio::class, 'sondaggio_id');
    }

    public function opzioni(): HasMany
    {
        return $this->hasMany(Opzione::class, 'domanda_id')->orderBy('ordine');
    }
}
