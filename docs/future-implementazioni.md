# Future implementazioni — Sondaggi Sicuri

Documento operativo per guidare le prossime evoluzioni del progetto senza perdere coerenza con architettura, schema dati e regole di privacy gia presenti.

## 1. Obiettivo

Definire:

- aree di evoluzione prioritarie;
- linee guida tecniche per implementarle;
- checklist minima di qualita per evitare regressioni.

## 2. Principi da mantenere

1. **Privacy-first**: ogni nuova funzionalita deve rispettare `privacy_mode` del sondaggio.
2. **Compatibilita con dati esistenti**: evitare breaking change su tabelle in produzione senza migrazione retrocompatibile.
3. **Regole di dominio centralizzate**: mantenere la logica in `app/Services/` e usare controller snelli.
4. **Sicurezza operativa**: preservare anti-duplicato, rate limit e validazioni server-side.
5. **Esperienza utente semplice**: preferire flussi chiari in Blade + JS progressivo, senza introdurre complessita non necessaria.

## 3. Backlog consigliato (ordine suggerito)

### 3.1 Notifiche e promemoria

- Email di conferma all'autore alla creazione del sondaggio.
- Reminder automatico prima della scadenza (es. -24h, -1h).
- Notifica all'autore al raggiungimento di soglie (es. 100 compilazioni).

Impatto tecnico:

- Queue job dedicati e policy di retry.
- Template email versionati e testabili.

### 3.2 Miglioramenti analytics

- Serie temporale delle compilazioni (per fascia oraria/giornaliera).
- Conversione visita->compilazione per sondaggi pubblici.
- Export CSV oltre al PDF.

Impatto tecnico:

- Query aggregate ottimizzate e indici dedicati.
- Endpoint backend separati per metriche pesanti.

### 3.3 Estensione tipi domanda

- Domande aperte (testo breve/lungo).
- Scala Likert.
- Matrice semplice (righe/colonne predefinite).

Impatto tecnico:

- Evoluzione schema `domande` e `dettaglio_risposte`.
- Aggiornamento validazioni in `ResponseSubmissionService`.
- Regole di rendering UI dedicate.

### 3.4 Moderazione e gestione contenuti

- Stato sondaggio: bozza/pubblicato/archiviato.
- Soft delete con recupero.
- Flag contenuti sensibili.

Impatto tecnico:

- Nuovi campi su `sondaggi`.
- Policy e filtri in listing pubblico.

### 3.5 API esterne

- Endpoint API token-based per creare/leggere sondaggi.
- Webhook su nuova compilazione.

Impatto tecnico:

- Guard/API token separato dalla sessione web.
- Versionamento endpoint (`/api/v1`).

## 4. Linee guida implementative

### 4.1 Database e migrazioni

- Ogni nuova migration deve essere **reversibile** (`down`) e idempotente.
- Per colonne nuove su tabelle grandi: nullable + backfill + vincolo finale in step separati.
- Aggiungere indici quando si introducono query su nuove chiavi di filtro.

### 4.2 Backend (Laravel)

- Nuova logica di dominio: prima in `Service`, poi orchestrazione da controller.
- Validazioni in `FormRequest` o in service con messaggi coerenti alle view.
- Policy/Gate per ogni nuova azione con accesso ai dati.
- Evitare query N+1 nelle pagine dashboard/statistiche (usare eager loading e aggregate).

### 4.3 Frontend (Blade + JS)

- Mantenere progressive enhancement: funzionalita base fruibile anche senza JS avanzato.
- Componenti JS piccoli e focalizzati, preferibilmente per pagina.
- Feedback utente chiaro su errori, stato chiuso/scaduto, privacy.

### 4.4 Test

Minimo richiesto per ogni feature:

1. **Feature test** percorso felice.
2. **Feature test** per autorizzazione/privacy.
3. **Feature test** almeno un caso limite (scaduto, duplicato, payload invalido).
4. **Unit test** se viene introdotta logica non banale nel service.

### 4.5 Osservabilita

- Loggare eventi dominio principali (creazione sondaggio, submit, export report) senza dati sensibili.
- Distinguere warning applicativi da errori di sistema.
- Preparare metriche base per monitorare error rate submit e tempi risposta.

## 5. Regole di compatibilita per privacy e risposte

Quando una feature tocca compilazione o statistiche:

- Verificare effetto su modalita `anonimo`, `identified_list`, `identified_full`.
- Non esporre dettagli risposta dove la privacy non lo consente.
- Mantenere coerenza tra dati aggregate e viste nominali.
- Aggiornare anche export PDF/CSV per rispettare gli stessi vincoli.

## 6. Checklist PR (future implementazioni)

- [ ] Documentazione aggiornata in `docs/` (file tecnico e impatti utente).
- [ ] Migrazioni testate in locale su DB realistico.
- [ ] Test automatici aggiunti/aggiornati e verdi.
- [ ] Verifica manuale del flusso in UI (dashboard, compilazione, statistiche).
- [ ] Controllo privacy completato.
- [ ] Nessuna regressione sui sondaggi esistenti.

## 7. Riferimenti utili

- [Documentazione generale](documentazione-generale.md)
- [Documentazione backend](documentazione-backend.md)
- [Documentazione frontend](documentazione-frontend.md)
- [Documentazione database](documentazione-database.md)
- [Caso d'uso del progetto](caso-duso-progetto.md)
