---
name: laravel-conventions
description: Convenzioni strutturali del backend toBear (Laravel) — dove va ogni tipo di file, naming, pattern Controller/Request/Resource. Usa questa skill quando crei nuovi endpoint, controller, model o quando non sei sicuro dove posizionare un file nel progetto.
license: MIT
metadata:
  project: tobear-laravel-backend
---

# Laravel conventions — toBear backend

## Struttura a 4 file per ogni risorsa

Ogni entità del dominio (Task, Album, Image, ...) ha sempre questi 4 file:

1. **Model** — `app/Models/{Nome}.php`, con `$fillable` esplicito, usa `HasFactory`.
2. **Controller** — `app/Http/Controllers/V1/{Nome}Controller.php`, metodi REST standard (index, store, show, update, destroy) + eventuali azioni custom (es. `reorder`).
3. **FormRequest** — `app/Http/Requests/Store{Nome}Request.php` e `Update{Nome}Request.php` per la validazione in ingresso.
4. **Resource** — `app/Http/Resources/V1/{Nome}Resource.php` per la trasformazione in JSON in uscita.

Non saltare nessuno di questi 4 file per "velocità": è il pattern consolidato del progetto e il frontend si aspetta la forma di risposta che ne deriva (`{ data: {...} }` o `{ data: [...] }`).

## Route

Tutte le route autenticate vivono in `routes/api.php`, dentro:









Per risorse CRUD standard usa `Route::apiResource(...)`. Per azioni custom (tipo `reorder`), aggiungi una route esplicita PRIMA di `apiResource` se rischia di confliggere con `{resource}` wildcard (es. `/tasks/reorder` deve precedere `apiResource('tasks', ...)`).

## Ownership check

Ownership check tramite trait `App\Traits\OwnsModel::authorizeOwnership()`:
Usa `$this->authorizeOwnership($request, $model)` all'inizio di show, update, destroy di ogni controller che usa il trait.

## Risposte

- Successo con dati: ritorna sempre una Resource o una collection di Resource.
- Successo senza dati (es. delete): `response()->json(['message' => '...'], 200)` con messaggio in inglese.
- Errore di autorizzazione tra utenti: `response()->json(['message' => '...'], 403)`.

## Cosa controllare prima di aggiungere un campo

Prima di aggiungere un campo a un model:
1. Verifica la migration esistente in `database/migrations/`.
2. Se il campo non esiste ancora, crea una NUOVA migration (`php artisan make:migration add_x_to_y_table`) — non modificare una migration già "storica" se è già stata eseguita in altri ambienti.
3. Aggiorna `$fillable` nel model.
4. Aggiorna FormRequest e Resource corrispondenti.
