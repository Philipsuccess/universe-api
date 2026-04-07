# Render Deployment

## Frontend URL

Your frontend is already live at:

- https://universe-app-beta.vercel.app

## Backend on Render Free

Render needs a Git repository or Docker image source. This backend is now prepared with:

- `Dockerfile`
- `.dockerignore`
- `render.yaml`

## What to do in Render

1. Push `C:\laragon\www\universe-api` to GitHub.
2. In Render, choose `New +` -> `Blueprint`.
3. Select the repository that contains this Laravel backend.
4. Render will detect `render.yaml` and create:
   - a web service named `universe-api`
   - a free Postgres database named `universe-db`
5. In the web service environment variables, set:
   - `APP_URL` to your Render service URL
   - `APP_KEY` to a generated Laravel app key
   - `FRONTEND_URL` to `https://universe-app-beta.vercel.app`

## After first deploy

Run these once in the Render shell for the web service:

```bash
php artisan migrate --force
php artisan db:seed --class=UniverseAdminSeeder --force
```

## Connect frontend to backend

After Render gives you a backend URL like:

- `https://universe-api.onrender.com`

set this in Vercel for the frontend:

- `VITE_API_BASE_URL=https://universe-api.onrender.com/api`

Then redeploy the Vercel frontend.
