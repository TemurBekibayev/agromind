@extends('layouts.admin')

@section('title', 'Dehqonlar Ro\'yxati')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    .custom-div-icon {
        background: transparent !important;
        border: none !important;
    }
    .custom-dot-icon {
        width: 14px;
        height: 14px;
        background-color: #1a3c2a;
        border: 2.5px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
        cursor: move;
        transition: transform 0.2s ease, background-color 0.2s ease;
    }
    .custom-dot-icon:hover {
        transform: scale(1.3);
        background-color: #10B981;
    }
</style>
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
            <button onclick="openAddFarmerModal()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0">
                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Yangi Dehqon Qo'shish
            </button>
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
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center self-start rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                        {{ $farmer->region ? $farmer->region->name : 'Noma\'lum' }}
                                    </span>
                                    @if($farmer->district)
                                        <p class="text-xs text-gray-500 font-semibold">{{ $farmer->district }}</p>
                                    @endif
                                </div>
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
<div id="addFarmModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-md z-[100] hidden">
    <div class="bg-white w-screen h-screen shadow-2xl overflow-hidden flex flex-col">
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
        <form action="/admin/farms/store" method="POST" class="flex flex-col lg:flex-row overflow-hidden flex-1" id="farmStoreForm">
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
                                <option value="{{ $region->id }}" {{ str_contains($region->name, 'Qoraqalpog') ? 'selected' : '' }}>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuman nomi</label>
                        <input type="text" name="district" required value="Amudaryo tumani" placeholder="Masalan: Amudaryo tumani" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Maydoni (Gektar)</label>
                        <input type="number" step="0.1" name="size" required placeholder="Masalan: 45" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
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
                </div>

                <!-- Hidden inputs for coordinates array -->
                <input type="hidden" name="coordinates" id="coordinatesInput">

                <!-- Alert Warning for GIS -->
                <div id="drawWarning" class="p-3 bg-amber-50 border border-amber-250 rounded-xl text-xs text-amber-700">
                    ⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang (kamida 3 ta nuqtaga click qiling).
                </div>
                
                <div class="flex flex-col gap-2.5 pt-4 border-t border-slate-100 shrink-0">
                    <button type="button" onclick="startNewParcel()" class="w-full px-4 py-2 bg-emerald-50 border border-emerald-300 rounded-lg text-xs font-bold text-emerald-850 hover:bg-emerald-100 transition flex items-center justify-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi mustaqil maydon chizish
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="undoLastPoint()" class="w-1/2 px-3 py-2 border border-slate-250 rounded-lg text-xs font-semibold text-slate-705 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                            </svg>
                            Ortga qaytarish
                        </button>
                        <button type="button" onclick="clearDrawing()" class="w-1/2 px-3 py-2 border border-slate-250 rounded-lg text-xs font-semibold text-slate-705 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Tozalash
                        </button>
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 bg-forest-700 text-white rounded-lg text-sm font-bold shadow-md hover:bg-forest-600 transition flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Yer chegaralarini saqlash
                    </button>
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

<!-- Yangi Dehqon (Fermer) Modal Overlay -->
<div id="addFarmerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Yangi Dehqon (Fermer) Qo'shish</h3>
                <p class="text-xs text-gray-500 mt-0.5">Tizimga yangi dehqon yoki fermer yetakchisini qo'shish</p>
            </div>
            <button onclick="closeAddFarmerModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form action="/admin/farmers/store" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fermer F.I.Sh.</label>
                <input type="text" name="name" required placeholder="Masalan: Sobir Rahimov" class="w-full px-3 py-2 border border-slate-350 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Telefon Raqami</label>
                <input type="text" name="phone" required value="998" placeholder="Masalan: 998901234567" class="w-full px-3 py-2 border border-slate-350 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hudud / Viloyat</label>
                    <select name="region_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Viloyat...</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ str_contains($region->name, 'Qoraqalpog') ? 'selected' : '' }}>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuman nomi</label>
                    <input type="text" name="district" required value="Amudaryo tumani" placeholder="Masalan: Amudaryo tumani" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 shrink-0">
                <button type="button" onclick="closeAddFarmerModal()" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Bekor qilish</button>
                <button type="submit" class="px-5 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Saqlash</button>
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
    let polygonsList = [];
    let completedPolygonLayers = [];

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('farmStoreForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // If current active poly has points, try to save it
                if (polyPoints.length >= 3) {
                    polygonsList.push([...polyPoints]);
                    polyPoints = [];
                }
                
                if (polygonsList.length === 0) {
                    alert("Kamida bitta mustaqil yer maydoni to'liq chizilgan bo'lishi kerak (kamida 3 ta nuqta).");
                    e.preventDefault();
                    return false;
                }
                
                document.getElementById('coordinatesInput').value = JSON.stringify(polygonsList);
            });
        }
    });

    const customDotIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="custom-dot-icon"></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7]
    });

    function openAddFarmModal() {
        const modal = document.getElementById('addFarmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Initialize Map inside modal (wait for layout animation)
        if (!drawMap) {
            setTimeout(() => {
                drawMap = L.map('drawMap').setView([42.11005, 60.07327], 14); // Zomed in for easier field drawing

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(drawMap);

                // Handle click to draw polygon vertices
                drawMap.on('click', function(e) {
                    // Ignore clicks on markers themselves to prevent duplicate points
                    if (e.originalEvent.target.closest('.custom-dot-icon')) {
                        return;
                    }

                    const lat = parseFloat(e.latlng.lat);
                    const lng = parseFloat(e.latlng.lng);
                    
                    polyPoints.push([lat, lng]);

                    // Add vertex marker to map
                    const marker = L.marker([lat, lng], {
                        icon: customDotIcon,
                        draggable: true
                    }).addTo(drawMap);

                    // Handle drag behavior
                    marker.on('drag', function(evt) {
                        const newLatlng = evt.target.getLatLng();
                        const idx = pointMarkers.indexOf(marker);
                        if (idx !== -1) {
                            polyPoints[idx] = [parseFloat(newLatlng.lat), parseFloat(newLatlng.lng)];
                            updatePolygon();
                        }
                    });

                    marker.on('dragend', function() {
                        updatePolygon();
                    });
                    
                    pointMarkers.push(marker);
                    updatePolygon();
                });
            }, 100);
        } else {
            setTimeout(() => {
                drawMap.invalidateSize();
            }, 100);
        }
    }

    function updatePolygon() {
        if (drawPolygon && drawMap) {
            drawMap.removeLayer(drawPolygon);
            drawPolygon = null;
        }

        if (polyPoints.length >= 3) {
            drawPolygon = L.polygon(polyPoints, {
                color: '#1a3c2a',
                fillColor: '#10B981',
                fillOpacity: 0.25,
                weight: 2.5
            }).addTo(drawMap);
        }

        const totalCompleted = polygonsList.length;
        const activeCount = polyPoints.length;

        const warn = document.getElementById('drawWarning');
        if (totalCompleted > 0 || activeCount >= 3) {
            warn.className = "p-3 bg-emerald-50 border border-emerald-250 rounded-xl text-xs text-emerald-700";
            let msg = `✅ <strong>Yer maydonlari aniqlandi:</strong> `;
            if (totalCompleted > 0) {
                msg += `${totalCompleted} ta alohida maydon yakunlandi. `;
            }
            if (activeCount > 0) {
                msg += `Hozirgi chizilayotgan maydonda ${activeCount} ta nuqta bor.`;
            } else {
                msg += `Yangi mustaqil maydon chizishni boshlashingiz mumkin.`;
            }
            warn.innerHTML = msg;
        } else {
            warn.className = "p-3 bg-amber-50 border border-amber-250 rounded-xl text-xs text-amber-700";
            warn.innerHTML = "⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang (kamida 3 ta nuqtaga click qiling).";
        }
    }

    function startNewParcel() {
        if (polyPoints.length >= 3) {
            // Push active polygon to completed list
            polygonsList.push([...polyPoints]);
            
            // Draw static completed polygon
            const staticPoly = L.polygon(polyPoints, {
                color: '#059669',
                fillColor: '#10B981',
                fillOpacity: 0.20,
                weight: 2.5
            }).addTo(drawMap);
            completedPolygonLayers.push(staticPoly);
            
            // Clear current active markers and polygon drawing
            pointMarkers.forEach(m => drawMap.removeLayer(m));
            pointMarkers = [];
            
            polyPoints = [];
            drawPolygon = null;
            
            updatePolygon();
            alert("Ushbu maydon saqlandi! Endi xaritaning boshqa joyiga bosib yangi mustaqil maydon chizishingiz mumkin.");
        } else if (polyPoints.length > 0) {
            alert("Yangi maydon boshlash uchun joriy maydonda kamida 3 ta nuqta bo'lishi kerak!");
        } else {
            alert("Siz hali nuqta chizmadingiz. Xaritada biron bir joyni belgilang.");
        }
    }

    function undoLastPoint() {
        if (polyPoints.length > 0) {
            polyPoints.pop();
            const marker = pointMarkers.pop();
            if (marker && drawMap) {
                drawMap.removeLayer(marker);
            }
            updatePolygon();
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
            completedPolygonLayers.forEach(l => drawMap.removeLayer(l));
        }
        pointMarkers = [];
        completedPolygonLayers = [];
        polygonsList = [];
        updatePolygon();
    }

    function openAddFarmerModal() {
        const modal = document.getElementById('addFarmerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAddFarmerModal() {
        const modal = document.getElementById('addFarmerModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>
@endsection
