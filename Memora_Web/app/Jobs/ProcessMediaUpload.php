<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public array $tempFiles;

    /**
     * Number of times the job may be attempted.
     */
    public int $timeout = 3600;
    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(int $postId, array $tempFiles)
    {
        $this->postId = $postId;
        $this->tempFiles = $tempFiles;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = Post::find($this->postId);
        if (!$post) {
            Log::error("ProcessMediaUpload: Post #{$this->postId} not found, aborting.");
            return;
        }

        // Foto-foto sudah disimpan secara lokal oleh Controller untuk tampilan instant.
        // Job ini sekarang fokus melakukan kompresi background dan backup ke cloud.
        $photos = json_decode($post->photo, true);
        
        // Fallback jika photo bukan JSON (single photo string)
        if (!is_array($photos)) {
            if ($post->photo && $post->photo !== '[]') {
                $photos = [$post->photo];
            } else {
                Log::info("ProcessMediaUpload: No photos to process for Post #{$this->postId}");
                return;
            }
        }

        $cloudDisk = 's3';
        $cloudConfigured = !empty(config('filesystems.disks.s3.key'))
                        && !empty(config('filesystems.disks.s3.bucket'))
                        && !empty(config('filesystems.disks.s3.endpoint'));

        Log::info("ProcessMediaUpload: Start handling post #{$this->postId}", [
            'cloud_configured' => $cloudConfigured,
            'photo_count' => count($photos)
        ]);

        foreach ($photos as $relativePath) {
            $localPath = storage_path('app/public/' . $relativePath);

            if (!file_exists($localPath)) {
                Log::warning("ProcessMediaUpload: File not found — {$localPath}");
                continue;
            }

            $mimeType = mime_content_type($localPath);
            $isVideo = str_starts_with($mimeType, 'video/');

            // ── Step 1: Kompresi gambar di background (in-place) ─────────────
            if (!$isVideo) {
                try {
                    $manager = new ImageManager(new Driver());
                    $img = $manager->decode($localPath);
                    
                    // Resize jika terlalu lebar
                    if ($img->width() > 1920) {
                        $img->scaleDown(width: 1920);
                    }
                    
                    // Simpan kembali dengan kualitas 80% untuk optimasi size
                    $img->save($localPath, quality: 80);
                    Log::info("ProcessMediaUpload: Image optimized in-place → {$relativePath}");
                } catch (\Throwable $e) {
                    Log::error("ProcessMediaUpload: Image compress error — " . $e->getMessage());
                }
            }

            // ── Step 2: Upload ke Cloud (R2) ──────────────────────────────────
            if ($cloudConfigured) {
                try {
                    // Gunakan streaming untuk upload file besar agar hemat memory
                    $stream = fopen($localPath, 'r');
                    Storage::disk($cloudDisk)->put($relativePath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    Log::info("ProcessMediaUpload: Backed up to Cloud (R2) → {$relativePath}");
                } catch (\Throwable $e) {
                    Log::warning("ProcessMediaUpload: Cloud backup failed (non-fatal) — " . $e->getMessage());
                }
            }
        }

        Log::info("ProcessMediaUpload: Post #{$this->postId} background processing complete.");
    }
}
