# hello-world

A starter application built with **Laravel**, **Inertia.js**, **Vue 3**, and **MySQL**.

It ships with a small working example — a Tasks list — so you can see the whole
stack wired together: Laravel routes and controllers render Vue pages via
Inertia (no separate API/SPA build needed), and data is persisted to MySQL
through Eloquent.

## Stack

- **Backend:** Laravel 12 (PHP 8.4)
- **Frontend:** Vue 3 + Inertia.js (server-driven SPA, no REST/GraphQL layer)
- **Database:** MySQL
- **Build tool:** Vite (with Tailwind CSS v4)
- **Routing helper:** Ziggy (use Laravel named routes from Vue via `route()`)

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8+ (or MariaDB)

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

Create the database (adjust for your MySQL client/credentials):

```bash
mysql -u root -e "CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run migrations:

```bash
php artisan migrate
```

## Running the app

In one terminal, start Vite for hot-reloading assets:

```bash
npm run dev
```

In another, start the Laravel dev server:

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000` — it redirects to `/tasks`, where you can add,
complete, and delete tasks.

## How it fits together

- `routes/web.php` — defines the `tasks` resource routes.
- `app/Http/Controllers/TaskController.php` — validates input, talks to the
  `Task` Eloquent model, and returns Inertia responses.
- `app/Models/Task.php` — the Eloquent model backed by the `tasks` MySQL table.
- `database/migrations/..._create_tasks_table.php` — the `tasks` table schema.
- `resources/js/Pages/Tasks/Index.vue` — the Vue 3 page component Inertia renders.
- `resources/js/app.js` — the Inertia + Vue app entry point.
- `resources/views/app.blade.php` — the single Blade root template Inertia hydrates into.

## Production build

```bash
npm run build
php artisan migrate --force
```
