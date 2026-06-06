# Akatabo

Your business, one dashboard — inventory, quotations, invoices, payments, and customers — managed in a clean, professional interface.

## Tech Stack

- **Backend:** PHP ^8.3, Laravel ^13.7, Livewire ^4.1, Laravel Fortify ^1.37
- **Frontend:** Tailwind CSS ^4, Livewire Flux ^2.13, Alpine.js, Vite ^8
- **Database:** MySQL (configurable via `.env`)
- **PDF:** barryvdh/laravel-dompdf ^3.1
- **Auth:** Email/password, 2FA (TOTP), Passkeys (WebAuthn), email verification

## Features

- **Business Onboarding** — 4-step wizard to set up your business profile
- **Customer Management** — CRUD with search, sort, pagination, PDF export
- **Inventory** — Fabrics (roll/meter tracking), products & services, office rentals; image uploads
- **Quotations** — Create/edit with live preview, discount/tax, QR code, PDF download, convert to invoice
- **Invoices** — Full lifecycle with payment recording, receipts, balance tracking, PDF download
- **Payments** — Receipt listing with CSV export
- **Reports** — Revenue, outstanding, payment breakdown, PDF export
- **User Management** — Admin/employee roles, multi-user per business
- **Security** — 2FA, passkeys, password confirmation, rate limiting
- **Appearance** — Light/dark/system theme toggle

## Requirements

- PHP ^8.3
- Composer
- Node.js & NPM
- MySQL (or other supported database)

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd akatabo-web-app

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

## Development

```bash
# Watch frontend assets
npm run dev

# Run queue worker (for jobs)
php artisan queue:work

# Run tests
php artisan test
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
