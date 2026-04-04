<?php
/**
 * estimating.php — SCP Central Estimating Module
 * Place at: /home/ssaiadmin/scp-stack/php/modules/estimating/index.php
 *
 * AUTH STUB: Replace this block with real session auth later.
 * For now it injects a test user so the page works immediately.
 */

// ── AUTH STUB ────────────────────────────────────────────────────────────────
// TODO: Replace with real session check e.g. require_once '../../includes/auth.php';
$user = [
    'name'     => 'Sarah M.',
    'initials' => 'SM',
    'role'     => 'CSR',
    'role_key' => 'csr',
];
// ── END AUTH STUB ────────────────────────────────────────────────────────────

// Generate a new quote number (stub — replace with DB sequence later)
$quote_num = 'Q' . str_pad(mt_rand(2848, 2999), 6, '0', STR_PAD_LEFT) . chr(rand(65,90));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCP Central — Estimating</title>
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
  font-family:var(--sans);
}
.nav-brand{white-space:nowrap;overflow:hidden;flex:1}
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
.nav-item-icon{
  width:18px;height:18px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.nav-item-icon svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.nav-item-label{white-space:nowrap;overflow:hidden;flex:1}
.nav-badge{
  flex-shrink:0;
  background:var(--red);color:#fff;font-size:10px;font-weight:600;
  padding:2px 7px;border-radius:20px;font-family:var(--mono);
}
.sidenav.collapsed .nav-item-label,
.sidenav.collapsed .nav-badge,
.sidenav.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}

.nav-tooltip{
  position:fixed;left:calc(var(--nav-collapsed) + 10px);
  background:#2a2a2c;color:rgba(255,255,255,0.9);font-size:12px;
  padding:5px 11px;border-radius:6px;white-space:nowrap;
  pointer-events:none;opacity:0;transition:opacity 0.15s;z-index:500;
  border:0.5px solid rgba(255,255,255,0.1);
  font-family:var(--sans);
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

/* ══ MAIN AREA ══ */
.main{
  margin-left:var(--nav-width);flex:1;display:flex;flex-direction:column;
  min-height:100vh;transition:margin-left 0.25s ease;
}
.main.nav-collapsed{margin-left:var(--nav-collapsed)}

/* ══ TOPBAR ══ */
.topbar{
  height:var(--topbar);background:#F5F3EF;
  border-bottom:0.5px solid var(--border);
  display:flex;align-items:center;padding:0 24px;
  position:sticky;top:0;z-index:100;gap:12px;
}
.topbar-page-title{font-size:15px;font-weight:600;color:var(--text);flex:1}
.topbar-search{
  display:flex;align-items:center;gap:8px;
  background:var(--bg-surface);border:0.5px solid var(--border);
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

/* ══ CONTENT ══ */
.content{padding:28px;flex:1}
.content-inner{
  max-width:1440px;margin:0 auto;
  display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;
}



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

/* ══ QUICK ACTIONS ══ */
.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.qa-tile{
  background:var(--bg-card);border:0.5px solid var(--border);
  border-radius:var(--radius);padding:14px 16px;cursor:pointer;
  transition:border-color 0.15s,background 0.15s,transform 0.15s;
  display:flex;flex-direction:column;gap:6px;
  justify-content:flex-start;
}
.qa-tile:hover{border-color:var(--blue-border);background:var(--blue-light);transform:translateY(-1px)}
.qa-tile.primary{background:var(--blue-light);border-color:var(--blue-border)}
.qa-tile.primary:hover{background:#cee5f7;border-color:var(--blue-mid)}
.qa-tile-header{display:flex;align-items:center;gap:9px;margin-bottom:2px}
.qa-icon{
  width:28px;height:28px;border-radius:6px;
  display:flex;align-items:center;justify-content:center;
  background:var(--bg-surface);flex-shrink:0;
}
.qa-tile.primary .qa-icon{background:rgba(255,255,255,0.65)}.qa-tile.primary .qa-icon svg{stroke:#0C447C}
.qa-icon svg{width:14px;height:14px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.qa-tile.primary .qa-icon svg{stroke:#0C447C}
.qa-title{font-size:13px;font-weight:600;color:var(--text)}
.qa-tile.primary .qa-title{color:#0C447C}
.qa-sub{font-size:11px;color:var(--text-muted);line-height:1.45}
.qa-tile.primary .qa-sub{color:#185FA5}

/* ══ CARDS ══ */
.card{background:var(--bg-card);border:0.5px solid var(--border);border-radius:var(--radius);padding:20px 22px;margin-bottom:16px}
.card:last-child{margin-bottom:0}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.card-title{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em}
.card-link{font-size:12px;color:var(--blue-mid);cursor:pointer;text-decoration:none;transition:color 0.12s}
.card-link:hover{color:var(--blue);text-decoration:underline}

/* ══ TABLE ══ */
.tbl{width:100%;border-collapse:collapse}
.tbl th{
  text-align:left;font-size:10px;font-weight:600;color:var(--text-muted);
  padding:8px 10px;border-bottom:0.5px solid var(--border);
  text-transform:uppercase;letter-spacing:0.07em;
}
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

/* ══ RECENT WINS ══ */
.win-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:10px 0;border-bottom:0.5px solid var(--border);
}
.win-item:last-child{border-bottom:none}
.win-qnum{font-family:var(--mono);font-size:11px;color:var(--blue-mid)}
.win-desc{font-size:12px;color:var(--text-muted);margin-top:2px}
.win-right{text-align:right}
.win-val{font-family:var(--mono);font-size:13px;font-weight:500;color:var(--text)}
.win-avanti{font-family:var(--mono);font-size:10px;color:var(--text-muted);margin-top:2px}

/* ══ AUTO TAG ══ */
.auto-tag{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);font-style:italic;margin-top:12px}
.auto-dot{width:7px;height:7px;border-radius:50%;background:#639922;flex-shrink:0}

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

.edna-chat{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth}
.edna-chat::-webkit-scrollbar{width:4px}
.edna-chat::-webkit-scrollbar-track{background:transparent}
.edna-chat::-webkit-scrollbar-thumb{background:var(--border-mid);border-radius:2px}

.bubble{max-width:90%;padding:9px 13px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word}
.bubble.edna{background:var(--blue-light);color:#0C447C;border-bottom-left-radius:4px;align-self:flex-start;border:0.5px solid var(--blue-border)}
.bubble.user{background:var(--bg-surface);color:var(--text);border-bottom-right-radius:4px;align-self:flex-end;border:0.5px solid var(--border)}
.bubble.thinking{font-style:italic;opacity:0.7}

.edna-footer{padding:12px 14px;border-top:0.5px solid var(--border);flex-shrink:0}
.listening-bar{display:none;align-items:center;gap:6px;font-size:11px;color:#A32D2D;margin-bottom:7px}
.listening-bar.active{display:flex}
.pulse-dot{width:7px;height:7px;border-radius:50%;background:#E24B4A;animation:pulse 1s ease-in-out infinite}
.edna-input-row{display:flex;gap:8px;align-items:flex-end}
.edna-textarea{
  flex:1;font-family:var(--sans);font-size:13px;padding:9px 12px;
  border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);
  background:var(--bg-card);color:var(--text);resize:none;height:40px;line-height:1.4;
  transition:border-color 0.15s;
}
.edna-textarea:focus{outline:none;border-color:var(--blue-mid)}
.mic-btn{
  width:36px;height:36px;border-radius:50%;border:0.5px solid var(--border-mid);
  background:var(--bg-card);cursor:pointer;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:all 0.15s;
}
.mic-btn:hover{background:var(--bg-surface);border-color:var(--blue-mid)}
.mic-btn.listening{background:var(--red-light);border-color:#E24B4A}
.mic-btn svg{width:15px;height:15px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.mic-btn.listening svg{stroke:#A32D2D}
.chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:9px}
.chip{
  font-size:11px;padding:4px 11px;border-radius:20px;
  border:0.5px solid var(--border);background:var(--bg-card);
  color:var(--text-muted);cursor:pointer;transition:all 0.12s;white-space:nowrap;
}
.chip:hover{border-color:var(--blue-mid);color:var(--blue-mid);background:var(--blue-light)}

/* ══ MOBILE OVERLAY ══ */
.nav-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,0.5);z-index:190;
}
.nav-overlay.visible{display:block}
.mobile-menu-btn{
  display:none;width:36px;height:36px;border-radius:var(--radius-sm);
  border:0.5px solid var(--border);background:var(--bg-card);
  cursor:pointer;align-items:center;justify-content:center;
}
.mobile-menu-btn svg{width:18px;height:18px;stroke:var(--text-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}

/* ══ ANIMATIONS ══ */
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.25}}
@keyframes statusPulse{0%,100%{opacity:1}50%{opacity:0.5}}
@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.bubble{animation:fadeIn 0.2s ease}

/* ══ RESPONSIVE ══ */
@media(max-width:1200px){
  .metrics-row{grid-template-columns:repeat(2,1fr)}
  .quick-actions{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:1000px){
  .content-inner{grid-template-columns:1fr;display:flex;flex-direction:column-reverse}
  .edna-pane{position:static;height:420px}
}
@media(max-width:768px){
  .sidenav{
    transform:translateX(-100%);
    transition:transform 0.25s ease;
    width:var(--nav-width) !important;
  }
  .sidenav.mobile-open{transform:translateX(0)}
  .nav-overlay.visible{display:block}
  .main{margin-left:0 !important}
  .mobile-menu-btn{display:flex}
  .topbar-search{display:none}
  .content{padding:16px}
  .metrics-row{grid-template-columns:repeat(2,1fr)}
  .quick-actions{grid-template-columns:repeat(2,1fr)}
  .tbl th:nth-child(3),.tbl td:nth-child(3){display:none}
}
@media(max-width:480px){  .metric-value{font-size:26px}
  .quick-actions{grid-template-columns:1fr 1fr}
  .tbl th:nth-child(4),.tbl td:nth-child(4){display:none}
}


/* ══ BUTTONS ══ */
.btn{font-family:var(--sans);font-size:13px;padding:8px 16px;border-radius:var(--radius-sm);border:0.5px solid var(--border-mid);background:var(--bg-card);color:var(--text);cursor:pointer;transition:background 0.12s,border-color 0.12s;white-space:nowrap}
.btn:hover{background:var(--bg-surface);border-color:#aaa}
.btn-primary{background:var(--blue);border-color:var(--blue);color:#fff}
.btn-primary:hover{background:#0C447C;border-color:#0C447C}
.btn-sm{font-size:12px;padding:5px 12px}

/* ══ FIELD LABELS ══ */
.field-label{display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5px}
.fi{font-family:var(--sans);font-size:13px;padding:8px 11px;border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);background:var(--bg-card);color:var(--text);width:100%;transition:border-color 0.15s}
.fi:focus{outline:none;border-color:var(--blue-mid)}
textarea.fi{line-height:1.5}

/* ══ TILE SELECTED STATE ══ */
.qa-tile.selected{border-color:var(--blue-mid);background:var(--blue-light)}
.qa-tile.selected .qa-title{color:#0C447C}
.qa-tile.selected .qa-sub{color:var(--blue-mid)}
.qa-tile.selected .qa-icon svg{stroke:#0C447C}
.qa-tile.selected .qa-icon{background:rgba(255,255,255,0.7)}

/* ══ LOOKUP RESULT ══ */
.lookup-card{background:var(--blue-light);border:0.5px solid var(--blue-border);border-radius:var(--radius-sm);padding:12px 14px}
.lookup-card-title{font-size:13px;font-weight:600;color:#0C447C;margin-bottom:4px;display:flex;align-items:center;gap:7px}
.lookup-card-detail{font-size:12px;color:#185FA5;margin-bottom:8px;line-height:1.5}
.lookup-actions{display:flex;gap:7px;flex-wrap:wrap}
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
    <button class="nav-collapse-btn" id="collapse-btn" onclick="toggleNav()" title="Collapse menu">
      <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10 3L5 8l5 5"/></svg>
    </button>
  </div>

  <div class="nav-section-label">Main</div>

  <a class="nav-item">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    </span>
    <span class="nav-item-label">Dashboard</span>
    <span class="nav-tooltip">Dashboard</span>
  </a>

  <a class="nav-item active">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/><path d="M9 15h3l8.5-8.5a1.5 1.5 0 00-3-3L9 12v3z"/></svg>
    </span>
    <span class="nav-item-label">Estimating</span>
    <span class="nav-badge">6</span>
    <span class="nav-tooltip">Estimating</span>
  </a>

  <a class="nav-item">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
    </span>
    <span class="nav-item-label">Customers</span>
    <span class="nav-tooltip">Customers</span>
  </a>

  <div class="nav-section-label">Production</div>

  <a class="nav-item">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
    </span>
    <span class="nav-item-label">Jobs</span>
    <span class="nav-tooltip">Jobs</span>
  </a>

  <a class="nav-item">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </span>
    <span class="nav-item-label">Reports</span>
    <span class="nav-tooltip">Reports</span>
  </a>

  <div class="nav-section-label">System</div>

  <a class="nav-item">
    <span class="nav-item-icon">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg>
    </span>
    <span class="nav-item-label">Admin</span>
    <span class="nav-tooltip">Admin</span>
  </a>

  <div class="nav-footer">
    <div class="nav-avatar" id="nav-avatar"><?= htmlspecialchars($user['initials']) ?></div>
    <div class="nav-user-info">
      <div class="nav-user-name" id="nav-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="nav-user-role" id="nav-user-role"><?= htmlspecialchars($user['role']) ?></div>
    </div>
  </div>
</nav>

<!-- ══ MOBILE OVERLAY ══ -->
<div class="nav-overlay" id="nav-overlay" onclick="closeMobileNav()"></div>

<!-- ══ MAIN ══ -->
<div class="main" id="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="topbar-page-title">Estimating</div>

    <div class="topbar-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input placeholder="Search quotes, customers, jobs...">
    </div>

    <div class="topbar-actions">
      <button class="topbar-btn" title="Notifications">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </button>

      <div class="topbar-user" id="topbar-user" onclick="toggleUserMenu()">
        <div class="topbar-avatar" id="topbar-avatar"><?= htmlspecialchars($user['initials']) ?></div>
        <span class="topbar-user-name" id="topbar-name"><?= htmlspecialchars($user['name']) ?></span>
        <svg class="topbar-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="user-dropdown">
          <div class="dropdown-section-label">Switch role (demo)</div>
          <div class="dropdown-item" onclick="switchRole('csr')">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Sarah M. — CSR
          </div>
          <div class="dropdown-item" onclick="switchRole('manager')">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Cam M. — Manager
          </div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-item">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg>
            Preferences
          </div>
          <div class="dropdown-item danger">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <div class="content-inner">

      <!-- ══ LEFT COLUMN ══ -->
      <div>

        <!-- COMPACT METRICS -->
        <div class="metrics-row" style="margin-bottom:14px">
          <div class="metric-card accent-blue" style="padding:12px 16px">
            <div class="metric-label">Open quotes</div>
            <div class="metric-value" style="font-size:22px" id="m-open">6</div>
            <div class="metric-sub" id="m-open-sub">4 need action</div>
          </div>
          <div class="metric-card accent-green" style="padding:12px 16px">
            <div class="metric-label">Win rate</div>
            <div class="metric-value green" style="font-size:22px" id="m-winrate">74%</div>
            <div class="metric-sub">Last 90 days</div>
          </div>
          <div class="metric-card accent-amber" style="padding:12px 16px">
            <div class="metric-label">Aged quotes</div>
            <div class="metric-value amber" style="font-size:22px" id="m-aged">3</div>
            <div class="metric-sub">30+ days silent</div>
          </div>
          <div class="metric-card accent-red" style="padding:12px 16px">
            <div class="metric-label">Sent this week</div>
            <div class="metric-value" style="font-size:22px" id="m-sent">4</div>
            <div class="metric-sub" id="m-sent-sub">2 converted</div>
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
            <div style="display:flex;flex-direction:column;gap:10px">
              <div>
                <label class="field-label">Job description — paste a spec, forward an email, or just describe it</label>
                <textarea class="fi" id="nq-desc" rows="3" placeholder="e.g. 10,000 3-part NCR sets, 8.5×11, black ink, top perf, padded in 50s..." style="resize:vertical"></textarea>
              </div>
              <div>
                <button class="btn btn-primary" onclick="parseAndGo()">Parse with AI and open pricing →</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ACTIVE QUOTES -->
        <div class="card" style="margin-bottom:14px">
          <div class="card-header">
            <div class="card-title" id="quotes-title">My active quotes</div>
            <a class="card-link">View all →</a>
          </div>
          <table class="tbl">
            <thead>
              <tr>
                <th>Quote #</th>
                <th>Customer</th>
                <th>Description</th>
                <th>Breaks</th>
                <th>Mid value</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="mono">Q002847R</td>
                <td>BCAA</td>
                <td class="muted">NCR snap sets 3-part</td>
                <td class="muted" style="font-size:12px">5K / 10K / 25K</td>
                <td class="val">$4,200</td>
                <td><span class="badge badge-draft">Draft</span></td>
              </tr>
              <tr>
                <td class="mono">Q002846W</td>
                <td>BC Hydro</td>
                <td class="muted">Continuous forms #10</td>
                <td class="muted" style="font-size:12px">10K / 25K / 50K</td>
                <td class="val">$8,750</td>
                <td><span class="badge badge-pending">Pending</span></td>
              </tr>
              <tr>
                <td class="mono">Q002844T</td>
                <td>Telus</td>
                <td class="muted">Window envelopes #10</td>
                <td class="muted" style="font-size:12px">5K / 10K</td>
                <td class="val">$2,100</td>
                <td><span class="badge badge-approved">Approved</span></td>
              </tr>
              <tr>
                <td class="mono">Q002841M</td>
                <td>City of Burnaby</td>
                <td class="muted">4-part NCR sets</td>
                <td class="muted" style="font-size:12px">2.5K / 5K / 10K</td>
                <td class="val">$6,400</td>
                <td><span class="badge badge-sent">Sent</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- AGED QUOTES -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Aged quotes — need attention</div>
            <span style="font-size:11px;color:var(--text-muted)">30+ days no activity</span>
          </div>
          <div class="aged-item">
            <div>
              <div class="aged-qnum">Q002802W</div>
              <div class="aged-customer">Telus — business forms #10</div>
            </div>
            <div class="aged-days">42d</div>
          </div>
          <div class="aged-item">
            <div>
              <div class="aged-qnum">Q002779H</div>
              <div class="aged-customer">BC Hydro — NCR sets</div>
            </div>
            <div class="aged-days">38d</div>
          </div>
          <div class="aged-item">
            <div>
              <div class="aged-qnum">Q002751K</div>
              <div class="aged-customer">BCAA — mailers</div>
            </div>
            <div class="aged-days">35d</div>
          </div>
          <div class="auto-tag">
            <div class="auto-dot"></div>
            Won/lost resolved automatically when an Avanti order appears against this quote
          </div>
        </div>

      </div>

            <!-- ══ RIGHT COLUMN — EDNA ══ -->
      <div>
        <div class="edna-pane">
          <div class="edna-header">
            <div class="edna-avatar">👩‍💼</div>
            <div>
              <div class="edna-name">Edna</div>
              <div class="edna-tagline">40 years on the press floor</div>
              <div class="edna-status">
                <div class="edna-status-dot"></div>
                <span class="edna-status-text">Ready</span>
              </div>
            </div>
          </div>

          <div class="edna-chat" id="edna-chat">
            <div class="bubble edna">
<?php if ($user['role_key'] === 'manager'): ?>
Morning <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>. You've got quotes waiting on approval — want to start there, or are we building something new?
<?php else: ?>
Morning <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! What are we estimating today? Describe the job, pick a customer — or click a tile above and I'll pull up what I know.
<?php endif; ?>
</div>
          </div>

          <div class="edna-footer">
            <div class="listening-bar" id="listening-bar">
              <div class="pulse-dot"></div>
              Listening — speak now
            </div>
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

/* ── USER MENU ── */
function toggleUserMenu() {
  document.getElementById('topbar-user').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const u = document.getElementById('topbar-user');
  if (u && !u.contains(e.target)) u.classList.remove('open');
});

/* ── ROLES ── */
const roles = {
  csr: {
    name: 'Sarah M.', role: 'CSR', initials: 'SM',
    greeting: 'Good morning, Sarah.',
    sub: 'You have 4 quotes that need attention today.',
    open: '6', openSub: '4 need action',
    winrate: '74%', aged: '3',
    sent: '4', sentSub: '2 converted',
    quotesTitle: 'My active quotes',
    ednaMsg: "Morning! What are we estimating today? Describe the job, pick a customer — or click a tile above and I'll pull up what I know."
  },
  manager: {
    name: 'Cam M.', role: 'Manager', initials: 'CM',
    greeting: 'Good morning, Cam.',
    sub: '3 quotes are waiting on your approval right now.',
    open: '24', openSub: 'across all CSRs',
    winrate: '71%', aged: '5',
    sent: '12', sentSub: '8 converted this week',
    quotesTitle: 'All active quotes',
    ednaMsg: "Morning Cam. BC Hydro, Rogers, and Loblaws are all sitting in your approval queue — they've been waiting since yesterday. Want to knock those out first?"
  }
};

function switchRole(role) {
  const r = roles[role];
  document.getElementById('topbar-user').classList.remove('open');
  document.getElementById('topbar-avatar').textContent = r.initials;
  document.getElementById('topbar-name').textContent = r.name;
  document.getElementById('nav-avatar').textContent = r.initials;
  document.getElementById('nav-user-name').textContent = r.name;
  document.getElementById('nav-user-role').textContent = r.role;
  document.getElementById('m-open').textContent = r.open;
  document.getElementById('m-open-sub').textContent = r.openSub;
  document.getElementById('m-winrate').textContent = r.winrate;
  document.getElementById('m-aged').textContent = r.aged;
  document.getElementById('m-sent').textContent = r.sent;
  document.getElementById('m-sent-sub').textContent = r.sentSub;
  document.getElementById('quotes-title').textContent = r.quotesTitle;
  const chat = document.getElementById('edna-chat');
  chat.innerHTML = '';
  addBubble(r.ednaMsg, 'edna');
}

/* ── DATE ── */


/* ── ROLE (from PHP session) ── */
// currentRole is set server-side — JS role switcher is demo only
const serverRole = '<?= $user['role_key'] ?>';

/* ── EDNA ── */
function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendEdna(); }
}

function quickMsg(txt) {
  document.getElementById('edna-input').value = txt;
  sendEdna();
}

function addBubble(txt, type) {
  const chat = document.getElementById('edna-chat');
  const d = document.createElement('div');
  d.className = 'bubble ' + type;
  d.textContent = txt;
  chat.appendChild(d);
  chat.scrollTop = chat.scrollHeight;
  return d;
}

const responses = [
  [/reorder.*bcaa|bcaa.*reorder/i,
    "BCAA's last job was Q002831K — NCR snap sets 3-part, 10K sets, 8.5×11, black ink, top perf, padded in 50s, Press 3. Same specs this time, or are we changing something?"],
  [/gang.?run/i,
    "Good eye — there's a potential gang on Press 3 this week between Q002847R and Q002844T. Both are running 15lb NCR. Combining saves roughly $380 on paper waste. Want me to flag it on both quotes?"],
  [/attention|today|urgent|need/i,
    "Q002846W for BC Hydro is sitting in pending — it's over the $5K threshold so it needs Cam's approval before it can go out. And you've got 3 aged quotes sitting at 35+ days. That's your day right there."],
  [/approv/i,
    "Three quotes in the queue: BC Hydro $8,750, Rogers $12,400, and Loblaws $6,100. All over the $5K threshold. Want me to pull up the first one?"],
  [/ncr|snap.?set|3.?part/i,
    "NCR snap set — my bread and butter. What's the customer, size, and quantity? I'll have a spec and a price faster than you can find the right file in BFE. 😄"],
  [/continuous|cont.?form/i,
    "Continuous forms — got it. Customer, size, number of parts, and quantity is all I need to get started."],
  [/envelope/i,
    "Window envelope #10 is the most common run here — is that what we're looking at? Just need a customer and quantity."],
  [/hi|hello|hey|morning/i,
    "Hey! Ready to go. What are we building today?"],
];

function processEdna(txt) {
  for (const [pat, reply] of responses) {
    if (pat.test(txt)) return reply;
  }
  return "Give me a customer name and job type and I'm off to the races. Or just describe the whole thing — the more detail you give me, the less you have to fill in on the form.";
}

function sendEdna() {
  const inp = document.getElementById('edna-input');
  const txt = inp.value.trim();
  if (!txt) return;
  inp.value = '';
  addBubble(txt, 'user');
  const t = addBubble('Thinking...', 'edna thinking');
  setTimeout(() => { t.remove(); addBubble(processEdna(txt), 'edna'); }, 500 + Math.random() * 400);
}

/* ── MIC ── */
let listening = false;
let recognition = null;

function toggleMic() {
  if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
    addBubble("Voice input needs Chrome — type your spec and I'll parse it!", 'edna');
    return;
  }
  if (listening) { recognition && recognition.stop(); return; }
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  recognition = new SR();
  recognition.continuous = false;
  recognition.interimResults = false;
  recognition.lang = 'en-CA';
  recognition.onstart = () => {
    listening = true;
    document.getElementById('mic-btn').classList.add('listening');
    document.getElementById('listening-bar').classList.add('active');
  };
  recognition.onresult = (e) => {
    document.getElementById('edna-input').value = e.results[0][0].transcript;
    sendEdna();
  };
  recognition.onend = () => {
    listening = false;
    document.getElementById('mic-btn').classList.remove('listening');
    document.getElementById('listening-bar').classList.remove('active');
  };
  recognition.onerror = () => {
    listening = false;
    document.getElementById('mic-btn').classList.remove('listening');
    document.getElementById('listening-bar').classList.remove('active');
    addBubble("Mic trouble? Try typing instead.", 'edna');
  };
  recognition.start();
}

/* ── TILE SELECTION ── */
function selectTile(type, el) {
  document.querySelectorAll('.qa-tile').forEach(t => {
    t.classList.remove('selected','primary');
  });
  el.classList.add('selected');
  document.getElementById('nq-lookup').focus();
  // Edna reacts
  const msgs = {
    newquote: "Ready to build something new. Give me a customer and describe the job — as much or as little as you've got.",
    template: "Four templates ready: 3-part NCR snap sets, continuous forms #10, window envelope #10, and 4-part NCR carbonless. Who's the customer?",
    reorder: "BCAA's last order was Q002831K — 10K NCR 3-part snap sets. Want to reorder that, or pick a different customer?",
    similar: "Pick a base quote and a new customer — I'll flag any pricing differences worth knowing before we run the estimate."
  };
  addBubble(msgs[type] || "What are we building?", 'edna');
}

/* ── LOOKUP ── */
let lookupTimer = null;
/* ── PARSE AND GO ── */
function parseAndGo() {
  const customer    = document.getElementById('nq-lookup').value.trim();
  const description = document.getElementById('nq-desc').value.trim();
  if (!description) {
    addBubble("Give me at least a job description to work with — try: '10,000 3-part NCR sets, 8.5×11, black ink, top perf, padded in 50s'", 'edna');
    document.getElementById('nq-desc').focus();
    return;
  }
  sessionStorage.setItem('scp_parse_job', JSON.stringify({ customer, description }));
  window.location.href = '/modules/forms-estimating/spec_review.php';
}

/* ── LOOKUP ── */
function handleLookup(val) {
  clearTimeout(lookupTimer);
  const result = document.getElementById('lookup-result');
  if (!val || val.length < 2) { result.style.display = 'none'; return; }
  lookupTimer = setTimeout(() => {
    const v = val.toLowerCase().trim();
    let html = '';
    if (v.includes('bcaa')) {
      html = lookupCard('👤','BCAA','12 quotes · last job Q002831K — NCR 3-part snap sets · avg $2,800 · pays on time',[['New quote for BCAA →',''],['Reorder Q002831K →','']]);
    } else if (v.includes('hydro')) {
      html = lookupCard('👤','BC Hydro','8 quotes · last job Q002795M — continuous forms · avg $6,400 · slow payer on large jobs',[['New quote for BC Hydro →','']]);
    } else if (v.includes('telus')) {
      html = lookupCard('👤','Telus','6 quotes · last job Q002808T — window envelopes #10 · avg $3,100 · reliable',[['New quote for Telus →','']]);
    } else if (v.match(/q0*\d{4,}/i)) {
      html = lookupCard('📄','Quote found: Q002831K','BCAA — NCR 3-part snap sets · 10,000 sets · $2,100 · Won',[['Clone this quote →',''],['View quote →','']]);
    } else if (v.includes('ncr') || v.includes('snap')) {
      html = lookupCard('🖨','NCR snap sets','Most common job type — 62% of forms quotes. Template ready.',[['Use NCR template →',''],['Blank quote →','']]);
    } else if (val.length >= 3) {
      html = lookupCard('🔍','No exact match','Describe the job below and I\'ll parse it for you.',[ ]);
    }
    if (html) { result.innerHTML = html; result.style.display = 'block'; }
    else result.style.display = 'none';
  }, 300);
}

function lookupCard(icon, title, detail, actions) {
  const btns = actions.map(([label]) =>
    `<button class="btn btn-sm btn-primary" style="font-size:12px">${label}</button>`
  ).join('');
  return `<div class="lookup-card">
    <div class="lookup-card-title"><span>${icon}</span>${title}</div>
    <div class="lookup-card-detail">${detail}</div>
    ${btns ? `<div class="lookup-actions">${btns}</div>` : ''}
  </div>`;
}

</script>
</body>
</html>