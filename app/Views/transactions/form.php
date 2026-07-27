<?php
declare(strict_types=1);
/**
 * Bootstrap 5 Transaction Form View (Create / Edit)
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
<div class="page" style="max-width: 640px; margin: 0 auto;">
  <div class="mb-4">
    <h1 class="h3 fw-bold mb-1"><?= $isEdit ? 'Edit Transaksi' : 'Tambah Transaksi Baru' ?></h1>
    <p class="text-muted small mb-0">Lengkapi formulir di bawah ini untuk menyimpan data transaksi keuangan Anda.</p>
  </div>

  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <form method="post" action="<?= e($action) ?>" class="d-flex flex-column gap-4">
        <?= \App\Helpers\csrf_field() ?>
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= (int)$tx['id'] ?>">
        <?php endif; ?>

        <!-- Tipe Transaksi -->
        <div>
          <label class="form-label small fw-semibold text-secondary mb-2 d-block">TIPE TRANSAKSI</label>
          <div class="row g-3">
            <div class="col-6">
              <input type="radio" class="btn-check" name="type" id="type-expense" value="expense" <?= $type === 'expense' ? 'checked' : '' ?>>
              <label class="btn btn-outline-danger w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1 fw-semibold shadow-sm" for="type-expense">
                <i class="bi bi-arrow-up-right-circle fs-4"></i>
                <span>Pengeluaran</span>
              </label>
            </div>
            <div class="col-6">
              <input type="radio" class="btn-check" name="type" id="type-income" value="income" <?= $type === 'income' ? 'checked' : '' ?>>
              <label class="btn btn-outline-success w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1 fw-semibold shadow-sm" for="type-income">
                <i class="bi bi-arrow-down-left-circle fs-4"></i>
                <span>Pemasukan</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Kategori -->
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">KATEGORI <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-tag text-primary"></i></span>
            <select class="form-select border-start-0 ps-0" name="category_id" required>
              <option value="">— Pilih Kategori Transaksi —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"
                  <?= $catId === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Nominal -->
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">NOMINAL <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 fw-semibold text-secondary">Rp</span>
            <input type="text" class="form-control border-start-0 ps-1 fw-semibold fs-5" name="amount" inputmode="decimal"
                   value="<?= e((string)$amount) ?>" placeholder="0" required>
          </div>
          <div class="form-text small text-muted">Contoh: 1500000 atau 1.500.000,00</div>
        </div>

        <!-- Tanggal -->
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">TANGGAL <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-date text-primary"></i></span>
            <input type="date" class="form-control border-start-0 ps-0" name="tx_date" value="<?= e($date) ?>" required>
          </div>
        </div>

        <!-- Deskripsi -->
        <div>
          <label class="form-label small fw-semibold text-secondary mb-1">DESKRIPSI <span class="text-muted fw-normal">(Opsional)</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-text text-secondary"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" name="description" maxlength="255"
                   value="<?= e((string)$desc) ?>" placeholder="Catatan atau keterangan transaksi">
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex align-items-center justify-content-end gap-3 mt-3 pt-3 border-top">
          <a href="/transactions" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
          <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold shadow-sm">
            <i class="bi bi-check-lg me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
