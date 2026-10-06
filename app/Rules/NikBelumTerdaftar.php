<?php

namespace App\Rules;

use App\Models\Pegawai;
use App\Support\HashIdentitas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** NIK unik lewat nik_hash (BR-01); mengabaikan record sendiri. */
class NikBelumTerdaftar implements ValidationRule
{
    public function __construct(private readonly ?string $abaikanId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $ada = Pegawai::withTrashed()
            ->where('nik_hash', HashIdentitas::nik($value))
            ->when($this->abaikanId, fn ($q, $id) => $q->whereKeyNot($id))
            ->exists();

        if ($ada) {
            $fail('NIK sudah terdaftar pada pegawai lain.');
        }
    }
}
