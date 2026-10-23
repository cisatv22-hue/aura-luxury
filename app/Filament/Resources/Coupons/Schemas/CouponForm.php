<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Código')->required()->maxLength(40)->unique(ignoreRecord: true)->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state))),
                Select::make('type')->label('Tipo')->options(['percent' => 'Porcentaje', 'amount' => 'Monto fijo'])->required()->live(),
                TextInput::make('value')->label(fn ($get): string => $get('type') === 'percent' ? 'Descuento en puntos base (1000 = 10%)' : 'Descuento en centavos')->numeric()->minValue(1)->required(),
                TextInput::make('minimum_subtotal_cents')->label('Compra mínima en centavos')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('usage_limit')->label('Límite de usos')->numeric()->minValue(1),
                TextInput::make('usage_count')->label('Usos acumulados')->numeric()->disabled()->dehydrated(false),
                DateTimePicker::make('starts_at')->label('Válido desde'),
                DateTimePicker::make('ends_at')->label('Válido hasta')->after('starts_at'),
                Toggle::make('is_active')->label('Activo')->default(true)->required(),
            ]);
    }
}
