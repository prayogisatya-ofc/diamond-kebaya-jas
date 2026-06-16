<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadedImageCompressor
{
    public function store(UploadedFile $image, string $directory, int $maxWidth = 1600, int $quality = 78): string
    {
        $source = $this->createImageResource($image);

        if (! $source) {
            return $image->store($directory, 'public');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $maxWidth / max(1, $sourceWidth));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagejpeg($target, null, $quality);
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        if (! $contents) {
            return $image->store($directory, 'public');
        }

        $path = trim($directory, '/').'/'.Str::ulid().'.jpg';

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    private function createImageResource(UploadedFile $image): mixed
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        return match (strtolower($image->extension() ?: $image->getClientOriginalExtension())) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($image->getRealPath()) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($image->getRealPath()) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($image->getRealPath()) : false,
            default => false,
        };
    }
}
