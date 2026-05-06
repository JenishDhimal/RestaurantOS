<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo     = db();
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header('Location: /bill.php');
    exit;
}

$order = $pdo->prepare(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at, u.name AS server_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.staff_id
     WHERE o.id = ? AND o.status IN ('Ready','Paid')"
);
$order->execute([$orderId]);
$order = $order->fetch();

if (!$order) {
    header('Location: /bill.php');
    exit;
}

$items = $pdo->prepare(
    "SELECT mi.name, oi.quantity, oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
     FROM order_items oi
     JOIN menu_items mi ON mi.id = oi.menu_item_id
     WHERE oi.order_id = ?
     ORDER BY mi.name"
);
$items->execute([$orderId]);
$items = $items->fetchAll();

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];
$subtotal   = array_sum(array_column($items, 'line_total'));
$gst        = $gstEnabled ? round($subtotal * $gstRate / 100, 2) : 0;
$total      = $subtotal + $gst;

$pageTitle    = 'Bill #' . $orderId;
$pageSubtitle = 'Order receipt';
$activePage   = 'billing';
require_once __DIR__ . '/includes/header.php';
?>
