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
                        <th scope="col" class="px-6 py-4 text-right">Amallar</th>
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
                                                <div>
                                                    <span class="font-medium text-forest-800">{{ $farm->name }}</span>
                                                    <span class="text-gray-500 font-semibold ml-2">{{ $farm->size }} gektar ({{ $farm->soil_type }})</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <!-- Edit Farm Button -->
                                                    <button onclick="openEditFarmModal({{ json_encode($farm->only(['id', 'name', 'size', 'soil_type', 'region_id', 'district', 'user_id', 'geofences'])) }})" class="text-blue-600 hover:text-blue-900 p-0.5" title="Xo'jalikni tahrirlash">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                    <!-- Delete Farm Button -->
                                                    <form action="/admin/farms/destroy/{{ $farm->id }}" method="POST" class="inline-block" onsubmit="return confirm('Haqiqatan ham ushbu fermer xo\'jaligini o\'chirmoqchimisiz? Barcha tegishli tuproq tahlillari va geofencelar ham o\'chib ketadi!')">
                                                        @csrf
                                                        <button type="submit" class="text-red-650 hover:text-red-900 p-0.5" title="Xo'jalikni o'chirish">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
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
                            
                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4 text-right text-xs font-medium space-x-2 shrink-0">
                                <!-- Edit Farmer Button -->
                                <button onclick="openEditFarmerModal({{ json_encode($farmer->only(['id', 'name', 'phone', 'region_id', 'district'])) }})" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-1 px-2.5 rounded transition inline-block">
                                    Tahrirlash
                                </button>
                                <!-- Delete Farmer Button -->
                                <form action="/admin/farmers/destroy/{{ $farmer->id }}" method="POST" class="inline-block" onsubmit="return confirm('Haqiqatan ham ushbu dehqonni o\'chirmoqchimisiz? Uning barcha fermer xo\'jaliklari, yer maydonlari va barcha tegishli ma\'lumotlari butunlay o\'chib ketadi!')">
                                    @csrf
                                    <button type="submit" class="text-red-650 hover:text-red-900 bg-red-50 hover:bg-red-100 p-1 px-2.5 rounded transition inline-block">
                                        O'chirish
                                    </button>
                                </form>
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

<!-- Dehqonni Tahrirlash Modal Overlay -->
<div id="editFarmerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Dehqon (Fermer) Ma'lumotlarini Tahrirlash</h3>
                <p class="text-xs text-gray-500 mt-0.5">Tizimda ro'yxatdan o'tgan dehqon yoki fermer yetakchisining ma'lumotlarini o'zgartirish</p>
            </div>
            <button onclick="closeEditFarmerModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form id="editFarmerForm" action="" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fermer F.I.Sh.</label>
                <input type="text" name="name" id="edit_farmer_name" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Telefon Raqami</label>
                <input type="text" name="phone" id="edit_farmer_phone" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hudud / Viloyat</label>
                    <select name="region_id" id="edit_farmer_region_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Viloyat...</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuman nomi</label>
                    <input type="text" name="district" id="edit_farmer_district" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 shrink-0">
                <button type="button" onclick="closeEditFarmerModal()" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Bekor qilish</button>
                <button type="submit" class="px-5 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<!-- Fermer Xo'jaligini (Yer) Tahrirlash Modal Overlay -->
<div id="editFarmModal" class="fixed inset-0 bg-slate-955/40 backdrop-blur-md z-[100] hidden">
    <div class="bg-white w-screen h-screen shadow-2xl overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Fermer Xo'jaligi Ma'lumotlarini Tahrirlash</h3>
                <p class="text-xs text-gray-500 mt-0.5">Xo'jalik nomi, maydoni va xaritadagi yer chegarasini tahrirlang</p>
            </div>
            <button onclick="closeEditFarmModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form id="editFarmForm" action="" method="POST" class="flex flex-col lg:flex-row overflow-hidden flex-1">
            @csrf
            
            <!-- Left inputs panel -->
            <div class="w-full lg:w-96 p-6 border-r border-slate-200 overflow-y-auto space-y-4 shrink-0">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fermer Xo'jaligi Nomi</label>
                    <input type="text" name="name" id="edit_farm_name" required placeholder="Masalan: G'ofur G'ulom Xo'jaligi" class="w-full px-3 py-2 border border-slate-350 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mas'ul Dehqon (Rahbar)</label>
                    <select name="user_id" id="edit_farm_user_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Rahbarni tanlang...</option>
                        @foreach($farmers as $f)
                            <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hudud / Viloyat</label>
                        <select name="region_id" id="edit_farm_region_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                            <option value="">Viloyat...</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuman nomi</label>
                        <input type="text" name="district" id="edit_farm_district" required placeholder="Masalan: Amudaryo tumani" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Maydoni (Gektar)</label>
                        <input type="number" step="0.1" name="size" id="edit_farm_size" required placeholder="Masalan: 45" class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tuproq Turi</label>
                        <select name="soil_type" id="edit_farm_soil_type" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                            <option value="Loyli tuproq">Loyli tuproq</option>
                            <option value="Serozyom (Bo'z tuproq)">Serozyom (Bo'z tuproq)</option>
                            <option value="Sho'rlangan tuproq">Sho'rlangan tuproq</option>
                            <option value="Qumloq tuproq">Qumloq tuproq</option>
                        </select>
                    </div>
                </div>

                <!-- Hidden inputs for coordinates array -->
                <input type="hidden" name="coordinates" id="editCoordinatesInput">

                <!-- Alert Warning for GIS -->
                <div id="editDrawWarning" class="p-3 bg-emerald-50 border border-emerald-250 rounded-xl text-xs text-emerald-700">
                    ✅ <strong>Chegara yuklangan:</strong> Xaritada joriy yer chegarasi ko'rsatilgan. Uni o'zgartirish uchun "Tozalash" tugmasini bosing va yangidan chizing.
                </div>
                
                <div class="flex flex-col gap-2.5 pt-4 border-t border-slate-100 shrink-0">
                    <button type="button" onclick="startEditNewParcel()" class="w-full px-4 py-2 bg-emerald-50 border border-emerald-300 rounded-lg text-xs font-bold text-emerald-850 hover:bg-emerald-100 transition flex items-center justify-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi mustaqil maydon chizish
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="undoEditLastPoint()" class="w-1/2 px-3 py-2 border border-slate-250 rounded-lg text-xs font-semibold text-slate-705 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                            </svg>
                            Ortga
                        </button>
                        <button type="button" onclick="clearEditDrawing()" class="w-1/2 px-3 py-2 border border-slate-250 rounded-lg text-xs font-semibold text-slate-705 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
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
                        O'zgarishlarni saqlash
                    </button>
                </div>
            </div>

            <!-- Right map panel -->
            <div class="flex-1 relative min-h-[400px] bg-slate-100">
                <div id="editMap" class="h-full w-full"></div>
                <!-- HUD instruction -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm border border-slate-250 rounded-lg px-3 py-2 text-[10px] text-slate-700 z-[1000] shadow-md max-w-xs font-medium">
                    💡 <strong>Yer chegarasini tahrirlash:</strong> Agar yer chegarasini o'zgartirmoqchi bo'lsangiz, "Tozalash" tugmasini bosing va xaritaga ketma-ket click qilib yangi poligon chizing.
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
    let polygonsList = [];
    let completedPolygonLayers = [];

    let editMap = null;
    let editPolyPoints = [];
    let editDrawPolygon = null;
    let editPointMarkers = [];
    let editPolygonsList = [];
    let editCompletedPolygonLayers = [];
    let editCompletedPolygonMarkers = [];



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

        const editForm = document.getElementById('editFarmForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                // If current active poly has points, try to save it
                if (editPolyPoints.length >= 3) {
                    editPolygonsList.push([...editPolyPoints]);
                    editPolyPoints = [];
                }
                
                // If they cleared it, or if they drew new shapes, we update the hidden input
                if (editPolygonsList.length > 0) {
                    document.getElementById('editCoordinatesInput').value = JSON.stringify(editPolygonsList);
                }
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
                drawMap = L.map('drawMap').setView([42.11005, 60.07327], 14); // Zoomed in for easier field drawing

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

                // Add Google Hybrid as default for drawing boundaries
                googleHybrid.addTo(drawMap);

                const baseMaps = {
                    "Gibrid xarita": googleHybrid,
                    "Sun'iy yo'ldosh": googleSatellite,
                    "Oddiy xarita": osmLayer,
                    "Google ko'chalari": googleStreets
                };

                // Add layers control on the top-left
                L.control.layers(baseMaps, null, { position: 'topleft' }).addTo(drawMap);

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

    function openEditFarmerModal(farmer) {
        document.getElementById('editFarmerForm').action = '/admin/farmers/update/' + farmer.id;
        document.getElementById('edit_farmer_name').value = farmer.name;
        document.getElementById('edit_farmer_phone').value = farmer.phone;
        document.getElementById('edit_farmer_region_id').value = farmer.region_id;
        document.getElementById('edit_farmer_district').value = farmer.district;

        const modal = document.getElementById('editFarmerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditFarmerModal() {
        const modal = document.getElementById('editFarmerModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function openEditFarmModal(farm) {
        // Update form action with actual farm ID
        document.getElementById('editFarmForm').action = '/admin/farms/update/' + farm.id;
        
        // Populate inputs
        document.getElementById('edit_farm_name').value = farm.name;
        document.getElementById('edit_farm_user_id').value = farm.user_id;
        document.getElementById('edit_farm_region_id').value = farm.region_id;
        document.getElementById('edit_farm_district').value = farm.district;
        document.getElementById('edit_farm_size').value = parseFloat(farm.size);
        document.getElementById('edit_farm_soil_type').value = farm.soil_type;
        document.getElementById('editCoordinatesInput').value = ''; // Reset coordinates input

        // Reset variables
        editPolyPoints = [];
        editDrawPolygon = null;
        editPointMarkers = [];
        editPolygonsList = [];
        editCompletedPolygonLayers = [];
        editCompletedPolygonMarkers = [];

        // Open modal
        const modal = document.getElementById('editFarmModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Initialize Map inside modal (wait for layout animation)
        if (!editMap) {
            setTimeout(() => {
                editMap = L.map('editMap');

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

                // Add Google Hybrid as default for drawing boundaries
                googleHybrid.addTo(editMap);

                const baseMaps = {
                    "Gibrid xarita": googleHybrid,
                    "Sun'iy yo'ldosh": googleSatellite,
                    "Oddiy xarita": osmLayer,
                    "Google ko'chalari": googleStreets
                };

                // Add layers control on the top-left
                L.control.layers(baseMaps, null, { position: 'topleft' }).addTo(editMap);

                // Handle click to draw polygon vertices
                editMap.on('click', function(e) {
                    // Ignore clicks on markers themselves
                    if (e.originalEvent.target.closest('.custom-dot-icon')) {
                        return;
                    }

                    const lat = parseFloat(e.latlng.lat);
                    const lng = parseFloat(e.latlng.lng);
                    
                    const idx = editPolyPoints.length;
                    editPolyPoints.push([lat, lng]);

                    // Add vertex marker to map
                    const marker = L.marker([lat, lng], {
                        icon: customDotIcon,
                        draggable: true
                    }).addTo(editMap);

                    // Handle drag behavior
                    marker.on('drag', function(evt) {
                        const newLatlng = evt.target.getLatLng();
                        editPolyPoints[idx] = [parseFloat(newLatlng.lat), parseFloat(newLatlng.lng)];
                        updateEditPolygon();
                    });

                    marker.on('dragend', function() {
                        updateEditPolygon();
                    });
                    
                    editPointMarkers.push(marker);
                    updateEditPolygon();
                });

                loadExistingFarmGeofences(farm);
            }, 100);
        } else {
            setTimeout(() => {
                editMap.invalidateSize();
                loadExistingFarmGeofences(farm);
            }, 100);
        }
    }

    function loadExistingFarmGeofences(farm) {
        // Clear map of previous edit shapes
        if (editMap) {
            editCompletedPolygonLayers.forEach(l => editMap.removeLayer(l));
            editPointMarkers.forEach(m => editMap.removeLayer(m));
            editCompletedPolygonMarkers.forEach(m => editMap.removeLayer(m));
            if (editDrawPolygon) {
                editMap.removeLayer(editDrawPolygon);
            }
        }
        editCompletedPolygonLayers = [];
        editPointMarkers = [];
        editCompletedPolygonMarkers = [];
        editDrawPolygon = null;
        editPolygonsList = [];
        editPolyPoints = [];

        // Load farm geofences
        let existingGeofences = farm.geofences || [];
        existingGeofences.forEach((gf) => {
            if (gf.coordinates && gf.coordinates.length > 0) {
                const points = gf.coordinates.map(c => [parseFloat(c[0]), parseFloat(c[1])]);
                editPolygonsList.push(points);
            }
        });

        redrawCompletedPolygons();

        // Fit map bounds to the existing geofences
        if (editCompletedPolygonLayers.length > 0) {
            const group = new L.featureGroup(editCompletedPolygonLayers);
            editMap.fitBounds(group.getBounds().pad(0.1));
            
            document.getElementById('editDrawWarning').className = "p-3 bg-emerald-50 border border-emerald-250 rounded-xl text-xs text-emerald-700";
            document.getElementById('editDrawWarning').innerHTML = "✅ <strong>Chegara yuklangan:</strong> Xaritada joriy yer chegarasi ko'rsatilgan. Burchaklardagi nuqtalarni surib tahrirlashingiz, chiziq ustiga click qilib yangi nuqta qo'shishingiz yoki nuqtani 2 marta click qilib o'chirishingiz mumkin.";
        } else if (farm.latitude && farm.longitude) {
            editMap.setView([farm.latitude, farm.longitude], 14);
            document.getElementById('editDrawWarning').className = "p-3 bg-amber-50 border border-amber-250 rounded-xl text-xs text-amber-700";
            document.getElementById('editDrawWarning').innerHTML = "⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang.";
        }
    }

    function redrawCompletedPolygons() {
        // Remove old completed layers and markers
        editCompletedPolygonLayers.forEach(l => editMap.removeLayer(l));
        editCompletedPolygonMarkers.forEach(m => editMap.removeLayer(m));
        
        editCompletedPolygonLayers = [];
        editCompletedPolygonMarkers = [];

        editPolygonsList.forEach((points, pIdx) => {
            const poly = L.polygon(points, {
                color: '#059669',
                fillColor: '#10B981',
                fillOpacity: 0.20,
                weight: 2.5
            }).addTo(editMap);
            editCompletedPolygonLayers.push(poly);

            // Click polygon to add vertex
            poly.on('click', function(e) {
                if (e.originalEvent.target.closest('.custom-dot-icon')) {
                    return;
                }
                
                const lat = parseFloat(e.latlng.lat);
                const lng = parseFloat(e.latlng.lng);
                
                let minDist = Infinity;
                let insertIdx = -1;
                for (let i = 0; i < points.length; i++) {
                    let p1 = points[i];
                    let p2 = points[(i + 1) % points.length];
                    let dist = getSqSegDist([lat, lng], p1, p2);
                    if (dist < minDist) {
                        minDist = dist;
                        insertIdx = i + 1;
                    }
                }
                
                if (insertIdx !== -1) {
                    points.splice(insertIdx, 0, [lat, lng]);
                    redrawCompletedPolygons();
                }
                
                L.DomEvent.stopPropagation(e);
            });

            // Draggable vertex markers
            points.forEach((pt, vIdx) => {
                const marker = L.marker(pt, {
                    icon: customDotIcon,
                    draggable: true
                }).addTo(editMap);

                marker.on('drag', function(evt) {
                    const newLatlng = evt.target.getLatLng();
                    points[vIdx] = [parseFloat(newLatlng.lat), parseFloat(newLatlng.lng)];
                    poly.setLatLngs(points);
                });

                marker.on('dragend', function() {
                    poly.setLatLngs(points);
                });

                // Double click marker to remove vertex
                marker.on('dblclick', function(evt) {
                    if (points.length > 3) {
                        points.splice(vIdx, 1);
                        redrawCompletedPolygons();
                    } else {
                        alert("Poligon kamida 3 ta nuqtadan iborat bo'lishi kerak!");
                    }
                });

                editCompletedPolygonMarkers.push(marker);
            });
        });
    }

    function getSqSegDist(p, p1, p2) {
        let x = p1[0], y = p1[1];
        let dx = p2[0] - x, dy = p2[1] - y;
        if (dx !== 0 || dy !== 0) {
            let t = ((p[0] - x) * dx + (p[1] - y) * dy) / (dx * dx + dy * dy);
            if (t > 1) {
                x = p2[0];
                y = p2[1];
            } else if (t > 0) {
                x += dx * t;
                y += dy * t;
            }
        }
        dx = p[0] - x;
        dy = p[1] - y;
        return dx * dx + dy * dy;
    }

    function updateEditPolygon() {
        if (editDrawPolygon && editMap) {
            editMap.removeLayer(editDrawPolygon);
            editDrawPolygon = null;
        }

        if (editPolyPoints.length >= 3) {
            editDrawPolygon = L.polygon(editPolyPoints, {
                color: '#1a3c2a',
                fillColor: '#10B981',
                fillOpacity: 0.25,
                weight: 2.5
            }).addTo(editMap);
        }

        const totalCompleted = editPolygonsList.length;
        const activeCount = editPolyPoints.length;

        const warn = document.getElementById('editDrawWarning');
        if (totalCompleted > 0 || activeCount >= 3) {
            warn.className = "p-3 bg-emerald-50 border border-emerald-250 rounded-xl text-xs text-emerald-700";
            let msg = `✅ <strong>Yangi yer maydonlari chizilmoqda:</strong> `;
            if (totalCompleted > 0) {
                msg += `${totalCompleted} ta alohida maydon chizildi. `;
            }
            if (activeCount > 0) {
                msg += `Hozirgi chizilayotgan maydonda ${activeCount} ta nuqta bor.`;
            } else {
                msg += `Yangi maydon chizishni boshlashingiz mumkin.`;
            }
            warn.innerHTML = msg;
        } else {
            warn.className = "p-3 bg-amber-50 border border-amber-250 rounded-xl text-xs text-amber-700";
            warn.innerHTML = "⚠️ <strong>Chegara chizilmagan:</strong> Xaritada fermer xo'jaligining yer chegarasini belgilang (kamida 3 ta nuqtaga click qiling).";
        }
    }

    function startEditNewParcel() {
        if (editPolyPoints.length >= 3) {
            editPolygonsList.push([...editPolyPoints]);
            
            editPointMarkers.forEach(m => editMap.removeLayer(m));
            editPointMarkers = [];
            
            editPolyPoints = [];
            editDrawPolygon = null;
            
            redrawCompletedPolygons();
            updateEditPolygon();
            alert("Ushbu maydon saqlandi! Yangi mustaqil maydon chizishingiz mumkin.");
        } else if (editPolyPoints.length > 0) {
            alert("Yangi maydon boshlash uchun joriy maydonda kamida 3 ta nuqta bo'linger kerak!");
        }
    }

    function undoEditLastPoint() {
        if (editPolyPoints.length > 0) {
            editPolyPoints.pop();
            const marker = editPointMarkers.pop();
            if (marker && editMap) {
                editMap.removeLayer(marker);
            }
            updateEditPolygon();
        }
    }

    function clearEditDrawing() {
        editPolyPoints = [];
        if (editDrawPolygon && editMap) {
            editMap.removeLayer(editDrawPolygon);
            editDrawPolygon = null;
        }
        if (editMap) {
            editPointMarkers.forEach(m => editMap.removeLayer(m));
        }
        editPointMarkers = [];
        editPolygonsList = [];
        
        redrawCompletedPolygons();
        
        document.getElementById('editCoordinatesInput').value = '[]';
        updateEditPolygon();
    }

    function closeEditFarmModal() {
        const modal = document.getElementById('editFarmModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>
@endsection
