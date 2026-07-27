<?php
declare(strict_types=1);
/**
 * Standalone login page (Bootstrap 5).
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
  <!-- Google Fonts: Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <div class="text-center mb-4">
      <div class="brand-icon mx-auto d-flex align-items-center justify-content-center rounded-4 shadow mb-3" style="width: 56px; height: 56px;">
        <i class="bi bi-wallet2 text-white fs-3"></i>
      </div>
      <h1 class="h3 fw-bold mb-1">Keuangan</h1>
      <p class="text-muted small">Masuk ke akun Anda untuk melanjutkan</p>
    </div>

    <?php foreach ($flashes as $f): ?>
      <?php 
        $alertType = $f['type'] === 'success' ? 'success' : ($f['type'] === 'error' ? 'danger' : 'info');
      ?>
      <div class="alert alert-<?= $alertType ?> alert-dismissible fade show small py-2 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-info-circle-fill"></i>
        <div><?= e($f['msg']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endforeach; ?>

    <form action="/login" method="post" class="d-flex flex-column gap-3">
      <?= \App\Helpers\csrf_field() ?>
      
      <div>
        <label class="form-label small fw-medium text-secondary">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" class="form-control border-start-0 ps-0" name="username" autocomplete="username" placeholder="Masukkan username" required autofocus>
        </div>
      </div>

      <div>
        <label class="form-label small fw-medium text-secondary">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" class="form-control border-start-0 ps-0" name="password" autocomplete="current-password" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 mt-2 rounded-3 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
      </button>
    </form>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
