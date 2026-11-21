# TNTJ Thanjai Vadakku — tvtntj.net (PHP + MySQL)

Modern rebuild of https://www.tvtntj.net (currently a Blogger site framed at that domain) with:

* **Public website** — Tamil, mobile-first, server-rendered (SEO friendly), the 14-item menu.
* **Admin panel** at `/admin` — manage every section, approve public submissions, upload files, users, API tokens, site settings.
* **REST API** at `/api/v1` — **every endpoint requires `Authorization: Bearer <token>`** (only `POST /auth/login` is open, because that is where an admin gets a token).
* **MySQL / MariaDB** database, seeded with the real content of the live site (district executives, 42 daees, 34 branches with 176 officers, Maktab downloads, monthly reports, latest 300 posts).

No Composer or Node needed in production — plain PHP 8.1+ with PDO MySQL. Runs on any cPanel / shared host.

## Menu → URL

| Menu | URL | Admin module |
|---|---|---|
| 🏠 முகப்பு | `/` | (news, events, blood requests, latest report) |
| 📖 கொள்கை | `/policy` | கொள்கை + பக்கங்கள் (`policy`) |
| 👥 மாவட்ட நிர்வாகிகள் | `/executives` | மாவட்ட நிர்வாகிகள் |
| 👥 மாவட்ட தாயிக்கள் | `/daees` | மாவட்ட தாயிக்கள் (ஆண் / பெண் filter) |
| 🏢 கிளை நிர்வாகிகள் | `/branches`, `/branches/{id}` | கிளைகள் + கிளை நிர்வாகிகள் |
| 🏫 பெண்கள் இஸ்லாமிய கல்லூரிகள் | `/colleges` | பெண்கள் இஸ்லாமிய கல்லூரிகள் |
| 🕌 மக்தப் மதரஸா | `/maktab` | மக்தப் மதரஸா + downloads in “மக்தப்…” categories |
| 🩸 இரத்ததானம் தேவை & கொடையாளர் | `/blood` | இரத்தம் தேவை, இரத்த கொடையாளர்கள் (public forms → pending) |
| 💍 திருமண தகவல் | `/matrimony` | திருமண தகவல் (public form → pending; phone hidden unless enabled) |
| 📂 படிவங்கள் & தரவிறக்கம் | `/downloads` | படிவங்கள் & தரவிறக்கம் (download counter) |
| 🗓️ மாவட்ட நிகழ்வுகள் | `/events` | மாவட்ட நிகழ்வுகள் |
| 📊 மாதாந்திர புள்ளி பட்டியல் | `/monthly-reports` | மாதாந்திர புள்ளி பட்டியல் |
| 📞 தொடர்புக்கு | `/contact` | தொடர்பு செய்திகள் + தள அமைப்புகள் |
| 🌐 அதிகாரப்பூர்வ இணைப்புகள் | `/links` | அதிகாரப்பூர்வ இணைப்புகள் |

Plus `/news` (search, pagination) and `/news/{slug}` for all posts.

## Languages (தமிழ் · English · العربية)

Tamil is the default. Visitors switch with the தமிழ் / English / العربية links in the header (or the phone menu); the choice is kept in a `lang` cookie for a year, and any page can be opened in a language with `?lang=en` or `?lang=ar`. Arabic pages are laid out right to left.

* **Interface text** (menus, buttons, forms, messages, activity names, roles, branch names) lives in `app/lang/en.php` and `app/lang/ar.php`, keyed by the Tamil wording used in the templates. Any text without a translation simply shows in Tamil.
* **Content typed in the admin panel** (policy points, pages, or any other text) is translated in **Admin → மொழிபெயர்ப்புகள்**: paste the exact Tamil text, choose `en` or `ar`, and enter the translation. The policy and about pages are already translated (`database/migrations/003_translations_policy.sql`).
* News posts and people's names stay in Tamil. In English and Arabic, branch posts are titled from their translated activity and branch (for example “Blackboard dawah by Valuthur branch”).

Existing installs: run `database/migrations/002_translations.sql` and then `003_translations_policy.sql`.

## Project layout

```
app/
  bootstrap.php        config, autoloader, helpers (escaping, CSRF, dates, t() translation)
  I18n.php             language choice + lookup (files, then the translations table)
  lang/                en.php, ar.php interface text; names.php (names shown in Latin script in Arabic)
  Core/                DB (PDO), Router, Auth (bearer tokens), HttpException
  Resources.php        schema of every admin module — drives both the API and the admin UI
  Controllers/Api.php  REST API
  Controllers/Site.php public pages + public form handlers
  views/               layout + page templates
config/config.example.php   copy to config/config.php
database/schema.sql, seed.sql, migrations/
public/                web root (index.php, .htaccess, assets/, admin/, uploads/)
scripts/
  create_admin.php     create / reset an admin user
  import_blogger.php   import ALL ~7,300 posts from tvtntj.blogspot.com (re-runnable)
  retag_news.php       re-derive each post's activity (கரும்பலகை தாவா…) and branch from its title
  build_seed.js        how seed.sql was generated from the live site
legacy-prototype/      the earlier static HTML/Node prototype (not used)
```

## Install (production, cPanel or VPS)

To run it behind **Netlify** on a Docker host (Railway, Render…), see [DEPLOY.md](DEPLOY.md).

1. Create a MySQL 8 / MariaDB 10.6+ database and user (utf8mb4).
2. Upload the project. Point the domain's **document root to `public/`** (on cPanel: set the domain's root to `.../public`, or upload `public/*` into `public_html` and the rest one level above it).
3. `cp config/config.example.php config/config.php` and fill in DB details and `app_url`. Keep `debug => false`.
4. Import the database:
   ```bash
   mysql -u USER -p DBNAME < database/schema.sql
   ```
   ```bash
   mysql -u USER -p DBNAME < database/seed.sql
   ```
5. Create the first super admin (asks for the password interactively):
   ```bash
   php scripts/create_admin.php "Admin Name" admin@example.com superadmin
   ```
6. Optional — bring over every old Blogger post (seed already has the newest 300):
   ```bash
   php scripts/import_blogger.php
   ```
7. Make `public/uploads/` writable by PHP (`chmod 755`, owned by the web user).
8. Apache: `public/.htaccess` handles routing and forwards the `Authorization` header. Nginx: `try_files $uri /index.php?$query_string;` and keep `fastcgi_param HTTP_AUTHORIZATION $http_authorization;`.

Local development:
```bash
php -S localhost:8080 -t public public/index.php
```

## API

Base: `/api/v1`. JSON in, JSON out. All responses use UTF-8 Tamil.

**Get a token**
```bash
curl -X POST https://www.tvtntj.net/api/v1/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@example.com","password":"…"}'
```
→ `{"token_type":"Bearer","access_token":"…","expires_in":43200,"user":{…}}`

Then send `Authorization: Bearer <access_token>` on every call.

| Method | Path | Ability |
|---|---|---|
| POST | `/auth/logout`, GET `/auth/me` | any token |
| GET | `/meta` (module schemas + branches) | read |
| GET | `/{resource}?q=&page=&per_page=&sort=-field&<field>=<value>&pending=1` | read* |
| GET | `/{resource}/{id}` | read* |
| POST / PUT / PATCH / DELETE | `/{resource}[/{id}]` | write |
| POST | `/uploads` (multipart `file`; jpg/png/webp/gif/pdf/doc/xls) | write |
| GET / PUT | `/settings` | write |
| GET | `/dashboard` | write |
| GET / POST / PUT / DELETE | `/users[/{id}]` | super admin |
| GET / POST / DELETE | `/tokens[/{id}]` | super admin |

`{resource}` = `news, events, monthly-reports, downloads, policy-points, executives, daees, branches, branch-officers, colleges, maktabs, blood-requests, blood-donors, matrimony, links, pages, contact-messages`.

\* **read-only tokens** (create them in Admin → API Tokens, e.g. for a mobile app) only see published / approved rows of public modules; private fields (matrimony contact details unless “show phone” is on) are stripped, and `contact-messages` / `pages` are not readable. Admin login tokens have `read,write` and expire after `token_ttl_hours`.

Errors: `401` missing/invalid/expired token, `403` ability missing, `404`, `422` with `{"errors":{"field":"message"}}`, `429` too many login attempts (8 per 15 min per IP).

## Security notes

* Tokens are 40 random bytes; only their SHA-256 hash is stored. Logout, password change and user deactivation revoke tokens.
* Passwords use `password_hash()` (bcrypt/argon, auto-rehash).
* All SQL is prepared; column names come only from the `Resources` whitelist.
* Public forms: CSRF token, honeypot field, per-session rate limit; submissions are stored **unapproved** until an admin approves them.
* Uploads: extension whitelist, image validation, random file names, scripts blocked in `uploads/.htaccess`.
* Rich text (news, pages) is sanitised (scripts, iframes, event handlers, `javascript:` links removed).

## Still to fill in through the admin panel

The live site has no data for these, so they start empty (each page shows a friendly “coming soon” state): **பெண்கள் இஸ்லாமிய கல்லூரிகள், மக்தப் மதரஸா list, இரத்த கொடையாளர்கள், திருமண தகவல், மாவட்ட நிகழ்வுகள்**. Also set the office **email, address and Google-Map link** under Admin → தள அமைப்புகள், and upload photos for executives / daees if wanted.
