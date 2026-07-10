@extends('layouts.admin')

@section('title', 'Murojaatlar & Bildirishnomalar')

@section('content')
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Murojaatlar va Bildirishnomalar</h2>
            <p class="text-sm text-gray-500">Dehqonlardan kelgan yordam murojaatlari va ro'yxatdan o'tish to'g'risidagi bildirishnomalar.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700 border border-forest-200 shrink-0">
                Jami: {{ $messages->count() }} ta xabar
            </span>
            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 border border-amber-200 shrink-0">
                O'qilmagan: {{ $messages->where('is_resolved', false)->count() }} ta
            </span>
        </div>
    </div>

    <!-- Success & Error Notifications -->
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm flex items-center justify-between">
            <span class="font-medium">🎉 Muvaffaqiyatli: {{ session('success') }}</span>
        </div>
    @endif

    <!-- Messages List -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Fermer va Kontakt</th>
                        <th scope="col" class="px-6 py-4">Turi</th>
                        <th scope="col" class="px-6 py-4">Xabar mazmuni</th>
                        <th scope="col" class="px-6 py-4">Kelgan vaqti</th>
                        <th scope="col" class="px-6 py-4">Holati</th>
                        <th scope="col" class="px-6 py-4 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($messages as $msg)
                        <tr class="hover:bg-gray-50/75 transition {{ !$msg->is_resolved ? 'bg-amber-50/20 font-medium' : '' }}">
                            <!-- Name & Phone -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full {{ $msg->type === 'registration' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' }} flex items-center justify-center font-bold text-base">
                                        {{ Str::upper(Str::substr($msg->sender_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $msg->sender_name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $msg->sender_phone }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($msg->type === 'registration')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-100">
                                        📝 Ro'yxatdan o'tish
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 border border-blue-100">
                                        💬 Murojaat (Yordam)
                                    </span>
                                @endif
                            </td>

                            <!-- Message Content -->
                            <td class="px-6 py-4 max-w-md">
                                <p class="text-sm text-gray-800 whitespace-normal break-words leading-relaxed">
                                    {{ $msg->message }}
                                </p>
                            </td>

                            <!-- Time -->
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                {{ $msg->created_at ? $msg->created_at->diffForHumans() : '-' }}
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $msg->created_at ? $msg->created_at->format('Y-m-d H:i') : '' }}
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($msg->is_resolved)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 border border-gray-250">
                                        Hal etilgan / O'qilgan
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        Kutilmoqda
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4 text-right text-xs font-medium space-x-2 shrink-0">
                                @if(!$msg->is_resolved)
                                    <form action="/admin/messages/resolve/{{ $msg->id }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-emerald-700 hover:text-emerald-950 bg-emerald-50 hover:bg-emerald-100 p-1 px-2.5 rounded-lg transition inline-block border border-emerald-200">
                                            ✓ Hal etildi
                                        </button>
                                    </form>
                                @endif
                                <form action="/admin/messages/destroy/{{ $msg->id }}" method="POST" class="inline-block" onsubmit="return confirm('Haqiqatan ham ushbu murojaatni o\'chirmoqchimisiz?')">
                                    @csrf
                                    <button type="submit" class="text-red-650 hover:text-red-950 bg-red-50 hover:bg-red-100 p-1 px-2.5 rounded-lg transition inline-block border border-red-200">
                                        O'chirish
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                Kelib tushgan murojaatlar yoki bildirishnomalar mavjud emas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
