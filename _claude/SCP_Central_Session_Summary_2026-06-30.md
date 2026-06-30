<!-- File: SCP_Central_Session_Summary_2026-06-30.md -->

# SCP Central — Session Summary & Handoff
**Date:** June 30, 2026
**Developer:** Steve (StepSolutionsAI)
**Focus this session:** Fix the Edna error properly, harden error handling, then build the admin **Settings page** (model selector) on the existing RBAC engine, and finish the **Re-parse** feature on spec review. Next up: the **estimating** step.

> Continues from `SCP_Central_Session_Summary_2026-06-29.md` (recon + credential separation + diagnosing the retired-model bug). Read that for VPS/deploy ground truth.

---

## TL;DR

Confirmed the Edna `not_found_error` was a **retired model string** in `secrets.php` (config, not code). Hardened `edna.php` so Anthropic HTTP errors fail **loud to us** (logged 502) instead of silently to the client, and closed the `$e->getMessage()` leak. Then built a real **admin Settings page** with a DB-backed, UI-editable **Anthropic model selector**, gated by the existing RBAC engine — adding a `super_admin` role whose "full access" is expressed as **data** (a `*` wildcard grant), not a hardcoded role check. Finally, completed the **Re-parse** feature on spec review (it was a `prompt()` stub). Everything is built locally; **deploy is manual `scp`** (SFTP-on-save still broken).

---

## What We Did — and Why

### 1. Edna model fix — confirmed it's config, not code
The error `not_found_error — model: claude-sonnet-4-20250514` was a retired model string. Per the project rule, the model lives in config, so the fix is a server-side `secrets.php` edit (or now the Settings UI). No `edna.php` code change was needed for the error itself.

### 2. Hardened `edna.php` error handling (the real bug behind the confusion)
`edna.php` only caught cURL transport failures — an Anthropic **HTTP 404/401/etc. with a JSON error body** sailed through and was passed straight to the client. Added a check: any `$http_code >= 400` or `error` key → **logged 502 with a generic client message**. Also stopped leaking cURL detail and the DB handler's `$e->getMessage()`. All three failure paths now `error_log()` the real detail and return generic text. Same pattern applied to the new `save_settings.php`. This is the "remove single points of silent failure" goal in practice.

### 3. Admin Settings page + DB-backed model selector
- **New `app_settings` table** — generic key/value store so future settings are INSERTs, not schema changes. Seeded `anthropic_model`.
- **New `anthropic_models` table** — curated dropdown list (Haiku 4.5 / Sonnet 4.6 / Opus 4.8) with plain-language speed/cost notes. This is also the **validation allowlist** that blocks saving a retired/invalid model string (prevents the class of bug from §1).
- **`public/settings.php`** — gated page (`page:settings` READ), model selector card, sections layout for future settings.
- **`public/api/save_settings.php`** — gated save (`page:settings` WRITE), validates the model against `anthropic_models.is_active`, stamps `updated_by`.
- **`edna.php`** now reads `app_settings.anthropic_model`, falling back to the `ANTHROPIC_MODEL` constant in `secrets.php` if the row is missing — degrade to last-known-good, never break Edna. The constant must stay as a **valid** fallback (don't delete it).
- Cost/usage *comparison* logging was **deferred** — it needs a separate usage_log; the selector alone is what was asked for.

### 4. RBAC: super_admin via wildcard (data, not code)
Ground truth from pgAdmin: roles were `csr, senior_estimator, manager, partner, admin` (no super_admin); all `role_permissions` were `widget:*` grants. So:
- Added a **`super_admin`** role and moved **`reno.devadmin`** (Steve's account) to it.
- `super_admin` full access = **one `role_permissions` row with `resource_key='*'`** and all bits set. Extended `hasPermission()` to honour a `*` grant on the user's role (cached per user). "Super admin has everything" is now pure data — adding a new resource needs no new super_admin grant.
- `admin` granted `page:settings` (READ+WRITE). `page:settings` is the **first page-level resource** (convention established).
- **Nav gating:** the live nav is hardcoded in `includes/header.php` (NOT `nav.php` — see below). Renamed the stub "Admin" link → **Settings** (`/settings.php`), and **hid it** behind `hasPermission(page:settings, READ)` with a null-safe guard.

### 5. Found `includes/nav.php` is DEAD CODE
Nothing includes `nav.php`. The live nav is hardcoded inside `header.php` (which every page includes). Noted in CLAUDE.md; edits go in `header.php`.

### 6. Re-parse feature completed (was a stub)
`reparseWithEdna()` on spec_review was a 3-line `prompt('Paste updated job description')` placeholder — never finished. Rebuilt it (option 1: reconstruct text from fields, no backend change):
- `serializeForm()` reads current form state (inputs, job-type pick, press, finishing, qty tags) into a description.
- `callEdna()` extracted as the shared fetch+overlay helper (used by first parse and re-parse).
- `diffSpec()` compares Edna's response to current values: blank fields she fills silently; non-empty fields she **disagrees** with go to an **accept/keep modal** (pre-checked = use Edna's value, uncheck = keep yours).
- Edna's notes always update, including "looks good" on agreement.
- **Scope:** diff covers text/select fields only. **press / job_type / finishing / quantities are NOT diffed yet** — deferred, Steve OK'd.

---

## Working Agreement (how Steve wants us to operate)
*(Also in CLAUDE.md Conventions — these are the durable rules.)*
- **Questions:** plain **numbered list**, never the question-bubble UI. Steve answers by number.
- **Commands:** each runnable command in its **own code block** (own copy bubble), but give **all** of them. State PowerShell (local) vs SSH (VPS).
- **Complete files, not snippets.** File path in a comment at the top of every file.
- **Everything is data, no hardcoding** (tables over hardcoded logic — "tables are cheap, labour is not"). Mark unavoidable temp hardcoding `// TODO: hardcoded`.
- **Auth:** only `hasPermission()`. Never inline bitwise checks or role-name checks.
- Discuss/confirm design **before** building. Don't re-suggest a fix that already failed.
- Be less verbose where possible.

---

## Estimating Engine — Planning (this session, no code yet)

Spent the back half of the session planning the **estimating step** (the core of the whole product). Decisions reached, all recorded in `.claude/SCP_Central_Design_Reference.md` and `.claude/docs/BFE_press_cost_analysis.md`. **No code written** — next session builds.

**The architecture: a configurable formula engine (Avanti-style).**
- Each estimating task = an **admin-configurable formula over variables** (modelled on Avanti's "estimating functions"). Formula + rates are **data**, editable without a deploy ("everything is data").
- User + Edna run the base formulas, then **adjust output post-calculation** on screen.
- Pricing is a **band: floor → recommended → ceiling** (already in the design doc; Edna's tone shifts near the ceiling).

**The three data sources, roles settled:**
- **BFE (FoxPro DBFs at `D:\dev\stillcreekpress\reference\bfe`)** = one-time **teacher**. It's **live through 2026** (not stale — verified), so its *recent* rates are valid seed defaults. Mined locally (I can read DBF headers + data directly via Python — no import needed). **Design doc §7 REVERSED:** the old `bfe_estimates` import plan is **dropped** — BFE is never a live pricing dependency. Lifecycle: teacher now → (optional disposable reference during testing, deferred) → **archived at go-live**. Go-live checklist gets an "archive BFE, confirm nothing depends on it" gate.
- **Avanti Classic (MSSQL, read-only over IPsec tunnel)** = the **live empirical layer** going forward — current cost-center rates (`CostCenterFile`/`PressStandards`), paper costs, `View_CostFormula`, actual job costs, sell price, won/lost. BFE↔Avanti already linked via `EstimatinInformation.OrigEstNumber`. **Connection NOT built yet** (no SQL Server driver in the php image, no creds) — it's designed in the schema comments but unwired. **Validation against Avanti is DEFERRED** — build the formula engine first.
- **Formula engine** = the truth; Avanti validates/refines its rates later (ML tunes the knobs, formula stays transparent for the CSR-confirms workflow).

**Two hard rules locked in (design doc §10a):**
1. **Estimate immutability** — a saved estimate is a **frozen snapshot**. Stamp at save time: inputs + **every rate value used** (copied, not referenced) + **formula version ID** + computed outputs + the floor/recommended/ceiling band. Display **never recomputes**; re-estimate = a **new** snapshot. (Mirrors the existing `prompt_version_ids` pattern. BFE/Avanti both lack this — old estimates shift when rates change; we deliberately don't.)
2. **Seed rates from the RECENT window only** (2024–26), never full-history. Full-history median under-prices by ~40% (Press 3 snap $/hr went ~$120 in 2013–17 → ~$220 in 2023–26). The "don't anchor to the past" principle, applied inside rate derivation.

**BFE press-cost analysis done — the v1 slice's seed values** (full detail + methodology in `.claude/docs/BFE_press_cost_analysis.md`):
- Formula: `press_cost = (make_ready_hours + quantity/impressions_per_hour) × press_rate`, all three keyed by press.
- Current seeds (2024–26 forms estimates): Press **3 & 5 = $220/hr**, **11 = $265/hr**, **4 = $220/hr**; make-ready ~1.3–2.0 hr; impr/hr 17k (P5) / 40k (P3) / 52k (P11). Presses 3 & 5 are the forms workhorses.

**Deferred within estimating (decided, not built):**
- Formula **storage format** (JSON vs stored expression vs free-form admin-written expression engine) — its own future topic; v1 uses the simplest thing (likely structured JSON).
- **Avanti read connection** + validation layer — build after the formula engine slice works.
- Make-ready / impr/hr keyed on **parts & colours** (not just press) — v1 defaults per-press; refine later.

**Next action (start of next session):** design the **formula-engine schema** — `estimating_tasks` / `estimating_variables` / `estimating_formulas` / `estimating_rates` — built around the immutability rule, seeded with the press-cost numbers above, formula storage placeholdered. Then build the **press-cost vertical slice** end-to-end (compute press cost from the spec → show on screen → user/Edna adjust). Validation against Avanti comes later.

---

## Open Items / Next Session

### Immediate — current focus (NEXT SESSION STARTS HERE)
1. **Design the formula-engine schema** (`estimating_tasks` / `estimating_variables` / `estimating_formulas` / `estimating_rates`), built around the **immutability rule** (design doc §10a) and seeded with the press-cost numbers from `.claude/docs/BFE_press_cost_analysis.md`. Formula storage placeholdered (deferred topic).
2. **Build the press-cost vertical slice** end-to-end: compute `press_cost` from the spec, show it on screen, let user/Edna adjust. One task, full workflow — to reveal the whole scope. **Validation against Avanti deferred.**

### Deferred (decided, not yet built)
3. **Avanti read connection** — SQL Server driver in php image + read creds (over IPsec tunnel) + a read layer mirroring the schema-mapped tables (`CostCenterFile`, `PressStandards`, `PaperCosts`, `View_CostFormula`, `ActiveJobCostM`). Prerequisite for the validation half of estimating. NOT built.
4. **Estimate validation** (required fields at estimate time: all except finishing ops + ink-back; required finishing ops per job type) → future **`job_type_requirements`** table.
5. **Re-parse diff for press / job_type / finishing / quantities** — extend the field registry in spec_review.
6. **Cost/usage comparison logging** — usage_log capturing model + input/output tokens per parse.
7. **Formula storage format** decision (JSON / expression / free-form engine).

### Still open from June 29 (foundation hygiene)
5. **Clean baseline Git commit** — narrow `.gitignore` `db/` rule (track schema SQL), `git add` the never-tracked dirs (`includes/`, `public/api/`, `db/*.sql`). History has never held a secret.
6. **Automated Postgres backups** (highest blast radius — none exist).
7. Verify Let's Encrypt auto-renewal; uptime monitoring; real `/health` endpoint (could validate the configured model against the Models API).
8. **Fix/replace the deploy path** — SFTP extension broken; every change is manual `scp` right now.
9. Quantity-break add still uses native `prompt()` — replace when next touched.

---

## Files Changed/Created This Session — ALL DEPLOYED

**All code this session is DEPLOYED and working** (Steve applied `db/migrate_settings.sql` in pgAdmin and `scp`'d the files; confirmed in-session):
- `db/migrate_settings.sql` — super_admin role, app_settings, anthropic_models, grants, reno→super_admin
- `public/settings.php`, `public/api/save_settings.php` — Settings page + model selector
- `public/api/edna.php` — error hardening + reads model from DB
- `includes/permissions.php` — wildcard `*` grant
- `includes/header.php` — nav → Settings, gated
- `public/modules/forms-estimating/spec_review.php` — re-parse feature (serialize → Edna → accept/keep modal)

**Local-only (docs, nothing to deploy):** `.claude/CLAUDE.md`, `.claude/SCP_Central_Design_Reference.md` (§7/§10a), `.claude/docs/BFE_press_cost_analysis.md`, this summary.

> For future sessions: the assistant cannot see the VPS or pgAdmin, and `git status` reflects only the local tree — it is NOT a deploy indicator. Trust Steve's confirmations and do not re-question deploy status.

---

## Security Reminders — Do NOT paste into chat
- Contents of `secrets.php` (`DB_PASSWORD`, `ANTHROPIC_API_KEY`, `SETUP_TOKEN`) or `.env`
- VPS public IP / SCP public IP / full SSH host address; SSH key or passphrase; IPsec PSK

*(Filenames, command structure, and non-secret config like the model name are fine.)*
