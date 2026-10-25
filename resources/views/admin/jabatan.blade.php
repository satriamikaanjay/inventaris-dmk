<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f8fafc; display: flex; height: 100vh; overflow: hidden; }
        
        .content-container {
            margin-left: 270px;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        h1 { color: #1a4f91; font-size: 28px; margin-bottom: 10px; }
        p { color: #64748b; font-size: 15px; margin-bottom: 25px; line-height: 1.5; }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #1a4f91;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-back:hover { background: #12386a; }
    </style>
</head>
<body>
    <!-- Memanggil sidebar yang sudah dipisah -->
    @include('layouts.adminsidebar')

    <div class="content-container">
        <div class="card">
            <h1>Jabatan 🚀</h1>
            <p>Halaman ini sedang dalam tahap pengembangan.</p>
            <a href="{{ route('admin.dashboard') }}" class="btn-back">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>