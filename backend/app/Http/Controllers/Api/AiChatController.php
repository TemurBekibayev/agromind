<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GroqService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    protected $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    /**
     * Fermerning savoliga agronom AI orqali javob berish.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant,system',
            'history.*.content' => 'required_with:history|string',
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);

        $reply = $this->groqService->ask($message, $history);

        return response()->json([
            'status' => 'success',
            'message' => 'AI agronom javobi muvaffaqiyatli tayyorlandi.',
            'reply' => $reply,
        ]);
    }
}
