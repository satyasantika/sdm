<?php

use Illuminate\Support\Facades\File;

/** Larangan URL absolut berawalan "/" agar aman di bawah sub-path /sdm (STANDAR-TEKNIS §2.4). */
test('view dan js tidak memakai url absolut berawalan slash', function () {
    $berkas = collect([...File::allFiles(resource_path('views')), ...File::allFiles(resource_path('js'))])
        ->filter(fn ($b) => str_ends_with($b->getFilename(), '.blade.php') || $b->getExtension() === 'js');

    expect($berkas)->not->toBeEmpty();

    $pelanggar = $berkas
        ->filter(fn ($b) => preg_match('/(href|src|action)="\/(?!\/)|fetch\([\'"]\//', $b->getContents()) === 1)
        ->map(fn ($b) => $b->getRelativePathname());

    expect($pelanggar->values()->all())->toBe([]);
});
