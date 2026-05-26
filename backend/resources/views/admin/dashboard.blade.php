@extends('layouts.admin')

@section('title', 'Tizim Boshqaruv Paneli')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Boshqaruv Paneli</h2>
            <p class="text-sm text-gray-500">Respublika bo'yicha dehqon xo'jaliklari va texnikalar monitoringi.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.location.reload()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Yangilash
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1 -->
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Jami Fermerlar</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($farmersCount) }} ta</h3>
                <span class="inline-flex items-center text-xs font-medium text-green-700 mt-2">
                    +12% o'sish (shu oyda)
                </span>
            </div>
            <div class="h-12 w-12 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Faol Texnikalar</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($vehiclesCount) }} ta</h3>
                <span class="inline-flex items-center text-xs font-medium text-emerald-700 mt-2">
                    <span class="h-2 w-2 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                    Barchasi online monitoringda
                </span>
            </div>
            <div class="h-12 w-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V9.75M3.75 14.25h16.5M3.75 14.25V7.5a1.5 1.5 0 011.5-1.5h13.5a1.5 1.5 0 011.5 1.5v6.75m-16.5 0H18M13.5 6V4.5a1.5 1.5 0 00-3 0V6" />
                </svg>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tuproq Tahlillari</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($soilCount) }} ta</h3>
                <span class="inline-flex items-center text-xs font-medium text-orange-700 mt-2">
                    100% AI tavsiyalari tayyor
                </span>
            </div>
            <div class="h-12 w-12 rounded-lg bg-orange-50 flex items-center justify-center text-orange-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v17.792m0-17.792L5.47 7.378M9.75 3.104l4.28 4.274M18.75 6.002v12.004M18.75 6.002l-4.25 4.25m4.25-4.25l4.25 4.25" />
                </svg>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Faol Ogohlantirishlar</p>
                <h3 class="text-2xl font-bold {{ $alertsCount > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ number_format($alertsCount) }} ta faol</h3>
                <span class="inline-flex items-center text-xs font-medium text-red-700 mt-2">
                    Tezkor e'tibor talab etiladi
                </span>
            </div>
            <div class="h-12 w-12 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Charts and Table Section -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Monthly Analysis Chart -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Tuproq tahlillari va ekin turlari dinamikasi</h3>
            <div class="h-72 w-full">
                <canvas id="analysesChart"></canvas>
            </div>
        </div>

        <!-- Active Alerts Panel -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Oxirgi ogohlantirishlar</h3>
                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                    Live
                </span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3.5 max-h-72">
                @forelse($alerts as $alert)
                    <div class="flex gap-3 border-b border-gray-100 pb-3">
                        <span class="h-2 w-2 mt-1.5 {{ $alert->status === 'active' ? 'bg-red-600 animate-pulse' : 'bg-amber-500' }} rounded-full shrink-0"></span>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-900">{{ $alert->type }} @if($alert->vehicle) - {{ $alert->vehicle->name }}@endif</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $alert->message }}</p>
                            <span class="text-[10px] font-medium text-gray-400">
                                {{ $alert->created_at->diffForHumans() }} 
                                @if($alert->farm) | {{ $alert->farm->name }}@endif
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500 text-sm">
                        Faol xavflar va ogohlantirishlar mavjud emas.
                    </div>
                @endforelse
            </div>
            <a href="/admin/alerts" class="mt-4 block text-center text-xs font-semibold text-forest-700 hover:text-forest-800 transition">
                Barcha ogohlantirishlarni ko'rish &rarr;
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('analysesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun'],
                datasets: [
                    {
                        label: 'G\'o\'za (Paxta)',
                        data: [120, 150, 320, 480, 520, 610],
                        borderColor: '#2A5C43',
                        backgroundColor: 'rgba(42, 92, 67, 0.05)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Bug\'doy',
                        data: [210, 240, 290, 310, 180, 90],
                        borderColor: '#E65C00',
                        backgroundColor: 'rgba(230, 92, 0, 0.05)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter', size: 12 }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#E5E7EB' },
                        ticks: { font: { family: 'Inter' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter' } }
                    }
                }
            }
        });
    });
</script>
@endsection
