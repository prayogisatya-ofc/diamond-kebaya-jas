<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreFaviconGenerator
{
    public function generate(UploadedFile $logo): string
    {
        $source = $this->createImageResource($logo);

        if (! $source) {
            $extension = $logo->extension() ?: $logo->getClientOriginalExtension() ?: 'png';

            return $logo->storeAs('store-settings', "favicon.{$extension}", 'public');
        }

        $favicon = imagecreatetruecolor(64, 64);
        imagesavealpha($favicon, true);
        imagefill($favicon, 0, 0, imagecolorallocatealpha($favicon, 0, 0, 0, 127));

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(64 / $sourceWidth, 64 / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetX = (int) floor((64 - $targetWidth) / 2);
        $targetY = (int) floor((64 - $targetHeight) / 2);

        imagecopyresampled(
            $favicon,
            $source,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        imagepng($favicon);
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($favicon);

        $path = 'store-settings/favicon.png';
        Storage::disk('public')->put($path, $contents ?: file_get_contents($logo->getRealPath()));

        return $path;
    }

    private function createImageResource(UploadedFile $logo): mixed
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        return match ($logo->extension()) {
            'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($logo->getRealPath()) : false,
            'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($logo->getRealPath()) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($logo->getRealPath()) : false,
            default => false,
        };
    }
}
