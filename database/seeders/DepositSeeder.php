<?php

namespace Database\Seeders;

use App\Models\Deposit;
use Illuminate\Database\Seeder;

class DepositSeeder extends Seeder
{
    public function run(): void
    {
        Deposit::updateOrCreate(
            ['denumire' => 'Depou Principal'],
            ['adresa' => null, 'activ' => true]
        );
    }
}
