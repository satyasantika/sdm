<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('code') - @yield('title') · SDM FKIP Unsil</title>
    <style>
        :root{color-scheme:light;--bg:#f8fafc;--fg:#0f172a;--muted:#475569;--card:#fff;--line:#e2e8f0;--aksen:#4f46e5}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);color:var(--fg);font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
        .wadah{max-width:460px;width:100%;margin:0 auto;padding:40px 24px;text-align:center}
        .merek{display:flex;align-items:center;justify-content:center;gap:8px;color:var(--aksen);font-weight:700;font-size:.85rem;letter-spacing:.02em;text-transform:uppercase;margin:0 0 28px}
        .merek span.titik{width:7px;height:7px;border-radius:50%;background:var(--aksen);display:inline-block}
        .kode{font-size:3.4rem;font-weight:800;color:var(--aksen);line-height:1;margin:0 0 .2em}
        h1{font-size:1.3rem;margin:0 0 .6em}
        p.pesan{color:var(--muted);font-size:1rem;margin:0 0 32px}
        .aksi{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:.95rem;font-weight:600;border:1px solid var(--line);cursor:pointer;font-family:inherit}
        .btn.utama{background:var(--aksen);border-color:var(--aksen);color:#fff}
        .btn.sekunder{background:var(--card);color:var(--fg)}
        .btn.utama:hover{opacity:.9}
        .btn.sekunder:hover{border-color:var(--aksen)}
    </style>
</head>
<body>
<div class="wadah">
    <p class="merek"><span class="titik"></span> SDM FKIP Unsil</p>
    <div class="kode">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p class="pesan">@yield('message')</p>
    <div class="aksi">
        <button type="button" class="btn sekunder" onclick="(function(){ if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/') }}'; } })()">&larr; Kembali</button>
        <a class="btn utama" href="{{ url('/') }}">Beranda</a>
    </div>
</div>
</body>
</html>
