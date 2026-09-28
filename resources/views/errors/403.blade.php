<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fb;
            color: #1f2937;
        }
        .card {
            text-align: center;
            max-width: 420px;
            padding: 40px;
        }
        .code {
            font-size: 72px;
            font-weight: 700;
            color: #6366f1;
            line-height: 1;
            margin-bottom: 12px;
        }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { color: #6b7280; font-size: 14px; margin: 0 0 28px; line-height: 1.6; }
        a.btn {
            display: inline-block;
            padding: 10px 24px;
            background: #6366f1;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        a.btn:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">403</div>
        <h1>Kamu tidak punya akses ke halaman ini</h1>
        <p>Hubungi admin toko kalau kamu merasa seharusnya bisa membuka halaman ini.</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>