<?php

namespace Tests\Feature\Setari;

use App\Livewire\Setari\Utilizatori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Testează că adminii obișnuiți (tip=1) nu pot vedea, crea sau edita
 * conturi SuperAdmin (tip=100). Numai SuperAdminul (tip=100) poate gestiona
 * alte conturi SuperAdmin.
 */
class UtilizatoriSuperadminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superadmin;
    private User $superadminTinta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'tip'      => User::TIP_ADMIN,
            'confirmat' => true,
        ]);
        $this->superadmin = User::factory()->create([
            'tip'      => User::TIP_SUPERADMIN,
            'confirmat' => true,
        ]);
        $this->superadminTinta = User::factory()->create([
            'tip'      => User::TIP_SUPERADMIN,
            'confirmat' => true,
        ]);
    }

    // ===== LISTA UTILIZATORI =====

    public function test_admin_nu_vede_conturi_superadmin_in_lista(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Utilizatori::class)
            ->assertViewHas('utilizatori', function ($utilizatori) {
                return $utilizatori->doesntContain(
                    fn ($u) => $u->tip === User::TIP_SUPERADMIN
                );
            });
    }

    public function test_superadmin_vede_toate_conturile_inclusiv_superadmin(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(Utilizatori::class)
            ->assertViewHas('utilizatori', function ($utilizatori) {
                return $utilizatori->contains(
                    fn ($u) => $u->tip === User::TIP_SUPERADMIN
                );
            });
    }

    // ===== ROLURI DISPONIBILE =====

    public function test_admin_nu_vede_rolul_superadmin_in_dropdown(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Utilizatori::class);
        $roluri = $component->instance()->roluriDisponibile();

        $this->assertArrayNotHasKey(User::TIP_SUPERADMIN, $roluri);
    }

    public function test_superadmin_vede_rolul_superadmin_in_dropdown(): void
    {
        $component = Livewire::actingAs($this->superadmin)->test(Utilizatori::class);
        $roluri = $component->instance()->roluriDisponibile();

        $this->assertArrayHasKey(User::TIP_SUPERADMIN, $roluri);
    }

    // ===== CREARE CONT SUPERADMIN =====

    public function test_admin_nu_poate_crea_cont_superadmin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Utilizatori::class)
            ->set('name', 'Hacker Test')
            ->set('email', 'hack@test.com')
            ->set('password', 'parola123')
            ->set('tip', User::TIP_SUPERADMIN)
            ->call('salveaza')
            ->assertHasErrors(['tip']);

        $this->assertDatabaseMissing('users', ['email' => 'hack@test.com']);
    }

    public function test_superadmin_poate_crea_alt_cont_superadmin(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(Utilizatori::class)
            ->set('name', 'Superadmin Nou')
            ->set('email', 'superadmin.nou@test.com')
            ->set('password', 'parola123')
            ->set('tip', User::TIP_SUPERADMIN)
            ->set('confirmat', true)
            ->call('salveaza')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'superadmin.nou@test.com',
            'tip'   => User::TIP_SUPERADMIN,
        ]);
    }

    // ===== EDITARE CONT SUPERADMIN =====

    public function test_admin_nu_poate_deschide_editarea_unui_superadmin(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Utilizatori::class);
        $component->call('editeaza', $this->superadminTinta->id);
        $this->assertFalse($component->get('modalDeschis'));
    }

    public function test_admin_nu_poate_salva_modificari_pe_cont_superadmin(): void
    {
        $numeOriginal = $this->superadminTinta->name;

        // Simulam un admin care incearca sa editeze direct (ocolind editeaza())
        // Validarea respinge tip=100 pentru un admin (nu e in roluriDisponibile)
        Livewire::actingAs($this->admin)
            ->test(Utilizatori::class)
            ->set('editandId', $this->superadminTinta->id)
            ->set('name', 'Modificat Ilegal')
            ->set('email', $this->superadminTinta->email)
            ->set('tip', User::TIP_SUPERADMIN)
            ->set('password', '')
            ->set('confirmat', true)
            ->call('salveaza')
            ->assertHasErrors(['tip']);

        // Contul superadmin nu a fost modificat
        $this->assertDatabaseHas('users', [
            'id'   => $this->superadminTinta->id,
            'name' => $numeOriginal,
        ]);
    }

    public function test_superadmin_poate_edita_alt_cont_superadmin(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(Utilizatori::class)
            ->call('editeaza', $this->superadminTinta->id)
            ->assertSet('modalDeschis', true)
            ->assertSet('editandId', $this->superadminTinta->id);
    }

    // ===== TOGGLE CONFIRMAT =====

    public function test_admin_nu_poate_dezactiva_cont_superadmin(): void
    {
        $this->assertTrue($this->superadminTinta->confirmat);

        Livewire::actingAs($this->admin)
            ->test(Utilizatori::class)
            ->call('comutaConfirmat', $this->superadminTinta->id)
            ->assertHasNoErrors();

        // Starea confirmat nu s-a schimbat
        $this->assertTrue($this->superadminTinta->fresh()->confirmat);
    }

    public function test_superadmin_poate_dezactiva_alt_cont_superadmin(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(Utilizatori::class)
            ->call('comutaConfirmat', $this->superadminTinta->id)
            ->assertHasNoErrors();

        $this->assertFalse($this->superadminTinta->fresh()->confirmat);
    }
}
