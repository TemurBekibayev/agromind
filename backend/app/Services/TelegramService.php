<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send formatted message to the Telegram Admin Bot.
     */
    public static function sendMessage(string $text)
    {
        $token = config('telegram.bot_token');
        $chatId = config('telegram.admin_chat_id');

        if (!$token || !$chatId) {
            Log::warning('Telegram bot credentials not configured in config/telegram.php');
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::error("Telegram API Curl Error: " . $err);
            return false;
        }

        return $result;
    }
}
