<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_role('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /orders.php');
    exit;
}

$type     = $_POST['type'] ?? '';
$tableNum = trim($_POST['table_number'] ?? '');
$rawItems = $_POST['items'] ?? [];

if (!in_array($type, ['dine-in', 'takeaway'], true)) {
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

if ($type === 'takeaway') {
    $tableNum = 'TW-' . strtoupper(substr(uniqid(), -5));
} elseif ($tableNum === '') {
    header('Location: /orders.php');
    exit;
}

$staffId = (int)$_SESSION['user_id'];

$pdo->beginTransaction();
try {
    $pdo->prepare(
        "INSERT INTO orders (type, table_number, status, staff_id) VALUES (?, ?, 'Received', ?)"
    )->execute([$type, $tableNum, $staffId]);
    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)"
    );
    foreach ($selected as $menuItemId => $qty) {
        if (!isset($items[$menuItemId])) continue;
        $itemStmt->execute([$orderId, $menuItemId, $qty, $items[$menuItemId]['price']]);
    }

    $pdo->commit();
    header('Location: /orders.php?success=' . $orderId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: /orders.php');
    exit;
}
