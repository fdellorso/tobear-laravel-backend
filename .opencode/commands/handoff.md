---
description: Scrivi un file di handoff per la prossima sessione di lavoro sul backend
agent: build
---

Scrivi un file di handoff in `handoffs/YYYY-MM-DD-breve-titolo.md` (usa la data di oggi) che documenti lo stato della sessione corrente, in modo che la prossima sessione OpenCode possa riprendere senza dover rileggere tutta la chat.

Struttura del file:

```markdown
# Handoff — {data} — {titolo breve}

## Cosa è stato fatto
- elenco puntato concreto delle modifiche fatte in questa sessione (file toccati, feature implementate, bug risolti)

## Stato attuale
- cosa funziona, cosa è in WIP, cosa è rotto/non testato

## Decisioni prese
- eventuali scelte architetturali o di pattern fatte in questa sessione e il perché

## Prossimi passi
- cosa fare nella prossima sessione, in ordine di priorità

## Note per il frontend
- se questa sessione ha cambiato qualcosa nell'API (nuovi campi, nuovi endpoint, cambi di formato risposta), elencalo qui esplicitamente: il frontend è in un repo separato e un'altra sessione OpenCode dovrà saperlo

## File rilevanti
- percorsi dei file principali toccati, per riferimento rapido
```

Prima di scrivere il file:
1. Esegui `git status` e `git diff --stat` per avere il quadro reale delle modifiche.
2. Controlla se esistono già handoff precedenti in `handoffs/` per non duplicare contesto già scritto — riferisciti a quelli invece di ripeterli.

Sii specifico e concreto, evita frasi vaghe come "migliorato il codice". Scrivi come se stessi spiegando a un collega che riprende il lavoro domani senza aver letto la chat di oggi.
