<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Region;
use App\Models\Farm;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Models\GpsTrack;
use App\Models\SoilAnalysis;
use App\Models\Recommendation;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Find or create Qoraqalpog'iston region
        $region = Region::firstOrCreate(
            ['name' => 'Qoraqalpog\'iston Respublikasi'],
            ['geojson' => null]
        );

        // Also make sure other regions exist for presentation drop-downs
        Region::firstOrCreate(['name' => 'Toshkent viloyati']);
        Region::firstOrCreate(['name' => 'Buxoro viloyati']);
        Region::firstOrCreate(['name' => 'Farg\'ona viloyati']);

        // 2. Create/Update the Test Farmer ("Test Rejimi")
        $farmer = User::updateOrCreate(
            ['phone' => '998901111111'],
            [
                'name' => 'Test Rejimi',
                'role' => 'farmer',
                'region_id' => $region->id,
                'district' => 'Amudaryo tumani',
                'password' => Hash::make('secret123'),
                'plain_password' => 'secret123'
            ]
        );

        // Also create/update standard monitor users for testing/demo
        User::updateOrCreate(
            ['phone' => 'amudaryo_monitor'],
            [
                'name' => 'Amudaryo Nazoratchisi',
                'role' => 'monitor',
                'region_id' => $region->id,
                'district' => 'Amudaryo tumani',
                'password' => Hash::make('secretpassword'),
                'plain_password' => 'secretpassword'
            ]
        );

        User::updateOrCreate(
            ['phone' => 'shumanay_monitor'],
            [
                'name' => 'Shumanay Nazoratchisi',
                'role' => 'monitor',
                'region_id' => $region->id,
                'district' => 'Shumanay tumani',
                'password' => Hash::make('secretpassword'),
                'plain_password' => 'secretpassword'
            ]
        );

        // 3. Define the 6 test fields data
        $farmsData = [
            [
                'name' => 'Test Ekin Maydoni 1 (Paxta)',
                'lat' => 42.1100, 'lng' => 60.0700, 'size' => 15.5, 'soil_type' => 'Bo\'z tuproq',
                'crop' => 'G\'o\'za (Paxta)', 'ph' => 6.80, 'fertility' => 72.5, 'moisture' => 45.0,
                'rec_crops' => ['Paxta', 'Bedaviy ekinlar'],
                'fertilizer' => ['Azotli o\'g\'itlar: 50kg/ga', 'Fosforli o\'g\'itlar: 30kg/ga'],
                'coords' => [[42.1080, 60.0680], [42.1120, 60.0680], [42.1120, 60.0720], [42.1080, 60.0720], [42.1080, 60.0680]]
            ],
            [
                'name' => 'Test Ekin Maydoni 2 (Bug\'doy)',
                'lat' => 42.1150, 'lng' => 60.0780, 'size' => 10.2, 'soil_type' => 'Loy tuproq',
                'crop' => 'Bug\'doy', 'ph' => 7.10, 'fertility' => 65.0, 'moisture' => 55.0,
                'rec_crops' => ['Bug\'doy', 'Arpa', 'Soya'],
                'fertilizer' => ['Azotli o\'g\'itlar: 60kg/ga', 'Kaliyli o\'g\'itlar: 20kg/ga'],
                'coords' => [[42.1130, 60.0760], [42.1170, 60.0760], [42.1170, 60.0800], [42.1130, 60.0800], [42.1130, 60.0760]]
            ],
            [
                'name' => 'Test Ekin Maydoni 3 (Sholi)',
                'lat' => 42.1030, 'lng' => 60.0630, 'size' => 8.0, 'soil_type' => 'Sho\'r tuproq',
                'crop' => 'Sholi', 'ph' => 6.20, 'fertility' => 58.0, 'moisture' => 75.0,
                'rec_crops' => ['Sholi', 'Makkajo\'xori'],
                'fertilizer' => ['Ammoniyli o\'g\'itlar: 40kg/ga', 'Organik o\'g\'itlar'],
                'coords' => [[42.1010, 60.0610], [42.1050, 60.0610], [42.1050, 60.0650], [42.1010, 60.0650], [42.1010, 60.0610]]
            ],
            [
                'name' => 'Test Ekin Maydoni 4 (Yonchqa)',
                'lat' => 42.1220, 'lng' => 60.0820, 'size' => 12.5, 'soil_type' => 'Qumloq tuproq',
                'crop' => 'Bedaviy ekinlar (Yonchqa)', 'ph' => 6.90, 'fertility' => 78.0, 'moisture' => 38.0,
                'rec_crops' => ['Yonchqa', 'Paxta'],
                'fertilizer' => ['Fosforli o\'g\'itlar: 40kg/ga', 'Sideratlar kiritish'],
                'coords' => [[42.1200, 60.0800], [42.1240, 60.0800], [42.1240, 60.0840], [42.1200, 60.0840], [42.1200, 60.0800]]
            ],
            [
                'name' => 'Test Ekin Maydoni 5 (Makkajo\'xori)',
                'lat' => 42.0980, 'lng' => 60.0580, 'size' => 7.0, 'soil_type' => 'Sariq tuproq',
                'crop' => 'Makkajo\'xori', 'ph' => 6.50, 'fertility' => 62.0, 'moisture' => 48.0,
                'rec_crops' => ['Makkajo\'xori', 'Soya', 'Kunjut'],
                'fertilizer' => ['Azotli o\'g\'itlar: 55kg/ga', 'Kompost solish'],
                'coords' => [[42.0960, 60.0560], [42.1000, 60.0560], [42.1000, 60.0600], [42.0960, 60.0600], [42.0960, 60.0560]]
            ],
            [
                'name' => 'Test Ekin Maydoni 6 (Poliz)',
                'lat' => 42.1280, 'lng' => 60.0880, 'size' => 9.5, 'soil_type' => 'Yengil qumloq',
                'crop' => 'Poliz ekinlari (Qovun/Tarvuz)', 'ph' => 7.20, 'fertility' => 70.0, 'moisture' => 42.0,
                'rec_crops' => ['Qovun', 'Tarvuz', 'Qovoq'],
                'fertilizer' => ['Kaliy o\'g\'itlari: 30kg/ga', 'Organik chirindi'],
                'coords' => [[42.1260, 60.0860], [42.1300, 60.0860], [42.1300, 60.0900], [42.1260, 60.0900], [42.1260, 60.0860]]
            ],
        ];

        $seededFarms = [];

        foreach ($farmsData as $index => $fd) {
            // Create or update farm
            $farm = Farm::updateOrCreate(
                [
                    'user_id' => $farmer->id,
                    'name' => $fd['name']
                ],
                [
                    'location' => 'Mangit sh.',
                    'latitude' => $fd['lat'],
                    'longitude' => $fd['lng'],
                    'size' => $fd['size'],
                    'soil_type' => $fd['soil_type'],
                    'region_id' => $region->id,
                    'district' => 'Amudaryo tumani',
                ]
            );

            // Create or update geofence
            $geofence = Geofence::updateOrCreate(
                [
                    'farm_id' => $farm->id,
                    'name' => 'Chegara maydoni'
                ],
                [
                    'coordinates' => $fd['coords']
                ]
            );

            // Create soil analysis
            $analysis = SoilAnalysis::updateOrCreate(
                [
                    'farm_id' => $farm->id,
                    'geofence_id' => $geofence->id,
                ],
                [
                    'target_crop' => $fd['crop'],
                    'ph' => $fd['ph'],
                    'fertility' => $fd['fertility'],
                    'moisture' => $fd['moisture'],
                    'temperature' => 22.50 + $index,
                    'sunlight' => 40000.00,
                    'humidity' => 52.00,
                    'analysis_date' => Carbon::today()->toDateString(),
                    'status' => 'completed'
                ]
            );

            // Create AI recommendation
            Recommendation::updateOrCreate(
                ['soil_analysis_id' => $analysis->id],
                [
                    'content' => "Test Rejimidagi tuproq tahlili (pH: {$fd['ph']}, unumdorlik: {$fd['fertility']}%). Ekin turi: {$fd['crop']}. Tavsiya etiladi: o'g'itlash rejasini o'z vaqtida bajarish va namlikni saqlab qolish.",
                    'recommended_crops' => $fd['rec_crops'],
                    'fertilizer_plan' => $fd['fertilizer'],
                    'ai_model' => 'llama3-8b-8192',
                    'tokens_used' => 200
                ]
            );

            $seededFarms[] = $farm;
        }

        // 4. Create 3 test vehicles for this farmer's farms
        $vehiclesData = [
            [
                'name' => 'John Deere Kombayn (Test)',
                'type' => 'combine',
                'plate_number' => '95 V 007 AA',
                'gps_device_id' => 'gps_test_combine_01',
                'farm' => $seededFarms[0],
                'start_lat' => 42.1060, 'start_lng' => 60.0660,
                'end_lat' => 42.1100, 'end_lng' => 60.0700
            ],
            [
                'name' => 'TTZ Traktor (Test)',
                'type' => 'tractor',
                'plate_number' => '95 V 061 AA',
                'gps_device_id' => 'gps_test_tractor_01',
                'farm' => $seededFarms[1],
                'start_lat' => 42.1110, 'start_lng' => 60.0740,
                'end_lat' => 42.1150, 'end_lng' => 60.0780
            ],
            [
                'name' => 'Claas Kombayn (Test)',
                'type' => 'combine',
                'plate_number' => '95 V 999 AA',
                'gps_device_id' => 'gps_test_combine_02',
                'farm' => $seededFarms[2],
                'start_lat' => 42.0990, 'start_lng' => 60.0590,
                'end_lat' => 42.1030, 'end_lng' => 60.0630
            ],
        ];

        foreach ($vehiclesData as $vd) {
            $vehicle = Vehicle::updateOrCreate(
                ['gps_device_id' => $vd['gps_device_id']],
                [
                    'farm_id' => $vd['farm']->id,
                    'name' => $vd['name'],
                    'type' => $vd['type'],
                    'plate_number' => $vd['plate_number'],
                    'fuel_capacity' => 180.00
                ]
            );

            // Clean existing tracks for this test device to recreate fresh history for today
            GpsTrack::where('vehicle_id', $vehicle->id)->delete();

            // Generate 10 track points for today to show movement path history
            $now = Carbon::now();
            for ($i = 0; $i < 10; $i++) {
                // Interpolate coordinates from start to end
                $ratio = $i / 9.0;
                $lat = $vd['start_lat'] + ($vd['end_lat'] - $vd['start_lat']) * $ratio;
                $lng = $vd['start_lng'] + ($vd['end_lng'] - $vd['start_lng']) * $ratio;

                GpsTrack::create([
                    'vehicle_id' => $vehicle->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed' => $i === 9 ? 0.0 : 12.5, // 0 speed at final point
                    'fuel_level' => 85.0 - ($i * 0.5), // simulated fuel consumption
                    'signal_strength' => 90 + ($i % 10),
                    'recorded_at' => $now->copy()->subMinutes((9 - $i) * 10) // from 90 mins ago up to now
                ]);
            }
        }
    }
}
