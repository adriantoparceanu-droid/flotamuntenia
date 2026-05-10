<?php

namespace Database\Seeders;

use App\Models\Tva;
use Illuminate\Database\Seeder;

class TvaSeeder extends Seeder
{
    public function run(): void
    {
        // Cotele standard din Romania la momentul scrierii.
        // Userul le poate edita din /setari/tva daca legislatia se modifica.
        Tva::updateOrCreate(
            ['valoare' => 19.00],
            ['denumire' => '19% standard', 'activ' => true]
        );

        Tva::updateOrCreate(
            ['valoare' => 9.00],
            ['denumire' => '9% redusa', 'activ' => true]
        );
    }
}
