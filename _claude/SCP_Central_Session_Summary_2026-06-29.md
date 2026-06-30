<!-- File: SCP_Central_Session_Summary_2026-06-29.md -->

# SCP Central — Session Summary & Handoff
**Date:** June 29, 2026
**Developer:** Steve (StepSolutionsAI)
**Focus this session:** Reconnaissance after ~2 months dormant — verify the deployment, clean up repo/file-structure hygiene, separate credentials, and fix a broken Edna call. Goal: get a trustworthy foundation before building further.

---

## TL;DR

The app was already deployed on the Hostinger VPS — this was **not** a fresh setup. We verified the real state (vs. stale April notes), reconciled local-vs-server files, confirmed **no secrets ever leaked to Git**, finished moving the DB username out of `db.php` into the server-only secrets file, and diagnosed the Edna failure as a **retired model string** (fix provided). We did **not** yet make the clean baseline Git commit — that's the first thing next session.

---

## What We Did Today — and Why

### 1. Confirmed deployment is live and reconstructed true status
The April docs were partly stale. Verified ground truth: the stack is running on the Hostinger VPS, login works, DB connects. The one open thread from last time was the login/bcrypt area; today's Edna issue turned out to be unrelated (see §6).

### 2. Settled how deploys actually reach the server
Believed it might be GitHub Actions. **It isn't.** Verified on the server: `~/scp-stack/php` is **not a git repo**, and there are **no `.github/workflows/` files** anywhere. The real mechanism is **SFTP-on-save** (Natizyskunk VS Code extension); GitHub is only a version-history safety net. *(The minute-matching local→server timestamps were the giveaway.)*

⚠️ The SFTP extension is **currently not connecting**, so nothing auto-deploys right now — every change is a manual `scp` until that's fixed.

### 3. Repo / file-structure audit
- `git ls-files` showed only **9 files tracked**, all under `public/`. The entire `includes/`, `public/api/`, `db/`, and `.gitignore` itself were **never `git add`ed** — confirmed via `git check-ignore` that they're *not* being ignored, just never staged. So GitHub has been backing up ~1/3 of the app.
- `.gitignore` had a blanket `db/` rule that **wrongly excludes the schema SQL files** (`scp_central_schema.sql`, `migrate_auth.sql`) — those are blueprints, not secrets, and belong in version control. **Still needs narrowing.**

### 4. Git history secret check — clean
History showed `.vscode/sftp.json` was committed then removed across several commits (still recoverable in history). **But `.env` and `config/secrets.php` were never committed** — so the **DB password and Anthropic API key never leaked**. The only thing recoverable is the VPS host/IP + SSH username, in a **private, solo** repo protected by **passphrase-protected key auth**. Verdict: **low risk, no key rotation needed.** History scrub deferred (optional tidy-up).

### 5. Local ↔ server reconciliation (read-only)
Compared full file listings both sides (server clock is UTC, local is Pacific — 7h offset is display-only). Every file present on both sides matched **byte-for-byte**. Actions taken:
- **Pulled down** 3 server-only files the local clone was missing: `includes/head.php`, `includes/nav.php`, `db/migrate_auth.sql` (via `scp`, verified sizes match).
- **Left `config/secrets.php` server-only** by design.
- **Quarantined 2 junk files** — `public/index_1.php` (identical backup of `index.php`) and `public/modules/forms-estimating/estimating.php` (49 KB pre-refactor monolith). Confirmed via `grep` that nothing references them, then **moved to `~/scp-stack/_attic`** (outside the web root, so no longer URL-reachable) instead of hard-deleting.

### 6. Credential separation in `db.php` (the "step 2" cleanup)
`db.php` was already well-built — singleton PDO, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, and `DB_PASSWORD` already externalized to `secrets.php`. The only sensitive value still in it was `DB_USER`. We:
- Appended `DB_USER` into `secrets.php` server-side (copied the live value directly, never typed/pasted it).
- Removed the `DB_USER` define from local `db.php`, `scp`'d it up.
- **Login confirmed still working.** Verified `includes/` is bind-mounted (`docker-compose.yml` line 71), so `scp` to that folder deploys live instantly.

Result: `db.php` now holds only non-secret host/port/name and is safe to commit.

### 7. Edna model fix (separate, pre-existing bug)
`/api/edna.php` returned `not_found_error — model: claude-sonnet-4-20250514`. Root cause: that **model string has been retired**; it predates the current lineup (Opus 4.8 / Sonnet 4.6 / Haiku 4.5). The value lives in `ANTHROPIC_MODEL` in `secrets.php`, so the fix is a **one-line server edit, no redeploy**:
- Recommended replacement: **`claude-sonnet-4-6`** (same tier, drop-in). Alternatives: `claude-haiku-4-5-20251001` (cheaper/faster) or `claude-opus-4-8` (max accuracy).
- Commands provided (backup → `sed` swap → verify → optional php container restart).
- **Status: fix provided — confirm resolved.**

### 8. Discussed production / future-proofing
Reframed "future-proof" as removing **single points of silent failure** so external drift (like a retired model) fails loud to *us*, not silently to the client. Roadmap captured in Open Items below.

---

## Current State — Ground Truth Reference

| Thing | Value |
|---|---|
| Local working copy | `D:\dev\stillcreekpress\website` (**there is no E: drive** — old `E:\StepSolutionsAI\...` notes are wrong) |
| GitHub repo | `github.com/RenoRidesBikes/scp` — **private**, solo, this machine only |
| Deploy method | **SFTP-on-save** (currently broken) → fall back to manual `scp` via `scp-vps` SSH alias |
| Stack root (server) | `/home/ssaiadmin/scp-stack/` |
| Web root | `php/public/` → mounted to `/var/www/html` |
| `includes/` | mounted to `/var/www/includes` (bind-mount → `scp` deploys live) |
| DB | PostgreSQL, database `scp_central`, user **`scpadmin`** |
| `secrets.php` | server-only at `~/scp-stack/php/config/secrets.php` → mounted to `/var/www/secrets.php` |
| `secrets.php` contents | `ANTHROPIC_MODEL`, `ANTHROPIC_VERSION`, `ANTHROPIC_API_KEY`, `ANTHROPIC_API_URL`, `DB_PASSWORD`, `SETUP_TOKEN`, **`DB_USER`** (added today) |
| Containers | Docker Compose: nginx, php, certbot, (fastapi) |

---

## Open Items / Next Session

### Immediate (finish what we started)
1. **Confirm the Edna model fix worked** — if not already run, apply the `secrets.php` `ANTHROPIC_MODEL` → `claude-sonnet-4-6` change and retest.
2. **Make the clean baseline Git commit:**
   - Narrow the `db/` rule in `.gitignore` so the schema SQL files get tracked.
   - `git add` the never-tracked files (`includes/`, `public/api/`, `db/*.sql`, `.gitignore`).
   - First clean commit — history that has never contained a secret.
3. **Clean up leftovers once confident:** remove `~/scp-stack/_attic` and `secrets.php.bak`.

### Production-readiness roadmap (ranked by blast radius)
4. **Automated Postgres backups** — *highest priority, currently none exist.* Nightly `pg_dump` + an off-VPS copy. Only item whose failure mode is permanent data loss.
5. **Verify Let's Encrypt auto-renewal actually fires** and reloads nginx (90-day certs; classic dormant-site outage).
6. **Uptime monitoring** — external pinger on a `/health` endpoint so we hear about outages before the client.
7. **Add a real `/health` endpoint** — exercises DB + model so failures (like a retired model) surface as a monitored 503, optionally validating the configured model against the Models API (`GET /v1/models/{id}`).
8. **Fix / replace the deploy path** — repair the SFTP extension or move to git-pull-on-server / SSH deploy (auditable, no "sync up" footgun).
9. **Versioned DB migrations** — so schema drift (e.g. the past `auth_log` missing-column bug) can't recur.
10. **Dependency + base-image patching cadence** — deliberate, periodic `composer update` / image rebuilds; pin versions.

### Code hygiene / tech debt
- `edna.php` returns `$e->getMessage()` in its error JSON — **leaks internals to the client.** Tighten in the security pass.
- `// TODO: hardcoded` items to move into DB config: allowed CORS origins, the JSON output contract / field definitions per job type (`edna.php`), trackable fields list (`save_quote.php`).

---

## Security Reminders — Do NOT paste into chat
- Contents of `secrets.php` (`DB_PASSWORD`, `ANTHROPIC_API_KEY`, `SETUP_TOKEN`) or `.env`
- The IPsec PSK
- VPS public IP / SCP public IP / full SSH host address
- SSH private key or its passphrase

*(Filenames, command output structure, and non-secret config values like the model name are fine.)*
