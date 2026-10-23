<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\GenerateVariantMatrix;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === Product::STATUS_ACTIVE && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateVariantMatrix')
                ->label('Generar variantes')
                ->icon('heroicon-o-squares-plus')
                ->schema([
                    CheckboxList::make('sizes')->label('Tallas')->options(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', 'XXL' => 'XXL', '28' => '28', '30' => '30', '32' => '32', '34' => '34', '36' => '36', '38' => '38', '40' => '40'])->required()->minItems(1),
                    TextInput::make('prefix')->label('Prefijo SKU')->maxLength(20),
                ])
                ->action(function (array $data): void {
                    if (! $this->record instanceof Product) {
                        return;
                    }
                    $count = app(GenerateVariantMatrix::class)->handle($this->record, $data['sizes'], $data['prefix'] ?? null);
                    Notification::make()->title("{$count} variantes creadas sin existencias")->success()->send();
                })
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->colors()->exists()),
            DeleteAction::make(),
        ];
    }
}
