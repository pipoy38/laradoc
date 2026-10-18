# Laradoc

A small, self-hosted documentation wiki built on Laravel 12. It gives you an admin area for writing
articles in nested categories with a rich text editor, and a fast public site with search, tags, an
XML sitemap and SEO metadata.

## Requirements

- PHP 8.2–8.5 with the `pdo_mysql`, `mbstring`, `gd`, `fileinfo` and `xml` extensions
- MySQL 8 or MariaDB 10.6+
- Composer
- Node.js (only if you need to rebuild the CSS/JS assets — a prebuilt `public/build` is committed)

## Quick start

```bash
git clone https://github.com/pipoy38/laradoc.git laradoc
cd laradoc
composer install --no-dev --optimize-autoloader
cp .env.example .env
# edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD (and APP_URL) for your MySQL database
php artisan key:generate
php artisan wiki:install
```

`wiki:install` runs the migrations, creates the first admin user, seeds a short "Welcome" article and
links the storage directory. Run it non-interactively by passing options or setting environment
variables, which is what a fresh server uses:

```bash
php artisan wiki:install --name="Admin" --username=admin --email=you@example.com --password='a-strong-password'
# or set ADMIN_NAME / ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env and run without options
```

The `ADMIN_*` variables are read from the environment, so they also work after `config:cache`.

It's safe to re-run: once an admin user and the sample article exist, `wiki:install` skips creating
them again and only re-links storage.

Point your web server's document root at the `public/` directory.

## Shared hosting notes (cPanel and similar)

Keep the application code outside `public_html` (e.g. in a sibling directory) and either symlink the
contents of `public/` into `public_html`, or copy them there if your host doesn't support symlinks.
A `.htaccess` is already included in `public/` for Apache. Make sure `storage/` and
`bootstrap/cache/` are writable by the web server user.

## Updating

```bash
git pull
./deploy.sh
```

`deploy.sh` installs dependencies, runs migrations, re-links storage and rebuilds the config/route/view
caches. It's safe to re-run.

### Moving content between sites

Use Admin → Transfer. *Export* downloads one zip with every category, article (status, tags, SEO fields)
and the images they use; *Import* uploads such a zip into another install. Articles and categories are
matched by their address (slug) and updated, nothing is deleted, and each imported article gets a fresh
revision. Users, settings, redirects and revision history are not included. For bulk imports from a docs
folder the `php artisan wiki:import-docs <dir>` command still exists.

## Going live

Before pointing real traffic at an install, check:

- `APP_ENV=production` and `APP_DEBUG=false` in `.env` (these are already the defaults in
  `.env.example`; don't flip them back for a live site — `APP_DEBUG=true` leaks stack traces).
- `APP_URL` is set to the real `https://` URL of the site.
- `SESSION_SECURE_COOKIE=true` in `.env` once the site is served over https.
- `storage/` and `bootstrap/cache/` are writable by the web server user.
- `php artisan db:seed` is **not** part of install — `wiki:install` seeds the sample article itself
  (see Quick start above); running `db:seed` is only for optionally re-adding the sample content later.
- Run `./deploy.sh` after every `git pull` to pick up migrations and rebuild caches.

## Admin URL

The admin area lives at `/admin` by default. You can change this to something less guessable from
**Settings › Admin URL** in the admin area. If you ever forget the current prefix (or lock yourself
out), run on the server:

```bash
php artisan wiki:admin-prefix         # show the current prefix
php artisan wiki:admin-prefix reset   # reset it back to /admin
```

On hosts that use `route:cache` (see `deploy.sh`), changing the admin prefix — from the settings page
or with `wiki:admin-prefix`— clears the route cache automatically so the change takes effect
immediately. Re-run `./deploy.sh` afterwards to rebuild that cache, or routing will be uncached until
you do.

## Email

Outgoing mail (used for the "send test email" button and any future notifications) is configured from
**Settings › Email** in the admin area — no `.env` editing required. Choose SMTP, `sendmail`, or the
`log` driver (writes mail to `storage/logs/laravel.log`, useful while testing). The default in
`.env.example` is `MAIL_MAILER=log`.

## Rebuilding assets

Only needed if you change CSS/JS in `resources/`:

```bash
npm ci
npm run build
```

Commit the resulting `public/build` directory — production installs run with `--no-dev` and don't run
Node.

## Development

```bash
php artisan serve --host=0.0.0.0 --port=9005
php artisan test
```

## Uploads

The media library accepts `jpg`, `png`, `gif` and `webp` images only. SVG uploads are rejected by
design (an SVG can carry executable script); convert logos and icons to PNG before uploading them in
**Settings › Branding**.

**Security:** uploaded files are re-typed by their sniffed MIME type, not by the extension in the
client-supplied filename — a file named `shell.php.html` that is actually a JPEG is stored as `.jpg`.
The client-supplied filename is only ever used for display (`original_name`) and is never used to
choose the extension the file is stored under.

## Roles

| Role   | Can do |
|--------|--------|
| admin  | Everything: articles, categories, media, menu, users and settings. |
| editor | Create and edit articles, categories, tags, media and their own profile. No access to the menu, users or settings. |

## License

Laradoc's own code is MIT. It bundles two third-party libraries under their own licences: the
TinyMCE rich text editor (GPL-2.0-or-later) and highlight.js (BSD-3-Clause).
