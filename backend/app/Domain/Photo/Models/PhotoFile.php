<?php

declare(strict_types=1);

namespace App\Domain\Photo\Models;

use App\Core\Traits\HasUuid;
use App\Domain\Photo\Enums\PhotoFileType;
use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoFile extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'project_id',
        'type',
        'filename',
        'path',
        'disk',
        'size',
        'mime_type',
        'is_selected',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'type'        => PhotoFileType::class,
            'is_selected' => 'boolean',
            'size'        => 'integer',
            'order'       => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PhotoProject::class, 'project_id');
    }

    /**
     * Generate temporary URL untuk akses file di MinIO dari BROWSER.
     * Sengaja tidak pakai Storage::disk()->temporaryUrl() bawaan Laravel,
     * karena itu menandatangani URL pakai endpoint internal Docker (minio:9000)
     * yang tidak bisa diakses browser. Di sini kita bikin S3 client terpisah
     * yang endpoint-nya sengaja di-set ke host publik (localhost:9000),
     * supaya signature dihitung dengan host yang sama dengan yang dipakai browser.
     */
    public function getTemporaryUrl(int $minutes = 60): string
    {
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
            'Key'    => $this->path,
        ]);

        $request = $client->createPresignedRequest($command, now()->addMinutes($minutes));

        return (string) $request->getUri();
    }
}