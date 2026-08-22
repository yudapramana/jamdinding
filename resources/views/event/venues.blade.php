<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lokasi Lomba - {{ $event->event_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .islamic-pattern {
            background-color: #f8faf8;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='%2315803d' stroke-opacity='0.05' stroke-width='1.5'%3E%3Cpath d='M40 4 L60 20 L60 60 L40 76 L20 60 L20 20 Z'/%3E%3Cpath d='M40 4 L20 20 L60 20 Z'/%3E%3Cpath d='M40 76 L20 60 L60 60 Z'/%3E%3Ccircle cx='40' cy='40' r='14'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 80px 80px;
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

        /* Sticky search bar biar tetap terlihat saat scroll daftar panjang (mobile-friendly) */
        .search-sticky {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .venue-photo {
            width: 64px;
            height: 64px;
            object-fit: cover;
        }

        /* Modal QR sederhana, tanpa library tambahan */
        #qrModal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.55);
            padding: 1rem;
        }

        #qrModal.open {
            display: flex;
        }
    </style>
</head>

<body class="min-h-screen islamic-pattern">

    {{-- ===============================
         HEADER
         =============================== --}}
    <div class="bg-green-700 text-white">
        <div class="max-w-3xl mx-auto px-4 py-5">

            {{-- LOGO ROW --}}
            <div class="flex justify-center items-center gap-3 mb-3">
                @php
                    $logos = [$event->logo_event, $event->logo_sponsor_1, $event->logo_sponsor_2, $event->logo_sponsor_3];
                @endphp

                @foreach ($logos as $logoPath)
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white rounded-lg shadow logo-slot p-1">
                        @if ($logoPath)
                            <img src="{{ $logoPath }}" alt="Logo">
                        @else
                            <span class="text-green-700 text-lg md:text-xl">🕌</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="text-center">
                <h1 class="text-2xl md:text-3xl font-extrabold text-red-400 drop-shadow-sm tracking-wide">
                    LOKASI LOMBA
                </h1>
                <h2 class="text-base md:text-xl font-bold mt-0.5">
                    {{ strtoupper($event->event_name) }}
                </h2>
                <p class="text-green-100 text-xs md:text-sm mt-1">
                    Tahun {{ $event->event_year }}
                    @if ($event->event_location)
                        &bull; {{ $event->event_location }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ===============================
         SEARCH (client-side, mobile-friendly)
         =============================== --}}
    <div class="search-sticky bg-white shadow-sm border-b">
        <div class="max-w-3xl mx-auto px-4 py-2.5">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Cari bidang lomba atau tempat lomba..." class="w-full border border-gray-300 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600" oninput="filterVenues()">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
            </div>
            <p id="resultCount" class="text-[11px] text-gray-400 mt-1.5"></p>
        </div>
    </div>

    {{-- ===============================
         LIST LOKASI LOMBA (compact rows)
         =============================== --}}
    <div class="max-w-3xl mx-auto px-4 py-4">

        <div id="venueList" class="space-y-2.5">

            @forelse ($groups as $index => $item)
                <div class="venue-card bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md hover:border-green-200 transition-all" data-search="{{ strtolower($item['bidang_lomba'] . ' ' . $item['tempat_lomba']) }}">
                    <div class="flex items-center gap-3 p-3">

                        {{-- FOTO LOKASI (dengan nomor urut menempel) --}}
                        <div class="relative shrink-0">
                            @if (!empty($item['photo_url']))
                                <img src="{{ $item['photo_url'] }}" alt="{{ $item['tempat_lomba'] }}" class="venue-photo rounded-lg border border-gray-200" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('div'), { className: 'venue-photo rounded-lg border border-gray-200 bg-green-50 flex items-center justify-center text-2xl', innerHTML: '🕌' }))">
                            @else
                                <div class="venue-photo rounded-lg border border-gray-200 bg-green-50 flex items-center justify-center text-2xl">
                                    🕌
                                </div>
                            @endif

                            <span class="absolute -top-1.5 -left-1.5 bg-green-600 text-white text-[11px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow">
                                {{ $index + 1 }}
                            </span>
                        </div>

                        {{-- INFO --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-green-800 font-bold text-sm leading-tight truncate">
                                {{ $item['bidang_lomba'] }}
                            </div>
                            @if ($item['majelis'])
                                <div class="text-[11px] text-gray-400 truncate">
                                    {{ $item['majelis'] }}
                                </div>
                            @endif
                            <div class="text-xs text-gray-600 flex items-center gap-1 mt-1 truncate">
                                <span class="shrink-0">📍</span>
                                <span class="truncate">{{ $item['tempat_lomba'] }}</span>
                            </div>
                        </div>

                        {{-- AKSI: QR (buka modal) + Maps --}}
                        <div class="flex flex-col items-center gap-1 shrink-0">
                            @if ($item['qr_code_url'])
                                <button type="button" class="p-0.5 border border-gray-200 rounded-md hover:border-green-400" title="Lihat QR Code" onclick="openQrModal(
                                        '{{ addslashes($item['qr_code_url']) }}',
                                        '{{ addslashes($item['tempat_lomba']) }}',
                                        '{{ addslashes($item['maps_url'] ?? '') }}'
                                    )">
                                    <img src="{{ $item['qr_code_url'] }}" alt="QR" class="w-9 h-9 rounded" loading="lazy">
                                </button>
                            @endif

                            @if ($item['maps_url'])
                                <a href="{{ $item['maps_url'] }}" target="_blank" rel="noopener" class="text-[10px] font-semibold text-green-700 hover:text-green-900">
                                    🗺️ Maps
                                </a>
                            @else
                                <span class="text-[10px] text-gray-300 italic">-</span>
                            @endif
                        </div>

                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 text-sm py-16">
                    Belum ada data lokasi lomba untuk event ini.
                </div>
            @endforelse

        </div>

        <div id="noResult" class="hidden text-center text-gray-400 text-sm py-16">
            Tidak ditemukan bidang lomba / lokasi yang cocok dengan pencarian.
        </div>

    </div>

    <footer class="text-center text-[11px] text-gray-400 pb-6">
        &copy; {{ $event->event_year }} {{ $event->app_name ?? $event->event_name }}
    </footer>

    {{-- ===============================
         MODAL QR (muncul saat foto QR di-tap)
         =============================== --}}
    <div id="qrModal" onclick="if(event.target===this) closeQrModal()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs p-5 relative">
            <button type="button" onclick="closeQrModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-lg leading-none" aria-label="Tutup">
                &times;
            </button>

            <div class="text-center">
                <p id="qrModalTitle" class="font-semibold text-gray-800 text-sm mb-3"></p>
                <img id="qrModalImage" src="" alt="QR Code" class="w-48 h-48 mx-auto rounded-lg border border-gray-200">
                <p class="text-[11px] text-gray-400 mt-3">
                    Scan dengan aplikasi QR & Barcode Scanner untuk navigasi langsung.
                </p>
                <a id="qrModalMapsLink" href="#" target="_blank" rel="noopener" class="mt-4 block bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl px-3 py-2.5 transition-colors">
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

            const resultEl = document.getElementById('resultCount');
            resultEl.textContent = keyword ?
                `Menampilkan ${visibleCount} dari ${cards.length} lokasi` :
                '';
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
