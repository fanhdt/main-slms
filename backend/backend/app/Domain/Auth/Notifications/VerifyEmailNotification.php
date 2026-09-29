<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends BaseVerifyEmail
{
    /**
     * Override supaya signed URL mengarah ke backend API (bukan Blade view),
     * tapi begitu diklik nanti backend redirect ke frontend.
     * Kita generate signed URL manual di sini, lalu bungkus jadi parameter untuk frontend.
     */
    protected function verificationUrl($notifiable)
    {
        $backendUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return $backendUrl;
    }

    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->subject('Verifikasi Email - SLMS')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Terima kasih sudah mendaftar di SLMS. Klik tombol di bawah untuk verifikasi email kamu.')
            ->action('Verifikasi Email', $url)
            ->line('Link ini berlaku selama 60 menit.')
            ->line('Kalau kamu tidak merasa mendaftar, abaikan email ini saja.');
    }
}