<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo = db();

$rangeFrom    = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
$rangeTo      = $_GET['to']   ?? date('Y-m-d');
$isAllTime    = false;
$activePeriod = $_GET['period'] ?? '';

if (isset($_GET['period'])) {
    if ($_GET['period'] === 'today') {
        $rangeFrom = date('Y-m-d');
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'week') {
        $rangeFrom = date('Y-m-d', strtotime('-6 days'));
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'month') {
        $rangeFrom = date('Y-m-d', strtotime('-29 days'));
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'alltime') {
        $earliest  = $pdo->query("SELECT COALESCE(MIN(expense_date), CURDATE()) FROM expenses")->fetchColumn();
        $rangeFrom = $earliest;
        $rangeTo   = date('Y-m-d');
        $isAllTime = true;
    }
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeFrom)) $rangeFrom = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeTo))   $rangeTo   = date('Y-m-d');

$expenseRows = $pdo->prepare(
    "SELECT e.id, e.amount, e.category, e.note, e.expense_date, u.name AS recorded_by
     FROM expenses e
     JOIN users u ON u.id = e.recorded_by
     WHERE e.expense_date BETWEEN ? AND ?
     ORDER BY e.expense_date DESC, e.id DESC"
);
$expenseRows->execute([$rangeFrom, $rangeTo]);
$expenses = $expenseRows->fetchAll();

$totalExpenses = array_sum(array_column($expenses, 'amount'));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle    = 'Expenses';
$pageSubtitle = 'Track and manage operational expenses';
$activePage   = 'expenses';
require_once __DIR__ . '/includes/header.php';

$categories = ['Food & Supplies', 'Utilities', 'Wages & Salaries', 'Maintenance', 'Equipment', 'Marketing', 'Other'];
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Filter ────────────────────────────────────────── -->
<form method="GET" class="analytics-filter mb-3">
  <span class="af-label">Filter:</span>
  <div class="af-quick">
    <button type="submit" name="period" value="today"   class="af-btn<?= $activePeriod === 'today'   ? ' af-btn-active' : '' ?>">Today</button>
    <button type="submit" name="period" value="week"    class="af-btn<?= $activePeriod === 'week'    ? ' af-btn-active' : '' ?>">Week</button>
    <button type="submit" name="period" value="month"   class="af-btn<?= $activePeriod === 'month'   ? ' af-btn-active' : '' ?>">Month</button>
    <button type="submit" name="period" value="alltime" class="af-btn<?= $activePeriod === 'alltime' ? ' af-btn-active' : '' ?>">All Time</button>
  </div>
  <div class="af-custom">
    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($rangeFrom) ?>">
    <span style="color:var(--text-light);font-size:13px;">to</span>
    <input type="date" name="to"   class="form-control form-control-sm" value="<?= htmlspecialchars($rangeTo) ?>">
    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
  </div>
</form>

<!-- ── Record Expense ──────────────────────────────── -->
<div class="section-card mb-3">
  <div class="section-card-header">
    <h2>Record Expense</h2>
  </div>
  <div class="section-card-body">
    <form method="POST" action="/expenses-create.php" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label form-label-sm">Amount ($)</label>
        <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="0.00" required>
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm">Category</label>
        <select name="category" class="form-select form-select-sm" required>
          <option value="" disabled selected>Select…</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label form-label-sm">Note <span style="color:var(--text-light);font-weight:400;">(optional)</span></label>
        <input type="text" name="note" class="form-control form-control-sm" placeholder="Brief description…" maxlength="255">
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm">Date</label>
        <input type="date" name="expense_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fa-solid fa-plus me-1"></i> Save Expense
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Expenses List ───────────────────────────────── -->
<div class="section-card mb-4">
  <div class="section-card-header">
    <h2>Expenses</h2>
    <div style="display:flex;align-items:center;gap:16px;">
      <span class="af-range-label"><?= $isAllTime ? 'All Time' : htmlspecialchars($rangeFrom) . ' – ' . htmlspecialchars($rangeTo) ?></span>
      <span style="font-size:13px;font-weight:700;color:var(--red);">Total: $<?= number_format($totalExpenses, 2) ?></span>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Note</th>
          <th>Amount</th>
          <th>Recorded By</th>
          <?php if (is_admin()): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($expenses as $e): ?>
        <tr>
          <td><?= htmlspecialchars(date('d M Y', strtotime($e['expense_date']))) ?></td>
          <td>
            <span class="exp-category-badge"><?= htmlspecialchars($e['category']) ?></span>
          </td>
          <td style="color:var(--text-secondary);"><?= $e['note'] ? htmlspecialchars($e['note']) : '—' ?></td>
          <td><strong style="color:var(--red);">$<?= number_format($e['amount'], 2) ?></strong></td>
          <td><?= htmlspecialchars($e['recorded_by']) ?></td>
          <?php if (is_admin()): ?>
          <td>
            <form method="POST" action="/expenses-delete.php" onsubmit="return confirm('Delete this expense?')">
              <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" style="padding:2px 10px;font-size:12px;">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($expenses)): ?>
        <tr><td colspan="<?= is_admin() ? 6 : 5 ?>" class="text-center text-muted py-4">No expenses recorded for this period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
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
.af-btn:hover      { border-color:var(--accent); color:var(--accent); }
.af-btn-active     { border-color:var(--accent) !important; color:var(--accent) !important; background:rgba(232,93,4,.08) !important; }
.af-custom         { display:flex; align-items:center; gap:8px; margin-left:auto; }
.af-range-label    { font-size:11px; color:var(--text-light); white-space:nowrap; }
.exp-category-badge {
  display:inline-block; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:600;
  background:rgba(239,68,68,.10); color:var(--red);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
