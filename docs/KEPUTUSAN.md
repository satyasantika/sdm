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
