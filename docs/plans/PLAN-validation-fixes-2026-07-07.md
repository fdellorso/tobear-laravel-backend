# Backend — Fix post-validazione 2026-07-07

**Repo**: `backend/`
**Scope**: fix critici e medi emersi dalla validazione post-deploy su x10hosting
**Base**: `VALIDATION-2026-07-07.md` (root `todos_app/`)
**Architettura di deploy**: same-host, catch-all serve SPA da `public/app/`

---

## 1. Commit fix DebugTokenGuard fail-closed

**Riferimenti**: A3, A5, A6, A7, A8 (VALIDATION-2026-07-07.md)
**Cosa fare**:

Il fix è già applicato localmente in `app/Http/Middleware/DebugTokenGuard.php` (sessione validazione 2026-07-07). Il middleware ora è fail-closed:

```php
$expected = config('app.debug_token');
if (! $expected || $token !== $expected) {
    abort(404);
}
```

**Motivazione**: la versione precedente (`if ($token !== config('app.debug_token'))`) confronta con `!==`. Quando sia `$token` (header mancante → `''`) che `config('app.debug_token')` (secret non iniettato → `''`) sono vuoti, `'' !== ''` è `false` → **non abortisce** → route debug esposte pubblicamente. Il fix fail-closed `if (! $expected || ...)` abortisce sempre se il token config è vuoto, indipendentemente dall'header. Questo impedisce esposizioni anche in caso di config cache stantio o secret non iniettato.

Verificare che il fix sia presente localmente, poi includerlo nel commit.

---

## 2. Fix catch-all route per multi-segment paths

**Riferimenti**: A1 (catch-all non matcha path multi-segment), B5/D6 (navigazione diretta a `/app/todo` → 404)
**Cosa fare**:

1. Modifica `routes/web.php:12-18`:
   - **Rimuovi** il route catch-all attuale:
     ```php
     Route::get('/{any}', function () {
         $path = public_path('app/index.html');
         return file_exists($path)
             ? response(file_get_contents($path))
             : redirect(config('app.frontend_url'));
     })->where('any', '^(?!api|assets|css|js).*$');
     ```
   - **Sostituisci** con `Route::fallback`:
     ```php
     Route::fallback(function () {
         $path = public_path('app/index.html');
         if (! file_exists($path)) {
             return redirect(config('app.frontend_url'));
         }
         return response(file_get_contents($path));
     });
     ```

2. **Motivazione**: `Route::get('/{any}')` con `{any}` cattura solo un segmento URL (fino al prossimo `/`). Per `/app/todo`, `{any}` cattura `app` ma `todo` è unmatched → 404. `Route::fallback()` è il modo idiomatico Laravel per catturare TUTTE le route non matchate, inclusi path multi-segment come `/app/todo`, `/app/about`, `/app/login`. La SPA può così ricevere l'HTML shell anche su navigazione diretta via URL.

3. **Sicurezza**: le route API (`routes/api.php`) sono registrate prima di web.php via `withRouting(api: ...)` → `/api/*` matcha le route API prima del fallback. I file statici (assets, CSS, JS) sono serviti da LiteSpeed direttamente e non raggiungono Laravel. Le route di debug (`routes/debug.php`) sono incluse prima del fallback in `web.php`. Nessuna esposizione aggiuntiva.

---

## 3. Security headers via .htaccess

**Riferimenti**: F2, F3 (VALIDATION-2026-07-07.md)
**Cosa fare**:

1. Crea/aggiorna `public/.htaccess` con:
   ```apache
   # Security headers
   <IfModule mod_headers.c>
     Header set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
     Header set X-Content-Type-Options "nosniff"
     Header set X-Frame-Options "SAMEORIGIN"
   </IfModule>

   # Manifest MIME type
   <IfModule mod_mime.c>
     AddType application/manifest+json .webmanifest
   </IfModule>
   ```

2. **Nota**: le direttive sono wrapper in `<IfModule>` per evitare errori 500 se `mod_headers` o `mod_mime` non sono abilitati su x10hosting. Su free hosting alcune feature potrebbero non essere disponibili.

---

## 4. Investigazione cookie domain

**Riferimenti**: F5 (VALIDATION-2026-07-07.md)
**Cosa fare**:

1. Il `.env.production.x10` ha `SESSION_DOMAIN=tobear.x10.mx`, ma il cookie di sessione in produzione ha `domain=.x10.mx`.
2. Verificare se c'è un `php.ini` o `.htaccess` a livello server che imposta `session.cookie_domain = .x10.mx`.
3. Tentativo di fix via `public/.htaccess` (se non già presente):
   ```apache
   php_value session.cookie_domain tobear.x10.mx
   ```
4. Se non risolvibile (server condiviso x10hosting impone il domain), documentare come limitazione. Non bloccante per PWABuilder — il cookie con domain `.x10.mx` è funzionale, solo non ottimale (leak cross-subdomain su altri servizi x10hosting).

---

## 5. Handoff + commit

1. Scrivi `backend/handoffs/2026-07-07-fix-post-validazione.md`
   - Formato comando `/handoff`
   - Cosa è stato fatto (3 fix backend + 1 investigatione)
   - Stato attuale (test passano, aggiornare after verify)
   - Decisioni prese (Route::fallback, fail-closed DebugTokenGuard, .htaccess headers)
   - Prossimi passi (push, redeploy, validazione post-fix)
   - Note per il frontend (nessuna — i fix backend non impattano API)

2. **Commit** in `backend/` con messaggio Conventional Commits EN:
   ```
   fix: resolve post-deploy validation issues

   - Fix DebugTokenGuard fail-closed (abort if config empty)
   - Fix catch-all route for multi-segment SPA paths (Route::fallback)
   - Add security headers and manifest MIME type via .htaccess
   - Investigate cookie domain mismatch
   ```
   Niente `git push` — lo fai tu dopo verifica.

---

**Fine piano backend.**
