<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use App\Models\CostProduct;
use App\Models\Tva;
use Illuminate\Database\Seeder;

class CostProductSeeder extends Seeder
{
    public function run(): void
    {
        // ID-urile sunt fixate intentionat — sunt referite explicit
        // in business logic (vezi DOCUMENTATION.md §2 cost_products).
        $apa = CostCategory::firstWhere('denumire', 'Apa imbuteliata');
        $dozatoare = CostCategory::firstWhere('denumire', 'Dozatoare');

        $tva19 = Tva::firstWhere('valoare', 19.00);
        $tva9 = Tva::firstWhere('valoare', 9.00);

        // ATENTIE: NU includem `pret` in array-ul updateOrCreate.
        // Motivul: utilizatorul poate edita preturile prin UI in productie;
        // daca am pus `pret` aici, deploy-ul urmator ar reseta preturile la 0.
        // La primul create, coloana foloseste default-ul DB (0.00, vezi migrare).
        // La update, pretul existent ramane neschimbat.
        $produseCheie = [
            ['id' => 45, 'denumire' => 'APA PLATA 19L', 'id_category' => $apa?->id, 'id_tva' => $tva9?->id ?? $tva19?->id],
            ['id' => 46, 'denumire' => 'APA PLATA 11L', 'id_category' => $apa?->id, 'id_tva' => $tva9?->id ?? $tva19?->id],
            ['id' => 47, 'denumire' => 'DOZATOR PODEA', 'id_category' => $dozatoare?->id, 'id_tva' => $tva19?->id],
            ['id' => 52, 'denumire' => 'DOZATOR CUSTODIE', 'id_category' => $dozatoare?->id, 'id_tva' => $tva19?->id],
            ['id' => 55, 'denumire' => 'DOZATOR CU FILTRE - Custodie', 'id_category' => $dozatoare?->id, 'id_tva' => $tva19?->id],
        ];

        foreach ($produseCheie as $p) {
            CostProduct::updateOrCreate(
                ['id' => $p['id']],
                [
                    'denumire' => $p['denumire'],
                    'id_category' => $p['id_category'],
                    'id_tva' => $p['id_tva'],
                    'activ' => true,
                ]
            );
        }
    }
}
