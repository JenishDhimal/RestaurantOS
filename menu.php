<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();

$items = $pdo->query(
    "SELECT id, name, description, price, category, is_available
     FROM menu_items ORDER BY category, name"
)->fetchAll();

$grouped = [];
foreach ($items as $item) {
    $grouped[$item['category'] ?: 'Uncategorised'][] = $item;
}

$pageTitle    = 'Menu Management';
$pageSubtitle = 'Add, edit, or disable menu items';
$activePage   = 'menu';
require_once __DIR__ . '/includes/header.php';
?>