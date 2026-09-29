<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Aws\S3\S3Client;

trait HasImageUrl
{
    /**
     * Generate presigned URL dari path gambar yang tersimpan di kolom tertentu.
     * Sengaja tidak pakai Storage::disk()->temporaryUrl() bawaan Laravel,
     * karena itu menandatangani URL pakai endpoint internal Docker (minio:9000)
     * yang tidak bisa diakses browser. Di sini kita pakai S3 client terpisah
     * yang endpoint-nya di-set ke host publik (config 'filesystems.disks.s3.public_endpoint'),
     * supaya signature dihitung dengan host yang sama dengan yang dipakai browser.
     * (Pola ini sudah terverifikasi jalan di PhotoFile::getTemporaryUrl().)
     */
    public function getImageUrlFrom(?string $path, int $minutes = 60): ?string
    {
        if (!$path) {
            return null;
        }

        $config = config('filesystems.disks.s3');

        $client = new S3Client([
            'version'                 => 'latest',
            'region'                  => $config['region'],
            'endpoint'                => $config['public_endpoint'],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'],
            'credentials'             => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);

        $command = $client->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key'    => $path,
        ]);

        $request = $client->createPresignedRequest($command, now()->addMinutes($minutes));

        return (string) $request->getUri();
    }
}