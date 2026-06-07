# VendorMap

Let vendors book tables at public markets and conventions online, while hosts design the floor plan (boundary, doors, power, tables and prices) and manage events and vendor approvals from an admin panel.

## Background

This project was written to support the Parents Advisory Committee at my son's school. Every year they host a Christmas Market in the gym, and my wife was managing all the bookings manually, which was turning out to be too difficult. We discussed creating a website where vendors at the market can book. Searching on GitHub, I couldn't find any other projects that offered this, most "table booking" apps are for restaurant reservations, not markets, conventions and so on. So this project was started to meet that need.

## Live demo

**https://vendormap.jameshansen.org**

The demo is a sandbox: every visitor gets their own isolated copy of the data, so feel free to change anything: it resets and never affects anyone else (see [Demo mode](#demo-mode)).

| Role | How to sign in |
| --- | --- |
| **Vendor** | `vendor@demo.test` / `demo1234` (already approved: can book immediately) |
| **Admin** | Go to `/admin`. Credentials are set in `config.php` (default `admin` / `change-me`). |

## Features

- **Floor-plan designer** (admin): draw the venue boundary, drop doors, power outlets and tables, set table size/price/shape/status, mark **"has power"**, save reusable presets, and duplicate venues. Built on Konva.
- **Per-venue layouts**: each event+venue keeps its own independent table layout.
- **Vendor booking**: vendors view the live floor plan and click an available table to book. Shows price, size and a power indicator. Respects a configurable **tables-per-vendor** limit and an **auto-approve vs. hold-for-admin** booking rule.
- **Vendor accounts**: email/password or **Google** sign-in (Laravel Socialite). Sign-up collects business details + socials and is hardened against bots (honeypot, timing, optional reCAPTCHA). New accounts are **pending** until an admin approves them.
- **Admin panel** (`/admin`): dashboard, full event CRUD, the designer, and a vendor approval queue. Approving/rejecting emails the vendor.
- **Email notifications**: admin alert on new sign-ups, approval/rejection notices to vendors (best-effort; logged if SMTP isn't configured).
- **Single `config.php`**: site name, admin login, SMTP, Google keys, reCAPTCHA, booking rules and demo mode, all in one commented file.

## Tech stack

PHP 8.3 · Laravel 13 · Blade · Vite · Konva · MySQL 8+ / MariaDB 10.5+.

> MySQL/MariaDB is required: the floor-plan `geometry` columns use spatial types, so SQLite won't work.

## Setup

```bash
composer install
cp .env.example .env
# Create a MySQL database, then set DB_HOST, DB_PORT, DB_DATABASE, etc. in .env
php artisan key:generate
php artisan migrate
npm install
npm run build

# Site settings (admin login, SMTP, Google, booking rules, demo mode)
cp config.php.example config.php   # then edit config.php

# Optional sample data
php artisan db:seed --class=DemoSeeder
```

While developing, run `npm run dev` instead of `npm run build`. Serve the app with `php artisan serve` (or Laravel Herd, which serves it automatically).

## Configuration (`config.php`)

`config.php` (git-ignored, copied from `config.php.example`) holds all operational settings and is bridged into Laravel at boot. You don't need to touch `.env` for these:

- **`app_name`** - shown in the public header and as "`<name> Admin`".
- **`admin`** - admin panel username/password.
- **`smtp`** - outgoing mail. Leave `host` blank to log emails instead of sending.
- **`google_oauth`** - Google sign-in keys. Blank `client_id` hides the Google button.
- **`recaptcha`** - optional anti-bot keys for sign-up.
- **`booking`** - `tables_per_vendor` and `auto_approve_booking`.
- **`demo`** - see below.

## Demo mode

For a public sandbox where visitors can change anything without risking a shared database. When `demo.enabled` is `true`, the app hands each visitor one of a fixed **pool of pre-seeded MySQL databases** (a "slot"), tracked by a cookie. When all slots are busy, the least-recently-used one is recycled (reset to the demo baseline) for the new visitor. Nothing is created or dropped at runtime, and the main database is never touched.

```bash
# One-time: create + seed the pool (needs CREATE privilege for this command only)
php artisan demo:setup
```

Then set in `config.php`:

```php
'demo' => [
    'enabled'   => true,
    'pool_size' => 25,                // max concurrent isolated visitors
    'db_prefix' => 'vendormap_demo_', // slot DBs: vendormap_demo_1 .. _25
],
```

In demo mode, sessions/cache/queue are kept on the filesystem so they don't depend on the swapped-per-visitor database.

## Deployment

VendorMap is a standard Laravel app: follow the official guide for Nginx, PHP and optimisation: **https://laravel.com/docs/13.x/deployment**

Project-specific notes:

- Use **MySQL 8+ / MariaDB 10.5+** (spatial columns; SQLite won't work).
- Build assets with `npm run build` (Node only needed at build time).
- First deploy: set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, then `php artisan key:generate` and `php artisan migrate --force`.
- Copy and edit `config.php`. For the public demo, run `php artisan demo:setup` and set `demo.enabled = true`.
