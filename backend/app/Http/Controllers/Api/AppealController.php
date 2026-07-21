<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appeal;
use Illuminate\Http\Request;

class AppealController extends Controller
{
    /**
     * Store a newly created appeal (registration request).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'farm_name' => 'required|string|max:255',
            'inn' => 'nullable|string|max:20',
            'message' => 'nullable|string',
        ]);

        $appeal = Appeal::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'farm_name' => $request->farm_name,
            'inn' => $request->inn,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        // Admin panelda ko'rinishi uchun SupportMessage yaratamiz
        \App\Models\SupportMessage::create([
            'type' => 'registration',
            'sender_name' => $appeal->name,
            'sender_phone' => $appeal->phone,
            'message' => "Yangi ro'yxatdan o'tish arizasi. Ferma: {$appeal->farm_name}, INN: " . ($appeal->inn ?? '-') . ". Xabar: " . ($appeal->message ?? '-'),
        ]);

        // Telegram orqali adminga xabar yuborish
        $telegramText = "<b>📩 Yangi Murojaat (Ariza)</b>\n\n"
            . "👤 <b>Fermer:</b> {$appeal->name}\n"
            . "📞 <b>Telefon:</b> {$appeal->phone}\n"
            . "🚜 <b>Ferma nomi:</b> {$appeal->farm_name}\n"
            . "📝 <b>INN:</b> " . ($appeal->inn ?? '-') . "\n"
            . "💬 <b>Xabar:</b> " . ($appeal->message ?? '-') . "\n"
            . "⚙️ <b>Tizim ID:</b> [Ariza ID: {$appeal->id}]";
        \App\Services\TelegramService::sendMessage($telegramText);

        return response()->json([
            'status' => 'success',
            'message' => 'Ariza muvaffaqiyatli qabul qilindi.',
            'appeal' => $appeal
        ], 201);
    }
}
