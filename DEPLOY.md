# Deploy dev.portal (branch `dev`)

Automatic deploy runs on **push to `dev`** via [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml).

## GitHub Secrets (repository or `Development` environment)

| Secret           | Example / notes                                                       |
| ---------------- | --------------------------------------------------------------------- |
| `FTP_HOST`       | FTP hostname from cPanel                                              |
| `FTP_USERNAME`   | cPanel FTP user                                                       |
| `FTP_PASSWORD`   | cPanel FTP password                                                   |
| `FTP_SERVER_DIR` | `/public_html/dev.portal.hstkb.sch.id/` (Laravel root with `artisan`) |
| `DEPLOY_URL`     | `https://dev.portal.hstkb.sch.id` (no trailing slash)                 |
| `DEPLOY_SECRET`  | Same value as `DEPLOY_SECRET` in server `.env`                        |

## Server `.env`

```env
DEPLOY_SECRET=your-long-random-string
```

## Post-deploy hook

After FTP upload, CI calls:

1. `GET {DEPLOY_URL}/deploy-route-cache-clear.php?token={DEPLOY_SECRET}` (clears stale `bootstrap/cache/routes-*.php` — common cause of **404** on `/release`)
2. `GET {DEPLOY_URL}/deploy/{DEPLOY_SECRET}/release`

If `/release` returns 404, CI falls back to `/composer-install`, `/migrate`, and `/optimize`.

**`DEPLOY_URL`** must be the public site URL only (e.g. `https://dev.portal.hstkb.sch.id`) — no trailing slash, no `/public` path.

Runs `php composer.phar install --no-dev` (or system `composer` if phar missing), then `migrate --force`, and `optimize`.

`vendor/` is **not** uploaded via FTP (too large; causes session timeouts). CI uploads `composer.phar` next to `artisan` — **no cPanel terminal / SSH required**.

FTP deploy **auto-retries once** if the host drops the connection (`.ftp-deploy-sync-state.json` resumes upload). If FTPS keeps failing with `FIN packet`, try changing `protocol` to `ftp` in `deploy.yml` (less secure) or ask host to raise FTP session timeout.

## cPanel Git

Disable **automatic deployment** on `dev-portal-hstkb` to avoid conflicting with FTP.

Manual pull only: **Update from Remote** on branch `dev` (does not replace GitHub Actions deploy).

## First deploy checklist

1. Backup server `.env` and database.
2. Add all secrets above in GitHub → Settings → Secrets → Actions (create `Development` environment if the workflow requires it).
3. Push to `dev` and watch the **deploy** workflow in Actions.
4. Hard-refresh dev.portal and smoke-test login + student pages.
