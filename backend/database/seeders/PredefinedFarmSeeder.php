<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PredefinedFarm;
use Illuminate\Support\Facades\File;

class PredefinedFarmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/predefined_farms.json');
        if (!File::exists($path)) {
            $this->command->error("JSON file not found at: {$path}");
            return;
        }

        $json = File::get($path);
        $farms = json_decode($json, true);

        if (!is_array($farms)) {
            $this->command->error("Invalid JSON format");
            return;
        }

        $this->command->info("Seeding " . count($farms) . " predefined farms...");

        foreach ($farms as $farm) {
            PredefinedFarm::updateOrCreate(
                ['name' => $farm['name']],
                [
                    'stir' => $farm['stir'] ?? null,
                    'crop_type' => $farm['crop_type'] ?? null,
                    'size' => $farm['size'] ?? null,
                ]
            );
        }

        $this->command->info("Predefined farms seeded successfully!");
    }
}
