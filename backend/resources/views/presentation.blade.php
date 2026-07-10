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
            <button onclick="toggleSpeakerNotes()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-950 text-emerald-450 border border-emerald-800/50 hover:bg-emerald-900 transition font-display">
                🎙️ Nutq Yordamchisi
            </button>
            <span id="slideCounter" class="min-w-[70px]">Slayd: 1 / 11</span>
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
                    <p class="text-xs text-slate-450 leading-relaxed">Sug'orish jadvallari va suv limitlarining qog'ozda qolib ketishi, suv isrofi va suv boshqarmasi bilan real-vaqtda aloqa yo'qligi sababli eng qimmatli resursimiz behuda sarflanmoqda.</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 shadow-md flex flex-col justify-between">
                    <div class="h-10 w-10 rounded-xl bg-rose-950 text-rose-400 flex items-center justify-center text-xl">🌱</div>
                    <h3 class="font-bold text-slate-200 text-sm">Tuproq Sho'rlanishi</h3>
                    <p class="text-xs text-slate-450 leading-relaxed">Tuproq tarkibini (pH kislotaliligi, unumdorligini) aniq bilmay, ko'r-ko'rona va me'yoridan ortiqcha kimyoviy o'g'it solish natijasida yerlarimiz sho'rlanib, hosildorlik yildan-yilga tushib bormoqda.</p>
                </div>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 shadow-md flex flex-col justify-between">
                    <div class="h-10 w-10 rounded-xl bg-rose-950 text-rose-400 flex items-center justify-center text-xl">🚜</div>
                    <h3 class="font-bold text-slate-200 text-sm">Texnikalar Nazoratsizligi</h3>
                    <p class="text-xs text-slate-450 leading-relaxed">Traktor va og'ir texnikalarning real-vaqtda qayerda yurgani, yoqilg'i qanchalik to'g'ri sarflanayotgani va maqsadsiz foydalanishlar ustidan ishonchli nazorat mexanizmining yo'qligi.</p>
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
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-450 flex items-center justify-center font-bold shrink-0 mt-0.5">1</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">GIS va Geofencing Xaritasi</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Har bir xo'jalik va dala chegaralari GPS yordamida sun'iy yo'ldosh xaritasi ustida kontur shaklida chiziladi. Bu har bir qarich yerni aniq nazorat qilish, maydonlarni to'g'ri hisoblash va chegaradan tashqaridagi noqonuniy ishlarning oldini olish poydevoridir.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-450 flex items-center justify-center font-bold shrink-0 mt-0.5">2</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">IoT datchiklar va Telemetriya</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Dala tuproq datchigi pH, namlik va unumdorlikni o'lchaydi. Traktorlarga o'rnatilgan GPS trekerlar real-vaqtda ularning harakati, joylashuvi va yoqilg'i sarfi ma'lumotlarini serverga uzatib turadi.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-950 text-emerald-450 flex items-center justify-center font-bold shrink-0 mt-0.5">3</div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-200">Sun'iy Intellekt Agro-Tavsiyasi</h4>
                            <p class="text-xs text-slate-400 leading-relaxed mt-0.5">Yig'ilgan tahlillar platformaning o'ziga xos Groq AI (Llama-3) sun'iy intellekti yordamida qayta ishlanadi va fermerga tayyor qarorlar, sug'orish va o'g'itlash rejalari shaklida taqdim etiladi.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Farming Cycle Image -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2.5 shadow-xl flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 to-blue-500/10 opacity-50"></div>
                    <img src="/presentation_assets/farming_cycle.png" alt="UzAgromind Ekotizimi" class="rounded-xl w-full h-auto object-cover border border-slate-800 shadow-md">
                    <div class="w-full text-center mt-2">
                        <span class="text-[9px] text-slate-450 font-semibold tracking-wide uppercase">Tizimning yaxlit ishlash va ma'lumot almashish sxemasi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== SLIDE 4: SOIL & AI SIMULATOR ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Interaktiv Simulyatsiya</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Tuproq Tahlili va Sun'iy Intellekt Tavsiyasi</h2>
                <p class="text-xs text-slate-400 leading-relaxed mt-1.5">Dalaga o'rnatilgan aqlli sensorlar orqali tuproqning namligi, kislotaliligi (pH) va ozuqa moddalari (NPK) real-vaqtda o'lchanadi. Groq AI esa ushbu ko'rsatkichlarni bir necha soniyada tahlil qilib, fermerga tayyor o'g'itlash va sug'orish rejasini generatsiya qiladi. Quyidagi slayderlarni o'zgartirib, tizim qanday ishlashini sinab ko'ring:</p>
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
          <!-- ==================== SLIDE 5: GPS TRACKING ==================== -->
        <div class="slide hidden flex-col w-full max-w-4xl space-y-6">
            <div class="text-center md:text-left">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Texnika nazorati</span>
                <h2 class="text-2xl md:text-3.5xl font-black font-display mt-1">Real-Vaqtda GPS Harakati va Aloqa Uzilishlari</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Text Details -->
                <div class="space-y-3.5">
                    <div class="p-3.5 bg-slate-900 border border-slate-850 rounded-xl space-y-1.5">
                        <h4 class="font-bold text-xs text-emerald-400 uppercase tracking-wider">🟢 Jonli Harakat va Drift Filtr</h4>
                        <p class="text-xs text-slate-350">Har 10 soniyada yangilanadigan ma'lumotlar bilan texnika markeri xaritada sakrashlarsiz, mutlaqo tekis harakatlanadi. Maxsus GPS drift filtri sun'iy siljishlarni tozalab, bosib o'tilgan yo'lni (kilometrajni) 99% aniqlikda hisoblab beradi.</p>
                    </div>
                    <div class="p-3.5 bg-slate-900 border border-slate-850 rounded-xl space-y-1.5">
                        <h4 class="font-bold text-xs text-rose-455 uppercase tracking-wider">⚠️ Aloqa uzilgan hududlar (GPRS Gaps)</h4>
                        <p class="text-xs text-slate-350">GSM/GPRS signallari past bo'lgan o'rmonzor yoki daryo bo'yi hududlarida aloqa uzilgan qismlar tizimda aniqlanib, xaritada qizil rang va uzilish vaqti bilan chizib ko'rsatiladi. Bu haydovchining nazoratsiz qolib ketishini oldini oladi.</p>
                    </div>
                </div>

                <!-- Animated SVG and Screenshot Stack -->
                <div class="space-y-4">
                    <!-- Animated GPS Track Canvas (SVG Mock) -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center justify-center min-h-[160px]">
                        <svg width="400" height="130" viewBox="0 0 400 130" class="w-full h-full">
                            <polygon points="20,110 380,110 350,15 50,15" fill="none" stroke="#047857" stroke-width="1.5" stroke-dasharray="4, 4" />
                            <path id="routePath" d="M 50 100 Q 150 20 250 100 T 350 20" fill="none" stroke="#10B981" stroke-width="3.5" class="gps-trail" />
                            <path d="M 150 65 L 250 100" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-dasharray="4, 4" />
                            <circle cx="200" cy="82" r="5" fill="#EF4444" />
                            <text x="180" y="70" fill="#EF4444" font-size="9" font-weight="bold" font-family="sans-serif">Aloqa uzilishi (12 min)</text>
                            <g class="moving-tractor">
                                <circle cx="0" cy="0" r="10" fill="#10B981" stroke="#ffffff" stroke-width="1.5" />
                                <text x="-4" y="3" fill="#ffffff" font-size="9" font-weight="bold" font-family="sans-serif">🚜</text>
                            </g>
                        </svg>
                    </div>
                    
                    <!-- Real tractor trail screenshot -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shadow-xl flex flex-col items-center relative overflow-hidden">
                        <img src="/presentation_assets/tractor_trail.png" alt="Traktor GPS yo'nalishi" class="rounded-xl w-full h-28 object-cover border border-slate-800 shadow-md">
                        <div class="text-[9px] text-slate-450 font-semibold tracking-wide uppercase mt-1">Tizimdagi real traktor monitoringi va yo'l chizmasi</div>
                    </div>
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
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl max-w-sm mx-auto w-full space-y-4 text-center relative overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-emerald-500" id="dash-border"></div>
                    
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-250 font-display">Tractor TTZ-80 (01 A 123 AA)</h4>
                        <p class="text-[10px] text-slate-555 uppercase tracking-wider font-semibold">Fermer: Sherzod Dehqon</p>
                    </div>

                    <!-- Engine status badge -->
                    <div class="py-3 bg-slate-950 rounded-2xl border border-slate-850 flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5 text-emerald-400 font-bold text-xs" id="engine-status">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" id="engine-dot"></span>
                            DVIGATEL YONIQ (ACC ON)
                        </div>
                        <span class="text-[9px] text-slate-550 mt-1">Tezligi: 14 km/soat</span>
                    </div>

                    <!-- Interactive trigger button -->
                    <button onclick="toggleEngineSimulation()" id="btn-cutoff" class="w-full rounded-xl bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition border border-rose-500/30">
                        🛑 Dvigatelni Bloklash (Cutoff)
                    </button>
                    
                    <p class="text-[9px] text-slate-500 italic">*Tugmani bosing va ovozli simulyatsiyani sinang.</p>
                </div>

                <!-- Explanation text & SMS commands image -->
                <div class="space-y-4">
                    <div class="space-y-2.5 text-xs text-slate-300 leading-relaxed">
                        <div class="flex gap-2">
                            <span class="text-rose-500 mt-0.5">⚠️</span>
                            <span>**Chegara nazorati (Geofence)**: Agar texnika fermer tomonidan belgilangan dala konturidan ruxsatsiz chiqsa, telefonga darhol ogohlantirish keladi va uning harakati real-vaqtda kuzatiladi.</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-emerald-450 mt-0.5">🔌</span>
                            <span>**Dvigatelni masofadan o'chirish (Cutoff)**: GPS trekerga ulangan masofaviy rele yordamida, fermer smartfonidan bitta tugmani bosib, dvigatel starterini bloklashi mumkin. Bu texnika ruxsatsiz foydalanilganda yoki o'g'irlanganda uni zudlik bilan to'xtatishga imkon beradi.</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-blue-400 mt-0.5">💬</span>
                            <span>**SMS va GPRS xavfsiz buyruqlar**: Tizim internet bo'lmagan sharoitda ham SMS buyruqlar (GPRS relay protokoli) orqali boshqaruvni ta'minlaydi. Quyida GPRS protokoli orqali tizim loglarida aks etgan haqiqiy boshqaruv buyruqlari ko'rsatilgan.</span>
                        </div>
                    </div>
                    
                    <!-- SMS commands log image -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-2 shadow-lg relative overflow-hidden group">
                        <img src="/presentation_assets/sms_commands.png" alt="SMS GPRS Commands Log" class="rounded-lg w-full h-28 object-cover border border-slate-850">
                        <div class="text-[9px] text-slate-450 text-center mt-1 font-semibold">Tizim loglari: Qurilmaga yuborilgan GPRS va SMS boshqaruv buyruqlari</div>
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
                    <p class="text-xs text-slate-350 leading-relaxed">UzAgromind tizimi suv limitlarini oylik kiritib borish va qoldiqni real-vaqtda nazorat qilish moduliga ega:</p>
                    
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Oylik limit: Har bir oy uchun ajratilgan suv limiti (m³)
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Amaldagi sarf: Sug'orish vaqtida haqiqiy sarflangan suv hajmi
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            Qoldiq hisobi: Limitdan sarfni ayirib, real-vaqtda qoldiqni ko'rsatish
                        </div>
                    </div>

                    <div class="p-3 bg-blue-950/40 border border-blue-900/30 rounded-xl text-xs text-blue-400 leading-relaxed">
                        <strong>Soddalashtirilgan tizim</strong>: Tashqi xodimlar va mas'ullar uchun murakkab jadvallarsiz, faqatgina 2 ta maydonni (Limit va Amalda) telefonda to'ldirish kifoya.
                    </div>
                </div>

                <!-- Water bar chart simulation -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-200 text-center font-display">Oylik Suv sarfi (m³): Limit vs Amalda</h4>
                    
                    <div class="space-y-3">
                        <!-- Iyun -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>Iyun oyi</span>
                                <span>Limit: 120,000 m³ | Amalda: 105,000 m³ (Qoldiq: +15,000 m³)</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 80%;"></div>
                                <div class="h-full bg-emerald-500" style="width: 70%;"></div>
                            </div>
                        </div>
                        
                        <!-- Iyul -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>Iyul oyi</span>
                                <span>Limit: 135,000 m³ | Amalda: 140,000 m³ (⚠️ -5,000 m³)</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 80%;"></div>
                                <div class="h-full bg-rose-500 animate-pulse" style="width: 83%;"></div>
                            </div>
                        </div>

                        <!-- Avgust -->
                        <div class="space-y-1 text-[10px]">
                            <div class="flex justify-between text-slate-400">
                                <span>Avgust oyi</span>
                                <span>Limit: 110,000 m³ | Amalda: 92,000 m³ (Qoldiq: +18,000 m³)</span>
                            </div>
                            <div class="w-full h-3 bg-slate-950 rounded overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 80%;"></div>
                                <div class="h-full bg-emerald-500" style="width: 67%;"></div>
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
                            <span class="text-slate-300">AI tavsiyasini yangilang. Azotli o'g'it va sug'orish muddatlarini avtomatik to'g'rilab beradi!</span>
                        </div>
                    </div>
                </div>

                <!-- Rental listings mockup -->
                <div class="space-y-4">
                    <h3 class="font-bold text-sm text-slate-200 font-display">🚜 Texnikalarni Ijara / Almashish bozori</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Fermerlar o'zlarida bo'sh turgan kultivator, omoch yoki plug kabi texnikalarni ijaraga berish e'loniga qo'yib, qo'shimcha daromad topishi mumkin. Boshqa fermerlar esa arzon narxlarda kerakli texnikani ijaraga olib, ish unumdorligini oshiradilar.</p>
                    
                    <div class="bg-slate-900 border border-slate-850 rounded-xl p-3.5 flex justify-between items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded bg-slate-950 border border-slate-800 flex items-center justify-center text-xl">🚜</div>
                            <div>
                                <h4 class="font-bold text-xs text-slate-250 font-sans">Chizel kultivatori ijaraga</h4>
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
                    <p class="text-xs text-slate-350 leading-relaxed">Tuman rahbarlari va mutaxassislar uchun maxsus avtorizatsiya talab qilmaydigan, faqat xavfsiz token bilan himoyalangan `/monitor` boshqaruv paneli:</p>
                    
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            <span>**Hududiy xarita**: Amudaryo tumanining barcha fermalari, dalalari konturlari va faol texnikalari bitta umumiy GIS xaritada real-vaqtda ko'rinadi.</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            <span>**Tahliliy vidjetlar**: Jami nazoratdagi maydon, faol/nofaol traktorlar soni, o'rtacha tuproq unumdorligi va suv balansi statistikasi.</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-500">✔</span>
                            <span>**Tezkor qidiruv**: Har bir fermer xo'jaligi bo'yicha qidirish, ekin turlari va sug'orish ko'rsatkichlarini filtrlash.</span>
                        </div>
                    </div>
                    
                    <!-- Stats Counter Widget -->
                    <div class="grid grid-cols-2 gap-3 bg-slate-900 border border-slate-850 p-3 rounded-xl shadow-md">
                        <div class="text-center bg-slate-950/60 p-2 rounded-lg border border-slate-850/60">
                            <span class="text-[8px] text-slate-500 font-bold uppercase block">Jami maydon</span>
                            <span class="text-xs font-black text-slate-100 font-display mt-0.5 block">1 590.3 ha</span>
                        </div>
                        <div class="text-center bg-slate-950/60 p-2 rounded-lg border border-slate-850/60">
                            <span class="text-[8px] text-slate-500 font-bold uppercase block">Online Texnika</span>
                            <span class="text-xs font-black text-emerald-400 font-display mt-0.5 block">12 / 15 ta</span>
                        </div>
                    </div>
                </div>

                <!-- Real Dashboard Map Screenshot -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2.5 shadow-xl flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 to-blue-500/10 opacity-30"></div>
                    <img src="/presentation_assets/dashboard_map.png" alt="UzAgromind GIS Monitoring Paneli" class="rounded-xl w-full h-auto object-cover border border-slate-800 shadow-md">
                    <div class="w-full text-center mt-2">
                        <span class="text-[9px] text-slate-450 font-semibold tracking-wide uppercase">Tizimning haqiqiy GIS xaritasi va tuman monitoringi interfeysi</span>
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
                Tizimning joriy etilishi har bir fermer xo'jaligi uchun birinchi mavsumdayoq investitsiyalarni to'liq qoplash va daromad        <!-- ==================== SLIDE 11: WRAP-UP ==================== -->
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

    <!-- Speaker Notes Panel -->
    <div id="speakerNotesPanel" class="fixed right-0 top-16 bottom-12 w-80 bg-slate-900/95 backdrop-blur-lg border-l border-slate-800 z-35 transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col hidden">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-950/60">
            <h3 class="font-bold text-xs uppercase tracking-wider text-emerald-400 font-display flex items-center gap-1.5">
                🎙️ Spiker Nutqi (Yordamchi)
            </h3>
            <button onclick="toggleSpeakerNotes()" class="text-slate-400 hover:text-white text-base font-bold">&times;</button>
        </div>
        <div class="flex-1 p-4 overflow-y-auto text-xs text-slate-300 leading-relaxed space-y-3" id="speakerNotesContent">
            <!-- Dinamik yuklanadi -->
        </div>
    </div>

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
                crop = "Makkajo'xori, Beda (Qurg'oqchilikke chidamli)";
                plan = "Sug'orish chastotasini 2 martaga oshiring!";
            }
            
            document.getElementById('sim-crop').textContent = crop;
            document.getElementById('sim-plan').textContent = plan;
        }

        // Speaker notes text database
        const speakerNotes = {
            0: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 1: Kirish (Titul)</p>
                <p class="font-medium text-slate-100">"Assalomu alaykum, hurmatli fermerlar va qishloq xo'jaligi sohasi mutaxassislari!"</p>
                <p class="mt-2 text-slate-350">"Bugun sizlarga qishloq xo'jaligimizni yangi bosqichga olib chiquvchi, har biringizning kundalik og'iringizni yengillashtiruvchi <strong>UzAgromind</strong> platformasini taqdim etmoqchiman. Ushbu taqdimotda tizim qanday qilib sizning daromadingizni oshirishi, suv va o'g'it xarajatlarini tejashi hamda texnikalar xavfsizligini ta'minlashini tushuntirib beraman."</p>`,
            1: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 2: Muammolar</p>
                <p class="font-medium text-slate-100">"Keling, avvalo kundalik qiyinchiliklarimiz haqida gaplashaylik."</p>
                <p class="mt-2 text-slate-355">"Qishloq xo'jaligida uchta eng katta muammoga duch kelyapmiz. Birinchidan, suv yildan-yilga kamayib bormoqda, sug'orish rejalarini faqat qog'ozda yuritish esa real nazorat yo'qligiga va suv isrofiga olib keladi. Ikkinchidan, tuproq tarkibini bilmay dorilaymiz, bu yerni sho'rlatib, unumdorlikni pasaytiradi. Uchinchidan, traktorlar qayerda yurgani va yoqilg'i sarfi ustidan ishonchli nazorat yo'qligi sababli xarajatlarimiz oshib ketmoqda. UzAgromind loyihasi aynan shu muammolarga yechim beradi."</p>`,
            2: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 3: Yechim</p>
                <p class="font-medium text-slate-100">"UzAgromind barcha muammolarni bitta aqlli ekotizim orqali hal etadi."</p>
                <p class="mt-2 text-slate-350">"Dalalarimiz xaritaga kontur shaklida chiziladi. Tuproqqa sensorlar o'rnatiladi va traktorlarimizga GPS qo'yiladi. Barcha ma'lumotlar sun'iy intellekt tomonidan tahlil qilinib, sizning telefoningizga tayyor agro-tavsiyalar sifatida uzatiladi. O'ng tomondagi chizmada sensorlar, texnikalar va markaziy boshqaruv platformamiz qanday qilib yaxlit integratsiyada ishlashi sxemasi tasvirlangan."</p>`,
            4: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 4: Tuproq Tahlili va AI</p>
                <p class="font-medium text-slate-100">"Aqlli tuproq tahlili o'g'it xarajatlarini keskin kamaytirish uchun xizmat qiladi."</p>
                <p class="mt-2 text-slate-350">"Sensor pH darajasi, tuproq namligi va ozuqa moddalari (NPK) kabi ko'rsatkichlarni real-vaqtda o'lchab turadi. Telefoningizda esa Groq sun'iy intellekti ushbu ko'rsatkichlarni tahlil qilib, dalangizga qaysi ekin mos kelishi, qaysi o'g'itdan qancha solish va sug'orish rejalarini tayyorlab beradi. Slayddagi ko'rsatkichlarni o'zgartirib AI tavsiyasi qanday o'zgarishini ko'rishingiz mumkin."</p>`,
            3: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 5: GPS Monitoring</p>
                <p class="font-medium text-slate-105">"GPS monitoring tizimi – bu yoqilg'i tejash va haydovchining ish sifatini tekshirish usulidir."</p>
                <p class="mt-2 text-slate-350">"Trekerlar orqali traktor qayerda yurganini aniq ko'rib turasiz. Bizning tizimda aloqa uzilgan (daraxtzor yoki daryo bo'ylarida) nuqtalar ham xaritada qizil rang bilan ko'rsatilib, yo'qolgan vaqtlar hisoblab boriladi. GPS Drift filtri esa kilometrajni 99% aniqlikda hisoblaydi. O'ng tomonda tizimning haqiqiy traktor yo'nalishi va aloqa uzilishi ko'rinishi aks etgan."</p>`,
            5: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 6: Dvigatelni Bloklash</p>
                <p class="font-medium text-slate-100">"Dvigatelni masofadan boshqarish va xavfsizlik (Engine Cutoff) modulini ko'rib chiqamiz."</p>
                <p class="mt-2 text-slate-350">"Agar traktor belgilangan maydondan ruxsatsiz chiqib ketsa, telefonga darhol ogohlantirish keladi va fermer telefondan bitta tugmani bosib traktor starterini bloklab qo'yishi mumkin. Traktor qayta o't olmaydi. O'ng tomonda qurilmaga yuborilgan haqiqiy SMS/GPRS buyruqlari jurnali ko'rsatilgan. Dvigatelni yoritib yoki o'chirib simulyatsiyani sinab ko'rishingiz mumkin."</p>`,
            6: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 7: Suv Limitlari</p>
                <p class="font-medium text-slate-100">"Suv – dehqonchiligimiz asosi. Uni tejamkorlik bilan boshqarishimiz zarur."</p>
                <p class="mt-2 text-slate-350">"Endi har bir fermer uchun oylik limitlar va amaldagi sarf hisoblab boriladi. Tizim avtomatik ravishda qoldiqni (+/-) aniqlaydi. Suv boshqarmasi xodimlari dalaning o'zida turib telefonda limit va sarfni kiritadilar, fermer esa suv limitidan qancha qolganini real-vaqtda ko'rib turadi. Bu suv isrofiga yo'l qo'ymaslik va qoldiq tugab qolishining oldini olish imkonini beradi."</p>`,
            7: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 8: Hamjamiyat</p>
                <p class="font-medium text-slate-100">"UzAgromind faqat nazorat tizimi emas, balki fermerlar hamjamiyatidir."</p>
                <p class="mt-2 text-slate-350">"Siz chat orqali boshqa fermerlar va agronom mutaxassislar bilan maslahatlashishingiz mumkin. Shuningdek, bo'sh turgan kultivator, omoch yoki plug kabi texnikalaringizni ijaraga qo'yib, qo'shimcha daromad topishingiz mumkin. Bu fermerlar uchun o'zaro foydali bozor vazifasini o'taydi."</p>`,
            8: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 9: Raislar Monitoringi</p>
                <p class="font-medium text-slate-100">"Hukumat vakillari va viloyat rahbarlari uchun maxsus monitoring paneli ishlab chiqilgan."</p>
                <p class="mt-2 text-slate-350">"O'ng tomondagi rasmda tumanimizdagi barcha fermer maydonlari, suv sarfi va faol texnikalarni bitta umumiy GIS xaritada real-vaqtda ko'rsatuvchi haqiqiy boshqaruv ekrani tasvirlangan. Bu orqali butun tuman bo'yicha tahliliy ko'rsatkichlarni bir joyda kuzatib borish mumkin."</p>`,
            9: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 10: Iqtisodiy Foyda</p>
                <p class="font-medium text-slate-100">"Xulosa qilib aytganda, tizim fermerga qanday iqtisodiy foyda keltiradi?"</p>
                <p class="mt-2 text-slate-350">"Tizim o'g'it xarajatlarini 30% gacha, yoqilg'i sarfini 20% gacha, suv sarfini 15% gacha tejaydi. Hosildorlikni esa kamida 25% ga oshiradi. Bu investitsiya o'zini birinchi mavsumdayoq to'liq qoplaydi va sof foydaga chiqadi."</p>`,
            10: `<p class="font-bold text-emerald-400 border-b border-slate-800 pb-1.5 mb-1.5 font-display text-[10px] uppercase">Slayd 11: Savol-Javob</p>
                <p class="font-medium text-slate-105">"E'tiboringiz uchun kattakon rahmat!"</p>
                <p class="mt-2 text-slate-350">"Aqlli qishloq xo'jaligi tizimini keng joriy etish barchamiz uchun katta imkoniyat. Taqdimotimiz yakunlandi. Endi sizlarni qiziqtirgan savollarga bajonidil javob berishga tayyorman."</p>`
        };

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
                    
                    // Trigger counters on slide 10 (index 9)
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
            
            // Update speaker notes
            const notesContent = document.getElementById('speakerNotesContent');
            if (notesContent && speakerNotes[index]) {
                notesContent.innerHTML = speakerNotes[index];
            }
        }

        let isNotesOpen = false;
        function toggleSpeakerNotes() {
            const panel = document.getElementById('speakerNotesPanel');
            if (!panel) return;
            
            isNotesOpen = !isNotesOpen;
            if (isNotesOpen) {
                panel.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('translate-x-full');
                }, 10);
            } else {
                panel.classList.add('translate-x-full');
                setTimeout(() => {
                    panel.classList.add('hidden');
                }, 300);
            }
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

        // Initialize first slide and load speaker notes
        showSlide(0);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'Enter') {
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                prevSlide();
            }
        });
    </script>
</body>
</html>     prevSlide();
            }
        });
    </script>
</body>
</html>
