# Handoff — 2026-07-07 — Fix post-validazione

## Cosa è stato fatto

1. **Fix DebugTokenGuard fail-closed** (`app/Http/Middleware/DebugTokenGuard.php`):
   - Verificato fix già applicato: `if (! $expected || $token !== $expected)` abortisce sempre quando il token config è vuoto, indipendentemente dall'header.
   - Incluso nel commit (file già ` M` da sessione validazione).

2. **Fix catch-all route per multi-segment paths** (`routes/web.php:12-18`):
   - Sostituito `Route::get('/{any}', ...)->where(...)` con `Route::fallback(...)`.
   - `Route::fallback()` matcha TUTTE le route non registrate, inclusi path multi-segment (`/app/todo`, `/app/about`, `/app/login`).
   - API routes registrate prima via `withRouting(api:)`, debug routes incluse prima del fallback, static files serviti da LiteSpeed — nessuna esposizione aggiuntiva.

3. **Security headers via .htaccess** (`public/.htaccess`):
   - Aggiunto blocco HSTS (`max-age=31536000; includeSubDomains`), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN` in `<IfModule mod_headers.c>`.
   - Aggiunto `AddType application/manifest+json .webmanifest` in `<IfModule mod_mime.c>`.
   - Wrapper `<IfModule>` per evitare errori 500 su x10hosting free se moduli non abilitati.

4. **Investigazione cookie domain** (`public/.htaccess`):
   - Aggiunto tentativo di override via `php_value session.cookie_domain tobear.x10.mx` in `<IfModule mod_php.c>`.
   - **Nota**: x10hosting free usa LiteSpeed (non mod_php) — la direttiva sarà probabilmente inerte. Da verificare post-deploy. Se il cookie resta `.x10.mx`, è una limitazione tecnica dell'hosting condiviso, non bloccante per PWABuilder (cookie funzionale, solo leak cross-subdomain).

## Stato attuale

- 66 test passano, 1 skipped (ExampleTest pre-esistente).
- Working tree con modifiche da committare: `routes/web.php`, `public/.htaccess`, `app/Http/Middleware/DebugTokenGuard.php`, `docs/plans/PLAN-validation-fixes-2026-07-07.md`.
- Branch `main` allineato con `origin/main`.

## Decisioni prese

1. **Route::fallback** → preferito a `Route::get('/{any}', ...)` con regex e `Route::get('/{any?}', ...)` per match multi-segment. Idiomatico Laravel, sicuro (registrato dopo api/debug routes).
2. **DebugTokenGuard fail-closed** → `if (! $expected || $token !== $expected)` protegge anche quando `debug_token` config è vuoto/iniettato.
3. **Security headers via .htaccess con `<IfModule>`** → safe per free hosting, non genera 500 se moduli assenti.
4. **Cookie domain: wrapper `<IfModule mod_php.c>`** → sicuro (no 500), probabilmente inerte su LiteSpeed. Limitazione documentata. Non bloccante.

## Prossimi passi

1. **Revisione diff** → git status + git diff --stat (da mostrare a chi ha eseguito il prompt).
2. **Commit** → `fix: resolve post-deploy validation issues` con body a bullet.
3. **NO git push** — chi ha eseguito il prompt lo fa manualmente.
4. **Post-deploy**: validazione su x10hosting:
   - Verificare che `/app/todo`, `/app/about`, `/app/login` funzionino con navigazione diretta (URL bar).
   - Verificare HSTS + X-Content-Type-Options + X-Frame-Options con `curl -I`.
   - Verificare manifest content-type: `curl -I /app/manifest.webmanifest` → `application/manifest+json`.
   - Verificare cookie domain: `domain=tobear.x10.mx` o `.x10.mx` (se resta `.x10.mx`, documentare come limitazione).
5. **Frontend fix** (repo separato): bug `cspDynamicPlugin` che rimuove meta/link tag dall'HTML — bloccante per PWABuilder.

## Note per il frontend

- **Nessun cambiamento API.** Endpoint, campi, formato risposta invariati.
- I fix riguardano solo: catch-all per navigazione diretta (ora `/app/todo` funziona da URL bar), security headers lato server (HSTS, X-Content-Type-Options, manifest MIME type).
- Il fix critico per PWABuilder (cspDynamicPlugin che rimuove `<link rel="manifest">`) è nel frontend — repo separato, richiede altra sessione.

## File rilevanti

```
routes/web.php                                        # MODIFICATO: catch-all → Route::fallback
public/.htaccess                                      # MODIFICATO: security headers + manifest MIME + cookie domain
app/Http/Middleware/DebugTokenGuard.php                # GIÀ MODIFICATO: fail-closed fix
docs/plans/PLAN-validation-fixes-2026-07-07.md         # NUOVO: piano eseguito (untracked)
handoffs/2026-07-07-fix-post-validazione.md             # QUESTO FILE
```
