<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    /**
     * Oxirgi 100 ta xabarni olish.
     */
    public function index()
    {
        $messages = ChatMessage::with(['user.region'])
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values(); // UI da xronologik tartibda ko'rsatish uchun

        return response()->json([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    /**
     * Yangi xabar yuborish.
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        // Aloqador modellarni yuklab olamiz
        $message->load('user.region');

        return response()->json([
            'status' => 'success',
            'message' => $message
        ], 201);
    }
}
