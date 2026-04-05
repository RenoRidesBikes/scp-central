<?php
/**
 * /modules/forms-estimating/spec_review.php
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

// ── User info ─────────────────────────────────────────────────────────────────
$userName = $_AUTH_USER['name'] ?? 'User';
$userRole = strtolower($_AUTH_USER['role'] ?? 'csr');

// ── Page config ───────────────────────────────────────────────────────────────
$pageTitle  = 'Spec Review';
$activePage = 'forms-estimating';
$navBadges  = [];

// ── Page-specific CSS ─────────────────────────────────────────────────────────
$extraCss = '<style>
/* ── content overrides ── */
.content{padding:24px;flex:1}
.content-inner{max-width:1440px;margin:0 auto;display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}

/* ── breadcrumb ── */
.topbar-left{display:flex;align-items:center;gap:10px;flex:1}
.breadcrumb{font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px}
.breadcrumb a{color:var(--blue-mid);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb-sep{color:var(--border-mid)}
.breadcrumb-current{color:var(--text);font-weight:500}

/* ── page header ── */
.page-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px}
.page-title{font-size:20px;font-weight:600;color:var(--text);letter-spacing:-0.01em}
.page-sub{font-size:13px;color:var(--text-muted);margin-top:2px}
.hdr-actions{display:flex;gap:8px;align-items:center;flex-shrink:0}

/* ── status pills ── */
.status-pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;padding:4px 11px;border-radius:20px;font-family:var(--mono)}
.status-parsing{background:var(--amber-light);color:#633806;border:0.5px solid var(--amber-border)}
.status-ready{background:var(--green-light);color:#27500A;border:0.5px solid var(--green-border)}
.status-missing{background:var(--red-light);color:#791F1F;border:0.5px solid var(--red-border)}
.status-dot{width:6px;height:6px;border-radius:50%;background:currentColor;animation:blink 1s ease-in-out infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0.3}}

/* ── card title override ── */
.card-title{margin-bottom:16px}

/* ── form layout ── */
.form-row{display:flex;gap:12px;margin-bottom:12px}
.form-row:last-child{margin-bottom:0}
.form-group{display:flex;flex-direction:column;gap:5px;flex:1}
.form-group.w2{flex:2}
.form-group.w3{flex:3}
.fl{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;display:flex;align-items:center;gap:5px}
select.fi{cursor:pointer}
textarea.fi{resize:vertical;line-height:1.5}

/* ── confidence field states ── */
.fi-confirmed{border-color:var(--border-mid)}
.fi-suggested{border-color:#EF9F27;background:#FFFBF2}
.fi-missing{border-color:#E24B4A;background:#FFF5F5}
.cf-dot{display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cf-green{background:#639922}
.cf-amber{background:#EF9F27}
.cf-red{background:#E24B4A}
.cf-grey{background:#ccc}

/* ── unit wrap ── */
.fi-unit-wrap{position:relative;display:flex;align-items:center}
.fi-unit-wrap .fi{padding-right:30px}
.fi-unit{position:absolute;right:10px;font-size:12px;color:var(--text-muted);pointer-events:none}

/* ── job type picker ── */
.jt-picker{display:flex;border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);overflow:hidden}
.jt-btn{flex:1;text-align:center;padding:8px 12px;font-size:13px;cursor:pointer;color:var(--text-muted);background:var(--bg-card);transition:all 0.12s;border-right:0.5px solid var(--border);font-family:var(--sans)}
.jt-btn:last-child{border-right:none}
.jt-btn:hover{background:var(--bg-surface);color:var(--text)}
.jt-selected{background:var(--blue-light)!important;color:#0C447C!important;font-weight:600}

/* ── press grid ── */
.press-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-bottom:10px}
.press-card{border:0.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 8px;text-align:center;cursor:pointer;transition:all 0.12s;background:var(--bg-card)}
.press-card:hover{border-color:var(--blue-mid);background:var(--blue-light)}
.press-selected{border-color:var(--blue-mid)!important;background:var(--blue-light)!important;border-width:1.5px!important}
.press-num{font-size:18px;font-weight:600;font-family:var(--mono);color:var(--text)}
.press-selected .press-num{color:#0C447C}
.press-name{font-size:11px;font-weight:600;color:var(--text);margin-top:2px}
.press-spec{font-size:10px;color:var(--text-muted);margin-top:2px;line-height:1.3}
.press-note{font-size:10px;color:var(--text-muted);margin-top:4px}
.press-edna{color:#0C447C!important;font-weight:600!important}
.press-reason{font-size:12px;color:#0C447C;background:var(--blue-light);border:0.5px solid var(--blue-border);border-radius:var(--radius-sm);padding:8px 12px;line-height:1.55}

/* ── finishing grid ── */
.fin-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.fin-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:0.5px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:all 0.12s;background:var(--bg-card)}
.fin-item:hover{border-color:var(--blue-mid)}
.fin-item input[type=checkbox]{width:15px;height:15px;accent-color:var(--blue-mid);flex-shrink:0;cursor:pointer}
.fin-checked{background:var(--bg-surface)}
.fin-suggested{background:#FFFBF2;border-color:#EF9F27}
.fin-body{flex:1}
.fin-name{font-size:13px;font-weight:500;color:var(--text)}
.fin-detail{font-size:11px;color:var(--text-muted);margin-top:1px}
.fin-badge{font-size:10px;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0}
.fin-badge-confirmed{background:var(--green-light);color:var(--green);border:0.5px solid var(--green-border)}
.fin-badge-suggested{background:var(--amber-light);color:#633806;border:0.5px solid var(--amber-border)}

/* ── qty breaks ── */
.break-tags{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.break-tag{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-family:var(--mono);padding:6px 12px;background:var(--bg-surface);border:0.5px solid var(--border);border-radius:20px;color:var(--text)}
.break-tag.edna-tag{background:var(--blue-light);border-color:var(--blue-border);color:#0C447C}
.break-tag-remove{cursor:pointer;color:var(--text-muted);font-size:14px;line-height:1}
.break-tag-remove:hover{color:var(--red)}
.break-add{display:inline-flex;align-items:center;font-size:13px;padding:6px 14px;border:0.5px dashed var(--border-mid);border-radius:20px;color:var(--text-muted);cursor:pointer;transition:all 0.12s;background:transparent}
.break-add:hover{border-color:var(--blue-mid);color:var(--blue-mid);border-style:solid;background:var(--blue-light)}

/* ── button extras ── */
.btn-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.btn-green{background:var(--green);border-color:var(--green);color:#fff}
.btn-green:hover{background:#27500A}

/* ── edna confidence panel ── */
.edna-panel{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:16px;position:sticky;top:calc(var(--topbar) + 24px)}
.edna-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.edna-avatar-sm{width:38px;height:38px;border-radius:50%;background:var(--blue-light);border:2px solid var(--blue-mid);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.edna-panel-name{font-size:14px;font-weight:600;color:var(--text)}
.edna-panel-sub{font-size:11px;color:var(--text-muted)}
.cf-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:14px;text-align:center}
.cf-box{border-radius:var(--radius-sm);padding:8px 4px}
.cf-box-green{background:var(--green-light);border:0.5px solid var(--green-border)}
.cf-box-amber{background:var(--amber-light);border:0.5px solid var(--amber-border)}
.cf-box-red{background:var(--red-light);border:0.5px solid var(--red-border)}
.cf-box-num{font-size:20px;font-weight:600;font-family:var(--mono)}
.cf-box-green .cf-box-num{color:#3B6D11}
.cf-box-amber .cf-box-num{color:#854F0B}
.cf-box-red .cf-box-num{color:#A32D2D}
.cf-box-label{font-size:10px;text-transform:uppercase;letter-spacing:0.05em;margin-top:1px}
.cf-box-green .cf-box-label{color:#3B6D11}
.cf-box-amber .cf-box-label{color:#854F0B}
.cf-box-red .cf-box-label{color:#A32D2D}
.cf-row{display:flex;align-items:center;gap:6px;font-size:12px;padding:4px 8px;border-radius:6px;margin-bottom:3px}
.cf-row-green{background:var(--green-light)}
.cf-row-amber{background:var(--amber-light)}
.cf-row-red{background:var(--red-light)}
.cf-row-grey{background:var(--bg-surface)}
.cf-field{flex:1;color:var(--text-muted)}
.cf-val{font-weight:600;color:var(--text);font-family:var(--mono);font-size:11px}
.edna-notes{border-top:0.5px solid var(--border);padding-top:12px;margin-top:4px}
.edna-notes-label{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
.edna-notes-text{font-size:12px;color:#0C447C;line-height:1.6}

/* ── parsing overlay ── */
.parsing-overlay{position:fixed;inset:0;background:rgba(250,248,245,0.92);display:flex;align-items:center;justify-content:center;z-index:400;flex-direction:column;gap:16px}
.parsing-overlay.hidden{display:none}
.parsing-spinner{width:48px;height:48px;border:3px solid var(--blue-border);border-top-color:var(--blue-mid);border-radius:50%;animation:spin 0.8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.parsing-label{font-size:15px;font-weight:500;color:var(--text)}
.parsing-sub{font-size:13px;color:var(--text-muted)}

/* ── responsive ── */
@media(max-width:1000px){
  .content-inner{grid-template-columns:1fr}
  .edna-panel{position:static}
  .press-grid{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:768px){
  .press-grid{grid-template-columns:repeat(3,1fr)}
  .fin-grid{grid-template-columns:1fr}
  .form-row{flex-direction:column}
}
</style>';

require_once __DIR__ . '/../../../includes/header.php';
?>

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-left">
      <div class="breadcrumb">
        <a href="/modules/forms-estimating/">Estimating</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Spec review</span>
      </div>
    </div>
    <div class="topbar-actions">
      <div id="parse-status" class="status-pill status-parsing" style="display:none">
        <div class="status-dot"></div> Edna is parsing...
      </div>
      <div id="topbar-user" class="topbar-user" onclick="toggleUserMenu()">
        <div class="topbar-avatar"><?= htmlspecialchars($_navInitials) ?></div>
        <span class="topbar-user-name"><?= htmlspecialchars($userName) ?></span>
        <svg class="topbar-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="user-dropdown">
          <div class="dropdown-section-label"><?= htmlspecialchars($userName) ?></div>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="/logout.php">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- PARSING OVERLAY -->
  <div class="parsing-overlay hidden" id="parsing-overlay">
    <div class="parsing-spinner"></div>
    <div class="parsing-label">Edna is reading your spec...</div>
    <div class="parsing-sub" id="parsing-sub">Pulling customer history from Avanti</div>
  </div>

  <div class="content">
    <div class="page-hdr">
      <div>
        <div class="page-title" id="page-title">New quote</div>
        <div class="page-sub" id="page-sub">Review Edna's work, fill any gaps, then run the estimate.</div>
      </div>
      <div class="hdr-actions">
        <div id="missing-pill" style="display:none" class="status-pill status-missing">2 fields need attention</div>
        <a href="/modules/forms-estimating/" class="btn">← Back</a>
        <button class="btn btn-primary" onclick="runEstimate()">Run estimate →</button>
      </div>
    </div>

    <div class="content-inner">

      <!-- ══ LEFT — SPEC FORM ══ -->
      <div>

        <!-- JOB BASICS -->
        <div class="card">
          <div class="card-title">Job basics</div>
          <div class="form-row">
            <div class="form-group w2">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-customer"></span> Customer</label>
              <input class="fi" id="f-customer" placeholder="Customer name">
            </div>
            <div class="form-group w3">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-jobname"></span> Job name / description</label>
              <input class="fi" id="f-jobname" placeholder="Brief description">
            </div>
          </div>
          <div class="form-row" style="margin-bottom:0">
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-jobtype"></span> Job type</label>
              <div class="jt-picker" id="jt-picker">
                <div class="jt-btn" onclick="selectJobType('continuous',this)">Continuous</div>
                <div class="jt-btn" onclick="selectJobType('snapset',this)">Snap Set</div>
                <div class="jt-btn" onclick="selectJobType('sheetfed',this)">Sheetfed</div>
              </div>
            </div>
            <div class="form-group">
              <label class="fl">Quote number</label>
              <input class="fi fi-confirmed" id="f-quotenum" value="Q002848R" style="font-family:var(--mono)">
            </div>
            <div class="form-group">
              <label class="fl">Date</label>
              <input class="fi fi-confirmed" id="f-date">
            </div>
          </div>
        </div>

        <!-- DIMENSIONS & STOCK -->
        <div class="card">
          <div class="card-title">Dimensions &amp; stock</div>
          <div class="form-row">
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-width"></span> Finished width</label>
              <div class="fi-unit-wrap"><input class="fi" id="f-width" placeholder="0.00"><span class="fi-unit">in</span></div>
            </div>
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-depth"></span> Finished depth</label>
              <div class="fi-unit-wrap"><input class="fi" id="f-depth" placeholder="0.00"><span class="fi-unit">in</span></div>
            </div>
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-parts"></span> Parts / plies</label>
              <input class="fi" id="f-parts" placeholder="—">
            </div>
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-ncrtype"></span> NCR type</label>
              <select class="fi" id="f-ncrtype">
                <option value="">Select...</option>
                <option>CB / CFB / CF (3-part)</option>
                <option>CB / CF (2-part)</option>
                <option>CB / CFB / CFB / CF (4-part)</option>
                <option>N/A</option>
              </select>
            </div>
          </div>
          <div class="form-row" style="margin-bottom:0">
            <div class="form-group w2">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-stock"></span> Paper stock</label>
              <input class="fi" id="f-stock" placeholder="—">
            </div>
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-inkfront"></span> Ink — front</label>
              <input class="fi" id="f-inkfront" placeholder="—">
            </div>
            <div class="form-group">
              <label class="fl"><span class="cf-dot cf-grey" id="dot-inkback"></span> Ink — back</label>
              <input class="fi" id="f-inkback" placeholder="—">
            </div>
          </div>
        </div>

        <!-- PRESS -->
        <div class="card">
          <div class="card-title">Press assignment</div>
          <div class="press-grid" id="press-grid">
            <div class="press-card" onclick="selectPress(1,this)">
              <div class="press-num">1</div><div class="press-name">MVP</div>
              <div class="press-spec">11" cutoff · Memjet</div><div class="press-note">Rarely used</div>
            </div>
            <div class="press-card" onclick="selectPress(2,this)">
              <div class="press-num">2</div><div class="press-name">Didde</div>
              <div class="press-spec">17" web</div><div class="press-note">1–2 colour</div>
            </div>
            <div class="press-card" onclick="selectPress(3,this)">
              <div class="press-num">3</div><div class="press-name">Didde</div>
              <div class="press-spec">22" web · 5 colour</div><div class="press-note" id="press-3-note">—</div>
            </div>
            <div class="press-card" onclick="selectPress(4,this)">
              <div class="press-num">4</div><div class="press-name">MVP</div>
              <div class="press-spec">14" cutoff</div><div class="press-note">Short run</div>
            </div>
            <div class="press-card" onclick="selectPress(5,this)">
              <div class="press-num">5</div><div class="press-name">Didde</div>
              <div class="press-spec">17" web</div><div class="press-note">Backup</div>
            </div>
            <div class="press-card" onclick="selectPress(11,this)">
              <div class="press-num">11</div><div class="press-name">Didde</div>
              <div class="press-spec">22" web · 8 colour</div><div class="press-note">Full colour</div>
            </div>
          </div>
          <div class="press-reason" id="press-reason" style="display:none"></div>
        </div>

        <!-- FINISHING -->
        <div class="card">
          <div class="card-title">Finishing operations</div>
          <div class="fin-grid" id="fin-grid">
            <label class="fin-item" id="fin-perf">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Perforation</div><div class="fin-detail" id="fin-perf-detail">Top, bottom, or none?</div></div>
            </label>
            <label class="fin-item" id="fin-pad">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Padding</div><div class="fin-detail">Sets of 25, 50, or 100?</div></div>
            </label>
            <label class="fin-item" id="fin-collate">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Collating / interleaving</div><div class="fin-detail">Required for multi-part NCR</div></div>
            </label>
            <label class="fin-item" id="fin-number">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Numbering</div><div class="fin-detail">Sequential per set</div></div>
            </label>
            <label class="fin-item" id="fin-drill">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Drilling</div><div class="fin-detail">3-hole or custom pattern</div></div>
            </label>
            <label class="fin-item" id="fin-shrink">
              <input type="checkbox" onchange="toggleFin(this)">
              <div class="fin-body"><div class="fin-name">Shrink wrap</div><div class="fin-detail">Individual pad wrapping</div></div>
            </label>
          </div>
        </div>

        <!-- QTY BREAKS -->
        <div class="card">
          <div class="card-title">Quantity breaks</div>
          <div class="break-tags" id="break-tags">
            <span class="break-add" onclick="addBreak()">+ Add quantity break</span>
          </div>
          <div class="auto-tag" style="margin-top:10px">
            <div class="auto-dot"></div>
            Each break gets its own pricing band on the next screen
          </div>
        </div>

        <div class="btn-row" style="margin-top:4px">
          <button class="btn btn-primary" onclick="runEstimate()">Run estimate →</button>
          <button class="btn" onclick="reparseWithEdna()">↺ Re-parse with Edna</button>
        </div>

      </div>

      <!-- ══ RIGHT — EDNA CONFIDENCE ══ -->
      <div>
        <div class="edna-panel" id="edna-panel">
          <div class="edna-panel-header">
            <div class="edna-avatar-sm">👩‍💼</div>
            <div>
              <div class="edna-panel-name">Edna's confidence</div>
              <div class="edna-panel-sub" id="edna-panel-sub">Waiting to parse...</div>
            </div>
          </div>
          <div class="cf-summary">
            <div class="cf-box cf-box-green"><div class="cf-box-num" id="cf-confirmed">—</div><div class="cf-box-label">Confirmed</div></div>
            <div class="cf-box cf-box-amber"><div class="cf-box-num" id="cf-suggested">—</div><div class="cf-box-label">Suggested</div></div>
            <div class="cf-box cf-box-red"><div class="cf-box-num" id="cf-missing">—</div><div class="cf-box-label">Missing</div></div>
          </div>
          <div id="cf-rows" style="margin-bottom:14px"></div>
          <div class="edna-notes">
            <div class="edna-notes-label">Edna's notes</div>
            <div class="edna-notes-text" id="edna-notes">Paste a job description on the previous screen and hit "Let Edna take it from here" — I'll fill in everything I can and flag what I need from you.</div>
          </div>
        </div>
        <button class="btn btn-primary" style="width:100%;display:flex;justify-content:center;padding:11px" onclick="runEstimate()">Run estimate →</button>
        <div style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:8px">Suggested fields will be confirmed</div>
      </div>

    </div>
  </div>

<script>
/* ── DATE ── */
document.getElementById('f-date').value = new Date().toISOString().split('T')[0];

/* ── PRESS ── */
const pressReasons = {
  1:  "Press 1 (MVP Memjet) — rarely used for forms work. Only if specifically requested.",
  2:  "Press 2 (Didde 17\") — good for 1–2 colour narrow web. Under-powered for wide jobs.",
  3:  "Press 3 (Didde 22\" · 5 colour) — best fit for most snap set and continuous work.",
  4:  "Press 4 (MVP 14\" cutoff) — short run specialist. Cost-effective at lower quantities.",
  5:  "Press 5 (Didde 17\") — backup to Press 2. Narrow web, 1–2 colour.",
  11: "Press 11 (Didde 22\" · 8 colour) — overkill for 1-colour work. Reserve for full colour."
};
function selectPress(num, el) {
  document.querySelectorAll('.press-card').forEach(c => c.classList.remove('press-selected'));
  el.classList.add('press-selected');
  const r = document.getElementById('press-reason');
  r.style.display = 'block';
  r.textContent = pressReasons[num] || '';
}

/* ── JOB TYPE ── */
let currentJobType = null;

// Maps picker value → DB module key for edna_prompts job_type layer
const jobTypeMap = {
  continuous: 'forms_continuous',
  snapset:    'forms_snap_set',
  sheetfed:   'forms_sheetfed'
};

function selectJobType(type, el) {
  currentJobType = type;
  document.querySelectorAll('.jt-btn').forEach(b => b.classList.remove('jt-selected'));
  el.classList.add('jt-selected');
  setDot('dot-jobtype', 'confirmed');
}

/* ── FINISHING ── */
function toggleFin(cb) {
  const item = cb.closest('.fin-item');
  item.classList.toggle('fin-checked', cb.checked);
  item.classList.remove('fin-suggested');
}

/* ── BREAKS ── */
function addBreak() {
  const val = prompt('Enter quantity:');
  if (!val || isNaN(val)) return;
  insertBreakTag(parseInt(val).toLocaleString(), false);
}
function insertBreakTag(label, isEdna) {
  const tags   = document.getElementById('break-tags');
  const addBtn = tags.querySelector('.break-add');
  const tag    = document.createElement('span');
  tag.className = 'break-tag' + (isEdna ? ' edna-tag' : '');
  tag.innerHTML = label + ' <span class="break-tag-remove" onclick="this.parentElement.remove()">×</span>';
  tags.insertBefore(tag, addBtn);
}

/* ── CONFIDENCE DOTS ── */
function setDot(id, state) {
  const dot = document.getElementById(id);
  if (!dot) return;
  dot.className = 'cf-dot';
  if      (state === 'confirmed') dot.classList.add('cf-green');
  else if (state === 'suggested') dot.classList.add('cf-amber');
  else if (state === 'missing')   dot.classList.add('cf-red');
  else                            dot.classList.add('cf-grey');
}

function setField(id, value, state) {
  const el = document.getElementById(id);
  if (!el) return;
  el.value = value || '';
  el.className = 'fi';
  if      (state === 'confirmed') el.classList.add('fi-confirmed');
  else if (state === 'suggested') el.classList.add('fi-suggested');
  else if (state === 'missing')   el.classList.add('fi-missing');
}

/* ── POPULATE FORM FROM AI RESPONSE ── */
function populateForm(spec) {
  if (spec.customer) { setField('f-customer', spec.customer, 'confirmed'); setDot('dot-customer', 'confirmed'); }
  else setDot('dot-customer', 'missing');

  if (spec.job_name) { setField('f-jobname', spec.job_name, 'confirmed'); setDot('dot-jobname', 'confirmed'); }

  if (spec.job_type) {
    document.querySelectorAll('.jt-btn').forEach(b => {
      b.classList.remove('jt-selected');
      const norm = s => s.toLowerCase().replace(/[\s_-]/g, '');
      if (norm(b.textContent) === norm(spec.job_type)) b.classList.add('jt-selected');
    });
    setDot('dot-jobtype', spec.job_type_confidence || 'confirmed');
  }

  if (spec.width)  { setField('f-width',  spec.width,  spec.width_confidence  || 'confirmed'); setDot('dot-width',  spec.width_confidence  || 'confirmed'); } else setDot('dot-width',  'missing');
  if (spec.depth)  { setField('f-depth',  spec.depth,  spec.depth_confidence  || 'confirmed'); setDot('dot-depth',  spec.depth_confidence  || 'confirmed'); } else setDot('dot-depth',  'missing');
  if (spec.parts)  { setField('f-parts',  spec.parts,  spec.parts_confidence  || 'suggested'); setDot('dot-parts',  spec.parts_confidence  || 'suggested'); } else setDot('dot-parts',  'missing');

  if (spec.ncr_type) {
    const sel = document.getElementById('f-ncrtype');
    for (let o of sel.options) {
      if (o.text.toLowerCase().includes(spec.ncr_type.toLowerCase())) { sel.value = o.value; break; }
    }
    setDot('dot-ncrtype', spec.ncr_type_confidence || 'suggested');
  } else setDot('dot-ncrtype', 'missing');

  if (spec.stock)     { setField('f-stock',    spec.stock,     spec.stock_confidence     || 'suggested'); setDot('dot-stock',    spec.stock_confidence     || 'suggested'); } else setDot('dot-stock',    'missing');
  if (spec.ink_front) { setField('f-inkfront', spec.ink_front, spec.ink_front_confidence || 'confirmed'); setDot('dot-inkfront', spec.ink_front_confidence || 'confirmed'); } else setDot('dot-inkfront', 'missing');
  if (spec.ink_back)  { setField('f-inkback',  spec.ink_back,  spec.ink_back_confidence  || 'confirmed'); setDot('dot-inkback',  spec.ink_back_confidence  || 'confirmed'); }

  if (spec.press) {
    document.querySelectorAll('.press-card').forEach(c => {
      c.classList.remove('press-selected');
      if (c.querySelector('.press-num').textContent == spec.press) {
        c.classList.add('press-selected');
        const note = c.querySelector('.press-note');
        note.textContent = '✦ Edna\'s pick';
        note.className = 'press-note press-edna';
      }
    });
    const r = document.getElementById('press-reason');
    r.style.display = 'block';
    r.textContent = spec.press_reason || '';
  }

  // finishing — Edna returns a plain string; scan it for known operations and tick the checkboxes
  if (spec.finishing && typeof spec.finishing === 'string') {
    const lower = spec.finishing.toLowerCase();
    const finMap = { perforation:'fin-perf', padding:'fin-pad', collating:'fin-collate', numbering:'fin-number', drilling:'fin-drill', 'shrink wrap':'fin-shrink' };
    Object.entries(finMap).forEach(([keyword, id]) => {
      if (lower.includes(keyword)) {
        const el = document.getElementById(id);
        if (!el) return;
        el.querySelector('input').checked = true;
        el.classList.add('fin-checked', 'fin-suggested');
        const badge = document.createElement('span');
        badge.className = 'fin-badge fin-badge-suggested';
        badge.textContent = 'Edna added';
        el.appendChild(badge);
      }
    });
    // show full finishing string in perf detail as a note
    const perfDetail = document.getElementById('fin-perf-detail');
    if (perfDetail) perfDetail.textContent = spec.finishing;
  }

  // qty breaks — Edna returns as "quantities"; fall back to qty_breaks for future compatibility
  const breaks = spec.quantities || spec.qty_breaks || [];
  breaks.forEach(q => insertBreakTag(parseInt(q).toLocaleString(), true));

  updateConfidencePanel(spec);
}

function updateConfidencePanel(spec) {
  // Build counts from individual field confidences — Edna doesn't return confidence_counts
  const fields = [
    spec.job_type_confidence, spec.width_confidence, spec.depth_confidence,
    spec.parts_confidence, spec.ncr_type_confidence, spec.stock_confidence,
    spec.ink_front_confidence, spec.ink_back_confidence,
    spec.perforation_confidence, spec.finishing_confidence
  ];
  let confirmed = 0, suggested = 0, missing = 0;
  fields.forEach(f => {
    if (!f || f === 'missing') missing++;
    else if (f === 'suggested') suggested++;
    else confirmed++;
  });
  if (spec.customer) confirmed++; else missing++;

  document.getElementById('cf-confirmed').textContent = confirmed;
  document.getElementById('cf-suggested').textContent = suggested;
  document.getElementById('cf-missing').textContent   = missing;
  document.getElementById('edna-panel-sub').textContent = 'Parsed from your input';

  if (missing > 0) {
    const pill = document.getElementById('missing-pill');
    pill.style.display = 'inline-flex';
    pill.textContent = missing + ' field' + (missing > 1 ? 's' : '') + ' need attention';
  }

  // Build cf-rows from what we have; use confidence_rows if Edna ever returns them
  const rows = spec.confidence_rows || [
    { label:'Customer',  value: spec.customer   || '—', state: spec.customer ? 'confirmed' : 'missing' },
    { label:'Job type',  value: spec.job_type   || '—', state: spec.job_type_confidence   || 'missing' },
    { label:'Width',     value: spec.width      || '—', state: spec.width_confidence      || 'missing' },
    { label:'Depth',     value: spec.depth      || '—', state: spec.depth_confidence      || 'missing' },
    { label:'Parts',     value: spec.parts      || '—', state: spec.parts_confidence      || 'missing' },
    { label:'Stock',     value: spec.stock      || '—', state: spec.stock_confidence      || 'missing' },
    { label:'Ink front', value: spec.ink_front  || '—', state: spec.ink_front_confidence  || 'missing' },
    { label:'Finishing', value: spec.finishing  || '—', state: spec.finishing_confidence  || 'missing' },
  ];

  const rowsEl = document.getElementById('cf-rows');
  rowsEl.innerHTML = rows.map(r => `
    <div class="cf-row cf-row-${r.state}">
      <span class="cf-dot cf-${r.state === 'confirmed' ? 'green' : r.state === 'suggested' ? 'amber' : 'red'}"></span>
      <span class="cf-field">${r.label}</span>
      <span class="cf-val">${r.value}</span>
    </div>`).join('');

  // handle both edna_note (current) and edna_notes (future/legacy)
  document.getElementById('edna-notes').textContent = spec.edna_note || spec.edna_notes || '';
}

/* ── REAL AI PARSE ── */
let lastPromptVersionIds = null; // stored for save_quote.php

async function parseWithAI(customer, description) {
  const overlay     = document.getElementById('parsing-overlay');
  const parseStatus = document.getElementById('parse-status');
  overlay.classList.remove('hidden');
  parseStatus.style.display = 'inline-flex';

  const subMsgs = ['Reading your job description...', 'Checking customer history...', 'Selecting best press...', 'Building confidence model...'];
  let i = 0;
  const subEl  = document.getElementById('parsing-sub');
  const ticker = setInterval(() => { subEl.textContent = subMsgs[i++ % subMsgs.length]; }, 1200);

  const userMsg       = `Customer: ${customer || 'not specified'}\nJob description: ${description}`;
  const mappedJobType = currentJobType ? jobTypeMap[currentJobType] : null;

  try {
    const payload = { module: 'forms_estimating', messages: [{ role: 'user', content: userMsg }] };
    if (mappedJobType) payload.job_type = mappedJobType;

    const response = await fetch('/api/edna.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });

    clearInterval(ticker);
    overlay.classList.add('hidden');
    parseStatus.style.display = 'none';

    if (!response.ok) throw new Error('API error ' + response.status);
    const data = await response.json();

    lastPromptVersionIds = data.prompt_version_ids || null;

    const spec = JSON.parse(data.content?.[0]?.text?.replace(/```json|```/g, '').trim() || '{}');
    populateForm(spec);

    if (spec.customer) document.getElementById('page-title').textContent = 'Spec review — ' + spec.customer;

  } catch (err) {
    clearInterval(ticker);
    overlay.classList.add('hidden');
    parseStatus.style.display = 'none';
    console.error(err);
    loadDemoSpec();
    document.getElementById('edna-notes').textContent =
      "Couldn't reach the API — showing demo data. Check that /api/edna.php is reachable and secrets.php is configured.";
  }
}

/* ── BLANK SPEC (fallback / direct navigation) ── */
function loadDemoSpec() {
  // No demo data — show blank form and let CSR describe the job
  document.getElementById('edna-notes').textContent =
    'Describe the job on the previous screen and I\'ll parse it — or fill in the fields manually.';
}

function reparseWithEdna() {
  const desc = prompt('Paste updated job description:');
  if (desc) parseWithAI(document.getElementById('f-customer').value, desc);
}

function runEstimate() {
  alert('Pricing screen coming next! 🚀');
}

/* ── ON LOAD ── */
window.addEventListener('load', () => {
  const pending = sessionStorage.getItem('scp_parse_job');
  if (pending) {
    sessionStorage.removeItem('scp_parse_job');
    const { customer, description } = JSON.parse(pending);
    parseWithAI(customer, description);
  } else {
    loadDemoSpec();
  }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
