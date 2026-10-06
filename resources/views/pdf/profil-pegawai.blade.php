@extends('pdf.layout')
@section('judul', 'Profil Pegawai')
@section('isi')
    <h1>PROFIL KEPEGAWAIAN</h1>
    <table class="kv">
        <tr><td>Nama</td><td>{{ $pegawai->nama_bergelar }}</td></tr>
        <tr><td>Jenis pegawai</td><td>{{ $pegawai->jenis_pegawai->getLabel() }}</td></tr>
        <tr><td>NIDN / NUPTK</td><td>{{ $pegawai->nidn ?? '-' }} / {{ $pegawai->nuptk ?? '-' }}</td></tr>
        <tr><td>Prodi / unit kerja</td><td>{{ $pegawai->prodi?->nama ?? $pegawai->unitKerja?->nama ?? '-' }}</td></tr>
        <tr><td>Status kepegawaian</td><td>{{ $pegawai->statusKepegawaian->nama }}</td></tr>
        <tr><td>Jabatan fungsional</td><td>{{ $pegawai->jabatanFungsional?->nama ?? '-' }}</td></tr>
        <tr><td>Golongan</td><td>{{ $pegawai->golongan?->label ?? '-' }}</td></tr>
        <tr><td>Status keaktifan</td><td>{{ $pegawai->status_aktif->getLabel() }}</td></tr>
    </table>

    @php
        $bagian = [
            'Riwayat jabatan fungsional' => [['Jabatan', 'TMT', 'Nomor SK'], $pegawai->riwayatJabatanFungsional->map(fn ($r) => [$r->jabatanFungsional->nama, $r->tmt->translatedFormat('d F Y'), $r->nomor_sk])],
            'Riwayat pangkat' => [['Golongan', 'TMT', 'Nomor SK'], $pegawai->riwayatPangkat->map(fn ($r) => [$r->golongan->label, $r->tmt->translatedFormat('d F Y'), $r->nomor_sk])],
            'Jabatan struktural/tugas tambahan' => [['Jabatan', 'Unit', 'Periode'], $pegawai->riwayatJabatanStruktural->map(fn ($r) => [$r->jenisJabatanStruktural->nama, $r->unitKerja?->nama ?? '-', $r->periode])],
            'Pendidikan' => [['Jenjang', 'Perguruan tinggi', 'Lulus'], $pegawai->riwayatPendidikan->map(fn ($r) => [$r->jenjangPendidikan->nama, $r->nama_pt, $r->tahun_lulus ?? '-'])],
            'Sertifikasi' => [['Nama', 'Jenis', 'Kedaluwarsa'], $pegawai->sertifikasi->map(fn ($r) => [$r->nama, $r->jenisSertifikasi->nama, $r->tanggal_kedaluwarsa?->translatedFormat('d F Y') ?? '-'])],
            'Pengembangan diri: penghargaan' => [['Penghargaan', 'Tingkat', 'Tanggal'], $pegawai->penghargaan->map(fn ($r) => [$r->nama, $r->tingkat->getLabel(), $r->tanggal?->translatedFormat('d F Y') ?? '-'])],
            'Pengembangan diri: pelatihan' => [['Pelatihan', 'Jenis', 'Jam'], $pegawai->pelatihan->map(fn ($r) => [$r->nama, $r->jenis->getLabel(), $r->jumlah_jam ?? '-'])],
        ];
    @endphp

    @foreach ($bagian as $judul => [$kolom, $data])
        <h2>{{ $judul }}</h2>
        <table>
            <thead><tr>@foreach ($kolom as $k)<th>{{ $k }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($data as $baris)
                    <tr>@foreach ($baris as $sel)<td>{{ $sel }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($kolom) }}" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
@endsection
