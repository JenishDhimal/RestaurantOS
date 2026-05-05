<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'] / 100;

$pdo  = db();
$stmt = $pdo->prepare(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
            u.name AS server_name,
            mi.name AS item_name,
            oi.quantity, oi.unit_price,
            (oi.quantity * oi.unit_price) AS line_total
     FROM orders o
     JOIN users u        ON u.id = o.staff_id
     JOIN order_items oi ON oi.order_id = o.id
     JOIN menu_items mi  ON mi.id = oi.menu_item_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     ORDER BY o.created_at ASC, o.id, mi.name"
);
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_' . $from . '_to_' . $to . '.csv"');

$out = fopen('php://output', 'w');

$headers = ['Order #', 'Date', 'Type', 'Table / Ref', 'Status', 'Server', 'Item', 'Qty', 'Unit Price', 'Line Total'];
if ($gstEnabled) $headers[] = 'GST (' . $settings['gst_rate'] . '%)';
$headers[] = 'Total';
fputcsv($out, $headers);

$orderTotals = [];
foreach ($rows as $r) {
    $orderTotals[$r['id']] = ($orderTotals[$r['id']] ?? 0) + (float)$r['line_total'];
}

$printed = [];
foreach ($rows as $r) {
    $lineTotal = (float)$r['line_total'];
    $orderSub  = $orderTotals[$r['id']];
    $gst       = $gstEnabled ? round($orderSub * $gstRate, 2) : 0;
    $orderTotal = $orderSub + $gst;

    $row = [
        '#' . $r['id'],
        date('d M Y, g:i A', strtotime($r['created_at'])),
        $r['type'] === 'dine-in' ? 'Dine-In' : 'Takeaway',
        $r['table_number'] ?? '—',
        $r['status'],
        $r['server_name'],
        $r['item_name'],
        (int)$r['quantity'],
        number_format((float)$r['unit_price'], 2),
        number_format($lineTotal, 2),
    ];
    if ($gstEnabled) $row[] = !isset($printed[$r['id']]) ? number_format($gst, 2) : '';
    $row[] = !isset($printed[$r['id']]) ? number_format($orderTotal, 2) : '';
    $printed[$r['id']] = true;

    fputcsv($out, $row);
}

fclose($out);
exit;
