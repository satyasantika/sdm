<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('judul')</title>
    <style>
        @page { margin: 90px 40px 60px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; }
        header { position: fixed; top: -75px; left: 0; right: 0; text-align: center; border-bottom: 2px solid #111; padding-bottom: 6px; }
        header .instansi { font-size: 12px; font-weight: bold; }
        header .unit { font-size: 10px; }
        h1 { font-size: 13px; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 11px; margin: 14px 0 4px; border-bottom: 1px solid #999; padding-bottom: 2px; }
        .catatan { font-size: 8px; color: #555; margin: 2px 0 8px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #777; padding: 3px 4px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .kv td { border: none; padding: 1px 4px; }
        .kv td:first-child { width: 28%; color: #444; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <header>
        <div class="instansi">Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi</div>
        <div class="unit">Sistem Informasi SDM</div>
    </header>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
            $pdf->page_text(40, $pdf->get_height() - 40, 'Dicetak: {{ now()->translatedFormat('d F Y') }}', $font, 8, [0.3, 0.3, 0.3]);
            $pdf->page_text($pdf->get_width() - 110, $pdf->get_height() - 40, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0.3, 0.3, 0.3]);
        }
    </script>

    @yield('isi')
</body>
</html>
