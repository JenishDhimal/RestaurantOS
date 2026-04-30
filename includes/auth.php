<?php
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: ' . role_home());
        exit;
    }
}

function require_admin(): void {
    require_role('admin');
}

function is_admin(): bool   { return ($_SESSION['role'] ?? '') === 'admin'; }
function is_staff(): bool   { return ($_SESSION['role'] ?? '') === 'staff'; }
function is_kitchen(): bool { return ($_SESSION['role'] ?? '') === 'kitchen'; }

function role_home(): string {
    return match ($_SESSION['role'] ?? '') {
        'staff'   => '/orders.php',
        'kitchen' => '/kitchen.php',
        default   => '/dashboard.php',
    };
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role'],
    ];
}
