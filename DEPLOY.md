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

`GET {DEPLOY_URL}/deploy/{DEPLOY_SECRET}/release`

Runs `migrate --force` and `optimize`.

## cPanel Git

Disable **automatic deployment** on `dev-portal-hstkb` to avoid conflicting with FTP.

Manual pull only: **Update from Remote** on branch `dev` (does not replace GitHub Actions deploy).

## First deploy checklist

1. Backup server `.env` and database.
2. Add all secrets above in GitHub → Settings → Secrets → Actions (create `Development` environment if the workflow requires it).
3. Push to `dev` and watch the **deploy** workflow in Actions.
4. Hard-refresh dev.portal and smoke-test login + student pages.
