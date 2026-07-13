# Komagin HR — DigitalOcean Droplet Deployment Guide

**Document type:** Phase 6 Deliverable — Stage 6.2 (charter §6, retargeted for DigitalOcean)
**Status:** Complete — ready to execute once a droplet is provisioned. No live deployment performed this phase.
**Date compiled:** 2026-07-13
**Target stack:** Ubuntu 22.04 LTS · Nginx · PHP 8.2-FPM · MariaDB 10.x · Let's Encrypt (Certbot)

---

## 0. Before You Start

This guide assumes:
- A DigitalOcean account and a fresh Ubuntu 22.04 LTS droplet (any size — see §1).
- A domain name you control, with the ability to point an A record at the droplet's IP.
- SSH access to the droplet as `root` (DigitalOcean's default for a fresh droplet).

Every command below is written to be copy-pasted in order. Where a value is environment-specific (your domain, your database password, your droplet's IP), it's marked `<LIKE_THIS>`.

## 1. Droplet Sizing

This application is a traditional server-rendered PHP app (no Node build step, no heavy background workers beyond the lightweight scheduler) — it does not need a large droplet to run correctly for a typical HR department's real usage (tens to low hundreds of employees, not thousands of concurrent requests). Recommended starting point:

| Droplet | vCPU | RAM | Notes |
|---|---|---|---|
| **Basic — Regular, 2 GB / 1 vCPU** ($12/mo tier as of this writing) | 1 | 2 GB | Reasonable starting point. MariaDB + PHP-FPM + Nginx all fit comfortably at this size for moderate usage. |
| Basic — Regular, 4 GB / 2 vCPU | 2 | 4 GB | Recommended if you expect >100 concurrent users regularly, or want headroom for MariaDB's buffer pool without tuning tightly. |

Start at 2 GB/1 vCPU, monitor actual usage (`htop`, `docs/remediation/Phase6/` load-test results — see Stage 6.4), and resize the droplet later if needed — DigitalOcean droplets can be resized with a reboot, no migration required.

## 2. Initial Server Setup

```bash
# SSH in as root
ssh root@<DROPLET_IP>

# Update the system
apt update && apt upgrade -y

# Create a non-root sudo user (never run the app or do daily admin as root)
adduser deploy
usermod -aG sudo deploy

# Copy your SSH key to the new user (from your LOCAL machine, not the droplet)
# Run this on your own computer:
#   ssh-copy-id deploy@<DROPLET_IP>

# Back on the droplet: disable root SSH login and password auth, once the
# deploy user's key-based login is confirmed working in a SEPARATE terminal
# first — do not close your root session until you've verified you can log
# in as `deploy` in a new window.
nano /etc/ssh/sshd_config
#   PermitRootLogin no
#   PasswordAuthentication no
systemctl restart sshd
```

```bash
# Firewall (ufw) — allow only SSH, HTTP, HTTPS
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
ufw status
```

From here on, SSH in as `deploy` (`ssh deploy@<DROPLET_IP>`) and use `sudo` for privileged commands.

## 3. Installing the Stack

```bash
# Nginx
sudo apt install -y nginx

# PHP 8.2-FPM + required extensions (matching this app's actual usage —
# confirmed via full-codebase audit: pdo_mysql, mbstring, fileinfo,
# openssl. gd/curl/zip are NOT required — this app doesn't use them.)
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-common

# MariaDB
sudo apt install -y mariadb-server
sudo mysql_secure_installation
#   Set a strong root password when prompted.
#   Answer Y to every other prompt (remove anonymous users, disallow
#   remote root login, remove test database, reload privileges).

# Certbot (Let's Encrypt) — installed now, run after the Nginx server
# block below is live and the domain's DNS is pointed at this droplet
sudo apt install -y certbot python3-certbot-nginx
```

Verify PHP-FPM and CLI are the same version (avoids the classic "web works, cron doesn't" mismatch that plagued cPanel-style hosting):

```bash
php -v          # CLI
php-fpm8.2 -v   # FPM — must match
```

## 4. Database Setup

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE komagin_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'komagin_app'@'localhost' IDENTIFIED BY '<STRONG_RANDOM_PASSWORD>';
GRANT ALL PRIVILEGES ON komagin_hr.* TO 'komagin_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Use a dedicated, non-root application database user (`komagin_app` above) — never point the production app at MariaDB's `root` account, unlike this local dev environment's convenience setup.

## 5. Deploying the Application Code

```bash
sudo mkdir -p /var/www/komagin-hr
sudo chown deploy:deploy /var/www/komagin-hr
cd /var/www/komagin-hr

# Clone the repository (adjust the URL/branch to your actual remote)
git clone <YOUR_REPOSITORY_URL> .
git checkout main   # or whichever branch is your production branch

# Folder ownership: PHP-FPM runs as www-data by default on Ubuntu.
# The app writes to uploads/ and logs/ at runtime — those need
# www-data ownership; the rest of the codebase does not need to be
# writable by the web server at all.
sudo chown -R deploy:www-data /var/www/komagin-hr
sudo chmod -R 750 /var/www/komagin-hr
sudo chown -R www-data:www-data /var/www/komagin-hr/uploads /var/www/komagin-hr/logs
sudo chmod -R 770 /var/www/komagin-hr/uploads /var/www/komagin-hr/logs
```

## 6. Environment Configuration

Phase 6, Stage 6.1 made `config/config.php` read its key settings from real server environment variables via `getenv()`, rather than hardcoded constants — this is where that pays off. Set them in the **PHP-FPM pool config**, not a `.env` file (this app has no `.env` parser — see `.env.example`'s own comment: "set these as server environment variables... read via `getenv()`").

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Add near the bottom (adjust every value for your real deployment):

```ini
; Komagin HR production environment — Phase 6, Stage 6.2
env[APP_ENV] = production
env[APP_URL] = https://hr.yourdomain.com
env[DB_HOST] = localhost
env[DB_NAME] = komagin_hr
env[DB_USER] = komagin_app
env[DB_PASS] = <THE_STRONG_RANDOM_PASSWORD_FROM_STEP_4>
env[APP_TIMEZONE] = Pacific/Port_Moresby
```

**Critical**: PHP-FPM pool `env[]` directives are only honored if `clear_env` is **not** set to `On` for the pool (Ubuntu's default `www.conf` usually has `; clear_env = no` commented out, which means the default IS `clear_env = yes` in some PHP-FPM builds — verify explicitly):

```bash
grep -n "clear_env" /etc/php/8.2/fpm/pool.d/www.conf
```

If it says `clear_env = yes` (or is absent, which PHP-FPM treats as `yes` by default in some distro builds), add or change it to:

```ini
clear_env = no
```

Then restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

## 7. Nginx Server Block — Translating Every `.htaccess` Protection

**This is the single most important step in this guide.** Nginx does not read `.htaccess` files at all — every one of this app's 10 existing `.htaccess` files (blocking PHP execution in `uploads/`, denying access to `config/`/`database/`/`cron/`/`logs/`, security headers) becomes silently inert the moment the app runs behind Nginx, unless every rule is re-expressed here.

```bash
sudo nano /etc/nginx/sites-available/komagin-hr
```

```nginx
# Redirect HTTP to HTTPS — the production-safe place for this (see Phase
# 6 Stage 6.1's note on why this was NOT added at the application layer)
server {
    listen 80;
    listen [::]:80;
    server_name hr.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name hr.yourdomain.com;

    root /var/www/komagin-hr;
    index index.php;

    # Certbot will insert the ssl_certificate/ssl_certificate_key lines
    # here automatically when you run `certbot --nginx` in §8 — leave
    # this block as-is until then.

    client_max_body_size 10M;  # matches MAX_FILE_SIZE (10485760 bytes)

    # ── Deny access entirely to non-web-facing directories ──────────
    # Translates config/.htaccess, database/.htaccess, cron/.htaccess,
    # logs/.htaccess (all "Deny from all") into Nginx equivalents.
    location ~ ^/(config|database|logs|cron)/ {
        deny all;
        return 403;
    }

    # ── Block PHP execution inside uploads/ and its asset subfolders ──
    # Translates uploads/.htaccess and uploads/{letterheads,signatures,
    # stamps,watermarks}/.htaccess. This is a REAL security control
    # (prevents an uploaded file from ever being executed as PHP even
    # if it somehow got a .php-adjacent extension past upload
    # validation) — not paperwork.
    location ~ ^/uploads/.*\.(php|php3|php4|php5|phtml|pl|py|cgi|sh|rb|asp|aspx|jsp)$ {
        deny all;
        return 403;
    }

    # uploads/.htaccess also forces download (Content-Disposition) for
    # office-document types, so a browser doesn't try to render/execute
    # them inline
    location ~ ^/uploads/.*\.(pdf|doc|docx|xls|xlsx|csv)$ {
        add_header Content-Disposition "attachment";
    }

    # No directory listing anywhere (translates every "Options -Indexes")
    autoindex off;

    # ── Security headers (translates the root .htaccess's mod_headers
    # block) ─────────────────────────────────────────────────────────
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=15768000; includeSubDomains" always;
    # Content-Security-Policy: kept as Report-Only here too, matching
    # Phase 6 Stage 6.1's decision — see that stage's report for why.
    # Switch to the enforcing header (remove "-Report-Only") only after
    # confirming zero browser-console violations against real traffic
    # on this actual domain.
    add_header Content-Security-Policy-Report-Only "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'" always;

    # ── Static asset caching (translates the mod_expires block) ──────
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff2|woff)$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800, immutable";
        access_log off;
    }

    # ── Standard PHP-FPM routing ──────────────────────────────────────
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    # Deny access to dotfiles (.env, .git, etc.) generally
    location ~ /\. {
        deny all;
    }

    gzip on;
    gzip_types text/html text/plain text/css application/javascript application/json image/svg+xml;
}
```

Enable the site and test the config before reloading:

```bash
sudo ln -s /etc/nginx/sites-available/komagin-hr /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

**Verification before moving on** (from your own machine, or `curl` on the droplet):

```bash
curl -I http://hr.yourdomain.com/config/config.php     # expect 403
curl -I http://hr.yourdomain.com/database/schema.sql   # expect 403
curl -I http://hr.yourdomain.com/logs/                 # expect 403
curl -I http://hr.yourdomain.com/uploads/letterheads/anything.php  # expect 403 (once uploads/ exists)
```

Do not proceed to §9 (running the installer) until all four return 403.

## 8. SSL via Let's Encrypt

Only after DNS for `hr.yourdomain.com` is actually pointed at this droplet's IP (verify with `dig hr.yourdomain.com` from your own machine):

```bash
sudo certbot --nginx -d hr.yourdomain.com
```

Certbot will edit the Nginx server block automatically (inserting the `ssl_certificate` lines) and offer to set up the HTTP→HTTPS redirect — since this guide's server block (§7) already includes that redirect explicitly, you can decline Certbot's offer to add a duplicate one, or let it manage it; either is fine as long as there's exactly one redirect block, not two conflicting ones.

Certbot's systemd timer auto-renews the certificate — verify it's active:

```bash
sudo systemctl status certbot.timer
sudo certbot renew --dry-run
```

## 9. Running the Database Installer

**Only after §7's verification (the deny rules) is confirmed working** — running the installer while `database/`/`config/` are still web-accessible would be a real exposure window.

Visit `https://hr.yourdomain.com/database/install.php` in a browser, fill in the MySQL details from §4 (`komagin_app` / the password you set / `komagin_hr`), and submit. See `database/README.md` for exactly what the install sequence does.

**Immediately after installation succeeds:**

```bash
# Delete or restrict the installer — it accepts arbitrary DB connection
# details via POST and writes them into config/config.php
sudo rm /var/www/komagin-hr/database/install.php
```

Then log in as `superadmin` / `Admin@123` and **change the password immediately** — the app forces this on first login, but don't skip it.

## 10. Cron / Scheduler Setup

`cron/README.md` already covers the mechanism in detail (lock-based single entry point, failure isolation). Droplet-specific setup:

```bash
sudo crontab -u www-data -e
```

Add:

```cron
*/15 * * * * /usr/bin/php /var/www/komagin-hr/cron/run.php >> /var/www/komagin-hr/logs/cron.log 2>&1
```

**The `>> logfile 2>&1` redirect is not optional** — without it, standard Linux cron mails the crontab owner (`www-data`, which has no mail delivery configured on a fresh droplet) the full stdout of every single invocation. At a 15-minute cadence that's 96 attempted mail deliveries/day; redirecting to a log file instead is the correct approach either way.

Add log rotation so `logs/cron.log` doesn't grow unbounded:

```bash
sudo nano /etc/logrotate.d/komagin-cron
```

```
/var/www/komagin-hr/logs/cron.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
}
```

Verify the scheduler runs correctly and is blocked over the web:

```bash
sudo -u www-data php /var/www/komagin-hr/cron/run.php   # should print "Scheduler run finished"
curl -I https://hr.yourdomain.com/cron/run.php            # expect 403
```

## 11. Post-Deployment Checklist

Run through this in order, every deployment:

- [ ] `config/`, `database/`, `logs/`, `cron/` all return 403 over HTTP/HTTPS (§7 verification)
- [ ] `uploads/**/*.php`-style paths return 403
- [ ] `database/install.php` deleted (§9)
- [ ] `superadmin`/`Admin@123` password changed
- [ ] SSL certificate valid, HTTP→HTTPS redirect confirmed working (`curl -I http://...` returns a `301` to `https://...`)
- [ ] `php-fpm` pool `env[]` values all correct (`APP_ENV=production`, `APP_URL` matches the real domain, DB credentials point at the dedicated `komagin_app` user, not root)
- [ ] Cron entry installed under `www-data`, output redirected to a log file, log rotation configured
- [ ] `uploads/` and `logs/` owned by `www-data`, mode 770; everything else owned by `deploy:www-data`, mode 750
- [ ] `ufw` firewall active, only SSH/HTTP/HTTPS open
- [ ] Root SSH login and password auth disabled (§2)
- [ ] Company settings (name, logo, SMTP) configured via the app's own Settings pages — not this guide's scope, but don't go live without them
- [ ] A full backup taken immediately after go-live (see `Phase6/06-backup-disaster-recovery-guide.md`)

## 12. Rollback Procedure

If a deployment introduces a regression:

```bash
cd /var/www/komagin-hr
git log --oneline -10          # find the last known-good commit
git checkout <LAST_GOOD_COMMIT>
sudo systemctl reload php8.2-fpm
```

If the issue is database-related (a bad migration), restore from the most recent backup **before** the problematic change — see the Backup & Disaster Recovery Guide (Stage 6.6) for the exact restore commands. Never attempt to "undo" a migration by manually reverse-engineering `DROP`/`ALTER` statements; restore from a known-good backup instead.
