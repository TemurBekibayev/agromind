<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\SoilAnalysis;
use App\Models\Recommendation;
use App\Services\GroqService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SoilAnalysisController extends Controller
{
    protected $groqService;

    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }

    /**
     * Fermaning barcha tuproq tahlillarini olish.
     */
    public function index(Request $request, $farmId)
    {
        $farm = $request->user()->farms()->find($farmId);

        if (!$farm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ferma topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $analyses = $farm->soilAnalyses()->latest()->get();

        return response()->json([
            'status' => 'success',
            'analyses' => $analyses
        ]);
    }

    /**
     * Yangi tuproq tahlili qo'shish (status: pending).
     */
    public function store(Request $request, $farmId)
    {
        $farm = $request->user()->farms()->find($farmId);

        if (!$farm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ferma topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $validated = $request->validate([
            'geofence_id' => 'nullable|exists:geofences,id',
            'target_crop' => 'required|string|max:255',
            'ph' => 'required|numeric|between:0,14',
            'fertility' => 'required|numeric|between:0,100',
            'moisture' => 'required|numeric|between:0,100',
            'temperature' => 'required|numeric|between:-20,60',
            'sunlight' => 'required|numeric|min:0',
            'humidity' => 'required|numeric|between:0,100',
            'analysis_date' => 'required|date',
        ]);

        $analysis = $farm->soilAnalyses()->create(array_merge($validated, [
            'status' => 'pending'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Tuproq tahlili muvaffaqiyatli saqlandi. Endi AI maslahatini olishingiz mumkin.',
            'analysis' => $analysis
        ], 201);
    }

    /**
     * Bitta tahlilning to'liq ma'lumotini olish (tavsiyasi bilan).
     */
    public function show(Request $request, $id)
    {
        $analysis = SoilAnalysis::with(['farm', 'recommendation'])->find($id);

        if (!$analysis || $analysis->farm->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tahlil topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'analysis' => $analysis
        ]);
    }

    /**
     * Sun'iy intellekt (Groq) orqali tavsiyani shakllantirish.
     */
    public function recommend(Request $request, $id)
    {
        $analysis = SoilAnalysis::with('farm')->find($id);

        if (!$analysis || $analysis->farm->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tahlil topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        // Agar tavsiya allaqachon mavjud bo'lsa, uni qaytaramiz (API chaqiruvlarni tejash uchun)
        $existingRec = Recommendation::where('soil_analysis_id', $analysis->id)->first();
        if ($existingRec) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tavsiya allaqachon tayyor.',
                'recommendation' => $existingRec
            ]);
        }

        // Groq API yordamida tavsiya olish
        $aiData = $this->groqService->getSoilRecommendation($analysis);

        // Tavsiyani saqlash
        $recommendation = Recommendation::create([
            'soil_analysis_id' => $analysis->id,
            'content' => $aiData['content'],
            'recommended_crops' => $aiData['recommended_crops'],
            'fertilizer_plan' => $aiData['fertilizer_plan'],
            'ai_model' => $aiData['ai_model'],
            'tokens_used' => $aiData['tokens_used'],
        ]);

        // Tahlil statusini 'completed' ga yangilash
        $analysis->update([
            'status' => 'completed'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'AI tavsiyasi muvaffaqiyatli tayyorlandi.',
            'recommendation' => $recommendation
        ]);
    }
}
