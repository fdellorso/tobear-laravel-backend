# Handoff — 2026-06-20 — Uniform naming route API

## Cosa è stato fatto

Uniformati i path delle route API REST a plurali inglesi coerenti in `routes/api.php`:

1. **`apiResource('album', ...)` → `apiResource('albums', ...)`** — tutte e 5 le route Album (index, store, show, update, destroy) ora sotto `/api/v1/albums`.

2. **`apiResource('/myimages', ...)` → `apiResource('images', ...)`** — le 3 route Image (index, store, destroy) ora sotto `/api/v1/images`.

3. **Rimossa route ridondante `POST /myimages/{image}/delete`** — `apiResource` già espone `DELETE /images/{image}` per `destroy()`. Stesso controller, stesso metodo. Eliminata per evitare duplicazione.

**Nessun** altro file modificato (`git diff --stat`: 1 file, 2 insertions, 4 deletions). Controller, model, test, route name — nessuno di questi referenziava i vecchi path.

## Stato attuale

- **9 test passano, 1 skipped** (ExampleTest — SPA build assente, già noto), 0 failure.
- Route list visivamente corretta: `/api/v1/albums`, `/api/v1/images`, `/api/v1/tasks`, `/api/v1/image/...` (ImageManipulation).
- Task/Album/Image/ImageManipulation: nessun test (copertura zero, già noto da handoff precedenti).
- Migration `add_user_id_to_images_table` (sessione precedente) **ancora non eseguita**.

## Decisioni prese

- **`/myimages` → `/images`**: scelto `images` (plurale inglese standard) invece di mantenere `myimages` (nome scelto arbitrariamente in passato, non rifletteva né la risorsa né il contesto utente).
- **`POST /myimages/{image}/delete` rimossa**: era un clone esatto di `DELETE /images/{image}` ereditato da un vecchio refactor. `apiResource` gestisce già `destroy`. Il frontend deve chiamare `DELETE /images/{image}` invece di `POST /images/{image}/delete`.
- **`image/by-album/{album}` lasciata invariata**: è una route custom di `ImageManipulationController` (non `AlbumController`/`ImageController`), e `by-album` è un nome azione descrittivo, non la risorsa Album.

## Prossimi passi

1. **Aggiornare il frontend** — tutte le chiamate axios da vecchi path a nuovi path (vedi mapping sotto). È il passo più critico: senza, il frontend si rompe.
2. **Eseguire `php artisan migrate`** per la migration `add_user_id_to_images_table` (in sospeso da sessione precedente).
3. **Scrivere test** per le 4 risorse API (Task, Album, Image, ImageManipulation) — copertura zero, priorità alta.
4. **Uniformare risposte HTTP delete** (Task: 200 JSON, Album: 204, Image: 204).

## Note per il frontend

**⚠️ Breaking change API.** I path degli endpoint Album e Image sono cambiati. Mapping esatto:

| Metodo | Vecchio path | Nuovo path |
|--------|-------------|------------|
| GET | `/api/v1/album` | `/api/v1/albums` |
| POST | `/api/v1/album` | `/api/v1/albums` |
| GET | `/api/v1/album/{album}` | `/api/v1/albums/{album}` |
| PUT/PATCH | `/api/v1/album/{album}` | `/api/v1/albums/{album}` |
| DELETE | `/api/v1/album/{album}` | `/api/v1/albums/{album}` |
| GET | `/api/v1/myimages` | `/api/v1/images` |
| POST | `/api/v1/myimages` | `/api/v1/images` |
| DELETE | `/api/v1/myimages/{image}` | `/api/v1/images/{image}` |
| ~~POST~~ | ~~`/api/v1/myimages/{image}/delete`~~ | **RIMOSSO** — usare `DELETE /api/v1/images/{image}` |

Le route name (es. `route('albums.index')`) sono cambiate di conseguenza (`album.*` → `albums.*`, `myimages.*` → `images.*`). Se il frontend usa `route()` lato backend (Blade) non ce n'è — ma se in futuro venissero usate, i nuovi nomi sono `albums.*` e `images.*`.

## File rilevanti

```
routes/api.php                          # Unico file modificato
```
