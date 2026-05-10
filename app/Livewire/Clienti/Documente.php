<?php

namespace App\Livewire\Clienti;

use App\Models\Client;
use App\Models\DocumentClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Faza 6.7 — Tab „Documente" pe Detalii client.
 *
 * Embed-uit ca `<livewire:clienti.documente :client="$client" />` din pagina
 * principala. NU are layout propriu — se renderizeaza in interiorul Detalii.
 *
 * Functionalitati:
 *   - Upload multi-file (max 5 / batch, 10MB / fisier) cu descriere comuna
 *   - Listare cu icon per tip + denumire originala + descriere + dimensiune
 *     + uploaded_by + data; download via DocumentDownloadController
 *   - Stergere cu modal confirmare (sterge si fisierul fizic)
 *
 * Storage: disk `local` (privat); path
 * `storage/app/private/documente-clienti/{id_client}/{nume_stocat}`.
 *
 * Acces: tab-ul e vizibil doar pe pagina Detalii client (admin/superadmin),
 * deci nu necesita middleware suplimentar in componenta. Defense in depth pe
 * download e in DocumentDownloadController.
 */
class Documente extends Component
{
    use WithFileUploads;

    public Client $client;

    /**
     * Fisiere temporare in upload (Livewire WithFileUploads).
     * Validare: max 5 fisiere, fiecare max 10MB.
     */
    public array $fisiereNoi = [];

    public string $descriereComuna = '';

    public bool $modalStergere = false;
    public ?int $idDeSters = null;
    public string $denumireDeSters = '';

    protected function rules(): array
    {
        return [
            'fisiereNoi' => ['required', 'array', 'min:1', 'max:5'],
            'fisiereNoi.*' => ['file', 'max:10240'], // 10240 KB = 10 MB
            'descriereComuna' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'fisiereNoi.required' => 'Selecteaza cel putin un fisier.',
            'fisiereNoi.min' => 'Selecteaza cel putin un fisier.',
            'fisiereNoi.max' => 'Maxim 5 fisiere per upload.',
            'fisiereNoi.*.file' => 'Fiecare element trebuie sa fie un fisier valid.',
            'fisiereNoi.*.max' => 'Fiecare fisier trebuie sa aiba maxim 10 MB.',
            'descriereComuna.max' => 'Descrierea poate avea maxim 1000 caractere.',
        ];
    }

    public function incarca(): void
    {
        $this->validate();

        foreach ($this->fisiereNoi as $fisier) {
            // Generam nume unic pe filesystem; pastram extensia originala pentru
            // ca browser-ul sa stie cum sa-l deschida la download.
            $extensie = $fisier->getClientOriginalExtension();
            $numeStocat = (string) Str::uuid() . ($extensie ? ".{$extensie}" : '');

            $cale = "documente-clienti/{$this->client->id}";

            // Storage::putFileAs salveaza pe disk-ul `local` (privat)
            Storage::disk('local')->putFileAs($cale, $fisier, $numeStocat);

            DocumentClient::create([
                'id_client' => $this->client->id,
                'nume_fisier' => $fisier->getClientOriginalName(),
                'nume_stocat' => $numeStocat,
                'mime_type' => $fisier->getMimeType(),
                'marime_bytes' => $fisier->getSize(),
                'descriere' => $this->descriereComuna ?: null,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $nrUploadate = count($this->fisiereNoi);
        $this->fisiereNoi = [];
        $this->descriereComuna = '';

        session()->flash('mesaj_documente', "Am incarcat {$nrUploadate} fisier(e).");
    }

    public function deschideModalStergere(int $id): void
    {
        $doc = DocumentClient::find($id);
        if (! $doc || $doc->id_client !== $this->client->id) {
            session()->flash('eroare_documente', 'Documentul nu a fost gasit.');
            return;
        }
        $this->idDeSters = $doc->id;
        $this->denumireDeSters = $doc->nume_fisier;
        $this->modalStergere = true;
    }

    public function inchideModalStergere(): void
    {
        $this->modalStergere = false;
        $this->idDeSters = null;
        $this->denumireDeSters = '';
    }

    public function confirmaStergere(): void
    {
        if (! $this->idDeSters) {
            return;
        }

        $doc = DocumentClient::find($this->idDeSters);
        if (! $doc || $doc->id_client !== $this->client->id) {
            $this->inchideModalStergere();
            session()->flash('eroare_documente', 'Documentul nu a fost gasit.');
            return;
        }

        // Sterge fisierul fizic; daca lipseste deja (orphan), ignoram silent.
        if (Storage::disk('local')->exists($doc->caleStocare())) {
            Storage::disk('local')->delete($doc->caleStocare());
        }

        $denumire = $doc->nume_fisier;
        $doc->delete();

        $this->inchideModalStergere();
        session()->flash('mesaj_documente', "Document „{$denumire}\" sters.");
    }

    public function render()
    {
        $documente = DocumentClient::with('uploadedBy')
            ->where('id_client', $this->client->id)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.clienti.documente', [
            'documente' => $documente,
        ]);
    }
}
