<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings.php');
    exit;
}

$settings = [
    'table_count' => max(1, min(50, (int)($_POST['table_count'] ?? 10))),
    'gst_enabled' => isset($_POST['gst_enabled']),
    'gst_number'  => trim($_POST['gst_number'] ?? ''),
    'gst_rate'    => 10,
];

file_put_contents(__DIR__ . '/includes/settings.json', json_encode($settings, JSON_PRETTY_PRINT));

header('Location: /settings.php?saved=1');
exit;
