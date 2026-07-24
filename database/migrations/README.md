# Database Migrations

Versioned SQL files applied by `scripts/migrate.php` and `setup.php`.

## Cara menambah migration baru

1. Buat file baru dengan nama `NNN_deskripsi.sql` di folder ini, di mana `NNN`
   adalah nomor urut 3-digit yang melanjutkan urutan tertinggi. Contoh:
   `002_add_user_email.sql`.
2. Tulis SQL biasa (DDL, DML, ALTER, dst.). Setiap file dijalankan dalam
   satu transaction — jika ada statement gagal, file di-rollback dan tidak
   ditandai applied.
3. Jalankan `php scripts/migrate.php` di lokal untuk uji.
4. Commit. Saat `setup.php` dijalankan di server (atau operator menjalankan
   `php scripts/migrate.php`), file baru akan terpasang otomatis.

## Konvensi

- **Penomoran**: zero-padded 3 digit (`001`, `002`, ..., `099`, `100`). Sort
  lexicographic harus sama dengan sort numerik.
- **Idempotent where possible**: gunakan `CREATE TABLE IF NOT EXISTS`,
  `INSERT IGNORE`, dan `ADD COLUMN IF NOT EXISTS` (MySQL 8.0+) supaya
  migration aman di-replay (berguna untuk debugging lokal).
- **Forward-only**: tidak ada file `down` / rollback. Untuk development,
  gunakan `php scripts/migrate.php fresh --confirm` untuk reset database.

## Perintah CLI

```bash
php scripts/migrate.php             # up (default) — apply pending
php scripts/migrate.php up          # sama
php scripts/migrate.php status      # tampilkan applied vs pending
php scripts/migrate.php fresh --confirm   # drop semua tabel & rebuild
```

`fresh` menghancurkan seluruh isi database; selalu hanya untuk local dev.

## Mekanisme internal

- Tabel `schema_migrations(version PK, applied_at)` melacak file yang sudah
  dipasang.
- Tabel itu dibuat otomatis saat migrator pertama kali berjalan.
- `setup.php` memanggil Migrator sebelum membuat admin user, sehingga fresh
  install = setup.php sekali saja, dan upgrade = setup.php lagi kapanpun
  ada migration baru.
