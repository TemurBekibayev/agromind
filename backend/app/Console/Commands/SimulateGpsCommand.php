<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Services\GpsService;
use Illuminate\Support\Facades\Log;

class SimulateGpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:simulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate GPS coordinates movement for all vehicles every 15 seconds';

    protected $gpsService;

    public function __construct(GpsService $gpsService)
    {
        parent::__construct();
        $this->gpsService = $gpsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("GPS simulation starting...");
        
        $seconds = 0;
        
        // Laravel Scheduler har daqiqada ishga tushiradi. Biz sub-minute (15s) simulyatsiya 
        // qilish uchun 1 daqiqa davomida loopda ishlatamiz va har 15 soniyada sleep qilamiz.
        while ($seconds < 60) {
            $startTime = microtime(true);
            
            $this->runSimulationIteration();
            
            $elapsed = microtime(true) - $startTime;
            $sleepTime = max(1, 15 - (int)$elapsed);
            
            $this->info("Iteration complete. Sleeping for {$sleepTime} seconds...");
            sleep($sleepTime);
            $seconds += 15;
        }

        $this->info("GPS simulation cycle finished.");
        return Command::SUCCESS;
    }

    /**
     * Barcha transport vositalari uchun bir martalik simulyatsiyani bajarish.
     */
    protected function runSimulationIteration()
    {
        $vehicles = Vehicle::with('farm.owner', 'farm.geofences')->get();

        if ($vehicles->isEmpty()) {
            $this->warn("No vehicles found to simulate.");
            return;
        }

        foreach ($vehicles as $vehicle) {
            try {
                // Agar fermaning koordinatalari kiritilmagan bo'lsa o'tkazib yuboramiz
                if (!$vehicle->farm || is_null($vehicle->farm->latitude) || is_null($vehicle->farm->longitude)) {
                    continue;
                }

                // 1. Soxta koordinatalarni hisoblash
                $fakeTelemetry = $this->gpsService->getFakeLocation($vehicle);

                // 2. Telemetriyani qayta ishlash (geofence tekshiruvi bilan birga)
                $track = $this->gpsService->processIncoming($fakeTelemetry);

                $this->line("Simulated: {$vehicle->name} (Plate: {$vehicle->plate_number}) at [{$track->latitude}, {$track->longitude}], Fuel: {$track->fuel_level}%, Speed: {$track->speed} km/h");
            } catch (\Exception $e) {
                Log::error("Failed to simulate GPS for Vehicle ID {$vehicle->id}: " . $e->getMessage());
                $this->error("Error simulating vehicle {$vehicle->id}: " . $e->getMessage());
            }
        }
    }
}
