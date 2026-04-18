<?php

$fromEnv = array_filter(array_map(
    static fn (string $d): string => strtolower(trim($d)),
    explode(',', (string) env('REGISTRATION_BLOCKED_EMAIL_DOMAINS', ''))
));

$defaults = [
    'mailinator.com',
    '10minutemail.com',
    'guerrillamail.com',
    'throwaway.email',
    'yopmail.com',
    'tempmail.com',
    'trashmail.com',
    'maildrop.cc',
];

return [
    /*
    |--------------------------------------------------------------------------
    | Domini email considerati temporanei / usa-e-getta
    |--------------------------------------------------------------------------
    |
    | Confronto esatto sul dominio (parte dopo @). Estendibile con
    | REGISTRATION_BLOCKED_EMAIL_DOMAINS nel .env (lista separata da virgole).
    |
    */
    'disposable_email_domains' => array_values(array_unique(array_merge($defaults, $fromEnv))),
];
