<!-- File: SCP_Central_Session_Summary_2026-07-01.md -->

# SCP Central — Session Summary & Handoff
**Date:** July 1, 2026
**Developer:** Steve
**Focus this session:** Design + build the **estimating formula-engine schema** — the core of the product. Reached a materially different (and better) model than the 2026-06-30 plan by working it through with Steve. Wrote `db/migrate_estimating.sql`. No app code yet — next session builds the press-cost vertical slice on this schema.

> Continues from `SCP_Central_Session_Summary_2026-06-30.md`. The estimating tables it proposed (`estimating_tasks/variables/formulas/rates`) were **superseded** this session — see below.

---

## TL;DR

Redesigned the estimating schema from scratch with Steve, who steered it hard toward **pure relational modelling, everything-is-data, no types-as-strings**. The 06-30 plan (generic `estimating_rates` key/value store, JSONB-blob snapshots) was **dropped** as too abstract. The new model:

- **Three grains:** `estimate` (header, 1) → `estimate_break` (N, one per qty break) → `estimate_component` (N per break, the cost-build lines). Header carries **no money, no quantity** — price is always `SUM(components)`, fully recreatable, never a stamped total.
- **Numbers live on real domain things**, not a generic rate table: a press *has* a rate/make-ready/impr-per-hr. Placeholder `temp_press/temp_paper/temp_ink/temp_bindery` tables now (Avanti-sourced later).
- **Immutability** = each component STAMPS a copy of the formula + resolved numbers + computed cost on **lock**; sandbox reads live until then.
- **Overrides/global markup are just component rows** (`type='adjustment'`, `rate_flat` or `rate_percent`), so "price = sum of rows" has no exceptions.
- **Formula engine** = `estimating_formula`, versioned, declarative **step-list** JSON (no eval), each formula names the spec inputs + source-row columns it reads.

`db/migrate_estimating.sql` written (structure only, **no seed data**, idempotent). Not yet applied by Steve / not deployed.

---

## The design, and WHY it landed here (the important part)

Steve rejected several of my instincts in a row; each correction is a durable rule. Recording them because they'll recur:

1. **No generic `estimating_rate` / `rate_type` catalog.** A rate is an *attribute of a real thing*. `press_rate` is a column on the press, `cost_per_m` a column on the paper. Model the domain tables (presses, paper, inks, bindery), put the numbers on them. Generic key/value rate tables were "painting us into a corner."

2. **No types-as-strings for domain identity.** `rate_key='press_rate'` smeared across rows is a type modelled wrong. If it's a closed vocabulary the code references → real table + FK, never a magic string.

3. **BUT a structural discriminator IS a legit hardcoded token.** `estimate_component.component_type` (`press|paper|ink|bindery|adjustment`) is correct as an enum-ish `VARCHAR + CHECK`, because the engine must `switch` on it to know which table `source_id` joins to. Hardcoding earns its keep when it's *structure driving a join*, not *data*. This is the one place a token is right.

4. **Header ≠ break.** An `estimate` with a row per quantity break isn't a header — quantity forces a middle grain (`estimate_break`). Header holds what's true once (quote, press, status); breaks hold quantity; components hang off breaks (costs scale with qty).

5. **Don't store the price.** Everything needed to recreate it is in header + components. A total in the header would be a stored *derived* value — the opposite of immutability-by-storing-inputs. An override isn't a header column — it's another component row (a flat or percent `adjustment`). So the total is *still* `SUM(components)`; the override lives inside the rule.

6. **Flat vs percent = two columns, not parsed text.** `rate_flat` / `rate_percent`, the engine knows the math by which is non-null. (Named generically — they're rate-carrying columns any adjustment uses, not "adj_" columns welded to one purpose.)

7. **Makeready and press-run are SEPARATE components** (the demo breaks them out), each with its **own** small formula. They share the `rate_per_hr` *lookup* but never share a formula. Near-duplicate rate lists were the smell that told us to split.

**Grounded in `SCP_Estimating_Demo.html`** (Steve provided it this session — the design target): the "Pricing" screen IS the Run-estimate target; it's **per-quantity-break** (`qtyData[]`); the floor→cost+→Edna→caution→ceiling **band is sandbox-only Edna feedback, never stored** (all derived live in `updatePrice()`); the est-vs-actual **cost-centre breakdown** (Paper / Press run / Bindery / Makeready) is just components rendered by label; Admin screens (Presses / Stock / Finishing / Markup) confirm the domain tables + two-tier markup (per-op default + global 35%, Edna targets a 44–48% margin band). **Edna intervenes only between spec review and estimate submission, and is not always required** — no Edna scratch table; she's an AJAX call when invoked.

**Sandbox model:** pure browser state (sessionStorage stash so refresh doesn't lose tinkering), **zero DB writes** until an explicit **Lock in and send** (the demo's green button). No autosave, no draft table.

---

## What `db/migrate_estimating.sql` contains

- **`temp_press` / `temp_paper` / `temp_ink` / `temp_bindery`** — local placeholder domain tables (numbers on the real thing). No seed data.
- **`estimating_formula`** — versioned live engine; `steps_json` declarative step-list; `default_markup_pct`; `is_current` flag per `formula_key`.
- **`estimate`** (header) — `quote_id` FK, `press_source_id` (plain INT — see FUTURE FIX), `status` (draft|locked), `edna_analysis`, `locked_at`, soft-delete.
- **`estimate_break`** — `estimate_id` FK, `quantity`, unique per estimate.
- **`estimate_component`** — `estimate_break_id` FK, `component_type` discriminator (CHECK), `source_id` (plain INT), `formula_id` FK, stamped `formula_json`/`rate_values`/`cost`, `markup_pct`, `rate_flat`/`rate_percent`, CHECK constraints (adjustment shape + flat-xor-percent).
- Reuses the base schema's `update_updated_at()` trigger. Idempotent (`IF NOT EXISTS`, `DROP TRIGGER IF EXISTS`), no rollback.

---

## >>> FUTURE FIX (flagged, do not forget)

**`temp_*` tables are throwaway.** Presses AND paper are authoritative in **Avanti**, read live over the tunnel — but that connection is **not built yet** (no SQL Server driver in the php image, no creds). So we stand up local `temp_press/paper/ink/bindery` to develop and test the estimate slice against real rows. When the Avanti read layer lands (Steve is solving the interconnect ~next week):
- swap `temp_*` out for the live Avanti source, and
- add real FK constraints on `estimate.press_source_id` and `estimate_component.source_id` (plain INTEGERs today **by design**, awaiting a real target table).

---

## Open Items / Next Session

### Immediate — NEXT SESSION STARTS HERE
1. **Apply `db/migrate_estimating.sql`** in pgAdmin (Steve) + populate `temp_*` with a few real test rows.
2. **Build the press-cost vertical slice** end-to-end on this schema: from the spec → create `estimate` + `estimate_break`(s) → compute `makeready` + `press_run` components live (read `temp_press` + `estimating_formula`) → render the Pricing/band screen (sandbox) → **Lock** stamps immutable components. Formula evaluator = the safe step-list walker (`add sub mul div min max round`, no eval).
3. Seed `estimating_formula` rows for `makeready` and `press_run` (the two-formula split), plus `paper` and the finishing ops.

### Deferred (unchanged from 06-30 unless noted)
- **Avanti read connection** (the FUTURE FIX above depends on it).
- **Band = Edna sandbox feedback** — build as live client compute + AJAX to Edna; nothing stamped.
- Estimate validation (`job_type_requirements`), re-parse diff for press/job_type/finishing/qty, cost/usage logging.
- Clean baseline Git commit; automated Postgres backups; fix deploy path (SFTP broken → manual scp).

---

## Files Changed/Created This Session
- **`db/migrate_estimating.sql`** — NEW. The estimating schema above. **Not yet applied / not deployed.**
- **`_claude/SCP_Central_Session_Summary_2026-07-01.md`** — this file.
- (Pending: update CLAUDE.md app-flow pointer + design ref §10a to the new table names — do at start of next session or when touching those docs.)

> The 06-30 summary's estimating table names (`estimating_tasks/variables/formulas/rates`) are **superseded**; use the grains above.
