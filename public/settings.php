<?php
/**
 * /settings.php
 *
 * Protected admin settings page. Gated by RBAC:
 *   - page:settings READ  to view
 *   - page:settings WRITE to save (enforced in /api/save_settings.php)
 * super_admin gets access via the '*' wildcard grant.
 *
 * First card: Anthropic model selector (active model used by Edna).
 * Designed as stacked sections so future settings slot in as new cards.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/permissions.php';

$userId = (int) $_AUTH_USER['id'];

// ── Gate ──────────────────────────────────────────────────────────────────────
if (!hasPermission($userId, 'page:settings', PERM_READ)) {
    http_response_code(403);
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="content"><div class="content-inner" style="grid-template-columns:1fr">'
       . '<div class="card"><div class="card-title">Access denied</div>'
       . '<p style="margin-top:10px;color:var(--text-mid)">You don\'t have permission to view Settings.</p>'
       . '</div></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$canWrite = hasPermission($userId, 'page:settings', PERM_WRITE);

// ── Load data ─────────────────────────────────────────────────────────────────
$db = getDB();

// Active model (with sane fallback if the row is somehow missing)
$stmt = $db->prepare("SELECT value FROM app_settings WHERE key = 'anthropic_model'");
$stmt->execute();
$currentModel = $stmt->fetchColumn() ?: '';

// Available models for the dropdown
$stmt = $db->query("
    SELECT model_id, label, speed_note, cost_note
    FROM anthropic_models
    WHERE is_active = true
    ORDER BY sort_order, label
");
$models = $stmt->fetchAll();

// ── Page config ───────────────────────────────────────────────────────────────
$pageTitle  = 'Settings';
$activePage = 'settings';
$navBadges  = [];

$extraCss = '<style>
.settings-wrap{max-width:760px}
.settings-section{margin-bottom:20px}
.set-field{display:flex;flex-direction:column;gap:6px;max-width:420px}
.set-select{font-family:var(--sans);font-size:14px;padding:9px 11px;border:0.5px solid var(--border-mid);border-radius:var(--radius-sm);background:var(--bg-card);color:var(--text);width:100%;transition:border-color 0.15s}
.set-select:focus{outline:none;border-color:var(--blue-mid)}
.set-select:disabled{background:var(--bg-surface);color:var(--text-muted);cursor:not-allowed}
.set-help{font-size:12px;color:var(--text-muted);line-height:1.5}
.set-current{font-size:12px;color:var(--text-mid);margin-top:2px}
.set-current code{font-family:var(--mono);background:var(--bg-surface);border:0.5px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px}
.set-actions{display:flex;align-items:center;gap:12px;margin-top:16px}
.set-status{font-size:13px;display:none}
.set-status.ok{display:inline;color:var(--green)}
.set-status.err{display:inline;color:var(--red)}
.readonly-note{font-size:12px;color:var(--amber);background:var(--amber-light);border:0.5px solid var(--amber-border);border-radius:var(--radius-sm);padding:8px 12px;margin-bottom:16px}
</style>';

require_once __DIR__ . '/../includes/header.php';
?>

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <button class="mobile-menu-btn" onclick="toggleMobileNav()">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="topbar-page-title">Settings</div>
  </div>

  <!-- ══ CONTENT ══ -->
  <div class="content">
    <div class="content-inner" style="grid-template-columns:1fr">
      <div class="settings-wrap">

        <?php if (!$canWrite): ?>
        <div class="readonly-note">You have read-only access to Settings — changes are disabled.</div>
        <?php endif; ?>

        <!-- ══ SECTION: Edna / AI model ══ -->
        <div class="card settings-section">
          <div class="card-header">
            <div class="card-title">Edna — AI Model</div>
          </div>

          <div class="set-field">
            <label class="field-label" for="model-select">Active Claude model</label>
            <select class="set-select" id="model-select" <?= $canWrite ? '' : 'disabled' ?>>
              <?php foreach ($models as $m):
                $notes = array_filter([$m['speed_note'], $m['cost_note']]);
                $noteStr = $notes ? ' — ' . implode(' · ', $notes) : '';
              ?>
              <option value="<?= htmlspecialchars($m['model_id']) ?>" <?= $m['model_id'] === $currentModel ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['label'] . $noteStr) ?>
              </option>
              <?php endforeach; ?>
              <?php
                // If the stored model isn't in the active list (e.g. a retired
                // string), surface it so the admin can see what's set.
                $known = array_column($models, 'model_id');
                if ($currentModel && !in_array($currentModel, $known, true)):
              ?>
              <option value="<?= htmlspecialchars($currentModel) ?>" selected><?= htmlspecialchars($currentModel) ?> — not in list</option>
              <?php endif; ?>
            </select>
            <div class="set-help">The model Edna uses to parse job specs. Changes take effect immediately — no redeploy.</div>
            <div class="set-current">Currently active: <code id="current-model"><?= htmlspecialchars($currentModel ?: 'none') ?></code></div>
          </div>

          <?php if ($canWrite): ?>
          <div class="set-actions">
            <button class="btn btn-primary btn-sm" id="save-model" onclick="saveModel()">Save model</button>
            <span class="set-status" id="model-status"></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Future settings sections slot in here as additional .card.settings-section blocks -->

      </div>
    </div>
  </div>

<?php if ($canWrite): ?>
<script>
function saveModel() {
    const sel    = document.getElementById('model-select');
    const status = document.getElementById('model-status');
    const btn    = document.getElementById('save-model');
    const model  = sel.value;

    status.className = 'set-status';
    status.textContent = '';
    btn.disabled = true;

    fetch('/api/save_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ anthropic_model: model })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.saved) {
            status.className = 'set-status ok';
            status.textContent = 'Saved';
            document.getElementById('current-model').textContent = model;
        } else {
            status.className = 'set-status err';
            status.textContent = data.error || 'Save failed';
        }
    })
    .catch(() => {
        status.className = 'set-status err';
        status.textContent = 'Could not reach the server';
    })
    .finally(() => { btn.disabled = false; });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
