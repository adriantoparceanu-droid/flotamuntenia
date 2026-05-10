<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder pentru productie — DOAR master data, fara conturi demo.
 *
 * Rulare pe cPanel:
 *   php artisan db:seed --class=ProductionSeeder --force
 *
 * Pentru admin: foloseste `php artisan app:create-admin` (interactiv).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TvaSeeder::class,
            DepositSeeder::class,
            CostCategorySeeder::class,
            CostProductSeeder::class,
            TemplateuriEmailSeeder::class,
        ]);
    }
}
