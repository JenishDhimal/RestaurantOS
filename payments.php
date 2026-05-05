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
