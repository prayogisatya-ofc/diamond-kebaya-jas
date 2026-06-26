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

        $source = $this->orientImage($source, $image);
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

    private function orientImage(mixed $source, UploadedFile $image): mixed
    {
        if (! $source instanceof \GdImage || ! $this->isJpeg($image) || ! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($image->getRealPath(), 'IFD0');
        $orientation = is_array($exif) ? ($exif['Orientation'] ?? null) : null;

        if (! is_numeric($orientation)) {
            return $source;
        }

        $orientation = (int) $orientation;

        if (in_array($orientation, [2, 4, 5, 7], true) && function_exists('imageflip')) {
            imageflip($source, IMG_FLIP_HORIZONTAL);
        }

        $rotated = match ($orientation) {
            3, 4 => imagerotate($source, 180, 0),
            5, 6 => imagerotate($source, 270, 0),
            7, 8 => imagerotate($source, 90, 0),
            default => $source,
        };

        if ($rotated instanceof \GdImage && $rotated !== $source) {
            imagedestroy($source);

            return $rotated;
        }

        return $source;
    }

    private function isJpeg(UploadedFile $image): bool
    {
        return in_array(strtolower($image->extension() ?: $image->getClientOriginalExtension()), ['jpg', 'jpeg'], true);
    }
}
