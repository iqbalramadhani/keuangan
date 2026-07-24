<?php
declare(strict_types=1);
/**
 * @var array{kpi:array, recent:array, summary:array, csrf:string} $__
 */
use App\Helpers\Money;

$kpi     = $kpi     ?? [];
$recent  = $recent  ?? [];
$summary = $summary ?? [];
?>
<div class="page">
  <h1>Dashboard</h1>

  <section class="kpi-grid">
    <div class="kpi">
      <div class="kpi-label">Pemasukan bulan ini</div>
      <div class="kpi-value income"><?= Money::formatRupiah($kpi['income'] ?? 0) ?></div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Pengeluaran bulan ini</div>
      <div class="kpi-value expense"><?= Money::formatRupiah($kpi['expense'] ?? 0) ?></div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Saldo bulan ini</div>
      <div class="kpi-value balance"><?= Money::formatRupiah($kpi['balance'] ?? 0) ?></div>
    </div>
  </section>

  <section class="card">
    <h2>Tren 12 bulan terakhir</h2>
    <canvas id="chart" height="180"></canvas>
    <p class="muted">Biru: Pemasukan · Oranye: Pengeluaran</p>
  </section>

  <section class="card">
    <div class="row-between">
      <h2>10 transaksi terakhir</h2>
      <a class="btn-primary" href="/transactions/new">+ Tambah Transaksi</a>
    </div>
    <?php if (empty($recent)): ?>
      <p class="muted">Belum ada transaksi. <a href="/transactions/new">Tambah sekarang</a>.</p>
    <?php else: ?>
      <table class="table">
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
              <td><?= e($r['tx_date']) ?></td>
              <td>
                <span class="badge badge-<?= e($r['type']) ?>">
                  <?= e($r['category_name']) ?>
                </span>
              </td>
              <td><?= e($r['description'] ?? '-') ?></td>
              <td class="num <?= e($r['type']) ?>">
                <?= $r['type'] === 'income' ? '+' : '-' ?>
                <?= Money::formatRupiah($r['amount']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<script type="application/json" id="summary-data"><?= e(json_encode($summary, JSON_UNESCAPED_UNICODE)) ?></script>
