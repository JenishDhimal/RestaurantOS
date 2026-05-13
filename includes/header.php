<?php

$user = current_user();
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $user['name'])))));
$today = date('D, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — RestaurantOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">

  <nav class="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon"><i class="fa-solid fa-utensils"></i></span>
      <span class="brand-name">RestaurantOS</span>
      <span class="brand-sub">Restaurant Management</span>
    </div>

    <span class="role-badge"><?= strtoupper(htmlspecialchars($user['role'])) ?></span>

    <p class="sidebar-section-label">Main</p>
    <ul class="sidebar-nav">
      <?php if (is_admin()): ?>
      <li><a href="/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a></li>
      <?php endif; ?>
      <?php if (!is_kitchen()): ?>
      <li><a href="/orders.php" class="<?= $activePage === 'orders' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-list"></i> Order Placement
      </a></li>
      <?php endif; ?>
      <li><a href="/kitchen.php" class="<?= $activePage === 'kitchen' ? 'active' : '' ?>">
        <i class="fa-solid fa-kitchen-set"></i> Kitchen Display
      </a></li>
      <?php if (!is_kitchen()): ?>
      <li><a href="/bill.php" class="<?= $activePage === 'billing' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Billing
      </a></li>
      <?php endif; ?>
    </ul>

    <p class="sidebar-section-label"><?= is_admin() ? 'Management' : 'More' ?></p>
    <ul class="sidebar-nav">
      <?php if (!is_kitchen()): ?>
      <li><a href="/payments.php" class="<?= $activePage === 'payments' ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i> Payments
      </a></li>
      <li><a href="/expenses.php" class="<?= $activePage === 'expenses' ? 'active' : '' ?>">
        <i class="fa-solid fa-arrow-trend-down"></i> Expenses
      </a></li>
      <?php endif; ?>
      <?php if (is_admin()): ?>
      <li><a href="/menu.php" class="<?= $activePage === 'menu' ? 'active' : '' ?>">
        <i class="fa-solid fa-book-open"></i> Menu
      </a></li>
      <li><a href="/staff.php" class="<?= $activePage === 'staff' ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> Staff
      </a></li>
      <li><a href="/settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">
        <i class="fa-solid fa-gear"></i> Settings
      </a></li>
      <?php endif; ?>
    </ul>

    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role"><?= ucfirst(htmlspecialchars($user['role'])) ?></div>
      </div>
      <form method="POST" action="/logout.php" style="margin:0">
        <button type="submit" class="btn-logout" title="Logout">
          <i class="fa-solid fa-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </nav>

  <div class="main-content">
    <header class="topbar">
      <div>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="topbar-sub"><?= htmlspecialchars($pageSubtitle ?? '') ?></div>
      </div>
      <div class="topbar-actions">
        <span class="topbar-date"><?= htmlspecialchars($today) ?></span>
      </div>
    </header>

    <div class="page-content">
