<!-- File: CLAUDE.md  (place at repo root: D:\dev\stillcreekpress\website\CLAUDE.md — commit to Git) -->

# CLAUDE.md — SCP Central

Persistent context for Claude Code. Lives at the repo root, loaded every session. Keep it lean — every line should change how you act.

> **Companion docs (load on demand, not every session):** `.claude/SCP_Central_Design_Reference.md` (the *why* — domain knowledge, data architecture, design rules), `.claude/docs/BFE_press_cost_analysis.md` (estimating seed rates), and the latest `_claude/SCP_Central_Session_Summary_*.md` (build status + next action). Read these before working on estimating, Avanti, RBAC, or Edna internals.

## What this is
SCP Central is a web-based print-estimating system replacing a legacy DOS/FoxPro system (BFE) for Still Creek Press. An AI assistant named **Edna** parses job specs and suggests pricing; a human CSR confirms. Currently a working demo being hardened toward production.

## Stack
- **PHP**, no framework, PDO. **PostgreSQL** database `scp_central`, user `scpadmin`.
- **Docker Compose**: nginx, php, certbot. **Anthropic Claude API** powers Edna.
- Frontend: jQuery, IBM Plex Sans / Plex Mono.

## Layout (local repo = the files you edit)
```
website/            repo root  →  maps to ~/scp-stack/php on the server
├── config/         secrets.php is SERVER-ONLY — must NEVER exist here
├── db/             schema + migration .sql files
├── includes/       auth.php, db.php, permissions.php, head.php, nav.php, header.php, footer.php
├── public/         ← WEB ROOT (container /var/www/html)
│   ├── api/        edna.php, save_settings.php, ping.php, prefs.php, save_quote.php
│   ├── modules/forms-estimating/   index.php, spec_review.php
│   ├── index.php   dashboard
│   ├── settings.php  admin settings (model selector)
│   ├── login.php  logout.php  reset_request.php  reset_password.php
├── .vscode/sftp.json   deploy config (gitignored)
└── .gitignore
```
- `public/` is the only web-served dir. `config/`, `includes/`, `db/` are outside it on purpose — don't move web-served logic out of `public/`, or non-public logic into it.
- **`includes/nav.php` is DEAD CODE** — nothing includes it. The live nav is hardcoded inside `includes/header.php` (every page includes that). Edit nav in `header.php`; `nav.php` is out of sync and should not be relied on (delete or reconcile later).

## Deploy reality — IMPORTANT
- The server dir `~/scp-stack/php` is **NOT a git repo**, and there is **no GitHub Actions pipeline**. Don't assume push-to-deploy.
- Deploy mechanism is **SFTP-on-save** (VS Code Natizyskunk extension) — **currently broken**. Fallback is a manual `scp` over the `scp-vps` SSH alias.
- `includes/` and `public/` are **bind-mounted** into the php container, so an `scp` to those folders is live immediately (no rebuild).
- Git (`github.com/RenoRidesBikes/scp`, **private**, solo) is a version-history safety net only — not a deploy path.

## Security — YOU MUST
- **NEVER commit or create locally:** `config/secrets.php`, `.env`, `.vscode/sftp.json`. They hold the DB password, Anthropic API key, setup token, and VPS host.
- **NEVER hardcode credentials.** `DB_USER` and `DB_PASSWORD` come from `secrets.php` (server-only) and are read via `getDB()`. `db.php` holds only non-secret `DB_HOST` / `DB_PORT` / `DB_NAME`.
- **NEVER run `git add .` or `git add -A` blindly.** Stage files explicitly so an untracked secret-bearing file can't slip in.
- Require paths like `/var/www/secrets.php` and `/var/www/includes/...` are **container paths and are correct** — do not "fix" them to local relative paths.

## Conventions — YOU MUST
- Put the file's path in a comment block at the **top of every file** you create or edit.
- **Everything is data, no hardcoding.** If something must be hardcoded temporarily, mark it `// TODO: hardcoded`.
- **Auth gate:** the only permitted permission check is `hasPermission()`. Never write inline bitwise permission logic.
- **Passwords:** hash and verify with PHP `password_hash()` / `password_verify()` (bcrypt). **Never** use PostgreSQL `crypt()` — they're incompatible and it caused a real login bug.
- Hand over **complete files, not snippets.** When giving commands, state exactly where they run: **PowerShell (local)** vs **SSH (VPS)**.
- **Commands to copy/paste:** put each runnable command in its **own separate code block** (its own copy bubble) so it can be copied and executed one at a time — but still provide **all** of them. Don't bundle multiple commands into one block.
- **Asking questions:** never use the question-bubble UI. Give a plain **numbered list** of questions; Steve answers with the corresponding numbers.
- **Treat Steve as a workmate, not a child.** Do NOT assume he hasn't deployed code, run a migration, or executed queries. `git status` shows only the local tree — it is not a deploy indicator and proves nothing about the VPS or DB. When he says something is done/tested, it's done — don't re-question it or re-explain how to verify it unless he asks. Trust his confirmations and track them.
- Don't re-suggest a fix that already failed earlier in the session — track what's been tried.

## App flow (where things are in the pipeline)
- **Dashboard** (`index.php`) → CSR types a free-text job description → stored in `sessionStorage` → **spec review**.
- **Spec review** (`modules/forms-estimating/spec_review.php`) = a pre-populated estimating form. Edna's first pass fills what she can; CSR edits fields directly. This is the **"Edna, help me" stage** — no validation gate here.
  - **Re-parse** (`reparseWithEdna()`): serializes current form state into a description (`serializeForm()`), re-runs Edna, writes her notes (always — including agreement), and shows an **accept/keep modal** for fields where she disagrees with a non-empty user value. Blank fields she fills silently. Covers text/select fields only; press / job_type / finishing / quantities are **not** in the diff yet (deferred).
  - Confidence system: per-field `cf-dot` colours + right-panel counts, states `confirmed | suggested | missing`.
- **Run estimate** → next stage, **NOT BUILT YET** (current focus). It's a **configurable formula engine** (Avanti-style: admin-editable formulas over variables, all data). Pricing is a band (floor → recommended → ceiling). **Design + seed values are planned** — read `SCP_Central_Design_Reference.md` §10a (estimate immutability rule) + §7 (BFE = throwaway teacher, NOT imported) and `.claude/docs/BFE_press_cost_analysis.md` (press-cost formula + current seed rates) before building. Next action: design `estimating_tasks/variables/formulas/rates` schema, then the press-cost vertical slice. **Two hard rules:** (1) saved estimates are immutable stamped snapshots — copy every rate used + formula version at save, never recompute on display; (2) seed rates from recent BFE data only (2024–26), never full-history.
- **Validation** (required fields at estimate time = all except finishing ops + **ink-back**; required finishing ops vary by job type → future `job_type_requirements` table) and **Avanti read connection** (live rate validation, SQL Server over the tunnel — not wired yet) are both **deferred** until the formula engine slice works.

## Danger zones / known issues
- **No DB migration system.** Schema is edited by hand in pgAdmin — this already caused a bug (a missing `auth_log.ip` column surfacing as a cryptic login error). Be explicit and cautious with any schema change, and flag that there's no migration tracking. Migrations live in `db/*.sql`, hand-applied, written idempotent (no rollback — dev, not live).
- `// TODO: hardcoded` items to migrate into DB config: CORS allowed origins + the JSON output contract (`edna.php`), and the trackable-fields list (`save_quote.php`).
- **Quick-stub UX still using native `prompt()`:** quantity-break add on spec_review (`addBreak()`) — replace with a proper input when that area is next touched. (The re-parse `prompt()` stub is already fixed.)
- **API error handling (done this session):** `edna.php` and `save_settings.php` log real detail via `error_log()` and return generic client messages — they no longer leak `$e->getMessage()` or Anthropic error bodies. `edna.php` now hard-fails (logged 502) on any Anthropic HTTP 4xx/5xx instead of passing the error through silently. Keep this pattern in new endpoints.
- The Anthropic model is now **admin-configurable** via the Settings page → `app_settings` table (`key='anthropic_model'`). `edna.php` reads it from there and **falls back to the `ANTHROPIC_MODEL` constant in `secrets.php`** if the row is missing. To change the model, use the Settings UI (super_admin/admin) or update the `app_settings` row — the `secrets.php` constant is now only the fallback default. Add new selectable models to the `anthropic_models` table (that's also the validation allowlist that blocks retired/invalid strings). Model strings get retired over time — if Edna returns `not_found_error`, the model in `app_settings`/`anthropic_models` is the thing to fix.
- **Settings & RBAC:** admin settings live in `public/settings.php` (gated `page:settings` READ) + `public/api/save_settings.php` (gated WRITE). Generic key/value store is `app_settings`. The `super_admin` role has a `role_permissions` row with `resource_key='*'` (all bits) — `hasPermission()` honours that wildcard as full access on any resource, so "super admin = full access" is **data, not a hardcoded role check**. `reno.devadmin` is super_admin. Schema in `db/migrate_settings.sql`.

## Build / test / lint
- No build step, test suite, or linter is configured yet. There is no local PHP runtime — "running" means loading the page in a browser against the live VPS.
- If you add tooling, document the exact command here.
