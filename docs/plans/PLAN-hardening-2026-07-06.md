# Backend Hardening — Piano di esecuzione

**Repo**: `backend/`
**Scope**: solo critici+alta sicurezza/build
**Base**: audit `AUDIT-2026-07-06.md` (sezioni 7, 8, 12)
**Architettura di deploy**: same-host, catch-all serve SPA da `public/app/`, frontend FTP in `backend/public/app/`

---

## 1. Consolidamento route di debug

**Riferimenti**: C2, C3, A3 (parziale)
**Cosa fare**:

1. Crea `routes/debug.php` con:
   - `GET /serverphpinfo` (dietro `DebugTokenGuard`)
   - `GET /laravelversion` (dietro `DebugTokenGuard`)
   - `GET /artisan/{name}` (dietro `DebugTokenGuard` + solo lettura; per `migrate` cambia in POST)
   - `POST /artisan/migrate` (con `--force`, solo POST, via `DebugTokenGuard`)

2. Crea `app/Http/Middleware/DebugTokenGuard.php`:
   - Legge header `X-Debug-Token`
   - Confronta con `config('app.debug_token')`
   - Su mismatch: `abort(404)` (silente, non rivela esistenza)
   - Opzionale: IP allowlist da `config('app.debug_ip_allowlist', [])`

3. Modifica `routes/web.php`:
   - Rimuovi le 3 route debug esistenti
   - Aggiungi `require __DIR__.'/debug.php';`

4. Modifica `app/Http/Controllers/ExecuteArtisanCommandController.php`:
   - `env('ARTISAN_DEBUG_TOKEN')` → `config('app.debug_token')`

5. Modifica `config/app.php`:
   - Aggiungi `'debug_token' => env('DEBUG_TOKEN', '')`
   - Aggiungi `'debug_ip_allowlist' => env('DEBUG_IP_ALLOWLIST', [])`
   - Aggiungi `'contact_notification_email' => env('CONTACT_NOTIFICATION_EMAIL', '')`

6. Modifica `ContactController.php:16`:
   - `env('CONTACT_NOTIFICATION_EMAIL')` → `config('app.contact_notification_email')`

7. Modifica `bootstrap/app.php`:
   - Registra alias `DebugTokenGuard` nel middleware aliases

---

## 2. SSRF e upload size limit

**Riferimenti**: A1, A2
**Cosa fare**:

1. Modifica `ImageManipulationController::resize` (`:79-86`):
   - **Rimuovi** il branch che accetta `image` come URL string
   - Lascia solo il branch che accetta `UploadedFile`
   - Se il resize da URL fosse necessario in futuro, lo implementeremo con allowlist + queue + timeout

2. Modifica `ResizeImageRequest::rules()`:
   - Rimuovi regola `url` dal campo `image`
   - Aggiungi `max:5120` alla regola `image` (se `UploadedFile`)

3. Modifica `StoreImageRequest::rules()`:
   - Aggiungi `max:5120` alla regola `image`

---

## 3. Pulizia credenziali leakate nei .env tracciati

**Riferimenti**: C1, C4
**Cosa fare**:

1. Modifica `backend/.env.development`:
   - `APP_KEY=` (vuoto)
   - `DB_PASSWORD=placeholder`
   - `ARTISAN_DEBUG_TOKEN=`
   - `TEST_CLEANUP_TOKEN=`
   - `CONTACT_NOTIFICATION_EMAIL=`

2. Modifica `backend/.env.production.if`:
   - `APP_KEY=` (vuoto)
   - Stessi placeholder

3. Modifica `backend/.env.production.x10`:
   - `APP_KEY=` (vuoto)
   - `ARTISAN_DEBUG_TOKEN=`
   - `CONTACT_NOTIFICATION_EMAIL=`
   - `SESSION_DOMAIN=tobear.x10.mx` (era `.x10.mx` — fix M1)
   - `SESSION_SAME_SITE=lax` (era `none` — fix M1)
   - `DB_PASSWORD=placeholder` (già placeholder, ok)
   - `MAIL_PASSWORD=placeholder` (già placeholder, ok)

4. Crea/aggiorna `backend/.env.example` con tutte le key e placeholder come reference per nuovi sviluppatori.

5. **Nota**: i valori reali (`frisedda`, `tobear-artisan-2024`, APP_KEY) restano nel git history.
   - La pulizia del git history richiede BFG Repo-Cleaner o `git filter-repo` — **è fuori scope di questa sessione**, lo segnalo come follow-up.
   - Per la password `frisedda` su `mariadb.fritz.box`: devi **rotarla manualmente sul DB** e mettere la nuova password in `.env` locale (gitignored).

---

## 4. Workflow GitHub — CI/CD

**Riferimenti**: C1, A3, M8
**Cosa fare**:

1. Modifica `.github/workflows/main.yml`:
   - **Rimuovi** step `key:generate` (sia la versione condizionale che quella incondizionale)
   - **Aggiungi** step `sed` per iniettare `APP_KEY` da secret `${{ secrets.APP_KEY }}`
   - **Aggiungi** step `sed` per `ARTISAN_DEBUG_TOKEN` da secret
   - **Aggiungi** step `sed` per `TEST_CLEANUP_TOKEN` da secret (se serve in prod; altrimenti salta)
   - **Aggiungi** step `sed` per `CONTACT_NOTIFICATION_EMAIL` da secret
   - **Aggiungi** step `composer install` (con dev) prima dei test
   - **Aggiungi** step `./vendor/bin/pint --test`
   - **Aggiungi** step `php artisan test`
   - Ordine: checkout → composer install (dev) → pint → test → composer install --no-dev → cp .env → sed secrets → FTP deploy

2. **Tu devi creare i seguenti secrets nel repository GitHub**:
   - `APP_KEY` — genera con `php artisan key:generate --show`
   - `ARTISAN_DEBUG_TOKEN` — genera con `php -r "echo bin2hex(random_bytes(32));"`
   - `TEST_CLEANUP_TOKEN` — stesso metodo (opzionale, se serve E2E in prod)
   - `CONTACT_NOTIFICATION_EMAIL` — la tua email per notifiche contatto
   - (gli esistenti DB_*, MAIL_*, FTP_* sono già ok)

---

## 5. trustProxies

**Riferimenti**: M2
**Cosa fare**:

1. Modifica `bootstrap/app.php:15`:
   - `trustProxies(at: '*')` (cambia da `'127.0.0.1'`)
   - Oppure per maggiore sicurezza: `trustProxies(at: config('app.trusted_proxies', '*'))` e aggiungi `'trusted_proxies'` a `config/app.php` con default `'*'`

---

## 6. Documentazione

**Riferimenti**: A6
**Cosa fare**:

1. Modifica `backend/AGENTS.md`:
   - Header: "PHP ^8.2 (runtime 8.4.1)" (rimuovi "PHP 8.2" ambiguo)
   - Sezione "Convenzioni di progetto": "Lingua: **English** per messaggi utente-facing. Le stringe italiane sono state migrate a EN (handoff 2026-07-04)."
   - Sezione "Convenzioni di progetto": "Ownership check: tramite trait **`OwnsModel::authorizeOwnership()`**" (non `authorizeTask`/inline)
   - Aggiungi sezione "Route di debug": spiega `routes/debug.php`, `DebugTokenGuard`, token in header
   - Stack table: rimuovi "PHP 8.2" dalla header line, lascia solo la tabella con 8.4.1
   - Sezione "Skills": rimuovi `laravel-simplifier` (non installato)

2. Modifica `backend/TODO.md`:
   - Sezione "Completato": aggiungi righe per i fix fatti in questa sessione
   - Sezione "Pre-deploy": aggiorna stati
   - Aggiungi la nuova variabile `DEBUG_TOKEN` nella checklist deploy

3. Modifica `backend/.opencode/skills/laravel-conventions/SKILL.md`:
   - Sostituisci il pattern `authorizeTask()` con il trait `OwnsModel::authorizeOwnership()`
   - Sezione: "Ownership check: usa `$this->authorizeOwnership($request, $model)` dal trait `App\Traits\OwnsModel`"

---

## 7. Handoff + commit

1. Scrivi `backend/handoffs/2026-07-06-hardening-sicurezza-pre-deploy.md`
   - Usa il formato del comando `/handoff`
   - Cosa è stato fatto
   - Stato attuale
   - Decisioni prese (deploy same-host, debug route consolidato, APP_KEY come secret, SSRF rimosso)
   - Prossimi passi (test manuali, deploy x10hosting, validazione PWABuilder)
   - Note per il frontend (nessuna — tutto backend questo giro)

2. **Commit** in `backend/` con messaggio in Conventional Commits EN:
   ```
   feat: harden security for pre-production deploy
   
   - Consolidate debug routes under DebugTokenGuard (routes/debug.php)
   - Add Upload size limit to StoreImageRequest and ResizeImageRequest
   - Remove SSRF surface from ImageManipulationController::resize
   - Clean leaked credentials from tracked .env files
   - Replace env() with config() in ContactController and Artisan controller
   - Add CI test/lint steps to deploy workflow
   - Fix SESSION_DOMAIN and trustProxies for production
   - Update AGENTS.md, TODO.md, laravel-conventions skill
   ```
   Niente `git push` — lo fai tu dopo verifica.

---

**Fine piano backend.** Il piano frontend è in `frontend/docs/plans/PLAN-hardening-2026-07-06.md`.
