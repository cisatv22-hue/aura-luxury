<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
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
        $this->order->loadMissing(['items', 'customer']);

        return (new MailMessage)
            ->subject("Nuevo pedido {$this->order->number} · AURA")
            ->greeting('Se recibió un pedido nuevo.')
            ->line("Folio: {$this->order->number}")
            ->line("Cliente: {$this->order->customer->name}")
            ->line('Total: $'.number_format($this->order->total_cents / 100, 2).' MXN')
            ->action('Ver pedidos', url('/admin/orders'));
    }
}
