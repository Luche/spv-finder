# SpvFinder

A web app for BINUS University students to find and contact thesis supervisors (pembimbing skripsi).

Students can search by supervisor name, specific topics, or previous thesis titles, filter by program and topic, and sort by various signals. Each supervisor profile shows 30-day view and contact counts, active bimbingan count, handled thesis titles, Google Scholar link, and a pre-filled email request button.

## Features

- Live search across supervisor names, specific topics, and previous thesis titles
- Filter by program study and topic
- Multi-sort: most viewed, most contacted, most active titles, relevance, A–Z
- Rolling 30-day view and contact tracking (deduplicated per student)
- Pre-filled Bahasa Indonesia email template
- Anonymous cookie-based identity with optional NIM
- First-time visitor tour on home and profile pages

## Stack

- **Backend:** Laravel (PHP 8.4), MySQL 8
- **Frontend:** Blade, Alpine.js, HTMX, Tailwind CSS
- **Infrastructure:** Docker (PHP-FPM + nginx)

## Data source

Supervisor data is imported from `supervisors.xlsx` — a workbook with 6 program sheets plus an `Admin` sheet for manual data (Scholar URLs, active titles, specific topics, last 5 thesis titles).

## Deployment

See [DEPLOY.md](DEPLOY.md) for Docker setup, environment variables, nginx proxy config, and Excel import instructions.

## Development

```bash
composer install
cp .env.example .env   # configure DB_ credentials
php artisan key:generate
php artisan migrate --seed
php artisan import:supervisors
php artisan serve
```
