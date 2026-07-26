<?php
declare(strict_types=1);
/**
 * @var ?array $tx
 * @var array $categories  (flat list from Category::all())
 * @var string $csrf
 */
use App\Helpers\Money;

$tx         = $tx         ?? null;
$categories = $categories ?? [];

$isEdit   = (bool)$tx;
$action   = $isEdit ? '/transactions/update' : '/transactions';
$type     = $tx['type']           ?? 'expense';
$catId    = (int)($tx['category_id'] ?? 0);
$amount   = $tx['amount']         ?? '';
$desc     = $tx['description']    ?? '';
$date     = $tx['tx_date']        ?? date('Y-m-d');
?>
<div class="page">
  <h1><?= $isEdit ? 'Edit' : 'Tambah' ?> Transaksi</h1>

  <form method="post" action="<?= e($action) ?>" class="form">
    <?= \App\Helpers\csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$tx['id'] ?>">
    <?php endif; ?>

    <fieldset>
      <legend>Tipe</legend>
      <label class="radio">
        <input type="radio" name="type" value="expense" <?= $type === 'expense' ? 'checked' : '' ?>>
        Pengeluaran
      </label>
      <label class="radio">
        <input type="radio" name="type" value="income" <?= $type === 'income' ? 'checked' : '' ?>>
        Pemasukan
      </label>
    </fieldset>

    <label>
      Kategori
      <select name="category_id" required>
        <option value="">— Pilih kategori —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>"
            <?= $catId === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Nominal (contoh: 1500000 atau 1.500.000,00)
      <input type="text" name="amount" inputmode="decimal"
             value="<?= e((string)$amount) ?>" required>
    </label>

    <label>
      Tanggal
      <input type="date" name="tx_date" value="<?= e($date) ?>" required>
    </label>

    <label>
      Deskripsi (opsional)
      <input type="text" name="description" maxlength="255"
             value="<?= e((string)$desc) ?>">
    </label>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Simpan</button>
      <a href="/transactions" class="btn-link">Batal</a>
    </div>
  </form>
</div>
