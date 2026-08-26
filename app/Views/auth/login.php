<?php
declare(strict_types=1);
/**
 * Standalone login page.
 * @var array $__flashes
 */
$flashes = $__flashes ?? [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk — Keuangan</title>
  <meta name="description" content="Masuk ke aplikasi Keuangan untuk mencatat dan memantau keuangan Anda.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f4f6fb;
      padding: 1.5rem;
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 20px;
      border: 1px solid #e8ecf4;
      box-shadow: 0 8px 40px -8px rgba(15, 23, 42, 0.12);
      padding: 2.5rem;
    }

    /* Logo + Header */
    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-logo {
      width: 56px; height: 56px;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    }

    .login-title {
      font-size: 1.6rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 0.25rem;
      letter-spacing: -0.02em;
    }

    .login-subtitle {
      color: #94a3b8;
      font-size: 0.9rem;
    }

    /* Fields */
    .field-wrap { margin-bottom: 1.25rem; }

    .field-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.5rem;
      display: block;
    }

    .input-box {
      display: flex;
      align-items: center;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .input-box:focus-within {
      border-color: #4f46e5;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .input-icon {
      padding: 0 0.85rem;
      color: #94a3b8;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .input-box input {
      flex: 1;
      border: none;
      outline: none;
      padding: 0.8rem 0.85rem 0.8rem 0;
      font-family: 'Outfit', sans-serif;
      font-size: 0.95rem;
      color: #0f172a;
      background: transparent;
    }

    .input-box input::placeholder { color: #c0c9d8; }

    .toggle-pw-btn {
      border: none;
      background: none;
      padding: 0 0.85rem;
      color: #94a3b8;
      cursor: pointer;
      font-size: 1rem;
      line-height: 1;
      transition: color 0.15s;
    }
    .toggle-pw-btn:hover { color: #475569; }

    /* Submit */
    .btn-login {
      width: 100%;
      padding: 0.85rem;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      border: none;
      border-radius: 12px;
      color: #fff;
      font-family: 'Outfit', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 0.5rem;
    }
    .btn-login:hover  { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4); }
    .btn-login:active { transform: translateY(0); }

    /* Alerts */
    .login-alert {
      padding: 0.75rem 1rem;
      border-radius: 10px;
      font-size: 0.875rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .login-alert.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .login-alert.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .login-alert.info    { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
  </style>
</head>
<body>
  <div class="login-card">

    <div class="login-header">
      <div class="login-logo">
        <i class="bi bi-wallet2 text-white fs-4"></i>
      </div>
      <h1 class="login-title">Keuangan</h1>
      <p class="login-subtitle">Masuk ke akun Anda untuk melanjutkan.</p>
    </div>

    <!-- Flash messages -->
    <?php foreach ($flashes as $f): ?>
      <?php
        $cls  = match($f['type']) { 'success' => 'success', 'error' => 'error', default => 'info' };
        $icon = match($f['type']) { 'success' => 'bi-check-circle-fill', 'error' => 'bi-exclamation-circle-fill', default => 'bi-info-circle-fill' };
      ?>
      <div class="login-alert <?= $cls ?>">
        <i class="bi <?= $icon ?>"></i>
        <span><?= e($f['msg']) ?></span>
      </div>
    <?php endforeach; ?>

    <form action="/login" method="post">
      <?= \App\Helpers\csrf_field() ?>

      <div class="field-wrap">
        <label class="field-label" for="username">Username</label>
        <div class="input-box">
          <span class="input-icon"><i class="bi bi-person"></i></span>
          <input type="text" id="username" name="username"
                 autocomplete="username" placeholder="Masukkan username"
                 required autofocus>
        </div>
      </div>

      <div class="field-wrap">
        <label class="field-label" for="password">Password</label>
        <div class="input-box">
          <span class="input-icon"><i class="bi bi-lock"></i></span>
          <input type="password" id="password" name="password"
                 autocomplete="current-password" placeholder="••••••••"
                 required>
          <button type="button" class="toggle-pw-btn" id="toggle-pw" title="Tampilkan password">
            <i class="bi bi-eye" id="toggle-pw-icon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right"></i>
        Masuk
      </button>
    </form>

  </div>

  <script>
    const btn  = document.getElementById('toggle-pw');
    const icon = document.getElementById('toggle-pw-icon');
    const pw   = document.getElementById('password');
    btn.addEventListener('click', () => {
      const show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  </script>
</body>
</html>
