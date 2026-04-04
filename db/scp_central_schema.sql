-- ============================================================
-- SCP Central — PostgreSQL Schema v2.0
-- ============================================================

-- ── EXTENSIONS ──────────────────────────────────────────────
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- ROLES & USERS (stub — full RBAC added later)
-- ============================================================

CREATE TABLE roles (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
                -- csr | senior_estimator | manager | partner | admin
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at  TIMESTAMPTZ
);

CREATE TABLE users (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role_id         INTEGER NOT NULL REFERENCES roles(id),
    initials        VARCHAR(5),
    last_active_at  TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ
);

-- ============================================================
-- QUOTES
-- ============================================================

CREATE TABLE quotes (
    id                  SERIAL PRIMARY KEY,

    -- Avanti fields — populated when quote is pushed to Avanti
    avanti_quote_no     VARCHAR(8),             -- QuoteNo from EstimatinInformation — null until pushed
    avanti_quote_id     INTEGER,                -- RecID from EstimatinInformation

    -- Customer — sourced from Avanti ClientFile at quote time
    customer_code       VARCHAR(16) NOT NULL,   -- ClientFile.LongCode e.g. CBCAA
    customer_name       VARCHAR(64) NOT NULL,   -- ClientFile.CompLongName — denormalized for speed

    -- Job details
    job_name            VARCHAR(255) NOT NULL,
    job_type            VARCHAR(20) NOT NULL,   -- continuous | snapset
    status              VARCHAR(20) NOT NULL DEFAULT 'draft',
                                                -- draft | reviewed | sent | won | lost | expired

    -- Won/lost — auto-detected via Avanti JobMaster.OrgQuoteNumber
    avanti_job_number   VARCHAR(8),             -- JobMaster.JobNumber — populated when won
    won_at              TIMESTAMPTZ,
    lost_at             TIMESTAMPTZ,
    sent_at             TIMESTAMPTZ,
    expires_at          TIMESTAMPTZ,

    -- Edna
    edna_analysis       JSONB,                  -- overall job-level reasoning and confidence

    -- Metadata
    created_by          INTEGER NOT NULL REFERENCES users(id),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

-- ============================================================
-- FORMS SPECS (Continuous + Snap Set)
-- ============================================================

CREATE TABLE forms_specs (
    id                      SERIAL PRIMARY KEY,
    quote_id                INTEGER NOT NULL REFERENCES quotes(id),

    -- Dimensions
    width                   NUMERIC(6,3) NOT NULL,      -- inches
    depth                   NUMERIC(6,3) NOT NULL,      -- inches (cutoff)

    -- Stock
    parts                   INTEGER NOT NULL DEFAULT 1, -- plies
    ncr_type                VARCHAR(50),                -- CB/CFB/CF | CB/CF | CB/CFB/CFB/CF
    avanti_stock_code       VARCHAR(16),                -- CostCenterFile.Code for paper
    stock_notes             VARCHAR(255),               -- freeform override if needed

    -- Ink
    ink_front               VARCHAR(100),               -- 1 colour black | 4 colour etc
    ink_back                VARCHAR(100),

    -- Press — sourced from Avanti CostCenterFile
    avanti_press_code       VARCHAR(8),                 -- CostCenterFile.Code
    press_name              VARCHAR(50),                -- CostCenterFile.OpLongName — denormalized
    press_override_reason   TEXT,                       -- if CSR overrides Edna's press pick

    notes                   TEXT,
    edna_analysis           JSONB,                      -- field-level confidence and reasoning

    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMPTZ
);

-- ============================================================
-- FINISHING OPERATIONS (per quote)
-- ============================================================

CREATE TABLE quote_finishing (
    id                  SERIAL PRIMARY KEY,
    quote_id            INTEGER NOT NULL REFERENCES quotes(id),

    operation           VARCHAR(50) NOT NULL,
                        -- perforation | padding | collating | numbering
                        -- drilling | shrink_wrap | gluing | custom_cutting

    -- Operation-specific detail
    sets_of             INTEGER,                -- padding: sets of 25 | 50 etc
    perf_position       VARCHAR(20),            -- top | bottom | both | custom

    -- Confidence
    edna_suggested      BOOLEAN NOT NULL DEFAULT FALSE,
    confirmed_by_csr    BOOLEAN NOT NULL DEFAULT FALSE,

    notes               VARCHAR(255),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

-- ============================================================
-- PRICING (one row per quantity break)
-- ============================================================

CREATE TABLE quote_pricing (
    id              SERIAL PRIMARY KEY,
    quote_id        INTEGER NOT NULL REFERENCES quotes(id),

    quantity        INTEGER NOT NULL,

    -- Price band
    cost_floor      NUMERIC(10,2) NOT NULL,     -- hard floor — cost to produce
    cost_plus       NUMERIC(10,2) NOT NULL,     -- cost + standard markup
    edna_suggested  NUMERIC(10,2) NOT NULL,     -- Edna's recommended price
    edna_ceiling    NUMERIC(10,2) NOT NULL,     -- customer ceiling from history

    -- CSR decision
    final_price     NUMERIC(10,2),              -- what CSR locked in
    margin_pct      NUMERIC(5,2),               -- calculated margin at final price
    price_per_m     NUMERIC(10,2),              -- per thousand at final price

    edna_analysis   JSONB,                      -- reasoning behind this break's pricing

    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,

    UNIQUE (quote_id, quantity)                 -- one row per break per quote
);

-- ============================================================
-- QUOTE OUTCOMES
-- ============================================================

CREATE TABLE quote_outcomes (
    id                  SERIAL PRIMARY KEY,
    quote_id            INTEGER NOT NULL REFERENCES quotes(id),

    outcome             VARCHAR(20) NOT NULL,       -- won | lost | expired
    detection_method    VARCHAR(20) NOT NULL,       -- auto | manual
    avanti_job_number   VARCHAR(8),                 -- if auto-detected from JobMaster
    notes               TEXT,

    recorded_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
-- BFE HISTORICAL IMPORT
-- (populated once from FoxPro data migration)
-- ============================================================

CREATE TABLE bfe_estimates (
    id                      SERIAL PRIMARY KEY,

    -- BFE original data
    bfe_estimate_number     VARCHAR(20) NOT NULL UNIQUE,
    bfe_account_code        VARCHAR(6),             -- CMASTER.ACCOUNT
    bfe_customer_name       VARCHAR(30),            -- CMASTER.NAME

    -- Avanti link
    avanti_quote_no         VARCHAR(8),             -- EstimatinInformation.OrigEstNumber match
    avanti_customer_code    VARCHAR(16),            -- ClientFile.LongCode — resolved via name match

    -- Job details
    job_type                VARCHAR(20),
    quantity                INTEGER,
    final_price             NUMERIC(10,2),
    outcome                 VARCHAR(20),            -- won | lost | unknown

    -- Edna uses this for pricing intelligence
    imported_at             TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
-- INDEXES
-- ============================================================

CREATE INDEX idx_quotes_customer_code       ON quotes(customer_code);
CREATE INDEX idx_quotes_status              ON quotes(status);
CREATE INDEX idx_quotes_created_by          ON quotes(created_by);
CREATE INDEX idx_quotes_avanti_job_number   ON quotes(avanti_job_number);
CREATE INDEX idx_quotes_avanti_quote_no     ON quotes(avanti_quote_no);
CREATE INDEX idx_quotes_deleted_at          ON quotes(deleted_at);

CREATE INDEX idx_forms_specs_quote_id       ON forms_specs(quote_id);
CREATE INDEX idx_quote_pricing_quote_id     ON quote_pricing(quote_id);
CREATE INDEX idx_quote_finishing_quote_id   ON quote_finishing(quote_id);
CREATE INDEX idx_quote_outcomes_quote_id    ON quote_outcomes(quote_id);

CREATE INDEX idx_bfe_estimates_account      ON bfe_estimates(bfe_account_code);
CREATE INDEX idx_bfe_estimates_avanti_cust  ON bfe_estimates(avanti_customer_code);
CREATE INDEX idx_bfe_estimates_avanti_quote ON bfe_estimates(avanti_quote_no);

-- ============================================================
-- UPDATED_AT TRIGGER
-- ============================================================

CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_quotes_updated_at
    BEFORE UPDATE ON quotes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_forms_specs_updated_at
    BEFORE UPDATE ON forms_specs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER trg_quote_pricing_updated_at
    BEFORE UPDATE ON quote_pricing
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ============================================================
-- SEED DATA — Roles
-- ============================================================

INSERT INTO roles (name) VALUES
    ('csr'),
    ('senior_estimator'),
    ('manager'),
    ('partner'),
    ('admin');

