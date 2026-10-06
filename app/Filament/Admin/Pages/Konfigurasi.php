<?php

namespace App\Filament\Admin\Pages;

use App\Support\Konfigurasi as Pengaturan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Konfigurasi extends Page
{
    protected string $view = 'filament.admin.pages.konfigurasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Konfigurasi';

    protected static ?string $title = 'Konfigurasi Kepegawaian';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('konfigurasi.kelola');
    }

    public function mount(): void
    {
        $data = Pengaturan::semua();
        $data['syarat_unggul_sdm'] = json_encode($data['syarat_unggul_sdm'] ?? new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->form->fill($data);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Batas usia pensiun')->description('UU 14/2005 — pastikan dengan regulasi terbaru')->schema([
                TextInput::make('bup_dosen')->label('BUP dosen (tahun)')->numeric()->required()->minValue(50)->maxValue(80),
                TextInput::make('bup_profesor')->label('BUP profesor (tahun)')->numeric()->required()->minValue(50)->maxValue(80),
                TextInput::make('bup_tendik')->label('BUP tendik (tahun)')->numeric()->required()->minValue(50)->maxValue(80),
                Select::make('pembulatan_tmt_pensiun')->label('Pembulatan TMT pensiun')->required()->options([
                    'tepat' => 'Tepat', 'akhir_bulan' => 'Akhir bulan', 'awal_bulan_berikutnya' => 'Awal bulan berikutnya',
                ]),
            ])->columns(2),
            Section::make('Interval kenaikan')->description('Perlu verifikasi dengan aturan kepegawaian yang berlaku')->schema([
                TextInput::make('interval_kp_bulan')->label('Interval kenaikan pangkat (bulan)')->numeric()->required()->minValue(1),
                TextInput::make('interval_kgb_bulan')->label('Interval KGB (bulan)')->numeric()->required()->minValue(1),
            ])->columns(2),
            Section::make('Pengingat')->schema([
                TagsInput::make('tahap_pengingat_hari')->label('Tahap pengingat (hari sebelum jatuh tempo)')->required()
                    ->nestedRecursiveRules(['integer', 'min:1']),
                TextInput::make('cakrawala_pengingat_hari')->label('Cakrawala pengingat (hari)')->numeric()->required()->minValue(1),
                TextInput::make('ambang_segera_berakhir_hari')->label('Ambang segera berakhir (hari)')->numeric()->required()->minValue(1),
            ])->columns(2),
            Section::make('Tautan berkas')->schema([
                TextInput::make('interval_periksa_tautan_hari')->label('Interval pemeriksaan tautan (hari)')->numeric()->required()->minValue(1),
            ]),
            Section::make('Syarat unggul LAMDIK')->description('JSON per jenjang. Sumber: Peraturan BAN-PT 27/2025; perlu verifikasi.')->schema([
                Textarea::make('syarat_unggul_sdm')->label('Syarat unggul (JSON)')->rows(12)->rules(['json'])->columnSpanFull(),
            ]),
            Section::make('Laporan')->schema([
                TextInput::make('ambang_rasio_dosen_mahasiswa')->label('Ambang rasio mahasiswa per dosen (opsional)')->numeric()->minValue(1)
                    ->helperText('Kosongkan bila tidak dipakai. Nilai ideal perlu verifikasi instrumen akreditasi.'),
            ]),
            Section::make('Privasi')->schema([
                TextInput::make('versi_kebijakan_privasi')->label('Versi kebijakan privasi')->required()->maxLength(20),
                MarkdownEditor::make('teks_kebijakan_privasi')->label('Teks kebijakan privasi')->columnSpanFull(),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('simpan')
            ->footer([
                Actions::make([
                    Action::make('simpan')->label('Simpan')->submit('simpan')->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function simpan(): void
    {
        $data = $this->form->getState();
        $data['syarat_unggul_sdm'] = json_decode((string) $data['syarat_unggul_sdm'], true);
        $data['tahap_pengingat_hari'] = array_map('intval', (array) $data['tahap_pengingat_hari']);
        $data['ambang_rasio_dosen_mahasiswa'] = filled($data['ambang_rasio_dosen_mahasiswa'] ?? null) ? (string) $data['ambang_rasio_dosen_mahasiswa'] : '';
        foreach (['bup_dosen', 'bup_profesor', 'bup_tendik', 'interval_kp_bulan', 'interval_kgb_bulan', 'cakrawala_pengingat_hari', 'ambang_segera_berakhir_hari', 'interval_periksa_tautan_hari'] as $kunci) {
            $data[$kunci] = (int) $data[$kunci];
        }

        // Event KonfigurasiKepegawaianDiubah dikirim oleh KonfigurasiObserver untuk kunci yang berpengaruh (BR-28).
        foreach ($data as $kunci => $nilai) {
            if (Pengaturan::get($kunci) !== $nilai) {
                Pengaturan::set($kunci, $nilai, auth()->user());
            }
        }

        Notification::make()->title('Konfigurasi disimpan')->success()->send();
    }
}
