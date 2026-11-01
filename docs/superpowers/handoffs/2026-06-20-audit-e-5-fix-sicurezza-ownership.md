# Handoff — 2026-06-20 — Audit e 5 fix (sicurezza + ownership)

## Cosa è stato fatto

1. **Audit completo del backend** — analisi di tutti i model, controller, route, migration, test, composer.json, configurazioni, e verifica coerenza con AGENTS.md/skills. Report strutturato consegnato al founder.

2. **Fix 1 — ExecuteArtisanCommandController** (`app/Http/Controllers/ExecuteArtisanCommandController.php`)
   - Aggiunto controllo tramite `?token=` query string: il controller confronta il token con `env('ARTISAN_DEBUG_TOKEN')`; se assente o errato → 404 silenzioso.
   - Whitelist di 5 comandi: `route:list`, `cache:clear`, `config:clear`, `migrate:status`, `queue:failed`. Qualsiasi altro comando → 404.
   - Rimosso il passaggio di `$request->all()` come parametri ad Artisan (era un injection vector).
   - Token generato (64 char hex) e salvato in `.env` come `ARTISAN_DEBUG_TOKEN`. Aggiunta chiave vuota anche in `.env.development`, `.env.production.if`, `.env.production.x10`.

3. **Fix 2 — AlbumController ownership check invertito** (`app/Http/Controllers/V1/AlbumController.php`)
   - Corretta la condizione `if (!$request->user()->id != $album->user_id)` → `if ($request->user()->id != $album->user_id)` in show(), update(), destroy().
   - La doppia negazione bloccava sempre il proprietario legittimo (logica: `!userId` = false, `false != $album->user_id` = true per qualsiasi user_id > 0).

4. **Fix 3 — Image reso user-scoped** (4 file toccati)
   - Nuova migration `2026_06_20_072005_add_user_id_to_images_table.php`: aggiunge `user_id` come foreignId con constrained + cascadeOnDelete.
   - `app/Models/Image.php`: `user_id` in `$fillable`; aggiunto metodo `user()`.
   - `app/Models/User.php`: aggiunto metodo `images()`.
   - `app/Http/Controllers/V1/ImageController.php`: index() filtra per `user_id`; store() assegna `user_id` dal richiedente; destroy() chiama `authorizeImage()`.

5. **Fix 4 — ImageResource** (`app/Http/Resources/V1/ImageResource.php`)
   - Rimosso `'name' => $this->name` perché il campo `name` non esiste nella tabella `images` (solo `path`, `label`).

6. **Fix 5 — StoreImageRequest dead code** (`app/Http/Requests/StoreImageRequest.php`)
   - Rimosso blocco `if (!isset($rules['image']) || !is_array($rules['image']))` sempre falso.
   - Rimosso controllo `instanceof UploadedFile` ridondante (la validazione `'image'` era già nell'array iniziale).
   - Semplificato il metodo `rules()` al return diretto.

## Stato attuale

- **Auth** (Sanctum SPA cookie-based): funzionante, testato (4 feature test Breeze standard)
- **Task CRUD + reorder**: funzionante, ownership check presente, messaggi in italiano, **zero test**
- **Album CRUD**: **appena fixato** (bug ownership), ownership check ora corretto, **zero test**
- **Image**: **appena reso user-scoped** (migration da eseguire, vedi sotto), ownership check aggiunto in destroy/index/store, **zero test**
- **ImageManipulation** (resize album): funzionante, ownership check presente, **zero test**
- **ExecuteArtisanCommandController**: protetto da token + whitelist, **da testare manualmente**

**⚠️ La migration `add_user_id_to_images_table` è stata creata ma NON ancora eseguita.** Prima di testare Image, lanciare:
```bash
php artisan migrate
```

**⚠️ Vecchie immagini** (create prima di questa migration) avranno `user_id = NULL` e non saranno accessibili via API finché non si assegna un user_id o si aggiorna la migration per dare un default.

## Decisioni prese

- **Token debug via query string**: scelto `?token=` invece di header custom per permettere l'uso diretto da URL (anche da telefono/browser). Il token è generato con `bin2hex(random_bytes(32))` (64 char hex).
- **Whitelist chiusa**: abbiamo preferito una whitelist esplicita (5 comandi) invece di pattern matching, per evitare di esporre inavvertitamente comandi pericolosi.
- **404 silenzioso**: token sbagliato o comando non whitelistato danno 404, non 401/403, per non rivelare l'esistenza dell'endpoint.
- **Ownership check manuale mantenuto**: nonostante l'audit abbia rilevato che sarebbe il momento di migrare a Policy, abbiamo corretto il bug mantenendo il pattern esistente (check manuale con `abort(403)`) per coerenza col resto del codice e per non introdurre un cambio architetturale in un fix session. La migrazione a Policy rimane un TODO.
- **Risposta delete non uniformizzata volutamente**: Image::destroy continua a tornare 204/null come prima, non l'abbiamo cambiata in JSON 200 per non rompere il frontend. Da valutare in seguito.

## Prossimi passi

1. **Eseguire `php artisan migrate`** per applicare la nuova migration di Image.
2. **Testare manualmente** l'endpoint `/artisan/route:list?token=<token>` e verificare che comandi non whitelistati diano 404.
3. **Testare manualmente** Album show/update/destroy con utente proprietario e non proprietario per verificare il fix.
4. **Testare manualmente** Image upload con utente A, verify che utente B non veda l'immagine in index né possa cancellarla.
5. **Scrivere test** per le 4 risorse API (Task, Album, Image, ImageManipulation) — attualmente copertura zero sull'intera logica di business. Priorità alta.
6. **Uniformare le risposte HTTP** di delete (Task: 200 JSON, Album: 204, Image: 204).
7. **Valutare migrazione a Policy** per l'ownership check (invece del pattern manuale `if ($req->user()->id != $model->user_id) abort(403)`).
8. **Rimuovere o proteggere** `/serverphpinfo` (espone phpinfo() pubblicamente su web.php).

## Note per il frontend

- **Nessun breaking change API** in questa sessione: tutti gli endpoint Task, Album, ImageManipulation restituiscono gli stessi campi di prima.
- **ImageResource**: rimosso il campo `name` (non esisteva nel database, tornava sempre null). Il frontend se lo stava già ignorando presumibilmente. I campi disponibili sono: `id`, `label`, `path` (URL assoluto via Storage), `created_at`.
- **ImageController::store()** ora assegna `user_id` al backend in automatico — nessun cambiamento per il frontend, non deve inviare `user_id`.
- Se il frontend ha logica che assumeva `name` su Image, va rimossa.

## File rilevanti

```
app/Http/Controllers/ExecuteArtisanCommandController.php   # Fix 1: token + whitelist
app/Http/Controllers/V1/AlbumController.php                # Fix 2: ownership check
app/Http/Controllers/V1/ImageController.php                # Fix 3: user-scoped
app/Models/Image.php                                        # Fix 3: +user_id, +user()
app/Models/User.php                                         # Fix 3: +images()
database/migrations/2026_06_20_072005_add_user_id_to_images_table.php  # Fix 3: nuova migration
app/Http/Resources/V1/ImageResource.php                    # Fix 4: rimosso name
app/Http/Requests/StoreImageRequest.php                    # Fix 5: dead code rimosso
.env                                                       # Fix 1: ARTISAN_DEBUG_TOKEN (gitignored)
.env.development                                           # Fix 1: chiave template vuota
.env.production.if                                         # Fix 1: chiave template vuota
.env.production.x10                                        # Fix 1: chiave template vuota
```
