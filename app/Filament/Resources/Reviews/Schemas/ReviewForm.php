<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')->label('Moderación')->options(['pending' => 'Pendiente', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'])->required(),
            ]);
    }
}
