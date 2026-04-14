<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SurveyPrivacyMode;

/**
 * Testi informativi per chi compila il sondaggio (pagina take), allineati a {@see SurveyPrivacyMode}.
 */
final class SurveyTakePrivacyNotice
{
    /**
     * @return array{heading: string, body: string}
     */
    public static function forMode(SurveyPrivacyMode $mode): array
    {
        return match ($mode) {
            SurveyPrivacyMode::Anonymous => [
                'heading' => 'Sondaggio anonimo',
                'body' => 'Le tue risposte non sono collegate al tuo account: il creatore del sondaggio non può risalire alla tua identità e vede solo i risultati complessivi.',
            ],
            SurveyPrivacyMode::IdentifiedHiddenAnswers => [
                'heading' => 'Chi vede cosa',
                'body' => 'Il creatore saprà che hai partecipato, ma nelle statistiche non potrà vedere quali opzioni hai scelto.',
            ],
            SurveyPrivacyMode::IdentifiedFull => [
                'heading' => 'Chi vede cosa',
                'body' => 'Il creatore del sondaggio potrà vedere che hai partecipato e anche il dettaglio delle risposte che invii.',
            ],
        };
    }
}
