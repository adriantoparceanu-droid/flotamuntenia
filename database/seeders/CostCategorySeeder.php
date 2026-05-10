<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

class CostCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categorii = [
            'Apa imbuteliata',
            'Dozatoare',
            'Filtre',
            'Consumabile',
        ];

        foreach ($categorii as $denumire) {
            CostCategory::updateOrCreate(
                ['denumire' => $denumire],
                ['activ' => true]
            );
        }
    }
}
