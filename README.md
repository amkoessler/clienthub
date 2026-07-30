# ClientHub

Full-stack client management (CRM simples) — Laravel API + React.

## Stack

- Backend: Laravel 11, Sanctum, SQLite
- Frontend: React + Vite + Tailwind + Axios (em desenvolvimento)
- Auth: Bearer tokens (Sanctum)

## Status

- [x] Day 1 – Auth API (register, login, logout, me) + Feature tests
- [ ] Day 2 – Clients CRUD API + Feature tests
- [ ] Day 3 – React setup + Auth frontend
- [ ] Day 4 – Client listing
- [ ] Day 5 – Create & Edit clients
- [ ] Day 6 – Detail, delete, filters & search
- [ ] Day 7 – Polish + final tests
- [ ] Day 8 – Documentation

## Setup (backend)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
