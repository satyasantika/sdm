@extends('pdf.layout')
@section('judul', 'Rekap Pejabat')
@section('isi')
    <h1>REKAP PEJABAT STRUKTURAL DAN TUGAS TAMBAHAN AKTIF</h1>
    <table>
        <thead><tr><th>No</th><th>Jabatan</th><th>Pejabat</th><th>Unit kerja</th><th>Periode</th></tr></thead>
        <tbody>
            @forelse ($baris as $i => $b)
                <tr><td>{{ $i + 1 }}</td><td>{{ $b['jabatan'] }}</td><td>{{ $b['nama'] }}</td><td>{{ $b['unit'] }}</td><td>{{ $b['periode'] }}</td></tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada pejabat aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
