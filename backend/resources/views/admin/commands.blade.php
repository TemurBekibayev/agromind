@extends('layouts.admin')

@section('title', 'Kutilayotgan GPS Buyruqlar Navbati')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Kutilayotgan GPS Buyruqlar Navbati</h2>
            <p class="text-sm text-gray-500">Mobil ilovadan yuborilgan, biroq trekkerlar tarmoqdan uzilganligi (offline) yoki GPS aloqasi yo'qligi sababli kutish rejimida turgan bloklash va blokdan ochish buyruqlari jurnali.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 border border-amber-200">
                Navbatda: {{ count($pendingCommands) }} ta buyruq
            </span>
        </div>
    </div>

    <!-- Alert Message -->
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
            <span class="font-medium">🎉 Muvaffaqiyatli:</span> {{ session('success') }}
        </div>
    @endif

    <!-- Commands Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Navbatdagi faol buyruqlar ro'yxati</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Texnika Nomi</th>
                        <th scope="col" class="px-6 py-4">GPS IMEI</th>
                        <th scope="col" class="px-6 py-4">Fermer Xo'jaligi</th>
                        <th scope="col" class="px-6 py-4 text-center">Buyruq turi</th>
                        <th scope="col" class="px-6 py-4 text-center">Tarmoq holati</th>
                        <th scope="col" class="px-6 py-4">Kutish sababi va yechimi</th>
                        <th scope="col" class="px-6 py-4 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($pendingCommands as $item)
                        <tr class="hover:bg-gray-50/75 transition">
                            <!-- Vehicle details -->
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center text-gray-500 font-semibold text-xs">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V9.75M3.75 14.25h16.5M3.75 14.25V7.5a1.5 1.5 0 011.5-1.5h13.5a1.5 1.5 0 011.5 1.5v6.75m-16.5 0H18M13.5 6V4.5a1.5 1.5 0 00-3 0V6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ $item['name'] }}</p>
                                        <p class="text-xs text-gray-400">Plita: {{ $item['plate_number'] }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- IMEI -->
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-gray-600">
                                {{ $item['gps_device_id'] }}
                            </td>

                            <!-- Farm Name -->
                            <td class="whitespace-nowrap px-6 py-4 text-gray-500 font-medium">
                                {{ $item['farm_name'] }}
                            </td>

                            <!-- Command Type -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if($item['command'] === 'RELAY,1#')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 border border-red-200">
                                        🔴 Dvigatelni Bloklash
                                    </span>
                                @elseif($item['command'] === 'RELAY,0#')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                        🟢 Blokdan Chiqarish
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 border border-gray-200">
                                        {{ $item['command'] }}
                                    </span>
                                @endif
                            </td>

                            <!-- Connection Status -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if($item['status'] === 'online')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-500 border border-gray-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>

                            <!-- Reason & Solution -->
                            <td class="px-6 py-4 max-w-md">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-red-600 flex items-center gap-1">
                                        ⚠️ Sabab: {{ $item['reason'] }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        💡 Yechim: {{ $item['solution'] }}
                                    </p>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <form action="/admin/commands/clear/{{ $item['id'] }}" method="POST" onsubmit="return confirm('Haqiqatan ham ushbu kutayotgan buyruqni o\'chirib yubormoqchimisiz?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition shadow-sm">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Tozalash
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold text-gray-800 text-sm">Kutilayotgan buyruqlar navbati bo'sh</p>
                                        <p class="text-xs text-gray-400">Barcha yuborilgan bloklash va ochish buyruqlari muvaffaqiyatli bajarilgan!</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
