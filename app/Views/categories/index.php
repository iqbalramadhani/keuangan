<?php
declare(strict_types=1);
/**
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
  <h1>Kategori</h1>
  <p class="muted">Kelola kategori transaksi. Semua kategori bisa dipakai untuk pemasukan maupun pengeluaran.</p>

  <div class="grid-2">
    <!-- BAWAAN -->
    <section class="card">
      <h2>Bawaan</h2>
      <p class="muted">Kategori bawaan tidak dapat dihapus.</p>
      <ul class="category-list">
        <?php foreach ($builtin as $c): ?>
          <li>
            <span><?= e($c['name']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <!-- CUSTOM -->
    <section class="card">
      <h2>Kategori Kustom</h2>

      <form method="post" action="/categories" class="inline-form">
        <?= \App\Helpers\csrf_field() ?>
        <input type="text" name="name" placeholder="cth: Gaji, Makan, Transport" maxlength="64" required>
        <button class="btn-primary" type="submit">Tambah</button>
      </form>

      <ul class="category-list">
        <?php if (empty($custom)): ?>
          <li class="muted">Belum ada kategori kustom.</li>
        <?php else: ?>
          <?php foreach ($custom as $c): ?>
            <li>
              <span><?= e($c['name']) ?></span>
              <form action="/categories/delete" method="post" class="inline"
                    onsubmit="return confirm('Hapus kategori ini?');">
                <?= \App\Helpers\csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn-link danger" type="submit">Hapus</button>
              </form>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </section>
  </div>
</div>
