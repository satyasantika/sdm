<?php

namespace App\Http\Controllers;

use App\Support\KeluaranSementara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnduhKeluaranController extends Controller
{
    /** Hanya pembuat berkas; berkas sementara kedaluwarsa ≤ 24 jam. */
    public function __invoke(Request $request, string $id): StreamedResponse
    {
        $meta = KeluaranSementara::cari($id);
        $disk = Storage::disk((string) config('berkas.disk_tmp', 'tmp'));

        abort_if($meta === null || ! $disk->exists($meta['path']), 404);
        abort_unless($request->user()?->getKey() === $meta['user_id'], 403);

        return $disk->download($meta['path'], $meta['nama']);
    }
}
