# Documentazione frontend — Sondaggi Sicuri

## 1. Panoramica

Il frontend è principalmente **server-rendered** (HTML generato da **Blade**). Gli asset compilati da **Vite** includono:

- `resources/css/app.css` — foglio principale (importa token di design e componenti condivisi).
- `resources/js/app.js` — script unico della applicazione (moduli ES).

**Bootstrap 5.3** e **Bootstrap Icons** sono caricati dal **CDN** nel layout (`layouts/app.blade.php`), insieme a font Google (Manrope, Inter).

Non è presente un framework UI reattivo globale (niente Vue/React): lo stato dinamico è gestito nel DOM con JavaScript vanilla.

## 2. Build e integrazione Laravel

| File | Ruolo |
|------|--------|
| `vite.config.js` | Plugin `laravel-vite-plugin`, entry `app.css` + `app.js`, refresh su modifiche Blade |
| `@vite(['resources/css/app.css', 'resources/js/app.js'])` | Inietta tag script/link in sviluppo o asset versionati in build |

Comandi tipici:

- `npm run dev` — server Vite con hot reload (sviluppo locale con Node installato).
- `npm run build` — asset di produzione in `public/build/` (anche eseguito nella fase Docker dell’immagine app).

**Docker (override dev):** dopo modifiche a CSS/JS va rigenerato il bundle sul mount, altrimenti il browser continua a usare i file già presenti in `public/build/`:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile assets run --rm assets
```

Il servizio `assets` esegue `npm ci && npm run build` in un container Node; il volume anonimo su `/app/node_modules` evita conflitti tra dipendenze installate sull’host (es. macOS) e `npm ci` su Linux (vedi `docker-compose.dev.yml`).

## 3. Struttura CSS

### 3.1 Catena di import

`resources/css/app.css` importa:

1. `design-tokens.css` — variabili CSS (`--site-color-*`, `--font-*`, transizioni, componenti tipo pulsanti pill primari).
2. `components-ui.css` — layout pagine condivise, card pubbliche di base, empty state, paginazione, form loading, ecc.

Il resto di `app.css` contiene regole specifiche del sito: navbar, hero home, **card sondaggi pubblici** (inclusa variante `--answered` per “già compilato”), form compilazione, statistiche, reveal on scroll, ecc.

### 3.2 Convenzioni di classi

- Prefisso **`site-`**: identità visiva del prodotto (es. `site-btn-pill-primary`, `site-public-card`, `site-shell`).
- Prefisso **`sm-`**: componenti legacy o utility di progetto (es. `sm-public-surveys-*`, `sm-tag-chip`).
- **Bootstrap**: griglia (`container`, `row`, `col-*`), utilità (`d-flex`, `gap-*`, `text-muted`), componenti (`alert`, `pagination`, `modal`).

### 3.3 Responsive e accessibilità

- Layout filtri pubblici: `sm-public-surveys-filter-layout` (colonne su desktop, stack su mobile).
- **Focus visibile** su input e pulsanti dove definito nei token.
- **Movimento ridotto**: classi `html.motion-reveal-enabled` e `@media (prefers-reduced-motion: reduce)` per disabilitare animazioni di reveal e hover eccessivi.

## 4. Struttura JavaScript (`resources/js/app.js`)

Il file è organizzato in **IIFE** e blocchi per contesto (non moduli ES separati per pagina). Aree principali:

| Blocco | Comportamento |
|--------|----------------|
| **Reveal** | Elementi `[data-reveal]` osservati con `IntersectionObserver`; stagger opzionale `[data-reveal-stagger]`. |
| **Navbar** | Ombra allo scroll su `#site-navbar`. |
| **Form loading** | Form con `data-sm-form-loading`: spinner e `aria-busy` al submit. |
| **Contatti** | Validazione minima client su `#contact-form`. |
| **Compilazione sondaggio** | Form con `action` contenente `/compila`: barra progresso domande, validazione “tutte le domande”, disabilitazione submit; escluso se `data-survey-take-closed`. |
| **Modale eliminazione** | Bootstrap modal: imposta `action` del form da `data-delete-url`. |
| **Builder sondaggi** | DOM dinamico per aggiungere/rimuovere domande e opzioni in creazione/modifica (`#questions-container`, `window.__initialQuestions`). |
| **Statistiche** | `fetch` JSON verso `surveys.stats.data`, Chart.js per grafici, aggiornamento DOM. |
| **Sondaggi pubblici** | `#sm-public-surveys-root`: `fetch` verso `surveys.public.search`, debounce su ricerca, chip tag, paginazione HTML sostituita, stato errore. |
| **QR condivisione** | `data-sm-qr-share`: generazione canvas con libreria `qrcode`. |
| **Count-up** | Animazione numeri su `[data-count-up]`. |
| **Profilo / foto** | `#profile-avatar-root`: upload foto via `fetch` (POST `multipart` con CSRF) verso `profile.photo.upload`; aggiornamento DOM dell’avatar e messaggi in `#profile-photo-alert`. |

### 4.1 Stato lato client

Non c’è store centralizzato (Redux/Pinia). Lo stato è:

- **Locale al DOM** (valori input, checkbox tag, innerHTML delle sezioni risultati).
- **Sessione server** per autenticazione e CSRF (`meta name="csrf-token"`).

## 5. Blade: layout e partial

- **`layouts/app.blade.php`**: shell comune (navbar, footer, `@yield('content')`), CSRF, Vite.
- **`partials/site-navbar.blade.php`**, **`partials/site-footer.blade.php`**: navigazione globale.
- **`surveys/partials/public-survey-cards.blade.php`**: griglia card; supporta `viewer_has_responded`, classi griglia e gutter parametrizzate.
- **`partials/survey-take-privacy-notice.blade.php`**: box informativo privacy in compilazione.

Le pagine impostano `@section('title', …)` per il titolo finestra.

## 6. UI/UX per area

### 6.1 Home e marketing

- Hero, sezione FAQ, anteprima sondaggi pubblici (stesse card partial con `staggerReveal` opzionale).

### 6.2 Autenticazione

- Form login/registrazione con messaggi di errore Laravel; redirect sicuro tramite query `redirect` (validato server-side).

### 6.3 Dashboard e form builder

- Tabella o elenco card sondaggi autore; call-to-action verso modifica/statistiche/compila.
- Form lungo per definizione domande: UX guidata da JS per righe opzioni e domande.

### 6.4 Compilazione (`take`)

- Progresso visivo, fieldset per domanda, footer con avviso privacy e submit primario.
- Stati errore: alert rosso; sondaggio chiuso: messaggio neutro.

### 6.5 Elenco pubblico (`/sondaggi`)

- Pannello filtri (ricerca + tag scrollabili), chip “filtri attivi”, overlay loading sui risultati.
- Card **attive**: gradient header, CTA pill primaria “Compila”.
- Card **già compilate dall’utente**: classe `site-public-card--answered` (aspetto disattivato, desaturazione), CTA muted con icona check; `aria-label` sull’`article` per accessibilità.

### 6.6 Statistiche

- Intestazione survey, grafici Chart.js, tabelle partecipanti (colonne dipendenti dalla privacy), link export PDF.

### 6.7 Profilo utente (`/profilo`)

- Hero con avatar circolare (iniziali o foto), upload immagine con overlay e icona fotocamera (stili `.site-profile-avatar*` in `app.css`).
- Pannelli “Dati personali” e “Sicurezza e accesso” (logout, link dashboard).
- Le foto servite da `/storage/...` richiedono `php artisan storage:link` se il symlink non è presente.

## 7. Dipendenze npm (runtime)

| Pacchetto | Uso |
|-----------|-----|
| `chart.js` | Grafici dashboard statistiche |
| `qrcode` | QR code nella sezione condivisione |
| `axios` | Presente nel progetto; gran parte delle chiamate usa `fetch` nativo |

## 8. Dove approfondire

- Flussi server che alimentano le viste → [documentazione-backend.md](documentazione-backend.md).
- Dati mostrati nelle card e nei report → [documentazione-database.md](documentazione-database.md).
- Percorso utente end-to-end (accesso, creazione sondaggio, condivisione, report) → [caso-duso-progetto.md](caso-duso-progetto.md).
