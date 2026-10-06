<?php

namespace App\Models;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ekspor extends Export
{
    use HasUuids;

    protected $table = 'exports';
}
