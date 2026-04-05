<?php
/**
 * /includes/header.php
 *
 * Variables expected before include:
 *   $pageTitle  string   e.g. 'Dashboard', 'Estimating'
 *   $activePage string   nav key: 'dashboard' | 'estimating' | 'customers' | 'jobs' | 'reports' | 'admin'
 *   $navBadges  array    optional — e.g. ['estimating' => 6]
 *   $extraCss   string   optional page-specific <style> block (include the <style> tags)
 *
 * Expects $_AUTH_USER to be set by auth.php before this include.
 */

// Derive user display values
$_navName     = $_AUTH_USER['name'] ?? 'User';
$_navRole     = $_AUTH_USER['role'] ?? 'CSR';
$_navWords    = array_filter(explode(' ', trim($_navName)));
$_navInitials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice($_navWords, 0, 2)));
$_navBadges   = $navBadges ?? [];
$_activePage  = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'SCP Central') ?> — SCP Central</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ══ RESET & BASE ══ */
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --red:#D94032;--red-light:#FCEBEB;--red-border:#F7C1C1;
  --blue:#1A4E8F;--blue-mid:#185FA5;--blue-light:#E6F1FB;--blue-border:#B5D4F4;
  --green:#3B6D11;--green-light:#EAF3DE;--green-border:#C0DD97;
  --amber:#854F0B;--amber-light:#FAEEDA;--amber-border:#FAC775;
  --text:#1a1a1a;--text-mid:#555;--text-muted:#888;
  --border:#e4e2de;--border-mid:#ccc;
  --bg:#FAF8F5;--bg-card:#ffffff;--bg-surface:#F9F8F6;
  --nav-bg:#F5F3EF;--nav-width:240px;--nav-collapsed:60px;
  --topbar:52px;
  --mono:'DM Mono',monospace;--sans:'DM Sans',sans-serif;
  --radius:12px;--radius-sm:8px;
}
body{font-family:var(--sans);background:var(--bg);color:var(--text);font-size:14px;line-height:1.5;min-height:100vh;overflow-x:hidden}

/* ══ LAYOUT SHELL ══ */
.shell{display:flex;min-height:100vh}

/* ══ SIDE NAV ══ */
.sidenav{
  width:var(--nav-width);background:var(--nav-bg);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;
  z-index:200;transition:width 0.25s ease;
  overflow:hidden;border-right:0.5px solid var(--border);
}
.sidenav.collapsed{width:var(--nav-collapsed)}
.sidenav.collapsed .nav-header{padding:0;justify-content:center}
.sidenav.collapsed .nav-logo,.sidenav.collapsed .nav-brand{display:none}
.nav-header{
  display:flex;align-items:center;gap:10px;
  padding:0 14px 0 16px;height:var(--topbar);
  border-bottom:0.5px solid var(--border);
  flex-shrink:0;overflow:hidden;
}
.nav-logo{
  width:32px;height:32px;border-radius:50%;background:var(--red);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;font-weight:700;font-size:14px;color:#fff;letter-spacing:0.02em;
  font-family:var(--sans);cursor:pointer;text-decoration:none;
}
.nav-brand{white-space:nowrap;overflow:hidden;flex:1;cursor:pointer;text-decoration:none}
.nav-brand-name{font-size:13px;font-weight:600;color:var(--text);line-height:1.2}
.nav-brand-sub{font-size:10px;color:var(--text-muted);letter-spacing:0.06em;text-transform:uppercase}
.nav-collapse-btn{
  width:28px;height:28px;border-radius:6px;border:none;
  background:var(--bg-surface);color:var(--text-muted);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:background 0.15s,color 0.15s;
}
.nav-collapse-btn:hover{background:var(--border);color:var(--text)}
.nav-section-label{
  font-size:10px;font-weight:500;color:var(--text-muted);
  text-transform:uppercase;letter-spacing:0.09em;
  padding:20px 18px 5px;white-space:nowrap;overflow:hidden;
  transition:opacity 0.2s;
}
.sidenav.collapsed .nav-section-label{opacity:0;pointer-events:none}
.nav-item{
  display:flex;align-items:center;gap:12px;
  padding:10px 18px;cursor:pointer;
  color:#444440;font-size:13px;font-weight:400;
  border-left:2px solid transparent;
  transition:all 0.15s;white-space:nowrap;overflow:hidden;
  text-decoration:none;position:relative;
}
.nav-item:hover{color:var(--text);background:var(--bg-surface)}
.nav-item.active{color:var(--red);background:#FEF0EF;border-left-color:var(--red)}
.nav-item-icon{width:18px;height:18px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.nav-item-icon svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.nav-item-label{white-space:nowrap;overflow:hidden;flex:1}
.nav-badge{
  flex-shrink:0;background:var(--red);color:#fff;
  font-size:10px;font-weight:600;padding:2px 7px;
  border-radius:20px;font-family:var(--mono);
}
.sidenav.collapsed .nav-item-label,
.sidenav.collapsed .nav-badge,
.sidenav.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.nav-tooltip{
  position:fixed;left:calc(var(--nav-collapsed) + 10px);
  background:#2a2a2c;color:rgba(255,255,255,0.9);font-size:12px;
  padding:5px 11px;border-radius:6px;white-space:nowrap;
  pointer-events:none;opacity:0;transition:opacity 0.15s;z-index:500;
  border:0.5px solid rgba(255,255,255,0.1);font-family:var(--sans);
}
.sidenav.collapsed .nav-item:hover .nav-tooltip{opacity:1}
.nav-footer{
  margin-top:auto;border-top:0.5px solid var(--border);
  padding:12px 14px;display:flex;align-items:center;gap:10px;
  overflow:hidden;flex-shrink:0;cursor:pointer;transition:background 0.15s;
}
.nav-footer:hover{background:var(--bg-surface)}
.nav-avatar{
  width:32px;height:32px;border-radius:50%;background:var(--red);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;color:#fff;flex-shrink:0;font-family:var(--sans);
}
.nav-user-info{overflow:hidden;flex:1;transition:opacity 0.2s}
.nav-user-name{font-size:13px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nav-user-role{font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em}
.sidenav.collapsed .nav-user-info{opacity:0;width:0}

/* ══ MOBILE ══ */
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:190}
.nav-overlay.visible{display:block}
.mobile-menu-btn{
  display:none;width:34px;height:34px;border:none;background:transparent;
  cursor:pointer;align-items:center;justify-content:center;flex-shrink:0;
}
.mobile-menu-btn svg{width:18px;height:18px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round}
@media(max-width:768px){
  .sidenav{transform:translateX(-100%);transition:transform 0.25s ease,width 0.25s ease}
  .sidenav.mobile-open{transform:translateX(0);width:var(--nav-width)}
  .main{margin-left:0!important}
  .mobile-menu-btn{display:flex}
}

/* ══ MAIN AREA ══ */
.main{
  margin-left:var(--nav-width);flex:1;display:flex;flex-direction:column;
  min-height:100vh;transition:margin-left 0.25s ease;
}
.main.nav-collapsed{margin-left:var(--nav-collapsed)}

/* ══ TOPBAR ══ */
.topbar{
  height:var(--topbar);background:var(--bg);
  border-bottom:0.5px solid var(--border);
  display:flex;align-items:center;padding:0 24px;
  position:sticky;top:0;z-index:100;gap:12px;
}
.topbar-page-title{font-size:15px;font-weight:600;color:var(--text);flex:1}
.topbar-search{
  display:flex;align-items:center;gap:8px;
  background:var(--bg-card);border:0.5px solid var(--border);
  border-radius:var(--radius-sm);padding:0 12px;height:34px;
  width:240px;transition:border-color 0.15s,width 0.2s;
}
.topbar-search:focus-within{border-color:var(--blue-mid);width:280px}
.topbar-search svg{width:14px;height:14px;stroke:var(--text-muted);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
.topbar-search input{border:none;background:transparent;font-family:var(--sans);font-size:13px;color:var(--text);outline:none;width:100%}
.topbar-search input::placeholder{color:var(--text-muted)}
.topbar-actions{display:flex;align-items:center;gap:8px}
.topbar-btn{
  width:34px;height:34px;border-radius:var(--radius-sm);
  border:0.5px solid var(--border);background:var(--bg-card);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background 0.12s;position:relative;
}
.topbar-btn:hover{background:var(--bg-surface)}
.topbar-btn svg{width:16px;height:16px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.notif-dot{
  position:absolute;top:6px;right:6px;width:7px;height:7px;
  border-radius:50%;background:var(--red);border:1.5px solid var(--bg-card);
}
.topbar-user{
  display:flex;align-items:center;gap:8px;cursor:pointer;
  padding:5px 10px;border-radius:var(--radius-sm);
  border:0.5px solid var(--border);transition:background 0.12s;
  position:relative;background:var(--bg-card);
}
.topbar-user:hover{background:var(--bg-surface)}
.topbar-avatar{
  width:28px;height:28px;border-radius:50%;background:var(--red);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;color:#fff;font-family:var(--sans);
}
.topbar-user-name{font-size:13px;font-weight:500;color:var(--text)}
.topbar-chevron{width:12px;height:12px;stroke:var(--text-muted);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s}
.topbar-user.open .topbar-chevron{transform:rotate(180deg)}
.user-dropdown{
  display:none;position:absolute;top:calc(100% + 6px);right:0;
  background:var(--bg-card);border:0.5px solid var(--border);
  border-radius:var(--radius-sm);min-width:190px;
  box-shadow:0 4px 20px rgba(0,0,0,0.1);z-index:300;padding:4px 0;
}
.topbar-user.open .user-dropdown{display:block}
.dropdown-item{
  font-size:13px;color:var(--text);padding:9px 14px;cursor:pointer;
  display:flex;align-items:center;gap:9px;transition:background 0.1s;
}
.dropdown-item:hover{background:var(--bg-surface)}
.dropdown-item svg{width:14px;height:14px;stroke:var(--text-muted);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
.dropdown-item.danger{color:#A32D2D}
.dropdown-item.danger svg{stroke:#A32D2D}
.dropdown-divider{border-top:0.5px solid var(--border);margin:4px 0}
.dropdown-section-label{font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;padding:8px 14px 2px}

/* ══ CONTENT WRAPPER ══ */
.content{padding:28px;flex:1}
.content-inner{
  max-width:1440px;margin:0 auto;
  display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;
}

/* ══ CARDS ══ */
.card{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:20px 22px;margin-bottom:16px}
.card:last-child{margin-bottom:0}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.card-title{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em}
.card-link{font-size:12px;color:var(--blue-mid);cursor:pointer;text-decoration:none;transition:color 0.12s}
.card-link:hover{color:var(--blue);text-decoration:underline}

/* ══ METRIC CARDS ══ */
.metrics-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.metric-card{
  background:var(--bg-card);border:0.5px solid var(--border);
  border-radius:var(--radius);padding:14px 16px;
  border-left-width:3px;border-left-style:solid;
}
.metric-card.accent-blue{border-left-color:var(--blue-mid)}
.metric-card.accent-green{border-left-color:#639922}
.metric-card.accent-amber{border-left-color:#EF9F27}
.metric-card.accent-red{border-left-color:var(--red)}
.metric-label{font-size:11px;font-weight:500;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:7px}
.metric-value{font-size:24px;font-weight:600;font-family:var(--mono);color:var(--text);line-height:1}
.metric-value.green{color:#3B6D11}
.metric-value.amber{color:#854F0B}
.metric-value.red{color:#A32D2D}
.metric-sub{font-size:11px;color:var(--text-muted);margin-top:6px}

/* ══ TABLE ══ */
.tbl{width:100%;border-collapse:collapse}
.tbl th{text-align:left;font-size:10px;font-weight:600;color:var(--text-muted);padding:8px 10px;border-bottom:0.5px solid var(--border);text-transform:uppercase;letter-spacing:0.07em}
.tbl td{padding:11px 10px;border-bottom:0.5px solid var(--border);font-size:13px;vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tbody tr{cursor:pointer;transition:background 0.1s}
.tbl tbody tr:hover td{background:var(--bg-surface)}
.mono{font-family:var(--mono);font-size:12px;color:var(--blue-mid)}
.muted{color:var(--text-muted)}
.val{font-family:var(--mono);font-size:13px}

/* ══ BADGES ══ */
.badge{display:inline-block;font-size:10px;font-weight:500;padding:3px 9px;border-radius:20px;font-family:var(--mono);white-space:nowrap;letter-spacing:0.02em}
.badge-draft{background:#E6F1FB;color:#0C447C}
.badge-pending{background:var(--amber-light);color:#633806}
.badge-sent{background:#EEEDFE;color:#3C3489}
.badge-won{background:var(--green-light);color:#27500A}
.badge-lost{background:var(--red-light);color:#791F1F}
.badge-aged{background:var(--amber-light);color:#633806}
.badge-approved{background:var(--green-light);color:#27500A}

/* ══ BUTTONS ══ */
.btn{font-family:var(--sans);font-size:13px;padding:8px 16px;border-radius:var(--radius-sm);border:0.5px solid var(--border-mid);background:var(--bg-card);color:var(--text);cursor:pointer;transition:background 0.12s,border-color 0.12s;white-space:nowrap}
.btn:hover{background:var(--bg-surface);border-color:#aaa}
.btn-primary{background:var(--blue);border-color:var(--blue);color:#fff}
.btn-primary:hover{background:#0C447C;border-color:#0C447C}
.btn-sm{font-size:12px;padding:5px 12px}

/* ══ FORM FIELDS ══ */
.field-label{display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5px}
.fi{font-family:var(--sans);font-size:13px;padding:8px 11px;border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);background:var(--bg-card);color:var(--text);width:100%;transition:border-color 0.15s}
.fi:focus{outline:none;border-color:var(--blue-mid)}
textarea.fi{line-height:1.5}

/* ══ EDNA PANE ══ */
.edna-pane{
  position:sticky;top:calc(var(--topbar) + 28px);
  background:var(--bg-card);border:0.5px solid var(--border);
  border-radius:var(--radius);display:flex;flex-direction:column;
  height:580px;overflow:hidden;
}
.edna-header{
  padding:14px 16px;border-bottom:0.5px solid var(--border);
  display:flex;align-items:center;gap:12px;flex-shrink:0;
}
.edna-avatar{
  width:44px;height:44px;border-radius:50%;
  background:var(--blue-light);border:2px solid var(--blue-mid);
  display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;
}
.edna-name{font-size:15px;font-weight:600;color:var(--text)}
.edna-tagline{font-size:11px;color:var(--text-muted);margin-top:1px}
.edna-status{display:flex;align-items:center;gap:5px;margin-top:3px}
.edna-status-dot{width:6px;height:6px;border-radius:50%;background:#639922;animation:statusPulse 3s ease-in-out infinite}
.edna-status-text{font-size:10px;color:var(--text-muted)}
@keyframes statusPulse{0%,100%{opacity:1}50%{opacity:0.4}}
.edna-chat{flex:1;overflow-y:auto;padding:14px 14px 8px;display:flex;flex-direction:column;gap:10px}
.bubble{padding:10px 13px;border-radius:10px;font-size:13px;line-height:1.55;max-width:92%}
.bubble.edna{background:var(--blue-light);color:#0C3A78;border-radius:4px 10px 10px 10px;align-self:flex-start}
.bubble.user{background:var(--bg-surface);color:var(--text);border-radius:10px 4px 10px 10px;align-self:flex-end;border:0.5px solid var(--border)}
.bubble.thinking{opacity:0.6;font-style:italic}
.edna-footer{border-top:0.5px solid var(--border);padding:12px;flex-shrink:0}
.listening-bar{
  display:none;align-items:center;gap:8px;
  background:var(--red-light);border:0.5px solid var(--red-border);
  border-radius:var(--radius-sm);padding:7px 12px;
  font-size:12px;color:var(--red);margin-bottom:8px;
}
.listening-bar.active{display:flex}
.pulse-dot{
  width:8px;height:8px;border-radius:50%;background:var(--red);
  animation:pulseDot 1.2s ease-in-out infinite;flex-shrink:0;
}
@keyframes pulseDot{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:0.6}}
.edna-input-row{display:flex;gap:8px;align-items:flex-end}
.edna-textarea{
  flex:1;font-family:var(--sans);font-size:13px;padding:8px 11px;
  border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);
  background:var(--bg-card);color:var(--text);resize:none;
  line-height:1.5;min-height:38px;max-height:100px;outline:none;
  transition:border-color 0.15s;
}
.edna-textarea:focus{border-color:var(--blue-mid)}
.mic-btn{
  width:38px;height:38px;border-radius:var(--radius-sm);border:0.5px solid var(--border-mid);
  background:var(--bg-card);cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background 0.12s,border-color 0.12s;flex-shrink:0;
}
.mic-btn:hover{background:var(--bg-surface)}
.mic-btn.listening{background:var(--red-light);border-color:var(--red)}
.mic-btn svg{width:15px;height:15px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.mic-btn.listening svg{stroke:var(--red)}
.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.chip{
  font-size:11px;padding:4px 10px;border-radius:20px;
  background:var(--bg-surface);border:0.5px solid var(--border);
  color:var(--text-mid);cursor:pointer;transition:all 0.12s;white-space:nowrap;
}
.chip:hover{background:var(--blue-light);border-color:var(--blue-border);color:var(--blue-mid)}

/* ══ AGED ALERTS ══ */
.aged-item{
  display:flex;justify-content:space-between;align-items:center;
  padding:10px 12px;border-radius:var(--radius-sm);
  background:var(--amber-light);border:0.5px solid var(--amber-border);
  margin-bottom:6px;
}
.aged-item:last-of-type{margin-bottom:0}
.aged-qnum{font-family:var(--mono);font-size:11px;color:#633806;font-weight:500}
.aged-customer{font-size:12px;color:#633806;margin-top:2px}
.aged-days{font-weight:600;color:#854F0B;font-family:var(--mono);font-size:13px}

/* ══ AUTO TAG ══ */
.auto-tag{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);font-style:italic;margin-top:12px}
.auto-dot{width:7px;height:7px;border-radius:50%;background:#639922;flex-shrink:0}

/* ══ RESPONSIVE ══ */
@media(max-width:900px){
  .metrics-row{grid-template-columns:repeat(2,1fr)}
  .content-inner{grid-template-columns:1fr}
  .edna-pane{position:static;height:auto}
}
@media(max-width:480px){
  .metric-value{font-size:22px}
  .content{padding:16px}
}
</style>
<?= $extraCss ?? '' ?>
</head>
<body>
<div class="shell">

<!-- ══ SIDE NAV ══ -->
<nav class="sidenav" id="sidenav">
  <div class="nav-header">
    <a class="nav-logo" href="/index.php">S</a>
    <a class="nav-brand" href="/index.php">
      <div class="nav-brand-name">SCP Central</div>
      <div class="nav-brand-sub">Print Management</div>
    </a>
    <button class="nav-collapse-btn" id="collapse-btn" onclick="toggleNav()" title="Collapse nav">
      <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <path id="collapse-icon" d="M10 3L5 8l5 5"/>
      </svg>
    </button>
  </div>

  <div class="nav-section-label">Main</div>

  <a class="nav-item<?= $_activePage === 'dashboard'  ? ' active' : '' ?>" href="/index.php">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
    <span class="nav-item-label">Dashboard</span>
    <span class="nav-tooltip">Dashboard</span>
  </a>

  <a class="nav-item<?= $_activePage === 'estimating' ? ' active' : '' ?>" href="/modules/forms-estimating/">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/><path d="M9 15h3l8.5-8.5a1.5 1.5 0 00-3-3L9 12v3z"/></svg></span>
    <span class="nav-item-label">Estimating</span>
    <?php if (!empty($_navBadges['estimating'])): ?>
    <span class="nav-badge"><?= (int)$_navBadges['estimating'] ?></span>
    <?php endif; ?>
    <span class="nav-tooltip">Estimating</span>
  </a>

  <a class="nav-item<?= $_activePage === 'customers'  ? ' active' : '' ?>" href="#">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>
    <span class="nav-item-label">Customers</span>
    <span class="nav-tooltip">Customers</span>
  </a>

  <div class="nav-section-label">Production</div>

  <a class="nav-item<?= $_activePage === 'jobs'       ? ' active' : '' ?>" href="#">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg></span>
    <span class="nav-item-label">Jobs</span>
    <span class="nav-tooltip">Jobs</span>
  </a>

  <a class="nav-item<?= $_activePage === 'reports'    ? ' active' : '' ?>" href="#">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
    <span class="nav-item-label">Reports</span>
    <span class="nav-tooltip">Reports</span>
  </a>

  <div class="nav-section-label">System</div>

  <a class="nav-item<?= $_activePage === 'admin'      ? ' active' : '' ?>" href="#">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg></span>
    <span class="nav-item-label">Admin</span>
    <span class="nav-tooltip">Admin</span>
  </a>

  <div class="nav-footer">
    <div class="nav-avatar"><?= htmlspecialchars($_navInitials) ?></div>
    <div class="nav-user-info">
      <div class="nav-user-name"><?= htmlspecialchars($_navName) ?></div>
      <div class="nav-user-role"><?= htmlspecialchars($_navRole) ?></div>
    </div>
  </div>
</nav>

<div class="nav-overlay" id="nav-overlay" onclick="closeMobileNav()"></div>

<!-- ══ MAIN ══ -->
<div class="main" id="main">
