<?php

namespace Tests\Unit;

use App\Support\UploadedImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedImageCompressorTest extends TestCase
{
    public function test_it_applies_jpeg_exif_orientation_before_storing(): void
    {
        Storage::fake('public');

        $uploadedFile = $this->jpegUploadWithOrientation(6, 40, 20);

        $path = app(UploadedImageCompressor::class)->store($uploadedFile, 'products');
        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        $this->assertSame(20, $width);
        $this->assertSame(40, $height);
    }

    private function jpegUploadWithOrientation(int $orientation, int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        imagedestroy($image);

        $path = tempnam(sys_get_temp_dir(), 'oriented-jpeg-');

        file_put_contents($path, $this->injectExifOrientation($jpeg, $orientation));

        return new UploadedFile($path, 'camera.jpg', 'image/jpeg', null, true);
    }

    private function injectExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = "II*\x00\x08\x00\x00\x00"
            ."\x01\x00"
            ."\x12\x01"
            ."\x03\x00"
            ."\x01\x00\x00\x00"
            .chr($orientation)."\x00\x00\x00"
            ."\x00\x00\x00\x00";
        $exif = "Exif\x00\x00".$tiff;
        $segment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
    }
}
