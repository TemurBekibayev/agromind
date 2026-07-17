<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PrivateMessage;
use Illuminate\Http\Request;

class PrivateMessageController extends Controller
{
    /**
     * Get list of users in the same district with their last message and unread counts.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $district = $user->district ?? 'Amudaryo tumani';

        $chatUsers = User::where('district', $district)
            ->where('id', '!=', $user->id)
            ->get();

        // Fallback 1: If no users in district, check same region
        if ($chatUsers->isEmpty() && $user->region_id) {
            $chatUsers = User::where('region_id', $user->region_id)
                ->where('id', '!=', $user->id)
                ->get();
        }

        // Fallback 2: If still empty, get any other farmers in the system
        if ($chatUsers->isEmpty()) {
            $chatUsers = User::where('role', 'farmer')
                ->where('id', '!=', $user->id)
                ->get();
        }

        $chatUsersData = $chatUsers->map(function ($u) use ($user) {
            // Find last message
            $lastMsg = PrivateMessage::where(function ($q) use ($user, $u) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $u->id);
                })
                ->orWhere(function ($q) use ($user, $u) {
                    $q->where('sender_id', $u->id)->where('receiver_id', $user->id);
                })
                ->latest()
                ->first();

            // Unread count from this partner to auth user
            $unread = PrivateMessage::where('sender_id', $u->id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            return [
                'id' => $u->id,
                'name' => $u->name,
                'district' => $u->district,
                'last_message' => $lastMsg ? ($lastMsg->is_voice ? 'Ovozli xabar' : $lastMsg->message) : '',
                'last_message_time' => $lastMsg ? $lastMsg->created_at->toIso8601String() : null,
                'unread_count' => $unread,
            ];
        });

        return response()->json([
            'status' => 'success',
            'users' => $chatUsersData
        ]);
    }

    /**
     * Get private chat messages between auth user and selected user.
     */
    public function getMessages(Request $request, $partnerId)
    {
        $user = $request->user();

        // Mark incoming messages as read
        PrivateMessage::where('sender_id', $partnerId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Get messages
        $messages = PrivateMessage::where(function ($q) use ($user, $partnerId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $partnerId);
            })
            ->orWhere(function ($q) use ($user, $partnerId) {
                $q->where('sender_id', $partnerId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'message' => $msg->message,
                    'is_voice' => (bool)$msg->is_voice,
                    'voice_duration' => $msg->voice_duration,
                    'audio_path' => $msg->audio_path,
                    'is_read' => (bool)$msg->is_read,
                    'is_me' => $msg->sender_id === $user->id,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    /**
     * Send a private message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,aac,m4a,ogg,amr,mp4,3gp|max:10240',
            'is_voice' => 'nullable|boolean',
            'voice_duration' => 'nullable|integer',
        ]);

        $audioPath = null;
        $isVoice = $request->is_voice ?? false;
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('private_audio', 'public');
            $audioPath = asset('storage/' . $path);
            $isVoice = true;
        }

        $message = PrivateMessage::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'audio_path' => $audioPath,
            'is_voice' => $isVoice,
            'voice_duration' => $request->voice_duration,
            'is_read' => false,
        ]);

        // If the receiver is admin, forward the message to Telegram
        $receiver = User::find($request->receiver_id);
        if ($receiver && $receiver->role === 'admin') {
            $this->sendToTelegram($request->user(), $message);
        }

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message' => $message->message,
                'is_voice' => (bool)$message->is_voice,
                'voice_duration' => $message->voice_duration,
                'audio_path' => $message->audio_path,
                'is_read' => (bool)$message->is_read,
                'is_me' => true,
                'created_at' => $message->created_at->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Get the admin user info.
     */
    public function getAdminUser()
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin AgroMind',
                'phone' => '998901234567',
                'role' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
            ]);
        }
        return response()->json([
            'status' => 'success',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'phone' => $admin->phone,
            ]
        ]);
    }

    /**
     * Send message to Telegram bot.
     */
    private function sendToTelegram($sender, $message)
    {
        $token = config('telegram.bot_token');
        $chatId = config('telegram.admin_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        $text = "🌱 *Yangi Murojaat!*\n"
              . "👤 *Fermer:* {$sender->name}\n"
              . "📞 *Telefon:* {$sender->phone}\n"
              . "📝 *Xabar:* " . ($message->message ?? '🎙️ Ovozli xabar') . "\n\n"
              . "[Fermer ID: {$sender->id}]";

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($message->audio_path) {
            $text .= "\n\n🎙️ Ovozli xabar manzili: " . $message->audio_path;
            $data['text'] = $text;
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
