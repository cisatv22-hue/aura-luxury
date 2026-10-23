<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('storefront.orders_notification_email', null);
    }

    public function down(): void
    {
        $this->migrator->delete('storefront.orders_notification_email');
    }
};
