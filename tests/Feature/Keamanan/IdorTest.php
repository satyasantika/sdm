<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\DokumenPegawai\Pages\ListDokumenPegawai;
use App\Filament\Admin\Resources\Pengingat\Pages\ListPengingat;
use App\Models\Bkd;
use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\KeluaranSementara;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->pmat = Prodi::factory()->create(['kode' => 'PMAT']);
    $this->pbio = Prodi::factory()->create(['kode' => 'PBIO']);
    $this->adminPmat = User::factory()->create(['email' => 'ap@unsil.ac.id', 'prodi_id' => $this->pmat->id, 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminProdi->value);
    $this->orangPbio = Pegawai::factory()->create(['prodi_id' => $this->pbio->id]);
    $this->orangPmat = Pegawai::factory()->create(['prodi_id' => $this->pmat->id]);
});

test('admin-prodi tidak dapat membuka pegawai prodi lain lewat uuid', function () {
    $this->actingAs($this->adminPmat);

    foreach (['/admin/pegawai/pegawais/%s', '/admin/pegawai/pegawais/%s/edit'] as $pola) {
        expect($this->get(sprintf($pola, $this->orangPbio->id))->status())->toBeIn([403, 404]);
    }
    $this->get('/admin/pegawai/pegawais/'.$this->orangPmat->id)->assertOk();
});

test('admin-prodi tidak dapat membuka bkd atau usulan prodi lain', function () {
    $bkd = Bkd::factory()->create(['pegawai_id' => $this->orangPbio->id]);
    $usulan = UsulanPerubahan::factory()->create(['pegawai_id' => $this->orangPbio->id, 'status' => 'diajukan']);
    $this->actingAs($this->adminPmat);

    expect($this->get("/admin/bkd/bkds/{$bkd->id}/edit")->status())->toBeIn([403, 404])
        ->and($this->get("/admin/usulan-perubahan/usulan-perubahans/{$usulan->id}")->status())->toBeIn([403, 404]);
});

test('daftar dokumen dan pengingat admin-prodi hanya memuat prodinya', function () {
    $dokMilik = DokumenPegawai::factory()->create(['pegawai_id' => $this->orangPmat->id, 'jenis_dokumen_id' => JenisDokumen::factory()->create(['is_identitas' => false])->id]);
    $dokLain = DokumenPegawai::factory()->create(['pegawai_id' => $this->orangPbio->id, 'jenis_dokumen_id' => JenisDokumen::factory()->create(['is_identitas' => false])->id]);
    $ingMilik = Pengingat::factory()->create(['pegawai_id' => $this->orangPmat->id]);
    $ingLain = Pengingat::factory()->create(['pegawai_id' => $this->orangPbio->id]);
    $this->actingAs($this->adminPmat);

    Livewire::test(ListDokumenPegawai::class)->loadTable()->removeTableFilters()->assertCanSeeTableRecords([$dokMilik])->assertCanNotSeeTableRecords([$dokLain]);
    $this->actingAs($this->adminPmat);
    Livewire::test(ListPengingat::class)->loadTable()->assertCanSeeTableRecords([$ingMilik])->assertCanNotSeeTableRecords([$ingLain]);
});

test('dosen tidak dapat membuka tautan berkas dosen lain dan admin-prodi tidak membuka tautan sensitif', function () {
    $dosenA = User::factory()->create(['email' => 'a@unsil.ac.id'])->assignRole(Peran::Dosen->value);
    Pegawai::factory()->create(['user_id' => $dosenA->id]);
    $tautanB = TautanBerkas::factory()->create(['pemilik_id' => $this->orangPbio->id, 'pegawai_id' => $this->orangPbio->id, 'is_sensitif' => true]);
    $tautanPmatSensitif = TautanBerkas::factory()->create(['pemilik_id' => $this->orangPmat->id, 'pegawai_id' => $this->orangPmat->id, 'is_sensitif' => true]);

    $this->actingAs($dosenA)->get(route('tautan.buka', $tautanB))->assertForbidden();
    $this->actingAs($this->adminPmat)->get(route('tautan.buka', $tautanB))->assertForbidden();
    $this->actingAs($this->adminPmat)->get(route('tautan.buka', $tautanPmatSensitif))->assertForbidden();
});

test('dosen tidak dapat mengunduh keluaran sementara orang lain', function () {
    $dosenA = User::factory()->create(['email' => 'a@unsil.ac.id'])->assignRole(Peran::Dosen->value);
    $pembuat = User::factory()->create(['email' => 'b@unsil.ac.id'])->assignRole(Peran::AdminKepegawaian->value);
    Storage::fake('tmp');
    $id = KeluaranSementara::simpan($pembuat, 'Uji', 'uji.pdf', '%PDF-x');

    $this->actingAs($dosenA)->get(route('keluaran.unduh', $id))->assertForbidden();
});
