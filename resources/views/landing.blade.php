<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SDM FKIP Unsil</title>
    <meta name="description" content="Sistem Informasi Sumber Daya Manusia Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi">
    <style>
        :root{--bg:#f8fafc;--fg:#0f172a;--muted:#475569;--card:#fff;--line:#e2e8f0;--aksen:#4f46e5}
        @media (prefers-color-scheme:dark){:root{--bg:#0b1020;--fg:#e5e7eb;--muted:#94a3b8;--card:#131a2e;--line:#26304a;--aksen:#a5b4fc}}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
        .wadah{max-width:880px;margin:0 auto;padding:48px 16px}
        h1{font-size:2rem;line-height:1.25;margin:0 0 .3em}
        p.lead{color:var(--muted);font-size:1.08rem;margin:0 0 28px}
        .grid{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
        a.kartu{display:block;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px;text-decoration:none;color:inherit}
        a.kartu:hover{border-color:var(--aksen)}
        a.kartu h2{margin:0 0 4px;font-size:1.1rem;color:var(--aksen)}
        a.kartu p{margin:0;color:var(--muted);font-size:.93rem}
        footer{margin-top:36px;color:var(--muted);font-size:.88rem}
    </style>
</head>
<body>
<div class="wadah">
    <h1>Sistem Informasi SDM FKIP Unsil</h1>
    <p class="lead">Data kepegawaian dosen dan tenaga kependidikan Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi.</p>
    <div class="grid">
        <a class="kartu" href="{{ url('saya') }}"><h2>Masuk Dosen &amp; Tendik</h2><p>Lihat profil Anda dan ajukan perubahan data.</p></a>
        <a class="kartu" href="{{ url('admin') }}"><h2>Masuk Admin &amp; Pimpinan</h2><p>Pengelolaan data, verifikasi, dan laporan.</p></a>
        <a class="kartu" href="{{ url('panduan/index.html') }}"><h2>Panduan Pengguna</h2><p>Langkah demi langkah per peran, dengan tangkapan layar.</p></a>
    </div>
    <footer>Versi {{ config('app.version') }}. Berkas pribadi tidak diunggah ke sistem; cukup tempel tautan Google Drive dengan akses terbatas.</footer>
</div>
</body>
</html>
