<!DOCTYPE html>
<html lang="uz" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AgroMind Admin') - Qishloq Xo'jaligi Platformasi</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#F4F7F5',
                            100: '#E8EDE9',
                            500: '#2A5C43',
                            600: '#1C422F',
                            700: '#1A3C2A', // Primary deep green
                            800: '#122A1E',
                        },
                        accent: {
                            500: '#E65C00', // Orange accent
                            600: '#CC5200',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @yield('styles')
</head>
<body class="h-full flex flex-col overflow-hidden">

    <!-- Top Navigation Bar -->
    <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 shrink-0 z-20">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-lg bg-forest-700 flex items-center justify-center text-white font-bold text-xl">
                A
            </div>
            <div>
                <h1 class="text-lg font-bold text-forest-700 leading-tight">AgroMind</h1>
                <p class="text-xs text-gray-500 font-medium">Uzbekistan Agricultural Portal</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Monitoring Dashboard Quick Link -->
            <a href="/monitor?token=agromind_monitoring_token_2026" target="_blank" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                Monitoring Paneli
            </a>

            <!-- User Menu -->
            <div class="flex items-center gap-2 border-l pl-4">
                <div class="h-8 w-8 rounded-full bg-forest-100 flex items-center justify-center text-forest-700 font-semibold text-sm">
                    AD
                </div>
                <div class="hidden md:block text-left">
                    <p class="text-sm font-semibold text-gray-800 leading-none">Admin Shaxsiy</p>
                    <p class="text-xs text-gray-500 mt-0.5">Hukumat Administratori</p>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="hidden md:flex w-64 flex-col border-r border-gray-200 bg-white overflow-y-auto z-10">
            <nav class="flex-1 space-y-1.5 px-4 py-6">
                <!-- Nav Item -->
                <a href="/admin/dashboard" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/dashboard*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Boshqaruv Paneli
                </a>

                <a href="/admin/farmers" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/farmers*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0110.089 18a11.374 11.374 0 01-9.345-8.334.75.75 0 01.428-.771 11.39 11.39 0 0111.962 0 .75.75 0 01.428.771 11.374 11.374 0 01-3.345 8.334m6.353-6.353a4.125 4.125 0 11-8.25 0 4.125 4.125 0 018.25 0z" />
                    </svg>
                    Dehqonlar
                </a>

                <a href="/admin/vehicles" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/vehicles*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V9.75M3.75 14.25h16.5M3.75 14.25V7.5a1.5 1.5 0 011.5-1.5h13.5a1.5 1.5 0 011.5 1.5v6.75m-16.5 0H18M13.5 6V4.5a1.5 1.5 0 00-3 0V6" />
                    </svg>
                    Texnikalar
                </a>

                <a href="/admin/soil" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/soil*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v17.792m0-17.792L5.47 7.378M9.75 3.104l4.28 4.274M18.75 6.002v12.004M18.75 6.002l-4.25 4.25m4.25-4.25l4.25 4.25" />
                    </svg>
                    Tuproq Tahlillari
                </a>

                <a href="/admin/alerts" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/alerts*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a9.04 9.04 0 01-1.8-.13c-2.135-.372-4.078-1.414-5.5-2.97M12.986 8.54a6 6 0 00-5.369 6.187M13.477 10.525a4 4 0 00-3.577 4.124M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Ogohlantirishlar
                </a>

                <a href="/admin/regions" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/regions*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    Hududlar
                </a>

                <a href="/admin/messages" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->is('admin/messages*') ? 'bg-forest-700 text-white' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    Murojaatlar
                </a>
            </nav>
            
            <div class="border-t border-gray-150 p-4">
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    Chiqish
                </button>
            </div>
        </aside>

        <!-- Main Content Panel -->
        <main class="flex-1 overflow-y-auto px-6 py-8 relative">
            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
