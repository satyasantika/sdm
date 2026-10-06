<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Model impor Filament dengan kunci UUIDv7 (STANDAR-TEKNIS §4a). */
class Impor extends Import
{
    use HasUuids;

    protected $table = 'imports';

    public function failedRows(): HasMany
    {
        return $this->hasMany(BarisImporGagal::class, 'import_id');
    }
}
