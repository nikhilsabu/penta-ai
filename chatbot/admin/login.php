<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/config.php';

if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');

    if (ADMIN_PASSWORD === '') {
        $error = 'Admin password is not configured. Set ADMIN_PASSWORD in chatbot/.env.';
    } elseif (hash_equals(ADMIN_PASSWORD, $password)) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid password.';
    }
}
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign in | Pentame AI Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="admin.css" rel="stylesheet" />
  <style>
    .login-page {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 1.5rem;
    }
    .login-card {
      width: min(420px, 100%);
      background: var(--admin-surface);
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius);
      box-shadow: var(--admin-shadow);
      padding: 2rem;
    }
    .login-brand {
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 0.35rem;
    }
    .login-sub {
      color: var(--admin-muted);
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body class="admin-body">
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">Pentame AI Dashboard</div>
      <p class="login-sub">Sign in to manage your chatbot</p>
      <?php if ($error !== ''): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <form method="post" class="admin-form" autocomplete="off">
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required autofocus />
        </div>
        <button type="submit" class="admin-btn admin-btn-primary w-100">Sign in</button>
      </form>
    </div>
  </div>
</body>
</html>
