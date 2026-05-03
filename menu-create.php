<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /menu.php');
    exit;
}

$name         = trim($_POST['name'] ?? '');
$description  = trim($_POST['description'] ?? '');
$price        = (float)($_POST['price'] ?? 0);
$category     = trim($_POST['category'] ?? '');
$is_available = isset($_POST['is_available']) ? 1 : 0;

if ($name === '' || $price <= 0) {
    header('Location: /menu.php?error=1');
    exit;
}

$pdo = db();
$pdo->prepare(
    "INSERT INTO menu_items (name, description, price, category, is_available) VALUES (?, ?, ?, ?, ?)"
)->execute([$name, $description, $price, $category, $is_available]);

header('Location: /menu.php?saved=1');
exit;
