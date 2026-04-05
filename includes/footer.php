<?php
/**
 * SCP Central — Shared footer
 * php/includes/footer.php
 *
 * Closes .main and .shell opened by nav.php and head.php.
 * Call sessionTimeoutScript() from auth.php in <head> on every protected page.
 * This file handles the session modal and shared JS.
 */

// Output session timeout modal from auth.php
sessionModal();
?>

<script>
/* ══ NAV COLLAPSE ══ */
let navCollapsed = false;
let mobileNavOpen = false;

function toggleNav() {
    navCollapsed = !navCollapsed;
    document.getElementById('sidenav').classList.toggle('collapsed', navCollapsed);
    document.getElementById('main').classList.toggle('nav-collapsed', navCollapsed);
    document.getElementById('collapse-icon').setAttribute('d', navCollapsed ? 'M6 3l5 5-5 5' : 'M10 3L5 8l5 5');
    // Persist preference
    try { localStorage.setItem('scp_nav_collapsed', navCollapsed ? '1' : '0'); } catch(e) {}
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

/* ══ USER DROPDOWN ══ */
function toggleUserMenu() {
    document.getElementById('topbar-user').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const u = document.getElementById('topbar-user');
    if (u && !u.contains(e.target)) u.classList.remove('open');
});

/* ══ RESTORE NAV STATE ══ */
(function() {
    try {
        if (localStorage.getItem('scp_nav_collapsed') === '1') {
            navCollapsed = true;
            document.getElementById('sidenav').classList.add('collapsed');
            document.getElementById('main').classList.add('nav-collapsed');
            const icon = document.getElementById('collapse-icon');
            if (icon) icon.setAttribute('d', 'M6 3l5 5-5 5');
        }
    } catch(e) {}
})();
</script>

</div><!-- /main -->
</div><!-- /shell -->
</body>
</html>
