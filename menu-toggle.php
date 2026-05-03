<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /menu.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $pdo = db();
    $pdo->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id = ?")->execute([$id]);
}

header('Location: /menu.php');
exit;
