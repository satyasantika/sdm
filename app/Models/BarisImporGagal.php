<?php

namespace App\Models;

use Filament\Actions\Imports\Models\FailedImportRow;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarisImporGagal extends FailedImportRow
{
    use HasUuids;

    protected $table = 'failed_import_rows';

    public function import(): BelongsTo
    {
        return $this->belongsTo(Impor::class, 'import_id');
    }
}
