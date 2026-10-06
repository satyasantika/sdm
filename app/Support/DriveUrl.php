<?php

namespace App\Support;

use App\Enums\PenyediaBerkas;

/** Mengurai URL berkas: penyedia, id berkas Drive, dan deteksi tautan folder. */
class DriveUrl
{
    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }

    public static function penyedia(string $url): PenyediaBerkas
    {
        $host = self::host($url);

        return match (true) {
            $host === 'drive.google.com' => PenyediaBerkas::GoogleDrive,
            $host === 'docs.google.com' => PenyediaBerkas::GoogleDocs,
            $host === 'onedrive.live.com' || ($host !== null && str_ends_with($host, '.sharepoint.com')) => PenyediaBerkas::Onedrive,
            $host === 'unsil.ac.id' || ($host !== null && str_ends_with($host, '.unsil.ac.id')) => PenyediaBerkas::Unsil,
            default => PenyediaBerkas::Lainnya,
        };
    }

    /** Pola: /file/d/{id}, open?id={id}, uc?id={id}, docs.google.com/{document|spreadsheets|presentation}/d/{id}. */
    public static function fileId(string $url): ?string
    {
        if (self::folder($url)) {
            return null;
        }

        if (preg_match('#/(?:file|document|spreadsheets|presentation|forms)/d/([A-Za-z0-9_-]{10,})#', $url, $m)) {
            return $m[1];
        }

        $host = self::host($url);
        if (in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            if (isset($query['id']) && is_string($query['id']) && preg_match('/^[A-Za-z0-9_-]{10,}$/', $query['id'])) {
                return $query['id'];
            }
        }

        return null;
    }

    public static function folder(string $url): bool
    {
        return (bool) preg_match('#/drive/(?:u/\d+/)?folders/|/folderview#', $url);
    }
}
