@extends('layouts.admin')

@section('title', 'Texnikalar Monitoringi')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Mavjud Texnikalar va Telemetriya</h2>
            <p class="text-sm text-gray-500">Dala texnikalarining GPS monitoringi, yoqilg'i darajasi va joriy tezligi.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 shrink-0">
                Jami: {{ $vehicles->count() }} ta texnika
            </span>
            <form action="/admin/vehicles/clear-all-commands" method="POST" class="inline-block" onsubmit="return confirm('Haqiqatan ham barcha kutilayotgan GPS buyruqlarini tozalashni xohlaysizmi?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Buyruqlarni Tozalash
                </button>
            </form>
            <button onclick="openAddVehicleModal()" class="inline-flex items-center gap-2 rounded-lg bg-forest-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-forest-600 transition shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Yangi Texnika Qo'shish
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
    @php
        $pendingFuelAlerts = \App\Models\FuelAlert::with('vehicle')->where('status', 'pending_check')->get();
    @endphp

    @if($pendingFuelAlerts->isNotEmpty())
        <div class="p-6 rounded-xl bg-amber-50 border border-amber-250 shadow-sm space-y-4">
            <div class="flex items-center gap-2 text-amber-800">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="font-bold text-sm">Diqqat! Tizimda tasdiqlash kutilayotgan shubhali yoqilg'i holatlari aniqlandi</h3>
            </div>
            
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($pendingFuelAlerts as $alert)
                    <div class="p-4 rounded-lg bg-white border border-amber-200 text-xs shadow-sm flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-gray-900">{{ $alert->vehicle->name }}</span>
                                <span class="font-mono text-gray-500 bg-gray-100 px-1 py-0.5 rounded">{{ $alert->vehicle->plate_number }}</span>
                            </div>
                            <p class="text-gray-700 leading-relaxed">{{ $alert->description }}</p>
                            <p class="text-[10px] text-gray-400 mt-1 font-mono">Aniqlangan vaqt: {{ $alert->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="/admin/fuel-alerts/{{ $alert->id }}/resolve" method="POST" class="inline-block">
                                @csrf
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="px-3 py-1.5 bg-forest-700 text-white rounded font-semibold hover:bg-forest-600 transition text-[11px]">
                                    Tasdiqlash (Kalibrlash)
                                </button>
                            </form>
                            <form action="/admin/fuel-alerts/{{ $alert->id }}/resolve" method="POST" class="inline-block">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 transition text-[11px]">
                                    Rad etish
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <!-- Vehicles Table & Grid -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Texnika va Raqami</th>
                        <th scope="col" class="px-6 py-4">Turi</th>
                        <th scope="col" class="px-6 py-4">Tegishli Ferma</th>
                        <th scope="col" class="px-6 py-4">GPS Device (IMEI) / SIM</th>
                        <th scope="col" class="px-6 py-4">Joriy Holat (GPS)</th>
                        <th scope="col" class="px-6 py-4">Yoqilg'i Darajasi</th>
                        <th scope="col" class="px-6 py-4">Yoqilg'i Sarfi (Sarf/Masofa)</th>
                        <th scope="col" class="px-6 py-4">Tezlik & Koordinatalar</th>
                        <th scope="col" class="px-6 py-4">Oxirgi signal</th>
                        <th scope="col" class="px-6 py-4 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($vehicles as $vehicle)
                        @php
                            $status = $vehicle->status;
                            $latestTrack = $vehicle->latestGpsTrack;
                            $pendingCmd = $vehicle->gps_device_id ? \Illuminate\Support\Facades\Cache::get("gps_command_{$vehicle->gps_device_id}") : null;
                        @endphp
                        <tr class="hover:bg-gray-50/75 transition">
                            <!-- Name & Plate -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                        @if(Str::contains(Str::lower($vehicle->type), ['kombayn', 'combine']))
                                            <span class="font-bold text-lg">K</span>
                                        @else
                                            <span class="font-bold text-lg">T</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $vehicle->name }}</p>
                                        <p class="text-xs font-mono text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 inline-block mt-0.5">{{ $vehicle->plate_number }}</p>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Type -->
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-850">
                                {{ $vehicle->type === 'tractor' ? 'Traktor' : ($vehicle->type === 'combine' ? 'Kombayn' : 'Boshqa') }}
                            </td>

                            <!-- Farm -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($vehicle->farm)
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $vehicle->farm->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $vehicle->farm->region ? $vehicle->farm->region->name : '' }}</p>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Biriktirilmagan</span>
                                @endif
                            </td>

                            <!-- IMEI -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-semibold">
                                <div class="font-mono text-gray-700">{{ $vehicle->gps_device_id }}</div>
                                @if($vehicle->sim_number)
                                    <div class="text-[11px] text-gray-500 font-normal mt-0.5">{{ $vehicle->sim_number }}</div>
                                @else
                                    <div class="text-[11px] text-gray-400 italic font-normal mt-0.5">Sim raqami yo'q</div>
                                @endif
                                
                                @if($pendingCmd)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border text-amber-700 bg-amber-50 border-amber-200 animate-pulse font-mono">
                                            Navbatda: {{ $pendingCmd }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($status === 'online')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Online
                                    </span>
                                @elseif($status === 'problem')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Kam yoqilg'i
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 border border-gray-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>

                            <!-- Fuel Level -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($latestTrack)
                                    @php
                                        $fuel = floatval($latestTrack->fuel_level);
                                        $fuelColor = $fuel < 15 ? 'bg-red-500' : ($fuel < 40 ? 'bg-amber-500' : 'bg-emerald-600');
                                    @endphp
                                    <div class="w-32">
                                        <div class="flex items-center justify-between text-xs font-medium mb-1">
                                            <span class="text-gray-700">{{ $fuel }}%</span>
                                            <span class="text-gray-400">{{ $vehicle->fuel_capacity }}L</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden border border-gray-200">
                                            <div class="{{ $fuelColor }} h-1.5 rounded-full" style="width: {{ $fuel }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">No'malum</span>
                                @endif
                            </td>

                            <!-- Fuel Consumption -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="space-y-1 text-xs">
                                    <p class="font-semibold text-gray-900">Masofa: {{ $vehicle->getDistanceTraveled() }} km</p>
                                    <p class="text-[11px] text-gray-700">Qoldiq: <span class="font-bold text-blue-600">{{ round($vehicle->current_fuel_level, 1) }} L</span></p>
                                    
                                    @php
                                        $trustScore = $vehicle->trust_score;
                                        $trustColor = $trustScore >= 80 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($trustScore >= 50 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-red-700 bg-red-50 border-red-200');
                                    @endphp
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border {{ $trustColor }}">
                                            Ishonch: {{ $trustScore }}%
                                        </span>
                                    </div>
                                    
                                    <p class="text-[10px] text-gray-500 mt-1">Me'yorlar: Yo'l: {{ $vehicle->nominal_rate_road }}L/s | Yengil: {{ $vehicle->nominal_rate_work_light }}L/s | Og'ir: {{ $vehicle->nominal_rate_work_heavy }}L/s</p>
                                </div>
                            </td>

                            <!-- Speed & GPS Coordinates -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($latestTrack)
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">{{ $latestTrack->speed }} km/s</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5 font-mono">{{ number_format($latestTrack->latitude, 5) }}, {{ number_format($latestTrack->longitude, 5) }}</p>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Ma'lumotlar yo'q</span>
                                @endif
                            </td>

                            <!-- Last Signal Time -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                @if($latestTrack && $latestTrack->recorded_at)
                                    {{ $latestTrack->recorded_at->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                            
                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4 text-right text-xs font-medium space-x-2 shrink-0">
                                @if($pendingCmd)
                                    <form action="/admin/vehicles/clear-command/{{ $vehicle->id }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 p-1 px-2.5 rounded transition inline-block">
                                            Buyruqni Tozalash
                                        </button>
                                    </form>
                                @endif
                                <button onclick="openEditVehicleModal({{ json_encode($vehicle->only(['id', 'name', 'type', 'plate_number', 'farm_id', 'gps_device_id', 'sim_number', 'fuel_capacity', 'nominal_rate_road', 'nominal_rate_work_light', 'nominal_rate_work_heavy'])) }})" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-1 px-2.5 rounded transition inline-block">
                                    Tahrirlash
                                </button>
                                <form action="/admin/vehicles/destroy/{{ $vehicle->id }}" method="POST" class="inline-block" onsubmit="return confirm('Haqiqatan ham ushbu texnikani o\'chirmoqchimisiz? Barcha tegishli GPS ma\'lumotlari ham butunlay o\'chib ketadi!')">
                                    @csrf
                                    <button type="submit" class="text-red-650 hover:text-red-900 bg-red-50 hover:bg-red-100 p-1 px-2.5 rounded transition inline-block">
                                        O'chirish
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda hozircha ro'yxatdan o'tgan texnikalar mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yangi Texnika va GPS IMEI Modal Overlay -->
<div id="addVehicleModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Yangi Texnika Qo'shish</h3>
                <p class="text-xs text-gray-500 mt-0.5">Dala texnikasini ro'yxatga oling va unga GPS IMEI raqamini biriktiring</p>
            </div>
            <button onclick="closeAddVehicleModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form action="/admin/vehicles/store" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Texnika Nomi</label>
                    <input type="text" name="name" required placeholder="Masalan: TTZ-80 Traktor" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dala Texnikasi Turi</label>
                    <select name="type" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="tractor">Traktor</option>
                        <option value="combine">Kombayn</option>
                        <option value="other">Boshqa texnika / Yuk mashinasi</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Davlat Raqami</label>
                    <input type="text" name="plate_number" required placeholder="Masalan: 01 A 123 AA" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Biriktirilgan Ferma</label>
                    <select name="farm_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Fermani tanlang...</option>
                        @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }} (Rahbar: {{ $farm->owner ? $farm->owner->name : 'Noma\'lum' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">GPS Qurilma IMEI</label>
                    <input type="text" name="gps_device_id" required placeholder="Masalan: 862292055529242" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">GPS Sim Raqami</label>
                    <input type="text" name="sim_number" placeholder="Masalan: +998901234567" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yoqilg'i Sig'imi (Litr)</label>
                    <input type="number" step="1" name="fuel_capacity" required placeholder="Masalan: 150" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yo'l me'yori (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_road" placeholder="Avtomatik" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yengil ish (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_work_light" placeholder="Avtomatik" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Og'ir ish (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_work_heavy" placeholder="Avtomatik" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddVehicleModal()" class="w-1/2 px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Bekor qilish</button>
                <button type="submit" class="w-1/2 px-4 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Ro'yxatga olish</button>
            </div>
        </form>
    </div>
</div>

<!-- Tahrirlash Modal Overlay -->
<div id="editVehicleModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Texnika Ma'lumotlarini Tahrirlash</h3>
                <p class="text-xs text-gray-500 mt-0.5">Dala texnikasi va unga tegishli ma'lumotlarni o'zgartirish</p>
            </div>
            <button onclick="closeEditVehicleModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form id="editVehicleForm" action="" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Texnika Nomi</label>
                    <input type="text" name="name" id="edit_name" required placeholder="Masalan: TTZ-80 Traktor" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dala Texnikasi Turi</label>
                    <select name="type" id="edit_type" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="tractor">Traktor</option>
                        <option value="combine">Kombayn</option>
                        <option value="other">Boshqa texnika / Yuk mashinasi</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Davlat Raqami</label>
                    <input type="text" name="plate_number" id="edit_plate_number" required placeholder="Masalan: 01 A 123 AA" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Biriktirilgan Ferma</label>
                    <select name="farm_id" id="edit_farm_id" required class="w-full px-3 py-2 border border-slate-355 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                        <option value="">Fermani tanlang...</option>
                        @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }} (Rahbar: {{ $farm->owner ? $farm->owner->name : 'Noma\'lum' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">GPS Qurilma IMEI</label>
                    <input type="text" name="gps_device_id" id="edit_gps_device_id" required placeholder="Masalan: 862292055529242" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">GPS Sim Raqami</label>
                    <input type="text" name="sim_number" id="edit_sim_number" placeholder="Masalan: +998901234567" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yoqilg'i Sig'imi (Litr)</label>
                    <input type="number" step="1" name="fuel_capacity" id="edit_fuel_capacity" required placeholder="Masalan: 150" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yo'l me'yori (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_road" id="edit_nominal_rate_road" placeholder="Yo'l sarfi" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yengil ish (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_work_light" id="edit_nominal_rate_work_light" placeholder="Yengil ish" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Og'ir ish (L/s)</label>
                    <input type="number" step="0.1" name="nominal_rate_work_heavy" id="edit_nominal_rate_work_heavy" placeholder="Og'ir ish" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditVehicleModal()" class="w-1/2 px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Bekor qilish</button>
                <button type="submit" class="w-1/2 px-4 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Saqlash</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openAddVehicleModal() {
        const modal = document.getElementById('addVehicleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAddVehicleModal() {
        const modal = document.getElementById('addVehicleModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function openEditVehicleModal(vehicle) {
        // Update form action with actual vehicle ID
        document.getElementById('editVehicleForm').action = '/admin/vehicles/update/' + vehicle.id;
        
        // Populate input values
        document.getElementById('edit_name').value = vehicle.name;
        document.getElementById('edit_type').value = vehicle.type;
        document.getElementById('edit_plate_number').value = vehicle.plate_number;
        document.getElementById('edit_farm_id').value = vehicle.farm_id;
        document.getElementById('edit_gps_device_id').value = vehicle.gps_device_id;
        document.getElementById('edit_sim_number').value = vehicle.sim_number || '';
        document.getElementById('edit_fuel_capacity').value = Math.round(vehicle.fuel_capacity);
        document.getElementById('edit_nominal_rate_road').value = vehicle.nominal_rate_road || '';
        document.getElementById('edit_nominal_rate_work_light').value = vehicle.nominal_rate_work_light || '';
        document.getElementById('edit_nominal_rate_work_heavy').value = vehicle.nominal_rate_work_heavy || '';
        
        // Open modal
        const modal = document.getElementById('editVehicleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditVehicleModal() {
        const modal = document.getElementById('editVehicleModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>
@endsection
