@props(['judul', 'subjudul', 'poin' => [], 'aksen' => 'indigo'])

@php
    $statistik = rescue(fn (): array => app(\App\Actions\Laporan\HitungStatistikDasbor::class)->handle(), null, report: false);
@endphp

<style>
    .fi-simple-layout{--sdm-a:#312e81;--sdm-b:#4f46e5;--sdm-c:#0f766e}
    .fi-simple-layout.sdm-teal,body:has(.sdm-samping[data-aksen=teal]) .fi-simple-layout{--sdm-a:#134e4a;--sdm-b:#0f766e;--sdm-c:#4f46e5}
    .sdm-samping{position:relative;overflow:hidden;color:#fff;background:radial-gradient(900px 420px at 90% -10%,rgba(45,212,191,.35),transparent 60%),linear-gradient(145deg,var(--sdm-a),var(--sdm-b) 62%,var(--sdm-c));padding:28px 24px;font-family:inherit}
    .sdm-samping .merek{display:flex;align-items:center;gap:10px;font-weight:700}
    .sdm-samping .mono{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.18);display:grid;place-items:center;font-size:.8rem;font-weight:800;border:1px solid rgba(255,255,255,.3)}
    .sdm-samping small{display:block;font-weight:500;opacity:.8;font-size:.74rem;line-height:1.1}
    .sdm-samping h2{font-size:1.55rem;line-height:1.25;margin:22px 0 8px;font-weight:800}
    .sdm-samping p{margin:0;opacity:.88;max-width:26em;font-size:.95rem}
    .sdm-samping ul{display:none;list-style:none;margin:26px 0 0;padding:0;gap:10px}
    .sdm-samping li{display:flex;gap:10px;font-size:.9rem;opacity:.92}
    .sdm-samping li::before{content:"✓";font-weight:800;color:#5eead4}
    .sdm-samping .angka{display:none;gap:12px;margin-top:28px}
    .sdm-samping .angka div{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:12px;padding:10px 16px}
    .sdm-samping .angka b{display:block;font-size:1.4rem;line-height:1.2}
    .sdm-samping .angka span{font-size:.78rem;opacity:.85}
    .sdm-samping .kembali{display:none;margin-top:28px;font-size:.85rem}
    .sdm-samping .kembali a{color:#fff;opacity:.85}
    @media (min-width:1024px){
        .fi-simple-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);grid-template-rows:1fr auto;min-height:100vh;align-items:stretch;justify-items:stretch}
        .fi-simple-layout > :not(.sdm-samping):not(.fi-simple-main-ctn){grid-column:2}
        .sdm-samping{padding:56px 52px;display:flex;flex-direction:column;justify-content:center}
        .sdm-samping h2{font-size:2.2rem}
        .sdm-samping ul{display:grid}
        .sdm-samping .angka{display:flex}
        .sdm-samping .kembali{display:block}
        .fi-simple-layout > .fi-simple-main-ctn{grid-column:2;grid-row:1}
        .fi-simple-layout > .sdm-samping{grid-column:1;grid-row:1 / 3}
    }
</style>

<aside class="sdm-samping" data-aksen="{{ $aksen }}">
    <div class="merek"><span class="mono">SDM</span><span>SDM FKIP Unsil<small>Universitas Siliwangi</small></span></div>
    <h2>{{ $judul }}</h2>
    <p>{{ $subjudul }}</p>
    <ul>
        @foreach ($poin as $baris)
            <li>{{ $baris }}</li>
        @endforeach
    </ul>
    @if ($statistik)
        <div class="angka">
            <div><b>{{ number_format($statistik['jumlah_dosen']) }}</b><span>Dosen aktif</span></div>
            <div><b>{{ number_format($statistik['jumlah_tendik']) }}</b><span>Tenaga kependidikan</span></div>
        </div>
    @endif
    <div class="kembali"><a href="{{ url('/') }}">&larr; Kembali ke beranda</a></div>
</aside>
