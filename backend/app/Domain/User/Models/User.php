<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Lab\Models\Lab;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\HasImageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use App\Domain\Auth\Notifications\ResetPasswordNotification;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
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
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasImageUrl;
    use HasUuid;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'nim',
        'rfid_uid',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->avatar);
    }

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

    public function bookings(): HasMany
{
    return $this->hasMany(\App\Domain\Booking\Models\Booking::class);
}

    public function hasLabAccess(int $labId): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->labs()->where('lab_id', $labId)->exists();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // NEW — override notifikasi verifikasi email, biar link-nya ke frontend Vue
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}