# Production Backup & Recovery

PostgreSQL backup strategy for GitRadar on AWS EC2.

---

## What to Back Up

| Asset | Priority | Method |
|-------|----------|--------|
| PostgreSQL database | **Critical** | `pg_dump` |
| `.env` file | **Critical** | Encrypted off-site copy (NOT in git) |
| `storage/app` (if user uploads added later) | Medium | rsync / S3 |
| Application code | Low | Git repository |

---

## PostgreSQL Backup

### Manual backup

```bash
# On EC2 (adjust credentials/database name)
export PGDATABASE=gitradar
export PGUSER=gitradar
export PGHOST=127.0.0.1

mkdir -p /var/backups/gitradar
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
pg_dump -Fc -f "/var/backups/gitradar/gitradar_${TIMESTAMP}.dump"
```

`-Fc` = custom compressed format (recommended for restore).

### Automated daily backup (cron)

```bash
# /etc/cron.d/gitradar-backup
0 3 * * * postgres pg_dump -Fc -d gitradar -f /var/backups/gitradar/gitradar_$(date +\%Y\%m\%d).dump
```

### Retention

| Tier | Retention |
|------|-----------|
| Daily | 7 days |
| Weekly | 4 weeks (copy Sunday dump) |
| Monthly | 3 months |

Prune old files:

```bash
find /var/backups/gitradar -name '*.dump' -mtime +7 -delete
```

### Off-site copy (recommended)

Sync to S3 with encryption:

```bash
aws s3 cp /var/backups/gitradar/ s3://YOUR-BUCKET/gitradar-db/ --recursive --sse AES256
```

---

## Restore Procedure

**⚠️ Destructive — test on staging first.**

```bash
# Stop app traffic (maintenance mode)
cd /var/www/gitradar
php artisan down

# Drop and recreate database (ONLY if full restore needed)
sudo -u postgres psql -c "DROP DATABASE IF EXISTS gitradar;"
sudo -u postgres psql -c "CREATE DATABASE gitradar OWNER gitradar;"

# Restore
pg_restore -d gitradar -c /var/backups/gitradar/gitradar_YYYYMMDD.dump

# Bring app back
php artisan up
php artisan config:cache
sudo supervisorctl restart gitradar-worker:*
```

---

## `.env` Backup

Store encrypted copy outside the server:

```bash
gpg -c /var/www/gitradar/.env
# Copy .env.gpg to secure storage
```

Never commit `.env` to git.

---

## Recovery Time Objective (RTO)

| Scenario | Target |
|----------|--------|
| DB restore from daily backup | < 1 hour |
| Full EC2 rebuild from git + backup | < 4 hours |

---

## Verification

Monthly restore drill on staging:

1. Restore latest dump to staging DB
2. Run `php artisan migrate --force`
3. Verify login, dashboard, one repository

---

## Related Docs

- [DEPLOYMENT.md](DEPLOYMENT.md)
- [troubleshooting.md](troubleshooting.md)
