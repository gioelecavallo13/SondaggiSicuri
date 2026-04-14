<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveySubmitAttempt extends Model
{
    protected $table = 'survey_submit_attempts';

    public $timestamps = false;

    protected $fillable = [
        'sondaggio_id',
        'ip_hash',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    public function sondaggio(): BelongsTo
    {
        return $this->belongsTo(Sondaggio::class, 'sondaggio_id');
    }
}
