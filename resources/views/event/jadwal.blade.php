<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Jadwal Penampilan - {{ $event->event_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Pola geometris Islami (bintang segi delapan) sebagai background berulang,
           dibuat sebagai SVG data-uri supaya tetap satu file, tanpa perlu asset tambahan. */
        .islamic-pattern {
            background-color: #15803d;
            /* green-700 */
            background-image:
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.05) 0, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='0.08' stroke-width='1.5'%3E%3Cpath d='M40 4 L60 20 L60 60 L40 76 L20 60 L20 20 Z'/%3E%3Cpath d='M40 4 L20 20 L60 20 Z'/%3E%3Cpath d='M40 76 L20 60 L60 60 Z'/%3E%3Ccircle cx='40' cy='40' r='14'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 24px 24px, 80px 80px;
        }

        .logo-slot {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .logo-slot img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body class="min-h-screen islamic-pattern flex items-start justify-center py-10 px-4">

    {{-- ➕ PERBESAR: max-w-md -> max-w-xl --}}
    <div class="w-full max-w-xl">

        {{-- ===============================
             HEADER LOGO (4 KOLOM)
             logo_event, logo_sponsor_1, logo_sponsor_2, logo_sponsor_3
             =============================== --}}
        <div class="flex justify-center items-center gap-3 mb-6">

            @php
                $logos = [$event->logo_event, $event->logo_sponsor_1, $event->logo_sponsor_2, $event->logo_sponsor_3];
            @endphp

            @foreach ($logos as $logoPath)
                {{-- ➕ PERBESAR: w-14 h-14 -> w-16 h-16 --}}
                <div class="w-16 h-16 bg-white rounded-xl shadow-md logo-slot p-1.5">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="Logo">
                    @else
                        {{-- Fallback kalau logo belum diupload --}}
                        <span class="text-green-700 text-2xl">🕌</span>
                    @endif
                </div>
            @endforeach

        </div>

        {{-- CARD --}}
        <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl overflow-hidden">

            {{-- HEADER EVENT --}}
            <div class="bg-green-700 text-white px-6 py-6 text-center">
                {{-- ➕ PERBESAR: text-lg -> text-2xl --}}
                <h1 class="text-2xl font-bold leading-snug">
                    {{ $event->event_name }}
                </h1>
                {{-- ➕ PERBESAR: text-xs -> text-sm --}}
                <p class="text-sm text-green-100 mt-1">
                    Tahun {{ $event->event_year }}
                    @if ($event->event_location)
                        &bull; {{ $event->event_location }}
                    @endif
                </p>
                @if ($event->event_tagline)
                    {{-- ➕ PERBESAR: text-[11px] -> text-xs --}}
                    <p class="text-xs italic text-green-200 mt-1">
                        "{{ $event->event_tagline }}"
                    </p>
                @endif
            </div>

            <div class="p-6">

                <div class="text-center mb-5">
                    {{-- ➕ PERBESAR: text-xl -> text-2xl --}}
                    <h2 class="text-2xl font-bold text-green-700 flex items-center justify-center gap-2">
                        📅 Jadwal Penampilan
                    </h2>
                </div>

                {{-- LIST JADWAL --}}
                <div class="space-y-4">
                    @php
                        $colors = ['bg-green-600', 'bg-blue-600', 'bg-orange-500', 'bg-teal-600', 'bg-emerald-600'];
                    @endphp

                    @forelse ($locations as $index => $row)
                        @php
                            $color = $colors[$index % count($colors)];

                            // Majelis & cabang sudah terurut & unik dari backend (1 s.d. 12)
                            $majelisText = $row['majelis']->join(' dan ');

                            $number = $index + 1;
                        @endphp

                        <div class="{{ $color }} text-white rounded-xl px-5 py-4 shadow-md">
                            <div class="flex items-start gap-3">
                                {{-- ➕ PERBESAR: text-sm -> text-base --}}
                                <span class="font-bold text-base shrink-0">{{ $number }}.</span>
                                <div class="flex-1">
                                    {{-- ➕ PERBESAR: text-sm -> text-base --}}
                                    <div class="font-semibold text-base">
                                        {{ $row['location_name'] }}
                                    </div>
                                    @if ($majelisText)
                                        {{-- ➕ PERBESAR: text-xs -> text-sm --}}
                                        <div class="text-sm opacity-90 mt-0.5">
                                            {{ $majelisText }}
                                        </div>
                                    @endif

                                    {{-- ➕ CABANG LOMBA: dipisah per baris + penomoran, bukan digabung koma --}}
                                    @if ($row['cabang']->isNotEmpty())
                                        <ol class="mt-2 space-y-0.5 text-sm opacity-90">
                                            @foreach ($row['cabang'] as $i => $cabang)
                                                <li>{{ $i + 1 }}. {{ $cabang }}</li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-sm text-gray-400 py-6">
                            Belum ada jadwal lokasi untuk event ini.
                        </div>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- ➕ PERBESAR: text-[11px] -> text-xs --}}
        <p class="text-center text-green-100 text-xs mt-5">
            &copy; {{ $event->event_year }} {{ $event->app_name ?? $event->event_name }}
        </p>

    </div>

</body>

</html>
