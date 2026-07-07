# Handoff — 2026-07-06 — Hardening sicurezza pre-deploy

## Cosa è stato fatto

1. **Route di debug consolidate** (`routes/debug.php` + `DebugTokenGuard`):
   - Creato `app/Http/Middleware/DebugTokenGuard.php` — legge header `X-Debug-Token`, confronta con `config('app.debug_token')`, opzionale IP allowlist, mismatch → `abort(404)` silente.
   - Creato `routes/debug.php` con 4 route: `GET /serverphpinfo`, `GET /laravelversion`, `GET /artisan/{name}` (read-only), `POST /artisan/migrate` (con `--force`).
   - `ExecuteArtisanCommandController` riscritto: split in `__invoke` (read-only whitelist) + `migrate()` (POST). Rimosso check query-string `token` — delegato al middleware.
   - `routes/web.php` ripulito: rimosse 3 route debug esistenti, aggiunto `require __DIR__.'/debug.php'`.
   - `bootstrap/app.php`: registrato alias `debug-token => DebugTokenGuard::class`.

2. **SSRF rimosso** (`ImageManipulationController::resize`):
   - Rimosso il branch `else` che accettava `image` come URL string con `copy()`.
   - Ora accetta solo `UploadedFile` (file caricato).
   - Sostituito `$request->all()` con `$request->validated()`.
   - Rimosso import `Illuminate\Support\Facades\File` non più usato.

3. **Upload size limit**:
   - `ResizeImageRequest`: `image` → `['required', 'file', 'image', 'max:5120']` (rimosso branch URL).
   - `StoreImageRequest`: aggiunto `max:5120`.

4. **env() → config()**:
   - `ContactController::store`: `env('CONTACT_NOTIFICATION_EMAIL')` → `config('app.contact_notification_email')`.
   - `ExecuteArtisanCommandController`: rimosso `env('ARTISAN_DEBUG_TOKEN')` (gestito dal middleware).
   - `config/app.php`: aggiunte key `debug_token`, `debug_ip_allowlist`, `contact_notification_email`.

5. **Pulizia credenziali leakate** nei `.env` tracciati:
   - `.env.development`, `.env.production.if`, `.env.production.x10`: `APP_KEY=` vuoto, `ARTISAN_DEBUG_TOKEN=` vuoto, `DB_PASSWORD=placeholder`, `CONTACT_NOTIFICATION_EMAIL=` vuoto.
   - `.env.production.x10`: `SESSION_DOMAIN=tobear.x10.mx` (era `.x10.mx`), `SESSION_SAME_SITE=lax` (era `none`).
   - Creato `.env.example` con tutte le key e placeholder.

6. **CI/CD workflow** (`.github/workflows/main.yml`):
   - Rimosso step `Generate app key if missing`.
   - Aggiunti step: `composer install` (con dev), `./vendor/bin/pint --test`, `php artisan test`.
   - Aggiunto step `composer install --no-dev` per produzione dopo i test.
   - Aggiunti step `sed` per iniettare `APP_KEY`, `ARTISAN_DEBUG_TOKEN`, `CONTACT_NOTIFICATION_EMAIL` da secrets.

7. **trustProxies**: `at: '127.0.0.1'` → `at: '*'` in `bootstrap/app.php`.

8. **Documentazione aggiornata**:
   - `AGENTS.md`: PHP version, lingua EN, trait OwnsModel, sezione "Route di debug" aggiunta, `laravel-simplifier` rimosso, secrets elencati.
   - `TODO.md`: 9 hardening item in "Completato", secrets checklist aggiornata.
   - `laravel-conventions SKILL.md`: ownership check pattern → `OwnsModel::authorizeOwnership()`, lingua risposte EN.

## Stato attuale

- 66 test passano (1 skipped: ExampleTest, pre-esistente).
- `.env.development`, `.env.production.if`, `.env.production.x10`: tutti i valori sensibili sono placeholder vuoti. I valori reali vanno iniettati via GitHub Secrets.
- Working tree sporco con tutte le modifiche di hardening — da committare.
- Branch `main` avanti di 2 commit rispetto a `origin/main` (pre-esistenti).

## Decisioni prese

1. **Deploy same-host**: backend catch-all serve SPA da `public/app/`. Frontend `dist/` va in `backend/public/app/` via FTP separato. Confermato.
2. **Route di debug** consolidate in `routes/debug.php` con `DebugTokenGuard` — token in header, mai query string, 404 silente.
3. **APP_KEY** come GitHub Secret stabile: placeholder vuoto nei `.env.*` tracciati, iniettata via `sed` nel workflow. Rimosso step `key:generate`.
4. **SSRF rimosso**: `ImageManipulationController::resize` accetta solo `UploadedFile` — niente URL string.
5. **Upload size limit**: `max:5120` su `StoreImageRequest` e `ResizeImageRequest`.
6. **SESSION_DOMAIN=tobear.x10.mx** (non `.x10.mx`), **SESSION_SAME_SITE=lax** (non `none`).
7. **trustProxies(at: '*')**.
8. **CI**: test + pint prima del deploy. Secrets iniettati via `sed`.
9. `.env.example` creato come reference.
10. **Git history pulizia**: il leak di APP_KEY/password/token rimane nel git history. BFG Repo-Cleaner fuori scope. Password DB `frisedda` va ruotata manualmente sul server.

## Prossimi passi

1. **Creare i secrets GitHub** (elencati sotto).
2. **Ruotare password DB** sul server `mariadb.fritz.box` (non più `frisedda`).
3. **Push su `main`** per attivare il workflow CI/CD.
4. **Verificare deploy** su x10hosting.
5. **Validazione PWABuilder** dopo deploy.
6. **Feature principali**: liste annidate (`parent_id`), sync multi-dispositivo.

## Note per il frontend

- **Nessun cambiamento API.** Tutti gli endpoint esistenti (Task, Album, Image, ImageManipulation, Contact, Stats, Auth) invariati — stessi campi, stesso formato risposta.
- I messaggi di risposta sono in inglese (già dalla precedente sessione del 2026-07-04).
- Le route di debug (`/serverphpinfo`, `/artisan`, `/laravelversion`) ora richiedono header `X-Debug-Token` e sono silenziose su mismatch — il frontend non ne fa uso.

## Secrets GitHub da creare

- `APP_KEY` → genera con `php artisan key:generate --show`
- `ARTISAN_DEBUG_TOKEN` → genera con `php -r "echo bin2hex(random_bytes(32));"`
- `TEST_CLEANUP_TOKEN` → opzionale, stesso metodo (per E2E su ambiente test)
- `CONTACT_NOTIFICATION_EMAIL` → la tua email per notifiche contatto

(Esistenti: `DB_HOST_X10`, `DB_DATABASE_X10`, `DB_USERNAME_X10`, `DB_PASSWORD_X10`, `MAIL_HOST_X10`, `MAIL_USERNAME_X10`, `MAIL_PASSWORD_X10`, `MAIL_FROM_ADDRESS_X10`, `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_FOLDER`)

## File rilevanti

```
routes/debug.php                                    # NUOVO: route debug consolidate dietro middleware
routes/web.php                                      # MODIFICATO: rimosse route debug, require debug.php
app/Http/Middleware/DebugTokenGuard.php              # NUOVO: middleware X-Debug-Token
app/Http/Controllers/ExecuteArtisanCommandController.php  # REWRITE: split GET/POST, whitelist read-only
app/Http/Controllers/ContactController.php           # MODIFICATO: env() → config()
app/Http/Controllers/V1/ImageManipulationController.php  # MODIFICATO: SSRF rimosso, $all → $validated
app/Http/Requests/ResizeImageRequest.php             # MODIFICATO: solo file upload, max:5120
app/Http/Requests/StoreImageRequest.php              # MODIFICATO: max:5120
config/app.php                                       # MODIFICATO: debug_token, debug_ip_allowlist, contact_notification_email
bootstrap/app.php                                    # MODIFICATO: alias debug-token, trustProxies at '*'
.github/workflows/main.yml                           # MODIFICATO: test + pint + secret injection
.env.development                                     # MODIFICATO: placeholder vuoti
.env.production.if                                   # MODIFICATO: placeholder vuoti
.env.production.x10                                  # MODIFICATO: placeholder vuoti, SESSION_DOMAIN, SESSION_SAME_SITE
.env.example                                         # NUOVO: reference
AGENTS.md                                            # MODIFICATO: lingua, ownership, debug routes, laravel-simplifier rimosso
TODO.md                                              # MODIFICATO: hardening items, secrets checklist
.opencode/skills/laravel-conventions/SKILL.md         # MODIFICATO: ownership check trait
handoffs/2026-07-06-hardening-sicurezza-pre-deploy.md  # QUESTO FILE
```
