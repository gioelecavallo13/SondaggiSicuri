# Sondaggi Sicuri

Installazione e avvio **solo con Docker**. Non è necessario avere PHP, Composer o Node.js installati sul computer: tutto avviene nei container (build immagine, `composer install` in avvio, MySQL, migrazioni, applicazione).

---

## Cosa ti serve

- **Git**
- **Docker Engine** e **Docker Compose** (v2, comando `docker compose`)
- Un ambiente che esponga il demone Docker, ad esempio:
  - **Docker Desktop** (Windows / macOS), oppure
  - **Colima** o altra **VM** con Docker (macOS / Linux), oppure
  - **Linux** con Docker installato

---

## 1. Clona il repository

```bash
git clone <URL-del-repository> sondaggi
cd sondaggi
```

---

## 2. Configura l’ambiente (file `.env`)

```bash
cp .env.example .env
```

Modifica **`.env`** almeno così (valori coerenti tra loro; puoi adattare nomi e password):

| Variabile | Valore tipico (stack dev) |
|-----------|---------------------------|
| `APP_URL` | `http://127.0.0.1:18080` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `db` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | Uguale a `MYSQL_DATABASE` (es. `sondaggi_db`) |
| `DB_USERNAME` | Uguale a `MYSQL_USER` |
| `DB_PASSWORD` | Uguale a `MYSQL_PASSWORD` |
| `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` | Come da `.env.example` o valori tuoi |

Genera la chiave applicativa **prima** del primo avvio completo (così il servizio `migrate` non fallisce):

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm --no-deps --entrypoint php web artisan key:generate --force
```

Il comando usa l’immagine `web` (eventualmente la costruisce), monta la cartella del progetto e aggiorna `APP_KEY` nel tuo `.env`.

**Coda (opzionale):** nel `.env` puoi usare `QUEUE_CONNECTION=sync` e **non** impostare `COMPOSE_PROFILES=queue` se non ti serve il container **worker**. Con `QUEUE_CONNECTION=database` imposta `COMPOSE_PROFILES=queue` per avviare anche il worker (come nei commenti di `.env.example`).

---

## 3. Avvia lo stack

Assicurati che Docker sia avviato (Docker Desktop aperto, oppure ad esempio `colima start`).

Dalla root del progetto:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

Cosa succede in sintesi:

- viene costruita l’immagine dell’app (**PHP 8.4**, estensioni, asset Vite già compilati in fase di build);
- **MySQL** parte e il servizio **`migrate`** esegue `php artisan migrate --force`;
- il servizio **`web`** espone l’app (nel dev override anche sulla porta host **18080**).

Apri nel browser: **http://127.0.0.1:18080**

- **phpMyAdmin** (solo overlay dev): **http://127.0.0.1:8080** (solo loopback, vedi `docker-compose.dev.yml`).

---

## 4. Dipendenze PHP e asset (tutto in Docker)

- **Composer**: al primo avvio (e quando cambia `composer.lock`) lo script di entrypoint del container `web` esegue `composer install` **dentro il container** (volume `vendor` nominato). Non serve `composer` sul PC.
- **Frontend (Vite)**: dopo modifiche a `resources/js` o `resources/css`, rigenera gli asset con:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile assets run --rm assets
```

(Profilo **`assets`**: container Node che esegue `npm ci && npm run build` sul codice montato; `public/build/` viene aggiornato sul tuo disco. Un volume anonimo su `/app/node_modules` nel servizio `assets` evita errori se sul Mac hai già installato dipendenze diverse da quelle Linux.)

Opzionale: profilo **`tools`** con servizio `composer` per un `composer install` manuale sul mount, se ti serve.

---

## 5. Comandi utili

| Azione | Comando |
|--------|---------|
| Log del container app | `docker compose -f docker-compose.yml -f docker-compose.dev.yml logs -f web` |
| Fermare tutto | `docker compose -f docker-compose.yml -f docker-compose.dev.yml down` |
| Artisan nel container | `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec web php artisan …` |

---

## 6. Documentazione tecnica

Approfondimenti su architettura, frontend, backend e database: cartella **[`docs/`](docs/README.md)**.
