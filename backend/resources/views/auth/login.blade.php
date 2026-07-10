<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UzAgromind - Tizimga Kirish</title>
    
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
                            500: '#10B981', // Emerald for brand
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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="h-full flex flex-col justify-center items-center bg-slate-950 px-4">

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl space-y-6">
        
        <!-- Header / Logo -->
        <div class="text-center space-y-2">
            <span class="inline-flex h-12 w-12 rounded-xl bg-emerald-950/80 border border-emerald-800/50 items-center justify-center text-2xl shadow-md">
                🌱
            </span>
            <h1 class="text-2xl font-black tracking-tight text-slate-100 font-display">
                UZAGROMIND
            </h1>
            <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Admin Panelga Kirish</p>
        </div>

        <!-- Success/Error Notifications -->
        @if(session('error'))
            <div class="p-3.5 bg-rose-950/80 border border-rose-800/60 rounded-xl text-rose-400 text-xs font-semibold flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="p-3.5 bg-emerald-950/80 border border-emerald-800/60 rounded-xl text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 bg-rose-950/80 border border-rose-800/60 rounded-xl text-rose-450 text-xs font-semibold space-y-1">
                @foreach($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 shrink-0"></span>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="/login" class="space-y-4">
            @csrf
            
            <div>
                <label for="phone" class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5">Login (Telefon yoki Email)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm select-none">
                        👤
                    </span>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" 
                           placeholder="admin@uzagromind.uz yoki 998901234567" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-3 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500 font-semibold text-slate-200 transition placeholder-slate-650"
                           required autofocus>
                </div>
            </div>

            <div>
                <label for="password" class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1.5">Maxfiy Kod (Parol)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm select-none">
                        🔒
                    </span>
                    <input type="password" id="password" name="password" 
                           placeholder="••••••••" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-3 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500 font-semibold text-slate-200 transition placeholder-slate-650"
                           required>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-500/10 transition border border-emerald-500/30 flex items-center justify-center gap-1.5">
                    Tizimga Kirish
                </button>
            </div>
        </form>

        <div class="text-center pt-2">
            <a href="/" class="text-[10px] text-slate-500 hover:text-slate-400 transition font-medium">
                ← Bosh sahifaga qaytish
            </a>
        </div>

    </div>

</body>
</html>
