# PLAN — MVP+ Liste Annidate (Backend)

**Data**: 2026-07-08
**Repo**: tobear-laravel-backend (`backend/`)
**Scope**: Schema, API, e tests per liste annidate. Dopo Phase 1 → push + deploy (coordinato con frontend).
**Dipende da**: Frontend Phases 2-5 dipendono da questa API. **Phase 1 deve essere completata e pushata prima di Phase 2 frontend**.

---

## Tempistiche stimate

| Phase | Cosa | Tempo |
|---|---|---|
| Phase 0 | Setup + allineamento | 1-2h |
| Phase 1 | Migration + Controller + Tests + Pint | 4-5 giorni |

Totale ~1 settimana.

---

## Principi di progetto (validati col TL)

| Principio | Decisione |
|---|---|
| Schema nestzati | `parent_id` nullable FK self `tasks.id` + `depth` unsignedTinyInt default 0 su `tasks`. Max depth ≤ 2 (0=root, 1=sub-list, 2=leaf). DB cascade, app gesture conferma |
| Tipo "list" vs "leaf" | **Implicito** da presence di children. Niente `is_list` flag. Ogni item parte leaf, diventa list se qualcuno ci crea figli dentro |
| Drag&drop | Solo intra-level (riordino fratelli di stesso parent_id) |
| Completamento lista | Cascade silenziosa su tutti i figli |
| Delete lista con figli | API ritorna 409 se `?cascade=true` non passato. Frontend mostra modal, poi invia `?cascade=true` |
| Reorder | `PATCH /v1/tasks/reorder` accetta `{ parent_id, ordered_ids }`. Ordina solo siblings di quel parent |
| Data model forward-compat | Sync tier futuro readerà `parent_id` + `depth` + `user_id` già pronti. Nessuna breaking change per early adopter |

---

## Phase 0 — Setup (1-2h)

### 0.1 Branch
```bash
git checkout main
git pull
git checkout -b feat/nested-lists
```

### 0.2 Piano d'attacco concordato
- Backend prima, frontend dopo
- No coordinamento live con frontend session — ogni repo ha il suo piano e PROMPT.md
- Fine Phase 1 = segnalare al TL "Backend Phase 1 completata. Commit pronti. Coordinate deploy."

---

## Phase 1 — Schema + API + Tests (4-5 giorni)

### 1.1 Migration `add_nested_list_columns_to_tasks`

```php
Schema::table('tasks', function (Blueprint $table) {
    $table->foreignId('parent_id')
        ->nullable()
        ->constrained('tasks')
        ->cascadeOnDelete();

    $table->unsignedTinyInteger('depth')
        ->default(0)
        ->after('parent_id');
});
```

Aggiungi indici:
```php
$table->index(['parent_id', 'order']);
```

**Vincoli lato applicazione**:
- `depth ≤ 2` — validare in FormRequest
- `parent_id` punta a task dello stesso user — validare in controller (OwnsModel già lo fa via relazione)

### 1.2 Modello Task

Aggiungi relazioni:
```php
public function parent(): BelongsTo
{
    return $this->belongsTo(Task::class, 'parent_id');
}

public function children(): HasMany
{
    return $this->hasMany(Task::class, 'parent_id');
}

public function hasChildren(): bool
{
    return $this->children()->exists();
}

public function allDescendants(): HasMany
{
    // Ricorsiva per cascade — gestisci in query manuale o collection
}
```

Accessor già esistente: mantieni pattern tranne eventuali aggiunte.

### 1.3 TaskController

**`index`** — già ritorna tutti i task dello user autenticato (`where('user_id', auth()->id())`). Aggiungi `?parent_id=` filtro opzionale per lazy-load (es. `GET /v1/tasks?parent_id=42` ritorna solo figli di task 42). Se `?parent_id=null` (o assente) ritorna root tasks + figli in un colpo solo (sync-ready).

**`store`** — accetta `parent_id` nullable. Calcola `depth` automaticamente:
```php
$depth = $request->parent_id
    ? Task::findOrFail($request->parent_id)->depth + 1
    : 0;

if ($depth > 2) {
    abort(422, 'Maximum nesting depth is 2');
}
```

Validazione: `parent_id` deve esistere e appartenere allo stesso user (OwnsModel).

**`update`** — accetta cambiamento di `parent_id`. Se cambiato:
1. Ricalcola depth del task
2. Ricalcola depth di tutti i descendants recursive (transazione)
3. Conserva `order` nel nuovo sibling group (appendo in fondo)

**`destroy`** — se task ha figli e `?cascade` non è presente:
```php
if ($task->hasChildren() && !$request->boolean('cascade')) {
    return response()->json([
        'error' => 'Task has children',
        'children_count' => $task->children()->count(),
        'cascade_url' => route('tasks.destroy', [
            'task' => $task,
            'cascade' => true,
        ]),
    ], 409);
}
```
Se `?cascade=true`, `$task->delete()` → DB CASCADE elimina tutti i discendenti.

**`complete`** (se endpoint esiste già o crea): se task ha figli, completa ricorsivamente tutti i discendenti. Transazione singola.

**`reorder`** — modifica endpoint esistente per accettare `{ parent_id, ordered_ids }`:
```php
foreach ($orderedIds as $index => $id) {
    Task::where('id', $id)
        ->where('parent_id', $request->parent_id) // safety
        ->update(['order' => $index]);
}
```

**`move`** (opzionale, endpoint separato se preferisci non mischiare in update):
```php
PATCH /v1/tasks/{task}/move
Body: { parent_id: newParentId }
```

### 1.4 Form Requests

- `StoreTaskRequest`: aggiungi `parent_id` nullable `exists:tasks,id`
- `UpdateTaskRequest`: aggiungi `parent_id` nullable with custom rule per validare ownership e depth

### 1.5 TaskResource

Aggiungi campi:
```json
{
  "id": 1,
  "title": "...",
  "completed": true/false,
  "order": 0,
  "parent_id": null,
  "depth": 0,
  "has_children": false,
  "created_at": "...",
  "updated_at": "..."
}
```

`has_children` è computed (caricato via `->loadCount('children')` sulla collection).

### 1.6 Tests PHPUnit (+10 nuovi)

| Test | Cosa verifica |
|---|---|
| `test_guest_cannot_access_nested_endpoints` | Auth barrier su tutte le route v1 |
| `test_can_create_root_task` | parent_id=null, depth=0 |
| `test_can_create_subtask_with_auto_depth` | parent_id con depth, figlio calcola depth+1 |
| `test_cannot_create_depth_3` | 422 quando si tenta depth=3 |
| `test_cannot_move_to_invalid_parent` | parent_id non esistente o altro user |
| `test_complete_cascades_to_children` | completare lista → tutti i figli completed |
| `test_delete_root_with_children_returns_409` | delete senza cascade → 409 |
| `test_delete_with_cascade_removes_subtree` | delete con cascade=true → tutto sparito |
| `test_reorder_per_sibling_group` | reorder di un gruppo non influenza altri |
| `test_move_task_changes_depth_and_descendants` | move sotto altro parent → depth ricorsivo |

Usa `RefreshDatabase` + `Sanctum::actingAs(User::factory()->create())` per integrità.

### 1.7 Pint

```bash
./vendor/bin/pint
```

Prima di ogni commit.

---

## Phase 6/7 — Backend deploy pre-frontend

Dopo Phase 1 completata e committata:

### 6.10 Backend .env Namecheap

- APP_URL=https://tobear.in (o subdomain eventuale)
- FRONTEND_URL=https://tobear.in
- CORS_ALLOWED_ORIGINS=https://tobear.in
- DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD (da cPanel Namecheap)
- SESSION_DOMAIN=tobear.in
- SESSION_DRIVER=database
- APP_DEBUG=false

### 7.2 Post-deploy backend

- `php artisan migrate --force` (via cPanel Terminal o endpoint DebugTokenGuard)
- `php artisan route:list` — verifica debug routes protette
- Verifica `GET /api/user` ritorna 401 (come atteso su auth disabilitato)

### Coordination

Dopo deploy: segnala al TL che la backend Phase 1 è live. Il frontend session può iniziare Phase 2.

---

## Rischi backend

| Rischio | Probabilità | Mitigation |
|---|---|---|
| Depth recalculation complessa su move di task con sub-albero profondo | Media | Test su alberi di 10+ nodi. Transazione singola |
| DB CASCADE DELETE collision con app-level check | Media | Db CASCADE con `ON DELETE CASCADE` sulla FK; l'app check 409 solo dopo app-level ownership check |
| Reorder endpoint rompe compatibilità client attuale | Bassa | Payload esteso mantiene retrocompatibilità: `{ ordered_ids }` funziona anche senza `parent_id` (parent_id=null implicito per root) |

---

## Riferimenti incrociati

- Frontend plan: `frontend/docs/plans/PLAN-2026-07-08-mvp-plus-nested-lists.md`
- Backend AGENTS.md: `AGENTS.md`
- Schema attuale tasks: `database/migrations/...create_tasks_table.php`
