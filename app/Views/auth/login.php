<?php
declare(strict_types=1);
/**
 * Standalone login page (no top-bar layout).
 * @var array $__flashes
 */
use App\Helpers\Flash;
$flashes = $__flashes ?? [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk — Keuangan</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <h1 class="brand-title">Keuangan</h1>
    <p class="muted">Masuk untuk melanjutkan.</p>

    <?php foreach ($flashes as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <form action="/login" method="post" class="form">
      <?= \App\Helpers\csrf_field() ?>
      <label>
        Username
        <input type="text" name="username" autocomplete="username" required autofocus>
      </label>
      <label>
        Password
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button type="submit" class="btn-primary">Masuk</button>
    </form>
  </main>
</body>
</html>
