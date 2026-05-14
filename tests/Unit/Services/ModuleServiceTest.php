<?php

namespace Tests\Unit\Services;

use App\Models\SetariPlatforma;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ModuleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleService::invalidateCache();
    }

    public function test_modul_este_activ_by_default_cand_nu_exista_in_db(): void
    {
        // Fara nicio inregistrare in DB, modulul trebuie sa fie considerat activ
        $this->assertTrue(ModuleService::isActive('modul_test_inexistent'));
    }

    public function test_modul_activ_cand_valoare_1(): void
    {
        SetariPlatforma::set('modul_portal_client', '1');
        ModuleService::invalidateCache();

        $this->assertTrue(ModuleService::isActive(SetariPlatforma::MODUL_PORTAL_CLIENT));
    }

    public function test_modul_inactiv_cand_valoare_0(): void
    {
        SetariPlatforma::set('modul_portal_client', '0');
        ModuleService::invalidateCache();

        $this->assertFalse(ModuleService::isActive(SetariPlatforma::MODUL_PORTAL_CLIENT));
    }

    public function test_toggle_dezactiveaza_modulul_activ(): void
    {
        SetariPlatforma::set('modul_comenzi_rapide', '1');
        ModuleService::invalidateCache();

        ModuleService::toggle('modul_comenzi_rapide', false);

        $this->assertFalse(ModuleService::isActive('modul_comenzi_rapide'));
        $this->assertDatabaseHas('setari_platforma', [
            'cheie'  => 'modul_comenzi_rapide',
            'valoare' => '0',
        ]);
    }

    public function test_toggle_activeaza_modulul_inactiv(): void
    {
        SetariPlatforma::set('modul_comenzi_rapide', '0');
        ModuleService::invalidateCache();

        ModuleService::toggle('modul_comenzi_rapide', true);

        $this->assertTrue(ModuleService::isActive('modul_comenzi_rapide'));
        $this->assertDatabaseHas('setari_platforma', [
            'cheie'  => 'modul_comenzi_rapide',
            'valoare' => '1',
        ]);
    }

    public function test_toggle_invalideaza_cache_ul(): void
    {
        SetariPlatforma::set('modul_probleme', '1');
        // Forteaza popularea cache-ului
        ModuleService::isActive('modul_probleme');

        ModuleService::toggle('modul_probleme', false);

        // Dupa toggle, cache-ul trebuie sa fie curat
        $this->assertFalse(Cache::has('module_settings'));
        $this->assertFalse(ModuleService::isActive('modul_probleme'));
    }

    public function test_toate_modulele_active_returneaza_array_complet(): void
    {
        // Seed toate modulele cu valoarea 1
        foreach (ModuleService::definitiiModule() as $cheie => $def) {
            SetariPlatforma::set($cheie, '1');
        }
        ModuleService::invalidateCache();

        $rezultat = ModuleService::toateModuleleActive();

        $this->assertCount(13, $rezultat);
        foreach ($rezultat as $activ) {
            $this->assertTrue($activ);
        }
    }

    public function test_definitii_module_returneaza_13_module(): void
    {
        $definitii = ModuleService::definitiiModule();
        $this->assertCount(13, $definitii);
    }

    public function test_fiecare_definitie_are_campurile_obligatorii(): void
    {
        foreach (ModuleService::definitiiModule() as $cheie => $def) {
            $this->assertArrayHasKey('slug', $def, "Modul {$cheie} nu are 'slug'");
            $this->assertArrayHasKey('cheie', $def, "Modul {$cheie} nu are 'cheie'");
            $this->assertArrayHasKey('nume', $def, "Modul {$cheie} nu are 'nume'");
            $this->assertArrayHasKey('descriere', $def, "Modul {$cheie} nu are 'descriere'");
            $this->assertArrayHasKey('blocheaza', $def, "Modul {$cheie} nu are 'blocheaza'");
            $this->assertArrayHasKey('avertizare', $def, "Modul {$cheie} nu are 'avertizare'");
        }
    }

    public function test_cache_ul_este_populat_la_prima_citire(): void
    {
        SetariPlatforma::set('modul_stoc', '1');
        ModuleService::invalidateCache();

        $this->assertFalse(Cache::has('module_settings'));
        ModuleService::isActive('modul_stoc');
        $this->assertTrue(Cache::has('module_settings'));
    }

    public function test_modul_harti_inactiv_dupa_toggle(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_HARTI, '1');
        ModuleService::invalidateCache();
        $this->assertTrue(ModuleService::isActive(SetariPlatforma::MODUL_HARTI));

        ModuleService::toggle(SetariPlatforma::MODUL_HARTI, false);
        $this->assertFalse(ModuleService::isActive(SetariPlatforma::MODUL_HARTI));

        // Reactivare
        ModuleService::toggle(SetariPlatforma::MODUL_HARTI, true);
        $this->assertTrue(ModuleService::isActive(SetariPlatforma::MODUL_HARTI));
    }
}
