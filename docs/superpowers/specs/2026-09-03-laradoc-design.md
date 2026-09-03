# Laradoc — Design Spec

Date: 2026-09-03
Status: approved in conversation, pending written review

## What this is

Laradoc is a self-hosted documentation website in the spirit of Wiki.js: a public,
login-free reading side and a password-protected admin side where staff write
articles. It documents the VentaSync / Preyes ERP, so it borrows that
application's palette, but it is a generic product: nothing in it is ERP-specific.

It must deploy to any ordinary PHP web host reachable over SSH (shared hosting,
VPS, cPanel). The Raspberry Pi ("Buko PI", `/home/ubuntu/laradoc`) is only the
development sandbox. Nothing auto-starts there; the dev server is run on request.

## Constraints

- Laravel 12, pinned. Minimum PHP 8.2 (runs on 8.2–8.5).
- MySQL 8 / MariaDB 10.6+. Dev DB on the PI: `laradoc`, dedicated user `laradoc`.
- Few dependencies. Composer: `laravel/framework`, `laravel/tinker`, dev-only
  `pestphp/pest` and `laravel/pint`. No Livewire, Inertia, Breeze, Jetstream,
  Filament, or Spatie packages.
- Front-end: Blade + Tailwind CSS v4 + Alpine.js, built with Vite. Compiled
  assets in `public/build` are committed so a host never needs Node.
- Editor: TinyMCE 7 (open-source, self-hosted from `node_modules`, copied into
  `public/vendor/tinymce` by the Vite build). No API key, no CDN.
- Code highlighting: highlight.js, bundled.
- Fonts: system font stack. No web-font requests.
- GitHub: `pao2pao16/laradoc`, default branch `main`.

## Roles

| Role | Can |
|---|---|
| editor | Articles, categories, tags, media, own profile |
| admin | Everything an editor can, plus users, menu, settings |

There is no public login. Readers never authenticate.

## Data model

All tables have `id`, `created_at`, `updated_at` unless noted.

**users** — `name`, `username` (unique), `email` (unique), `password`,
`role` enum(`admin`,`editor`), `remember_token`. Standard
`password_reset_tokens` table.

**categories** — `parent_id` nullable FK self, `name`, `slug` (unique among
siblings), `description` nullable, `sort_order` int, `icon` nullable (emoji or
short text). Depth is unlimited in the model; the admin UI and sidebar render
any depth. Deleting a category that has children or articles is refused.

**articles** — `category_id` FK, `title`, `slug` (unique within category),
`excerpt` nullable text (shown in search and category index; auto-generated
from body when blank), `body_html` longtext, `body_text` longtext (stripped
copy used for fulltext search), `status` enum(`draft`,`published`),
`published_at` nullable, `sort_order` int, `seo_title` nullable,
`meta_description` nullable, `canonical_url` nullable, `noindex` bool,
`og_image_id` nullable FK media, `created_by` FK users, `updated_by` FK users.
Fulltext index on (`title`, `excerpt`, `body_text`).

**article_revisions** — `article_id` FK cascade, `user_id` FK nullable (null
if user deleted), `title`, `body_html`, `excerpt`, `seo_title`,
`meta_description`, `change_note` nullable, `created_at` only. One row per save,
including the first. Restore copies a revision into the article and writes a
new revision noted "Restored revision #N".

**tags** — `name`, `slug` unique. **article_tag** pivot.

**media** — `disk_path`, `original_name`, `mime`, `size`, `width`, `height`
nullable, `alt` nullable, `uploaded_by` FK users nullable. Files live in
`storage/app/public/media/YYYY/MM/`. Accepted: jpg, png, gif, webp, svg
(svg sanitised: script and foreignObject stripped). Max 5 MB.

**menu_items** — `label`, `url`, `new_tab` bool, `sort_order`.

**settings** — `key` unique, `value` text nullable. Keys:
`site_name`, `site_tagline`, `logo_media_id`, `favicon_media_id`,
`home_intro` (HTML shown on home page), `footer_text`,
`admin_prefix` (default `admin`),
`mail_mailer` (`smtp`|`sendmail`|`log`), `mail_host`, `mail_port`,
`mail_username`, `mail_password` (encrypted at rest with the app key),
`mail_encryption` (`tls`|`ssl`|none), `mail_from_address`, `mail_from_name`.
Settings are read through a `Settings` service that caches the whole table
and clears the cache on write.

**path_redirects** — `from_path` unique, `to_path`, `hits` int. Created
automatically when an article or category slug changes or an article moves
category. Front controller consults it on 404 and issues a 301.

## URLs

Front (public):

- `/` home: intro from settings, category cards, ten most recently updated
  published articles.
- `/{category/path...}` category index: description, child categories,
  articles in `sort_order`.
- `/{category/path...}/{article-slug}` article. Category path is the slugs
  from root to leaf joined with `/`.
- `/search?q=` results page. `/api/search?q=` JSON for the live dropdown
  (max 8 results). Search matches published articles only.
- `/tags/{tag}` articles with that tag.
- `/sitemap.xml`, `/robots.txt`.
- Route resolution order: reserved prefixes (`search`, `tags`, `api`,
  `sitemap.xml`, `robots.txt`, `storage`, the admin prefix) first, then
  category/article path match, then `path_redirects`, then 404.
- Draft articles are 404 to the public. A category with no published articles still renders, showing only its child categories.

Admin (prefix `{p}`, read from settings at boot, default `admin`):

- `/{p}/login`, `/{p}/logout`, `/{p}/forgot-password`,
  `/{p}/reset-password/{token}`.
- `/{p}` dashboard: article/category/user counts, last 10 revisions with
  author and time, drafts awaiting publish.
- `/{p}/articles` list with filters (status, category, tag, text), sort by
  updated. Create/edit/delete. Edit page tabs: Content, SEO, Revisions.
- `/{p}/articles/{id}/revisions/{rev}` view; POST restore.
- `/{p}/categories` tree view with create/edit/delete and up/down reorder.
- `/{p}/tags` CRUD.
- `/{p}/media` grid, upload, edit alt text, delete (refused if used as
  logo/favicon/og image), copy URL. `POST /{p}/media/upload` also serves
  TinyMCE image uploads (returns `{location}`).
- `/{p}/menu` CRUD with reorder. Admin only.
- `/{p}/users` CRUD. Admin only. An admin cannot delete themselves or demote
  the last admin.
- `/{p}/settings` tabs: General, Branding, Email (with "Send test email to
  me" button), Admin URL. Admin only.
- `/{p}/profile` change own name, email, password.

Changing the admin prefix: validated as `[a-z0-9-]{2,32}`, not in the
reserved list, not an existing top-level category slug. After save the user
is redirected to the new prefix. `php artisan wiki:admin-prefix` prints it;
`php artisan wiki:admin-prefix reset` sets it back to `admin`.

## Front-end interface

Layout on ≥1024px: header, then three columns — 280px left sidebar,
fluid centre (max 760px measure), 220px right rail. On <1024px the right rail
folds into a collapsible "On this page" block above the content; on <768px the
sidebar becomes a slide-in drawer opened from a header button.

- Header: logo (or site name if no logo), site name, menu items from
  `menu_items` (external links open in a new tab when flagged), search input,
  theme toggle.
- Sidebar: category tree. Categories are collapsible; the branch containing the
  current article is expanded; the current article is highlighted. Only
  published articles appear.
- Article: breadcrumbs (Home › Category › … › Article), H1, meta line
  ("Updated 3 Sep 2026 by Jane"), tags, content, prev/next within the
  category, footer.
- Right rail: "On this page" from H2/H3 in the body, with scroll-spy.
- Body rendering: headings get ids (slugified, de-duplicated) for anchors;
  `pre code` blocks get highlight.js and a Copy button; images are
  `loading="lazy"`; tables wrap in a horizontally scrolling container.
- Search box: typing ≥2 chars queries `/api/search` (debounced 250ms) and
  shows up to 8 results with category path; Enter goes to `/search`.
- Theme: toggle stores `theme` in `localStorage` (`light`|`dark`); absent
  means follow `prefers-color-scheme`. Front and admin each store their own
  key (`laradoc.theme.front`, `laradoc.theme.admin`) so they are independent.
  Applied by an inline script in `<head>` before paint to avoid a flash.
- SEO per article: `<title>` = seo_title or "{title} · {site_name}",
  meta description, canonical (custom or self), robots noindex when flagged,
  Open Graph title/description/type/url/image (og image or logo),
  Twitter card summary, JSON-LD `TechArticle` with headline, dateModified,
  author. Category and home pages get title/description/canonical/OG.
- 404 page in the site chrome with a search box.

## Admin interface

Same theme tokens, denser layout: fixed left nav (Dashboard, Articles,
Categories, Tags, Media, Menu*, Users*, Settings*; * admin only), top bar
with site name, "View site" link, theme toggle, user menu.

Article editor:

- Title (auto-fills slug until slug is edited by hand), slug, category
  (indented select), status, tags (comma-separated with existing-tag
  suggestions), change note.
- TinyMCE with toolbar: undo/redo, blocks (p, h2, h3, h4), bold, italic,
  underline, strike, code, link, image, blockquote, lists, table, codesample
  (with language picker), hr, source code view, fullscreen. Images upload to
  the media library and insert by URL. Relative links to other articles are
  allowed as typed.
- SEO tab: seo title (60-char counter), meta description (160-char counter),
  canonical, noindex, OG image picker from media, and a live "Google result"
  preview.
- Revisions tab: list (who, when, note), view any, restore with confirm.
- Save keeps the user on the edit page and flashes "Saved". Delete asks for
  confirmation and cascades revisions and tag links.

## Security

- Session auth, bcrypt, CSRF on all forms, `throttle:5,1` on login and
  password reset, remember-me.
- Article HTML sanitised on save: `<script>`, `<object>`, `<embed>`,
  `on*` attributes, and `javascript:` URLs are removed. `<iframe>` allowed only
  from youtube.com, youtube-nocookie.com, vimeo.com. Everything else is kept
  because authors are trusted staff.
- Uploads validated by MIME sniff, renamed to a random filename, served from
  `public/storage` symlink.
- Mail password encrypted with Laravel `Crypt`.
- Admin routes behind `auth` and `role` middleware; editors get 403 pages on
  admin-only routes and the nav hides those links.

## Email

Mail config is loaded from settings at runtime (a service provider overrides
`config('mail.*')` from the `settings` table on each request). Used for
forgot-password and the "send test email" button. If `mail_mailer` is `log`,
mail goes to the log file, which is the dev default.

## Console commands

- `wiki:install` — runs migrations, creates the first admin (prompts for name,
  username, email, password; reads `ADMIN_*` env vars when non-interactive),
  seeds the sample "Getting started" category/article and default settings,
  links storage. Idempotent.
- `wiki:admin-prefix [reset]`.

## Deployment

`README.md` documents: requirements, `composer install --no-dev`,
`.env` keys, `php artisan key:generate`, `wiki:install`, `storage:link`,
document root = `public`, and the `.htaccess` that ships with Laravel for
Apache hosts. `deploy.sh` runs the post-pull steps (composer install, migrate,
cache config/routes/views) for SSH hosts. On the PI, `php artisan serve
--host=0.0.0.0 --port=9005` is run manually when testing.

## Testing

Pest feature tests using an in-memory SQLite database (fulltext search test
uses a LIKE fallback that the search service applies when the driver is not
MySQL):

- login, logout, throttle, role gating of admin-only routes
- category CRUD, nested path resolution, delete refusal with children
- article CRUD, slug uniqueness, revision created on save, restore, redirect
  created on slug change, draft hidden from public and sitemap
- tag pages, media upload validation, menu rendering, settings save,
  admin prefix change and console reset
- sitemap and robots output, JSON search endpoint

Manual browser pass on the PI at port 9005 in both themes, mobile width, and
keyboard-only navigation of the admin editor.

## Out of scope

Comments, public accounts, multiple languages, Markdown editing, PDF export,
per-article permissions, analytics snippets, "was this helpful" widget.
