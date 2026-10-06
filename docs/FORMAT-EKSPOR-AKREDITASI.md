# Format Ekspor Akreditasi (DKPS SDM LAMDIK IAPSK 3.0)

> **Urutan kolom perlu verifikasi terhadap templat DKPS resmi.** Kolom di bawah disusun agar mudah dipetakan; tidak ada
> data sensitif (NIK, NIP lengkap, tanggal lahir, alamat, rekening, keluarga) pada ekspor mana pun (BR-17).

| Ekspor (Exporter) | Padanan tabel DKPS | Kolom |
|---|---|---|
| `ProfilDosenProdiExporter` (LAP-01) | Dosen Tetap Perguruan Tinggi / DTPS | Nama dosen, NIDN/NIDK, NUPTK, Prodi homebase, Status kepegawaian, S1/S2/S3 (PT & bidang ilmu), Bidang keahlian, Jabatan akademik, TMT jabatan akademik, Sertifikat pendidik, Sertifikat kompetensi/profesi (yang berlaku), Status, DTPS |
| `BebanKerjaDtpsExporter` | Beban Kerja DTPS | Nama, NIDN, Semester, SKS pendidikan/penelitian/pengabdian/penunjang, Total SKS (dari rekap BKD semester terpilih) |
| `RekognisiDtpsExporter` | Rekognisi Kepakaran/Prestasi DTPS | Nama, Kategori, Rekognisi, Pemberi, Tingkat, Tanggal (pemetaan kategori perlu verifikasi) |
| `PengembanganKompetensiExporter` | Pengembangan Kompetensi DTPS / Tenaga Kependidikan | Nama, Jenis pegawai, Pelatihan, Jenis, Penyelenggara, Mulai, Jumlah jam |
| `TenagaKependidikanExporter` | Tenaga Kependidikan | Nama, Unit kerja, Status kepegawaian, Jabatan fungsional, Pendidikan tertinggi, Status |

## Catatan definisi

- **Dosen tetap** (BR-25): `jenis_pegawai = dosen`, `status_aktif` ∈ {aktif, tugas_belajar}, status kepegawaian bertanda `dihitung_dosen_tetap`.
- **DTPS** (BR-31): dosen tetap dengan `sesuai_kompetensi_inti_ps = true` pada prodi homebase (**perlu verifikasi** definisi instrumen).
- **Syarat unggul** (BR-32, LAP-10): nilai ambang dibaca dari konfigurasi `syarat_unggul_sdm` per jenjang; hanya jenjang S1 yang terisi awal.
- Penyimpanan: ekspor besar berjalan di antrean `ekspor`, disimpan sementara di disk `tmp` dan dihapus otomatis ≤ 24 jam
  oleh `sdm:bersihkan-tmp`; ekspor ringkasan kecil di-stream langsung.
