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
git clone <this repo>
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

## Roles

| Role   | Can do |
|--------|--------|
| admin  | Everything: articles, categories, media, menu, users and settings. |
| editor | Create and edit articles, categories, media and menu. No access to users or settings. |

## License

MIT.
