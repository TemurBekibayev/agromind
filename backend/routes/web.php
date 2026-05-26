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
    $farmers = User::where('role', 'farmer')->with(['region', 'farms'])->get();
    $regions = Region::all();
    return view('admin.farmers', compact('farmers', 'regions'));
});

// Yangi Farm va uning xarita geofence chegarasini saqlash
Route::post('/admin/farms/store', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'user_id' => 'required|exists:users,id',
        'region_id' => 'required|exists:regions,id',
        'size' => 'required|numeric|min:0.1',
        'soil_type' => 'required|string',
        'coordinates' => 'required|string', // JSON string array of coords
    ]);

    $coordinates = json_decode($request->coordinates, true);
    if (!is_array($coordinates) || count($coordinates) < 3) {
        return back()->withErrors(['coordinates' => 'Kamida 3 ta nuqta tanlab, yopiq ko\'rinishda chegara chizishingiz kerak.']);
    }

    // Birinchi chizilgan nuqtani fermaning markaziy koordinatasi deb hisoblaymiz
    $firstVertex = $coordinates[0];

    $farm = \App\Models\Farm::create([
        'name' => $request->name,
        'user_id' => $request->user_id,
        'region_id' => $request->region_id,
        'size' => $request->size,
        'soil_type' => $request->soil_type,
        'latitude' => $firstVertex[0],
        'longitude' => $firstVertex[1],
        'location' => 'GIS Chegara maydoni',
    ]);

    // Xaritada chizilgan geofence chegara maydonini yaratish
    \App\Models\Geofence::create([
        'farm_id' => $farm->id,
        'name' => 'Asosiy yer chegarasi',
        'coordinates' => $coordinates,
    ]);

    return back()->with('success', 'Yangi fermer xo\'jaligi va uning yer maydoni muvaffaqiyatli saqlandi!');
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
    $farms = \App\Models\Farm::with(['owner', 'geofences', 'vehicles.latestGpsTrack'])->get();
    $farms->each(function ($f) {
        $f->vehicles->each(function ($v) {
            $v->append('status');
        });
    });
    return response()->json($farms);
});
