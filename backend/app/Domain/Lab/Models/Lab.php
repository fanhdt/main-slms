<?php

declare(strict_types=1);

namespace App\Domain\Lab\Models;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

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
}
