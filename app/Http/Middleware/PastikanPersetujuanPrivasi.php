<?php

namespace App\Http\Middleware;

use App\Models\PersetujuanPrivasi;
use App\Support\Konfigurasi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** BR-18: pegawai wajib menyetujui versi kebijakan privasi terkini sebelum memakai panel swalayan. */
class PastikanPersetujuanPrivasi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('filament.swalayan.pages.persetujuan-privasi', 'filament.swalayan.auth.*')) {
            return $next($request);
        }

        $versi = (string) Konfigurasi::get('versi_kebijakan_privasi', '1');

        if (PersetujuanPrivasi::where('user_id', $user->getKey())->where('versi', $versi)->exists()) {
            return $next($request);
        }

        return redirect()->route('filament.swalayan.pages.persetujuan-privasi');
    }
}
