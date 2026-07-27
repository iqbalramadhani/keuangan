<?php
declare(strict_types=1);
/**
 * Bootstrap 5 Dashboard View
 * @var array{kpi:array, recent:array, summary:array, csrf:string} $__
 */
use App\Helpers\Money;

$kpi     = $kpi     ?? [];
$recent  = $recent  ?? [];
$summary = $summary ?? [];
?>
<div class="page">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Dashboard</h1>
      <p class="text-muted small mb-0">Ringkasan arus kas dan aktivitas keuangan Anda bulan ini.</p>
    </div>
    <a class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm" href="/transactions/new">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Transaksi</span>
    </a>
  </div>

  <!-- KPI Grid -->
  <section class="kpi-grid">
    <div class="kpi d-flex align-items-center justify-content-between">
      <div>
        <div class="kpi-label">Pemasukan bulan ini</div>
        <div class="kpi-value income"><?= Money::formatRupiah($kpi['income'] ?? 0) ?></div>
      </div>
      <div class="rounded-circle bg-success-subtle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
        <i class="bi bi-arrow-down-left-circle-fill text-success fs-3"></i>
      </div>
    </div>

    <div class="kpi d-flex align-items-center justify-content-between">
      <div>
        <div class="kpi-label">Pengeluaran bulan ini</div>
        <div class="kpi-value expense"><?= Money::formatRupiah($kpi['expense'] ?? 0) ?></div>
      </div>
      <div class="rounded-circle bg-danger-subtle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
        <i class="bi bi-arrow-up-right-circle-fill text-danger fs-3"></i>
      </div>
    </div>

    <div class="kpi d-flex align-items-center justify-content-between">
      <div>
        <div class="kpi-label">Saldo bulan ini</div>
        <div class="kpi-value balance"><?= Money::formatRupiah($kpi['balance'] ?? 0) ?></div>
      </div>
      <div class="rounded-circle bg-primary-subtle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
        <i class="bi bi-wallet-fill text-primary fs-3"></i>
      </div>
    </div>
  </section>

  <!-- Chart Card -->
  <section class="card mb-4">
    <div class="card-body p-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Tren 12 Bulan Terakhir</h2>
        <div class="d-flex align-items-center gap-3 small">
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill d-inline-flex align-items-center gap-1">
            <i class="bi bi-circle-fill" style="font-size: 8px;"></i> Pemasukan
          </span>
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill d-inline-flex align-items-center gap-1">
            <i class="bi bi-circle-fill" style="font-size: 8px;"></i> Pengeluaran
          </span>
        </div>
      </div>
      <div class="chart-wrapper mt-2">
        <canvas id="chart" height="180"></canvas>
      </div>
    </div>
  </section>

  <!-- Recent Transactions Card -->
  <section class="card">
    <div class="card-body p-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 fw-bold mb-0">10 Transaksi Terakhir</h2>
        <a class="text-decoration-none small fw-semibold d-flex align-items-center gap-1" href="/transactions">
          <span>Lihat Semua</span>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>

      <?php if (empty($recent)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          <p class="mb-1">Belum ada transaksi tercatat.</p>
          <a class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2" href="/transactions/new">
            <i class="bi bi-plus-lg"></i> Tambah sekarang
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th class="num">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
                <tr>
                  <td class="text-nowrap text-secondary small"><?= e($r['tx_date']) ?></td>
                  <td>
                    <span class="badge badge-<?= e($r['type']) ?> rounded-pill px-3 py-1 fw-medium">
                      <i class="bi <?= $r['type'] === 'income' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i>
                      <?= e($r['category_name']) ?>
                    </span>
                  </td>
                  <td class="text-secondary"><?= e($r['description'] ?? '-') ?></td>
                  <td class="num <?= e($r['type']) ?> fw-semibold">
                    <?= $r['type'] === 'income' ? '+' : '-' ?>
                    <?= Money::formatRupiah($r['amount']) ?>
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

<script type="application/json" id="summary-data"><?= e(json_encode($summary, JSON_UNESCAPED_UNICODE)) ?></script>
