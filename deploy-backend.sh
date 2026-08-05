#!/usr/bin/env bash
#
# MoonJoin Laravel backend deployment.
#
# Deliberately separate from the legacy deploy.sh, which deploys the
# Next.js frontend via yarn/pm2 and is untouched by this script.
#
# Portable by design: no cPanel-specific paths or assumptions, so the same
# script can be invoked from .cpanel.yml today and from CI/systemd on
# DigitalOcean later. It performs release steps only -- fetching code is the
# caller's job (cPanel Git checks out before running .cpanel.yml).
#
# Overridable:
#   APP_ROOT      application root            (default: this script's directory)
#   PHP_BIN       php binary                  (default: first php on PATH)
#   COMPOSER_BIN  composer command            (default: autodetected)
#
# Idempotent: safe to run repeatedly.

set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
cd "$APP_ROOT"

log()  { printf '[deploy] %s\n' "$*"; }
fail() { printf '[deploy] ERROR: %s\n' "$*" >&2; exit 1; }

# --------------------------------------------------------------------------
# Preflight -- everything that can fail is checked BEFORE maintenance mode,
# so an avoidable error never leaves the site down.
# --------------------------------------------------------------------------
log "Preflight checks in $APP_ROOT"

PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
[ -n "$PHP_BIN" ] || fail "No php binary found. Set PHP_BIN to an absolute path."
command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP_BIN is not executable: $PHP_BIN"

PHP_OK=$("$PHP_BIN" -r 'echo PHP_VERSION_ID >= 80200 ? "yes" : "no";')
[ "$PHP_OK" = "yes" ] || fail "$($PHP_BIN -v | head -1) is too old; Laravel 12 needs PHP >= 8.2."

[ -f artisan ]           || fail "artisan not found -- APP_ROOT is not a Laravel root."
[ -f .env ]              || fail ".env not found. Create it on the server; it is never deployed from git."
[ -w storage ]           || fail "storage/ is not writable."
[ -w bootstrap/cache ]   || fail "bootstrap/cache/ is not writable."

# Composer: prefer a real binary, fall back to the repo-tracked phar.
if [ -n "${COMPOSER_BIN:-}" ]; then
    :
elif command -v composer >/dev/null 2>&1; then
    COMPOSER_BIN="composer"
elif [ -f composer.phar ]; then
    COMPOSER_BIN="$PHP_BIN composer.phar"
    log "System composer not found; using repo-tracked composer.phar"
else
    fail "No composer and no composer.phar. Cannot install dependencies."
fi

$COMPOSER_BIN --version >/dev/null 2>&1 || fail "Composer found but not runnable: $COMPOSER_BIN"
log "php:      $("$PHP_BIN" -v | head -1)"
log "composer: $($COMPOSER_BIN --version 2>/dev/null | head -1)"

# --------------------------------------------------------------------------
# Release
# --------------------------------------------------------------------------
# Always restore availability, even if a later step aborts.
restore_up() {
    local rc=$?
    [ $rc -ne 0 ] && log "Deployment failed (exit $rc); bringing application back up."
    "$PHP_BIN" artisan up || true
    return $rc
}
trap restore_up EXIT

log "Enabling maintenance mode"
"$PHP_BIN" artisan down --retry=60 || true   # already-down is not an error

log "Installing production dependencies"
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Best-effort database snapshot before schema changes. Rolling back to a git
# tag restores code but never schema, so this is the only thing that makes a
# failed migration recoverable. Never fatal: a missing mysqldump must not block
# a deployment. Set SKIP_DB_BACKUP=1 to opt out.
if [ -z "${SKIP_DB_BACKUP:-}" ] && command -v mysqldump >/dev/null 2>&1; then
    env_get() {
        "$PHP_BIN" -r '
            $k = $argv[1];
            foreach (preg_split("/\R/", file_get_contents(".env")) as $l) {
                $l = trim($l);
                if ($l === "" || $l[0] === "#" || !str_contains($l, "=")) continue;
                [$a, $b] = explode("=", $l, 2);
                if (trim($a) === $k) { echo trim($b, " \"'"'"'"); return; }
            }' "$1"
    }
    if [ "$(env_get DB_CONNECTION)" = "mysql" ]; then
        BACKUP_DIR="storage/app/backups"
        mkdir -p "$BACKUP_DIR"
        # Self-protecting: dumps must never enter version control. Written here
        # rather than committed so this script needs no companion files.
        [ -f "$BACKUP_DIR/.gitignore" ] || printf '*\n!.gitignore\n' > "$BACKUP_DIR/.gitignore"
        BACKUP_FILE="$BACKUP_DIR/pre-deploy-$(date +%Y%m%d-%H%M%S).sql"
        log "Backing up database to $BACKUP_FILE"
        dump() {
            MYSQL_PWD="$(env_get DB_PASSWORD)" mysqldump \
                --host="$(env_get DB_HOST)" --port="$(env_get DB_PORT)" \
                --user="$(env_get DB_USERNAME)" --quick --no-tablespaces \
                "$@" "$(env_get DB_DATABASE)" > "$BACKUP_FILE" 2>/dev/null
        }
        # --single-transaction gives a consistent snapshot but issues FLUSH
        # TABLES, which needs the RELOAD privilege that shared hosting withholds.
        # Fall back to an unlocked dump: the site is already in maintenance mode
        # and QUEUE_CONNECTION=sync, so there are no concurrent writers.
        if dump --single-transaction --routines; then
            log "Backup written, consistent snapshot ($(du -h "$BACKUP_FILE" | cut -f1))"
        elif dump --lock-tables=false; then
            log "Backup written, unlocked dump ($(du -h "$BACKUP_FILE" | cut -f1))"
        else
            rm -f "$BACKUP_FILE"
            log "WARNING: database backup failed; continuing without a snapshot."
        fi
    fi
else
    log "WARNING: mysqldump unavailable or backup skipped; no pre-migration snapshot."
fi

log "Running migrations"
"$PHP_BIN" artisan migrate --force

log "Clearing stale caches"
"$PHP_BIN" artisan optimize:clear

log "Rebuilding caches"
# config:cache is DELIBERATELY NOT RUN.
#
# Laravel skips loading .env entirely when the config is cached
# (LoadEnvironmentVariables::bootstrap() returns early), so every env() call
# outside config/ returns null. Audit of this codebase found 265 such call
# sites; 234 supply no default and collapse to 11 distinct keys.
#
# PRIMARY reason -- reproduced API contract change:
#   ConfigController.php:396   'websocket_key' => env('PUSHER_APP_KEY')
#       A field in the GET /api/v1/config response consumed by the User,
#       Vendor and Delivery Flutter apps. Measured before/after: "" -> null.
#       A non-nullable Dart String receiving null throws on deserialisation.
#
# SECONDARY -- runtime, currently inert:
#   ConfigServiceProvider.php:31 -> :152  $mode = env('APP_MODE')
#       PAYTM_ENVIRONMENT flips PROD -> TEST. The only APP_MODE comparison
#       made against 'live'. Paytm is is_active=0, so no present impact.
#
# ADMIN-ONLY -- real but off the production request path:
#   SOFTWARE_VERSION (7 sites)  blank version in footers; UpdateController.
#   REACT_APP_KEY    (4 sites)  Helpers::activation_submit(), reachable only
#                               from BusinessSettingsController:7185/7199.
#
# NOT reasons (verified false positives): the other 194 APP_MODE sites compare
# only against 'demo'/'test'/'dev', where null and 'live' behave identically;
# APP_ENV is already absent from .env.production; APP_NAME's cache key changes
# consistently at all 6 sites and is self-healing; PROJECT_PATH is already unset.
#
# event:cache and view:cache do not read env() and are safe.
# Re-enable config:cache only after the calls above are moved into config/ and
# read via config() -- an application change, out of scope for deployment.
"$PHP_BIN" artisan event:cache
"$PHP_BIN" artisan view:cache

# route:cache is OPTIONAL by explicit decision. 14 duplicate route names remain,
# three of them on live Vendor/Delivery API endpoints that share a name across
# different URIs, so they need renaming rather than deletion -- deferred to a
# dedicated maintenance phase. The application is fully correct without a route
# cache, so a failure here must never fail the deployment. This block needs no
# change when the duplicates are eventually resolved: it will simply start
# succeeding and the cache will be built.
if "$PHP_BIN" artisan route:cache 2>/dev/null; then
    log "Route cache built"
else
    log "NOTICE: route:cache unavailable (known duplicate route names). Routes resolve uncached."
    "$PHP_BIN" artisan route:clear || true
fi

# Note: storage:link is intentionally NOT run. Helpers::get_full_url() serves
# assets from asset('storage/app/public'), which resolves directly because the
# document root is the application root. The public/storage symlink is unused
# here, and creating it would break idempotency on repeat runs.

log "Deployment complete"
exit 0
