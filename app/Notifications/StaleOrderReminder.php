<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaleOrderReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
        $this->afterCommit = true;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('customer');

        return (new MailMessage)
            ->subject("Pedido pendiente {$this->order->number} · AURA")
            ->greeting('Hay un pedido que sigue nuevo desde hace más de 48 horas.')
            ->line("Folio: {$this->order->number}")
            ->line("Cliente: {$this->order->customer?->name}")
            ->line('Total: $'.number_format($this->order->total_cents / 100, 2).' MXN')
            ->action('Revisar pedidos', url('/admin/orders'));
    }
}
