---
name: task-resource-pattern
description: Come creare una nuova entità/risorsa API replicando esattamente il pattern usato da Task (il modello di riferimento del progetto). Usa questa skill quando devi aggiungere una nuova feature CRUD-like a toBear (es. "lista della spesa", "tag", "categoria", liste condivise).
license: MIT
metadata:
  project: tobear-laravel-backend
---

# Task resource pattern — template per nuove entità

`Task` è il modello di riferimento del progetto. Quando aggiungi una nuova entità simile (CRUD user-scoped, eventualmente ordinabile), segui esattamente questo schema.

## 1. Migration

```php
Schema::create('{tabella}', function (Blueprint $table) {
    $table->id();
    $table->string('title');           // o nome appropriato
    $table->text('description')->nullable();
    $table->boolean('completed')->default(false); // se applicabile
    $table->integer('order')->default(0);          // se l'entità è ordinabile/drag&drop
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
```

## 2. Model (`app/Models/{Nome}.php`)

```php
class {Nome} extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'completed',
        'order',
        'user_id',
    ];
}
```

Aggiungi la relazione inversa su `User` se serve iterare (`$user->tasks()`), come già fatto per Task.

## 3. FormRequest

`Store{Nome}Request` — valida i campi obbligatori in creazione (es. `title` required).
`Update{Nome}Request` — valida i campi in update (spesso gli stessi, ma `sometimes`/opzionali dove sensato).

## 4. Resource (`app/Http/Resources/V1/{Nome}Resource.php`)

Trasforma il model in array per la risposta JSON — replica `TaskResource` come base.

## 5. Controller (`app/Http/Controllers/V1/{Nome}Controller.php`)

Replica `TaskController`:
- `index`: filtra per `user_id` corrente, ordina per `order` se applicabile.
- `store`: calcola `order` come max+1 se l'entità è ordinabile, associa `user_id` dalla request.
- `show`/`update`/`destroy`: usa il check di ownership (`authorizeTask`-style, rinominato per l'entità).
- Se serve drag&drop, aggiungi un metodo `reorder` identico nello spirito a `TaskController::reorder`.

## 6. Route

In `routes/api.php`, dentro il gruppo `v1`:

```php
Route::patch('/{tabella}/reorder', [{Nome}Controller::class, 'reorder']); // PRIMA di apiResource se serve reorder
Route::apiResource('{tabella}', {Nome}Controller::class);
```

## Checklist finale prima di considerare la feature completa

- [ ] Migration creata e testata con `php artisan migrate`
- [ ] Model con `$fillable` corretto
- [ ] FormRequest Store + Update
- [ ] Resource
- [ ] Controller con ownership check
- [ ] Route registrate (ordine corretto se c'è un'azione custom)
- [ ] Segnalato nell'handoff se il frontend deve essere aggiornato di conseguenza
