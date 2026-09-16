# hello-world

An online shop built with **Laravel 13**, **Inertia.js**, **Vue 3**, and **MySQL**.

It has a public storefront (categories, subcategories, products), customer
registration/login, and an admin panel for managing catalog data — all
server-rendered through Inertia, so there's no separate API layer. It also
still ships the original Tasks demo at `/tasks`.

## Stack

- **Backend:** Laravel 13 (PHP 8.2+)
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

Run migrations, seed some demo data, and link storage (for product images):

```bash
php artisan migrate --seed
php artisan storage:link
```

The seeder creates:

- An admin: `admin@example.com` / `password`
- A customer: `customer@example.com` / `password`
- A few category trees (Electronics, Clothing, Home & Garden) with
  subcategories, products, and attributes.

## Running the app

In one terminal, start Vite for hot-reloading assets:

```bash
npm run dev
```

In another, start the Laravel dev server:

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000` for the storefront, or log in as the seeded
admin and visit `/admin` for the admin panel.

## Data model

- **Category** — has a nullable `parent_id` pointing at another category, so
  a category can be a top-level category or a subcategory of another. A
  category can have many products, and a product can belong to many
  categories (`category_product` pivot table).
- **Product** — has the usual commerce fields: `name`, `slug`, `sku`,
  `description`, `short_description`, `price`, `sale_price`, `quantity`,
  `weight`, `image`, `gallery` (JSON), `is_active`, `is_featured`, and SEO
  `meta_title`/`meta_description`.
- **ProductAttribute** — a `name`/`value` pair (e.g. `Color: Red`). A product
  can have many attributes, and an attribute can belong to many products
  (`attribute_product` pivot table).
- **User** — a single `users` table for both shop customers and admins,
  distinguished by a `role` column (`customer` or `admin`). Only admins can
  reach `/admin` (enforced by the `admin` middleware, `App\Http\Middleware\EnsureUserIsAdmin`).
  A user has one `Profile` (contact/address details) and many `SalesOrder`s.
- **Profile** — one-to-one with `User` (`user_id` is unique), holding
  `phone`, `date_of_birth`, `avatar`, `bio`, and address fields.
- **SalesOrder** — belongs to a `User`; has many `SalesOrderItem`s. Tracks
  `order_number`, `status`, `subtotal`/`tax`/`shipping_cost`/`total`,
  `shipping_address`, and `notes`.
- **SalesOrderItem** — belongs to a `SalesOrder` and to a `Product`. Stores
  `quantity`, `unit_price`, and `total`, plus a `product_name`/`product_sku`
  snapshot so order history stays intact even if the product is later
  edited or deleted (`product_id` is nullable and set null on delete).

## How it fits together

- `routes/web.php` — public storefront routes, `guest`-only auth routes,
  and the `admin`-only route group.
- `app/Http/Controllers/Shop/*` — storefront controllers (home, category,
  product pages).
- `app/Http/Controllers/Auth/*` — registration and session (login/logout)
  controllers.
- `app/Http/Controllers/Admin/*` — admin CRUD controllers for categories,
  products, attributes, and user role management.
- `app/Models/{Category,Product,ProductAttribute,User}.php` — Eloquent
  models and their relationships.
- `resources/js/Pages/Shop/*` — storefront Vue pages.
- `resources/js/Pages/Auth/*` — login/register Vue pages.
- `resources/js/Pages/Admin/*` — admin panel Vue pages.
- `resources/js/Layouts/{ShopLayout,AdminLayout}.vue` — the two page shells.
- `app/Http/Controllers/TaskController.php`, `resources/js/Pages/Tasks/Index.vue`
  — the original Tasks demo, still available at `/tasks`.

## Tests

```bash
php artisan test
```

Feature tests cover model relationships, auth (register/login/logout),
admin authorization, and admin CRUD for categories, products, attributes,
and users.

## Production build

```bash
npm run build
php artisan migrate --force
```
