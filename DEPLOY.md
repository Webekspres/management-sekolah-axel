# Deploy (GitHub Actions)

Deploy runs as the final job in [`.github/workflows/ci.yml`](.github/workflows/ci.yml) **after** lint and tests succeed on `dev` or `main`. That gate includes Pint, Unit, Feature, and Playwright browser tests — deploy does not run its own duplicate Pest suite.

| Branch | GitHub environment | Target                            |
| ------ | ------------------ | --------------------------------- |
| `dev`  | `Development`      | `https://dev.portal.hstkb.sch.id` |
| `main` | `Production`       | `https://portal.hstkb.sch.id`     |

Each environment has its own secrets. The deploy job matrix picks the target from the branch that passed tests.

You can also trigger deploy manually from **Actions → CI → Run workflow** and choose the target host.

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

This is **not** a GitHub secret. It is defined at the top of `ci.yml` under the workflow-level `env:` block:

```yaml
env:
    FTP_DEPLOY_EXCLUDE: |
        **/.git*
        **/vendor/**
        ...
```

Those patterns are passed to [FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action) and tell it which files **not** to upload. To change what gets excluded:

1. Edit `.github/workflows/ci.yml`.
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

Use a different secret per environment. The value must match the `DEPLOY_SECRET` GitHub secret for that environment. Special characters (`[`, `]`, `$`, etc.) are supported — CI URL-encodes the token before calling deploy hooks.

## Post-deploy hook

After FTP upload, CI calls:

1. `GET {DEPLOY_URL}/deploy-route-cache-clear.php?token={DEPLOY_SECRET}` (clears stale `bootstrap/cache/routes-*.php` — common cause of **404** on `/release`)
2. `GET {DEPLOY_URL}/deploy-composer-install.php?token={DEPLOY_SECRET}` (installs `vendor/` in the browser — **no SSH**; required because FTP excludes `vendor/`)
3. `GET {DEPLOY_URL}/deploy/{DEPLOY_SECRET}/release`

If `/release` returns 404, CI falls back to `/composer-install`, `/migrate`, and `/optimize`.

**`DEPLOY_URL`** must be the public site URL only (e.g. `https://portal.hstkb.sch.id`) — no trailing slash, no `/public` path.

`deploy-composer-install.php` runs `php composer.phar install --no-dev` without booting Laravel (use this on first deploy or when `vendor/` is missing). `/release` then runs package discover, `migrate --force`, and `optimize`.

`vendor/` is **not** uploaded via FTP (too large; causes session timeouts). CI uploads `composer.phar` next to `artisan` — **no cPanel terminal / SSH required**.

FTP deploy **auto-retries once** if the host drops the connection (`.ftp-deploy-sync-state.json` resumes upload). If FTPS keeps failing with `FIN packet`, try changing `protocol` to `ftp` in `ci.yml` (less secure) or ask host to raise FTP session timeout.

## Concurrency (rapid pushes)

CI uses **job-level** concurrency so lint/test and deploy behave differently:

| Phase | Behavior on a new push to the same branch |
| ----- | ------------------------------------------- |
| Lint / test | In-progress checks are **cancelled** — only the latest commit is validated. |
| Deploy | In-flight FTP upload is **never cancelled** — it runs to completion. |
| Deploy queue | If another run finishes tests while deploy is busy, its deploy job **waits** until the current upload finishes, then deploys in order. |

The server always ends up on the newest commit that passed CI; you may briefly see an older commit live while a deploy is finishing, then the next queued deploy updates it.

## cPanel Git

Disable **automatic deployment** on both `dev-portal-hstkb` and production repos in cPanel to avoid conflicting with FTP.

Manual pull only: **Update from Remote** (does not replace GitHub Actions deploy).

## First deploy checklist

### Development

1. Backup server `.env` and database.
2. Add Development secrets in GitHub → Settings → Environments → `Development`.
3. Push to `dev` and watch the **CI** workflow in Actions (lint → test → deploy).
4. Hard-refresh dev.portal and smoke-test login + student pages.

### Production

1. Backup production `.env` and database.
2. Add Production secrets in GitHub → Settings → Environments → `Production`.
3. Merge or push to `main` and watch the **CI** workflow in Actions (lint → test → deploy).
4. Hard-refresh portal.hstkb.sch.id and smoke-test critical flows.

## Manual recovery (no SSH / terminal)

When the site returns **500** and `vendor/` is missing, use the browser only:

1. Confirm `composer.phar` exists next to `artisan` (uploaded by CI).
2. Open `https://portal.hstkb.sch.id/deploy-composer-install.php?token={DEPLOY_SECRET}` and wait until JSON shows `"status":"success"` (can take several minutes).
3. Open `https://portal.hstkb.sch.id/up` — expect HTTP 200.
4. Open `https://portal.hstkb.sch.id/deploy-route-cache-clear.php?token={DEPLOY_SECRET}`.
5. Open `https://portal.hstkb.sch.id/deploy/{DEPLOY_SECRET}/release`.

Optional in `.env`: `DEPLOY_PHP_CLI=/opt/cpanel/ea-php84/root/usr/bin/php` if the host uses a non-default PHP CLI.
