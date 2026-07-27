<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <!-- Google Fonts: Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-light py-5">
  <div class="container" style="max-width: 680px;">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4 p-md-5">
        <div class="d-flex align-items-center gap-3 mb-4">
          <div class="brand-icon d-flex align-items-center justify-content-center rounded-3 shadow-sm">
            <i class="bi bi-database-gear text-white fs-4"></i>
          </div>
          <div>
            <h1 class="h4 fw-bold mb-0">Database Migration</h1>
            <p class="text-muted small mb-0">Keuangan Database Schema Management</p>
          </div>
        </div>

        <?php if (isset($result)): ?>
          <?php if ($result['status'] === 'success'): ?>
            <div class="alert alert-success d-flex flex-column gap-2 rounded-3 border-0 shadow-sm" role="alert">
              <div class="d-flex align-items-center gap-2 fw-semibold">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span><?= htmlspecialchars($result['message']) ?></span>
              </div>
              <?php if (!empty($result['applied'])): ?>
                <ul class="mb-0 small ps-3">
                  <?php foreach ($result['applied'] as $v): ?>
                    <li><?= htmlspecialchars($v) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 shadow-sm" role="alert">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <strong><?= htmlspecialchars($result['message']) ?></strong>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="/migrate" class="mt-4">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
          
          <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah Migration</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                      <i class="bi bi-check2 me-1"></i> Applied
                    </span>
                  </td>
                  <td class="text-end fw-semibold"><?= count($status['applied']) ?></td>
                </tr>
                <tr>
                  <td>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">
                      <i class="bi bi-clock me-1"></i> Pending
                    </span>
                  </td>
                  <td class="text-end fw-semibold"><?= count($status['pending']) ?></td>
                </tr>
              </tbody>
            </table>
          </div>

          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
            <i class="bi bi-play-circle me-1"></i>
            <?= empty($status['pending']) ? 'Re-run Migration' : 'Jalankan Migration' ?>
          </button>
        </form>

        <?php if (!empty($status['pending'])): ?>
          <div class="mt-4 pt-3 border-top">
            <h2 class="h6 fw-semibold text-secondary mb-2">Migration Pending:</h2>
            <ul class="list-group list-group-flush small">
              <?php foreach ($status['pending'] as $p): ?>
                <li class="list-group-item bg-transparent px-0 py-1 text-muted">
                  <i class="bi bi-arrow-right-short text-primary me-1"></i><?= htmlspecialchars($p) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top text-center">
          <a class="btn btn-link text-decoration-none small" href="/">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
