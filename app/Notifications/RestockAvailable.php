<?php

namespace App\Notifications;

use App\Models\RestockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly RestockAlert $alert)
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
        $variant = $this->alert->variant()->with('product')->firstOrFail();

        return (new MailMessage)
            ->subject('Ya volvió: '.$variant->product->name)
            ->greeting('¡Buenas noticias!')
            ->line("La talla {$variant->size} de {$variant->product->name} volvió a estar disponible.")
            ->action('Ver producto', route('products.show', ['product' => $variant->product->slug]))
            ->line('La disponibilidad puede cambiar mientras se agota el inventario.');
    }
}
