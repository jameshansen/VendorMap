# VendorMap
## Overview
Allow vendors at a public market event or convention to book a table online, while allowing hosts to manage the events and design spaces for events, including the layout and table prices and properties

## Framework
This is a PHP based app using the Laravel framework, using Blade and Vite for simplicity, along with the Konva library for anything map related.

##Setup
Soon there will be an automated setup script. For now, manual setup is required.

cd into the VendorMap folder and run the following:
```bash
composer install
cp .env.example .env

Create a database and modify the .env DB_HOST, DB_PORT etc. to be your MySQL database settings. 

```bash
php artisan migrate
npm install
npm run build
```

Sample data (optional):
```bash
php artisan db:seed --class=DemoSeeder
```

While developing, use npm run dev instead of npm run build. I used Laravel Herd on my development machine, which serves the site automatically. Without Herd, run php artisan serve to run locally for development.

## Deployment

VendorMap is a standard Laravel app, so follow the official guide for the web
server (Nginx) setup, PHP requirements, and optimisation commands:

**https://laravel.com/docs/13.x/deployment**

Project-specific notes on top of that guide:

- Use **MySQL 8+ or MariaDB 10.5+**. The floor-plan `geometry` columns rely on
  spatial functions, so SQLite will not work.
- Build the front-end assets with `npm run build`. Node is only needed at build
  time, not at runtime.
- On the first deploy, set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL`
  in `.env`, then run `php artisan key:generate` and `php artisan migrate --force`.
- Optional sample data: `php artisan db:seed --class=DemoSeeder --force`.
