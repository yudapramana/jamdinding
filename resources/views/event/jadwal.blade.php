<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Jadwal Penampilan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-green-700 flex items-start justify-center py-12">

    <div class="w-full max-w-md">

        {{-- LOGO HEADER (OPSIONAL) --}}
        <div class="flex justify-center gap-3 mb-6">
            <div class="w-10 h-10 bg-white rounded-xl shadow"></div>
            <div class="w-10 h-10 bg-white rounded-xl shadow"></div>
            <div class="w-10 h-10 bg-white rounded-xl shadow"></div>
            <div class="w-10 h-10 bg-white rounded-xl shadow"></div>
            <div class="w-10 h-10 bg-white rounded-xl shadow"></div>
        </div>

        {{-- CARD --}}
        <div class="bg-white rounded-2xl shadow-xl p-6">

            {{-- TITLE --}}
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-green-700 flex items-center justify-center gap-2">
                    📅 Jadwal Penampilan
                </h1>
            </div>

            {{-- LIST JADWAL --}}
            <div class="space-y-4">
                @php
                    $colors = ['bg-green-500', 'bg-blue-500', 'bg-orange-500', 'bg-green-500', 'bg-blue-500'];
                @endphp

                @foreach ($locations as $index => $row)
                    @php
                        // Ambil angka majelis saja (02, 15, dst)
                        $numbers = $row['majelis']->map(fn($m) => preg_replace('/\D+/', '', $m))->filter()->map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT))->unique()->sort()->values();

                        // Final text: Majelis 02 dan 15
                        $majelisText = 'Majelis ' . $numbers->join(' dan ');
                    @endphp

                    @php
                        $color = $colors[$index % count($colors)];
                        $majelisText = $row['majelis']->join(' dan ');
                        $cabangText = $row['cabang']->join(', ');

                        $number = $index + 1;
                    @endphp



                    <div class="{{ $color }} text-white rounded-xl px-4 py-3 shadow-md">
                        <div class="text-center font-semibold text-sm">
                            {{ $number }}. {{ $majelisText }}
                            {{-- Jadwal Penampilan --}}
                        </div>
                        <div class="text-center text-xs opacity-90 mt-1">
                            ({{ $cabangText }})
                        </div>
                    </div>
                @endforeach
            </div>


        </div>

    </div>

</body>

</html>
