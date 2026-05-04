<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();

$items = $pdo->query(
    "SELECT id, name, description, price, category, is_available
     FROM menu_items ORDER BY category, name"
)->fetchAll();

$grouped = [];
foreach ($items as $item) {
    $grouped[$item['category'] ?: 'Uncategorised'][] = $item;
}

$pageTitle    = 'Menu Management';
$pageSubtitle = 'Add, edit, or disable menu items';
$activePage   = 'menu';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Add / Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="itemForm">
        <input type="hidden" name="id" id="modal-id" value="">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="modal-title">Add Menu Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pt-3">
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2">Name and a valid price are required.</div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Name *</label>
            <input type="text" name="name" id="modal-name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="modal-description" class="form-control" rows="2"></textarea>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Price (AUD) *</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="price" id="modal-price" class="form-control" step="0.01" min="0.01" required>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" name="category" id="modal-category" class="form-control" placeholder="e.g. Mains">
            </div>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_available" id="modal-available" checked>
            <label class="form-check-label" for="modal-available">Available on menu</label>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <?php if (isset($_GET['saved'])): ?>
      <div class="alert alert-success mb-0 py-2 px-3 d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-check-circle"></i> Menu item saved.
      </div>
    <?php endif; ?>
  </div>
  <button class="btn btn-primary" onclick="openAddModal()">
    <i class="fa-solid fa-plus me-2"></i>Add Menu Item
  </button>
</div>

<!-- Menu grouped by category -->
<?php foreach ($grouped as $category => $catItems): ?>
<div class="section-card mb-3">
  <div class="section-card-header">
    <h2>
      <?= htmlspecialchars($category) ?>
      <span class="ms-2 fw-normal" style="font-size:13px;color:var(--text-secondary);"><?= count($catItems) ?> items</span>
    </h2>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Description</th>
          <th>Price</th>
          <th>Status</th>
          <th style="width:100px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($catItems as $it): ?>
        <tr>
          <td><strong><?= htmlspecialchars($it['name']) ?></strong></td>
          <td style="color:var(--text-secondary);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= htmlspecialchars($it['description'] ?? '') ?>
          </td>
          <td><strong>$<?= number_format($it['price'], 2) ?></strong></td>
          <td>
            <?php if ($it['is_available']): ?>
              <span class="status-badge status-ready">Available</span>
            <?php else: ?>
              <span class="status-badge" style="background:rgba(107,114,128,.1);color:#6b7280;">Unavailable</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-secondary me-1" title="Edit"
              onclick="openEditModal(<?= htmlspecialchars(json_encode($it), ENT_QUOTES) ?>)">
              <i class="fa-solid fa-pen"></i>
            </button>
            <form method="POST" action="/menu-toggle.php" class="d-inline">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button type="submit"
                class="btn btn-sm <?= $it['is_available'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                title="<?= $it['is_available'] ? 'Disable' : 'Enable' ?>">
                <i class="fa-solid fa-<?= $it['is_available'] ? 'eye-slash' : 'eye' ?>"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($items)): ?>
<div class="section-card">
  <div class="section-card-body text-center py-5">
    <i class="fa-solid fa-book-open fa-3x mb-3 d-block" style="color:var(--border)"></i>
    <p class="text-muted mb-3">No menu items yet.</p>
    <button class="btn btn-primary" onclick="openAddModal()">Add your first item</button>
  </div>
</div>
<?php endif; ?>

<script>
const itemForm = document.getElementById('itemForm');

function openAddModal() {
  itemForm.action = '/menu-create.php';
  document.getElementById('modal-id').value          = '';
  document.getElementById('modal-title').textContent = 'Add Menu Item';
  document.getElementById('modal-name').value        = '';
  document.getElementById('modal-description').value = '';
  document.getElementById('modal-price').value       = '';
  document.getElementById('modal-category').value    = '';
  document.getElementById('modal-available').checked = true;
  new bootstrap.Modal(document.getElementById('itemModal')).show();
}

function openEditModal(item) {
  itemForm.action = '/menu-update.php';
  document.getElementById('modal-id').value          = item.id;
  document.getElementById('modal-title').textContent = 'Edit Menu Item';
  document.getElementById('modal-name').value        = item.name;
  document.getElementById('modal-description').value = item.description || '';
  document.getElementById('modal-price').value       = item.price;
  document.getElementById('modal-category').value    = item.category || '';
  document.getElementById('modal-available').checked = item.is_available == 1;
  new bootstrap.Modal(document.getElementById('itemModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
