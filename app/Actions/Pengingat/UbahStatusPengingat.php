<?php

namespace App\Actions\Pengingat;

use App\Enums\StatusPengingat;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Models\Pengingat;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UbahStatusPengingat
{
    public function handle(Pengingat $pengingat, StatusPengingat $ke, User $oleh, ?string $catatan = null): Pengingat
    {
        if (! $pengingat->status->bolehBerpindahKe($ke)) {
            throw new TransisiUsulanTidakSah("Pengingat berstatus {$pengingat->status->getLabel()} tidak dapat berpindah ke {$ke->getLabel()}.");
        }

        $catatan = $catatan === null ? null : trim($catatan);

        if ($ke === StatusPengingat::Diabaikan && blank($catatan)) {
            throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi saat mengabaikan pengingat.']);
        }

        $dari = $pengingat->status;
        $pengingat->forceFill(['status' => $ke, 'catatan' => $catatan ?: $pengingat->catatan])->save();

        activity()->performedOn($pengingat)->causedBy($oleh)
            ->withProperties(['dari' => $dari->value, 'ke' => $ke->value, 'catatan' => $catatan])
            ->log('status pengingat diubah');

        return $pengingat;
    }
}
