<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

$settings = get_settings();

$pageTitle    = 'Settings';
$pageSubtitle = 'System configuration';
$activePage   = 'settings';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success mb-4 py-2 px-3 d-inline-flex align-items-center gap-2">
  Settings saved.
</div>
<?php endif; ?>

<form method="POST" action="/settings-save.php">

  <!-- Tables -->
  <div class="section-card mb-3">
    <div class="section-card-header">
      <h2>Tables</h2>
    </div>
    <div class="section-card-body">
      <label class="form-label fw-semibold">Number of Tables</label>
      <input type="number" name="table_count" class="form-control"
             value="<?= (int)$settings['table_count'] ?>" min="1" max="50" required
             style="max-width:140px;">
      <div class="form-text mt-1">Controls the table options shown when placing an order.</div>
    </div>
  </div>

  <!-- GST -->
  <div class="section-card mb-4">
    <div class="section-card-header">
      <h2>GST</h2>
    </div>
    <div class="section-card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="gst_enabled" id="gstToggle"
               <?= $settings['gst_enabled'] ? 'checked' : '' ?>
               onchange="document.getElementById('gstFields').style.display=this.checked?'':'none'">
        <label class="form-check-label fw-semibold" for="gstToggle">Enable GST (10%)</label>
      </div>
      <div id="gstFields" <?= $settings['gst_enabled'] ? '' : 'style="display:none"' ?>>
        <label class="form-label fw-semibold">GST Registration Number</label>
        <input type="text" name="gst_number" class="form-control"
               value="<?= htmlspecialchars($settings['gst_number']) ?>"
               placeholder="e.g. 12 345 678 901" style="max-width:260px;">
        <div class="form-text mt-1">Printed on receipts when set.</div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary px-4">Save Settings</button>
</form>

<!-- Export -->
<div class="section-card mt-4">
  <div class="section-card-header">
    <h2>Export Orders</h2>
  </div>
  <div class="section-card-body">
    <form method="GET" action="/settings-export.php" class="d-flex align-items-end gap-3 flex-wrap">
      <div>
        <label class="form-label fw-semibold mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm"
               value="<?= date('Y-m-01') ?>">
      </div>
      <div>
        <label class="form-label fw-semibold mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm"
               value="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn btn-outline-secondary">Download CSV</button>
    </form>
    <div class="form-text mt-2">Exports all orders with line items for the selected date range.</div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
