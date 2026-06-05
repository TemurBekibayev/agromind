<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Tizimga kirgan fermerning barcha xo'jaliklarini olish.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            $farms = Farm::with(['region', 'geofences'])->get();
        } else {
            $farms = $user->farms()->with(['region', 'geofences'])->get();
        }

        return response()->json([
            'status' => 'success',
            'farms' => $farms
        ]);
    }

    /**
     * Yangi ferma xo'jaligini qo'shish.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'size' => 'required|numeric|min:0.1',
            'soil_type' => 'required|string|max:255',
            'region_id' => 'required|exists:regions,id',
        ]);

        $farm = $request->user()->farms()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Ferma muvaffaqiyatli yaratildi.',
            'farm' => $farm->load('region')
        ], 201);
    }

    /**
     * Fermaning batafsil ma'lumotlarini olish.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            $farm = Farm::with(['region', 'geofences'])->find($id);
        } else {
            $farm = $user->farms()->with(['region', 'geofences'])->find($id);
        }

        if (!$farm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ferma topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'farm' => $farm
        ]);
    }

    /**
     * Ferma ma'lumotlarini tahrirlash.
     */
    public function update(Request $request, $id)
    {
        $farm = $request->user()->farms()->find($id);

        if (!$farm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ferma topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'size' => 'sometimes|required|numeric|min:0.1',
            'soil_type' => 'sometimes|required|string|max:255',
            'region_id' => 'sometimes|required|exists:regions,id',
        ]);

        $farm->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Ferma ma\'lumotlari yangilandi.',
            'farm' => $farm->load('region')
        ]);
    }

    /**
     * Fermani o'chirish.
     */
    public function destroy(Request $request, $id)
    {
        $farm = $request->user()->farms()->find($id);

        if (!$farm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ferma topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $farm->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ferma muvaffaqiyatli o\'chirildi.'
        ]);
    }
}
