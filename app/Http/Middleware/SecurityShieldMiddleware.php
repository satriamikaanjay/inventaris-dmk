<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    private const BLOCKED_REGISTRY_FILE = 'app/sec_blocked_ips.json';

    public function handle(Request $request, Closure $next, string $stage = 'all'): Response
    {
        $clientIp = (string) ($request->ip() ?: '0.0.0.0');
        $isDevConsole = $request->is('dev-console*');

        if ($stage === 'edge' || $stage === 'all') {
            $banKey = 'sec_ban_ip|' . $clientIp;
            if (!$isDevConsole && Cache::has($banKey)) {
                return $this->applySecurityHeaders($this->buildBanResponse($request), $request);
            }

            if (!$isDevConsole && !$this->isAutomaticPollingRequest($request)) {
                $burstLimit = 150;
                $windowBucket = (int) floor(time() / 10);
                $burstKey = 'sec_burst_ip|' . $clientIp . '|' . $windowBucket;
                $hits = (int) Cache::get($burstKey, 0) + 1;
                Cache::put($burstKey, $hits, now()->addSeconds(15));
                if ($hits > $burstLimit) {
                    $alasanBurst = 'DoS/DDoS burst (' . $hits . ' req/10 dtk)';
                    Cache::put($banKey, 1, now()->addMinutes(15));
                    $this->catatBlokirIp($request, $clientIp, $alasanBurst, 'blocked', 3);
                    $this->catatAncamanKeamanan($request, 'Memblokir IP ' . $clientIp . ' selama 15 menit karena ' . $alasanBurst);

                    $burstResponse = $this->isApiOrAjax($request)
                        ? response()->json(['success' => false, 'message' => 'Terlalu banyak permintaan (Anti-DoS aktif). IP Anda ditahan sementara.'], 429)
                        : response('Terlalu banyak permintaan (Anti-DoS aktif). IP Anda ditahan sementara.', 429);

                    return $this->applySecurityHeaders($burstResponse, $request);
                }
            }

            $userAgent = strtolower((string) $request->userAgent());
            foreach (self::BLOCKED_USER_AGENTS as $scanner) {
                if ($userAgent !== '' && str_contains($userAgent, $scanner)) {
                    $this->recordSecurityStrike($request, $clientIp, 'Automated security scanner (' . $scanner . ')');
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
                    $this->recordSecurityStrike($request, $clientIp, 'Path Traversal / Probe URL (' . substr($decodedUri, 0, 60) . ')');
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
                $this->recordSecurityStrike($request, $clientIp, $ancamanDitemukan);
                $this->catatAncamanKeamanan($request, 'Memblokir percobaan serangan siber (' . $ancamanDitemukan . ') dari IP ' . $clientIp);

                return $this->applySecurityHeaders($this->buildThreatResponse($request), $request);
            }
        }

        $response = $next($request);

        if ($stage === 'edge' && !$request->attributes->get('_sec_payload_checked', false)) {
            $ancamanEdge = $this->periksaPayloadRekursif($this->extractInputsForInspection($request));
            if ($ancamanEdge !== null) {
                $this->recordSecurityStrike($request, $clientIp, $ancamanEdge);
                $this->catatAncamanKeamanan($request, 'Memblokir percobaan serangan siber pada rute tidak dikenal (' . $ancamanEdge . ') dari IP ' . $clientIp);

                return $this->applySecurityHeaders($this->buildThreatResponse($request, true), $request);
            }
        }

        if ($stage === 'edge' || $stage === 'all') {
            return $this->applySecurityHeaders($response, $request);
        }

        return $response;
    }

    private function isAutomaticPollingRequest(Request $request): bool
    {
        return $request->is('cek-sesi', '_boost/*', 'up')
            || $request->has('_poll_pengajuan_rt')
            || $request->has('_poll_riwayat_realtime')
            || $request->has('_check_permissions_rt')
            || $request->has('_poll_dev_rt')
            || $request->has('_poll_dev_act')
            || $request->has('_poll_dev_struktur');
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

    private function recordSecurityStrike(Request $request, string $ip, string $reason = 'Pelanggaran Keamanan'): void
    {
        $strikeKey = 'sec_strike_ip|' . $ip;
        $strikes = (int) Cache::get($strikeKey, 0) + 1;
        Cache::put($strikeKey, $strikes, now()->addMinutes(5));
        if ($strikes >= 3) {
            Cache::put('sec_ban_ip|' . $ip, 1, now()->addMinutes(15));
            $this->catatBlokirIp($request, $ip, '3x Strike: ' . $reason, 'blocked', $strikes);
        } else {
            $this->catatBlokirIp($request, $ip, 'Strike ' . $strikes . '/3: ' . $reason, 'warning', $strikes);
        }
    }

    public static function parseDeviceName(?string $userAgent): string
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return 'Perangkat Tidak Dikenal';
        }

        $uaLower = strtolower($ua);
        foreach (self::BLOCKED_USER_AGENTS as $scanner) {
            if (str_contains($uaLower, $scanner)) {
                return 'Scanner / Bot (' . $scanner . ')';
            }
        }

        $os = 'Perangkat Lain';
        if (str_contains($uaLower, 'iphone')) {
            $os = 'iPhone (iOS)';
        } elseif (str_contains($uaLower, 'ipad')) {
            $os = 'iPad (iOS)';
        } elseif (str_contains($uaLower, 'android')) {
            if (preg_match('/android\s*[^;)]*;\s*([^;)]+)/i', $ua, $m) && !str_contains(strtolower($m[1]), 'wv')) {
                $model = trim((string) preg_replace('/\bbuild\/.*$/i', '', $m[1]));
                $os = $model !== '' ? 'Android (' . substr($model, 0, 35) . ')' : 'Android';
            } else {
                $os = 'Android';
            }
        } elseif (str_contains($uaLower, 'windows nt 10.0')) {
            $os = 'Windows 10/11 (PC)';
        } elseif (str_contains($uaLower, 'windows')) {
            $os = 'Windows (PC)';
        } elseif (str_contains($uaLower, 'macintosh') || str_contains($uaLower, 'mac os x')) {
            $os = 'macOS (Mac)';
        } elseif (str_contains($uaLower, 'cros')) {
            $os = 'Chromebook';
        } elseif (str_contains($uaLower, 'linux')) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (str_contains($uaLower, 'okhttp') || str_contains($uaLower, 'dalvik') || str_contains($uaLower, 'dart')) {
            $browser = 'Aplikasi Mobile APK';
        } elseif (str_contains($uaLower, 'postman')) {
            $browser = 'Postman';
        } elseif (str_contains($uaLower, 'curl/')) {
            $browser = 'cURL / CLI';
        } elseif (str_contains($uaLower, 'edg/') || str_contains($uaLower, 'edge/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($uaLower, 'opr/') || str_contains($uaLower, 'opera')) {
            $browser = 'Opera';
        } elseif (str_contains($uaLower, 'chrome/') || str_contains($uaLower, 'crios/')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($uaLower, 'firefox/') || str_contains($uaLower, 'fxios/')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($uaLower, 'safari/')) {
            $browser = 'Safari';
        }

        return $browser . ' • ' . $os;
    }

    private function catatBlokirIp(Request $request, string $ip, string $reason, string $status = 'blocked', int $strikes = 1): void
    {
        try {
            $path = storage_path(self::BLOCKED_REGISTRY_FILE);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $list = self::bacaFileRegistry($path);
            $now = time();
            $ua = substr((string) ($request->userAgent() ?: ''), 0, 220);
            $deviceName = self::parseDeviceName($ua);
            $reqPath = '/' . ltrim((string) $request->path(), '/');

            $existingIdx = null;
            foreach ($list as $idx => $item) {
                if (($item['ip'] ?? '') === $ip) {
                    $existingIdx = $idx;
                    break;
                }
            }

            $entry = [
                'ip'           => $ip,
                'device_name'  => $deviceName,
                'user_agent'   => $ua !== '' ? $ua : '-',
                'reason'       => substr($reason, 0, 180),
                'path'         => substr($reqPath, 0, 120),
                'status'       => $status,
                'strikes'      => $strikes,
                'blocked_at'   => date('Y-m-d H:i:s', $now),
                'blocked_ts'   => $now,
                'expires_ts'   => $status === 'blocked' ? ($now + 900) : ($now + 300),
                'expires_at'   => date('Y-m-d H:i:s', $status === 'blocked' ? ($now + 900) : ($now + 300)),
                'unblocked_at' => null,
                'unblocked_by' => null,
            ];

            if ($existingIdx !== null) {
                unset($list[$existingIdx]);
            }
            array_unshift($list, $entry);
            $list = array_slice(array_values($list), 0, 80);

            @file_put_contents($path, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        } catch (\Throwable $e) {
        }
    }

    public static function getBlockedRegistry(): array
    {
        $path = storage_path(self::BLOCKED_REGISTRY_FILE);
        $list = self::bacaFileRegistry($path);
        $now = time();
        $changed = false;
        $knownBlockedIps = [];

        foreach ($list as $idx => &$item) {
            $ip = (string) ($item['ip'] ?? '');
            if ($ip === '') {
                unset($list[$idx]);
                $changed = true;
                continue;
            }

            $status = (string) ($item['status'] ?? 'blocked');
            $expTs = (int) ($item['expires_ts'] ?? 0);
            $cacheBlocked = Cache::has('sec_ban_ip|' . $ip);

            if ($status === 'blocked') {
                if ($cacheBlocked) {
                    $knownBlockedIps[$ip] = true;
                    $item['remaining_sec'] = max(0, $expTs - $now);
                } else {
                    $item['status'] = 'expired';
                    $item['remaining_sec'] = 0;
                    $changed = true;
                }
            } elseif ($status === 'warning') {
                if ($cacheBlocked) {
                    $item['status'] = 'blocked';
                    $knownBlockedIps[$ip] = true;
                    $item['remaining_sec'] = max(0, $expTs - $now);
                    $changed = true;
                } elseif ($expTs > 0 && $now >= $expTs) {
                    $item['status'] = 'expired';
                    $item['remaining_sec'] = 0;
                    $changed = true;
                } else {
                    $item['remaining_sec'] = max(0, $expTs - $now);
                }
            } else {
                $item['remaining_sec'] = 0;
            }
        }
        unset($item);

        // Sertakan juga IP terblokir yang sudah tersimpan di cache database sebelum registry dicatat
        try {
            $cacheTable = (string) config('cache.stores.database.table', 'cache');
            if (config('cache.default') === 'database' && Schema::hasTable($cacheTable)) {
                $rows = DB::table($cacheTable)
                    ->where('key', 'like', '%sec_ban_ip|%')
                    ->where('expiration', '>', $now)
                    ->get(['key', 'expiration']);

                foreach ($rows as $row) {
                    $rawKey = (string) $row->key;
                    $pos = strrpos($rawKey, 'sec_ban_ip|');
                    if ($pos === false) {
                        continue;
                    }
                    $ipFromCache = substr($rawKey, $pos + 11);
                    if ($ipFromCache === '' || isset($knownBlockedIps[$ipFromCache])) {
                        continue;
                    }

                    $knownBlockedIps[$ipFromCache] = true;
                    $expCache = (int) $row->expiration;
                    $entryCache = [
                        'ip'            => $ipFromCache,
                        'device_name'   => 'Perangkat / Browser (Blokir Cache Aktif)',
                        'user_agent'    => '-',
                        'reason'        => 'Terblokir oleh Proteksi WAF / Anti-DoS',
                        'path'          => '-',
                        'status'        => 'blocked',
                        'strikes'       => 3,
                        'blocked_at'    => date('Y-m-d H:i:s', max(0, $expCache - 900)),
                        'blocked_ts'    => max(0, $expCache - 900),
                        'expires_ts'    => $expCache,
                        'expires_at'    => date('Y-m-d H:i:s', $expCache),
                        'remaining_sec' => max(0, $expCache - $now),
                        'unblocked_at'  => null,
                        'unblocked_by'  => null,
                    ];

                    $replaced = false;
                    foreach ($list as $i => $it) {
                        if (($it['ip'] ?? '') === $ipFromCache) {
                            $list[$i] = $entryCache;
                            $replaced = true;
                            break;
                        }
                    }
                    if (!$replaced) {
                        array_unshift($list, $entryCache);
                    }
                    $changed = true;
                }
            }
        } catch (\Throwable $e) {
        }

        // Buang riwayat lama (> 24 jam) yang sudah tidak aktif
        $list = array_values(array_filter($list, function ($item) use ($now) {
            if (($item['status'] ?? '') === 'blocked') {
                return true;
            }
            $ts = (int) ($item['blocked_ts'] ?? 0);
            return $ts === 0 || ($now - $ts) <= 86400;
        }));

        usort($list, function ($a, $b) {
            $order = ['blocked' => 0, 'warning' => 1, 'unblocked' => 2, 'expired' => 3];
            $oa = $order[$a['status'] ?? 'expired'] ?? 9;
            $ob = $order[$b['status'] ?? 'expired'] ?? 9;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            return ((int) ($b['blocked_ts'] ?? 0)) <=> ((int) ($a['blocked_ts'] ?? 0));
        });

        if ($changed) {
            @file_put_contents($path, json_encode(array_slice($list, 0, 80), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        return $list;
    }

    public static function unblockIp(string $ip, string $actor = 'Developer'): bool
    {
        $cleanIp = trim($ip);
        if ($cleanIp === '') {
            return false;
        }

        Cache::forget('sec_ban_ip|' . $cleanIp);
        Cache::forget('sec_strike_ip|' . $cleanIp);
        Cache::forget('sec_burst_ip|' . $cleanIp);
        $bucket = (int) floor(time() / 10);
        Cache::forget('sec_burst_ip|' . $cleanIp . '|' . $bucket);
        Cache::forget('sec_burst_ip|' . $cleanIp . '|' . ($bucket - 1));

        $path = storage_path(self::BLOCKED_REGISTRY_FILE);
        $list = self::bacaFileRegistry($path);
        $found = false;

        foreach ($list as &$item) {
            if (($item['ip'] ?? '') === $cleanIp) {
                $item['status'] = 'unblocked';
                $item['remaining_sec'] = 0;
                $item['unblocked_at'] = date('Y-m-d H:i:s');
                $item['unblocked_by'] = $actor;
                $found = true;
            }
        }
        unset($item);

        if (!$found) {
            array_unshift($list, [
                'ip'            => $cleanIp,
                'device_name'   => 'Perangkat / Browser',
                'user_agent'    => '-',
                'reason'        => 'Blokir dibuka manual oleh Developer',
                'path'          => '-',
                'status'        => 'unblocked',
                'strikes'       => 0,
                'blocked_at'    => date('Y-m-d H:i:s'),
                'blocked_ts'    => time(),
                'expires_ts'    => time(),
                'expires_at'    => date('Y-m-d H:i:s'),
                'remaining_sec' => 0,
                'unblocked_at'  => date('Y-m-d H:i:s'),
                'unblocked_by'  => $actor,
            ]);
        }

        @file_put_contents($path, json_encode(array_slice(array_values($list), 0, 80), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return true;
    }

    public static function unblockAll(string $actor = 'Developer'): int
    {
        $registry = self::getBlockedRegistry();
        $count = 0;

        foreach ($registry as $item) {
            $ip = (string) ($item['ip'] ?? '');
            if ($ip !== '' && in_array($item['status'] ?? '', ['blocked', 'warning'], true)) {
                self::unblockIp($ip, $actor);
                $count++;
            }
        }

        return $count;
    }

    private static function bacaFileRegistry(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
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
