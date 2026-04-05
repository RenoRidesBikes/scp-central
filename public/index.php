<?php
/**
 * SCP Central — Main Dashboard
 * public/index.php
 *
 * Protected by auth middleware.
 * Widgets gated by RBAC (hasPermission) + user hide prefs (dashboard_prefs JSONB).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/permissions.php';

// ── Current user (set by auth.php into $_AUTH_USER) ──────────────────────────
$userId    = (int) $_AUTH_USER['id'];
$userName  = $_AUTH_USER['name'] ?? $_AUTH_USER['username'] ?? 'User';
$userRole  = strtolower($_AUTH_USER['role'] ?? 'csr');

// Build initials (up to 2 chars)
$parts    = array_filter(explode(' ', trim($userName)));
$initials = strtoupper(
    count($parts) >= 2
        ? mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1)
        : mb_substr($parts[0] ?? 'U', 0, 2)
);
$firstName = $parts[0] ?? 'there';

// Role display label
$roleLabels = [
    'csr'              => 'CSR',
    'senior_estimator' => 'Senior Estimator',
    'manager'          => 'Manager',
    'partner'          => 'Partner',
    'admin'            => 'Admin',
];
$roleLabel = $roleLabels[$userRole] ?? ucfirst($userRole);

// ── Load dashboard prefs ──────────────────────────────────────────────────────
$stmt  = getDB()->prepare('SELECT dashboard_prefs FROM users WHERE id = ?');
$stmt->execute([$userId]);
$prefsRaw = $stmt->fetchColumn();
$prefs    = json_decode($prefsRaw ?: '{}', true);
$hidden   = $prefs['hidden'] ?? [];

// ── Widget visibility ─────────────────────────────────────────────────────────
// widgetAllowed = permission gate only (used in Customize panel)
// widgetOn      = permission gate AND not hidden by user
$widgetAllowed = [];
$widgetOn      = [];
$widgetKeys    = ['new_quote', 'my_queue', 'aged_quotes', 'team_pipeline', 'win_rate', 'revenue'];

foreach ($widgetKeys as $key) {
    $allowed             = hasPermission($userId, "widget:{$key}", PERM_READ);
    $widgetAllowed[$key] = $allowed;
    $widgetOn[$key]      = $allowed && !($hidden[$key] ?? false);
}

// ── Time-of-day greeting ──────────────────────────────────────────────────────
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// ── Placeholder data ──────────────────────────────────────────────────────────
// TODO: replace each value with a real DB query once quotes table has data.
// Structure is final — just swap the values.
$d = [
    'my_queue_count'     => 6,
    'my_queue_action'    => 4,
    'aged_count'         => 3,
    'aged_oldest_days'   => 38,
    'win_rate_pct'       => 74,
    'win_rate_period'    => 'last 90 days',
    'win_rate_delta'     => '+3%',
    'win_rate_up'        => true,
    'pipeline_count'     => 14,
    'pipeline_value'     => '$42,800',
    'pipeline_pending'   => 5,
    'revenue_mtd'        => '$187,200',
    'revenue_target'     => '$210,000',
    'revenue_pct'        => 89,
];

// ── Edna dashboard context ────────────────────────────────────────────────────
// Static contextual blurb — no API call on load (expensive + slow).
// "Brief Me" button can trigger a real Edna call later.
$ednaSummary = match(true) {
    in_array($userRole, ['partner'])     => "Revenue is tracking at {$d['revenue_pct']}% of target this month. Pipeline looks solid at {$d['pipeline_value']} across {$d['pipeline_count']} open quotes.",
    in_array($userRole, ['manager'])     => "Team has {$d['pipeline_count']} open quotes worth {$d['pipeline_value']}. Win rate is holding at {$d['win_rate_pct']}% — {$d['win_rate_delta']} vs last period.",
    default                              => "You have {$d['my_queue_count']} open quotes, {$d['my_queue_action']} need attention. " . ($d['aged_count'] > 0 ? "{$d['aged_count']} quotes are going quiet — worth a follow-up." : "No aged quotes. Nice work."),
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SCP Central — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
/* ── Reset & base ─────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 14px;
    background: #F4F4F6;
    color: #1A1A1C;
    -webkit-font-smoothing: antialiased;
}

/* ── Design tokens ───────────────────────────────────────────────────────── */
:root {
    --red:          #D94032;
    --red-dark:     #B5342A;
    --blue:         #0F52A0;
    --blue-mid:     #1A68C4;
    --blue-light:   #E6F1FB;
    --green:        #2E6B0F;
    --green-light:  #EAF4E2;
    --amber:        #854F0B;
    --amber-light:  #FEF3E2;
    --bg:           #F4F4F6;
    --bg-card:      #FFFFFF;
    --bg-surface:   #F8F8FA;
    --border:       rgba(0,0,0,0.08);
    --border-mid:   rgba(0,0,0,0.14);
    --text:         #1A1A1C;
    --text-mid:     #4A4A50;
    --text-muted:   #80808A;
    --topbar-bg:    #1E1E20;
    --topbar-h:     52px;
    --sans:         'IBM Plex Sans', sans-serif;
    --mono:         'IBM Plex Mono', monospace;
    --radius:       8px;
    --radius-sm:    5px;
    --shadow:       0 1px 4px rgba(0,0,0,0.06), 0 0 0 0.5px rgba(0,0,0,0.07);
    --shadow-md:    0 4px 16px rgba(0,0,0,0.10), 0 0 0 0.5px rgba(0,0,0,0.06);
}

/* ── Topbar ──────────────────────────────────────────────────────────────── */
.topbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    height: var(--topbar-h);
    background: var(--topbar-bg);
    display: flex; align-items: center; gap: 0;
    padding: 0 20px;
    border-bottom: 0.5px solid rgba(255,255,255,0.08);
}
.topbar-brand {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0; margin-right: 28px;
    cursor: pointer;
}
.topbar-logo {
    width: 30px; height: 30px; border-radius: 50%;
    background: var(--red); display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.topbar-logo svg { display: block; }
.topbar-wordmark {
    font-size: 14px; font-weight: 600; color: #fff;
    letter-spacing: 0.01em; white-space: nowrap;
}
.topbar-module-sep { color: rgba(255,255,255,0.2); font-size: 16px; margin: 0 2px; }
.topbar-module {
    font-size: 12px; font-weight: 400; color: rgba(255,255,255,0.40);
    white-space: nowrap;
}
.topbar-nav {
    display: flex; align-items: center; gap: 2px; flex: 1;
}
.topbar-nav a {
    font-size: 13px; color: rgba(255,255,255,0.55); text-decoration: none;
    padding: 6px 12px; border-radius: var(--radius-sm);
    transition: color 0.12s, background 0.12s;
    white-space: nowrap;
}
.topbar-nav a:hover { color: #fff; background: rgba(255,255,255,0.07); }
.topbar-nav a.active { color: #fff; background: rgba(255,255,255,0.10); font-weight: 500; }

/* ── User dropdown ───────────────────────────────────────────────────────── */
.topbar-user {
    position: relative; display: flex; align-items: center; gap: 9px;
    cursor: pointer; padding: 5px 8px; border-radius: var(--radius-sm);
    transition: background 0.12s; flex-shrink: 0;
}
.topbar-user:hover { background: rgba(255,255,255,0.07); }
.topbar-avatar {
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--red); display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0; letter-spacing: 0.03em;
}
.topbar-user-info { line-height: 1.25; }
.topbar-user-name { font-size: 13px; font-weight: 500; color: #fff; }
.topbar-user-role { font-size: 10px; color: rgba(255,255,255,0.38); text-transform: uppercase; letter-spacing: 0.06em; }
.topbar-chevron { opacity: 0.4; flex-shrink: 0; transition: transform 0.2s; }
.topbar-user.open .topbar-chevron { transform: rotate(180deg); }

.topbar-dropdown {
    display: none; position: absolute; top: calc(100% + 6px); right: 0;
    background: #2A2A2C; border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: var(--radius-sm); min-width: 168px; padding: 4px 0;
    box-shadow: var(--shadow-md); z-index: 200;
}
.topbar-user.open .topbar-dropdown { display: block; }
.dd-item {
    font-size: 13px; color: rgba(255,255,255,0.72); padding: 8px 14px;
    cursor: pointer; transition: background 0.1s;
}
.dd-item:hover { background: rgba(255,255,255,0.08); color: #fff; }
.dd-label {
    font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.28);
    text-transform: uppercase; letter-spacing: 0.07em; padding: 8px 14px 4px;
}
.dd-divider { border-top: 0.5px solid rgba(255,255,255,0.09); margin: 4px 0; }

/* ── Layout ──────────────────────────────────────────────────────────────── */
.page {
    margin-top: var(--topbar-h);
    display: flex; min-height: calc(100vh - var(--topbar-h));
}
.main-col {
    flex: 1 1 0; min-width: 0;
    padding: 28px 28px 60px;
}
.edna-col {
    width: 300px; flex-shrink: 0;
    border-left: 0.5px solid var(--border);
    background: var(--bg-card);
    display: flex; flex-direction: column;
    position: sticky; top: var(--topbar-h);
    height: calc(100vh - var(--topbar-h));
    overflow-y: auto;
}

/* ── Page header ─────────────────────────────────────────────────────────── */
.page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 24px; gap: 16px;
}
.page-header-left {}
.page-greeting {
    font-size: 20px; font-weight: 600; color: var(--text);
    letter-spacing: -0.01em; line-height: 1.2;
}
.page-subtitle {
    font-size: 13px; color: var(--text-muted); margin-top: 3px;
}
.customize-btn {
    display: flex; align-items: center; gap: 6px;
    font-family: var(--sans); font-size: 12px; color: var(--text-muted);
    background: var(--bg-card); border: 0.5px solid var(--border-mid);
    border-radius: var(--radius-sm); padding: 6px 12px; cursor: pointer;
    transition: border-color 0.12s, color 0.12s; white-space: nowrap; flex-shrink: 0;
}
.customize-btn:hover { border-color: #aaa; color: var(--text); }
.customize-btn svg { opacity: 0.5; }

/* ── Customize panel ─────────────────────────────────────────────────────── */
.customize-panel {
    display: none; background: var(--bg-card); border: 0.5px solid var(--border-mid);
    border-radius: var(--radius); padding: 16px 18px; margin-bottom: 20px;
    box-shadow: var(--shadow);
}
.customize-panel.open { display: block; }
.customize-panel-title {
    font-size: 11px; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 12px;
}
.customize-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.widget-toggle {
    display: flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 20px;
    border: 0.5px solid var(--border-mid); background: var(--bg-surface);
    cursor: pointer; font-size: 12px; color: var(--text-mid);
    transition: all 0.12s; user-select: none;
}
.widget-toggle.on {
    background: var(--blue-light); border-color: #97C0E8; color: var(--blue);
}
.widget-toggle .dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--border-mid); flex-shrink: 0;
}
.widget-toggle.on .dot { background: var(--blue); }

/* ── Widget grid ─────────────────────────────────────────────────────────── */
.widget-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
.widget-full { grid-column: 1 / -1; }

/* ── Base widget card ────────────────────────────────────────────────────── */
.widget {
    background: var(--bg-card);
    border: 0.5px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    box-shadow: var(--shadow);
    display: flex; flex-direction: column; gap: 12px;
}
.widget-header {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.widget-title {
    font-size: 11px; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.07em;
}
.widget-action {
    font-size: 12px; color: var(--blue-mid); text-decoration: none; cursor: pointer;
    white-space: nowrap;
}
.widget-action:hover { color: var(--blue); text-decoration: underline; }

/* ── New Quote widget ────────────────────────────────────────────────────── */
.widget-new-quote {
    background: linear-gradient(135deg, #0F52A0 0%, #1A68C4 100%);
    border-color: #0C447C;
    cursor: default;
}
.widget-new-quote .widget-title { color: rgba(255,255,255,0.55); }
.nq-label {
    font-size: 18px; font-weight: 600; color: #fff; line-height: 1.2;
}
.nq-sub { font-size: 12px; color: rgba(255,255,255,0.55); margin-top: 2px; }
.nq-input-wrap { position: relative; }
.nq-input {
    width: 100%; font-family: var(--sans); font-size: 13px;
    background: rgba(255,255,255,0.12); border: 0.5px solid rgba(255,255,255,0.20);
    border-radius: var(--radius-sm); color: #fff; padding: 9px 36px 9px 12px;
    outline: none; transition: border-color 0.12s, background 0.12s;
}
.nq-input::placeholder { color: rgba(255,255,255,0.38); }
.nq-input:focus { border-color: rgba(255,255,255,0.50); background: rgba(255,255,255,0.16); }
.nq-submit {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,0.18); border: none; border-radius: 3px;
    color: #fff; cursor: pointer; width: 24px; height: 24px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.12s;
}
.nq-submit:hover { background: rgba(255,255,255,0.30); }
.nq-hint { font-size: 11px; color: rgba(255,255,255,0.38); }

/* ── Metric widget ───────────────────────────────────────────────────────── */
.metric-value {
    font-size: 36px; font-weight: 600; font-family: var(--mono);
    color: var(--text); line-height: 1;
}
.metric-value.green  { color: var(--green); }
.metric-value.amber  { color: var(--amber); }
.metric-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.metric-delta {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 11px; font-family: var(--mono);
    padding: 2px 7px; border-radius: 20px; margin-top: 6px;
}
.metric-delta.up   { background: var(--green-light); color: var(--green); }
.metric-delta.down { background: var(--amber-light); color: var(--amber); }

/* ── Aged quotes alert accent ────────────────────────────────────────────── */
.widget-aged { border-left: 3px solid #D97706; }
.aged-list { display: flex; flex-direction: column; gap: 6px; }
.aged-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 10px; background: var(--amber-light); border-radius: var(--radius-sm);
    font-size: 12px; color: var(--amber);
}
.aged-placeholder {
    font-size: 12px; color: var(--text-muted); font-style: italic;
    padding: 6px 0;
}

/* ── My queue widget ─────────────────────────────────────────────────────── */
.queue-stats {
    display: flex; gap: 20px; padding-bottom: 12px;
    border-bottom: 0.5px solid var(--border);
}
.queue-stat { }
.queue-stat-val { font-size: 26px; font-weight: 600; font-family: var(--mono); }
.queue-stat-val.amber { color: var(--amber); }
.queue-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
.queue-placeholder { font-size: 12px; color: var(--text-muted); font-style: italic; padding: 4px 0; }

/* ── Team pipeline widget ────────────────────────────────────────────────── */
.pipeline-stats { display: flex; gap: 20px; }
.pipeline-stat-val { font-size: 26px; font-weight: 600; font-family: var(--mono); }
.pipeline-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
.pipeline-bar-wrap { margin-top: 2px; }
.pipeline-bar-track {
    height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;
}
.pipeline-bar-fill {
    height: 100%; background: var(--blue); border-radius: 2px;
    transition: width 0.4s ease;
}
.pipeline-bar-lbl { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

/* ── Revenue widget ──────────────────────────────────────────────────────── */
.rev-progress-wrap { margin-top: 2px; }
.rev-track {
    height: 6px; background: var(--border); border-radius: 3px; overflow: hidden;
}
.rev-fill {
    height: 100%; border-radius: 3px;
    background: linear-gradient(90deg, var(--green) 0%, #52A02A 100%);
    transition: width 0.5s ease;
}
.rev-labels {
    display: flex; justify-content: space-between;
    font-size: 11px; color: var(--text-muted); margin-top: 5px;
    font-family: var(--mono);
}

/* ── Edna pane ───────────────────────────────────────────────────────────── */
.edna-header {
    padding: 16px 16px 12px;
    border-bottom: 0.5px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.edna-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--topbar-bg); display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.edna-name { font-size: 13px; font-weight: 600; color: var(--text); }
.edna-tagline { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
.edna-body { padding: 16px; flex: 1; }
.edna-bubble {
    background: var(--bg-surface); border: 0.5px solid var(--border);
    border-radius: 0 var(--radius) var(--radius) var(--radius);
    padding: 12px 14px; font-size: 13px; color: var(--text-mid); line-height: 1.55;
    position: relative;
}
.edna-bubble::before {
    content: ''; position: absolute; top: 0; left: -6px;
    border: 6px solid transparent;
    border-right-color: var(--border);
    border-top: none;
}
.edna-timestamp {
    font-size: 11px; color: var(--text-muted); margin-top: 8px; text-align: right;
    font-family: var(--mono);
}
.edna-footer {
    padding: 14px 16px;
    border-top: 0.5px solid var(--border);
    display: flex; flex-direction: column; gap: 8px;
}
.edna-brief-btn {
    font-family: var(--sans); font-size: 12px; font-weight: 500;
    background: var(--topbar-bg); color: rgba(255,255,255,0.8);
    border: none; border-radius: var(--radius-sm); padding: 8px 14px;
    cursor: pointer; text-align: left; display: flex; align-items: center; gap: 7px;
    transition: background 0.12s;
}
.edna-brief-btn:hover { background: #2e2e32; }
.edna-brief-btn .spark { font-size: 14px; }
.edna-nav-links { display: flex; flex-direction: column; gap: 0; }
.edna-nav-link {
    font-size: 12px; color: var(--blue-mid); text-decoration: none;
    padding: 5px 0; border-bottom: 0.5px solid var(--border); display: flex;
    align-items: center; gap: 6px; transition: color 0.1s;
}
.edna-nav-link:last-child { border-bottom: none; }
.edna-nav-link:hover { color: var(--blue); }

/* ── Empty state (no widgets visible) ───────────────────────────────────── */
.no-widgets {
    grid-column: 1 / -1; text-align: center; padding: 60px 20px;
    color: var(--text-muted); font-size: 13px;
}

/* ── Utility ─────────────────────────────────────────────────────────────── */
.hidden { display: none !important; }
</style>
</head>
<body>

<!-- ══════════ TOPBAR ══════════ -->
<div class="topbar">
    <div class="topbar-brand" onclick="window.location='/'">
        <div class="topbar-logo">
            <svg width="18" height="18" viewBox="0 0 32 32" fill="none">
                <path d="M21.2 10.8C20.2 9.7 18.7 9 16.9 9C13.6 9 11 11.2 11 14C11 16.1 12.3 17.6 14.6 18.4L17.1 19.3C18.4 19.8 19 20.4 19 21.2C19 22.2 18.1 22.9 16.8 22.9C15.4 22.9 14.2 22.1 13.4 20.8L11.2 22.1C12.3 24 14.3 25 16.8 25C20.3 25 22.8 22.8 22.8 19.9C22.8 17.7 21.5 16.2 19 15.3L16.6 14.4C15.4 14 14.8 13.4 14.8 12.6C14.8 11.7 15.6 11 16.8 11C17.9 11 18.9 11.6 19.5 12.6L21.2 10.8Z" fill="white"/>
            </svg>
        </div>
        <span class="topbar-wordmark">Still Creek Press</span>
        <span class="topbar-module-sep">/</span>
        <span class="topbar-module">SCP Central</span>
    </div>

    <nav class="topbar-nav">
        <a href="/" class="active">Dashboard</a>
        <a href="/modules/forms-estimating/">Forms Estimating</a>
        <!-- Future modules added here as they're built -->
    </nav>

    <div class="topbar-user" id="userBtn" onclick="toggleUserMenu()">
        <div class="topbar-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="topbar-user-info">
            <div class="topbar-user-name"><?= htmlspecialchars($userName) ?></div>
            <div class="topbar-user-role"><?= htmlspecialchars($roleLabel) ?></div>
        </div>
        <svg class="topbar-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M2 4l4 4 4-4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="topbar-dropdown" id="userDropdown">
            <div class="dd-label">Signed in as</div>
            <div class="dd-item" style="color:rgba(255,255,255,0.9);cursor:default">
                <?= htmlspecialchars($userName) ?>
                <span style="color:rgba(255,255,255,0.3);font-size:11px;margin-left:6px"><?= htmlspecialchars($roleLabel) ?></span>
            </div>
            <div class="dd-divider"></div>
            <div class="dd-item" onclick="window.location='/preferences.php'">Preferences</div>
            <div class="dd-divider"></div>
            <div class="dd-item" onclick="window.location='/logout.php'">Sign out</div>
        </div>
    </div>
</div>

<!-- ══════════ PAGE ══════════ -->
<div class="page">

    <!-- ── Main column ── -->
    <div class="main-col">

        <!-- Page header -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-greeting"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?>.</div>
                <div class="page-subtitle"><?= date('l, F j, Y') ?></div>
            </div>
            <?php if (array_filter($widgetAllowed)): // only show if user has any permitted widgets ?>
            <button class="customize-btn" onclick="toggleCustomize()" id="customizeBtn">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.05 3.05l1.41 1.41M11.54 11.54l1.41 1.41M3.05 12.95l1.41-1.41M11.54 4.46l1.41-1.41" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Customize
            </button>
            <?php endif; ?>
        </div>

        <!-- Customize panel -->
        <div class="customize-panel" id="customizePanel">
            <div class="customize-panel-title">Show / hide widgets</div>
            <div class="customize-grid" id="customizeGrid">
                <?php
                $widgetLabels = [
                    'new_quote'     => '✦ New Quote',
                    'my_queue'      => '📋 My Queue',
                    'aged_quotes'   => '⏰ Aged Quotes',
                    'team_pipeline' => '📊 Team Pipeline',
                    'win_rate'      => '🎯 Win Rate',
                    'revenue'       => '💰 Revenue',
                ];
                foreach ($widgetAllowed as $key => $allowed):
                    if (!$allowed) continue;
                    $isOn = !($hidden[$key] ?? false);
                ?>
                <div class="widget-toggle <?= $isOn ? 'on' : '' ?>"
                     data-key="<?= $key ?>"
                     onclick="toggleWidget('<?= $key ?>')">
                    <span class="dot"></span>
                    <?= htmlspecialchars($widgetLabels[$key] ?? $key) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Widget grid -->
        <div class="widget-grid" id="widgetGrid">

            <?php if (!array_filter($widgetOn)): ?>
            <div class="no-widgets">
                No widgets visible. Use <strong>Customize</strong> above to turn some on.
            </div>
            <?php endif; ?>

            <!-- ── New Quote ── -->
            <?php if ($widgetOn['new_quote']): ?>
            <div class="widget widget-new-quote widget-full" id="w-new_quote">
                <div class="widget-header">
                    <span class="widget-title">New Quote</span>
                </div>
                <div>
                    <div class="nq-label">Start a quote</div>
                    <div class="nq-sub">Describe the job — Edna will parse the spec</div>
                </div>
                <div class="nq-input-wrap">
                    <input class="nq-input" id="nqInput"
                           placeholder="Customer, job name, or paste a spec…"
                           onkeydown="nqKeydown(event)">
                    <button class="nq-submit" onclick="nqGo()" title="Start quote">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M2 6h8M7 3l3 3-3 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="nq-hint">Press Enter or → to continue · Edna reviews your spec before pricing</div>
            </div>
            <?php endif; ?>

            <!-- ── My Queue ── -->
            <?php if ($widgetOn['my_queue']): ?>
            <div class="widget" id="w-my_queue">
                <div class="widget-header">
                    <span class="widget-title">My Open Quotes</span>
                    <a class="widget-action" href="/modules/forms-estimating/">View all →</a>
                </div>
                <div class="queue-stats">
                    <div class="queue-stat">
                        <div class="queue-stat-val"><?= $d['my_queue_count'] ?></div>
                        <div class="queue-stat-lbl">open</div>
                    </div>
                    <div class="queue-stat">
                        <div class="queue-stat-val amber"><?= $d['my_queue_action'] ?></div>
                        <div class="queue-stat-lbl">need action</div>
                    </div>
                </div>
                <div class="queue-placeholder">
                    <!-- TODO: replace with real quote rows once quotes table has data -->
                    Quote list coming once estimating data is live.
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Aged Quotes ── -->
            <?php if ($widgetOn['aged_quotes']): ?>
            <div class="widget widget-aged" id="w-aged_quotes">
                <div class="widget-header">
                    <span class="widget-title">Aged Quotes</span>
                    <a class="widget-action" href="/modules/forms-estimating/?filter=aged">Follow up →</a>
                </div>
                <div>
                    <div class="metric-value amber"><?= $d['aged_count'] ?></div>
                    <div class="metric-sub">30+ days silent · oldest <?= $d['aged_oldest_days'] ?> days</div>
                </div>
                <div class="aged-list">
                    <div class="aged-placeholder">
                        <!-- TODO: replace with real aged quote rows -->
                        Aged quote details will appear once estimating data is live.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Team Pipeline ── -->
            <?php if ($widgetOn['team_pipeline']): ?>
            <div class="widget widget-full" id="w-team_pipeline">
                <div class="widget-header">
                    <span class="widget-title">Team Pipeline</span>
                    <a class="widget-action" href="/modules/forms-estimating/?view=all">All quotes →</a>
                </div>
                <div class="pipeline-stats">
                    <div>
                        <div class="pipeline-stat-val"><?= $d['pipeline_count'] ?></div>
                        <div class="pipeline-stat-lbl">open quotes</div>
                    </div>
                    <div>
                        <div class="pipeline-stat-val"><?= $d['pipeline_value'] ?></div>
                        <div class="pipeline-stat-lbl">total value</div>
                    </div>
                    <div>
                        <div class="pipeline-stat-val"><?= $d['pipeline_pending'] ?></div>
                        <div class="pipeline-stat-lbl">awaiting approval</div>
                    </div>
                </div>
                <div class="pipeline-bar-wrap">
                    <?php $pipelinePct = min(100, round(($d['pipeline_count'] / 20) * 100)); ?>
                    <div class="pipeline-bar-track">
                        <div class="pipeline-bar-fill" style="width:<?= $pipelinePct ?>%"></div>
                    </div>
                    <div class="pipeline-bar-lbl">
                        <!-- TODO: replace with real CSR breakdown -->
                        CSR breakdown available once team data is live.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Win Rate ── -->
            <?php if ($widgetOn['win_rate']): ?>
            <div class="widget" id="w-win_rate">
                <div class="widget-header">
                    <span class="widget-title">Win Rate</span>
                </div>
                <div>
                    <div class="metric-value green"><?= $d['win_rate_pct'] ?>%</div>
                    <div class="metric-sub"><?= htmlspecialchars($d['win_rate_period']) ?></div>
                    <div class="metric-delta <?= $d['win_rate_up'] ? 'up' : 'down' ?>">
                        <?= $d['win_rate_up'] ? '↑' : '↓' ?> <?= htmlspecialchars($d['win_rate_delta']) ?> vs prior period
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Revenue ── -->
            <?php if ($widgetOn['revenue']): ?>
            <div class="widget" id="w-revenue">
                <div class="widget-header">
                    <span class="widget-title">Revenue MTD</span>
                </div>
                <div>
                    <div class="metric-value"><?= $d['revenue_mtd'] ?></div>
                    <div class="metric-sub">target <?= $d['revenue_target'] ?></div>
                </div>
                <div class="rev-progress-wrap">
                    <div class="rev-track">
                        <div class="rev-fill" style="width:<?= $d['revenue_pct'] ?>%"></div>
                    </div>
                    <div class="rev-labels">
                        <span>0</span>
                        <span><?= $d['revenue_pct'] ?>% of target</span>
                        <span><?= $d['revenue_target'] ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /widget-grid -->

    </div><!-- /main-col -->

    <!-- ── Edna pane ── -->
    <div class="edna-col">
        <div class="edna-header">
            <div class="edna-avatar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="rgba(255,255,255,0.7)" stroke-width="1.5"/>
                    <path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <div>
                <div class="edna-name">Edna</div>
                <div class="edna-tagline">40 years on the press floor</div>
            </div>
        </div>

        <div class="edna-body">
            <div class="edna-bubble" id="ednaBubble">
                <?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?>. <?= htmlspecialchars($ednaSummary) ?>
            </div>
            <div class="edna-timestamp" id="ednaTimestamp"><?= date('g:i a') ?></div>
        </div>

        <div class="edna-footer">
            <button class="edna-brief-btn" onclick="ednaBrief()" id="ednaBriefBtn">
                <span class="spark">✦</span>
                Morning briefing
            </button>
            <div class="edna-nav-links">
                <a class="edna-nav-link" href="/modules/forms-estimating/">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6h8M7 3l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Go to Forms Estimating
                </a>
                <?php if (hasPermission($userId, 'page:admin', PERM_READ)): ?>
                <a class="edna-nav-link" href="/admin.php">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6h8M7 3l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Admin
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /edna-col -->

</div><!-- /page -->

<script>
/* ── User dropdown ────────────────────────────────────────────────────────── */
function toggleUserMenu() {
    document.getElementById('userBtn').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const btn = document.getElementById('userBtn');
    if (btn && !btn.contains(e.target)) btn.classList.remove('open');
});

/* ── Customize panel ──────────────────────────────────────────────────────── */
function toggleCustomize() {
    const panel = document.getElementById('customizePanel');
    const btn   = document.getElementById('customizeBtn');
    panel.classList.toggle('open');
    btn.style.color = panel.classList.contains('open') ? 'var(--blue)' : '';
}

/* ── Widget toggle ────────────────────────────────────────────────────────── */
// Current hidden state — mirrors PHP's $hidden
const hiddenState = <?= json_encode((object)$hidden) ?>;
// Widgets this user is permitted to see
const widgetAllowed = <?= json_encode(array_keys(array_filter($widgetAllowed))) ?>;

function toggleWidget(key) {
    // Flip state
    hiddenState[key] = !hiddenState[key];

    // Update toggle pill
    const pill = document.querySelector(`.widget-toggle[data-key="${key}"]`);
    if (pill) pill.classList.toggle('on', !hiddenState[key]);

    // Show/hide the actual widget card
    const card = document.getElementById('w-' + key);
    if (card) card.classList.toggle('hidden', !!hiddenState[key]);

    // Check if any widgets are now showing; display empty state if not
    const anyVisible = widgetAllowed.some(k => !hiddenState[k]);
    let empty = document.querySelector('.no-widgets');
    if (!anyVisible && !empty) {
        empty = document.createElement('div');
        empty.className = 'no-widgets';
        empty.innerHTML = 'No widgets visible. Use <strong>Customize</strong> above to turn some on.';
        document.getElementById('widgetGrid').prepend(empty);
    } else if (anyVisible && empty) {
        empty.remove();
    }

    // Persist to server (fire and forget — no user feedback needed for hide/show)
    savePrefs();
}

function savePrefs() {
    fetch('/api/prefs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ hidden: hiddenState })
    }).catch(err => console.warn('Prefs save failed:', err));
}

/* ── New Quote ────────────────────────────────────────────────────────────── */
function nqKeydown(e) {
    if (e.key === 'Enter' || e.key === 'ArrowRight') nqGo();
}
function nqGo() {
    const val = document.getElementById('nqInput').value.trim();
    if (!val) { document.getElementById('nqInput').focus(); return; }
    const dest = '/modules/forms-estimating/spec_review.php?q=' + encodeURIComponent(val);
    window.location.href = dest;
}

/* ── Edna morning briefing ────────────────────────────────────────────────── */
function ednaBrief() {
    const btn     = document.getElementById('ednaBriefBtn');
    const bubble  = document.getElementById('ednaBubble');
    const ts      = document.getElementById('ednaTimestamp');

    btn.disabled  = true;
    btn.textContent = '⏳ Asking Edna…';
    bubble.style.opacity = '0.5';

    fetch('/api/edna.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            context: 'dashboard_brief',
            role:    <?= json_encode($userRole) ?>,
            name:    <?= json_encode($firstName) ?>
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.reply) {
            bubble.textContent = data.reply;
            ts.textContent = new Date().toLocaleTimeString('en-CA', { hour: 'numeric', minute: '2-digit' });
        }
    })
    .catch(() => {
        bubble.textContent = 'I couldn\'t reach the server right now — try again in a moment.';
    })
    .finally(() => {
        btn.disabled  = false;
        btn.innerHTML = '<span class="spark">✦</span> Morning briefing';
        bubble.style.opacity = '1';
    });
}
</script>

</body>
</html>
