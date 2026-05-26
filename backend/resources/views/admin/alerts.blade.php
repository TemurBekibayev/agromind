@extends('layouts.admin')

@section('title', 'Tizim Ogohlantirishlari va Xavflar')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Favqulodda Ogohlantirishlar Logi</h2>
            <p class="text-sm text-gray-500">Geofence (hudud) buzilishi, yoqilg'i tugashi va texnik nosozlik signallari tarixi.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 border border-red-200">
                Jami: {{ $alerts->count() }} ta ogohlantirish
            </span>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                Faol: {{ $alerts->where('status', 'active')->count() }} ta faol xavf
            </span>
        </div>
    </div>

    <!-- Alert List Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Xavf Turi va Holati</th>
                        <th scope="col" class="px-6 py-4">Tafsilotlar</th>
                        <th scope="col" class="px-6 py-4">Aloqador Texnika</th>
                        <th scope="col" class="px-6 py-4">Joylashgan Ferma</th>
                        <th scope="col" class="px-6 py-4">Yuz bergan vaqt</th>
                        <th scope="col" class="px-6 py-4">Holat / Hal qilinishi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($alerts as $alert)
                        @php
                            $isActive = $alert->status === 'active';
                            $rowBg = $isActive ? 'bg-red-50/15 hover:bg-red-50/25' : 'hover:bg-gray-50/75';
                            
                            $typeColor = Str::contains(Str::lower($alert->type), 'geofence') ? 'text-red-700 bg-red-50 border-red-200' : 
                                         (Str::contains(Str::lower($alert->type), 'yoqilg') ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-blue-700 bg-blue-50 border-blue-200');
                        @endphp
                        <tr class="{{ $rowBg }} transition">
                            <!-- Type and Indicator -->
                            <td class="whitespace-nowrap px-6 py-4 font-semibold">
                                <div class="flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full shrink-0 {{ $isActive ? 'bg-red-600 animate-ping' : 'bg-gray-400' }}"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $typeColor }}">
                                        {{ $alert->type }}
                                    </span>
                                </div>
                            </td>

                            <!-- Message Message -->
                            <td class="px-6 py-4 font-medium text-gray-900 max-w-xs">
                                {{ $alert->message }}
                            </td>

                            <!-- Connected Vehicle -->
                            <td class="whitespace-nowrap px-6 py-4 font-medium">
                                @if($alert->vehicle)
                                    <div>
                                        <p class="text-gray-900">{{ $alert->vehicle->name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $alert->vehicle->plate_number }}</p>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tizimli/Dasturiy</span>
                                @endif
                            </td>

                            <!-- Connected Farm -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($alert->farm)
                                    <div>
                                        <p class="text-gray-900 font-medium">{{ $alert->farm->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $alert->farm->region ? $alert->farm->region->name : '' }}</p>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tizim</span>
                                @endif
                            </td>

                            <!-- Time Triggered -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-medium text-gray-500">
                                <p class="text-gray-900">{{ $alert->created_at->format('d.m.Y H:i:s') }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $alert->created_at->diffForHumans() }}</p>
                            </td>

                            <!-- Resolution Status -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-medium">
                                @if($isActive)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 border border-red-200">
                                        Faol / Kutmoqda
                                    </span>
                                @else
                                    <div>
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                            Hal etilgan
                                        </span>
                                        <p class="text-[9px] text-gray-400 mt-0.5">Yopilgan: {{ $alert->updated_at->format('H:i') }}</p>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda ogohlantirish va xavf signallari jurnali bo'sh.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
