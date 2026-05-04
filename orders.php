<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo        = db();
$tableCount = get_settings()['table_count'];

$activeRows = $pdo->query(
    "SELECT table_number, id FROM orders
     WHERE type = 'dine-in' AND status IN ('Received','Preparing','Ready')
     ORDER BY created_at DESC"
)->fetchAll();
$activeOrders = [];
foreach ($activeRows as $row) {
    if (!isset($activeOrders[$row['table_number']])) {
        $activeOrders[$row['table_number']] = (int)$row['id'];
    }
}

$menuItems = $pdo->query(
    "SELECT id, name, description, price, category
     FROM menu_items WHERE is_available = 1 ORDER BY category, name"
)->fetchAll();

$grouped = [];
foreach ($menuItems as $item) {
    $grouped[$item['category'] ?: 'Other'][] = $item;
}

$successOrderId = isset($_GET['success']) ? (int)$_GET['success'] : 0;

$pageTitle    = 'Place Order';
$pageSubtitle = 'Place a dine-in or takeaway order';
$activePage   = 'orders';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($successOrderId): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
  Order <strong>#<?= $successOrderId ?></strong> sent to kitchen.
  <a href="/kitchen.php" class="alert-link ms-2">View kitchen →</a>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="/orders-create.php" id="orderForm">
<input type="hidden" name="existing_order_id" id="existing-order-id" value="">
<div class="row g-4">

  <!-- Left: type + menu -->
  <div class="col-lg-8">

    <div class="section-card mb-4">
      <div class="section-card-body">
        <p class="mb-3" style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-secondary);">Order Type</p>
        <div class="d-flex gap-3 flex-wrap">
          <label class="order-type-btn active" id="btn-dinein">
            <input type="radio" name="type" value="dine-in" checked onchange="switchType('dine-in')">
            <i class="fa-solid fa-chair"></i>
            <div>
              <div class="otb-label">Dine-In</div>
              <div class="otb-sub">Assign a table number</div>
            </div>
          </label>
          <label class="order-type-btn" id="btn-takeaway">
            <input type="radio" name="type" value="takeaway" onchange="switchType('takeaway')">
            <i class="fa-solid fa-bag-shopping"></i>
            <div>
              <div class="otb-label">Takeaway</div>
              <div class="otb-sub">Auto reference number</div>
            </div>
          </label>
        </div>

        <div id="field-table" class="mt-3">
          <label class="form-label fw-semibold">Table Number</label>
          <select name="table_number" class="form-select" style="max-width:180px;" onchange="checkActiveOrder()">
            <?php for ($i = 1; $i <= $tableCount; $i++): ?>
              <option value="Table <?= $i ?>">Table <?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div id="active-order-banner" class="d-none mt-3 p-3 rounded-2"
             style="background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.3);">
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
            <i class="fa-solid fa-triangle-exclamation" style="color:#ca8a04;margin-right:6px;"></i>
            Table <span id="aob-table"></span> has an active order — <strong>Order #<span id="aob-num"></span></strong>
          </div>
          <div class="d-flex gap-2 mt-2">
            <button type="button" onclick="setAddMode()" id="btn-add-mode" class="btn btn-sm btn-warning">
              Add to Order #<span id="aob-num2"></span>
            </button>
            <button type="button" onclick="setNewMode()" id="btn-new-mode" class="btn btn-sm btn-outline-secondary">
              Start New Order
            </button>
          </div>
        </div>

        <div id="field-takeaway" class="mt-3 d-none">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2"
               style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);color:#3b82f6;font-size:13px;">
            A unique reference number will be assigned automatically.
          </div>
        </div>
      </div>
    </div>

    <?php foreach ($grouped as $category => $items): ?>
    <div class="section-card mb-3">
      <div class="section-card-header">
        <h2><?= htmlspecialchars($category) ?></h2>
      </div>
      <div class="section-card-body">
        <div class="row g-3">
          <?php foreach ($items as $item): ?>
          <div class="col-md-6">
            <div class="menu-item-card"
                 id="card-<?= $item['id'] ?>"
                 data-id="<?= $item['id'] ?>"
                 data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                 data-price="<?= $item['price'] ?>">
              <div class="mic-body">
                <div class="mic-name"><?= htmlspecialchars($item['name']) ?></div>
                <?php if ($item['description']): ?>
                  <div class="mic-desc"><?= htmlspecialchars($item['description']) ?></div>
                <?php endif; ?>
                <div class="mic-price">$<?= number_format($item['price'], 2) ?></div>
              </div>
              <div class="qty-control">
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                <span class="qty-val" id="qty-<?= $item['id'] ?>">0</span>
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
              </div>
              <input type="hidden" name="items[<?= $item['id'] ?>]" id="inp-<?= $item['id'] ?>" value="0">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($menuItems)): ?>
    <div class="section-card">
      <div class="section-card-body text-center py-5">
        <p class="text-muted">No menu items available.</p>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right: summary -->
  <div class="col-lg-4">
    <div class="section-card" style="position:sticky;top:20px;">
      <div class="section-card-header">
        <h2>Order Summary</h2>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAll()">Clear</button>
      </div>
      <div class="section-card-body">
        <div id="summary-empty" class="text-center py-4" style="color:var(--text-light);font-size:13px;">
          No items selected
        </div>
        <div id="summary-lines"></div>
        <div id="summary-footer" class="d-none">
          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold" style="font-size:15px;">Total</span>
            <span id="summary-total" class="fw-bold" style="font-size:20px;color:var(--accent);">$0.00</span>
          </div>
        </div>
        <button type="submit" id="submit-btn" class="btn btn-primary w-100 py-3 fw-bold" disabled>
          Send to Kitchen
        </button>
      </div>
    </div>
  </div>

</div>
</form>

<style>
.order-type-btn {
  display:flex; align-items:center; gap:12px;
  padding:14px 20px; border-radius:var(--radius-sm);
  border:2px solid var(--border); cursor:pointer;
  background:white; transition:all .15s; user-select:none; flex:1; min-width:160px;
}
.order-type-btn input { display:none; }
.order-type-btn i { font-size:22px; color:var(--text-light); transition:color .15s; }
.order-type-btn.active { border-color:var(--accent); background:rgba(232,93,4,.05); }
.order-type-btn.active i { color:var(--accent); }
.otb-label { font-weight:700; font-size:14px; }
.otb-sub   { font-size:12px; color:var(--text-secondary); margin-top:1px; }

.menu-item-card {
  display:flex; align-items:center; gap:12px;
  padding:12px 14px; border-radius:var(--radius-sm);
  border:1.5px solid var(--border); background:white;
  transition:border-color .15s, box-shadow .15s;
}
.menu-item-card.selected {
  border-color:var(--accent); box-shadow:0 0 0 3px rgba(232,93,4,.08);
}
.mic-body  { flex:1; min-width:0; }
.mic-name  { font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mic-desc  { font-size:11px; color:var(--text-secondary); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mic-price { font-weight:700; color:var(--accent); font-size:13px; margin-top:4px; }

.qty-control { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.qty-btn {
  width:26px; height:26px; border-radius:50%;
  border:1.5px solid var(--border); background:white;
  font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center;
  font-weight:700; line-height:1; transition:all .1s; padding:0;
}
.qty-btn:hover { border-color:var(--accent); color:var(--accent); background:rgba(232,93,4,.05); }
.qty-val { min-width:18px; text-align:center; font-weight:700; font-size:14px; }

.summary-line {
  display:flex; justify-content:space-between; align-items:center;
  padding:7px 0; font-size:13px; border-bottom:1px solid var(--border);
}
.summary-line:last-child { border-bottom:none; }
.sl-qty { color:var(--text-secondary); font-size:12px; margin-left:4px; }
</style>

<script>
const cart = {};
const activeOrders = <?= json_encode($activeOrders) ?>;

function changeQty(id, delta) {
  cart[id] = Math.max(0, (cart[id] || 0) + delta);
  document.getElementById('qty-' + id).textContent = cart[id];
  document.getElementById('inp-' + id).value = cart[id];
  document.getElementById('card-' + id).classList.toggle('selected', cart[id] > 0);
  renderSummary();
}

function clearAll() {
  Object.keys(cart).forEach(id => {
    cart[id] = 0;
    document.getElementById('qty-' + id).textContent = '0';
    document.getElementById('inp-' + id).value = '0';
    document.getElementById('card-' + id).classList.remove('selected');
  });
  renderSummary();
}

function renderSummary() {
  const cards = document.querySelectorAll('.menu-item-card');
  let html = '', total = 0, hasItems = false;
  cards.forEach(card => {
    const id  = card.dataset.id;
    const qty = cart[id] || 0;
    if (qty < 1) return;
    hasItems = true;
    const price = parseFloat(card.dataset.price);
    const sub   = price * qty;
    total += sub;
    html += `<div class="summary-line">
      <span>${card.dataset.name}<span class="sl-qty">×${qty}</span></span>
      <strong>$${sub.toFixed(2)}</strong>
    </div>`;
  });
  document.getElementById('summary-empty').classList.toggle('d-none', hasItems);
  document.getElementById('summary-lines').innerHTML = html;
  document.getElementById('summary-footer').classList.toggle('d-none', !hasItems);
  document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
  document.getElementById('submit-btn').disabled = !hasItems;
}

function switchType(type) {
  document.getElementById('field-table').classList.toggle('d-none',    type !== 'dine-in');
  document.getElementById('field-takeaway').classList.toggle('d-none', type !== 'takeaway');
  document.getElementById('btn-dinein').classList.toggle('active',     type === 'dine-in');
  document.getElementById('btn-takeaway').classList.toggle('active',   type === 'takeaway');
  if (type === 'dine-in') {
    checkActiveOrder();
  } else {
    document.getElementById('active-order-banner').classList.add('d-none');
    resetOrderMode();
  }
}

function checkActiveOrder() {
  const table   = document.querySelector('select[name="table_number"]').value;
  const orderId = activeOrders[table];
  const banner  = document.getElementById('active-order-banner');
  if (orderId) {
    document.getElementById('aob-table').textContent = table;
    document.getElementById('aob-num').textContent   = orderId;
    document.getElementById('aob-num2').textContent  = orderId;
    banner.classList.remove('d-none');
    setAddMode();
  } else {
    banner.classList.add('d-none');
    resetOrderMode();
  }
}

function setAddMode() {
  const orderId = document.getElementById('aob-num').textContent;
  document.getElementById('existing-order-id').value = orderId;
  document.getElementById('orderForm').action = '/order-items-add.php';
  document.getElementById('submit-btn').textContent = 'Add Items to Order #' + orderId;
  document.getElementById('btn-add-mode').className = 'btn btn-sm btn-warning';
  document.getElementById('btn-new-mode').className = 'btn btn-sm btn-outline-secondary';
}

function setNewMode() {
  resetOrderMode();
  document.getElementById('btn-add-mode').className = 'btn btn-sm btn-outline-warning';
  document.getElementById('btn-new-mode').className = 'btn btn-sm btn-secondary';
}

function resetOrderMode() {
  document.getElementById('existing-order-id').value = '';
  document.getElementById('orderForm').action = '/orders-create.php';
  document.getElementById('submit-btn').textContent = 'Send to Kitchen';
}

document.addEventListener('DOMContentLoaded', checkActiveOrder);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
