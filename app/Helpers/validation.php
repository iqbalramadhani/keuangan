<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Validation helpers — return true/false and (where useful) normalize.
 * Errors are collected by Controllers into a flat array, then rendered
 * in the View.
 */

final class Validation
{
    /**
     * Amount must be numeric, > 0, with up to 2 decimals.
     * Accepts plain digits or "1.234.567,89" or "1234567.89" strings.
     * Returns canonical string (with "." as decimal separator)
     * suitable for DECIMAL(15,2) columns.
     */
    public static function amount(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }
        // Replace Indonesian thousands separators (dot) with empty,
        // and decimal comma with dot.
        $s = str_replace(['.', ' '], '', $s);
        $s = str_replace(',', '.', $s);
        if (!preg_match('/^\d{1,13}(\.\d{1,2})?$/', $s)) {
            return null;
        }
        if ((float)$s <= 0) {
            return null;
        }
        return $s;
    }

    public static function date(string $raw): ?string
    {
        $raw = trim($raw);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }
        $parts = explode('-', $raw);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            return null;
        }
        return $raw;
    }

    public static function enum(array $allowed, string $v): bool
    {
        return in_array($v, $allowed, true);
    }

    public static function text(string $raw, int $maxLen = 255): string
    {
        $s = trim(strip_tags($raw));
        return mb_substr($s, 0, $maxLen);
    }

    public static function username(string $raw): ?string
    {
        $s = trim($raw);
        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $s)) {
            return null;
        }
        return $s;
    }

    public static function intId(string $raw): int
    {
        return (int)filter_var($raw, FILTER_SANITIZE_NUMBER_INT);
    }
}
