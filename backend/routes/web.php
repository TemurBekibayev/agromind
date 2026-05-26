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

// Dehqonlar ro'yxati
Route::get('/admin/farmers', function () {
    $farmers = User::where('role', 'farmer')->with(['region', 'farms'])->get();
    return view('admin.farmers', compact('farmers'));
});

// Texnikalar ro'yxati
Route::get('/admin/vehicles', function () {
    $vehicles = Vehicle::with(['farm', 'latestGpsTrack'])->get();
    return view('admin.vehicles', compact('vehicles'));
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
