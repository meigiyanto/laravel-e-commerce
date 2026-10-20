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
            background: #101827;
            color: #f1f5f9;
            font-family: system-ui, -apple-system, BlinkMacSystemFont,
                         "Segoe UI", sans-serif;
        }
        .error-card {
            width: 100%;
            max-width: 560px;
            padding: 42px 28px;
            text-align: center;
            background: #182338;
            border: 1px solid #2b3951;
            border-radius: 22px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }
        .eyebrow {
            color: #93a4c1;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .error-code {
            margin: 18px 0 0;
            color: #8ba8ff;
            font-size: clamp(64px, 16vw, 100px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -5px;
        }
        h1 { margin: 22px 0 12px; font-size: 26px; }
        .message {
            margin: 0 auto;
            max-width: 410px;
            color: #b2bfd3;
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
            border: 1px solid #42516b;
            border-radius: 11px;
            color: #e2e8f0;
            background: #202e45;
            font-size: 14px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }
        .button-primary {
            color: #101827;
            border-color: #a8bcff;
            background: #a8bcff;
        }
        .footer {
            margin-top: 28px;
            color: #8392aa;
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
        <div class="eyebrow">Administration Panel</div>
        <p class="error-code">{{ $status }}</p>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        <div class="actions">
            <a class="button button-primary" href="/admin/dashboard">
                Kembali ke Dashboard
            </a>
            <button class="button" type="button" onclick="history.back()">
                Halaman Sebelumnya
            </button>
        </div>

        <p class="footer">Terjadi kendala saat memproses halaman ini.</p>
    </main>
</body>
</html>
