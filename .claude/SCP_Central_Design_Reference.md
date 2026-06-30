<!-- File: docs/SCP_Central_Design_Reference.md  (suggested home: a docs/ folder in the repo, or .claude/docs/ — load on demand, not every session) -->

# SCP Central — Design & Domain Reference

The **why** behind the code. This captures design decisions and printing-domain knowledge that aren't derivable from the source alone — so the schema comments, the Edna prompt structure, and the Avanti mappings make sense in context. This is reference material (load on demand); standing rules live in `CLAUDE.md`, and build status lives in the session summary.

---

## 1. What this project is

Replacing **BFE** — a DOS-era FoxPro estimating system — at **Still Creek Press (SCP)**, a large independent commercial printer in Burnaby, BC. Partners: Cam McClean, Ted Mason. Built by Steve (freelance full-stack; former 10-yr SCP employee, manages their Avanti Classic MIS and built their fulfillment/proofing systems).

The product is a modern web estimating system fronted by an AI assistant named **Edna** — persona: "40 years on the press floor." She parses a plain-language job description, fills in the spec, picks a press, and proposes pricing; a human CSR reviews and confirms.

---

## 2. Product philosophy (drives every UX decision)

- **AI-forward, human-confirmed.** Edna *always arrives with an answer*. The CSR reviews and confirms rather than entering from scratch. Never make the human do what Edna can propose.
- **Minimum data entry — the system figures out the rest.** A CSR should type the least possible; inference fills the gaps. Treat every required field as a chance to infer instead of ask.
- **Edna never blocks.** She lives in a permanent **70/30 right pane** — present, not modal. Workflow continues whether or not the user engages her.
- **Every field carries a confidence state:** `confirmed` / `suggested` / `missing` — rendered green / amber / red. "Suggested" means Edna inferred it; the CSR can accept or correct.
- **Pricing is a band, not a number:** **floor → recommended → ceiling**. Edna's *tone shifts* as the price approaches the ceiling (more cautionary).
- **Won/lost is auto-detected**, never hand-entered — see §6. Outcomes feed back into Edna's pricing intuition.

---

## 3. Domain model — job types

Three job types; **Phase 1 covers Continuous + Snap Set only**:

- **Continuous** — web-fed continuous business forms (fan-folded, pin-feed).
- **Snap Set** — cut-sheet NCR / multi-part forms (glued sets).
- **Sheetfed** — offset/digital sheet work. *(Phase 2.)*

Legacy BFE `PROD_CODE`s are deliberately discarded — the new system models job types cleanly rather than inheriting FoxPro codes. The menu groups these under **"Forms Estimating"** (module = `forms_estimating`), with the three job types beneath.

---

## 4. Estimating domain knowledge (canonical reference)

> Currently embedded in `edna.php`'s system-prompt string; **slated to move into DB config per job type** (`// TODO: hardcoded`). This section is the canonical source either way.

**Presses (Still Creek Press equipment):**
| Press | Machine | Use |
|---|---|---|
| 1 | MVP Memjet, 11" cutoff | rarely used for forms |
| 2 | Didde, 17" web | 1–2 colour narrow web |
| 3 | Didde, 22" web, 5 colour | **primary forms press** — most snap-set & continuous |
| 4 | MVP, 14" cutoff | short-run specialist |
| 5 | Didde, 17" web | backup to Press 2, narrow web 1–2 colour |
| 11 | Didde, 22" web, 8 colour | full-colour jobs only |

**NCR types** (use these exact strings):
- `CB / CF` (2-part)
- `CB / CFB / CF` (3-part)
- `CB / CFB / CFB / CF` (4-part)
- `N/A` — non-NCR (continuous, sheetfed, single-part). Always `N/A` for continuous.
- Infer from part count if unstated: 2-part=CB/CF, 3-part=CB/CFB/CF, 4-part=CB/CFB/CFB/CF.

**Finishing operations** (the only valid set):
- **perforation** — always specify location (top/bottom/left/right/centre)
- **padding** — glued at head; specify set size (25/50/100). Treat "padded / pads of / glued in sets / books of" as padding.
- **collating** — interleaving NCR plies in order. **Always include for any multi-part NCR job** — automatic and non-negotiable.
- **numbering** — sequential per set; only if mentioned/implied.
- **drilling** — hole punching; only if mentioned.
- **shrink wrap** — only if mentioned.

**Quantity-break defaults** (if unspecified):
- snap set: 5 000 / 10 000 / 25 000
- continuous: 10 000 / 25 000 / 50 000
- sheetfed: 500 / 1 000 / 2 500

---

## 5. Edna — AI architecture

- **Proxy pattern:** `public/api/edna.php` is a server-side proxy. The Anthropic API key never reaches the browser — it lives in `secrets.php` outside the web root.
- **Layered system prompt, composed at runtime:** `base (global) + module + job_type`. Layers are rows in the **`edna_prompts`** table, loaded and concatenated per request. `edna.php` is **stateless** — it holds no prompt text itself; everything comes from the DB. This is the "everything is data" principle (§9) applied to the AI.
  - On a first parse the job_type may be unknown → load `base + module` only. Once known (clarification round), the `job_type` layer is added.
  - The IDs of the prompt rows used are returned as `prompt_version_ids` so a saved quote records *exactly which prompt version produced it*.
- **Output contract:** Edna returns strict JSON — every field paired with a `_confidence` value (`confirmed`/`suggested`/`missing`), plus `press` + `press_reason`, `quantities`, and a one-line `edna_note` flagging anything unusual or missing. *(The field list is currently hardcoded in `edna.php`; slated to move to DB per job type.)*
- **Learning loop:** `edna_field_corrections` records where a CSR overrode Edna's suggestion. `edna_analysis` (JSONB) stores Edna's reasoning on the quote/spec/pricing rows. Over time, corrections + won/lost outcomes tune her suggestions. Backlog: `edna_prompt_suggestions` table + an admin review UI so prompt improvements are curated, not silent.
- **Customer guard:** Edna only returns a customer if the description clearly matches a known name; otherwise `null`. (Real customer resolution comes from Avanti — §6 — not from Edna guessing.)

---

## 6. Data architecture — two homes (this is the part that confuses readers of the schema)

There are **two databases**, and knowing which owns what is essential:

**A) PostgreSQL `scp_central`** — the app's *own* database. Owns quotes, specs, pricing, outcomes, auth/RBAC, and Edna's prompt + correction tables. DB user `scpadmin`.

Key tables: `quotes` (headers, all job types) · `forms_specs` (Continuous + Snap Set detail) · `quote_finishing` · `quote_pricing` (one row per quantity break) · `quote_outcomes` (won/lost/expired) · `bfe_estimates` (historical import target) · `edna_prompts` · `edna_field_corrections` · plus the auth/RBAC tables (§8).

**B) Avanti Classic MSSQL** — SCP's existing MIS. **Read-only, accessed live over the IPsec tunnel — never copied into Postgres.** Customers, presses, and stocks are *authoritative in Avanti*; SCP Central reads them on demand. This is why the schema has Avanti mappings in comments rather than tables: those entities live in Avanti.

| What we need | Avanti table | Field / note |
|---|---|---|
| Customer code | `ClientFile` | `LongCode` VARCHAR(16) — e.g. `CBCAA`; filter `WHERE LongCode LIKE 'C%'` |
| Customer name | `ClientFile` | `CompLongName` CHAR(64) |
| Quote number | `EstimatinInformation` | `QuoteNo` CHAR(8) — auto-generated by Avanti |
| Won/lost flag | `EstimatinInformation` | `OpenWonLostFlag` |
| Quote→Job link | `JobMaster` | `OrgQuoteNumber` |
| BFE link | `EstimatinInformation` | `OrigEstNumber` = original BFE estimate number |

Gotchas (real, not typos on our end):
- **`EstimatinInformation` is the actual table name** — misspelled in Avanti's schema. Don't "correct" it.
- Customer codes: `C` prefix = customer, `V` = vendor, 8 chars. Use `LongCode`; **ignore legacy `ClientFile.Code` (CHAR(5))**. `JobMaster.CustomerCode` still uses the old CHAR(5) and would need translation.
- **Won/lost detection:** a quote is *won* if a `JobMaster` row exists where `OrgQuoteNumber` matches our quote number. This is the auto-detection that replaces manual CSR entry.
- Avanti sandbox and production are different databases on the same SQL Server.

---

## 7. BFE historical data — teacher, not foundation (REVISED 2026-06-30)

> **This section was reversed during the estimating-engine planning session.** The original plan (import 30k+ BFE estimates into a `bfe_estimates` table as "the empirical basis for Edna's pricing") is **dropped.** Rationale below. The `bfe_estimates` table is **not** built and BFE is **never** a live pricing dependency.

**The data:** ~30k+ active + 80k+ archived BFE estimates in FoxPro DBF files (`E4X'MAIN/METH/PART`, `PAPER`, `CMASTER`, `ORDERS`) — local reference copies at `D:\dev\stillcreekpress\reference\bfe`. Each estimate is a full itemized cost-build (`E4X'METH` has materials, labour hours, burden, speeds, waste, and `SELL_PER_M`).

**BFE is LIVE, not legacy-stale.** Estimates run through 2026 (verified — steady volume 2013→2026), and rates are actively maintained (Press 3 snap $/hr: ~$120 in 2013–17 → ~$220 in 2023–26). So BFE's *current* rates are valid seed defaults. **Seed only from the recent window (2024–26)** — the full-history median under-prices by ~40% (the "anchor to the past" trap, in miniature; see `.claude/docs/BFE_press_cost_analysis.md`).

**Why we still don't *keep* it.** Maintaining rates in two systems post-go-live is the affliction we're removing. **Avanti has everything BFE has, but as the single live source** — estimated cost, *actual* run cost, sell price, won/lost outcome on recent jobs. BFE↔Avanti are already linked via **`EstimatinInformation.OrigEstNumber`** (the BFE estimate number is stored in the Avanti job), so even BFE-originated history is reachable through the *live* system. The reconciliation with Avanti is about **single source of truth**, not staleness.

**BFE lifecycle (the discipline — don't let the crutch become load-bearing):**
| Phase | Role | Lives where |
|---|---|---|
| Build / now | **Teacher** — mine locally to derive formula structure + starting default rates so v1 isn't blank | Local DBF study only (no import) |
| Testing → early go-live | *Optional* disposable reference — only if testing shows we want it; built behind a clear seam, never in the pricing path | Deferred — not built speculatively |
| Post-go-live | **Archived** — cold storage (the DBFs offline), nothing in the live system depends on it | Offline |

**Go-live checklist gets an explicit gate:** "archive BFE; confirm nothing in the pricing path depends on it."

**The empirical layer is Avanti** (read-only, live, over the tunnel — §6), reached for comparables / validation / future ML rate-refinement using *current* data. Whether we ever want a BFE reference during testing is deferred until we can measure Avanti's recent-forms-job volume over the live connection.

---

## 8. Auth & RBAC — permission bitmap design

Phase 1 auth: local session-based, PHP sessions, **bcrypt** (PHP `password_hash`/`password_verify` — never Postgres `crypt()`), configurable timeout, 8-hr session with a 15-min warning modal. Five roles: **CSR, Senior Estimator, Manager, Partner, Admin**. SSO (LDAP over the tunnel) is a documented Phase-2 option.

**The permission model is a scoped 64-bit bitmap:**
- Every **resource** (page, card/feature, or field) has a 64-bit permission integer; every **role** has one per resource. Access check = **bitwise AND**.
- Bit positions are **scoped per `resource_key`** — each resource gets its own 64-bit space, so there's no global bit-exhaustion limit.
- **Resolution order:** user override → role default → parent inheritance (field → card → page) → deny (0). **A user override always wins.**
- Bit definitions (confirmed): page-level universal = READ(0) WRITE(1) DELETE(2) EXPORT(3) APPROVE(4); Pricing card adds VIEW_MARGIN(3) VIEW_COST(4) APPROVE_CEILING(5) OVERRIDE_FLOOR(6); Edna card adds OVERRIDE_CONFIRMED(2) DISMISS_WARNING(3) RETRIGGER_EDNA(4); field-level adds AUDIT(3) *(a flag, not a gate)*.
- **`permission_registry` drives the Admin UI dynamically** — add a bit to the registry and its checkbox appears everywhere automatically. **No hardcoded bit lists in the UI.**
- Bits are named PHP constants (e.g. `PERM_VIEW_MARGIN = 1 << 3`). **`hasPermission()` is the only permitted gate check — never inline raw bitwise checks.**
- RBAC tables: `roles`, `users`, `role_permissions`, `user_permissions`, `permission_registry`, `auth_log`, `permission_change_log`.
- **Admin Permissions page** (Admin-only), four tabs: Role Permissions (checkbox grid per resource) · User Overrides (amber-highlighted deltas from role default) · Permission Registry (define/add bits) · Audit Log (permission changes + auth log).

---

## 9. Conventions (rationale; the enforceable short form is in CLAUDE.md)

- **Everything is data, no hardcoding.** Prompts, field definitions, permissions, press/finishing references — all belong in DB config, not in code. The system should let an admin change behaviour without a deploy. Where something *must* be hardcoded temporarily, mark it `// TODO: hardcoded` so it's findable.
- **UI labels vs dev labels are separate.** What the user sees (e.g. button copy "Let Edna take it from here →") is presentation; the dev identifier (`module = forms_estimating`) is stable. Don't couple them.
- These two principles are *why* `edna.php` loads prompts from a table and why the Admin permissions UI is registry-driven.

---

## 10. Quote lifecycle

```
draft → reviewed → sent → won / lost / expired
```
`won`/`lost` are set by the Avanti sync (§6), not by hand. `expired` is time-based.

---

## 10a. Estimate immutability — a first-class rule (added 2026-06-30)

**A saved estimate is a frozen snapshot. It never recomputes.** If an admin changes a rate or formula tomorrow, every estimate saved before that must still display *exactly* what it showed when saved.

How: **stamp everything at save time.** A saved estimate stores not a reference to the live formula engine, but the resolved math itself —
- the input spec (qty, press, parts…),
- **every rate value used**, copied in (press_rate=220, make_ready=1.5, impr/hr=40000…) — not looked up live,
- the **formula version ID** that produced it,
- the computed outputs (press_cost, sell, the floor/recommended/ceiling band).

The estimate is self-contained — it carries its own math. The live formula engine is consulted only to **create** an estimate, never to **display** one. A "re-estimate" is an explicit user action that produces a **new** snapshot; it never mutates the old one.

This mirrors the existing `prompt_version_ids` pattern on quotes (which records exactly which Edna prompt produced a parse). BFE and Avanti both *lack* this — reopening an old estimate there can shift numbers under you when rates change. We deliberately do not.

**Formula storage format is deferred** — JSON or a stored expression for the v1 slice; a full admin-writes-free-form-expressions engine is its own future topic. What is *not* deferred is the stamping rule above.

## 11. Phasing & parallel work

- **Phase 1:** Forms Estimating (Continuous + Snap Set), local auth, Edna parse → spec review → pricing → save, Avanti customer/press lookup (read-only), BFE import.
- **Phase 2:** Sheetfed/Digital/Wide Format, full RBAC admin, Avanti quote *push* (write-back), SSO, won/lost feedback fully wired into pricing.
- **Parallel workstream:** an **n8n-based AP automation** (PO/invoice matching against Avanti SQL) — separate PRD, shares the same VPS and Avanti tunnel. Out of scope for the estimating build but shares infrastructure.

---

## 12. Reference artifacts that exist
- `SCP_Estimating_Demo.html` — the static, fully-designed UI demo (Dashboard, Spec Review with confidence dots + press picker + finishing ops + qty breaks, Pricing band, All Quotes, Admin). Hardcoded data; it's the design target for the PHP build.
- A formatted PRD (`scp_estimating_prd_v2.docx`) — 13 sections.
- Design system: IBM Plex Sans + Plex Mono; confidence colour tokens (green/amber/red); status badge system.
