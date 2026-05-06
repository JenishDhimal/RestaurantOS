<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();

$roles = $pdo->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();
$users = $pdo->query(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.last_login, r.name AS role, r.id AS role_id
     FROM users u JOIN roles r ON r.id = u.role_id ORDER BY r.id, u.name"
)->fetchAll();

$errorMessages = [
    'required'        => 'Name, email, and password are required.',
    'invalid_email'   => 'Invalid email address.',
    'short_password'  => 'Password must be at least 6 characters.',
    'duplicate_email' => 'A user with that email already exists.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';

$total  = count($users);
$active = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$admins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));

$pageTitle    = 'Staff Management';
$pageSubtitle = 'Manage user accounts and roles';
$activePage   = 'staff';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Add Staff Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="/staff-create.php">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Add Staff Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name *</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="04xxxxxxxx">
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Role *</label>
              <select name="role_id" class="form-select">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $r['name'] === 'staff' ? 'selected' : '' ?>>
                    <?= ucfirst(htmlspecialchars($r['name'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Password *</label>
              <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Add Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="/staff-update.php">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name *</label>
            <input type="text" name="name" id="edit-name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email *</label>
            <input type="email" name="email" id="edit-email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone</label>
            <input type="text" name="phone" id="edit-phone" class="form-control">
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Role</label>
              <select name="role_id" id="edit-role" class="form-select">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= ucfirst(htmlspecialchars($r['name'])) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" id="edit-status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password <small class="text-muted">(leave blank to keep current)</small></label>
            <input type="password" name="password" class="form-control" minlength="6" placeholder="••••••">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">Save Changes</button>
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
        Account saved.
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger mb-0 py-2 px-3">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="fa-solid fa-plus me-2"></i>Add Staff
  </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="fa-solid fa-users"></i></div>
      <div class="stat-value"><?= $total ?></div>
      <div class="stat-label">Total Accounts</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(16,185,129,.1);color:var(--green);"><i class="fa-solid fa-circle-check"></i></div>
      <div class="stat-value"><?= $active ?></div>
      <div class="stat-label">Active Accounts</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(232,93,4,.1);color:var(--accent);"><i class="fa-solid fa-user-shield"></i></div>
      <div class="stat-value"><?= $admins ?></div>
      <div class="stat-label">Admins</div>
    </div>
  </div>
</div>

<!-- Staff Table -->
<div class="section-card">
  <div class="section-card-header">
    <h2>All Accounts</h2>
    <span style="background:rgba(59,130,246,.1);color:#3b82f6;font-size:12px;font-weight:700;padding:3px 10px;border-radius:12px;">
      <?= $total ?> users
    </span>
  </div>
  <div style="overflow-x:auto;">
    <table class="ros-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Status</th>
          <th>Last Login</th>
          <th style="width:80px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:white;
                          display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                <?= strtoupper(mb_substr($u['name'], 0, 1)) ?>
              </div>
              <strong><?= htmlspecialchars($u['name']) ?></strong>
              <?php if ($u['id'] == $_SESSION['user_id']): ?>
                <span style="font-size:10px;background:rgba(59,130,246,.1);color:#3b82f6;padding:1px 6px;border-radius:8px;font-weight:700;">You</span>
              <?php endif; ?>
            </div>
          </td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td>
            <span class="status-badge" style="background:rgba(232,93,4,.08);color:var(--accent);">
              <?= ucfirst(htmlspecialchars($u['role'])) ?>
            </span>
          </td>
          <td>
            <?php if ($u['status'] === 'active'): ?>
              <span class="status-badge status-ready">Active</span>
            <?php else: ?>
              <span class="status-badge" style="background:rgba(107,114,128,.1);color:#6b7280;">Inactive</span>
            <?php endif; ?>
          </td>
          <td style="color:var(--text-secondary);font-size:13px;">
            <?= $u['last_login'] ? date('d M Y, g:i A', strtotime($u['last_login'])) : '—' ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-secondary"
              onclick="openEdit(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
              <i class="fa-solid fa-pen"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openEdit(u) {
  document.getElementById('edit-id').value     = u.id;
  document.getElementById('edit-name').value   = u.name;
  document.getElementById('edit-email').value  = u.email;
  document.getElementById('edit-phone').value  = u.phone || '';
  document.getElementById('edit-role').value   = u.role_id;
  document.getElementById('edit-status').value = u.status;
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
