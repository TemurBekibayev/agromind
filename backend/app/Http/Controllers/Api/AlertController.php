<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AlertController extends Controller
{
    /**
     * Fermerning faol ogohlantirishlarini olish.
     */
    public function index(Request $request)
    {
        $farmIds = $request->user()->farms()->pluck('id');

        $alerts = Alert::whereIn('farm_id', $farmIds)
            ->with(['vehicle', 'farm'])
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'alerts' => $alerts
        ]);
    }

    /**
     * Ogohlantirishni hal qilingan (resolved) deb belgilash.
     */
    public function resolve(Request $request, $id)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        
        $alert = Alert::whereIn('farm_id', $farmIds)->find($id);

        if (!$alert) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ogohlantirish topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        if ($alert->status === 'resolved') {
            return response()->json([
                'status' => 'success',
                'message' => 'Ogohlantirish allaqachon yopilgan.',
                'alert' => $alert
            ]);
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ogohlantirish muvaffaqiyatli hal qilindi.',
            'alert' => $alert
        ]);
    }
}
