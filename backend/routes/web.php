<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\SoilAnalysis;
use App\Models\Alert;
use App\Models\Region;

Route::get('/', function () {
    return redirect('/login');
});

// Web Authentication Routes
Route::get('/login', [\App\Http\Controllers\WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\WebAuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\WebAuthController::class, 'logout'])->name('logout');

// Admin panel routes guarded by admin.auth
Route::middleware('admin.auth')->group(function () {
    // Admin Dashboard marshruti
    Route::get('/admin/dashboard', function () {
    // Haqiqiy ma'lumotlarni bazadan o'qiymiz
    $farmersCount = User::where('role', 'farmer')->count();
    $vehiclesCount = Vehicle::count();
    $soilCount = SoilAnalysis::count();
    $alertsCount = Alert::where('status', 'active')->count();
    $alerts = Alert::with(['vehicle', 'farm'])->latest()->take(5)->get();
    
    return view('admin.dashboard', compact('farmersCount', 'vehiclesCount', 'soilCount', 'alertsCount', 'alerts'));
});

// Dehqonlar ro'yxati (Xo'jalik chizish uchun viloyatlar bilan birga)
Route::get('/admin/farmers', function () {
    $farmers = User::where('role', 'farmer')->with(['region', 'farms.geofences'])->get();
    $monitors = User::where('role', 'monitor')->with('region')->get();
    $regions = Region::all();
    $predefinedFarms = \App\Models\PredefinedFarm::orderBy('name')->get();
    return view('admin.farmers', compact('farmers', 'monitors', 'regions', 'predefinedFarms'));
});

// Yangi Dehqon (Fermer) yoki Monitor saqlash
Route::post('/admin/farmers/store', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:50|unique:users,phone',
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'role' => 'required|string|in:farmer,monitor',
        'password' => 'nullable|string|max:255',
    ]);

    $defaultPassword = $request->role === 'monitor' ? 'secretpassword' : 'secret123';
    $password = $request->password ?: $defaultPassword;

    User::create([
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'role' => $request->role,
        'password' => Hash::make($password),
        'plain_password' => $password,
    ]);

    $roleName = $request->role === 'monitor' ? 'Tuman nazoratchisi' : 'Dehqon (fermer)';
    return back()->with('success', "Yangi {$roleName} muvaffaqiyatli ro'yxatga olindi!");
});

// Dehqon (Fermer) yoki Monitor tahrirlash (update)
Route::post('/admin/farmers/update/{id}', function (Request $request, $id) {
    $user = User::whereIn('role', ['farmer', 'monitor'])->findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:50|unique:users,phone,' . $id,
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'role' => 'required|string|in:farmer,monitor',
        'password' => 'nullable|string|max:255',
    ]);

    $updateData = [
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'role' => $request->role,
    ];

    if ($request->filled('password')) {
        $updateData['password'] = Hash::make($request->password);
        $updateData['plain_password'] = $request->password;
    }

    $user->update($updateData);

    $roleName = $request->role === 'monitor' ? 'Tuman nazoratchisi' : 'Dehqon (fermer)';
    return back()->with('success', "{$roleName} ma'lumotlari muvaffaqiyatli yangilandi!");
});

// Dehqon (Fermer) yoki Monitor o'chirish (delete)
Route::post('/admin/farmers/destroy/{id}', function ($id) {
    $user = User::whereIn('role', ['farmer', 'monitor'])->with('farms.vehicles', 'farms.soilAnalyses', 'farms.geofences', 'farms.alerts')->findOrFail($id);

    // Cascade delete related farms and their data
    foreach ($user->farms as $farm) {
        // Delete vehicles & their data
        foreach ($farm->vehicles as $vehicle) {
            $vehicle->gpsTracks()->delete();
            $vehicle->alerts()->delete();
            $vehicle->delete();
        }
        
        // Delete farm alerts
        $farm->alerts()->delete();

        // Delete soil analyses & recommendations
        foreach ($farm->soilAnalyses as $analysis) {
            $analysis->recommendation()->delete();
            $analysis->delete();
        }

        // Delete geofences
        $farm->geofences()->delete();

        // Delete farm
        $farm->delete();
    }

    $user->delete();

    return back()->with('success', 'Foydalanuvchi va uning barcha tegishli ma\'lumotlari muvaffaqiyatli o\'chirildi!');
});

// Yangi Farm va uning xarita geofence chegarasini saqlash
Route::post('/admin/farms/store', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'user_id' => 'required|exists:users,id',
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'size' => 'required|numeric|min:0.1',
        'soil_type' => 'required|string',
        'coordinates' => 'required|string', // JSON string array of coords
    ]);

    $coordinates = json_decode($request->coordinates, true);
    if (!is_array($coordinates) || count($coordinates) === 0) {
        return back()->withErrors(['coordinates' => 'Kamida 3 ta nuqta tanlab, yopiq ko\'rinishda chegara chizishingiz kerak.']);
    }

    // Check if multi-polygon or single polygon
    $isMultiPolygon = is_array($coordinates[0]) && is_array($coordinates[0][0]);

    if ($isMultiPolygon) {
        foreach ($coordinates as $poly) {
            if (!is_array($poly) || count($poly) < 3) {
                return back()->withErrors(['coordinates' => 'Har bir alohida yer maydoni kamida 3 ta nuqtadan iborat bo\'lishi kerak.']);
            }
        }
        $firstVertex = $coordinates[0][0];
    } else {
        if (count($coordinates) < 3) {
            return back()->withErrors(['coordinates' => 'Kamida 3 ta nuqta tanlab, yopiq ko\'rinishda chegara chizishingiz kerak.']);
        }
        $firstVertex = $coordinates[0];
    }

    $farm = \App\Models\Farm::create([
        'name' => $request->name,
        'user_id' => $request->user_id,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'size' => $request->size,
        'soil_type' => $request->soil_type,
        'latitude' => $firstVertex[0],
        'longitude' => $firstVertex[1],
        'location' => 'GIS Chegara maydoni',
    ]);

    if ($isMultiPolygon) {
        foreach ($coordinates as $index => $poly) {
            \App\Models\Geofence::create([
                'farm_id' => $farm->id,
                'name' => 'Yer maydoni #' . ($index + 1),
                'coordinates' => $poly,
            ]);
        }
    } else {
        \App\Models\Geofence::create([
            'farm_id' => $farm->id,
            'name' => 'Asosiy yer chegarasi',
            'coordinates' => $coordinates,
        ]);
    }

    return back()->with('success', 'Yangi fermer xo\'jaligi va uning yer maydonlari muvaffaqiyatli saqlandi!');
});

// Fermer xo'jaligi (Farm) tahrirlash (update)
Route::post('/admin/farms/update/{id}', function (Request $request, $id) {
    $farm = \App\Models\Farm::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'user_id' => 'required|exists:users,id',
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'size' => 'required|numeric|min:0.1',
        'soil_type' => 'required|string',
        'coordinates' => 'nullable|string',
    ]);

    $farm->update([
        'name' => $request->name,
        'user_id' => $request->user_id,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'size' => $request->size,
        'soil_type' => $request->soil_type,
    ]);

    if ($request->filled('coordinates')) {
        $coordinates = json_decode($request->coordinates, true);
        if ($coordinates !== null) {
            if (!is_array($coordinates) || count($coordinates) === 0) {
                return back()->withErrors(['coordinates' => 'Kamida 3 ta nuqta tanlab, yopiq ko\'rinishda chegara chizishingiz kerak.']);
            }

            // Check if multi-polygon or single polygon
            $isMultiPolygon = is_array($coordinates[0]) && is_array($coordinates[0][0]);

            if ($isMultiPolygon) {
                foreach ($coordinates as $poly) {
                    if (!is_array($poly) || count($poly) < 3) {
                        return back()->withErrors(['coordinates' => 'Har bir alohida yer maydoni kamida 3 ta nuqtadan iborat bo\'lishi kerak.']);
                    }
                }
                $firstVertex = $coordinates[0][0];
            } else {
                if (count($coordinates) < 3) {
                    return back()->withErrors(['coordinates' => 'Kamida 3 ta nuqta tanlab, yopiq ko\'rinishda chegara chizishingiz kerak.']);
                }
                $firstVertex = $coordinates[0];
            }

            // Update latitude/longitude
            $farm->update([
                'latitude' => $firstVertex[0],
                'longitude' => $firstVertex[1],
            ]);

            // Recreate geofences
            $farm->geofences()->delete();

            if ($isMultiPolygon) {
                foreach ($coordinates as $index => $poly) {
                    \App\Models\Geofence::create([
                        'farm_id' => $farm->id,
                        'name' => 'Yer maydoni #' . ($index + 1),
                        'coordinates' => $poly,
                    ]);
                }
            } else {
                \App\Models\Geofence::create([
                    'farm_id' => $farm->id,
                    'name' => 'Asosiy yer chegarasi',
                    'coordinates' => $coordinates,
                ]);
            }
        }
    }

    return back()->with('success', 'Fermer xo\'jaligi (yer) ma\'lumotlari muvaffaqiyatli yangilandi!');
});

// Fermer xo'jaligi (Farm) o'chirish (delete)
Route::post('/admin/farms/destroy/{id}', function ($id) {
    $farm = \App\Models\Farm::with('vehicles', 'soilAnalyses', 'geofences', 'alerts')->findOrFail($id);

    // Delete vehicles & their data
    foreach ($farm->vehicles as $vehicle) {
        $vehicle->gpsTracks()->delete();
        $vehicle->alerts()->delete();
        $vehicle->delete();
    }

    // Delete farm alerts
    $farm->alerts()->delete();

    // Delete soil analyses & recommendations
    foreach ($farm->soilAnalyses as $analysis) {
        $analysis->recommendation()->delete();
        $analysis->delete();
    }

    // Delete geofences
    $farm->geofences()->delete();

    // Delete farm
    $farm->delete();

    return back()->with('success', 'Fermer xo\'jaligi (yer maydoni) muvaffaqiyatli o\'chirildi!');
});

// Texnikalar ro'yxati (Kombaynlar/Traktorlar modal uchun fermalar bilan birga)
Route::get('/admin/vehicles', function () {
    $vehicles = Vehicle::with(['farm', 'latestGpsTrack'])->get();
    $farms = \App\Models\Farm::all();
    return view('admin.vehicles', compact('vehicles', 'farms'));
});

// Yangi Texnika va uning GPS IMEI raqamini ro'yxatga olish
Route::post('/admin/vehicles/store', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'plate_number' => 'required|string|max:20',
        'farm_id' => 'required|exists:farms,id',
        'gps_device_id' => 'required|string|max:50|unique:vehicles,gps_device_id',
        'sim_number' => 'nullable|string|max:30',
        'fuel_capacity' => 'required|numeric|min:10',
    ]);

    $vehicle = Vehicle::create([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
        'sim_number' => $request->sim_number,
        'fuel_capacity' => $request->fuel_capacity,
    ]);

    // Boshlang'ich GPS koordinatasini ferma joylashgan joyda yaratish (xaritada darhol ko'rinishi uchun)
    $farm = $vehicle->farm;
    if ($farm && $farm->latitude && $farm->longitude) {
        \App\Models\GpsTrack::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $farm->latitude,
            'longitude' => $farm->longitude,
            'speed' => 0.0,
            'fuel_level' => 85.0,
            'signal_strength' => 95,
            'recorded_at' => now(),
        ]);
    }

    return back()->with('success', 'Yangi texnika va uning GPS IMEI raqami muvaffaqiyatli ro\'yxatga olindi!');
});

// Texnikani tahrirlash (update)
Route::post('/admin/vehicles/update/{id}', function (Request $request, $id) {
    $vehicle = Vehicle::findOrFail($id);
    
    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'plate_number' => 'required|string|max:20',
        'farm_id' => 'required|exists:farms,id',
        'gps_device_id' => 'required|string|max:50|unique:vehicles,gps_device_id,' . $id,
        'sim_number' => 'nullable|string|max:30',
        'fuel_capacity' => 'required|numeric|min:10',
    ]);

    $vehicle->update([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
        'sim_number' => $request->sim_number,
        'fuel_capacity' => $request->fuel_capacity,
    ]);

    return back()->with('success', 'Texnika ma\'lumotlari muvaffaqiyatli yangilandi!');
});

// Texnikani o'chirish (delete)
Route::post('/admin/vehicles/destroy/{id}', function ($id) {
    $vehicle = Vehicle::findOrFail($id);
    
    // Bog'liq ma'lumotlarni o'chiramiz
    $vehicle->gpsTracks()->delete();
    $vehicle->alerts()->delete();
    $vehicle->delete();

    return back()->with('success', 'Texnika muvaffaqiyatli o\'chirildi!');
});

// Tuproq Tahlillari ro'yxati
Route::get('/admin/soil', function () {
    $soilAnalyses = SoilAnalysis::with(['farm', 'recommendation'])->get();
    return view('admin.soil', compact('soilAnalyses'));
});

// Tizim Ogohlantirishlari ro'yxati
Route::get('/admin/alerts', function () {
    $alerts = Alert::with(['vehicle', 'farm'])->latest()->get();
    return view('admin.alerts', compact('alerts'));
});

// Hududlar ro'yxati
Route::get('/admin/regions', function () {
    $regions = Region::withCount(['users', 'farms'])->get();
    return view('admin.regions', compact('regions'));
});

// Murojaatlar bo'limi (Ro'yxatdan o'tish arizalari va adminga shaxsiy xabarlar)
Route::get('/admin/messages', function () {
    $admin = User::where('role', 'admin')->first();
    $adminId = $admin ? $admin->id : null;

    // Load SupportMessage (registration and public appeals)
    $supportMsgs = \App\Models\SupportMessage::latest()->get()->map(function ($msg) {
        return (object)[
            'id' => $msg->id,
            'source' => 'support_message',
            'type' => $msg->type, // 'registration' or 'support'
            'sender_name' => $msg->sender_name,
            'sender_phone' => $msg->sender_phone,
            'message' => $msg->message,
            'is_voice' => false,
            'audio_path' => null,
            'is_resolved' => $msg->is_resolved,
            'created_at' => $msg->created_at,
        ];
    });

    // Load PrivateMessage (private chats sent to the admin)
    $privateMsgs = collect();
    if ($adminId) {
        $privateMsgs = \App\Models\PrivateMessage::where('receiver_id', $adminId)
            ->with('sender')
            ->latest()
            ->get()
            ->map(function ($msg) {
                return (object)[
                    'id' => $msg->id,
                    'source' => 'private_message',
                    'type' => 'support', // treat direct private chat as support request
                    'sender_name' => $msg->sender->name ?? 'Noma\'lum',
                    'sender_phone' => $msg->sender->phone ?? 'Noma\'lum',
                    'message' => $msg->message,
                    'is_voice' => (bool)$msg->is_voice,
                    'audio_path' => $msg->audio_path,
                    'is_resolved' => (bool)$msg->is_read, // use is_read as resolved status
                    'created_at' => $msg->created_at,
                ];
            });
    }

    // Combine and sort by created_at desc
    $messages = $supportMsgs->concat($privateMsgs)->sortByDesc('created_at');
    $regions = Region::all();

    return view('admin.messages', compact('messages', 'regions'));
});

// Murojaatga javob yozish
Route::post('/admin/messages/reply', function (Request $request) {
    $request->validate([
        'source' => 'required|in:support_message,private_message',
        'id' => 'required|integer',
        'reply_message' => 'required|string',
    ]);

    $admin = User::where('role', 'admin')->first();
    if (!$admin) {
        return back()->with('error', 'Admin foydalanuvchi topilmadi!');
    }

    $farmerId = null;

    if ($request->source === 'support_message') {
        $msg = \App\Models\SupportMessage::findOrFail($request->id);
        if ($msg->user_id) {
            $farmerId = $msg->user_id;
        } else {
            $user = User::where('phone', $msg->sender_phone)->first();
            if ($user) {
                $farmerId = $user->id;
            }
        }
        $msg->update(['is_resolved' => true]);
    } else {
        $msg = \App\Models\PrivateMessage::findOrFail($request->id);
        $farmerId = $msg->sender_id;
        $msg->update(['is_read' => true]);
    }

    if (!$farmerId) {
        return back()->with('error', 'Fermer hisobi aniqlanmadi (fermer tizimda ro\'yxatdan o\'tmagan bo\'lishi mumkin)!');
    }

    // Create private message from Admin to Farmer
    \App\Models\PrivateMessage::create([
        'sender_id' => $admin->id,
        'receiver_id' => $farmerId,
        'message' => $request->reply_message,
        'is_read' => false,
    ]);

    return back()->with('success', 'Javob dehqonga muvaffaqiyatli yuborildi!');
});

// Murojaatni hal etilgan deb belgilash
Route::post('/admin/messages/resolve/{source}/{id}', function ($source, $id) {
    if ($source === 'support_message') {
        $msg = \App\Models\SupportMessage::findOrFail($id);
        $msg->update(['is_resolved' => true]);
    } else {
        $msg = \App\Models\PrivateMessage::findOrFail($id);
        $msg->update(['is_read' => true]);
    }
    return back()->with('success', 'Murojaat hal etilgan deb belgilandi!');
});

// Murojaatni o'chirish
Route::post('/admin/messages/destroy/{source}/{id}', function ($source, $id) {
    if ($source === 'support_message') {
        $msg = \App\Models\SupportMessage::findOrFail($id);
        $msg->delete();
    } else {
        $msg = \App\Models\PrivateMessage::findOrFail($id);
        $msg->delete();
    }
    return back()->with('success', 'Murojaat o\'chirildi!');
});

}); // Close admin.auth group

// Hukumat monitoring paneli (Login orqali himoyalangan)
Route::middleware('monitor.auth')->group(function () {
    Route::get('/monitor', function (Request $request) {
        return view('monitor');
    });

// Real-vaqt rejimida texnika telemetriyasini beruvchi ochiq JSON API
Route::get('/api/live-vehicles', function () {
    $user = Auth::user();
    $query = Vehicle::with(['farm', 'latestGpsTrack']);
    if ($user && $user->role === 'monitor') {
        $query->whereHas('farm', function($q) use ($user) {
            $q->where('district', $user->district)
              ->where('region_id', $user->region_id);
        });
    }
    $vehicles = $query->get();
    $vehicles->each(function ($v) {
        $v->append('status');
    });
    return response()->json($vehicles);
});

// Real-vaqt rejimida fermer xo'jaliklari, geofencelar va tegishli texnikalarni beruvchi ochiq JSON API
Route::get('/api/live-farms', function () {
    $user = Auth::user();
    $query = \App\Models\Farm::with(['owner', 'geofences.latestSoilAnalysis.recommendation', 'vehicles.latestGpsTrack']);
    if ($user && $user->role === 'monitor') {
        $query->where('district', $user->district)
              ->where('region_id', $user->region_id);
    }
    $farms = $query->get();
    $farms->each(function ($f) {
        $f->vehicles->each(function ($v) {
            $v->append('status');
        });
    });
    return response()->json($farms);
});

// Texnikaning ma'lum bir kundagi GPS harakat tarixi (GIS monitor uchun ochiq API)
Route::get('/api/live-vehicles/{id}/history', function (Request $request, $id) {
    $vehicle = \App\Models\Vehicle::find($id);
    if (!$vehicle) {
        return response()->json(['status' => 'error', 'message' => 'Texnika topilmadi.'], 404);
    }
    
    $date = $request->query('date'); // Format: YYYY-MM-DD
    $query = $vehicle->gpsTracks();
    
    if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $query->whereDate('recorded_at', $date);
    } else {
        // Default: Bugungi kungi ma'lumotlar
        $query->whereDate('recorded_at', \Carbon\Carbon::today());
    }
    
    $history = $query->orderBy('recorded_at', 'asc')->get();
        
    return response()->json([
        'status' => 'success',
        'selected_date' => $date ?: \Carbon\Carbon::today()->toDateString(),
        'history' => $history
    ]);
});

// AI recommendation generator for monitor panel (unauthenticated / token-based)
Route::post('/api/monitor-analysis/{id}/recommend', function (Request $request, $id) {
    $analysis = SoilAnalysis::find($id);
    if (!$analysis) {
        return response()->json(['status' => 'error', 'message' => 'Tahlil topilmadi.'], 404);
    }
    
    $existingRec = \App\Models\Recommendation::where('soil_analysis_id', $analysis->id)->first();
    if ($existingRec) {
        return response()->json([
            'status' => 'success',
            'message' => 'Tavsiya tayyor.',
            'recommendation' => $existingRec
        ]);
    }
    
    $groqService = app(\App\Services\GroqService::class);
    $aiData = $groqService->getSoilRecommendation($analysis);
    
    $recommendation = \App\Models\Recommendation::create([
        'soil_analysis_id' => $analysis->id,
        'content' => $aiData['content'],
        'recommended_crops' => $aiData['recommended_crops'],
        'fertilizer_plan' => $aiData['fertilizer_plan'],
        'ai_model' => $aiData['ai_model'],
        'tokens_used' => $aiData['tokens_used'],
    ]);
    
    $analysis->update(['status' => 'completed']);
    
    return response()->json([
        'status' => 'success',
        'message' => 'AI tavsiyasi tayyorlandi.',
        'recommendation' => $recommendation
    ]);
});

}); // Close monitor.auth group

// Helper to run migrations & seeders on production (cPanel) guarded by admin.auth
Route::middleware('admin.auth')->group(function () {
    Route::get('/admin/deploy-migrate', function (\Illuminate\Http\Request $request) {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoDataSeeder',
                '--force' => true
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\PredefinedFarmSeeder',
                '--force' => true
            ]);
            return 'Migrations and Seeding completed successfully!';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    });
});

// Maxfiylik Siyosati (Privacy Policy) Google Play Market uchun
Route::get('/privacy-policy', function () {
    return view('privacy_policy');
});
