-- ============================================================
-- SCP Central — Auth migration v2
-- Adds lockout, last_login, active flag to users
-- Creates password_resets table
-- Run: docker compose exec -T postgres psql -U scpadmin -d scp_central < migrate_auth2.sql
-- ============================================================

-- ── ADD COLUMNS TO USERS ─────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_attempts  INT          DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until     TIMESTAMPTZ;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login       TIMESTAMPTZ;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active        BOOLEAN      DEFAULT true;

-- ── PASSWORD RESETS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     BIGINT       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash  VARCHAR(64)  NOT NULL,
    expires_at  TIMESTAMPTZ  NOT NULL,
    used_at     TIMESTAMPTZ,
    created_at  TIMESTAMPTZ  DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token  ON password_resets(token_hash);
CREATE INDEX IF NOT EXISTS idx_password_resets_user   ON password_resets(user_id);

-- ── REMEMBER TOKENS — ensure schema is correct ───────────────
CREATE TABLE IF NOT EXISTS remember_tokens (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     BIGINT       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash  VARCHAR(64)  NOT NULL,
    expires_at  TIMESTAMPTZ  NOT NULL,
    created_at  TIMESTAMPTZ  DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_remember_tokens_token  ON remember_tokens(token_hash);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_user   ON remember_tokens(user_id);

-- ── AUTH LOG — ensure schema is correct ─────────────────────
CREATE TABLE IF NOT EXISTS auth_log (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     BIGINT       REFERENCES users(id) ON DELETE SET NULL,
    event       VARCHAR(64)  NOT NULL,
    ip          VARCHAR(45),
    user_agent  TEXT,
    details     JSONB,
    created_at  TIMESTAMPTZ  DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_auth_log_user    ON auth_log(user_id);
CREATE INDEX IF NOT EXISTS idx_auth_log_event   ON auth_log(event);
CREATE INDEX IF NOT EXISTS idx_auth_log_created ON auth_log(created_at);
