<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\CancelOrder;
use App\Actions\ConfirmOrder;
use App\Actions\RecordMockRefund;
use App\Actions\SimulateMockPayment;
use App\Actions\UpdateOrderStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label('Celular')
                    ->searchable(),
                TextColumn::make('customer.city')
                    ->label('Ciudad')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('channel')
                    ->searchable(),
                TextColumn::make('delivery_method')
                    ->searchable(),
                TextColumn::make('delivery_zone')
                    ->searchable(),
                TextColumn::make('delivery_postal_code')
                    ->label('Código postal')
                    ->searchable(),
                TextColumn::make('total_cents')
                    ->label('Total estimado')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2).' MXN')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('shipping_free')
                    ->boolean(),
                IconColumn::make('shipping_needs_confirmation')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('packingSlip')
                    ->label('Hoja de empaque')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Order $record): string => route('admin.orders.packing-slip', ['order' => $record->number]))
                    ->openUrlInNewTab(),
                Action::make('confirm')
                    ->label('Confirmar pedido')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === Order::STATUS_NEW)
                    ->action(fn (Order $record) => app(ConfirmOrder::class)->handle($record, auth()->user())),
                Action::make('cancel')
                    ->label('Cancelar pedido')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->canBeCancelled())
                    ->action(fn (Order $record) => app(CancelOrder::class)->handle($record, auth()->user())),
                Action::make('markPaid')
                    ->label('Marcar pagado')
                    ->visible(fn (Order $record): bool => $record->status === Order::STATUS_CONFIRMED && $record->channel === 'whatsapp')
                    ->schema([Textarea::make('note')->label('Referencia del pago')->maxLength(1000)])
                    ->action(fn (Order $record, array $data) => app(UpdateOrderStatus::class)->handle($record, Order::STATUS_PAID, auth()->user(), $data['note'] ?? null)),
                Action::make('recordMockRefund')
                    ->label('Registrar reembolso mock')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(function (Order $record): bool {
                        if (! app()->environment(['local', 'testing']) || config('integrations.payments.driver') !== 'mock') {
                            return false;
                        }

                        $payment = $record->payments()
                            ->where('provider', 'mock')
                            ->where('status', 'paid')
                            ->latest('id')
                            ->first();

                        return $payment !== null && $payment->amount_cents > $payment->refunds()
                            ->whereIn('status', ['mocked', 'pending', 'approved'])
                            ->sum('amount_cents');
                    })
                    ->schema([
                        TextInput::make('amount')
                            ->label('Importe en pesos')
                            ->prefix('$')
                            ->numeric()
                            ->rules(['regex:/^\d+(?:\.\d{1,2})?$/'])
                            ->step('0.01')
                            ->minValue(0.01)
                            ->required(),
                        Textarea::make('note')
                            ->label('Motivo o referencia interna')
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Solo registra el reembolso de prueba en la aplicación; no devuelve dinero.')
                    ->action(function (Order $record, array $data): void {
                        [$pesos, $centavos] = array_pad(explode('.', (string) $data['amount'], 2), 2, '');
                        $amountCents = ((int) $pesos * 100) + (int) str_pad($centavos, 2, '0');

                        app(RecordMockRefund::class)->handle($record, $amountCents, $data['note'] ?? null);
                    }),
                Action::make('simulateMockPayment')
                    ->label('Simular pago mock')
                    ->icon('heroicon-o-beaker')
                    ->visible(fn (Order $record): bool => app()->environment(['local', 'testing'])
                        && config('integrations.payments.driver') === 'mock'
                        && $record->status === Order::STATUS_CONFIRMED
                        && $record->channel === 'online'
                        && $record->payments()->where('provider', 'mock')->where('status', 'pending')->exists())
                    ->requiresConfirmation()
                    ->modalDescription('Solo marca como pagado este pedido local de prueba. No se realiza ningún cobro.')
                    ->action(fn (Order $record) => app(SimulateMockPayment::class)->handle($record, auth()->user())),
                Action::make('markShipped')
                    ->label('Marcar enviado')
                    ->visible(fn (Order $record): bool => in_array($record->status, [Order::STATUS_CONFIRMED, Order::STATUS_PAID], true))
                    ->schema([Textarea::make('note')->label('Paquetería y rastreo')->maxLength(1000)])
                    ->action(fn (Order $record, array $data) => app(UpdateOrderStatus::class)->handle($record, Order::STATUS_SHIPPED, auth()->user(), $data['note'] ?? null)),
                Action::make('markDelivered')
                    ->label('Marcar entregado')
                    ->visible(fn (Order $record): bool => $record->status === Order::STATUS_SHIPPED)
                    ->action(fn (Order $record) => app(UpdateOrderStatus::class)->handle($record, Order::STATUS_DELIVERED, auth()->user())),
            ]);
    }
}
