<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\StatusAktifPegawai;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\PegawaiObserver;
use App\Support\HashIdentitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * @property-read string $nama_bergelar
 * @property JenisPegawai $jenis_pegawai
 * @property StatusAktifPegawai $status_aktif
 * @property CarbonInterface|null $tanggal_lahir
 * @property CarbonInterface|null $tanggal_pensiun
 */
#[ObservedBy(PegawaiObserver::class)]
class Pegawai extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'pegawai';

    /** @return list<string> */
    public static function kolomSensitif(): array
    {
        return ['nik', 'npwp', 'nomor_rekening', 'alamat', 'tanggal_lahir'];
    }

    protected $fillable = [
        'user_id', 'jenis_pegawai', 'gelar_depan', 'nama', 'gelar_belakang', 'nip', 'nidn', 'nidk', 'nuptk',
        'nik', 'npwp', 'nama_bank', 'nomor_rekening', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
        'status_perkawinan', 'alamat', 'no_hp', 'email_pribadi', 'email_unsil', 'status_kepegawaian_id', 'prodi_id',
        'unit_kerja_id', 'jabatan_fungsional_id', 'golongan_id', 'tmt_cpns', 'tmt_pns', 'tmt_masuk', 'status_aktif',
        'tanggal_pensiun', 'sesuai_kompetensi_inti_ps', 'kode_eksternal',
    ];

    protected $hidden = ['nik', 'npwp', 'nomor_rekening', 'nik_hash'];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'nomor_rekening' => 'encrypted',
            'tanggal_lahir' => 'date',
            'tmt_cpns' => 'date',
            'tmt_pns' => 'date',
            'tmt_masuk' => 'date',
            'tanggal_pensiun' => 'date',
            'jenis_pegawai' => JenisPegawai::class,
            'jenis_kelamin' => JenisKelamin::class,
            'status_aktif' => StatusAktifPegawai::class,
            'sesuai_kompetensi_inti_ps' => 'boolean',
        ];
    }

    /** Normalisasi digit NIK dan isi nik_hash (BR-01); NIK disimpan terenkripsi. */
    protected function nik(): Attribute
    {
        return Attribute::set(function (?string $nilai): array {
            $digit = $nilai === null ? null : (preg_replace('/\D/', '', $nilai) ?: null);

            return [
                'nik' => $digit === null ? null : Crypt::encryptString($digit),
                'nik_hash' => $digit === null ? null : HashIdentitas::nik($digit),
            ];
        });
    }

    protected function namaBergelar(): Attribute
    {
        return Attribute::get(function (): string {
            $depan = $this->gelar_depan ? $this->gelar_depan.' ' : '';
            $belakang = $this->gelar_belakang ? ', '.$this->gelar_belakang : '';

            return $depan.$this->nama.$belakang;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function statusKepegawaian(): BelongsTo
    {
        return $this->belongsTo(StatusKepegawaian::class);
    }

    public function jabatanFungsional(): BelongsTo
    {
        return $this->belongsTo(JabatanFungsional::class);
    }

    public function golongan(): BelongsTo
    {
        return $this->belongsTo(Golongan::class);
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPegawai::class)->latest('tmt')->latest('created_at');
    }

    public function scopeDosen(Builder $query): Builder
    {
        return $query->where('jenis_pegawai', JenisPegawai::Dosen);
    }

    public function scopeTendik(Builder $query): Builder
    {
        return $query->where('jenis_pegawai', JenisPegawai::Tendik);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', StatusAktifPegawai::Aktif);
    }

    public function scopeUntukProdi(Builder $query, string $prodiId): Builder
    {
        return $query->where('prodi_id', $prodiId);
    }

    /** Dosen tetap (BR-25): dosen berstatus aktif/tugas belajar dengan status kepegawaian yang dihitung dosen tetap. */
    public function scopeDosenTetap(Builder $query): Builder
    {
        return $query->where('jenis_pegawai', JenisPegawai::Dosen)
            ->whereIn('status_aktif', [StatusAktifPegawai::Aktif, StatusAktifPegawai::TugasBelajar])
            ->whereHas('statusKepegawaian', fn (Builder $q) => $q->where('dihitung_dosen_tetap', true));
    }
}
