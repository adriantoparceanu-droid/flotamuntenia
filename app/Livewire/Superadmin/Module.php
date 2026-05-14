<?php

namespace App\Livewire\Superadmin;

use App\Models\SetariPlatforma;
use App\Services\ModuleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pagina de gestionare module pentru SuperAdmin (/superadmin/module).
 *
 * Afișează un grid cu carduri pentru fiecare modul opțional.
 * Fiecare card arată: nume, descriere, ce blochează, avertizări de dependență
 * și un buton toggle cu confirmare pentru dezactivare.
 *
 * Acces: exclusiv tip=100 (SUPERADMIN) — grupul de rute e protejat prin
 * middleware rol:superadmin (NU rol:admin,superadmin).
 */
#[Layout('layouts.app')]
class Module extends Component
{
    /**
     * Toggle modul: activare sau dezactivare.
     *
     * La dezactivare, un confirm Alpine.js protejează acțiunea
     * (wire:confirm nu e suficient pentru mesaje lungi — folosim
     * confirm nativ din JS via @click pe buton).
     */
    public function toggle(string $cheie): void
    {
        // Verificăm că cheia e una dintre cheile de modul valide
        $cheiiValide = collect(ModuleService::definitiiModule())->pluck('cheie')->all();
        if (! in_array($cheie, $cheiiValide, true)) {
            session()->flash('eroare', 'Cheie modul invalidă.');
            return;
        }

        $actualaActiv = ModuleService::isActive($cheie);
        ModuleService::toggle($cheie, ! $actualaActiv);

        $definitie = ModuleService::definitiiModule()[$cheie];
        $stare = ! $actualaActiv ? 'activat' : 'dezactivat';
        session()->flash('mesaj', 'Modulul "' . $definitie['nume'] . '" a fost ' . $stare . '.');
    }

    public function render()
    {
        $module = collect(ModuleService::definitiiModule())
            ->map(function (array $def) {
                return array_merge($def, [
                    'activ' => ModuleService::isActive($def['cheie']),
                ]);
            })
            ->all();

        return view('livewire.superadmin.module', [
            'module' => $module,
        ]);
    }
}
