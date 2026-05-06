<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /staff.php');
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$role_id  = (int)($_POST['role_id'] ?? 2);
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    header('Location: /staff.php?error=required');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /staff.php?error=invalid_email');
    exit;
}
if (strlen($password) < 6) {
    header('Location: /staff.php?error=short_password');
    exit;
}

$pdo = db();
$dup = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$dup->execute([$email]);
if ($dup->fetch()) {
    header('Location: /staff.php?error=duplicate_email');
    exit;
}

$pdo->prepare(
    "INSERT INTO users (name, email, phone, password_hash, role_id) VALUES (?, ?, ?, ?, ?)"
)->execute([$name, $email, $phone ?: null, password_hash($password, PASSWORD_BCRYPT), $role_id]);

header('Location: /staff.php?saved=1');
exit;
