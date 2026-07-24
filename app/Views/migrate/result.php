<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        .ok   { background: #d4edda; border-left: 4px solid #28a745; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .err  { background: #f8d7da; border-left: 4px solid #dc3545; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { text-align: left; padding: 6px 10px; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; }
        form { margin-top: 1rem; }
        button { background: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .info { color: #6c757d; font-size: 0.9em; margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>Database Migration — Keuangan</h1>

    <?php if (isset($result)): ?>
        <?php if ($result['status'] === 'success'): ?>
            <div class="ok">
                <strong>✅ <?= htmlspecialchars($result['message']) ?></strong>
                <?php if (!empty($result['applied'])): ?>
                    <ul>
                        <?php foreach ($result['applied'] as $v): ?>
                            <li><?= htmlspecialchars($v) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="err">
                <strong>❌ <?= htmlspecialchars($result['message']) ?></strong>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="/migrate">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
        <table>
            <tr><th>Status</th><th>Jumlah</th></tr>
            <tr><td>Applied</td><td><?= count($status['applied']) ?></td></tr>
            <tr><td>Pending</td><td><?= count($status['pending']) ?></td></tr>
        </table>
        <button type="submit"><?= empty($status['pending']) ? 'Re-run Migration' : 'Jalankan Migration' ?></button>
    </form>

    <?php if (!empty($status['pending'])): ?>
        <p class="info">Migration pending:</p>
        <ul class="info">
            <?php foreach ($status['pending'] as $p): ?>
                <li><?= htmlspecialchars($p) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p class="info"><a href="/">← Kembali ke Dashboard</a></p>
</body>
</html>
