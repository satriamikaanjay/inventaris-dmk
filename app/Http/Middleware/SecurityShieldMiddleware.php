<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SecurityShieldMiddleware
{
    private const BLOCKED_USER_AGENTS = [
        'sqlmap',
        'nikto',
        'acunetix',
        'nessus',
        'openvas',
        'nmap',
        'masscan',
        'dirbuster',
        'gobuster',
        'feroxbuster',
        'wpscan',
        'nuclei',
        'havij',
        'zmeu',
        'morfeus',
        'commix',
        'xsser',
        'burpcollaborator',
        'netsparker',
        'appscan',
    ];

    private const BLOCKED_URI_PATTERNS = [
        '/\.\.\//i',
        '/\.\.\\\\/i',
        '/%2e%2e/i',
        '/%00/i',
        '/\x00/',
        '/(?:^|\/)\.env(?:\.|$|\/)/i',
        '/(?:^|\/)\.git(?:\.|$|\/)/i',
        '/(?:^|\/)\.htaccess(?:\.|$|\/)/i',
        '/(?:^|\/)wp-admin(?:\.|$|\/)/i',
        '/(?:^|\/)wp-login\.php/i',
        '/(?:^|\/)xmlrpc\.php/i',
        '/(?:^|\/)vendor\/phpunit/i',
        '/eval-stdin\.php/i',
        '/(?:php|expect|phar|zip|glob|data):\/\//i',
    ];

    private const MALICIOUS_PAYLOAD_PATTERNS = [
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/<\s*(?:iframe|object|embed|applet|meta|base|form)\b/i',
        '/\bon(?:error|load|mouseover|focus|blur|click|dblclick|keydown|keyup|submit|toggle|animationstart)\s*=/i',
        '/<\?(?:php|=)/i',
        '/\b(?:eval|shell_exec|passthru|proc_open|popen|assert)\s*\(/i',
        '/\bunion\s+(?:all\s+)?select\b/i',
        '/\binformation_schema\b/i',
        '/\b(?:benchmark|sleep)\s*\(\s*\d+/i',
        '/\bwaitfor\s+delay\b/i',
        '/\b(?:into\s+(?:out|dump)file|load_file\s*\()/i',
        '/(?:\'|\")\s*(?:or|and)\s*(?:\'|\")?\d+(?:\'|\")?\s*=\s*(?:\'|\")?\d+/i',
        '/\bdrop\s+table\b/i',
        '/\btruncate\s+table\b/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = strtolower((string) $request->userAgent());
        foreach (self::BLOCKED_USER_AGENTS as $scanner) {
            if ($userAgent !== '' && str_contains($userAgent, $scanner)) {
                $this->catatAncamanKeamanan($request, 'Memblokir akses dari automated security scanner (' . $scanner . ')');
                return response('Akses Ditolak oleh Sistem Keamanan Perusahaan.', 403);
            }
        }

        $rawUri = (string) $request->getRequestUri();
        $decodedUri = rawurldecode($rawUri);
        foreach (self::BLOCKED_URI_PATTERNS as $uriPattern) {
            if (preg_match($uriPattern, $rawUri) || preg_match($uriPattern, $decodedUri)) {
                $this->catatAncamanKeamanan($request, 'Memblokir percobaan Path Traversal / Probe File Sensitif pada URL: ' . substr($decodedUri, 0, 100));
                return response('Akses Ditolak: Permintaan mengandung pola URL terlarang.', 403);
            }
        }

        $allInputs = array_merge($request->query->all(), $request->request->all());
        $ancamanDitemukan = $this->periksaPayloadRekursif($allInputs);
        if ($ancamanDitemukan !== null) {
            $this->catatAncamanKeamanan($request, 'Memblokir percobaan serangan siber (' . $ancamanDitemukan . ') dari IP ' . $request->ip());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan ditolak oleh sistem keamanan (terdeteksi karakter atau skrip berbahaya).',
                ], 403);
            }

            if ($request->is('login*')) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Sistem Keamanan: Terdeteksi input tidak valid atau percobaan injeksi pada form login.',
                ]);
            }

            return redirect()->back()->withErrors([
                'security' => 'Sistem Keamanan: Input ditolak karena mengandung tag skrip atau pola injeksi terlarang.',
            ]);
        }

        $response = $next($request);

        if (method_exists($response, 'headers') && isset($response->headers)) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
            $response->headers->set('Content-Security-Policy', "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none';");
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');
        }

        return $response;
    }

    private function periksaPayloadRekursif(array $inputs, string $parentKey = ''): ?string
    {
        foreach ($inputs as $key => $value) {
            $keyStr = (string) $key;
            if ($keyStr === '_token' || $keyStr === 'password') {
                if ($keyStr === 'password' && is_string($value)) {
                    if (str_contains($value, "\0") || preg_match('/\bunion\s+(?:all\s+)?select\b/i', $value) || preg_match('/<\s*script\b/i', $value) || preg_match('/(?:\'|\")\s*or\s*(?:\'|\")?1(?:\'|\")?\s*=\s*(?:\'|\")?1/i', $value)) {
                        return 'SQL Injection / Script Injection pada kolom autentikasi';
                    }
                }
                continue;
            }

            if (is_array($value)) {
                $hasil = $this->periksaPayloadRekursif($value, $keyStr);
                if ($hasil !== null) {
                    return $hasil;
                }
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            if (str_contains($value, "\0")) {
                return 'Null-Byte Injection pada parameter ' . $keyStr;
            }

            $decodedVal = html_entity_decode(rawurldecode($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            foreach (self::MALICIOUS_PAYLOAD_PATTERNS as $pattern) {
                if (preg_match($pattern, $value) || preg_match($pattern, $decodedVal)) {
                    return 'Injeksi Skrip / SQL / Deface pada parameter ' . ($parentKey !== '' ? $parentKey . '.' . $keyStr : $keyStr);
                }
            }
        }

        return null;
    }

    private function catatAncamanKeamanan(Request $request, string $pesan): void
    {
        try {
            $cacheKey = 'sec_log_' . md5($request->ip() . '|' . $pesan);
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, 1, now()->addSeconds(30));

            if (!class_exists(\App\Models\RiwayatAktivitas::class)) {
                return;
            }

            $riwayat = new \App\Models\RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            if (!Schema::hasTable($tbl)) {
                return;
            }

            $data = [];
            if (Schema::hasColumn($tbl, 'user_id')) {
                $data['user_id'] = Auth::check() ? Auth::id() : null;
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $data['aktivitas'] = 'Proteksi Siber: ' . substr($pesan, 0, 220);
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $data['modul'] = 'Keamanan Sistem (WAF)';
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $data['status'] = 'Peringatan';
            }

            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    }
}
