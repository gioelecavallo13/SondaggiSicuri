<?php

/*
| Intervallo polling della pagina statistiche in dashboard (secondi).
| Non è un segreto né un dato privato: resta in questo file di config
| (versionato), non in `.env`.
*/
$statsPageRefreshSeconds = 15;

return [

    /*
    |--------------------------------------------------------------------------
    | Intervallo aggiornamento statistiche (dashboard)
    |--------------------------------------------------------------------------
    |
    | Secondi tra un poll e l'altro. Modifica la variabile sopra se serve.
    | Il valore effettivo è limitato tra 5 e 3600 secondi.
    |
    */

    'stats_refresh_interval_seconds' => max(5, min((int) $statsPageRefreshSeconds, 3600)),

];
