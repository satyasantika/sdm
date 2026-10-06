<?php

/** BR-24: tidak ada unggah berkas pengguna ke server. */
test('tidak ada FileUpload di app selain impor', function () {
    $pelanggar = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));
    foreach ($iterator as $berkas) {
        if (! str_ends_with((string) $berkas, '.php') || str_contains((string) $berkas, DIRECTORY_SEPARATOR.'Imports'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        if (str_contains(file_get_contents((string) $berkas), 'FileUpload')) {
            $pelanggar[] = str_replace(base_path().'/', '', (string) $berkas);
        }
    }

    expect($pelanggar)->toBe([]);
});

test('tidak ada penulisan berkas pengguna ke disk storage', function () {
    $pelanggar = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));
    foreach ($iterator as $berkas) {
        if (! str_ends_with((string) $berkas, '.php')) {
            continue;
        }

        if (preg_match('/Storage::(disk\([^)]*\)->)?(put|putFile|putFileAs)\(/', file_get_contents((string) $berkas))) {
            $pelanggar[] = str_replace(base_path().'/', '', (string) $berkas);
        }
    }

    expect($pelanggar)->toBe([]);
});
