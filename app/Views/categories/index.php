<?php
declare(strict_types=1);
/**
 * Bootstrap 5 Categories View
 * @var array $builtin  (built-in categories, not deletable)
 * @var array $custom   (user-created categories, deletable)
 * @var array $all      (full list for the form)
 * @var string $csrf
 */
use App\Helpers\Money;

$builtin = $builtin ?? [];
$custom  = $custom  ?? [];
?>
<div class="page">
  <div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Kategori</h1>
    <p class="text-muted small mb-0">Kelola kategori transaksi. Semua kategori dapat digunakan untuk pemasukan maupun pengeluaran.</p>
  </div>

  <div class="row g-4">
    <!-- BAWAAN -->
    <div class="col-12 col-md-6">
      <section class="card h-100">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold mb-0">Bawaan</h2>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">Sistem</span>
          </div>
          <p class="text-muted small mb-3">Kategori bawaan sistem tidak dapat dihapus.</p>

          <ul class="category-list">
            <?php foreach ($builtin as $c): ?>
              <li>
                <span class="fw-medium text-dark">
                  <i class="bi bi-tag-fill text-primary me-2"></i><?= e($c['name']) ?>
                </span>
                <span class="badge bg-light text-muted border rounded-pill">Bawaan</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    </div>

    <!-- CUSTOM -->
    <div class="col-12 col-md-6">
      <section class="card h-100">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold mb-0">Kategori Kustom</h2>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">Kustom</span>
          </div>

          <form method="post" action="/categories" class="mb-4">
            <?= \App\Helpers\csrf_field() ?>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-plus-circle text-primary"></i></span>
              <input type="text" class="form-control border-start-0 ps-0" name="name" placeholder="cth: Gaji, Makan, Transport" maxlength="64" required>
              <button class="btn btn-primary px-4 fw-semibold" type="submit">Tambah</button>
            </div>
          </form>

          <ul class="category-list">
            <?php if (empty($custom)): ?>
              <li class="text-muted small py-3 text-center d-block">
                <i class="bi bi-tags d-block fs-4 mb-1 text-secondary"></i>
                Belum ada kategori kustom.
              </li>
            <?php else: ?>
              <?php foreach ($custom as $c): ?>
                <li>
                  <span class="fw-medium text-dark">
                    <i class="bi bi-tag text-primary me-2"></i><?= e($c['name']) ?>
                  </span>
                  <form action="/categories/delete" method="post" class="m-0 inline"
                        onsubmit="return confirm('Hapus kategori <?= e(addslashes($c['name'])) ?> ini?');">
                    <?= \App\Helpers\csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-link text-danger p-0 border-0 d-flex align-items-center gap-1 small text-decoration-none" type="submit" title="Hapus">
                      <i class="bi bi-trash3"></i> Hapus
                    </button>
                  </form>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </section>
    </div>
  </div>
</div>
