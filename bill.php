<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo = db();

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];

$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to']   ?? '');

$isFiltered = ($dateFrom !== '' || $dateTo !== '');

if ($isFiltered) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-29 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

    $stmt = $pdo->prepare(
        "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
                SUM(oi.quantity * oi.unit_price) AS subtotal
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN ? AND ?
         GROUP BY o.id, o.type, o.table_number, o.status, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 200"
    );
    $stmt->execute([$dateFrom, $dateTo]);
} else {
    $stmt = $pdo->query(
        "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
                SUM(oi.quantity * oi.unit_price) AS subtotal
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id, o.type, o.table_number, o.status, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 200"
    );
}
$orders = $stmt->fetchAll();

$pageTitle    = 'Billing';
$pageSubtitle = 'View and print order receipts';
$activePage   = 'billing';
require_once __DIR__ . '/includes/header.php';
?>
