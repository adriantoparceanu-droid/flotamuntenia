<?php

namespace App\Livewire\Setari;

use App\Models\TemplateEmail;
use App\Services\TemplateEmailService;
use Database\Seeders\TemplateuriEmailSeeder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.5 — UI editare template-uri email.
 *
 * Doua moduri pe aceeasi pagina:
 *   - Mod listă (default): tabel cu cele 11 template-uri + buton Editează per linie
 *   - Mod editare: editor TinyMCE pe subiect + body, sidebar placeholdere disponibile
 *
 * Toggle activ se face din lista (un singur click). Restaurare implicit (per template)
 * scoate HTML-ul din TemplateuriEmailSeeder si il aplica fara salvare imediata —
 * adminul vede schimbarea in editor si confirma cu Salveaza.
 */
#[Layout('layouts.app')]
class TemplateuriEmail extends Component
{
    public ?int $editandId = null;

    public string $cheie = '';
    public string $denumire = '';
    public string $subiect = '';
    public string $continutHtml = '';

    public ?string $mesaj = null;
    public ?string $eroare = null;

    public function editeaza(int $id): void
    {
        $tpl = TemplateEmail::findOrFail($id);
        $this->editandId = $tpl->id;
        $this->cheie = $tpl->cheie;
        $this->denumire = $tpl->denumire;
        $this->subiect = $tpl->subiect;
        $this->continutHtml = $tpl->continut_html;
        $this->mesaj = null;
        $this->eroare = null;
    }

    public function inchideEditor(): void
    {
        $this->editandId = null;
        $this->cheie = '';
        $this->denumire = '';
        $this->subiect = '';
        $this->continutHtml = '';
        $this->mesaj = null;
        $this->eroare = null;
    }

    public function salveaza(): void
    {
        $this->validate([
            'subiect' => 'required|string|max:255',
            'continutHtml' => 'required|string|min:10',
        ], [
            'subiect.required' => 'Subiectul este obligatoriu.',
            'continutHtml.required' => 'Continutul template-ului nu poate fi gol.',
            'continutHtml.min' => 'Continutul template-ului este prea scurt.',
        ]);

        $tpl = TemplateEmail::findOrFail($this->editandId);
        $tpl->update([
            'subiect' => $this->subiect,
            'continut_html' => $this->continutHtml,
        ]);

        $this->mesaj = "Template „{$tpl->denumire}\" salvat cu succes.";
        $this->eroare = null;
    }

    public function comutaActiv(int $id): void
    {
        $tpl = TemplateEmail::findOrFail($id);
        $tpl->activ = ! $tpl->activ;
        $tpl->save();
        session()->flash('mesaj', $tpl->activ ? "Template „{$tpl->denumire}\" activat." : "Template „{$tpl->denumire}\" dezactivat.");
    }

    /**
     * Restaureaza HTML-ul implicit din seeder (suprascriere localā in editor;
     * NU salveaza — adminul confirma cu Salveaza).
     */
    public function restaureazaImplicit(): void
    {
        if (! $this->editandId) return;

        // Cautam in seeder versiunea implicita pentru cheia curenta
        $seeder = new TemplateuriEmailSeeder();
        $reflection = new \ReflectionMethod($seeder, 'templateuriImplicite');
        $reflection->setAccessible(true);
        $toate = $reflection->invoke($seeder);

        $implicit = collect($toate)->firstWhere('cheie', $this->cheie);
        if (! $implicit) {
            $this->eroare = 'Nu am gasit versiunea implicita pentru aceasta cheie.';
            return;
        }

        $this->subiect = $implicit['subiect'];
        $this->continutHtml = $implicit['continut_html'];
        $this->mesaj = 'Template-ul implicit a fost restaurat in editor (NU e salvat — apasa „Salveaza" pentru a persista).';
        $this->eroare = null;
    }

    public function render()
    {
        $templateuri = TemplateEmail::orderBy('cheie')->get();

        $placeholdere = [];
        if ($this->editandId) {
            $svc = app(TemplateEmailService::class);
            $placeholdere = $svc->placeholderePerCheie($this->cheie);
        }

        return view('livewire.setari.templateuri-email', [
            'templateuri' => $templateuri,
            'placeholdere' => $placeholdere,
        ]);
    }
}
