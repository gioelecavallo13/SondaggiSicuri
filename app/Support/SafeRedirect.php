<?php

declare(strict_types=1);

namespace App\Support;

final class SafeRedirect
{
    /**
     * Segmento URL del token di accesso alle pagine take (stesso pattern del `where` su `sondaggio` in `routes/web.php`).
     */
    public const SURVEY_ACCESS_TOKEN_SEGMENT = '[A-Za-z0-9]{48}';

    /**
     * Percorso relativo consentito dopo login/register: solo compilazione sondaggio (`/sondaggi/{access_token}`), senza query string.
     */
    public static function isAllowedSurveyTakeRelativePath(string $path): bool
    {
        return preg_match('#^/sondaggi/'.self::SURVEY_ACCESS_TOKEN_SEGMENT.'/?$#', $path) === 1;
    }

    /**
     * Fallback quando in sessione non c’è `url.intended` (es. link con `?redirect=` o campo hidden nei form auth).
     * Dopo login/register si usa `redirect()->intended(SafeRedirect::afterLogin(...))`: se il middleware `auth`
     * ha salvato l’URL del sondaggio, quello ha priorità; altrimenti si valida il candidato qui (whitelist path).
     */
    public static function afterLogin(?string $candidate): string
    {
        if ($candidate === null || $candidate === '') {
            return '/dashboard';
        }
        $candidate = trim($candidate);
        if (! str_starts_with($candidate, '/') || str_contains($candidate, "\0")) {
            return '/dashboard';
        }
        $pathOnly = explode('?', $candidate, 2)[0];
        $pathOnly = preg_replace('#/+$#', '', $pathOnly) ?: '/';
        if (self::isAllowedSurveyTakeRelativePath($pathOnly)) {
            return $pathOnly;
        }

        return '/dashboard';
    }
}
