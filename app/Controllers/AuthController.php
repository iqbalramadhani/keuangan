<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Helpers\Validation;
use App\Models\LoginAttempt;
use App\Models\User;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        // If already logged in, send to dashboard.
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
            return;
        }
        $this->render('auth/login', [
            'title' => 'Masuk — Keuangan',
        ], null);
    }

    public function loginProcess(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/login');
            return;
        }
        $supplied = (string)$this->request->postInput('_csrf', '');
        if (!\App\Helpers\csrf_check($supplied)) {
            Flash::error('Sesi tidak valid, silakan coba lagi.');
            $this->redirect('/login');
            return;
        }

        $username = trim((string)$this->request->postInput('username', ''));
        $password = (string)$this->request->postInput('password', '');
        $ip       = $this->request->ip();

        $userModel  = new User($this->db);
        $attemptMdl = new LoginAttempt($this->db);

        if ($username === '' || $password === '') {
            Flash::error('Username dan password wajib diisi.');
            $this->redirect('/login');
            return;
        }

        // Rate limit: bail if too many recent failures.
        $cfg    = $this->config;
        $max    = (int)($cfg['login_max_failures'] ?? 5);
        $window = (int)($cfg['login_failure_window'] ?? 900);
        $stats  = $attemptMdl->recentFailures($username, $ip, (int)($window / 60));
        if ($stats['byUser'] >= $max || $stats['byIp'] >= $max) {
            $attemptMdl->record($username, $ip, false);
            Flash::error('Terlalu banyak percobaan login. Coba lagi nanti.');
            $this->redirect('/login');
            return;
        }

        $row = $userModel->findByUsername($username);
        if (!$row || !password_verify($password, $row['password_hash'])) {
            $attemptMdl->record($username, $ip, false);
            Flash::error('Username atau password salah.');
            $this->redirect('/login');
            return;
        }

        // Success.
        $attemptMdl->record($username, $ip, true);
        $userModel->touchLastLogin((int)$row['id']);
        \App\Helpers\auth_login((int)$row['id'], (string)$row['username']);

        Flash::success('Selamat datang, ' . $row['username'] . '.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/');
            return;
        }
        $supplied = (string)$this->request->postInput('_csrf', '');
        if (!\App\Helpers\csrf_check($supplied)) {
            $this->redirect('/login');
            return;
        }
        \App\Helpers\auth_logout();
        // Start a brand-new session to set a fresh flash cookie.
        session_regenerate_id(true);
        Flash::success('Anda telah keluar.');
        $this->redirect('/login');
    }
}
