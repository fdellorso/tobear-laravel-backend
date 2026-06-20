---
description: Prepara un commit pulito con messaggio convenzionale per il backend
agent: build
---

Stato attuale del repository:

!`git status`

Diff completo:

!`git diff`

Diff dei file già in staging (se presenti):

!`git diff --cached`

Sulla base di queste modifiche:

1. Se ci sono modifiche non in staging che sembrano appartenere logicamente a questo commit, propon di farne `git add`, ma chiedi conferma prima di eseguirlo se ci sono file ambigui (es. file di config locali, `.env*`).
2. Esegui `./vendor/bin/pint` sui file modificati per garantire lo style corretto, prima di committare.
3. Scrivi un messaggio di commit in inglese, formato Conventional Commits (`feat:`, `fix:`, `refactor:`, `chore:`, `test:`, `docs:`), con un subject conciso (max ~72 caratteri) e, se utile, un corpo che spiega il "perché" oltre al "cosa".
4. Esegui il commit con `git commit`.
5. NON eseguire `git push` a meno che non venga chiesto esplicitamente.

Se le modifiche toccano l'API in modo che richiede un aggiornamento del frontend, menzionalo nel corpo del commit message.
