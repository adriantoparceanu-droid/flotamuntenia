<?php

namespace Database\Seeders;

use App\Models\AdresaLivrare;
use App\Models\Car;
use App\Models\Cheltuiala;
use App\Models\CheltuialaProdus;
use App\Models\Client;
use App\Models\Comanda;
use App\Models\ComandaProdus;
use App\Models\ComandaRapida;
use App\Models\ComandaRapidaProdus;
use App\Models\ContractClient;
use App\Models\Deposit;
use App\Models\Dozator;
use App\Models\DozatorFiltre;
use App\Models\DozatorFiltreIstoric;
use App\Models\Problema;
use App\Models\Produs;
use App\Models\SetariPlatforma;
use App\Models\Stoc;
use App\Models\Vizita;
use App\Services\ContracteService;
use Illuminate\Database\Seeder;

/**
 * Seeder de date demo pentru dezvoltare.
 *
 * Reguli importante:
 *  - Toate inserarile folosesc firstOrCreate cu chei naturale (cod_client,
 *    nr_inmatriculare, etc.) — rularile repetate NU duplica si NU suprascriu
 *    modificarile manuale facute din UI.
 *  - NU este apelat din DatabaseSeeder pentru a nu rula accidental in teste
 *    sau in productie. Se ruleaza explicit:
 *      php artisan db:seed --class=DemoDateSeeder
 *  - Nu sterge niciodata date existente (nu folosim truncate/delete).
 *
 * Continut:
 *  - 3 masini cu culori distincte pentru testarea hartii
 *  - 5 clienti cu scenarii diferite (PJ, PF, abonament lunar, saptamanal,
 *    per bucata, mixt 19L+11L, reziliat)
 *  - 7 adrese de livrare cu coordonate GPS reale din Bucuresti
 *  - 5 configurari `produs` (per adresa)
 *  - 8 comenzi cu data_livrare = today, distribuite pe masini
 *    (parte alocate, parte nealocate)
 */
class DemoDateSeeder extends Seeder
{
    public function run(): void
    {
        $depozit = Deposit::firstWhere('denumire', 'Depou Principal');
        if (! $depozit) {
            $this->command->error('Lipseste depozitul. Ruleaza intai: php artisan db:seed (DepositSeeder).');
            return;
        }

        // ============================================================
        // 1. MASINI — 3 vehicule cu culori distincte pentru harta.
        // ============================================================
        $this->command->info('Creez masini...');

        $masini = [
            'Iveco 01' => [
                'nr_inmatriculare' => 'B-100-FLT',
                'culoare' => '#3b82f6', // albastru
            ],
            'Iveco 02' => [
                'nr_inmatriculare' => 'B-200-FLT',
                'culoare' => '#10b981', // verde
            ],
            'Dacia Dokker' => [
                'nr_inmatriculare' => 'B-300-FLT',
                'culoare' => '#f59e0b', // amber/galben
            ],
        ];

        $masiniCreate = [];
        foreach ($masini as $denumire => $atribute) {
            $masiniCreate[$denumire] = Car::firstOrCreate(
                ['nr_inmatriculare' => $atribute['nr_inmatriculare']],
                [
                    'denumire' => $denumire,
                    'id_depozit' => $depozit->id,
                    'culoare' => $atribute['culoare'],
                    'activ' => true,
                ]
            );
        }

        // ============================================================
        // 2. CLIENTI — 5 scenarii diferite.
        // ============================================================
        $this->command->info('Creez clienti...');

        // Client 1: PJ cu abonament lunar fix (firma de office)
        $aquaPro = Client::firstOrCreate(
            ['cod_client' => 'DEMO-AQUAPRO'],
            [
                'client' => Client::TIP_PJ,
                'denumire' => 'AquaPro Office SRL',
                'cif' => 'RO12345678',
                'reg_com' => 'J40/1234/2020',
                'oras' => 'Bucuresti',
                'strada' => 'Calea Victoriei',
                'nr' => '100',
                'sector' => 'Sector 1',
                'email' => 'contact@aquapro.demo',
                'telefon' => '0721000001',
                'observatii' => 'Demo seed — abonament lunar fix 5x19L',
                'reziliat' => false,
            ]
        );

        // Client 2: PJ cu plata per bucata (hotel — cantitati variabile)
        $hotelBoulevard = Client::firstOrCreate(
            ['cod_client' => 'DEMO-HOTEL'],
            [
                'client' => Client::TIP_PJ,
                'denumire' => 'Hotel Boulevard SA',
                'cif' => 'RO87654321',
                'reg_com' => 'J40/5678/2018',
                'oras' => 'Bucuresti',
                'strada' => 'Bd. Unirii',
                'nr' => '15',
                'sector' => 'Sector 3',
                'email' => 'office@hotelboulevard.demo',
                'telefon' => '0721000002',
                'observatii' => 'Demo seed — per bucata, cantitati variabile',
                'reziliat' => false,
            ]
        );

        // Client 3: PF cu abonament saptamanal (casa)
        $popescuMaria = Client::firstOrCreate(
            ['cod_client' => 'DEMO-POPESCU'],
            [
                'client' => Client::TIP_PF,
                'denumire' => 'Popescu Maria',
                'cif' => '2701020030011', // CNP fictiv (format valid)
                'oras' => 'Bucuresti',
                'strada' => 'Str. Negustori',
                'nr' => '22',
                'bloc' => 'A',
                'apartament' => '12',
                'sector' => 'Sector 2',
                'email' => 'maria.popescu@email.demo',
                'telefon' => '0721000003',
                'observatii' => 'Demo seed — PF abonament saptamanal',
                'reziliat' => false,
            ]
        );

        // Client 4: PJ cu abonament mixt 19L + 11L (birou cu cantitati mici)
        $studioBirou = Client::firstOrCreate(
            ['cod_client' => 'DEMO-STUDIO'],
            [
                'client' => Client::TIP_PJ,
                'denumire' => 'Studio Birou SRL',
                'cif' => 'RO11223344',
                'reg_com' => 'J40/9012/2022',
                'oras' => 'Bucuresti',
                'strada' => 'Bd. Iuliu Maniu',
                'nr' => '78',
                'sector' => 'Sector 6',
                'email' => 'birou@studio.demo',
                'telefon' => '0721000004',
                'observatii' => 'Demo seed — abonament mixt 19L+11L',
                'reziliat' => false,
            ]
        );

        // Client 5: PJ reziliat (testare filtrare)
        $cofetariaVeche = Client::firstOrCreate(
            ['cod_client' => 'DEMO-COFETARIA'],
            [
                'client' => Client::TIP_PJ,
                'denumire' => 'Cofetaria Veche SRL',
                'cif' => 'RO99887766',
                'reg_com' => 'J40/3456/2015',
                'oras' => 'Bucuresti',
                'strada' => 'Str. Lipscani',
                'nr' => '8',
                'sector' => 'Sector 3',
                'email' => 'cofetaria@demo.test',
                'telefon' => '0721000005',
                'observatii' => 'Demo seed — client reziliat (testare filtre)',
                'reziliat' => true,
                'observatii_reziliere' => 'Inchis activitate — demo',
            ]
        );

        // ============================================================
        // 3. ADRESE DE LIVRARE — coordonate GPS reale Bucuresti.
        // ============================================================
        $this->command->info('Creez adrese de livrare...');

        // AquaPro: 2 adrese (sediu + punct lucru)
        $adrAquaProSediu = AdresaLivrare::firstOrCreate(
            ['id_client' => $aquaPro->id, 'denumire' => 'Sediu Calea Victoriei'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Calea Victoriei',
                'nr' => '100',
                'etaj' => '3',
                'sector' => 'Sector 1',
                'lat' => 44.43780000,
                'lng' => 26.09580000,
                'activ' => true,
            ]
        );

        $adrAquaProPunct = AdresaLivrare::firstOrCreate(
            ['id_client' => $aquaPro->id, 'denumire' => 'Punct lucru Pipera'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Sos. Pipera',
                'nr' => '41',
                'etaj' => '2',
                'sector' => 'Sector 2',
                'lat' => 44.47620000,
                'lng' => 26.11380000,
                'activ' => true,
            ]
        );

        // Hotel Boulevard: 1 adresa
        $adrHotel = AdresaLivrare::firstOrCreate(
            ['id_client' => $hotelBoulevard->id, 'denumire' => 'Hotel Bd. Unirii'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Bd. Unirii',
                'nr' => '15',
                'sector' => 'Sector 3',
                'lat' => 44.42680000,
                'lng' => 26.11310000,
                'activ' => true,
            ]
        );

        // Popescu Maria: 1 adresa rezidentiala
        $adrPopescu = AdresaLivrare::firstOrCreate(
            ['id_client' => $popescuMaria->id, 'denumire' => 'Acasa'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Str. Negustori',
                'nr' => '22',
                'bloc' => 'A',
                'apartament' => '12',
                'sector' => 'Sector 2',
                'lat' => 44.43950000,
                'lng' => 26.11840000,
                'activ' => true,
            ]
        );

        // Studio Birou: 1 adresa
        $adrStudio = AdresaLivrare::firstOrCreate(
            ['id_client' => $studioBirou->id, 'denumire' => 'Birou Iuliu Maniu'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Bd. Iuliu Maniu',
                'nr' => '78',
                'etaj' => '5',
                'sector' => 'Sector 6',
                'lat' => 44.43390000,
                'lng' => 26.02940000,
                'activ' => true,
            ]
        );

        // Cofetaria (reziliat): 1 adresa inactiva
        $adrCofetaria = AdresaLivrare::firstOrCreate(
            ['id_client' => $cofetariaVeche->id, 'denumire' => 'Punct vanzare Lipscani'],
            [
                'oras' => 'Bucuresti',
                'strada' => 'Str. Lipscani',
                'nr' => '8',
                'sector' => 'Sector 3',
                'lat' => 44.43200000,
                'lng' => 26.10300000,
                'activ' => false, // adresa dezactivata, ca si clientul
            ]
        );

        // ============================================================
        // 4. CONFIGURARI `produs` (per adresa) — scenarii distincte.
        // ============================================================
        $this->command->info('Creez configurari livrare (produs)...');

        // AquaPro Sediu — abonament lunar fix 5x19L
        Produs::firstOrCreate(
            ['id_adresa' => $adrAquaProSediu->id],
            [
                'id_client' => $aquaPro->id,
                'abonament' => Produs::TIP_ABONAMENT,
                'denumire_abonament' => 'Pachet Office Standard',
                'nr_bidoane' => 5,
                'nr_bidoane_11l' => 0,
                'pret' => 87.50,
                'pret_suplimentar_19l' => 17.50,
                'frecventa' => null, // lunar — nu folosim frecventa
                'zi_livrare' => now()->startOfMonth()->toDateString(),
                'id_depozit' => $depozit->id,
                'observatii' => 'Demo seed — abonament lunar 5 bidoane',
            ]
        );

        // AquaPro Pipera — per bucata pentru consum suplimentar
        Produs::firstOrCreate(
            ['id_adresa' => $adrAquaProPunct->id],
            [
                'id_client' => $aquaPro->id,
                'abonament' => Produs::TIP_PER_BUCATA,
                'nr_bidoane' => 0,
                'nr_bidoane_11l' => 0,
                'pret' => 17.50,
                'pret_11l' => 12.00,
                'id_depozit' => $depozit->id,
                'observatii' => 'Demo seed — per bucata',
            ]
        );

        // Hotel — abonament FILTRE (Faza 5.4): dozator cu filtre, fara livrare
        // fizica, doar facturare lunara. Folosim updateOrCreate ca seederul
        // sa fie idempotent si la rerulare daca s-a schimbat configurarea.
        Produs::updateOrCreate(
            ['id_adresa' => $adrHotel->id],
            [
                'id_client' => $hotelBoulevard->id,
                'abonament' => Produs::TIP_FILTRE,
                'denumire_abonament' => 'Abonament dozator filtre',
                'nr_bidoane' => 0,
                'nr_bidoane_11l' => 0,
                'pret' => 50.00, // tarif lunar facturat
                'id_depozit' => $depozit->id,
                'observatii' => 'Demo seed — abonament filtre lunar (fara livrare fizica)',
            ]
        );

        // Popescu Maria — abonament saptamanal (frecventa 7 zile)
        Produs::firstOrCreate(
            ['id_adresa' => $adrPopescu->id],
            [
                'id_client' => $popescuMaria->id,
                'abonament' => Produs::TIP_ABONAMENT,
                'denumire_abonament' => 'Pachet Familie Saptamanal',
                'nr_bidoane' => 2,
                'nr_bidoane_11l' => 0,
                'pret' => 35.00, // 2x19L pe livrare
                'pret_suplimentar_19l' => 17.50,
                'frecventa' => 7,
                'zi_livrare' => now()->startOfWeek()->toDateString(),
                'id_depozit' => $depozit->id,
                'observatii' => 'Demo seed — saptamanal, frecventa 7 zile',
            ]
        );

        // Studio Birou — abonament mixt 19L + 11L
        Produs::firstOrCreate(
            ['id_adresa' => $adrStudio->id],
            [
                'id_client' => $studioBirou->id,
                'abonament' => Produs::TIP_ABONAMENT,
                'denumire_abonament' => 'Pachet Mixt Office',
                'nr_bidoane' => 3,
                'nr_bidoane_11l' => 4,
                'pret' => 100.50,
                'pret_suplimentar_19l' => 17.50,
                'pret_suplimentar_11l' => 12.00,
                'frecventa' => null,
                'zi_livrare' => now()->startOfMonth()->toDateString(),
                'id_depozit' => $depozit->id,
                'observatii' => 'Demo seed — mixt 3x19L + 4x11L',
            ]
        );

        // ============================================================
        // 5. COMENZI — pentru ZIUA CURENTA, distribuite pe masini.
        // Combinatie de tipuri si moduri de plata pentru testare.
        // ============================================================
        $this->command->info('Creez comenzi pentru azi...');

        $azi = now()->toDateString();
        $lunaCurenta = now()->format('Y/m');

        $comenzi = [
            // 1) AquaPro Sediu — abonament lunar livrat de Iveco 01
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 5,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP, // firma plateste OP
                'data_livrare' => $azi,
                'luna_livrata' => $lunaCurenta,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO — Abonament lunar AquaPro',
                'produse' => [['id_produs' => 45, 'cantitate' => 5, 'pret' => 17.50]],
            ],

            // 2) AquaPro Pipera — consum suplimentar Iveco 01
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProPunct->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'tip_comanda' => Comanda::TIP_CONSUM_SUPLIMENTAR,
                'nr_recipienti' => 3,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => $azi,
                'luna_livrata' => null,
                'ordine_traseu' => 2,
                'observatii' => 'DEMO — Consum extra AquaPro Pipera',
                'produse' => [['id_produs' => 45, 'cantitate' => 3, 'pret' => 17.50]],
            ],

            // 3) Hotel Boulevard — fara abonament Iveco 01
            [
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'tip_comanda' => Comanda::TIP_FARA_ABONAMENT,
                'nr_recipienti' => 10,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_CARD,
                'data_livrare' => $azi,
                'luna_livrata' => null,
                'ordine_traseu' => 3,
                'observatii' => 'DEMO — Hotel Boulevard, plata card',
                'produse' => [['id_produs' => 45, 'cantitate' => 10, 'pret' => 16.00]],
            ],

            // 4) Popescu Maria — abonament saptamanal Iveco 02
            [
                'id_client' => $popescuMaria->id,
                'id_adresa' => $adrPopescu->id,
                'id_masina' => $masiniCreate['Iveco 02']->id,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 2,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_CASH,
                'data_livrare' => $azi,
                'luna_livrata' => $lunaCurenta,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO — Popescu, abonament saptamanal',
                'produse' => [['id_produs' => 45, 'cantitate' => 2, 'pret' => 17.50]],
            ],

            // 5) Studio Birou — abonament mixt Iveco 02
            [
                'id_client' => $studioBirou->id,
                'id_adresa' => $adrStudio->id,
                'id_masina' => $masiniCreate['Iveco 02']->id,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 3,
                'nr_pahare' => 4,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => $azi,
                'luna_livrata' => $lunaCurenta,
                'ordine_traseu' => 2,
                'observatii' => 'DEMO — Studio, mixt 3x19L + 4x11L',
                'produse' => [
                    ['id_produs' => 45, 'cantitate' => 3, 'pret' => 17.50],
                    ['id_produs' => 46, 'cantitate' => 4, 'pret' => 12.00],
                ],
            ],

            // 6) AquaPro Sediu (a doua livrare urgenta) — Dacia Dokker, deja LIVRATA
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Dacia Dokker']->id,
                'tip_comanda' => Comanda::TIP_FARA_ABONAMENT,
                'nr_recipienti' => 2,
                'nr_pahare' => 2,
                'id_modalitate_plata' => Comanda::MODPLATA_CASH,
                'data_livrare' => $azi,
                'luna_livrata' => null,
                'livrat' => true, // marcata ca livrata pentru testare opacity pin
                'achitat' => true,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO — Livrare urgenta AquaPro (livrata)',
                'produse' => [
                    ['id_produs' => 45, 'cantitate' => 2, 'pret' => 17.50],
                    ['id_produs' => 46, 'cantitate' => 2, 'pret' => 12.00],
                ],
            ],

            // 7) Hotel Boulevard — comanda 11L de luat Dacia Dokker
            [
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => $masiniCreate['Dacia Dokker']->id,
                'tip_comanda' => Comanda::TIP_FARA_ABONAMENT,
                'nr_recipienti' => 0,
                'nr_pahare' => 6,
                'id_modalitate_plata' => Comanda::MODPLATA_CARD,
                'data_livrare' => $azi,
                'luna_livrata' => null,
                'ordine_traseu' => 2,
                'observatii' => 'DEMO — Hotel, doar 11L',
                'produse' => [['id_produs' => 46, 'cantitate' => 6, 'pret' => 11.00]],
            ],

            // 8) Studio Birou — comanda NEALOCATA (testare pin rosu)
            [
                'id_client' => $studioBirou->id,
                'id_adresa' => $adrStudio->id,
                'id_masina' => null, // NEALOCATA — pin rosu pe harta
                'tip_comanda' => Comanda::TIP_CONSUM_SUPLIMENTAR,
                'nr_recipienti' => 2,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => $azi,
                'luna_livrata' => null,
                'ordine_traseu' => 0,
                'observatii' => 'DEMO — Nealocata (testare pin rosu)',
                'produse' => [['id_produs' => 45, 'cantitate' => 2, 'pret' => 17.50]],
            ],
        ];

        foreach ($comenzi as $datele) {
            $linii = $datele['produse'];
            unset($datele['produse']);

            $datele['id_depozit'] = $depozit->id;

            // Cheie naturala: doar `observatii` (fiecare comanda demo are
            // observatii unice cu prefix "DEMO — ..."). Garanteaza idempotenta:
            // rulari repetate nu creeaza duplicate. Nu folosim data_livrare
            // ca discriminator pentru ca cast-ul Carbon o stocheaza cu time
            // ('2026-05-10 00:00:00') iar firstOrCreate ar rata potrivirea.
            $comanda = Comanda::firstOrCreate(
                ['observatii' => $datele['observatii']],
                $datele
            );

            // Liniile de comanda — firstOrCreate pe (id_comanda, id_produs)
            // ca sa permita re-rulari fara duplicat.
            foreach ($linii as $linie) {
                ComandaProdus::firstOrCreate(
                    [
                        'id_comanda' => $comanda->id,
                        'id_produs' => $linie['id_produs'],
                    ],
                    [
                        'cantitate' => $linie['cantitate'],
                        'pret' => $linie['pret'],
                    ]
                );
            }
        }

        // ============================================================
        // 5a. ISTORIC ABONAMENTE (Faza 5.4) — comenzi cu luna_livrata
        // pentru luni trecute, pentru a popula raportul de abonamente lipsa.
        //
        // Scenarii dupa rularea seed-ului (luna curenta = mai 2026):
        //  - AquaPro Sediu (#1, abonament bidoane): istoric 2026/01 + 2026/03
        //    + 2026/05 (existent) → lipsuri detectate: feb 2026, apr 2026
        //  - Hotel Boulevard (#3, abonament filtre): istoric 2026/02
        //    → lipsuri: mar 2026, apr 2026, mai 2026
        //  - Popescu Maria (#4): existent doar 2026/05 → fara lipsuri (start
        //    luna curenta)
        //  - Studio Birou (#5): existent 2026/05 → fara lipsuri
        // ============================================================
        $this->command->info('Creez istoric abonamente pentru raport...');

        $istoricAbonamente = [
            // AquaPro Sediu — luna ianuarie (acum 4 luni)
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 5,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => now()->subMonths(4)->format('Y-m-15'),
                'luna_livrata' => now()->subMonths(4)->format('Y/m'),
                'livrat' => true,
                'achitat' => true,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO ISTORIC — Abonament AquaPro luna ianuarie',
                'produse' => [['id_produs' => 45, 'cantitate' => 5, 'pret' => 17.50]],
            ],
            // AquaPro Sediu — luna martie (acum 2 luni); FARA februarie (lipsa)
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 5,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => now()->subMonths(2)->format('Y-m-15'),
                'luna_livrata' => now()->subMonths(2)->format('Y/m'),
                'livrat' => true,
                'achitat' => true,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO ISTORIC — Abonament AquaPro luna martie',
                'produse' => [['id_produs' => 45, 'cantitate' => 5, 'pret' => 17.50]],
            ],
            // Hotel Boulevard — abonament filtre februarie (acum 3 luni)
            [
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => null,
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 0,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => now()->subMonths(3)->format('Y-m-15'),
                'luna_livrata' => now()->subMonths(3)->format('Y/m'),
                'livrat' => true,
                'achitat' => true,
                'ordine_traseu' => 1,
                'observatii' => 'DEMO ISTORIC — Abonament filtre Hotel Boulevard luna februarie',
                'produse' => [], // abonament filtre — fara linii (nu se livreaza fizic)
            ],
        ];

        foreach ($istoricAbonamente as $datele) {
            $linii = $datele['produse'];
            unset($datele['produse']);
            $datele['id_depozit'] = $depozit->id;

            $comanda = Comanda::firstOrCreate(
                ['observatii' => $datele['observatii']],
                $datele
            );

            foreach ($linii as $linie) {
                ComandaProdus::firstOrCreate(
                    [
                        'id_comanda' => $comanda->id,
                        'id_produs' => $linie['id_produs'],
                    ],
                    [
                        'cantitate' => $linie['cantitate'],
                        'pret' => $linie['pret'],
                    ]
                );
            }
        }

        // ============================================================
        // 5b. COMENZI PORTAL IN ASTEPTARE (Faza 3.3).
        // Simulam plasarea de comenzi de catre clienti pe portalul self-service
        // (status='In asteptare'). Apar in /comenzi/aprobare cu badge contor in
        // sidebar; ASCUNSE din lista zilnica si traseul soferului pana la aprobare.
        // Idempotent prin observatii cu prefix 'DEMO PORTAL'.
        // ============================================================
        $this->command->info('Creez comenzi portal in asteptare...');

        $maine = now()->addDay()->toDateString();

        $comenziPortal = [
            // 1) AquaPro — abonament 5x19L cu data dorita maine, asteapta aprobare
            [
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProPunct->id,
                'id_masina' => null, // alocarea se face la aprobare
                'tip_comanda' => Comanda::TIP_ABONAMENT,
                'nr_recipienti' => 5,
                'nr_pahare' => 0,
                'id_modalitate_plata' => Comanda::MODPLATA_OP,
                'data_livrare' => $maine,
                'interval_livrare' => '09:00-12:00',
                'luna_livrata' => now()->addDay()->format('Y/m'),
                'status' => Comanda::STATUS_IN_ASTEPTARE,
                'observatii' => 'DEMO PORTAL — AquaPro Pipera, abonament 5x19L plasat din portal',
                'produse' => [['id_produs' => 45, 'cantitate' => 5, 'pret' => 17.50]],
            ],

            // 2) Hotel Boulevard — fara abonament 11L, plasata din portal
            [
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => null,
                'tip_comanda' => Comanda::TIP_FARA_ABONAMENT,
                'nr_recipienti' => 0,
                'nr_pahare' => 8,
                'id_modalitate_plata' => Comanda::MODPLATA_CARD,
                'data_livrare' => $maine,
                'interval_livrare' => '14:00-17:00',
                'luna_livrata' => null,
                'status' => Comanda::STATUS_IN_ASTEPTARE,
                'observatii' => 'DEMO PORTAL — Hotel, 8x11L plasata online',
                'produse' => [['id_produs' => 46, 'cantitate' => 8, 'pret' => 12.00]],
            ],
        ];

        foreach ($comenziPortal as $datele) {
            $linii = $datele['produse'];
            unset($datele['produse']);

            $datele['id_depozit'] = $depozit->id;

            $comanda = Comanda::firstOrCreate(
                ['observatii' => $datele['observatii']],
                $datele
            );

            foreach ($linii as $linie) {
                ComandaProdus::firstOrCreate(
                    [
                        'id_comanda' => $comanda->id,
                        'id_produs' => $linie['id_produs'],
                    ],
                    [
                        'cantitate' => $linie['cantitate'],
                        'pret' => $linie['pret'],
                    ]
                );
            }
        }

        // ============================================================
        // 5b. COMANDA RAPIDA DEMO (Faza 3.1) — comanda ad-hoc fara cont client,
        // utila pentru rapoartele Faza 5 (financiar bidoane).
        // Idempotent prin denumire cu prefix DEMO RAPIDA.
        // ============================================================
        $this->command->info('Creez comanda rapida demo...');

        $rapidaDenumire = 'DEMO RAPIDA — Persoana fizica strada';
        $cmdRapida = ComandaRapida::firstOrCreate(
            ['denumire' => $rapidaDenumire],
            [
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'id_depozit' => $depozit->id,
                'adresa' => 'Bucuresti, Strada Lipscani 12',
                'telefon' => '0729111222',
                'lat' => 44.4322,
                'lng' => 26.1010,
                'data_livrare' => $azi,
                'livrat' => true,
                'achitat' => true,
                'ordine_traseu' => 4,
                'observatii' => 'Achitat pe loc, primire la usa.',
            ]
        );

        // Liniile produs pentru comanda rapida (idempotent prin pereche id+id_produs)
        ComandaRapidaProdus::firstOrCreate(
            ['id_comanda_rapida' => $cmdRapida->id, 'id_produs' => 45], // APA PLATA 19L
            ['cantitate' => 3, 'pret' => 18.00]
        );
        ComandaRapidaProdus::firstOrCreate(
            ['id_comanda_rapida' => $cmdRapida->id, 'id_produs' => 46], // APA PLATA 11L
            ['cantitate' => 2, 'pret' => 12.00]
        );

        // ============================================================
        // 6. PROBLEME / INTERVENȚII pentru ziua curentă (Faza 3.2).
        // Idempotent prin firstOrCreate pe `descriere` (cu prefix DEMO).
        // ============================================================
        $this->command->info('Creez probleme/interventii pentru azi...');

        $probleme = [
            [
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => $masiniCreate['Iveco 02']->id,
                'descriere' => 'DEMO — Pompa dozator defecta, scurgere la racord. Verificare si inlocuire piese.',
                'suma' => 150.00,
                'id_modalitate_plata' => Problema::MODPLATA_CASH,
                'data_livrare' => $azi,
                'interval_livrare' => '14:00-16:00',
                'nume' => 'Receptie Hotel',
                'telefon' => '0721000222',
                'ordine_traseu' => 3,
            ],
            [
                'id_client' => $studioBirou->id,
                'id_adresa' => $adrStudio->id,
                'id_masina' => null, // NEALOCATA pentru testare
                'descriere' => 'DEMO — Verificare instalatie + curatare filtru intrare. Programata la cererea clientului.',
                'suma' => 80.00,
                'id_modalitate_plata' => Problema::MODPLATA_OP,
                'data_livrare' => $azi,
                'interval_livrare' => '10:00-11:00',
                'nume' => 'Office Manager',
                'telefon' => '0721000444',
                'ordine_traseu' => 0,
            ],
        ];

        foreach ($probleme as $datele) {
            $datele['id_depozit'] = $depozit->id;
            // Cheie naturala: descriere (unica per problema demo cu prefix DEMO).
            Problema::firstOrCreate(
                ['descriere' => $datele['descriere']],
                $datele
            );
        }

        // ============================================================
        // 7. DOZATOARE cu BIDOANE (Faza 4.1) — 3 scenarii distincte:
        //   - DEMO-DOZ-1: la zi (perioada_igenizare > 30 zile) — verde
        //   - DEMO-DOZ-2: scadent in 15 zile — rosu
        //   - DEMO-DOZ-3: expirat (-10 zile) — negru
        // Idempotent prin firstOrCreate pe `serie`.
        // ============================================================
        $this->command->info('Creez dozatoare cu bidoane...');

        $dozatoareDemo = [
            [
                'serie' => 'DEMO-DOZ-1',
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'id_produs' => 47, // DOZATOR PODEA (fix din Faza 1.2)
                'id_depozit' => $depozit->id,
                'tranzactie' => Dozator::TRANZACTIE_CUSTODIE,
                'data_instalare' => now()->subMonths(3)->toDateString(),
                'perioada_igenizare' => now()->addMonths(2)->addDays(15)->toDateString(), // ~75 zile
                'observatii' => 'DEMO — instalat acum 3 luni, igienizare la zi',
                'activ' => true,
            ],
            [
                'serie' => 'DEMO-DOZ-2',
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => $masiniCreate['Iveco 02']->id,
                'id_produs' => 52, // DOZATOR CUSTODIE (fix din Faza 1.2)
                'id_depozit' => $depozit->id,
                'tranzactie' => Dozator::TRANZACTIE_CUSTODIE,
                'data_instalare' => now()->subMonths(6)->toDateString(),
                'perioada_igenizare' => now()->addDays(10)->toDateString(), // urgent (15 zile)
                'observatii' => 'DEMO — scadent in 10 zile, necesita reminder',
                'activ' => true,
            ],
            [
                'serie' => 'DEMO-DOZ-3',
                'id_client' => $studioBirou->id,
                'id_adresa' => $adrStudio->id,
                'id_masina' => null,
                'id_produs' => 47,
                'id_depozit' => $depozit->id,
                'tranzactie' => Dozator::TRANZACTIE_CUMPARAT, // dozator vandut
                'data_instalare' => now()->subMonths(8)->toDateString(),
                'perioada_igenizare' => now()->subDays(10)->toDateString(), // expirat -10 zile
                'observatii' => 'DEMO — vandut, igienizare expirata de 10 zile',
                'activ' => true,
            ],
        ];

        $dozatoareCreated = [];
        foreach ($dozatoareDemo as $datele) {
            $d = Dozator::firstOrCreate(
                ['serie' => $datele['serie']],
                $datele
            );
            $dozatoareCreated[$datele['serie']] = $d;

            // Mişcare de stoc CUSTODIE/OUT — idempotent prin tip_referinta+id_referinta
            $tipStoc = $datele['tranzactie'] === Dozator::TRANZACTIE_CUMPARAT
                ? Stoc::TIP_OUT
                : Stoc::TIP_CUSTODIE;

            Stoc::firstOrCreate(
                [
                    'tip_referinta' => Stoc::REF_DOZATOR,
                    'id_referinta' => $d->id,
                ],
                [
                    'id_produs' => $d->id_produs,
                    'id_depozit' => $d->id_depozit,
                    'cantitate' => 1,
                    'tip' => $tipStoc,
                    'data' => $d->data_instalare,
                    'observatii' => 'Serie ' . $d->serie,
                ]
            );
        }

        // Vizite istoric (2 pe primul dozator — la zi)
        $doz1 = $dozatoareCreated['DEMO-DOZ-1'];
        $vizitepDemo = [
            [
                'id_dozator' => $doz1->id,
                'data_vizita' => now()->subMonths(3)->toDateString(), // initiala la instalare
                'data_urmatoare' => now()->subMonths(3)->addMonths(6)->toDateString(),
                'pret' => 0,
                'observatii' => 'DEMO — vizita initiala la instalare',
            ],
            [
                'id_dozator' => $doz1->id,
                'data_vizita' => now()->subDays(15)->toDateString(),
                'data_urmatoare' => now()->addMonths(2)->addDays(15)->toDateString(),
                'pret' => 50.00,
                'observatii' => 'DEMO — igienizare programata efectuata',
            ],
        ];
        foreach ($vizitepDemo as $vd) {
            // Cheie naturala: `observatii` (text unic per vizita demo).
            // Cast `date` pe data_vizita stocheaza datetime, deci firstOrCreate
            // pe data_vizita ar gasi 0 match si ar duplica la fiecare rulare.
            Vizita::firstOrCreate(
                ['observatii' => $vd['observatii']],
                array_merge($vd, [
                    'id_client' => $doz1->id_client,
                    'id_adresa' => $doz1->id_adresa,
                    'id_masina' => $doz1->id_masina,
                    'livrat' => true,
                    'achitat' => true,
                ])
            );
        }

        // ============================================================
        // 8. DOZATOARE cu FILTRE (Faza 4.3) — 3 scenarii distincte:
        //   - DEMO-FILTRU-1: la zi (data_urmatoare_mentenanta > 30 zile) — verde
        //   - DEMO-FILTRU-2: scadent in 15 zile — rosu
        //   - DEMO-FILTRU-3: expirat (-10 zile) — negru
        // Toate folosesc id_produs=55 (DOZATOR CU FILTRE - Custodie, fix din Faza 1.2).
        // Idempotent prin firstOrCreate pe `serie`.
        // ============================================================
        $this->command->info('Creez dozatoare cu filtre...');

        $filtreDemo = [
            [
                'serie' => 'DEMO-FILTRU-1',
                'id_client' => $aquaPro->id,
                'id_adresa' => $adrAquaProSediu->id,
                'id_masina' => $masiniCreate['Iveco 01']->id,
                'id_produs' => 55,
                'id_depozit' => $depozit->id,
                'tranzactie' => DozatorFiltre::TRANZACTIE_CUSTODIE,
                'data_instalare' => now()->subMonths(2)->toDateString(),
                'data_ultima_mentenanta' => null, // primara — nu a fost inca interventie
                'data_urmatoare_mentenanta' => now()->addMonths(10)->toDateString(), // ~10 luni — la zi
                'status' => DozatorFiltre::STATUS_ACTIV,
                'suma_garantie' => 250.00,
                'observatii' => 'DEMO — instalat acum 2 luni, schimb filtre la zi',
            ],
            [
                'serie' => 'DEMO-FILTRU-2',
                'id_client' => $hotelBoulevard->id,
                'id_adresa' => $adrHotel->id,
                'id_masina' => $masiniCreate['Iveco 02']->id,
                'id_produs' => 55,
                'id_depozit' => $depozit->id,
                'tranzactie' => DozatorFiltre::TRANZACTIE_CUSTODIE,
                'data_instalare' => now()->subMonths(11)->toDateString(),
                'data_ultima_mentenanta' => now()->subMonths(11)->toDateString(),
                'data_urmatoare_mentenanta' => now()->addDays(10)->toDateString(), // urgent (15 zile)
                'status' => DozatorFiltre::STATUS_ACTIV,
                'suma_garantie' => 250.00,
                'observatii' => 'DEMO — scadent in 10 zile, necesita reminder 15_zile',
            ],
            [
                'serie' => 'DEMO-FILTRU-3',
                'id_client' => $studioBirou->id,
                'id_adresa' => $adrStudio->id,
                'id_masina' => null,
                'id_produs' => 55,
                'id_depozit' => $depozit->id,
                'tranzactie' => DozatorFiltre::TRANZACTIE_CUMPARAT, // vandut
                'data_instalare' => now()->subMonths(13)->toDateString(),
                'data_ultima_mentenanta' => now()->subMonths(13)->toDateString(),
                'data_urmatoare_mentenanta' => now()->subDays(10)->toDateString(), // expirat
                'status' => DozatorFiltre::STATUS_ACTIV,
                'suma_garantie' => 0,
                'observatii' => 'DEMO — vandut, schimb filtre expirat de 10 zile',
            ],
        ];

        $filtreCreated = [];
        foreach ($filtreDemo as $datele) {
            $df = DozatorFiltre::firstOrCreate(
                ['serie' => $datele['serie']],
                $datele
            );
            $filtreCreated[$datele['serie']] = $df;

            // Mişcare de stoc CUSTODIE/OUT — idempotent prin tip_referinta+id_referinta
            $tipStoc = $datele['tranzactie'] === DozatorFiltre::TRANZACTIE_CUMPARAT
                ? Stoc::TIP_OUT
                : Stoc::TIP_CUSTODIE;

            Stoc::firstOrCreate(
                [
                    'tip_referinta' => Stoc::REF_DOZATOR_FILTRU,
                    'id_referinta' => $df->id,
                ],
                [
                    'id_produs' => $df->id_produs,
                    'id_depozit' => $df->id_depozit,
                    'cantitate' => 1,
                    'tip' => $tipStoc,
                    'data' => $df->data_instalare,
                    'observatii' => 'Filtru serie ' . $df->serie,
                ]
            );
        }

        // Istoric interventii: 1 pe DEMO-FILTRU-2 (mentenanta initiala la instalare)
        $filtru2 = $filtreCreated['DEMO-FILTRU-2'];
        $istoricDemo = [
            [
                'id_dozator_filtre' => $filtru2->id,
                'data_interventie' => now()->subMonths(11)->toDateString(),
                'data_urmatoare' => now()->addDays(10)->toDateString(),
                'pret' => 0,
                'observatii' => 'DEMO — interventie initiala la instalare filtru-2',
            ],
            [
                'id_dozator_filtre' => $filtreCreated['DEMO-FILTRU-3']->id,
                'data_interventie' => now()->subMonths(13)->toDateString(),
                'data_urmatoare' => now()->subDays(10)->toDateString(),
                'pret' => 0,
                'observatii' => 'DEMO — interventie initiala la instalare filtru-3 (expirata)',
            ],
        ];

        foreach ($istoricDemo as $iv) {
            // Cheie naturala: `observatii` (text unic per istoric demo).
            // Acelasi pattern ca la `vizite` — cast date pe data_interventie ar
            // duplica match-ul cu firstOrCreate pe data_interventie.
            $df = DozatorFiltre::find($iv['id_dozator_filtre']);
            DozatorFiltreIstoric::firstOrCreate(
                ['observatii' => $iv['observatii']],
                array_merge($iv, [
                    'id_client' => $df->id_client,
                    'id_masina' => $df->id_masina,
                ])
            );
        }

        // ============================================================
        // 9. CHELTUIELI (Faza 5.1) — 2 facturi DEMO cu mişcari de stoc IN:
        //   - DEMO-FACT-001: 2 linii (200 buc apa 19L + 100 buc apa 11L) — achitata
        //   - DEMO-FACT-002: 1 linie (10 dozatoare filtre) — neachitata
        // Idempotent prin `nr_factura`.
        // ============================================================
        $this->command->info('Creez facturi de cheltuieli...');

        $cheltuieliDemo = [
            [
                'nr_factura' => 'DEMO-FACT-001',
                'furnizor' => 'SC Aqua Production SRL',
                'id_depozit' => $depozit->id,
                'data' => now()->startOfMonth()->addDays(2)->toDateString(),
                'achitat' => true,
                'observatii' => 'DEMO — achizitie apa pentru luna curenta',
                'linii' => [
                    ['id_produs' => 45, 'cantitate' => 200, 'pret' => 8.00],  // 200 × 8 = 1600
                    ['id_produs' => 46, 'cantitate' => 100, 'pret' => 6.00],  // 100 × 6 = 600
                ],
                // total auto-calculat = 2200
            ],
            [
                'nr_factura' => 'DEMO-FACT-002',
                'furnizor' => 'SC FilterPro SRL',
                'id_depozit' => $depozit->id,
                'data' => now()->startOfMonth()->addDays(8)->toDateString(),
                'achitat' => false,
                'observatii' => 'DEMO — achizitie 10 dozatoare filtre, neachitata',
                'linii' => [
                    ['id_produs' => 55, 'cantitate' => 10, 'pret' => 250.00], // 10 × 250 = 2500
                ],
                // total auto-calculat = 2500
            ],
        ];

        $totalCheltuieliLinii = 0;
        foreach ($cheltuieliDemo as $datele) {
            $linii = $datele['linii'];
            unset($datele['linii']);

            // Total auto-calculat din linii
            $totalCalculat = array_sum(array_map(
                fn ($l) => (int) $l['cantitate'] * (float) $l['pret'],
                $linii
            ));
            $datele['total'] = $totalCalculat;

            $cheltuiala = Cheltuiala::firstOrCreate(
                ['nr_factura' => $datele['nr_factura']],
                $datele
            );

            // Linii — cheia naturala (id_cheltuiala, id_produs) suficienta
            // pentru ca DEMO are produse distincte per linie pe aceeasi factura
            foreach ($linii as $linie) {
                CheltuialaProdus::firstOrCreate(
                    [
                        'id_cheltuiala' => $cheltuiala->id,
                        'id_produs' => $linie['id_produs'],
                    ],
                    [
                        'cantitate' => $linie['cantitate'],
                        'pret' => $linie['pret'],
                    ]
                );

                // Mişcare de stoc IN — idempotent prin (tip_referinta, id_referinta)
                // dar necesita per-linie un Stoc separat. Folosim cheia naturala
                // (tip_referinta + id_referinta + id_produs) pe firstOrCreate
                // pentru ca o factura poate avea N linii cu produse distincte.
                Stoc::firstOrCreate(
                    [
                        'tip_referinta' => Stoc::REF_CHELTUIALA,
                        'id_referinta' => $cheltuiala->id,
                        'id_produs' => $linie['id_produs'],
                    ],
                    [
                        'id_depozit' => $cheltuiala->id_depozit,
                        'cantitate' => $linie['cantitate'],
                        'tip' => Stoc::TIP_IN,
                        'data' => $cheltuiala->data,
                        'observatii' => null,
                    ]
                );

                $totalCheltuieliLinii++;
            }
        }

        // ============================================================
        // 9. TEMPLATE GLOBAL CONTRACT + CONTRACT DEMO PER CLIENT (Faza 6.2)
        // ============================================================
        $this->command->info('Configurez template global contract si generez contracte demo...');

        // Template global — idempotent prin cheia UNIQUE 'contract_template_html'.
        // Daca exista deja in DB (admin l-a editat), il pastram intact.
        if (! SetariPlatforma::where('cheie', SetariPlatforma::CHEIE_CONTRACT_TEMPLATE)->exists()) {
            SetariPlatforma::set(
                SetariPlatforma::CHEIE_CONTRACT_TEMPLATE,
                ContracteService::templateImplicit()
            );
        }

        // Generam contracte pentru clientii principali (idempotent prin UNIQUE id_client).
        $contracteDemo = 0;
        foreach ([$aquaPro, $hotelBoulevard, $studioBirou] as $clientPentruContract) {
            ContractClient::firstOrCreate(
                ['id_client' => $clientPentruContract->id],
                [
                    'continut_html' => ContracteService::inlocuiestePlaceholdere(
                        ContracteService::templateGlobal(),
                        $clientPentruContract
                    ),
                ]
            );
            $contracteDemo++;
        }

        $this->command->info('OK: ' . count($comenzi) . ' comenzi + ' . count($istoricAbonamente) . ' istoric abonamente + ' . count($probleme) . ' probleme de azi (' . $azi . ') + ' . count($comenziPortal) . ' comenzi portal in asteptare + ' . count($dozatoareDemo) . ' dozatoare bidoane (' . count($vizitepDemo) . ' vizite) + ' . count($filtreDemo) . ' dozatoare filtre (' . count($istoricDemo) . ' interventii) + ' . count($cheltuieliDemo) . ' facturi cheltuieli (' . $totalCheltuieliLinii . ' linii / mişcari IN) + ' . $contracteDemo . ' contracte (template global setat).');
        $this->command->info('Login: admin@flotamuntenia.test / parola123');
        $this->command->info('Naviga la: /comenzi/lista-zilnica, /comenzi/aprobare, /dozatoare?tip=bidoane, /dozatoare?tip=filtre, /cheltuieli sau /setari/contract-template');
    }
}
