<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Menulis Excel (xlsx) kecil langsung ke respons (stream), tanpa menyimpan berkas. */
class TulisXlsx
{
    /**
     * @param  list<string>  $header
     * @param  iterable<int, list<scalar|null>>  $baris
     */
    public static function unduh(string $namaBerkas, array $header, iterable $baris): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $baris): void {
            $penulis = new Writer;
            $penulis->openToFile('php://output');
            $penulis->addRow(Row::fromValues($header));

            foreach ($baris as $b) {
                $penulis->addRow(Row::fromValues($b));
            }

            $penulis->close();
        }, $namaBerkas, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
