<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'catalog.view', 'catalog.manage', 'inventory.view', 'inventory.manage',
            'orders.view', 'orders.manage', 'customers.view', 'customers.manage', 'content.view', 'content.manage',
            'promotions.view', 'promotions.manage', 'settings.view', 'settings.manage', 'users.view', 'users.manage',
            'marketing.view', 'marketing.manage',
            'reviews.view', 'reviews.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = [
            'admin' => $permissions,
            'catalog' => ['catalog.view', 'catalog.manage', 'content.view', 'content.manage', 'promotions.view', 'promotions.manage', 'reviews.view', 'reviews.manage'],
            'inventory' => ['catalog.view', 'inventory.view', 'inventory.manage'],
            'orders' => ['catalog.view', 'inventory.view', 'orders.view', 'orders.manage', 'customers.view'],
        ];

        foreach ($roles as $name => $grants) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
                ->syncPermissions($grants);
        }

        $categories = [
            'hoodies' => 'Sudaderas',
            'streetwear' => 'Streetwear',
            'pants' => 'Pants',
            'pants_youngla' => 'Pants YoungLA',
            'jackets_afelpadas' => 'Chamarras afelpadas',
            'chains' => 'Plata .925',
            'combos' => 'Kits',
            'caps' => 'Gorras',
            'all' => 'Destacados',
        ];

        foreach ($categories as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        ProductCollection::firstOrCreate(
            ['slug' => 'essentials'],
            ['name' => 'Essentials', 'description' => 'Piezas esenciales de AURA'],
        );
    }
}
