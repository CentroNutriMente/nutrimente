-- =============================================
-- Nutrimente – Supabase / PostgreSQL schema
-- Generated from Laravel migrations (in order)
-- teams = false, model_morph_key = 'model_id'
-- =============================================

-- ── users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id                        BIGSERIAL PRIMARY KEY,
    name                      VARCHAR(255) NOT NULL,
    email                     VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at         TIMESTAMP NULL,
    password                  VARCHAR(255) NOT NULL,
    two_factor_secret         TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at   TIMESTAMP NULL,
    remember_token            VARCHAR(100) NULL,
    current_team_id           BIGINT NULL,
    profile_photo_path        VARCHAR(2048) NULL,
    created_at                TIMESTAMP NULL,
    updated_at                TIMESTAMP NULL
);

-- ── password_reset_tokens ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      VARCHAR(255) PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- ── sessions ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS sessions_user_id_index        ON sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index  ON sessions (last_activity);

-- ── cache ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cache (
    key        VARCHAR(255) PRIMARY KEY,
    value      TEXT NOT NULL,
    expiration BIGINT NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_expiration_index ON cache (expiration);

-- ── cache_locks ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cache_locks (
    key        VARCHAR(255) PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration BIGINT NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_locks_expiration_index ON cache_locks (expiration);

-- ── jobs ──────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS jobs (
    id           BIGSERIAL PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      TEXT NOT NULL,
    attempts     SMALLINT NOT NULL,
    reserved_at  INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at   INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS jobs_queue_index ON jobs (queue);

-- ── job_batches ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS job_batches (
    id              VARCHAR(255) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    total_jobs      INTEGER NOT NULL,
    pending_jobs    INTEGER NOT NULL,
    failed_jobs     INTEGER NOT NULL,
    failed_job_ids  TEXT NOT NULL,
    options         TEXT NULL,
    cancelled_at    INTEGER NULL,
    created_at      INTEGER NOT NULL,
    finished_at     INTEGER NULL
);

-- ── failed_jobs ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS failed_jobs (
    id         BIGSERIAL PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue      TEXT NOT NULL,
    payload    TEXT NOT NULL,
    exception  TEXT NOT NULL,
    failed_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ── personal_access_tokens ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id             BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id   BIGINT NOT NULL,
    name           TEXT NOT NULL,
    token          VARCHAR(64) NOT NULL UNIQUE,
    abilities      TEXT NULL,
    last_used_at   TIMESTAMP NULL,
    expires_at     TIMESTAMP NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_index
    ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE INDEX IF NOT EXISTS personal_access_tokens_expires_at_index
    ON personal_access_tokens (expires_at);

-- ── teams ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS teams (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    name          VARCHAR(255) NOT NULL,
    personal_team BOOLEAN NOT NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS teams_user_id_index ON teams (user_id);

-- ── team_user ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_user (
    id         BIGSERIAL PRIMARY KEY,
    team_id    BIGINT NOT NULL REFERENCES teams (id) ON DELETE CASCADE,
    user_id    BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    role       VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (team_id, user_id)
);

-- ── team_invitations ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_invitations (
    id         BIGSERIAL PRIMARY KEY,
    team_id    BIGINT NOT NULL REFERENCES teams (id) ON DELETE CASCADE,
    email      VARCHAR(255) NOT NULL,
    role       VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (team_id, email)
);

-- ── permissions (spatie/laravel-permission) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS permissions (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (name, guard_name)
);

-- ── roles (spatie/laravel-permission) ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (name, guard_name)
);

-- ── model_has_permissions ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id BIGINT NOT NULL REFERENCES permissions (id) ON DELETE CASCADE,
    model_type    VARCHAR(255) NOT NULL,
    model_id      BIGINT NOT NULL,
    CONSTRAINT model_has_permissions_permission_model_type_primary
        PRIMARY KEY (permission_id, model_id, model_type)
);
CREATE INDEX IF NOT EXISTS model_has_permissions_model_id_model_type_index
    ON model_has_permissions (model_id, model_type);

-- ── model_has_roles ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id    BIGINT NOT NULL REFERENCES roles (id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id   BIGINT NOT NULL,
    CONSTRAINT model_has_roles_role_model_type_primary
        PRIMARY KEY (role_id, model_id, model_type)
);
CREATE INDEX IF NOT EXISTS model_has_roles_model_id_model_type_index
    ON model_has_roles (model_id, model_type);

-- ── role_has_permissions ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id BIGINT NOT NULL REFERENCES permissions (id) ON DELETE CASCADE,
    role_id       BIGINT NOT NULL REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_permission_id_role_id_primary
        PRIMARY KEY (permission_id, role_id)
);

-- ── patients ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patients (
    id                      BIGSERIAL PRIMARY KEY,
    first_name              VARCHAR(255) NOT NULL,
    last_name               VARCHAR(255) NOT NULL,
    codice_fiscale          VARCHAR(255) NULL UNIQUE,
    date_of_birth           DATE NULL,
    gender                  VARCHAR(255) NULL,
    email                   VARCHAR(255) NULL,
    phone                   VARCHAR(255) NULL,
    address                 TEXT NULL,
    city                    VARCHAR(255) NULL,
    cap                     VARCHAR(255) NULL,
    emergency_contact_name  VARCHAR(255) NULL,
    emergency_contact_phone VARCHAR(255) NULL,
    medico_base             VARCHAR(255) NULL,
    notes                   TEXT NULL,
    is_active               BOOLEAN NOT NULL DEFAULT TRUE,
    booking_token           VARCHAR(255) NULL UNIQUE,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    deleted_at              TIMESTAMP NULL
);

-- ── patient_tags ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_tags (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL UNIQUE,
    color      VARCHAR(255) NOT NULL DEFAULT '#6B7280',
    category   VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- ── professional_profiles ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS professional_profiles (
    id                       BIGSERIAL PRIMARY KEY,
    user_id                  BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    category                 VARCHAR(255) NULL,
    title                    VARCHAR(255) NULL,
    bio                      TEXT NULL,
    curriculum               TEXT NULL,
    specializations          JSONB NULL,
    photo                    VARCHAR(255) NULL,
    partita_iva              VARCHAR(255) NULL,
    codice_fiscale           VARCHAR(255) NULL,
    regime_fiscale           VARCHAR(255) NOT NULL DEFAULT 'ordinario',
    cassa_previdenziale      VARCHAR(255) NULL,
    albo_professionale       VARCHAR(255) NULL,
    numero_albo              VARCHAR(255) NULL,
    invoice_counter          INTEGER NOT NULL DEFAULT 0,
    is_bookable              BOOLEAN NOT NULL DEFAULT TRUE,
    session_duration_minutes INTEGER NOT NULL DEFAULT 50,
    session_price            NUMERIC(8, 2) NULL,
    booking_notes            TEXT NULL,
    phone                    VARCHAR(255) NULL,
    website                  VARCHAR(255) NULL,
    created_at               TIMESTAMP NULL,
    updated_at               TIMESTAMP NULL
);

-- ── patient_records ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_records (
    id                   BIGSERIAL PRIMARY KEY,
    patient_id           BIGINT NOT NULL REFERENCES patients (id) ON DELETE CASCADE,
    user_id              BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    category             VARCHAR(255) NOT NULL,
    record_type          VARCHAR(255) NOT NULL,
    title                VARCHAR(255) NOT NULL,
    data                 JSONB NOT NULL,
    notes                TEXT NULL,
    treatment_plan       TEXT NULL,
    record_date          DATE NOT NULL,
    is_shared_with_team  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    deleted_at           TIMESTAMP NULL
);

-- ── patient_tag (pivot) ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_tag (
    patient_id     BIGINT NOT NULL REFERENCES patients (id) ON DELETE CASCADE,
    patient_tag_id BIGINT NOT NULL REFERENCES patient_tags (id) ON DELETE CASCADE,
    PRIMARY KEY (patient_id, patient_tag_id)
);

-- ── patient_consents ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_consents (
    id            BIGSERIAL PRIMARY KEY,
    patient_id    BIGINT NOT NULL REFERENCES patients (id) ON DELETE CASCADE,
    user_id       BIGINT NOT NULL REFERENCES users (id),
    type          VARCHAR(255) NOT NULL,
    accepted      BOOLEAN NOT NULL DEFAULT FALSE,
    accepted_at   TIMESTAMP NULL,
    method        VARCHAR(255) NOT NULL DEFAULT 'digital',
    document_path VARCHAR(255) NULL,
    ip_address    VARCHAR(255) NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL
);

-- ── intervisioni ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS intervisioni (
    id               BIGSERIAL PRIMARY KEY,
    created_by       BIGINT NOT NULL REFERENCES users (id),
    patient_id       BIGINT NULL REFERENCES patients (id) ON DELETE SET NULL,
    title            VARCHAR(255) NOT NULL,
    description      TEXT NULL,
    discussion_notes TEXT NULL,
    conclusions      TEXT NULL,
    status           VARCHAR(255) NOT NULL DEFAULT 'draft',
    scheduled_at     TIMESTAMP NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);

-- ── availability_slots ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS availability_slots (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    day_of_week SMALLINT NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    room        VARCHAR(255) NULL,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    valid_from  DATE NULL,
    valid_until DATE NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

-- ── appointments ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT NOT NULL REFERENCES users (id),
    patient_id          BIGINT NULL REFERENCES patients (id) ON DELETE SET NULL,
    type                VARCHAR(255) NOT NULL DEFAULT 'session',
    title               VARCHAR(255) NOT NULL,
    description         TEXT NULL,
    start_at            TIMESTAMP NOT NULL,
    end_at              TIMESTAMP NOT NULL,
    room                VARCHAR(255) NULL,
    status              VARCHAR(255) NOT NULL DEFAULT 'scheduled',
    color               VARCHAR(255) NULL,
    is_shared           BOOLEAN NOT NULL DEFAULT FALSE,
    intervisione_id     BIGINT NULL REFERENCES intervisioni (id) ON DELETE SET NULL,
    cancellation_reason TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);

-- ── invoices ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
    id                    BIGSERIAL PRIMARY KEY,
    user_id               BIGINT NOT NULL REFERENCES users (id),
    patient_id            BIGINT NOT NULL REFERENCES patients (id),
    appointment_id        BIGINT NULL REFERENCES appointments (id) ON DELETE SET NULL,
    invoice_number        INTEGER NOT NULL,
    invoice_year          INTEGER NOT NULL,
    invoice_code          VARCHAR(255) NOT NULL UNIQUE,
    issuer_name           VARCHAR(255) NOT NULL,
    issuer_partita_iva    VARCHAR(255) NULL,
    issuer_codice_fiscale VARCHAR(255) NULL,
    issuer_address        VARCHAR(255) NULL,
    issuer_regime_fiscale VARCHAR(255) NOT NULL,
    client_name           VARCHAR(255) NOT NULL,
    client_codice_fiscale VARCHAR(255) NULL,
    client_address        VARCHAR(255) NULL,
    subtotal              NUMERIC(10, 2) NOT NULL,
    marca_da_bollo        NUMERIC(10, 2) NOT NULL DEFAULT 0,
    total                 NUMERIC(10, 2) NOT NULL,
    iva_exempt            BOOLEAN NOT NULL DEFAULT TRUE,
    iva_exemption_reason  VARCHAR(255) NOT NULL DEFAULT 'Art. 10 DPR 633/72',
    sts_sent              BOOLEAN NOT NULL DEFAULT FALSE,
    sts_sent_at           TIMESTAMP NULL,
    payment_method        VARCHAR(255) NULL,
    issued_at             DATE NOT NULL,
    paid_at               DATE NULL,
    status                VARCHAR(255) NOT NULL DEFAULT 'draft',
    pdf_path              VARCHAR(255) NULL,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL
);

-- ── invoice_lines ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoice_lines (
    id          BIGSERIAL PRIMARY KEY,
    invoice_id  BIGINT NOT NULL REFERENCES invoices (id) ON DELETE CASCADE,
    description VARCHAR(255) NOT NULL,
    quantity    INTEGER NOT NULL DEFAULT 1,
    unit_price  NUMERIC(10, 2) NOT NULL,
    total       NUMERIC(10, 2) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

-- ── messages ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
    id              BIGSERIAL PRIMARY KEY,
    sender_id       BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    channel_type    VARCHAR(255) NOT NULL,
    channel_id      VARCHAR(255) NOT NULL,
    body            TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    read_at         TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);

-- ── documents ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS documents (
    id                       BIGSERIAL PRIMARY KEY,
    uploaded_by              BIGINT NOT NULL REFERENCES users (id),
    patient_id               BIGINT NULL REFERENCES patients (id) ON DELETE SET NULL,
    title                    VARCHAR(255) NOT NULL,
    description              TEXT NULL,
    file_path                VARCHAR(255) NOT NULL,
    file_name                VARCHAR(255) NOT NULL,
    mime_type                VARCHAR(255) NOT NULL,
    file_size                BIGINT NOT NULL,
    category                 VARCHAR(255) NULL,
    visible_to_categories    JSONB NULL,
    is_shared_with_patient   BOOLEAN NOT NULL DEFAULT FALSE,
    created_at               TIMESTAMP NULL,
    updated_at               TIMESTAMP NULL,
    deleted_at               TIMESTAMP NULL
);

-- ── social_posts ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS social_posts (
    id           BIGSERIAL PRIMARY KEY,
    created_by   BIGINT NOT NULL REFERENCES users (id),
    title        VARCHAR(255) NOT NULL,
    content      TEXT NOT NULL,
    platforms    JSONB NULL,
    media_paths  JSONB NULL,
    status       VARCHAR(255) NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    notes        TEXT NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL
);

-- ── audit_logs ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGSERIAL PRIMARY KEY,
    user_id    BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
    action     VARCHAR(255) NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id   BIGINT NOT NULL,
    old_values JSONB NULL,
    new_values JSONB NULL,
    ip_address VARCHAR(255) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL
);
