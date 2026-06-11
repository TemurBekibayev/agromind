<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    /**
     * Faol e'lonlar ro'yxatini olish.
     */
    public function index()
    {
        $listings = Listing::with(['user.region'])
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'listings' => $listings
        ]);
    }

    /**
     * Yangi e'lon qo'shish.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'equipment_type' => 'required|string',
            'price' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:50',
        ]);

        $listing = Listing::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'equipment_type' => $request->equipment_type,
            'price' => $request->price,
            'contact_phone' => $request->contact_phone,
            'status' => 'active',
        ]);

        $listing->load('user.region');

        return response()->json([
            'status' => 'success',
            'listing' => $listing
        ], 201);
    }

    /**
     * E'lonni o'chirish.
     */
    public function destroy(Request $request, $id)
    {
        $listing = Listing::find($id);

        if (!$listing) {
            return response()->json([
                'status' => 'error',
                'message' => 'E\'lon topilmadi.'
            ], 404);
        }

        // Faqat e'lon egasi yoki admin o'chira oladi
        if ($listing->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sizda ushbu e\'lonni o\'chirish huquqi yo\'q.'
            ], 403);
        }

        $listing->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'E\'lon muvaffaqiyatli o\'chirildi.'
        ]);
    }
}
