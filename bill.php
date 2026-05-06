<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo = db();

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];

$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to']   ?? '');

$isFiltered = ($dateFrom !== '' || $dateTo !== '');

if ($isFiltered) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-29 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

    $stmt = $pdo->prepare(
        "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
                SUM(oi.quantity * oi.unit_price) AS subtotal
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN ? AND ?
         GROUP BY o.id, o.type, o.table_number, o.status, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 200"
    );
    $stmt->execute([$dateFrom, $dateTo]);
} else {
    $stmt = $pdo->query(
        "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
                SUM(oi.quantity * oi.unit_price) AS subtotal
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id, o.type, o.table_number, o.status, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 200"
    );
}
$orders = $stmt->fetchAll();

$pageTitle    = 'Billing';
$pageSubtitle = 'View and print order receipts';
$activePage   = 'billing';
require_once __DIR__ . '/includes/header.php';
?>

<form method="GET" class="d-flex align-items-center gap-3 mb-4 flex-wrap"
      style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:12px 18px;">
  <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-light);">Filter by Date</span>
  <input type="date" name="from" class="form-control form-control-sm" style="max-width:160px;" value="<?= htmlspecialchars($dateFrom) ?>">
  <span style="color:var(--text-light);font-size:13px;">to</span>
  <input type="date" name="to"   class="form-control form-control-sm" style="max-width:160px;" value="<?= htmlspecialchars($dateTo) ?>">
  <button type="submit" class="btn btn-sm btn-primary">Apply</button>
  <?php if ($isFiltered): ?>
  <a href="/bill.php" class="btn btn-sm btn-outline-secondary">Clear</a>
  <?php endif; ?>
  <a href="/bill.php?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
  <a href="/bill.php?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Last 7 Days</a>
  <a href="/bill.php?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">This Month</a>
</form>

<div class="section-card">
  <div class="section-card-header">
    <h2>Orders</h2>
    <span style="font-size:13px;color:var(--text-secondary);"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?><?= $isFiltered ? ' in range' : '' ?></span>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Type</th>
          <th>Table / Ref</th>
          <th>Total</th>
          <th>Time</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $sub   = (float)$o['subtotal'];
          $total = $sub + ($gstEnabled ? round($sub * $gstRate / 100, 2) : 0);
        ?>
        <tr>
          <td><strong>#<?= (int)$o['id'] ?></strong></td>
          <td><?= $o['type'] === 'dine-in' ? 'Dine-in' : 'Takeaway' ?></td>
          <td><?= htmlspecialchars($o['table_number'] ?? '—') ?></td>
          <td><strong>$<?= number_format($total, 2) ?></strong></td>
          <td style="color:var(--text-secondary);font-size:13px;"><?= time_ago($o['created_at']) ?></td>
          <td>
            <span class="status-badge <?= $o['status'] === 'Paid' ? 'status-paid' : 'status-ready' ?>">
              <?= htmlspecialchars($o['status']) ?>
            </span>
          </td>
          <td>
            <?php if (in_array($o['status'], ['Ready','Paid'])): ?>
              <a href="/bill-view.php?order_id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-secondary">View Bill</a>
            <?php else: ?>
              <span style="font-size:12px;color:var(--text-light);">In progress</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
