<?php

namespace App\Models;

use App\Enums\SurveyPrivacyMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sondaggio extends Model
{
    protected $table = 'sondaggi';

    const CREATED_AT = 'data_creazione';

    const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (Sondaggio $sondaggio): void {
            if ($sondaggio->access_token !== null && $sondaggio->access_token !== '') {
                return;
            }
            $sondaggio->access_token = self::newUniqueAccessToken();
        });
    }

    protected static function newUniqueAccessToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('access_token', $token)->exists());

        return $token;
    }

    /**
     * Token sicuro per le route di compilazione (`surveys.show`, `surveys.submit`) con `{sondaggio:access_token}`.
     */
    public static function generateUniqueAccessToken(): string
    {
        return self::newUniqueAccessToken();
    }

    /**
     * @return array{sondaggio: string}
     */
    public function takeRouteParameters(): array
    {
        $token = $this->access_token;
        if ($token === null || $token === '') {
            $this->refresh();
            $token = $this->access_token;
        }
        if ($token === null || $token === '') {
            throw new \LogicException(
                'Sondaggio senza access_token: eseguire `php artisan migrate` (migrazione access_token su sondaggi).'
            );
        }

        return ['sondaggio' => $token];
    }

    protected $fillable = [
        'titolo',
        'descrizione',
        'autore_id',
        'is_pubblico',
        'data_scadenza',
        'privacy_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_pubblico' => 'boolean',
            'data_creazione' => 'datetime',
            'data_scadenza' => 'datetime',
            'privacy_mode' => SurveyPrivacyMode::class,
        ];
    }

    public function isPrivacyAnonymous(): bool
    {
        return $this->privacy_mode === SurveyPrivacyMode::Anonymous;
    }

    public function showsIndividualAnswersToCreator(): bool
    {
        return $this->privacy_mode === SurveyPrivacyMode::IdentifiedFull;
    }

    public function autore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autore_id');
    }

    public function domande(): HasMany
    {
        return $this->hasMany(Domanda::class, 'sondaggio_id')->orderBy('ordine');
    }

    public function risposte(): HasMany
    {
        return $this->hasMany(Risposta::class, 'sondaggio_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'sondaggio_tag', 'sondaggio_id', 'tag_id');
    }

    public function scopePubblici($query)
    {
        return $query->where('is_pubblico', true);
    }

    /**
     * Elenchi pubblici (home, indice / ricerca): esclude i sondaggi scaduti.
     * Criterio allineato a {@see self::isScaduto()}: scaduto se `data_scadenza` non è null e `data_scadenza` è strettamente precedente a `now()`.
     */
    public function scopeNonScaduti($query)
    {
        $now = now();

        return $query->where(function ($q) use ($now) {
            $q->whereNull('data_scadenza')
                ->orWhere('data_scadenza', '>=', $now);
        });
    }

    /**
     * Per elenchi (es. dashboard autore): prima i sondaggi ancora aperti, in fondo quelli scaduti.
     * Criterio allineato a {@see self::isScaduto()}: scaduto solo se `data_scadenza` è strettamente nel passato rispetto a `now()`.
     */
    public function scopeOrdineScadutiInFondo($query)
    {
        $now = now();

        return $query
            ->orderByRaw(
                'CASE WHEN data_scadenza IS NOT NULL AND data_scadenza < ? THEN 1 ELSE 0 END ASC',
                [$now]
            )
            ->orderByDesc('id');
    }

    public function isScaduto(): bool
    {
        if ($this->data_scadenza === null) {
            return false;
        }

        return now()->greaterThan($this->data_scadenza);
    }
}
