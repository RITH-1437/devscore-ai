# Production Deployment

GitRadar on **Ubuntu EC2 + Nginx + PHP-FPM + PostgreSQL + Supervisor**.

Live URL: `https://gitradar.duckdns.org`

---

## Server Layout (Typical)

```
/var/www/gitradar/          Application root
/etc/nginx/sites-available/gitradar
/etc/supervisor/conf.d/gitradar-worker.conf
```

---

## Initial Deploy

```bash
cd /var/www/gitradar
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci
npm run build

cp .env.example .env   # first time only — then edit
php artisan key:generate

# PostgreSQL: create DB + user (once)
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Production `.env` Essentials

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gitradar.duckdns.org

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gitradar
DB_USERNAME=gitradar
DB_PASSWORD=<strong-password>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

QUEUE_CONNECTION=database

GITHUB_CLIENT_ID=<from GitHub OAuth app>
GITHUB_CLIENT_SECRET=<secret>
GITHUB_REDIRECT_URI=https://gitradar.duckdns.org/auth/github/callback

GEMINI_API_KEY=<optional>
OPENROUTER_API_KEY=<fallback>

LOG_LEVEL=warning
```

---

## Nginx (Reference)

```nginx
server {
    listen 80;
    server_name gitradar.duckdns.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name gitradar.duckdns.org;
    root /var/www/gitradar/public;

    index index.php;

    # TLS certificates (Let's Encrypt / certbot)
    ssl_certificate     /etc/letsencrypt/live/gitradar.duckdns.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gitradar.duckdns.org/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Serve public/robots.txt directly; fall back to Laravel SeoController if missing.
    # Avoid `location ~* \.txt$ { try_files $uri =404; }` — it breaks dynamic robots.txt.
    location = /robots.txt {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 7d;
        access_log off;
    }
}
```

**Block sensitive paths:**

```nginx
location ~ ^/(\.env|\.git|storage|vendor|composer\.(json|lock)) {
    deny all;
}
```

---

## PHP-FPM

- Use PHP **8.5** matching production requirement
- `opcache.enable=1` in production
- Do not run queue workers as root

---

## Queue Workers

See [QUEUE.md](QUEUE.md).

---

## Deploy Update (Zero-Downtime Lite)

```bash
cd /var/www/gitradar
git pull

composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo systemctl reload php8.5-fpm
sudo supervisorctl restart gitradar-worker:*
```

---

## Health Checks

| Endpoint | Auth | Purpose |
|----------|------|---------|
| `GET /health` | Public | `{ "status": "ok" }` |
| `GET /up` | Public | Laravel DB connectivity |
| `GET /health/ai` | Authenticated | AI provider status (minimal) |

Monitor externally:

```bash
curl -sf https://gitradar.duckdns.org/health
curl -sf https://gitradar.duckdns.org/up
curl -sf https://gitradar.duckdns.org/robots.txt
curl -sf https://gitradar.duckdns.org/sitemap.xml
```

**SEO files:** `public/robots.txt` is committed and served by nginx as a static file. The Laravel `SeoController@robots` route remains as a fallback when the file is absent. After changing `config/seo.php` `robots_disallow`, update `public/robots.txt` to match.

---

## Rollback

```bash
git checkout <previous-tag-or-commit>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force   # only if migration was run
php artisan config:cache
sudo supervisorctl restart gitradar-worker:*
```

---

## Related Docs

- [PRODUCTION_BACKUP.md](PRODUCTION_BACKUP.md)
- [QUEUE.md](QUEUE.md)
- [environment.md](environment.md)
- [security.md](security.md)
