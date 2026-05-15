<?php

namespace Tests\Feature\Comenzi;

use App\Livewire\Comenzi\Form;
use App\Models\Car;
use App\Models\Client;
use App\Models\AdresaLivrare;
use App\Models\Comanda;
use App\Models\CostCategory;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Models\Produs;
use App\Models\Tva;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormLiniiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Client $client;
    private AdresaLivrare $adresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['tip' => User::TIP_ADMIN]);

        $tva = Tva::create(['valoare' => 9, 'denumire' => '9%', 'activ' => true]);
        $cat = CostCategory::create(['denumire' => 'Apa imbuteliata', 'activ' => true]);

        // Produse fixe cu ID-urile din business logic (45=19L, 46=11L)
        CostProduct::forceCreate(['id' => 45, 'id_category' => $cat->id, 'id_tva' => $tva->id, 'denumire' => 'APA PLATA 19L', 'pret' => 0, 'activ' => true]);
        CostProduct::forceCreate(['id' => 46, 'id_category' => $cat->id, 'id_tva' => $tva->id, 'denumire' => 'APA PLATA 11L', 'pret' => 0, 'activ' => true]);

        $this->client = Client::create([
            'cod_client' => 'TEST-001',
            'client' => 1,
            'denumire' => 'Client Test SRL',
            'reziliat' => false,
        ]);

        $this->adresa = AdresaLivrare::create([
            'id_client' => $this->client->id,
            'denumire' => 'Sediu principal',
            'oras' => 'Bucuresti',
            'strada' => 'Test',
            'activ' => true,
        ]);
    }

    private function creeazaProdusCfg(array $atribute = []): Produs
    {
        return Produs::create(array_merge([
            'id_adresa'            => $this->adresa->id,
            'id_client'            => $this->client->id,
            'abonament'            => Produs::TIP_ABONAMENT,
            'nr_bidoane'           => 3,
            'nr_bidoane_11l'       => 1,
            'pret'                 => 100.00,
            'pret_11l'             => 0,
            'pret_suplimentar_19l' => null,
            'pret_suplimentar_11l' => null,
        ], $atribute));
    }

    // ─── Cerința 1: Abonament → bidoane reale ──────────────────────────────────

    #[Test]
    public function abonament_genereaza_linii_cu_bidoane_reale_si_pret_distribuit(): void
    {
        // 3×19L + 1×11L, total 100 RON → 100/4 = 25 RON/bidon
        $this->creeazaProdusCfg(['nr_bidoane' => 3, 'nr_bidoane_11l' => 1, 'pret' => 100.00]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $comp->assertSet('tipComanda', Comanda::TIP_ABONAMENT);
        $linii = $comp->get('linii');

        $this->assertCount(2, $linii);
        $this->assertEquals(45, $linii[0]['id_produs']);
        $this->assertEquals(3, $linii[0]['cantitate']);
        $this->assertEquals('25', $linii[0]['pret']);
        $this->assertEquals(46, $linii[1]['id_produs']);
        $this->assertEquals(1, $linii[1]['cantitate']);
        $this->assertEquals('25', $linii[1]['pret']);
    }

    #[Test]
    public function abonament_pret_per_bidon_calculat_corect(): void
    {
        // 60 RON / (2×19L + 2×11L) = 15 RON/bidon, total = 60
        $this->creeazaProdusCfg(['nr_bidoane' => 2, 'nr_bidoane_11l' => 2, 'pret' => 60.00]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $linii = $comp->get('linii');
        $this->assertCount(2, $linii);
        $this->assertEquals('15', $linii[0]['pret']);
        $this->assertEquals(2, $linii[0]['cantitate']);
        $this->assertEquals('15', $linii[1]['pret']);
        $this->assertEquals(2, $linii[1]['cantitate']);

        // Total din linii: 2×15 + 2×15 = 60
        $total = collect($linii)->sum(fn($l) => $l['cantitate'] * (float) $l['pret']);
        $this->assertEquals(60.0, $total);
    }

    #[Test]
    public function abonament_doar_19l_fara_11l(): void
    {
        // Abonament fara bidoane 11L — o singura linie 19L
        $this->creeazaProdusCfg(['nr_bidoane' => 4, 'nr_bidoane_11l' => 0, 'pret' => 80.00]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $linii = $comp->get('linii');
        $this->assertCount(1, $linii);
        $this->assertEquals(45, $linii[0]['id_produs']);
        $this->assertEquals(4, $linii[0]['cantitate']);
        $this->assertEquals('20', $linii[0]['pret']); // 80/4 = 20
    }

    #[Test]
    public function abonament_fallback_linie_custom_cand_ambele_bidoane_zero(): void
    {
        // Config incompleta — nr_bidoane=0 si nr_bidoane_11l=0
        $this->creeazaProdusCfg([
            'nr_bidoane'       => 0,
            'nr_bidoane_11l'   => 0,
            'pret'             => 50.00,
            'denumire_abonament' => 'Pachet Special',
        ]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $linii = $comp->get('linii');
        $this->assertCount(1, $linii);
        $this->assertNull($linii[0]['id_produs']);
        $this->assertEquals('Pachet Special', $linii[0]['denumire']);
        $this->assertEquals(1, $linii[0]['cantitate']);
        $this->assertEquals('50', $linii[0]['pret']);
    }

    // ─── Cerința 1: Consum suplimentar → pret_suplimentar ──────────────────────

    #[Test]
    public function consum_suplimentar_foloseste_pret_suplimentar(): void
    {
        $this->creeazaProdusCfg([
            'pret_suplimentar_19l' => 15.00,
            'pret_suplimentar_11l' => 12.00,
        ]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id)
            ->set('tipComanda', Comanda::TIP_CONSUM_SUPLIMENTAR);

        $linii = $comp->get('linii');
        $this->assertCount(2, $linii);
        $this->assertEquals(45, $linii[0]['id_produs']);
        $this->assertEquals('15', $linii[0]['pret']);
        $this->assertEquals(46, $linii[1]['id_produs']);
        $this->assertEquals('12', $linii[1]['pret']);
    }

    #[Test]
    public function consum_suplimentar_fallback_la_pret_bucata_cand_suplimentar_null(): void
    {
        $this->creeazaProdusCfg([
            'pret'                 => 18.00,
            'pret_11l'             => 14.00,
            'pret_suplimentar_19l' => null,
            'pret_suplimentar_11l' => null,
        ]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id)
            ->set('tipComanda', Comanda::TIP_CONSUM_SUPLIMENTAR);

        $linii = $comp->get('linii');
        $this->assertEquals('18', $linii[0]['pret']);
        $this->assertEquals('14', $linii[1]['pret']);
    }

    // ─── Cerința 2: Reload la schimbare tip comandă ─────────────────────────────

    #[Test]
    public function schimbare_tip_reincarca_liniile_din_config_adresa(): void
    {
        $this->creeazaProdusCfg([
            'nr_bidoane'           => 3,
            'nr_bidoane_11l'       => 1,
            'pret'                 => 100.00,
            'pret_suplimentar_19l' => 20.00,
            'pret_suplimentar_11l' => 16.00,
        ]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        // Initial: abonament → bidoane cu preț distribuit (100/4=25)
        $this->assertEquals('abonament', $comp->get('tipComanda'));
        $this->assertEquals('25', $comp->get('linii.0.pret'));

        // Schimb în consum suplimentar → prețuri suplimentare
        $comp->set('tipComanda', Comanda::TIP_CONSUM_SUPLIMENTAR);
        $this->assertEquals('20', $comp->get('linii.0.pret'));
        $this->assertEquals('16', $comp->get('linii.1.pret'));

        // Revin la abonament → bidoane reale cu preț distribuit
        $comp->set('tipComanda', Comanda::TIP_ABONAMENT);
        $this->assertEquals(3, $comp->get('linii.0.cantitate'));
        $this->assertEquals('25', $comp->get('linii.0.pret'));
    }

    #[Test]
    public function schimbare_tip_fara_adresa_selectata_nu_modifica_liniile(): void
    {
        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class);

        $liniiInitiale = $comp->get('linii');

        $comp->set('tipComanda', Comanda::TIP_CONSUM_SUPLIMENTAR);

        // Fara adresa — linii raman la starea anterioara
        $this->assertEquals($liniiInitiale, $comp->get('linii'));
    }

    // ─── Cerința 3: Cantități negative ─────────────────────────────────────────

    #[Test]
    public function total_calculat_cu_discount_negativ(): void
    {
        // 5 bidoane × 20 RON = 100, minus discount 1 × 20 RON = 80 RON net
        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('linii', [
                ['id_produs' => 45, 'denumire' => 'APA PLATA 19L', 'cantitate' => 5, 'pret' => '20'],
                ['id_produs' => null, 'denumire' => 'Discount',      'cantitate' => -1, 'pret' => '20'],
            ]);

        $linii = $comp->get('linii');
        $total = collect($linii)->sum(fn($l) => $l['cantitate'] * (float) $l['pret']);
        $this->assertEquals(80.0, $total);
    }

    #[Test]
    public function validarea_accepta_cantitate_negativa(): void
    {
        $deposit = Deposit::create(['denumire' => 'Depou', 'adresa' => '', 'activ' => true]);
        Car::create(['denumire' => 'M1', 'nr_inmatriculare' => 'B-01-TST', 'id_depozit' => $deposit->id, 'culoare' => '#000000', 'activ' => true]);

        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id)
            ->set('tipComanda', Comanda::TIP_FARA_ABONAMENT)
            ->set('idModalitatePlata', 1)
            ->set('dataLivrare', now()->toDateString())
            ->set('linii', [
                ['id_produs' => 45, 'denumire' => 'APA PLATA 19L',    'cantitate' => 3,  'pret' => '20'],
                ['id_produs' => null, 'denumire' => 'Discount fidel', 'cantitate' => -1, 'pret' => '20'],
            ])
            ->call('salveaza')
            ->assertHasNoErrors(['linii.0.cantitate', 'linii.1.cantitate']);
    }

    #[Test]
    public function validarea_respinge_pret_negativ(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('linii', [
                ['id_produs' => 45, 'denumire' => 'APA PLATA 19L', 'cantitate' => 1, 'pret' => '-5'],
            ])
            ->call('salveaza')
            ->assertHasErrors(['linii.0.pret']);
    }

    // ─── Depozit implicit ───────────────────────────────────────────────────────

    #[Test]
    public function mount_preumple_idDepozit_cu_depozitul_implicit(): void
    {
        $depozit = Deposit::create(['denumire' => 'ENAQUA', 'adresa' => '', 'activ' => true, 'implicit' => true]);

        $comp = Livewire::actingAs($this->admin)->test(Form::class);

        $this->assertEquals($depozit->id, $comp->get('idDepozit'));
    }

    #[Test]
    public function mount_lasa_idDepozit_null_cand_nu_exista_depozit_implicit(): void
    {
        // Niciun depozit cu implicit=true
        $comp = Livewire::actingAs($this->admin)->test(Form::class);

        $this->assertNull($comp->get('idDepozit'));
    }

    #[Test]
    public function selectare_adresa_fara_depozit_in_config_pastreaza_depozitul_implicit(): void
    {
        $depozit = Deposit::create(['denumire' => 'ENAQUA', 'adresa' => '', 'activ' => true, 'implicit' => true]);
        // Adresa are config produs fara id_depozit
        $this->creeazaProdusCfg(['id_depozit' => null]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $this->assertEquals($depozit->id, $comp->get('idDepozit'));
    }

    #[Test]
    public function selectare_adresa_cu_depozit_specific_il_foloseste_pe_acela(): void
    {
        $implicit  = Deposit::create(['denumire' => 'ENAQUA', 'adresa' => '', 'activ' => true, 'implicit' => true]);
        $specific  = Deposit::create(['denumire' => 'Depozit Vest', 'adresa' => '', 'activ' => true, 'implicit' => false]);
        $this->creeazaProdusCfg(['id_depozit' => $specific->id]);

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $this->assertEquals($specific->id, $comp->get('idDepozit'));
    }

    #[Test]
    public function golire_adresa_reseteaza_idDepozit_la_implicit(): void
    {
        $depozit = Deposit::create(['denumire' => 'ENAQUA', 'adresa' => '', 'activ' => true, 'implicit' => true]);
        $this->creeazaProdusCfg();

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        // Golim adresa
        $comp->set('idAdresa', '');

        $this->assertEquals($depozit->id, $comp->get('idDepozit'));
    }

    #[Test]
    public function adresa_fara_config_produs_pastreaza_depozitul_implicit(): void
    {
        $depozit = Deposit::create(['denumire' => 'ENAQUA', 'adresa' => '', 'activ' => true, 'implicit' => true]);
        // Adresa fara produs config deloc

        $comp = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('idClient', $this->client->id)
            ->set('idAdresa', $this->adresa->id);

        $this->assertEquals($depozit->id, $comp->get('idDepozit'));
    }
}
