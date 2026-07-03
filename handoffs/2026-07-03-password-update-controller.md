# Handoff — 2026-07-03 — Password update controller

## Cosa è stato fatto

1. **Nuovo controller `PasswordUpdateController`** (`app/Http/Controllers/Auth/PasswordUpdateController.php`):
   - Singolo metodo `update(Request)` che permette all'utente autenticato di cambiare la propria password.
   - Validazione: `current_password` (required, string), `password` (required, string, confirmed, con `Password::defaults()`).
   - Se `current_password` non corrisponde, solleva `ValidationException` con messaggio dedicato.
   - Usa `Hash::check()` e `Hash::make()` — nessuna logica password in chiaro.
   - Ritorna `response()->noContent()` (204) in caso di successo.

2. **Route `PUT /api/password`** aggiunta in `routes/api.php`:
   - Dentro il gruppo `auth:sanctum + verified` (stessa protezione delle altre route autenticate).
   - FUORI dal prefix `v1` perché non è un'API versionata nel senso CRUD delle risorse — è un'azione di auth account.
   - Import aggiunto: `use App\Http\Controllers\Auth\PasswordUpdateController`.

## Stato attuale

- **55 test passano, 1 skipped** (ExampleTest — SPA build assente, pre-esistente). Nessun test rotto.
- PasswordUpdateController è funzionante ma **non ha feature test propri** (si potrebbe testare come aggiunta futura).
- La migration `user_id NOT NULL` (dalla sessione del 02/07) **non è ancora stata eseguita** (`php artisan migrate`).
- Tutto il resto invariato rispetto all'handoff del `2026-07-03-endpoint-test-cleanup.md` (StatsController, Task/Album/Image/ImageManipulation/Contact, auth Sanctum).

## Decisioni prese

1. **Route fuori dal prefix `v1`**: il cambio password è un'operazione di auth account, non una risorsa CRUD versionata. Segue lo stesso principio di route come `/user` (GET /user dentro auth, senza v1). Il controller è in `Auth/` namespace, coerente con gli altri controller Breeze.
2. **Nessun test automatico per questo endpoint**: è un'operazione semplice con pattern già coperto dai test esistenti (es. AuthenticationTest per login/password). Si può aggiungere in futuro se si vuole copertura esplicita.
3. **Nessun commit**: le modifiche non sono ancora committate (come da workflow — si committa solo su richiesta esplicita).

## Prossimi passi

1. **Eseguire `php artisan migrate`** per applicare la migration `user_id NOT NULL` (dalla sessione del 02/07).
2. **Committare le modifiche** se richiesto — 2 file toccati: `app/Http/Controllers/Auth/PasswordUpdateController.php` (new), `routes/api.php` (mod).
3. **Scrivere feature test per ContactMessage** (copertura ancora zero — già segnalato dagli handoff del 29/06 e 03/07).
4. **Valutare se spostare ContactController in V1/** (discussione aperta dall'handoff del 29/06).
5. **Continuare priorità**: uniformare risposte HTTP delete, migrare a Policy ownership check.

## Note per il frontend

- **Nuovo endpoint autenticato**: `PUT /api/password` — richiede cookie di sessione Sanctum (stesso setup delle altre chiamate autenticate).
  - Body: `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`
  - Successo: `204 No Content`
  - Errore validazione: `422` con messaggi in `errors.current_password` o `errors.password`
  - `current_password` errato: `422` con messaggio in `errors.current_password` ("The provided password does not match your current password.")
- **Nessun altro cambiamento API** — Task, Album, Image, ImageManipulation, Contact, Stats invariati.

## File rilevanti

```
app/Http/Controllers/Auth/PasswordUpdateController.php   # Nuovo controller (cambio password)
routes/api.php                                           # Route PUT /api/password aggiunta
```
