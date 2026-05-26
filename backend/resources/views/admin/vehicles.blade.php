@extends('layouts.admin')

@section('title', 'Texnikalar Monitoringi')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Mavjud Texnikalar va Telemetriya</h2>
            <p class="text-sm text-gray-500">Dala texnikalarining GPS monitoringi, yoqilg'i darajasi va joriy tezligi.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200">
                Jami: {{ $vehicles->count() }} ta texnika
            </span>
        </div>
    </div>

    <!-- Vehicles Table & Grid -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Texnika va Raqami</th>
                        <th scope="col" class="px-6 py-4">Turi</th>
                        <th scope="col" class="px-6 py-4">Tegishli Ferma</th>
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
                                            <!-- Combine icon or letter -->
                                            <span class="font-bold text-lg">K</span>
                                        @else
                                            <!-- Tractor icon or letter -->
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
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-800">
                                {{ $vehicle->type }}
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
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda hozircha ro'yxatdan o'tgan texnikalar mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
