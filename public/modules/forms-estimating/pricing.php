<?php
/*
 * /modules/forms-estimating/pricing.php
 *
 * Pricing screen for the forms-estimating slice.
 * Reads the sandbox estimate stashed by spec_review runEstimate()
 * (sessionStorage key scp_estimate) and renders the cost band + slider.
 *
 * LIVE (from our formula engine):
 *   - Cost to produce  = break.cost_total
 *   - Cost-plus        = break.price_total (cost + per-op markup)
 *   - The break tabs, the slider, the floor and cost-plus band markers.
 *
 * PLACEHOLDER (Avanti / Edna layer deferred, marked "not live"):
 *   - Edna suggests, customer ceiling, quote history, estimated-vs-actual.
 *   These are derived per break as simple multiples of cost until the
 *   Avanti read layer + Edna judgment are wired.
 *
 * Sandbox only. Nothing is saved. Lock/save endpoint is a later step.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

$userName = $_AUTH_USER['name'] ?? 'User';
$userRole = strtolower($_AUTH_USER['role'] ?? 'csr');

$pageTitle  = 'Pricing';
$activePage = 'forms-estimating';
$navBadges  = [];

$extraCss = '<style>
.content{padding:24px;flex:1}
/* Override the global two-column .content-inner grid from header.php.
   This page manages its own layout (metric row + grid2). */
.content-inner{display:block!important;grid-template-columns:none!important;max-width:1440px;margin:0 auto}

.topbar-left{display:flex;align-items:center;gap:10px;flex:1}
.breadcrumb{font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px}
.breadcrumb a{color:var(--blue-mid);text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb-sep{color:var(--border-mid)}
.breadcrumb-current{color:var(--text);font-weight:500}

.page-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px}
.page-title{font-size:22px;font-weight:600}
.page-sub{font-size:13px;color:var(--text-muted);margin-top:3px}

.pr-tabs{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.pr-tab{font-family:var(--mono);font-size:13px;padding:7px 14px;border:0.5px solid var(--border);border-radius:6px;background:#fff;cursor:pointer;color:var(--text-muted)}
.pr-tab.active{border-color:var(--blue-mid);background:var(--blue-light);color:#0C447C;font-weight:500}

.metric-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.metric{background:#fff;border:0.5px solid var(--border);border-radius:8px;padding:14px 16px}
.metric-label{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px}
.metric-value{font-size:26px;font-weight:600;font-family:var(--mono)}
.metric-sub{font-size:11px;color:var(--text-muted);margin-top:3px}
.metric.placeholder{background:#faf9f7;border-style:dashed}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:#fff;border:0.5px solid var(--border);border-radius:10px;padding:20px 24px}
.card.placeholder{background:#faf9f7;border-style:dashed}
.card-title{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:0.02em;margin-bottom:14px}
.ph-tag{font-size:10px;font-weight:600;color:#854F0B;background:var(--amber-light);border:0.5px solid var(--amber-border);border-radius:20px;padding:2px 9px;text-transform:none;letter-spacing:0;margin-left:8px}

.band-track{height:10px;border-radius:5px;background:var(--bg-surface);border:0.5px solid var(--border);position:relative;margin:34px 0 26px}
.band-fill{position:absolute;height:100%;border-radius:5px;top:0;transition:width 0.08s,background 0.3s}
.band-marker{position:absolute;height:24px;width:2px;top:-7px;border-radius:1px}
.band-label{position:absolute;font-size:10px;top:-22px;transform:translateX(-50%);white-space:nowrap;font-weight:600;font-family:var(--mono)}
.band-sublabel{position:absolute;font-size:10px;top:16px;transform:translateX(-50%);white-space:nowrap;color:var(--text-muted)}
input[type=range]{width:100%;accent-color:var(--blue-mid);margin:6px 0;height:4px}

.price-row{display:flex;align-items:baseline;gap:12px;margin:10px 0 4px}
.price-display{font-size:38px;font-weight:600;font-family:var(--mono);color:var(--blue-mid);transition:color 0.3s}
.price-meta{display:flex;flex-direction:column;gap:3px}
.perm-display{font-size:13px;color:var(--text-muted);font-family:var(--mono)}
.margin-display{font-size:14px;font-weight:600;font-family:var(--mono)}
.comment-box{min-height:40px;padding:10px 12px;border-radius:6px;font-size:13px;font-style:italic;line-height:1.55;margin:10px 0;border:0.5px solid transparent}
.c-ok{background:var(--blue-light);color:#0C447C;border-color:var(--blue-border)}
.c-good{background:var(--green-light);color:var(--green);border-color:var(--green-border)}
.c-warn{background:var(--amber-light);color:#633806;border-color:var(--amber-border)}
.c-danger{background:var(--red-light);color:var(--red);border-color:var(--red-border)}
.divider{border:none;border-top:0.5px solid var(--border);margin:16px 0}
.btn-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.ph-row{display:flex;justify-content:space-between;font-size:12px;padding:5px 0;border-bottom:0.5px solid var(--border)}
.ph-row:last-child{border-bottom:none}
.ph-label{color:var(--text-muted)}
.ph-val{font-family:var(--mono);font-weight:600}

@media(max-width:900px){.metric-row{grid-template-columns:repeat(2,1fr)}.grid2{grid-template-columns:1fr}}
</style>';

require_once __DIR__ . '/../../../includes/header.php';
?>

  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-left">
      <div class="breadcrumb">
        <a href="/modules/forms-estimating/">Estimating</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <a href="/modules/forms-estimating/spec_review.php">Spec review</a>
        <span class="breadcrumb-sep">&rsaquo;</span>
        <span class="breadcrumb-current">Pricing</span>
      </div>
    </div>
    <div class="topbar-actions">
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

  <div class="content">
    <div class="content-inner">

      <div class="page-hdr">
        <div>
          <div class="page-title" id="pr-title">Pricing</div>
          <div class="page-sub" id="pr-sub"></div>
        </div>
        <a class="btn" href="/modules/forms-estimating/spec_review.php">Back to spec</a>
      </div>

      <div class="pr-tabs" id="pr-tabs"></div>

      <div class="metric-row">
        <div class="metric">
          <div class="metric-label">Cost to produce</div>
          <div class="metric-value" id="m-cost">--</div>
          <div class="metric-sub">Hard floor (live)</div>
        </div>
        <div class="metric">
          <div class="metric-label">Cost-plus</div>
          <div class="metric-value" id="m-cp">--</div>
          <div class="metric-sub" id="m-cp-sub">Per-op markup (live)</div>
        </div>
        <div class="metric placeholder">
          <div class="metric-label">Edna suggests</div>
          <div class="metric-value" style="color:var(--blue-mid)" id="m-sug">--</div>
          <div class="metric-sub" id="m-sug-sub">placeholder</div>
        </div>
        <div class="metric placeholder">
          <div class="metric-label">Customer ceiling</div>
          <div class="metric-value" style="color:var(--amber)" id="m-ceil">--</div>
          <div class="metric-sub">placeholder</div>
        </div>
      </div>

      <div class="grid2">

        <div class="card">
          <div class="card-title">Set your price</div>
          <div class="band-track" id="band-track">
            <div class="band-fill" id="band-fill"></div>
            <div class="band-marker" id="bm-floor" style="left:0%;background:#ccc">
              <div class="band-label" style="color:var(--text-muted)" id="bl-floor">--</div>
              <div class="band-sublabel">Floor</div>
            </div>
            <div class="band-marker" id="bm-cp" style="background:#378ADD">
              <div class="band-label" style="color:var(--blue-mid)" id="bl-cp">--</div>
              <div class="band-sublabel">Cost+</div>
            </div>
            <div class="band-marker" id="bm-sug" style="background:var(--blue-mid);width:3px">
              <div class="band-label" style="color:var(--blue-mid);font-weight:700" id="bl-sug">--</div>
              <div class="band-sublabel" style="color:var(--blue-mid)">Edna</div>
            </div>
            <div class="band-marker" id="bm-ceil" style="left:100%;background:#D85A30">
              <div class="band-label" style="color:#712B13" id="bl-ceil">--</div>
              <div class="band-sublabel" style="color:#712B13">Ceiling</div>
            </div>
          </div>

          <input type="range" id="pr-slider" min="0" max="100" value="50" step="1" oninput="onSlide(this.value)">

          <div class="price-row">
            <div class="price-display" id="pr-price">--</div>
            <div class="price-meta">
              <div class="perm-display" id="pr-perm">-- / M</div>
              <div class="margin-display" id="pr-margin" style="color:var(--green)">--</div>
            </div>
          </div>

          <div class="comment-box c-ok" id="pr-comment">Slide to set the price. Edna's guidance is a placeholder until Avanti history is wired.</div>

          <div class="divider"></div>
          <div class="btn-row">
            <button class="btn btn-green" onclick="lockIn()">Lock in and send quote</button>
            <button class="btn" onclick="resetTo('cp')">Cost-plus</button>
          </div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:10px">Sandbox - nothing is saved yet. Lock/save is the next build step.</div>
        </div>

        <div class="card placeholder">
          <div class="card-title">Edna's take <span class="ph-tag">placeholder - Avanti pending</span></div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
            These figures come from customer history and Edna's judgment (the Avanti read layer),
            which is not connected yet. Shown as illustrative placeholders.
          </div>
          <div class="ph-row"><span class="ph-label">Customer acceptance rate</span><span class="ph-val">--</span></div>
          <div class="ph-row"><span class="ph-label">Their avg / M on similar jobs</span><span class="ph-val">--</span></div>
          <div class="ph-row"><span class="ph-label">Last comparable quote</span><span class="ph-val">--</span></div>
          <div class="ph-row"><span class="ph-label">Estimated vs actual</span><span class="ph-val">--</span></div>
        </div>

      </div>

    </div>
  </div>

<script>
// The stashed sandbox estimate from spec_review.
var EST = null;
var curBreak = 0;

// Placeholder multipliers for the Edna/ceiling band (NOT live - Avanti pending).
// TODO: hardcoded - replace with Avanti history + Edna judgment.
var PH_SUG_MULT  = 1.15;  // Edna-suggested = cost-plus * this
var PH_CEIL_MULT = 1.35;  // ceiling        = cost-plus * this

function money(n) {
  return '$' + Number(n).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}
function money0(n) {
  return '$' + Math.round(Number(n)).toLocaleString();
}

// Derived band for a break: floor (cost), cost-plus (live), and the
// placeholder Edna/ceiling multiples.
function bandFor(b) {
  var floor = b.cost_total;
  var cp    = b.price_total;
  var sug   = cp * PH_SUG_MULT;
  var ceil  = cp * PH_CEIL_MULT;
  return { floor: floor, cp: cp, sug: sug, ceil: ceil };
}

function pctOf(v, band) {
  var span = band.ceil - band.floor;
  if (span <= 0) return 0;
  return Math.max(0, Math.min(100, (v - band.floor) / span * 100));
}

function renderTabs() {
  var t = document.getElementById('pr-tabs');
  t.innerHTML = '';
  EST.breaks.forEach(function(b, i) {
    var el = document.createElement('div');
    el.className = 'pr-tab' + (i === curBreak ? ' active' : '');
    el.textContent = b.quantity.toLocaleString();
    el.onclick = function() { selectBreak(i); };
    t.appendChild(el);
  });
}

function selectBreak(i) {
  curBreak = i;
  renderTabs();
  var b = EST.breaks[i];
  var band = bandFor(b);

  document.getElementById('m-cost').textContent = money0(band.floor);
  document.getElementById('m-cp').textContent   = money0(band.cp);
  document.getElementById('m-sug').textContent  = money0(band.sug);
  document.getElementById('m-ceil').textContent = money0(band.ceil);

  document.getElementById('bl-floor').textContent = money0(band.floor);
  document.getElementById('bl-cp').textContent    = money0(band.cp);
  document.getElementById('bl-sug').textContent   = money0(band.sug);
  document.getElementById('bl-ceil').textContent  = money0(band.ceil);
  document.getElementById('bm-cp').style.left  = pctOf(band.cp, band) + '%';
  document.getElementById('bm-sug').style.left = pctOf(band.sug, band) + '%';

  // Slider spans floor..ceiling; start at the (placeholder) Edna suggestion.
  var slider = document.getElementById('pr-slider');
  slider.min = Math.round(band.floor);
  slider.max = Math.round(band.ceil);
  slider.value = Math.round(band.sug);
  onSlide(slider.value);
}

function onSlide(val) {
  var b = EST.breaks[curBreak];
  var band = bandFor(b);
  var v = parseFloat(val);
  var margin = v > 0 ? Math.round((v - band.floor) / v * 100) : 0;
  var perM = Math.round(v / (b.quantity / 1000));

  document.getElementById('pr-price').textContent  = money0(v);
  document.getElementById('pr-perm').textContent   = '$' + perM + ' / M';
  document.getElementById('pr-margin').textContent = margin + '% margin';
  document.getElementById('band-fill').style.width = pctOf(v, band) + '%';

  var fc, cc, ct, mc;
  if (v <= band.floor)      { fc='#F09595'; mc='#A32D2D'; cc='c-danger'; ct='At or below cost. You would make no margin.'; }
  else if (v < band.cp)     { fc='#FAC775'; mc='#854F0B'; cc='c-warn';   ct='Below cost-plus. Covers cost but leaves margin on the table.'; }
  else if (v < band.sug)    { fc='#85B7EB'; mc='#185FA5'; cc='c-ok';     ct='Solid. Above cost-plus.'; }
  else if (v < band.ceil)   { fc='#378ADD'; mc='#3B6D11'; cc='c-good';   ct='Good margin. (Edna guidance is a placeholder until Avanti is wired.)'; }
  else                      { fc='#E24B4A'; mc='#A32D2D'; cc='c-danger'; ct='At the (placeholder) ceiling.'; }

  document.getElementById('band-fill').style.background = fc;
  document.getElementById('pr-margin').style.color = mc;
  var cb = document.getElementById('pr-comment');
  cb.className = 'comment-box ' + cc;
  cb.textContent = ct;
}

function resetTo(which) {
  var band = bandFor(EST.breaks[curBreak]);
  var slider = document.getElementById('pr-slider');
  slider.value = Math.round(which === 'cp' ? band.cp : band.sug);
  onSlide(slider.value);
}

function lockIn() {
  alert('Lock/save is the next build step - it will stamp the immutable estimate. Nothing saved yet.');
}

window.addEventListener('load', function() {
  var raw = sessionStorage.getItem('scp_estimate');
  if (!raw) {
    document.getElementById('pr-sub').textContent = 'No estimate in progress. Go back to spec review and run an estimate.';
    return;
  }
  EST = JSON.parse(raw);
  var who = [EST.customer, EST.jobname].filter(Boolean).join(' - ');
  document.getElementById('pr-title').textContent = 'Pricing' + (EST.jobname ? ' - ' + EST.jobname : '');
  document.getElementById('pr-sub').textContent =
    (who ? who + '. ' : '') + 'Press ' + EST.press.number + ' - ' + (EST.press.name || '');
  renderTabs();
  selectBreak(0);
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
