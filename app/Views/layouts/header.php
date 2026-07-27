<?php
declare(strict_types=1);
/**
 * Bootstrap 5 Main Layout
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

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  
  <!-- Google Fonts: Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom Application Styling -->
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Main Navbar -->
  <header class="topbar sticky-top shadow-sm">
    <nav class="navbar navbar-expand-lg py-2">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/dashboard">
          <span class="brand-icon d-flex align-items-center justify-content-center rounded-3 shadow-sm">
            <i class="bi bi-wallet2 text-white"></i>
          </span>
          <span class="brand-text"><?= e($appName) ?></span>
        </a>

        <?php if ($user): ?>
          <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" 
                  data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" 
                  aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-1">
              <li class="nav-item">
                <a class="nav-link px-3 rounded-pill <?= ($uri === '/dashboard' || $uri === '/') ? 'active fw-semibold' : '' ?>" href="/dashboard">
                  <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link px-3 rounded-pill <?= str_starts_with($uri, '/transactions') ? 'active fw-semibold' : '' ?>" href="/transactions">
                  <i class="bi bi-arrow-left-right me-1"></i> Transaksi
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link px-3 rounded-pill <?= str_starts_with($uri, '/categories') ? 'active fw-semibold' : '' ?>" href="/categories">
                  <i class="bi bi-tags-fill me-1"></i> Kategori
                </a>
              </li>
            </ul>
            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0 pt-3 pt-lg-0 border-top border-lg-0">
              <div class="user-chip d-flex align-items-center gap-2 px-3 py-1 rounded-pill">
                <i class="bi bi-person-circle text-primary fs-5"></i>
                <span class="fw-medium small"><?= e($user['username']) ?></span>
              </div>
              <form action="/logout" method="post" class="m-0">
                <?= \App\Helpers\csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                  <i class="bi bi-box-arrow-right"></i>
                  <span>Keluar</span>
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <!-- Flash Messages -->
  <?php if (!empty($flashes)): ?>
    <div class="container mt-3">
      <?php foreach ($flashes as $f): ?>
        <?php 
          $alertType = $f['type'] === 'success' ? 'success' : ($f['type'] === 'error' ? 'danger' : 'info');
          $icon = $f['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-4 border-0 mb-2" role="alert">
          <i class="bi <?= $icon ?> fs-5"></i>
          <div><?= e($f['msg']) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Main Content Area -->
  <main class="main container my-4 flex-grow-1">
    <?= $content ?>
  </main>

  <!-- Footer -->
  <footer class="footer py-4 mt-auto border-top bg-white">
    <div class="container text-center">
      <small class="text-muted">&copy; <?= date('Y') ?> <?= e($appName) ?>. Built with Bootstrap 5.</small>
    </div>
  </footer>

  <!-- Bootstrap 5 JS Bundle & Application Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
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
