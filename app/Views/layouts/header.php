<?php
declare(strict_types=1);
/**
 * @var string $content
 * @var string $title
 * @var array $__user
 * @var array $__flashes
 */
use App\Helpers\Flash;

$title    = $title ?? 'Keuangan';
$appName  = $__config['app_name'] ?? 'Keuangan';
$user     = $__user;
$flashes  = $__flashes ?? [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/dashboard"><?= e($appName) ?></a>
    <?php if ($user): ?>
      <nav class="topnav">
        <a href="/dashboard">Dashboard</a>
        <a href="/transactions">Transaksi</a>
        <a href="/categories">Kategori</a>
      </nav>
      <div class="user-chip">
        <span>👤 <?= e($user['username']) ?></span>
        <form action="/logout" method="post" class="inline">
          <?= \App\Helpers\csrf_field() ?>
          <button type="submit" class="btn-link">Keluar</button>
        </form>
      </div>
    <?php endif; ?>
  </header>

  <?php if (!empty($flashes)): ?>
    <ul class="flashes">
      <?php foreach ($flashes as $f): ?>
        <li class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <main class="main">
    <?= $content ?>
  </main>

  <footer class="footer">
    <small>&copy; <?= date('Y') ?> <?= e($appName) ?></small>
  </footer>

  <script src="/assets/js/app.js" defer></script>
  <?php if (str_starts_with($title, 'Dashboard')): ?>
    <script src="/assets/js/dashboard.js" defer></script>
  <?php elseif (str_starts_with($title, 'Transaksi')): ?>
    <script src="/assets/js/transactions.js" defer></script>
  <?php elseif (str_starts_with($title, 'Kategori')): ?>
    <script src="/assets/js/categories.js" defer></script>
  <?php endif; ?>
</body>
</html>
