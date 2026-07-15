<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PrivateMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PrivateChatController extends Controller
{
    /**
     * Get list of chat users in the current user's district with last message and unread count.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $district = $user->district;

        if (!$district) {
            return response()->json([
                'status' => 'success',
                'chats' => []
            ]);
        }

        $partners = User::with('region')
            ->where('district', $district)
            ->where('id', '!=', $user->id)
            ->get();

        // Fallback 1: If no users in district, check same region
        if ($partners->isEmpty() && $user->region_id) {
            $partners = User::with('region')
                ->where('region_id', $user->region_id)
                ->where('id', '!=', $user->id)
                ->get();
        }

        // Fallback 2: If still empty, get any other farmers in the system
        if ($partners->isEmpty()) {
            $partners = User::with('region')
                ->where('role', 'farmer')
                ->where('id', '!=', $user->id)
                ->get();
        }

        $chats = $partners->map(function ($partner) use ($user) {
            $lastMessage = PrivateMessage::where(function ($q) use ($user, $partner) {
                $q->where('sender_id', $user->id)->where('receiver_id', $partner->id);
            })->orWhere(function ($q) use ($user, $partner) {
                $q->where('sender_id', $partner->id)->where('receiver_id', $user->id);
            })->orderBy('created_at', 'desc')->first();

            $unreadCount = PrivateMessage::where('sender_id', $partner->id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            return [
                'partner' => [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'phone' => $partner->phone,
                    'role' => $partner->role,
                    'district' => $partner->district,
                    'region' => $partner->region ? $partner->region->name : null,
                ],
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'sender_id' => $lastMessage->sender_id,
                    'receiver_id' => $lastMessage->receiver_id,
                    'message' => $lastMessage->message,
                    'audio_path' => $lastMessage->audio_path,
                    'is_read' => $lastMessage->is_read,
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                ] : null,
                'unread_count' => $unreadCount,
                'sort_time' => $lastMessage ? $lastMessage->created_at->timestamp : 0,
            ];
        })
        ->sortByDesc('sort_time')
        ->values();

        return response()->json([
            'status' => 'success',
            'chats' => $chats
        ]);
    }

    /**
     * Get chat messages history with a partner and mark incoming messages as read.
     */
    public function show(Request $request, $partnerId)
    {
        $user = $request->user();

        // Mark incoming messages as read
        PrivateMessage::where('sender_id', $partnerId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Get all messages between the two users
        $messages = PrivateMessage::where(function ($q) use ($user, $partnerId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $partnerId);
            })
            ->orWhere(function ($q) use ($user, $partnerId) {
                $q->where('sender_id', $partnerId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'message' => $msg->message,
                    'audio_path' => $msg->audio_path,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    /**
     * Send a private text or audio message.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,aac,m4a,ogg,amr,mp4,3gp|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$request->filled('message') && !$request->hasFile('audio')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Matnli yoki ovozli xabar kiritilishi shart.'
            ], 420);
        }

        $audioPath = null;
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('private_audio', 'public');
            $audioPath = asset('storage/' . $path);
        }

        $message = PrivateMessage::create([
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'audio_path' => $audioPath,
            'is_read' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message' => $message->message,
                'audio_path' => $message->audio_path,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
