# Caso d’uso del progetto — Sondaggi Sicuri

Documento orientato a **chi usa l’applicazione** (non solo agli sviluppatori): dal primo accesso alla creazione di sondaggi, alle opzioni disponibili, fino a condivisione e report. Per i dettagli tecnici si rimanda a [documentazione-generale.md](documentazione-generale.md) e agli altri file in `docs/`.

---

## 1. Chi può fare cosa

| Ruolo | Cosa può fare |
|--------|------------------|
| **Visitatore** | Consultare home, pagina “Chi siamo”, contatti, elenco **Sondaggi pubblici** (`/sondaggi`) con ricerca e filtri. |
| **Utente registrato** | Tutto quanto sopra, più **dashboard**, **profilo**, **creazione/modifica/eliminazione** dei propri sondaggi, **compilazione** dei sondaggi (tramite link), **statistiche** e **report PDF** sui propri questionari. |

La **compilazione** di un sondaggio e l’uso dell’area riservata richiedono **registrazione e login** (autenticazione a sessione).

---

## 2. Accesso al sito

1. **Registrazione** (`/register`): nome, email, password → creazione account sulla tabella `utenti`.
2. **Login** (`/login`): accesso all’area autenticata; eventuale redirect sicuro verso una pagina interna dopo il login.
3. **Logout**: dalla pagina profilo o da dove è esposto il comando “Esci”.
4. **Profilo** (`/profilo`): dati personali (nome, email, data registrazione, ID account), **foto profilo** opzionale (caricamento file; formati e limiti validati lato server), sezione sicurezza con logout e link alla dashboard.

La **navbar** offre collegamenti a Home, Sondaggi pubblici, Chi siamo, Contatti; dopo l’accesso compaiono Dashboard e Profilo.

---

## 3. Dashboard autore

Dopo il login, **Dashboard** (`/dashboard`) mostra:

- Indicatori riepilogativi (numero di sondaggi creati, compilazioni totali).
- Elenco dei **propri** sondaggi con stato (pubblico/privato, scaduto), date, azioni:
  - **Modifica** il questionario
  - **Statistiche** (aggregati e, se previsto, partecipanti)
  - **Compila** (apre la stessa esperienza del partecipante, utile per test)
  - **Elimina** (con conferma in modale)

È disponibile il pulsante **Nuovo sondaggio** verso il form di creazione.

---

## 4. Creazione e modifica di un sondaggio

Percorsi: **Nuovo sondaggio** → `/dashboard/sondaggi/nuovo`; **Modifica** → `/dashboard/sondaggi/{id}/modifica`.

### 4.1 Dettaglio del questionario

- **Titolo** (obbligatorio) e **descrizione** (facoltativa).
- **Pubblico** (checkbox): se attivo, il sondaggio può comparire in home e nell’elenco `/sondaggi`; se disattivo resta **privato** (accesso principalmente tramite link). In ogni caso, per rispondere l’utente deve essere autenticato.
- **Privacy e partecipanti** (radio, non modificabile dopo la prima risposta ricevuta):
  - **Anonimo** — le risposte non sono collegate all’identità in archivio; in statistiche solo totali/distribuzioni, non elenco nominale.
  - **Identificato — solo elenco partecipanti** — si sa chi ha compilato, senza vedere le opzioni scelte.
  - **Identificato — elenco e risposte** — partecipanti e dettaglio delle scelte (anche nel PDF).
- **Scadenza** (facoltativa, data/ora): dopo tale istante il sondaggio non accetta più risposte; la pagina pubblica può risultare in sola lettura.
- **Tag**: categorie predefinite (tabella `tags`) per filtrare il sondaggio nell’elenco pubblico; scelta multipla.

### 4.2 Domande e opzioni

- Ogni domanda ha **testo**, tipo **scelta singola** o **scelta multipla**, e un elenco di **opzioni** testuali ordinate.
- Dal form si possono **aggiungere/rimuovere** domande e righe opzioni (comportamento guidato da JavaScript nel builder).

### 4.3 Vincoli in modifica

Se il sondaggio ha **già ricevuto risposte**, alcune modifiche strutturali o alla **modalità privacy** possono essere bloccate o limitate (per coerenza dei dati già raccolti). Messaggi di errore del server compaiono nel form.

Salvataggio con **Salva sondaggio**; **Annulla** torna alla dashboard senza applicare le modifiche non salvate (comportamento standard del browser sul form).

---

## 5. Condivisione del sondaggio

Ogni sondaggio ha un **`access_token`** lungo (nell’URL non compare l’ID numerico interno).

- **Link di compilazione**: `GET /sondaggi/{token}` — pagina con le domande e invio risposte a `POST .../compila`.
- Nella **vista statistiche** (autore) sono presenti strumenti per **copiare il link** e, dove previsto dalla UI, **QR code** + link per inviare il questionario a partecipanti esterni (email, chat, stampa).

Condividendo solo il link (e opzionalmente il QR), si controlla chi può partecipare senza esporre l’ID del record nel database.

---

## 6. Compilazione (partecipante)

1. L’utente **accede** e apre il link del sondaggio.
2. Se il sondaggio è **scaduto** o **chiuso**, vede un messaggio appropriato senza poter inviare risposte.
3. Altrimenti compila il modulo (barra di avanzamento tra le domande, validazione lato client e server), legge l’**avviso privacy** in calce e invia.
4. In base alla modalità **anonima** o **identificata**, il sistema applica vincoli di **una sola compilazione per utente** (identificato) o meccanismi anti-duplicato per anonimo (cookie/fingerprint ove configurati), oltre al **rate limiting** sugli invii ripetuti.

Messaggio di ringraziamento o errori di validazione sono mostrati nella stessa area di compilazione.

---

## 7. Elenco pubblico e ricerca

- **`/sondaggi`**: elenco dei sondaggi **pubblici** e **non scaduti**, con **ricerca testuale**, **filtri per tag** e **paginazione**.
- La ricerca può aggiornare card e paginazione **senza ricaricare** tutta la pagina (risposta JSON dal server).
- Se l’utente ha **già compilato** un sondaggio, l’interfaccia può evidenziare la card come “già risposto” e ordinarla con priorità più bassa.

---

## 8. Statistiche e report

Solo l’**autore** (e solo dopo autorizzazione applicativa) accede a `/dashboard/sondaggi/{id}/statistiche`:

- Riepilogo compilazioni, **grafici** (Chart.js) per distribuzione risposte.
- Tabella **partecipanti** solo se la privacy del sondaggio lo consente (in anonimo: nessun elenco nominale).
- **Export PDF** (`.../statistiche/report`): documento generato lato server (DomPDF), con eventuale limite al numero di righe partecipanti in base alla configurazione.

---

## 9. Altre pagine

- **Contatti**: invio messaggio salvato nel database (`contatti`).
- **Chi siamo** e **Home**: contenuti informativi; la home può mostrare un’anteprima di sondaggi pubblici.

---

## 10. Riferimenti incrociati

| Argomento | Documento |
|-----------|------------|
| Architettura e stack | [documentazione-generale.md](documentazione-generale.md) |
| Route e servizi Laravel | [documentazione-backend.md](documentazione-backend.md) |
| Vite, Blade, JS | [documentazione-frontend.md](documentazione-frontend.md) |
| Tabelle e relazioni | [documentazione-database.md](documentazione-database.md) |
