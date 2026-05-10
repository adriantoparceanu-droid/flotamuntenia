<?php

namespace App\Livewire\Setari;

use App\Models\Client;
use App\Services\ContracteService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.2 — Editor template global pentru contractul de prestari servicii.
 *
 * Adminul editeaza HTML-ul template-ului din TinyMCE; placeholderele de tipul
 * `{DENUMIRE}`, `{CIF_CNP}` etc. sunt substituite la generarea contractului
 * per client (vezi `ContracteService::inlocuiestePlaceholdere`).
 *
 * Functionalitati:
 *  - Salveaza modificarile in `setari_platforma.contract_template_html`
 *  - Restaureaza template-ul implicit (suprascrie cu valoarea din
 *    `ContracteService::templateImplicit()`)
 *  - Preview live pe primul client din DB (substituire placeholdere)
 */
#[Layout('layouts.app')]
class ContractTemplate extends Component
{
    public string $html = '';

    public bool $modPreview = false;

    public ?int $clientPreviewId = null;

    public ?string $previewHtml = null;

    public ?string $mesaj = null;

    public ?string $eroare = null;

    public function mount(): void
    {
        $this->html = ContracteService::templateGlobal();

        // Selectam primul client activ pentru preview (daca exista)
        $clientPreview = Client::where('reziliat', false)->orderBy('id')->first();
        $this->clientPreviewId = $clientPreview?->id;
    }

    public function salveaza(): void
    {
        $this->validate([
            'html' => 'required|string|min:10',
        ], [
            'html.required' => 'Continutul template-ului nu poate fi gol.',
            'html.min' => 'Continutul template-ului este prea scurt.',
        ]);

        ContracteService::salveazaTemplateGlobal($this->html);
        $this->mesaj = 'Template salvat cu succes.';
        $this->eroare = null;
    }

    public function restaureazaImplicit(): void
    {
        $this->html = ContracteService::templateImplicit();
        $this->mesaj = 'Template-ul implicit a fost restaurat (NU e salvat inca — apasa „Salveaza" pentru a persista).';
        $this->eroare = null;
    }

    public function activeazaPreview(): void
    {
        if (! $this->clientPreviewId) {
            $this->eroare = 'Nu exista clienti pentru preview. Adauga macar un client.';
            return;
        }

        $client = Client::find($this->clientPreviewId);
        if (! $client) {
            $this->eroare = 'Clientul selectat pentru preview nu mai exista.';
            return;
        }

        $this->previewHtml = ContracteService::inlocuiestePlaceholdere($this->html, $client);
        $this->modPreview = true;
        $this->mesaj = null;
        $this->eroare = null;
    }

    public function inchidePreview(): void
    {
        $this->modPreview = false;
        $this->previewHtml = null;
    }

    public function render()
    {
        $clientiPreview = Client::where('reziliat', false)
            ->orderBy('denumire')
            ->limit(50)
            ->get(['id', 'denumire', 'cod_client']);

        return view('livewire.setari.contract-template', [
            'placeholdere' => ContracteService::placeholdereDisponibile(),
            'clientiPreview' => $clientiPreview,
        ]);
    }
}
