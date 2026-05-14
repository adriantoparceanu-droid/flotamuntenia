<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\Module;
use App\Models\SetariPlatforma;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Testează componenta Livewire de gestionare module accesibilă SuperAdminului.
 */
class GestionareModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superadmin = User::factory()->create([
            'tip'      => User::TIP_SUPERADMIN,
            'confirmat' => true,
        ]);
        $this->admin = User::factory()->create([
            'tip'      => User::TIP_ADMIN,
            'confirmat' => true,
        ]);
        ModuleService::invalidateCache();
    }

    // ===== ACCES LA RUTA =====

    public function test_superadmin_poate_accesa_pagina_module(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('superadmin.module'))
            ->assertStatus(200);
    }

    public function test_admin_nu_poate_accesa_pagina_module(): void
    {
        $this->actingAs($this->admin)
            ->get(route('superadmin.module'))
            ->assertStatus(403);
    }

    public function test_sofer_nu_poate_accesa_pagina_module(): void
    {
        $sofer = User::factory()->create(['tip' => User::TIP_SOFER, 'confirmat' => true]);

        $this->actingAs($sofer)
            ->get(route('superadmin.module'))
            ->assertStatus(403);
    }

    public function test_utilizator_neautentificat_este_redirectat_la_login(): void
    {
        $this->get(route('superadmin.module'))
            ->assertRedirect(route('login'));
    }

    // ===== TOGGLE MODULE =====

    public function test_superadmin_poate_dezactiva_un_modul(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '1');
        ModuleService::invalidateCache();

        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->call('toggle', SetariPlatforma::MODUL_COMENZI_RAPIDE)
            ->assertHasNoErrors();

        $this->assertFalse(ModuleService::isActive(SetariPlatforma::MODUL_COMENZI_RAPIDE));
        $this->assertDatabaseHas('setari_platforma', [
            'cheie'  => SetariPlatforma::MODUL_COMENZI_RAPIDE,
            'valoare' => '0',
        ]);
    }

    public function test_superadmin_poate_reactiva_un_modul(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '0');
        ModuleService::invalidateCache();

        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->call('toggle', SetariPlatforma::MODUL_COMENZI_RAPIDE)
            ->assertHasNoErrors();

        $this->assertTrue(ModuleService::isActive(SetariPlatforma::MODUL_COMENZI_RAPIDE));
    }

    public function test_toggle_cu_cheie_invalida_nu_modifica_db(): void
    {
        // O cheie necunoscuta nu trebuie sa creeze nicio inregistrare in DB
        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->call('toggle', 'modul_inexistent_hack')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('setari_platforma', [
            'cheie' => 'modul_inexistent_hack',
        ]);
    }

    public function test_toggle_dezactivare_salveaza_valoarea_0_in_db(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_PROBLEME, '1');
        ModuleService::invalidateCache();

        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->call('toggle', SetariPlatforma::MODUL_PROBLEME)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('setari_platforma', [
            'cheie'  => SetariPlatforma::MODUL_PROBLEME,
            'valoare' => '0',
        ]);
    }

    public function test_toggle_activare_salveaza_valoarea_1_in_db(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_PROBLEME, '0');
        ModuleService::invalidateCache();

        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->call('toggle', SetariPlatforma::MODUL_PROBLEME)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('setari_platforma', [
            'cheie'  => SetariPlatforma::MODUL_PROBLEME,
            'valoare' => '1',
        ]);
    }

    // ===== RENDER =====

    public function test_pagina_afiseaza_toate_cele_13_module(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->assertViewHas('module', function ($module) {
                return count($module) === 13;
            });
    }

    public function test_fiecare_modul_are_campul_activ_in_view(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_STOC, '0');
        ModuleService::invalidateCache();

        Livewire::actingAs($this->superadmin)
            ->test(Module::class)
            ->assertViewHas('module', function ($module) {
                $stoc = $module[SetariPlatforma::MODUL_STOC] ?? null;
                return $stoc !== null && $stoc['activ'] === false;
            });
    }

    // ===== TOGGLE MULTIPLU =====

    public function test_toggle_multiplu_sucesiv_functioneaza_corect(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_RAPOARTE, '1');
        ModuleService::invalidateCache();

        $component = Livewire::actingAs($this->superadmin)->test(Module::class);

        // Dezactivare
        $component->call('toggle', SetariPlatforma::MODUL_RAPOARTE);
        ModuleService::invalidateCache();
        $this->assertFalse(ModuleService::isActive(SetariPlatforma::MODUL_RAPOARTE));

        // Reactivare
        $component->call('toggle', SetariPlatforma::MODUL_RAPOARTE);
        ModuleService::invalidateCache();
        $this->assertTrue(ModuleService::isActive(SetariPlatforma::MODUL_RAPOARTE));
    }
}
