<?php

declare(strict_types=1);

namespace App\Domain\Lab\Models;

use App\Core\Traits\HasImageUrl;
use App\Core\Traits\HasUuid;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Lab Model.
 *
 * Representasi sebuah laboratorium di SLMS.
 * Setiap lab memiliki branding, konfigurasi, dan data sendiri.
 * Semua entity lain (Booking, Asset, Service) memiliki lab_id
 * yang menunjuk ke record ini.
 *
 * @property int         $id
 * @property string      $uuid
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property string|null $logo
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property string|null $hero_image
 * @property string|null $favicon
 * @property array|null  $contact
 * @property array|null  $settings
 * @property bool        $is_active
 */
class Lab extends Model
{
    use HasFactory;
    use HasImageUrl;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    /**
     * Disk penyimpanan buat gambar branding lab (logo, hero, favicon).
     * Sama seperti PhotoFile — pakai MinIO/S3 dengan presigned URL,
     * bukan disk 'public' bawaan Laravel.
     */
    public const IMAGE_DISK = 's3';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'logo',
        'primary_color',
        'secondary_color',
        'hero_image',
        'favicon',
        'contact',
        'settings',
        'is_active',
        'is_photography_lab'
    ];

    protected function casts(): array
    {
        return [
            'contact'   => 'array',
            'settings'  => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active', 'primary_color', 'secondary_color'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('lab');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_labs')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function rentalRatePerHour(\App\Domain\Booking\Enums\BookingPurpose $purpose): float
    {
        $rental = $this->settings['lab_rental'] ?? [];

        return match ($purpose) {
            \App\Domain\Booking\Enums\BookingPurpose::Academic     => 0,
            \App\Domain\Booking\Enums\BookingPurpose::Organization => (float) ($rental['student_price_per_hour'] ?? 0),
            \App\Domain\Booking\Enums\BookingPurpose::Public       => (float) ($rental['public_price_per_hour'] ?? 0),
        };
    }

    /**
     * Presigned URL logo, valid 60 menit. Dipanggil ulang tiap request
     * (lewat LabResource), jadi selalu fresh — bukan URL statis yang disimpan.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->logo);
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->hero_image);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->favicon);
    }
}