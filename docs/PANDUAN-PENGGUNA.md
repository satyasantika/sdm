# Panduan Pengguna — Sistem Informasi SDM FKIP Unsil

Alamat: panel admin `/admin` (admin kepegawaian, admin prodi, pimpinan) dan swalayan `/saya` (dosen/tendik).
Data pribadi sensitif (NIK, NPWP, rekening) disimpan terenkripsi dan hanya tampil penuh bagi yang berwenang, dengan
pencatatan akses. **Berkas (SK, ijazah, dll.) tidak diunggah ke sistem**: Anda menempelkan tautan Google Drive/Docs
(atau domain unsil.ac.id) dengan akses berbagi terbatas.

## 1. Admin Kepegawaian

**Login & MFA.** Masuk di `/admin` memakai surel Unsil. Pada login pertama sistem meminta pemasangan aplikasi autentikator
(MFA wajib); simpan kode pemulihan. Setiap login berikutnya meminta kode 6 digit.

**Tugas utama**
1. *Menambah pegawai*: **Kepegawaian → Pegawai → Buat**. NIK harus unik (sistem menolak NIK ganda).
2. *Impor massal*: tombol **Impor** di daftar Pegawai (Excel); periksa laporan baris gagal.
3. *Membuat akun swalayan*: buka pegawai → **Buat akun**. Pegawai wajib punya surel Unsil; ia menerima surel aktivasi.
4. *Riwayat*: tab riwayat pada halaman pegawai (jabatan fungsional, pangkat, KGB, pendidikan, sertifikasi, dst.). Perubahan
   data pegawai sendiri tidak dilakukan lewat swalayan, melainkan lewat usulan (langkah 5).
5. *Memverifikasi usulan*: **Kepegawaian → Usulan Perubahan** → buka usulan → **Setujui / Tolak / Kembalikan**. Bandingkan data
   lama–baru; buka tautan bukti. Bila data berubah sejak usulan diajukan, sistem memberi peringatan konflik.
6. *Data sensitif*: pada halaman pegawai tekan **Tampilkan** untuk NIK/NPWP/rekening (maks 10 per menit, tercatat).
7. *BKD*: **BKD → Rekap BKD** → Impor berkas SISTER semester terkait.
8. *Pengingat*: **Kepegawaian → Pengingat** (KP, KGB, jabatan fungsional, pensiun, dokumen, sertifikasi, studi lanjut). Ubah status
   (ditindaklanjuti / diabaikan dengan catatan).
9. *Laporan*: **Laporan → Laporan Akreditasi** (ekspor profil dosen prodi, beban kerja, rekognisi, dst.; maks 5 ekspor per menit)
   dan **Laporan Kepegawaian** (DUK, rekap pejabat, daftar pensiun; PDF diproses di latar belakang lalu muncul notifikasi unduh).
10. *Master & Konfigurasi*: **Master** (prodi, status, golongan, jabatan, jenjang) dan **Sistem → Konfigurasi** (BUP, interval KP/KGB,
    teks & versi kebijakan privasi, ambang). Mengubah konfigurasi menghitung ulang pengingat otomatis.

## 2. Admin Prodi

Masuk di `/admin` (MFA dianjurkan). Anda hanya melihat pegawai, usulan, dokumen non-identitas, BKD, dan pengingat **prodi Anda**.
1. *Memantau*: Dasbor (statistik prodi), Pegawai, Dokumen (filter status Segera berakhir/Kedaluwarsa), Pengingat (hanya baca).
2. *Ekspor akreditasi*: **Laporan Akreditasi** otomatis terkunci ke prodi Anda.
3. *Jumlah mahasiswa*: **Jumlah Mahasiswa** per semester untuk rasio dosen–mahasiswa.
4. Anda **tidak** dapat menyetujui usulan, menampilkan NIK/NPWP/rekening, atau membuka tautan berkas sensitif; hanya melihat
   ada/tidaknya berkas.

## 3. Pimpinan

Akses hanya-baca. Dasbor fakultas (jumlah dosen/tendik, S3, serdos, jabatan, pensiun 5 tahun), Laporan Akreditasi, Laporan
Kepegawaian, dan daftar pegawai tanpa data sensitif.

## 4. Dosen / Tenaga Kependidikan (swalayan `/saya`)

**Login pertama.** Buka tautan di surel aktivasi → buat kata sandi → masuk di `/saya`. Baca dan setujui **Pemberitahuan Privasi**
(wajib sebelum memakai sistem; muncul lagi bila versinya diperbarui).

**Menu**
- *Beranda*: ringkasan profil, pengingat tenggat Anda, status usulan.
- *Profil Saya*: data dan seluruh riwayat Anda; tombol **Cetak profil** (PDF tanpa data sensitif).
- *Usulan Saya*: daftar usulan dan statusnya. Ajukan perubahan lewat tombol di Profil Saya:
  1. Pilih data (biodata atau tambah/ubah/hapus riwayat), isi nilai baru dan alasan.
  2. Untuk NIK/NPWP/rekening/ijazah dan riwayat penting, tempel **tautan bukti** (Google Drive, akses terbatas) dan centang konfirmasi.
  3. **Kirim** (atau simpan sebagai draf). Admin kepegawaian akan memverifikasi; data Anda berubah hanya setelah disetujui.
- *BKD Saya*: data BKD per semester (dosen).

## 5. FAQ

**Lupa kata sandi?** Di halaman login pilih *Lupa kata sandi* dan ikuti surel. Bila MFA hilang, hubungi admin kepegawaian
(super-admin) untuk mengatur ulang; gunakan kode pemulihan bila ada.

**Usulan saya dikembalikan.** Buka *Usulan Saya* → usulan berstatus *Dikembalikan* → baca catatan verifikator → perbaiki → kirim ulang.
Usulan ditolak berarti data tidak diubah; ajukan ulang dengan bukti yang benar.

**Data BKD belum muncul.** BKD diimpor admin dari berkas SISTER per semester. Pastikan NIDN Anda di profil benar, lalu minta admin
kepegawaian mengimpor ulang semester tersebut.

**Tautan berkas saya ditolak.** Gunakan tautan satu berkas (bukan folder), diawali `https://`, dari Google Drive/Docs atau
unsil.ac.id; pemendek URL (bit.ly, dll.) tidak diterima. Atur akses berbagi ke pihak berwenang saja (jangan "siapa saja yang memiliki tautan").

**Berkas saya tertandai "terlalu terbuka".** Ubah pengaturan berbagi di Drive menjadi terbatas, lalu minta pemeriksaan ulang.

**Tampilan data sensitif ditolak "terlalu banyak permintaan".** Tunggu satu menit; batas 10 tampilan per menit.
