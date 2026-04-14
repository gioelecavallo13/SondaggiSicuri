<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'utenti';

    /** Colonna reale in DB (Laravel usa di default `password` per rehash al login). */
    protected $authPasswordName = 'password_hash';

    const CREATED_AT = 'data_creazione';

    const UPDATED_AT = null;

    protected $fillable = [
        'nome',
        'email',
        'password_hash',
        'foto_profilo',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'data_creazione' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /** Path assoluto sul sito (es. /storage/profile-photos/…); richiede symlink `public/storage`. */
    public function profilePhotoUrl(): ?string
    {
        if (blank($this->foto_profilo)) {
            return null;
        }

        $normalized = str_replace('\\', '/', $this->foto_profilo);

        return '/storage/'.ltrim($normalized, '/');
    }

    public function sondaggi(): HasMany
    {
        return $this->hasMany(Sondaggio::class, 'autore_id');
    }
}
