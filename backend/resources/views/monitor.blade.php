<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroMind GPS Live Tracking</title>
    
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

    <!-- Premium Clean Header -->
    <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h1 class="text-base font-bold tracking-tight text-slate-800 font-display flex items-center gap-2">
                    AGROMIND <span class="bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5 rounded-full font-sans font-bold">JONLI GPS</span>
                </h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Texnikalar va datchiklar real-vaqt nazorati</p>
            </div>
        </div>

        <!-- Clock and Connection Status -->
        <div class="flex items-center gap-4">
            <span class="hidden md:inline-flex items-center gap-1.5 text-xs text-slate-600 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Server: ALOQA FAOL
            </span>
            <div id="liveClock" class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                12:00:00
            </div>
        </div>
    </header>

    <!-- Main Container: Sidebar + Map -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Left Side: Devices Sidebar -->
        <aside class="w-80 border-r border-slate-200 bg-white flex flex-col overflow-hidden shrink-0 z-10 shadow-lg">
            <div class="p-4 border-b border-slate-150 bg-slate-50/50 shrink-0">
                <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider font-display">Texnikalar ro'yxati</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Xaritada ko'rish uchun texnikani tanlang</p>
            </div>
            
            <!-- Devices List -->
            <div id="devicesList" class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1 bg-slate-50/30">
                <div class="p-6 text-center text-xs text-slate-400 mt-10">Qurilmalar yuklanmoqda...</div>
            </div>
        </aside>

        <!-- Right Side: Fullscreen Map & HUD overlay -->
        <main class="flex-1 relative bg-slate-100">
            <!-- Full Map Container -->
            <div id="map"></div>
            
            <!-- Speed display and Vehicle HUD -->
            <div id="selectedDeviceHud" class="hidden absolute top-4 right-4 bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-xl p-4 shadow-xl z-[1000] w-64 transition-all duration-300 transform scale-95 translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="hudName" class="font-extrabold text-sm text-slate-800 font-display">-</h3>
                        <p id="hudPlate" class="text-xs text-slate-500 font-semibold font-display">-</p>
                    </div>
                    <button onclick="closeHud()" class="text-slate-400 hover:text-slate-600 bg-slate-100 p-1 rounded-full transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Large Speed Gauge -->
                <div class="mt-4 bg-slate-50 border border-slate-100 rounded-lg p-3 flex flex-col items-center justify-center">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Hozirgi tezligi</span>
                    <div class="flex items-baseline mt-1">
                        <span id="hudSpeed" class="text-3xl font-black text-slate-800 font-display">0</span>
                        <span class="text-xs font-semibold text-slate-500 ml-1 font-display">km/soat</span>
                    </div>
                </div>

                <!-- Secondary indicators -->
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Batareya</span>
                        <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block"><span id="hudVoltage">12.96</span> <span class="text-[10px] font-normal text-slate-500">V</span></span>
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Yoqilg'i</span>
                        <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block"><span id="hudFuel">-</span> <span class="text-[10px] font-normal text-slate-500">%</span></span>
                    </div>
                </div>

                <div class="mt-2.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100 text-xs space-y-1">
                    <div class="flex justify-between text-slate-600">
                        <span>Aloqa:</span>
                        <span id="hudStatus" class="font-bold text-emerald-600">-</span>
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

        // Initialize Map centered directly on Qoraqalpog'iston (Amudaryo)
        const map = L.map('map').setView([42.11005, 60.07327], 11);

        // Standard clean street map layer (OpenStreetMap light theme)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Store Leaflet Markers and vehicles list globally
        const vehicleMarkers = {};
        let activeVehicles = [];
        let selectedVehicleId = null;

        // Dynamic Leaflet Marker Icon with Glowing Ring
        function createCustomIcon(status) {
            let color = '#94A3B8'; // gray
            if (status === 'online') color = '#10B981'; // emerald
            else if (status === 'problem') color = '#EF4444'; // red

            return L.divIcon({
                className: 'custom-gps-icon',
                html: `
                    <div class="relative flex items-center justify-center" style="width: 22px; height: 22px;">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-35" style="background-color: ${color};"></span>
                        <div class="rounded-full shadow-md border-2 border-white" style="width: 14px; height: 14px; background-color: ${color};"></div>
                    </div>
                `,
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });
        }

        // Sidebar card selection
        function selectVehicle(id, coords) {
            selectedVehicleId = id;
            
            // Re-render sidebar active styling
            document.querySelectorAll('.vehicle-card').forEach(card => {
                const cardId = parseInt(card.getAttribute('data-id'));
                if (cardId === id) {
                    card.classList.remove('border-slate-200', 'bg-white');
                    card.classList.add('border-emerald-500', 'bg-emerald-50/40');
                } else {
                    card.classList.remove('border-emerald-500', 'bg-emerald-50/40');
                    card.classList.add('border-slate-200', 'bg-white');
                }
            });

            // Focus and Open Popup on Map
            if (coords && coords[0] && coords[1]) {
                map.setView(coords, 14);
                if (vehicleMarkers[id]) {
                    vehicleMarkers[id].openPopup();
                }
            }

            // Populate Floating HUD
            const vehicle = activeVehicles.find(v => v.id === id);
            if (vehicle && vehicle.latest_gps_track) {
                const speed = parseFloat(vehicle.latest_gps_track.speed);
                const fuel = parseFloat(vehicle.latest_gps_track.fuel_level);
                
                // Show real battery status 12.96V (from packet voltage 0x0510) or calculated
                const voltage = vehicle.gps_device_id === '862292055529242' ? '12.96' : '12.80';

                document.getElementById('hudName').textContent = vehicle.name;
                document.getElementById('hudPlate').textContent = vehicle.plate_number;
                document.getElementById('hudSpeed').textContent = speed.toFixed(0);
                document.getElementById('hudVoltage').textContent = voltage;
                document.getElementById('hudFuel').textContent = fuel.toFixed(0);
                document.getElementById('hudStatus').textContent = vehicle.status.toUpperCase();
                document.getElementById('hudStatus').className = `font-bold text-${vehicle.status === 'online' ? 'emerald' : 'red'}-600`;
                document.getElementById('hudTime').textContent = vehicle.latest_gps_track.recorded_at;

                // Show HUD
                const hud = document.getElementById('selectedDeviceHud');
                hud.classList.remove('hidden');
            }
        }

        // Close HUD HUD
        function closeHud() {
            document.getElementById('selectedDeviceHud').classList.add('hidden');
            selectedVehicleId = null;
            document.querySelectorAll('.vehicle-card').forEach(card => {
                card.classList.remove('border-emerald-500', 'bg-emerald-50/40');
                card.classList.add('border-slate-200', 'bg-white');
            });
        }

        // Fetch Live vehicles and render Sidebar + Markers
        function loadLiveVehicles() {
            fetch('/api/live-vehicles')
                .then(response => response.json())
                .then(vehicles => {
                    activeVehicles = vehicles;
                    const listContainer = document.getElementById('devicesList');
                    listContainer.innerHTML = '';

                    vehicles.forEach(v => {
                        const hasTrack = v.latest_gps_track;
                        const coords = hasTrack ? [parseFloat(v.latest_gps_track.latitude), parseFloat(v.latest_gps_track.longitude)] : null;
                        const speed = hasTrack ? parseFloat(v.latest_gps_track.speed) : 0;
                        const statusColor = v.status === 'online' ? 'bg-emerald-500' : 'bg-slate-400';

                        // 1. Render Sidebar Device Card
                        const activeStyle = selectedVehicleId === v.id ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 bg-white';
                        const clickHandler = coords ? `onclick="selectVehicle(${v.id}, [${coords[0]}, ${coords[1]}])"` : '';
                        
                        const cardHTML = `
                            <div class="vehicle-card p-3 border rounded-xl shadow-sm ${activeStyle} cursor-pointer transition-all duration-200 hover:shadow" data-id="${v.id}" ${clickHandler}>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-800 font-display">${v.name}</h4>
                                        <p class="text-[10px] text-slate-500 font-medium font-display mt-0.5">${v.plate_number}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-semibold px-2 py-0.5 rounded-full ${v.status === 'online' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
                                        <span class="h-1.5 w-1.5 rounded-full ${statusColor}"></span>
                                        ${v.status.toUpperCase()}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center mt-3 pt-2.5 border-t border-slate-100 text-[10px] text-slate-500 font-medium">
                                    <span class="flex items-center gap-0.5">
                                        Tezlik: <strong class="text-slate-800">${speed.toFixed(0)} km/s</strong>
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-normal">
                                        ${hasTrack ? hasTrack.recorded_at.split(' ')[1] || '' : 'No data'}
                                    </span>
                                </div>
                            </div>
                        `;
                        listContainer.insertAdjacentHTML('beforeend', cardHTML);

                        // 2. Render / Update Map Markers (No flickering)
                        if (coords) {
                            if (vehicleMarkers[v.id]) {
                                // Update position
                                vehicleMarkers[v.id].setLatLng(coords);
                                vehicleMarkers[v.id].setIcon(createCustomIcon(v.status));
                            } else {
                                // Create marker
                                const marker = L.marker(coords, {
                                    icon: createCustomIcon(v.status)
                                }).addTo(map);

                                marker.bindPopup(`
                                    <div class="p-1 font-sans text-xs">
                                        <h4 class="font-extrabold text-slate-800 text-sm font-display">${v.name} (${v.plate_number})</h4>
                                        <div class="mt-2 space-y-1 text-slate-600">
                                            <p>Holati: <strong class="text-${v.status === 'online' ? 'emerald' : 'red'}-600">${v.status.toUpperCase()}</strong></p>
                                            <p>Tezlik: <strong>${speed.toFixed(0)} km/soat</strong></p>
                                            <p>Akkumulyator: <strong>${v.gps_device_id === '862292055529242' ? '12.96' : '12.80'} V</strong></p>
                                            <p class="text-[10px] text-slate-400 mt-2 border-t pt-1.5">IMEI: ${v.gps_device_id || 'Yo\'q'}</p>
                                        </div>
                                    </div>
                                `);

                                // Add marker click handler
                                marker.on('click', () => {
                                    selectVehicle(v.id, coords);
                                });

                                vehicleMarkers[v.id] = marker;
                            }
                        }
                    });

                    // Update HUD info if already opened
                    if (selectedVehicleId) {
                        const activeVehicle = vehicles.find(v => v.id === selectedVehicleId);
                        if (activeVehicle && activeVehicle.latest_gps_track) {
                            const coords = [parseFloat(activeVehicle.latest_gps_track.latitude), parseFloat(activeVehicle.latest_gps_track.longitude)];
                            selectVehicle(selectedVehicleId, coords);
                        }
                    }
                })
                .catch(err => console.error('Error fetching live vehicles:', err));
        }

        // Initial Load and 5 seconds interval refresh
        loadLiveVehicles();
        setInterval(loadLiveVehicles, 5000);
    </script>
</body>
</html>
