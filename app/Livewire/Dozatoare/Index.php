<?php

namespace App\Livewire\Dozatoare;

use App\Models\AdresaLivrare;
use App\Models\Car;
use App\Models\Client;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Models\Dozator;
use App\Models\DozatorFiltre;
use App\Models\DozatorFiltreIstoric;
use App\Models\DozatorReminder;
use App\Models\NotificareMentenanta;
use App\Models\Vizita;
use App\Services\MailService;
use App\Services\MiscariStocService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public const TIP_BIDOANE = 'bidoane';
    public const TIP_FILTRE = 'filtre';

    // Interval auto-prefill mentenanta la creare un dozator filtru.
    // Decizie validata cu user: 12 luni (filtre standard).
    private const MENTENANTA_LUNI = 12;

    // Interval auto-prefill igienizare bidoane (Faza 4.1).
    private const IGIENIZARE_LUNI = 6;

    // ===== Toggle entitate =====
    #[Url(as: 'tip')]
    public string $tipDozator = self::TIP_BIDOANE;

    // ===== Filtre =====
    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'client')]
    public ?int $filtruClient = null;

    #[Url(as: 'masina')]
    public ?int $filtruMasina = null;

    // 'toate' | 'la_zi' | 'scadent_30' | 'scadent_15' | 'expirat' | 'fara_data'
    #[Url(as: 'status')]
    public string $filtruStatus = 'toate';

    // 'toate' | 'activ' | 'inactiv'
    #[Url(as: 'activ')]
    public string $filtruActiv = 'activ';

    // ===== Modal CRUD dozator (partajat) =====
    public bool $modalDozator = false;
    public ?int $dozatorId = null;
    public ?int $idClient = null;
    public ?int $idAdresa = null;
    public ?int $idMasina = null;
    public ?int $idDepozit = null;
    public ?int $idProdus = null;
    public string $serie = '';
    public string $tranzactie = Dozator::TRANZACTIE_CUSTODIE;
    public string $dataInstalare = '';

    // Bidoane:
    public string $perioadaIgenizare = '';
    public bool $comanda = false;

    // Filtre:
    public string $dataUrmatoareMentenanta = '';
    public string $sumaGarantie = '';

    public string $observatii = '';

    // ===== Modal Vizite (Bidoane: igienizari) =====
    public bool $modalVizite = false;
    public ?int $viziteForDozatorId = null;
    public string $vizDataVizita = '';
    public string $vizDataUrmatoare = '';
    public string $vizPret = '';
    public string $vizObservatii = '';

    // ===== Modal Istoric (Filtre: interventii) =====
    public bool $modalIstoric = false;
    public ?int $istoricForFiltruId = null;
    public string $intDataInterventie = '';
    public string $intDataUrmatoare = '';
    public string $intPret = '';
    public string $intObservatii = '';

    // ===== Modal stergere =====
    public bool $modalStergere = false;
    public ?int $idDeSters = null;
    public string $denumireDeSters = '';

    public function mount(): void
    {
        // Suport URL: /dozatoare?tip=filtre&id_client=X&new=1 (din tab Detalii client)
        $idClientPrefill = (int) request()->query('id_client', 0);
        if (request()->boolean('new') && $idClientPrefill > 0) {
            $this->nou();
            $this->idClient = $idClientPrefill;
        }

        // Suport URL: /dozatoare?tip=filtre&edit=Y
        $editId = (int) request()->query('edit', 0);
        if ($editId > 0) {
            $this->editeaza($editId);
        }
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['cautare', 'filtruClient', 'filtruMasina', 'filtruStatus', 'filtruActiv', 'tipDozator'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Comutarea toggle-ului Bidoane/Filtre. Resetam paginarea si form-ul ca
     * sa nu pastram un dozatorId valid pe entitatea cealalta.
     */
    public function comutaTip(string $tip): void
    {
        if (! in_array($tip, [self::TIP_BIDOANE, self::TIP_FILTRE], true)) {
            return;
        }
        if ($this->tipDozator === $tip) {
            return;
        }
        $this->tipDozator = $tip;
        $this->resetPage();
        $this->resetForm();
        $this->modalDozator = false;
        $this->modalVizite = false;
        $this->modalIstoric = false;
    }

    public function reseteazaFiltre(): void
    {
        $this->cautare = '';
        $this->filtruClient = null;
        $this->filtruMasina = null;
        $this->filtruStatus = 'toate';
        $this->filtruActiv = 'activ';
        $this->resetPage();
    }

    public function esteFiltre(): bool
    {
        return $this->tipDozator === self::TIP_FILTRE;
    }

    private function intervalLuni(): int
    {
        return $this->esteFiltre() ? self::MENTENANTA_LUNI : self::IGIENIZARE_LUNI;
    }

    // ===== CRUD dozator (ramificat) =====

    public function nou(): void
    {
        $this->resetForm();
        $this->dataInstalare = now()->toDateString();
        if ($this->esteFiltre()) {
            $this->dataUrmatoareMentenanta = now()->addMonths(self::MENTENANTA_LUNI)->toDateString();
        } else {
            $this->perioadaIgenizare = now()->addMonths(self::IGIENIZARE_LUNI)->toDateString();
        }
        $this->modalDozator = true;
    }

    public function editeaza(int $id): void
    {
        if ($this->esteFiltre()) {
            $d = DozatorFiltre::find($id);
            if (! $d) {
                return;
            }
            $this->dozatorId = $d->id;
            $this->idClient = $d->id_client;
            $this->idAdresa = $d->id_adresa;
            $this->idMasina = $d->id_masina;
            $this->idDepozit = $d->id_depozit;
            $this->idProdus = $d->id_produs;
            $this->serie = $d->serie ?? '';
            $this->tranzactie = $d->tranzactie;
            $this->dataInstalare = $d->data_instalare?->format('Y-m-d') ?? '';
            $this->dataUrmatoareMentenanta = $d->data_urmatoare_mentenanta?->format('Y-m-d') ?? '';
            $this->sumaGarantie = $d->suma_garantie !== null ? (string) $d->suma_garantie : '';
            $this->observatii = $d->observatii ?? '';
        } else {
            $d = Dozator::find($id);
            if (! $d) {
                return;
            }
            $this->dozatorId = $d->id;
            $this->idClient = $d->id_client;
            $this->idAdresa = $d->id_adresa;
            $this->idMasina = $d->id_masina;
            $this->idDepozit = $d->id_depozit;
            $this->idProdus = $d->id_produs;
            $this->serie = $d->serie ?? '';
            $this->tranzactie = $d->tranzactie;
            $this->dataInstalare = $d->data_instalare?->format('Y-m-d') ?? '';
            $this->perioadaIgenizare = $d->perioada_igenizare?->format('Y-m-d') ?? '';
            $this->comanda = (bool) $d->comanda;
            $this->observatii = $d->observatii ?? '';
        }
        $this->modalDozator = true;
    }

    /**
     * La schimbarea data_instalare propagam scadenta urmatoare doar la creare
     * (cand admin nu a atins-o manual). La editare nu suprascriem.
     */
    public function updatedDataInstalare(): void
    {
        if ($this->dataInstalare === '' || $this->dozatorId !== null) {
            return;
        }
        try {
            $sugestie = Carbon::parse($this->dataInstalare)->addMonths($this->intervalLuni())->toDateString();
        } catch (\Throwable $e) {
            return;
        }
        if ($this->esteFiltre()) {
            $this->dataUrmatoareMentenanta = $sugestie;
        } else {
            $this->perioadaIgenizare = $sugestie;
        }
    }

    public function salveaza(MiscariStocService $stocService): void
    {
        $reguli = [
            'idClient' => ['required', 'exists:clienti,id'],
            'idAdresa' => ['required', 'exists:adresa_livrare,id'],
            'idProdus' => ['required', 'exists:cost_products,id'],
            'idDepozit' => ['nullable', 'exists:deposits,id'],
            'idMasina' => ['nullable', 'exists:cars,id'],
            'serie' => ['nullable', 'string', 'max:100'],
            'tranzactie' => ['required', 'in:custodie,cumparat'],
            'dataInstalare' => ['required', 'date'],
            'observatii' => ['nullable', 'string'],
        ];
        if ($this->esteFiltre()) {
            $reguli['dataUrmatoareMentenanta'] = ['required', 'date'];
            $reguli['sumaGarantie'] = ['nullable', 'numeric', 'min:0'];
        } else {
            $reguli['perioadaIgenizare'] = ['nullable', 'date'];
        }

        $this->validate($reguli, [
            'idClient.required' => 'Selecteaza un client.',
            'idAdresa.required' => 'Selecteaza o adresa de livrare.',
            'idProdus.required' => 'Selecteaza tipul dozatorului din catalog.',
            'dataInstalare.required' => 'Data instalarii este obligatorie.',
            'dataUrmatoareMentenanta.required' => 'Data urmatoarei mentenante este obligatorie.',
        ]);

        DB::transaction(function () use ($stocService) {
            if ($this->esteFiltre()) {
                $payload = [
                    'id_client' => $this->idClient,
                    'id_adresa' => $this->idAdresa,
                    'id_masina' => $this->idMasina,
                    'id_depozit' => $this->idDepozit,
                    'id_produs' => $this->idProdus,
                    'serie' => $this->serie ?: null,
                    'tranzactie' => $this->tranzactie,
                    'data_instalare' => $this->dataInstalare,
                    'data_urmatoare_mentenanta' => $this->dataUrmatoareMentenanta ?: null,
                    'suma_garantie' => $this->sumaGarantie !== '' ? (float) $this->sumaGarantie : 0,
                    'observatii' => $this->observatii ?: null,
                ];

                if ($this->dozatorId) {
                    $d = DozatorFiltre::findOrFail($this->dozatorId);
                    $d->update($payload);
                } else {
                    $payload['status'] = DozatorFiltre::STATUS_ACTIV;
                    $d = DozatorFiltre::create($payload);
                    $this->dozatorId = $d->id;
                }
                $d->refresh();
                $stocService->sincronizeazaCustodieDozatorFiltre($d);
            } else {
                $payload = [
                    'id_client' => $this->idClient,
                    'id_adresa' => $this->idAdresa,
                    'id_masina' => $this->idMasina,
                    'id_depozit' => $this->idDepozit,
                    'id_produs' => $this->idProdus,
                    'serie' => $this->serie ?: null,
                    'tranzactie' => $this->tranzactie,
                    'data_instalare' => $this->dataInstalare,
                    'perioada_igenizare' => $this->perioadaIgenizare ?: null,
                    'comanda' => $this->comanda,
                    'observatii' => $this->observatii ?: null,
                ];

                if ($this->dozatorId) {
                    $d = Dozator::findOrFail($this->dozatorId);
                    $d->update($payload);
                } else {
                    $payload['activ'] = true;
                    $d = Dozator::create($payload);
                    $this->dozatorId = $d->id;
                }
                $d->refresh();
                $stocService->sincronizeazaCustodieDozator($d);
            }
        });

        session()->flash('mesaj', 'Dozator salvat cu succes.');
        $this->inchideModalDozator();
    }

    public function comutaActiv(int $id, MiscariStocService $stocService): void
    {
        if ($this->esteFiltre()) {
            $d = DozatorFiltre::find($id);
            if (! $d) {
                return;
            }
            $d->status = $d->esteActiv() ? DozatorFiltre::STATUS_RETRAS : DozatorFiltre::STATUS_ACTIV;
            $d->save();
            $stocService->sincronizeazaCustodieDozatorFiltre($d);

            session()->flash('mesaj', $d->esteActiv() ? 'Dozator filtru reactivat.' : 'Dozator filtru retras.');
        } else {
            $d = Dozator::find($id);
            if (! $d) {
                return;
            }
            $d->activ = ! $d->activ;
            $d->save();
            $stocService->sincronizeazaCustodieDozator($d);

            session()->flash('mesaj', $d->activ ? 'Dozator reactivat.' : 'Dozator dezactivat (recuperat).');
        }
    }

    public function inchideModalDozator(): void
    {
        $this->modalDozator = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->dozatorId = null;
        $this->idClient = null;
        $this->idAdresa = null;
        $this->idMasina = null;
        $this->idDepozit = null;
        $this->idProdus = null;
        $this->serie = '';
        $this->tranzactie = Dozator::TRANZACTIE_CUSTODIE;
        $this->dataInstalare = '';
        $this->perioadaIgenizare = '';
        $this->comanda = false;
        $this->dataUrmatoareMentenanta = '';
        $this->sumaGarantie = '';
        $this->observatii = '';
        $this->resetErrorBag();
    }

    // ===== Stergere =====

    public function deschideModalStergere(int $id): void
    {
        if ($this->esteFiltre()) {
            $d = DozatorFiltre::with('client')->find($id);
            if (! $d) {
                return;
            }
            $this->idDeSters = $d->id;
            $this->denumireDeSters = '#' . $d->id . ' — ' . ($d->client?->denumire ?? '?')
                . ($d->serie ? ' (serie ' . $d->serie . ')' : '');
        } else {
            $d = Dozator::with('client')->find($id);
            if (! $d) {
                return;
            }
            $this->idDeSters = $d->id;
            $this->denumireDeSters = '#' . $d->id . ' — ' . ($d->client?->denumire ?? '?')
                . ($d->serie ? ' (serie ' . $d->serie . ')' : '');
        }
        $this->modalStergere = true;
    }

    public function inchideModalStergere(): void
    {
        $this->modalStergere = false;
        $this->idDeSters = null;
        $this->denumireDeSters = '';
    }

    public function confirmaStergere(MiscariStocService $stocService): void
    {
        if (! $this->idDeSters) {
            return;
        }
        if ($this->esteFiltre()) {
            $d = DozatorFiltre::find($this->idDeSters);
            if (! $d) {
                $this->inchideModalStergere();
                return;
            }
            $stocService->revertCustodieDozatorFiltre($d);
            $d->delete();
        } else {
            $d = Dozator::find($this->idDeSters);
            if (! $d) {
                $this->inchideModalStergere();
                return;
            }
            $stocService->revertCustodieDozator($d);
            $d->delete();
        }
        $this->inchideModalStergere();
        session()->flash('mesaj', 'Dozator sters.');
    }

    // ===== Vizite Bidoane (igienizari) =====

    public function deschideModalVizite(int $id): void
    {
        $d = Dozator::find($id);
        if (! $d) {
            return;
        }
        $this->viziteForDozatorId = $d->id;
        $this->vizDataVizita = now()->toDateString();
        $this->vizDataUrmatoare = now()->addMonths(self::IGIENIZARE_LUNI)->toDateString();
        $this->vizPret = '';
        $this->vizObservatii = '';
        $this->modalVizite = true;
    }

    public function inchideModalVizite(): void
    {
        $this->modalVizite = false;
        $this->viziteForDozatorId = null;
        $this->vizDataVizita = '';
        $this->vizDataUrmatoare = '';
        $this->vizPret = '';
        $this->vizObservatii = '';
        $this->resetErrorBag();
    }

    public function adaugaVizita(): void
    {
        $this->validate([
            'vizDataVizita' => ['required', 'date'],
            'vizDataUrmatoare' => ['nullable', 'date', 'after_or_equal:vizDataVizita'],
            'vizPret' => ['nullable', 'numeric', 'min:0'],
            'vizObservatii' => ['nullable', 'string'],
        ], [
            'vizDataVizita.required' => 'Data vizitei este obligatorie.',
            'vizDataUrmatoare.after_or_equal' => 'Data urmatoarei igienizari trebuie sa fie >= data vizitei.',
        ]);

        $d = Dozator::find($this->viziteForDozatorId);
        if (! $d) {
            return;
        }

        DB::transaction(function () use ($d) {
            Vizita::create([
                'id_dozator' => $d->id,
                'id_client' => $d->id_client,
                'id_adresa' => $d->id_adresa,
                'id_masina' => $d->id_masina,
                'data_vizita' => $this->vizDataVizita,
                'data_urmatoare' => $this->vizDataUrmatoare ?: null,
                'pret' => $this->vizPret !== '' ? (float) $this->vizPret : 0,
                'observatii' => $this->vizObservatii ?: null,
                'livrat' => true,
                'achitat' => false,
            ]);

            if ($this->vizDataUrmatoare) {
                $d->update(['perioada_igenizare' => $this->vizDataUrmatoare]);
            }
        });

        session()->flash('mesaj', 'Vizita inregistrata. Perioada urmatoare actualizata.');
        $this->inchideModalVizite();
    }

    public function marcheazaIgienizareAzi(int $id): void
    {
        $d = Dozator::find($id);
        if (! $d) {
            return;
        }

        DB::transaction(function () use ($d) {
            Vizita::create([
                'id_dozator' => $d->id,
                'id_client' => $d->id_client,
                'id_adresa' => $d->id_adresa,
                'id_masina' => $d->id_masina,
                'data_vizita' => now()->toDateString(),
                'data_urmatoare' => now()->addMonths(self::IGIENIZARE_LUNI)->toDateString(),
                'pret' => 0,
                'livrat' => true,
                'achitat' => false,
            ]);
            $d->update(['perioada_igenizare' => now()->addMonths(self::IGIENIZARE_LUNI)->toDateString()]);
        });

        session()->flash('mesaj', "Igienizare inregistrata pentru dozator #{$d->id}. Urmatoarea: " . now()->addMonths(self::IGIENIZARE_LUNI)->format('d.m.Y'));
    }

    // ===== Istoric Filtre (interventii) =====

    public function deschideModalIstoric(int $id): void
    {
        $d = DozatorFiltre::find($id);
        if (! $d) {
            return;
        }
        $this->istoricForFiltruId = $d->id;
        $this->intDataInterventie = now()->toDateString();
        $this->intDataUrmatoare = now()->addMonths(self::MENTENANTA_LUNI)->toDateString();
        $this->intPret = '';
        $this->intObservatii = '';
        $this->modalIstoric = true;
    }

    public function inchideModalIstoric(): void
    {
        $this->modalIstoric = false;
        $this->istoricForFiltruId = null;
        $this->intDataInterventie = '';
        $this->intDataUrmatoare = '';
        $this->intPret = '';
        $this->intObservatii = '';
        $this->resetErrorBag();
    }

    public function adaugaInterventie(): void
    {
        $this->validate([
            'intDataInterventie' => ['required', 'date'],
            'intDataUrmatoare' => ['nullable', 'date', 'after_or_equal:intDataInterventie'],
            'intPret' => ['nullable', 'numeric', 'min:0'],
            'intObservatii' => ['nullable', 'string'],
        ], [
            'intDataInterventie.required' => 'Data interventiei este obligatorie.',
            'intDataUrmatoare.after_or_equal' => 'Data urmatoarei mentenante trebuie sa fie >= data interventiei.',
        ]);

        $d = DozatorFiltre::find($this->istoricForFiltruId);
        if (! $d) {
            return;
        }

        DB::transaction(function () use ($d) {
            DozatorFiltreIstoric::create([
                'id_dozator_filtre' => $d->id,
                'id_client' => $d->id_client,
                'id_masina' => $d->id_masina,
                'data_interventie' => $this->intDataInterventie,
                'data_urmatoare' => $this->intDataUrmatoare ?: null,
                'pret' => $this->intPret !== '' ? (float) $this->intPret : 0,
                'observatii' => $this->intObservatii ?: null,
            ]);

            $update = ['data_ultima_mentenanta' => $this->intDataInterventie];
            if ($this->intDataUrmatoare) {
                $update['data_urmatoare_mentenanta'] = $this->intDataUrmatoare;
            }
            $d->update($update);
        });

        session()->flash('mesaj', 'Interventie inregistrata. Datele de mentenanta actualizate.');
        $this->inchideModalIstoric();
    }

    /**
     * Mirror al `marcheazaIgienizareAzi` pentru filtre. Data interventie = azi,
     * urmatoarea = +12 luni, pret = 0.
     */
    public function marcheazaInterventieAzi(int $id): void
    {
        $d = DozatorFiltre::find($id);
        if (! $d) {
            return;
        }

        DB::transaction(function () use ($d) {
            DozatorFiltreIstoric::create([
                'id_dozator_filtre' => $d->id,
                'id_client' => $d->id_client,
                'id_masina' => $d->id_masina,
                'data_interventie' => now()->toDateString(),
                'data_urmatoare' => now()->addMonths(self::MENTENANTA_LUNI)->toDateString(),
                'pret' => 0,
            ]);
            $d->update([
                'data_ultima_mentenanta' => now()->toDateString(),
                'data_urmatoare_mentenanta' => now()->addMonths(self::MENTENANTA_LUNI)->toDateString(),
            ]);
        });

        session()->flash('mesaj', "Interventie inregistrata pentru dozatorul filtru #{$d->id}. Urmatoarea: " . now()->addMonths(self::MENTENANTA_LUNI)->format('d.m.Y'));
    }

    // ===== Reminder =====

    public function trimiteReminder(int $id): void
    {
        if ($this->esteFiltre()) {
            $d = DozatorFiltre::with('client', 'adresa')->find($id);
            if (! $d) {
                return;
            }
            if (! $d->necesitaReminder()) {
                session()->flash('eroare', 'Dozatorul filtru nu este scadent.');
                return;
            }

            $tip = $d->tipReminderAuto();

            NotificareMentenanta::create([
                'id_dozator_filtre' => $d->id,
                'id_client' => $d->id_client,
                'tip_notificare' => $tip,
                'trimis_de' => auth()->id(),
                'data_trimitere' => now(),
            ]);

            MailService::send('mentenanta_filtru_reminder', $d->client?->email, [
                'client' => $d->client?->denumire,
                'serie' => $d->serie,
                'adresa' => $d->adresa?->denumire,
                'data_scadenta' => $d->data_urmatoare_mentenanta?->format('d.m.Y'),
                'tip_notificare' => $tip,
                'status' => $d->etichetaStatusMentenanta(),
            ]);

            $eticheta = $tip === NotificareMentenanta::TIP_15_ZILE ? '15 zile' : '30 zile';
            session()->flash('mesaj', "Reminder ({$eticheta}) trimis catre {$d->client?->denumire}.");
            return;
        }

        // Bidoane (Faza 4.1).
        $d = Dozator::with('client', 'adresa')->find($id);
        if (! $d) {
            return;
        }
        if (! $d->necesitaReminder()) {
            session()->flash('eroare', 'Dozatorul nu este scadent.');
            return;
        }

        DozatorReminder::create([
            'id_dozator' => $d->id,
            'trimis_de' => auth()->id(),
            'trimis_la' => now(),
        ]);

        MailService::send('igienizare_reminder', $d->client?->email, [
            'client' => $d->client?->denumire,
            'serie' => $d->serie,
            'adresa' => $d->adresa?->denumire,
            'data_scadenta' => $d->perioada_igenizare?->format('d.m.Y'),
            'status' => $d->etichetaStatusIgienizare(),
        ]);

        session()->flash('mesaj', "Reminder trimis catre {$d->client?->denumire}.");
    }

    // ===== Render =====

    public function render()
    {
        $azi = now()->startOfDay()->toDateString();

        if ($this->esteFiltre()) {
            $q = DozatorFiltre::query()
                ->with(['client', 'adresa', 'masina', 'depozit', 'produs'])
                ->withCount('istoric')
                ->orderByDesc('id');

            if ($this->cautare !== '') {
                $term = '%' . $this->cautare . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->whereHas('client', fn ($cq) => $cq->where('client', 'like', $term)
                        ->orWhere('cod_client', 'like', $term)
                        ->orWhere('cif', 'like', $term))
                        ->orWhere('serie', 'like', $term)
                        ->orWhere('id', $this->cautare);
                });
            }
            if ($this->filtruClient) {
                $q->where('id_client', $this->filtruClient);
            }
            if ($this->filtruMasina) {
                $q->where('id_masina', $this->filtruMasina);
            }
            if ($this->filtruActiv === 'activ') {
                $q->where('status', DozatorFiltre::STATUS_ACTIV);
            } elseif ($this->filtruActiv === 'inactiv') {
                $q->where('status', DozatorFiltre::STATUS_RETRAS);
            }

            // Status pe `data_urmatoare_mentenanta` (paginare corecta).
            if ($this->filtruStatus === 'la_zi') {
                $q->where('data_urmatoare_mentenanta', '>', now()->addDays(30)->toDateString());
            } elseif ($this->filtruStatus === 'scadent_30') {
                $q->whereBetween('data_urmatoare_mentenanta', [
                    now()->addDays(16)->toDateString(),
                    now()->addDays(30)->toDateString(),
                ]);
            } elseif ($this->filtruStatus === 'scadent_15') {
                $q->whereBetween('data_urmatoare_mentenanta', [
                    $azi,
                    now()->addDays(15)->toDateString(),
                ]);
            } elseif ($this->filtruStatus === 'expirat') {
                $q->where('data_urmatoare_mentenanta', '<', $azi);
            } elseif ($this->filtruStatus === 'fara_data') {
                $q->whereNull('data_urmatoare_mentenanta');
            }

            $colectie = $q->paginate(20);

            $totalScadenteFiltre = DozatorFiltre::where('status', DozatorFiltre::STATUS_ACTIV)
                ->whereNotNull('data_urmatoare_mentenanta')
                ->where('data_urmatoare_mentenanta', '<=', now()->addDays(30)->toDateString())
                ->count();

            $totalScadenteBidoane = Dozator::where('activ', true)
                ->whereNotNull('perioada_igenizare')
                ->where('perioada_igenizare', '<=', now()->addDays(30)->toDateString())
                ->count();

            $istoricFiltru = collect();
            if ($this->istoricForFiltruId) {
                $istoricFiltru = DozatorFiltreIstoric::where('id_dozator_filtre', $this->istoricForFiltruId)
                    ->orderByDesc('data_interventie')
                    ->get();
            }

            $adreseClient = collect();
            if ($this->idClient) {
                $adreseClient = AdresaLivrare::where('id_client', $this->idClient)
                    ->where('activ', true)
                    ->orderBy('denumire')
                    ->get();
            }

            return view('livewire.dozatoare.index', [
                'tipDozator' => $this->tipDozator,
                'dozatoare' => $colectie,
                'clienti' => Client::where('reziliat', false)->orderBy('denumire')->limit(200)->get(),
                'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
                'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
                'produse' => CostProduct::where('activ', true)->orderBy('denumire')->get(),
                'adreseClient' => $adreseClient,
                'vizitepDozator' => collect(),
                'istoricFiltru' => $istoricFiltru,
                'totalScadente' => $totalScadenteBidoane + $totalScadenteFiltre,
                'totalScadenteBidoane' => $totalScadenteBidoane,
                'totalScadenteFiltre' => $totalScadenteFiltre,
            ]);
        }

        // ===== Bidoane (Faza 4.1, neschimbat) =====
        $q = Dozator::query()
            ->with(['client', 'adresa', 'masina', 'depozit', 'produs'])
            ->withCount('vizite')
            ->orderByDesc('id');

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('client', fn ($cq) => $cq->where('client', 'like', $term)
                    ->orWhere('cod_client', 'like', $term)
                    ->orWhere('cif', 'like', $term))
                    ->orWhere('serie', 'like', $term)
                    ->orWhere('id', $this->cautare);
            });
        }

        if ($this->filtruClient) {
            $q->where('id_client', $this->filtruClient);
        }
        if ($this->filtruMasina) {
            $q->where('id_masina', $this->filtruMasina);
        }
        if ($this->filtruActiv === 'activ') {
            $q->where('activ', true);
        } elseif ($this->filtruActiv === 'inactiv') {
            $q->where('activ', false);
        }

        if ($this->filtruStatus === 'la_zi') {
            $q->where('perioada_igenizare', '>', now()->addDays(30)->toDateString());
        } elseif ($this->filtruStatus === 'scadent_30') {
            $q->whereBetween('perioada_igenizare', [
                now()->addDays(16)->toDateString(),
                now()->addDays(30)->toDateString(),
            ]);
        } elseif ($this->filtruStatus === 'scadent_15') {
            $q->whereBetween('perioada_igenizare', [
                $azi,
                now()->addDays(15)->toDateString(),
            ]);
        } elseif ($this->filtruStatus === 'expirat') {
            $q->where('perioada_igenizare', '<', $azi);
        } elseif ($this->filtruStatus === 'fara_data') {
            $q->whereNull('perioada_igenizare');
        }

        $dozatoare = $q->paginate(20);

        $adreseClient = collect();
        if ($this->idClient) {
            $adreseClient = AdresaLivrare::where('id_client', $this->idClient)
                ->where('activ', true)
                ->orderBy('denumire')
                ->get();
        }

        $vizitepDozator = collect();
        if ($this->viziteForDozatorId) {
            $vizitepDozator = Vizita::where('id_dozator', $this->viziteForDozatorId)
                ->orderByDesc('data_vizita')
                ->get();
        }

        $totalScadenteBidoane = Dozator::where('activ', true)
            ->whereNotNull('perioada_igenizare')
            ->where('perioada_igenizare', '<=', now()->addDays(30)->toDateString())
            ->count();

        $totalScadenteFiltre = DozatorFiltre::where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->where('data_urmatoare_mentenanta', '<=', now()->addDays(30)->toDateString())
            ->count();

        return view('livewire.dozatoare.index', [
            'tipDozator' => $this->tipDozator,
            'dozatoare' => $dozatoare,
            'clienti' => Client::where('reziliat', false)->orderBy('denumire')->limit(200)->get(),
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'produse' => CostProduct::where('activ', true)->orderBy('denumire')->get(),
            'adreseClient' => $adreseClient,
            'vizitepDozator' => $vizitepDozator,
            'istoricFiltru' => collect(),
            'totalScadente' => $totalScadenteBidoane + $totalScadenteFiltre,
            'totalScadenteBidoane' => $totalScadenteBidoane,
            'totalScadenteFiltre' => $totalScadenteFiltre,
        ]);
    }
}
