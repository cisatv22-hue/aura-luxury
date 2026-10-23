<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make('user_id')
                    ->label('Cuenta verificada vinculada')
                    ->relationship('user', 'email', modifyQueryUsing: fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->searchable()
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Vincula una cuenta solo después de verificar por un canal independiente que el cliente la controla. Esa cuenta podrá ver los pedidos de este registro.'),
                TextInput::make('city'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
