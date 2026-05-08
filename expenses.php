<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role('admin', 'staff');

$pdo = db();

$rangeFrom    = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
$rangeTo      = $_GET['to']   ?? date('Y-m-d');
$isAllTime    = false;
$activePeriod = $_GET['period'] ?? '';

if (isset($_GET['period'])) {
    if ($_GET['period'] === 'today') {
        $rangeFrom = date('Y-m-d');
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'week') {
        $rangeFrom = date('Y-m-d', strtotime('-6 days'));
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'month') {
        $rangeFrom = date('Y-m-d', strtotime('-29 days'));
        $rangeTo   = date('Y-m-d');
    } elseif ($_GET['period'] === 'alltime') {
        $earliest  = $pdo->query("SELECT COALESCE(MIN(expense_date), CURDATE()) FROM expenses")->fetchColumn();
        $rangeFrom = $earliest;
        $rangeTo   = date('Y-m-d');
        $isAllTime = true;
    }
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeFrom)) $rangeFrom = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeTo))   $rangeTo   = date('Y-m-d');

$expenseRows = $pdo->prepare(
    "SELECT e.id, e.amount, e.category, e.note, e.expense_date, u.name AS recorded_by
     FROM expenses e
     JOIN users u ON u.id = e.recorded_by
     WHERE e.expense_date BETWEEN ? AND ?
     ORDER BY e.expense_date DESC, e.id DESC"
);
$expenseRows->execute([$rangeFrom, $rangeTo]);
$expenses = $expenseRows->fetchAll();

$totalExpenses = array_sum(array_column($expenses, 'amount'));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle    = 'Expenses';
$pageSubtitle = 'Track and manage operational expenses';
$activePage   = 'expenses';
require_once __DIR__ . '/includes/header.php';

$categories = ['Food & Supplies', 'Utilities', 'Wages & Salaries', 'Maintenance', 'Equipment', 'Marketing', 'Other'];
?>
