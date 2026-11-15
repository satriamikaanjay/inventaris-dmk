<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Sedang Maintenance — Inventaris DMK</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #1e293b 0%, #0b1120 65%);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 540px;
            width: 100%;
            background: rgba(15, 23, 42, 0.92);
            border: 1px solid #334155;
            border-top: 4px solid #ef4444;
            border-radius: 18px;
            padding: 36px 32px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
        }
        .logo {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: #fff;
            padding: 6px;
            object-fit: contain;
            margin-bottom: 18px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.16);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
        }
        h1 {
            font-size: 24px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 12px;
        }
        p {
            font-size: 14.5px;
            line-height: 1.65;
            color: #94a3b8;
            margin-bottom: 20px;
        }
        .meta {
            display: inline-block;
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 12px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('logo.png') }}" alt="Logo" class="logo">
        <div>
            <span class="pill"><span class="dot"></span> Mode Maintenance Darurat</span>
        </div>
        <h1>Web Sedang Maintenance</h1>
        <p>
            Sistem Inventaris DMK sedang dikunci sementara untuk pemeliharaan dan pengamanan sistem.
            Seluruh akses pengguna ditutup sementara. Silakan coba kembali beberapa saat lagi.
        </p>
        @if (!empty($lockInfo['locked_at']))
            <div class="meta">
                Status aktif sejak: <strong>{{ $lockInfo['locked_at'] }}</strong>
            </div>
        @endif
    </div>
</body>
</html>
