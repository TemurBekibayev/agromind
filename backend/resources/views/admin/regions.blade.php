@extends('layouts.admin')

@section('title', 'Hududlar Boshqaruvi')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Hududlar va Tumanlar Boshqaruvi</h2>
            <p class="text-sm text-gray-500">Respublika bo'yicha agro-monitoring qamrab olingan hududlar, ro'yxatdan o'tgan dehqonlar va fermer xo'jaliklari statistikasi.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700 border border-forest-200">
                Qamrab olingan: {{ $regions->count() }} ta hudud
            </span>
        </div>
    </div>

    <!-- Regions Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        @foreach($regions->take(3) as $region)
            <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Agro-Monitoring Hududi</p>
                        <h3 class="text-xl font-bold text-gray-900 mt-1">{{ $region->name }}</h3>
                    </div>
                    <div class="h-10 w-10 rounded-lg bg-forest-50 flex items-center justify-center text-forest-700 font-bold shrink-0">
                        {{ mb_substr($region->name, 0, 1) }}
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Jami Fermerlar</p>
                        <p class="text-lg font-bold text-gray-800 mt-0.5">{{ $region->users_count }} ta</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Fermer Xo'jaliklari</p>
                        <p class="text-lg font-bold text-forest-700 mt-0.5">{{ $region->farms_count }} ta</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Detailed Regions Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Barcha Qamrab Olingan Hududlar Tafsilotlari</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Hudud Nomi</th>
                        <th scope="col" class="px-6 py-4">Turi</th>
                        <th scope="col" class="px-6 py-4 text-center">Fermerlar soni</th>
                        <th scope="col" class="px-6 py-4 text-center">Fermer Xo'jaliklari</th>
                        <th scope="col" class="px-6 py-4">Geodezik Ma'lumot (Geofencing)</th>
                        <th scope="col" class="px-6 py-4 text-right">Tizim holati</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($regions as $region)
                        <tr class="hover:bg-gray-50/75 transition">
                            <!-- Name -->
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded bg-forest-50 flex items-center justify-center text-forest-700 font-semibold text-xs">
                                        {{ mb_substr($region->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ $region->name }}</p>
                                        <p class="text-xs text-gray-400">ID: #{{ $region->id }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-500">
                                Viloyat / Ma'muriy Hudud
                            </td>

                            <!-- Registered Users -->
                            <td class="whitespace-nowrap px-6 py-4 text-center font-bold text-gray-800">
                                {{ $region->users_count }}
                            </td>

                            <!-- Registered Farms -->
                            <td class="whitespace-nowrap px-6 py-4 text-center font-bold text-forest-700">
                                {{ $region->farms_count }}
                            </td>

                            <!-- Geojson details -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-mono text-gray-500">
                                @if(is_array($region->geojson) && count($region->geojson) > 0)
                                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Poligon mavjud ({{ count($region->geojson) }} nuqtali)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-gray-500 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        Belgilanmagan
                                    </span>
                                @endif
                            </td>

                            <!-- System Status -->
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Faol monitoring
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda qamrab olingan hududlar ro'yxati mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
