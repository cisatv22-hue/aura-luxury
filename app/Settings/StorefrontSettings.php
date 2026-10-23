<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StorefrontSettings extends Settings
{
    public string $whatsapp_number;

    public string $instagram_handle;

    public int $free_shipping_threshold_cents;

    public string $shipping_mode;

    public string $default_seo_title;

    public string $default_seo_description;

    public ?string $orders_notification_email;

    public static function group(): string
    {
        return 'storefront';
    }
}
