# Deploying: PHP host + Netlify

Netlify only serves static files, so the PHP + MySQL site runs on a PHP host, and Netlify sits in front of it: visitors use the Netlify address (or your own domain on Netlify), and Netlify forwards every request to the PHP host. Pages, the admin panel, the API, forms and language switching all keep working.

```
visitor ──▶ Netlify (your domain, HTTPS) ──▶ PHP host (this repo, Docker) ──▶ MySQL
```

## 1. PHP host (example: Railway)

Any host that runs a Docker image and offers MySQL works (Railway, Render, Fly.io, a VPS). Steps for Railway:

1. **New project → Deploy from GitHub repo →** `RajaDevIos/TNTJ`. Railway finds the `Dockerfile` and builds it.
2. **Add a MySQL database** to the same project (**New → Database → MySQL**).
3. In the web service, open **Variables** and add:

   | Variable | Value |
   |---|---|
   | `MYSQL_URL` | `${{MySQL.MYSQL_URL}}` (reference to the database) |
   | `APP_URL` | your Netlify address, e.g. `https://tntj.netlify.app` (later your own domain) |
   | `TRUST_PROXY` | `1` |
   | `ADMIN_NAME` | name of the first admin |
   | `ADMIN_EMAIL` | email of the first admin |
   | `ADMIN_PASSWORD` | a strong password (8+ characters) |

4. **Add a volume** to the web service mounted at `/var/www/html/public/uploads` so uploaded photos and PDFs survive redeploys.
5. **Settings → Networking → Generate domain.** Copy it, e.g. `https://tntj-production.up.railway.app`.

On every start the container creates the tables, loads the live site's content (first start only), applies migrations, and creates the first admin if there is none yet. After the first successful start you can delete `ADMIN_PASSWORD` from the variables.

## 2. Netlify

1. In `netlify.toml`, replace `BACKEND_URL` with the address from step 1.5 (no trailing slash), then commit and push.
2. In Netlify: **Add new site → Import an existing project → GitHub →** `RajaDevIos/TNTJ`. Leave the build settings as they are (they come from `netlify.toml`) and deploy.
3. Optional: **Domain management → Add a domain** to use `www.tvtntj.net`, then set `APP_URL` on the PHP host to that domain.

## Other PHP hosts

* **Shared hosting / cPanel:** follow *Install* in `README.md` (upload, point the domain at `public/`, create `config/config.php`, import the SQL). Set `'trust_proxy' => true` in `config/config.php` if Netlify sits in front.
* **Environment variables** understood by the app are listed in `config/config.env.php` (`DATABASE_URL` or `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASS`, `APP_URL`, `APP_DEBUG`, `TRUST_PROXY`, `TOKEN_TTL_HOURS`, `UPLOAD_MAX_MB`, `CORS_ORIGINS`).

## Notes

* `TRUST_PROXY=1` makes the app read the visitor's real IP from Netlify's headers (used for login rate limits). Anyone who knows the PHP host's own address could reach it directly, so keep using the Netlify address publicly.
* Run the installer by hand at any time with `php scripts/install_db.php`; it is safe to re-run.
