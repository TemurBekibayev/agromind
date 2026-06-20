<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UzAgromind - Taqdimot</title>
    
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
                        forest: {
                            500: '#10B981', // Emerald
                            600: '#059669',
                            700: '#047857'
                        },
                        brand: {
                            500: '#3B82F6', // Blue for water
                            600: '#2563EB',
                            700: '#1D4ED8'
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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        
        /* Slide Transition Animations */
        .slide {
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .slide.fade-in {
            animation: fadeIn 0.6s forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Simulated GPS Map Animation */
        @keyframes drawLine {
            to { stroke-dashoffset: 0; }
        }
        .gps-trail {
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: drawLine 6s linear infinite;
        }
        @keyframes moveTractor {
            0% { transform: translate(0, 0); }
            30% { transform: translate(120px, -40px); }
            65% { transform: translate(240px, 30px); }
            100% { transform: translate(380px, -10px); }
        }
        .moving-tractor {
            animation: moveTractor 6s linear infinite;
        }
    </style>
</head>
<body class="h-full flex flex-col justify-between overflow-hidden bg-slate-950 text-slate-100 select-none">

    <!-- Top Navigation Header -->
    <header class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-900/60 backdrop-blur-md px-6 shrink-0 z-20 shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <h1 class="text-sm font-bold tracking-tight text-slate-100 font-display flex items-center gap-2">
                UZAGROMIND <span class="bg-emerald-950 text-emerald-400 text-[9px] px-2 py-0.5 rounded-full font-sans font-bold border border-emerald-800/50">AQLLI TAQDIMOT</span>
            </h1>
        </div>
        
        <div class="flex items-center gap-4 text-xs font-semibold text-slate-400">
            <span id="slideCounter">Slayd: 1 / 11</span>
            <div class="flex gap-1">
                <button onclick="prevSlide()" class="p-1.5 rounded bg-slate-800 hover:bg-slate-700 transition">&larr;</button>
                <button onclick="nextSlide()" class="p-1.5 rounded bg-slate-800 hover:bg-slate-700 transition">&rarr;</button>
            </div>
        </div>
    </header>

    <!-- Slide Deck Area -->
    <main class="flex-1 flex items-center justify-center p-6 relative overflow-hidden">

        <!-- ==================== SLIDE 1: TITLE ==================== -->
        <div class="slide flex flex-col items-center justify-center text-center space-y-6 w-full max-w-4xl fade-in">
            <div class="h-20 w-20 rounded-2xl bg-gradient-to-tr from-emerald-600 to-blue-500 flex items-center justify-center text-white font-extrabold text-4xl shadow-xl shadow-emerald-500/20 font-display">
                U
            </div>
            <div class="space-y-3">
                <h1 class="text-4xl md:text-6xl font-black tracking-tight text-white font-display">UZAGROMIND</h1>
                <p class="text-emerald-400 font-semibold tracking-wider uppercase text-xs md:text-sm font-display">Qishloq xo'jaligini boshqarish va GIS monitoring ekotizimi</p>
            </div>
            <div class="max-w-md bg-slate-900/60 backdrop-blur border border-slate-800/80 p-4 rounded-xl text-xs text-slate-400 leading-relaxed shadow-lg">
                Suv limitlari va sarfi nazorati, aqlli tuproq tahlili, IoT datchiklar hamda real-vaqtda texnikalar monitoringi va xavfsizligi.
            </div>
            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold pt-4">
                Slaydlarni boshqarish uchun <kbd class="px-1.5 py-0.5 bg-slate-800 rounded border border-slate-700 text-slate-350 font-mono">Bo'shliq (Space)</kbd> yoki klaviatura yo'nalishlaridan foydalaning
            </div>
        </div>

        <!-- ==================== SLIDE 2: THE PROBLEM ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block">Mavjud vaziyat</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Qishloq xo'jaligidagi 3 ta asosiy muammo</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 shadow-md flex flex-col justify-between">
                    <div class="h-10 w-10 rounded-xl bg-rose-950 text-rose-400 flex items-center justify-center text-xl">💧</div>
                    <h3 class="font-bold text-slate-200 text-sm">Nazoratsiz Suv Sarfi</h3>
                    <p class="text-xs text-slate-450 leading-relaxed">Suv taqchilligi sharoitida sug'orish jadvallari va limitlarining qog'ozda qolib ketishi, amaliy nazorat yo'qligi.</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 shadow-md flex flex-col justify-between">
                    <div class="h-10 w-10 rounded-xl bg-rose-950 text-rose-400 flex items-center justify-center text-xl">🌱</div>
                    <h3 class="font-bold text-slate-200 text-sm">Tuproq Sho'rlanishi</h3>
                    <p class="text-xs text-slate-450 leading-relaxed">Tuproq tarkibini (pH, NPK) o'rganmay turib kimyoviy o'g'itlarni me'yoridan ortiqcha solish va hosildorlik tushishi.</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 shadow-md flex flex-col justify-between">
                    <div class="h-10 w-10 rounded-xl bg-rose-950 text-rose-400 flex items-center justify-center text-xl">🚜</div>
                    <h3 class="font-bold text-slate-200 text-sm">Texnikalar Nazoratsizligi</h3>
                    <p class="text-xs text-slate-450 leading-relaxed">Traktorlarning qayerda yurgani, yoqilg'ining maqsadsiz sarflanishi va o'g'irlanishi ustidan nazorat yo'qligi.</p>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 3: THE SOLUTION ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">UzAgromind platformasi</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Ehtiyojlarga mos mukammal yechim</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-400 flex items-center justify-center font-bold shrink-0 mt-0.5">1</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">GIS va Geofencing Xaritasi</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Har bir xo'jalik va dala chegaralari GPS orqali xaritaga kontur qilib chiziladi.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-400 flex items-center justify-center font-bold shrink-0 mt-0.5">2</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">IoT datchiklar va Telemetriya</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Dala tuproq datchigi va traktorlarga o'rnatilgan GPS trekerlar real-vaqtda ma'lumot yuboradi.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-400 flex items-center justify-center font-bold shrink-0 mt-0.5">3</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">Sun'iy Intellekt Agro-Tavsiyasi</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Yig'ilgan tahlillar asosida aqlli tavsiyalar va sug'orish muddatlari ishlab chiqiladi.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stylized Mock Screen / Vector -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center justify-center relative min-h-[220px]">
                    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 to-blue-500/10 rounded-2xl"></div>
                    <div class="relative border border-slate-800 rounded-xl p-3 bg-slate-950/80 max-w-sm space-y-3 shadow-inner w-full">
                        <div class="flex justify-between items-center border-b border-slate-900 pb-2">
                            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">UzAgromind GIS</span>
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="h-20 bg-slate-900 rounded-lg flex items-center justify-center text-[10px] text-slate-500 italic relative overflow-hidden">
                            <!-- simulated map lines -->
                            <div class="absolute inset-0 border border-slate-800 m-2 rounded opacity-30 bg-slate-950"></div>
                            <div class="absolute w-12 h-12 bg-emerald-500/20 rounded-full blur"></div>
                            🗺️ GIS Xarita Vizualizatsiyasi
                        </div>
                        <div class="flex justify-between text-[9px] text-slate-400">
                            <span>Jami Maydon: <strong>35.5 ha</strong></span>
                            <span>Texnikalar: <strong>2 faol</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 4: SOIL & AI SIMULATOR ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Interaktiv Simulyatsiya</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Tuproq Tahlili va Sun'iy Intellekt Tavsiyasi</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sliders input -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md">
                    <h3 class="font-bold text-sm text-slate-200 mb-2">Dala datchigi ko'rsatkichlari:</h3>
                    
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Tuproq namligi (Moisture):</span>
                            <span class="font-bold text-slate-200" id="val-moisture">45%</span>
                        </div>
                        <input type="range" id="slider-moisture" min="10" max="90" value="45" oninput="updateSoilSimulation()"
                               class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>pH kislotaliligi (Acidity):</span>
                            <span class="font-bold text-slate-200" id="val-ph">6.8</span>
                        </div>
                        <input type="range" id="slider-ph" min="4.0" max="9.0" step="0.1" value="6.8" oninput="updateSoilSimulation()"
                               class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Tuproq unumdorligi (NPK):</span>
                            <span class="font-bold text-slate-200" id="val-fertility">72%</span>
                        </div>
                        <input type="range" id="slider-fertility" min="20" max="95" value="72" oninput="updateSoilSimulation()"
                               class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-purple-500">
                    </div>
                </div>

                <!-- AI Output Box -->
                <div class="bg-slate-900/60 backdrop-blur border border-slate-850 rounded-2xl p-5 flex flex-col justify-between shadow-lg">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400 uppercase tracking-wider">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Groq AI Tavsiyasi
                        </div>
                        
                        <div class="space-y-2">
                            <div class="text-xs text-slate-400">
                                Mos keluvchi ekinlar:
                                <div id="sim-crop" class="text-sm font-bold text-slate-200 mt-1">G'o'za (Paxta), Bug'doy, Pomidor</div>
                            </div>
                            <div class="text-xs text-slate-400">
                                O'g'itlash va Sug'orish rejasi:
                                <div id="sim-plan" class="text-xs text-emerald-450 font-semibold bg-emerald-950/40 border border-emerald-900/30 p-2.5 rounded-lg mt-1 leading-relaxed">
                                    Fosforli o'g'it: 30kg/ga, Sug'orish: Me'yorda
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-[9px] text-slate-500 border-t border-slate-850 pt-2.5">
                        *Llama-3 modeli asosida datchik ma'lumotlariga ko'ra real-vaqtda generatsiya qilindi.
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 5: GPS TRACKING ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Texnika nazorati</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Real-Vaqtda GPS Harakati va Aloqa Uzilishlari</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Text Details -->
                <div class="space-y-3.5">
                    <div class="p-3 bg-slate-900 border border-slate-850 rounded-xl space-y-1">
                        <h4 class="font-bold text-xs text-emerald-400 uppercase tracking-wider">● Jonli Harakat</h4>
                        <p class="text-xs text-slate-350">Har 10 soniyada ma'lumotlarni so'rash orqali xaritada texnika markeri sakramasdan, tekis harakatlanadi.</p>
                    </div>
                    <div class="p-3 bg-slate-900 border border-slate-850 rounded-xl space-y-1">
                        <h4 class="font-bold text-xs text-rose-450 uppercase tracking-wider">⚠️ Aloqa uzilgan hududlar (GPRS Gaps)</h4>
                        <p class="text-xs text-slate-350">Aloqa uzilgan (daraxtzor yoki daryo bo'ylarida) qismlar xaritada qizil rangda va yo'qolgan daqiqalari bilan chiqadi.</p>
                    </div>
                </div>

                <!-- Animated GPS Track Canvas (SVG Mock) -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center justify-center min-h-[220px]">
                    <svg width="400" height="180" viewBox="0 0 400 180" class="w-full h-full">
                        <!-- Simulated farm boundaries -->
                        <polygon points="20,160 380,160 350,20 50,20" fill="none" stroke="#047857" stroke-width="1.5" stroke-dasharray="4, 4" />
                        
                        <!-- Simulated GPS Route path -->
                        <path id="routePath" d="M 50 140 Q 150 40 250 140 T 350 40" fill="none" stroke="#10B981" stroke-width="3.5" class="gps-trail" />
                        
                        <!-- Red connection break segment -->
                        <path d="M 150 90 L 250 140" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-dasharray="4, 4" />
                        
                        <!-- Warning circle for connection gap -->
                        <circle cx="200" cy="115" r="5" fill="#EF4444" />
                        <text x="180" y="102" fill="#EF4444" font-size="9" font-weight="bold" font-family="sans-serif">Aloqa uzilishi (12 min)</text>

                        <!-- Tractor marker -->
                        <g class="moving-tractor">
                            <circle cx="0" cy="0" r="10" fill="#10B981" stroke="#ffffff" stroke-width="1.5" />
                            <text x="-4" y="3" fill="#ffffff" font-size="9" font-weight="bold" font-family="sans-serif">🚜</text>
                        </g>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 6: ENGINE CUTOFF SIMULATOR ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Masofaviy Boshqaruv</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Dvigatelni Bloklash va Geofence Xavfsizligi</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Simulated phone dashboard -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl max-w-sm mx-auto w-full space-y-5 text-center relative overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-emerald-500" id="dash-border"></div>
                    
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-250 font-display">Tractor TTZ-80 (01 A 123 AA)</h4>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Fermer: Sherzod Dehqon</p>
                    </div>

                    <!-- Engine status badge -->
                    <div class="py-4 bg-slate-950 rounded-2xl border border-slate-850 flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5 text-emerald-400 font-bold text-xs" id="engine-status">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" id="engine-dot"></span>
                            DVIGATEL YONIQ (ACC ON)
                        </div>
                        <span class="text-[9px] text-slate-550 mt-1">Tezligi: 14 km/soat</span>
                    </div>

                    <!-- Interactive trigger button -->
                    <button onclick="toggleEngineSimulation()" id="btn-cutoff" class="w-full rounded-xl bg-rose-600 hover:bg-rose-500 py-3 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition border border-rose-500/30">
                        🛑 Dvigatelni Bloklash (Cutoff)
                    </button>
                    
                    <p class="text-[9px] text-slate-500 italic">*Tugmani bosing va brauzer ovoziga diqqat qiling.</p>
                </div>

                <!-- Explanation text -->
                <div class="space-y-4 text-xs text-slate-300 leading-relaxed">
                    <div class="flex gap-2">
                        <span class="text-rose-500 mt-0.5">⚠️</span>
                        <span>**Chegara buzilishi (Geofence Breach)**: Traktor o'z maydonidan tashqariga ruxsatsiz chiqib ketsa, telefoningizga darhol avtomatik bildirishnoma yuboriladi.</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-emerald-400 mt-0.5">🔌</span>
                        <span>**Masofaviy Rele orqali Bloklash**: Dvigatelning starter simlariga ulangan rele orqali, ulanish mavjud bo'lgan zaxira holatida dvigatel starterini bir soniyada bloklash imkoniyati.</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-blue-400 mt-0.5">💡</span>
                        <span>**Kutilayotgan buyruqlar**: Aloqa uzilgan vaqtda yuborilgan buyruqlar navbatga olinadi va traktor tarmoqqa kirishi bilan dvigatel bloklanadi.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 7: WATER MONITORING ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-brand-500 uppercase tracking-wider block">Suv Resurslari Nazorati</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Suv Limitlari va Ishlatilgan Suv Balansi</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Text details -->
                <div class="space-y-4">
                    <p class="text-xs text-slate-350 leading-relaxed">UzAgromind tizimi suv limitlarini dekadalar (har 10 kunlik) va manbalar bo'yicha hisoblab borish moduliga ega:</p>
                    
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Er ustidan (daryo, kanal, soy, buloq) olinadigan suv
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Er ostidan (sug'orish quduqlari) olinadigan suv
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                            Kollektor-drenaj tarmoqlaridan qayta olinadigan suv
                        </div>
                    </div>

                    <div class="p-3 bg-blue-950/40 border border-blue-900/30 rounded-xl text-xs text-blue-400 leading-relaxed">
                        **Tashqi to'ldirish oynasi**: Suv boshqarmasi xodimlari admin panelga kirmasdan, maxsus token orqali to'g'ridan-to'g'ri dalaning o'zida limit va sarfni kiritib ketadilar.
                    </div>
                </div>

                <!-- Water bar chart simulation -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-200 text-center font-display">Dekadalar bo'yicha Suv sarfi (m³): Limit vs Amalda</h4>
                    
                    <div class="space-y-3">
                        <!-- 1-decade -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>1-dekada (Aprel)</span>
                                <span>Limit: 64,970 m³ | Amalda: 62,750 m³</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 80%;"></div>
                                <div class="h-full bg-emerald-500" style="width: 77%;"></div>
                            </div>
                        </div>
                        
                        <!-- 2-decade -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>2-dekada (Aprel)</span>
                                <span>Limit: 65,420 m³ | Amalda: 72,110 m³ (Oshib ketdi ⚠️)</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 80%;"></div>
                                <div class="h-full bg-rose-500 animate-pulse" style="width: 88%;"></div>
                            </div>
                        </div>

                        <!-- 3-decade -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>3-dekada (Aprel)</span>
                                <span>Limit: 69,970 m³ | Amalda: 40,270 m³</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 85%;"></div>
                                <div class="h-full bg-emerald-500" style="width: 50%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 8: COMMUNITY & MARKETPLACE ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Hamjamiyat kuchaytirish</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Fermerlar Chati va Texnikalar Ijarasi</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Phone UI mockup representing Chat -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-xl max-w-sm mx-auto w-full space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-850 pb-2 text-[10px]">
                        <span class="font-bold text-slate-350">💬 AgroMind Chat Hamjamiyati</span>
                        <span class="text-emerald-400 font-semibold">● 14 faol</span>
                    </div>
                    
                    <div class="space-y-3 text-[10px] h-36 overflow-y-auto pr-1">
                        <!-- Message 1 -->
                        <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-850">
                            <span class="font-bold text-emerald-400 block mb-0.5">Sherzod Dehqon:</span>
                            <span class="text-slate-300">Pomidor maydonimda tuproq namligi juda past, pH 6.2 ko'rsatyapti. Nima maslahat berasizlar?</span>
                        </div>
                        <!-- Message 2 -->
                        <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-850 ml-4 border-l-2 border-l-emerald-500">
                            <span class="font-bold text-blue-400 block mb-0.5">Akmal Monitor (Mutaxassis):</span>
                            <span class="text-slate-300">AI tavsiyasini yangilang. Azotli o'g'it va sug'orish muddatlarini to'g'rilab beradi!</span>
                        </div>
                    </div>
                </div>

                <!-- Rental listings mockup -->
                <div class="space-y-3">
                    <h3 class="font-bold text-sm text-slate-200 font-display">🚜 Texnikalarni Ijara / Almashish bozori:</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Fermerlar o'zlarida bo'sh turgan chizel kultivatori, omoch yoki pluglarni ijara e'loniga joylashtirib, qo'shimcha daromad topishi mumkin.</p>
                    
                    <div class="bg-slate-900 border border-slate-850 rounded-xl p-3.5 flex justify-between items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded bg-slate-950 border border-slate-800 flex items-center justify-center text-xl">🚜</div>
                            <div>
                                <h4 class="font-bold text-xs text-slate-250">Chizel kultivatori ijaraga</h4>
                                <p class="text-[9px] text-slate-500">Narxi: 100 000 so'm/kun | Tel: 998901111111</p>
                            </div>
                        </div>
                        <span class="bg-emerald-950 text-emerald-400 text-[9px] px-2 py-0.5 rounded-full font-bold border border-emerald-900/30">FAOL</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 9: GOVERNMENT MONITORING ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Hududiy Tahlil</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Hukumat va Raislar Monitoring Paneli</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div class="space-y-4">
                    <p class="text-xs text-slate-350 leading-relaxed">Tuman rahbarlari va mutaxassislar uchun maxsus avtorizatsiya talab qilmaydigan, faqat xavfsiz token bilan himoyalangan `/monitor` sahifasi:</p>
                    
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            Barcha fermerlar maydonining jami gektari va o'rtacha unumdorligi
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            Ayni vaqtda faol texnikalarning soni (Online/Offline)
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            Amudaryo tumani xaritasi markazida joylashgan premium GIS interfeysi
                        </div>
                    </div>
                </div>

                <!-- Mock Monitor Statistics counter -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl grid grid-cols-2 gap-4">
                    <div class="bg-slate-950 border border-slate-850 p-3 rounded-xl text-center">
                        <span class="text-[9px] text-slate-550 font-bold uppercase tracking-wider block">Nazoratdagi maydon</span>
                        <span class="text-base font-black text-slate-100 font-display mt-1 block">1 590.3 ha</span>
                    </div>
                    <div class="bg-slate-950 border border-slate-850 p-3 rounded-xl text-center">
                        <span class="text-[9px] text-slate-550 font-bold uppercase tracking-wider block">Faol Texnikalar</span>
                        <span class="text-base font-black text-slate-100 font-display mt-1 block">12 / 15</span>
                    </div>
                    <div class="bg-slate-950 border border-slate-850 p-3 rounded-xl text-center">
                        <span class="text-[9px] text-slate-550 font-bold uppercase tracking-wider block">Fermer Xo'jaliklari</span>
                        <span class="text-base font-black text-slate-100 font-display mt-1 block">42 ta</span>
                    </div>
                    <div class="bg-slate-950 border border-slate-850 p-3 rounded-xl text-center">
                        <span class="text-[9px] text-slate-550 font-bold uppercase tracking-wider block">O'rtacha unumdorlik</span>
                        <span class="text-base font-black text-emerald-400 font-display mt-1 block">74.5%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 10: ECONOMIC IMPACT ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Iqtisodiy foydalar</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Tizimning Fermerga Keltiradigan Foydasi</h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-md flex flex-col justify-center">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">O'g'it xarajatlari</span>
                    <span id="counter-fertilizer" class="text-3xl font-black text-emerald-400 font-display mt-1.5 block">0%</span>
                    <span class="text-[9px] text-slate-550 mt-1 block">tahlil asosida tejash</span>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-md flex flex-col justify-center">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Yoqilg'i tejash</span>
                    <span id="counter-fuel" class="text-3xl font-black text-emerald-400 font-display mt-1.5 block">0%</span>
                    <span class="text-[9px] text-slate-550 mt-1 block">GPS nazorati evaziga</span>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-md flex flex-col justify-center">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Hosildorlik</span>
                    <span id="counter-yield" class="text-3xl font-black text-blue-400 font-display mt-1.5 block">0%</span>
                    <span class="text-[9px] text-slate-550 mt-1 block">o'z vaqtida ekin ekish</span>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-md flex flex-col justify-center">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Suv sarfi</span>
                    <span id="counter-water" class="text-3xl font-black text-blue-400 font-display mt-1.5 block">0%</span>
                    <span class="text-[9px] text-slate-550 mt-1 block">dekada nazorati</span>
                </div>
            </div>
            
            <div class="bg-slate-900/40 border border-slate-850 p-4 rounded-xl text-center text-xs text-slate-400 leading-relaxed max-w-xl mx-auto">
                Tizimning joriy etilishi har bir fermer xo'jaligi uchun birinchi mavsumdayoq investitsiyalarni to'liq qoplash va daromadni oshirish imkonini beradi.
            </div>
        </div>

        <!-- ==================== SLIDE 11: WRAP-UP ==================== -->
        <div class="slide hidden flex-col items-center justify-center text-center space-y-6 w-full max-w-4xl">
            <div class="h-16 w-16 rounded-2xl bg-emerald-950 border border-emerald-800/60 text-emerald-400 flex items-center justify-center text-3xl animate-bounce">
                🤝
            </div>
            <div class="space-y-2">
                <h2 class="text-3xl md:text-5xl font-black font-display text-white">E'tiboringiz uchun rahmat!</h2>
                <p class="text-emerald-400 font-semibold tracking-wider text-xs md:text-sm uppercase font-display">UzAgromind - Kelajak Qishloq Xo'jaligi Bugun</p>
            </div>
            <div class="bg-slate-900/60 backdrop-blur border border-slate-850 p-5 rounded-2xl max-w-sm w-full space-y-2 text-xs text-slate-300">
                <p class="font-bold text-slate-100 border-b border-slate-850 pb-2 mb-2 font-display">Aloqa ma'lumotlari:</p>
                <p>Telefon: <strong>+998 (90) XXX-XX-XX</strong></p>
                <p>Veb-sayt: <strong>www.agromind.uz</strong></p>
            </div>
        </div>

    </main>

    <!-- Bottom Footer Navigation helper -->
    <footer class="h-12 border-t border-slate-850 bg-slate-900/40 flex items-center justify-center text-[10px] text-slate-500 shrink-0 font-medium font-display">
        UzAgromind GIS Ekotizim Taqdimoti.
    </footer>

    <!-- Web Audio API synthesized audio for engine start/stop -->
    <script>
        let audioCtx = null;
        let isEngineRunning = true;

        function playEngineSoundEffect(stop = false) {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                if (!stop) {
                    // Engine Starter
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(60, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(140, audioCtx.currentTime + 0.5);
                    gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
                    gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 0.6);
                    osc.start();
                    setTimeout(() => osc.stop(), 700);
                } else {
                    // Engine shutdown
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(120, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(15, audioCtx.currentTime + 1.2);
                    gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
                    gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 1.2);
                    osc.start();
                    setTimeout(() => osc.stop(), 1400);
                }
            } catch (err) {
                console.error("Audio error:", err);
            }
        }
        
        function toggleEngineSimulation() {
            const statusBadge = document.getElementById('engine-status');
            const dot = document.getElementById('engine-dot');
            const btn = document.getElementById('btn-cutoff');
            const border = document.getElementById('dash-border');
            
            if (isEngineRunning) {
                // Shut down
                isEngineRunning = false;
                playEngineSoundEffect(true);
                
                statusBadge.innerHTML = `<span class="h-2.5 w-2.5 rounded-full bg-rose-500" id="engine-dot"></span> DVIGATEL BLOKLANDI (ACC OFF)`;
                statusBadge.className = "flex items-center gap-1.5 text-rose-500 font-bold text-xs";
                btn.textContent = "🔓 Dvigatelni Blokdan chiqarish";
                btn.className = "w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-500/20 transition border border-emerald-500/30";
                border.className = "absolute inset-x-0 top-0 h-1 bg-rose-500";
            } else {
                // Restart
                isEngineRunning = true;
                playEngineSoundEffect(false);
                
                statusBadge.innerHTML = `<span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" id="engine-dot"></span> DVIGATEL YONIQ (ACC ON)`;
                statusBadge.className = "flex items-center gap-1.5 text-emerald-400 font-bold text-xs";
                btn.textContent = "🛑 Dvigatelni Bloklash (Cutoff)";
                btn.className = "w-full rounded-xl bg-rose-600 hover:bg-rose-500 py-3 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition border border-rose-500/30";
                border.className = "absolute inset-x-0 top-0 h-1 bg-emerald-500";
            }
        }

        // Soil analysis simulation values
        function updateSoilSimulation() {
            const moisture = document.getElementById('slider-moisture').value;
            const ph = parseFloat(document.getElementById('slider-ph').value).toFixed(1);
            const fertility = document.getElementById('slider-fertility').value;
            
            document.getElementById('val-moisture').textContent = moisture + "%";
            document.getElementById('val-ph').textContent = ph;
            document.getElementById('val-fertility').textContent = fertility + "%";
            
            let crop = "Arpa, Beda, Qurg'oqchilik ekinlari";
            let plan = "Azotli o'g'it: 20kg/ga, Sug'orish: 1 marta/hafta";
            
            if (ph >= 6.5 && ph <= 7.5 && fertility >= 70) {
                crop = "G'o'za (Paxta), Bug'doy, Pomidor";
                plan = "Fosforli o'g'it: 30kg/ga, Sug'orish: Me'yorda";
            } else if (ph < 6.0) {
                crop = "Sholi, Suli, Kartoshka";
                plan = "Ohaklash tavsiya etiladi (Kislotani kamaytirish uchun), Azotli o'g'itlar";
            } else if (moisture < 35) {
                crop = "Makkajo'xori, Beda (Qurg'oqchilikka chidamli)";
                plan = "Sug'orish chastotasini 2 martaga oshiring!";
            }
            
            document.getElementById('sim-crop').textContent = crop;
            document.getElementById('sim-plan').textContent = plan;
        }

        // Slide controller
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const slideCounter = document.getElementById('slideCounter');

        function showSlide(index) {
            slides.forEach((slide, i) => {
                if (i === index) {
                    slide.classList.remove('hidden');
                    slide.classList.add('flex');
                    slide.classList.add('fade-in');
                    
                    // Trigger counters on slide 10
                    if (index === 9) {
                        animateCounter('counter-fertilizer', -30, "%");
                        animateCounter('counter-fuel', -20, "%");
                        animateCounter('counter-yield', 25, "+%");
                        animateCounter('counter-water', -15, "%");
                    }
                } else {
                    slide.classList.add('hidden');
                    slide.classList.remove('flex');
                    slide.classList.remove('fade-in');
                }
            });
            slideCounter.textContent = `Slayd: ${index + 1} / ${slides.length}`;
        }

        function nextSlide() {
            if (currentSlide < slides.length - 1) {
                currentSlide++;
                showSlide(currentSlide);
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }

        // Counter animator helper
        function animateCounter(id, target, suffix = "%") {
            const el = document.getElementById(id);
            if (!el) return;
            
            let start = 0;
            const duration = 1200;
            const stepTime = 15;
            const steps = duration / stepTime;
            const stepVal = target / steps;
            
            const interval = setInterval(() => {
                start += stepVal;
                if (Math.abs(start) >= Math.abs(target)) {
                    el.textContent = (target > 0 ? "+" : "") + Math.abs(target) + suffix.replace('+', '');
                    clearInterval(interval);
                } else {
                    el.textContent = (target > 0 ? "+" : "-") + Math.abs(Math.round(start)) + suffix.replace('+', '');
                }
            }, stepTime);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'Enter') {
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                prevSlide();
            }
        });
    </script>
</body>
</html>
