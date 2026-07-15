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

        return response()->json([
            'status' => 'success',
            'message' => 'Ariza muvaffaqiyatli qabul qilindi.',
            'appeal' => $appeal
        ], 201);
    }
}
