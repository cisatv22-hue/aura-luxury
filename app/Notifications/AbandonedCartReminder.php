<?php

namespace App\Notifications;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly AbandonedCart $cart)
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
        $message = (new MailMessage)
            ->subject('Tu bolsa sigue aquí · AURA')
            ->greeting('Guardamos tu selección.')
            ->line('Estas piezas siguen en tu bolsa. La existencia y el precio se confirman al completar tu pedido.');

        foreach ($this->cart->items as $item) {
            $message->line($item['name'].' · '.$item['quantity'].' pieza(s)');
        }

        return $message
            ->action('Volver a mi bolsa', url('/pedido'))
            ->line('Recibiste este mensaje porque solicitaste un recordatorio para tu bolsa.')
            ->line('Para dejar de recibir estos avisos, usa este enlace: '.URL::temporarySignedRoute('abandoned-carts.unsubscribe', now()->addDays(7), ['cart' => $this->cart->id]));
    }
}
