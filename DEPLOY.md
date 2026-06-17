# Deploy Guide (Docker + nginx)

## Quick start

```bash
# 1. Copy and edit env
cp .env.example .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_KEY, APP_URL

# 2. Generate app key (run once)
docker compose run --rm app php artisan key:generate

# 3. Build and start
docker compose up -d --build

# 4. Run migrations + seed
docker compose exec app php artisan migrate --seed

# 5. Import Excel
#    Put supervisors.xlsx in storage/app/ first
docker compose exec app php artisan import:supervisors
```

App will be available at `http://localhost:8080` (or whatever `APP_PORT` is set to).

---

## Environment variables

Create a `.env` from `.env.example`. The compose file injects `DB_HOST=mysql` automatically,
so you only need to set credentials and the app key:

```dotenv
APP_KEY=          # php artisan key:generate fills this
APP_URL=http://your-server-ip-or-domain
APP_ENV=production
APP_DEBUG=false
APP_PORT=8080     # host port nginx listens on

DB_DATABASE=spv_finder
DB_USERNAME=spv
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootsecret   # used only by the mysql container
```

---

## Nginx on the host (optional)

If you already have a host nginx routing to this container, point it at the container's port:

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Then set `TRUSTED_PROXIES=*` (or the specific IP) in `.env` so Laravel trusts forwarded headers.

---

## Updating Excel data

1. Replace `storage/app/supervisors.xlsx` with the new version.
2. Re-run the import (idempotent — upserts by `Kddsn`):

```bash
docker compose exec app php artisan import:supervisors
```

## Admin sheet format

Add a sheet named `Admin` to the workbook with these columns (row 1 = header):

| Kddsn | ActiveTitles | ScholarURL | SpecificTopics | Title1 | Title2 | Title3 | Title4 | Title5 |
|-------|-------------|-----------|----------------|--------|--------|--------|--------|--------|
| D1633 | 3 | https://scholar.google.com/... | Machine Learning; NLP; Computer Vision | Judul skripsi 1 | Judul 2 | ... | | |

- **Kddsn** must match the code in the source sheet (e.g. `D1633`)
- **ActiveTitles**: current number of active bimbingan
- **ScholarURL**: full Google Scholar profile URL
- **SpecificTopics**: up to 10 topics separated by `;` (e.g. `Machine Learning; NLP; Computer Vision`)
- **Title1–Title5**: last 5 thesis titles supervised (leave blank if fewer than 5)

---

## Useful commands

```bash
docker compose logs -f app        # PHP-FPM logs
docker compose logs -f nginx      # nginx access/error logs
docker compose exec app php artisan migrate:status
docker compose exec app php artisan tinker
docker compose down               # stop (data preserved in mysql_data volume)
docker compose down -v            # stop + wipe DB volume
```

## Local development (no Docker)

```bash
composer install
cp .env.example .env   # set DB_ to point at local MySQL
php artisan key:generate
php artisan migrate --seed
php artisan import:supervisors
php artisan serve
```
