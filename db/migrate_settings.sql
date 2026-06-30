-- File: db/migrate_settings.sql
-- ============================================================
-- SCP Central — Settings page + super_admin role migration
-- ============================================================
-- Adds:
--   * super_admin role
--   * app_settings  (generic key/value store — future settings are INSERTs)
--   * anthropic_models (curated model dropdown list + speed/cost text)
--   * role_permissions grants: super_admin -> '*' (all bits), admin -> page:settings
--   * moves reno.devadmin to super_admin
--
-- Hand-applied in pgAdmin (no migration runner — see CLAUDE.md).
-- Idempotent: safe to re-run. No rollback section (dev, not live).
-- ============================================================

-- ── super_admin role ──────────────────────────────────────────────────────────
INSERT INTO roles (name)
SELECT 'super_admin'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'super_admin');

-- ── app_settings: generic key/value config store ──────────────────────────────
CREATE TABLE IF NOT EXISTS app_settings (
    key         VARCHAR(120) PRIMARY KEY,
    value       TEXT,
    type        VARCHAR(20)  NOT NULL DEFAULT 'string',  -- string | int | bool | enum
    description TEXT,
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_by  INTEGER      REFERENCES users(id)
);

-- Seed the active Anthropic model. Value mirrors secrets.php ANTHROPIC_MODEL;
-- edna.php falls back to that constant if this row is ever missing.
INSERT INTO app_settings (key, value, type, description)
VALUES (
    'anthropic_model',
    'claude-sonnet-4-6',
    'enum',
    'Active Claude model used by Edna. Options come from anthropic_models.'
)
ON CONFLICT (key) DO NOTHING;

-- ── anthropic_models: curated dropdown list ───────────────────────────────────
-- "Everything is data" — adding/retiring a model is an INSERT/UPDATE here,
-- not a code change. speed_note / cost_note are shown in the selector UI.
CREATE TABLE IF NOT EXISTS anthropic_models (
    model_id   VARCHAR(120) PRIMARY KEY,   -- exact API string, e.g. claude-sonnet-4-6
    label      VARCHAR(120) NOT NULL,      -- friendly name, e.g. "Claude Sonnet 4.6"
    speed_note VARCHAR(120),               -- plain-language speed indication
    cost_note  VARCHAR(120),               -- plain-language cost indication
    sort_order INTEGER      NOT NULL DEFAULT 0,
    is_active  BOOLEAN      NOT NULL DEFAULT TRUE
);

INSERT INTO anthropic_models (model_id, label, speed_note, cost_note, sort_order, is_active) VALUES
    ('claude-haiku-4-5',  'Claude Haiku 4.5',  'Fastest',  'Lowest cost',  1, TRUE),
    ('claude-sonnet-4-6', 'Claude Sonnet 4.6', 'Balanced', 'Moderate cost', 2, TRUE),
    ('claude-opus-4-8',   'Claude Opus 4.8',   'Slowest',  'Highest cost',  3, TRUE)
ON CONFLICT (model_id) DO NOTHING;

-- ── Permission grants ─────────────────────────────────────────────────────────
-- super_admin -> '*' wildcard, all bits set (max signed bigint = all 63 usable
-- bits). hasPermission() honours a '*' grant as full access on any resource, so
-- "super admin has everything" stays pure data — no per-resource rows needed.
INSERT INTO role_permissions (role_id, resource_key, permission_bits)
SELECT r.id, '*', 9223372036854775807
FROM roles r
WHERE r.name = 'super_admin'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.resource_key = '*'
  );

-- admin -> page:settings, READ (1) + WRITE (2) = 3
INSERT INTO role_permissions (role_id, resource_key, permission_bits)
SELECT r.id, 'page:settings', 3
FROM roles r
WHERE r.name = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.resource_key = 'page:settings'
  );

-- ── Move reno.devadmin to super_admin ─────────────────────────────────────────
UPDATE users
SET role_id = (SELECT id FROM roles WHERE name = 'super_admin')
WHERE username = 'reno.devadmin';
