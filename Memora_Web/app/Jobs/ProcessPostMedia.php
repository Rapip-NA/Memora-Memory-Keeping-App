<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class ProcessPostMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $absolutePath = storage_path('app/public/' . $this->filePath);
        
        if (!file_exists($absolutePath)) {
            Log::error("ProcessPostMedia Job Error: File not found at {$absolutePath}");
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $img = $manager->decode($absolutePath);
            
            // Re-compress the image in background to ensure it is optimized
            // regardless of whether client-side compression succeeded.
            if ($img->width() > 1920) {
                $img->scaleDown(width: 1920);
                $img->save($absolutePath, quality: 80);
            } else {
                $img->save($absolutePath, quality: 80);
            }
            
            Log::info("ProcessPostMedia Job Success: Processed {$this->filePath}");
        } catch (\Exception $e) {
            Log::error("ProcessPostMedia Job Error: " . $e->getMessage());
        }
    }
}
