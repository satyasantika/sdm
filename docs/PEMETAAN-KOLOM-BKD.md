# Pemetaan Kolom Impor BKD (SISTER → tabel `bkd`)

> **Nama kolom SISTER perlu verifikasi setiap ada perubahan format.** `BkdImporter` memetakan kolom lewat `->guess([...])`
> sehingga nama kolom yang berbeda tetap dapat dipetakan manual pada langkah "Petakan kolom" di modal impor.

| Kolom berkas (tebakan nama) | Kolom `bkd` / pemakaian | Aturan |
|---|---|---|
| `nidn`, `NIDN` | Pencocokan dosen (prioritas 1) | Hanya `jenis_pegawai = dosen` |
| `nuptk`, `NUPTK` | Pencocokan dosen (prioritas 2) | Dipakai bila NIDN kosong/tidak cocok |
| `nip`, `NIP` | Pencocokan dosen (prioritas 3) | Dipakai bila NIDN dan NUPTK tidak cocok |
| `nama`, `Nama`, `nama dosen` | Informasi saja | Tidak disimpan |
| `sks_pendidikan`, `pendidikan`, `sks pendidikan` | `sks_pendidikan` | Desimal; "12,5" → 12.50 |
| `sks_penelitian`, `penelitian`, `sks penelitian` | `sks_penelitian` | Idem |
| `sks_pengabdian`, `pengabdian`, `sks pengabdian` | `sks_pengabdian` | Idem |
| `sks_penunjang`, `penunjang`, `sks penunjang` | `sks_penunjang` | Idem |
| `total_sks`, `total`, `jumlah sks` | `total_sks` | Dihitung dari keempat unsur bila kosong |
| `kesimpulan`, `Kesimpulan`, `status` | `kesimpulan` | `M`/`Memenuhi` → `memenuhi`; `T`/`TM`/`Tidak Memenuhi` → `tidak_memenuhi`; lainnya → `belum_dinilai` |
| `kewajiban_khusus`, `kewajiban khusus` | `kewajiban_khusus` | Maks. 30 karakter; istilah SISTER perlu verifikasi |

## Aturan impor

- Satu baris `bkd` per (`pegawai_id`, `semester_id`) (BR-21): impor ulang memperbarui, tidak menggandakan.
- Semester dipilih pada opsi impor (default semester aktif). Satu impor per semester berjalan pada satu waktu
  (lock `sdm:lock:impor-bkd:{semester_id}`, TTL 15 menit, dilepas saat `ImportCompleted`) (BR-22).
- Baris yang NIDN/NUPTK/NIP-nya tidak ditemukan gagal dengan pesan "NIDN/NUPTK/NIP tidak ditemukan." dan dapat diunduh.
- Kolom `sumber` = `sister_impor`; `import_id` menautkan baris ke catatan impor Filament.
