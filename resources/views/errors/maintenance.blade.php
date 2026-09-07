<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang Maintenis - MTQ Pesisir Selatan</title>
    <style>
        /* Pattern Background Islami Modern & Flat */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #047857; /* Emerald Green */
            background-image: 
                linear-gradient(30deg, #065f46 12%, transparent 12.5%, transparent 87%, #065f46 87.5%, #065f46),
                linear-gradient(150deg, #065f46 12%, transparent 12.5%, transparent 87%, #065f46 87.5%, #065f46),
                linear-gradient(30deg, #065f46 12%, transparent 12.5%, transparent 87%, #065f46 87.5%, #065f46),
                linear-gradient(150deg, #065f46 12%, transparent 12.5%, transparent 87%, #065f46 87.5%, #065f46),
                linear-gradient(60deg, #065f4677 25%, transparent 25.5%, transparent 75%, #065f4677 75%, #065f4677), 
                linear-gradient(60deg, #065f4677 25%, transparent 25.5%, transparent 75%, #065f4677 75%, #065f4677);
            background-size: 80px 140px;
            background-position: 0 0, 0 0, 40px 70px, 40px 70px, 0 0, 40px 70px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: #334155;
        }

        /* Card Container Minimalis */
        .container {
            background: #ffffff;
            max-width: 500px;
            width: 90%;
            padding: 50px 30px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        /* Animasi CSS Pak Haji (Flat 2D) */
        .pak-haji-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px auto;
            animation: floatHaji 3s ease-in-out infinite;
        }

        @keyframes floatHaji {
            0% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0); }
        }

        .wajah {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            background-color: #fcd34d; /* Warna kulit flat */
            border-radius: 50%;
            z-index: 2;
        }

        .kopiah {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 84px;
            height: 45px;
            background-color: #1e293b; /* Hitam peci */
            border-radius: 40px 40px 5px 5px;
            z-index: 3;
        }

        .kacamata {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 20px;
            display: flex;
            justify-content: space-between;
            z-index: 4;
        }

        .lensa {
            width: 28px;
            height: 28px;
            border: 3px solid #334155;
            border-radius: 50%;
            background: rgba(255,255,255,0.8);
        }

        .gagang {
            position: absolute;
            top: 13px;
            left: 28px;
            width: 14px;
            height: 3px;
            background-color: #334155;
        }

        .kumis {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 15px;
            background-color: #ffffff;
            border-radius: 20px 20px 0 0;
            z-index: 4;
        }

        /* Tipografi */
        h1 {
            font-size: 2rem;
            color: #0f172a;
            margin: 0 0 10px 0;
            font-weight: 800;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 25px;
        }

        .badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #047857;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #a7f3d0;
        }

        .footer {
            margin-top: 30px;
            font-size: 0.85rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="pak-haji-wrapper">
            <div class="wajah">
                <div class="kopiah"></div>
                <div class="kacamata">
                    <div class="lensa"></div>
                    <div class="gagang"></div>
                    <div class="lensa"></div>
                </div>
                <div class="kumis"></div>
            </div>
        </div>

        <h1>Web Sedang Maintenis 🛠️</h1>
        
        <p>
            Mohon bersabar, website <strong>MTQ Pesisir Selatan</strong> sedang dalam perbaikan sementara. Nanti pasti akan dihidupkan kembali kok kalau sudah rapi.
        </p>

        <div class="badge">
            Pak Haji lagi beres-beres server... ☕
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} MTQN Pesisir Selatan. 
        </div>
    </div>

</body>
</html>