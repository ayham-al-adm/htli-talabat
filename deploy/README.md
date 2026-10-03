# Deployment — Admin-Panel-2.2

Runbook for a single-server install on Ubuntu 24.04 LTS. Target box: `albishvps`, public IP `94.252.183.131`.

| | |
|---|---|
| Stack | nginx + PHP **8.3**-FPM (unix socket) + MySQL **8.0** + Node **20** |
| App root | `/var/www/admin-panel` |
| Cache / session / queue | `file` / `file` / `database` |

## Files in this directory

| File | Destination on the server |
|---|---|
| `nginx/admin-panel.conf` | `/etc/nginx/sites-available/admin-panel` |
| `php/99-app-fpm.ini` | `/etc/php/8.3/fpm/conf.d/99-app.ini` |
| `php/99-app-cli.ini` | `/etc/php/8.3/cli/conf.d/99-app.ini` |
| `systemd/admin-panel-worker@.service` | `/etc/systemd/system/` |
| `logrotate/admin-panel` | `/etc/logrotate.d/admin-panel` |
| `crontab.example` | `sudo crontab -u www-data -e` |
| `env.production.example` | `/var/www/admin-panel/.env` |
| `deploy.sh` | `/var/www/admin-panel/deploy.sh` |
| `backup.sh` | `/usr/local/bin/admin-panel-backup.sh` |

Each file documents its own reasoning inline. Read `env.production.example` carefully — it is where the non-obvious decisions live.

---

## Three ways this app fails silently

Know these before you start; they account for most of the fiddly parts below.

1. **No cron ⇒ the app looks broken, with no errors.** `app/Console/Kernel.php` assigns drivers to rides from a per-minute scheduled command. Without `schedule:run`, rides simply never get a driver.
2. **`QUEUE_CONNECTION=sync` ⇒ pushes, invoice PDFs and emails run inside the HTTP request.** There are ~30 `ShouldQueue` classes.
3. **`config/session.php` forces `Secure` cookies whenever `APP_ENV !== 'local'`.** On plain HTTP that means **nobody can log in** — the cookie is set and never sent back. `SESSION_SECURE_COOKIE=false` is the documented override; delete it when TLS goes live.

## Version constraints — do not deviate

- **PHP 8.3.** `composer.json` claims `^8.1`, but the lock file's real floor is 8.2 (`laravel-notification-channels/fcm`, `mercadopago/dx-php`, several `symfony/*`) and its ceiling is 8.4 (`kreait/laravel-firebase`; `inertiajs/inertia-laravel` caps at `~8.3`).
- **MySQL 8.0, not MariaDB, never Postgres.** `fleetbase/laravel-mysql-spatial` is a hard dependency, and `db_point()` in `app/Helpers/helpers.php` emits MySQL 8's `ST_GeomFromText(..., 'axis-order=long-lat')`. MariaDB takes an older, less-exercised fallback.
- **Node 18 or 20.** Vite 4 + `sass@1.77.6`. Node 22+ is a known risk.

---

## First deploy

### 1. Server prep

```bash
ssh user1@94.252.183.131

# SSH keys first, then disable password auth (verify key login in a 2nd terminal
# BEFORE this), then `passwd` to rotate the initial password.
sudo sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo grep -rn 'PasswordAuthentication' /etc/ssh/sshd_config.d/   # 24.04 drop-ins override
sudo systemctl restart ssh

sudo ufw allow OpenSSH && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp
sudo ufw --force enable

sudo apt update && sudo apt -y upgrade
sudo apt -y install git curl unzip fail2ban unattended-upgrades
sudo timedatectl set-timezone UTC     # config/app.php hardcodes UTC, not env-driven
```

**Swap is required for the frontend build.** `node_modules` is 459 MB and the build pulls in amCharts 5, Firebase 10, 8 `@fullcalendar` packages, Leaflet + plugins and CKEditor — it OOMs on a small VPS without it.

```bash
free -h
sudo fallocate -l 4G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 2. Stack

```bash
sudo apt -y install php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
  php8.3-opcache php8.3-readline
sudo apt -y install mysql-server nginx
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt -y install nodejs
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
sudo mysql_secure_installation
```

Extension rationale: `zip`/`xml`/`gd` for `maatwebsite/excel`; `gd` for `intervention/image`; `xml`+`mbstring` for `barryvdh/laravel-dompdf`; `curl`+`openssl` for the payment gateways, Socialite and Firebase/FCM. **Nothing here needs `pgsql`, `soap`, `ldap` or `redis`.**

> FCM falls back to slow pure-PHP without gRPC. If push volume grows, `sudo pecl install grpc protobuf`. Not needed at launch.

Then install the PHP ini files from this directory, and set `pm.max_children` in `/etc/php/8.3/fpm/pool.d/www.conf` to roughly `(RAM × 0.7) / 80 MB` — 12 is about right for 4 GB. Confirm the pool listens on `/run/php/php8.3-fpm.sock`.

### 3. Database

```sql
CREATE DATABASE admin_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adminpanel'@'127.0.0.1' IDENTIFIED BY '<openssl rand -base64 24>';
GRANT ALL PRIVILEGES ON admin_panel.* TO 'adminpanel'@'127.0.0.1';
FLUSH PRIVILEGES;
```

`utf8mb4_unicode_ci` must match `config/database.php`. **No `sql_mode` tuning needed** — `config/database.php` sets `'strict' => false`, so Laravel issues `SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'` per connection. Leave `strict` alone; the app's aggregate queries likely depend on `ONLY_FULL_GROUP_BY` being off.

Check MySQL is on loopback only: `sudo ss -ltnp | grep 3306` → `127.0.0.1:3306`. Never open 3306 to the network.

### 4. Code

```bash
sudo mkdir -p /var/www && sudo chown user1:user1 /var/www
ssh-keygen -t ed25519 -f ~/.ssh/deploy_key -N ""
cat ~/.ssh/deploy_key.pub        # add as a READ-ONLY deploy key on the private repo
git clone git@github.com:<you>/admin-panel.git /var/www/admin-panel
cd /var/www/admin-panel

composer install --no-dev --optimize-autoloader --no-interaction
NODE_OPTIONS=--max-old-space-size=3072 npm ci
NODE_OPTIONS=--max-old-space-size=3072 npm run build
ls -la public/build/manifest.json    # must exist
```

`--no-dev` is not optional — `require-dev` includes `spatie/laravel-ignition`, which renders source code and env vars on error pages. `public/build` is gitignored, so the build step is mandatory or every page throws *"Vite manifest not found"*.

Permissions:

```bash
sudo chown -R user1:www-data /var/www/admin-panel
sudo find /var/www/admin-panel -type d -exec chmod 755 {} \;
sudo find /var/www/admin-panel -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;
sudo usermod -aG www-data user1      # re-login to take effect
```

The `g+s` setgid bit is what stops intermittent permission errors: without it, cache files written by `user1`'s `artisan` runs aren't group-writable by `www-data` (and vice versa).

### 5. Environment

```bash
cp deploy/env.production.example .env
nano .env                    # fill in every <...>
php artisan key:generate     # mandatory: the old example key is in git history
chmod 640 .env && sudo chown user1:www-data .env
```

### 6. Migrate and seed

```bash
php artisan migrate --force      # 381 migrations
php artisan db:seed --force      # a few minutes; needs the 1 GB CLI memory_limit
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

The seeders that matter: `RolesAndPermissionsSeeder` (the whole permission matrix every `permission:` middleware check reads), `SettingsSeeder`, `AdminSeeder`, `NotificationChannelSeeder`, `CountriesTableSeeder`, `TimeZoneSeeder`, `DefaultLanguageSeeder`, `MailTemplateSeeder`. If `db:seed` OOMs anyway: `php -d memory_limit=-1 artisan db:seed --force`.

Optional, not in `DatabaseSeeder`:

```bash
php artisan db:seed --class=StatesAndCitiesTableSeeder --force
php artisan db:seed --class=CarMakeAndModelSeeder --force
php artisan db:seed --class=DriverNeededDocumentSeeder --force
```

#### ⚠️ Change the seeded admin before nginx starts

`database/seeders/AdminSeeder.php` hardcodes `admin@admin.com` / `123456789` / mobile `9999999999` — a publicly known default for this codebase family.

```bash
php artisan tinker
>>> $u = App\Models\User::where('email','admin@admin.com')->first();
>>> $u->email = 'you@yourdomain.com'; $u->password = bcrypt('<long random>'); $u->save();
>>> $u->admin()->update(['email' => 'you@yourdomain.com']);
```

### 7. nginx, worker, cron

Install `nginx/admin-panel.conf`, `systemd/admin-panel-worker@.service`, `logrotate/admin-panel` and the `crontab.example` line, then:

```bash
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl daemon-reload
sudo systemctl enable --now admin-panel-worker@1 admin-panel-worker@2
sudo crontab -u www-data -l      # confirm the schedule:run line is there
```

---

## Verification

Work through it in order — each step should be *observed*, not assumed.

```bash
# 1. Services
sudo systemctl is-active nginx php8.3-fpm mysql admin-panel-worker@1 admin-panel-worker@2

# 2. Responds, and debug is off
curl -I http://94.252.183.131/
#   expect: X-Content-Type-Options, X-Frame-Options: DENY,
#           Content-Security-Policy-Report-Only, and a short enforced
#           Content-Security-Policy baseline
#   expect NO: Strict-Transport-Security (correct on HTTP), X-Powered-By, Server version
curl -s http://94.252.183.131/no-such-route | grep -ci 'ignition\|stack trace'   # expect 0

# 3. Assets
curl -sI http://94.252.183.131/build/manifest.json | head -1
```

4. **Log in.** This is the real test of `SESSION_SECURE_COOKIE`. Bouncing back to the login page with no error message means it's still `true` — check with `php artisan tinker --execute="dd(config('session.secure'));"`.

5. **Rate limiting.** Six bad logins in a minute → the sixth returns 429 with `Retry-After`. Five failures in 15 min locks the account for 15 min. Keep the escape hatch handy: `php artisan auth:unlock <email|mobile|username>`.

6. **Queue drains.** Trigger something queued (approve a driver, send a test mail), then `select count(*) from jobs` should return to 0. `php artisan queue:failed` for anything stuck. `tail -f storage/logs/worker.log`.

7. **Scheduler fires.** `php artisan schedule:list`, then after 90 s check `storage/logs/schedule.log`.

8. **Uploads work end to end.** Upload a profile picture, confirm it appears under `storage/app/public/uploads/user/profile-picture/`, then fetch it over HTTP at `/storage/uploads/...` — expect 200. This exercises `storage:link`, the remapped `local` disk (this project points `local` at `storage/app/public`, not the usual `storage/app`) and the `config:cache` interaction all at once. **If images 404, start here:** check `config('filesystems.disks.local.url')` in tinker.

9. **Invoice PDF + Excel export.** These need the dompdf font cache to be writable, `ext-zip`/`ext-gd`, and the raised `memory_limit`/`max_execution_time`.

10. **`sudo reboot`**, then re-run step 1.

11. `tail -50 storage/logs/laravel-$(date +%F).log` and `/var/log/nginx/admin-panel.error.log`.

---

## Subsequent deploys

```bash
cd /var/www/admin-panel && ./deploy.sh
```

Both of its sudo steps matter: the PHP-FPM reload because `opcache.validate_timestamps=0` means bytecode is never revalidated, and `queue:restart` because long-lived workers hold the old code in memory indefinitely.

---

## Adding TLS

Required before real users or the mobile apps point here — right now login passwords travel in cleartext.

```bash
sudo snap install --classic certbot && sudo ln -sf /snap/bin/certbot /usr/bin/certbot
sudo certbot --nginx -d panel.example.com
sudo systemctl list-timers | grep certbot     # renewal
```

Then in `.env`: `APP_URL`/`ASSET_URL` → `https://…`, set `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN` to the domain, and **delete the `SESSION_SECURE_COOKIE=false` line**. Update `server_name` in the nginx config. Finish with `php artisan config:cache && sudo systemctl reload php8.3-fpm && php artisan queue:restart`.

HSTS starts working by itself — `SecurityHeaders` only emits it on HTTPS, and `TrustProxies` forwards `X-Forwarded-Proto` so Laravel sees the real scheme. **Leave `SECURITY_HSTS_PRELOAD=false`**; preload is effectively irreversible.

**Keep `SECURITY_CSP_ENFORCE=false` for at least a week.** The policy ships as report-only and allowlists ~10 payment-gateway CDNs plus Google Maps/reCAPTCHA, Firebase and 5 JS CDNs. Watch the browser console for violations, fix them, then flip it.

---

## Configure in the admin panel, not in `.env`

Most runtime credentials live in the DB `settings` tables and are edited through the UI (`get_settings()`, `SettingsSeeder`, `ThirdPartySettingSeeder`). After the first deploy, log in and set:

- **Map settings** — Google Maps API key. Nothing geographic works without it.
- **Payment gateways**, **SMS gateway**, **Mail configuration**, **reCAPTCHA**.
- **Firebase / FCM** — `FCM_SERVER_KEY` in `.env`, plus the service-account key at
  `public/push-configurations/firebase.json`. **That file is gitignored**, so a clone
  does not bring it: upload it through the panel's Firebase settings page, or scp it
  into place. Without it the Firebase Admin SDK throws on first use. nginx denies
  `/push-configurations/` over HTTP — only PHP reads it off disk.

## Backups

Install `backup.sh` and enable its cron line. **The database is the only copy of the schema** — this repo has 381 migrations and no `.sql` dump — and `storage/app/public/uploads` is user data that exists nowhere else. Add the offsite copy step at the bottom of that script; without it a backup only protects you from your own mistakes, not from losing the server.

## Known rough edges

- **Realtime is Firebase, not Laravel.** No Reverb/Soketi/Pusher server to deploy — `BroadcastServiceProvider` is commented out in `config/app.php`. The browser talks to the Firebase Realtime Database directly, and server-side pushes go via FCM.
- **`app/Jobs/NotifyViaSocket.php` and `NotifyViaMqtt.php` are dead code.** Their packages (`elephantio/elephant.io`, `salman/mqtt`) are not in `composer.json` at all and every dispatch call site is commented out. **Never uncomment one** — it's an instant `Class not found` fatal.
- **`public/docs/` is tracked** (Scribe static output) and will be publicly served at `/docs`. There's a commented-out `deny all` block in the nginx config if you'd rather it weren't.
- The web installer (`routes/Install/install.php`, `InstallationController`) is **not routed** — `routes/web.php` only imports the class. Nothing to block.
