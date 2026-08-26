<?php
declare(strict_types=1);

namespace App\Telegram;

/**
 * TelegramClient — thin curl wrapper untuk Telegram Bot API.
 * Tidak membutuhkan library eksternal.
 */
final class TelegramClient
{
    private string $baseUrl;

    public function __construct(string $token)
    {
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    /**
     * Kirim pesan teks biasa ke chat_id tertentu.
     *
     * @param array<string,mixed> $extra Parameter tambahan (parse_mode, dll)
     */
    public function sendMessage(int|string $chatId, string $text, array $extra = []): array
    {
        return $this->call('sendMessage', array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    /**
     * Kirim pesan dengan inline keyboard.
     *
     * @param array<array<array{text:string, callback_data:string}>> $buttons
     *   Array 2D: baris → kolom → tombol.
     *   Contoh: [[['text'=>'A','callback_data'=>'a'],['text'=>'B','callback_data'=>'b']]]
     */
    public function sendInlineKeyboard(
        int|string $chatId,
        string     $text,
        array      $buttons,
        array      $extra = []
    ): array {
        return $this->sendMessage($chatId, $text, array_merge($extra, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]));
    }

    /**
     * Answer callback query (hapus loading indicator di inline button).
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): array
    {
        return $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
        ]);
    }

    /**
     * Edit teks pesan yang sudah terkirim (dipakai setelah user pilih kategori).
     */
    public function editMessageText(
        int|string $chatId,
        int        $messageId,
        string     $text,
        array      $extra = []
    ): array {
        return $this->call('editMessageText', array_merge([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    /**
     * Daftarkan webhook URL ke Telegram.
     */
    public function setWebhook(string $url, string $secretToken = ''): array
    {
        $params = ['url' => $url];
        if ($secretToken !== '') {
            $params['secret_token'] = $secretToken;
        }
        return $this->call('setWebhook', $params);
    }

    /**
     * Hapus webhook (kembali ke polling).
     */
    public function deleteWebhook(): array
    {
        return $this->call('deleteWebhook', []);
    }

    /**
     * Info webhook yang terdaftar.
     */
    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo', []);
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * POST ke Telegram API. Throws RuntimeException jika curl gagal.
     */
    private function call(string $method, array $params): array
    {
        $url = "{$this->baseUrl}/{$method}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            error_log("[TelegramClient] curl error #{$errno} on {$method}");
            return ['ok' => false, 'description' => 'curl_error'];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log("[TelegramClient] bad JSON response on {$method}: {$body}");
            return ['ok' => false, 'description' => 'bad_json'];
        }

        if (!($decoded['ok'] ?? false)) {
            error_log("[TelegramClient] API error on {$method}: " . ($decoded['description'] ?? '?'));
        }

        return $decoded;
    }
}
