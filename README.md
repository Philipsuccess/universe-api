# Universe API

Laravel backend for the Universe student platform.

## Current frontend

- Live Vercel app: `https://universe-app-beta.vercel.app`

## Stack

- Laravel 10
- Sanctum token auth
- MySQL locally on Laragon
- Render Free target for web deployment
- Postgres-ready config for Render

## Features already wired

- student signup and login
- admin-only dashboard
- verified user flow
- feed post submission and approval
- likes and comments on approved posts
- follow and friend actions
- direct messaging
- notifications
- referrals
- study hub and tutor endpoints

## Local development

1. Copy `.env.example` to `.env` if needed.
2. Install dependencies:

```bash
composer install
```

3. Run migrations:

```bash
php artisan migrate
```

4. Seed the admin account:

```bash
php artisan db:seed --class=UniverseAdminSeeder
```

5. Start the API:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## Admin login

- Email: `Archii101@universe.app`
- Password: `Universe#Archii4life`

## Render deployment

This repo is already prepared for Render with:

- `Dockerfile`
- `.dockerignore`
- `render.yaml`
- `RENDER_DEPLOY.md`

Main deployment flow:

1. Push this folder to GitHub as its own repository.
2. In Render, create a new `Blueprint` from that repository.
3. Set `APP_URL`, `APP_KEY`, and `FRONTEND_URL`.
4. After first deploy, run:

```bash
php artisan migrate --force
php artisan db:seed --class=UniverseAdminSeeder --force
```

## Important environment values

- `FRONTEND_URL=https://universe-app-beta.vercel.app`
- frontend Vercel env after backend deploy:

```env
VITE_API_BASE_URL=https://your-render-service.onrender.com/api
```
