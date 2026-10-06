<?php

namespace App\Filament\Swalayan\Pages;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Support\AksiTampilSensitif;
use App\Filament\Swalayan\Support\AksiUsulan;
use App\Models\Pegawai;
use App\Support\RegistriTargetUsulan;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProfilSaya extends Page
{
    protected static ?string $title = 'Profil Saya';

    protected static ?string $slug = 'profil-saya';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    private ?Pegawai $pegawai = null;

    /** Selalu data milik sendiri; tidak ada parameter ID di URL. */
    public function getPegawai(): Pegawai
    {
        return $this->pegawai ??= Pegawai::query()
            ->with([
                'prodi', 'unitKerja', 'statusKepegawaian', 'jabatanFungsional', 'golongan', 'tautanBerkas',
                'riwayatJabatanFungsional.jabatanFungsional', 'riwayatJabatanFungsional.tautanBerkas',
                'riwayatPangkat.golongan', 'riwayatPangkat.tautanBerkas',
                'riwayatKgb.golongan', 'riwayatKgb.tautanBerkas',
                'riwayatJabatanStruktural.jenisJabatanStruktural', 'riwayatJabatanStruktural.unitKerja', 'riwayatJabatanStruktural.tautanBerkas',
                'riwayatPendidikan.jenjangPendidikan', 'riwayatPendidikan.tautanBerkas',
                'sertifikasi.jenisSertifikasi', 'sertifikasi.tautanBerkas',
                'penghargaan.tautanBerkas', 'pelatihan.tautanBerkas',
                'keluarga', 'studiLanjut.jenjangPendidikan', 'studiLanjut.tautanBerkas',
            ])
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /** @return array<int, mixed> */
    protected function getHeaderActions(): array
    {
        return [
            AksiUsulan::biodata(),
            ActionGroup::make(array_map(
                fn (string $tabel) => AksiUsulan::tambah($tabel),
                array_values(array_filter(RegistriTargetUsulan::tabel(), fn (string $t) => isset(AksiUsulan::FORM[$t]) && $t !== 'pegawai')),
            ))->label('Usulkan tambah data')->icon(Heroicon::OutlinedPlus)->button(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->record($this->getPegawai())->components([
            Tabs::make('Profil')->columnSpanFull()->persistTabInQueryString()->tabs([
                $this->tabBiodata(),
                $this->tabRiwayat('Jabatan Fungsional', [
                    ['riwayatJabatanFungsional', 'Jabatan fungsional', 'riwayat_jabatan_fungsional', [
                        TextEntry::make('jabatanFungsional.nama')->label('Jabatan'),
                        TextEntry::make('tmt')->label('TMT')->date('d F Y'),
                        TextEntry::make('nomor_sk')->label('Nomor SK'),
                        TautanBerkasField::entri('sk', JenisTautan::Sk, 'Berkas SK'),
                    ]],
                ]),
                $this->tabRiwayat('Pangkat & KGB', [
                    ['riwayatPangkat', 'Pangkat', 'riwayat_pangkat', [
                        TextEntry::make('golongan.label')->label('Golongan'),
                        TextEntry::make('tmt')->label('TMT')->date('d F Y'),
                        TextEntry::make('nomor_sk')->label('Nomor SK'),
                        TautanBerkasField::entri('sk', JenisTautan::Sk, 'Berkas SK'),
                    ]],
                    ['riwayatKgb', 'Kenaikan gaji berkala', 'riwayat_kgb', [
                        TextEntry::make('tmt')->label('TMT')->date('d F Y'),
                        TextEntry::make('gaji_pokok')->label('Gaji pokok')->placeholder('-')
                            ->formatStateUsing(fn (?string $state): string => $state === null ? '-' : 'Rp '.number_format((float) $state, 2, ',', '.')),
                        TextEntry::make('nomor_sk')->label('Nomor SK'),
                    ]],
                ]),
                $this->tabRiwayat('Struktural', [
                    ['riwayatJabatanStruktural', 'Jabatan struktural/tugas tambahan', 'riwayat_jabatan_struktural', [
                        TextEntry::make('jenisJabatanStruktural.nama')->label('Jabatan'),
                        TextEntry::make('unitKerja.nama')->label('Unit kerja')->placeholder('-'),
                        TextEntry::make('periode')->label('Periode'),
                    ]],
                ]),
                $this->tabRiwayat('Pendidikan', [
                    ['riwayatPendidikan', 'Pendidikan', 'riwayat_pendidikan', [
                        TextEntry::make('jenjangPendidikan.nama')->label('Jenjang'),
                        TextEntry::make('nama_pt')->label('Perguruan tinggi'),
                        TextEntry::make('tahun_lulus')->label('Lulus')->placeholder('-'),
                        TautanBerkasField::entri('ijazah', JenisTautan::Ijazah, 'Ijazah'),
                    ]],
                ]),
                $this->tabRiwayat('Sertifikasi', [
                    ['sertifikasi', 'Sertifikasi', 'sertifikasi', [
                        TextEntry::make('jenisSertifikasi.nama')->label('Jenis'),
                        TextEntry::make('nama')->label('Nama'),
                        TextEntry::make('tanggal_kedaluwarsa')->label('Kedaluwarsa')->date('d F Y')->placeholder('-'),
                        TextEntry::make('status_berlaku')->label('Status')->badge(),
                    ]],
                ]),
                $this->tabRiwayat('Pengembangan Diri', [
                    ['penghargaan', 'Penghargaan', 'penghargaan', [
                        TextEntry::make('nama')->label('Nama'),
                        TextEntry::make('tingkat')->label('Tingkat')->badge(),
                        TextEntry::make('tanggal')->label('Tanggal')->date('d F Y')->placeholder('-'),
                    ]],
                    ['pelatihan', 'Pelatihan', 'pelatihan', [
                        TextEntry::make('nama')->label('Nama'),
                        TextEntry::make('jenis')->label('Jenis')->badge(),
                        TextEntry::make('jumlah_jam')->label('Jam')->placeholder('-'),
                    ]],
                ]),
                $this->tabRiwayat('Keluarga', [
                    ['keluarga', 'Keluarga', 'keluarga', [
                        TextEntry::make('hubungan')->label('Hubungan')->badge(),
                        TextEntry::make('nama')->label('Nama'),
                        TextEntry::make('nik_tersamar')->label('NIK')->fontFamily('mono'),
                    ]],
                ]),
                $this->tabRiwayat('Studi Lanjut', [
                    ['studiLanjut', 'Studi lanjut', 'studi_lanjut', [
                        TextEntry::make('jenis')->label('Jenis')->badge(),
                        TextEntry::make('nama_pt')->label('Perguruan tinggi'),
                        TextEntry::make('status')->label('Status')->badge(),
                    ]],
                ]),
            ]),
        ]);
    }

    private function tabBiodata(): Tab
    {
        return Tab::make('Biodata')->columns(2)->schema([
            TextEntry::make('nama_bergelar')->label('Nama'),
            TextEntry::make('jenis_pegawai')->label('Jenis')->badge(),
            TextEntry::make('nip')->label('NIP')->placeholder('-'),
            TextEntry::make('nidn')->label('NIDN')->placeholder('-'),
            TextEntry::make('nuptk')->label('NUPTK')->placeholder('-'),
            TextEntry::make('tempat_lahir')->label('Tempat lahir')->placeholder('-'),
            TextEntry::make('tanggal_lahir')->label('Tanggal lahir')->date('d F Y')->placeholder('-'),
            TextEntry::make('email_unsil')->label('Surel Unsil')->placeholder('-'),
            TextEntry::make('no_hp')->label('No. HP')->placeholder('-'),
            TextEntry::make('alamat')->label('Alamat')->placeholder('-'),
            TextEntry::make('statusKepegawaian.nama')->label('Status kepegawaian'),
            TextEntry::make('jabatanFungsional.nama')->label('Jabatan fungsional')->placeholder('-'),
            TextEntry::make('golongan.label')->label('Golongan')->placeholder('-'),
            TextEntry::make('tanggal_pensiun')->label('Tanggal pensiun')->date('d F Y')->placeholder('-'),
            TextEntry::make('nik_tersamar')->label('NIK')->fontFamily('mono')->suffixAction(AksiTampilSensitif::make('nik', 'NIK')),
            TextEntry::make('npwp_tersamar')->label('NPWP')->fontFamily('mono')->suffixAction(AksiTampilSensitif::make('npwp', 'NPWP')),
            TextEntry::make('rekening_tersamar')->label('Nomor rekening')->fontFamily('mono')->suffixAction(AksiTampilSensitif::make('nomor_rekening', 'Nomor rekening')),
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: list<Component>}>  $kelompok  [relasi, judul, tabel usulan, entri]
     */
    private function tabRiwayat(string $judul, array $kelompok): Tab
    {
        $komponen = [];

        foreach ($kelompok as [$relasi, $subjudul, $tabel, $entri]) {
            $entri[] = Actions::make([AksiUsulan::ubah($tabel), AksiUsulan::hapus($tabel)])->columnSpanFull();
            $komponen[] = RepeatableEntry::make($relasi)->label($subjudul)->schema($entri)->columns(['default' => 1, 'sm' => 2])->contained()
                ->placeholder('Belum ada data')->columnSpanFull();
        }

        return Tab::make($judul)->schema($komponen);
    }
}
