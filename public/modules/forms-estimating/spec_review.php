<?php
/**
 * spec_review.php — SCP Central Spec Review
 * Place at: /modules/forms-estimating/spec_review.php
 */

// ── AUTH STUB ────────────────────────────────────────────────────────────────
// TODO: Replace with require_once '../includes/auth.php';
$user = [
    'name'     => 'Sarah M.',
    'initials' => 'SM',
    'role'     => 'CSR',
    'role_key' => 'csr',
];
// ── END AUTH STUB ────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCP Central — Spec Review</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
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
.shell{display:flex;min-height:100vh}

/* ══ NAV (same as shell) ══ */
.sidenav{width:var(--nav-width);background:var(--nav-bg);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:200;transition:width 0.25s ease;overflow:hidden;border-right:0.5px solid var(--border)}
.sidenav.collapsed{width:var(--nav-collapsed)}
.nav-header{display:flex;align-items:center;gap:10px;padding:0 14px 0 16px;height:var(--topbar);border-bottom:0.5px solid var(--border);flex-shrink:0;overflow:hidden}
.nav-logo{width:32px;height:32px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:14px;color:#fff;font-family:var(--sans)}
.nav-brand{white-space:nowrap;overflow:hidden;flex:1}
.nav-brand-name{font-size:13px;font-weight:600;color:var(--text);line-height:1.2}
.nav-brand-sub{font-size:10px;color:var(--text-muted);letter-spacing:0.06em;text-transform:uppercase}
.nav-collapse-btn{width:28px;height:28px;border-radius:6px;border:none;background:var(--bg-surface);color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.15s,color 0.15s}
.nav-collapse-btn:hover{background:var(--border);color:var(--text)}
.nav-section-label{font-size:10px;font-weight:500;color:#999994;text-transform:uppercase;letter-spacing:0.09em;padding:20px 18px 5px;white-space:nowrap;overflow:hidden;transition:opacity 0.2s}
.sidenav.collapsed .nav-section-label{opacity:0;pointer-events:none}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 18px;cursor:pointer;color:#444440;font-size:13px;font-weight:400;border-left:2px solid transparent;transition:all 0.15s;white-space:nowrap;overflow:hidden;text-decoration:none;position:relative}
.nav-item:hover{color:var(--text);background:var(--bg-surface)}
.nav-item.active{color:var(--red);background:#FEF0EF;border-left-color:var(--red)}
.nav-item-icon{width:18px;height:18px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.nav-item-icon svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.nav-item-label{white-space:nowrap;overflow:hidden;flex:1}
.nav-badge{flex-shrink:0;background:var(--red);color:#fff;font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;font-family:var(--mono)}
.sidenav.collapsed .nav-item-label,.sidenav.collapsed .nav-badge,.sidenav.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.nav-tooltip{position:fixed;left:calc(var(--nav-collapsed) + 10px);background:#2a2a2c;color:rgba(255,255,255,0.9);font-size:12px;padding:5px 11px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity 0.15s;z-index:500;border:0.5px solid rgba(255,255,255,0.1);font-family:var(--sans)}
.sidenav.collapsed .nav-item:hover .nav-tooltip{opacity:1}
.nav-footer{margin-top:auto;border-top:0.5px solid var(--border);padding:12px 14px;display:flex;align-items:center;gap:10px;overflow:hidden;flex-shrink:0;cursor:pointer;transition:background 0.15s}
.nav-footer:hover{background:var(--bg-surface)}
.nav-avatar{width:32px;height:32px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;font-family:var(--sans)}
.nav-user-info{overflow:hidden;flex:1;transition:opacity 0.2s}
.nav-user-name{font-size:13px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nav-user-role{font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em}
.sidenav.collapsed .nav-user-info{opacity:0;width:0}

/* ══ MAIN ══ */
.main{margin-left:var(--nav-width);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left 0.25s ease}
.main.nav-collapsed{margin-left:var(--nav-collapsed)}
.topbar{height:var(--topbar);background:var(--nav-bg);border-bottom:0.5px solid var(--border);display:flex;align-items:center;padding:0 24px;position:sticky;top:0;z-index:100;gap:12px}
.topbar-left{display:flex;align-items:center;gap:10px;flex:1}
.breadcrumb{font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px}
.breadcrumb a{color:var(--blue-mid);cursor:pointer;text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb-sep{color:var(--border-mid)}
.breadcrumb-current{color:var(--text);font-weight:500}
.topbar-actions{display:flex;align-items:center;gap:8px}
.topbar-user{display:flex;align-items:center;gap:8px;cursor:pointer;padding:5px 10px;border-radius:var(--radius-sm);border:0.5px solid var(--border);transition:background 0.12s;position:relative;background:var(--bg-card)}
.topbar-user:hover{background:var(--bg-surface)}
.topbar-avatar{width:28px;height:28px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff}
.topbar-user-name{font-size:13px;font-weight:500;color:var(--text)}
.mobile-menu-btn{display:none;width:36px;height:36px;border-radius:var(--radius-sm);border:0.5px solid var(--border);background:var(--bg-card);cursor:pointer;align-items:center;justify-content:center}
.mobile-menu-btn svg{width:18px;height:18px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.nav-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:190}
.nav-overlay.visible{display:block}

/* ══ CONTENT ══ */
.content{padding:24px;flex:1}
.content-inner{max-width:1440px;margin:0 auto;display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}

/* ══ PAGE HEADER ══ */
.page-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px}
.page-title{font-size:20px;font-weight:600;color:var(--text);letter-spacing:-0.01em}
.page-sub{font-size:13px;color:var(--text-muted);margin-top:2px}
.hdr-actions{display:flex;gap:8px;align-items:center;flex-shrink:0}

/* ══ STATUS PILL ══ */
.status-pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;padding:4px 11px;border-radius:20px;font-family:var(--mono)}
.status-parsing{background:var(--amber-light);color:#633806;border:0.5px solid var(--amber-border)}
.status-ready{background:var(--green-light);color:#27500A;border:0.5px solid var(--green-border)}
.status-missing{background:var(--red-light);color:#791F1F;border:0.5px solid var(--red-border)}
.status-dot{width:6px;height:6px;border-radius:50%;background:currentColor;animation:blink 1s ease-in-out infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0.3}}

/* ══ CARDS ══ */
.card{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:20px 22px;margin-bottom:16px}
.card:last-child{margin-bottom:0}
.card-title{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:16px}

/* ══ FORM ══ */
.form-row{display:flex;gap:12px;margin-bottom:12px}
.form-row:last-child{margin-bottom:0}
.form-group{display:flex;flex-direction:column;gap:5px;flex:1}
.form-group.w2{flex:2}
.form-group.w3{flex:3}
.fl{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;display:flex;align-items:center;gap:5px}
.fi{font-family:var(--sans);font-size:13px;padding:8px 11px;border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);background:var(--bg-card);color:var(--text);width:100%;transition:border-color 0.15s}
.fi:focus{outline:none;border-color:var(--blue-mid)}
select.fi{cursor:pointer}
textarea.fi{resize:vertical;line-height:1.5}

/* ── confidence states ── */
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

/* ══ BUTTONS ══ */
.btn{font-family:var(--sans);font-size:13px;padding:8px 16px;border-radius:var(--radius-sm);border:0.5px solid var(--border-mid);background:var(--bg-card);color:var(--text);cursor:pointer;transition:background 0.12s,border-color 0.12s;white-space:nowrap}
.btn:hover{background:var(--bg-surface);border-color:#aaa}
.btn:active{transform:scale(0.99)}
.btn-primary{background:var(--blue);border-color:var(--blue);color:#fff}
.btn-primary:hover{background:#0C447C;border-color:#0C447C}
.btn-green{background:var(--green);border-color:var(--green);color:#fff}
.btn-green:hover{background:#27500A}
.btn-sm{font-size:12px;padding:5px 12px}
.btn-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}

/* ══ EDNA CONFIDENCE PANEL ══ */
.edna-panel{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:16px;position:sticky;top:calc(var(--topbar) + 24px)}
.edna-panel-header{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.edna-avatar-sm{width:38px;height:38px;border-radius:50%;background:var(--blue-light);border:2px solid var(--blue-mid);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.edna-panel-name{font-size:14px;font-weight:600;color:var(--text)}
.edna-panel-sub{font-size:11px;color:var(--text-muted)}

/* confidence summary */
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

/* field rows */
.cf-row{display:flex;align-items:center;gap:6px;font-size:12px;padding:4px 8px;border-radius:6px;margin-bottom:3px}
.cf-row-green{background:var(--green-light)}
.cf-row-amber{background:var(--amber-light)}
.cf-row-red{background:var(--red-light)}
.cf-row-grey{background:var(--bg-surface)}
.cf-field{flex:1;color:var(--text-muted)}
.cf-val{font-weight:600;color:var(--text);font-family:var(--mono);font-size:11px}

/* edna notes */
.edna-notes{border-top:0.5px solid var(--border);padding-top:12px;margin-top:4px}
.edna-notes-label{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
.edna-notes-text{font-size:12px;color:#0C447C;line-height:1.6}

/* ══ PARSING OVERLAY ══ */
.parsing-overlay{
  position:fixed;inset:0;background:rgba(250,248,245,0.92);
  display:flex;align-items:center;justify-content:center;
  z-index:400;flex-direction:column;gap:16px;
}
.parsing-overlay.hidden{display:none}
.parsing-spinner{
  width:48px;height:48px;border:3px solid var(--blue-border);
  border-top-color:var(--blue-mid);border-radius:50%;
  animation:spin 0.8s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}
.parsing-label{font-size:15px;font-weight:500;color:var(--text)}
.parsing-sub{font-size:13px;color:var(--text-muted)}

/* ══ AUTO TAG ══ */
.auto-tag{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);font-style:italic;margin-top:10px}
.auto-dot{width:7px;height:7px;border-radius:50%;background:#639922;flex-shrink:0}

/* ══ RESPONSIVE ══ */
@media(max-width:1000px){
  .content-inner{grid-template-columns:1fr}
  .edna-panel{position:static}
  .press-grid{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:768px){
  .sidenav{transform:translateX(-100%);transition:transform 0.25s ease;width:var(--nav-width)!important}
  .sidenav.mobile-open{transform:translateX(0)}
  .main{margin-left:0!important}
  .mobile-menu-btn{display:flex}
  .content{padding:16px}
  .press-grid{grid-template-columns:repeat(3,1fr)}
  .fin-grid{grid-template-columns:1fr}
  .form-row{flex-direction:column}
}
</style>
</head>
<body>
<div class="shell">

<!-- ══ SIDE NAV ══ -->
<nav class="sidenav" id="sidenav">
  <div class="nav-header">
    <div class="nav-logo">S</div>
    <div class="nav-brand">
      <div class="nav-brand-name">SCP Central</div>
      <div class="nav-brand-sub">Print Management</div>
    </div>
    <button class="nav-collapse-btn" id="collapse-btn" onclick="toggleNav()" title="Collapse">
      <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10 3L5 8l5 5"/></svg>
    </button>
  </div>
  <div class="nav-section-label">Main</div>
  <a class="nav-item" href="/dashboard.php">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
    <span class="nav-item-label">Dashboard</span>
    <span class="nav-tooltip">Dashboard</span>
  </a>
  <a class="nav-item active" href="/modules/forms-estimating/">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/><path d="M9 15h3l8.5-8.5a1.5 1.5 0 00-3-3L9 12v3z"/></svg></span>
    <span class="nav-item-label">Estimating</span>
    <span class="nav-badge">6</span>
    <span class="nav-tooltip">Estimating</span>
  </a>
  <a class="nav-item">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>
    <span class="nav-item-label">Customers</span>
    <span class="nav-tooltip">Customers</span>
  </a>
  <div class="nav-section-label">Production</div>
  <a class="nav-item">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg></span>
    <span class="nav-item-label">Jobs</span>
    <span class="nav-tooltip">Jobs</span>
  </a>
  <a class="nav-item">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
    <span class="nav-item-label">Reports</span>
    <span class="nav-tooltip">Reports</span>
  </a>
  <div class="nav-section-label">System</div>
  <a class="nav-item">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg></span>
    <span class="nav-item-label">Admin</span>
    <span class="nav-tooltip">Admin</span>
  </a>
  <div class="nav-footer">
    <div class="nav-avatar"><?= htmlspecialchars($user['initials']) ?></div>
    <div class="nav-user-info">
      <div class="nav-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="nav-user-role"><?= htmlspecialchars($user['role']) ?></div>
    </div>
  </div>
</nav>

<div class="nav-overlay" id="nav-overlay" onclick="closeMobileNav()"></div>

<!-- ══ MAIN ══ -->
<div class="main" id="main">
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
      <div class="topbar-user">
        <div class="topbar-avatar"><?= htmlspecialchars($user['initials']) ?></div>
        <span class="topbar-user-name"><?= htmlspecialchars($user['name']) ?></span>
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
            <div class="cf-box cf-box-green">
              <div class="cf-box-num" id="cf-confirmed">—</div>
              <div class="cf-box-label">Confirmed</div>
            </div>
            <div class="cf-box cf-box-amber">
              <div class="cf-box-num" id="cf-suggested">—</div>
              <div class="cf-box-label">Suggested</div>
            </div>
            <div class="cf-box cf-box-red">
              <div class="cf-box-num" id="cf-missing">—</div>
              <div class="cf-box-label">Missing</div>
            </div>
          </div>

          <div id="cf-rows" style="margin-bottom:14px">
            <!-- populated by JS -->
          </div>

          <div class="edna-notes">
            <div class="edna-notes-label">Edna's notes</div>
            <div class="edna-notes-text" id="edna-notes">Paste a job description on the previous screen and hit "Parse with AI" — I'll fill in everything I can and flag what I need from you.</div>
          </div>
        </div>

        <button class="btn btn-primary" style="width:100%;display:flex;justify-content:center;padding:11px" onclick="runEstimate()">
          Run estimate →
        </button>
        <div style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:8px">Suggested fields will be confirmed</div>
      </div>

    </div>
  </div>
</div>
</div>

<script>
/* ── NAV ── */
let navCollapsed = false;
let mobileNavOpen = false;
function toggleNav() {
  navCollapsed = !navCollapsed;
  document.getElementById('sidenav').classList.toggle('collapsed', navCollapsed);
  document.getElementById('main').classList.toggle('nav-collapsed', navCollapsed);
  const path = document.querySelector('#collapse-btn svg path');
  path.setAttribute('d', navCollapsed ? 'M6 3l5 5-5 5' : 'M10 3L5 8l5 5');
}
function toggleMobileNav() {
  mobileNavOpen = !mobileNavOpen;
  document.getElementById('sidenav').classList.toggle('mobile-open', mobileNavOpen);
  document.getElementById('nav-overlay').classList.toggle('visible', mobileNavOpen);
}
function closeMobileNav() {
  mobileNavOpen = false;
  document.getElementById('sidenav').classList.remove('mobile-open');
  document.getElementById('nav-overlay').classList.remove('visible');
}

/* ── DATE ── */
document.getElementById('f-date').value = new Date().toISOString().split('T')[0];

/* ── PRESS ── */
const pressReasons = {
  1:"Press 1 (MVP Memjet) — rarely used for forms work. Only if specifically requested.",
  2:"Press 2 (Didde 17\") — good for 1–2 colour narrow web. Under-powered for wide jobs.",
  3:"Press 3 (Didde 22\" · 5 colour) — best fit for most snap set and continuous work.",
  4:"Press 4 (MVP 14\" cutoff) — short run specialist. Cost-effective at lower quantities.",
  5:"Press 5 (Didde 17\") — backup to Press 2. Narrow web, 1–2 colour.",
  11:"Press 11 (Didde 22\" · 8 colour) — overkill for 1-colour work. Reserve for full colour."
};
function selectPress(num, el) {
  document.querySelectorAll('.press-card').forEach(c => c.classList.remove('press-selected'));
  el.classList.add('press-selected');
  const r = document.getElementById('press-reason');
  r.style.display = 'block';
  r.textContent = pressReasons[num] || '';
}
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
  setDot('dot-jobtype','confirmed');
}

/* ── FINISHING ── */
function toggleFin(cb) {
  const item = cb.closest('.fin-item');
  item.classList.toggle('fin-checked', cb.checked);
  item.classList.remove('fin-suggested');
}

/* ── BREAKS ── */
function addBreak() {
  const qty = prompt('Enter quantity (e.g. 10000):');
  if (!qty || isNaN(qty.replace(/,/g,''))) return;
  const n = parseInt(qty.replace(/,/g,''));
  insertBreakTag(n.toLocaleString(), false);
}
function insertBreakTag(label, edna=false) {
  const tags = document.getElementById('break-tags');
  const addBtn = tags.querySelector('.break-add');
  const tag = document.createElement('span');
  tag.className = 'break-tag' + (edna ? ' edna-tag' : '');
  tag.innerHTML = label + ' <span class="break-tag-remove" onclick="this.parentElement.remove()">×</span>';
  tags.insertBefore(tag, addBtn);
}

/* ── CONFIDENCE DOTS ── */
function setDot(id, state) {
  const dot = document.getElementById(id);
  if (!dot) return;
  dot.className = 'cf-dot';
  if (state === 'confirmed') dot.classList.add('cf-green');
  else if (state === 'suggested') dot.classList.add('cf-amber');
  else if (state === 'missing') dot.classList.add('cf-red');
  else dot.classList.add('cf-grey');
}

function setField(id, value, state) {
  const el = document.getElementById(id);
  if (!el) return;
  el.value = value || '';
  el.className = 'fi';
  if (state === 'confirmed') el.classList.add('fi-confirmed');
  else if (state === 'suggested') el.classList.add('fi-suggested');
  else if (state === 'missing') el.classList.add('fi-missing');
}

/* ── POPULATE FORM FROM AI RESPONSE ── */
function populateForm(spec) {
  // Customer
  if (spec.customer) { setField('f-customer', spec.customer, 'confirmed'); setDot('dot-customer','confirmed'); }
  else { setDot('dot-customer','missing'); }

  // Job name
  if (spec.job_name) { setField('f-jobname', spec.job_name, 'confirmed'); setDot('dot-jobname','confirmed'); }

  // Job type
  if (spec.job_type) {
    const btns = document.querySelectorAll('.jt-btn');
    btns.forEach(b => {
      b.classList.remove('jt-selected');
      if (b.textContent.toLowerCase().replace(' ','') === spec.job_type.toLowerCase().replace(' ','').replace('_','')) {
        b.classList.add('jt-selected');
      }
    });
    setDot('dot-jobtype', spec.job_type_confidence || 'confirmed');
  }

  // Dimensions
  if (spec.width) { setField('f-width', spec.width, spec.width_confidence || 'confirmed'); setDot('dot-width', spec.width_confidence || 'confirmed'); }
  else setDot('dot-width','missing');
  if (spec.depth) { setField('f-depth', spec.depth, spec.depth_confidence || 'confirmed'); setDot('dot-depth', spec.depth_confidence || 'confirmed'); }
  else setDot('dot-depth','missing');
  if (spec.parts) { setField('f-parts', spec.parts, spec.parts_confidence || 'suggested'); setDot('dot-parts', spec.parts_confidence || 'suggested'); }
  else setDot('dot-parts','missing');

  // NCR type
  if (spec.ncr_type) {
    const sel = document.getElementById('f-ncrtype');
    for (let o of sel.options) {
      if (o.text.toLowerCase().includes(spec.ncr_type.toLowerCase()) || o.text.includes(spec.ncr_type)) {
        sel.value = o.value; break;
      }
    }
    setDot('dot-ncrtype', spec.ncr_type_confidence || 'suggested');
  }

  // Stock
  if (spec.stock) { setField('f-stock', spec.stock, spec.stock_confidence || 'suggested'); setDot('dot-stock', spec.stock_confidence || 'suggested'); }
  else setDot('dot-stock','missing');

  // Ink
  if (spec.ink_front) { setField('f-inkfront', spec.ink_front, spec.ink_front_confidence || 'confirmed'); setDot('dot-inkfront', spec.ink_front_confidence || 'confirmed'); }
  if (spec.ink_back !== undefined) { setField('f-inkback', spec.ink_back, 'confirmed'); setDot('dot-inkback','confirmed'); }

  // Press
  if (spec.press) {
    const cards = document.querySelectorAll('.press-card');
    cards.forEach(c => {
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

  // Finishing
  const finMap = {
    perforation: 'fin-perf', padding: 'fin-pad',
    collating: 'fin-collate', numbering: 'fin-number',
    drilling: 'fin-drill', shrink_wrap: 'fin-shrink'
  };
  if (spec.finishing) {
    spec.finishing.forEach(f => {
      const el = document.getElementById(finMap[f.name]);
      if (!el) return;
      const cb = el.querySelector('input');
      cb.checked = f.include;
      el.classList.toggle('fin-checked', f.include);
      if (f.confidence === 'suggested') el.classList.add('fin-suggested');
      if (f.detail) el.querySelector('.fin-detail').textContent = f.detail;
      if (f.include) {
        const badge = document.createElement('span');
        badge.className = 'fin-badge ' + (f.confidence === 'suggested' ? 'fin-badge-suggested' : 'fin-badge-confirmed');
        badge.textContent = f.confidence === 'suggested' ? 'Edna added' : 'Confirmed';
        el.appendChild(badge);
      }
    });
  }

  // Qty breaks
  if (spec.qty_breaks && spec.qty_breaks.length) {
    spec.qty_breaks.forEach(q => insertBreakTag(parseInt(q).toLocaleString(), true));
  }

  // Confidence panel
  updateConfidencePanel(spec);
}

function updateConfidencePanel(spec) {
  const confirmed = spec.confidence_counts?.confirmed || 0;
  const suggested = spec.confidence_counts?.suggested || 0;
  const missing = spec.confidence_counts?.missing || 0;

  document.getElementById('cf-confirmed').textContent = confirmed;
  document.getElementById('cf-suggested').textContent = suggested;
  document.getElementById('cf-missing').textContent = missing;
  document.getElementById('edna-panel-sub').textContent = 'Parsed from your input';

  if (missing > 0) {
    document.getElementById('missing-pill').style.display = 'inline-flex';
    document.getElementById('missing-pill').textContent = missing + ' field' + (missing > 1 ? 's' : '') + ' need attention';
  }

  // Build cf-rows
  const rows = spec.confidence_rows || [];
  const rowsEl = document.getElementById('cf-rows');
  rowsEl.innerHTML = rows.map(r => `
    <div class="cf-row cf-row-${r.state}">
      <span class="cf-dot cf-${r.state === 'confirmed' ? 'green' : r.state === 'suggested' ? 'amber' : 'red'}"></span>
      <span class="cf-field">${r.label}</span>
      <span class="cf-val">${r.value}</span>
    </div>`).join('');

  document.getElementById('edna-notes').textContent = spec.edna_notes || '';
}

/* ── REAL AI PARSE ── */
let lastPromptVersionIds = null; // stored for save_quote.php

async function parseWithAI(customer, description) {
  const overlay     = document.getElementById('parsing-overlay');
  const parseStatus = document.getElementById('parse-status');
  overlay.classList.remove('hidden');
  parseStatus.style.display = 'inline-flex';

  const subMsgs = [
    'Reading your job description...',
    'Checking customer history...',
    'Selecting best press...',
    'Building confidence model...'
  ];
  let i = 0;
  const subEl  = document.getElementById('parsing-sub');
  const ticker = setInterval(() => { subEl.textContent = subMsgs[i++ % subMsgs.length]; }, 1200);

  const userMsg = `Customer: ${customer || 'not specified'}\nJob description: ${description}`;

  // Map picker value to DB job_type key — null on first parse, Edna will detect it
  const mappedJobType = currentJobType ? jobTypeMap[currentJobType] : null;

  try {
    const payload = {
      module:   'forms_estimating',
      messages: [{ role: 'user', content: userMsg }]
    };
    if (mappedJobType) payload.job_type = mappedJobType;

    const response = await fetch('/api/edna.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });

    clearInterval(ticker);
    overlay.classList.add('hidden');
    parseStatus.style.display = 'none';

    if (!response.ok) throw new Error('API error ' + response.status);
    const data = await response.json();

    // Store prompt version IDs for save_quote.php
    lastPromptVersionIds = data.prompt_version_ids || null;

    const text  = data.content?.[0]?.text || '';
    const clean = text.replace(/```json|```/g, '').trim();
    const spec  = JSON.parse(clean);
    populateForm(spec);

    if (spec.customer) {
      document.getElementById('page-title').textContent = 'Spec review — ' + spec.customer;
    }

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


/* ── DEMO SPEC (fallback / direct navigation) ── */
function loadDemoSpec() {
  populateForm({
    customer: "BCAA",
    job_name: "NCR snap sets 3-part, 8.5×11, black ink, top perf, padded 50s",
    job_type: "snapset",
    job_type_confidence: "confirmed",
    width: "8.5", width_confidence: "confirmed",
    depth: "11", depth_confidence: "confirmed",
    parts: "3", parts_confidence: "confirmed",
    ncr_type: "CB / CFB / CF (3-part)", ncr_type_confidence: "confirmed",
    stock: "15lb NCR carbonless", stock_confidence: "suggested",
    ink_front: "1 colour — black", ink_front_confidence: "confirmed",
    ink_back: "0 (blank)",
    press: "3",
    press_reason: "Press 3 (Didde 22\" · 5 colour) — best fit for this snap set job. Right width, 1-colour capacity, primary forms press.",
    finishing: [
      {name:"perforation", include:true, confidence:"confirmed", detail:"Top — from spec"},
      {name:"padding", include:true, confidence:"confirmed", detail:"Sets of 50 — from spec"},
      {name:"collating", include:true, confidence:"suggested", detail:"Required for multi-part NCR"},
      {name:"numbering", include:false, confidence:"confirmed", detail:"Sequential per set"},
      {name:"drilling", include:false, confidence:"confirmed", detail:"3-hole or custom"},
      {name:"shrink_wrap", include:false, confidence:"confirmed", detail:"Individual wrapping"},
    ],
    qty_breaks: ["5000","10000","25000"],
    confidence_counts: {confirmed:9, suggested:3, missing:0},
    confidence_rows: [
      {label:"Customer", value:"BCAA", state:"confirmed"},
      {label:"Job type", value:"Snap Set", state:"confirmed"},
      {label:"Size", value:"8.5 × 11\"", state:"confirmed"},
      {label:"Parts", value:"3", state:"confirmed"},
      {label:"NCR type", value:"CB/CFB/CF", state:"confirmed"},
      {label:"Stock", value:"15lb NCR", state:"suggested"},
      {label:"Ink", value:"1/0 black", state:"confirmed"},
      {label:"Perf", value:"Top", state:"confirmed"},
      {label:"Padding", value:"50s", state:"confirmed"},
      {label:"Press", value:"Press 3 ✦", state:"suggested"},
      {label:"Collating", value:"Auto-added", state:"suggested"},
      {label:"Qty breaks", value:"5K/10K/25K", state:"confirmed"},
    ],
    edna_notes: "Everything looks solid. I added collating — it's always required on multi-part NCR. Confirm the stock weight if you have a customer spec sheet."
  });
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
  // Check if we were passed a description via sessionStorage (from estimating page)
  const pending = sessionStorage.getItem('scp_parse_job');
  if (pending) {
    sessionStorage.removeItem('scp_parse_job');
    const { customer, description } = JSON.parse(pending);
    parseWithAI(customer, description);
  } else {
    // Load demo spec so the page isn't blank
    loadDemoSpec();
  }
});
</script>
</body>
</html>
