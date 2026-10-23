<?php

namespace App\Filament\Pages;

use App\Settings\StorefrontSettings as Values;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.store-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Ajustes de tienda';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(Values $settings): void
    {
        $this->formSchema()->fill([
            'whatsapp_number' => $settings->whatsapp_number,
            'instagram_handle' => $settings->instagram_handle,
            'free_shipping_threshold' => number_format($settings->free_shipping_threshold_cents / 100, 2, '.', ''),
            'shipping_mode' => $settings->shipping_mode,
            'default_seo_title' => $settings->default_seo_title,
            'default_seo_description' => $settings->default_seo_description,
            'orders_notification_email' => $settings->orders_notification_email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('whatsapp_number')->label('WhatsApp (código de país y números)')->required()->maxLength(24),
                TextInput::make('instagram_handle')->label('Instagram')->required()->maxLength(80),
                TextInput::make('free_shipping_threshold')->label('Envío gratis desde (MXN)')->prefix('$')->numeric()->step('0.01')->required(),
                Select::make('shipping_mode')->label('Tarifas')->options(['confirm' => 'Confirmar por WhatsApp', 'fixed' => 'Tarifas fijas por zona'])->required(),
                TextInput::make('default_seo_title')->label('Título SEO predeterminado')->required()->maxLength(180),
                TextInput::make('default_seo_description')->label('Descripción SEO predeterminada')->required()->maxLength(320)->columnSpanFull(),
                TextInput::make('orders_notification_email')->label('Correo para avisos de pedidos')->email()->nullable()->maxLength(255)->helperText('Los avisos se envían cuando se configura un proveedor de correo y la cola está activa.'),
            ])
            ->statePath('data');
    }

    public function save(Values $settings): void
    {
        $data = $this->formSchema()->getState();
        $settings->whatsapp_number = $data['whatsapp_number'];
        $settings->instagram_handle = $data['instagram_handle'];
        $settings->free_shipping_threshold_cents = (int) round((float) $data['free_shipping_threshold'] * 100);
        $settings->shipping_mode = $data['shipping_mode'];
        $settings->default_seo_title = $data['default_seo_title'];
        $settings->default_seo_description = $data['default_seo_description'];
        $settings->orders_notification_email = $data['orders_notification_email'] ?: null;
        $settings->save();

        Notification::make()->title('Ajustes guardados')->success()->send();
    }

    private function formSchema(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('El formulario de ajustes no está disponible.');
    }
}
