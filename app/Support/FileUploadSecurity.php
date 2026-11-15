<?php

namespace App\Support;

use App\Models\RiwayatAktivitas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FileUploadSecurity
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    public const MAX_FILE_BYTES = 10485760;

    private const DANGEROUS_EXTENSIONS_REGEX = '/\.(?:php\d*|phtml|phar|pl|py|cgi|sh|bat|cmd|exe|dll|jsp|asp|aspx|hta|htaccess|ini|svg|html|htm|xhtml|js|mjs|vbs)\b/i';

    private const ALLOWED_MIMES_BY_EXTENSION = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/cdfv2', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/cdfv2', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    ];

    public static function validateUploadedFile(
        ?UploadedFile $file,
        array $allowedExtensions = self::ALLOWED_EXTENSIONS,
        int $maxBytes = self::MAX_FILE_BYTES
    ): ?string {
        if (!$file || !$file->isValid()) {
            return 'File lampiran gagal diunggah atau rusak.';
        }

        $origName = (string) $file->getClientOriginalName();
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        if (!$realPath || !is_file($realPath)) {
            return 'File lampiran tidak dapat diverifikasi di server.';
        }

        $size = (int) (@filesize($realPath) ?: $file->getSize() ?: 0);
        if ($size <= 0) {
            return 'File lampiran kosong atau tidak valid.';
        }
        if ($size > $maxBytes) {
            return 'Ukuran file "' . strip_tags($origName) . '" melebihi batas maksimal (' . (int) floor($maxBytes / 1048576) . ' MB).';
        }

        $realMime = self::detectMimeFromFile($realPath);
        $rawSample = (string) @file_get_contents($realPath, false, null, 0, 262144);

        return self::validateMetadataAndSample($origName, $ext, $realMime, $rawSample, $allowedExtensions);
    }

    public static function validateRawUpload(
        string $originalName,
        string $bytes,
        array $allowedExtensions = self::ALLOWED_EXTENSIONS,
        int $maxBytes = self::MAX_FILE_BYTES
    ): ?string {
        $size = strlen($bytes);
        if ($size <= 0) {
            return 'File lampiran kosong atau format base64 tidak valid.';
        }
        if ($size > $maxBytes) {
            return 'Ukuran file "' . strip_tags($originalName) . '" melebihi batas maksimal (' . (int) floor($maxBytes / 1048576) . ' MB).';
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $realMime = self::detectMimeFromBytes($bytes);
        $sample = substr($bytes, 0, 262144);

        return self::validateMetadataAndSample($originalName, $ext, $realMime, $sample, $allowedExtensions);
    }

    public static function generateSafeFilename(string $extension): string
    {
        $cleanExt = preg_replace('/[^a-z0-9]/', '', strtolower($extension)) ?: 'bin';

        return time() . '_' . bin2hex(random_bytes(6)) . '.' . $cleanExt;
    }

    private static function validateMetadataAndSample(
        string $origName,
        string $ext,
        string $realMime,
        string $rawSample,
        array $allowedExtensions
    ): ?string {
        $safeLabel = strip_tags(basename($origName));

        if ($origName === '' || str_contains($origName, "\0") || str_contains($origName, '..')) {
            self::logBlockedUpload($safeLabel, 'Path traversal atau null byte pada nama file');
            return 'Sistem Keamanan: Nama file "' . $safeLabel . '" tidak valid.';
        }

        if (!in_array($ext, $allowedExtensions, true)) {
            self::logBlockedUpload($safeLabel, 'Ekstensi tidak diizinkan (' . $ext . ')');
            return 'Format file "' . $safeLabel . '" tidak sesuai! Hanya diizinkan format ' . strtoupper(implode(', ', $allowedExtensions)) . '.';
        }

        if (preg_match(self::DANGEROUS_EXTENSIONS_REGEX, $origName)) {
            self::logBlockedUpload($safeLabel, 'Ekstensi ganda berbahaya');
            return 'Sistem Keamanan: File "' . $safeLabel . '" ditolak karena mengandung ekstensi ganda berbahaya.';
        }

        if ($realMime !== '') {
            $expectedMimes = self::ALLOWED_MIMES_BY_EXTENSION[$ext] ?? [];
            if (!empty($expectedMimes) && !in_array($realMime, $expectedMimes, true)) {
                self::logBlockedUpload($safeLabel, 'MIME asli tidak sesuai ekstensi (' . $realMime . ')');
                return 'Sistem Keamanan: Isi asli file "' . $safeLabel . '" (' . $realMime . ') tidak sesuai dengan format ekstensi .' . strtoupper($ext) . '.';
            }
        }

        if ($rawSample !== '' && preg_match('/<\?(?:php|=)|<\s*script\b|<\s*svg\b|\b(?:eval|shell_exec|passthru|proc_open|popen|assert)\s*\(/i', $rawSample)) {
            self::logBlockedUpload($safeLabel, 'Skrip berbahaya di dalam konten file');
            return 'Sistem Keamanan Anti-Deface: File "' . $safeLabel . '" ditolak karena terdeteksi mengandung skrip berbahaya.';
        }

        return null;
    }

    private static function detectMimeFromFile(string $realPath): string
    {
        if (!function_exists('finfo_open')) {
            return '';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }
        $mime = strtolower((string) finfo_file($finfo, $realPath));
        finfo_close($finfo);

        return $mime;
    }

    private static function detectMimeFromBytes(string $bytes): string
    {
        if (!function_exists('finfo_open')) {
            return '';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }
        $mime = strtolower((string) finfo_buffer($finfo, $bytes));
        finfo_close($finfo);

        return $mime;
    }

    private static function logBlockedUpload(string $fileLabel, string $reason): void
    {
        try {
            if (!class_exists(RiwayatAktivitas::class)) {
                return;
            }
            $riwayat = new RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            if (!Schema::hasTable($tbl)) {
                return;
            }
            $data = [];
            if (Schema::hasColumn($tbl, 'user_id')) {
                $data['user_id'] = Auth::check() ? Auth::id() : null;
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $data['aktivitas'] = 'Proteksi Anti-Deface: Memblokir unggahan file (' . substr($fileLabel, 0, 80) . ') - ' . $reason;
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $data['modul'] = 'Keamanan Upload File';
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $data['status'] = 'Peringatan';
            }
            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    }
}
