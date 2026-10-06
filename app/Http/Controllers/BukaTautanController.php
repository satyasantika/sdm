<?php

namespace App\Http\Controllers;

use App\Models\TautanBerkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BukaTautanController extends Controller
{
    /** Redirect hanya ke URL tersimpan; tidak ada parameter tujuan (bebas open-redirect). */
    public function __invoke(Request $request, TautanBerkas $tautanBerkas): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('buka', $tautanBerkas);

        if ($tautanBerkas->is_sensitif) {
            activity('akses-berkas')
                ->performedOn($tautanBerkas)
                ->causedBy($request->user())
                ->withProperties(['jenis' => $tautanBerkas->jenis->value, 'ip' => $request->ip()])
                ->log('buka');
        }

        return redirect()->away($tautanBerkas->url);
    }
}
