PROMPT 1 — Sessione Backend (apri sessione su backend/)
Sei il Technical Lead del progetto Tobear, repo backend Laravel. Esegui il piano di hardening pre-deploy salvato in questo repo.

## Bootstrap (OBBLIGATORIO, in ordine)

1. Leggi AGENTS.md per intero.
2. Leggi gli ultimi 2 file in handoffs/ (più recente per primo).
3. Leggi docs/plans/PLAN-hardening-2026-07-06.md — È IL PIANO DA ESEGUIRE.
4. Per contesto: leggi ../AUDIT-2026-07-06.md (audit completo che ha generato il piano).

## Decisioni già prese (NON rinegoziare)

- Deploy same-host: backend in root, catch-all serve SPA da public/app/. Frontend dist/ va in backend/public/app/ via FTP separato.
- Route di debug (/artisan, /serverphpinfo, /laravelversion) consolidate in routes/debug.php dietro nuovo middleware DebugTokenGuard (token in header X-Debug-Token, mai query string, 404 silente su mismatch).
- APP_KEY come GitHub Secret stabile: placeholder vuoto nei .env.\* tracciati, iniettata via sed nel workflow. Rimuovi step key:generate.
- Credenziali leakate (DB_PASSWORD=frisedda, ARTISAN_DEBUG_TOKEN, TEST_CLEANUP_TOKEN): placeholder nei .env.\* tracciati, valori reali in GitHub Secrets o .env locale gitignored.
- SSRF rimosso: ImageManipulationController::resize accetta solo UploadedFile (rimuovi branch URL string).
- Upload size limit: max:5120 su StoreImageRequest e ResizeImageRequest.
- env() sostituito con config() in ContactController e ExecuteArtisanCommandController; aggiungi key a config/app.php.
- SESSION_DOMAIN=tobear.x10.mx (non .x10.mx), SESSION_SAME_SITE=lax (non none).
- trustProxies(at: '\*').
- CI: aggiungi composer test + pint --test prima del deploy.

## Esecuzione

Esegui le 7 sezioni del piano in ordine. Per ogni sezione:

- Mostrami cosa modifichi prima di farlo (file, righe, diff atteso).
- Dopo le modifiche, esegui `php artisan test` per verificare regressioni.
- Se un test fallisce, fermati e correggi prima di proseguire.

Alla fine:

1. Scrivi handoff in handoffs/2026-07-06-hardening-sicurezza-pre-deploy.md (formato /handoff: Cosa è stato fatto / Stato attuale / Decisioni prese / Prossimi passi / Note per il frontend / File rilevanti).
2. Esegui ./vendor/bin/pint per il code style.
3. Mostrami git status + git diff --stat per revisione.
4. Commit con messaggio Conventional Commits EN: "feat: harden security for pre-production deploy" + body con bullet dei fix.
5. NON eseguire git push — lo faccio io manualmente.

## Secrets GitHub che devi creare tu (te li elenco alla fine)

- APP_KEY (genera con: php artisan key:generate --show)
- ARTISAN_DEBUG_TOKEN (genera con: php -r "echo bin2hex(random_bytes(32));")
- TEST_CLEANUP_TOKEN (opzionale, stesso metodo)
- CONTACT_NOTIFICATION_EMAIL (la tua email)

Inizia leggendo i file del bootstrap e fammi un riepilogo del piano prima di iniziare a modificare.

---

PROMPT 4 — Sessione Backend Fix Post-Validazione (apri sessione su backend/)
Sei il Technical Lead del progetto Tobear, repo backend Laravel. Esegui il piano di fix post-validazione salvato in questo repo.

## Prerequisiti (devono essere già fatti)

- Backend hardenato, committato, pushato, deployato su x10hosting (PROMPT 1).
- Validazione post-deploy eseguita (PROMPT 3, root todos_app/) → report in ../VALIDATION-2026-07-07.md.
- 3 FAIL critici/alti trovati nel backend: catch-all route, security headers, cookie domain.

## Bootstrap (OBBLIGATORIO, in ordine)

1. Leggi AGENTS.md per intero.
2. Leggi gli ultimi 2 file in handoffs/ (più recente per primo).
3. Leggi docs/plans/PLAN-validation-fixes-2026-07-07.md — È IL PIANO DA ESEGUIRE.
4. Per contesto: leggi ../VALIDATION-2026-07-07.md (report validazione che ha generato il piano, sezioni A e F).

## Decisioni già prese (NON rinegoziare)

- Route::fallback() sostituisce Route::get('/{any}') per matchare path multi-segment (/app/todo, /app/about, /app/login). Sicuro: API routes registrate prima (api.php), static files serviti da LiteSpeed.
- DebugTokenGuard fail-closed: if (! $expected || $token !== $expected) abort(404). Già applicato localmente nella sessione validazione — da includere nel commit (verifica file app/Http/Middleware/DebugTokenGuard.php).
- Security headers via public/.htaccess (HSTS, X-Content-Type-Options, X-Frame-Options, manifest MIME type). Wrapper in <IfModule> per evitare errori 500 se moduli non abilitati su x10hosting free.
- Cookie domain: investigare se php.ini/.htaccess server impone .x10.mx. Tentativo fix via php_value session.cookie_domain in .htaccess. Se non risolvibile, documentare come limitazione x10hosting free — non bloccante.

## Esecuzione

Esegui le 5 sezioni del piano in ordine. Per ogni sezione:

- Mostrami cosa modifichi prima di farlo (file, righe, diff atteso).
- Dopo le modifiche, esegui `php artisan test` per verificare regressioni.
- Se un test fallisce, fermati e correggi prima di proseguire.

Alla fine:

1. Scrivi handoff in handoffs/2026-07-07-fix-post-validazione.md (formato /handoff: Cosa è stato fatto / Stato attuale / Decisioni prese / Prossimi passi / Note per il frontend / File rilevanti).
2. Esegui ./vendor/bin/pint per il code style.
3. Mostrami git status + git diff --stat per revisione.
4. Commit con messaggio Conventional Commits EN: "fix: resolve post-deploy validation issues" + body con bullet dei fix.
5. NON eseguire git push — lo faccio io manualmente.

Inizia leggendo i file del bootstrap e fammi un riepilogo del piano prima di iniziare a modificare.

---

# PROMPT 9 — Phase 1 Piano MVP+ (Backend — Liste Annidate)

Sei il Technical Lead del backend Laravel di toBear (repo: `tobear-laravel-backend`, cartella `backend/`).

## Bootstrap (OBBLIGATORIO)
1. Leggi `AGENTS.md` (convenzioni + regole no-push, no-amend).
2. Leggi il piano: `backend/docs/plans/PLAN-2026-07-08-mvp-plus-nested-lists.md`.
3. Leggi gli ultimi 2 handoff in `backend/handoffs/`.
4. Solo dopo, inizia.

## Task — Implementa Phase 1 del piano (Liste Annidate — backend)

### Phase 0 (1-2h)
- Crea branch `feat/nested-lists` da main
- Non serve altro setup: le decisioni tecniche sono nel piano

### Phase 1 (4-5 giorni)
1. **Migration** `add_nested_list_columns_to_tasks`:
   - `parent_id` foreignId, nullable, `constrained('tasks')->cascadeOnDelete()`
   - `depth` unsignedTinyInteger, default 0
   - Index `(parent_id, order)`

2. **Modello Task**: relazioni `parent()`, `children()`, accessor `hasChildren()`

3. **TaskController**:
   - `index`: filtro opzionale `?parent_id=` (per lazy load) — default ritorna tutti
   - `store`: accetta `parent_id`, calcola `depth` automatico (max 2)
   - `update`: può cambiare `parent_id` → ricalcola depth + progenie (transazione)
   - `destroy`: se `hasChildren` + no `?cascade=true` → 409. Se cascade → elimina tutto
   - `complete`: se hasChildren → cascata completamento silenzioso
   - `reorder`: accetta `{ parent_id, ordered_ids }` — ordina solo siblings di quel parent

4. **FormRequest** `StoreTaskRequest`/`UpdateTaskRequest`: accetta `parent_id` nullable con validazione ownership + depth ≤ 2

5. **TaskResource**: aggiungi `parent_id`, `depth`, `has_children`

6. **Tests** (+10 nuovi):
   - Creazione root / subtask con auto-depth
   - Rifiuta depth=3 (422)
   - Complete cascade
   - Delete senza cascade (409) / con cascade (success)
   - Reorder per sibling group
   - Move cambia depth e discendenti

7. **Pint**: esegui `./vendor/bin/pint` prima di ogni commit

## Coordinate
- Il frontend (Vue 3) è in **repo separato** gestito da altra sessione OpenCode. **NON scrivere codice frontend**.
- NO git push senza richiesta esplicita.
- NO git commit --amend su commit già pushati.
- Esegui `./vendor/bin/pint` prima di ogni commit.

## Stop point
Termina alla fine di Phase 1. Fai un commit su `feat/nested-lists` (non pushare). Comunicami in chat: "Backend Phase 1 completata su branch feat/nested-lists, commit pronti. Non pushati — coordinate deploy quando frontend è pronto."

Mantieni il branch locale. Push e merge su main solo quando coordinato col frontend.
