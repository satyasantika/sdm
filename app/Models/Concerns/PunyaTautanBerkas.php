<?php

namespace App\Models\Concerns;

use App\Enums\JenisTautan;
use App\Models\TautanBerkas;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait PunyaTautanBerkas
{
    public function tautanBerkas(): MorphMany
    {
        return $this->morphMany(TautanBerkas::class, 'pemilik');
    }

    /** Tautan terbaru dengan jenis tertentu. */
    public function tautan(JenisTautan|string $jenis): ?TautanBerkas
    {
        $jenis = $jenis instanceof JenisTautan ? $jenis : JenisTautan::from($jenis);

        /** @var TautanBerkas|null $tautan */
        $tautan = $this->tautanBerkas()->where('jenis', $jenis->value)->latest()->first();

        return $tautan;
    }
}
