# Sistem Informasi SDM FKIP Universitas Siliwangi

[![CI](../../actions/workflows/ci.yml/badge.svg)](../../actions/workflows/ci.yml)

Aplikasi pengelolaan data Sumber Daya Manusia (dosen dan tenaga kependidikan) Fakultas Keguruan dan Ilmu
Pendidikan Universitas Siliwangi. Satu basis data terpusat memuat data induk pegawai dan riwayat karier
(jabatan fungsional, pangkat, KGB, pendidikan, sertifikasi) beserta tautan berkas SK di Google Drive, tanpa unggah ke server.
Pegawai mengajukan perubahan datanya sendiri lewat swalayan untuk diverifikasi admin, sistem mengingatkan tenggat
(kenaikan pangkat, KGB, pensiun, dokumen kedaluwarsa), dan menyediakan laporan akreditasi serta API read-only untuk sistem FKIP lain.

**Status rilis:** v1.0.0 (kandidat). Butir *perlu verifikasi* dan checklist go-live masih terbuka; lihat [CHANGELOG](CHANGELOG.md).

## Prasyarat

- PHP 8.5 (minimal 8.3) dengan ekstensi mbstring, intl, pdo_mysql, zip, gd, bcmath
- MariaDB 10.11 LTS atau lebih baru, native di host (database `db_sdm` dan `db_sdm_testing`, kolasi `utf8mb4_unicode_ci`; kolom UUID bertipe native MariaDB); SQLite tidak dipakai

> Keputusan 16 Oktober 2026 berlaku setelah Fase B; kondisi saat ini: `.env.example` dan `phpunit.xml` masih memakai SQLite; `compose.production.yaml` masih memuat container `mysql` 8.4 (target: MariaDB native di host lewat `host.docker.internal`); `docker/Dockerfile` masih PHP 8.4 dan `composer.json` `^8.3` (target PHP 8.5); nama database lama `sdm`/`sdm_testing` menjadi `db_sdm`/`db_sdm_testing` (lihat `docs/KEPUTUSAN.md`).

- Composer 2
- Node 22
- Docker Desktop / WSL2
- Git

## Dokumentasi

| Berkas | Isi |
|---|---|
| [`docs/STANDAR-TEKNIS.md`](docs/STANDAR-TEKNIS.md) | Standar teknis bersama |
| [`docs/STANDAR-GIT.md`](docs/STANDAR-GIT.md) | Standar commit, branch, tag |
| [`docs/PANDUAN-PENGGUNA.md`](docs/PANDUAN-PENGGUNA.md) | Panduan per peran dan FAQ |
| [`docs/OPERASI.md`](docs/OPERASI.md) | Pemantauan antrean, regulasi tanpa deploy, kebijakan privasi, hak subjek data |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) · [`docs/PEMULIHAN.md`](docs/PEMULIHAN.md) · [`docs/KINERJA.md`](docs/KINERJA.md) | Deploy produksi, backup/pulihkan, catatan kinerja |
| [`public/panduan/`](public/panduan/index.html) | Panduan HTML per peran dengan tangkapan layar (landing page `/panduan/`) |
| [`docs/API.md`](docs/API.md) | API integrasi read-only v1 |

## Cara kontribusi

Ikuti [`docs/STANDAR-GIT.md`](docs/STANDAR-GIT.md): satu langkah = satu commit Conventional Commits yang lolos
`pint`, `phpstan`, dan `pest`; satu fase = satu branch fitur, Pull Request, lalu tag versi.

## Setelah klon

```bash
git config core.hooksPath .githooks   # aktifkan hook commit-msg dan pre-commit
cp .env.example .env && php artisan key:generate
```

Semua perintah PHP/Composer/NPM dijalankan di container aplikasi (`sdp` = `docker compose exec sdm-php`); lihat
`docs/STANDAR-TEKNIS.md` §2 untuk Docker FKIP, Profil Ringan §1b (tanpa Redis/Horizon), dan sub-path `/sdm`.

## Menjalankan antrean

```bash
docker compose restart sdm-scheduler     # penjadwal menyalakan worker antrean (default, berat) tiap menit (§1b)
docker compose logs -f sdm-scheduler     # penjadwal lokal berjalan sebagai service
```

Tanpa dasbor antrean: pantau lewat `/api/health`, `sdp php artisan queue:failed`, dan `queue:retry <id>` (§1b).

## Penjadwal

Semua jadwal memakai zona waktu `Asia/Jakarta` dan `onOneServer()`. Jalankan lokal dengan:

```bash
sdp php artisan schedule:work   # produksi: container scheduler menjalankan schedule:work
```

| Jadwal | Perintah | Fungsi |
|---|---|---|
| Harian 00:30 | `sdm:tandai-kedaluwarsa` | Status berlaku dokumen & sertifikasi, pengingat lewat tempo (BR-20) |
| Harian 01:00 | `sdm:hitung-pengingat` | Hitung ulang pengingat KP/KGB/jabfung/pensiun/dokumen/sertifikasi/studi |
| Harian 07:00 | `sdm:kirim-pengingat` | Kirim pengingat bertahap H-90/H-30/H-7 (BR-19) |
| Senin 03:00 | `sdm:periksa-tautan` | Periksa keteraksesan tautan berkas, sensitif lebih dahulu (BR-29) |
| Setiap jam | `sdm:bersihkan-tmp` | Hapus berkas keluaran sementara > 24 jam (BR-24) |
| Bulanan, tgl 1 02:00 | `activitylog:clean` | Bersihkan log audit sesuai retensi |
| Harian | `queue:prune-failed`, `queue:prune-batches` | Kebersihan tabel antrean (jadwal standar §1b) |

Pengiriman WhatsApp bersifat opsional: set `WHATSAPP_ENABLED=true`, `WHATSAPP_ENDPOINT`, dan `WHATSAPP_TOKEN` di `.env`.

## Integrasi

Sistem lain (Akreditasi, Keuangan) membaca data dosen dan pejabat lewat API read-only bertoken Sanctum;
lihat [docs/API.md](docs/API.md). Integrasi API SISTER/PDDIKTI adalah pengembangan lanjut dan memerlukan akses resmi.
