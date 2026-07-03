<?php

declare(strict_types=1);

namespace App\Domain\Notification\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getMyNotifications(
            $request->user()->id,
            $request->all()
        );

        return $this->success(
            $notifications->through(fn ($n) => [
                'uuid'       => $n->uuid,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
            ])->response()->getData(true)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => $this->notificationService->unreadCount($request->user()->id),
        ]);
    }

    public function markAsRead(Request $request, string $uuid): JsonResponse
    {
        $this->notificationService->markAsRead($request->user()->id, $uuid);

        return $this->successMessage('Notifikasi ditandai dibaca.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return $this->successMessage('Semua notifikasi ditandai dibaca.');
    }
}
