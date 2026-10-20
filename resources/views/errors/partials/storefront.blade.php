<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} — {{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f7fb;
            color: #172033;
            font-family: system-ui, -apple-system, BlinkMacSystemFont,
                         "Segoe UI", sans-serif;
        }
        .error-card {
            width: 100%;
            max-width: 560px;
            padding: 42px 28px;
            text-align: center;
            background: #fff;
            border: 1px solid #e5e9f2;
            border-radius: 24px;
            box-shadow: 0 18px 55px rgba(24, 39, 75, .08);
        }
        .error-code {
            margin: 0;
            color: #3156d9;
            font-size: clamp(64px, 16vw, 100px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -5px;
        }
        h1 { margin: 22px 0 12px; font-size: 26px; }
        .message {
            margin: 0 auto;
            max-width: 410px;
            color: #667085;
            line-height: 1.8;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border: 1px solid #d8deea;
            border-radius: 12px;
            color: #344054;
            background: #fff;
            font-size: 14px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }
        .button-primary {
            color: #fff;
            border-color: #3156d9;
            background: #3156d9;
        }
        .footer {
            margin-top: 28px;
            color: #98a2b3;
            font-size: 12px;
        }
        @media (max-width: 420px) {
            .error-card { padding: 34px 20px; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="error-card">
        <p class="error-code">{{ $status }}</p>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        <div class="actions">
            <a class="button button-primary" href="/">Kembali ke Beranda</a>
            <button class="button" type="button" onclick="history.back()">
                Halaman Sebelumnya
            </button>
        </div>

        <p class="footer">Silakan coba kembali atau lanjutkan menjelajahi toko.</p>
    </main>
</body>
</html>
