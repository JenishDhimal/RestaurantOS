<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_role('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /orders.php');
    exit;
}

$orderId  = (int)($_POST['existing_order_id'] ?? 0);
$rawItems = $_POST['items'] ?? [];

if ($orderId <= 0) {
    header('Location: /orders.php');
    exit;
}

$selected = [];
foreach ($rawItems as $menuItemId => $qty) {
    $qty = (int)$qty;
    if ($qty > 0) $selected[(int)$menuItemId] = $qty;
}

if (empty($selected)) {
    header('Location: /orders.php');
    exit;
}

$pdo = db();

$order = $pdo->prepare(
    "SELECT id FROM orders WHERE id = ? AND type = 'dine-in' AND status IN ('Received','Preparing','Ready')"
);
$order->execute([$orderId]);
if (!$order->fetch()) {
    header('Location: /orders.php');
    exit;
}

$placeholders = implode(',', array_fill(0, count($selected), '?'));
$stmt = $pdo->prepare(
    "SELECT id, price FROM menu_items WHERE id IN ($placeholders) AND is_available = 1"
);
$stmt->execute(array_keys($selected));
$items = $stmt->fetchAll(PDO::FETCH_UNIQUE);

if (empty($items)) {
    header('Location: /orders.php');
    exit;
}

$pdo->beginTransaction();
try {
    $checkStmt  = $pdo->prepare("SELECT id FROM order_items WHERE order_id = ? AND menu_item_id = ?");
    $updateStmt = $pdo->prepare("UPDATE order_items SET quantity = quantity + ? WHERE id = ?");
    $insertStmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)"
    );

    foreach ($selected as $menuItemId => $qty) {
        if (!isset($items[$menuItemId])) continue;
        $checkStmt->execute([$orderId, $menuItemId]);
        $existing = $checkStmt->fetch();
        if ($existing) {
            $updateStmt->execute([$qty, $existing['id']]);
        } else {
            $insertStmt->execute([$orderId, $menuItemId, $qty, $items[$menuItemId]['price']]);
        }
    }

    $pdo->commit();
    header('Location: /orders.php?success=' . $orderId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: /orders.php');
    exit;
}
