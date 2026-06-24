# Deploy NutriMente to Aruba Hosting Linux (Easy) — subfolder split

FTP-only, MySQL, no SSH / Composer / Node. You build locally and upload the
result. This folder contains the three files that make the docroot split work.

## Target layout on the server (this account)

The FTP root above the docroot is NOT writable on this account, so everything
lives INSIDE the docroot `www.saraalessandripsicologa.it/`. The framework folder
`nutrimente_app/` is placed there too and kept unreachable over HTTP by its own
deny-all `.htaccess`:

```
www.saraalessandripsicologa.it/              ← DOCROOT (the only writable place)
├── cgi-bin/                                 ← leave Aruba's folder as-is
├── ver.php                                  ← leave Aruba's file as-is (or delete)
├── index.php          ← REPLACE Aruba's with deploy/aruba/webroot/index.php
├── .htaccess          ← ADD deploy/aruba/webroot/.htaccess
├── favicon.ico  favicon.png  logo.jpeg  robots.txt
├── build/             ← the WHOLE contents of public/build (Vite assets)
└── nutrimente_app/                          ← framework, INSIDE the docroot
    ├── .htaccess      ← deploy/aruba/nutrimente_app/.htaccess (deny-all — REQUIRED)
    ├── .env           ← create on the server (production values)
    ├── app/  bootstrap/  config/  database/  routes/  resources/  lang/
    ├── storage/       ← must be writable (chmod 775)
    ├── vendor/        ← uploaded, not installed on server
    └── artisan  composer.json ...
```

The docroot `index.php` points to `__DIR__.'/nutrimente_app'`. Because that
folder is under the docroot, its deny-all `.htaccess` is what keeps `.env` and
all clinical data unreachable — make sure that file is uploaded. After going
live, verify by opening `https://www.saraalessandripsicologa.it/nutrimente_app/.env`
in a browser: it must return **403 Forbidden**.

## Steps

1. **Build locally** (so `vendor/` and `public/build/` are current):
   ```
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   ```

2. **Assemble the upload** from your local project:
   - Everything EXCEPT the `public/` folder  →  `nutrimente_app/`
   - The CONTENTS of `public/` (index.php, .htaccess, build/, favicon*, robots.txt, logo.jpeg)
     →  the web root
   - Do **not** upload: `node_modules/`, `.git/`, the `public/storage` symlink,
     `tests/`, `.env` from your machine.

3. **Drop in the three files from this folder:**
   - `deploy/aruba/webroot/index.php`      → replaces the web-root `index.php`
   - `deploy/aruba/webroot/.htaccess`      → web-root `.htaccess` (same as Laravel's)
   - `deploy/aruba/nutrimente_app/.htaccess` → inside `nutrimente_app/`

4. **Create `nutrimente_app/.env`** on the server (it is gitignored on purpose):
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain`
   - Keep the SAME `APP_KEY` as today (do NOT regenerate — breaks sessions / encrypted data)
   - `DB_CONNECTION=mysql` + Aruba MySQL host/db/user/pass, REMOVE `DB_SSLMODE`
   - `QUEUE_CONNECTION=sync`  (no queue worker on shared hosting; you have no queued jobs)
   - `SESSION_DRIVER=database`, `CACHE_STORE=database` (tables come from your DB import)
   - `MAIL_*` for your SMTP
   - `FILESYSTEM_DISK` — see note below

5. **Permissions** (via your FTP client): make `nutrimente_app/storage/` and
   `nutrimente_app/bootstrap/cache/` writable — `chmod -R 775`.

6. **Database — fresh start, Sara + templates only.** Aruba Easy = MySQL only,
   and you are NOT migrating patient data. So:
   a. Create the MySQL DB in the Aruba panel and put its credentials in `.env`.
   b. Build the empty schema + base roles. You have no shell, so either:
      - point a temporary local `.env` at the Aruba MySQL and run
        `php artisan migrate --seed` (the seed creates the Spatie roles), OR
      - run `php artisan schema:dump` locally and import the schema via
        phpMyAdmin, then run the RolesAndPermissionsSeeder equivalent.
      Either way you need the `roles` table populated (the bootstrap seed below
      looks up the `psicologo` role by name).
   c. Generate the bootstrap seed LOCALLY, while your .env still points at the
      current (Supabase) database:
      ```
      php deploy/aruba/export-seed.php          # email = psicoalessandri@gmail.com
      ```
      This writes `deploy/aruba/sara-seed.sql`, which creates Sara's account,
      her `psicologo` role, her professional profile, and the whole template
      library (questionnaire/report/doc templates) owned by her — using a
      `@sara` SQL variable, so no id juggling.
   d. Import `deploy/aruba/sara-seed.sql` via Aruba phpMyAdmin (AFTER step 6b).
      Nothing else (patients, reports, filled questionnaires, invoices,
      appointments) is migrated.

   Sara logs in with `psicoalessandri@gmail.com` and her EXISTING password (her
   hash is carried over); she can use "password reset" later if she wants.

7. **PHP version:** in the Aruba panel select **PHP 8.3+** and confirm these
   extensions are on: `pdo_mysql, mbstring, gd, dom/xml, bcmath, zip, fileinfo,
   curl, openssl` (DomPDF needs gd + dom + mbstring).

8. **Do NOT run `php artisan config:cache`** — there is no shell, and a cache
   built on your machine would bake in local absolute paths. Leave config uncached.

## File storage note (you are on `FILESYSTEM_DISK=local`)

Uploaded files live in `nutrimente_app/storage/app/`. Upload that folder's
real contents so existing files survive.

- If clinical files are served through controllers (with access checks) — the
  usual pattern — you need nothing else; the missing `public/storage` symlink
  is irrelevant.
- If you actually serve files via the *public* disk (`storage/app/public`), the
  FTP-uncreatable symlink is a problem. Either switch those to controller-served
  downloads, or copy that subfolder's contents into a real `storage/` folder in
  the web root. Verify before cutover.

## What you do NOT need to change

- App PHP code — portable Eloquent, no Postgres-specific SQL, JSON `array` casts
  work on MySQL.
- No queue worker — you have zero `ShouldQueue` jobs; mail sends inline.
- The only scheduled task (`notifications:upcoming-tasks`, daily 08:00) needs an
  Aruba "Operazione pianificata" running `php artisan schedule:run`. If Easy
  can't run a PHP script on a schedule, the app still works — those daily
  reminders just won't fire.
