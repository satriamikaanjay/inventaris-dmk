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
        'zgrab',
        'ffuf',
        'wfuzz',
        'hydra',
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
        '/(?:^|\/)phpmyadmin/i',
        '/(?:^|\/)composer\.(?:json|lock)(?:$|\?)/i',
        '/eval-stdin\.php/i',
        '/(?:php|expect|phar|zip|glob|data):\/\//i',
    ];

    private const MALICIOUS_PAYLOAD_PATTERNS = [
        '/<\s*script\b/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/<\s*(?:iframe|object|embed|applet|meta|base|form|svg|marquee)\b/i',
        '/<\s*[^>]*\bon[a-z]{2,}\s*=/i',
        '/\bon(?:abort|afterprint|animation\w*|auxclick|before\w*|begin|blur|canplay\w*|cancel|change|click|close|contextmenu|copy|cuechange|cut|dblclick|drag\w*|durationchange|emptied|ended|error|focus\w*|formdata|hashchange|input|invalid|key\w*|load\w*|message\w*|mouse\w*|offline|online|page\w*|paste|pause|play\w*|pointer\w*|popstate|progress|ratechange|repeat|reset|resize|scroll\w*|search|securitypolicyviolation|seek\w*|select\w*|show|slotchange|stalled|submit|suspend|timeupdate|toggle|touch\w*|transition\w*|unload|volumechange|waiting|wheel)\s*=/i',
        '/<\?(?:php|=)/i',
        '/\b(?:eval|shell_exec|passthru|proc_open|popen|assert)\s*\(/i',
        '/\bunion\s+(?:all\s+)?select\b/i',
        '/\binformation_schema\b/i',
        '/\b(?:benchmark|sleep)\s*\(\s*\d+/i',
        '/\bwaitfor\s+delay\b/i',
        '/\b(?:into\s+(?:out|dump)file|load_file\s*\()/i',
        '/(?:\'|\")\s*(?:or|and)\s*(?:\'|\")?[a-z0-9_]+(?:\'|\")?\s*=\s*(?:\'|\")?[a-z0-9_]+/i',
        '/\border\s+by\s+\d+\s*(?:--|#|\/\*)/i',
        '/\bhaving\s+\d+\s*=\s*\d+/i',
        '/\bgroup_concat\s*\(/i',
        '/\bdrop\s+table\b/i',
        '/\btruncate\s+table\b/i',
        '/\bpg_sleep\s*\(/i',
        '/\b(?:extractvalue|updatexml)\s*\(/i',
        '/;\s*(?:select|insert|update|delete|drop|alter|create|exec)\b/i',
        '/\b0x[0-9a-f]{8,}\b/i',
    ];

    public function handle(Request $request, Closure $next, string $stage = 'all'): Response
    {
        $clientIp = (string) ($request->ip() ?: '0.0.0.0');

        if ($stage === 'edge' || $stage === 'all') {
            $banKey = 'sec_ban_ip|' . $clientIp;
            if (Cache::has($banKey)) {
                return $this->applySecurityHeaders($this->buildBanResponse($request), $request);
            }

            $isDevPoll = $request->is('dev-console*') && ($request->has('_poll_dev_rt') || $request->has('_poll_dev_act') || $request->is('dev-console/struktur-folder/*'));
            $burstLimit = $isDevPoll ? 300 : 150;
            $burstKey = 'sec_burst_ip|' . $clientIp;
            $hits = (int) Cache::get($burstKey, 0) + 1;
            Cache::put($burstKey, $hits, now()->addSeconds(10));
            if ($hits > $burstLimit) {
                Cache::put($banKey, 1, now()->addMinutes(15));
                $this->catatAncamanKeamanan($request, 'Memblokir IP ' . $clientIp . ' selama 15 menit karena DoS/DDoS burst (' . $hits . ' req/10 dtk)');

                $burstResponse = $this->isApiOrAjax($request)
                    ? response()->json(['success' => false, 'message' => 'Terlalu banyak permintaan (Anti-DoS aktif). IP Anda ditahan sementara.'], 429)
                    : response('Terlalu banyak permintaan (Anti-DoS aktif). IP Anda ditahan sementara.', 429);

                return $this->applySecurityHeaders($burstResponse, $request);
            }

            $userAgent = strtolower((string) $request->userAgent());
            foreach (self::BLOCKED_USER_AGENTS as $scanner) {
                if ($userAgent !== '' && str_contains($userAgent, $scanner)) {
                    $this->recordSecurityStrike($clientIp);
                    $this->catatAncamanKeamanan($request, 'Memblokir akses dari automated security scanner (' . $scanner . ')');
                    $uaResponse = $this->isApiOrAjax($request)
                        ? response()->json(['success' => false, 'message' => 'Akses Ditolak oleh Sistem Keamanan Perusahaan.'], 403)
                        : response('Akses Ditolak oleh Sistem Keamanan Perusahaan.', 403);

                    return $this->applySecurityHeaders($uaResponse, $request);
                }
            }

            $rawUri = (string) $request->getRequestUri();
            $decodedUri = rawurldecode($rawUri);
            foreach (self::BLOCKED_URI_PATTERNS as $uriPattern) {
                if (preg_match($uriPattern, $rawUri) || preg_match($uriPattern, $decodedUri)) {
                    $this->recordSecurityStrike($clientIp);
                    $this->catatAncamanKeamanan($request, 'Memblokir percobaan Path Traversal / Probe File Sensitif pada URL: ' . substr($decodedUri, 0, 100));
                    $uriResponse = $this->isApiOrAjax($request)
                        ? response()->json(['success' => false, 'message' => 'Akses Ditolak: Permintaan mengandung pola URL terlarang.'], 403)
                        : response('Akses Ditolak: Permintaan mengandung pola URL terlarang.', 403);

                    return $this->applySecurityHeaders($uriResponse, $request);
                }
            }
        }

        if ($stage === 'payload' || $stage === 'all') {
            $request->attributes->set('_sec_payload_checked', true);
            $ancamanDitemukan = $this->periksaPayloadRekursif($this->extractInputsForInspection($request));
            if ($ancamanDitemukan !== null) {
                $this->recordSecurityStrike($clientIp);
                $this->catatAncamanKeamanan($request, 'Memblokir percobaan serangan siber (' . $ancamanDitemukan . ') dari IP ' . $clientIp);

                return $this->applySecurityHeaders($this->buildThreatResponse($request), $request);
            }
        }

        $response = $next($request);

        if ($stage === 'edge' && !$request->attributes->get('_sec_payload_checked', false)) {
            $ancamanEdge = $this->periksaPayloadRekursif($this->extractInputsForInspection($request));
            if ($ancamanEdge !== null) {
                $this->recordSecurityStrike($clientIp);
                $this->catatAncamanKeamanan($request, 'Memblokir percobaan serangan siber pada rute tidak dikenal (' . $ancamanEdge . ') dari IP ' . $clientIp);

                return $this->applySecurityHeaders($this->buildThreatResponse($request, true), $request);
            }
        }

        if ($stage === 'edge' || $stage === 'all') {
            return $this->applySecurityHeaders($response, $request);
        }

        return $response;
    }

    private function applySecurityHeaders(Response $response, Request $request): Response
    {
        if (isset($response->headers) && method_exists($response->headers, 'set')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
            if (!$response->headers->has('Content-Security-Policy')) {
                $response->headers->set('Content-Security-Policy', "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; frame-src 'self' https://challenges.cloudflare.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none';");
            }
            if ($request->isSecure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');
            if ($request->is('dev-console*')) {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
            }
        }

        return $response;
    }

    private function isApiOrAjax(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->is('api/*') || $request->isJson();
    }

    private function buildBanResponse(Request $request): Response
    {
        if ($this->isApiOrAjax($request)) {
            return response()->json([
                'success' => false,
                'message' => 'IP Anda diblokir sementara karena aktivitas mencurigakan atau spam request.',
            ], 429);
        }

        return response('Akses Diblokir Sementara: Terdeteksi aktivitas berlebihan atau serangan dari IP Anda.', 429);
    }

    private function buildThreatResponse(Request $request, bool $forcePlain403 = false): Response
    {
        if ($this->isApiOrAjax($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan ditolak oleh sistem keamanan (terdeteksi karakter atau skrip berbahaya).',
            ], 403);
        }

        if (!$forcePlain403 && $request->hasSession()) {
            if ($request->is('login*')) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Sistem Keamanan: Terdeteksi input tidak valid atau percobaan injeksi pada form login.',
                ]);
            }

            if ($request->headers->has('referer')) {
                return redirect()->back()->withErrors([
                    'security' => 'Sistem Keamanan: Input ditolak karena mengandung tag skrip atau pola injeksi terlarang.',
                ]);
            }
        }

        return response('Akses Ditolak: Sistem Keamanan mendeteksi karakter, skrip, atau pola injeksi terlarang.', 403);
    }

    private function extractInputsForInspection(Request $request): array
    {
        $inputs = array_merge($request->query->all(), $request->request->all());

        $contentType = strtolower((string) $request->header('Content-Type', ''));
        $rawBody = (string) $request->getContent();
        if ($rawBody !== '' && ($request->isJson() || str_contains($contentType, 'application/json') || str_starts_with(ltrim($rawBody), '{') || str_starts_with(ltrim($rawBody), '['))) {
            $decodedJson = json_decode($rawBody, true);
            if (is_array($decodedJson)) {
                $inputs['_json_payload'] = $decodedJson;
            } else {
                $inputs['_raw_payload'] = $rawBody;
            }
        }

        $fileNames = $this->extractUploadedFilenames($request->allFiles());
        if (!empty($fileNames)) {
            $inputs['_uploaded_filenames'] = $fileNames;
        }

        return $inputs;
    }

    private function extractUploadedFilenames(array $files): array
    {
        $names = [];
        foreach ($files as $key => $item) {
            if (is_array($item)) {
                $names[$key] = $this->extractUploadedFilenames($item);
            } elseif ($item instanceof \Illuminate\Http\UploadedFile) {
                $names[$key] = (string) $item->getClientOriginalName();
            }
        }

        return $names;
    }

    private function recordSecurityStrike(string $ip): void
    {
        $strikeKey = 'sec_strike_ip|' . $ip;
        $strikes = (int) Cache::get($strikeKey, 0) + 1;
        Cache::put($strikeKey, $strikes, now()->addMinutes(5));
        if ($strikes >= 3) {
            Cache::put('sec_ban_ip|' . $ip, 1, now()->addMinutes(15));
        }
    }

    private function periksaPayloadRekursif(array $inputs, string $parentKey = ''): ?string
    {
        foreach ($inputs as $key => $value) {
            $keyStr = (string) $key;
            if ($keyStr === '_token' || $keyStr === 'password') {
                if ($keyStr === 'password' && is_string($value)) {
                    if (str_contains($value, "\0") || preg_match('/\bunion\s+(?:all\s+)?select\b/i', $value) || preg_match('/<\s*script\b/i', $value) || preg_match('/(?:\'|\")\s*or\s*(?:\'|\")?[a-z0-9_]+(?:\'|\")?\s*=\s*(?:\'|\")?[a-z0-9_]+/i', $value)) {
                        return 'SQL Injection / Script Injection pada kolom autentikasi';
                    }
                }
                continue;
            }

            if (in_array($keyStr, ['file_base64', 'lampiran_base64', 'foto_base64', 'image_base64'], true) && is_string($value)) {
                if (str_contains($value, "\0")) {
                    return 'Null-Byte Injection pada parameter ' . $keyStr;
                }
                if (preg_match('/^(?:data:image\/(?:png|jpe?g|gif|webp);base64,)?[A-Za-z0-9+\/\r\n]+=*$/i', trim($value))) {
                    continue;
                }
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
