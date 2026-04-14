<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'nome',
        'slug',
    ];

    public function sondaggi(): BelongsToMany
    {
        return $this->belongsToMany(Sondaggio::class, 'sondaggio_tag', 'tag_id', 'sondaggio_id');
    }
}
