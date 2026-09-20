#!/usr/bin/env bash
#
# Redeploy Admin-Panel-2.2 after a `git push` to main.
#
#   sudo cp deploy/deploy.sh /var/www/admin-panel/deploy.sh
#   chmod +x /var/www/admin-panel/deploy.sh
#   ./deploy.sh
#
# Needs sudo for two commands (see SUDO NOTE at the bottom).

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/admin-panel}"
PHP="${PHP:-/usr/bin/php}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"

echo "==> Maintenance mode on"
$PHP artisan down --retry=60 || true

# Always come back up, even if a step below fails
trap '$PHP artisan up || true' EXIT

echo "==> Pulling $BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Composer (production)"
# --no-dev is not optional: require-dev contains spatie/laravel-ignition, which
# renders source code and env vars on error pages.
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Frontend build"
# public/build is gitignored, so it must be built here or every page throws
# "Vite manifest not found". The build is memory-hungry (amCharts 5, Firebase,
# @fullcalendar, Leaflet, CKEditor) -- hence the heap bump and the swap file.
export NODE_OPTIONS=--max-old-space-size=3072
npm ci
npm run build
test -f public/build/manifest.json || { echo "!! manifest.json missing"; exit 1; }

echo "==> Migrations"
$PHP artisan migrate --force

echo "==> Rebuilding caches"
$PHP artisan config:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

echo "==> Permissions"
sudo chown -R www-data:www-data storage bootstrap/cache

# Required because opcache.validate_timestamps=0 -- without this reload, PHP-FPM
# keeps serving the pre-pull bytecode indefinitely.
echo "==> Reloading PHP-FPM"
sudo systemctl reload php8.3-fpm

# Long-lived workers hold the old code in memory until told to exit.
echo "==> Restarting queue workers"
$PHP artisan queue:restart

echo "==> Done"
# The EXIT trap runs `artisan up`.

# SUDO NOTE: rather than blanket NOPASSWD, add a narrow rule with `sudo visudo
# -f /etc/sudoers.d/admin-panel-deploy`:
#
#   user1 ALL=(root) NOPASSWD: /bin/systemctl reload php8.3-fpm, \
#                              /bin/chown -R www-data\:www-data /var/www/admin-panel/storage, \
#                              /bin/chown -R www-data\:www-data /var/www/admin-panel/bootstrap/cache
