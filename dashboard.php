<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

$pdo = db();

$ordersToday = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$ordersYesterday = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY"
)->fetchColumn();
$ordersDelta = $ordersYesterday > 0
    ? round((($ordersToday - $ordersYesterday) / $ordersYesterday) * 100)
    : null;

$revenueToday = $pdo->query(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE DATE(o.created_at) = CURDATE()"
)->fetchColumn();

$revenueYesterday = $pdo->query(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE DATE(o.created_at) = CURDATE() - INTERVAL 1 DAY"
)->fetchColumn();
$revenueDelta = $revenueYesterday > 0
    ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100)
    : null;

$pendingOrders = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status IN ('Received','Preparing')"
)->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
            COUNT(oi.id)                              AS item_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS amount,
            u.name                                    AS server_name
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     LEFT JOIN users u       ON u.id = o.staff_id
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10"
)->fetchAll();

$weekRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $sum   = $pdo->prepare(
        "SELECT COALESCE(SUM(p.amount), 0)
         FROM payments p
         JOIN orders o ON p.order_id = o.id
         WHERE DATE(o.created_at) = ?"
    );
    $sum->execute([$date]);
    $weekRevenue[] = ['label' => $label, 'value' => (float)$sum->fetchColumn()];
}

$rangeFrom  = $_GET['from'] ?? date('Y-m-d');
$rangeTo    = $_GET['to']   ?? date('Y-m-d');
$isAllTime  = false;
$activePeriod = $_GET['period'] ?? '';
if (isset($_GET['period'])) {
    if ($_GET['period'] === 'today') {
        $rangeFrom = date('Y-m-d');
        $rangeTo   = date('Y-m-d');
    } else if ($_GET['period'] === 'week') {
        $rangeFrom = date('Y-m-d', strtotime('-6 days'));
        $rangeTo   = date('Y-m-d');
    } else if ($_GET['period'] === 'month') {
        $rangeFrom = date('Y-m-d', strtotime('-29 days'));
        $rangeTo   = date('Y-m-d');
    } else if ($_GET['period'] === 'alltime') {
        $earliest  = $pdo->query("SELECT COALESCE(MIN(DATE(created_at)), CURDATE()) FROM orders")->fetchColumn();
        $rangeFrom = $earliest;
        $rangeTo   = date('Y-m-d');
        $isAllTime = true;
    }
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeFrom)) $rangeFrom = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeTo))   $rangeTo   = date('Y-m-d');

$ordersQuery = $pdo->prepare(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?"
);
$ordersQuery->execute([$rangeFrom, $rangeTo]);
$ordersCount = $ordersQuery->fetchColumn();

$revenueQuery = $pdo->prepare(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE DATE(o.created_at) BETWEEN ? AND ?"
);
$revenueQuery->execute([$rangeFrom, $rangeTo]);
$revenueTotal = $revenueQuery->fetchColumn();

$expenseQuery = $pdo->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN ? AND ?"
);
$expenseQuery->execute([$rangeFrom, $rangeTo]);
$expenseTotal = $expenseQuery->fetchColumn();

$pendingOrders = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status IN ('Received','Preparing')"
)->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
            COUNT(oi.id)                              AS item_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS amount,
            u.name                                    AS server_name
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     LEFT JOIN users u       ON u.id = o.staff_id
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10"
)->fetchAll();

$revenuePerDay = [];
$daysDiff = (strtotime($rangeTo) - strtotime($rangeFrom)) / 86400;
$useMonthlyGrouping = $daysDiff > 60;

if ($useMonthlyGrouping) {
    $monthlyStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS month,
                COALESCE(SUM(p.amount), 0) AS total
         FROM payments p
         JOIN orders o ON p.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN ? AND ?
         GROUP BY month
         ORDER BY month"
    );
    $monthlyStmt->execute([$rangeFrom, $rangeTo]);
    foreach ($monthlyStmt->fetchAll() as $r) {
        $revenuePerDay[] = [
            'label' => date("M 'y", strtotime($r['month'] . '-01')),
            'value' => (float)$r['total']
        ];
    }
} else {
    $currentDate = new DateTime($rangeFrom);
    $endDate     = new DateTime($rangeTo);
    $interval    = new DateInterval('P1D');
    $period      = new DatePeriod($currentDate, $interval, $endDate->modify('+1 day'));
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(p.amount), 0)
         FROM payments p
         JOIN orders o ON p.order_id = o.id
         WHERE DATE(o.created_at) = ?"
    );
    foreach ($period as $date) {
        $stmt->execute([$date->format('Y-m-d')]);
        $revenuePerDay[] = [
            'label' => $date->format('D'),
            'value' => (float)$stmt->fetchColumn()
        ];
    }
}

$orderTypeRows = $pdo->prepare(
    "SELECT type, COUNT(*) AS cnt FROM orders
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY type"
);
$orderTypeRows->execute([$rangeFrom, $rangeTo]);
$orderTypeCounts = ['dine-in' => 0, 'takeaway' => 0];
foreach ($orderTypeRows->fetchAll() as $r) $orderTypeCounts[$r['type']] = (int)$r['cnt'];

$methodRows = $pdo->prepare(
    "SELECT p.method, COUNT(*) AS cnt
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY p.method"
);
$methodRows->execute([$rangeFrom, $rangeTo]);
$methodCounts = ['cash' => 0, 'card' => 0, 'digital' => 0];
foreach ($methodRows->fetchAll() as $r) $methodCounts[$r['method']] = (int)$r['cnt'];

$topItemRows = $pdo->prepare(
    "SELECT mi.name, SUM(oi.quantity) AS total_qty
     FROM order_items oi
     JOIN menu_items mi ON mi.id = oi.menu_item_id
     JOIN orders o ON o.id = oi.order_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY mi.id
     ORDER BY total_qty DESC
     LIMIT 5"
);
$topItemRows->execute([$rangeFrom, $rangeTo]);
$topItems = $topItemRows->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of restaurant operations';
$activePage   = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Analytics filter ─────────────────── -->
<form method="GET" class="analytics-filter mb-3" id="rangeForm">
  <span class="af-label">Analytics range:</span>
  <div class="af-quick">
    <button type="submit" name="period" value="today"   class="af-btn<?= $activePeriod==='today'   ? ' af-btn-active' : '' ?>">Today</button>
    <button type="submit" name="period" value="week"    class="af-btn<?= $activePeriod==='week'    ? ' af-btn-active' : '' ?>">Week</button>
    <button type="submit" name="period" value="month"   class="af-btn<?= $activePeriod==='month'   ? ' af-btn-active' : '' ?>">Month</button>
    <button type="submit" name="period" value="alltime" class="af-btn<?= $activePeriod==='alltime' ? ' af-btn-active' : '' ?>">All Time</button>
  </div>
  <div class="af-custom">
    <input type="date" name="from" id="af-from" class="form-control form-control-sm"
           value="<?= htmlspecialchars($rangeFrom) ?>">
    <span style="color:var(--text-light);font-size:13px;">to</span>
    <input type="date" name="to" id="af-to" class="form-control form-control-sm"
           value="<?= htmlspecialchars($rangeTo) ?>">
    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
  </div>
</form>

<div class="row g-3 mb-4">

  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(59,130,246,.12);color:#3b82f6;">
        <i class="fa-solid fa-receipt"></i>
      </div>
      <div class="stat-value"><?= $ordersCount ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(16,185,129,.12);color:var(--green);">
        <i class="fa-solid fa-dollar-sign"></i>
      </div>
      <div class="stat-value">$<?= number_format($revenueTotal, 2) ?></div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(239,68,68,.12);color:var(--red);">
        <i class="fa-solid fa-arrow-trend-down"></i>
      </div>
      <div class="stat-value">$<?= number_format($expenseTotal, 2) ?></div>
      <div class="stat-label">Total Expenses</div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="stat-card">
      <span class="stat-badge badge-yellow">Active</span>
      <div class="stat-icon" style="background:rgba(245,158,11,.12);color:var(--yellow);">
        <i class="fa-solid fa-hourglass-half"></i>
      </div>
      <div class="stat-value"><?= $pendingOrders ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
  </div>

</div>

<div class="row g-3 mb-4">

  <div class="col-md-7">
    <div class="section-card">
      <div class="section-card-header">
        <h2>Revenue Over Time</h2>
        <span class="af-range-label"><?= $isAllTime ? 'All Time' : htmlspecialchars($rangeFrom) . ' – ' . htmlspecialchars($rangeTo) ?></span>
      </div>
      <div class="section-card-body">
        <div style="position:relative;height:160px;">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="section-card h-100">
      <div class="section-card-header">
        <h2>Quick Actions</h2>
      </div>
      <div class="section-card-body">
        <div class="row g-2">
          <div class="col-6">
            <a href="/orders.php" class="btn btn-outline-secondary w-100 text-start py-3">
              <i class="fa-solid fa-plus me-2" style="color:var(--accent);"></i>New Order
            </a>
          </div>
          <div class="col-6">
            <a href="/bill.php" class="btn btn-outline-secondary w-100 text-start py-3">
              <i class="fa-solid fa-file-invoice me-2" style="color:var(--accent);"></i>Generate Bill
            </a>
          </div>
          <div class="col-6">
            <a href="/kitchen.php" class="btn btn-outline-secondary w-100 text-start py-3">
              <i class="fa-solid fa-kitchen-set me-2" style="color:var(--accent);"></i>Kitchen View
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Recent Orders ─────────────────────────────────── -->
<div class="section-card mb-4">
  <div class="section-card-header">
    <h2>Recent Orders</h2>
    <a href="/bill.php" class="btn btn-sm btn-outline-secondary">View All</a>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Table</th>
          <th>Items</th>
          <th>Amount</th>
          <th>Time</th>
          <th>Status</th>
          <th>Server</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td>
            <?php if (in_array($o['status'], ['Ready','Paid'])): ?>
              <a href="/bill-view.php?order_id=<?= (int)$o['id'] ?>" class="fw-bold" style="color:var(--accent);text-decoration:none;">#<?= (int)$o['id'] ?></a>
            <?php else: ?>
              <strong>#<?= (int)$o['id'] ?></strong>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($o['table_number'] ?? 'Takeaway') ?></td>
          <td><?= (int)$o['item_count'] ?> items</td>
          <td>$<?= number_format($o['amount'], 2) ?></td>
          <td><?= time_ago($o['created_at']) ?></td>
          <td>
            <span class="status-badge <?= status_class($o['status']) ?>">
              <?= htmlspecialchars($o['status']) ?>
            </span>
          </td>
          <td><?= htmlspecialchars($o['server_name']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3 mb-4">

  <!-- Order Type Donut -->
  <div class="col-md-4">
    <div class="section-card h-100">
      <div class="section-card-header">
        <h2>Order Types</h2>
        <span class="af-range-label"><?= $isAllTime ? 'All Time' : htmlspecialchars($rangeFrom) . ' – ' . htmlspecialchars($rangeTo) ?></span>
      </div>
      <div class="section-card-body d-flex flex-column align-items-center justify-content-center" style="min-height:200px;">
        <canvas id="orderTypeChart" style="max-height:180px;"></canvas>
      </div>
    </div>
  </div>

  <!-- Payment Method Donut -->
  <div class="col-md-4">
    <div class="section-card h-100">
      <div class="section-card-header">
        <h2>Payment Methods</h2>
        <span class="af-range-label"><?= $isAllTime ? 'All Time' : htmlspecialchars($rangeFrom) . ' – ' . htmlspecialchars($rangeTo) ?></span>
      </div>
      <div class="section-card-body d-flex flex-column align-items-center justify-content-center" style="min-height:200px;">
        <canvas id="payMethodChart" style="max-height:180px;"></canvas>
      </div>
    </div>
  </div>

  <!-- Top Items Bar -->
  <div class="col-md-4">
    <div class="section-card h-100">
      <div class="section-card-header">
        <h2>Top 5 Items</h2>
        <span class="af-range-label"><?= $isAllTime ? 'All Time' : htmlspecialchars($rangeFrom) . ' – ' . htmlspecialchars($rangeTo) ?></span>
      </div>
      <div class="section-card-body" style="min-height:200px;">
        <canvas id="topItemsChart"></canvas>
      </div>
    </div>
  </div>

</div>

<style>
.analytics-filter {
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  background:var(--bg-card); border:1px solid var(--border);
  border-radius:var(--radius); padding:12px 18px;
}
.af-label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-light); white-space:nowrap; }
.af-quick { display:flex; gap:6px; }
.af-btn {
  padding:5px 14px; border-radius:var(--radius-sm); border:1.5px solid var(--border);
  background:var(--bg-main); font-size:12px; font-weight:600; color:var(--text-secondary);
  cursor:pointer; transition:all .15s;
}
.af-btn:hover { border-color:var(--accent); color:var(--accent); }
.af-btn-active { border-color:var(--accent) !important; color:var(--accent) !important; background:rgba(232,93,4,.08) !important; }
.af-custom { display:flex; align-items:center; gap:8px; margin-left:auto; }
.af-range-label { font-size:11px; color:var(--text-light); white-space:nowrap; }
</style>

<!-- Chart.js data from PHP -->
<script>
const revenueLabels = <?= json_encode(array_column($revenuePerDay, 'label')) ?>;
const revenueValues = <?= json_encode(array_column($revenuePerDay, 'value')) ?>;
const orderTypeLbls = <?= json_encode(['Dine-In', 'Takeaway']) ?>;
const orderTypeVals = <?= json_encode([$orderTypeCounts['dine-in'], $orderTypeCounts['takeaway']]) ?>;
const methodLbls    = <?= json_encode(['Cash', 'Card', 'Digital']) ?>;
const methodVals    = <?= json_encode([$methodCounts['cash'], $methodCounts['card'], $methodCounts['digital']]) ?>;
const topItemLbls   = <?= json_encode(array_column($topItems, 'name')) ?>;
const topItemVals   = <?= json_encode(array_map(fn($r) => (int)$r['total_qty'], $topItems)) ?>;
</script>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
