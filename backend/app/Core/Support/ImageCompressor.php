<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kompres & resize gambar upload sebelum disimpan ke storage (pakai GD).
 *
 * - Sisi terpanjang dibatasi $maxDimension (rasio dijaga, tidak di-upscale).
 * - Orientasi EXIF diperbaiki (foto HP tidak jadi miring).
 * - Output WebP (transparansi PNG tetap aman). Jika GD tidak mendukung WebP -> JPEG/PNG.
 * - Jika hasil kompres justru lebih besar dari file asli, file asli yang disimpan.
 * - Jika apa pun gagal, fallback ke file asli (upload user tidak pernah gagal karena kompresi).
 *
 * JANGAN dipakai untuk PhotoDeliveryService (hasil foto customer harus resolusi asli).
 */
class ImageCompressor
{
    public const PRESET_AVATAR   = ['max' => 512,  'quality' => 80];
    public const PRESET_LOGO     = ['max' => 800,  'quality' => 85];
    public const PRESET_STANDARD = ['max' => 1600, 'quality' => 80];
    public const PRESET_GALLERY  = ['max' => 2000, 'quality' => 82];

    /**
     * Simpan gambar (terkompres) ke $disk di dalam folder $directory.
     *
     * @param  array{max:int,quality:int}  $preset
     * @return string path relatif di disk (simpan ini ke database)
     */
    public static function store(
        UploadedFile $file,
        string $disk,
        string $directory,
        array $preset = self::PRESET_STANDARD,
    ): string {
        $directory = trim($directory, '/');
        $name = (string) Str::uuid();

        try {
            [$binary, $ext] = self::compress($file, $preset['max'], $preset['quality']);
        } catch (Throwable $e) {
            report($e);
            $binary = null;
            $ext = null;
        }

        if ($binary === null) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $binary = file_get_contents($file->getRealPath());
        }

        $path = "{$directory}/{$name}.{$ext}";
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    /** @return array{0:?string,1:?string} [binary, extension] atau [null, null] jika tidak perlu/bisa dikompres */
    private static function compress(UploadedFile $file, int $maxDimension, int $quality): array
    {
        if (! extension_loaded('gd')) {
            return [null, null];
        }

        $realPath = $file->getRealPath();
        $info = @getimagesize($realPath);
        if ($info === false) {
            return [null, null];
        }

        [$width, $height, $type] = $info;
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($realPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : false,
            default        => false,
        };
        if (! $source) {
            return [null, null];
        }

        // Perbaiki orientasi EXIF (JPEG dari kamera/HP)
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($realPath);
            $rotate = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($rotate !== 0) {
                $rotated = imagerotate($source, $rotate, 0);
                if ($rotated) {
                    $source = $rotated;
                    $width = imagesx($source);
                    $height = imagesy($source);
                }
            }
        }

        // Resize (hanya mengecilkan)
        $scale = min(1, $maxDimension / max($width, $height));
        if ($scale < 1) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
            $source = $resized;
        }

        $hasAlpha = $type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP;
        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($source, null, $quality);
            $ext = 'webp';
        } elseif ($hasAlpha) {
            imagepng($source, null, 8);
            $ext = 'png';
        } else {
            imagejpeg($source, null, $quality);
            $ext = 'jpg';
        }
        $binary = (string) ob_get_clean();

        // Jangan simpan hasil yang malah lebih besar dari aslinya
        if ($binary === '' || ($scale >= 1 && strlen($binary) >= $file->getSize())) {
            return [null, null];
        }

        return [$binary, $ext];
    }
}