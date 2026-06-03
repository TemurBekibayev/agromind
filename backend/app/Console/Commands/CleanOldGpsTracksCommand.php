<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GpsTrack;
use Illuminate\Support\Facades\Log;

class CleanOldGpsTracksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean GPS tracks older than 7 days from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Cleaning GPS tracks older than 7 days starting...");
        
        try {
            $cutoffDate = now()->subDays(7);
            
            // Delete old tracks
            $deletedCount = GpsTrack::where('recorded_at', '<', $cutoffDate)->delete();
            
            $this->info("Successfully deleted {$deletedCount} old GPS tracks.");
            Log::info("CleanOldGpsTracksCommand: Deleted {$deletedCount} GPS tracks older than 7 days (cutoff date: {$cutoffDate->toDateTimeString()}).");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Failed to clean old GPS tracks: " . $e->getMessage());
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
