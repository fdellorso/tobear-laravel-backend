---
name: sanctum-auth
description: Specifiche dell'autenticazione Sanctum SPA cookie-based usata in toBear. Usa questa skill quando lavori su login, registrazione, verifica email, CSRF, CORS, o qualunque cosa relativa ad autenticazione/sessione tra frontend e backend.
license: MIT
metadata:
  project: tobear-laravel-backend
---

# Sanctum auth — toBear backend

## Modalità: SPA cookie-based, NON token Bearer

Il frontend Vue NON usa header `Authorization: Bearer`. Usa:
- `withCredentials: true`
- `withXSRFToken: true`
- un round-trip preliminare a `GET /sanctum/csrf-cookie` prima di operazioni che richiedono CSRF (login, register, ecc.)

Questo significa che lato backend:
- Il domain del frontend deve essere in `SANCTUM_STATEFUL_DOMAINS`.
- `config/cors.php` deve avere `supports_credentials: true` e l'origine corretta (no wildcard `*` con credentials).
- Le route protette usano `auth:sanctum` come guard di sessione, non come token API.

## Route coinvolte

- `routes/auth.php` — login, register, email verification, password reset (scaffolding Breeze).
- `routes/api.php` — `GET /user` e tutte le route v1, dietro `auth:sanctum + verified`.

## Middleware `verified`

Tutte le route v1 richiedono anche `verified`, cioè l'utente deve aver verificato l'email. Se serve un endpoint accessibile anche a utenti non verificati, va messo FUORI dal gruppo `verified`, non rimuovere il middleware dal gruppo esistente.

## Quando tocchi questa area

- Non cambiare la modalità di auth (cookie → token) senza che sia una richiesta esplicita e consapevole: rompe il frontend esistente che è già scritto per cookie-auth.
- Se devi aggiungere un endpoint mobile-friendly (vedi `TODO.md` del frontend: "Modify Backend Authentication to serve Mobile App for offline authentication"), questo è un cambiamento architetturale importante — proponi un piano prima di implementare, non procedere silenziosamente.
- Verifica sempre `config/sanctum.php` e `config/cors.php` insieme quando debuggi problemi di auth cross-origin; il problema è quasi sempre lì, non nel controller.
