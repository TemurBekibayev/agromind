<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Region;
use App\Models\Farm;
use App\Models\Vehicle;
use App\Models\Geofence;
use App\Models\SoilAnalysis;
use App\Models\Recommendation;
use App\Models\Alert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Regions
        $tashkent = Region::create([
            'name' => 'Toshkent viloyati',
            'geojson' => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [69.0, 41.0], [70.0, 41.0], [70.0, 42.0], [69.0, 42.0], [69.0, 41.0]
                        ]
                    ]
                ]
            ]
        ]);

        $bukhara = Region::create([
            'name' => 'Buxoro viloyati',
            'geojson' => null
        ]);

        $fergana = Region::create([
            'name' => 'Farg\'ona viloyati',
            'geojson' => null
        ]);

        // 2. Create Users
        $admin = User::create([
            'name' => 'Admin AgroMind',
            'phone' => '998901234567',
            'role' => 'admin',
            'password' => Hash::make('secret123'),
        ]);

        $farmer = User::create([
            'name' => 'Sherzod Dehqon',
            'phone' => '998901111111',
            'role' => 'farmer',
            'region_id' => $tashkent->id,
            'password' => Hash::make('secret123'),
        ]);

        $monitor = User::create([
            'name' => 'Nazoratchi Akmal',
            'phone' => '998902222222',
            'role' => 'monitor',
            'region_id' => $tashkent->id,
            'password' => Hash::make('secret123'),
        ]);

        // 3. Create Farms
        $farm1 = Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Paxtakor Oltin Dala',
            'location' => 'Qibray tumani',
            'latitude' => 41.380000,
            'longitude' => 69.450000,
            'size' => 15.5,
            'soil_type' => 'Bo\'z tuproq',
            'region_id' => $tashkent->id,
        ]);

        $farm2 = Farm::create([
            'user_id' => $farmer->id,
            'name' => 'Chilonzor Bog\'lari',
            'location' => 'Zangiota tumani',
            'latitude' => 41.250000,
            'longitude' => 69.150000,
            'size' => 8.2,
            'soil_type' => 'Loy tuproq',
            'region_id' => $tashkent->id,
        ]);

        // 4. Create Geofences
        $geofence1 = Geofence::create([
            'farm_id' => $farm1->id,
            'name' => 'Oltin Dala Chegarasi',
            'coordinates' => [
                [41.375000, 69.445000],
                [41.385000, 69.445000],
                [41.385000, 69.455000],
                [41.375000, 69.455000],
                [41.375000, 69.445000]
            ]
        ]);

        // 5. Create Vehicles
        $vehicle1 = Vehicle::create([
            'farm_id' => $farm1->id,
            'name' => 'Tractor TTZ-80',
            'type' => 'tractor',
            'plate_number' => '01 A 123 AA',
            'gps_device_id' => 'gps_tractor_01',
            'fuel_capacity' => 120.00,
        ]);

        $vehicle2 = Vehicle::create([
            'farm_id' => $farm1->id,
            'name' => 'John Deere 9670',
            'type' => 'combine',
            'plate_number' => '01 B 456 BB',
            'gps_device_id' => 'gps_combine_01',
            'fuel_capacity' => 250.00,
        ]);

        // 6. Create Soil Analyses
        $soil = SoilAnalysis::create([
            'farm_id' => $farm1->id,
            'geofence_id' => $geofence1->id,
            'target_crop' => 'G\'o\'za (Paxta)',
            'ph' => 6.80,
            'fertility' => 72.50,
            'moisture' => 45.00,
            'temperature' => 24.50,
            'sunlight' => 45000.00,
            'humidity' => 50.00,
            'analysis_date' => now()->subDays(2)->toDateString(),
            'status' => 'completed',
        ]);

        // 7. Create AI Recommendation
        Recommendation::create([
            'soil_analysis_id' => $soil->id,
            'content' => "G'o'za yetishtirish uchun tuproq pH ko'rsatkichi (6.80) juda yaxshi. Biroq, namlik darajasi biroz past (45%). Azotli o'g'itlar va fosfor o'g'itlarini me'yorida solish tavsiya etiladi. Sug'orish chastotasini haftasiga 2 martaga oshiring.",
            'recommended_crops' => ['Paxta', 'Bug\'doy', 'Bedaviy ekinlar'],
            'fertilizer_plan' => ['Azotli o\'g\'itlar: 50kg/ga', 'Fosforli o\'g\'itlar: 30kg/ga'],
            'ai_model' => 'llama3-8b-8192',
            'tokens_used' => 245,
        ]);

        // 8. Create Alerts
        Alert::create([
            'farm_id' => $farm1->id,
            'vehicle_id' => $vehicle1->id,
            'type' => 'geofence_breach',
            'message' => 'Tractor TTZ-80 fermadan tashqariga chiqdi (Geofence buzilishi)',
            'status' => 'active',
        ]);

        $this->call(PredefinedFarmSeeder::class);

        // 9. Create Chat Messages
        \App\Models\ChatMessage::create([
            'user_id' => $farmer->id,
            'message' => 'Salom hammaga! Pomidor maydonimda tuproq sho‘rlanishi kuzatilyapti. Kimda qanday tajriba bor?',
        ]);

        \App\Models\ChatMessage::create([
            'user_id' => $monitor->id,
            'message' => 'Assalomu alaykum Sherzod aka! Tuproq AI yordamida tahlil o‘tkazing. O‘g‘itlash rejasini tuzib beradi.',
        ]);

        \App\Models\ChatMessage::create([
            'user_id' => $farmer->id,
            'message' => 'Rahmat, hozir tahlil natijasini oldim. Juda foydali maslahatlar berdi. Tavsiya qilaman!',
        ]);

        // 10. Create Listings for Agricultural Equipment Sharing
        \App\Models\Listing::create([
            'user_id' => $farmer->id,
            'title' => 'Chizel kultivatori vaqtincha foydalanishga beriladi',
            'description' => 'Yaxshi holatdagi chizel kultivatori bor, hozircha ishlatmayapman. Vaqtincha ijaraga yoki foydalanishga beraman.',
            'equipment_type' => 'Kultivator',
            'price' => '100 000 so‘m/kun',
            'contact_phone' => '998901111111',
            'status' => 'active',
        ]);

        \App\Models\Listing::create([
            'user_id' => $monitor->id,
            'title' => 'John Deere 4-korpusli plug',
            'description' => 'John Deere traktorlariga mos keladigan plug. Bo‘sh turibdi, kelishilgan narxda ijaraga beriladi.',
            'equipment_type' => 'Plug',
            'price' => 'Kelishuv asosida',
            'contact_phone' => '998902222222',
            'status' => 'active',
        ]);
    }
}
