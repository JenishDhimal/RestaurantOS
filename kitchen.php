<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/orders.php';
require_login();

$pdo    = db();
$orders = fetch_kitchen_orders($pdo);

$pageTitle    = 'Kitchen Display';
$pageSubtitle = 'Active orders — refreshes every 10 seconds';
$activePage   = 'kitchen';
require_once __DIR__ . '/includes/header.php';
?>
