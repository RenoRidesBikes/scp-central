<?php
/**
 * /index.php
 *
 * Protected page — requires auth middleware.
 * Widgets gated by RBAC (hasPermission) + user hide prefs (dashboard_prefs JSONB).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/permissions.php';

// ── User info from auth middleware ────────────────────────────────────────────
$userId    = (int) $_AUTH_USER['id'];
$userRole  = strtolower($_AUTH_USER['role'] ?? 'csr');
$firstName = explode(' ', trim($_AUTH_USER['name'] ?? 'User'))[0];

// ── Load dashboard prefs ──────────────────────────────────────────────────────
$stmt = getDB()->prepare('SELECT dashboard_prefs FROM users WHERE id = ?');
$stmt->execute([$userId]);
$prefs  = json_decode($stmt->fetchColumn() ?: '{}', true);
$hidden = $prefs['hidden'] ?? [];

// ── Widget visibility helpers ─────────────────────────────────────────────────
function widgetOn(int $userId, string $key, array $hidden): bool {
    return hasPermission($userId, "widget:{$key}", PERM_READ) && !($hidden[$key] ?? false);
}
function widgetAllowed(int $userId, string $key): bool {
    return hasPermission($userId, "widget:{$key}", PERM_READ);
}

$W = [];
foreach (['new_quote','my_queue','aged_quotes','team_pipeline','win_rate','revenue'] as $k) {
    $W[$k] = widgetOn($userId, $k, $hidden);
}

// ── Placeholder data (TODO: replace with real DB queries) ─────────────────────
$d = [
    'my_queue_count'   => 6,   'my_queue_action'  => 4,
    'aged_count'       => 3,   'aged_oldest'      => 42,
    'win_rate_pct'     => 74,  'win_rate_period'  => 'last 90 days',
    'win_rate_delta'   => '+3%',
    'sent_count'       => 4,   'sent_converted'   => 2,
    'pipeline_count'   => 14,  'pipeline_value'   => '$42,800',
    'revenue_mtd'      => '$187,200', 'revenue_target' => '$210,000',
    'revenue_pct'      => 89,
];

// ── Greeting ──────────────────────────────────────────────────────────────────
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// ── Edna dashboard message (role-aware, no API call on load) ─────────────────
$ednaMsg = match(true) {
    $userRole === 'partner'          => "Revenue is tracking at {$d['revenue_pct']}% of target this month. Pipeline looks solid at {$d['pipeline_value']} across {$d['pipeline_count']} open quotes.",
    $userRole === 'manager'          => "Team has {$d['pipeline_count']} open quotes worth {$d['pipeline_value']}. Win rate holding at {$d['win_rate_pct']}% — {$d['win_rate_delta']} vs prior period.",
    default                          => "{$greeting}, {$firstName}! You have {$d['my_queue_count']} open quotes, {$d['my_queue_action']} need attention. " . ($d['aged_count'] > 0 ? "{$d['aged_count']} quotes are going quiet — worth a follow-up today." : "No aged quotes — nice work!"),
};

// ── Page config ───────────────────────────────────────────────────────────────
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$navBadges  = [];

// ── Page-specific CSS ─────────────────────────────────────────────────────────
$extraCss = '<style>
.qa-tile{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:14px 16px;cursor:pointer;transition:border-color 0.15s,background 0.15s,transform 0.15s;display:flex;flex-direction:column;gap:6px}
.qa-tile:hover{border-color:var(--blue-border);background:var(--blue-light);transform:translateY(-1px)}
.qa-tile-header{display:flex;align-items:center;gap:9px;margin-bottom:2px}
.qa-icon{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);flex-shrink:0}
.qa-icon svg{width:14px;height:14px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.qa-title{font-size:13px;font-weight:600;color:var(--text)}
.qa-sub{font-size:11px;color:var(--text-muted);line-height:1.45}
.dash-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.dash-greeting{font-size:18px;font-weight:600;color:var(--text)}
.dash-date{font-size:12px;color:var(--text-muted);margin-top:2px}
.customize-btn{font-family:var(--sans);font-size:12px;color:var(--text-muted);background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius-sm);padding:6px 12px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:border-color 0.12s,color 0.12s}
.customize-btn:hover{border-color:#aaa;color:var(--text)}
.customize-panel{display:none;background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:16px}
.customize-panel.open{display:block}
.customize-label{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:10px}
.widget-toggles{display:flex;flex-wrap:wrap;gap:8px}
.widget-toggle{display:flex;align-items:center;gap:7px;padding:5px 12px;border-radius:20px;border:0.5px solid var(--border);background:var(--bg-surface);cursor:pointer;font-size:12px;color:var(--text-mid);transition:all 0.12s;user-select:none}
.widget-toggle.on{background:var(--blue-light);border-color:var(--blue-border);color:var(--blue-mid)}
.toggle-dot{width:7px;height:7px;border-radius:50%;background:var(--border-mid);flex-shrink:0}
.widget-toggle.on .toggle-dot{background:var(--blue-mid)}
.rev-track{height:5px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:8px}
.rev-fill{height:100%;background:#639922;border-radius:3px;transition:width 0.5s ease}
.rev-labels{display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);margin-top:4px;font-family:var(--mono)}
.delta{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-family:var(--mono);padding:2px 7px;border-radius:20px;margin-top:6px}
.delta.up{background:var(--green-light);color:var(--green)}
.delta.down{background:var(--amber-light);color:var(--amber)}
</style>';

require_once __DIR__ . '/../includes/header.php';
?>

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-page-title">Dashboard</div>
    <div class="topbar-actions">
      <div id="topbar-user" class="topbar-user" onclick="toggleUserMenu()">
        <div class="topbar-avatar"><?= htmlspecialchars($_navInitials) ?></div>
        <span class="topbar-user-name"><?= htmlspecialchars($firstName) ?></span>
        <svg class="topbar-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="user-dropdown">
          <div class="dropdown-section-label"><?= htmlspecialchars($_AUTH_USER['name'] ?? 'User') ?></div>
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

        <!-- Page header -->
        <div class="dash-header">
          <div>
            <div class="dash-greeting"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?>.</div>
            <div class="dash-date"><?= date('l, F j, Y') ?></div>
          </div>
          <button class="customize-btn" onclick="toggleCustomize()">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <circle cx="8" cy="8" r="2.5"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.05 3.05l1.41 1.41M11.54 11.54l1.41 1.41M3.05 12.95l1.41-1.41M11.54 4.46l1.41-1.41"/>
            </svg>
            Customize
          </button>
        </div>

        <!-- Customize panel -->
        <?php
        $widgetLabels = ['new_quote'=>'✦ New Quote','my_queue'=>'My Queue','aged_quotes'=>'Aged Quotes','team_pipeline'=>'Team Pipeline','win_rate'=>'Win Rate','revenue'=>'Revenue'];
        $anyAllowed = false;
        foreach (array_keys($widgetLabels) as $k) { if (widgetAllowed($userId, $k)) { $anyAllowed = true; break; } }
        ?>
        <?php if ($anyAllowed): ?>
        <div class="customize-panel" id="customizePanel">
          <div class="customize-label">Show / hide widgets</div>
          <div class="widget-toggles">
            <?php foreach ($widgetLabels as $key => $label):
              if (!widgetAllowed($userId, $key)) continue;
              $isOn = !($hidden[$key] ?? false);
            ?>
            <div class="widget-toggle <?= $isOn ? 'on' : '' ?>" data-key="<?= $key ?>" onclick="toggleWidget('<?= $key ?>')">
              <span class="toggle-dot"></span><?= htmlspecialchars($label) ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Metrics row -->
        <div class="metrics-row">
          <?php if ($W['my_queue']): ?>
          <div class="metric-card accent-blue" id="w-my_queue">
            <div class="metric-label">My open quotes</div>
            <div class="metric-value" style="font-size:22px"><?= $d['my_queue_count'] ?></div>
            <div class="metric-sub"><?= $d['my_queue_action'] ?> need action</div>
          </div>
          <?php endif; ?>
          <?php if ($W['win_rate']): ?>
          <div class="metric-card accent-green" id="w-win_rate">
            <div class="metric-label">Win rate</div>
            <div class="metric-value green" style="font-size:22px"><?= $d['win_rate_pct'] ?>%</div>
            <div class="metric-sub"><?= htmlspecialchars($d['win_rate_period']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($W['aged_quotes']): ?>
          <div class="metric-card accent-amber" id="w-aged_quotes">
            <div class="metric-label">Aged quotes</div>
            <div class="metric-value amber" style="font-size:22px"><?= $d['aged_count'] ?></div>
            <div class="metric-sub">30+ days silent</div>
          </div>
          <?php endif; ?>
          <?php if ($W['team_pipeline']): ?>
          <div class="metric-card accent-blue" id="w-team_pipeline">
            <div class="metric-label">Team pipeline</div>
            <div class="metric-value" style="font-size:22px"><?= $d['pipeline_count'] ?></div>
            <div class="metric-sub"><?= $d['pipeline_value'] ?> total</div>
          </div>
          <?php elseif ($W['revenue']): ?>
          <div class="metric-card accent-red" id="w-revenue_metric">
            <div class="metric-label">Revenue MTD</div>
            <div class="metric-value" style="font-size:22px"><?= $d['revenue_mtd'] ?></div>
            <div class="metric-sub">target <?= $d['revenue_target'] ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- New Quote entry -->
        <?php if ($W['new_quote']): ?>
        <div class="card" id="w-new_quote" style="margin-bottom:14px">
          <div class="card-header">
            <div class="card-title">Start a quote</div>
            <a class="card-link" href="/modules/forms-estimating/">All quotes →</a>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <div>
              <label class="field-label">Customer, quote number, or job name</label>
              <input class="fi" id="nq-lookup" placeholder="e.g. BCAA, Q002831K, window envelopes..." oninput="handleLookup(this.value)" autocomplete="off">
              <div id="lookup-result" style="display:none;margin-top:6px"></div>
            </div>
            <div>
              <label class="field-label">Job description</label>
              <textarea class="fi" id="nq-desc" rows="3" placeholder="e.g. 10,000 3-part NCR sets, 8.5×11, black ink, top perf, padded in 50s..." style="resize:vertical"></textarea>
            </div>
            <button class="btn btn-primary" onclick="parseAndGo()">Let Edna take it from here →</button>
          </div>
        </div>
        <?php endif; ?>

        <!-- Aged quotes detail -->
        <?php if ($W['aged_quotes']): ?>
        <div class="card" id="w-aged_detail">
          <div class="card-header">
            <div class="card-title">Aged quotes — need attention</div>
            <span style="font-size:11px;color:var(--text-muted)">30+ days no activity</span>
          </div>
          <!-- TODO: replace with real DB query -->
          <div class="aged-item"><div><div class="aged-qnum">Q002802W</div><div class="aged-customer">Telus — business forms #10</div></div><div class="aged-days">42d</div></div>
          <div class="aged-item"><div><div class="aged-qnum">Q002779H</div><div class="aged-customer">BC Hydro — NCR sets</div></div><div class="aged-days">38d</div></div>
          <div class="aged-item"><div><div class="aged-qnum">Q002751K</div><div class="aged-customer">BCAA — mailers</div></div><div class="aged-days">35d</div></div>
          <div class="auto-tag"><div class="auto-dot"></div>Won/lost resolved automatically when an Avanti order appears against this quote</div>
        </div>
        <?php endif; ?>

        <!-- Revenue detail (partner+ only) -->
        <?php if ($W['revenue']): ?>
        <div class="card" id="w-revenue">
          <div class="card-header"><div class="card-title">Revenue MTD</div></div>
          <div class="metric-value"><?= $d['revenue_mtd'] ?></div>
          <div class="metric-sub" style="margin-top:6px">target <?= $d['revenue_target'] ?></div>
          <div class="rev-track"><div class="rev-fill" style="width:<?= $d['revenue_pct'] ?>%"></div></div>
          <div class="rev-labels"><span>$0</span><span><?= $d['revenue_pct'] ?>% of target</span><span><?= $d['revenue_target'] ?></span></div>
        </div>
        <?php endif; ?>

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
            <div class="bubble edna"><?= htmlspecialchars($ednaMsg) ?></div>
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
              <span class="chip" onclick="quickMsg('What needs my attention today?')">What needs attention?</span>
              <span class="chip" onclick="quickMsg('Any aged quotes I should follow up on?')">Aged follow-ups</span>
              <?php if (in_array($userRole, ['manager','partner','admin'])): ?>
              <span class="chip" onclick="quickMsg('Show me quotes pending approval')">Pending approvals</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /content -->

<script>
const hiddenState = <?= json_encode((object)$hidden) ?>;

function toggleCustomize() {
    document.getElementById('customizePanel')?.classList.toggle('open');
}
function toggleWidget(key) {
    hiddenState[key] = !hiddenState[key];
    document.querySelector(`.widget-toggle[data-key="${key}"]`)?.classList.toggle('on', !hiddenState[key]);
    const card = document.getElementById('w-' + key);
    if (card) card.style.display = hiddenState[key] ? 'none' : '';
    fetch('/api/prefs.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({hidden:hiddenState}) })
        .catch(e => console.warn('Prefs save failed:', e));
}
function parseAndGo() {
    const customer    = document.getElementById('nq-lookup')?.value.trim() || '';
    const description = document.getElementById('nq-desc')?.value.trim() || '';
    if (!description) { addBubble("Give me at least a job description to work with.", 'edna'); document.getElementById('nq-desc')?.focus(); return; }
    sessionStorage.setItem('scp_parse_job', JSON.stringify({ customer, description }));
    window.location.href = '/modules/forms-estimating/spec_review.php';
}
let lookupTimer = null;
function handleLookup(val) {
    clearTimeout(lookupTimer);
    const result = document.getElementById('lookup-result');
    if (!val || val.length < 2) { result.style.display = 'none'; return; }
    lookupTimer = setTimeout(() => {
        const v = val.toLowerCase();
        let html = '';
        if (v.includes('bcaa')) html = lookupCard('👤','BCAA','12 quotes · last job Q002831K');
        else if (v.includes('hydro')) html = lookupCard('👤','BC Hydro','8 quotes · last Q002795M');
        else if (v.includes('telus')) html = lookupCard('👤','Telus','6 quotes · last Q002808T');
        if (html) { result.innerHTML = html; result.style.display = 'block'; }
        else result.style.display = 'none';
    }, 300);
}
function lookupCard(icon, title, detail) {
    return `<div style="background:var(--blue-light);border:0.5px solid var(--blue-border);border-radius:var(--radius-sm);padding:10px 14px"><div style="font-size:13px;font-weight:600;color:#0C447C;margin-bottom:3px">${icon} ${title}</div><div style="font-size:12px;color:#185FA5">${detail}</div></div>`;
}
function handleKey(e) { if (e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendEdna();} }
function quickMsg(txt) { document.getElementById('edna-input').value=txt; sendEdna(); }
function addBubble(txt, type) {
    const chat = document.getElementById('edna-chat');
    const d = document.createElement('div'); d.className='bubble '+type; d.textContent=txt;
    chat.appendChild(d); chat.scrollTop=chat.scrollHeight; return d;
}
function sendEdna() {
    const inp = document.getElementById('edna-input');
    const txt = inp.value.trim(); if (!txt) return;
    inp.value = ''; addBubble(txt,'user');
    const t = addBubble('Thinking...','edna thinking');
    fetch('/api/edna.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:txt,context:'dashboard'})})
        .then(r=>r.json()).then(data=>{t.remove();addBubble(data.reply||"Sorry, no response.",'edna');})
        .catch(()=>{t.remove();addBubble("Couldn't reach the server — try again.",'edna');});
}
let listening=false,recognition=null;
function toggleMic(){
    if(!('webkitSpeechRecognition' in window||'SpeechRecognition' in window)){addBubble("Voice input needs Chrome — type instead!",'edna');return;}
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
