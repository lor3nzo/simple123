# simple123

**Small platform. Big ideas.**

A minimal single-administrator Markdown blog built with PHP 8.2+ and MySQL, featuring Google OpenID Connect sign-in, scheduled publishing, image/PDF uploads, and customizable themes.

**Release:** v0.2.3 · **License:** [Apache License 2.0](LICENSE)

## Features

- Markdown editing, HTML-sanitized rendering, live preview and local draft recovery
- Draft, publish, or schedule posts; five-minute CLI cron supported
- Single-admin Google OpenID Connect authentication
- PNG/JPG/WebP/GIF image uploads, PDF embedding, YouTube embeds
- Six built-in palettes, four editable colors, up to five saved custom presets
- Administrator timezone selector (dates displayed locally, timestamps stored in UTC)
- Editable homepage, About page, footer, and GA4 fields (analytics off by default)
- RSS 2.0, XML sitemap, canonical links, export ZIP
- Optional decoy paths `/admin` and `/login` recording login *usernames only*, never passwords

## Requirements

- PHP 8.2+ with PDO MySQL, JSON, mbstring, fileinfo, OpenSSL, ZIP and cURL
- MySQL/MariaDB database, HTTPS, Composer 2, and a web server with rewrite routing
- A Google Cloud OAuth web client for the single administrator
- CLI cron scheduler for scheduled publishing

## Install

1. Create a MySQL database and least-privilege application account; import [`schema.sql`](schema.sql).
2. Run `composer install --no-dev --optimize-autoloader` in the application directory.
3. Copy `config.example.php` to **`config.php`** and set `base_url`, database credentials, Google OAuth client ID and secret, and the administrator email. **Never commit `config.php`.**
4. In Google Cloud configure your authorized callback as `https://example.com/auth/callback`, replacing `example.com` with your actual HTTPS base URL. For a subdirectory installation, include that path.
5. Deploy the source and Composer dependencies. Make `uploads/` and `storage/` writable as needed. Restrict configuration files and runtime directories at the **web-server layer**; `.htaccess` alone may not protect static files behind a proxy.
6. Visit `/porta` for the first Google sign-in and bind your account. Review `/settings` for timezone, theme and site title.
7. Configure a five-minute cron job calling `php /absolute/path/to/cron.php`, then test a scheduled post. The script refuses HTTP execution.
8. Test HTTPS, login/logout, public/draft visibility, uploads, RSS, sitemap, and backups before opening the site publicly.

## Hostname configuration

The source contains **no hardcoded production hostname**. Set your own HTTPS hostname in `config.php`. For a WWW-to-non-WWW redirect, see [`docs/CANONICAL_REDIRECT.md`](docs/CANONICAL_REDIRECT.md). `robots.txt` omits a host-specific sitemap line until you configure it: copy `robots.txt.example` over `robots.txt` **after replacing `example.com`** or configure the equivalent at your hosting provider.

## Security and privacy

- Do not push production configuration, OAuth secrets, database dumps, backups, SSH keys, `vendor/`, `storage/`, or `uploads/` to GitHub.
- Decoy login event logs may contain IP addresses, usernames, and user agents. Keep them private and define a retention policy.
- Media file URLs are public even if the post containing them is a draft. Do not upload confidential content.
- GA4 remains disabled unless you explicitly enable it and address applicable privacy/consent obligations.
- HSTS starts at five minutes on application HTTPS responses; expand only after verifying all applicable HTTPS endpoints.
- This is a self-hosted project. Follow [`SECURITY.md`](SECURITY.md) for vulnerability reporting.

## Running source checks

```bash
php -l index.php
php -l src/bootstrap.php
php -l src/timezone.php
php tests/test-v022.php
php tests/test-settings-atomicity.php
php tests/test-v023.php
```

These tests are not a replacement for end-to-end deployment checks.

## Licensing

Copyright belongs to the actual rights holder named in [`NOTICE`](NOTICE). Distributed under the Apache License, Version 2.0. Keep the license and applicable notices with redistributed copies.
