<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmNewsletterSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $confirmationUrl, private readonly string $unsubscribeUrl)
    {
        $this->afterCommit = true;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu suscripción a AURA')
            ->greeting('Gracias por acercarte a AURA.')
            ->line('Confirma tu correo para recibir novedades y lanzamientos.')
            ->action('Confirmar suscripción', $this->confirmationUrl)
            ->line('Si no lo solicitaste, puedes ignorar este mensaje.')
            ->line('Cancelar suscripción: '.$this->unsubscribeUrl);
    }
}
