<!-- File: .claude/docs/BFE_press_cost_analysis.md  (load on demand — reference, not standing rule) -->

# BFE Press-Cost Analysis → Formula Engine Seed Defaults

**Date:** 2026-06-30
**Purpose:** Extract the press-cost formula structure and *current* seed rates from BFE — the one-time "teacher" job (see Design Reference §7). BFE is **live and actively maintained** (estimates run through 2026); these rates are current SCP rates, not stale. Reconciliation with Avanti post-go-live is about single-source-of-truth, not currency.

**Method:** Parsed `E4X'METH.DBF` (87,537 method rows) joined to `E4X'MAIN.DBF` for estimate dates. Forms jobs only (`PROD_DESC` ∈ SNAP / R/S / CONT). Rates derived empirically, not assumed.

---

## The cost structure (what BFE does, what we replicate as a BASE)

Press cost = fixed setup + quantity-scaled run, both at an effective press rate:

```
press_cost = (make_ready_hours + run_hours) × press_rate
  run_hours = quantity / impressions_per_hour
```

Three data-driven variables, all varying by press: **press_rate ($/hr)**, **make_ready_hours**, **impressions_per_hour**. This is the v1 skeleton — make-ready, run, and everything else gain complexity later (e.g. keyed on parts/colours, not just press).

---

## ⚠️ Methodology lesson: seed from RECENT data only

The full-history (2005–2026) median press rate for Press 3 was **$127/hr** — **wrong as a seed.** It averages 13 years and is dragged down by the old era. Rates have been actively bumped:

| Era | Press 3 snap $/hr |
|---|---|
| 2013–2017 | ~$120 |
| 2018–2021 | ~$140 |
| **2023–2026** | **~$220** |

Seeding at the full-history median would have under-priced every estimate by ~40%. **Always derive seeds from the most recent window (2024–2026 used here).** This is the "don't anchor to the past" principle applied inside the rate derivation itself.

---

## Seed defaults (from 2024–2026 forms estimates only)

| Press | $/hr | make_ready hr | impr/hr | n |
|---|---|---|---|---|
| 3 (Didde 22" 5-col) | **220** | 1.50 | 40,300 | 1,929 |
| 5 (Didde 17") | **220** | 1.29 | 17,100 | 1,941 |
| 11 (Didde 22" 8-col) | **265** | 2.00 | 52,600 | 749 |
| 4 (MVP 14") | **220** | 1.65 | 18,750 | 534 |
| 2 (Didde 17") | **220** | 1.98 | 26,300 | 84 |
| 1 (MVP Memjet) | **100** | 2.30 | 11,300 | 20 |

Press 3 and 5 are the forms workhorses (≈3,900 of 5,257 recent rows). Presses 1/2 have thin recent samples — treat their seeds as soft.

**Other drivers captured (for later tasks, not press):** paper waste median ~12%, press waste ~1,655 impressions. These feed the future material-cost task.

---

## How this maps to the formula engine

```
Variables (inputs):     quantity, press_number   (later: parts, colours)
Rates (admin-tunable):  press_rate[press]          {3:220, 5:220, 11:265, 4:220, 2:220, 1:100}
                        make_ready_hours[press]    {3:1.5, 5:1.3, 11:2.0, ...}
                        impressions_per_hour[press] {3:40000, 5:17000, 11:52600, ...}
Formula:  press_cost = (make_ready_hours[press] + quantity/impressions_per_hour[press]) × press_rate[press]
```

Every right-hand value is a row in `estimating_rates` (admin-editable, no deploy). The formula expression is a row in `estimating_formulas` (storage format deferred — JSON/expression, separate discussion).

**Validation:** computed press cost is checked against Avanti's current cost-center / press-standard rates (`CostCenterFile` / `PressStandards`) — the live authoritative source that supersedes these seeds over time.

---

## Caveats
- Make-ready & impr/hr should eventually key on **parts and colours**, not press alone (a 4-part set sets up slower than 1-part). v1 defaults per-press.
- Presses 1/2 recent n is small — verify against Avanti before trusting.
- These seeds are a **starting point**; Avanti is authoritative going forward (single source of truth, not staleness).
