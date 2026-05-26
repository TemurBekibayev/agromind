@extends('layouts.admin')

@section('title', 'Dehqonlar Ro\'yxati')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dehqonlar va Fermerlar</h2>
            <p class="text-sm text-gray-500">Tizimda ro'yxatdan o'tgan barcha fermer xo'jaliklari va dehqonlar ro'yxati.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700 border border-forest-200">
                Jami: {{ $farmers->count() }} ta fermer
            </span>
        </div>
    </div>

    <!-- Table Card -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Fermer F.I.Sh.</th>
                        <th scope="col" class="px-6 py-4">Telefon Raqami</th>
                        <th scope="col" class="px-6 py-4">Hudud</th>
                        <th scope="col" class="px-6 py-4">Fermer Xo'jaliklari (Yer maydoni)</th>
                        <th scope="col" class="px-6 py-4">Ro'yxatdan o'tgan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($farmers as $farmer)
                        <tr class="hover:bg-gray-50/75 transition">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-forest-50 flex items-center justify-center text-forest-700 font-bold text-sm">
                                        {{ mb_substr($farmer->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ $farmer->name }}</p>
                                        <p class="text-xs text-gray-400">ID: #{{ $farmer->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $farmer->phone }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                    {{ $farmer->region ? $farmer->region->name : 'Noma\'lum' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($farmer->farms->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($farmer->farms as $farm)
                                            <div class="flex items-center justify-between text-xs gap-4 bg-forest-50/50 p-1 px-2 rounded border border-forest-100">
                                                <span class="font-medium text-forest-800">{{ $farm->name }}</span>
                                                <span class="text-gray-500 font-semibold">{{ $farm->size }} gektar ({{ $farm->soil_type }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Ferma biriktirilmagan</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                {{ $farmer->created_at ? $farmer->created_at->format('d.m.Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                                Tizimda hozircha ro'yxatdan o'tgan dehqonlar mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
