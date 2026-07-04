<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Opticedge Credit verification code')
            ->greeting('Verify your admin sign in')
            ->line('Use this one-time code to continue signing in to Opticedge Credit:')
            ->line($this->code)
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request this code, change your password and contact support immediately.');
    }
}
