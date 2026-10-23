<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;
use UnitEnum;

trait AuthorizesByPermission
{
    abstract protected static function permissionPrefix(): string;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return match (class_basename(static::class)) {
            'ProductResource', 'ProductColorResource', 'ProductVariantResource', 'CategoryResource', 'CollectionResource', 'BundleResource', 'SizeGuideResource' => 'Catálogo',
            'OrderResource', 'CustomerResource' => 'Operación',
            'CouponResource', 'PromotionResource', 'ReviewResource', 'SubscriberResource', 'AnnouncementResource' => 'Marketing',
            'BannerResource', 'HomeSectionResource', 'LookResource', 'PageResource' => 'Contenido',
            'ShippingRateResource', 'RoleResource', 'UserResource' => 'Configuración',
            default => null,
        };
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::permissionPrefix().'.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::permissionPrefix().'.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }
}
