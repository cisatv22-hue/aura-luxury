<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'aura:send-cart-reminders';

    protected $description = 'Envía recordatorios consentidos de bolsas abandonadas después de 24 horas';

    public function handle(): int
    {
        $sent = 0;
        AbandonedCart::query()->whereNull('reminder_sent_at')->whereNull('recovered_at')->whereNull('unsubscribed_at')
            ->where('consented_at', '<=', now()->subDay())->orderBy('id')->chunkById(100, function ($carts) use (&$sent): void {
                foreach ($carts as $cart) {
                    Notification::route('mail', $cart->email)->notify(new AbandonedCartReminder($cart));
                    $cart->update(['reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Recordatorios encolados: {$sent}");

        return self::SUCCESS;
    }
}
