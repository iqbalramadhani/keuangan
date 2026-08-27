<?php
declare(strict_types=1);
/**
 * System Logs Index View
 * @var array{logs:array} $__
 */
$logs = $logs ?? [];
?>
<div class="page">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">System Logs</h1>
      <p class="text-muted small mb-0">Daftar file log aplikasi.</p>
    </div>
  </div>

  <section class="card">
    <div class="card-body p-4">
      <?php if (empty($logs)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          <p class="mb-1">Belum ada file log tersimpan.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Tanggal (File)</th>
                <th>Ukuran</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td class="fw-semibold">
                    <i class="bi bi-file-earmark-text text-secondary me-2"></i>
                    <?= e(basename($log['path'])) ?>
                  </td>
                  <td class="text-secondary small">
                    <?= e(number_format($log['size'] / 1024, 2)) ?> KB
                  </td>
                  <td class="text-end">
                    <a href="/system/logs/view?file=<?= e(basename($log['path'])) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                      <i class="bi bi-eye"></i> Lihat
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
