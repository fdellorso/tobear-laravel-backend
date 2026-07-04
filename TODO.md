# TODO — toBear Backend (Laravel)

## Completato

- [x] Task CRUD + reorder con ownership check (trait OwnsModel)
- [x] Auth Sanctum cookie-based (login, register, logout, verify email, reset password)
- [x] Endpoint PUT /password per cambio password da utente autenticato
- [x] Endpoint GET /v1/stats per statistiche utente
- [x] Endpoint POST /v1/contact pubblico con rate limiting e notifica email
- [x] Endpoint POST /test/cleanup per isolamento test E2E
- [x] Migration user_id NOT NULL su tasks
- [x] Storage::fake() nei test ImageManipulation
- [x] Messaggi di errore tradotti in inglese
- [x] 66 test PHPUnit (Task, Album, Image, ImageManipulation, Auth, Stats, Contact, PasswordUpdate)

## Da fare — pre-deploy

- [ ] Configurare .env.production con valori reali (DB, mail, CORS, frontend URL)
- [ ] Verificare CORS config per dominio pubblico Namecheap
- [ ] Configurare mail reale (SMTP) per notifiche contatto e verifica email
- [ ] Eseguire php artisan migrate --force in produzione

## Liste annidate [feature principale pre-sync]

- [ ] Migration: aggiungere parent_id (nullable, FK self-referential) e depth (int, default 0) alla tabella tasks
- [ ] Limite profondità: max 3 livelli (0=lista root, 1=sottolista, 2=task foglia)
- [ ] TaskController: aggiornare index/store/update/destroy per gestire parent_id
- [ ] Cascata completamento silenziosa (completare lista → completa tutti i figli)
- [ ] Dialogo conferma solo per eliminazione lista con figli (zero attriti)
- [ ] Test per tutte le nuove operazioni

## Sync multi-dispositivo [tier pagante Classic]

- [ ] Modello tier/subscription sull'utente (free / classic / reels)
- [ ] Integrazione Stripe (checkout, webhook, rinnovi)
- [ ] Endpoint batch-import per migrazione guest→account ottimizzata
- [ ] Infrastruttura Hetzner Cloud (VPS Laravel + DB)

## toBear Reels [tier pagante premium]

- [ ] Modello dati foto-task (estensione Task o entità separata — da decidere)
- [ ] Storage Cloudflare R2 per immagini
- [ ] Le pagine Image/Album/Resize esistenti sono il punto di partenza tecnico

## Decisioni architetturali in sospeso

- [ ] Policy vs trait OwnsModel: rivalutare solo se arrivano ruoli/permessi complessi
- [ ] Endpoint POST /v1/tasks/batch-import: non urgente, N POST singole sufficienti ora
- [ ] Localizzazione backend (lang/en, lang/it): da valutare quando si implementa i18n completo

## Note tecniche

- Autenticazione: Sanctum SPA cookie-based, MAI Bearer token in localStorage
- Ownership: trait OwnsModel::authorizeOwnership($request, $model) — non Policy
- Risposte sempre via Resource, mai array raw
- Route versionate sotto v1
- Test: sempre RefreshDatabase + Storage::fake() dove si scrive su disco
- ExampleTest skipped intenzionalmente (richiede build SPA frontend)
