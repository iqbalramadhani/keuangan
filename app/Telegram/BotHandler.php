<?php
declare(strict_types=1);

namespace App\Telegram;

use App\Models\Category;
use App\Models\Transaction;
use PDO;

/**
 * BotHandler — Proses semua update dari Telegram.
 *
 * Mode: Single-user (chat_id divalidasi dari TELEGRAM_ALLOWED_CHAT_ID).
 *
 * Flow utama:
 *   1. User kirim pesan teks  → parseAmount() → simpan state "pending_category"
 *      → kirim inline keyboard daftar kategori
 *   2. User tekan tombol kategori → simpan transaksi ke DB → konfirmasi
 *
 * Format pesan yang didukung:
 *   +50000 makan siang    → pemasukan Rp 50.000
 *   -25000 bensin         → pengeluaran Rp 25.000
 *   50rb kopi             → pengeluaran Rp 50.000 (tanpa tanda = expense)
 *   +50rb gaji            → pemasukan Rp 50.000
 *   50000                 → pengeluaran tanpa deskripsi
 *
 * Commands:
 *   /start   → sambutan & panduan
 *   /help    → daftar perintah
 *   /ringkasan → KPI bulan ini
 *   /terakhir  → 5 transaksi terakhir
 *   /batal     → batalkan input yang sedang berjalan
 */
final class BotHandler
{
    public function __construct(
        private readonly TelegramClient $client,
        private readonly PDO            $db,
        private readonly string         $allowedChatId,
    ) {}

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    /**
     * Proses satu update dari Telegram (parsed JSON).
     */
    public function handle(array $update): void
    {
        // Pesan teks biasa
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
            return;
        }

        // Callback dari inline keyboard
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }
    }

    // -------------------------------------------------------------------------
    // Message handler
    // -------------------------------------------------------------------------

    private function handleMessage(array $msg): void
    {
        $chatId = (string)($msg['chat']['id'] ?? '');
        $text   = trim($msg['text'] ?? '');

        // Guard: hanya chat_id yang diizinkan
        if (!$this->isAllowed($chatId)) {
            $this->client->sendMessage($chatId, '⛔ Maaf, bot ini privat.');
            return;
        }

        // Command routing
        $command = strtolower(strtok($text, ' '));
        switch ($command) {
            case '/start':
                $this->cmdStart($chatId);
                return;
            case '/help':
                $this->cmdHelp($chatId);
                return;
            case '/ringkasan':
                $this->cmdRingkasan($chatId);
                return;
            case '/terakhir':
                $this->cmdTerakhir($chatId);
                return;
            case '/batal':
                $this->clearState($chatId);
                $this->client->sendMessage($chatId, '❌ Input dibatalkan.');
                return;
        }

        // Parse amount dari teks
        $parsed = $this->parseAmount($text);
        if ($parsed === null) {
            $this->client->sendMessage(
                $chatId,
                "❓ Format tidak dikenali.\n\n" .
                "Contoh:\n" .
                "<code>+50000 gaji</code> — pemasukan\n" .
                "<code>-25000 makan</code> — pengeluaran\n" .
                "<code>50rb kopi</code> — pengeluaran (tanpa tanda = expense)\n\n" .
                "Ketik /help untuk panduan lengkap."
            );
            return;
        }

        // Simpan state pending, lalu minta pilih kategori
        $this->saveState($chatId, 'pending_category', $parsed);
        $this->sendCategoryKeyboard($chatId, $parsed);
    }

    // -------------------------------------------------------------------------
    // Callback handler (inline keyboard)
    // -------------------------------------------------------------------------

    private function handleCallback(array $cb): void
    {
        $chatId    = (string)($cb['message']['chat']['id'] ?? '');
        $messageId = (int)($cb['message']['message_id'] ?? 0);
        $data      = $cb['data'] ?? '';
        $cbId      = $cb['id'] ?? '';

        if (!$this->isAllowed($chatId)) {
            $this->client->answerCallbackQuery($cbId, '⛔ Tidak diizinkan.');
            return;
        }

        // Format callback_data: "cat:{category_id}"
        if (!str_starts_with($data, 'cat:')) {
            $this->client->answerCallbackQuery($cbId, '❓ Tidak dikenal.');
            return;
        }

        $categoryId = (int)substr($data, 4);

        // Ambil state pending
        $state = $this->getState($chatId);
        if ($state === null || $state['state'] !== 'pending_category') {
            $this->client->answerCallbackQuery($cbId, 'Tidak ada transaksi yang pending.');
            $this->client->editMessageText($chatId, $messageId, '⚠️ Sesi sudah kedaluwarsa. Silakan input ulang.');
            return;
        }

        $payload = $state['payload'];

        // Validasi kategori
        $catModel = new Category($this->db);
        $category = $catModel->find($categoryId);
        if ($category === null) {
            $this->client->answerCallbackQuery($cbId, 'Kategori tidak valid.');
            return;
        }

        // Tentukan user_id — untuk single-user bot, ambil user pertama atau dari config
        $userId = $this->resolveUserId();
        if ($userId === null) {
            $this->client->answerCallbackQuery($cbId, 'Konfigurasi user tidak ditemukan.');
            return;
        }

        // Simpan transaksi
        $txModel = new Transaction($this->db);
        $txId    = $txModel->create(
            userId:        $userId,
            categoryId:    $categoryId,
            type:          $payload['type'],
            amount:        (string)$payload['amount'],
            description:   $payload['description'] ?: null,
            txDate:        $payload['tx_date'],
            paymentMethod: 'cash',
        );

        // Hapus state
        $this->clearState($chatId);

        // Konfirmasi
        $icon     = $payload['type'] === 'income' ? '💚' : '🔴';
        $typeText = $payload['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $amount   = $this->formatRupiah($payload['amount']);
        $desc     = $payload['description'] ? "\nKeterangan: {$payload['description']}" : '';
        $date     = $payload['tx_date'];

        $this->client->answerCallbackQuery($cbId, '✅ Tersimpan!');
        $this->client->editMessageText(
            $chatId,
            $messageId,
            "{$icon} <b>{$typeText} tersimpan!</b>\n\n" .
            "💰 Nominal: <b>{$amount}</b>\n" .
            "🏷️ Kategori: <b>{$category['name']}</b>{$desc}\n" .
            "📅 Tanggal: {$date}\n" .
            "🆔 ID: #{$txId}"
        );
    }

    // -------------------------------------------------------------------------
    // Commands
    // -------------------------------------------------------------------------

    private function cmdStart(string $chatId): void
    {
        $this->client->sendMessage(
            $chatId,
            "👋 <b>Selamat datang di Bot Keuangan!</b>\n\n" .
            "Catat pemasukan & pengeluaran langsung dari Telegram.\n\n" .
            "<b>Cara input transaksi:</b>\n" .
            "• <code>+50000 gaji</code> — pemasukan Rp 50.000\n" .
            "• <code>-25000 makan</code> — pengeluaran Rp 25.000\n" .
            "• <code>50rb kopi</code> — pengeluaran Rp 50.000\n" .
            "• <code>1.5jt listrik</code> — pengeluaran Rp 1.500.000\n\n" .
            "<b>Commands:</b>\n" .
            "/ringkasan — Ringkasan keuangan bulan ini\n" .
            "/terakhir — 5 transaksi terakhir\n" .
            "/batal — Batalkan input yang sedang berjalan\n" .
            "/help — Bantuan lengkap"
        );
    }

    private function cmdHelp(string $chatId): void
    {
        $this->client->sendMessage(
            $chatId,
            "<b>📖 Panduan Penggunaan Bot Keuangan</b>\n\n" .
            "<b>Format Input Transaksi:</b>\n" .
            "<code>[+/-] [nominal] [deskripsi]</code>\n\n" .
            "<b>Contoh:</b>\n" .
            "• <code>+150000 gaji harian</code>\n" .
            "• <code>-50000 bensin</code>\n" .
            "• <code>25rb makan siang</code> (pengeluaran default)\n" .
            "• <code>+2jt project freelance</code>\n" .
            "• <code>+1.5jt</code> (tanpa deskripsi)\n\n" .
            "<b>Satuan yang didukung:</b>\n" .
            "• <code>rb</code> atau <code>ribu</code> = × 1.000\n" .
            "• <code>jt</code> atau <code>juta</code> = × 1.000.000\n\n" .
            "<b>Commands:</b>\n" .
            "/ringkasan — KPI bulan ini\n" .
            "/terakhir — 5 transaksi terakhir\n" .
            "/batal — Batalkan input\n" .
            "/help — Tampilkan panduan ini"
        );
    }

    private function cmdRingkasan(string $chatId): void
    {
        $userId = $this->resolveUserId();
        if ($userId === null) {
            $this->client->sendMessage($chatId, '⚠️ Konfigurasi user tidak ditemukan.');
            return;
        }

        $txModel = new Transaction($this->db);
        $kpi     = $txModel->kpiForCurrentMonth($userId);

        $income  = $this->formatRupiah($kpi['income']);
        $expense = $this->formatRupiah($kpi['expense']);
        $balance = $this->formatRupiah(abs($kpi['balance']));
        $balSign = $kpi['balance'] >= 0 ? '✅' : '⚠️';
        $balLabel = $kpi['balance'] >= 0 ? 'Surplus' : 'Defisit';
        $count   = (int)$kpi['count'];
        $month   = date('F Y');

        $this->client->sendMessage(
            $chatId,
            "📊 <b>Ringkasan Keuangan — {$month}</b>\n\n" .
            "💚 Pemasukan  : <b>{$income}</b>\n" .
            "🔴 Pengeluaran: <b>{$expense}</b>\n" .
            "─────────────────\n" .
            "{$balSign} {$balLabel}    : <b>{$balance}</b>\n\n" .
            "📋 Total transaksi: {$count}"
        );
    }

    private function cmdTerakhir(string $chatId): void
    {
        $userId = $this->resolveUserId();
        if ($userId === null) {
            $this->client->sendMessage($chatId, '⚠️ Konfigurasi user tidak ditemukan.');
            return;
        }

        $txModel = new Transaction($this->db);
        $rows    = $txModel->recent($userId, 5);

        if (empty($rows)) {
            $this->client->sendMessage($chatId, '📭 Belum ada transaksi.');
            return;
        }

        $lines = ["<b>📋 5 Transaksi Terakhir:</b>\n"];
        foreach ($rows as $r) {
            $icon   = $r['type'] === 'income' ? '💚' : '🔴';
            $amount = $this->formatRupiah((float)$r['amount']);
            $desc   = $r['description'] ? " — {$r['description']}" : '';
            $date   = $r['tx_date'];
            $cat    = $r['category_name'];
            $lines[] = "{$icon} <b>{$amount}</b> [{$cat}]{$desc}\n   📅 {$date}";
        }

        $this->client->sendMessage($chatId, implode("\n\n", $lines));
    }

    // -------------------------------------------------------------------------
    // Inline keyboard kategori
    // -------------------------------------------------------------------------

    private function sendCategoryKeyboard(string $chatId, array $parsed): void
    {
        $catModel   = new Category($this->db);
        $categories = $catModel->all();

        if (empty($categories)) {
            $this->client->sendMessage($chatId, '⚠️ Belum ada kategori. Tambahkan dulu di web app.');
            return;
        }

        // Susun tombol: 2 kolom
        $buttons = [];
        $row     = [];
        foreach ($categories as $i => $cat) {
            $row[] = [
                'text'          => $cat['name'],
                'callback_data' => 'cat:' . $cat['id'],
            ];
            if (count($row) === 2) {
                $buttons[] = $row;
                $row       = [];
            }
        }
        if (!empty($row)) {
            $buttons[] = $row;
        }

        $icon     = $parsed['type'] === 'income' ? '💚' : '🔴';
        $typeText = $parsed['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $amount   = $this->formatRupiah($parsed['amount']);
        $desc     = $parsed['description'] ? "\nKeterangan: <i>{$parsed['description']}</i>" : '';

        $this->client->sendInlineKeyboard(
            $chatId,
            "{$icon} <b>{$typeText}: {$amount}</b>{$desc}\n\n🏷️ Pilih kategori:",
            $buttons
        );
    }

    // -------------------------------------------------------------------------
    // Amount parser
    // -------------------------------------------------------------------------

    /**
     * Parse string input menjadi array transaksi.
     *
     * @return array{type:string, amount:float, description:string, tx_date:string}|null
     */
    private function parseAmount(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        // Tentukan tipe dari tanda di awal
        $type = 'expense'; // default
        if ($text[0] === '+') {
            $type = 'income';
            $text = ltrim(substr($text, 1));
        } elseif ($text[0] === '-') {
            $type = 'expense';
            $text = ltrim(substr($text, 1));
        }

        // Pecah menjadi token pertama (nominal) dan sisa (deskripsi)
        $parts       = preg_split('/\s+/', $text, 2);
        $nominalStr  = $parts[0] ?? '';
        $description = trim($parts[1] ?? '');

        // Parsing nominal dengan dukungan satuan:
        // 50000 / 50.000 / 50,000 / 50rb / 50ribu / 50jt / 50juta / 1.5jt
        $nominal = $this->parseNominal($nominalStr);
        if ($nominal === null || $nominal <= 0) {
            return null;
        }

        return [
            'type'        => $type,
            'amount'      => $nominal,
            'description' => $description,
            'tx_date'     => date('Y-m-d'),
        ];
    }

    /**
     * Parse string nominal menjadi float.
     * Mendukung: 50000, 50.000, 50,000, 50rb, 50ribu, 50jt, 50juta, 1.5jt, 1,5juta
     */
    private function parseNominal(string $s): ?float
    {
        $s = strtolower(trim($s));
        if ($s === '') {
            return null;
        }

        $multiplier = 1.0;

        // Cek satuan suffix
        if (str_ends_with($s, 'juta') || str_ends_with($s, 'jt')) {
            $multiplier = 1_000_000.0;
            $s = preg_replace('/(juta|jt)$/', '', $s);
        } elseif (str_ends_with($s, 'ribu') || str_ends_with($s, 'rb')) {
            $multiplier = 1_000.0;
            $s = preg_replace('/(ribu|rb)$/', '', $s);
        }

        // Hapus pemisah ribuan titik/koma jika ada (selain desimal)
        // Deteksi desimal: "1.5" atau "1,5"
        // Jika ada titik+koma atau lebih dari satu titik → titik = pemisah ribuan
        $dotCount   = substr_count($s, '.');
        $commaCount = substr_count($s, ',');

        if ($dotCount > 1 || ($dotCount === 1 && $commaCount === 1)) {
            // Format: 1.500.000 atau 1.500,00 → hapus titik, ganti koma dengan titik
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif ($commaCount === 1 && $dotCount === 0) {
            // Format: 1,5 (desimal pakai koma) atau 50,000 (ribuan pakai koma)
            $parts = explode(',', $s);
            if (strlen($parts[1] ?? '') === 3) {
                // 50,000 → pemisah ribuan
                $s = str_replace(',', '', $s);
            } else {
                // 1,5 → desimal
                $s = str_replace(',', '.', $s);
            }
        } elseif ($dotCount === 1) {
            // Satu titik: bisa 1.5 (desimal) atau 50.000 (ribuan)
            $parts = explode('.', $s);
            if (strlen($parts[1] ?? '') === 3) {
                // 50.000 → pemisah ribuan
                $s = str_replace('.', '', $s);
            }
            // else: 1.5 → biarkan sebagai desimal
        }

        $s = trim($s);
        if (!is_numeric($s)) {
            return null;
        }

        return (float)$s * $multiplier;
    }

    // -------------------------------------------------------------------------
    // State management (stored in DB)
    // -------------------------------------------------------------------------

    private function saveState(string $chatId, string $state, array $payload): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO telegram_bot_state (chat_id, state, payload)
             VALUES (:cid, :st, :pl)
             ON DUPLICATE KEY UPDATE state = :st, payload = :pl, updated_at = NOW()'
        );
        $stmt->execute([
            ':cid' => $chatId,
            ':st'  => $state,
            ':pl'  => json_encode($payload),
        ]);
    }

    private function getState(string $chatId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT state, payload FROM telegram_bot_state WHERE chat_id = :cid LIMIT 1'
        );
        $stmt->execute([':cid' => $chatId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'state'   => $row['state'],
            'payload' => json_decode($row['payload'] ?? '{}', true) ?? [],
        ];
    }

    private function clearState(string $chatId): void
    {
        $this->db->prepare('DELETE FROM telegram_bot_state WHERE chat_id = :cid')
                 ->execute([':cid' => $chatId]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isAllowed(string $chatId): bool
    {
        if ($this->allowedChatId === '') {
            // Jika tidak dikonfigurasi, tolak semua
            return false;
        }
        return $chatId === $this->allowedChatId;
    }

    /**
     * Resolusi user_id untuk single-user mode.
     * Ambil user pertama dari DB (karena hanya 1 user).
     */
    private function resolveUserId(): ?int
    {
        $stmt = $this->db->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
