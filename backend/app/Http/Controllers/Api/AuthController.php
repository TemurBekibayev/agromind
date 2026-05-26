<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mobil ilovaga telefon raqam va parol orqali login qilish.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telefon raqam yoki parol xato.',
            ], 401);
        }

        // Faqat farmer (dehqon) roliga ruxsat beramiz mobil ilova orqali kirishga
        if ($user->role !== 'farmer') {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu tizimga faqat fermerlar kirishi mumkin.',
            ], 403);
        }

        $deviceName = $request->device_name ?? 'mobile_app';
        // Eski tokenlarni o'chirib yuboramiz (bitta qurilmada faqat bitta faol token bo'lishi uchun)
        $user->tokens()->where('name', $deviceName)->delete();
        
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Muvaffaqiyatli tizimga kirildi.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'region_id' => $user->region_id,
            ]
        ]);
    }

    /**
     * Tizimdan chiqish (Tokenni o'chirish).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Muvaffaqiyatli tizimdan chiqildi.'
        ]);
    }

    /**
     * Joriy foydalanuvchi ma'lumotlarini qaytarish.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        // Region ma'lumotini yuklaymiz
        $user->load('region');

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }
}
