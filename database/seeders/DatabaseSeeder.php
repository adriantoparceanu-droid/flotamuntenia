<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Master data — Faza 1.2.
        // Ordinea conteaza: TVA si categorii inainte de produse;
        // depozitele inainte de masini (cand vor fi seedate).
        $this->call([
            TvaSeeder::class,
            DepositSeeder::class,
            CostCategorySeeder::class,
            CostProductSeeder::class,
            // Faza 6.5 — template-uri email implicite (idempotent: nu suprascrie editarile manuale)
            TemplateuriEmailSeeder::class,
        ]);

        // Utilizatori demo, unul per rol — pentru dezvoltare locala.
        // Parola comuna: parola123
        $parola = Hash::make('parola123');

        User::updateOrCreate(
            ['email' => 'admin@flotamuntenia.test'],
            ['name' => 'Administrator', 'password' => $parola, 'tip' => User::TIP_ADMIN, 'confirmat' => true]
        );

        User::updateOrCreate(
            ['email' => 'sofer@flotamuntenia.test'],
            ['name' => 'Ion Sofer', 'password' => $parola, 'tip' => User::TIP_SOFER, 'confirmat' => true]
        );

        User::updateOrCreate(
            ['email' => 'client@flotamuntenia.test'],
            ['name' => 'Maria Client', 'password' => $parola, 'tip' => User::TIP_CLIENT, 'confirmat' => true]
        );

        User::updateOrCreate(
            ['email' => 'gestiune@flotamuntenia.test'],
            ['name' => 'Andrei Gestiune', 'password' => $parola, 'tip' => User::TIP_GESTIUNE, 'confirmat' => true]
        );
    }
}
