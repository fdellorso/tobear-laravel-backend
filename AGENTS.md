# toBear — Backend (Laravel)

API REST per toBear, todo app in stile [Clear](https://www.useclear.com/) per iOS/web.
Stack: Laravel 12, PHP 8.2, Sanctum (SPA cookie auth), MySQL/SQLite, Intervention/Image.

## Bootstrap di sessione

All'inizio di OGNI sessione, in quest'ordine:

1. Leggi questo AGENTS.md per intero.
2. Leggi gli ultimi 2 file in `handoffs/` (ordinati per data, il più recente per primo).
3. Solo dopo, chiedi cosa si vuole fare oggi — non assumere il task dal solo handoff.

Se l'utente dice "leggi gli handoff" o "riprendi da dove eravamo", esegui i punti 1-2 senza chiedere conferma.

## Architettura

```
app/
  Http/
    Controllers/
      Auth/            # Breeze scaffolding (login, register, verify, password reset)
      V1/               # API versionata: TaskController, AlbumController, ImageController, ImageManipulationController
      ExecuteArtisanCommandController.php
      FileController.php
    Requests/           # FormRequest per validazione (Store*, Update*)
    Resources/V1/        # JSON:API Resource transformers
    Middleware/
  Models/                # Task, Album, Image, ImageManipulation, User
routes/
  api.php                # tutte le route v1, sotto auth:sanctum + verified
  auth.php               # route Breeze (login/register/verify/reset)
  web.php
  console.php
database/migrations/
```

Pattern delle risorse: ogni entità (Task, Album, Image) segue Controller → FormRequest → Model → Resource. Nuove entità seguono lo stesso schema a 4 file.

## Convenzioni di progetto (NON assumere, usa queste)

- **Autenticazione**: Sanctum SPA mode con cookie (`withCredentials` + XSRF token lato frontend), NON Bearer token. Tutte le route API protette stanno dentro `Route::middleware(['auth:sanctum', 'verified'])`.
- **Versionamento API**: tutto sotto `Route::prefix('v1')`. Nuovi endpoint vanno in `app/Http/Controllers/V1/`.
- **Ownership check manuale**: i controller verificano `$request->user()->id != $model->user_id` e fanno `abort(403)` esplicitamente — non si usa Policy/Gate per ora. Segui questo pattern per coerenza, a meno che non venga chiesto esplicitamente di migrare a Policies.
- **Ordinamento task**: campo `order` su `tasks`, gestito con un endpoint dedicato `PATCH /v1/tasks/reorder` che riceve un array di id in ordine e fa update posizionale. Non aggiungere logica di ordinamento altrove.
- **Risposte**: sempre tramite Resource (`TaskResource::collection(...)` o `new TaskResource($task)`), mai array raw, per restare compatibili col frontend che si aspetta `response.data.data`.
- **Lingua**: messaggi di errore/risposta utente-facing in italiano (es. "Task eliminato.", "Ordine aggiornato con successo."). Mantieni questa convenzione per nuovi endpoint a meno che non si stia esplicitamente internazionalizzando.
- **Validazione**: tramite FormRequest dedicate (`StoreTaskRequest`, `UpdateTaskRequest`), non `$request->validate()` inline nei controller nuovi — eccetto per endpoint molto piccoli come `reorder` dove la validazione è inline per semplicità.

## Workflow di modifica

- Usa `git status` e `git diff` prima di ogni commit per verificare cosa stai per inviare.
- Per modifiche a modelli con campi `fillable`, verifica sempre la migration corrispondente in `database/migrations/` — non assumere lo schema.
- Quando aggiungi un endpoint, aggiorna `routes/api.php`, crea/aggiorna FormRequest e Resource, e verifica che il pattern di ownership sia rispettato.
- Per cambi che toccano anche il frontend (es. nuovo campo in risposta API), segnalalo esplicitamente nel messaggio finale e nell'handoff: il frontend è in un repo separato (`tobear-vuejs-frontend`) gestito con un'altra sessione OpenCode.
- "Non modificare ancora" / "Do NOT edit yet" → fai solo analisi e proponi un piano, senza toccare file.

## Comandi utili

```bash
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed
php artisan test
./vendor/bin/pint          # code style, esegui prima di ogni commit
composer dev                # serve + queue + pail + vite insieme (richiede frontend nello stesso contesto se serve npm run dev)
```

## Cosa NON fare

- Non introdurre middleware pipeline complessi o dipendenze pesanti senza necessità reale (preferenza nota: setup minimali).
- Non cambiare il meccanismo di auth da cookie-based Sanctum a token Bearer senza esplicita richiesta — il frontend dipende da questo.
- Non rimuovere il check di ownership manuale nei controller per "semplificare" — è una scelta consapevole finché non si migra a Policies.
- Non eseguire `git push` o creare PR senza che venga richiesto esplicitamente.

## Skills disponibili

- `laravel-conventions` — struttura controller/request/resource, naming, dove va cosa (specifica di questo progetto).
- `sanctum-auth` — specifiche dell'auth SPA cookie-based di questo progetto.
- `task-resource-pattern` — come replicare il pattern Task/Album/Image per una nuova entità.
- `spatie-laravel-php` (Spatie, installata via `npx skills add spatie/guidelines-skills`) — standard PSR-12 e convenzioni Laravel generiche (typed properties, constructor promotion, early returns, naming, validazione, Blade).
- `spatie-security` (Spatie) — linee guida di sicurezza generiche (CSRF, hashing password, permessi DB, hardening server).
- `laravel-simplifier` (Laravel ufficiale, installata via `npx skills add laravel/agent-skills`) — revisiona codice PHP/Laravel modificato di recente per chiarezza e coerenza, senza cambiarne il comportamento.

**Come si combinano:**
- `laravel-conventions` e `task-resource-pattern` restano la fonte di verità su COSA fare in questo specifico progetto (dove va un file, quale pattern di ownership usare, come è strutturata una risorsa).
- `spatie-laravel-php` interviene sul COME scrivere il codice PHP/Laravel a livello di stile e qualità generale (PSR-12, naming, validazione) — usala in supporto, non in sostituzione delle convenzioni di progetto: se c'è conflitto (es. Spatie suggerisce un pattern diverso dal nostro ownership check manuale), vince la convenzione di progetto a meno che non si stia deliberatamente migrando verso quel pattern.
- `spatie-security` è utile soprattutto quando si lavora su `sanctum-auth`, CORS, gestione cookie/sessioni o configurazione server.
- `laravel-simplifier` va usata a fine sessione o su richiesta esplicita per una passata di pulizia sul codice appena scritto, non durante lo sviluppo attivo di una feature.

## Fine sessione

Prima di chiudere una sessione di lavoro significativa, esegui `/handoff` per scrivere il file di handoff in `handoffs/`.
