<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

$settings = get_settings();

$pageTitle    = 'Settings';
$pageSubtitle = 'System configuration';
$activePage   = 'settings';
require_once __DIR__ . '/includes/header.php';
?>