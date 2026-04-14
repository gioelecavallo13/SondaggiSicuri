# Documentazione backend — Sondaggi Sicuri

## 1. Stack e convenzioni

- **Framework**: Laravel (PHP 8.4+ nel Dockerfile di progetto).
- **Routing**: solo `routes/web.php` (nessun `api.php` separato per REST classico); alcune route restituiscono **JSON** per uso AJAX.
- **Autenticazione**: guard web, driver sessione, modello `App\Models\User` su tabella `utenti` (`password_hash`, `getAuthPassword()`).
- **Autorizzazione**: policy `SondaggioPolicy` registrata sul modello `Sondaggio` (update/delete/viewStats).

## 2. Routing HTTP (sintesi)

### 2.1 Pubbliche (senza `auth`)

| Metodo | Path | Nome route | Controller |
|--------|------|------------|--------------|
| GET | `/` | `home` | `HomeController@index` |
| GET | `/chi-siamo` | `about` | `HomeController@about` |
| GET/POST | `/contatti` | `contacts.*` | `ContactController` |
| GET | `/login`, `/register` | `login`, `register` | `AuthController` (middleware `guest`) |
| POST | `/login`, `/register` | — | `AuthController` |
| GET | `/sondaggi` | `surveys.public.index` | `PublicSurveyController@index` |
| GET | `/sondaggi/ricerca` | `surveys.public.search` | `PublicSurveyController@search` → **JSON** |

### 2.2 Autenticate (`middleware auth`)

| Metodo | Path | Nome route | Note |
|--------|------|------------|------|
| POST | `/logout` | `logout` | |
| GET | `/profilo` | `profile.show` | Profilo utente (dati account, foto) |
| POST | `/profilo/foto` | `profile.photo.upload` | Upload foto profilo → **JSON** (`url`, errori validazione) |
| GET | `/dashboard` | `dashboard` | Elenco sondaggi autore |
| GET/POST | `/dashboard/sondaggi/nuovo` | `surveys.create`, `surveys.store` | Creazione |
| GET/POST | `/dashboard/sondaggi/{id}/modifica` | `surveys.edit`, `surveys.update` | `whereNumber` |
| POST | `/dashboard/sondaggi/{id}/elimina` | `surveys.destroy` | |
| GET | `/dashboard/sondaggi/{id}/statistiche` | `surveys.stats` | Vista |
| GET | `/dashboard/sondaggi/{id}/statistiche/dati` | `surveys.stats.data` | **JSON** Chart.js |
| GET | `/dashboard/sondaggi/{id}/statistiche/report` | `surveys.stats.report` | PDF stream |
| GET | `/sondaggi/{access_token}` | `surveys.show` | Token 48 caratteri |
| POST | `/sondaggi/{access_token}/compila` | `surveys.submit` | Invio risposte |

**Health**: `GET /up` (bootstrap Laravel).

## 3. Controller principali

### 3.1 `AuthController`

- Validazione credenziali, `Auth::attempt`, rigenerazione sessione.
- Registrazione: creazione `User` con hash password, login immediato.
- Logout: invalidazione sessione e token CSRF.
- Redirect post-login tramite `App\Support\SafeRedirect` (whitelist percorsi interni).

### 3.2 `HomeController`

- Home: ultimi sondaggi pubblici non scaduti (limit), about statico.

### 3.3 `ContactController`

- Validazione e persistenza messaggi in `contatti`.

### 3.4 `ProfileController`

- `show`: vista Blade `profile.show` con dati utente corrente.
- `uploadPhoto`: validazione file immagine (JPEG/PNG/WebP/GIF, max 2 MB), delega a `ProfilePhotoService::storeReplacingPrevious`, salva path in `utenti.foto_profilo`, risposta JSON con URL pubblico (`/storage/...`). Richiede symlink storage per servire i file.

### 3.5 `PublicSurveyController`

- Dipende da `ResponseSubmissionService`.
- Elenco filtrato: pubblici, non scaduti, ricerca testuale, filtro tag; ordinamento con **partecipazione** (già risposti in coda) via SQL; paginazione.
- Annotazione attributo `viewer_has_responded` su ogni modello della pagina corrente.
- `search`: validazione input; JSON con frammenti HTML (card + paginazione) per aggiornamento client.

### 3.6 `SurveyController`

- Dashboard, CRUD sondaggio (validazione payload in controller), show take, statistiche, export PDF.
- Autorizzazione `authorize` su update/destroy/stats dove applicabile.
- Binding implicito `Sondaggio` per route numeriche dashboard; binding per **token** su `show`/`submit`.

### 3.7 `ResponseController`

- Submit: carica sondaggio, gestisce scaduto (vista chiusa + errori), validazione risposte, richiede utente autenticato, delega a `ResponseSubmissionService::submitAuthenticated`.
- Cookie opzionale per client UUID su sondaggi **anonimi** (config `sondaggi.anonymous_vote_cookie`).

## 4. Servizi di dominio

### 4.1 `SurveyService`

- Creazione/aggiornamento sondaggio in transazione DB.
- Vincoli se esistono risposte (es. cambio struttura domande, privacy).
- Trasformazione modelli in array per viste (`toTakeViewArray`, `toClosedSurveyViewArray`, statistiche, PDF).
- Integrazione `SurveyTakePrivacyNotice` per testi informativi in compilazione.

### 4.2 `ResponseSubmissionService`

- Validazione struttura risposte vs domande/opzioni.
- Rate limiting tramite tabella `survey_submit_attempts` + hash IP (`config('sondaggi.response_ip_salt')`).
- **Privacy anonima**: UUID in cookie e/o fingerprint sessione; salvataggio `risposte` con `utente_id` null.
- **Privacy identificata**: una risposta per `(sondaggio_id, utente_id)` (vincolo DB).
- Metodi per ordinamento/elenco pubblico: `applyPublicSurveyListParticipationOrdering`, `participatedSurveyIdsForRequest`, `viewerHasResponded`.

## 5. Autenticazione e sessione

- Middleware standard Laravel `web` (cookie sessione, CSRF su form POST).
- Model `User`: tabella `utenti`, campo password `password_hash` mappato con `authPasswordName`.
- Route login/register protette da `guest` per evitare accesso ridondante.

## 6. “API” e JSON

L’applicazione non espone un’API REST versionata separata; gli endpoint JSON sono:

1. **`GET /sondaggi/ricerca`** — parametri `q`, `tags[]`, `page`; risposta `{ cards_html, pagination_html, empty }`.
2. **`GET .../statistiche/dati`** — payload per grafici (autore solo); struttura definita in `SurveyController::statsData` / servizio.
3. **`POST /profilo/foto`** — upload foto profilo; risposta JSON con `url` o struttura errori Laravel.

Tutti pensati per consumo interno dal frontend della stessa origine (cookie di sessione e header `X-CSRF-TOKEN` dove applicabile).

## 7. Config rilevante (`config/sondaggi.php`)

- `response_ip_salt` — ingrediente per hash IP (rate limit / tracciamento leggero).
- `rate_limit_window_seconds`, `rate_limit_max_attempts` — finestra e tetto tentativi submit.
- `anonymous_vote_cookie` (+ max age, cap a scadenza sondaggio) — anti-duplicato lato browser per anonimo.
- `stats_pdf_max_participants` — limite righe partecipanti nel PDF.

## 8. Code e PDF

- **DomPDF** (`barryvdh/laravel-dompdf`): generazione report statistiche lato server, stream HTTP.

## 9. Coda e job (opzionale)

- `docker-compose.yml` definisce un servizio **`worker`** (profilo Compose `queue`) per `php artisan queue:work` se `QUEUE_CONNECTION=database`.

## 10. Middleware globale

- `bootstrap/app.php`: `trustProxies(at: '*')` per deploy dietro reverse proxy.

## 11. Test

- Feature test su flussi sondaggio, pubblico, auth, PDF, partecipazione elenco pubblico.
- Unit test su servizi di supporto (privacy notice, redirect sicuri, statistiche partecipanti).

Per il modello dati sottostante → [documentazione-database.md](documentazione-database.md).  
Scenario d’uso dal punto di vista utente → [caso-duso-progetto.md](caso-duso-progetto.md).
