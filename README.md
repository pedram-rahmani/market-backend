# Marketplace Backend

The REST API for the Marketplace project. It is built with Laravel and provides authentication, catalog management, user accounts, orders, reviews, support, notifications, and administration features for the [market-frontend repository](https://github.com/pedram-rahmani/market-frontend).

## Features

- Token-based authentication with Laravel Sanctum
- Registration, login, logout, current-user lookup, and password reset
- Product, category, product feature, specification, color, introduction, and warranty management
- Product search, filtering, pagination, and public product details
- Orders, addresses, wallet, and transaction history
- Reviews and review media with reactions, reports, and moderation
- Product questions with reactions and moderation
- Coupons and user coupon assignments
- Real-time live chat and messaging with WebSockets
- Notifications and admin messaging
- Role and permission management with `spatie/laravel-permission`
- User management, profile updates, soft-deleted user restoration, and account permissions
- Site settings and file uploads

## Technology

- PHP `8.2+`
- Laravel `12`
- Laravel Sanctum
- Spatie Laravel Permission
- SQLite by default for local development (other Laravel-supported databases can be configured)
- Pest / PHPUnit for testing

## Project structure

```text
app/Http/Controllers/   API controllers grouped by domain
app/Models/             Eloquent models
app/Services/           Shared services such as uploads and notifications
app/Policies/           Authorization policies
database/migrations/    Database schema
database/seeders/       Seed data
routes/api.php          Public and authenticated API endpoints
tests/                  Feature and unit tests
```

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm (needed by the Laravel development scripts)

## Local setup

From this directory:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

On macOS/Linux, use `cp .env.example .env` instead of `copy`.

The default local configuration uses SQLite. Ensure `database/database.sqlite` exists, or configure another database in `.env`.

## Run the API

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`. The frontend should use:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_ASSET_URL=http://127.0.0.1:8000/storage
```

For the full local development process, the project also defines a Composer `dev` script:

```bash
composer run dev
```

## Testing

```bash
php artisan test
```

## API overview

Public endpoints include products, categories, filters, warranties, settings, authentication, reviews, and questions. Authenticated endpoints include the user dashboard, orders, wallet, live chat, notifications, reviews, questions, coupons, and management resources. The complete route definitions are in [`routes/api.php`](routes/api.php).

## Deployment

- [Backend API](https://my-market-backend.liara.run/)
- [Marketplace frontend](https://my-market-frontend.liara.run/)

## Related documentation

- [Frontend repository](https://github.com/pedram-rahmani/market-frontend)
