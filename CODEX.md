# NutriMente — Project Context for Codex

## What is NutriMente
NutriMente is a practice management SaaS for Italian healthcare professionals
(nutritionists, psychologists, osteopaths). It is a multi-user platform for a
single studio (Centro NutriMente — Studio Associato) that handles:
- Patient registry with GDPR-compliant consent tracking
- Appointment scheduling with a shared team calendar
- Clinical reports (referti) with customisable templates
- Italian-compliant invoicing (partita IVA, codice fiscale, marca da bollo, STS/Sistema Tessera Sanitaria export)
- Document repository (S3-backed) and compilable document templates
- Peer consultation sessions (intervisioni) with multi-professional participation and Google Meet link
- Internal team chat with team channels and direct messages
- Public booking pages (`/prenota/{slug}`) for patients to request appointments
- Patient-facing portal (`/mia-area`)
- Social content calendar, workspace task list, in-app notifications

---

## Tech Stack
| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.4), Jetstream 5.5, Sanctum, Spatie Permission 7.3, DomPDF 3.1 |
| Frontend | Vue 3 + Inertia.js 2.0, Tailwind CSS 3.4, Vite 8, FullCalendar v6, Axios |
| Database | SQLite (local dev) / PostgreSQL via Supabase (production) |
| File storage | Local disk (dev) / AWS S3 via Flysystem (production) |
| Queue | Database driver |
| Auth | Jetstream session-based + Sanctum (API); roles via Spatie RBAC |
| Locale | Italian — `APP_LOCALE=it`; `lang/it/` contains `auth.php`, `passwords.php`, `validation.php` |

---

## Directory Layout
```
app/
  Http/Controllers/        # ~20 controllers, one per feature
  Models/                  # 24 Eloquent models
  Mail/                    # BookingConfirmedMail, BookingRequestMail
  Policies/                # TeamPolicy
database/
  migrations/              # 49 migrations, date-prefixed 2026_*
  seeders/
resources/
  js/Pages/                # Vue 3 page components (Inertia)
    Auth/                  # 7 Jetstream auth pages (all translated to Italian)
    Appointments/  Calendar/  Documents/  Gdpr/  Intervisioni/
    Invoices/  Messages/  Patients/  Reports/  Social/  Workspace/
    Professionals/  Booking/  PatientPortal/
  views/
    pdf/                   # Blade templates for DomPDF (report.blade.php, invoice.blade.php)
lang/it/                   # auth.php, passwords.php, validation.php
routes/
  web.php                  # all application routes
  api.php                  # GET /api/user only
public/
  build/                   # Vite output (hashed assets, auto-generated)
storage/app/private/
  gdpr/                    # drop "Consenso Informato.pdf" and "Privacy Policy.pdf" here
  documents/               # patient documents
```

---

## Key Models and Relationships
| Model | Table | Key relations / notes |
|---|---|---|
| User | users | hasOne ProfessionalProfile; belongsToMany via Jetstream teams |
| ProfessionalProfile | professional_profiles | belongsTo User; holds slug, specialization, pricing, partita_iva, albo_professionale, curriculum JSON |
| Patient | patients | hasMany Appointment, Invoice, Report, Document, PatientConsent, PatientRecord |
| Appointment | appointments | belongsTo User, Patient; nullable intervisione_id |
| AvailabilitySlot | availability_slots | belongsTo User |
| Intervisione | intervisioni | belongsTo User (creator), Patient; belongsToMany User (participants); has `meet_link` nullable string |
| Invoice | invoices | belongsTo User, Patient; hasMany InvoiceLine |
| Report | reports | belongsTo User, Patient, ReportTemplate; `sections_data` JSON |
| ReportTemplate | report_templates | belongsTo User; `sections` JSON |
| Document | documents | belongsTo Patient; stored on configured disk |
| DocTemplate | doc_templates | compilable form templates with section schemas |
| Message | messages | belongsTo User (sender); routed by `channel_type` + `channel_id` |
| PatientConsent | patient_consents | belongsTo Patient, User; type: `gdpr` / `trattamento_dati` / `consenso_informato` |
| BookingRequest | booking_requests | public/unauthenticated; linked to ProfessionalProfile |
| Notification | notifications | in-app notifications; `type`, `data` JSON, `read_at` |
| AuditLog | audit_logs | compliance change tracking |

---

## Chat System
The chat was refactored from `setInterval` polling to **long polling**.

**Channel model:**
- `channel_type`: `"team"` or `"direct"`
- `channel_id`: for team → one of `general | psicologo | nutrizionista | osteopata`; for direct → `dm_{min_user_id}_{max_user_id}` (smaller ID always first)

**`channel_reads` table:** `(user_id, channel_type, channel_id, last_read_at)` — tracks when each user last opened a channel; used to compute unread badge counts.

**Long-poll endpoint** `GET /messages/poll?channel_type&channel_id&last_id&last_unread_check`:
- Holds connection open max 25s (`set_time_limit(30)`), checks every 500ms
- Breaks early when: new message with `id > last_id` exists in active channel, OR any message from others arrived after `last_unread_check`
- Returns `{ messages[], unread_counts{channel_id: count}, unread_dm_counts{user_id: count} }`
- `unread_dm_counts` is always present (including on timeout) so DM badges update without the recipient opening the DM

**Frontend (`Messages/Index.vue`):**
- Single `poll()` async function using `AbortController`
- Recurses immediately on response; retries after 2s on error
- Stopped on unmount via `pollStopped` flag
- `lastId` ref tracks highest known message ID; `lastUnreadCheck` tracks last unread refresh timestamp
- Channel switch: aborts in-flight poll, awaits `loadMessages()`, restarts poll

---

## Routes Summary

**Public (no auth):**
```
GET  /prenota                           public professional listing
GET  /prenota/{slug}                    professional booking page
POST /prenota/{slug}                    submit booking request
GET  /prenota/{slug}/conferma/{token}   confirm appointment
GET  /prenota/{slug}/rifiuta/{token}    reject appointment
```

**Authenticated (`auth:sanctum` + Jetstream session + `verified`):**
```
GET       /dashboard
CRUD      /patients                     patient registry
GET       /patients/{patient}/records   clinical records
CRUD      /appointments                 appointment management
GET       /calendar                     FullCalendar shared view
CRUD      /intervisioni                 peer consultations (with meet_link)
CRUD      /invoices                     invoicing + PDF + STS export
CRUD      /reports, /report-templates   clinical reports
CRUD      /documents, /doc-templates    document repository + templates
GET       /gdpr                         GDPR info page
GET       /gdpr/download/{document}     serve PDF (consenso-informato | privacy-policy)
GET/POST  /messages                     team chat
GET       /messages/load                load channel history (JSON)
GET       /messages/poll                long-poll endpoint (JSON)
POST      /messages/read-channel        mark channel as read
GET       /messages/unread              unread counts (JSON)
DELETE    /messages/{message}           soft-delete own message
GET       /workspace                    task list
CRUD      /tasks
GET/POST  /social                       content calendar
GET       /notifications
GET       /team/professionals           professional profiles admin
GET       /team/professionals/{user}/slots  availability management
GET       /mia-area                     patient portal
GET       /reports/{report}/pdf         DomPDF download
GET       /invoices/{invoice}/pdf       DomPDF download
POST      /invoices/{invoice}/sts       STS export
```

---

## Deployment

The app is deployed on **Railway** with **auto-deploy on push to `main`**.

### nixpacks.toml (Railway — primary)
```toml
[phases.setup]
nixPkgs = ["nodejs_22", "npm-10_x", "php84", "php84Packages.composer"]

[phases.install]
cmds = ["npm install --no-audit --no-fund", "composer install --optimize-autoloader --no-scripts --no-interaction"]

[phases.build]
cmds = ["npm run build", "mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache && chmod -R a+rw storage"]

[start]
cmd = "php artisan config:cache && php artisan route:cache && php artisan view:cache && /start-container.sh"
```

### Dockerfile (alternative, manual Docker builds)
- Base: `php:8.4-fpm-alpine` + nginx on port **10000**
- nginx serves `/public`; PHP-FPM on `127.0.0.1:9000`
- `start.sh`: php-fpm → `config:clear` → `cache:clear` → `migrate --force` → scheduler loop → nginx

### Production environment variables (set in Railway dashboard)
```
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=it
APP_FALLBACK_LOCALE=it
DB_CONNECTION=pgsql        # Supabase PostgreSQL
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
MAIL_MAILER=smtp           # configured SMTP for transactional email
```

### Local development
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve        # backend on :8000
npm run dev              # Vite HMR on :5173
```
Local defaults: `DB_CONNECTION=sqlite`, `FILESYSTEM_DISK=local`, `MAIL_MAILER=log`.

---

## Conventions to Follow

- **All user-facing strings must be in Italian.** Backend errors come from `lang/it/` — never hardcode English messages.
- **Form submissions use Inertia `useForm`** (not raw axios), unless the endpoint returns JSON only (e.g. calendar events, chat).
- **New pages**: use `AppLayout` with `<template #header>` for the page title bar. Match existing purple/teal colour conventions.
- **New DB columns**: create a migration in `database/migrations/` with date prefix `2026_MM_DD_NNNNNN_*.php`. Run `php artisan migrate`.
- **Professional filtering**: always use `whereHas('professionalProfile')`, never `whereHas('roles')`, to identify healthcare professionals.
- **PDF generation**: `Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.template', [...])` — Blade templates live in `resources/views/pdf/`.
- **File storage**: always use `Storage::disk(config('filesystems.default'))` to respect the environment (local vs S3).
- **DM channel IDs**: always built as `dm_{min_id}_{max_id}` — use the existing `dmChannelId(a, b)` helper in `MessageController` or the `dmId(otherId)` function in `Messages/Index.vue`.
- **Do not** use `whereHas('roles')` to filter professionals.
- **Do not** add English-language labels, buttons, or error messages in Vue components.
