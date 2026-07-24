<?php
/**
 * OPTIONAL — DB config fallback.
 *
 * Most users should use a `.env` file in the project root instead of this
 * file. This file is provided as a fallback for environments where .env
 * is not appropriate. Copy this file to `db.php` and adjust the values.
 *
 * NOTE: Both `db.php` and `.env` live above the public/ webroot and are
 * therefore never exposed via HTTP.
 */

return [
    'host'    => '127.0.0.1',
    'port'    => 3306,
    'name'    => 'keuangan',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
];
