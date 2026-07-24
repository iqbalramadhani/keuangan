<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Money formatting — Indonesian Rupiah (no decimal digits).
 */
final class Money
{
    public static function formatRupiah($amount): string
    {
        $cfg = \App\Bootstrap::$config;
        $locale   = $cfg['locale']  ?? 'id_ID';
        $currency = $cfg['currency'] ?? 'IDR';
        $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);
        return $fmt->formatCurrency((float)$amount, $currency);
    }

    public static function formatPlain($amount): string
    {
        $cfg = \App\Bootstrap::$config;
        $locale = $cfg['locale'] ?? 'id_ID';
        $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        return $fmt->format((float)$amount);
    }

    public static function sign($type): string
    {
        return $type === 'income' ? '+' : '-';
    }
}
