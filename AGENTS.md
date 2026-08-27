# AGENTS.md — Keuangan

## What this is

Pure PHP MVC (no framework, no Composer runtime). Vanilla JS frontend. MySQL via PDO.

## Commands

```bash
# Dev server (from project root or public/)
php -S 127.0.0.1:8000 -t public/

# Lint (what CI runs)
find . -name '*.php' -not -path './vendor/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l

# First-time setup
cp .env.example .env   # then edit credentials
php public/setup.php admin yourPassword

# Migrations
php scripts/migrate.php            # apply pending
php scripts/migrate.php status     # show applied vs pending
php scripts/migrate.php fresh --confirm  # DROP ALL + rebuild

# Backup
mysqldump --single-transaction keuangan > backup_$(date +%F).sql
```

## Architecture

- **Entrypoint**: `public/index.php` → `app/Bootstrap.php` → `App\Core\App`
- **Autoloader**: hand-rolled PSR-4-like in `Bootstrap::registerAutoloader()`. Namespace `App\` → `app/`. No Composer.
- **Routes**: defined in `App.php:registerRoutes()`. Format: `$r->get('/path', 'ControllerShortName', 'action')`. Router resolves `ControllerShortName` → `App\Controllers\{Name}Controller`.
- **Controllers** extend `App\Core\Controller`. Access `$this->db` (PDO), `$this->request`, `$this->config`.
- **Views** in `app/Views/`, rendered via `$this->render('view/name', $data, $layout)`. Default layout: `layouts/header`. Global helpers: `e()` (escape), `old()` (sticky input).
- **Qualified helpers** must be called with namespace: `\App\Helpers\csrf_token()`, `\App\Helpers\auth_require_login()`, `\App\Helpers\csrf_field()`, etc.
- **Models** extend `App\Core\Model`, get PDO via constructor.
- **Session**: name `KEUANGAN_SESS`, HttpOnly+SameSite=Lax, idle timeout 30min.

## CI/CD (GitHub Actions)

- **`.github/workflows/deploy.yml`**: push to `main` → lint (`php -l`) → FTP deploy → `curl https://$DEPLOY_URL/setup.php?token=$DEPLOY_TOKEN`
- Secrets required: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`, `DEPLOY_URL`, `DEPLOY_TOKEN`
- `.env` and `config/db.php` are excluded from FTP mirror (secrets stay server-side)

## Migrations

- Files: `database/migrations/NNN_name.sql` (lexicographic order = numeric order)
- State tracked in `schema_migrations` table (auto-created)
- Each migration runs in a transaction. Failure = rollback, no version recorded.
- `fresh` drops ALL tables and re-runs every migration (local dev only).
- `setup.php` handles: token-protected (SETUP_TOKEN env), auto-repair for partial migrations, admin user creation.

## Key security defaults

- All SQL goes through prepared statements (`PDO::ATTR_EMULATE_PREPARES = false`)
- CSRF: per-session 32-byte hex token, validated on POST. Field: `_csrf`
- Login throttling: `login_attempts` table, 5 attempts / 15min (configurable)
- Password: `PASSWORD_BCRYPT`
- Output: `htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8')` via `e()` helper

## Testing

No test framework or test files found. `.phpunit.cache` in `.gitignore` (artifact from abandoned setup).

## Logging

`App\Core\Logger` writes daily files to `runtime/logs/YYYY-MM-DD.log`. Configurable retention via `LOG_RETENTION_DAYS` (default 30). Probabilistic cleanup (~1% of requests).

## Telegram Bot

- **Files**: `app/Telegram/`, `app/Controllers/TelegramController.php`
- **Routes**: `POST /telegram/webhook`, `GET /telegram/setup`, `GET /telegram/info`
- **Requires** env vars: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `APP_URL`
- Supports free-form input (`+50000 gaji`, `-25000 makan`, `50rb kopi`), inline category keyboard, and commands (`/ringkasan`, `/terakhir`, `/batal`)
- Single-user mode: resolves user as `SELECT id FROM users ORDER BY id ASC LIMIT 1`
- State (`pending_category`) stored in `telegram_bot_state` table (migration `003_telegram_bot_state.sql`)
- Setup: visit `/telegram/setup?token=SETUP_TOKEN` once to register webhook

## Important gotchas

- `.env` and `config/db.php` are **gitignored**. Never commit them.
- `SETUP_TOKEN` must match between `.env` (server-side) and GitHub secret `DEPLOY_TOKEN`. Default `CHANGE-ME-TO-RANDOM-STRING` is rejected.
- `installed.lock` in gitignore — not used by current codebase.
- Deployment is file-level FTP — no PHP package manager involved.