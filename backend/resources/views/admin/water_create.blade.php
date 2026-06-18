@extends('layouts.admin')

@section('title', 'Suv Limitini Kiritish')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Yangi Suv Limiti va Sarfini Kiritish</h2>
            <p class="text-sm text-gray-500 font-medium">Fermer xo'jaligi bo'yicha dekada va manba kesimida suv hajmlarini to'ldirish shakli.</p>
        </div>
        <div>
            <a href="/admin/water" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-200 transition border border-gray-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Orqaga Qaytish
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

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold space-y-1">
            @foreach($errors->all() as $error)
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                    {{ $error }}
                </div>
            @endforeach
        </div>
    @endif

    <!-- Selection Filters Form (GET method) -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="" id="filterForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Fermer Xo'jaligi</label>
                <select name="farm_id" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500 cursor-pointer">
                    <option value="">-- Tanlang --</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ request('farm_id') == $farm->id ? 'selected' : '' }}>
                            {{ $farm->name }} ({{ $farm->owner ? $farm->owner->name : 'Noma\'lum' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Yil</label>
                <select name="year" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500 cursor-pointer">
                    @php
                        $currentYear = date('Y');
                    @endphp
                    @for($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                        <option value="{{ $y }}" {{ (request('year', $currentYear) == $y) ? 'selected' : '' }}>{{ $y }}-yil</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1.5">Oy</label>
                <select name="month" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-forest-500 cursor-pointer">
                    <option value="">-- Tanlang --</option>
                    <optgroup label="Sug'orish mavsumi (Aprel - Sentyabr)">
                        <option value="4" {{ request('month') == 4 ? 'selected' : '' }}>Aprel</option>
                        <option value="5" {{ request('month') == 5 ? 'selected' : '' }}>May</option>
                        <option value="6" {{ request('month') == 6 ? 'selected' : '' }}>Iyun</option>
                        <option value="7" {{ request('month') == 7 ? 'selected' : '' }}>Iyul</option>
                        <option value="8" {{ request('month') == 8 ? 'selected' : '' }}>Avgust</option>
                        <option value="9" {{ request('month') == 9 ? 'selected' : '' }}>Sentyabr</option>
                    </optgroup>
                    <optgroup label="Kuzgi-qishki mavsum (Oktyabr - Mart)">
                        <option value="10" {{ request('month') == 10 ? 'selected' : '' }}>Oktyabr</option>
                        <option value="11" {{ request('month') == 11 ? 'selected' : '' }}>Noyabr</option>
                        <option value="12" {{ request('month') == 12 ? 'selected' : '' }}>Dekabr</option>
                        <option value="1" {{ request('month') == 1 ? 'selected' : '' }}>Yanvar</option>
                        <option value="2" {{ request('month') == 2 ? 'selected' : '' }}>Fevral</option>
                        <option value="3" {{ request('month') == 3 ? 'selected' : '' }}>Mart</option>
                    </optgroup>
                </select>
            </div>
        </form>
    </div>

    @if(request('farm_id') && request('month'))
        <!-- Data Entry Form -->
        <form method="POST" action="/admin/water/store" class="space-y-6">
            @csrf
            <input type="hidden" name="farm_id" value="{{ request('farm_id') }}">
            <input type="hidden" name="year" value="{{ request('year', date('Y')) }}">
            <input type="hidden" name="month" value="{{ request('month') }}">

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Suv manbai</th>
                            <th class="px-6 py-4">Dekadalar</th>
                            <th class="px-6 py-4">Suv olish limiti, m³</th>
                            <th class="px-6 py-4">Amalda ishlatilgan suv, m³</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                        @php
                            $sources = [
                                'surface' => 'Er ustidan (daryo, kanal, soy, buloq)',
                                'groundwater' => 'Er ostidan (sug\'orish qudug\'i)',
                                'drainage' => 'Kollektor-drenaj tarmog\'idan'
                            ];
                        @endphp

                        @foreach($sources as $sourceKey => $sourceName)
                            @for($dec = 1; $dec <= 3; $dec++)
                                @php
                                    $existingLimit = 0;
                                    $existingUsed = 0;
                                    
                                    $rec = $existingRecords->where('water_source', $sourceKey)->where('decade', $dec)->first();
                                    if ($rec) {
                                        $existingLimit = $rec->limit_m3;
                                        $existingUsed = $rec->used_m3;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    @if($dec === 1)
                                        <td class="px-6 py-4 font-semibold text-gray-900 border-r border-gray-150" rowspan="3">
                                            {{ $sourceName }}
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 font-medium text-gray-500 border-r border-gray-150">
                                        {{ $dec }}-dekada
                                    </td>
                                    <td class="p-2 border-r border-gray-150">
                                        <input type="number" step="0.01" min="0" 
                                               name="records[{{ $sourceKey }}][{{ $dec }}][limit_m3]" 
                                               value="{{ old('records.'.$sourceKey.'.'.$dec.'.limit_m3', $existingLimit > 0 ? $existingLimit : '') }}" 
                                               placeholder="0.00" 
                                               class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs focus:outline-none focus:ring-1 focus:ring-forest-500 font-semibold text-gray-800">
                                    </td>
                                    <td class="p-2">
                                        <input type="number" step="0.01" min="0" 
                                               name="records[{{ $sourceKey }}][{{ $dec }}][used_m3]" 
                                               value="{{ old('records.'.$sourceKey.'.'.$dec.'.used_m3', $existingUsed > 0 ? $existingUsed : '') }}" 
                                               placeholder="0.00" 
                                               class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs focus:outline-none focus:ring-1 focus:ring-forest-500 font-semibold text-gray-800">
                                    </td>
                                </tr>
                            @endfor
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-forest-700 hover:bg-forest-600 px-6 py-2.5 text-xs font-bold text-white shadow-md transition border border-forest-600">
                    Ma'lumotlarni Saqlash
                </button>
            </div>
        </form>
    @else
        <div class="p-10 text-center border border-dashed border-gray-200 rounded-xl bg-white shadow-sm flex flex-col items-center justify-center">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Xo'jalik va Oyni tanlang</h4>
            <p class="text-[11px] text-gray-400 mt-1 max-w-[280px]">Dala suv limitlarini to'ldirishni boshlash uchun yuqoridagi maydonlardan fermer xo'jaligi va kerakli oyni tanlang.</p>
        </div>
    @endif
</div>
@endsection
