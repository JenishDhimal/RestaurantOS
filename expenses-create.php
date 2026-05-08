<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_role('admin', 'staff');

$pdo = db();

$amount   = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$category = trim($_POST['category'] ?? '');
$note     = trim($_POST['note'] ?? '');
$date     = trim($_POST['expense_date'] ?? '');

$allowed = ['Food & Supplies', 'Utilities', 'Wages & Salaries', 'Maintenance', 'Equipment', 'Marketing', 'Other'];

if (!$amount || $amount <= 0 || !in_array($category, $allowed, true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid expense data. Please check all fields.'];
    header('Location: /expenses.php');
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO expenses (amount, category, note, expense_date, recorded_by) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$amount, $category, $note !== '' ? $note : null, $date, $_SESSION['user_id']]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Expense recorded successfully.'];
header('Location: /expenses.php');
exit;
