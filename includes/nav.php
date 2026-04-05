<?php
/**
 * SCP Central — Shared sidenav + topbar
 * php/includes/nav.php
 *
 * Variables expected before include:
 *   $activePage  string  e.g. 'dashboard', 'estimating', 'customers', 'jobs', 'reports', 'admin'
 *   $pageTitle   string  displayed in the topbar
 *   $navBadges   array   optional badge counts e.g. ['estimating' => 6]
 *   $_AUTH_USER  array   set by auth.php — name, role, role_id
 */

// Build user display values from auth middleware
$_navName     = $_AUTH_USER['name']     ?? 'User';
$_navRole     = ucfirst($_AUTH_USER['role'] ?? 'csr');
$_navParts    = array_filter(explode(' ', trim($_navName)));
$_navInitials = strtoupper(
    count($_navParts) >= 2
        ? mb_substr($_navParts[0], 0, 1) . mb_substr(end($_navParts), 0, 1)
        : mb_substr($_navParts[0] ?? 'U', 0, 2)
);

// Badge helper — returns rendered badge HTML or empty string
function navBadge(string $key, array $badges): string {
    $count = $badges[$key] ?? 0;
    return $count > 0
        ? '<span class="nav-badge">' . (int)$count . '</span>'
        : '';
}

$_navBadges = $navBadges ?? [];

// Nav item definitions — [key, label, href, svgPath]
$_navItems = [
    'main' => [
        ['dashboard',  'Dashboard',  '/',
            '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
        ['estimating', 'Estimating', '/modules/forms-estimating/',
            '<path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3"/><path d="M9 15h3l8.5-8.5a1.5 1.5 0 00-3-3L9 12v3z"/>'],
        ['customers',  'Customers',  '/customers.php',
            '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>'],
    ],
    'production' => [
        ['jobs',    'Jobs',    '/jobs.php',
            '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>'],
        ['reports', 'Reports', '/reports.php',
            '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ],
    'system' => [
        ['admin', 'Admin', '/admin.php',
            '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/>'],
    ],
];
?>

<!-- ══ SIDE NAV ══ -->
<nav class="sidenav" id="sidenav">

  <div class="nav-header">
    <a class="nav-logo" href="/" title="SCP Central">S</a>
    <a class="nav-brand" href="/">
      <div class="nav-brand-name">SCP Central</div>
      <div class="nav-brand-sub">Print Management</div>
    </a>
    <button class="nav-collapse-btn" id="collapse-btn" onclick="toggleNav()" title="Collapse menu">
      <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <path id="collapse-icon" d="M10 3L5 8l5 5"/>
      </svg>
    </button>
  </div>

  <div class="nav-section-label">Main</div>
  <?php foreach ($_navItems['main'] as [$key, $label, $href, $svg]): ?>
  <a class="nav-item <?= $activePage === $key ? 'active' : '' ?>" href="<?= $href ?>">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $svg ?></svg></span>
    <span class="nav-item-label"><?= $label ?></span>
    <?= navBadge($key, $_navBadges) ?>
    <span class="nav-tooltip"><?= $label ?></span>
  </a>
  <?php endforeach; ?>

  <div class="nav-section-label">Production</div>
  <?php foreach ($_navItems['production'] as [$key, $label, $href, $svg]): ?>
  <a class="nav-item <?= $activePage === $key ? 'active' : '' ?>" href="<?= $href ?>">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $svg ?></svg></span>
    <span class="nav-item-label"><?= $label ?></span>
    <?= navBadge($key, $_navBadges) ?>
    <span class="nav-tooltip"><?= $label ?></span>
  </a>
  <?php endforeach; ?>

  <div class="nav-section-label">System</div>
  <?php foreach ($_navItems['system'] as [$key, $label, $href, $svg]): ?>
  <a class="nav-item <?= $activePage === $key ? 'active' : '' ?>" href="<?= $href ?>">
    <span class="nav-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $svg ?></svg></span>
    <span class="nav-item-label"><?= $label ?></span>
    <?= navBadge($key, $_navBadges) ?>
    <span class="nav-tooltip"><?= $label ?></span>
  </a>
  <?php endforeach; ?>

  <!-- User footer -->
  <div class="nav-footer" onclick="window.location='/logout.php'" title="Sign out">
    <div class="nav-avatar"><?= htmlspecialchars($_navInitials) ?></div>
    <div class="nav-user-info">
      <div class="nav-user-name"><?= htmlspecialchars($_navName) ?></div>
      <div class="nav-user-role"><?= htmlspecialchars($_navRole) ?></div>
    </div>
  </div>

</nav>

<!-- ══ MOBILE OVERLAY ══ -->
<div class="nav-overlay" id="nav-overlay" onclick="closeMobileNav()"></div>

<!-- ══ MAIN (opened here, closed in footer.php) ══ -->
<div class="main" id="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="topbar-page-title"><?= htmlspecialchars($pageTitle ?? 'SCP Central') ?></div>

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
        <div class="topbar-avatar"><?= htmlspecialchars($_navInitials) ?></div>
        <span class="topbar-user-name"><?= htmlspecialchars($_navName) ?></span>
        <svg class="topbar-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="user-dropdown">
          <div class="dropdown-section-label"><?= htmlspecialchars($_navName) ?></div>
          <div class="dropdown-item" style="cursor:default;color:var(--text-muted)">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= htmlspecialchars($_navRole) ?>
          </div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-item" onclick="window.location='/preferences.php'">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41"/></svg>
            Preferences
          </div>
          <div class="dropdown-item danger" onclick="window.location='/logout.php'">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign out
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /TOPBAR — page content follows -->
