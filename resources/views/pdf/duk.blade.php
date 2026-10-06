@extends('pdf.layout')
@section('judul', 'Daftar Urut Kepangkatan')
@section('isi')
    <h1>DAFTAR URUT KEPANGKATAN (DUK)</h1>
    <div class="catatan">Pegawai Negeri Sipil. Kriteria urutan DUK perlu verifikasi dengan ketentuan BKN. Tanggal cetak {{ now()->translatedFormat('d F Y') }}.</div>
    <table>
        <thead>
            <tr><th>No</th><th>Nama</th><th>Gol.</th><th>TMT Gol.</th><th>Jabatan</th><th>Masa kerja</th><th>Pendidikan</th><th>Usia</th></tr>
        </thead>
        <tbody>
            @forelse ($baris as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td><td>{{ $b['nama'] }}</td><td>{{ $b['golongan'] }}</td><td>{{ $b['tmt_golongan'] }}</td>
                    <td>{{ $b['jabatan'] }}</td><td>{{ $b['masa_kerja'] }}</td><td>{{ $b['pendidikan'] }}</td><td>{{ $b['usia'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
