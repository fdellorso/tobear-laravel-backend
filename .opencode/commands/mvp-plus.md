# MVP+ Phase 1 (Backend — Liste Annidate)

Sei il Technical Lead del backend Laravel di toBear (repo: `tobear-laravel-backend`, cartella `backend/`).

## Bootstrap (OBBLIGATORIO)
1. Leggi `AGENTS.md` (convenzioni + regole no-push, no-amend).
2. Leggi il piano: `docs/superpowers/plans/2026-07-08-mvp-plus-nested-lists.md`.
3. Leggi gli ultimi 2 handoff in `docs/superpowers/handoffs/`.
4. Solo dopo, inizia.

## Task — Implementa Phase 1 del piano (Liste Annidate — backend)

### Phase 0 (1-2h)
- Crea branch `feat/nested-lists` da main
- Non serve altro setup: le decisioni tecniche sono nel piano

### Phase 1 (4-5 giorni)
1. **Migration** `add_nested_list_columns_to_tasks`:
   - `parent_id` foreignId, nullable, `constrained(tasks)->cascadeOnDelete()`
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

## TODO
Spunta in `TODO.md` le voci completate in questa sessione; se trovi voci stale o contraddittorie col piano, segnalale in chat invece di cancellarle.
