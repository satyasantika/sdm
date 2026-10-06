# Sistem Informasi SDM FKIP Universitas Siliwangi

[![CI](../../actions/workflows/ci.yml/badge.svg)](../../actions/workflows/ci.yml)

Aplikasi pengelolaan data Sumber Daya Manusia (dosen dan tenaga kependidikan) Fakultas Keguruan dan Ilmu
Pendidikan Universitas Siliwangi. Satu basis data terpusat memuat data induk pegawai dan riwayat karier
(jabatan fungsional, pangkat, KGB, pendidikan, sertifikasi) beserta tautan berkas SK di Google Drive, tanpa unggah ke server.
Pegawai mengajukan perubahan datanya sendiri lewat swalayan untuk diverifikasi admin, sistem mengingatkan tenggat
(kenaikan pangkat, KGB, pensiun, dokumen kedaluwarsa), dan menyediakan laporan akreditasi serta API read-only untuk sistem FKIP lain.

## Prasyarat

- PHP 8.4 (minimal 8.3) dengan ekstensi mbstring, intl, pdo_sqlite, zip, gd, bcmath
- Composer 2
- Node 22
- Docker Desktop / WSL2
- Git

## Dokumentasi

| Berkas | Isi |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | Konteks untuk agen AI |
| [`docs/01-PRD.md`](docs/01-PRD.md) | Kebutuhan produk, peran, aturan bisnis |
| [`docs/02-ARSITEKTUR.md`](docs/02-ARSITEKTUR.md) | Stack, paket, Redis, panel, jadwal |
| [`docs/03-SKEMA-DATABASE.md`](docs/03-SKEMA-DATABASE.md) | ERD dan definisi tabel |
| [`docs/04-PROMPT-BERTAHAP.md`](docs/04-PROMPT-BERTAHAP.md) | Prompt bertahap fase F0–F10 |
| [`docs/05-UJI-PENERIMAAN.md`](docs/05-UJI-PENERIMAAN.md) | Skenario UAT dan checklist rilis |
| [`docs/STANDAR-TEKNIS.md`](docs/STANDAR-TEKNIS.md) | Standar teknis bersama |
| [`docs/STANDAR-GIT.md`](docs/STANDAR-GIT.md) | Standar commit, branch, tag |

## Cara kontribusi

Ikuti [`docs/STANDAR-GIT.md`](docs/STANDAR-GIT.md): satu langkah = satu commit Conventional Commits yang lolos
`pint`, `phpstan`, dan `pest`; satu fase = satu branch fitur, Pull Request, lalu tag versi.

## Setelah klon

```bash
git config core.hooksPath .githooks   # aktifkan hook commit-msg dan pre-commit
cp .env.example .env && php artisan key:generate
```

Basis data memakai SQLite (tanpa MySQL); layanan Sail yang dipakai hanya Redis dan Mailpit.

## Menjalankan antrean

```bash
./vendor/bin/sail artisan horizon        # worker antrean (default, impor, ekspor, notifikasi)
./vendor/bin/sail artisan schedule:work  # penjadwal lokal
```

Dasbor Horizon: `/horizon` (lokal terbuka; dibatasi ke peran `super-admin` pada F2.4).

## Penjadwal

Semua jadwal memakai zona waktu `Asia/Jakarta` dan `onOneServer()`. Jalankan lokal dengan:

```bash
./vendor/bin/sail artisan schedule:work   # produksi: container scheduler menjalankan schedule:work
```

| Jadwal | Perintah | Fungsi |
|---|---|---|
| Harian 00:30 | `sdm:tandai-kedaluwarsa` | Status berlaku dokumen & sertifikasi, pengingat lewat tempo (BR-20) |
| Harian 01:00 | `sdm:hitung-pengingat` | Hitung ulang pengingat KP/KGB/jabfung/pensiun/dokumen/sertifikasi/studi |
| Harian 07:00 | `sdm:kirim-pengingat` | Kirim pengingat bertahap H-90/H-30/H-7 (BR-19) |
| Senin 03:00 | `sdm:periksa-tautan` | Periksa keteraksesan tautan berkas, sensitif lebih dahulu (BR-29) |
| Setiap jam | `sdm:bersihkan-tmp` | Hapus berkas keluaran sementara > 24 jam (BR-24) |
| Bulanan, tgl 1 02:00 | `activitylog:clean` | Bersihkan log audit sesuai retensi |
| Mingguan | `queue:prune-batches`, `queue:prune-failed` | Kebersihan tabel antrean |

Pengiriman WhatsApp bersifat opsional: set `WHATSAPP_ENABLED=true`, `WHATSAPP_ENDPOINT`, dan `WHATSAPP_TOKEN` di `.env`.
