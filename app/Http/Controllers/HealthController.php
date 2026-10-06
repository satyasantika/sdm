<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = $this->periksa(fn () => DB::connection()->getPdo());
        $redis = $this->periksa(fn () => Redis::connection()->ping());

        return response()->json([
            'status' => $db && $redis ? 'ok' : 'gagal',
            'aplikasi' => config('app.name'),
            'versi' => config('app.version'),
            'waktu' => now()->toIso8601String(),
            'db' => $db,
            'redis' => $redis,
        ], $db && $redis ? 200 : 503);
    }

    private function periksa(callable $uji): bool
    {
        try {
            $uji();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
