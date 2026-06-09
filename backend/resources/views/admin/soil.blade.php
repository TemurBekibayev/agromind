@extends('layouts.admin')

@section('title', 'Tuproq Tahlillari va AI Tavsiyalari')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Tuproq Tahlili va AI Ekin Rejalari</h2>
            <p class="text-sm text-gray-500">Dala tuproq tahlili natijalari va Sun'iy Intellekt (Llama-3) tomonidan tayyorlangan o'g'itlash va ekin ekish bo'yicha tavsiyalar.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 border border-orange-200 shadow-sm">
                <svg class="h-3 w-3 mr-1.5 animate-pulse text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
                AI Llama-3 Monitoring faol
            </span>
        </div>
    </div>

    <!-- Soil Analysis Grid Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Ferma Xo'jaligi</th>
                        <th scope="col" class="px-6 py-4">Rejadagi Ekin</th>
                        <th scope="col" class="px-6 py-4 text-center">pH Ko'rsatkichi</th>
                        <th scope="col" class="px-6 py-4 text-center">Namlik</th>
                        <th scope="col" class="px-6 py-4 text-center">Unumdorlik (NPK)</th>
                        <th scope="col" class="px-6 py-4 text-center">Harorat / Namlik</th>
                        <th scope="col" class="px-6 py-4">Tahlil Sanasi</th>
                        <th scope="col" class="px-6 py-4 text-right">AI Tavsiyalari</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($soilAnalyses as $analysis)
                        @php
                            $ph = floatval($analysis->ph);
                            $phBg = $ph >= 6.0 && $ph <= 7.5 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200';
                            
                            $moisture = floatval($analysis->moisture);
                            $moistureBg = $moisture >= 40 ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-red-50 text-red-800 border border-red-200';
                            
                            $fertility = floatval($analysis->fertility);
                            $fertilityBg = $fertility >= 65 ? 'bg-forest-50 text-forest-700 border border-forest-200' : 'bg-rose-50 text-rose-800 border border-rose-200';
                        @endphp
                        <tr class="hover:bg-gray-50/75 transition">
                            <!-- Farm & Region -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-orange-50 flex items-center justify-center text-orange-600 shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $analysis->farm ? $analysis->farm->name : 'Noma\'lum' }}</p>
                                        <p class="text-xs text-gray-500">{{ $analysis->farm && $analysis->farm->region ? $analysis->farm->region->name : '' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Target Crop -->
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-800">
                                {{ $analysis->target_crop }}
                            </td>

                            <!-- pH Indicator -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $phBg }}">
                                    pH: {{ $ph }}
                                </span>
                            </td>

                            <!-- Moisture -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $moistureBg }}">
                                    {{ $moisture }}%
                                </span>
                            </td>

                            <!-- Fertility -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $fertilityBg }}">
                                    {{ $fertility }}%
                                </span>
                            </td>

                            <!-- Temp & Humidity -->
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <div class="text-xs font-medium">
                                    <p class="text-gray-900">{{ $analysis->temperature }}°C</p>
                                    <p class="text-gray-400 mt-0.5">{{ $analysis->humidity }}% H</p>
                                </div>
                            </td>

                            <!-- Analysis Date -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-medium text-gray-500">
                                {{ $analysis->analysis_date ? $analysis->analysis_date->format('d.m.Y') : $analysis->created_at->format('d.m.Y') }}
                            </td>

                            <!-- AI Recommendation Button -->
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if($analysis->recommendation)
                                    <button 
                                        onclick="showRecommendation('{{ addslashes($analysis->farm ? $analysis->farm->name : 'Ferma') }}', '{{ addslashes($analysis->target_crop) }}', {{ json_encode($analysis->recommendation->content) }}, {{ json_encode($analysis->recommendation->recommended_crops) }}, {{ json_encode($analysis->recommendation->fertilizer_plan) }}, '{{ $analysis->recommendation->ai_model }}')"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-orange-500 transition"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.982-11.861H13.62l.812-5.043L5.458 15.904h4.355z" />
                                        </svg>
                                        AI Tavsiyasi
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tavsiya yo'q</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda hozircha tuproq tahlillari hisobotlari mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dynamic Polished AI Recommendation Modal -->
<div id="ai-modal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all border border-gray-100 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-start justify-between border-b border-gray-150 pb-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.982-11.861H13.62l.812-5.043L5.458 15.904h4.355z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900" id="modal-farm-name">Ferma Xo'jaligi</h3>
                    <p class="text-xs text-gray-500">Tahlil asosida sun'iy intellekt ekin va o'g'it tavsiyasi</p>
                </div>
            </div>
            <button onclick="closeModal()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Content (Scrollable) -->
        <div class="flex-1 overflow-y-auto py-5 space-y-6">
            <!-- Summary Recommendation Text -->
            <div class="bg-forest-50/50 rounded-xl p-4 border border-forest-100">
                <h4 class="text-xs font-bold uppercase tracking-wider text-forest-800 mb-1">Umumiy tavsiya</h4>
                <p class="text-sm text-gray-700 leading-relaxed font-sans" id="modal-recommendation-content">Tavsiya matni yuklanmoqda...</p>
            </div>

            <!-- Grid: Recommended Crops & Fertilizer Plan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Recommended Crops Card -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Tavsiya qilingan ekin turlari
                    </h4>
                    <ul class="space-y-2" id="modal-recommended-crops">
                        <!-- Filled dynamically -->
                    </ul>
                </div>

                <!-- Fertilizer Plan Card -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                        O'g'itlash va parvarishlash rejasi
                    </h4>
                    <ul class="space-y-2" id="modal-fertilizer-plan">
                        <!-- Filled dynamically -->
                    </ul>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="border-t border-gray-150 pt-4 flex items-center justify-between shrink-0 text-xs text-gray-400">
            <p>Sun'iy intellekt modeli: <span class="font-semibold text-gray-600" id="modal-ai-model">Llama-3-Agri</span></p>
            <button onclick="closeModal()" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                Yopish
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function showRecommendation(farmName, targetCrop, content, recommendedCrops, fertilizerPlan, aiModel) {
        document.getElementById('modal-farm-name').innerText = farmName + ' (Reja: ' + targetCrop + ')';
        document.getElementById('modal-recommendation-content').innerText = content;
        document.getElementById('modal-ai-model').innerText = aiModel || 'Llama-3-Agri';

        // Crops List
        const cropsList = document.getElementById('modal-recommended-crops');
        cropsList.innerHTML = '';
        if (Array.isArray(recommendedCrops)) {
            recommendedCrops.forEach(crop => {
                const li = document.createElement('li');
                li.className = 'flex items-center gap-2 text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100 font-medium';
                li.innerHTML = `<svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> ${crop}`;
                cropsList.appendChild(li);
            });
        } else {
            cropsList.innerHTML = '<li class="text-xs text-gray-400 italic">Tavsiya etilgan ekinlar yo\'q</li>';
        }

        // Fertilizer Plan List
        const fertilizerList = document.getElementById('modal-fertilizer-plan');
        fertilizerList.innerHTML = '';
        if (Array.isArray(fertilizerPlan) && fertilizerPlan.length > 0) {
            fertilizerPlan.forEach(step => {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2 text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100';
                li.innerHTML = `<svg class="h-4 w-4 text-orange-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.008v.008H12V18zm0-3h.008v.008H12V15zm0-3h.008v.008H12V12zm0-3h.008v.008H12V9zm0-3h.008v.008H12V6z" /></svg> ${step}`;
                fertilizerList.appendChild(li);
            });
        } else if (fertilizerPlan && typeof fertilizerPlan === 'object' && Object.keys(fertilizerPlan).length > 0) {
            Object.entries(fertilizerPlan).forEach(([key, value]) => {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2 text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100';
                // Capitalize first letter of season/key
                const label = key.charAt(0).toUpperCase() + key.slice(1);
                li.innerHTML = `<svg class="h-4 w-4 text-orange-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.008v.008H12V18zm0-3h.008v.008H12V15zm0-3h.008v.008H12V12zm0-3h.008v.008H12V9zm0-3h.008v.008H12V6z" /></svg> <div><strong class="text-orange-700">${label}:</strong> ${value}</div>`;
                fertilizerList.appendChild(li);
            });
        } else {
            fertilizerList.innerHTML = '<li class="text-xs text-gray-400 italic">O\'g\'itlash rejasi belgilanmagan</li>';
        }

        // Open modal
        const modal = document.getElementById('ai-modal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('ai-modal');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>
@endsection
