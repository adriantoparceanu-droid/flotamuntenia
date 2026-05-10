<?php

namespace App\Livewire\Portal;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.3 — „Contul meu" (read-only).
 *
 * Afiseaza datele clientului si lista adreselor de livrare. Modificarile
 * se fac prin admin (in MVP) — afisam un mesaj cu datele de contact.
 */
#[Layout('layouts.portal')]
class Cont extends Component
{
    public function render()
    {
        $idClient = auth()->user()->id_client;

        $client = $idClient
            ? Client::with(['adrese' => fn ($q) => $q->where('activ', true)->orderBy('denumire')])->find($idClient)
            : null;

        return view('livewire.portal.cont', [
            'client' => $client,
        ]);
    }
}
