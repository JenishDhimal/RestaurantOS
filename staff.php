<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();

$roles = $pdo->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();
$users = $pdo->query(
    "SELECT u.id, u.name, u.email, u.phone, u.status, u.last_login, r.name AS role, r.id AS role_id
     FROM users u JOIN roles r ON r.id = u.role_id ORDER BY r.id, u.name"
)->fetchAll();

$errorMessages = [
    'required'        => 'Name, email, and password are required.',
    'invalid_email'   => 'Invalid email address.',
    'short_password'  => 'Password must be at least 6 characters.',
    'duplicate_email' => 'A user with that email already exists.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? '';

$total  = count($users);
$active = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$admins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));

$pageTitle    = 'Staff Management';
$pageSubtitle = 'Manage user accounts and roles';
$activePage   = 'staff';
require_once __DIR__ . '/includes/header.php';
?>
