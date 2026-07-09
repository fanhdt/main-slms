<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        $url = $frontendUrl . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage())
            ->subject('Reset Password - SLMS')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Kami menerima permintaan untuk reset password akun kamu.')
            ->action('Reset Password', $url)
            ->line('Link ini berlaku selama 60 menit.')
            ->line('Kalau kamu tidak merasa meminta ini, abaikan email ini saja.');
    }
}