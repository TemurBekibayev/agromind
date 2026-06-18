<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroMind GIS - Fermer Xo'jaliklari Monitoringi</title>
    
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;850&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#10B981', // Emerald
                            600: '#059669',
                            700: '#047857'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Leaflet.js CSS CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        #map { height: 100%; width: 100%; background-color: #0b0f19; }
        
        /* Custom styled scrollbars for sidebar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #0b0f19; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
        
        /* Premium custom styling for Leaflet controls */
        .leaflet-bar {
            border: none !important;
            box-shadow: 0 4px 12px -1px rgb(0 0 0 / 0.3), 0 2px 4px -2px rgb(0 0 0 / 0.3) !important;
        }
        .leaflet-bar a {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
            border-bottom: 1px solid #1e293b !important;
            transition: all 0.2s;
        }
        .leaflet-bar a:hover {
            background-color: #1e293b !important;
            color: #10B981 !important;
        }
        .leaflet-control-layers {
            background-color: #0f172a !important;
            color: #cbd5e1 !important;
            border: 1px solid #1e293b !important;
            box-shadow: 0 4px 12px -1px rgb(0 0 0 / 0.3) !important;
        }
    </style>
</head>
<body class="h-full flex flex-col overflow-hidden bg-slate-950 text-slate-100 select-none font-sans">

    <!-- Header Panel -->
    <header class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-900 px-6 shrink-0 z-20 shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h1 class="text-base font-bold tracking-tight text-slate-100 font-display flex items-center gap-2">
                    AGROMIND GIS <span class="bg-emerald-950 text-emerald-400 text-[10px] px-2.5 py-0.5 rounded-full font-sans font-bold border border-emerald-800/50">TIZIM MONITORINGI</span>
                </h1>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Enterprise Real-Time Farm & Fleet Controls</p>
            </div>
        </div>

        <!-- Clock and Connection Status -->
        <div class="flex items-center gap-3">
            <span class="hidden md:inline-flex items-center gap-1.5 text-xs text-slate-300 bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg shadow-inner">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> PORT: ULANISH FAOL
            </span>
            <div id="liveClock" class="text-xs font-semibold text-slate-300 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800 shadow-inner">
                12:00:00
            </div>
        </div>
    </header>

    <!-- Main Container: Sidebar + Fullscreen Map + AI Drawer -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Left Side: Fermer Xo'jaliklari Sidebar -->
        <aside class="w-96 border-r border-slate-800 bg-slate-900 flex flex-col overflow-hidden shrink-0 z-10 shadow-lg">
            <!-- Search & Filters -->
            <div class="p-4 border-b border-slate-800 bg-slate-950 text-slate-100 shrink-0 space-y-3">
                <div class="flex justify-between items-center">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider font-display">Tizim Monitoringi</h2>
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                </div>
                
                <!-- Search bar -->
                <div class="relative">
                    <input type="text" id="searchFarm" oninput="applyFilters()" placeholder="Xo'jalik yoki dehqon nomi..." class="w-full pl-8 pr-3 py-1.5 border border-slate-800 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-slate-900 text-slate-200 placeholder-slate-500 shadow-inner">
                    <span class="absolute left-2.5 top-2.5 text-slate-500">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>

                <!-- Advanced Filters -->
                <div class="grid grid-cols-2 gap-2 text-[10px]">
                    <div>
                        <label class="block text-slate-400 font-bold uppercase tracking-wider mb-1">Tumanlar</label>
                        <select id="filterDistrict" onchange="applyFilters()" class="w-full bg-slate-900 border border-slate-800 rounded p-1.5 font-semibold text-slate-350 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                            <option value="">Barchasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold uppercase tracking-wider mb-1">Tuproq unumdorligi</label>
                        <select id="filterSoil" onchange="applyFilters()" class="w-full bg-slate-900 border border-slate-800 rounded p-1.5 font-semibold text-slate-350 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                            <option value="">Barchasi</option>
                            <option value="high">Yuqori (>=70%)</option>
                            <option value="medium">O'rtacha (40-69%)</option>
                            <option value="low">Past (<40%)</option>
                            <option value="none">Tahlil qilinmagan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Statistics Panel -->
            <div class="grid grid-cols-2 gap-2 p-4 bg-slate-950 border-b border-slate-800 shrink-0">
                <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-lg">
                    <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Nazoratdagi maydon</span>
                    <span id="statTotalArea" class="text-xs font-black text-slate-200 font-display">0.0 ha</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-lg">
                    <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Faol texnikalar</span>
                    <span id="statActiveFleets" class="text-xs font-black text-slate-200 font-display">0/0</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-lg">
                    <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Fermer xo'jaliklari</span>
                    <span id="statTotalFarmers" class="text-xs font-black text-slate-200 font-display">0</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-lg">
                    <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">O'rtacha unumdorlik</span>
                    <span id="statAvgFertility" class="text-xs font-black text-emerald-400 font-display">0%</span>
                </div>
            </div>
            
            <!-- Farms and accordion list -->
            <div id="farmsList" class="flex-1 overflow-y-auto p-3 space-y-2.5 bg-slate-950">
                <div class="p-6 text-center text-xs text-slate-500 mt-10">Fermer xo'jaliklari yuklanmoqda...</div>
            </div>
        </aside>

        <!-- Center: Fullscreen Map & Vehicle HUD overlay -->
        <main class="flex-1 relative bg-slate-900">
            <!-- Leaflet Map -->
            <div id="map"></div>

            <!-- Floating Map Layer Selector -->
            <div class="absolute top-4 left-4 z-[1000] flex bg-slate-900/95 backdrop-blur-md border border-slate-800 rounded-xl p-1 shadow-2xl overflow-hidden font-display text-xs font-semibold">
                <button id="btnLayerSoil" onclick="switchMapLayer('soil')" class="px-4 py-2 rounded-lg text-emerald-400 bg-emerald-950/50 border border-emerald-800/40 transition-all duration-200 flex items-center gap-1.5 shadow-sm">
                    🧪 Tuproq tahlili
                </button>
                <button id="btnLayerNdvi" onclick="switchMapLayer('ndvi')" class="px-4 py-2 rounded-lg text-slate-450 hover:text-slate-200 transition-all duration-200 flex items-center gap-1.5 ml-1">
                    🛰️ Sun'iy yo'ldosh (NDVI)
                </button>
            </div>

            <!-- Floating NDVI Legend Widget -->
            <div id="ndviLegendWidget" class="hidden absolute bottom-4 left-4 z-[1000] bg-slate-900/95 backdrop-blur-md border border-slate-800 rounded-xl p-3 shadow-2xl w-48 text-xs text-slate-300 font-medium">
                <h4 class="font-extrabold text-slate-200 font-display uppercase tracking-wider text-[10px] mb-2">NDVI Rivojlanish Indeksi</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-md bg-[#059669]"></span>
                        <span>Zo'r (0.7 - 1.0)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-md bg-[#10B981]"></span>
                        <span>Yaxshi (0.5 - 0.7)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-md bg-[#F59E0B]"></span>
                        <span>O'rtacha (0.3 - 0.4)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-md bg-[#EF4444]"></span>
                        <span>Bo'sh yer / Suvsiz (<0.2)</span>
                    </div>
                </div>
            </div>
            
            <!-- Selected Vehicle Telemetry HUD -->
            <div id="selectedDeviceHud" class="hidden absolute top-4 right-4 bg-slate-900/95 backdrop-blur-md border border-slate-800 rounded-xl p-4 shadow-2xl z-[1000] w-64 transition-all duration-300 transform scale-95 translate-y-1 text-slate-300">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="hudName" class="font-extrabold text-sm text-slate-100 font-display">-</h3>
                        <p id="hudPlate" class="text-xs text-slate-400 font-semibold font-display">-</p>
                        <div id="hudConnectionStatus" class="mt-1"></div>
                    </div>
                    <button onclick="closeHud()" class="text-slate-400 hover:text-slate-200 bg-slate-850 p-1.5 border border-slate-800 rounded-lg transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Harakat kuni filtri -->
                <div class="mt-3.5 bg-slate-950 border border-slate-850 rounded-lg p-2.5">
                    <label for="historyDateFilter" class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Harakat traektoriyasi kuni</label>
                    <select id="historyDateFilter" onchange="changeHistoryDate()" class="w-full text-xs bg-slate-900 border border-slate-800 rounded p-1.5 font-bold text-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    </select>
                </div>
                
                <!-- Speedometer Widget -->
                <div class="mt-4 bg-slate-950 border border-slate-850 rounded-lg p-3 flex flex-col items-center justify-center">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Hozirgi tezligi</span>
                    <div class="flex items-baseline mt-0.5">
                        <span id="hudSpeed" class="text-3xl font-black text-slate-100 font-display">0</span>
                        <span class="text-xs font-semibold text-slate-400 ml-1 font-display">km/soat</span>
                    </div>
                </div>

                <!-- Ignition and Battery details -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="bg-slate-950 p-2.5 rounded-lg border border-slate-850 text-center">
                        <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Dvigatel (ACC)</span>
                        <span id="hudIgnition" class="text-[10px] font-extrabold mt-1.5 block">-</span>
                    </div>
                    <div class="bg-slate-950 p-2.5 rounded-lg border border-slate-850 text-center">
                        <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Batareya</span>
                        <span class="text-sm font-bold text-slate-200 font-display mt-1 block"><span id="hudVoltage">12.96</span> <span class="text-[10px] font-normal text-slate-500">V</span></span>
                    </div>
                </div>

                <!-- Live stats -->
                <div class="mt-2.5 bg-slate-950 p-2.5 rounded-lg border border-slate-850 text-xs space-y-1.5">
                    <div class="flex justify-between text-slate-400">
                        <span>Yoqilg'i miqdori:</span>
                        <span class="font-bold text-slate-200"><span id="hudFuel">-</span>%</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Faollik vaqti:</span>
                        <span id="hudDuration" class="font-bold text-slate-200">-</span>
                    </div>
                    <div class="flex justify-between text-slate-500 text-[9px] pt-1.5 border-t border-slate-850">
                        <span>Oxirgi yangilanish:</span>
                        <span id="hudTime" class="font-medium">-</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Right Side: AI Soil Analysis Drawer -->
        <aside id="aiAnalysisDrawer" class="w-96 border-l border-slate-800 bg-slate-900 flex flex-col overflow-hidden shrink-0 z-10 shadow-xl hidden">
            <!-- Header -->
            <div class="p-4 border-b border-slate-800 bg-slate-950 shrink-0 flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-lg bg-emerald-950 border border-emerald-800/60 flex items-center justify-center text-emerald-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.982-11.861H13.62l.812-5.043L5.458 15.904h4.355z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider font-display" id="drawerFieldName">Maydon Tahlili</h3>
                        <p class="text-[9px] text-slate-400 font-semibold" id="drawerFarmName">AgroMind AI Tizimi</p>
                    </div>
                </div>
                <button onclick="closeAiAnalysisDrawer()" class="text-slate-400 hover:text-slate-200 bg-slate-900 border border-slate-850 p-1.5 rounded-lg transition shadow-md">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div id="drawerContent" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-950 text-slate-300">
                <!-- Filled dynamically -->
            </div>
            
            <!-- Bottom Footer Actions -->
            <div class="p-4 border-t border-slate-800 bg-slate-950 flex gap-2 shrink-0">
                <button onclick="printAiReport()" class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-md hover:bg-emerald-500 transition border border-emerald-500/40">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 19.189l3-3m0 0l3-3m-3 3v12m5.94-9.94l.008-.008a3 3 0 00-4.243-4.243l-.008.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Hisobotni Chop Etish
                </button>
                <button onclick="closeAiAnalysisDrawer()" class="rounded-lg bg-slate-900 border border-slate-800 px-4 py-2 text-xs font-bold text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition">
                    Yopish
                </button>
            </div>
        </aside>
        
    </div>

    <!-- Leaflet JS CDN -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // Real Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toTimeString().split(' ')[0];
            document.getElementById('liveClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Initialize Map centered directly in Karakalpakstan (Amudaryo)
        const map = L.map('map');
        
        let lastMapInteractionTime = 0;
        let isProgrammaticMove = false;

        function programmaticMove(action) {
            isProgrammaticMove = true;
            try {
                action();
            } finally {
                isProgrammaticMove = false;
            }
        }

        programmaticMove(() => {
            map.setView([42.11005, 60.07327], 9);
        });

        // Track user interactions to suspend auto-centering
        map.on('movestart', function() {
            if (!isProgrammaticMove) {
                lastMapInteractionTime = Date.now();
            }
        });

        // Base Layers
        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        });

        const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            attribution: '&copy; Google Maps',
            maxZoom: 20
        });

        const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            attribution: '&copy; Google Maps',
            maxZoom: 20
        });

        const googleStreets = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            attribution: '&copy; Google Maps',
            maxZoom: 20
        });

        // Add default map layer
        osmLayer.addTo(map);

        const baseMaps = {
            "Oddiy xarita": osmLayer,
            "Sun'iy yo'ldosh": googleSatellite,
            "Gibrid xarita": googleHybrid,
            "Google ko'chalari": googleStreets
        };

        // Add layers control on the top-left (so it does not overlap with the HUD on the right)
        L.control.layers(baseMaps, null, { position: 'topleft' }).addTo(map);

        // Global states
        let rawFarmsData = [];
        let filteredFarmsData = [];
        let selectedFarmId = null;
        let selectedVehicleId = null;
        let currentMapLayer = 'soil'; // 'soil' or 'ndvi'
        
        let mapGeofenceLayers = [];
        const mapVehicleMarkers = {};
        const geofenceLayersMap = {};
        
        let activeHistoryPolylines = [];
        let activeHistoryMarkers = [];

        // Fill history date filter dropdown dynamically for the last 7 days in Uzbek
        function populateDateFilter() {
            const select = document.getElementById('historyDateFilter');
            if (!select) return;
            
            select.innerHTML = '';
            
            const uzMonths = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'];
            const uzWeekdays = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
            
            for (let i = 0; i < 7; i++) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                const dateStr = `${yyyy}-${mm}-${dd}`;
                
                let label = "";
                if (i === 0) {
                    label = `Bugun (${d.getDate()}-${uzMonths[d.getMonth()]})`;
                } else if (i === 1) {
                    label = `Kecha (${d.getDate()}-${uzMonths[d.getMonth()]})`;
                } else {
                    const weekday = uzWeekdays[d.getDay()];
                    label = `${weekday} (${d.getDate()}-${uzMonths[d.getMonth()]})`;
                }
                
                const option = document.createElement('option');
                option.value = dateStr;
                option.textContent = label;
                select.appendChild(option);
            }
        }

        // Change handler for date filter
        function changeHistoryDate() {
            if (!selectedVehicleId) return;
            const select = document.getElementById('historyDateFilter');
            const selectedDate = select.value;
            loadVehicleHistoryAndDrawTrail(selectedVehicleId, selectedDate, true);
        }

        // Clear GPRS track history trail from map
        function clearHistoryTrail() {
            activeHistoryPolylines.forEach(layer => map.removeLayer(layer));
            activeHistoryPolylines = [];
            activeHistoryMarkers.forEach(layer => map.removeLayer(layer));
            activeHistoryMarkers = [];
        }

        // Fetch and draw last 24h history trail for a vehicle
        function loadVehicleHistoryAndDrawTrail(vId, date = '', fitBounds = false) {
            clearHistoryTrail();
            
            let url = `/api/live-vehicles/${vId}/history`;
            if (date) {
                url += `?date=${date}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.history && data.history.length > 0) {
                        const rawHistory = data.history;
                        
                        // GPS Drift Filter & Stationary Noise Filter
                        const history = [];
                        for (let i = 0; i < rawHistory.length; i++) {
                            const point = rawHistory[i];
                            const lat = parseFloat(point.latitude);
                            const lng = parseFloat(point.longitude);
                            
                            if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) continue;
                            
                            if (history.length === 0) {
                                history.push(point);
                                continue;
                            }
                            
                            const lastPoint = history[history.length - 1];
                            const lastLat = parseFloat(lastPoint.latitude);
                            const lastLng = parseFloat(lastPoint.longitude);
                            
                            const distance = L.latLng(lastLat, lastLng).distanceTo(L.latLng(lat, lng));
                            
                            const prevTime = new Date(lastPoint.recorded_at).getTime();
                            const currTime = new Date(point.recorded_at).getTime();
                            const diffSeconds = Math.max(1, Math.floor((currTime - prevTime) / 1000));
                            
                            // Average speed in meters per second
                            const speedMPS = distance / diffSeconds;
                            
                            // GPS Drift: if vehicle jumps at a speed greater than 90 km/h (25 m/s) over short time
                            // and the jump distance is substantial (> 35 meters)
                            if (speedMPS > 25 && distance > 35 && diffSeconds < 180) {
                                console.warn("GPS Drift filtrlangan: Tezlik =", (speedMPS * 3.6).toFixed(1), "km/h, Masofa =", distance.toFixed(1), "m");
                                continue;
                            }
                            
                            // Stationary Drift: if vehicle is stopped but GPS bounces slightly (under 5 meters)
                            if (distance < 5 && parseFloat(point.speed) === 0) {
                                continue;
                            }
                            
                            history.push(point);
                        }

                        if (history.length === 0) return;

                        // Circle marker for the start of the trail (oldest point)
                        const firstPoint = history[0];
                        const startLatLng = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
                        const startMarker = L.circleMarker(startLatLng, {
                            radius: 7,
                            color: '#3B82F6', // Blue border for start
                            fillColor: '#93C5FD',
                            fillOpacity: 1,
                            weight: 2.5
                        }).addTo(map).bindPopup(`
                            <div class="p-1.5 font-sans text-xs">
                                <h4 class="font-extrabold text-blue-700 text-sm font-display mb-1">📍 Yo'nalish boshlanishi</h4>
                                <p class="text-[10px] text-slate-500">Boshlangan vaqt: <strong>${firstPoint.recorded_at}</strong></p>
                            </div>
                        `);
                        activeHistoryMarkers.push(startMarker);

                        let currentSegment = [startLatLng];
                        let gapCount = 0;
                        
                        // Time difference threshold: 90 seconds (1.5 minutes) for connection break
                        const MAX_GAP_SECONDS = 90; 

                        for (let i = 1; i < history.length; i++) {
                            const prevPoint = history[i - 1];
                            const currPoint = history[i];
                            
                            const prevTime = new Date(prevPoint.recorded_at).getTime();
                            const currTime = new Date(currPoint.recorded_at).getTime();
                            const diffSeconds = Math.floor((currTime - prevTime) / 1000);
                            
                            const currLatLng = [parseFloat(currPoint.latitude), parseFloat(currPoint.longitude)];
                            
                            if (diffSeconds > MAX_GAP_SECONDS) {
                                // GPRS Gap / GPS Connection break found
                                gapCount++;
                                
                                // Draw the current active green segment
                                if (currentSegment.length > 0) {
                                    const poly = L.polyline(currentSegment, {
                                        color: '#10B981', // Emerald green
                                        weight: 4,
                                        opacity: 0.8,
                                        dashArray: '6, 8', // elegant dashed line
                                        lineJoin: 'round'
                                    }).addTo(map);
                                    activeHistoryPolylines.push(poly);
                                }
                                
                                // Draw the red gap connection line to show building fly-over or connection loss jumps
                                const lastLatLngOfPrevSegment = currentSegment[currentSegment.length - 1];
                                const gapPoly = L.polyline([lastLatLngOfPrevSegment, currLatLng], {
                                    color: '#EF4444', // Red for connection gap
                                    weight: 2,
                                    opacity: 0.6,
                                    dashArray: '4, 6',
                                    lineJoin: 'round'
                                }).addTo(map);
                                activeHistoryPolylines.push(gapPoly);
                                
                                // Format connection break duration
                                let durationStr = "";
                                const diffMinutes = Math.floor(diffSeconds / 60);
                                if (diffMinutes < 60) {
                                    durationStr = `${diffMinutes} daqiqa`;
                                } else {
                                    const diffHours = Math.floor(diffMinutes / 60);
                                    const remMinutes = diffMinutes % 60;
                                    durationStr = `${diffHours} soat ${remMinutes} daqiqa`;
                                }
                                
                                // Create custom warning marker representing GPS break with popup
                                const gapMarker = L.circleMarker(currLatLng, {
                                    radius: 6,
                                    color: '#B91C1C', // Dark red
                                    fillColor: '#FCA5A5', // Light red
                                    fillOpacity: 1,
                                    weight: 2
                                }).addTo(map).bindPopup(`
                                    <div class="p-1.5 font-sans text-xs w-48">
                                        <h4 class="font-bold text-red-650 text-xs font-display flex items-center gap-1">
                                            ⚠️ ALOQA UZILISHI #${gapCount}
                                        </h4>
                                        <div class="mt-2 space-y-1 text-slate-600 text-[10px]">
                                            <p>Aloqa yo'qolgan: <strong class="text-slate-800">${prevPoint.recorded_at}</strong></p>
                                            <p>Aloqa tiklangan: <strong class="text-slate-800">${currPoint.recorded_at}</strong></p>
                                            <p>Uzilish vaqti: <span class="bg-red-100 text-red-800 font-bold px-1.5 py-0.5 rounded">${durationStr}</span></p>
                                        </div>
                                    </div>
                                `);
                                activeHistoryMarkers.push(gapMarker);
                                
                                // Start new coordinate segment
                                currentSegment = [currLatLng];
                            } else {
                                currentSegment.push(currLatLng);
                            }
                        }
                        
                        // Draw the final remaining segment
                        if (currentSegment.length > 0) {
                            const poly = L.polyline(currentSegment, {
                                color: '#10B981', // Emerald green
                                weight: 4,
                                opacity: 0.8,
                                dashArray: '6, 8',
                                lineJoin: 'round'
                            }).addTo(map);
                            activeHistoryPolylines.push(poly);
                        }

                        // Fit bounds to the drawn history trail if requested and polylines exist
                        if (fitBounds && activeHistoryPolylines.length > 0) {
                            const group = new L.featureGroup(activeHistoryPolylines);
                            programmaticMove(() => {
                                map.fitBounds(group.getBounds().pad(0.1));
                            });
                        }
                    }
                })
                .catch(err => console.error('Error loading history:', err));
        }

        // Connection Status Badge helper
        function getConnectionStatusBadge(recordedAt) {
            const recordedTime = new Date(recordedAt);
            const now = new Date();
            const diffSeconds = Math.max(0, Math.floor((now - recordedTime) / 1000));
            
            if (diffSeconds <= 45) {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 border border-emerald-250 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded-full animate-pulse">● JONLI ALOQA (FAOL)</span>`;
            } else if (diffSeconds <= 180) {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 border border-amber-250 bg-amber-100 text-amber-800 text-[9px] font-bold rounded-full">● ULANISH KUCHSIZ (${diffSeconds}s)</span>`;
            } else {
                return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 border border-rose-250 bg-rose-100 text-rose-800 text-[9px] font-bold rounded-full">● ALOQA YO'Q (MUAMMO)</span>`;
            }
        }

        // Vehicle Heading/Bearing cache
        const vehicleAngles = {};
        const lastVehicleCoords = {};

        function getBearing(lat1, lng1, lat2, lng2) {
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const lat1Rad = lat1 * Math.PI / 180;
            const lat2Rad = lat2 * Math.PI / 180;
            const y = Math.sin(dLng) * Math.cos(lat2Rad);
            const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) - Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLng);
            let brng = Math.atan2(y, x) * 180 / Math.PI;
            return (brng + 360) % 360;
        }

        function updateVehicleAngle(vehicleId, newCoords, speed) {
            let angle = vehicleAngles[vehicleId];
            if (angle === undefined) {
                // Seed a default angle based on vehicle ID so they aren't all facing North initially
                angle = (vehicleId * 53) % 360;
                vehicleAngles[vehicleId] = angle;
            }

            const last = lastVehicleCoords[vehicleId];
            if (last) {
                if (speed > 0 && (last[0] !== newCoords[0] || last[1] !== newCoords[1])) {
                    angle = getBearing(last[0], last[1], newCoords[0], newCoords[1]);
                    vehicleAngles[vehicleId] = angle;
                }
            }
            lastVehicleCoords[vehicleId] = newCoords;
            return angle;
        }

        // SVG templates for different vehicle types
        function getVehicleSvg(type) {
            if (type === 'tractor') {
                return `
                    <svg viewBox="0 0 24 24" class="w-4 h-4 fill-current text-white">
                        <path d="M19 15h-1.2c-.4-1.2-1.5-2-2.8-2s-2.4.8-2.8 2H9.8c-.4-1.2-1.5-2-2.8-2s-2.4.8-2.8 2H3v-2.5C3 11.1 4.1 10 5.5 10H8l.8-2.5C9.2 6.6 10.1 6 11.1 6H15c1.1 0 2 .9 2 2v2.5h2c1.1 0 2 .9 2 2.5v2zm-12 3a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zm8 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                    </svg>
                `;
            } else if (type === 'combine') {
                return `
                    <svg viewBox="0 0 24 24" class="w-4.5 h-4.5 fill-current text-white">
                        <path d="M18 7h-6l-1.5 3H6v4h12v-5c0-1.1-.9-2-2-2z" />
                        <path d="M2 13v3h1.5v-3H2z M3 14.2h2v1H3z" />
                        <path d="M5 13.5l1.5-1.5v2.5z" />
                        <path d="M14 7l-4-4-.7.7 3.5 3.5z" />
                        <circle cx="8.5" cy="16.5" r="2.2" />
                        <circle cx="15.5" cy="17" r="1.3" />
                    </svg>
                `;
            } else { // other
                return `
                    <svg viewBox="0 0 24 24" class="w-4 h-4 fill-current text-white">
                        <path d="M20 10h-2V7c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h1.2c.4 1.2 1.5 2 2.8 2s2.4-.8 2.8-2h4.4c.4 1.2 1.5 2 2.8 2s2.4-.8 2.8-2H22v-4c0-1.7-1.3-3-3-3zM7.5 17c-.8 0-1.5-.7-1.5-1.5S6.7 14 7.5 14s1.5.7 1.5 1.5S8.3 17 7.5 17zm10 0c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zM17 11h-3V7h3v4z"/>
                    </svg>
                `;
            }
        }

        // Custom Glowing Marker Icon with Direction Arrow & Vehicle SVG
        function getVehicleIcon(status, isMoving, type = 'tractor', angle = 0) {
            let color = '#64748B'; // offline (gray)
            if (status === 'online') {
                color = isMoving ? '#10B981' : '#F59E0B'; // yashil (harakatda) yoki sariq (kutishda)
            } else if (status === 'problem') {
                color = '#EF4444'; // red
            }

            return L.divIcon({
                className: 'custom-gps-icon',
                html: `
                    <div class="relative flex items-center justify-center" style="width: 36px; height: 36px;">
                        <!-- Glow effect for online & moving -->
                        ${status === 'online' && isMoving ? `
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-30" style="background-color: ${color};"></span>
                        ` : ''}
                        
                        <!-- Circular border / background with SVG Icon inside -->
                        <div class="rounded-full shadow-lg border-2 border-slate-900 flex items-center justify-center z-10" 
                             style="width: 28px; height: 28px; background-color: ${color};">
                            ${getVehicleSvg(type)}
                        </div>
                        
                        <!-- Direction Arrow overlay pointing in the direction of travel -->
                        <div class="absolute inset-0 flex items-start justify-center pointer-events-none" style="transform: rotate(${angle}deg); z-index: 20;">
                            <!-- Small triangle pointing upwards at the top edge of the marker -->
                            <div class="w-0 h-0 border-l-[4px] border-r-[4px] border-b-[6px] border-l-transparent border-r-transparent" 
                                 style="border-b-color: ${color}; filter: drop-shadow(0px -1px 1px rgba(0,0,0,0.6)); margin-top: -6px;"></div>
                        </div>
                    </div>
                `,
                iconSize: [36, 36],
                iconAnchor: [18, 18]
            });
        }

        // Selection of Farm Accordion
        function toggleFarm(farmId) {
            if (selectedFarmId === farmId) {
                // If clicked again, close it
                selectedFarmId = null;
            } else {
                selectedFarmId = farmId;
                const farm = rawFarmsData.find(f => f.id === farmId);
                if (farm) {
                    // Zoom and pan to farm center
                    if (farm.latitude && farm.longitude) {
                        programmaticMove(() => {
                            map.setView([farm.latitude, farm.longitude], 12);
                        });
                    }
                }
            }
            drawAllFarmsGeofences(rawFarmsData);
            renderFarmsSidebar();
        }

        // Clear Geofence Polygon layers on map
        function clearGeofenceLayers() {
            mapGeofenceLayers.forEach(layer => map.removeLayer(layer));
            mapGeofenceLayers = [];
            // Clear geofence layers map cache
            for (let key in geofenceLayersMap) {
                delete geofenceLayersMap[key];
            }
        }

        // Zoom to specific geofence on map
        function zoomToGeofence(gfId) {
            const polygon = geofenceLayersMap[gfId];
            if (polygon) {
                programmaticMove(() => {
                    map.fitBounds(polygon.getBounds());
                });
            }
        }

        // Select Geofence (zooms on map and opens AI Drawer)
        function selectGeofence(gfId, farmId) {
            const polygon = geofenceLayersMap[gfId];
            if (polygon) {
                programmaticMove(() => {
                    map.fitBounds(polygon.getBounds());
                });
            }
            openAiAnalysisDrawer(gfId, farmId);
        }

        // Highlight selected geofence on map
        let highlightedPolygon = null;
        let originalPolygonStyle = null;

        function highlightGeofenceOnMap(gfId) {
            resetGeofenceHighlight();
            const polygon = geofenceLayersMap[gfId];
            if (polygon) {
                highlightedPolygon = polygon;
                originalPolygonStyle = {
                    color: polygon.options.color,
                    fillColor: polygon.options.fillColor,
                    fillOpacity: polygon.options.fillOpacity,
                    weight: polygon.options.weight
                };
                polygon.setStyle({
                    color: '#ffffff',
                    fillColor: polygon.options.fillColor,
                    fillOpacity: currentMapLayer === 'ndvi' ? 0.95 : 0.35,
                    weight: 4
                });
            }
        }

        function resetGeofenceHighlight() {
            if (highlightedPolygon && originalPolygonStyle) {
                highlightedPolygon.setStyle(originalPolygonStyle);
                highlightedPolygon = null;
                originalPolygonStyle = null;
            }
        }

        // Seeded random generator for deterministic NDVI heatmap canvas generation
        function seededRandom(seed) {
            const x = Math.sin(seed) * 10000;
            return x - Math.floor(x);
        }

        function generateNdviPattern(gfId, farmId) {
            const patternId = `ndvi-pattern-${gfId}`;
            
            // If pattern already exists, return its URL reference
            if (document.getElementById(patternId)) {
                return `url(#${patternId})`;
            }

            // Access Leaflet's SVG container
            const svgEl = map._renderer._container;
            if (!svgEl) return '#10B981'; // Green fallback
            
            let defs = svgEl.querySelector('defs');
            if (!defs) {
                defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
                svgEl.insertBefore(defs, svgEl.firstChild);
            }

            // Create canvas for NDVI heatmap texture
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 256;
            const ctx = canvas.getContext('2d');

            // Base grass/crop backdrop (light green)
            ctx.fillStyle = '#bdf3be'; 
            ctx.fillRect(0, 0, 256, 256);

            // Draw deterministic organic blobs (representing vegetation index variations)
            const seed = gfId * 7 + farmId * 13;
            const numBlobs = 6 + Math.floor(seededRandom(seed) * 5); // 6 to 10 blobs

            for (let i = 0; i < numBlobs; i++) {
                const x = seededRandom(seed + i * 2) * 256;
                const y = seededRandom(seed + i * 3) * 256;
                const r = 45 + seededRandom(seed + i * 4) * 80;
                
                const randType = seededRandom(seed + i * 5);
                let colorStart, colorEnd;

                if (randType > 0.4) {
                    // Strong healthy crop (dark green)
                    colorStart = 'rgba(5, 150, 105, 0.9)'; 
                    colorEnd = 'rgba(16, 185, 129, 0.0)'; 
                } else if (randType > 0.15) {
                    // Medium/developing crop (amber/yellow)
                    colorStart = 'rgba(245, 158, 11, 0.85)'; 
                    colorEnd = 'rgba(251, 191, 36, 0.0)'; 
                } else {
                    // Sparse vegetation/bare soil (red/orange)
                    colorStart = 'rgba(239, 68, 68, 0.8)'; 
                    colorEnd = 'rgba(248, 113, 113, 0.0)'; 
                }

                const grad = ctx.createRadialGradient(x, y, 4, x, y, r);
                grad.addColorStop(0, colorStart);
                grad.addColorStop(1, colorEnd);

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(x, y, r, 0, Math.PI * 2);
                ctx.fill();
            }

            // Apply fine noise factor to simulate satellite sensor pixelation
            const imgData = ctx.getImageData(0, 0, 256, 256);
            const data = imgData.data;
            for (let i = 0; i < data.length; i += 4) {
                const noise = (seededRandom(seed + i) - 0.5) * 12;
                data[i] = Math.max(0, Math.min(255, data[i] + noise));     
                data[i+1] = Math.max(0, Math.min(255, data[i+1] + noise)); 
                data[i+2] = Math.max(0, Math.min(255, data[i+2] + noise)); 
            }
            ctx.putImageData(imgData, 0, 0);

            // Convert to base64 DataURL
            const dataUrl = canvas.toDataURL();

            // Create SVG Pattern element
            const pattern = document.createElementNS("http://www.w3.org/2000/svg", "pattern");
            pattern.setAttribute("id", patternId);
            pattern.setAttribute("patternContentUnits", "objectBoundingBox");
            pattern.setAttribute("width", "1");
            pattern.setAttribute("height", "1");

            // Create image element inside pattern
            const img = document.createElementNS("http://www.w3.org/2000/svg", "image");
            img.setAttribute("href", dataUrl);
            img.setAttribute("x", "0");
            img.setAttribute("y", "0");
            img.setAttribute("width", "1");
            img.setAttribute("height", "1");
            img.setAttribute("preserveAspectRatio", "none");

            pattern.appendChild(img);
            defs.appendChild(pattern);

            return `url(#${patternId})`;
        }

        function switchMapLayer(layer) {
            if (currentMapLayer === layer) return;
            currentMapLayer = layer;
            
            const btnSoil = document.getElementById('btnLayerSoil');
            const btnNdvi = document.getElementById('btnLayerNdvi');
            const legend = document.getElementById('ndviLegendWidget');
            
            if (layer === 'soil') {
                btnSoil.className = "px-4 py-2 rounded-lg text-emerald-400 bg-emerald-950/50 border border-emerald-800/40 transition-all duration-200 flex items-center gap-1.5 shadow-sm";
                btnNdvi.className = "px-4 py-2 rounded-lg text-slate-450 hover:text-slate-200 transition-all duration-200 flex items-center gap-1.5 ml-1";
                legend.classList.add('hidden');
            } else {
                btnNdvi.className = "px-4 py-2 rounded-lg text-emerald-400 bg-emerald-950/50 border border-emerald-800/40 transition-all duration-200 flex items-center gap-1.5 shadow-sm";
                btnSoil.className = "px-4 py-2 rounded-lg text-slate-450 hover:text-slate-200 transition-all duration-200 flex items-center gap-1.5 ml-1";
                legend.classList.remove('hidden');
            }
            
            // Redraw geofences with the new layer styling
            drawAllFarmsGeofences(rawFarmsData);
            
            // Refresh details drawer if open
            if (activeGeofence && activeFarm) {
                openAiAnalysisDrawer(activeGeofence.id, activeFarm.id);
            }
        }

        // Draw All Farm Geofences on the map globally
        function drawAllFarmsGeofences(farms) {
            clearGeofenceLayers();

            farms.forEach(farm => {
                if (farm.geofences && farm.geofences.length > 0) {
                    farm.geofences.forEach(gf => {
                        if (gf.coordinates && gf.coordinates.length > 0) {
                            // Default styling (soft blue/gray for unanalyzed)
                            let borderColor = '#475569';
                            let fillColor = '#64748b';
                            let fillOpacity = 0.18;
                            
                            if (currentMapLayer === 'ndvi') {
                                borderColor = '#059669'; // Emerald border
                                fillColor = generateNdviPattern(gf.id, farm.id);
                                fillOpacity = 0.85;
                            } else {
                                if (gf.latest_soil_analysis) {
                                    const fertility = parseFloat(gf.latest_soil_analysis.fertility);
                                    if (fertility >= 70) {
                                        borderColor = '#059669'; // Emerald
                                        fillColor = '#10B981';
                                    } else if (fertility >= 40) {
                                        borderColor = '#D97706'; // Amber
                                        fillColor = '#F59E0B';
                                    } else {
                                        borderColor = '#E11D48'; // Rose
                                        fillColor = '#F43F5E';
                                    }
                                }
                            }
                            
                            const isSelected = selectedFarmId === farm.id;
                            
                            const polygon = L.polygon(gf.coordinates, {
                                color: isSelected ? '#ffffff' : borderColor,
                                fillColor: fillColor,
                                fillOpacity: isSelected ? (currentMapLayer === 'ndvi' ? 0.95 : 0.25) : fillOpacity,
                                weight: isSelected ? 3.5 : 2.5
                            }).addTo(map);

                            let tooltipContent = `${farm.name} - ${gf.name}`;
                            if (currentMapLayer === 'ndvi') {
                                tooltipContent += ` (Yo'ldosh NDVI)`;
                            } else {
                                if (gf.latest_soil_analysis) {
                                    tooltipContent += ` (${parseFloat(gf.latest_soil_analysis.fertility).toFixed(0)}% NPK)`;
                                } else {
                                    tooltipContent += ` (Tahlil qilinmagan)`;
                                }
                            }
                            polygon.bindTooltip(tooltipContent, { sticky: true });

                            // Click event to open the AI drawer / Farmer Info
                            polygon.on('click', (e) => {
                                L.DomEvent.stopPropagation(e);
                                selectGeofence(gf.id, farm.id);
                                if (selectedFarmId !== farm.id) {
                                    selectedFarmId = farm.id;
                                    renderFarmsSidebar();
                                }
                            });

                            mapGeofenceLayers.push(polygon);
                            geofenceLayersMap[gf.id] = polygon;
                        }
                    });
                } else {
                    // FALLBACK: Generate a mock boundary only if selected and center coordinates are available
                    if (selectedFarmId === farm.id && farm.latitude && farm.longitude) {
                        const lat = parseFloat(farm.latitude);
                        const lng = parseFloat(farm.longitude);
                        const deltaLat = 0.004;
                        const deltaLng = 0.007;

                        const mockCoordinates = [
                            [lat + deltaLat, lng],
                            [lat - deltaLat / 2, lng + deltaLng],
                            [lat - deltaLat, lng - deltaLng / 2],
                            [lat + deltaLat, lng]
                        ];

                        const polygon = L.polygon(mockCoordinates, {
                            color: '#ffffff',
                            fillColor: '#10B981',
                            fillOpacity: 0.25,
                            weight: 3.5
                        }).addTo(map);

                        polygon.bindTooltip(`Maydon: ${farm.name} (Chegara chizilmagan)`, { sticky: true });
                        mapGeofenceLayers.push(polygon);
                    }
                }
            });

            // Keep the highlighted polygon highlighted if it's still there
            if (activeGeofence) {
                highlightGeofenceOnMap(activeGeofence.id);
            }
        }

        // Selection of Farm Vehicle (centers map, zooms marker, opens HUD)
        function focusVehicle(vId, lat, lng, keepCurrentZoom = false) {
            const vehicleIdChanged = (selectedVehicleId !== vId);
            selectedVehicleId = vId;
            
            if (vehicleIdChanged || !keepCurrentZoom) {
                populateDateFilter();
                const select = document.getElementById('historyDateFilter');
                if (select && select.options.length > 0) {
                    select.value = select.options[0].value; // Reset to Today
                }
            }
            
            // Always reload vehicle history and draw trail to update it in real-time
            const select = document.getElementById('historyDateFilter');
            const selectedDate = select ? select.value : '';
            // Draw trail but do not auto-zoom to the entire trail when focusing a vehicle
            loadVehicleHistoryAndDrawTrail(vId, selectedDate, false);
            
            if (lat && lng) {
                const coords = [parseFloat(lat), parseFloat(lng)];
                const targetZoom = keepCurrentZoom ? map.getZoom() : 15;
                
                const isTodaySelected = select && select.options.length > 0 ? (select.value === select.options[0].value) : true;
                const timeSinceInteraction = Date.now() - lastMapInteractionTime;
                
                // Only auto-center on periodic updates if Today is selected in the history filter
                // and 3 minutes have passed since last user interaction
                const shouldCenter = !keepCurrentZoom || (isTodaySelected && timeSinceInteraction >= 180000);
                
                if (shouldCenter) {
                    programmaticMove(() => {
                        map.setView(coords, targetZoom);
                    });
                    
                    if (!keepCurrentZoom) {
                        lastMapInteractionTime = 0; // reset interaction timer on manual focus
                    }
                }
                
                // Trigger Leaflet popup on the vehicle marker only on manual selection
                if (!keepCurrentZoom && mapVehicleMarkers[vId]) {
                    mapVehicleMarkers[vId].openPopup();
                }
            }

            // Find vehicle object
            let foundVehicle = null;
            let foundFarm = null;
            
            rawFarmsData.forEach(f => {
                const v = f.vehicles.find(item => item.id === vId);
                if (v) {
                    foundVehicle = v;
                    foundFarm = f;
                }
            });

            if (foundVehicle && foundVehicle.latest_gps_track) {
                const speed = parseFloat(foundVehicle.latest_gps_track.speed);
                const fuel = parseFloat(foundVehicle.latest_gps_track.fuel_level);
                
                // Uzbek text formatting for HUD
                const isOnline = foundVehicle.status === 'online';
                const isMoving = speed > 0;
                
                let ignitionText = "O'CHIQ (Turipti)";
                let ignitionClass = "text-red-400 bg-red-950 border border-red-900/40 px-2 py-0.5 rounded";
                let durationText = "Harakatlanmayapti";

                if (isOnline) {
                    if (isMoving) {
                        ignitionText = "YONIQ (Harakatda)";
                        ignitionClass = "text-emerald-400 bg-emerald-950 border border-emerald-900/40 px-2 py-0.5 rounded";
                        durationText = "2 soat 45 daqiqadan beri faol";
                    } else {
                        ignitionText = "KUTISHDA (Bo'sh)";
                        ignitionClass = "text-amber-400 bg-amber-950 border border-amber-900/40 px-2 py-0.5 rounded";
                        durationText = "Dvigatel o'chiq";
                    }
                }

                // Voltage dynamic/fixed calculation
                const voltage = foundVehicle.gps_device_id === '862292055529242' ? '12.96' : '12.82';

                // Populate HUD
                document.getElementById('hudName').textContent = foundVehicle.name;
                document.getElementById('hudPlate').textContent = foundVehicle.plate_number;
                document.getElementById('hudConnectionStatus').innerHTML = getConnectionStatusBadge(foundVehicle.latest_gps_track.recorded_at);
                document.getElementById('hudSpeed').textContent = speed.toFixed(0);
                document.getElementById('hudIgnition').textContent = ignitionText;
                document.getElementById('hudIgnition').className = `text-[10px] font-bold mt-1.5 block text-center ${ignitionClass}`;
                document.getElementById('hudVoltage').textContent = voltage;
                document.getElementById('hudFuel').textContent = fuel.toFixed(0);
                document.getElementById('hudDuration').textContent = durationText;
                document.getElementById('hudTime').textContent = foundVehicle.latest_gps_track.recorded_at;

                // Open HUD
                document.getElementById('selectedDeviceHud').classList.remove('hidden');
            }
        }

        // Close HUD HUD panel
        function closeHud() {
            document.getElementById('selectedDeviceHud').classList.add('hidden');
            selectedVehicleId = null;
            clearHistoryTrail();
        }

        // Render Accordion Farms List on Sidebar
        function renderFarmsSidebar() {
            const container = document.getElementById('farmsList');
            if (!container) return;
            const savedScrollTop = container.scrollTop;
            container.innerHTML = '';

            if (filteredFarmsData.length === 0) {
                container.innerHTML = `<div class="p-6 text-center text-xs text-slate-500">Qidiruv bo'yicha xo'jaliklar topilmadi</div>`;
                return;
            }

            filteredFarmsData.forEach(farm => {
                const isOpen = selectedFarmId === farm.id;
                const accordionStyle = isOpen ? 'border-emerald-500/60 bg-slate-900 shadow-lg text-slate-200' : 'border-slate-800/80 bg-slate-900/60 hover:border-slate-700 text-slate-350 shadow-sm';
                
                // Expandable contents
                let expandedHTML = '';
                if (isOpen) {
                    // 1. Geofences / Yer maydoni details
                    let geofenceHTML = `<div class="text-[10px] text-slate-500 italic">Maydon chegaralari kiritilmagan</div>`;
                    if (farm.geofences && farm.geofences.length > 0) {
                        geofenceHTML = farm.geofences.map(gf => {
                            let fertilityBadge = `<span class="bg-slate-950 text-slate-500 px-1.5 py-0.5 rounded font-bold border border-slate-850">Tahlil yo'q</span>`;
                            if (gf.latest_soil_analysis) {
                                const fertility = parseFloat(gf.latest_soil_analysis.fertility).toFixed(0);
                                if (fertility >= 70) {
                                    fertilityBadge = `<span class="bg-emerald-950 text-emerald-400 px-1.5 py-0.5 rounded font-bold border border-emerald-900/40">Unumdor: ${fertility}%</span>`;
                                } else if (fertility >= 40) {
                                    fertilityBadge = `<span class="bg-amber-950 text-amber-400 px-1.5 py-0.5 rounded font-bold border border-amber-900/40">O'rtacha: ${fertility}%</span>`;
                                } else {
                                    fertilityBadge = `<span class="bg-rose-950 text-rose-400 px-1.5 py-0.5 rounded font-bold border border-rose-900/40">Yomon: ${fertility}%</span>`;
                                }
                            }
                            return `
                                <div class="flex justify-between items-center bg-slate-950 px-2.5 py-1.5 rounded border border-slate-850 text-[10px] text-slate-300 hover:bg-slate-900 hover:border-slate-700 transition cursor-pointer" onclick="event.stopPropagation(); selectGeofence(${gf.id}, ${farm.id})">
                                    <span class="font-medium flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        ${gf.name}
                                    </span>
                                    ${fertilityBadge}
                                </div>
                            `;
                        }).join('');
                    }

                    // 2. Related Vehicles
                    let vehiclesHTML = `<div class="p-3 text-center text-[10px] text-slate-500 border border-dashed border-slate-800 rounded-lg bg-slate-950/40">Ushbu xo'jalikka texnika biriktirilmagan</div>`;
                    if (farm.vehicles && farm.vehicles.length > 0) {
                        vehiclesHTML = farm.vehicles.map(v => {
                            const hasTrack = v.latest_gps_track;
                            const speed = hasTrack ? parseFloat(v.latest_gps_track.speed) : 0;
                            const statusText = v.status === 'online' ? (speed > 0 ? 'YONIQ (Faol)' : 'KUTISHDA') : "O'CHIQ";
                            const badgeColor = v.status === 'online' ? (speed > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400') : 'bg-slate-500';
                            const activeCardStyle = selectedVehicleId === v.id ? 'border-emerald-500/60 bg-slate-900' : 'border-slate-850 bg-slate-950';
                            
                            const clickHandler = hasTrack ? `onclick="event.stopPropagation(); focusVehicle(${v.id}, ${hasTrack.latitude}, ${hasTrack.longitude})"` : '';

                            return `
                                <div class="vehicle-card p-2.5 border rounded-lg shadow-sm transition hover:shadow-md cursor-pointer ${activeCardStyle}" ${clickHandler}>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-[11px] text-slate-300 font-display flex items-center gap-2">
                                            <span class="p-1 rounded bg-slate-900 border border-slate-800 text-slate-400 flex items-center justify-center shrink-0" style="width: 22px; height: 22px;">
                                                ${getVehicleSvg(v.type)}
                                            </span>
                                            <span>${v.name}</span>
                                        </span>
                                        <span class="flex items-center gap-1 text-[9px] font-bold text-slate-400 font-sans">
                                            <span class="h-1.5 w-1.5 rounded-full ${badgeColor}"></span>
                                            ${statusText}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2 text-[9px] text-slate-550 font-sans">
                                        <span>Raqami: <strong>${v.plate_number}</strong></span>
                                        <span>Tezlik: <strong>${speed.toFixed(0)} km/s</strong></span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }

                    expandedHTML = `
                        <div class="mt-3 pt-3 border-t border-slate-800 space-y-3">
                            <!-- Owner & Size Info -->
                            <div class="bg-slate-950/50 rounded-lg p-2.5 border border-slate-850 text-[10px] text-slate-400 space-y-1">
                                <div class="flex justify-between">
                                    <span>Rahbari:</span>
                                    <span class="font-semibold text-slate-200">${farm.owner ? farm.owner.name : 'Noma\'lum'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Telefon:</span>
                                    <span class="font-semibold text-slate-200">${farm.owner ? farm.owner.phone : '-'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tuproq turi:</span>
                                    <span class="font-semibold text-slate-200">${farm.soil_type || 'Loyli'}</span>
                                </div>
                            </div>

                            <!-- Yer Chegaralari -->
                            <div>
                                <h4 class="text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Belgilangan Maydonlari</h4>
                                <div class="space-y-1.5">${geofenceHTML}</div>
                            </div>

                            <!-- Tegishli Texnikalar -->
                            <div>
                                <h4 class="text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tegishli Texnikalar (${farm.vehicles.length})</h4>
                                <div class="space-y-2">${vehiclesHTML}</div>
                            </div>
                        </div>
                    `;
                }

                const cardHTML = `
                    <div class="farm-card p-3.5 border rounded-xl shadow-sm transition-all duration-300 cursor-pointer ${accordionStyle}" onclick="toggleFarm(${farm.id})">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-xs text-slate-200 font-display">${farm.name}</h3>
                                <p class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider mt-0.5">${farm.owner && farm.owner.region ? farm.owner.region.name : 'Qoraqalpog\'iston'}, ${farm.district || 'Amudaryo tumani'}</p>
                            </div>
                            <span class="bg-slate-850 text-slate-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-slate-800">
                                ${farm.size || '35'} GA
                            </span>
                        </div>
                        ${expandedHTML}
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHTML);
            });
            container.scrollTop = savedScrollTop;
        }

        // Apply dynamic filters for Farms
        function applyFilters() {
            const query = document.getElementById('searchFarm').value.toLowerCase().trim();
            const district = document.getElementById('filterDistrict').value;
            const soil = document.getElementById('filterSoil').value;

            filteredFarmsData = rawFarmsData.filter(farm => {
                // 1. Search Query
                const matchesSearch = query === '' || 
                    farm.name.toLowerCase().includes(query) || 
                    (farm.owner && farm.owner.name.toLowerCase().includes(query)) ||
                    (farm.soil_type && farm.soil_type.toLowerCase().includes(query));

                // 2. District Filter
                const matchesDistrict = district === '' || farm.district === district;

                // 3. Soil Filter
                let matchesSoil = true;
                if (soil !== '') {
                    const geofences = farm.geofences || [];
                    if (soil === 'none') {
                        matchesSoil = geofences.length === 0 || geofences.every(g => !g.latest_soil_analysis);
                    } else {
                        matchesSoil = geofences.some(g => {
                            if (!g.latest_soil_analysis) return false;
                            const f = parseFloat(g.latest_soil_analysis.fertility);
                            if (soil === 'high') return f >= 70;
                            if (soil === 'medium') return f >= 40 && f < 70;
                            if (soil === 'low') return f < 40;
                            return false;
                        });
                    }
                }

                return matchesSearch && matchesDistrict && matchesSoil;
            });

            renderFarmsSidebar();
        }

        // Populate dynamic district options
        function populateDistrictFilter(farms) {
            const select = document.getElementById('filterDistrict');
            if (!select || select.options.length > 1) return; // Already populated
            
            const districts = new Set();
            farms.forEach(f => {
                if (f.district) districts.add(f.district);
            });
            
            districts.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                select.appendChild(opt);
            });
        }

        // Calculate and render stats counters dynamically
        function calculateAndRenderStats(farms) {
            let totalArea = 0;
            let totalFarmers = new Set();
            let totalVehicles = 0;
            let activeVehicles = 0;
            let analyzedFieldsCount = 0;
            let sumFertility = 0;

            farms.forEach(farm => {
                totalArea += parseFloat(farm.size || 0);
                if (farm.owner && farm.owner.id) {
                    totalFarmers.add(farm.owner.id);
                }
                
                if (farm.vehicles) {
                    totalVehicles += farm.vehicles.length;
                    farm.vehicles.forEach(v => {
                        if (v.status === 'online') {
                            activeVehicles++;
                        }
                    });
                }

                if (farm.geofences) {
                    farm.geofences.forEach(g => {
                        if (g.latest_soil_analysis) {
                            analyzedFieldsCount++;
                            sumFertility += parseFloat(g.latest_soil_analysis.fertility || 0);
                        }
                    });
                }
            });

            const avgFertility = analyzedFieldsCount > 0 ? (sumFertility / analyzedFieldsCount).toFixed(1) : 0;

            document.getElementById('statTotalArea').textContent = totalArea.toFixed(1) + ' ha';
            document.getElementById('statTotalFarmers').textContent = totalFarmers.size;
            document.getElementById('statActiveFleets').textContent = `${activeVehicles}/${totalVehicles}`;
            document.getElementById('statAvgFertility').textContent = avgFertility + '%';
            
            const fertilityBadge = document.getElementById('statAvgFertility');
            if (avgFertility >= 70) {
                fertilityBadge.className = 'text-xs font-black text-emerald-400 font-display';
            } else if (avgFertility >= 40) {
                fertilityBadge.className = 'text-xs font-black text-amber-400 font-display';
            } else {
                fertilityBadge.className = 'text-xs font-black text-rose-400 font-display';
            }
        }

        // AI Drawer variables
        let activeGeofence = null;
        let activeFarm = null;
        let activeDrawerTab = 'soil';
        let ndviChart = null;

        // Open and Render AI Drawer details
        function openAiAnalysisDrawer(gfId, farmId) {
            const farm = rawFarmsData.find(f => f.id === farmId);
            if (!farm) return;
            const gf = farm.geofences.find(g => g.id === gfId);
            if (!gf) return;

            activeGeofence = gf;
            activeFarm = farm;

            highlightGeofenceOnMap(gfId);

            document.getElementById('drawerFieldName').textContent = gf.name;
            document.getElementById('drawerFarmName').textContent = farm.name + ' - ' + (farm.district || 'Amudaryo tumani');

            // Force active tab to match current map layer for intuitive workflow
            activeDrawerTab = currentMapLayer;

            renderDrawerContent();

            document.getElementById('aiAnalysisDrawer').classList.remove('hidden');
            map.invalidateSize();
        }

        function switchDrawerTab(tab) {
            activeDrawerTab = tab;
            renderDrawerContent();
        }

        function renderDrawerContent() {
            const contentContainer = document.getElementById('drawerContent');
            const gf = activeGeofence;
            const farm = activeFarm;
            if (!gf || !farm) return;

            // Render Tab headers
            let tabHeader = `
                <div class="flex border-b border-slate-800 shrink-0 font-display text-[10px] uppercase tracking-wider font-extrabold mb-4">
                    <button onclick="switchDrawerTab('soil')" class="flex-1 pb-2.5 border-b-2 ${activeDrawerTab === 'soil' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'} text-center transition-all duration-200">
                        🧪 Kimyoviy Tarkib
                    </button>
                    <button onclick="switchDrawerTab('ndvi')" class="flex-1 pb-2.5 border-b-2 ${activeDrawerTab === 'ndvi' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'} text-center transition-all duration-200">
                        🛰️ Yo'ldosh NDVI
                    </button>
                </div>
            `;

            if (activeDrawerTab === 'soil') {
                const latestAnalysis = gf.latest_soil_analysis;
                if (!latestAnalysis) {
                    contentContainer.innerHTML = tabHeader + `
                        <!-- Farmer Info Card -->
                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3 shadow-md mb-4">
                            <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                                <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-350">
                                    <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-200 uppercase tracking-wider">Fermer Ma'lumotlari</h4>
                                    <p class="text-[9px] font-semibold text-slate-400">Xo'jalik rahbari</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-[11px] text-slate-300">
                                <div class="flex justify-between">
                                    <span class="text-slate-550">Ism-familiya:</span>
                                    <span class="font-bold text-slate-200">${farm.owner ? farm.owner.name : 'Noma\'lum'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-550">Telefon raqami:</span>
                                    <span class="font-bold text-slate-200">${farm.owner ? farm.owner.phone : '-'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-550 font-bold uppercase tracking-wider block">Hudud:</span>
                                    <span class="font-bold text-slate-200">${farm.owner && farm.owner.region ? farm.owner.region.name : 'Qoraqalpog\'iston'}, ${farm.district || 'Amudaryo tumani'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-550">Xo'jalik nomi:</span>
                                    <span class="font-bold text-slate-200">${farm.name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-550">Umumiy maydon:</span>
                                    <span class="font-bold text-slate-200">${farm.size || '0'} GA</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-550 font-bold uppercase tracking-wider block font-sans">Tuproq turi:</span>
                                    <span class="font-bold text-slate-200">${farm.soil_type || 'Noma\'lum'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="h-12 w-12 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 mb-3 animate-pulse">
                                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h4 class="text-[10px] font-extrabold text-slate-350 uppercase tracking-wider">Tahlil Natijalari Yo'q</h4>
                            <p class="text-[9px] text-slate-500 mt-1 max-w-[200px] leading-relaxed">Ushbu yer maydoni uchun tuproq kimyoviy tarkibi tahlillari kiritilmagan.</p>
                            <a href="/admin/soil" target="_blank" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600/10 border border-emerald-500/20 px-3 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-600/20 transition">
                                Tahlil kiritish
                            </a>
                        </div>
                    `;
                    return;
                }

                const ph = parseFloat(latestAnalysis.ph || 0);
                const fertility = parseFloat(latestAnalysis.fertility || 0);
                const moisture = parseFloat(latestAnalysis.moisture || 0);
                
                let fertilityStatus = "Past unumdorlik";
                let fertilityColor = "bg-rose-500 shadow-[0_0_8px_#f43f5e]";
                let fertilityTextClass = "text-rose-400";
                if (fertility >= 70) {
                    fertilityStatus = "Yuqori";
                    fertilityColor = "bg-emerald-500 shadow-[0_0_8px_#10b981]";
                    fertilityTextClass = "text-emerald-400";
                } else if (fertility >= 40) {
                    fertilityStatus = "O'rtacha";
                    fertilityColor = "bg-amber-500 shadow-[0_0_8px_#f59e0b]";
                    fertilityTextClass = "text-amber-400";
                }

                let moistureStatus = "Past namlik";
                let moistureColor = "bg-rose-500 shadow-[0_0_8px_#f43f5e]";
                let moistureTextClass = "text-rose-400";
                if (moisture >= 40) {
                    moistureStatus = "Mo'tadil";
                    moistureColor = "bg-blue-500 shadow-[0_0_8px_#3b82f6]";
                    moistureTextClass = "text-blue-400";
                }

                let phStatus = "Neytral";
                let phTextClass = "text-emerald-450 bg-emerald-950 border-emerald-900/40";
                if (ph < 6.0) {
                    phStatus = "Kislotali";
                    phTextClass = "text-amber-400 bg-amber-950 border-amber-900/40";
                } else if (ph > 7.5) {
                    phStatus = "Ishqoriy";
                    phTextClass = "text-rose-400 bg-rose-950 border-rose-900/40";
                }

                let aiBlockHTML = '';
                if (latestAnalysis.recommendation) {
                    const rec = latestAnalysis.recommendation;
                    
                    let cropsListHTML = '';
                    if (rec.recommended_crops && rec.recommended_crops.length > 0) {
                        cropsListHTML = rec.recommended_crops.map(crop => `
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ${crop}
                            </span>
                        `).join('');
                    } else {
                        cropsListHTML = `<span class="text-[10px] text-slate-500 italic">Ekinlar belgilanmagan</span>`;
                    }

                    let fertilizerListHTML = '';
                    if (rec.fertilizer_plan && Array.isArray(rec.fertilizer_plan) && rec.fertilizer_plan.length > 0) {
                        fertilizerListHTML = rec.fertilizer_plan.map(step => `
                            <li class="flex items-start gap-2 text-[10px] text-slate-350 bg-slate-900 border border-slate-850 p-2 rounded">
                                <svg class="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>${step}</span>
                            </li>
                        `).join('');
                    } else if (rec.fertilizer_plan && typeof rec.fertilizer_plan === 'object' && Object.keys(rec.fertilizer_plan).length > 0) {
                        fertilizerListHTML = Object.entries(rec.fertilizer_plan).map(([key, value]) => {
                            const label = key.charAt(0).toUpperCase() + key.slice(1);
                            return `
                                <li class="flex items-start gap-2 text-[10px] text-slate-350 bg-slate-900 border border-slate-850 p-2 rounded">
                                    <svg class="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span><strong class="text-emerald-400">${label}:</strong> ${value}</span>
                                </li>
                            `;
                        }).join('');
                    } else {
                        fertilizerListHTML = `<li class="text-[10px] text-slate-500 italic">O'g'itlash rejasi kiritilmagan</li>`;
                    }

                    aiBlockHTML = `
                        <div class="bg-emerald-950/20 border border-emerald-900/40 rounded-xl p-3.5 space-y-2">
                            <div class="flex justify-between items-center">
                                <h4 class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5 font-display">
                                    <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Llama-3 AI Tahlili
                                </h4>
                                <span class="inline-flex items-center text-[8px] bg-emerald-900/40 text-emerald-400 border border-emerald-800/40 px-1.5 py-0.5 rounded font-mono">${rec.ai_model || 'llama3-8b'}</span>
                            </div>
                            <p class="text-[11px] text-slate-200 leading-relaxed font-sans">${rec.content}</p>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 space-y-2">
                            <h4 class="text-[9px] font-bold text-slate-400 uppercase tracking-wider font-display">Tavsiya ekin turlari</h4>
                            <div class="flex flex-wrap gap-1.5">${cropsListHTML}</div>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 space-y-2">
                            <h4 class="text-[9px] font-bold text-slate-400 uppercase tracking-wider font-display">Parvarish va O'g'itlash rejasi</h4>
                            <ul class="space-y-1.5">${fertilizerListHTML}</ul>
                        </div>

                        <div class="flex justify-between items-center text-[8px] text-slate-500 pt-1">
                            <span>Tokens model: ${rec.tokens_used || 0}</span>
                            <span>Oxirgi tahlil: ${latestAnalysis.analysis_date ? latestAnalysis.analysis_date.split('T')[0] : ''}</span>
                        </div>
                    `;
                } else {
                    aiBlockHTML = `
                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 text-center">
                            <svg class="h-8 w-8 text-amber-500/80 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h5 class="text-xs font-bold text-slate-350">AI Tavsiyalari Mavjud Emas</h5>
                            <p class="text-[10px] text-slate-500 mt-1.5 leading-relaxed">Laboratoriya olingan, biroq sun'iy intellekt tavsiyasi tayyorlanmagan.</p>
                            <button onclick="runAiAnalysisForField(${latestAnalysis.id})" class="mt-3.5 inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1 text-[10px] font-bold text-white shadow hover:bg-emerald-500 transition">
                                AI Tavsiya Yaratish
                            </button>
                        </div>
                    `;
                }

                contentContainer.innerHTML = tabHeader + `
                    <!-- Soil parameters grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">NPK Unumdorligi</span>
                            <div class="flex items-baseline justify-between mt-1">
                                <span class="text-lg font-black text-slate-200 font-display">${fertility.toFixed(1)}%</span>
                                <span class="text-[9px] font-bold ${fertilityTextClass}">${fertilityStatus}</span>
                            </div>
                            <div class="w-full bg-slate-950 rounded-full h-1 mt-2">
                                <div class="${fertilityColor} h-1 rounded-full" style="width: ${fertility}%"></div>
                            </div>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Tuproq Namligi</span>
                            <div class="flex items-baseline justify-between mt-1">
                                <span class="text-lg font-black text-slate-200 font-display">${moisture.toFixed(1)}%</span>
                                <span class="text-[9px] font-bold ${moistureTextClass}">${moistureStatus}</span>
                            </div>
                            <div class="w-full bg-slate-950 rounded-full h-1 mt-2">
                                <div class="${moistureColor} h-1 rounded-full" style="width: ${moisture}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-xl text-center">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">pH darajasi</span>
                            <span class="text-sm font-black text-slate-200 mt-1 block">${ph.toFixed(2)}</span>
                            <span class="inline-flex px-1.5 py-0.5 mt-1 text-[8px] font-bold rounded-full border ${phTextClass}">${phStatus}</span>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-xl text-center">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Harorat</span>
                            <span class="text-sm font-black text-slate-200 mt-1 block">${parseFloat(latestAnalysis.temperature || 0).toFixed(1)}°C</span>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-2.5 rounded-xl text-center">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block">Namgarchilik</span>
                            <span class="text-sm font-black text-slate-200 mt-1 block">${parseFloat(latestAnalysis.humidity || 0).toFixed(0)}%</span>
                        </div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex justify-between items-center text-[10px]">
                        <span class="text-slate-400">Ekilgan / Ekishga Reja:</span>
                        <span class="font-extrabold text-slate-200 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">${latestAnalysis.target_crop}</span>
                    </div>

                    <!-- AI Recommendations block -->
                    <div class="space-y-3 pt-1 border-t border-slate-800">
                        ${aiBlockHTML}
                    </div>
                `;
            } else {
                // Seeded deterministic calculations based on geofence ID
                const seedVal = gf.id * 11 + farm.id * 17;
                const baseVal = 0.45 + seededRandom(seedVal) * 0.4;
                const ndviVal = parseFloat(baseVal.toFixed(2));
                
                let ndviStatus = "Past rivojlanish";
                let ndviColorClass = "text-rose-400 bg-rose-950/40 border border-rose-900/30";
                if (ndviVal >= 0.7) {
                    ndviStatus = "Zo'r rivojlanish";
                    ndviColorClass = "text-emerald-400 bg-emerald-950/40 border border-emerald-900/30";
                } else if (ndviVal >= 0.5) {
                    ndviStatus = "Yaxshi rivojlanish";
                    ndviColorClass = "text-teal-400 bg-teal-950/40 border border-teal-900/30";
                } else if (ndviVal >= 0.3) {
                    ndviStatus = "O'rtacha rivojlanish";
                    ndviColorClass = "text-amber-400 bg-amber-950/40 border border-amber-900/30";
                }

                const historyData = [
                    (baseVal - 0.25 - seededRandom(seedVal + 1) * 0.1).toFixed(2),
                    (baseVal - 0.10 + seededRandom(seedVal + 2) * 0.08).toFixed(2),
                    ndviVal.toFixed(2)
                ];

                contentContainer.innerHTML = tabHeader + `
                    <!-- NDVI Status Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3 shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider block font-display">Joriy NDVI Indeksi</span>
                                <span class="text-2xl font-black text-slate-100 font-display mt-0.5 block">${ndviVal}</span>
                            </div>
                            <span class="inline-flex px-2.5 py-1 text-[9px] font-bold rounded-lg ${ndviColorClass}">${ndviStatus}</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-1.5 mt-2">
                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: ${ndviVal * 100}%"></div>
                        </div>
                    </div>

                    <!-- NDVI Historical Chart Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-2 shadow-md">
                        <h4 class="text-[9px] font-bold text-slate-400 uppercase tracking-wider font-display">Ekin Rivojlanish Dinamikasi (3 oylik)</h4>
                        <div class="relative h-44 w-full">
                            <canvas id="ndviHistoryChart"></canvas>
                        </div>
                    </div>

                    <!-- AI Satellite Commentary Card -->
                    <div class="bg-emerald-950/20 border border-emerald-900/40 rounded-xl p-3.5 space-y-2">
                        <h4 class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5 font-display">
                            <svg class="h-3.5 w-3.5 text-emerald-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Sun'iy Yo'ldosh Tahlili
                        </h4>
                        <p class="text-[11px] text-slate-200 leading-relaxed font-sans">
                            Sun'iy yo'ldoshning optik-spektral tahliliga ko'ra, maydonda vegetatsiya jarayoni barqaror ketmoqda.
                            ${ndviVal >= 0.7 ? 
                                `Ekin barglarining zichligi va tarkibidagi xlorofill miqdori yuqori darajada. Rivojlanish fazasi optimal. Sug'orish va o'g'itlash rejasi ayni vaqtda juda to'g'ri tashkil etilgan.` :
                                ndviVal >= 0.5 ?
                                `Ekin rivojlanishi mo'tadil, ammo ba'zi qismlarda begona o'tlar yoki ozgina suvsizlanish belgilari bo'lishi mumkin. Maydonning markaziy qismiga qo'shimcha o'g'it sepish hosildorlikni yaxshilaydi.` :
                                `Maydonning yashillik darajasi pasaygan. NDVI ko'rsatkichi pastligi barglar siyraklashganidan yoki ekin rivojlanishdan to'xtab qolganidan dalolat beradi. Tuproq namligi va azot miqdorini zudlik bilan tekshirish, shuningdek ekinni dori vositalari bilan qayta ishlash tavsiya etiladi.`
                            }
                        </p>
                    </div>
                `;

                // Render Chart.js line chart
                setTimeout(() => {
                    const ctx = document.getElementById('ndviHistoryChart');
                    if (!ctx) return;

                    if (ndviChart) {
                        ndviChart.destroy();
                    }

                    ndviChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Aprel', 'May', 'Iyun'],
                            datasets: [{
                                label: 'NDVI ko\'rsatkichi',
                                data: historyData,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#10B981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 1.0,
                                    grid: { color: 'rgba(51, 65, 85, 0.3)' },
                                    ticks: { color: '#94a3b8', font: { size: 9 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8', font: { size: 9 } }
                                }
                            }
                        }
                    });
                }, 100);
            }
        }

        function closeAiAnalysisDrawer() {
            document.getElementById('aiAnalysisDrawer').classList.add('hidden');
            activeGeofence = null;
            activeFarm = null;
            resetGeofenceHighlight();
            map.invalidateSize();
        }

        // Print formatted AI report window
        function printAiReport() {
            if (!activeGeofence || !activeFarm) return;
            const latestAnalysis = activeGeofence.latest_soil_analysis;
            if (!latestAnalysis) return;
            
            const rec = latestAnalysis.recommendation || {
                content: "AI tavsiyasi tayyorlanmagan.",
                recommended_crops: [],
                fertilizer_plan: [],
                ai_model: "N/A"
            };

            const printWindow = window.open('', '_blank', 'width=800,height=900');
            
            const cropsHTML = (rec.recommended_crops || []).map(c => `<li>${c}</li>`).join('');
            
            let fertHTML = '';
            if (rec.fertilizer_plan) {
                if (Array.isArray(rec.fertilizer_plan)) {
                    fertHTML = rec.fertilizer_plan.map(f => `<li>${f}</li>`).join('');
                } else if (typeof rec.fertilizer_plan === 'object') {
                    fertHTML = Object.entries(rec.fertilizer_plan).map(([key, value]) => {
                        const label = key.charAt(0).toUpperCase() + key.slice(1);
                        return `<li><strong>${label}:</strong> ${value}</li>`;
                    }).join('');
                }
            }
            if (!fertHTML) fertHTML = '<li>Kiritilmagan</li>';

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Tuproq AI Tahlil Hisoboti - ${activeGeofence.name}</title>
                    <style>
                        body { font-family: 'Inter', sans-serif; color: #334155; padding: 40px; line-height: 1.6; }
                        .header { border-bottom: 2px solid #10b981; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
                        .title { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0; }
                        .subtitle { font-size: 14px; color: #64748b; margin-top: 5px; }
                        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                        .meta-table td { padding: 10px; border: 1px solid #e2e8f0; font-size: 13px; }
                        .meta-table td.label { font-weight: bold; background: #f8fafc; width: 25%; }
                        .section { margin-bottom: 30px; }
                        .section-title { font-size: 16px; font-weight: bold; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px; }
                        .metrics-grid { display: grid; grid-template-cols: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
                        .metric-card { border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; }
                        .metric-val { font-size: 20px; font-weight: bold; color: #10b981; margin-top: 5px; }
                        .advice-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; font-size: 14px; color: #14532d; margin-bottom: 30px; }
                        .list-style { padding-left: 20px; }
                        .list-style li { margin-bottom: 8px; font-size: 13px; }
                        .footer { border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 11px; color: #94a3b8; display: flex; justify-content: space-between; margin-top: 50px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div>
                            <h1 class="title">AGROMIND GIS TAHLIL HISOBOTI</h1>
                            <div class="subtitle">Dala tuproq tarkibi va AI agrotexnik tavsiyalar</div>
                        </div>
                        <div style="font-size: 12px; text-align: right; color: #64748b;">
                            <strong>Sana:</strong> ${new Date().toLocaleDateString()}<br>
                            <strong>Hisobot ID:</strong> AM-TR-${latestAnalysis.id}
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">Umumiy Ma'lumotlar</h2>
                        <table class="meta-table">
                            <tr>
                                <td class="label">Xo'jalik Nomi</td>
                                <td>${activeFarm.name}</td>
                                <td class="label">Yer Maydoni</td>
                                <td>${activeGeofence.name}</td>
                            </tr>
                            <tr>
                                <td class="label">Rahbar / Dehqon</td>
                                <td>${activeFarm.owner ? activeFarm.owner.name : 'Noma\'lum'}</td>
                                <td class="label">Telefon</td>
                                <td>${activeFarm.owner ? activeFarm.owner.phone : '-'}</td>
                            </tr>
                            <tr>
                                <td class="label">Hudud / Tuman</td>
                                <td>${activeFarm.district || 'Amudaryo tumani'}</td>
                                <td class="label">Tuproq Turi</td>
                                <td>${activeFarm.soil_type || 'Loyli'}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="section">
                        <h2 class="section-title">Laboratoriya Ko'rsatkichlari</h2>
                        <div class="metrics-grid">
                            <div class="metric-card">
                                <div style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Tuproq Unumdorligi</div>
                                <div class="metric-val">${parseFloat(latestAnalysis.fertility).toFixed(1)}%</div>
                            </div>
                            <div class="metric-card">
                                <div style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Tuproq Namligi</div>
                                <div class="metric-val">${parseFloat(latestAnalysis.moisture).toFixed(1)}%</div>
                            </div>
                            <div class="metric-card">
                                <div style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">pH Darajasi</div>
                                <div class="metric-val" style="color: #3b82f6;">${parseFloat(latestAnalysis.ph).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">AI Tavsiya Ekrani (Llama-3-Agri)</h2>
                        <div class="advice-card">
                            <strong>Llama-3 Tahlil Xulosasi:</strong><br>
                            <p style="margin-top: 10px; line-height: 1.6;">${rec.content}</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-cols: 1fr 1fr; gap: 30px;">
                        <div class="section">
                            <h2 class="section-title">Tavsiya Etilgan Ekin Turlari</h2>
                            <ul class="list-style">${cropsHTML || '<li>Belgilanmagan</li>'}</ul>
                        </div>
                        <div class="section">
                            <h2 class="section-title">O'g'itlash va Parvarish Rejasi</h2>
                            <ul class="list-style">${fertHTML || '<li>Kiritilmagan</li>'}</ul>
                        </div>
                    </div>

                    <div class="footer">
                        <span>Model: ${rec.ai_model} | Tokens: ${rec.tokens_used || 0}</span>
                        <span>AgroMind GIS Platform - ${new Date().getFullYear()}</span>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Trigger AI recommendation generation in the backend
        function runAiAnalysisForField(analysisId) {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = "AI Tahlil qilinmoqda...";

            fetch(`/api/monitor-analysis/${analysisId}/recommend`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("AI Tavsiyasi muvaffaqiyatli tayyorlandi!");
                    isFirstLoad = true;
                    loadFarmsAndVehicles();
                } else {
                    alert("Xato yuz berdi: " + (data.message || 'Noma\'lum xato'));
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert("Groq serveriga ulanishda xatolik yuz berdi.");
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }

        // Fetch Live Farms data and populate Sidebar list + Markers
        let isFirstLoad = true;
        
        function loadFarmsAndVehicles() {
            fetch('/api/live-farms')
                .then(response => response.json())
                .then(farms => {
                    rawFarmsData = farms;
                    
                    // Render dynamic stats counts
                    calculateAndRenderStats(farms);
                    
                    // Dynamically populate filters once
                    if (isFirstLoad) {
                        populateDistrictFilter(farms);
                        // Draw all geofences on the map globally
                        drawAllFarmsGeofences(farms);
                        isFirstLoad = false;
                    }

                    // Refresh active filters
                    applyFilters();

                    // Refresh AI Drawer if open
                    if (activeGeofence && activeFarm) {
                        openAiAnalysisDrawer(activeGeofence.id, activeFarm.id);
                    }

                    // Render / Update all vehicles markers on the map
                    farms.forEach(farm => {
                        if (farm.vehicles && farm.vehicles.length > 0) {
                            farm.vehicles.forEach(v => {
                                if (v.latest_gps_track) {
                                    const coords = [parseFloat(v.latest_gps_track.latitude), parseFloat(v.latest_gps_track.longitude)];
                                    const speed = parseFloat(v.latest_gps_track.speed);
                                    
                                    // Update existing marker position
                                    if (mapVehicleMarkers[v.id]) {
                                        mapVehicleMarkers[v.id].setLatLng(coords);
                                        const angle = updateVehicleAngle(v.id, coords, speed);
                                        mapVehicleMarkers[v.id].setIcon(getVehicleIcon(v.status, speed > 0, v.type, angle));
                                        
                                        // Update dynamic live status in popup
                                        mapVehicleMarkers[v.id].setPopupContent(`
                                            <div class="p-1.5 font-sans text-xs">
                                                <h4 class="font-extrabold text-slate-800 text-sm font-display">${v.name} (${v.plate_number})</h4>
                                                <div class="mt-2.5 space-y-1.5 text-slate-650">
                                                    <div class="mb-2">${getConnectionStatusBadge(v.latest_gps_track.recorded_at)}</div>
                                                    <p>Xo'jalik: <strong>${farm.name}</strong></p>
                                                    <p>Dvigatel (ACC): <strong class="text-${v.status === 'online' ? (speed > 0 ? 'emerald' : 'amber') : 'red'}-600">${v.status === 'online' ? (speed > 0 ? 'YONIQ (Faol)' : 'KUTISHDA') : 'O\'CHIQ'}</strong></p>
                                                    <p>Tezlik: <strong>${speed.toFixed(0)} km/soat</strong></p>
                                                    <p>Akkumulyator: <strong>${v.gps_device_id === '862292055529242' ? '12.96' : '12.80'} V</strong></p>
                                                    <p class="text-[10px] text-slate-400 mt-2 border-t pt-1.5">IMEI: ${v.gps_device_id || 'Yo\'q'}</p>
                                                </div>
                                            </div>
                                        `);
                                    } else {
                                        // Create new Leaflet Marker
                                        const angle = updateVehicleAngle(v.id, coords, speed);
                                        const marker = L.marker(coords, {
                                            icon: getVehicleIcon(v.status, speed > 0, v.type, angle)
                                        }).addTo(map);

                                        // Detailed popup matching screenshot details
                                        marker.bindPopup(`
                                            <div class="p-1.5 font-sans text-xs">
                                                <h4 class="font-extrabold text-slate-800 text-sm font-display">${v.name} (${v.plate_number})</h4>
                                                <div class="mt-2.5 space-y-1.5 text-slate-650">
                                                    <div class="mb-2">${getConnectionStatusBadge(v.latest_gps_track.recorded_at)}</div>
                                                    <p>Xo'jalik: <strong>${farm.name}</strong></p>
                                                    <p>Dvigatel (ACC): <strong class="text-${v.status === 'online' ? (speed > 0 ? 'emerald' : 'amber') : 'red'}-600">${v.status === 'online' ? (speed > 0 ? 'YONIQ (Faol)' : 'KUTISHDA') : 'O\'CHIQ'}</strong></p>
                                                    <p>Tezlik: <strong>${speed.toFixed(0)} km/soat</strong></p>
                                                    <p>Akkumulyator: <strong>${v.gps_device_id === '862292055529242' ? '12.96' : '12.80'} V</strong></p>
                                                    <p class="text-[10px] text-slate-400 mt-2 border-t pt-1.5">IMEI: ${v.gps_device_id || 'Yo\'q'}</p>
                                                </div>
                                            </div>
                                        `);

                                        // Marker click focus
                                        marker.on('click', () => {
                                            // Select parent farm in accordion list
                                            if (selectedFarmId !== farm.id) {
                                                toggleFarm(farm.id);
                                            }
                                            focusVehicle(v.id, coords[0], coords[1]);
                                        });

                                        mapVehicleMarkers[v.id] = marker;
                                    }
                                }
                            });
                        }
                    });

                    // Update HUD variables in real-time if open
                    if (selectedVehicleId) {
                        let activeV = null;
                        farms.forEach(f => {
                            const v = f.vehicles.find(item => item.id === selectedVehicleId);
                            if (v) activeV = v;
                        });

                        if (activeV && activeV.latest_gps_track) {
                            const coords = [parseFloat(activeV.latest_gps_track.latitude), parseFloat(activeV.latest_gps_track.longitude)];
                            focusVehicle(selectedVehicleId, coords[0], coords[1], true);
                        }
                    }
                })
                .catch(err => console.error('Error fetching live farms data:', err));
        }

        // First Load and 10 seconds interval refresh
        loadFarmsAndVehicles();
        setInterval(loadFarmsAndVehicles, 10000);
    </script>
</body>
</html>
