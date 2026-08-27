<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lokasi Lomba - {{ $event->event_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        html {
            font-size: 17px;
        }

        body {
            background-color: #f4f6f4;
        }

        .islamic-pattern {
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%2315803d' stroke-opacity='0.05' stroke-width='1.5'%3E%3Cpath d='M40 4 L60 20 L60 60 L40 76 L20 60 L20 20 Z'/%3E%3Cpath d='M40 4 L20 20 L60 20 Z'/%3E%3Cpath d='M40 76 L20 60 L60 60 Z'/%3E%3Ccircle cx='40' cy='40' r='14'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 80px 80px;
        }

        .header-pattern {
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='56' viewBox='0 0 56 56'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='0.12' stroke-width='1'%3E%3Cpath d='M28 2 L42 14 L42 42 L28 54 L14 42 L14 14 Z'/%3E%3Cpath d='M28 2 L14 14 L42 14 Z'/%3E%3Cpath d='M28 54 L14 42 L42 42 Z'/%3E%3Ccircle cx='28' cy='28' r='9'/%3E%3C/g%3E%3C/svg%3E"),
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.06), transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(255, 255, 255, 0.05), transparent 40%);
            background-size: 56px 56px, auto, auto;
        }

        .header-arch-divider {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            width: 100%;
            height: 28px;
            line-height: 0;
        }

        .header-arch-divider svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .gold-divider {
            width: 64px;
            height: 3px;
            margin: 10px auto 0;
            border-radius: 999px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
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

        .search-sticky {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .venue-banner {
            aspect-ratio: 16 / 9;
            width: 100%;
            object-fit: cover;
        }

        .venue-banner-fallback {
            aspect-ratio: 16 / 9;
            width: 100%;
        }

        .btn-primary-action {
            min-height: 48px;
        }

        /* ➕ Baris cabang lomba di dalam card venue */
        .cabang-row+.cabang-row {
            border-top: 1px dashed #e5e7eb;
        }

        #qrModal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 40, 25, 0.65);
            backdrop-filter: blur(2px);
            padding: 1.25rem;
        }

        #qrModal.open {
            display: flex;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .venue-card {
            animation: fadeInUp 0.25s ease-out both;
        }
    </style>
</head>

<body class="min-h-screen">

    {{-- ===============================
         HEADER
         =============================== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-green-900 via-green-800 to-green-700 text-white header-pattern">

        <div class="relative max-w-4xl mx-auto px-4 pt-5 pb-9">

            <div class="flex flex-wrap justify-center items-center gap-2 sm:gap-3 mb-4">
                @php
                    $logos = [$event->logo_event, $event->logo_sponsor_1, $event->logo_sponsor_2, $event->logo_sponsor_3];
                @endphp

                @foreach ($logos as $logoPath)
                    <div class="w-11 h-11 sm:w-13 sm:h-13 bg-white/95 rounded-xl shadow-md logo-slot p-1.5">
                        @if ($logoPath)
                            <img src="{{ $logoPath }}" alt="Logo">
                        @else
                            <span class="text-green-700 text-lg">🕌</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="text-center px-2">
                <div class="flex items-center justify-center gap-2 text-amber-300 text-xs sm:text-sm tracking-widest font-medium mb-1">
                    <span>✦</span>
                    <span>MUSABAQAH TILAWATIL QUR'AN</span>
                    <span>✦</span>
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold text-white drop-shadow-sm tracking-wide">
                    LOKASI LOMBA
                </h1>

                <div class="gold-divider"></div>

                <h2 class="text-sm sm:text-xl font-semibold mt-2 text-green-50">
                    {{ strtoupper($event->event_name) }}
                </h2>
                <p class="text-green-100 text-xs sm:text-base mt-1.5 opacity-90">
                    Tahun {{ $event->event_year }}
                    @if ($event->event_location)
                        &bull; {{ $event->event_location }}
                    @endif
                </p>
            </div>
        </div>

        <div class="header-arch-divider">
            <svg viewBox="0 0 480 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,28
                       C 15,4 45,4 60,28
                       C 75,4 105,4 120,28
                       C 135,4 165,4 180,28
                       C 195,4 225,4 240,28
                       C 255,4 285,4 300,28
                       C 315,4 345,4 360,28
                       C 375,4 405,4 420,28
                       C 435,4 465,4 480,28
                       L480,28 L0,28 Z" fill="#f4f6f4" />
            </svg>
        </div>
    </div>

    {{-- ===============================
         SEARCH (sticky)
         =============================== --}}
    <div class="search-sticky bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-3">
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg pointer-events-none">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari bidang lomba, majelis, atau tempat lomba..." class="w-full border border-gray-300 rounded-full pl-11 pr-11 py-3 text-base focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition" oninput="filterVenues()">
                <button type="button" id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 text-xl leading-none" aria-label="Hapus pencarian">&times;</button>
            </div>
            <p id="resultCount" class="text-sm text-gray-500 mt-2 px-1"></p>
        </div>
    </div>

    {{-- ===============================
         LIST VENUE (dikelompokkan per lokasi, urut id venue terkecil)
         =============================== --}}
    <div class="max-w-4xl mx-auto px-4 py-5">

        <div id="venueList" class="grid grid-cols-1 gap-5">

            @forelse ($venues as $index => $venue)
                @php
                    $searchHaystack = collect([$venue['tempat_lomba']])
                        ->concat($venue['cabang']->pluck('bidang_lomba'))
                        ->concat($venue['cabang']->pluck('majelis'))
                        ->filter()
                        ->implode(' ');
                @endphp

                <div class="venue-card bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow duration-200" data-search="{{ strtolower($searchHaystack) }}">

                    {{-- FOTO BANNER + INFO VENUE --}}
                    <div class="sm:flex">
                        <div class="relative sm:w-64 shrink-0">
                            @if (!empty($venue['photo_url']))
                                <img src="{{ $venue['photo_url'] }}" alt="{{ $venue['tempat_lomba'] }}" class="venue-banner sm:h-full" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('div'), { className: 'venue-banner-fallback sm:h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center text-5xl', innerHTML: '🕌' }))">
                            @else
                                <div class="venue-banner-fallback sm:h-full bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center text-5xl">
                                    🕌
                                </div>
                            @endif

                            <div class="absolute inset-x-0 bottom-0 h-14 bg-gradient-to-t from-black/30 to-transparent pointer-events-none sm:hidden"></div>

                            {{-- NOMOR URUT VENUE --}}
                            <span class="absolute top-3 left-3 bg-white text-green-700 text-sm font-bold w-8 h-8 rounded-full flex items-center justify-center shadow-md">
                                {{ $index + 1 }}
                            </span>

                            {{-- TOMBOL QR --}}
                            @if ($venue['qr_code_url'])
                                <button type="button" class="absolute top-3 right-3 bg-white/95 hover:bg-white w-10 h-10 rounded-full shadow-md flex items-center justify-center text-lg transition-colors" title="Lihat QR Code" onclick="openQrModal(
                                        '{{ addslashes($venue['qr_code_url']) }}',
                                        '{{ addslashes($venue['tempat_lomba']) }}',
                                        '{{ addslashes($venue['maps_url'] ?? '') }}'
                                    )">
                                    🔳
                                </button>
                            @endif
                        </div>

                        <div class="p-4 sm:flex-1">
                            <div class="flex items-start gap-2">
                                <span class="shrink-0 mt-0.5 text-green-700">📍</span>
                                <div class="leading-snug">
                                    <div class="font-bold text-gray-900 text-lg">{{ $venue['tempat_lomba'] }}</div>
                                    @if ($venue['address'])
                                        <div class="text-gray-500 text-sm mt-0.5">{{ $venue['address'] }}</div>
                                    @endif
                                </div>
                            </div>

                            <span class="inline-block bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full mt-3">
                                {{ $venue['cabang']->count() }} Cabang Lomba
                            </span>

                            {{-- CTA MAPS --}}
                            <div class="mt-4">
                                @if ($venue['maps_url'])
                                    <a href="{{ $venue['maps_url'] }}" target="_blank" rel="noopener" class="btn-primary-action w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-base font-semibold rounded-2xl transition-colors">
                                        🗺️ Buka di Google Maps
                                    </a>
                                @else
                                    <div class="btn-primary-action w-full flex items-center justify-center text-sm text-gray-400 italic bg-gray-50 rounded-2xl">
                                        Koordinat belum tersedia
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- DAFTAR SEMUA CABANG LOMBA + MAJELIS DI VENUE INI --}}
                    <div class="border-t border-gray-100 bg-gray-50/60 px-4 py-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Cabang Lomba di Lokasi Ini
                        </p>
                        <div class="divide-y divide-dashed divide-gray-200">
                            @foreach ($venue['cabang'] as $cabang)
                                <div class="cabang-row flex items-center justify-between gap-3 py-2.5">
                                    <span class="text-gray-800 font-medium text-sm leading-snug">
                                        {{ $cabang['bidang_lomba'] }}
                                    </span>
                                    @if ($cabang['majelis'])
                                        <span class="shrink-0 bg-white border border-green-200 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                            {{ $cabang['majelis'] }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-400 text-lg py-20">
                    Belum ada data lokasi lomba untuk event ini.
                </div>
            @endforelse

        </div>

        <div id="noResult" class="hidden text-center text-gray-400 text-lg py-20">
            🔍 Tidak ditemukan bidang lomba / lokasi yang cocok dengan pencarian.
        </div>

    </div>

    <footer class="text-center text-sm text-gray-400 pb-8">
        &copy; {{ $event->event_year }} {{ $event->app_name ?? $event->event_name }}
    </footer>

    {{-- ===============================
         MODAL QR
         =============================== --}}
    <div id="qrModal" onclick="if(event.target===this) closeQrModal()">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 relative">
            <button type="button" onclick="closeQrModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-2xl leading-none w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100" aria-label="Tutup">
                &times;
            </button>

            <div class="text-center pt-2">
                <p id="qrModalTitle" class="font-bold text-gray-900 text-lg mb-4"></p>
                <img id="qrModalImage" src="" alt="QR Code" class="w-56 h-56 mx-auto rounded-2xl border border-gray-200">
                <p class="text-sm text-gray-500 mt-4">
                    Scan dengan aplikasi QR &amp; Barcode Scanner untuk navigasi langsung.
                </p>
                <a id="qrModalMapsLink" href="#" target="_blank" rel="noopener" class="btn-primary-action mt-5 flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white text-base font-semibold rounded-2xl transition-colors">
                    🗺️ Buka di Maps
                </a>
            </div>
        </div>
    </div>

    <script>
        function filterVenues() {
            const keyword = document.getElementById('searchInput').value.trim().toLowerCase();
            const cards = document.querySelectorAll('.venue-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const haystack = card.getAttribute('data-search') || '';
                const match = haystack.includes(keyword);
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            document.getElementById('noResult').classList.toggle('hidden', visibleCount !== 0 || cards.length === 0);
            document.getElementById('clearSearchBtn').classList.toggle('hidden', keyword.length === 0);

            const resultEl = document.getElementById('resultCount');
            resultEl.textContent = keyword ?
                `Menampilkan ${visibleCount} dari ${cards.length} lokasi` :
                '';
        }

        function clearSearch() {
            const input = document.getElementById('searchInput');
            input.value = '';
            input.focus();
            filterVenues();
        }

        function openQrModal(qrUrl, title, mapsUrl) {
            document.getElementById('qrModalImage').src = qrUrl;
            document.getElementById('qrModalTitle').textContent = title;

            const mapsLink = document.getElementById('qrModalMapsLink');
            if (mapsUrl) {
                mapsLink.href = mapsUrl;
                mapsLink.classList.remove('hidden');
            } else {
                mapsLink.classList.add('hidden');
            }

            document.getElementById('qrModal').classList.add('open');
        }

        function closeQrModal() {
            document.getElementById('qrModal').classList.remove('open');
        }
    </script>

</body>

</html>
