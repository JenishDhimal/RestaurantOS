<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo     = db();
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header('Location: /bill.php');
    exit;
}

$order = $pdo->prepare(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at, u.name AS server_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.staff_id
     WHERE o.id = ? AND o.status IN ('Ready','Paid')"
);
$order->execute([$orderId]);
$order = $order->fetch();

if (!$order) {
    header('Location: /bill.php');
    exit;
}

$items = $pdo->prepare(
    "SELECT mi.name, oi.quantity, oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
     FROM order_items oi
     JOIN menu_items mi ON mi.id = oi.menu_item_id
     WHERE oi.order_id = ?
     ORDER BY mi.name"
);
$items->execute([$orderId]);
$items = $items->fetchAll();

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];
$subtotal   = array_sum(array_column($items, 'line_total'));
$gst        = $gstEnabled ? round($subtotal * $gstRate / 100, 2) : 0;
$total      = $subtotal + $gst;

$pageTitle    = 'Bill #' . $orderId;
$pageSubtitle = 'Order receipt';
$activePage   = 'billing';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['paid'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-4 no-print" role="alert">
  <strong>Payment recorded.</strong> Order #<?= (int)$order['id'] ?> is now marked as Paid.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex gap-2 mb-4 no-print">
  <a href="/bill.php" class="btn btn-outline-secondary">← All Bills</a>
  <button onclick="window.print()" class="btn btn-outline-secondary ms-auto">Print</button>
</div>

<div class="receipt-wrap">
  <div class="receipt-card">

    <div class="receipt-header">
      <div class="receipt-logo">
        <span class="receipt-logo-icon"><i class="fa-solid fa-utensils"></i></span>
        <div>
          <div class="receipt-restaurant">RestaurantOS</div>
        </div>
      </div>
      <div class="receipt-meta">
        <div class="receipt-order-num">ORDER #<?= (int)$order['id'] ?></div>
        <div class="receipt-badges">
          <span class="status-badge <?= $order['status'] === 'Paid' ? 'status-paid' : 'status-ready' ?>">
            <?= htmlspecialchars($order['status']) ?>
          </span>
          <span class="status-badge" style="background:rgba(59,130,246,.1);color:#3b82f6;">
            <?= $order['type'] === 'dine-in' ? 'Dine-In' : 'Takeaway' ?>
          </span>
        </div>
      </div>
    </div>

    <div class="receipt-divider"></div>

    <div class="receipt-details-row">
      <div class="receipt-detail">
        <span class="rd-label">Date & Time</span>
        <span class="rd-value"><?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></span>
      </div>
      <div class="receipt-detail">
        <span class="rd-label"><?= $order['type'] === 'dine-in' ? 'Table' : 'Reference' ?></span>
        <span class="rd-value"><?= htmlspecialchars($order['table_number'] ?? '—') ?></span>
      </div>
      <div class="receipt-detail">
        <span class="rd-label">Served By</span>
        <span class="rd-value"><?= htmlspecialchars($order['server_name']) ?></span>
      </div>
    </div>

    <div class="receipt-divider"></div>

    <table class="receipt-table">
      <thead>
        <tr>
          <th>Item</th>
          <th class="text-center">Qty</th>
          <th class="text-end">Unit</th>
          <th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['name']) ?></td>
          <td class="text-center"><?= (int)$it['quantity'] ?></td>
          <td class="text-end">$<?= number_format($it['unit_price'], 2) ?></td>
          <td class="text-end"><strong>$<?= number_format($it['line_total'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="receipt-divider"></div>

    <div class="receipt-totals">
      <?php if ($gstEnabled): ?>
      <div class="receipt-total-row">
        <span>Subtotal</span>
        <span>$<?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="receipt-total-row">
        <span>GST (<?= $gstRate ?>%)</span>
        <span>$<?= number_format($gst, 2) ?></span>
      </div>
      <?php endif; ?>
      <div class="receipt-total-row receipt-grand-total">
        <span>Total</span>
        <span>$<?= number_format($total, 2) ?></span>
      </div>
    </div>

    <?php if ($gstEnabled && $settings['gst_number'] !== ''): ?>
    <div style="text-align:center;font-size:11px;color:var(--text-light);margin-top:12px;">
      GST No: <?= htmlspecialchars($settings['gst_number']) ?>
    </div>
    <?php endif; ?>

    <div class="receipt-footer">Thank you for dining with us</div>

  </div>
</div>

<style>
.receipt-wrap { display:flex; justify-content:center; padding-bottom:48px; }
.receipt-card {
  background:white; border-radius:var(--radius); box-shadow:var(--shadow-md);
  padding:40px 48px; width:100%; max-width:680px;
}
.receipt-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; }
.receipt-logo { display:flex; align-items:center; gap:14px; }
.receipt-logo-icon {
  width:48px; height:48px; background:var(--accent); border-radius:12px;
  display:flex; align-items:center; justify-content:center; color:white; font-size:20px; flex-shrink:0;
}
.receipt-restaurant { font-family:'Playfair Display',serif; font-size:20px; font-weight:700; color:var(--text-primary); }
.receipt-tagline { font-size:12px; color:var(--text-secondary); margin-top:2px; }
.receipt-order-num { font-family:'Playfair Display',serif; font-size:22px; font-weight:700; color:var(--text-primary); text-align:right; margin-bottom:8px; }
.receipt-badges { display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap; }
.receipt-divider { border:none; border-top:1.5px dashed var(--border); margin:20px 0; }
.receipt-details-row { display:flex; gap:32px; flex-wrap:wrap; }
.receipt-detail { display:flex; flex-direction:column; gap:3px; }
.rd-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); }
.rd-value { font-size:14px; font-weight:600; color:var(--text-primary); }
.receipt-table { width:100%; border-collapse:collapse; }
.receipt-table th { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); padding:8px 0; border-bottom:1.5px solid var(--border); }
.receipt-table td { padding:12px 0; font-size:14px; border-bottom:1px solid rgba(229,231,235,.5); color:var(--text-primary); }
.receipt-table tr:last-child td { border-bottom:none; }
.receipt-totals { display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
.receipt-total-row { display:flex; gap:48px; font-size:14px; color:var(--text-secondary); }
.receipt-total-row span:last-child { min-width:80px; text-align:right; }
.receipt-grand-total { font-size:20px; font-weight:800; color:var(--text-primary); margin-top:8px; padding-top:12px; border-top:2px solid var(--text-primary); }
.receipt-footer { text-align:center; margin-top:28px; font-size:13px; color:var(--text-secondary); padding-top:20px; border-top:1.5px dashed var(--border); }

@media print {
  .sidebar, .topbar, .no-print { display:none !important; }
  .main-content { margin:0 !important; }
  .page-content { padding:0 !important; }
  .receipt-card { box-shadow:none !important; }
  .receipt-wrap { padding:0 !important; }
  body { background:white !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
