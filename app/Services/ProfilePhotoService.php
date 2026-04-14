<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gestione foto profilo utente.
 *
 * I file sono salvati sul disco Laravel "public" (directory storage/app/public, es. profile-photos/{id}/...).
 * Per servire i file via web serve il symlink: php artisan storage:link (public/storage → storage/app/public).
 *
 * La colonna utenti.foto_profilo contiene il percorso relativo al disco (es. profile-photos/3/hash.jpg).
 */
final class ProfilePhotoService
{
    private const DIRECTORY_PREFIX = 'profile-photos';

    public function storeReplacingPrevious(User $user, UploadedFile $file): string
    {
        $disk = Storage::disk('public');

        if (filled($user->foto_profilo)) {
            $disk->delete($user->foto_profilo);
        }

        $path = $file->store(self::DIRECTORY_PREFIX.'/'.$user->id, 'public');

        return $path;
    }

    /**
     * URL pubblico per la foto (path assoluto sul sito, es. /storage/profile-photos/1/…).
     * Si usa un path relativo a dominio invece di Storage::url() così funziona anche se APP_URL
     * non coincide con host/porta reali (es. Docker dev su 127.0.0.1:18080 con APP_URL=http://localhost).
     */
    public function publicUrl(?string $pathRelativeToPublicDisk): ?string
    {
        if (blank($pathRelativeToPublicDisk)) {
            return null;
        }

        $normalized = str_replace('\\', '/', $pathRelativeToPublicDisk);

        return '/storage/'.ltrim($normalized, '/');
    }
}
