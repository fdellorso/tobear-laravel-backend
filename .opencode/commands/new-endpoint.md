---
description: Crea i 4 file standard (model, request, resource, controller) per una nuova risorsa API seguendo il pattern Task
agent: build
---

Voglio creare una nuova risorsa API chiamata: $ARGUMENTS

Usa la skill `task-resource-pattern` come riferimento esatto.

Prima di scrivere codice:
1. Chiedimi (se non è già chiaro dal contesto): nome della tabella/risorsa, se è user-scoped, se serve essere ordinabile (drag&drop / campo `order`), quali campi servono oltre a title/description.
2. Mostrami un piano sintetico (migration, model, request, resource, controller, route) PRIMA di scrivere file, a meno che non ti abbia detto esplicitamente di procedere senza piano.

Poi implementa nell'ordine: migration → model → FormRequest (Store + Update) → Resource → Controller → route in `routes/api.php`.

Alla fine, dimmi esplicitamente se questa nuova risorsa richiede modifiche lato frontend (nuova pagina, nuovo store Pinia, nuova chiamata axios) così posso passarlo alla sessione OpenCode del frontend.
