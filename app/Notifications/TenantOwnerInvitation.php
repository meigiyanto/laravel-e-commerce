<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantOwnerInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /*
        $url = route('tenant.invitations.accept', [
            'token' => $this->token,
        ]);
        */
        
        $url = route('tenant.invitations.show', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Undangan menjadi owner toko '.$this->tenantName)
            ->greeting('Halo!')
            ->line('Anda diundang menjadi owner toko '.$this->tenantName.'.')
            ->action('Terima undangan', $url)
            ->line('Undangan berlaku selama 3 hari.')
            ->line('Jika Anda tidak mengenali undangan ini, abaikan email ini.');
    }
}
