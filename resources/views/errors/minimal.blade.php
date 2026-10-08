<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('code') - @yield('title') · SDM FKIP Unsil</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root{color-scheme:light;--bg:#f8fafc;--fg:#0f172a;--muted:#475569;--card:#fff;--line:#e2e8f0;--aksen:#4f46e5;--aksen-bg:#eef2ff;--hero1:#312e81;--hero2:#4f46e5;--hero3:#0f766e}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;background:var(--bg);color:var(--fg);font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
        .bar{background:var(--card);border-bottom:1px solid var(--line)}
        .bar .isi{max-width:1080px;margin:0 auto;padding:0 20px;height:62px;display:flex;align-items:center;justify-content:space-between}
        .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--fg);font-weight:700}
        .mono{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--hero2),var(--hero3));color:#fff;display:grid;place-items:center;font-size:.8rem;font-weight:800}
        .logo small{display:block;font-weight:500;color:var(--muted);font-size:.72rem;line-height:1.1}
        main{flex:1;display:grid;place-items:center;padding:40px 20px}
        .kartu{width:100%;max-width:560px;background:var(--card);border:1px solid var(--line);border-radius:22px;padding:44px 36px;text-align:center;box-shadow:0 30px 60px -40px rgba(15,23,42,.35)}
        .kode{font-size:6rem;font-weight:800;line-height:1;margin:0;letter-spacing:-.03em;background:linear-gradient(135deg,var(--hero1),var(--hero2) 55%,var(--hero3));-webkit-background-clip:text;background-clip:text;color:transparent}
        h1{font-size:1.5rem;margin:.35em 0 .4em}
        p.pesan{color:var(--muted);margin:0 auto;max-width:30em}
        .saran{margin:22px auto 0;max-width:30em;text-align:left;background:var(--aksen-bg);border-left:4px solid var(--aksen);border-radius:0 10px 10px 0;padding:10px 14px;font-size:.9rem;color:var(--fg)}
        .aksi{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:30px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;border-radius:10px;text-decoration:none;font-size:.95rem;font-weight:600;border:1px solid var(--line);cursor:pointer;font-family:inherit;min-width:140px}
        .btn.utama{background:var(--aksen);border-color:var(--aksen);color:#fff}
        .btn.sekunder{background:var(--card);color:var(--fg)}
        .btn.utama:hover{opacity:.92}
        .btn.sekunder:hover{border-color:var(--aksen);color:var(--aksen)}
        .bantuan{margin-top:22px;font-size:.85rem;color:var(--muted)}
        .bantuan a{color:var(--aksen)}
        footer{text-align:center;color:var(--muted);font-size:.82rem;padding:0 20px 24px}
        @media (max-width:520px){.kartu{padding:32px 22px}.kode{font-size:4.6rem}.btn{flex:1}}
    </style>
</head>
<body>
<header class="bar">
    <div class="isi">
        <a class="logo" href="{{ url('/') }}"><span class="mono">SDM</span><span>SDM FKIP Unsil<small>Universitas Siliwangi</small></span></a>
    </div>
</header>
<main>
    <div class="kartu" role="alert">
        <p class="kode">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="pesan">@yield('message')</p>
        @hasSection('saran')
            <div class="saran">@yield('saran')</div>
        @endif
        <div class="aksi">
            <button type="button" class="btn sekunder" onclick="(function(){ if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/') }}'; } })()">&larr; Kembali</button>
            <a class="btn utama" href="{{ url('/') }}">Ke Beranda</a>
        </div>
        <p class="bantuan">Butuh bantuan? Buka <a href="{{ url('panduan/index.html') }}">Panduan Pengguna</a> atau hubungi admin kepegawaian fakultas.</p>
    </div>
</main>
<footer>© {{ now()->year }} Fakultas Keguruan dan Ilmu Pendidikan, Universitas Siliwangi · v{{ config('app.version') }}</footer>
</body>
</html>
