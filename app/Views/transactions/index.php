<?php
declare(strict_types=1);
/**
 * @var array $rows
 * @var array $categories
 * @var array $filters
 * @var string $csrf
 */
use App\Helpers\Money;

$rows       = $rows       ?? [];
$categories = $categories ?? ['income' => [], 'expense' => []];
$filters    = $filters    ?? ['from' => '', 'to' => '', 'type' => '', 'category_id' => ''];
?>
<div class="page">
  <div class="row-between">
    <h1>Transaksi</h1>
    <a class="btn-primary" href="/transactions/new">+ Tambah</a>
  </div>

  <form class="filters" method="get" action="/transactions">
    <label>
      Dari
      <input type="date" name="from" value="<?= e($filters['from']) ?>">
    </label>
    <label>
      Sampai
      <input type="date" name="to" value="<?= e($filters['to']) ?>">
    </label>
    <label>
      Tipe
      <select name="type">
        <option value=""          <?= $filters['type'] === '' ? 'selected' : '' ?>>(Semua)</option>
        <option value="income"  <?= $filters['type'] === 'income' ? 'selected' : '' ?>>Pemasukan</option>
        <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
      </select>
    </label>
    <label>
      Kategori
      <select name="category_id">
        <option value="">Semua</option>
        <?php foreach (($categories['income'] ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === $filters['category_id'] ? 'selected' : '' ?>>
            🟢 <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
        <?php foreach (($categories['expense'] ?? []) as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (string)$c['id'] === $filters['category_id'] ? 'selected' : '' ?>>
            🔴 <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="btn-secondary">Terapkan</button>
    <a class="btn-link" href="/transactions">Reset</a>
  </form>

  <div class="card">
    <?php if (empty($rows)): ?>
      <p class="muted">Tidak ada transaksi yang cocok dengan filter.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Tipe</th>
            <th>Kategori</th>
            <th>Deskripsi</th>
            <th class="num">Nominal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['tx_date']) ?></td>
              <td>
                <span class="badge badge-<?= e($r['type']) ?>">
                  <?= $r['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?>
                </span>
              </td>
              <td><?= e($r['category_name']) ?></td>
              <td><?= e($r['description'] ?? '-') ?></td>
              <td class="num <?= e($r['type']) ?>">
                <?= $r['type'] === 'income' ? '+' : '-' ?>
                <?= Money::formatRupiah($r['amount']) ?>
              </td>
              <td class="row-actions">
                <a href="/transactions/edit?id=<?= (int)$r['id'] ?>">Edit</a>
                <form action="/transactions/delete" method="post" class="inline"
                      onsubmit="return confirm('Hapus transaksi ini?');">
                  <?= \App\Helpers\csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn-link danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
