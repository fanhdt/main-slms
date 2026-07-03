<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Core\Services\BaseService;
use App\Domain\Lab\Models\Lab;
use App\Domain\Notification\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService extends BaseService
{
    /**
     * Simpan notifikasi ke DB untuk satu user (personal).
     */
    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        array $data = [],
        ?int $labId = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'lab_id'  => $labId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);
    }

    /**
     * Simpan notifikasi untuk semua staff di sebuah lab
     * (admin/operator/photographer/editor yang terdaftar di user_labs).
     */
    public function notifyLabStaff(
        int $labId,
        string $type,
        string $title,
        ?string $body = null,
        array $data = [],
        ?int $excludeUserId = null
    ): void {
        $staffIds = Lab::findOrFail($labId)
            ->users()
            ->pluck('users.id')
            ->when($excludeUserId, fn ($ids) => $ids->reject(fn ($id) => (int) $id === (int) $excludeUserId));

        foreach ($staffIds as $userId) {
            $this->notifyUser((int) $userId, $type, $title, $body, $data, $labId);
        }
    }

    public function getMyNotifications(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId)->latest();

        if (!empty($filters['unread_only'])) {
            $query->unread();
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function markAsRead(int $userId, string $uuid): Notification
    {
        $notif = Notification::where('user_id', $userId)->where('uuid', $uuid)->firstOrFail();
        $notif->update(['read_at' => now()]);

        return $notif;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->update(['read_at' => now()]);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }
}
