<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

$pdo = db();

$ordersToday = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$ordersYesterday = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY"
)->fetchColumn();
$ordersDelta = $ordersYesterday > 0
    ? round((($ordersToday - $ordersYesterday) / $ordersYesterday) * 100)
    : null;

$revenueToday = $pdo->query(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE DATE(o.created_at) = CURDATE()"
)->fetchColumn();

$revenueYesterday = $pdo->query(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE DATE(o.created_at) = CURDATE() - INTERVAL 1 DAY"
)->fetchColumn();
$revenueDelta = $revenueYesterday > 0
    ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100)
    : null;

$pendingOrders = $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status IN ('Received','Preparing')"
)->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT o.id, o.type, o.table_number, o.status, o.created_at,
            COUNT(oi.id)                              AS item_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS amount,
            u.name                                    AS server_name
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     LEFT JOIN users u       ON u.id = o.staff_id
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10"
)->fetchAll();

$weekRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $sum   = $pdo->prepare(
        "SELECT COALESCE(SUM(p.amount), 0)
         FROM payments p
         JOIN orders o ON p.order_id = o.id
         WHERE DATE(o.created_at) = ?"
    );
    $sum->execute([$date]);
    $weekRevenue[] = ['label' => $label, 'value' => (float)$sum->fetchColumn()];
}

$rangeFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-6 days'));
$rangeTo   = $_GET['to']   ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeFrom)) $rangeFrom = date('Y-m-d', strtotime('-6 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeTo))   $rangeTo   = date('Y-m-d');

$orderTypeRows = $pdo->prepare(
    "SELECT type, COUNT(*) AS cnt FROM orders
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY type"
);
$orderTypeRows->execute([$rangeFrom, $rangeTo]);
$orderTypeCounts = ['dine-in' => 0, 'takeaway' => 0];
foreach ($orderTypeRows->fetchAll() as $r) $orderTypeCounts[$r['type']] = (int)$r['cnt'];

$methodRows = $pdo->prepare(
    "SELECT p.method, COUNT(*) AS cnt
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY p.method"
);
$methodRows->execute([$rangeFrom, $rangeTo]);
$methodCounts = ['cash' => 0, 'card' => 0, 'digital' => 0];
foreach ($methodRows->fetchAll() as $r) $methodCounts[$r['method']] = (int)$r['cnt'];

$topItemRows = $pdo->prepare(
    "SELECT mi.name, SUM(oi.quantity) AS total_qty
     FROM order_items oi
     JOIN menu_items mi ON mi.id = oi.menu_item_id
     JOIN orders o ON o.id = oi.order_id
     WHERE DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY mi.id
     ORDER BY total_qty DESC
     LIMIT 5"
);
$topItemRows->execute([$rangeFrom, $rangeTo]);
$topItems = $topItemRows->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of today\'s restaurant operations';
$activePage   = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>