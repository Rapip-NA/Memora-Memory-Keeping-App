<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FixR2MediaPaths extends Command
{
    protected $signature = 'media:fix-r2-paths';
    protected $description = 'Download R2 media to local storage and fix DB paths from full URLs to relative paths';

    public function handle(): int
    {
        $posts = Post::whereNotNull('photo')->get();
        $fixed = 0;

        foreach ($posts as $post) {
            $photos = json_decode($post->photo, true);
            if (!is_array($photos)) continue;

            $needsUpdate = false;
            $newPaths = [];

            foreach ($photos as $path) {
                if (str_starts_with($path, 'http')) {
                    // This is a full R2 URL — extract the relative path and download
                    $parsed = parse_url($path);
                    $relativePath = ltrim($parsed['path'] ?? '', '/');

                    if (empty($relativePath)) {
                        $newPaths[] = $path;
                        continue;
                    }

                    $localDest = storage_path('app/public/' . $relativePath);
                    $localDir = dirname($localDest);

                    if (!file_exists($localDir)) {
                        mkdir($localDir, 0755, true);
                    }

                    // Try to download from R2 via S3 SDK (bypasses r2.dev SSL issue)
                    if (!file_exists($localDest)) {
                        try {
                            $contents = Storage::disk('s3')->get($relativePath);
                            file_put_contents($localDest, $contents);
                            $this->info("  ✅ Downloaded: {$relativePath}");
                        } catch (\Exception $e) {
                            $this->warn("  ❌ Failed to download {$relativePath}: " . $e->getMessage());
                            $newPaths[] = $path; // keep original URL as fallback
                            continue;
                        }
                    } else {
                        $this->info("  ⏭️  Already exists locally: {$relativePath}");
                    }

                    $newPaths[] = $relativePath;
                    $needsUpdate = true;
                } else {
                    // Already a relative path, keep it
                    $newPaths[] = $path;
                }
            }

            if ($needsUpdate) {
                $post->update(['photo' => json_encode($newPaths)]);
                $fixed++;
                $this->info("🔧 Post #{$post->id} paths fixed.");
            }
        }

        $this->info("\n✅ Done! Fixed {$fixed} post(s).");
        return Command::SUCCESS;
    }
}
