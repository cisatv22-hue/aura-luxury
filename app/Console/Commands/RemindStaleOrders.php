<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Notifications\StaleOrderReminder;
use App\Settings\StorefrontSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RemindStaleOrders extends Command
{
    protected $signature = 'aura:remind-stale-orders';

    protected $description = 'Notify the store team about new orders still untouched after 48 hours';

    public function handle(): int
    {
        $recipient = app(StorefrontSettings::class)->orders_notification_email;

        if (! is_string($recipient) || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->warn('No hay un correo de pedidos válido configurado; no se enviaron recordatorios.');

            return self::SUCCESS;
        }

        $reminded = 0;

        Order::query()
            ->where('status', Order::STATUS_NEW)
            ->where('created_at', '<=', now()->subHours(48))
            ->whereNull('stale_reminder_sent_at')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($recipient, &$reminded): void {
                foreach ($orders as $order) {
                    Notification::route('mail', $recipient)->notify(new StaleOrderReminder($order));
                    $order->forceFill(['stale_reminder_sent_at' => now()])->save();
                    $reminded++;
                }
            });

        $this->info("Recordatorios enviados: {$reminded}");

        return self::SUCCESS;
    }
}
