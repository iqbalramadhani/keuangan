<?php
declare(strict_types=1);
/**
 * Bootstrap 5 Transactions Index View
 * @var array $rows
 * @var array $categories  (flat list from Category::all())
 * @var array $filters
 * @var string $csrf
 */
use App\Helpers\Money;

$rows       = $rows       ?? [];
$categories = $categories ?? [];
$filters    = $filters    ?? ['from' => '', 'to' => '', 'type' => '', 'category_id' => ''];
?>
<div class="page">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Transaksi</h1>
      <p class="text-muted small mb-0">Kelola dan pantau riwayat pemasukan dan pengeluaran Anda.</p>
    </div>
    <a class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm" href="/transactions/new">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Transaksi</span>
    </a>
  </div>

  <!-- Filter Card -->
  <section class="card mb-4">
    <div class="card-body p-4 bg-light bg-opacity-50 rounded-4">
      <form class="row g-3 align-items-end" method="get" action="/transactions">
        <div class="col-6 col-md-2">
          <label class="form-label small fw-medium text-secondary mb-1">Dari Tanggal</label>
          <input type="date" class="form-control" name="from" value="<?= e($filters['from']) ?>">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small fw-medium text-secondary mb-1">Sampai Tanggal</label>
          <input type="date" class="form-control" name="to" value="<?= e($filters['to']) ?>">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label small fw-medium text-secondary mb-1">Tipe Transaksi</label>
          <select class="form-select" name="type">
            <option value=""          <?= $filters['type'] === '' ? 'selected' : '' ?>>(Semua Tipe)</option>
            <option value="income"  <?= $filters['type'] === 'income' ? 'selected' : '' ?>>Pemasukan</option>
            <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
          </select>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label small fw-medium text-secondary mb-1">Kategori</label>
          <select class="form-select" name="category_id">
            <option value="">(Semua Kategori)</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === $filters['category_id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold">
            <i class="bi bi-funnel me-1"></i> Filter
          </button>
          <a class="btn btn-outline-secondary px-3" href="/transactions" title="Reset">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </section>

  <!-- Transactions Table Card -->
  <section class="card">
    <div class="card-body p-0">
      <?php if (empty($rows)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-search fs-1 d-block mb-2"></i>
          <p class="mb-1">Tidak ada transaksi yang cocok dengan filter Anda.</p>
          <a class="btn btn-sm btn-link text-decoration-none" href="/transactions">Reset filter</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="ps-4">Tanggal</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th class="num">Nominal</th>
                <th class="text-end pe-4">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="ps-4 text-nowrap text-secondary small"><?= e($r['tx_date']) ?></td>
                  <td>
                    <span class="badge badge-<?= e($r['type']) ?> rounded-pill px-3 py-1 fw-medium">
                      <i class="bi <?= $r['type'] === 'income' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i>
                      <?= $r['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?>
                    </span>
                  </td>
                  <td class="fw-medium text-dark"><?= e($r['category_name']) ?></td>
                  <td class="text-secondary"><?= e($r['description'] ?? '-') ?></td>
                  <td class="num <?= e($r['type']) ?> fw-semibold">
                    <?= $r['type'] === 'income' ? '+' : '-' ?>
                    <?= Money::formatRupiah($r['amount']) ?>
                  </td>
                  <td class="text-end pe-4">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                      <a class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1" href="/transactions/edit?id=<?= (int)$r['id'] ?>" title="Edit">
                        <i class="bi bi-pencil-square"></i> Edit
                      </a>
                      <form action="/transactions/delete" method="post" class="m-0 inline"
                            onsubmit="return confirm('Hapus transaksi ini?');">
                        <?= \App\Helpers\csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" title="Hapus">
                          <i class="bi bi-trash3"></i> Hapus
                        </button>
                      </form>
                    </div>
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
