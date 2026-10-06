# Catatan Perubahan

Semua perubahan penting pada proyek ini dicatat di berkas ini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan versi mengikuti
[Semantic Versioning](https://semver.org/lang/id/): `v0.1.0` = fondasi (selesai Fase 1),
`v1.0.0` = rilis produksi pertama.

## [Belum dirilis]

## [1.0.0] - 2026-10-06

Rilis produksi pertama (kandidat; tag menunggu keputusan pengelola).

### Ditambahkan

- Pengerasan keamanan: header keamanan global (CSP report-only, HSTS di production), cookie sesi aman, CORS API tertutup,
  trusted proxies, suite `tests/Feature/Keamanan` (IDOR, rute terlindung, XSS, tautan/SSRF, rate limit, mass assignment, data sensitif) (F10.1).
- Kinerja: indeks tambahan, uji jumlah kueri, `BebanUjiSeeder`, `docs/KINERJA.md`, `composer produksi`, `docs/PEMULIHAN.md` (F10.2).
- Dockerfile multi-stage, `compose.production.yaml` (app, web, queue/Horizon, scheduler, MySQL 8.4, Redis 7), perintah
  `sdm:hitung-ulang-hash`, `docs/DEPLOY.md` (F10.3).
- `docs/PANDUAN-PENGGUNA.md`, `docs/OPERASI.md`, uji alur utama UAT (F10.4).
- Landing page di `/` (tamu) dan panduan HTML per peran dengan tangkapan layar di `/panduan/` (`public/panduan/`).
- Dukungan sub-path `https://supportfkip.unsil.ac.id/sdm`: nginx memotong `/sdm` dan mengirim `X-Forwarded-Prefix`, Horizon di `/sdm/horizon`, cookie sesi ber-path `/sdm` (docs/DEPLOY.md §1a).

### Diperbaiki

- `TRUSTED_PROXIES=*` kini benar-benar berarti semua proxy (sebelumnya diperlakukan sebagai daftar IP literal).
- Image produksi menjalankan `filament:assets` (aset Filament tidak lagi bergantung pada berkas lokal yang di-ignore git).
- Supervisor Horizon di semua lingkungan kini menyebut `connection` (sebelumnya container `queue` gagal start di production).
- Limiter `api` tidak lagi galat untuk permintaan berautentikasi sesi.
- Penanda pendidikan tertinggi memicu pembersihan cache dasbor (sebelumnya statistik jenjang bisa basi hingga 1 jam).

### Perlu verifikasi (masih terbuka)

- BUP dosen/profesor/tendik dan pembulatan TMT pensiun; interval KP & KGB; penanda status PPPK dan KGB; syarat jabatan fungsional;
  nomenklatur jabatan struktural; kriteria urutan DUK; masa retensi arsip kepegawaian (docs/KEPUTUSAN.md, G-01).
- Teks pemberitahuan privasi (G-02) dan daftar putih domain tautan (G-10b).
- Pengukuran kinerja di MySQL/staging; CSP masih report-only sampai diuji manual.
- Uji pulihkan backup, UAT bertanda tangan perwakilan peran, dan checklist go-live (docs/05-UJI-PENERIMAAN.md).
- Integrasi SISTER/PDDIKTI belum dikerjakan (perlu akses resmi).

## [0.9.0] - 2026-10-06

### Ditambahkan

- Dasbor ber-cache (statistik, grafik jabatan/pendidikan, dosen per prodi, pensiun 5 tahun) dengan pembersihan cache saat data berubah (F9.1).
- Ekspor profil dosen prodi, beban kerja, rekognisi, pengembangan kompetensi, dan tenaga kependidikan untuk akreditasi, serta syarat unggul SDM (F9.2).
- Jumlah mahasiswa per prodi dan rasio dosen–mahasiswa (F9.3).
- DUK, rekap pejabat, daftar pensiun (PDF/Excel) dan cetak profil pegawai PDF tanpa data sensitif (F9.4).
- Sanctum dan pengelolaan token klien API di panel admin (token tampil sekali, dapat dicabut) (F9.5).
- API v1 read-only `/api/v1/dosen`, `/api/v1/dosen/{id}`, `/api/v1/pejabat` beserta `docs/API.md` (F9.6).

### Catatan

- Integrasi SISTER/PDDIKTI belum dikerjakan (memerlukan akses resmi).

## [0.8.0] - 2026-10-06

### Ditambahkan

- Pengingat tenggat KP, KGB, kenaikan jabatan fungsional, pensiun, dokumen, sertifikasi, dan studi lanjut berbasis
  konfigurasi/master; hitung ulang otomatis saat konfigurasi atau master berubah (BR-28) (F8.1).
- Jadwal harian (WIB), halaman Pengingat dengan aksi status berizin, ekspor LAP-06, dan bagian Pengingat Saya di swalayan (F8.2).
- Notifikasi pengingat bertahap idempoten (database, surel, WhatsApp opsional) dan ringkasan harian untuk admin (F8.3).

### Catatan

- Kolom bertipe `date` pada model memakai cast `date` (disimpan sebagai datetime); pencarian idempoten memakai `whereDate`.

## [0.7.0] - 2026-10-06

### Ditambahkan

- Dokumen kepegawaian dengan masa berlaku, kebijakan akses dokumen identitas, resource lintas pegawai,
  `DokumenKedaluwarsaWidget`, dan tab Dokumen di swalayan (F7.1).
- Impor rekap BKD dari Excel/CSV SISTER via antrean `impor` dengan lock per semester dan ringkasan di log audit (F7.2).
- Rekap BKD per semester/prodi, input manual, ekspor tanpa data sensitif (disk `tmp`), dan halaman BKD Saya (F7.3).
- Dokumentasi pemetaan kolom impor BKD: `docs/PEMETAAN-KOLOM-BKD.md`.

### Diperbaiki

- `MasterPendukungSeeder` tidak lagi memakai variabel `static` untuk urutan jenjang (urutan bergeser saat seeder dijalankan ulang).

## [0.6.0] - 2026-10-06

### Ditambahkan

- Usulan perubahan data terenkripsi (`usulan_perubahan`, `riwayat_status_usulan`) dengan registri target berdaftar putih,
  kunci aktif BR-05, dan transisi status sesuai diagram PRD 7.1 (F6.1).
- Panel swalayan `/saya` (Beranda, Profil Saya, Usulan Saya) dan persetujuan privasi berversi (F6.2).
- Formulir pengajuan perubahan biodata/riwayat di swalayan; skema form riwayat dipakai bersama admin dan swalayan (F6.3).
- Verifikasi usulan di panel admin: lock Redis, deteksi konflik, penerapan ke data target, snapshot tautan BR-33,
  notifikasi database dan surel, badge antrean, serta tampil data sensitif berizin (F6.4).

### Catatan

- Tautan berkas pada usulan dikonfirmasi pengusul (centang berbagi terbatas); saat disetujui tautan disimpan dengan
  konfirmasi atas nama pengusul dan dicatat di log audit.

## [0.5.0] - 2026-10-06

### Ditambahkan

- Kebijakan "tautan, bukan unggahan": tabel `tautan_berkas`, validasi `TautanBerkasValid`, kontrak `PenyimpananBerkas`,
  job `PeriksaTautanBerkas` (anti-SSRF, antrean `tautan`), route `tautan.buka` berotorisasi, uji arsitektur tanpa unggah (F5.1).
- Riwayat jabatan fungsional dengan sinkron jabatan terkini dan validasi jenjang (F5.2), pangkat/golongan dan KGB (F5.3),
  jabatan struktural/tugas tambahan dengan `PejabatAktifWidget` (F5.4), pendidikan dengan penanda tertinggi (F5.5),
  sertifikasi dengan status berlaku (F5.6), penghargaan dan pelatihan (F5.7), keluarga terenkripsi dengan akses terbatas (F5.8),
  studi lanjut yang menyelaraskan status tugas belajar (F5.9).
- Uji matriks otorisasi riwayat (10 model × 6 peran × 4 aksi) dan contoh riwayat di `DemoSeeder` (F5.10).

### Catatan

- Pimpinan dan admin-prodi tidak dapat membuka tautan berkas sensitif meskipun pimpinan memiliki izin `tautan-sensitif.lihat`
  (BR-30 menjadi acuan).
- Alias morph berbahasa Indonesia memakai `Relation::morphMap` (tidak ketat) agar log audit lama tetap terbaca.

## [0.4.0] - 2026-10-06

### Ditambahkan

- Tabel `pegawai` dengan NIK/NPWP/rekening terenkripsi, `nik_hash`, tanggal pensiun otomatis dari konfigurasi,
  dan status keaktifan dengan riwayat (F4.1).
- Resource pegawai dengan Policy per record dan scope prodi untuk admin-prodi; form tulis-saja untuk data sensitif (F4.2).
- Masking NIK/NPWP/rekening, aksi tampil berizin dengan rate limit 10/menit dan log `akses-sensitif` (F4.3).
- Impor pegawai via antrean `impor` (Filament Importer) dan pembuatan akun swalayan dengan notifikasi atur kata sandi (F4.4).
- `DemoSeeder` (hanya local/testing): akun per peran dan 40 pegawai fiktif; kata sandi dari `SEED_DEMO_PASSWORD`.
- Tabel impor/ekspor Filament memakai UUID (lihat `docs/KEPUTUSAN.md`).

## [0.3.0] - 2026-10-06

### Ditambahkan

- Master prodi dan unit kerja, kolom `users.prodi_id` (wajib bagi admin-prodi) (F3.1).
- Master status kepegawaian dan golongan PNS/PPPK dengan penanda KP/KGB/dosen tetap (F3.2).
- Master jabatan fungsional (syarat kenaikan sebagai data) dan jabatan struktural (F3.3).
- Master jenjang pendidikan, jenis sertifikasi, jenis dokumen, dan semester (satu semester aktif) (F3.4).
- Konfigurasi BUP, interval KP/KGB, pengingat, syarat unggul LAMDIK, dan privasi; cache Redis
  `sdm:konfigurasi` dan `sdm:master:*` (F3.5).

### Perlu verifikasi

Nilai seeder berikut bertanda perlu verifikasi dan harus dicek admin sebelum dipakai produksi:

- Daftar resmi prodi dan kode PDDIKTI (`MasterProdiSeeder`).
- `pppk` `berlaku_kgb` (`MasterKepegawaianSeeder`).
- Syarat kenaikan jabatan fungsional (angka kredit, masa kerja, golongan minimal) dibiarkan kosong; dasar hukum
  Permenpan RB 1/2023 dan aturan turunan Kemendiktisaintek.
- Nomenklatur jabatan struktural terhadap OTK Unsil.
- `bup_dosen` 65, `bup_profesor` 70, `bup_tendik` 58, `pembulatan_tmt_pensiun`, `interval_kp_bulan` 48,
  `interval_kgb_bulan` 24, `syarat_unggul_sdm` (BAN-PT 27/2025, hanya S1), dan `teks_kebijakan_privasi`.

## [0.2.0] - 2026-10-06

### Ditambahkan

- Filament 5 panel `/admin` (merek "SDM FKIP Unsil", notifikasi database, footer versi) dan kolom
  `nip`, `nidn`, `no_hp`, `is_aktif`, `last_login_at` pada `users` (F2.1).
- 7 peran dan 35 izin (spatie/laravel-permission, UUID), enum `Peran`, `Gate::before` super-admin,
  akses panel per peran (F2.2).
- Activitylog dengan trait `TercatatAktivitas` (kolom sensitif tanpa nilai) dan halaman Log Audit baca-saja (F2.3).
- MFA TOTP wajib bagi super-admin dan admin-kepegawaian, pembatas login, kelola pengguna dengan Reset MFA,
  gerbang Horizon `horizon.lihat` (F2.4).

## [0.1.0] - 2026-10-06

### Ditambahkan

- Laravel 13 dengan Sail (Redis 7 dan Mailpit); basis data SQLite, MySQL tidak dipakai (F1.1).
- Locale `id`, zona waktu `Asia/Jakarta`, terjemahan validasi bahasa Indonesia, serta Redis untuk
  cache, sesi, dan antrean pada DB index terpisah (F1.2).
- Laravel Horizon dengan empat supervisor: `default`, `impor`, `ekspor`, `notifikasi` (F1.3).
- Pest 4, Larastan level 6, Pint, Laravel Boost, dan endpoint `GET /api/health` (F1.4).
- UUIDv7 sebagai primary key (`users`, `sessions`, `personal_access_tokens`) beserta uji arsitektur (F1.5).
- Workflow CI (pint, larastan, pest) pada setiap pull request (F1.6).
