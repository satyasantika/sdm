# Catatan Perubahan

Semua perubahan penting pada proyek ini dicatat di berkas ini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan versi mengikuti
[Semantic Versioning](https://semver.org/lang/id/): `v0.1.0` = fondasi (selesai Fase 1),
`v1.0.0` = rilis produksi pertama.

## [Belum dirilis]

## [0.1.0] - 2026-10-06

### Ditambahkan

- Laravel 13 dengan Sail (Redis 7 dan Mailpit); basis data SQLite, MySQL tidak dipakai (F1.1).
- Locale `id`, zona waktu `Asia/Jakarta`, terjemahan validasi bahasa Indonesia, serta Redis untuk
  cache, sesi, dan antrean pada DB index terpisah (F1.2).
- Laravel Horizon dengan empat supervisor: `default`, `impor`, `ekspor`, `notifikasi` (F1.3).
- Pest 4, Larastan level 6, Pint, Laravel Boost, dan endpoint `GET /api/health` (F1.4).
- UUIDv7 sebagai primary key (`users`, `sessions`, `personal_access_tokens`) beserta uji arsitektur (F1.5).
- Workflow CI (pint, larastan, pest) pada setiap pull request (F1.6).
