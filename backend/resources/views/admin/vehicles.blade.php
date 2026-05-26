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

    <!-- Vehicles Table & Grid -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Texnika va Raqami</th>
                        <th scope="col" class="px-6 py-4">Turi</th>
                        <th scope="col" class="px-6 py-4">Tegishli Ferma</th>
                        <th scope="col" class="px-6 py-4">GPS Device (IMEI)</th>
                        <th scope="col" class="px-6 py-4">Joriy Holat (GPS)</th>
                        <th scope="col" class="px-6 py-4">Yoqilg'i Darajasi</th>
                        <th scope="col" class="px-6 py-4">Tezlik & Koordinatalar</th>
                        <th scope="col" class="px-6 py-4">Oxirgi signal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($vehicles as $vehicle)
                        @php
                            $status = $vehicle->status;
                            $latestTrack = $vehicle->latestGpsTrack;
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
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-805">
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
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-gray-600 font-semibold">
                                {{ $vehicle->gps_device_id }}
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
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Yoqilg'i Sig'imi (Litr)</label>
                    <input type="number" step="1" name="fuel_capacity" required placeholder="Masalan: 150" class="w-full px-3 py-2 border border-slate-250 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-forest-500 bg-white">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddVehicleModal()" class="w-1/2 px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Bekor qilish</button>
                <button type="submit" class="w-1/2 px-4 py-2 bg-forest-700 text-white rounded-lg text-xs font-semibold hover:bg-forest-600 transition">Ro'yxatga olish</button>
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
</script>
@endsection
