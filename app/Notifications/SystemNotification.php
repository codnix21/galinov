<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление: колокольчик в приложении и письмо на email пользователя.
 */
class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $icon = 'info',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $email = $notifiable->email_polzovatela ?? $notifiable->email ?? null;
        if (is_string($email) && $email !== '' && config('mail.default') !== 'log') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = method_exists($notifiable, 'getNameAttribute')
            ? trim((string) $notifiable->name)
            : '';

        return (new MailMessage)
            ->subject($this->title)
            ->markdown('emails.system-notification', [
                'title' => $this->title,
                'message' => $this->message,
                'actionUrl' => $this->absoluteUrl(),
                'userName' => $name,
            ]);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }

    private function absoluteUrl(): string
    {
        if ($this->url === '') {
            return config('app.url');
        }

        if (str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://')) {
            return $this->url;
        }

        return url($this->url);
    }
}
