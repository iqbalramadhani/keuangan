# Keuangan — Aplikasi Pencatatan Keuangan

Aplikasi web sederhana untuk mencatat uang masuk/keluar dengan kategorisasi dan summary report (chart bulanan).

- Backend: PHP murni + MySQL (pola **MVC** hand-rolled, tanpa framework)
- Frontend: Vanilla JS (no framework)
- Deployment: Shared hosting (cPanel/XAMPP) via FTP, otomatis via **GitLab CI/CD**
- Konfigurasi: file `.env` di root project

## Fitur

- Login (single user, password bcrypt)
- Catat transaksi: uang masuk & keluar
- Subkategori per tipe (Pemasukan / Pengeluaran)
- CRUD transaksi dengan filter tanggal, tipe, kategori
- Dashboard: KPI bulan ini + chart 12 bulan (income vs expense)
- Aman: CSRF, prepared statements, session secure, output escaping, rate-limit login

## Setup Lokal

```bash
# 1. Salin .env.example menjadi .env dan isi kredensial Anda
cp .env.example .env
# edit .env → set DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
# (database belum perlu dibuat; CREATE DATABASE otomatis bila user punya hak)

# 2. Buat user admin pertama (jika tabel users kosong)
php setup.php admin yourPassword
# Setelah deploy ke hosting, migration otomatis saat request pertama

# 3. Jalankan dev server
cd public
php -S 127.0.0.1:8000
# → buka http://127.0.0.1:8000/login
```

## Update Schema Setelah Ada Migration Baru

**Production: otomatis via CI/CD** — setiap push ke `main` → workflow GitHub Actions otomatis trigger `curl /setup.php` ke hosting untuk jalankan migration.

```bash
# Lokal / staging: jalankan manual
php scripts/migrate.php

# Lihat status (applied vs pending)
php scripts/migrate.php status

# Development: drop semua tabel & re-run semua migration dari awal
php scripts/migrate.php fresh --confirm
```

Lihat [database/migrations/README.md](database/migrations/README.md) untuk cara
menambah migration baru.

### Setelah ada perubahan schema di server

Setelah push ke GitHub dan deploy otomatis selesai (file naik via FTP):

**Opsi 1 — Browser (paling aman):**
Buka `http://domain-anda.com/setup.php` di browser. Halaman akan:
- Jalankan semua pending migration
- Tampilkan hasil + buat admin jika tabel users kosong
- Opsional: prompt username + password interaktif

**Opsi 2 — CLI via SSH (jika hosting support SSH):**
```bash
ssh user@host 'php /path/to/setup.php'
# atau
ssh user@host 'php /path/to/scripts/migrate.php'
```

---

## CI/CD ke Shared Hosting via GitHub Actions

Workflow ada di `.github/workflows/deploy.yml`. Setiap push ke branch `main`:

1. **lint** — `php -l` di setiap file PHP.
2. **deploy** — `SamKirkland/FTP-Deploy-Action` upload repo ke hosting, kecuali file di-exclude (`.env`, `config/db.php`, `setup.php`, dll).

### Setup secret

1. Push repo ke GitHub.
2. Settings → Secrets and variables → Actions → New repository secret:
   - `FTP_HOST` — hostname FTP (mis. `ftp.yourdomain.com`)
   - `FTP_USER` — user FTP
   - `FTP_PASSWORD` — password FTP
   - `DEPLOY_URL` — domain hosting tanpa http/https (mis. `keuangan.kamu.com`)
   - `DEPLOY_TOKEN` — string random panjang, harus sama dengan `SETUP_TOKEN` di `.env` hosting
3. Di hosting, tambahkan `SETUP_TOKEN` yang sama ke file `.env` (jangan di-commit).

### Alur database update

**Otomatis via CI/CD** — setelah push ke `main`, workflow GitHub Actions otomatis memanggil `curl /setup.php` di hosting untuk menjalankan migration. Tidak perlu buka `/setup.php` secara manual.

Jika terjadi error migration, cek `runtime/php-error.log` di hosting, atau buka `/setup.php` langsung di browser untuk melihat detail error-nya.

`config/db.php` dan `.env` di-exclude dari mirror sehingga secret tidak ikut ter-upload. Schema migration dijalankan di server lewat `setup.php` (browser) atau `scripts/migrate.php` (CLI/SSH).

`config/db.php` dan `.env` di-exclude dari mirror sehingga secret tidak ikut ter-upload. Schema migration dijalankan di server lewat `php setup.php` atau `php scripts/migrate.php` setelah deploy (database `schema_migrations` melacak status per-DB).

## Backup

```bash
mysqldump --single-transaction keuangan > backup_$(date +%F).sql
```

## Struktur Folder

```
keuangan/
├── public/          # webroot (index.php + assets)
├── app/             # MVC: Core/, Controllers/, Models/, Views/, Helpers/
├── config/          # app.php + db.example.php (opsional, .env lebih utama)
├── database/migrations/  # versioned SQL migrations (NNN_*.sql)
├── scripts/migrate.php   # CLI: up / status / fresh untuk migrations
└── .env.example     # template kredensial (.env di-gitignore)
```

## Keamanan

- Password: bcrypt (`PASSWORD_BCRYPT`)
- Session: HttpOnly + SameSite=Lax + Secure-if-HTTPS; `session_regenerate_id(true)` saat login; idle timeout 30 menit
- DB: PDO dengan `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`; semua query prepared
- CSRF: per-session 32-byte token, validasi setiap POST
- Output: htmlspecialchars dengan ENT_QUOTES + UTF-8
- Validasi server-side di setiap Controller
- Rate limit login via `login_attempts` table (default 5x/15min)
- `.htaccess` deny untuk folder non-public & security headers
