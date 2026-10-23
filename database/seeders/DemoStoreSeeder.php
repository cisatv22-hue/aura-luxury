<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            DemoPreviewSeeder::class,
            DemoOperationsSeeder::class,
        ]);
    }
}
