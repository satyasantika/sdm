# API Integrasi SDM FKIP (v1)

API baca-saja untuk sistem Akreditasi, Keuangan, dan LMS. Hanya data non-sensitif (BR-17): tidak ada NIK, NIP, NPWP,
rekening, tanggal lahir, alamat, kontak pribadi, maupun data keluarga.

## Autentikasi

1. Super-admin membuat **klien API** dan token di panel admin: **Sistem → Token API → Buat token klien**.
2. Token polos (`<id>|<rahasia>`) hanya tampil **sekali**; simpan di tempat aman. Di basis data hanya hash-nya yang disimpan.
3. Token berability `sdm:read` dan berlaku 365 hari secara bawaan (dapat diubah 1–730 hari). Cabut kapan saja dari tabel yang sama.
4. Kirim di setiap permintaan: `Authorization: Bearer <token>` dan `Accept: application/json`.

```bash
curl -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  "https://sdm.example.ac.id/api/v1/dosen?prodi=PMAT&per_page=50"
```

Akun klien API tidak dapat masuk ke panel `/admin` maupun `/saya`.

## Endpoint

### `GET /api/v1/dosen`
Daftar dosen, terurut nama, dipaginasi (bawaan 50).

| Parameter | Keterangan |
|---|---|
| `per_page` | 1–100 (bawaan 50) |
| `prodi` | kode prodi, mis. `PMAT` |
| `jabfung` | kode jabatan fungsional |
| `status` | `status_aktif` (aktif, tugas_belajar, cuti_di_luar_tanggungan, pindah, diberhentikan, pensiun, meninggal). Tanpa parameter ini status `meninggal` dikecualikan |
| `diperbarui_sejak` | `Y-m-d`; dosen yang datanya (profil, sertifikasi, pendidikan, jabatan fungsional) diubah pada/setelah tanggal itu |

### `GET /api/v1/dosen/{id}`
Satu dosen (UUID). Tenaga kependidikan menghasilkan 404.

Field: `id`, `nama_bergelar`, `nidn`, `nuptk`, `prodi {kode, nama}`, `jabatan_fungsional {kode, nama}`,
`pendidikan_tertinggi {jenjang}`, `punya_serdos` (bool), `status_aktif`, `diperbarui_pada` (ISO 8601).

### `GET /api/v1/pejabat`
Pejabat struktural aktif, terurut menurut urutan jabatan. Field: `nama_bergelar`, `jabatan`, `unit_kerja`, `tmt_mulai` (Y-m-d).

## Header respons
`X-Api-Version: 1` pada semua respons.

## Kode galat
| Kode | Arti |
|---|---|
| 401 | token tidak ada, salah, kedaluwarsa, atau sudah dicabut |
| 403 | token tidak memiliki ability `sdm:read` |
| 404 | sumber daya tidak ada (termasuk id tendik pada `/dosen/{id}`) |
| 422 | parameter query tidak valid |
| 429 | melebihi 60 permintaan per menit per token; lihat header `Retry-After` |

## Kebijakan versi
Versi mayor ada di jalur (`/api/v1`). Penambahan field bersifat kompatibel dan dapat terjadi tanpa pemberitahuan;
klien harus mengabaikan field yang tidak dikenal. Perubahan yang merusak (hapus/ubah arti field) hanya dirilis di
`/api/v2`, dan v1 dipertahankan minimal 6 bulan setelah v2 tersedia.
