<?php

namespace App\Livewire\Setari;

use App\Models\SetariPlatforma;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.8 — Pagina pentru configurare cron jobs din cPanel.
 *
 * Afiseaza tokenul curent + URL-urile complete pentru copy-paste in cPanel
 * cron jobs. Permite regenerarea tokenului (invalideaza cron-urile vechi —
 * trebuie reactualizate in cPanel cu noul URL).
 *
 * Tokenul e generat lazy la mount daca lipseste.
 */
#[Layout('layouts.app')]
class Cron extends Component
{
    public string $token = '';

    public ?string $mesaj = null;

    public function mount(): void
    {
        $this->token = SetariPlatforma::obtineCronToken();
    }

    /**
     * Regenereaza tokenul. ATENTIE: cron-urile vechi din cPanel cu URL-ul
     * vechi vor primi 404 pana la actualizare. Protejat cu wire:confirm in UI.
     */
    public function regenereaza(): void
    {
        $this->token = SetariPlatforma::regenereazaCronToken();
        $this->mesaj = 'Token regenerat. Actualizeaza URL-urile in cPanel cron jobs — cele vechi au devenit invalide.';
    }

    public function render()
    {
        $base = url('/cron/' . $this->token);

        $cronJobs = [
            [
                'job' => 'igienizari:zilnice',
                'denumire' => 'Igienizari dozatoare bidoane',
                'descriere' => 'Listeaza in log dozatoarele BIDOANE cu igienizare scadenta in 7 zile (azi + 7 zile + expirate).',
                'schedule' => '0 7 * * *',
                'schedule_human' => 'Zilnic la 07:00',
                'url' => $base . '/igienizari-zilnice',
            ],
            [
                'job' => 'mentenanta:verifica',
                'denumire' => 'Mentenanta dozatoare cu filtre',
                'descriere' => 'Listeaza in log dozatoarele cu FILTRE cu scadenta in 30/15 zile + expirate. Adminul trimite reminder-ele manual.',
                'schedule' => '0 8 * * *',
                'schedule_human' => 'Zilnic la 08:00',
                'url' => $base . '/mentenanta-verifica',
            ],
        ];

        return view('livewire.setari.cron', [
            'cronJobs' => $cronJobs,
        ]);
    }
}
