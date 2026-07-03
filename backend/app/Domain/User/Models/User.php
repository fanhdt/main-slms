<?php

declare(strict_types=1);

namespace App\Domain\User\Models;
use App\Domain\Lab\Models\Lab;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model.
 *
 * Model utama untuk semua user di SLMS.
 * Menggunakan Sanctum untuk API auth, Spatie Permission
 * untuk role/permission, dan HasUuid untuk identifier publik.
 *
 * @property int         $id
 * @property string      $uuid
 * @property string      $name
 * @property string      $email
 * @property string|null $phone
 * @property string|null $avatar
 * @property bool        $is_active
 * @property string|null $email_verified_at
 * @property string      $password
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
   

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Konfigurasi activity log.
     * Field sensitif seperti password tidak di-log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    /**
     * Scope untuk user aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function labs(): BelongsToMany
{
    return $this->belongsToMany(Lab::class, 'user_labs')
                ->withPivot('role')
                ->withTimestamps();
}

public function hasLabAccess(int $labId): bool
{
    if ($this->hasRole('super_admin')) {
        return true;
    }

    return $this->labs()->where('lab_id', $labId)->exists();
}
}
