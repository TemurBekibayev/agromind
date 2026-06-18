@extends('layouts.admin')

@section('title', 'Suv Limitlari va Sarfi Boshqaruvi')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Suv Limitlari va Sarfi Boshqaruvi</h2>
            <p class="text-sm text-gray-550">Fermer xo'jaliklari bo'yicha dekadalar va manbalar kesimida ajratilgan suv limitlari va amalda sarflangan suv hajmlari.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/water/create" class="inline-flex items-center gap-1.5 rounded-lg bg-forest-700 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-forest-600 transition border border-forest-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Yangi Kiritish
            </a>
            <!-- Link to external form with token -->
            <a href="/water-entry?token=agromind_water_entry_2026" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-4 py-2.5 text-xs font-bold text-blue-700 border border-blue-200 hover:bg-blue-100 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                Tashqi Havola (Xodimlar uchun)
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Panel -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Jami Ajratilgan Limit</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalLimit, 2, '.', ' ') }} m³</h3>
                </div>
                <div class="h-10 w-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 font-bold shrink-0">
                    💧
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Amalda Sarflangan Suv</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalUsed, 2, '.', ' ') }} m³</h3>
                </div>
                <div class="h-10 w-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold shrink-0">
                    📈
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Suv Sarfi Balansi</p>
                    @php
                        $percentage = $totalLimit > 0 ? ($totalUsed / $totalLimit) * 100 : 0;
                        $colorClass = $percentage > 100 ? 'text-red-650' : ($percentage > 85 ? 'text-amber-600' : 'text-forest-700');
                    @endphp
                    <h3 class="text-2xl font-black {{ $colorClass }} mt-1">{{ number_format($percentage, 1) }}%</h3>
                </div>
                <div class="h-10 w-10 rounded-lg bg-gray-50 flex items-center justify-center font-bold shrink-0">
                    ⚖️
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Fermer Xo'jaligi</label>
                <select name="farm_id" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500">
                    <option value="">Barchasi</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ request('farm_id') == $farm->id ? 'selected' : '' }}>
                            {{ $farm->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Yil</label>
                <select name="year" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500">
                    <option value="">Barchasi</option>
                    @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}-yil</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Oy</label>
                <select name="month" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500">
                    <option value="">Barchasi</option>
                    <option value="1" {{ request('month') == 1 ? 'selected' : '' }}>Yanvar</option>
                    <option value="2" {{ request('month') == 2 ? 'selected' : '' }}>Fevral</option>
                    <option value="3" {{ request('month') == 3 ? 'selected' : '' }}>Mart</option>
                    <option value="4" {{ request('month') == 4 ? 'selected' : '' }}>Aprel</option>
                    <option value="5" {{ request('month') == 5 ? 'selected' : '' }}>May</option>
                    <option value="6" {{ request('month') == 6 ? 'selected' : '' }}>Iyun</option>
                    <option value="7" {{ request('month') == 7 ? 'selected' : '' }}>Iyul</option>
                    <option value="8" {{ request('month') == 8 ? 'selected' : '' }}>Avgust</option>
                    <option value="9" {{ request('month') == 9 ? 'selected' : '' }}>Sentyabr</option>
                    <option value="10" {{ request('month') == 10 ? 'selected' : '' }}>Oktyabr</option>
                    <option value="11" {{ request('month') == 11 ? 'selected' : '' }}>Noyabr</option>
                    <option value="12" {{ request('month') == 12 ? 'selected' : '' }}>Dekabr</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-forest-700 hover:bg-forest-600 px-4 py-2 text-xs font-bold text-white transition border border-forest-600">
                    Filtrlash
                </button>
                <a href="/admin/water" class="rounded-lg bg-gray-100 hover:bg-gray-200 px-4 py-2 text-xs font-bold text-gray-500 transition border border-gray-200 text-center">
                    Tozalash
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Tizimdagi Suv Sarfi va Limit Yozuvlari</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Fermer Xo'jaligi</th>
                        <th class="px-6 py-4">Sana va Dekada</th>
                        <th class="px-6 py-4">Suv Manbai</th>
                        <th class="px-6 py-4 text-center">Limit, m³</th>
                        <th class="px-6 py-4 text-center">Ishlatilgan, m³</th>
                        <th class="px-6 py-4 text-center">Farq (Balans)</th>
                        <th class="px-6 py-4 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @php
                        $months = [
                            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart',
                            4 => 'Aprel', 5 => 'May', 6 => 'Iyun',
                            7 => 'Iyul', 8 => 'Avgust', 9 => 'Sentyabr',
                            10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr'
                        ];
                        $sources = [
                            'surface' => 'Er usti (Daryo/Kanal)',
                            'groundwater' => 'Er osti (Quduq)',
                            'drainage' => 'Kollektor-drenaj'
                        ];
                    @endphp

                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50/75 transition">
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">
                                {{ $record->farm ? $record->farm->name : 'Noma\'lum' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-650">
                                {{ $record->year }}-yil, {{ $months[$record->month] }}
                                <span class="ml-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] font-bold border border-gray-200">
                                    {{ $record->decade }}-dekada
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-semibold">
                                @if($record->water_source === 'surface')
                                    <span class="text-blue-700 bg-blue-50 px-2.5 py-1 rounded border border-blue-200">💧 {{ $sources[$record->water_source] }}</span>
                                @elseif($record->water_source === 'groundwater')
                                    <span class="text-amber-700 bg-amber-50 px-2.5 py-1 rounded border border-amber-200">🪵 {{ $sources[$record->water_source] }}</span>
                                @else
                                    <span class="text-purple-700 bg-purple-50 px-2.5 py-1 rounded border border-purple-200">🌱 {{ $sources[$record->water_source] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center font-bold text-gray-900">
                                {{ number_format($record->limit_m3, 2, '.', ' ') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center font-bold text-gray-950">
                                {{ number_format($record->used_m3, 2, '.', ' ') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center font-bold">
                                @php
                                    $diff = $record->limit_m3 - $record->used_m3;
                                    $percent = $record->limit_m3 > 0 ? ($record->used_m3 / $record->limit_m3) * 100 : 0;
                                @endphp
                                @if($diff >= 0)
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-xs">
                                        +{{ number_format($diff, 1) }} m³ ({{ number_format($percent, 0) }}%)
                                    </span>
                                @else
                                    <span class="text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200 text-xs">
                                        {{ number_format($diff, 1) }} m³ ({{ number_format($percent, 0) }}%)
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <form method="POST" action="/admin/water/destroy/{{ $record->id }}" onsubmit="return confirm('Haqiqatdan ham ushbu suv yozuvini o\'chirib tashlamoqchimisiz?')" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800 transition">
                                        O'chirish
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 font-medium">
                                Suv sarfi bo'yicha yozuvlar topilmadi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
