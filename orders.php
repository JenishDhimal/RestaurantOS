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
