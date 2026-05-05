<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo        = db();
$is_admin   = is_admin();
$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];

$readyOrders = $pdo->query(
    "SELECT o.id, o.type, o.table_number, o.created_at,
            COUNT(oi.id) AS item_count,
            SUM(oi.quantity * oi.unit_price) AS subtotal
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     WHERE o.status = 'Ready'
     GROUP BY o.id
     ORDER BY o.created_at ASC"
)->fetchAll();

$errorKey = $_GET['error'] ?? null;

$pageTitle    = 'Payments';
$pageSubtitle = 'Record payment for ready orders';
$activePage   = 'payments';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errorKey): ?>
<div class="alert d-flex align-items-center gap-2 mb-4"
     style="background:rgba(239,68,68,.1);color:var(--red);border:none;border-radius:var(--radius);">
  <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
  <div>
    <?php if ($errorKey === 'notready'): ?>
      That order is no longer in Ready status.
    <?php else: ?>
      Something went wrong. Please try again.
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── Payment Entry ─────────────────────────────────── -->
<div class="section-card mb-4">
  <div class="section-card-header">
    <h2>Record Payment</h2>
    <span style="font-size:13px;color:var(--text-secondary);">
      <?= count($readyOrders) ?> order<?= count($readyOrders) !== 1 ? 's' : '' ?> ready
    </span>
  </div>
  <div class="section-card-body">
    <?php if (empty($readyOrders)): ?>
      <div class="pay-empty">
        <i class="fa-solid fa-hourglass-half"></i>
        <p>No orders are Ready for payment right now.</p>
      </div>
    <?php else: ?>
      <form method="POST" action="/payments-create.php" id="payForm">
        <input type="hidden" name="order_id" id="selected-order-id">

        <p class="pay-select-label">Select an order to pay:</p>
        <div class="pay-grid" id="orderGrid">
          <?php foreach ($readyOrders as $o):
            $sub   = (float)$o['subtotal'];
            $total = $gstEnabled ? round($sub * (1 + $gstRate / 100), 2) : $sub;
            $ref   = $o['type'] === 'dine-in'
                ? htmlspecialchars($o['table_number'])
                : 'Takeaway';
          ?>
          <div class="pay-card" tabindex="0"
               data-id="<?= (int)$o['id'] ?>"
               data-total="<?= number_format($total, 2) ?>">
            <div class="pay-card-top">
              <span class="pay-order-num">#<?= (int)$o['id'] ?></span>
              <span class="pay-type-badge <?= $o['type'] === 'dine-in' ? 'ptb-dinein' : 'ptb-takeaway' ?>">
                <?= $o['type'] === 'dine-in' ? 'Dine-In' : 'Takeaway' ?>
              </span>
            </div>
            <div class="pay-ref"><?= $ref ?></div>
            <div class="pay-items"><?= (int)$o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></div>
            <div class="pay-total">$<?= number_format($total, 2) ?></div>
            <div class="pay-time"><?= time_ago($o['created_at']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="pay-bottom" id="payBottom" style="display:none;">
          <div class="pay-summary">
            <span class="pay-summary-label">Amount Due</span>
            <span class="pay-summary-amount" id="displayTotal">$0.00</span>
            <span class="pay-summary-label" style="font-size:11px;margin-top:2px;">incl. 10% GST</span>
          </div>

          <div class="pay-method-group">
            <label class="pay-method-label">Payment Method</label>
            <div class="pay-methods">
              <label class="pay-method-opt">
                <input type="radio" name="method" value="cash" required>
                <span><i class="fa-solid fa-money-bill-wave"></i> Cash</span>
              </label>
              <label class="pay-method-opt">
                <input type="radio" name="method" value="card">
                <span><i class="fa-solid fa-credit-card"></i> Card</span>
              </label>
              <label class="pay-method-opt">
                <input type="radio" name="method" value="digital">
                <span><i class="fa-brands fa-google-pay"></i> Digital</span>
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-pay" id="submitBtn" disabled>
            <i class="fa-solid fa-check me-2"></i>Confirm Payment
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>


<style>
.pay-empty {
  text-align:center; padding:40px 24px; color:var(--text-light);
}
.pay-empty i { font-size:28px; display:block; margin-bottom:8px; }
.pay-empty p { margin:0; font-size:14px; }

.pay-select-label {
  font-size:13px; font-weight:600; color:var(--text-secondary); margin-bottom:14px;
}

.pay-grid {
  display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; margin-bottom:24px;
}

.pay-card {
  background:var(--bg-main); border:2px solid var(--border);
  border-radius:var(--radius); padding:16px; cursor:pointer;
  transition:all .15s; outline:none;
}
.pay-card:hover { border-color:var(--accent); box-shadow:0 0 0 3px rgba(232,93,4,.08); }
.pay-card.selected {
  border-color:var(--accent); background:rgba(232,93,4,.04);
  box-shadow:0 0 0 3px rgba(232,93,4,.12);
}

.pay-card-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.pay-order-num { font-family:'Playfair Display',serif; font-size:18px; font-weight:800; color:var(--text-primary); }
.pay-type-badge { font-size:10px; font-weight:700; padding:2px 8px; border-radius:8px; }
.ptb-dinein  { background:rgba(59,130,246,.1); color:#3b82f6; }
.ptb-takeaway{ background:rgba(139,92,246,.1); color:#7c3aed; }

.pay-ref   { font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:2px; }
.pay-items { font-size:12px; color:var(--text-light); margin-bottom:8px; }
.pay-total { font-size:20px; font-weight:800; color:var(--accent); margin-bottom:2px; }
.pay-time  { font-size:11px; color:var(--text-light); }

.pay-bottom {
  display:flex; align-items:center; gap:24px; flex-wrap:wrap;
  background:var(--bg-main); border-radius:var(--radius); padding:20px 24px;
  border:1.5px solid var(--border); margin-top:4px;
}

.pay-summary { display:flex; flex-direction:column; gap:2px; min-width:120px; }
.pay-summary-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light); }
.pay-summary-amount { font-family:'Playfair Display',serif; font-size:28px; font-weight:800; color:var(--text-primary); line-height:1.1; }

.pay-method-group { flex:1; min-width:240px; }
.pay-method-label { font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:10px; text-transform:uppercase; letter-spacing:.05em; }
.pay-methods { display:flex; gap:10px; }

.pay-method-opt { cursor:pointer; }
.pay-method-opt input { display:none; }
.pay-method-opt span {
  display:flex; align-items:center; gap:6px;
  padding:9px 16px; border-radius:var(--radius-sm);
  border:1.5px solid var(--border); font-size:13px; font-weight:600;
  color:var(--text-secondary); background:var(--bg-card); transition:all .15s;
}
.pay-method-opt input:checked + span {
  border-color:var(--accent); background:rgba(232,93,4,.08); color:var(--accent);
}
.pay-method-opt span:hover { border-color:var(--accent); }

.btn-pay { padding:10px 28px; font-weight:700; white-space:nowrap; }
.btn-pay:disabled { opacity:.45; cursor:not-allowed; }

</style>

<script>
(function () {
  const grid     = document.getElementById('orderGrid');
  const payBottom= document.getElementById('payBottom');
  const hiddenId = document.getElementById('selected-order-id');
  const display  = document.getElementById('displayTotal');
  const submitBtn= document.getElementById('submitBtn');
  const methods  = document.querySelectorAll('input[name="method"]');

  if (!grid) return;

  let selectedCard = null;

  grid.querySelectorAll('.pay-card').forEach(card => {
    card.addEventListener('click', () => selectCard(card));
    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') selectCard(card); });
  });

  methods.forEach(m => m.addEventListener('change', updateSubmit));

  function selectCard(card) {
    if (selectedCard) selectedCard.classList.remove('selected');
    card.classList.add('selected');
    selectedCard = card;
    hiddenId.value = card.dataset.id;
    display.textContent = '$' + card.dataset.total;
    payBottom.style.display = 'flex';
    updateSubmit();
  }

  function updateSubmit() {
    submitBtn.disabled = !(selectedCard && document.querySelector('input[name="method"]:checked'));
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
