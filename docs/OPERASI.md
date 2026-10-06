# Panduan Operasi

Untuk super-admin dan operator server. Perintah `docker compose` memakai `-f compose.production.yaml`.

## 1. Memantau antrean (Horizon)

- Buka `/horizon` (di produksi sub-path: `/sdm/horizon`; hanya super-admin). Lima supervisor harus aktif: `default`, `impor`, `ekspor`, `notifikasi`, `tautan`.
- Dari CLI: `docker compose exec queue php artisan horizon:status`.
- Metrik yang diperhatikan: *Failed Jobs* (harus 0), *Wait Time* per antrean, *Recent Jobs*.

## 2. Menjalankan ulang job gagal

- Horizon → *Failed* → pilih job → **Retry**; atau `php artisan queue:retry <uuid>` / `queue:retry all`.
- Bersihkan yang tidak relevan: `php artisan queue:forget <uuid>` atau `queue:flush`.
- Setelah deploy yang mengubah kelas job: `php artisan horizon:terminate` (container `queue` restart otomatis).
- Jadwal: `docker compose logs scheduler`; daftar: `php artisan schedule:list`. Perintah penting:
  `sdm:tandai-kedaluwarsa` (00:30), `sdm:hitung-pengingat` (01:00), `sdm:kirim-pengingat` (07:00), `sdm:periksa-tautan` (Senin 03:00),
  `sdm:bersihkan-tmp` (tiap jam; berkas ekspor sementara dihapus ≤ 24 jam).

## 3. Mengubah regulasi tanpa deploy

Seluruh angka regulasi ada di data, bukan kode:
- **Status kepegawaian / golongan / jabatan fungsional / jenjang / jabatan struktural**: menu **Master**. Tambah baris baru atau
  nonaktifkan; penanda seperti *berlaku kenaikan pangkat*, *dihitung dosen tetap*, dan *urutan* memengaruhi pengingat, DUK, dan statistik.
- **BUP, pembulatan TMT pensiun, interval KP/KGB, ambang rasio, syarat unggul SDM**: **Sistem → Konfigurasi**. Perubahan menghitung
  ulang pengingat otomatis (job `HitungUlangPengingat`).
- Catat setiap perubahan beserta dasar hukumnya di docs/KEPUTUSAN.md. Nilai bertanda *perlu verifikasi* wajib dikonfirmasi ke
  Subbagian Kepegawaian/Biro SDM.

## 4. Memperbarui kebijakan privasi

1. **Sistem → Konfigurasi**: ubah *Teks kebijakan privasi* (Markdown; HTML mentah dibuang) dan naikkan *Versi kebijakan privasi* (mis. `2026.2`).
2. Semua dosen/tendik diminta menyetujui ulang pada login berikutnya. Persetujuan tersimpan per versi.
3. Teks harus disetujui pimpinan/bagian hukum sebelum versi dinaikkan.

## 5. Permintaan hak subjek data

| Hak | Penanganan |
|---|---|
| Akses | Pegawai melihat data sendiri di *Profil Saya* dan mencetak profil PDF. Salinan lengkap: admin kepegawaian mengekspor atas permintaan tertulis dan menyerahkannya langsung (tidak lewat surel terbuka). |
| Koreksi | Lewat **usulan perubahan** (swalayan) atau admin kepegawaian bila pegawai tidak dapat memakai swalayan; tercatat di log audit. |
| Penghapusan | Eskalasi ke pimpinan/bagian hukum. Data kepegawaian ASN **wajib disimpan sesuai ketentuan kearsipan** (perlu verifikasi masa retensi); sistem memakai penghapusan lunak. Anonimisasi hanya atas keputusan tertulis pimpinan. |
| Keberatan/penarikan persetujuan | Catat di tiket; nonaktifkan akun swalayan bila diminta (data kepegawaian tetap sesuai dasar hukum). |

Setiap penampilan data sensitif tercatat di **Sistem → Log Audit** (jenis `akses-sensitif`): kolom, pengguna, IP.

## 6. Token API

**Sistem → Token API → Buat token klien.** Satu klien per sistem (Akreditasi, Keuangan, LMS). Token polos tampil sekali. Cabut
bila bocor atau tidak dipakai; token berlaku 365 hari (jadwalkan penggantian). Lihat docs/API.md.

## 7. Insiden dan pemulihan

- Backup/pulihkan: docs/PEMULIHAN.md. Rotasi kunci: docs/DEPLOY.md §6 (`sdm:hitung-ulang-hash` wajib).
- Kebocoran token API: cabut di panel; periksa log audit.
- Kebocoran kata sandi pengguna: nonaktifkan di **Pengguna** (`is_aktif`), reset kata sandi, periksa log audit.
- Tautan berkas bermasalah (`terlalu_terbuka`, tak terjangkau): pemilik dan admin menerima notifikasi; jalankan `php artisan sdm:periksa-tautan`.

## 8. Pemeriksaan harian/mingguan

| Kapan | Periksa |
|---|---|
| Harian | `/api/health` ok; Horizon tanpa job gagal; ringkasan pengingat admin terkirim 07:00. |
| Mingguan | Hasil `sdm:periksa-tautan`; ukuran log; backup terbaru dapat dibaca (`gunzip -t`). |
| Semester | Impor BKD; jumlah mahasiswa; uji pulihkan; `composer audit` / `npm audit`; tinjau akun klien API. |
