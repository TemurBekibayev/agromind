@extends('layouts.admin')

@section('title', 'Dehqonlar Ro\'yxati')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endsection

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dehqonlar va Fermerlar</h2>
            <p class="text-sm text-gray-500">Tizimda ro'yxatdan o'tgan barcha fermer xo'jaliklari va dehqonlar ro'yxati.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700 border border-forest-200 shrink-0">
                Jami: {{ $farmers->count() }} ta fermer
            </span>
            <button onclick="openAddFarmModal()" class="inline-flex items-center gap-2 rounded-lg bg-forest-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-forest-600 transition shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Yangi Xo'jalik Qo'shish
            </button>
        </div>
    </div>

    <!-- Success & Error Notifications -->
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm flex items-center justify-between">
            <span class="font-medium">🎉 Muvaffaqiyatli: {{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm">
            <span class="font-bold">⚠️ Xatolik yuz berdi:</span>
            <ul class="list-disc list-inside mt-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Table Card -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Fermer F.I.Sh.</th>
                        <th scope="col" class="px-6 py-4">Telefon Raqami</th>
                        <th scope="col" class="px-6 py-4">Hudud</th>
                        <th scope="col" class="px-6 py-4">Fermer Xo'jaliklari (Yer maydoni)</th>
                        <th scope="col" class="px-6 py-4">Ro'yxatdan o'tgan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($farmers as $farmer)
                        <tr class="hover:bg-gray-50/75 transition">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-forest-50 flex items-center justify-center text-forest-700 font-bold text-sm">
                                        {{ mb_substr($farmer->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ $farmer->name }}</p>
                                        <p class="text-xs text-gray-400">ID: #{{ $farmer->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $farmer->phone }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                    {{ $farmer->region ? $farmer->region->name : 'Noma\'lum' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($farmer->farms->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($farmer->farms as $farm)
                                            <div class="flex items-center justify-between text-xs gap-4 bg-forest-50/50 p-1 px-2 rounded border border-forest-100">
                                                <span class="font-medium text-forest-800">{{ $farm->name }}</span>
                                                <span class="text-gray-500 font-semibold">{{ $farm->size }} gektar ({{ $farm->soil_type }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Ferma biriktirilmagan</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                {{ $farmer->created_at ? $farmer->created_at->format('d.m.Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda hozircha ro'yxatdan o'tgan dehqonlar mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yangi Xo'jalik va GIS Geofence Modal Overlay -->
<div id="addFarmModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Yangi Fermer Xo'jaligi Qo'shish</h3>
                <p class="text-xs text-gray-500 mt-0.5">Xo'jalik ma'lumotlarini to'ldiring va xaritadan uning yer chegarasini chizing</p>
            </div>
            <button onclick="closeAddFarmModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form action="/admin/farms/store" method="POST" class="flex flex-col lg:flex-row overflow-hidden flex-1">
            @csrf
            
            <!-- Left inputs panel -->
            <div class="w-full lg:w-96 p-6 border-r border-slate-200 overflow-y-auto space-y-4 shrink-0">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fermer Xo'jaligi Nomi</label>
                    <input type="text" name="name" required placeholder="Masalan: G'ofur G'ulom Xo'jaligi" class="w-full px-3 py-2 border border-slate-350 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mas'ul Dehqon (Rahbar)</label>
                    <select name="user_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Rahbarni tanlang...</option>
                        @foreach($farmers as $farmer)
                            <option value="{{ $farmer->id }}">{{ $farmer->name }} ({{ $farmer->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hudud / Viloyat</label>
                        <select name="region_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                            <option value="">Viloyat...</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Maydoni (Gektar)</label>
                        <input type="number" step="0.1" name="size" required placeholder="Masalan: 45" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuproq Turi</label>
                    <select name="soil_type" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="Loyli tuproq">Loyli tuproq</option>
                        <option value="Serozyom (Bo'z tuproq)">Serozyom (Bo'z tuproq)</option>
                        <option value="Sho'rlangan tuproq">Sho'rlangan tuproq</option>
                        <option value="Qumloq tuproq">Qumloq tuproq</option>
                    </select>
                </div>

                <!-- Hidden inputs for coordinates array -->
                <input type="hidden" name="coordinates" id="coordinatesInput">

                <!-- Alert Warning for GIS -->
                <div id="drawWarning" class="p-3 bg-amber-50 border border-amber-250 rounded-xl text-xs text-amber-700">
                    ⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang (kamida 3 ta nuqtaga click qiling).
                </div>
                
                <div class="flex gap-3 pt-4 border-t border-slate-100 shrink-0">
                    <button type="button" onclick="clearDrawing()" class="w-1/2 px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Tozalash</button>
                    <button type="submit" class="w-1/2 px-4 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Saqlash</button>
                </div>
            </div>

            <!-- Right map panel -->
            <div class="flex-1 relative min-h-[400px] bg-slate-100">
                <div id="drawMap" class="h-full w-full"></div>
                <!-- HUD instruction -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm border border-slate-250 rounded-lg px-3 py-2 text-[10px] text-slate-700 z-[1000] shadow-md max-w-xs font-medium">
                    💡 <strong>Yer chegarasini chizish:</strong> Xaritadagi yer maydonlari ustiga ketma-ket click qilib nuqtalar chizing. Kamida 3 ta nuqta bosilsa, yopiq poligon chegarasi chiziladi.
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let drawMap = null;
    let polyPoints = [];
    let drawPolygon = null;
    let pointMarkers = [];

    function openAddFarmModal() {
        const modal = document.getElementById('addFarmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Initialize Map inside modal (wait for layout animation)
        if (!drawMap) {
            setTimeout(() => {
                drawMap = L.map('drawMap').setView([42.11005, 60.07327], 10); // Center on Karakalpakstan/Amudaryo

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(drawMap);

                // Handle click to draw polygon vertices
                drawMap.on('click', function(e) {
                    const lat = parseFloat(e.latlng.lat);
                    const lng = parseFloat(e.latlng.lng);
                    
                    polyPoints.push([lat, lng]);

                    // Add vertex marker to map
                    const marker = L.circleMarker([lat, lng], {
                        radius: 6,
                        color: '#1a3c2a',
                        fillColor: '#ffffff',
                        fillOpacity: 1,
                        weight: 2
                    }).addTo(drawMap);
                    
                    pointMarkers.push(marker);

                    // Redraw polygon
                    if (polyPoints.length >= 3) {
                        if (drawPolygon) {
                            drawMap.removeLayer(drawPolygon);
                        }
                        
                        drawPolygon = L.polygon(polyPoints, {
                            color: '#1a3c2a',
                            fillColor: '#10B981',
                            fillOpacity: 0.25,
                            weight: 2.5
                        }).addTo(drawMap);

                        // Update serial input
                        document.getElementById('coordinatesInput').value = JSON.stringify(polyPoints);

                        // Success notice
                        const warn = document.getElementById('drawWarning');
                        warn.className = "p-3 bg-emerald-50 border border-emerald-250 rounded-xl text-xs text-emerald-700";
                        warn.innerHTML = "✅ <strong>Yer maydoni aniqlandi:</strong> " + polyPoints.length + " ta koordinata chizildi.";
                    }
                });
            }, 100);
        }
    }

    function closeAddFarmModal() {
        const modal = document.getElementById('addFarmModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        clearDrawing();
    }

    function clearDrawing() {
        polyPoints = [];
        if (drawPolygon && drawMap) {
            drawMap.removeLayer(drawPolygon);
            drawPolygon = null;
        }
        if (drawMap) {
            pointMarkers.forEach(m => drawMap.removeLayer(m));
        }
        pointMarkers = [];
        document.getElementById('coordinatesInput').value = "";
        
        const warn = document.getElementById('drawWarning');
        warn.className = "p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700";
        warn.innerHTML = "⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang (kamida 3 ta nuqtaga click qiling).";
    }
</script>
@endsection
