<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SDM FKIP Unsil</title>
    <meta name="description" content="Sistem Informasi Sumber Daya Manusia Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root{color-scheme:light;--bg:#f8fafc;--fg:#0f172a;--muted:#475569;--card:#fff;--line:#e2e8f0;--aksen:#4f46e5;--aksen-bg:#eef2ff;--hero1:#312e81;--hero2:#4f46e5;--hero3:#0f766e}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
        a{color:var(--aksen)}
        .wadah{max-width:1080px;margin:0 auto;padding:0 20px}
        .bar{position:sticky;top:0;z-index:10;background:color-mix(in srgb,var(--bg) 88%,transparent);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
        .bar .wadah{display:flex;align-items:center;justify-content:space-between;gap:12px;height:62px}
        .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--fg);font-weight:700}
        .mono{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--hero2),var(--hero3));color:#fff;display:grid;place-items:center;font-size:.8rem;font-weight:800;letter-spacing:.02em}
        .logo small{display:block;font-weight:500;color:var(--muted);font-size:.72rem;line-height:1.1}
        .bar nav{display:flex;align-items:center;gap:18px;font-size:.92rem}
        .bar nav a.tautan{color:var(--muted);text-decoration:none}
        .bar nav a.tautan:hover{color:var(--aksen)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;border-radius:10px;font-weight:600;font-size:.95rem;text-decoration:none;border:1px solid transparent}
        .btn.utama{background:var(--aksen);color:#fff}
        .btn.utama:hover{opacity:.92}
        .btn.garis{border-color:rgba(255,255,255,.45);color:#fff}
        .btn.garis:hover{background:rgba(255,255,255,.12)}
        .btn.putih{background:#fff;color:#312e81}
        .btn.putih:hover{background:#eef2ff}
        .hero{background:radial-gradient(1200px 500px at 85% -10%,rgba(45,212,191,.35),transparent 60%),linear-gradient(135deg,var(--hero1),var(--hero2) 60%,var(--hero3));color:#fff}
        .hero .wadah{padding-top:72px;padding-bottom:80px;display:grid;gap:40px;grid-template-columns:1.15fr .85fr;align-items:center}
        .label{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;font-weight:700;background:rgba(255,255,255,.14);padding:5px 12px;border-radius:999px}
        .label i{width:7px;height:7px;border-radius:50%;background:#5eead4;display:inline-block}
        .hero h1{font-size:2.7rem;line-height:1.15;margin:18px 0 14px;letter-spacing:-.01em}
        .hero p.lead{font-size:1.1rem;color:rgba(255,255,255,.86);margin:0 0 28px;max-width:34em}
        .aksi{display:flex;gap:12px;flex-wrap:wrap}
        .stat{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .stat .kotak{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:16px;padding:18px;backdrop-filter:blur(4px)}
        .stat .angka{font-size:2rem;font-weight:800;line-height:1.1}
        .stat .ket{font-size:.85rem;color:rgba(255,255,255,.8);margin-top:4px}
        .stat .catatan{grid-column:1/-1;font-size:.78rem;color:rgba(255,255,255,.7);margin:2px 2px 0}
        section{padding:68px 0}
        section.pita{background:var(--card);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
        .kepala{max-width:620px;margin:0 0 32px}
        .kepala .atas{color:var(--aksen);font-weight:700;font-size:.8rem;letter-spacing:.07em;text-transform:uppercase}
        .kepala h2{font-size:1.85rem;line-height:1.25;margin:.25em 0 .35em}
        .kepala p{color:var(--muted);margin:0}
        .grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr))}
        .kartu{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:22px}
        section.pita .kartu{background:var(--bg)}
        .kartu h3{margin:12px 0 6px;font-size:1.05rem}
        .kartu p{margin:0;color:var(--muted);font-size:.93rem}
        .ikon{width:40px;height:40px;border-radius:11px;background:var(--aksen-bg);color:var(--aksen);display:grid;place-items:center;font-size:1.2rem}
        .peran a.kartu{display:block;text-decoration:none;color:inherit}
        .peran a.kartu:hover{border-color:var(--aksen)}
        .peran a.kartu h3{color:var(--aksen);margin-top:0}
        .tangkap{border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px -25px rgba(15,23,42,.45);background:var(--card)}
        .tangkap img{display:block;width:100%;height:auto}
        .dua{display:grid;gap:44px;grid-template-columns:.9fr 1.1fr;align-items:center}
        .dua ul{padding:0;margin:18px 0 0;list-style:none;display:grid;gap:10px}
        .dua li{display:flex;gap:10px;color:var(--muted)}
        .dua li::before{content:"✓";color:var(--aksen);font-weight:800}
        .cta{background:linear-gradient(135deg,var(--hero1),var(--hero2));color:#fff;border-radius:22px;padding:44px;display:flex;justify-content:space-between;align-items:center;gap:24px;flex-wrap:wrap}
        .cta h2{margin:0 0 6px;font-size:1.6rem}
        .cta p{margin:0;color:rgba(255,255,255,.85)}
        footer{border-top:1px solid var(--line);padding:28px 0;color:var(--muted);font-size:.88rem}
        footer .wadah{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}

        .bar-daftar{display:grid;gap:9px;margin:0;padding:0;list-style:none}
        .bar-daftar li{display:grid;grid-template-columns:minmax(90px,38%) 1fr auto;gap:10px;align-items:center;font-size:.88rem}
        .bar-daftar .nama{color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .bar-daftar .jalur{height:10px;border-radius:999px;background:var(--aksen-bg);overflow:hidden}
        .bar-daftar .isi{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,var(--hero2),var(--hero3))}
        .bar-daftar b{font-variant-numeric:tabular-nums}
        .panel-data{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))}
        .panel-data .kartu h3{margin:0 0 14px;font-size:1rem}
        .garis{position:relative;display:grid;gap:0;margin:0;padding:0;list-style:none}
        .garis li{position:relative;padding:0 0 22px 34px}
        .garis li::before{content:"";position:absolute;left:11px;top:22px;bottom:-2px;width:2px;background:var(--line)}
        .garis li:last-child::before{display:none}
        .garis li i{position:absolute;left:0;top:2px;width:24px;height:24px;border-radius:50%;background:var(--aksen);color:#fff;display:grid;place-items:center;font-size:.72rem;font-style:normal;font-weight:800}
        .garis b{display:block}
        .garis span{color:var(--muted);font-size:.92rem}
        .status{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:18px}
        .status span{padding:5px 12px;border-radius:999px;font-size:.82rem;font-weight:600;border:1px solid var(--line);background:var(--card)}
        .status .ok{color:#047857;border-color:#a7f3d0;background:#ecfdf5}
        .status .ret{color:#b45309;border-color:#fde68a;background:#fffbeb}
        .status .tol{color:#b91c1c;border-color:#fecaca;background:#fef2f2}
        .status em{color:var(--muted);font-style:normal}
        .modul{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}
        .modul div{border:1px solid var(--line);background:var(--card);border-radius:12px;padding:14px 16px}
        .modul b{display:block;font-size:.95rem}
        .modul span{color:var(--muted);font-size:.85rem}
        details.tanya{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 18px;margin:0 0 10px}
        details.tanya summary{cursor:pointer;font-weight:600}
        details.tanya p{margin:10px 0 0;color:var(--muted);font-size:.94rem}
        .api{background:#0f172a;color:#e2e8f0;border-radius:14px;padding:18px 20px;font:.85rem/1.7 ui-monospace,SFMono-Regular,Menlo,monospace;overflow:auto}
        .api .k{color:#94a3b8}.api .h{color:#5eead4}
        @media (max-width:860px){
            .hero .wadah,.dua{grid-template-columns:1fr}
            .hero h1{font-size:2.1rem}
            .bar nav a.tautan{display:none}
            .cta{padding:30px 24px}
            .aksi .btn{width:100%}
        }
    </style>
</head>
<body>
<header class="bar">
    <div class="wadah">
        <a class="logo" href="{{ url('/') }}"><span class="mono">SDM</span><span>SDM FKIP Unsil<small>Universitas Siliwangi</small></span></a>
        <nav>
            <a class="tautan" href="#fitur">Fitur</a>
            @if (($statistik['jumlah_dosen'] ?? 0) > 0)<a class="tautan" href="#gambaran">Gambaran</a>@endif
            <a class="tautan" href="#peran">Peran</a>
            <a class="tautan" href="#privasi">Privasi</a>
            <a class="tautan" href="{{ url('panduan/index.html') }}">Panduan</a>
            <a class="btn utama" href="{{ url('saya') }}">Masuk</a>
        </nav>
    </div>
</header>

<main>
<div class="hero">
    <div class="wadah">
        <div>
            <span class="label"><i></i> Fakultas Keguruan dan Ilmu Pendidikan</span>
            <h1>Data kepegawaian FKIP, rapi dan selalu terkini.</h1>
            <p class="lead">Satu tempat untuk profil, jabatan fungsional, pangkat, pendidikan, sertifikasi, BKD, dan tenggat penting dosen serta tenaga kependidikan Universitas Siliwangi.</p>
            <div class="aksi">
                <a class="btn putih" href="{{ url('saya') }}">Masuk Dosen &amp; Tendik</a>
                <a class="btn garis" href="{{ url('admin') }}">Masuk Admin &amp; Pimpinan</a>
            </div>
        </div>
        @if (($statistik['jumlah_dosen'] ?? 0) + ($statistik['jumlah_tendik'] ?? 0) > 0)
            <div class="stat">
                <div class="kotak"><div class="angka">{{ number_format($statistik['jumlah_dosen']) }}</div><div class="ket">Dosen aktif</div></div>
                <div class="kotak"><div class="angka">{{ number_format($statistik['jumlah_tendik']) }}</div><div class="ket">Tenaga kependidikan</div></div>
                <div class="kotak"><div class="angka">{{ $statistik['persen_s3'] }}%</div><div class="ket">Dosen berpendidikan S3</div></div>
                <div class="kotak"><div class="angka">{{ $statistik['persen_serdos'] }}%</div><div class="ket">Dosen bersertifikasi</div></div>
                <p class="catatan">Angka agregat, tanpa data pribadi.</p>
            </div>
        @endif
    </div>
</div>

<section id="fitur">
    <div class="wadah">
        <div class="kepala">
            <span class="atas">Fitur</span>
            <h2>Dari data induk sampai laporan akreditasi</h2>
            <p>Semua kebutuhan administrasi SDM fakultas dalam satu sistem yang saling terhubung.</p>
        </div>
        <div class="grid">
            <div class="kartu"><div class="ikon">👤</div><h3>Data induk pegawai</h3><p>NIP, NIDN, NUPTK, status ASN/Non-ASN, homebase program studi, dan unit kerja.</p></div>
            <div class="kartu"><div class="ikon">📈</div><h3>Riwayat karier</h3><p>Jabatan fungsional, pangkat/golongan, KGB, jabatan struktural, pendidikan, dan studi lanjut.</p></div>
            <div class="kartu"><div class="ikon">⏰</div><h3>Pengingat tenggat</h3><p>Kenaikan pangkat, KGB, kenaikan jabatan, pensiun, dan masa berlaku dokumen dihitung otomatis.</p></div>
            <div class="kartu"><div class="ikon">📊</div><h3>Rekap BKD</h3><p>Impor Excel dari SISTER per semester dengan pencocokan NIDN, NUPTK, dan NIP.</p></div>
            <div class="kartu"><div class="ikon">🏅</div><h3>Sertifikasi &amp; penghargaan</h3><p>Sertifikat pendidik, pelatihan, dan penghargaan tersimpan beserta masa berlakunya.</p></div>
            <div class="kartu"><div class="ikon">📑</div><h3>Laporan &amp; akreditasi</h3><p>Ekspor DUK dan profil, serta rekap DTPS dan rasio dosen–mahasiswa untuk akreditasi.</p></div>
        </div>
    </div>
</section>


@php
    $maks = fn (array $d): int => max(1, ...array_values($d ?: [1]));
    $urut = fn (array $d, int $n = 8): array => array_slice($d, 0, $n, true);
    $jabatan = $statistik['dosen_per_jabatan'] ?? [];
    $pendidikan = $statistik['dosen_per_pendidikan'] ?? [];
    $prodi = $statistik['dosen_per_prodi'] ?? [];
    arsort($prodi);
@endphp
@if (($statistik['jumlah_dosen'] ?? 0) > 0)
<section class="pita" id="gambaran">
    <div class="wadah">
        <div class="kepala">
            <span class="atas">Gambaran SDM fakultas</span>
            <h2>Komposisi dosen secara agregat</h2>
            <p>Dihitung langsung dari data induk dan diperbarui otomatis. Hanya angka ringkasan, tanpa data pribadi.</p>
        </div>
        <div class="panel-data">
            @foreach ([['Jabatan fungsional', $jabatan], ['Pendidikan tertinggi', $pendidikan], ['Dosen per program studi', $prodi]] as [$judul, $set])
                @if (count($set))
                    <div class="kartu">
                        <h3>{{ $judul }}</h3>
                        <ul class="bar-daftar">
                            @foreach ($urut($set) as $nama => $jumlah)
                                <li><span class="nama" title="{{ $nama }}">{{ $nama }}</span><span class="jalur"><span class="isi" style="width:{{ round($jumlah / $maks($set) * 100) }}%"></span></span><b>{{ number_format($jumlah) }}</b></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

<section id="swalayan">
    <div class="wadah dua">
        <div>
            <div class="kepala" style="margin:0">
                <span class="atas">Swalayan pegawai</span>
                <h2>Perbarui data sendiri, tetap terverifikasi</h2>
                <p>Dosen dan tendik mengajukan perubahan dari akun masing-masing. Admin memeriksa sebelum data resmi berubah.</p>
            </div>
            <ul>
                <li>Ajukan perubahan profil dan riwayat tanpa menulis langsung ke data induk.</li>
                <li>Pantau status usulan: diajukan, disetujui, dikembalikan, atau ditolak beserta catatannya.</li>
                <li>Berkas pendukung cukup berupa tautan Google Drive, tidak ada unggahan.</li>
            </ul>
        </div>
        <div class="tangkap"><img src="{{ asset('panduan/img/kepeg-dasbor.png') }}" alt="Tangkapan layar dasbor kepegawaian" loading="lazy"></div>
    </div>
</section>

<section class="pita peran" id="peran">
    <div class="wadah">
        <div class="kepala">
            <span class="atas">Peran</span>
            <h2>Setiap pengguna melihat yang relevan</h2>
            <p>Akses dibatasi sesuai peran. Klik untuk membaca panduannya.</p>
        </div>
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
            <a class="kartu" href="{{ url('panduan/dosen-tendik.html') }}"><h3>Dosen &amp; Tendik</h3><p>Lihat profil, ajukan perubahan, dan pantau usulan.</p></a>
            <a class="kartu" href="{{ url('panduan/admin-prodi.html') }}"><h3>Admin Prodi</h3><p>Mengelola dan memverifikasi data pegawai program studinya.</p></a>
            <a class="kartu" href="{{ url('panduan/admin-kepegawaian.html') }}"><h3>Admin Kepegawaian</h3><p>Pengelolaan data fakultas, verifikasi, impor BKD, dan laporan.</p></a>
            <a class="kartu" href="{{ url('panduan/pimpinan.html') }}"><h3>Pimpinan</h3><p>Melihat dasbor dan mengekspor laporan.</p></a>
        </div>
    </div>
</section>


<section id="siklus">
    <div class="wadah dua">
        <div>
            <div class="kepala" style="margin:0">
                <span class="atas">Pengingat otomatis</span>
                <h2>Tidak ada tenggat karier yang terlewat</h2>
                <p>Sistem menghitung sendiri jadwal penting tiap pegawai dari riwayat dan aturan yang berlaku, lalu mengirim pengingat bertahap.</p>
            </div>
            <div class="status">
                <span>90 hari</span><em>→</em><span>30 hari</span><em>→</em><span>7 hari sebelum tenggat</span>
            </div>
        </div>
        <ol class="garis">
            <li><i>1</i><b>Kenaikan pangkat &amp; KGB</b><span>Interval dihitung dari TMT terakhir sesuai jenis status kepegawaian.</span></li>
            <li><i>2</i><b>Kenaikan jabatan fungsional</b><span>Syarat jenjang dan angka kredit mengikuti ketentuan yang tersimpan di sistem.</span></li>
            <li><i>3</i><b>Masa berlaku dokumen &amp; sertifikat</b><span>Dokumen yang lewat tanggal otomatis ditandai kedaluwarsa.</span></li>
            <li><i>4</i><b>Batas usia pensiun</b><span>Proyeksi pensiun per tahun membantu perencanaan kebutuhan dosen.</span></li>
        </ol>
    </div>
</section>

<section class="pita" id="modul">
    <div class="wadah">
        <div class="kepala">
            <span class="atas">Cakupan data</span>
            <h2>Satu profil, seluruh rekam jejak</h2>
        </div>
        <div class="modul">
            <div><b>Identitas &amp; status</b><span>NIP, NIDN, NUPTK, ASN/Non-ASN, homebase</span></div>
            <div><b>Jabatan fungsional</b><span>Riwayat, TMT, dan status terkini</span></div>
            <div><b>Pangkat &amp; KGB</b><span>Golongan, masa kerja, kenaikan berkala</span></div>
            <div><b>Jabatan struktural</b><span>Penugasan dan masa jabatan</span></div>
            <div><b>Pendidikan</b><span>Jenjang, institusi, dan studi lanjut</span></div>
            <div><b>Sertifikasi</b><span>Serdos dan sertifikat lain beserta masa berlaku</span></div>
            <div><b>Pelatihan &amp; penghargaan</b><span>Rekam kegiatan pengembangan diri</span></div>
            <div><b>Keluarga</b><span>Data tanggungan, terlindungi dan terenkripsi</span></div>
            <div><b>Dokumen</b><span>Tautan berkas dengan masa berlaku</span></div>
            <div><b>Rekap BKD</b><span>Impor Excel SISTER per semester</span></div>
        </div>
    </div>
</section>

<section id="verifikasi">
    <div class="wadah">
        <div class="kepala">
            <span class="atas">Kendali mutu data</span>
            <h2>Setiap perubahan lewat verifikasi</h2>
            <p>Dosen dan tendik tidak menulis langsung ke data induk. Perubahan diajukan sebagai usulan, diperiksa admin, lalu diterapkan dengan jejak audit.</p>
            <div class="status">
                <span>Diajukan</span><em>→</em><span class="ok">Disetujui &amp; diterapkan</span><span class="ret">Dikembalikan dengan catatan</span><span class="tol">Ditolak dengan alasan</span>
            </div>
        </div>
    </div>
</section>

<section class="pita" id="integrasi">
    <div class="wadah dua">
        <div class="kepala" style="margin:0">
            <span class="atas">Untuk sistem FKIP lain</span>
            <h2>Data rujukan yang bisa dipakai bersama</h2>
            <p>API read-only bertoken menyediakan data pegawai non-sensitif bagi sistem fakultas lain, sehingga tidak perlu menyalin data secara manual.</p>
        </div>
        <div class="api"><span class="k">GET</span> /api/v1/pegawai<br><span class="k">Authorization:</span> <span class="h">Bearer &lt;token&gt;</span><br><span class="k">ability:</span> sdm:read · 60 permintaan/menit<br><span class="k">isi:</span> hanya field non-sensitif</div>
    </div>
</section>

<section id="tanya">
    <div class="wadah" style="max-width:820px">
        <div class="kepala">
            <span class="atas">Pertanyaan umum</span>
            <h2>Yang sering ditanyakan</h2>
        </div>
        <details class="tanya"><summary>Siapa yang bisa mengakses sistem ini?</summary><p>Dosen dan tenaga kependidikan FKIP, admin kepegawaian, admin program studi, dan pimpinan. Setiap peran hanya melihat data sesuai kewenangannya; admin prodi dibatasi pada prodinya.</p></details>
        <details class="tanya"><summary>Apakah saya perlu mengunggah berkas?</summary><p>Tidak. Berkas disimpan di Google Drive Anda sendiri, dan sistem hanya menyimpan tautannya. Berkas sensitif wajib dibagikan secara terbatas.</p></details>
        <details class="tanya"><summary>Bagaimana jika data saya keliru?</summary><p>Masuk ke "Data Saya", ajukan perubahan beserta tautan bukti. Admin akan memeriksa dan menyetujui atau mengembalikannya dengan catatan.</p></details>
        <details class="tanya"><summary>Siapa yang dapat melihat NIK, NPWP, dan rekening?</summary><p>Hanya pemilik data dan peran yang diberi izin khusus. Datanya tersimpan terenkripsi, tampil bertopeng, dan setiap akses penuh dicatat.</p></details>
        <details class="tanya"><summary>Ke mana saya meminta bantuan?</summary><p>Mulai dari <a href="{{ url('panduan/index.html') }}">Panduan Pengguna</a> per peran, lalu hubungi admin kepegawaian fakultas bila masih ada kendala.</p></details>
    </div>
</section>

<section class="pita" id="privasi">
    <div class="wadah dua">
        <div class="kepala" style="margin:0">
            <span class="atas">Privasi &amp; keamanan</span>
            <h2>Data pribadi dijaga sejak awal</h2>
            <p>Sistem dirancang agar data sensitif tidak tersebar.</p>
            <ul>
                <li>NIK, NPWP, dan rekening terenkripsi serta tampil bertopeng.</li>
                <li>Akses data sensitif dicatat dalam log audit.</li>
                <li>Berkas pribadi tidak diunggah; hanya tautan Drive berbagi terbatas.</li>
                <li>Ekspor dan API tidak memuat data pribadi sensitif.</li>
                <li>Autentikasi dua langkah untuk peran admin.</li>
            </ul>
        </div>
        <div class="grid" style="grid-template-columns:1fr 1fr">
            <div class="kartu"><div class="ikon">🔒</div><h3>Terenkripsi</h3><p>Data sensitif disimpan terenkripsi.</p></div>
            <div class="kartu"><div class="ikon">🧾</div><h3>Teraudit</h3><p>Setiap perubahan penting tercatat.</p></div>
            <div class="kartu"><div class="ikon">🔗</div><h3>Tanpa unggahan</h3><p>Berkas tetap di Drive pemiliknya.</p></div>
            <div class="kartu"><div class="ikon">✅</div><h3>Terverifikasi</h3><p>Perubahan lewat persetujuan admin.</p></div>
        </div>
    </div>
</section>

<section style="padding-top:0">
    <div class="wadah">
        <div class="cta">
            <div>
                <h2>Siap memperbarui data Anda?</h2>
                <p>Masuk dengan akun institusi, atau baca panduan terlebih dahulu.</p>
            </div>
            <div class="aksi">
                <a class="btn putih" href="{{ url('saya') }}">Masuk sekarang</a>
                <a class="btn garis" href="{{ url('panduan/index.html') }}">Panduan Pengguna</a>
            </div>
        </div>
    </div>
</section>
</main>

<footer>
    <div class="wadah">
        <span>© {{ now()->year }} Fakultas Keguruan dan Ilmu Pendidikan, Universitas Siliwangi</span>
        <span>SDM FKIP Unsil · v{{ config('app.version') }}</span>
    </div>
</footer>
</body>
</html>
