<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UzAgromind - Suv limitlari va sarfi kiritish</title>
    
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
    </style>
</head>
<body class="h-full flex flex-col justify-between bg-slate-950 text-slate-100 select-none">

    <!-- Top Header -->
    <header class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-900 px-6 shrink-0 shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
            </span>
            <div>
                <h1 class="text-base font-bold tracking-tight text-slate-100 font-display flex items-center gap-2">
                    UZAGROMIND <span class="bg-blue-950 text-blue-400 text-[10px] px-2.5 py-0.5 rounded-full font-sans font-bold border border-blue-800/50">SUV NAZORATI</span>
                </h1>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Tashqi ma'lumot kiritish tizimi</p>
            </div>
        </div>
        <div class="text-xs font-semibold text-slate-400 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800 shadow-inner">
            Token: Faol
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center p-4 md:p-8 overflow-y-auto">
        <div class="w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-6">
            
            <div class="border-b border-slate-800 pb-4">
                <h2 class="text-lg font-bold text-slate-100 font-display">Suv limitlari va sarfini kiritish shakli</h2>
                <p class="text-xs text-slate-450 mt-1">Fermer xo'jaligi, yil va oyni tanlang, so'ngra tegishli dekadalar bo'yicha limit va sarflangan suv hajmlarini (m³) kiriting.</p>
            </div>

            <!-- Notifications -->
            @if(session('success'))
                <div class="p-4 bg-emerald-950/80 border border-emerald-800/60 rounded-xl text-emerald-400 text-xs font-semibold flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 bg-rose-950/80 border border-rose-800/60 rounded-xl text-rose-450 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            {{ $error }}
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Selection Filters Form (GET method to reload with parameters) -->
            <form method="GET" action="" id="filterForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="token" value="{{ request('token') }}">
                
                <div>
                    <label class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5">Fermer Xo'jaligi</label>
                    <select name="farm_id" onchange="document.getElementById('filterForm').submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                        <option value="">-- Tanlang --</option>
                        @foreach($farms as $farm)
                            <option value="{{ $farm->id }}" {{ request('farm_id') == $farm->id ? 'selected' : '' }}>
                                {{ $farm->name }} ({{ $farm->owner ? $farm->owner->name : 'Noma\'lum' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5">Yil</label>
                    <select name="year" onchange="document.getElementById('filterForm').submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                        @php
                            $currentYear = date('Y');
                        @endphp
                        @for($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                            <option value="{{ $y }}" {{ (request('year', $currentYear) == $y) ? 'selected' : '' }}>{{ $y }}-yil</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5">Oy</label>
                    <select name="month" onchange="document.getElementById('filterForm').submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                        <option value="">-- Tanlang --</option>
                        <optgroup label="Sug'orish mavsumi (Aprel - Sentyabr)">
                            <option value="4" {{ request('month') == 4 ? 'selected' : '' }}>Aprel</option>
                            <option value="5" {{ request('month') == 5 ? 'selected' : '' }}>May</option>
                            <option value="6" {{ request('month') == 6 ? 'selected' : '' }}>Iyun</option>
                            <option value="7" {{ request('month') == 7 ? 'selected' : '' }}>Iyul</option>
                            <option value="8" {{ request('month') == 8 ? 'selected' : '' }}>Avgust</option>
                            <option value="9" {{ request('month') == 9 ? 'selected' : '' }}>Sentyabr</option>
                        </optgroup>
                        <optgroup label="Kuzgi-qishki mavsum (Oktyabr - Mart)">
                            <option value="10" {{ request('month') == 10 ? 'selected' : '' }}>Oktyabr</option>
                            <option value="11" {{ request('month') == 11 ? 'selected' : '' }}>Noyabr</option>
                            <option value="12" {{ request('month') == 12 ? 'selected' : '' }}>Dekabr</option>
                            <option value="1" {{ request('month') == 1 ? 'selected' : '' }}>Yanvar</option>
                            <option value="2" {{ request('month') == 2 ? 'selected' : '' }}>Fevral</option>
                            <option value="3" {{ request('month') == 3 ? 'selected' : '' }}>Mart</option>
                        </optgroup>
                    </select>
                </div>
            </form>

            @if(request('farm_id') && request('month'))
                <!-- Data Entry Form -->
                <form method="POST" action="/water-entry/store" class="space-y-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ request('token') }}">
                    <input type="hidden" name="farm_id" value="{{ request('farm_id') }}">
                    <input type="hidden" name="year" value="{{ request('year', date('Y')) }}">
                    <input type="hidden" name="month" value="{{ request('month') }}">

                    @php
                        $existingLimit = $existingRecord ? $existingRecord->limit_m3 : '';
                        $existingUsed = $existingRecord ? $existingRecord->used_m3 : '';
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-950/60 p-6 rounded-xl border border-slate-800 shadow-inner">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-350 uppercase tracking-wider mb-2">Suv olish limiti, m³</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm font-semibold select-none">
                                    💧
                                </span>
                                <input type="number" step="0.01" min="0" 
                                       name="limit_m3" 
                                       value="{{ old('limit_m3', $existingLimit) }}" 
                                       placeholder="0.00" 
                                       class="w-full bg-slate-900 border border-slate-850 rounded-xl pl-10 pr-4 py-3 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-bold text-slate-200 transition duration-150 placeholder-slate-600 shadow-sm"
                                       required>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1.5">Ushbu oy uchun xo'jalikka ajratilgan maksimal suv limiti.</p>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-slate-355 uppercase tracking-wider mb-2">Amalda ishlatilgan suv, m³</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-sm font-semibold select-none">
                                    📈
                                </span>
                                <input type="number" step="0.01" min="0" 
                                       name="used_m3" 
                                       value="{{ old('used_m3', $existingUsed) }}" 
                                       placeholder="0.00" 
                                       class="w-full bg-slate-900 border border-slate-850 rounded-xl pl-10 pr-4 py-3 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-bold text-slate-200 transition duration-150 placeholder-slate-600 shadow-sm"
                                       required>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1.5">Ushbu oyda amalda sarflangan umumiy suv miqdori.</p>
                        </div>
                    </div>

                    @if($existingRecord)
                        @php
                            $diff = $existingRecord->limit_m3 - $existingRecord->used_m3;
                        @endphp
                        <div class="flex items-center justify-between p-4 rounded-xl border {{ $diff >= 0 ? 'bg-emerald-950/20 border-emerald-900/50 text-emerald-400' : 'bg-rose-950/20 border-rose-900/50 text-rose-400' }} text-[11px] font-medium">
                            <span class="flex items-center gap-1.5">
                                ⚖️ Mavjud hisobot bo'yicha balans (Qoldiq):
                            </span>
                            <span class="font-bold text-xs">
                                {{ number_format($diff, 2, '.', ' ') }} m³
                            </span>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="submit" class="rounded-xl bg-blue-600 hover:bg-blue-500 px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-1.5 border border-blue-500/40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            Ma'lumotlarni Saqlash
                        </button>
                    </div>
                </form>
            @else
                <div class="p-8 text-center border border-dashed border-slate-850 rounded-2xl bg-slate-950/40 flex flex-col items-center justify-center">
                    <svg class="h-8 w-8 text-slate-600 mb-3 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h4 class="text-xs font-bold text-slate-450 uppercase tracking-wider">Xo'jalik va Oyni tanlang</h4>
                    <p class="text-[10px] text-slate-555 mt-1 max-w-[280px]">Dala suv limitlarini to'ldirishni boshlash uchun yuqoridagi maydonlardan fermer xo'jaligi va kerakli oyni tanlang.</p>
                </div>
            @endif

        </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="h-12 border-t border-slate-850 bg-slate-900/40 flex items-center justify-center text-[10px] text-slate-500 shrink-0 font-medium font-display">
        &copy; 2026 UzAgromind GIS - Barcha huquqlar himoyalangan.
    </footer>

</body>
</html>
