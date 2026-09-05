@extends('layouts.admin')

@section('title', 'Kutilayotgan GPS Buyruqlari')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">GPS Buyruqlar Navbati (Queue)</h2>
            <p class="text-sm text-gray-500">Trekkerlar tarmoqdan uzilganda yoki signal yomon bo'lganda yuborilgan buyruqlar shu yerda navbatda turadi.</p>
        </div>
        
        @if(count($pendingCommands) > 0)
            <div class="flex items-center gap-3">
                <form action="/admin/vehicles/clear-all-commands" method="POST" onsubmit="return confirm('Haqiqatan ham barcha kutilayotgan buyruqlarni tozalashni xohlaysizmi?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Barcha Buyruqlarni Tozalash
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Success Notification -->
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-250 shadow-sm flex items-center justify-between">
            <span class="font-medium">🎉 Muvaffaqiyatli: {{ session('success') }}</span>
        </div>
    @endif

    <!-- Info Banner -->
    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-800 flex items-start gap-3 shadow-sm">
        <svg class="h-5 w-5 shrink-0 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <h4 class="font-bold mb-1">Buyruqlar navbati qanday ishlaydi?</h4>
            <p class="leading-relaxed">
                Mobil ilova orqali dvigatelni o'chirish (block) yoki yoqish (restore) buyrug'i berilganda, tizim ushbu buyruqni bir zumda GPS trekkerga uzatadi. Agar trekker internetdan uzilgan bo'lsa, buyruq yo'qolib ketmasligi uchun <strong>1 soat muddatga navbatga (Cache)</strong> joylashtiriladi. Trekker qayta aloqaga chiqib, birinchi telemetriya signalini yuborgan zahoti ushbu buyruqni qabul qilib oladi va navbatdan o'chadi. Agar buyruq keraksiz bo'lib qolgan bo'lsa yoki xatolik yuz bergan bo'lsa, uni qo'lda tozalashingiz mumkin.
            </p>
        </div>
    </div>

    <!-- Queue List -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @if(count($pendingCommands) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th scope="col" class="px-6 py-4">Texnika</th>
                            <th scope="col" class="px-6 py-4">Fermer Xo'jaligi</th>
                            <th scope="col" class="px-6 py-4">GPS IMEI</th>
                            <th scope="col" class="px-6 py-4">Kutilayotgan Buyruq</th>
                            <th scope="col" class="px-6 py-4">Holati</th>
                            <th scope="col" class="px-6 py-4 text-right">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                        @foreach($pendingCommands as $item)
                            @php
                                $vehicle = $item['vehicle'];
                                $cmd = $item['command'];
                            @endphp
                            <tr class="hover:bg-gray-50/75 transition">
                                <!-- Vehicle -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                                            <span class="font-bold text-lg">T</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $vehicle->name }}</p>
                                            <p class="text-xs font-mono text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 inline-block mt-0.5">{{ $vehicle->plate_number }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Farm -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($vehicle->farm)
                                        <div>
                                            <p class="font-medium text-gray-900 text-xs">{{ $vehicle->farm->name }}</p>
                                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $vehicle->farm->district }}</p>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Biriktirilmagan</span>
                                    @endif
                                </td>

                                <!-- IMEI -->
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-gray-600">
                                    {{ $vehicle->gps_device_id }}
                                </td>

                                <!-- Command -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($cmd === 'RELAY,1#')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                                            Dvigatelni O'chirish (RELAY,1#)
                                        </span>
                                    @elseif($cmd === 'RELAY,0#')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Dvigatelni Yoqish (RELAY,0#)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200 font-mono">
                                            {{ $cmd }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                        Trekker ulanishi kutilmoqda...
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap px-6 py-4 text-right text-xs font-medium shrink-0">
                                    <form action="/admin/vehicles/clear-command/{{ $vehicle->id }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 p-1.5 px-3 rounded transition font-semibold">
                                            Tozalash
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="p-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-bold text-gray-900">Buyruqlar navbati bo'sh!</h3>
                <p class="mx-auto mt-2 max-w-sm text-xs text-gray-500">Hozirda hech qanday kutilayotgan GPS buyruqlari mavjud emas. Barcha jo'natilgan buyruqlar trekkerlar tomonidan qabul qilingan yoki navbat bo'sh.</p>
            </div>
        @endif
    </div>
</div>
@endsection
