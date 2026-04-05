<?php
/**
 * /modules/forms-estimating/index.php
 *
 * Protected page — auth middleware handles session + redirect to login.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

// ── User info from auth middleware ────────────────────────────────────────────
$userName  = $_AUTH_USER['name'] ?? 'User';
$userRole  = strtolower($_AUTH_USER['role'] ?? 'csr');
$firstName = explode(' ', trim($userName))[0];

// ── TODO: replace with real DB query — count of quotes needing action ─────────
$quoteActionCount = 6;

// ── Page config ───────────────────────────────────────────────────────────────
$pageTitle  = 'Estimating';
$activePage = 'estimating';
$navBadges  = ['estimating' => $quoteActionCount];

// ── Page-specific CSS ─────────────────────────────────────────────────────────
$extraCss = '<style>
/* ══ QUICK ACTION TILES ══ */
.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.qa-tile{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:14px 16px;cursor:pointer;transition:border-color 0.15s,background 0.15s,transform 0.15s;display:flex;flex-direction:column;gap:6px;justify-content:flex-start}
.qa-tile:hover{border-color:var(--blue-border);background:var(--blue-light);transform:translateY(-1px)}
.qa-tile.primary{background:var(--blue-light);border-color:var(--blue-border)}
.qa-tile.primary:hover{background:#cee5f7;border-color:var(--blue-mid)}
.qa-tile.selected{border-color:var(--blue-mid);background:var(--blue-light)}
.qa-tile-header{display:flex;align-items:center;gap:9px;margin-bottom:2px}
.qa-icon{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);flex-shrink:0}
.qa-tile.primary .qa-icon,.qa-tile.selected .qa-icon{background:rgba(255,255,255,0.65)}
.qa-icon svg{width:14px;height:14px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.qa-tile.primary .qa-icon svg,.qa-tile.selected .qa-icon svg{stroke:#0C447C}
.qa-title{font-size:13px;font-weight:600;color:var(--text)}
.qa-tile.primary .qa-title,.qa-tile.selected .qa-title{color:#0C447C}
.qa-sub{font-size:11px;color:var(--text-muted);line-height:1.45}
.qa-tile.primary .qa-sub,.qa-tile.selected .qa-sub{color:#185FA5}

/* ══ LOOKUP ══ */
.lookup-card{background:var(--blue-light);border:0.5px solid var(--blue-border);border-radius:var(--radius-sm);padding:12px 14px}
.lookup-card-title{font-size:13px;font-weight:600;color:#0C447C;margin-bottom:4px;display:flex;align-items:center;gap:7px}
.lookup-card-detail{font-size:12px;color:#185FA5;margin-bottom:8px;line-height:1.5}
.lookup-actions{display:flex;gap:7px;flex-wrap:wrap}

/* ══ WIN ITEMS ══ */
.win-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:0.5px solid var(--border)}
.win-item:last-child{border-bottom:none}
.win-qnum{font-family:var(--mono);font-size:11px;color:var(--blue-mid)}
.win-desc{font-size:12px;color:var(--text-muted);margin-top:2px}
.win-right{text-align:right}
.win-val{font-family:var(--mono);font-size:13px;font-weight:500;color:var(--text)}
.win-avanti{font-family:var(--mono);font-size:10px;color:var(--text-muted);margin-top:2px}

@media(max-width:600px){.quick-actions{grid-template-columns:1fr 1fr}}
</style>';

require_once __DIR__ . '/../../../includes/header.php';
?>

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-page-title">Estimating</div>
    <div class="topbar-actions">
      <div id="topbar-user" class="topbar-user" onclick="toggleUserMenu()">
        <div class="topbar-avatar"><?= htmlspecialchars($_navInitials) ?></div>
        <span class="topbar-user-name"><?= htmlspecialchars($firstName) ?></span>
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

  <!-- ══ CONTENT ══ -->
  <div class="content">
    <div class="content-inner">

      <!-- ══ LEFT COLUMN ══ -->
      <div>

        <!-- METRICS ROW -->
        <div class="metrics-row" style="margin-bottom:14px">
          <div class="metric-card accent-blue" style="padding:12px 16px">
            <div class="metric-label">Open quotes</div>
            <div class="metric-value" style="font-size:22px" id="m-open"><?= $userRole === 'manager' ? 24 : 6 ?></div>
            <div class="metric-sub" id="m-open-sub"><?= $userRole === 'manager' ? 'across all CSRs' : '4 need action' ?></div>
          </div>
          <div class="metric-card accent-green" style="padding:12px 16px">
            <div class="metric-label">Win rate</div>
            <div class="metric-value green" style="font-size:22px" id="m-winrate">74%</div>
            <div class="metric-sub">Last 90 days</div>
          </div>
          <div class="metric-card accent-amber" style="padding:12px 16px">
            <div class="metric-label">Aged quotes</div>
            <div class="metric-value amber" style="font-size:22px" id="m-aged"><?= $userRole === 'manager' ? 5 : 3 ?></div>
            <div class="metric-sub">30+ days silent</div>
          </div>
          <div class="metric-card accent-red" style="padding:12px 16px">
            <div class="metric-label">Sent this week</div>
            <div class="metric-value" style="font-size:22px" id="m-sent"><?= $userRole === 'manager' ? 12 : 4 ?></div>
            <div class="metric-sub" id="m-sent-sub"><?= $userRole === 'manager' ? '8 converted this week' : '2 converted' ?></div>
          </div>
        </div>

        <!-- ACTION TILES -->
        <div class="quick-actions" style="margin-bottom:14px">
          <div class="qa-tile primary" id="tile-newquote" onclick="selectTile('newquote',this)">
            <div class="qa-tile-header">
              <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></div>
              <div class="qa-title">New quote</div>
            </div>
            <div class="qa-sub">AI parses your spec — describe the job in plain language</div>
          </div>
          <div class="qa-tile" id="tile-template" onclick="selectTile('template',this)">
            <div class="qa-tile-header">
              <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div>
              <div class="qa-title">From template</div>
            </div>
            <div class="qa-sub">Pick a saved template for common job types</div>
          </div>
          <div class="qa-tile" id="tile-reorder" onclick="selectTile('reorder',this)">
            <div class="qa-tile-header">
              <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg></div>
              <div class="qa-title">Reorder</div>
            </div>
            <div class="qa-sub">Clone a previous quote — carry all specs forward</div>
          </div>
          <div class="qa-tile" id="tile-similar" onclick="selectTile('similar',this)">
            <div class="qa-tile-header">
              <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>
              <div class="qa-title">Similar job</div>
            </div>
            <div class="qa-sub">Use any quote as a starting point for a new customer</div>
          </div>
        </div>

        <!-- LOOKUP + DESCRIPTION -->
        <div class="card" style="margin-bottom:14px">
          <div style="display:flex;flex-direction:column;gap:10px">
            <div>
              <label class="field-label">Customer, quote number, or job name</label>
              <input class="fi" id="nq-lookup" placeholder="e.g. BCAA, Q002831K, window envelopes..." oninput="handleLookup(this.value)" autocomplete="off">
              <div id="lookup-result" style="display:none;margin-top:6px"></div>
            </div>
            <div>
              <label class="field-label">Job description — paste a spec, forward an email, or just describe it</label>
              <textarea class="fi" id="nq-desc" rows="3" placeholder="e.g. 10,000 3-part NCR sets, 8.5×11, black ink, top perf, padded in 50s..." style="resize:vertical"></textarea>
            </div>
            <div>
              <button class="btn btn-primary" onclick="parseAndGo()">Parse with AI and open pricing →</button>
            </div>
          </div>
        </div>

        <!-- ACTIVE QUOTES -->
        <div class="card" style="margin-bottom:14px">
          <div class="card-header">
            <div class="card-title" id="quotes-title"><?= $userRole === 'manager' ? 'All active quotes' : 'My active quotes' ?></div>
            <a class="card-link">View all →</a>
          </div>
          <!-- TODO: replace with real DB query -->
          <table class="tbl">
            <thead>
              <tr><th>Quote #</th><th>Customer</th><th>Description</th><th>Breaks</th><th>Mid value</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr><td class="mono">Q002847R</td><td>BCAA</td><td class="muted">NCR snap sets 3-part</td><td class="muted" style="font-size:12px">5K / 10K / 25K</td><td class="val">$4,200</td><td><span class="badge badge-draft">Draft</span></td></tr>
              <tr><td class="mono">Q002846W</td><td>BC Hydro</td><td class="muted">Continuous forms #10</td><td class="muted" style="font-size:12px">10K / 25K / 50K</td><td class="val">$8,750</td><td><span class="badge badge-pending">Pending</span></td></tr>
              <tr><td class="mono">Q002844T</td><td>Telus</td><td class="muted">Window envelopes #10</td><td class="muted" style="font-size:12px">5K / 10K</td><td class="val">$2,100</td><td><span class="badge badge-approved">Approved</span></td></tr>
              <tr><td class="mono">Q002841M</td><td>City of Burnaby</td><td class="muted">4-part NCR sets</td><td class="muted" style="font-size:12px">2.5K / 5K / 10K</td><td class="val">$6,400</td><td><span class="badge badge-sent">Sent</span></td></tr>
            </tbody>
          </table>
        </div>

        <!-- AGED QUOTES -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Aged quotes — need attention</div>
            <span style="font-size:11px;color:var(--text-muted)">30+ days no activity</span>
          </div>
          <div class="aged-item"><div><div class="aged-qnum">Q002802W</div><div class="aged-customer">Telus — business forms #10</div></div><div class="aged-days">42d</div></div>
          <div class="aged-item"><div><div class="aged-qnum">Q002779H</div><div class="aged-customer">BC Hydro — NCR sets</div></div><div class="aged-days">38d</div></div>
          <div class="aged-item"><div><div class="aged-qnum">Q002751K</div><div class="aged-customer">BCAA — mailers</div></div><div class="aged-days">35d</div></div>
          <div class="auto-tag"><div class="auto-dot"></div>Won/lost resolved automatically when an Avanti order appears against this quote</div>
        </div>

      </div><!-- /left column -->

      <!-- ══ RIGHT COLUMN — EDNA ══ -->
      <div>
        <div class="edna-pane">
          <div class="edna-header">
            <div class="edna-avatar">👩‍💼</div>
            <div>
              <div class="edna-name">Edna</div>
              <div class="edna-tagline">40 years on the press floor</div>
              <div class="edna-status"><div class="edna-status-dot"></div><span class="edna-status-text">Ready</span></div>
            </div>
          </div>
          <div class="edna-chat" id="edna-chat">
            <div class="bubble edna">
              <?php if ($userRole === 'manager'): ?>
              Morning <?= htmlspecialchars($firstName) ?>. You've got quotes waiting on approval — want to start there, or are we building something new?
              <?php else: ?>
              Morning <?= htmlspecialchars($firstName) ?>! What are we estimating today? Describe the job, pick a customer — or click a tile above and I'll pull up what I know.
              <?php endif; ?>
            </div>
          </div>
          <div class="edna-footer">
            <div class="listening-bar" id="listening-bar"><div class="pulse-dot"></div>Listening — speak now</div>
            <div class="edna-input-row">
              <textarea class="edna-textarea" id="edna-input" placeholder="Ask Edna anything..." onkeydown="handleKey(event)"></textarea>
              <button class="mic-btn" id="mic-btn" onclick="toggleMic()" title="Talk to Edna">
                <svg viewBox="0 0 24 24"><rect x="9" y="2" width="6" height="11" rx="3"/><path d="M5 10a7 7 0 0014 0M12 19v3M8 22h8"/></svg>
              </button>
            </div>
            <div class="chips">
              <span class="chip" onclick="quickMsg('Reorder last BCAA job')">↩ Reorder BCAA</span>
              <span class="chip" onclick="quickMsg('Any gang run opportunities this week?')">Gang runs?</span>
              <span class="chip" onclick="quickMsg('What needs my attention today?')">What needs attention?</span>
              <span class="chip" onclick="quickMsg('Show me pending approvals')">Pending approvals</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /content -->

<script>
/* ── TILE SELECTION ── */
function selectTile(type, el) {
    document.querySelectorAll('.qa-tile').forEach(t => t.classList.remove('selected','primary'));
    el.classList.add('selected');
    document.getElementById('nq-lookup').focus();
    const msgs = {
        newquote: "Ready to build something new. Give me a customer and describe the job — as much or as little as you've got.",
        template: "Four templates ready: 3-part NCR snap sets, continuous forms #10, window envelope #10, and 4-part NCR carbonless. Who's the customer?",
        reorder:  "BCAA's last order was Q002831K — 10K NCR 3-part snap sets. Want to reorder that, or pick a different customer?",
        similar:  "Pick a base quote and a new customer — I'll flag any pricing differences worth knowing before we run the estimate."
    };
    addBubble(msgs[type] || "What are we building?", 'edna');
}

/* ── PARSE AND GO ── */
function parseAndGo() {
    const customer    = document.getElementById('nq-lookup').value.trim();
    const description = document.getElementById('nq-desc').value.trim();
    if (!description) {
        addBubble("Give me at least a job description — try: '10,000 3-part NCR sets, 8.5×11, black ink, top perf, padded in 50s'", 'edna');
        document.getElementById('nq-desc').focus();
        return;
    }
    sessionStorage.setItem('scp_parse_job', JSON.stringify({ customer, description }));
    window.location.href = '/modules/forms-estimating/spec_review.php';
}

/* ── LOOKUP ── */
let lookupTimer = null;
function handleLookup(val) {
    clearTimeout(lookupTimer);
    const result = document.getElementById('lookup-result');
    if (!val || val.length < 2) { result.style.display = 'none'; return; }
    lookupTimer = setTimeout(() => {
        const v = val.toLowerCase().trim();
        let html = '';
        if (v.includes('bcaa')) {
            html = lookupCard('👤','BCAA','12 quotes · last job Q002831K — NCR 3-part snap sets · avg $2,800',[['New quote for BCAA →'],['Reorder Q002831K →']]);
        } else if (v.includes('hydro')) {
            html = lookupCard('👤','BC Hydro','8 quotes · last job Q002795M — continuous forms · avg $6,400',[['New quote for BC Hydro →']]);
        } else if (v.includes('telus')) {
            html = lookupCard('👤','Telus','6 quotes · last job Q002808T — window envelopes #10 · avg $3,100',[['New quote for Telus →']]);
        } else if (v.match(/q0*\d{4,}/i)) {
            html = lookupCard('📄','Quote found: Q002831K','BCAA — NCR 3-part snap sets · 10,000 sets · $2,100 · Won',[['Clone this quote →'],['View quote →']]);
        } else if (val.length >= 3) {
            html = lookupCard('🔍','No exact match','Describe the job below and I\'ll parse it for you.',[]);
        }
        if (html) { result.innerHTML = html; result.style.display = 'block'; }
        else result.style.display = 'none';
    }, 300);
}
function lookupCard(icon, title, detail, actions) {
    const btns = (actions||[]).map(([label]) => `<button class="btn btn-sm btn-primary" style="font-size:12px">${label}</button>`).join('');
    return `<div class="lookup-card"><div class="lookup-card-title"><span>${icon}</span>${title}</div><div class="lookup-card-detail">${detail}</div>${btns?`<div class="lookup-actions">${btns}</div>`:''}</div>`;
}

/* ── EDNA ── */
function handleKey(e) { if (e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendEdna();} }
function quickMsg(txt) { document.getElementById('edna-input').value=txt; sendEdna(); }
function addBubble(txt, type) {
    const chat=document.getElementById('edna-chat');
    const d=document.createElement('div'); d.className='bubble '+type; d.textContent=txt;
    chat.appendChild(d); chat.scrollTop=chat.scrollHeight; return d;
}
function sendEdna() {
    const inp=document.getElementById('edna-input');
    const txt=inp.value.trim(); if(!txt) return;
    inp.value=''; addBubble(txt,'user');
    const t=addBubble('Thinking...','edna thinking');
    fetch('/api/edna.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:txt,context:'estimating'})})
        .then(r=>r.json()).then(data=>{t.remove();addBubble(data.reply||"Sorry, no response.",'edna');})
        .catch(()=>{t.remove();addBubble("Couldn't reach the server right now.",'edna');});
}

/* ── MIC ── */
let listening=false,recognition=null;
function toggleMic(){
    if(!('webkitSpeechRecognition' in window||'SpeechRecognition' in window)){addBubble("Voice input needs Chrome — type your spec and I'll parse it!",'edna');return;}
    if(listening){recognition&&recognition.stop();return;}
    const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    recognition=new SR(); recognition.lang='en-CA';
    recognition.onstart=()=>{listening=true;document.getElementById('mic-btn').classList.add('listening');document.getElementById('listening-bar').classList.add('active');};
    recognition.onresult=e=>{document.getElementById('edna-input').value=e.results[0][0].transcript;sendEdna();};
    recognition.onend=()=>{listening=false;document.getElementById('mic-btn').classList.remove('listening');document.getElementById('listening-bar').classList.remove('active');};
    recognition.onerror=()=>{listening=false;document.getElementById('mic-btn').classList.remove('listening');document.getElementById('listening-bar').classList.remove('active');};
    recognition.start();
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
