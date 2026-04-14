<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contatto extends Model
{
    protected $table = 'contatti';

    const CREATED_AT = 'data_invio';

    const UPDATED_AT = null;

    protected $fillable = [
        'nome',
        'email',
        'messaggio',
    ];

    protected function casts(): array
    {
        return [
            'data_invio' => 'datetime',
        ];
    }
}
