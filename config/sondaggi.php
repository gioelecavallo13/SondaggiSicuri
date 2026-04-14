<?php

return [
    'response_ip_salt' => env('APP_RESPONSE_SALT', 'dev-response-salt-change-me'),
    'rate_limit_window_seconds' => 900,
    'rate_limit_max_attempts' => 30,
    'anonymous_vote_cookie' => env('SONDAGGI_ANONYMOUS_VOTE_COOKIE', 'sm_vote_client'),
    /** Durata massima del cookie anti-duplicato per sondaggi anonimi (secondi). Default ~1 anno. */
    'anonymous_vote_cookie_max_age_seconds' => (int) env('SONDAGGI_ANONYMOUS_VOTE_COOKIE_MAX_AGE_SECONDS', 31536000),
    /** Se true, la scadenza del cookie non supera `data_scadenza` del sondaggio (quando futura). */
    'anonymous_vote_cookie_cap_at_survey_expiry' => filter_var(
        env('SONDAGGI_ANONYMOUS_VOTE_COOKIE_CAP_SURVEY_EXPIRY', true),
        FILTER_VALIDATE_BOOL
    ),
    /**
     * Massimo numero di partecipanti inclusi nel report PDF (identificati), ordinati per data compilazione.
     * Riduce memoria/timeout DomPDF; 0 = nessun limite.
     */
    'stats_pdf_max_participants' => max(0, (int) env('SONDAGGI_STATS_PDF_MAX_PARTICIPANTS', 500)),
];
