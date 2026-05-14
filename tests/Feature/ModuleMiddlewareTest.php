<?php

namespace Tests\Feature;

use App\Models\SetariPlatforma;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testează că middleware-ul EnsureModuleActive blochează/permite
 * accesul la rutele opționale în funcție de statusul modulului.
 */
class ModuleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'tip'      => User::TIP_ADMIN,
            'confirmat' => true,
        ]);
        ModuleService::invalidateCache();
    }

    // ===== COMENZI RAPIDE =====

    public function test_comenzi_rapide_accesibil_cand_modul_activ(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '1');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('comenzi-rapide.index'))
            ->assertStatus(200);
    }

    public function test_comenzi_rapide_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('comenzi-rapide.index'))
            ->assertStatus(403);
    }

    public function test_comenzi_rapide_returneaza_view_modul_indisponibil(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('comenzi-rapide.index'))
            ->assertViewIs('modul-indisponibil');
    }

    // ===== PROBLEME =====

    public function test_probleme_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_PROBLEME, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('probleme.index'))
            ->assertStatus(403);
    }

    // ===== CHELTUIELI (Stoc & Costuri) =====

    public function test_cheltuieli_returneaza_403_cand_modul_stoc_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_STOC, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('cheltuieli.index'))
            ->assertStatus(403);
    }

    public function test_cheltuieli_accesibil_cand_modul_stoc_activ(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_STOC, '1');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('cheltuieli.index'))
            ->assertStatus(200);
    }

    // ===== RAPOARTE =====

    public function test_rapoarte_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_RAPOARTE, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('rapoarte.stoc'))
            ->assertStatus(403);
    }

    // ===== SETARI FACTURARE =====

    public function test_setari_facturare_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_FACTURARE, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('setari.facturare'))
            ->assertStatus(403);
    }

    // ===== SETARI EMAIL =====

    public function test_setari_template_email_returneaza_403_cand_modul_email_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_EMAIL, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('setari.template-email'))
            ->assertStatus(403);
    }

    public function test_setari_smtp_returneaza_403_cand_modul_email_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_EMAIL, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('setari.smtp'))
            ->assertStatus(403);
    }

    // ===== SETARI CRON =====

    public function test_setari_cron_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_CRON, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('setari.cron'))
            ->assertStatus(403);
    }

    // ===== CONTRACT TEMPLATE =====

    public function test_setari_contract_template_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_CONTRACTE, '0');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('setari.contract-template'))
            ->assertStatus(403);
    }

    // ===== PORTAL CLIENT (rute autentificate) =====

    public function test_portal_client_returneaza_403_cand_modul_dezactivat(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_PORTAL_CLIENT, '0');
        ModuleService::invalidateCache();

        $clientUser = User::factory()->create([
            'tip'      => User::TIP_CLIENT,
            'confirmat' => true,
        ]);

        $this->actingAs($clientUser)
            ->get(route('portal.comenzi.index'))
            ->assertStatus(403);
    }

    // ===== REACTIVARE — datele raman intacte =====

    public function test_reactivare_modul_restaureaza_accesul(): void
    {
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '0');
        ModuleService::invalidateCache();

        // Dezactivat
        $this->actingAs($this->admin)
            ->get(route('comenzi-rapide.index'))
            ->assertStatus(403);

        // Reactivat
        SetariPlatforma::set(SetariPlatforma::MODUL_COMENZI_RAPIDE, '1');
        ModuleService::invalidateCache();

        $this->actingAs($this->admin)
            ->get(route('comenzi-rapide.index'))
            ->assertStatus(200);
    }

    // ===== RUTE CORE (fara middleware modul) =====

    public function test_dashboard_este_intotdeauna_accesibil(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertStatus(200);
    }

    public function test_clienti_sunt_intotdeauna_accesibili(): void
    {
        $this->actingAs($this->admin)
            ->get(route('clienti.index'))
            ->assertStatus(200);
    }

    public function test_comenzi_sunt_intotdeauna_accesibile(): void
    {
        $this->actingAs($this->admin)
            ->get(route('comenzi.index'))
            ->assertStatus(200);
    }
}
