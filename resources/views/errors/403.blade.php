<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>403 - Akses Ditolak</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #fff;
            padding: 2.5rem 3rem;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
            max-width: 520px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 3rem;
            margin: 0;
            color: #dc3545;
        }

        h2 {
            margin-top: .5rem;
            font-size: 1.3rem;
        }

        p {
            margin-top: 1rem;
            color: #666;
            font-size: .95rem;
            line-height: 1.6;
        }

        a.btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: .55rem 1.25rem;
            background: #007bff;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-size: .9rem;
        }

        a.btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <h1>403</h1>
            <h2>Akses Ditolak</h2>

            <p>
                Anda tidak memiliki izin untuk mengakses dokumen ini.
                <br>
                Pastikan wilayah dan hak akses Anda sesuai dengan data peserta.
            </p>

            <a href="{{ url()->previous() }}" class="btn">
                Kembali
            </a>
        </div>
    </div>
</body>

</html>
