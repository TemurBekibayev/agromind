<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PrivateMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Handle incoming webhook requests from Telegram Bot.
     */
    public function webhook(Request $request)
    {
        $update = $request->all();

        if (!isset($update['message'])) {
            return response()->json(['status' => 'ignored']);
        }

        $message = $update['message'];
        
        // Check if this is a reply to a message sent by our bot
        if (isset($message['reply_to_message'])) {
            $replyTo = $message['reply_to_message'];
            $replyText = $replyTo['text'] ?? '';
            $replyCaption = $replyTo['caption'] ?? '';
            $fullText = $replyText . "\n" . $replyCaption;

            // Extract Farmer ID from the original message text using Regex
            if (preg_match('/\[Fermer ID:\s*(\d+)\]/', $fullText, $matches)) {
                $farmerId = (int)$matches[1];
                $adminReply = $message['text'] ?? '';

                if (empty($adminReply) && isset($message['voice'])) {
                    $adminReply = '🎙️ Ovozli xabar (Telegram)';
                }

                if (empty($adminReply)) {
                    return response()->json(['status' => 'no_text']);
                }

                // Find Admin user
                $admin = User::where('role', 'admin')->first();
                if (!$admin) {
                    return response()->json(['status' => 'admin_not_found'], 404);
                }

                // Create private message from Admin to Farmer
                $privateMessage = PrivateMessage::create([
                    'sender_id' => $admin->id,
                    'receiver_id' => $farmerId,
                    'message' => $adminReply,
                    'is_read' => false,
                ]);

                // Optionally notify admin on Telegram that it was delivered
                $this->sendMessage($message['chat']['id'], "✅ Javobingiz dehqonga yuborildi.", $message['message_id']);

                return response()->json([
                    'status' => 'success',
                    'message_id' => $privateMessage->id
                ]);
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    /**
     * Helper to send message back to Telegram
     */
    private function sendMessage($chatId, $text, $replyToMessageId = null)
    {
        $token = config('telegram.bot_token');
        if (!$token) return;

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
}
