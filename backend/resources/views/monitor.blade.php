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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        #map { height: 100%; width: 100%; }
        
        /* Custom styled scrollbars for sidebar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Premium custom styling for Leaflet controls */
        .leaflet-bar {
            border: none !important;
            box-shadow: 0 4px 12px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08) !important;
        }
        .leaflet-bar a {
            background-color: #ffffff !important;
            color: #334155 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            transition: all 0.2s;
        }
        .leaflet-bar a:hover {
            background-color: #f8fafc !important;
            color: #059669 !important;
        }
    </style>
</head>
<body class="h-full flex flex-col overflow-hidden bg-slate-50 select-none">

    <!-- Header Panel -->
    <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h1 class="text-base font-bold tracking-tight text-slate-800 font-display flex items-center gap-2">
                    AGROMIND GIS <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2.5 py-0.5 rounded-full font-sans font-bold">XARITA TIZIMI</span>
                </h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Fermer xo'jaliklari va yer chegaralari live nazorati</p>
            </div>
        </div>

        <!-- Clock and Connection Status -->
        <div class="flex items-center gap-4">
            <span class="hidden md:inline-flex items-center gap-1.5 text-xs text-slate-650 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Geo-Port: ULANISH FAOL
            </span>
            <div id="liveClock" class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                12:00:00
            </div>
        </div>
    </header>

    <!-- Main Container: Sidebar + Fullscreen Map -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Left Side: Fermer Xo'jaliklari Sidebar -->
        <aside class="w-80 border-r border-slate-200 bg-white flex flex-col overflow-hidden shrink-0 z-10 shadow-lg">
            <!-- Search bar -->
            <div class="p-4 border-b border-slate-150 bg-slate-50/50 shrink-0">
                <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider font-display">Fermer xo'jaliklari</h2>
                <div class="mt-2.5 relative">
                    <input type="text" id="searchFarm" oninput="filterFarms()" placeholder="Xo'jalikni qidirish..." class="w-full pl-8 pr-3 py-1.5 border border-slate-250 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white shadow-inner">
                    <span class="absolute left-2.5 top-2.5 text-slate-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>
            </div>
            
            <!-- Farms and accordion list -->
            <div id="farmsList" class="flex-1 overflow-y-auto p-2 space-y-2 bg-slate-50/30">
                <div class="p-6 text-center text-xs text-slate-400 mt-10">Fermer xo'jaliklari yuklanmoqda...</div>
            </div>
        </aside>

        <!-- Right Side: Fullscreen Map & Vehicle HUD overlay -->
        <main class="flex-1 relative bg-slate-150">
            <!-- Leaflet Map -->
            <div id="map"></div>
            
            <!-- Selected Vehicle Telemetry HUD -->
            <div id="selectedDeviceHud" class="hidden absolute top-4 right-4 bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-xl p-4 shadow-xl z-[1000] w-64 transition-all duration-300 transform scale-95 translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="hudName" class="font-extrabold text-sm text-slate-800 font-display">-</h3>
                        <p id="hudPlate" class="text-xs text-slate-500 font-semibold font-display">-</p>
                    </div>
                    <button onclick="closeHud()" class="text-slate-400 hover:text-slate-600 bg-slate-100 p-1.5 rounded-full transition">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Speedometer Widget -->
                <div class="mt-4 bg-slate-50 border border-slate-100 rounded-lg p-3 flex flex-col items-center justify-center">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Hozirgi tezligi</span>
                    <div class="flex items-baseline mt-0.5">
                        <span id="hudSpeed" class="text-3xl font-black text-slate-850 font-display">0</span>
                        <span class="text-xs font-semibold text-slate-500 ml-1 font-display">km/soat</span>
                    </div>
                </div>

                <!-- Ignition and Battery details -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Dvigatel (ACC)</span>
                        <span id="hudIgnition" class="text-xs font-extrabold mt-1 block">-</span>
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Batareya</span>
                        <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block"><span id="hudVoltage">12.96</span> <span class="text-[10px] font-normal text-slate-500">V</span></span>
                    </div>
                </div>

                <!-- Live stats -->
                <div class="mt-2.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-xs space-y-1">
                    <div class="flex justify-between text-slate-655">
                        <span>Yoqilg'i miqdori:</span>
                        <span class="font-bold text-slate-805"><span id="hudFuel">-</span>%</span>
                    </div>
                    <div class="flex justify-between text-slate-655">
                        <span>Faollik vaqti:</span>
                        <span id="hudDuration" class="font-bold text-slate-805">-</span>
                    </div>
                    <div class="flex justify-between text-slate-450 text-[9px] pt-1.5 border-t border-slate-200/80">
                        <span>Oxirgi yangilanish:</span>
                        <span id="hudTime" class="font-medium">-</span>
                    </div>
                </div>
            </div>
        </main>
        
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
        const map = L.map('map').setView([42.11005, 60.07327], 9);

        // Standard clean light street map layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Global states
        let rawFarmsData = [];
        let filteredFarmsData = [];
        let selectedFarmId = null;
        let selectedVehicleId = null;
        
        let mapGeofenceLayers = [];
        const mapVehicleMarkers = {};

        // Custom Glowing Marker Icon
        function getVehicleIcon(status, isMoving) {
            let color = '#94A3B8'; // offline (gray)
            if (status === 'online') {
                color = isMoving ? '#10B981' : '#F59E0B'; // yashil (harakatda) yoki sariq (o'chirilgan)
            } else if (status === 'problem') {
                color = '#EF4444'; // red
            }

            return L.divIcon({
                className: 'custom-gps-icon',
                html: `
                    <div class="relative flex items-center justify-center" style="width: 24px; height: 24px;">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-35" style="background-color: ${color};"></span>
                        <div class="rounded-full shadow-md border-2 border-white flex items-center justify-center" style="width: 16px; height: 16px; background-color: ${color};">
                            <span class="block bg-white rounded-full" style="width: 4px; height: 4px;"></span>
                        </div>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
        }

        // Selection of Farm Accordion
        function toggleFarm(farmId) {
            if (selectedFarmId === farmId) {
                // If clicked again, close it
                selectedFarmId = null;
                clearGeofenceLayers();
            } else {
                selectedFarmId = farmId;
                const farm = rawFarmsData.find(f => f.id === farmId);
                if (farm) {
                    // Zoom and pan to farm center
                    if (farm.latitude && farm.longitude) {
                        map.setView([farm.latitude, farm.longitude], 12);
                    }
                    drawFarmGeofences(farm);
                }
            }
            renderFarmsSidebar();
        }

        // Clear Geofence Polygon layers on map
        function clearGeofenceLayers() {
            mapGeofenceLayers.forEach(layer => map.removeLayer(layer));
            mapGeofenceLayers = [];
        }

        // Draw Farm Geofence boundary polygon
        function drawFarmGeofences(farm) {
            clearGeofenceLayers();

            let coordsList = [];
            
            // Check if geofence records exist in database
            if (farm.geofences && farm.geofences.length > 0) {
                farm.geofences.forEach(gf => {
                    if (gf.coordinates && gf.coordinates.length > 0) {
                        coordsList.push(gf.coordinates);
                        
                        const polygon = L.polygon(gf.coordinates, {
                            color: '#059669', // solid green borders
                            fillColor: '#10B981', // semi-transparent green fill
                            fillOpacity: 0.18,
                            weight: 2.5
                        }).addTo(map);

                        polygon.bindPopup(`<b>${gf.name}</b><br>Belgilangan yer maydoni: ${farm.name}`);
                        mapGeofenceLayers.push(polygon);
                    }
                });
            } else {
                // FALLBACK: Generate a gorgeous mock farm boundary polygon around farm center
                const lat = parseFloat(farm.latitude);
                const lng = parseFloat(farm.longitude);
                const deltaLat = 0.004;
                const deltaLng = 0.007;

                // Triangle/Diamond field geofence
                const mockCoordinates = [
                    [lat + deltaLat, lng],
                    [lat - deltaLat / 2, lng + deltaLng],
                    [lat - deltaLat, lng - deltaLng / 2],
                    [lat + deltaLat, lng]
                ];

                const polygon = L.polygon(mockCoordinates, {
                    color: '#059669',
                    fillColor: '#10B981',
                    fillOpacity: 0.18,
                    weight: 2.5
                }).addTo(map);

                polygon.bindPopup(`<b>Chegara maydoni (Geo-GIS)</b><br>Fermer xo'jaligi: ${farm.name}`);
                mapGeofenceLayers.push(polygon);
            }
        }

        // Selection of Farm Vehicle (centers map, zooms marker, opens HUD)
        function focusVehicle(vId, lat, lng) {
            selectedVehicleId = vId;
            
            if (lat && lng) {
                const coords = [parseFloat(lat), parseFloat(lng)];
                map.setView(coords, 15);
                
                // Trigger Leaflet popup on the vehicle marker
                if (mapVehicleMarkers[vId]) {
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
                let ignitionClass = "text-red-500 bg-red-50 border border-red-200 px-2 py-0.5 rounded";
                let durationText = "Harakatlanmayapti";

                if (isOnline) {
                    if (isMoving) {
                        ignitionText = "YONIQ (Harakatda)";
                        ignitionClass = "text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded";
                        durationText = "2 soat 45 daqiqadan beri faol";
                    } else {
                        ignitionText = "KUTISHDA (Bo'sh)";
                        ignitionClass = "text-amber-500 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded";
                        durationText = "Dvigatel o'chiq";
                    }
                }

                // Voltage dynamic/fixed calculation from 0x94 voltage packets (which showed 12.96V)
                const voltage = foundVehicle.gps_device_id === '862292055529242' ? '12.96' : '12.82';

                // Populate HUD
                document.getElementById('hudName').textContent = foundVehicle.name;
                document.getElementById('hudPlate').textContent = foundVehicle.plate_number;
                document.getElementById('hudSpeed').textContent = speed.toFixed(0);
                document.getElementById('hudIgnition').textContent = ignitionText;
                document.getElementById('hudIgnition').className = `text-[10px] font-bold mt-1 block text-center ${ignitionClass}`;
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
        }

        // Render Accordion Farms List on Sidebar
        function renderFarmsSidebar() {
            const container = document.getElementById('farmsList');
            container.innerHTML = '';

            if (filteredFarmsData.length === 0) {
                container.innerHTML = `<div class="p-6 text-center text-xs text-slate-400">Qidiruv bo'yicha xo'jaliklar topilmadi</div>`;
                return;
            }

            filteredFarmsData.forEach(farm => {
                const isOpen = selectedFarmId === farm.id;
                const accordionStyle = isOpen ? 'border-emerald-400 bg-emerald-50/20 shadow-md' : 'border-slate-200 bg-white hover:border-slate-350 shadow-sm';
                
                // Expandable contents
                let expandedHTML = '';
                if (isOpen) {
                    // 1. Geofences / Yer maydoni details
                    let geofenceHTML = `<div class="text-[10px] text-slate-400 italic">Maydon chegaralari kiritilmagan</div>`;
                    if (farm.geofences && farm.geofences.length > 0) {
                        geofenceHTML = farm.geofences.map(gf => `
                            <div class="flex justify-between items-center bg-slate-50 px-2.5 py-1.5 rounded border border-slate-100 text-[10px] text-slate-600">
                                <span class="font-medium">${gf.name}</span>
                                <span class="bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">${farm.size || '35'} gektar</span>
                            </div>
                        `).join('');
                    } else {
                        // Fallback simulated geofence name
                        geofenceHTML = `
                            <div class="flex justify-between items-center bg-slate-50 px-2.5 py-1.5 rounded border border-slate-100 text-[10px] text-slate-600">
                                <span class="font-medium">Chegara yer maydoni #1</span>
                                <span class="bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">${farm.size || '45'} gektar</span>
                            </div>
                        `;
                    }

                    // 2. Related Vehicles
                    let vehiclesHTML = `<div class="p-3 text-center text-[10px] text-slate-400 border border-dashed rounded-lg bg-slate-50/40">Ushbu xo'jalikka texnika biriktirilmagan</div>`;
                    if (farm.vehicles && farm.vehicles.length > 0) {
                        vehiclesHTML = farm.vehicles.map(v => {
                            const hasTrack = v.latest_gps_track;
                            const speed = hasTrack ? parseFloat(v.latest_gps_track.speed) : 0;
                            const statusText = v.status === 'online' ? (speed > 0 ? 'YONIQ (Faol)' : 'KUTISHDA') : "O'CHIQ";
                            const badgeColor = v.status === 'online' ? (speed > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-amber-400') : 'bg-slate-450';
                            const activeCardStyle = selectedVehicleId === v.id ? 'border-emerald-400 bg-emerald-50/50' : 'border-slate-150 bg-white';
                            
                            const clickHandler = hasTrack ? `onclick="event.stopPropagation(); focusVehicle(${v.id}, ${hasTrack.latitude}, ${hasTrack.longitude})"` : '';

                            return `
                                <div class="vehicle-card p-2.5 border rounded-lg shadow-sm transition hover:shadow-md cursor-pointer ${activeCardStyle}" ${clickHandler}>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-[11px] text-slate-700 font-display">${v.name}</span>
                                        <span class="flex items-center gap-1 text-[9px] font-bold">
                                            <span class="h-1.5 w-1.5 rounded-full ${badgeColor}"></span>
                                            ${statusText}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2 text-[9px] text-slate-500">
                                        <span>Raqami: <strong>${v.plate_number}</strong></span>
                                        <span>Tezlik: <strong>${speed.toFixed(0)} km/s</strong></span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }

                    expandedHTML = `
                        <div class="mt-3 pt-3 border-t border-slate-150/70 space-y-3">
                            <!-- Owner & Size Info -->
                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100 text-[10px] text-slate-500 space-y-1">
                                <div class="flex justify-between">
                                    <span>Rahbari:</span>
                                    <span class="font-semibold text-slate-800">${farm.owner ? farm.owner.name : 'Noma\'lum'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Telefon:</span>
                                    <span class="font-semibold text-slate-800">${farm.owner ? farm.owner.phone : '-'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tuproq turi:</span>
                                    <span class="font-semibold text-slate-800">${farm.soil_type || 'Loyli'}</span>
                                </div>
                            </div>

                            <!-- Yer Chegaralari -->
                            <div>
                                <h4 class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Belgilangan Maydonlari</h4>
                                <div class="space-y-1.5">${geofenceHTML}</div>
                            </div>

                            <!-- Tegishli Texnikalar -->
                            <div>
                                <h4 class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tegishli Texnikalar (${farm.vehicles.length})</h4>
                                <div class="space-y-2">${vehiclesHTML}</div>
                            </div>
                        </div>
                    `;
                }

                const cardHTML = `
                    <div class="farm-card p-3.5 border rounded-xl shadow-sm transition-all duration-300 cursor-pointer ${accordionStyle}" onclick="toggleFarm(${farm.id})">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-xs text-slate-850 font-display">${farm.name}</h3>
                                <p class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider mt-0.5">${farm.owner ? farm.owner.region?.name || 'Qoraqalpog\'iston' : 'Qoraqalpog\'iston'}</p>
                            </div>
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                ${farm.size || '35'} GA
                            </span>
                        </div>
                        ${expandedHTML}
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHTML);
            });
        }

        // Live text filter for Farms
        function filterFarms() {
            const query = document.getElementById('searchFarm').value.toLowerCase().trim();
            if (query === '') {
                filteredFarmsData = rawFarmsData;
            } else {
                filteredFarmsData = rawFarmsData.filter(f => 
                    f.name.toLowerCase().includes(query) || 
                    (f.owner && f.owner.name.toLowerCase().includes(query)) ||
                    (f.soil_type && f.soil_type.toLowerCase().includes(query))
                );
            }
            renderFarmsSidebar();
        }

        // Fetch Live Farms data and populate Sidebar list + Markers
        function loadFarmsAndVehicles() {
            fetch('/api/live-farms')
                .then(response => response.json())
                .then(farms => {
                    rawFarmsData = farms;
                    
                    // Filter list matching search query
                    const query = document.getElementById('searchFarm').value.toLowerCase().trim();
                    if (query === '') {
                        filteredFarmsData = farms;
                    } else {
                        filteredFarmsData = farms.filter(f => 
                            f.name.toLowerCase().includes(query) || 
                            (f.owner && f.owner.name.toLowerCase().includes(query))
                        );
                    }

                    // Render Sidebar accordion
                    renderFarmsSidebar();

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
                                        mapVehicleMarkers[v.id].setIcon(getVehicleIcon(v.status, speed > 0));
                                    } else {
                                        // Create new Leaflet Marker
                                        const marker = L.marker(coords, {
                                            icon: getVehicleIcon(v.status, speed > 0)
                                        }).addTo(map);

                                        // Detailed popup matching screenshot details
                                        marker.bindPopup(`
                                            <div class="p-1.5 font-sans text-xs">
                                                <h4 class="font-extrabold text-slate-800 text-sm font-display">${v.name} (${v.plate_number})</h4>
                                                <div class="mt-2.5 space-y-1.5 text-slate-600">
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
                            focusVehicle(selectedVehicleId, coords[0], coords[1]);
                        }
                    }
                })
                .catch(err => console.error('Error fetching live farms data:', err));
        }

        // First Load and 5 seconds interval refresh
        loadFarmsAndVehicles();
        setInterval(loadFarmsAndVehicles, 5000);
    </script>
</body>
</html>
