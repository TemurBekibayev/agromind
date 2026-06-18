<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\SoilAnalysis;
use App\Models\Alert;
use App\Models\Region;

Route::get('/', function () {
    return redirect('/admin/dashboard');
});

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
    $regions = Region::all();
    $predefinedFarms = \App\Models\PredefinedFarm::orderBy('name')->get();
    return view('admin.farmers', compact('farmers', 'regions', 'predefinedFarms'));
});

// Yangi Dehqon (Fermer) saqlash
Route::post('/admin/farmers/store', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:users,phone',
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
    ]);

    User::create([
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
        'role' => 'farmer',
        'password' => Hash::make('secret123'), // Default password
    ]);

    return back()->with('success', 'Yangi dehqon (fermer) muvaffaqiyatli ro\'yxatga olindi!');
});

// Dehqon (Fermer) tahrirlash (update)
Route::post('/admin/farmers/update/{id}', function (Request $request, $id) {
    $farmer = User::where('role', 'farmer')->findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:users,phone,' . $id,
        'region_id' => 'required|exists:regions,id',
        'district' => 'nullable|string|max:255',
    ]);

    $farmer->update([
        'name' => $request->name,
        'phone' => $request->phone,
        'region_id' => $request->region_id,
        'district' => $request->district ?? 'Amudaryo tumani',
    ]);

    return back()->with('success', 'Dehqon (fermer) ma\'lumotlari muvaffaqiyatli yangilandi!');
});

// Dehqon (Fermer) o'chirish (delete)
Route::post('/admin/farmers/destroy/{id}', function ($id) {
    $farmer = User::where('role', 'farmer')->with('farms.vehicles', 'farms.soilAnalyses', 'farms.geofences', 'farms.alerts')->findOrFail($id);

    // Cascade delete related farms and their data
    foreach ($farmer->farms as $farm) {
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

    $farmer->delete();

    return back()->with('success', 'Dehqon (fermer) va uning barcha yer maydonlari muvaffaqiyatli o\'chirildi!');
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
        'fuel_capacity' => 'required|numeric|min:10',
    ]);

    $vehicle = Vehicle::create([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
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
        'fuel_capacity' => 'required|numeric|min:10',
    ]);

    $vehicle->update([
        'name' => $request->name,
        'type' => $request->type,
        'plate_number' => $request->plate_number,
        'farm_id' => $request->farm_id,
        'gps_device_id' => $request->gps_device_id,
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

// Hukumat monitoring paneli (Token bilan himoyalangan, Login shart emas)
Route::get('/monitor', function (Request $request) {
    $validToken = 'agromind_monitoring_token_2026';
    
    if ($request->query('token') !== $validToken) {
        abort(403, 'Ruxsat etilmagan kirish. Monitoring tokeni xato yoki mavjud emas.');
    }
    
    return view('monitor');
});

// Real-vaqt rejimida texnika telemetriyasini beruvchi ochiq JSON API
Route::get('/api/live-vehicles', function () {
    $vehicles = Vehicle::with(['farm', 'latestGpsTrack'])->get();
    $vehicles->each(function ($v) {
        $v->append('status');
    });
    return response()->json($vehicles);
});

// Real-vaqt rejimida fermer xo'jaliklari, geofencelar va tegishli texnikalarni beruvchi ochiq JSON API
Route::get('/api/live-farms', function () {
    $farms = \App\Models\Farm::with(['owner', 'geofences.latestSoilAnalysis.recommendation', 'vehicles.latestGpsTrack'])->get();
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

// Helper to run migrations & seeders on production (cPanel)
Route::get('/admin/deploy-migrate', function (\Illuminate\Http\Request $request) {
    if ($request->query('token') !== 'agromind_monitoring_token_2026') {
        abort(403, 'Unauthorized.');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PredefinedFarmSeeder',
            '--force' => true
        ]);
        return 'Migrations and Seeding completed successfully!';
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
    
    $existingRecords = collect();
    $farmId = $request->query('farm_id');
    $year = $request->query('year', date('Y'));
    $month = $request->query('month');

    if ($farmId && $month) {
        $existingRecords = WaterRecord::where('farm_id', $farmId)
            ->where('year', $year)
            ->where('month', $month)
            ->get();
    }

    return view('water.entry', compact('farms', 'existingRecords'));
});

Route::post('/water-entry/store', function (Request $request) {
    if ($request->input('token') !== 'agromind_water_entry_2026') {
        abort(403, 'Ruxsat etilmagan kirish.');
    }

    $request->validate([
        'farm_id' => 'required|exists:farms,id',
        'year' => 'required|integer',
        'month' => 'required|integer|between:1,12',
        'records' => 'required|array',
    ]);

    $farmId = $request->input('farm_id');
    $year = $request->input('year');
    $month = $request->input('month');

    foreach ($request->input('records') as $source => $decades) {
        foreach ($decades as $decade => $values) {
            WaterRecord::updateOrCreate([
                'farm_id' => $farmId,
                'year' => $year,
                'month' => $month,
                'decade' => $decade,
                'water_source' => $source,
            ], [
                'limit_m3' => $values['limit_m3'] ?? 0.00,
                'used_m3' => $values['used_m3'] ?? 0.00,
            ]);
        }
    }

    return back()->with('success', 'Ushbu oy uchun suv limitlari va sarfi muvaffaqiyatli saqlandi!');
});

// 2. Admin Panel integratsiyasi
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
        ->orderBy('decade', 'asc')
        ->paginate(15);

    return view('admin.water', compact('records', 'farms', 'totalLimit', 'totalUsed'));
});

Route::get('/admin/water/create', function (Request $request) {
    $farms = Farm::with('owner')->orderBy('name')->get();
    
    $existingRecords = collect();
    $farmId = $request->query('farm_id');
    $year = $request->query('year', date('Y'));
    $month = $request->query('month');

    if ($farmId && $month) {
        $existingRecords = WaterRecord::where('farm_id', $farmId)
            ->where('year', $year)
            ->where('month', $month)
            ->get();
    }

    return view('admin.water_create', compact('farms', 'existingRecords'));
});

Route::post('/admin/water/store', function (Request $request) {
    $request->validate([
        'farm_id' => 'required|exists:farms,id',
        'year' => 'required|integer',
        'month' => 'required|integer|between:1,12',
        'records' => 'required|array',
    ]);

    $farmId = $request->input('farm_id');
    $year = $request->input('year');
    $month = $request->input('month');

    foreach ($request->input('records') as $source => $decades) {
        foreach ($decades as $decade => $values) {
            WaterRecord::updateOrCreate([
                'farm_id' => $farmId,
                'year' => $year,
                'month' => $month,
                'decade' => $decade,
                'water_source' => $source,
            ], [
                'limit_m3' => $values['limit_m3'] ?? 0.00,
                'used_m3' => $values['used_m3'] ?? 0.00,
            ]);
        }
    }

    return redirect('/admin/water')->with('success', 'Suv limitlari va sarfi muvaffaqiyatli saqlandi!');
});

Route::post('/admin/water/destroy/{id}', function ($id) {
    $record = WaterRecord::findOrFail($id);
    $record->delete();
    return back()->with('success', 'Suv yozuvi muvaffaqiyatli o\'chirildi.');
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
        ->orderBy('decade', 'asc')
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
        ->orderBy('decade', 'asc')
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
        'decade' => 'required|integer|between:1,3',
        'water_source' => 'required|string',
        'limit_m3' => 'required|numeric|min:0',
        'used_m3' => 'required|numeric|min:0',
    ]);

    $record = WaterRecord::updateOrCreate([
        'farm_id' => $request->farm_id,
        'year' => $request->year,
        'month' => $request->month,
        'decade' => $request->decade,
        'water_source' => $request->water_source,
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

// Maxfiylik Siyosati (Privacy Policy) Google Play Market uchun
Route::get('/privacy-policy', function () {
    return view('privacy_policy');
});
