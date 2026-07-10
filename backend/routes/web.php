<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\SoilAnalysis;
use App\Models\Alert;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

Route::get('/login', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->role === 'admin' || $user->role === 'monitor') {
            return redirect('/admin/dashboard');
        }
    }
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'phone' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        } elseif ($user->role === 'monitor') {
            return redirect()->intended('/monitor');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return back()->withErrors([
            'phone' => 'Admin panelga kirish ruxsatingiz yo\'q.',
        ])->onlyInput('phone');
    }

    return back()->withErrors([
        'phone' => 'Kiritilgan telefon raqam yoki maxfiy kod noto\'g\'ri.',
    ])->onlyInput('phone');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login')->with('success', 'Tizimdan muvaffaqiyatli chiqdingiz.');
})->name('logout');

Route::get('/', function () {
    return redirect('/admin/dashboard');
});

Route::middleware(['admin.auth'])->group(function () {

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
        'phone' => 'required|string|max:20|unique:users,phone',
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'role' => 'required|in:farmer,monitor',
        'password' => 'required|string|min:4',
    ]);

    User::create([
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'role' => $request->role,
        'password' => Hash::make($request->password),
        'plain_password' => $request->password,
    ]);

    $roleName = $request->role === 'monitor' ? 'Tuman nazoratchisi' : 'Dehqon (fermer)';
    return back()->with('success', "Yangi {$roleName} muvaffaqiyatli ro'yxatga olindi!");
});

// Dehqon (Fermer) yoki Monitor tahrirlash (update)
Route::post('/admin/farmers/update/{id}', function (Request $request, $id) {
    $user = User::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:users,phone,' . $id,
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
        'password' => 'nullable|string|min:4',
    ]);

    $updateData = [
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
    ];

    if ($request->filled('password')) {
        $updateData['password'] = Hash::make($request->password);
        $updateData['plain_password'] = $request->password;
    }

    $user->update($updateData);

    $roleName = $user->role === 'monitor' ? 'Tuman nazoratchisi' : 'Dehqon (fermer)';
    return back()->with('success', "{$roleName} ma'lumotlari muvaffaqiyatli yangilandi!");
});

// Dehqon (Fermer) yoki Monitor o'chirish (delete)
Route::post('/admin/farmers/destroy/{id}', function ($id) {
    $user = User::findOrFail($id);

    if ($user->role === 'farmer') {
        $user->load('farms.vehicles', 'farms.soilAnalyses', 'farms.geofences', 'farms.alerts');
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
    }

    $user->delete();

    $roleName = $user->role === 'monitor' ? 'Tuman nazoratchisi' : 'Dehqon (fermer)';
    return back()->with('success', "{$roleName} muvaffaqiyatli o'chirildi!");
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
        'nominal_rate_road' => 'nullable|numeric|min:0.1',
        'nominal_rate_work_light' => 'nullable|numeric|min:0.1',
        'nominal_rate_work_heavy' => 'nullable|numeric|min:0.1',
    ]);

    $rates = Vehicle::getNominalRatesForName($request->name);

    $vehicle = Vehicle::create([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
        'sim_number' => $request->sim_number,
        'fuel_capacity' => $request->fuel_capacity,
        'nominal_rate_road' => $request->nominal_rate_road ?? $rates['road'],
        'nominal_rate_work_light' => $request->nominal_rate_work_light ?? $rates['light'],
        'nominal_rate_work_heavy' => $request->nominal_rate_work_heavy ?? $rates['heavy'],
        'current_fuel_level' => $request->fuel_capacity * 0.8, // Boshlang'ich yoqilg'i: 80%
    ]);

    // Boshlang'ich GPS koordinatasini ferma joylashgan joyda yaratish (xaritada darhol ko'rinishi uchun)
    $farm = $vehicle->farm;
    if ($farm && $farm->latitude && $farm->longitude) {
        \App\Models\GpsTrack::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $farm->latitude,
            'longitude' => $farm->longitude,
            'speed' => 0.0,
            'fuel_level' => 80.0,
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
        'nominal_rate_road' => 'nullable|numeric|min:0.1',
        'nominal_rate_work_light' => 'nullable|numeric|min:0.1',
        'nominal_rate_work_heavy' => 'nullable|numeric|min:0.1',
    ]);

    $vehicle->update([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
        'sim_number' => $request->sim_number,
        'fuel_capacity' => $request->fuel_capacity,
        'nominal_rate_road' => $request->nominal_rate_road ?? $vehicle->nominal_rate_road,
        'nominal_rate_work_light' => $request->nominal_rate_work_light ?? $vehicle->nominal_rate_work_light,
        'nominal_rate_work_heavy' => $request->nominal_rate_work_heavy ?? $vehicle->nominal_rate_work_heavy,
    ]);

    return back()->with('success', 'Texnika ma\'lumotlari muvaffaqiyatli yangilandi!');
});

// Shubhali yoqilg'i ogohlantirishini tasdiqlash yoki rad etish
Route::post('/admin/fuel-alerts/{id}/resolve', function (Request $request, $id) {
    $alert = \App\Models\FuelAlert::findOrFail($id);
    $status = $request->status; // 'confirmed' yoki 'rejected'
    
    $alert->update(['status' => $status]);
    
    if ($status === 'confirmed') {
        $vehicle = $alert->vehicle;
        $road = floatval($vehicle->nominal_rate_road) * 1.05;
        $light = floatval($vehicle->nominal_rate_work_light) * 1.05;
        $heavy = floatval($vehicle->nominal_rate_work_heavy) * 1.05;
        
        $vehicle->update([
            'nominal_rate_road' => round($road, 2),
            'nominal_rate_work_light' => round($light, 2),
            'nominal_rate_work_heavy' => round($heavy, 2)
        ]);

        \App\Models\Alert::create([
            'vehicle_id' => $vehicle->id,
            'farm_id' => $vehicle->farm_id,
            'type' => 'system_calibration',
            'message' => "Tizim o'rganish natijasi: {$vehicle->name} uchun yoqilg'i me'yorlari kalibrlandi (Yo'l: {$vehicle->nominal_rate_road}L/s, Yengil: {$vehicle->nominal_rate_work_light}L/s, Og'ir: {$vehicle->nominal_rate_work_heavy}L/s).",
            'status' => 'resolved',
            'triggered_at' => now(),
        ]);
    }
    
    return back()->with('success', $status === 'confirmed' ? 'Shubhali holat tasdiqlandi va tizim me\'yorlari kalibrlandi!' : 'Shubhali holat rad etildi.');
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

// Adminga kelgan Murojaatlar ro'yxati
Route::get('/admin/messages', function () {
    $messages = \App\Models\SupportMessage::with('user')->orderBy('is_resolved', 'asc')->latest()->get();
    return view('admin.messages', compact('messages'));
});

// Murojaatni o'qilgan/hal qilingan deb belgilash
Route::post('/admin/messages/resolve/{id}', function ($id) {
    $msg = \App\Models\SupportMessage::findOrFail($id);
    $msg->update(['is_resolved' => true]);
    return back()->with('success', 'Murojaat muvaffaqiyatli o\'qildi/hal qilindi deb belgilandi.');
});

// Murojaatni o'chirish
Route::post('/admin/messages/destroy/{id}', function ($id) {
    $msg = \App\Models\SupportMessage::findOrFail($id);
    $msg->delete();
    return back()->with('success', 'Murojaat muvaffaqiyatli o\'chirildi.');
});

// Hududlar ro'yxati
Route::get('/admin/regions', function () {
    $regions = Region::withCount(['users', 'farms'])->get();
    return view('admin.regions', compact('regions'));
});

// Kutilayotgan GPS buyruqlari navbati (Bloklash/Ochish)
Route::get('/admin/commands', function () {
    $vehicles = Vehicle::with(['farm'])->get();
    $pendingCommands = [];
    
    foreach ($vehicles as $vehicle) {
        $key = "gps_command_{$vehicle->gps_device_id}";
        if (Cache::has($key)) {
            $command = Cache::get($key);
            $status = $vehicle->status;
            
            if ($status === 'offline') {
                $reason = 'Qurilma tarmoqdan uzilgan (Offline). Ulanishi kutilmoqda.';
                $solution = 'Qurilma quvvati, SIM-kartadagi megabayt balansi yoki ochiqroq osmon ostiga olib chiqishni tekshiring.';
            } else {
                $reason = 'GPS sun\'iy yo\'ldosh signali yo\'q (Searching for Satellite).';
                $solution = 'Qurilma online, biroq xavfsizlik cheklovi tufayli dvigatelni o\'chirish kechiktirilyapti. Mashinani tepasi ochiqroq joyga siljiting.';
            }
            
            $pendingCommands[] = [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'gps_device_id' => $vehicle->gps_device_id,
                'farm_name' => $vehicle->farm ? $vehicle->farm->name : 'Noma\'lum',
                'command' => $command,
                'status' => $status,
                'reason' => $reason,
                'solution' => $solution,
            ];
        }
    }
    
    return view('admin.commands', compact('pendingCommands'));
});

// Kutilayotgan GPS buyruqni bekor qilish
Route::post('/admin/commands/clear/{id}', function ($id) {
    $vehicle = Vehicle::findOrFail($id);
    Cache::forget("gps_command_{$vehicle->gps_device_id}");
    return back()->with('success', "{$vehicle->name} texnikasi uchun navbatdagi buyruq muvaffaqiyatli bekor qilindi.");
});

});

// Hukumat monitoring paneli (Hozirda faqat admin va monitor rollariga login orqali ruxsat beriladi)
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/monitor', function (Request $request) {
        return view('monitor');
    });

    // Real-vaqt rejimida texnika telemetriyasini beruvchi JSON API
    Route::get('/api/live-vehicles', function () {
        $user = Auth::user();
        $query = Vehicle::with(['farm', 'latestGpsTrack']);
        
        if ($user->role === 'monitor') {
            $query->whereHas('farm', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }
        
        $vehicles = $query->get();
        $vehicles->each(function ($v) {
            $v->append('status');
        });
        return response()->json($vehicles);
    });

    // Real-vaqt rejimida fermer xo'jaliklari, geofencelar va tegishli texnikalarni beruvchi JSON API
    Route::get('/api/live-farms', function () {
        $user = Auth::user();
        $query = \App\Models\Farm::with(['owner', 'geofences.latestSoilAnalysis.recommendation', 'vehicles.latestGpsTrack']);
        
        if ($user->role === 'monitor') {
            $query->where('district', $user->district);
        }
        
        $farms = $query->get();
        $farms->each(function ($f) {
            $f->vehicles->each(function ($v) {
                $v->append('status');
            });
        });
        return response()->json($farms);
    });

    // Texnikaning ma'lum bir kundagi GPS harakat tarixi (GIS monitor uchun API)
    Route::get('/api/live-vehicles/{id}/history', function (Request $request, $id) {
        $user = Auth::user();
        $vehicle = \App\Models\Vehicle::findOrFail($id);
        
        if ($user->role === 'monitor' && (!$vehicle->farm || $vehicle->farm->district !== $user->district)) {
            abort(403, 'Bu texnika ma\'lumotlarini ko\'rishga ruxsatingiz yo\'q.');
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

    // AI recommendation generator for monitor panel (session-authenticated)
    Route::post('/api/monitor-analysis/{id}/recommend', function (Request $request, $id) {
        $user = Auth::user();
        $analysis = SoilAnalysis::findOrFail($id);
        
        if ($user->role === 'monitor' && (!$analysis->farm || $analysis->farm->district !== $user->district)) {
            abort(403, 'Bu tahlil ma\'lumotlarini ko\'rishga ruxsatingiz yo\'q.');
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
});

Route::get('/admin/deploy-migrate', function (\Illuminate\Http\Request $request) {
    if ($request->query('token') !== 'agromind_monitoring_token_2026') {
        abort(403, 'Unauthorized.');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        
        // Update or create admin credentials
        $admin = \App\Models\User::where('role', 'admin')->first();
        if ($admin) {
            $admin->update([
                'phone' => 'admin@uzagromind.uz',
                'password' => \Illuminate\Support\Facades\Hash::make('uzagromind4321')
            ]);
        } else {
            \App\Models\User::create([
                'name' => 'Admin AgroMind',
                'phone' => 'admin@uzagromind.uz',
                'role' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('uzagromind4321')
            ]);
        }

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PredefinedFarmSeeder',
            '--force' => true
        ]);
        return 'Migrations and Seeding completed successfully, admin user updated!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// ==================== SUV NAZORATI VA MONITORINGI MODULI ====================

use App\Models\WaterRecord;
use App\Models\Farm;

// 1. Tashqi xodimlar uchun umumiy to'ldirish oynasi (Token bilan himoyalangan)
Route::get('/water-entry', function (Request $request) {
    $token = $request->query('token');
    if ($token !== 'agromind_water_entry_2026') {
        abort(403, 'Ruxsat etilmagan kirish. Token xato yoki taqdim etilmagan.');
    }

    $farms = Farm::with('owner')->orderBy('name')->get();
    
    $existingRecord = null;
    $farmId = $request->query('farm_id');
    $year = $request->query('year', date('Y'));
    $month = $request->query('month');

    if ($farmId && $month) {
        $existingRecord = WaterRecord::where('farm_id', $farmId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    return view('water.entry', compact('farms', 'existingRecord'));
});

Route::post('/water-entry/store', function (Request $request) {
    if ($request->input('token') !== 'agromind_water_entry_2026') {
        abort(403, 'Ruxsat etilmagan kirish.');
    }

    $request->validate([
        'farm_id' => 'required|exists:farms,id',
        'year' => 'required|integer',
        'month' => 'required|integer|between:1,12',
        'limit_m3' => 'required|numeric|min:0',
        'used_m3' => 'required|numeric|min:0',
    ]);

    $farmId = $request->input('farm_id');
    $year = $request->input('year');
    $month = $request->input('month');

    WaterRecord::updateOrCreate([
        'farm_id' => $farmId,
        'year' => $year,
        'month' => $month,
    ], [
        'limit_m3' => $request->input('limit_m3'),
        'used_m3' => $request->input('used_m3'),
    ]);

    return back()->with('success', 'Ushbu oy uchun suv limitlari va sarfi muvaffaqiyatli saqlandi!');
});

// 2. Admin Panel integratsiyasi
Route::middleware(['admin.auth'])->group(function () {
Route::get('/admin/water', function (Request $request) {
    $farms = Farm::orderBy('name')->get();

    $query = WaterRecord::with('farm');

    if ($request->filled('farm_id')) {
        $query->where('farm_id', $request->farm_id);
    }
    if ($request->filled('year')) {
        $query->where('year', $request->year);
    }
    if ($request->filled('month')) {
        $query->where('month', $request->month);
    }

    // Statistika hisoblash
    $totalLimit = (double) $query->sum('limit_m3');
    $totalUsed = (double) $query->sum('used_m3');

    $records = $query->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->paginate(15);

    return view('admin.water', compact('records', 'farms', 'totalLimit', 'totalUsed'));
});

Route::get('/admin/water/create', function (Request $request) {
    $farms = Farm::with('owner')->orderBy('name')->get();
    
    $existingRecord = null;
    $farmId = $request->query('farm_id');
    $year = $request->query('year', date('Y'));
    $month = $request->query('month');

    if ($farmId && $month) {
        $existingRecord = WaterRecord::where('farm_id', $farmId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    return view('admin.water_create', compact('farms', 'existingRecord'));
});

Route::post('/admin/water/store', function (Request $request) {
    $request->validate([
        'farm_id' => 'required|exists:farms,id',
        'year' => 'required|integer',
        'month' => 'required|integer|between:1,12',
        'limit_m3' => 'required|numeric|min:0',
        'used_m3' => 'required|numeric|min:0',
    ]);

    $farmId = $request->input('farm_id');
    $year = $request->input('year');
    $month = $request->input('month');

    WaterRecord::updateOrCreate([
        'farm_id' => $farmId,
        'year' => $year,
        'month' => $month,
    ], [
        'limit_m3' => $request->input('limit_m3'),
        'used_m3' => $request->input('used_m3'),
    ]);

    return redirect('/admin/water')->with('success', 'Suv limitlari va sarfi muvaffaqiyatli saqlandi!');
});

Route::post('/admin/water/destroy/{id}', function ($id) {
    $record = WaterRecord::findOrFail($id);
    $record->delete();
    return back()->with('success', 'Suv yozuvi muvaffaqiyatli o\'chirildi.');
});

});

// 3. Mobil dasturchilar uchun ochiq API yo'nalishlari (API routes)
Route::get('/api/farms/{id}/water-records', function ($id) {
    $farm = Farm::find($id);
    if (!$farm) {
        return response()->json(['status' => 'error', 'message' => 'Fermer xo\'jaligi topilmadi.'], 404);
    }

    $records = WaterRecord::where('farm_id', $id)
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'farm_id' => $farm->id,
        'farm_name' => $farm->name,
        'records' => $records
    ]);
});

Route::get('/api/farmers/{id}/water-records', function ($id) {
    $farmer = User::where('role', 'farmer')->find($id);
    if (!$farmer) {
        return response()->json(['status' => 'error', 'message' => 'Fermer topilmadi.'], 404);
    }

    $farmIds = Farm::where('user_id', $id)->pluck('id');

    $records = WaterRecord::whereIn('farm_id', $farmIds)
        ->with('farm')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'farmer_id' => $farmer->id,
        'farmer_name' => $farmer->name,
        'records' => $records
    ]);
});

Route::post('/api/water-records/store', function (Request $request) {
    // Mobil ilovadan kiritilganda ham token tekshirish
    if ($request->header('Authorization') !== 'Bearer agromind_water_entry_2026') {
        return response()->json(['status' => 'error', 'message' => 'Ruxsat berilmagan.'], 401);
    }

    $request->validate([
        'farm_id' => 'required|exists:farms,id',
        'year' => 'required|integer',
        'month' => 'required|integer|between:1,12',
        'limit_m3' => 'required|numeric|min:0',
        'used_m3' => 'required|numeric|min:0',
    ]);

    $record = WaterRecord::updateOrCreate([
        'farm_id' => $request->farm_id,
        'year' => $request->year,
        'month' => $request->month,
    ], [
        'limit_m3' => $request->limit_m3,
        'used_m3' => $request->used_m3,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Suv sarfi ma\'lumoti muvaffaqiyatli saqlandi.',
        'record' => $record
    ]);
});

// Interaktiv taqdimot slaydlari (Fermerlarga tushuntirish va namoyish qilish uchun)
Route::get('/presentation', function () {
    return view('presentation');
});

// Maxfiylik Siyosati (Privacy Policy) Google Play Market uchun
Route::get('/privacy-policy', function () {
    return view('privacy_policy');
});
