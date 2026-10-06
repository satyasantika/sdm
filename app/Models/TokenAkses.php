<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken;

class TokenAkses extends PersonalAccessToken
{
    use HasUuids;

    protected $table = 'personal_access_tokens';
}
