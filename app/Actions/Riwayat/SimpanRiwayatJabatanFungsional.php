<?php

namespace App\Actions\Riwayat;

use App\Enums\Peran;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanRiwayatJabatanFungsional
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, User $oleh, ?RiwayatJabatanFungsional $record = null): RiwayatJabatanFungsional
    {
        return DB::transaction(function () use ($pegawai, $data, $oleh, $record): RiwayatJabatanFungsional {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());
            $jabatan = JabatanFungsional::findOrFail($data['jabatan_fungsional_id']);

            if ($jabatan->kelompok->value !== $pegawai->jenis_pegawai->value) {
                throw ValidationException::withMessages([
                    'jabatan_fungsional_id' => 'Jabatan '.$jabatan->kelompok->getLabel().' tidak dapat dipasang pada pegawai '.$pegawai->jenis_pegawai->getLabel().'.',
                ]);
            }

            $koreksi = (bool) ($data['is_koreksi'] ?? false);
            if ($koreksi && ! $oleh->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
                throw ValidationException::withMessages(['is_koreksi' => 'Hanya admin kepegawaian yang dapat menandai koreksi.']);
            }

            $this->periksaJenjang($pegawai, $jabatan, CarbonImmutable::parse($data['tmt']), $koreksi, $record);

            $atribut = [
                'jabatan_fungsional_id' => $jabatan->getKey(),
                'tmt' => $data['tmt'],
                'nomor_sk' => $data['nomor_sk'],
                'tanggal_sk' => $data['tanggal_sk'] ?? null,
                'angka_kredit' => $data['angka_kredit'] ?? null,
                'is_koreksi' => $koreksi,
                'keterangan' => $data['keterangan'] ?? null,
            ];

            if ($record) {
                $record->update($atribut);
            } else {
                $record = RiwayatJabatanFungsional::create($atribut + ['pegawai_id' => $pegawai->getKey(), 'sumber' => $data['sumber'] ?? 'admin']);
            }

            app(SinkronkanJabatanTerkini::class)->handle($pegawai);

            return $record->refresh();
        });
    }

    /** BR-09: jenjang tidak turun kecuali koreksi oleh admin kepegawaian. */
    private function periksaJenjang(Pegawai $pegawai, JabatanFungsional $jabatan, CarbonImmutable $tmt, bool $koreksi, ?RiwayatJabatanFungsional $sendiri): void
    {
        if ($koreksi) {
            return;
        }

        $terkini = RiwayatJabatanFungsional::query()
            ->where('pegawai_id', $pegawai->getKey())
            ->when($sendiri, fn ($q) => $q->whereKeyNot($sendiri->getKey()))
            ->orderByDesc('tmt')->orderByDesc('created_at')
            ->with('jabatanFungsional')
            ->first();

        if (! $terkini || ! $tmt->greaterThan($terkini->tmt)) {
            return;
        }

        if ($jabatan->lebihRendahDari($terkini->jabatanFungsional)) {
            throw ValidationException::withMessages([
                'jabatan_fungsional_id' => "Jenjang {$jabatan->nama} lebih rendah dari jabatan terkini ({$terkini->jabatanFungsional->nama}). Tandai sebagai koreksi (admin kepegawaian) bila memang diperlukan.",
            ]);
        }
    }
}
