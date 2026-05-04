<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /kitchen.php');
    exit;
}

$orderId   = (int)($_POST['order_id']   ?? 0);
$newStatus = $_POST['new_status'] ?? '';

$validTransitions = [
    'Received'  => 'Preparing',
    'Preparing' => 'Ready',
    'Ready'     => 'Paid',
];

if ($orderId <= 0 || !in_array($newStatus, array_values($validTransitions), true)) {
    header('Location: /kitchen.php');
    exit;
}

$pdo  = db();
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || ($validTransitions[$order['status']] ?? null) !== $newStatus) {
    header('Location: /kitchen.php');
    exit;
}

$pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);

header('Location: /kitchen.php');
exit;
