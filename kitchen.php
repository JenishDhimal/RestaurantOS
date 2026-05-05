<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';
require_login();

$pdo    = db();
$orders = fetch_kitchen_orders($pdo);

$pageTitle    = 'Kitchen Display';
$pageSubtitle = 'Active orders — refreshes every 10 seconds';
$activePage   = 'kitchen';
require_once __DIR__ . '/includes/header.php';
?>

<div class="section-card">
  <div class="section-card-header">
    <h2>Active Orders</h2>
    <span style="font-size:13px;color:var(--text-secondary);"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></span>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Table / Ref</th>
          <th>Items</th>
          <th>Status</th>
          <th style="width:160px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-5">No active orders.</td>
        </tr>
        <?php endif; ?>

        <?php foreach ($orders as $o):
          $isReady    = $o['status'] === 'Ready';
          $statusCls  = 'status-' . strtolower($o['status']);
          $isTakeaway = $o['type'] === 'takeaway';
        ?>
        <tr>
          <td><strong>#<?= (int)$o['id'] ?></strong></td>
          <td>
            <?= htmlspecialchars($o['table_number'] ?? '—') ?>
            <?php if ($isTakeaway): ?>
              <span class="kd-takeaway">Takeaway</span>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-secondary);font-size:13px;">
            <?php foreach (explode(', ', $o['items_summary']) as $line): ?>
              <div><?= htmlspecialchars($line) ?></div>
            <?php endforeach; ?>
          </td>
          <td>
            <span class="status-badge <?= $statusCls ?>"><?= htmlspecialchars($o['status']) ?></span>
          </td>
          <td>
            <?php if ($o['status'] === 'Received'): ?>
              <form method="POST" action="/orders-update.php">
                <input type="hidden" name="order_id"   value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="new_status" value="Preparing">
                <button type="submit" class="btn btn-sm btn-outline-warning w-100">Start Preparing</button>
              </form>
            <?php elseif ($o['status'] === 'Preparing'): ?>
              <form method="POST" action="/orders-update.php">
                <input type="hidden" name="order_id"   value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="new_status" value="Ready">
                <button type="submit" class="btn btn-sm btn-outline-success w-100">Mark as Ready</button>
              </form>
            <?php else: ?>
              <span style="font-size:13px;color:var(--green);font-weight:600;">Ready for collection</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.kd-takeaway {
  display: inline-block;
  margin-left: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 8px;
  background: rgba(239, 68, 68, .12);
  color: var(--red);
}
</style>

<script>
setTimeout(() => location.reload(), 10000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
