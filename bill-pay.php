<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_role('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /bill.php');
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);

if ($orderId > 0) {
    $pdo = db();
    $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE id = ? AND status = 'Ready'")
        ->execute([$orderId]);
}

header('Location: /bill-view.php?order_id=' . $orderId);
exit;
