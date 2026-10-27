<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('storefront.whatsapp_number', '525516069816');
        $this->migrator->add('storefront.instagram_handle', '@tropiezo_mx');
        $this->migrator->add('storefront.free_shipping_threshold_cents', 120000);
        $this->migrator->add('storefront.shipping_mode', 'confirm');
        $this->migrator->add('storefront.default_seo_title', 'AURA · Ropa y accesorios');
        $this->migrator->add('storefront.default_seo_description', 'Ropa y accesorios seleccionados para todos los días.');
    }
};
