<?php

namespace App\Policies;

use App\Models\Sondaggio;
use App\Models\User;

class SondaggioPolicy
{
    /**
     * Solo l’autore può modificare; i sondaggi scaduti non sono aggiornabili.
     * `SurveyController::editForm` e `update` usano `authorize('update', …)`:
     * se il sondaggio è scaduto la risposta è 403 (nessun redirect flash).
     */
    public function update(User $user, Sondaggio $sondaggio): bool
    {
        return (int) $sondaggio->autore_id === (int) $user->id
            && ! $sondaggio->isScaduto();
    }

    public function delete(User $user, Sondaggio $sondaggio): bool
    {
        return (int) $sondaggio->autore_id === (int) $user->id;
    }

    public function viewStats(User $user, Sondaggio $sondaggio): bool
    {
        return (int) $sondaggio->autore_id === (int) $user->id;
    }
}
