CREATE TABLE IF NOT EXISTS temp_press (
    id              SERIAL       PRIMARY KEY,
    press_number    VARCHAR(8)   NOT NULL,
    name            VARCHAR(80),
    rate_per_hr     NUMERIC(10,2),
    make_ready_hr   NUMERIC(6,2),
    impr_per_hr     INTEGER,
    max_colours     INTEGER,
    web_width_in    NUMERIC(6,2),
    notes           VARCHAR(255),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS temp_paper (
    id              SERIAL       PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    weight          VARCHAR(20),
    kind            VARCHAR(40),
    cost_per_m      NUMERIC(10,4),
    waste_pct       NUMERIC(6,3),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS temp_ink (
    id              SERIAL       PRIMARY KEY,
    name            VARCHAR(80)  NOT NULL,
    cost_per_m      NUMERIC(10,4),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS temp_bindery (
    id              SERIAL       PRIMARY KEY,
    name            VARCHAR(80)  NOT NULL,
    cost_per_m      NUMERIC(10,4),
    applies_to      VARCHAR(40),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS estimating_formula (
    id                 SERIAL       PRIMARY KEY,
    formula_key        VARCHAR(60)  NOT NULL,
    version            INTEGER      NOT NULL DEFAULT 1,
    label              VARCHAR(120) NOT NULL,
    steps_json         JSONB        NOT NULL,
    default_markup_pct NUMERIC(6,3) NOT NULL DEFAULT 0,
    is_current         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    created_by         INTEGER      REFERENCES users(id),
    UNIQUE (formula_key, version)
);

CREATE INDEX IF NOT EXISTS idx_estimating_formula_key ON estimating_formula(formula_key);
CREATE INDEX IF NOT EXISTS idx_estimating_formula_current ON estimating_formula(formula_key) WHERE is_current;

CREATE TABLE IF NOT EXISTS estimate (
    id              SERIAL       PRIMARY KEY,
    quote_id        INTEGER      NOT NULL REFERENCES quotes(id),
    press_source_id INTEGER,
    status          VARCHAR(20)  NOT NULL DEFAULT 'draft',
    edna_analysis   JSONB,
    created_by      INTEGER      NOT NULL REFERENCES users(id),
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    locked_at       TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_estimate_quote_id ON estimate(quote_id);
CREATE INDEX IF NOT EXISTS idx_estimate_status ON estimate(status);
CREATE INDEX IF NOT EXISTS idx_estimate_deleted ON estimate(deleted_at);

CREATE TABLE IF NOT EXISTS estimate_break (
    id              SERIAL       PRIMARY KEY,
    estimate_id     INTEGER      NOT NULL REFERENCES estimate(id) ON DELETE CASCADE,
    quantity        INTEGER      NOT NULL,
    sort_order      INTEGER      NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    UNIQUE (estimate_id, quantity)
);

CREATE INDEX IF NOT EXISTS idx_estimate_break_estimate ON estimate_break(estimate_id);

CREATE TABLE IF NOT EXISTS estimate_component (
    id                SERIAL       PRIMARY KEY,
    estimate_break_id INTEGER      NOT NULL REFERENCES estimate_break(id) ON DELETE CASCADE,
    component_type    VARCHAR(20)  NOT NULL,
    source_id         INTEGER,
    formula_id        INTEGER      REFERENCES estimating_formula(id),
    label             VARCHAR(120) NOT NULL,
    sort_order        INTEGER      NOT NULL DEFAULT 0,
    formula_json      JSONB,
    rate_values       JSONB,
    cost              NUMERIC(12,4),
    markup_pct        NUMERIC(6,3) NOT NULL DEFAULT 0,
    rate_flat         NUMERIC(12,4),
    rate_percent      NUMERIC(7,4),
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_component_type CHECK (
        component_type IN ('press','paper','ink','bindery','adjustment')
    ),
    CONSTRAINT chk_component_shape CHECK (
        (component_type = 'adjustment' AND source_id IS NULL AND formula_id IS NULL)
        OR
        (component_type <> 'adjustment')
    ),
    CONSTRAINT chk_component_adjust CHECK (
        NOT (rate_flat IS NOT NULL AND rate_percent IS NOT NULL)
    )
);

CREATE INDEX IF NOT EXISTS idx_estimate_component_break ON estimate_component(estimate_break_id);
CREATE INDEX IF NOT EXISTS idx_estimate_component_type ON estimate_component(component_type);
CREATE INDEX IF NOT EXISTS idx_estimate_component_formula ON estimate_component(formula_id);

DROP TRIGGER IF EXISTS trg_temp_press_updated_at ON temp_press;
DROP TRIGGER IF EXISTS trg_temp_paper_updated_at ON temp_paper;
DROP TRIGGER IF EXISTS trg_temp_ink_updated_at ON temp_ink;
DROP TRIGGER IF EXISTS trg_temp_bindery_updated_at ON temp_bindery;
DROP TRIGGER IF EXISTS trg_estimate_updated_at ON estimate;
DROP TRIGGER IF EXISTS trg_estimate_component_updated_at ON estimate_component;

CREATE TRIGGER trg_temp_press_updated_at BEFORE UPDATE ON temp_press FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_temp_paper_updated_at BEFORE UPDATE ON temp_paper FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_temp_ink_updated_at BEFORE UPDATE ON temp_ink FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_temp_bindery_updated_at BEFORE UPDATE ON temp_bindery FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_estimate_updated_at BEFORE UPDATE ON estimate FOR EACH ROW EXECUTE FUNCTION update_updated_at();
CREATE TRIGGER trg_estimate_component_updated_at BEFORE UPDATE ON estimate_component FOR EACH ROW EXECUTE FUNCTION update_updated_at();
