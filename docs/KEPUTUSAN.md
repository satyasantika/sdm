# Catatan Keputusan Teknis

## UUID pada tabel impor/ekspor Filament (STANDAR-TEKNIS §4a butir 5)

Model bawaan Filament (`Import`, `FailedImportRow`, `Export`) tidak memakai `HasUuids`, tetapi Filament
meresolusinya lewat container (`app(Import::class)`). Karena itu `AppServiceProvider::register()` mengikat
ketiganya ke subkelas `App\Models\Impor`, `BarisImporGagal`, dan `Ekspor` yang memakai `HasUuids`. Tabel
`imports`, `failed_import_rows`, dan `exports` memakai kunci UUID sehingga **tidak ada pengecualian** untuk tabel ini.
Relasi `failedRows()`/`import()` dioverride dengan kunci asing eksplisit `import_id`.

## Database lokal: SQLite

MySQL dikecualikan atas permintaan pemilik proyek. Pengembangan dan uji memakai SQLite; migrasi bergantung-driver
(mis. FULLTEXT `pegawai.nama`) hanya dijalankan pada driver `mysql`.

## Profil Ringan: tanpa Redis, Horizon, Octane (15 Oktober 2026)

Mengikuti STANDAR-TEKNIS §1b, spesifikasi di `vibecoding/` beralih ke Profil Ringan: antrean (`database`, dua antrean
`default` dan `berat`), cache, sesi, dan lock berada di MySQL; worker dinyalakan penjadwal tiap menit
(`--stop-when-empty`) dalam satu container `scheduler`; pemantauan lewat `/api/health`. LMS ditunda. Kode berjalan
dan dokumen operasi produksi (`docs/DEPLOY.md`, `docs/OPERASI.md`, `docs/PEMULIHAN.md`) masih menggambarkan Redis/Horizon
sampai migrasi Fase B selesai; setelah itu dokumen tersebut dirapikan. Catatan keputusan lama di atas tidak diubah.

## Keputusan Pemilik 16 Oktober 2026: PHP 8.5 dan MariaDB native

Berlaku untuk semua sistem (STANDAR-TEKNIS diperbarui): (1) PHP 8.5, bukan 8.4; (2) basis data MariaDB 10.11 LTS atau lebih baru,
native di host (bukan MySQL 8.4, bukan container); (3) SQLite tidak dipakai untuk pengembangan, uji, maupun produksi;
(4) kolasi baku `utf8mb4_unicode_ci` (bukan `utf8mb4_0900_ai_ci`). Koneksi Laravel memakai `DB_CONNECTION=mariadb`; uji memakai database
`sdm_testing` di MariaDB. Keputusan ini menggantikan bagian "Database lokal: SQLite" di atas (catatan lama tidak diubah) dan berlaku setelah
Fase B; kondisi kode saat ini masih SQLite untuk dev/uji dan MySQL 8.4 di `compose.production.yaml`. Dokumen spesifikasi (`vibecoding/`) sudah
mengikuti keputusan ini; fitur khusus MySQL yang perlu ditinjau dicatat dalam laporan penyelarasan.

## Keputusan Pemilik 16 Oktober 2026 (lanjutan): nama database, UUID native, produksi native, PHP 8.5

Berlaku untuk semua sistem (STANDAR-TEKNIS §2.1, §2.2, §4a, §7 diperbarui) dan untuk sdm **setelah Fase B**:
(1) nama database seragam `db_<app>`/`db_<app>_testing`, sehingga sdm memakai `db_sdm` dan `db_sdm_testing` (nama pengguna DB `sdm` tidak berubah;
catatan di atas yang menyebut `sdm_testing` tidak ditulis ulang). (2) UUID mengikuti Laravel 13: `uuid()`/`foreignUuid()`/`uuidMorphs()` menghasilkan tipe
UUID native MariaDB; UUIDv7 dibuat aplikasi lewat `HasUuids`. Klaim "CHAR(36)" sebagai tipe kolom dihapus dari dokumen; `char('id', 36)` dan
`binary(16)` eksplisit dilarang; gerbang uji: kolom `id` bertipe uuid/char(36), 100 id UUIDv7 terurut sesuai `ORDER BY id`, FK dan morph berfungsi.
(3) Produksi memakai MariaDB native di host lewat `host.docker.internal` (`extra_hosts: host-gateway`), seperti aplikasi produksi lain; compose produksi
tidak boleh memuat container database. Kondisi saat ini: `compose.production.yaml` masih memuat container `mysql` 8.4, ditandai butir Fase B. CI boleh
memakai service container sementara (`MARIADB_*`, `healthcheck.sh --connect --innodb_initialized`); alat cadangan `mariadb-dump`.
(4) PHP 8.5: `docker/Dockerfile` masih `php:8.4-fpm-alpine` dan `composer.json` `^8.3`, butir Fase B (dev container sudah PHP 8.5 lewat `php/laravel8.5.Dockerfile`).
(5) Blok `<laravel-boost-guidelines>` di `CLAUDE.md` (masih PHP 8.4) tidak diedit manual; digenerate ulang oleh `php artisan boost:install` saat Fase B.
(6) `.env.example` dan `phpunit.xml` masih SQLite, butir Fase B (target `DB_CONNECTION=mariadb`, `db_sdm_testing`).
Antrean ekspor lama pada `vibecoding/04` dikoreksi menjadi antrean `berat`.
