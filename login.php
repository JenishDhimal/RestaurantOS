

<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/auth.php';
    header('Location: ' . role_home());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = db()->prepare(
            "SELECT u.id, u.name, u.password_hash, u.status, r.name AS role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ?
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row && $row['status'] === 'active' && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name']    = $row['name'];
            $_SESSION['role']    = $row['role'];
            db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$row['id']]);
            require_once __DIR__ . '/includes/auth.php';
            header('Location: ' . role_home());
            exit;
        }
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — RestaurantOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-screen">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon"><i class="fa-solid fa-utensils"></i></div>
      <h1>RestaurantOS</h1>
      <p>Restaurant Management System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login.php">
      <div class="mb-3">
        <label class="form-label" style="font-size:12px;font-weight:700;letter-spacing:.05em;color:var(--text-secondary);">EMAIL</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="fa-regular fa-envelope text-muted" style="font-size:13px;"></i>
          </span>
          <input type="text" name="username" class="form-control border-start-0"
                 placeholder="jenish@bistro.com" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" style="font-size:12px;font-weight:700;letter-spacing:.05em;color:var(--text-secondary);">PASSWORD</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="fa-solid fa-lock text-muted" style="font-size:13px;"></i>
          </span>
          <input type="password" name="password" class="form-control border-start-0"
                 placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 mt-2" style="padding:12px;font-weight:600;">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
      </button>
    </form>
  </div>
</div>
</body>
</html>
