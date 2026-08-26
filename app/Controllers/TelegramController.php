<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Telegram\BotHandler;
use App\Telegram\TelegramClient;

/**
 * TelegramController — handle webhook POST dari Telegram dan setup awal.
 *
 * Routes:
 *   POST /telegram/webhook  → webhook()   (dipanggil Telegram secara otomatis)
 *   GET  /telegram/setup    → setup()     (dijalankan sekali oleh operator)
 *   GET  /telegram/info     → info()      (cek status webhook)
 */
final class TelegramController extends Controller
{
    // -------------------------------------------------------------------------
    // Webhook endpoint (dipanggil oleh Telegram)
    // -------------------------------------------------------------------------

    public function webhook(): void
    {
        // 1. Validasi secret token dari header (Telegram mengirim ini)
        $secret = \App\Bootstrap::env('TELEGRAM_WEBHOOK_SECRET', '');
        if ($secret !== '') {
            $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if (!hash_equals($secret, $headerSecret)) {
                http_response_code(403);
                echo 'Forbidden';
                return;
            }
        }

        // 2. Baca body JSON
        $body   = file_get_contents('php://input');
        $update = json_decode($body ?: '', true);
        if (!is_array($update)) {
            http_response_code(200); // selalu 200 ke Telegram agar tidak retry
            echo 'ok';
            return;
        }

        // 3. Delegate ke BotHandler
        $token = \App\Bootstrap::env('TELEGRAM_BOT_TOKEN', '');

        $client  = new TelegramClient($token);
        $handler = new BotHandler($client, $this->db);

        try {
            $handler->handle($update);
        } catch (\Throwable $e) {
            error_log('[TelegramController] ' . $e->getMessage());
        }

        // 4. Selalu balas 200 ke Telegram (jika gagal, Telegram akan retry)
        http_response_code(200);
        echo 'ok';
    }

    // -------------------------------------------------------------------------
    // Setup webhook (jalankan sekali oleh operator)
    // -------------------------------------------------------------------------

    public function setup(): void
    {
        // Proteksi dengan SETUP_TOKEN supaya tidak sembarangan diakses
        $token       = \App\Bootstrap::env('SETUP_TOKEN', '');
        $inputToken  = $this->request->getInput('token', '');
        if ($token === '' || !hash_equals($token, $inputToken)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid setup token. Tambahkan ?token=SETUP_TOKEN di URL.']);
            return;
        }

        $botToken = \App\Bootstrap::env('TELEGRAM_BOT_TOKEN', '');
        if ($botToken === '') {
            http_response_code(500);
            echo json_encode(['error' => 'TELEGRAM_BOT_TOKEN belum diisi di .env']);
            return;
        }

        $appUrl      = rtrim(\App\Bootstrap::env('APP_URL', ''), '/');
        $webhookUrl  = $appUrl . '/telegram/webhook';
        $secret      = \App\Bootstrap::env('TELEGRAM_WEBHOOK_SECRET', '');

        $client = new TelegramClient($botToken);
        $result = $client->setWebhook($webhookUrl, $secret);

        header('Content-Type: application/json');
        echo json_encode([
            'webhook_url' => $webhookUrl,
            'telegram'    => $result,
        ], JSON_PRETTY_PRINT);
    }

    // -------------------------------------------------------------------------
    // Info webhook (cek status)
    // -------------------------------------------------------------------------

    public function info(): void
    {
        $token       = \App\Bootstrap::env('SETUP_TOKEN', '');
        $inputToken  = $this->request->getInput('token', '');
        if ($token === '' || !hash_equals($token, $inputToken)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid setup token.']);
            return;
        }

        $botToken = \App\Bootstrap::env('TELEGRAM_BOT_TOKEN', '');
        $client   = new TelegramClient($botToken);
        $result   = $client->getWebhookInfo();

        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
