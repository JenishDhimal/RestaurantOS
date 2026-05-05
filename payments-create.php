<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /payments.php');
    exit;
}

$pdo     = db();
$orderId = (int)($_POST['order_id'] ?? 0);
$method  = $_POST['method'] ?? '';

if (!$orderId || !in_array($method, ['cash', 'card', 'digital'], true)) {
    header('Location: /payments.php?error=invalid');
    exit;
}

$settings   = get_settings();
$gstEnabled = $settings['gst_enabled'];
$gstRate    = $settings['gst_rate'];

$amount = $pdo->prepare(
    "SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_id = ?"
);
$amount->execute([$orderId]);
$subtotal = (float)$amount->fetchColumn();
$amount   = round($subtotal * ($gstEnabled ? (1 + $gstRate / 100) : 1), 2);

$pdo->beginTransaction();
try {
    $claim = $pdo->prepare(
        "UPDATE orders SET status = 'Paid' WHERE id = ? AND status = 'Ready'"
    );
    $claim->execute([$orderId]);

    if ($claim->rowCount() === 0) {
        $pdo->rollBack();
        header('Location: /payments.php?error=notready');
        exit;
    }

    $pdo->prepare(
        "INSERT INTO payments (order_id, amount, method, staff_id) VALUES (?, ?, ?, ?)"
    )->execute([$orderId, $amount, $method, $_SESSION['user_id']]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: /payments.php?error=db');
    exit;
}

header('Location: /bill-view.php?order_id=' . $orderId . '&paid=1');
exit;
