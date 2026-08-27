<?php
declare(strict_types=1);
/**
 * System Logs View
 * @var array{file:string, content:string} $__
 */
$file = $file ?? '';
$content = $content ?? '';
?>
<div class="page">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Log: <?= e($file) ?></h1>
      <p class="text-muted small mb-0">Isi lengkap dari file log.</p>
    </div>
    <a href="/system/logs" class="btn btn-outline-secondary rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-arrow-left"></i>
      <span>Kembali</span>
    </a>
  </div>

  <section class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
      <pre class="m-0 p-4 bg-dark text-light" style="max-height: 70vh; overflow-y: auto; font-size: 0.85rem; white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;"><?= e($content) ?></pre>
    </div>
  </section>
</div>
