<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /staff.php');
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 2);
$status  = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

if ($id <= 0 || $name === '' || $email === '') {
    header('Location: /staff.php?error=required');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /staff.php?error=invalid_email');
    exit;
}

$pdo = db();
$pdo->prepare(
    "UPDATE users SET name=?, email=?, phone=?, role_id=?, status=? WHERE id=?"
)->execute([$name, $email, $phone ?: null, $role_id, $status, $id]);

$password = $_POST['password'] ?? '';
if ($password !== '') {
    if (strlen($password) < 6) {
        header('Location: /staff.php?error=short_password');
        exit;
    }
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
        ->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
}

header('Location: /staff.php?saved=1');
exit;
