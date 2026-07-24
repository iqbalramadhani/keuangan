<?php
declare(strict_types=1);
/**
 * @var array $byType
 */
use App\Helpers\Money;

$byType = $byType ?? ['income' => [], 'expense' => []];
?>
<div class="page">
  <h1>Kategori</h1>
  <p class="muted">Kelola subkategori di bawah Pemasukan &amp; Pengeluaran. Dua kategori bawaan tidak dapat dihapus.</p>

  <div class="grid-2">
    <!-- PEMASUKAN -->
    <section class="card">
      <h2><span class="dot income"></span> Pemasukan</h2>

      <form method="post" action="/categories" class="inline-form">
        <?= \App\Helpers\csrf_field() ?>
        <input type="hidden" name="type" value="income">
        <input type="text" name="name" placeholder="cth: Gaji, Bonus" maxlength="64" required>
        <button class="btn-primary" type="submit">Tambah</button>
      </form>

      <ul class="category-list">
        <?php foreach ($byType['income'] as $c): ?>
          <li>
            <span><?= e($c['name']) ?>
              <?php if ((int)$c['is_builtin']): ?>
                <small class="muted">(bawaan)</small>
              <?php endif; ?>
            </span>
            <?php if (!(int)$c['is_builtin']): ?>
              <form action="/categories/delete" method="post" class="inline"
                    onsubmit="return confirm('Hapus kategori ini?');">
                <?= \App\Helpers\csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn-link danger" type="submit">Hapus</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <!-- PENGELUARAN -->
    <section class="card">
      <h2><span class="dot expense"></span> Pengeluaran</h2>

      <form method="post" action="/categories" class="inline-form">
        <?= \App\Helpers\csrf_field() ?>
        <input type="hidden" name="type" value="expense">
        <input type="text" name="name" placeholder="cth: Makan, Transport" maxlength="64" required>
        <button class="btn-primary" type="submit">Tambah</button>
      </form>

      <ul class="category-list">
        <?php foreach ($byType['expense'] as $c): ?>
          <li>
            <span><?= e($c['name']) ?>
              <?php if ((int)$c['is_builtin']): ?>
                <small class="muted">(bawaan)</small>
              <?php endif; ?>
            </span>
            <?php if (!(int)$c['is_builtin']): ?>
              <form action="/categories/delete" method="post" class="inline"
                    onsubmit="return confirm('Hapus kategori ini?');">
                <?= \App\Helpers\csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn-link danger" type="submit">Hapus</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>
</div>
