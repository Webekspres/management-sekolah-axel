# Deploy (GitHub Actions)

Automatic deploy runs via [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml):

| Branch | GitHub environment | Target                            |
| ------ | ------------------ | --------------------------------- |
| `dev`  | `Development`      | `https://dev.portal.hstkb.sch.id` |
| `main` | `Production`       | `https://portal.hstkb.sch.id`     |

Each environment has its own secrets. The workflow picks the environment from the branch that was pushed.

## GitHub Secrets

Create two environments under **Settings → Environments**: `Development` and `Production`.

### Development (`dev` branch)

| Secret           | Example / notes                                                       |
| ---------------- | --------------------------------------------------------------------- |
| `FTP_HOST`       | FTP hostname from cPanel                                              |
| `FTP_USERNAME`   | cPanel FTP user                                                       |
| `FTP_PASSWORD`   | cPanel FTP password                                                   |
| `FTP_SERVER_DIR` | `/public_html/dev.portal.hstkb.sch.id/` (Laravel root with `artisan`) |
| `DEPLOY_URL`     | `https://dev.portal.hstkb.sch.id` (no trailing slash)                 |
| `DEPLOY_SECRET`  | Same value as `DEPLOY_SECRET` in server `.env`                        |

### Production (`main` branch)

| Secret           | Example / notes                                                   |
| ---------------- | ----------------------------------------------------------------- |
| `FTP_HOST`       | FTP hostname from cPanel                                          |
| `FTP_USERNAME`   | cPanel FTP user for production                                    |
| `FTP_PASSWORD`   | cPanel FTP password for production                                |
| `FTP_SERVER_DIR` | `/public_html/portal.hstkb.sch.id/` (Laravel root with `artisan`) |
| `DEPLOY_URL`     | `https://portal.hstkb.sch.id` (no trailing slash)                 |
| `DEPLOY_SECRET`  | Same value as `DEPLOY_SECRET` in production server `.env`         |

Optional: restrict the `Production` environment to the `main` branch and/or require manual approval before deploy.

## FTP exclude list (`FTP_DEPLOY_EXCLUDE`)

This is **not** a GitHub secret. It is defined at the top of `deploy.yml` under the workflow-level `env:` block:

```yaml
env:
    FTP_DEPLOY_EXCLUDE: |
        **/.git*
        **/vendor/**
        ...
```

Those patterns are passed to [FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action) and tell it which files **not** to upload. To change what gets excluded:

1. Edit `.github/workflows/deploy.yml`.
2. Add or remove lines under `FTP_DEPLOY_EXCLUDE` (one glob pattern per line).
3. Commit and push — no GitHub UI configuration needed.

Patterns use gitignore-style globs (`**` matches any directory depth). Common reasons items are excluded:

| Pattern                        | Why                                                   |
| ------------------------------ | ----------------------------------------------------- |
| `**/vendor/**`                 | Installed on server via `composer.phar` after upload  |
| `**/node_modules/**`           | Not needed; assets are built in CI (`npm run build`)  |
| `**/.env`, `**/.env.*`         | Server keeps its own `.env` — never overwrite via FTP |
| `**/storage/**`                | Server-side logs, cache, and uploads must persist     |
| `**/tests/**`, `**/.github/**` | Dev/CI only                                           |
| `**/*.md`, `**/scripts/**`     | Not needed in production runtime                      |

To **include** something that is currently excluded, remove its line from the list. To **exclude** a new path (e.g. a local tooling folder), add a line like `**/my-folder/**`.

Both FTP upload steps (initial + retry) use the same `${{ env.FTP_DEPLOY_EXCLUDE }}` value.

## Server `.env`

On each server (dev and prod), set:

```env
DEPLOY_SECRET=your-long-random-string
```

Use a different secret per environment. The value must match the `DEPLOY_SECRET` GitHub secret for that environment.

## Post-deploy hook

After FTP upload, CI calls:

1. `GET {DEPLOY_URL}/deploy-route-cache-clear.php?token={DEPLOY_SECRET}` (clears stale `bootstrap/cache/routes-*.php` — common cause of **404** on `/release`)
2. `GET {DEPLOY_URL}/deploy/{DEPLOY_SECRET}/release`

If `/release` returns 404, CI falls back to `/composer-install`, `/migrate`, and `/optimize`.

**`DEPLOY_URL`** must be the public site URL only (e.g. `https://portal.hstkb.sch.id`) — no trailing slash, no `/public` path.

Runs `php composer.phar install --no-dev` (or system `composer` if phar missing), then `migrate --force`, and `optimize`.

`vendor/` is **not** uploaded via FTP (too large; causes session timeouts). CI uploads `composer.phar` next to `artisan` — **no cPanel terminal / SSH required**.

FTP deploy **auto-retries once** if the host drops the connection (`.ftp-deploy-sync-state.json` resumes upload). If FTPS keeps failing with `FIN packet`, try changing `protocol` to `ftp` in `deploy.yml` (less secure) or ask host to raise FTP session timeout.

## cPanel Git

Disable **automatic deployment** on both `dev-portal-hstkb` and production repos in cPanel to avoid conflicting with FTP.

Manual pull only: **Update from Remote** (does not replace GitHub Actions deploy).

## First deploy checklist

### Development

1. Backup server `.env` and database.
2. Add Development secrets in GitHub → Settings → Environments → `Development`.
3. Push to `dev` and watch the **deploy** workflow in Actions.
4. Hard-refresh dev.portal and smoke-test login + student pages.

### Production

1. Backup production `.env` and database.
2. Add Production secrets in GitHub → Settings → Environments → `Production`.
3. Merge or push to `main` and watch the **deploy** workflow in Actions.
4. Hard-refresh portal.hstkb.sch.id and smoke-test critical flows.
