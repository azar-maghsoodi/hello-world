# hello-world

An online shop built with **Laravel 13**, **Inertia.js**, **Vue 3**, and **MySQL**.

It has a public storefront (categories, subcategories, products, cart,
checkout), customer registration/login, and an admin panel for managing
catalog data — all server-rendered through Inertia, so there's no separate
API layer. The storefront is multi-lingual (English, French, German,
Spanish, Italian, managed from a `languages` table) and priced in a single
configurable currency (EUR by default), aimed at selling to European
customers. It also still ships the original Tasks demo at `/tasks`.

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
- Store settings defaulting to EUR (`€`), 0% tax, and free shipping.
- Five active languages: English (default), French, German, Spanish, Italian.

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

## Shopping cart & checkout

- A product page's "Add to cart" form adds items to a session-based cart
  (`App\Services\Cart`) — works for guests too, capped to available stock.
- `/cart` — review quantities, remove items, see the subtotal.
- `/checkout` (requires login; a guest is sent to `/login` and returned to
  checkout after signing in) — enter a shipping address (pre-filled from
  the profile, if set) and place the order. This re-checks stock, then in
  one DB transaction creates the `SalesOrder` and its `SalesOrderItem`s
  (snapshotting product name/sku/price), decrements each product's
  `quantity`, clears the cart, and redirects to the new order's detail page.
  Tax and shipping come from the store settings below (both default to 0).

## Currency, language, and tax/shipping settings

Since the store is aimed at European customers:

- **Currency** — prices are entered and stored in a single base currency
  (EUR by default). There's no multi-currency conversion; every price,
  cart total, and order is in that one currency, formatted with
  `Intl.NumberFormat` using the current storefront language's locale (so a
  German-language customer sees `€1.234,56` while an English-language one
  sees `€1,234.56` — same amount, locale-correct formatting). Change the
  currency code/symbol in **Admin → Settings**.
- **Languages** — manageable at **Admin → Languages**, backed by a
  `languages` table (`code`, `name`, `native_name`, `is_active`,
  `is_default`). Only active languages appear in the storefront's language
  switcher; exactly one is the default used when a visitor hasn't picked
  one. The storefront currently ships real translations for **English,
  French, German, Spanish, and Italian** (`resources/js/i18n/translations.js`).
  Adding a language with a different code makes it selectable in the
  switcher, but the UI will fall back to English until translations for
  that code are added to that file — the admin screen says as much. The
  admin panel itself is English-only; only the customer-facing storefront
  is translated.
- **Tax & shipping** — a single flat tax rate (as a %) and a flat shipping
  cost, editable at **Admin → Settings**, applied to every order. Both
  default to 0. **This is not automatic EU VAT compliance** — VAT rates
  and rules vary by country and product type (and schemes like the EU's
  One-Stop-Shop add more nuance for cross-border sales) — confirm the
  right number with a tax advisor or a VAT service before relying on it.

## Customer account pages

Logged-in customers get, from the header nav:

- `/profile` — edit their name, email, and profile details (phone, date of
  birth, bio, address).
- `/orders` — their own order history (paginated), including orders placed
  through checkout.
- `/orders/{order}` — a single order's line items and totals. Viewing
  another user's order returns a 403.

## Admin panel pages

- `/admin/orders` — every order, filterable by status.
- `/admin/orders/{order}` — an order's line items, totals, and a status
  selector (pending/processing/completed/cancelled).
- `/admin/languages` — add/edit/remove storefront languages and pick the
  default.
- `/admin/settings` — currency code/symbol, tax rate, and shipping cost.

## How it fits together

- `routes/web.php` — public storefront + cart routes, `guest`-only auth
  routes, `auth`-only account routes (profile, orders, checkout), and the
  `admin`-only route group.
- `app/Services/Cart.php` — the session-backed cart (no `cart` DB table).
- `app/Http/Middleware/SetLocale.php` — resolves the active locale from the
  session (falling back to the default `Language`) and calls
  `app()->setLocale()` before Inertia shares props for the request.
- `app/Http/Controllers/Shop/*` — storefront controllers (home, category,
  product pages) plus the customer's own profile, order, cart, checkout,
  and locale-switching controllers.
- `app/Http/Controllers/Auth/*` — registration and session (login/logout)
  controllers.
- `app/Http/Controllers/Admin/*` — admin CRUD controllers for categories,
  products, attributes, orders, languages, settings, and user role
  management.
- `app/Models/{Category,Product,ProductAttribute,User,Profile,SalesOrder,SalesOrderItem,Language,StoreSetting}.php`
  — Eloquent models and their relationships.
- `resources/js/i18n/translations.js` + `resources/js/i18n/index.js` — the
  translation dictionary and the `useTranslations()` composable (`const t
  = useTranslations()`, then `t('nav.cart')` in templates).
- `resources/js/utils/currency.js` — the `useCurrency()` composable that
  formats an amount using the store's currency and the current locale.
- `resources/js/Pages/Shop/*` — storefront + account Vue pages (including
  `Shop/Orders/*`).
- `resources/js/Pages/Auth/*` — login/register Vue pages.
- `resources/js/Pages/Admin/*` — admin panel Vue pages (including
  `Admin/Orders/*`, `Admin/Languages/*`, `Admin/Settings/*`).
- `resources/js/Layouts/{ShopLayout,AdminLayout}.vue` — the two page
  shells; `ShopLayout` has the language switcher.
- `app/Http/Controllers/TaskController.php`, `resources/js/Pages/Tasks/Index.vue`
  — the original Tasks demo, still available at `/tasks`.

## Tests

```bash
php artisan test
```

Feature tests cover model relationships, auth (register/login/logout),
admin authorization, admin CRUD for categories/products/attributes/users,
cart and checkout (including the stock re-check and cart-clearing), locale
switching, and admin language/settings management.

## Production build

```bash
npm run build
php artisan migrate --force
```
