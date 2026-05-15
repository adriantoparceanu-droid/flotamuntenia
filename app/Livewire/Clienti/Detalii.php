<?php

namespace App\Livewire\Clienti;

use App\Models\AdresaLivrare;
use App\Models\Car;
use App\Models\Client;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Models\Dozator;
use App\Models\DozatorFiltre;
use App\Models\DozatorFiltreIstoric;
use App\Models\Problema;
use App\Models\Produs;
use App\Models\Recipient;
use App\Models\Vizita;
use App\Services\ContracteService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalii extends Component
{
    public Client $client;

    #[Url(as: 'tab')]
    public string $tab = 'general';

    // Modal reziliere
    public bool $modalReziliere = false;
    public string $motivReziliere = '';

    // Modal adresa
    public bool $modalAdresa = false;
    public ?int $adresaId = null;
    public string $adresaDenumire = '';
    public string $adresaOras = '';
    public string $adresaStrada = '';
    public string $adresaNr = '';
    public string $adresaBloc = '';
    public string $adresaScara = '';
    public string $adresaEtaj = '';
    public string $adresaApartament = '';
    public string $adresaSector = '';
    public string $adresaInterfon = '';
    // Coordonate GPS — UI accepta string format "lat, lng" (copy/paste din Google Maps).
    // La salvare se parseaza si se separa in cele doua coloane DB conform regulii §8.9.
    public string $adresaGps = '';
    public bool $adresaActiv = true;

    // ===== Modal abonament / configurare livrare =====
    public bool $modalAbonament = false;
    public ?int $abonamentAdresaId = null; // adresa pentru care configuram

    public int $abTip = Produs::TIP_ABONAMENT;
    public string $abDenumireAbonament = '';
    public int $abNrBidoane = 0;
    public int $abNrBidoane11l = 0;
    public string $abPret = '0.00';
    public string $abPret11l = '0.00';
    public string $abPretSuplimentar19l = '0.00';
    public string $abPretSuplimentar11l = '0.00';
    public string $abFrecventa = '';
    public string $abZiLivrare = ''; // YYYY-MM-DD
    public ?int $abIdMasina = null;
    public ?int $abIdDepozit = null;
    public string $abObservatii = '';

    // ===== Sub-modal: Aparat (dozator cu bidoane) in cadrul Configurare livrare =====
    public bool $modalAparatNou = false;
    public ?int $aparatEditId = null;
    public ?int $aparatIdProdus = null;
    public string $aparatSerie = '';
    public string $aparatTranzactie = 'custodie';
    public string $aparatDataInstalare = '';
    public string $aparatDataIgienizare = '';
    public string $aparatObservatii = '';

    // ===== Sub-modal: Purificator (dozator cu filtre) in cadrul Configurare livrare =====
    public bool $modalPurificatorNou = false;
    public ?int $purificatorEditId = null;
    public ?int $purificatorIdProdus = null;
    public string $purificatorSerie = '';
    public string $purificatorTranzactie = 'custodie';
    public string $purificatorDataInstalare = '';
    public string $purificatorDataMentenanta = '';
    public string $purificatorGarantie = '0.00';
    public string $purificatorObservatii = '';

    // ===== Modal Vizite (Dozatoare cu bidoane — igienizari) =====
    public bool $modalVizite = false;
    public ?int $viziteForDozatorId = null;
    public string $vizDataVizita = '';
    public string $vizDataUrmatoare = '';
    public string $vizPret = '';
    public string $vizObservatii = '';

    // ===== Modal Interventie (Dozatoare cu filtre — mentenanta) =====
    public bool $modalInterventie = false;
    public ?int $interventieForFiltruId = null;
    public string $intDataInterventie = '';
    public string $intDataUrmatoare = '';
    public string $intPret = '';
    public string $intObservatii = '';

    // ===== Modal recipienti (admin — corectie manuala) =====
    public bool $modalRecipientiAdmin = false;
    public ?int $recAdresaId = null;
    public string $recAdresaDenumire = '';
    public int $recAdminLasati19l = 0;
    public int $recAdminRecuperati19l = 0;
    public int $recAdminLasati11l = 0;
    public int $recAdminRecuperati11l = 0;
    public string $recAdminData = '';
    public string $recAdminObservatii = '';

    // Filtru in tabul Recipienti — afiseaza jurnalul doar pentru o adresa anume
    #[Url(as: 'rec_adresa')]
    public ?int $recFiltruAdresa = null;

    // ===== Tab Contract (Faza 6.2) =====
    // Continutul HTML curent al contractului — sincronizat cu TinyMCE prin
    // wire:ignore + Alpine wrapper (vezi view-ul detalii.blade.php).
    public string $contractHtml = '';
    public ?int $contractId = null;
    public ?string $contractMesaj = null;
    public ?string $contractEroare = null;

    public function mount(Client $client): void
    {
        $this->client = $client;
        // Tab-ul dozatoare a fost mutat inline in Adrese — redirecteaza URL-uri vechi
        if ($this->tab === 'dozatoare') {
            $this->tab = 'adrese';
        }
    }

    public function comutaTab(string $tab): void
    {
        $valide = ['general', 'adrese', 'comenzi', 'probleme', 'recipienti', 'documente', 'contract'];
        $this->tab = in_array($tab, $valide, true) ? $tab : 'general';

        if ($this->tab === 'contract') {
            $this->incarcaContract();
        }
    }

    // ===== Reziliere =====

    public function deschideModalReziliere(): void
    {
        $this->motivReziliere = $this->client->observatii_reziliere ?? '';
        $this->modalReziliere = true;
    }

    public function inchideModalReziliere(): void
    {
        $this->modalReziliere = false;
        $this->motivReziliere = '';
    }

    public function confirmaReziliere(): void
    {
        $this->client->update([
            'reziliat' => true,
            'observatii_reziliere' => $this->motivReziliere ?: null,
        ]);
        $this->client->refresh();

        $this->inchideModalReziliere();
        session()->flash('mesaj', 'Client reziliat.');
    }

    public function reactiveaza(): void
    {
        $this->client->update([
            'reziliat' => false,
            'observatii_reziliere' => null,
        ]);
        $this->client->refresh();

        session()->flash('mesaj', 'Client reactivat.');
    }

    // ===== Adrese =====

    public function adresaNoua(): void
    {
        $this->resetFormAdresa();
        $this->modalAdresa = true;
    }

    public function editeazaAdresa(int $id): void
    {
        $adresa = $this->client->adrese()->findOrFail($id);
        $this->adresaId = $adresa->id;
        $this->adresaDenumire = $adresa->denumire;
        $this->adresaOras = $adresa->oras ?? '';
        $this->adresaStrada = $adresa->strada ?? '';
        $this->adresaNr = $adresa->nr ?? '';
        $this->adresaBloc = $adresa->bloc ?? '';
        $this->adresaScara = $adresa->scara ?? '';
        $this->adresaEtaj = $adresa->etaj ?? '';
        $this->adresaApartament = $adresa->apartament ?? '';
        $this->adresaSector = $adresa->sector ?? '';
        $this->adresaInterfon = $adresa->interfon ?? '';
        $this->adresaGps = $adresa->areCoordonateGps()
            ? rtrim(rtrim((string) $adresa->lat, '0'), '.') . ', ' . rtrim(rtrim((string) $adresa->lng, '0'), '.')
            : '';
        $this->adresaActiv = $adresa->activ;
        $this->modalAdresa = true;
    }

    public function salveazaAdresa(): void
    {
        $date = $this->validate([
            'adresaDenumire' => 'required|string|max:255',
            'adresaOras' => 'nullable|string|max:100',
            'adresaStrada' => 'nullable|string|max:255',
            'adresaNr' => 'nullable|string|max:20',
            'adresaBloc' => 'nullable|string|max:20',
            'adresaScara' => 'nullable|string|max:10',
            'adresaEtaj' => 'nullable|string|max:10',
            'adresaApartament' => 'nullable|string|max:20',
            'adresaSector' => 'nullable|string|max:20',
            'adresaInterfon' => 'nullable|string|max:20',
            'adresaGps' => 'nullable|string|max:100',
            'adresaActiv' => 'boolean',
        ], [
            'adresaDenumire.required' => 'Denumirea adresei este obligatorie.',
        ]);

        // Parsam string-ul GPS in cele doua coloane lat/lng.
        // Daca formatul e invalid, adaugam eroare si oprim salvarea.
        [$lat, $lng] = $this->parseazaGps($date['adresaGps'] ?? '');
        if ($this->getErrorBag()->has('adresaGps')) {
            return;
        }

        $payload = [
            'id_client' => $this->client->id,
            'denumire' => $date['adresaDenumire'],
            'oras' => $date['adresaOras'] ?: null,
            'strada' => $date['adresaStrada'] ?: null,
            'nr' => $date['adresaNr'] ?: null,
            'bloc' => $date['adresaBloc'] ?: null,
            'scara' => $date['adresaScara'] ?: null,
            'etaj' => $date['adresaEtaj'] ?: null,
            'apartament' => $date['adresaApartament'] ?: null,
            'sector' => $date['adresaSector'] ?: null,
            'interfon' => $date['adresaInterfon'] ?: null,
            'lat' => $lat,
            'lng' => $lng,
            'activ' => $date['adresaActiv'],
        ];

        if ($this->adresaId) {
            $this->client->adrese()->whereKey($this->adresaId)->update($payload);
        } else {
            AdresaLivrare::create($payload);
        }

        $this->modalAdresa = false;
        $this->resetFormAdresa();
        session()->flash('mesaj', 'Adresa salvata cu succes.');
    }

    public function comutaActivAdresa(int $id): void
    {
        $adresa = $this->client->adrese()->findOrFail($id);
        $adresa->activ = ! $adresa->activ;
        $adresa->save();
    }

    public function inchideModalAdresa(): void
    {
        $this->modalAdresa = false;
        $this->resetFormAdresa();
    }

    private function resetFormAdresa(): void
    {
        $this->adresaId = null;
        $this->adresaDenumire = '';
        $this->adresaOras = '';
        $this->adresaStrada = '';
        $this->adresaNr = '';
        $this->adresaBloc = '';
        $this->adresaScara = '';
        $this->adresaEtaj = '';
        $this->adresaApartament = '';
        $this->adresaSector = '';
        $this->adresaInterfon = '';
        $this->adresaGps = '';
        $this->adresaActiv = true;
        $this->resetErrorBag();
    }

    // ===== Abonament / configurare livrare (tabela produs) =====

    public function configureazaAbonament(int $adresaId): void
    {
        $adresa = $this->client->adrese()->with('produs')->findOrFail($adresaId);
        $this->resetFormAbonament();
        $this->abonamentAdresaId = $adresa->id;

        if ($adresa->produs) {
            $p = $adresa->produs;
            $this->abTip = $p->abonament;
            $this->abDenumireAbonament = $p->denumire_abonament ?? '';
            $this->abNrBidoane = $p->nr_bidoane;
            $this->abNrBidoane11l = $p->nr_bidoane_11l;
            $this->abPret = (string) $p->pret;
            $this->abPret11l = (string) $p->pret_11l;
            $this->abPretSuplimentar19l = $p->pret_suplimentar_19l !== null ? (string) $p->pret_suplimentar_19l : '0.00';
            $this->abPretSuplimentar11l = $p->pret_suplimentar_11l !== null ? (string) $p->pret_suplimentar_11l : '0.00';
            $this->abFrecventa = $p->frecventa !== null ? (string) $p->frecventa : '';
            $this->abZiLivrare = $p->zi_livrare ? $p->zi_livrare->format('Y-m-d') : '';
            $this->abIdMasina = $p->id_masina;
            $this->abIdDepozit = $p->id_depozit;
            $this->abObservatii = $p->observatii ?? '';
        } else {
            // Default sensibil pentru noua configurare: zi_livrare = azi
            $this->abZiLivrare = now()->toDateString();
        }

        $this->modalAbonament = true;
    }

    public function inchideModalAbonament(): void
    {
        $this->modalAbonament = false;
        $this->resetFormAbonament();
    }

    public function salveazaAbonament(): void
    {
        // Validare adaptiva per tip
        $reguliComune = [
            'abTip' => ['required', 'in:0,1,2'],
            'abIdMasina' => ['nullable', 'exists:cars,id'],
            'abIdDepozit' => ['nullable', 'exists:deposits,id'],
            'abObservatii' => ['nullable', 'string'],
        ];

        if ($this->abTip === Produs::TIP_ABONAMENT) {
            $reguli = array_merge($reguliComune, [
                'abDenumireAbonament' => ['required', 'string', 'max:255'],
                'abPret' => ['required', 'numeric', 'min:0'],
                'abNrBidoane' => ['required', 'integer', 'min:0'],
                'abNrBidoane11l' => ['required', 'integer', 'min:0'],
                'abPretSuplimentar19l' => ['required', 'numeric', 'min:0'],
                'abPretSuplimentar11l' => ['required', 'numeric', 'min:0'],
                'abZiLivrare' => ['required', 'date'],
            ]);
        } elseif ($this->abTip === Produs::TIP_PER_BUCATA) {
            $reguli = array_merge($reguliComune, [
                'abPret' => ['required', 'numeric', 'min:0'],
                'abPret11l' => ['required', 'numeric', 'min:0'],
            ]);
        } else {
            // tip 2 (purificator/filtre) — pret lunar + data prima facturare
            $reguli = array_merge($reguliComune, [
                'abPret' => ['required', 'numeric', 'min:0'],
                'abZiLivrare' => ['required', 'date'],
            ]);
        }

        $date = $this->validate($reguli, [
            'abTip.required' => 'Selecteaza tipul de configurare.',
            'abDenumireAbonament.required' => 'Denumirea abonamentului este obligatorie.',
            'abPret.required' => 'Pretul abonamentului este obligatoriu.',
            'abNrBidoane.required' => 'Cantitatea de bidoane 19L inclusa este obligatorie.',
            'abNrBidoane11l.required' => 'Cantitatea de bidoane 11L inclusa este obligatorie.',
            'abPretSuplimentar19l.required' => 'Pretul consumului suplimentar 19L este obligatoriu.',
            'abPretSuplimentar11l.required' => 'Pretul consumului suplimentar 11L este obligatoriu.',
            'abPret11l.required' => 'Pretul 11L este obligatoriu.',
            'abZiLivrare.required' => 'Selecteaza data primei livrari.',
        ]);

        // Pentru abonament: cel putin una dintre cantitatile de bidoane trebuie sa fie > 0
        if ($this->abTip === Produs::TIP_ABONAMENT
            && (int) $this->abNrBidoane === 0
            && (int) $this->abNrBidoane11l === 0) {
            $this->addError('abNrBidoane', 'Abonamentul trebuie sa includa cel putin un bidon (19L sau 11L).');
            return;
        }

        // Semantica preturilor difera per tip:
        //  - ABONAMENT (1): pret = pret fix lunar; suplimentar_* = consum peste pachet
        //  - PER BUCATA (0): pret = pret/bidon 19L; pret_11l = pret/bidon 11L
        //  - FILTRE/PURIFICATOR (2): pret = taxa lunara serviciu; nr_bidoane = 0
        $estePerBucata = $this->abTip === Produs::TIP_PER_BUCATA;
        $esteAbonament = $this->abTip === Produs::TIP_ABONAMENT;
        $esteFiltre    = $this->abTip === Produs::TIP_FILTRE;

        $payload = [
            'id_adresa' => $this->abonamentAdresaId,
            'id_client' => $this->client->id,
            'abonament' => $this->abTip,
            'denumire_abonament' => $esteAbonament ? trim($this->abDenumireAbonament) : null,
            'nr_bidoane' => $esteAbonament ? (int) $this->abNrBidoane : 0,
            'nr_bidoane_11l' => $esteAbonament ? (int) $this->abNrBidoane11l : 0,
            'pret' => ($esteAbonament || $estePerBucata || $esteFiltre) ? $this->abPret : 0,
            'pret_11l' => $estePerBucata ? $this->abPret11l : 0,
            'pret_suplimentar_19l' => $esteAbonament ? $this->abPretSuplimentar19l : null,
            'pret_suplimentar_11l' => $esteAbonament ? $this->abPretSuplimentar11l : null,
            'frecventa' => null,
            'zi_livrare' => ($esteAbonament || $esteFiltre) && $this->abZiLivrare !== '' ? $this->abZiLivrare : null,
            'id_masina' => $this->abIdMasina,
            'id_depozit' => $this->abIdDepozit,
            'observatii' => $this->abObservatii ?: null,
        ];

        // Relatie 1:1 — updateOrCreate pe id_adresa (UNIQUE).
        Produs::updateOrCreate(
            ['id_adresa' => $this->abonamentAdresaId],
            $payload
        );

        $this->modalAbonament = false;
        $this->resetFormAbonament();
        session()->flash('mesaj', 'Configurare livrare salvata cu succes.');
    }

    private function resetFormAbonament(): void
    {
        $this->abonamentAdresaId = null;
        $this->abTip = Produs::TIP_ABONAMENT;
        $this->abDenumireAbonament = '';
        $this->abNrBidoane = 0;
        $this->abNrBidoane11l = 0;
        $this->abPret = '0.00';
        $this->abPret11l = '0.00';
        $this->abPretSuplimentar19l = '0.00';
        $this->abPretSuplimentar11l = '0.00';
        $this->abFrecventa = '';
        $this->abZiLivrare = '';
        $this->abIdMasina = null;
        $this->abIdDepozit = Deposit::implicit()?->id;
        $this->abObservatii = '';
        $this->resetErrorBag();
    }

    // ===== Sub-modal Aparat (dozator cu bidoane) =====

    public function deschideModalAparat(?int $id = null, ?int $adresaId = null): void
    {
        $this->resetFormAparat();
        if ($adresaId) {
            $this->abonamentAdresaId = $adresaId;
        }
        if ($id) {
            $aparat = Dozator::where('id_client', $this->client->id)
                ->findOrFail($id);
            if (! $this->abonamentAdresaId) {
                $this->abonamentAdresaId = $aparat->id_adresa;
            }
            $this->aparatEditId      = $aparat->id;
            $this->aparatIdProdus    = $aparat->id_produs;
            $this->aparatSerie       = $aparat->serie ?? '';
            $this->aparatTranzactie  = $aparat->tranzactie ?? 'custodie';
            $this->aparatDataInstalare  = $aparat->data_instalare?->format('Y-m-d') ?? now()->toDateString();
            $this->aparatDataIgienizare = $aparat->perioada_igenizare?->format('Y-m-d') ?? '';
            $this->aparatObservatii  = $aparat->observatii ?? '';
        }
        $this->modalAparatNou = true;
    }

    public function inchideModalAparat(): void
    {
        $this->modalAparatNou = false;
        $this->resetFormAparat();
    }

    public function salveazaAparat(): void
    {
        $this->validate([
            'aparatIdProdus'       => ['required', 'exists:cost_products,id'],
            'aparatSerie'          => ['nullable', 'string', 'max:100'],
            'aparatTranzactie'     => ['required', 'in:custodie,cumparat'],
            'aparatDataInstalare'  => ['required', 'date'],
            'aparatDataIgienizare' => ['nullable', 'date'],
            'aparatObservatii'     => ['nullable', 'string'],
        ], [
            'aparatIdProdus.required'      => 'Selecteaza produsul.',
            'aparatDataInstalare.required' => 'Data instalarii este obligatorie.',
        ]);

        $payload = [
            'id_client'          => $this->client->id,
            'id_adresa'          => $this->abonamentAdresaId,
            'id_produs'          => $this->aparatIdProdus,
            'serie'              => $this->aparatSerie ?: null,
            'tranzactie'         => $this->aparatTranzactie,
            'data_instalare'     => $this->aparatDataInstalare,
            'perioada_igenizare' => $this->aparatDataIgienizare ?: null,
            'activ'              => true,
            'comanda'            => false,
            'observatii'         => $this->aparatObservatii ?: null,
        ];

        if ($this->aparatEditId) {
            Dozator::where('id_client', $this->client->id)
                ->where('id', $this->aparatEditId)
                ->update($payload);
        } else {
            Dozator::create($payload);
        }

        $this->inchideModalAparat();
    }

    public function stergeAparat(int $id): void
    {
        $ap = Dozator::where('id_client', $this->client->id)->findOrFail($id);
        $ap->update(['activ' => ! $ap->activ]);
    }

    private function resetFormAparat(): void
    {
        $this->aparatEditId         = null;
        $this->aparatIdProdus       = null;
        $this->aparatSerie          = '';
        $this->aparatTranzactie     = 'custodie';
        $this->aparatDataInstalare  = now()->toDateString();
        $this->aparatDataIgienizare = now()->addMonths(6)->toDateString();
        $this->aparatObservatii     = '';
        $this->resetErrorBag();
    }

    // ===== Sub-modal Purificator (dozator cu filtre) =====

    public function deschideModalPurificator(?int $id = null, ?int $adresaId = null): void
    {
        $this->resetFormPurificator();
        if ($adresaId) {
            $this->abonamentAdresaId = $adresaId;
        }
        if ($id) {
            $pf = DozatorFiltre::where('id_client', $this->client->id)
                ->findOrFail($id);
            if (! $this->abonamentAdresaId) {
                $this->abonamentAdresaId = $pf->id_adresa;
            }
            $this->purificatorEditId        = $pf->id;
            $this->purificatorIdProdus      = $pf->id_produs;
            $this->purificatorSerie         = $pf->serie ?? '';
            $this->purificatorTranzactie    = $pf->tranzactie ?? 'custodie';
            $this->purificatorDataInstalare = $pf->data_instalare?->format('Y-m-d') ?? now()->toDateString();
            $this->purificatorDataMentenanta = $pf->data_urmatoare_mentenanta?->format('Y-m-d') ?? '';
            $this->purificatorGarantie      = $pf->suma_garantie !== null ? (string) $pf->suma_garantie : '0.00';
            $this->purificatorObservatii    = $pf->observatii ?? '';
        }
        $this->modalPurificatorNou = true;
    }

    public function inchideModalPurificator(): void
    {
        $this->modalPurificatorNou = false;
        $this->resetFormPurificator();
    }

    public function salveazaPurificator(): void
    {
        $this->validate([
            'purificatorIdProdus'       => ['required', 'exists:cost_products,id'],
            'purificatorSerie'          => ['nullable', 'string', 'max:100'],
            'purificatorTranzactie'     => ['required', 'in:custodie,cumparat'],
            'purificatorDataInstalare'  => ['required', 'date'],
            'purificatorDataMentenanta' => ['nullable', 'date'],
            'purificatorGarantie'       => ['required', 'numeric', 'min:0'],
            'purificatorObservatii'     => ['nullable', 'string'],
        ], [
            'purificatorIdProdus.required'      => 'Selecteaza produsul.',
            'purificatorDataInstalare.required' => 'Data instalarii este obligatorie.',
        ]);

        $payload = [
            'id_client'                 => $this->client->id,
            'id_adresa'                 => $this->abonamentAdresaId,
            'id_produs'                 => $this->purificatorIdProdus,
            'serie'                     => $this->purificatorSerie ?: null,
            'tranzactie'                => $this->purificatorTranzactie,
            'data_instalare'            => $this->purificatorDataInstalare,
            'data_urmatoare_mentenanta' => $this->purificatorDataMentenanta ?: null,
            'status'                    => DozatorFiltre::STATUS_ACTIV,
            'suma_garantie'             => $this->purificatorGarantie,
            'observatii'                => $this->purificatorObservatii ?: null,
        ];

        if ($this->purificatorEditId) {
            DozatorFiltre::where('id_client', $this->client->id)
                ->where('id', $this->purificatorEditId)
                ->update($payload);
        } else {
            DozatorFiltre::create($payload);
        }

        $this->inchideModalPurificator();
    }

    public function stergePurificator(int $id): void
    {
        $pf = DozatorFiltre::where('id_client', $this->client->id)->findOrFail($id);
        $pf->update([
            'status' => $pf->esteActiv() ? DozatorFiltre::STATUS_RETRAS : DozatorFiltre::STATUS_ACTIV,
        ]);
    }

    private function resetFormPurificator(): void
    {
        $this->purificatorEditId        = null;
        $this->purificatorIdProdus      = null;
        $this->purificatorSerie         = '';
        $this->purificatorTranzactie    = 'custodie';
        $this->purificatorDataInstalare = now()->toDateString();
        $this->purificatorDataMentenanta = now()->addMonths(12)->toDateString();
        $this->purificatorGarantie      = '0.00';
        $this->purificatorObservatii    = '';
        $this->resetErrorBag();
    }

    // ===== Vizite (Dozatoare cu bidoane — igienizari) =====

    public function marcheazaIgienizareAzi(int $id): void
    {
        $d = Dozator::where('id_client', $this->client->id)->findOrFail($id);
        DB::transaction(function () use ($d) {
            Vizita::create([
                'id_dozator'     => $d->id,
                'id_client'      => $d->id_client,
                'id_adresa'      => $d->id_adresa,
                'id_masina'      => $d->id_masina,
                'data_vizita'    => now()->toDateString(),
                'data_urmatoare' => now()->addMonths(6)->toDateString(),
                'pret'           => 0,
                'livrat'         => true,
                'achitat'        => false,
            ]);
            $d->update(['perioada_igenizare' => now()->addMonths(6)->toDateString()]);
        });
        session()->flash('mesaj', 'Igienizare inregistrata. Urmatoarea: ' . now()->addMonths(6)->format('d.m.Y'));
    }

    public function deschideModalVizite(int $id): void
    {
        $d = Dozator::where('id_client', $this->client->id)->findOrFail($id);
        $this->viziteForDozatorId = $d->id;
        $this->vizDataVizita      = now()->toDateString();
        $this->vizDataUrmatoare   = now()->addMonths(6)->toDateString();
        $this->vizPret            = '';
        $this->vizObservatii      = '';
        $this->modalVizite        = true;
    }

    public function inchideModalVizite(): void
    {
        $this->modalVizite        = false;
        $this->viziteForDozatorId = null;
        $this->vizDataVizita      = '';
        $this->vizDataUrmatoare   = '';
        $this->vizPret            = '';
        $this->vizObservatii      = '';
        $this->resetErrorBag();
    }

    public function adaugaVizita(): void
    {
        $this->validate([
            'vizDataVizita'    => ['required', 'date'],
            'vizDataUrmatoare' => ['nullable', 'date', 'after_or_equal:vizDataVizita'],
            'vizPret'          => ['nullable', 'numeric', 'min:0'],
            'vizObservatii'    => ['nullable', 'string'],
        ], [
            'vizDataVizita.required'          => 'Data vizitei este obligatorie.',
            'vizDataUrmatoare.after_or_equal' => 'Data urmatoarei igienizari trebuie sa fie >= data vizitei.',
        ]);

        $d = Dozator::where('id_client', $this->client->id)->findOrFail($this->viziteForDozatorId);

        DB::transaction(function () use ($d) {
            Vizita::create([
                'id_dozator'     => $d->id,
                'id_client'      => $d->id_client,
                'id_adresa'      => $d->id_adresa,
                'id_masina'      => $d->id_masina,
                'data_vizita'    => $this->vizDataVizita,
                'data_urmatoare' => $this->vizDataUrmatoare ?: null,
                'pret'           => $this->vizPret !== '' ? (float) $this->vizPret : 0,
                'observatii'     => $this->vizObservatii ?: null,
                'livrat'         => true,
                'achitat'        => false,
            ]);
            if ($this->vizDataUrmatoare) {
                $d->update(['perioada_igenizare' => $this->vizDataUrmatoare]);
            }
        });

        session()->flash('mesaj', 'Vizita inregistrata. Perioada urmatoare actualizata.');
        $this->inchideModalVizite();
    }

    // ===== Interventii (Dozatoare cu filtre — mentenanta) =====

    public function marcheazaInterventieAzi(int $id): void
    {
        $d = DozatorFiltre::where('id_client', $this->client->id)->findOrFail($id);
        DB::transaction(function () use ($d) {
            DozatorFiltreIstoric::create([
                'id_dozator_filtre' => $d->id,
                'id_client'         => $d->id_client,
                'id_masina'         => $d->id_masina,
                'data_interventie'  => now()->toDateString(),
                'data_urmatoare'    => now()->addMonths(12)->toDateString(),
                'pret'              => 0,
            ]);
            $d->update([
                'data_ultima_mentenanta'    => now()->toDateString(),
                'data_urmatoare_mentenanta' => now()->addMonths(12)->toDateString(),
            ]);
        });
        session()->flash('mesaj', 'Interventie inregistrata. Urmatoarea: ' . now()->addMonths(12)->format('d.m.Y'));
    }

    public function deschideModalInterventie(int $id): void
    {
        $d = DozatorFiltre::where('id_client', $this->client->id)->findOrFail($id);
        $this->interventieForFiltruId = $d->id;
        $this->intDataInterventie     = now()->toDateString();
        $this->intDataUrmatoare       = now()->addMonths(12)->toDateString();
        $this->intPret                = '';
        $this->intObservatii          = '';
        $this->modalInterventie       = true;
    }

    public function inchideModalInterventie(): void
    {
        $this->modalInterventie       = false;
        $this->interventieForFiltruId = null;
        $this->intDataInterventie     = '';
        $this->intDataUrmatoare       = '';
        $this->intPret                = '';
        $this->intObservatii          = '';
        $this->resetErrorBag();
    }

    public function adaugaInterventie(): void
    {
        $this->validate([
            'intDataInterventie' => ['required', 'date'],
            'intDataUrmatoare'   => ['nullable', 'date', 'after_or_equal:intDataInterventie'],
            'intPret'            => ['nullable', 'numeric', 'min:0'],
            'intObservatii'      => ['nullable', 'string'],
        ], [
            'intDataInterventie.required'          => 'Data interventiei este obligatorie.',
            'intDataUrmatoare.after_or_equal'      => 'Data urmatoarei mentenante trebuie sa fie >= data interventiei.',
        ]);

        $d = DozatorFiltre::where('id_client', $this->client->id)->findOrFail($this->interventieForFiltruId);

        DB::transaction(function () use ($d) {
            DozatorFiltreIstoric::create([
                'id_dozator_filtre' => $d->id,
                'id_client'         => $d->id_client,
                'id_masina'         => $d->id_masina,
                'data_interventie'  => $this->intDataInterventie,
                'data_urmatoare'    => $this->intDataUrmatoare ?: null,
                'pret'              => $this->intPret !== '' ? (float) $this->intPret : 0,
                'observatii'        => $this->intObservatii ?: null,
            ]);
            $update = ['data_ultima_mentenanta' => $this->intDataInterventie];
            if ($this->intDataUrmatoare) {
                $update['data_urmatoare_mentenanta'] = $this->intDataUrmatoare;
            }
            $d->update($update);
        });

        session()->flash('mesaj', 'Interventie inregistrata. Datele de mentenanta actualizate.');
        $this->inchideModalInterventie();
    }

    // ===== Recipienti — administrare admin (corectie manuala + jurnal) =====

    public function deschideRecipientiAdmin(int $adresaId): void
    {
        $adresa = $this->client->adrese()->findOrFail($adresaId);
        $this->resetFormRecipientiAdmin();
        $this->recAdresaId = $adresa->id;
        $this->recAdresaDenumire = $adresa->denumire;
        $this->recAdminData = now()->toDateString();
        $this->modalRecipientiAdmin = true;
    }

    public function inchideRecipientiAdmin(): void
    {
        $this->modalRecipientiAdmin = false;
        $this->resetFormRecipientiAdmin();
    }

    public function salveazaRecipientiAdmin(): void
    {
        $date = $this->validate([
            'recAdminLasati19l' => ['required', 'integer', 'min:0'],
            'recAdminRecuperati19l' => ['required', 'integer', 'min:0'],
            'recAdminLasati11l' => ['required', 'integer', 'min:0'],
            'recAdminRecuperati11l' => ['required', 'integer', 'min:0'],
            'recAdminData' => ['required', 'date'],
            'recAdminObservatii' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'recAdminLasati19l.required' => 'Completeaza cantitatea lasata 19L.',
            'recAdminRecuperati19l.required' => 'Completeaza cantitatea recuperata 19L.',
            'recAdminLasati11l.required' => 'Completeaza cantitatea lasata 11L.',
            'recAdminRecuperati11l.required' => 'Completeaza cantitatea recuperata 11L.',
            'recAdminData.required' => 'Selecteaza data miscarii.',
            'recAdminObservatii.required' => 'Motivul corectiei manuale e obligatoriu.',
            'recAdminObservatii.min' => 'Motivul trebuie sa aiba minim 3 caractere.',
        ]);

        if (! $this->recAdresaId) {
            return;
        }

        // Validare ca adresa apartine clientului (defense in depth)
        $adresa = $this->client->adrese()->where('id', $this->recAdresaId)->firstOrFail();

        // Soldul recipientilor poate fi negativ (datorie firma fata de client)
        // — nu mai blocam recuperari care duc soldul sub 0.

        Recipient::create([
            'id_client' => $this->client->id,
            'id_adresa' => $adresa->id,
            'lasati' => (int) $date['recAdminLasati19l'],
            'recuperati' => (int) $date['recAdminRecuperati19l'],
            'lasati_11l' => (int) $date['recAdminLasati11l'],
            'recuperati_11l' => (int) $date['recAdminRecuperati11l'],
            'data' => $date['recAdminData'],
            'id_comanda' => null, // miscare manuala admin — fara comanda asociata
            'id_utilizator' => auth()->id(),
            'observatii' => $date['recAdminObservatii'],
        ]);

        $this->modalRecipientiAdmin = false;
        $this->resetFormRecipientiAdmin();
        session()->flash('mesaj', 'Miscare manuala recipienti inregistrata.');
    }

    /**
     * Sterge o miscare de recipienti — permis DOAR pentru miscarile manuale
     * (id_comanda IS NULL). Miscarile legate de o comanda raman immutable —
     * pentru a corecta, admin adauga o noua miscare de compensare.
     */
    public function stergeMiscareAdmin(int $idMiscare): void
    {
        $miscare = Recipient::where('id', $idMiscare)
            ->where('id_client', $this->client->id)
            ->whereNull('id_comanda')
            ->first();

        if (! $miscare) {
            session()->flash('mesaj_eroare', 'Doar miscarile manuale (fara comanda asociata) pot fi sterse.');
            return;
        }

        $miscare->delete();
        session()->flash('mesaj', 'Miscare manuala stearsa.');
    }

    public function filtreazaJurnalAdresa(?int $adresaId): void
    {
        $this->recFiltruAdresa = $adresaId;
    }

    private function resetFormRecipientiAdmin(): void
    {
        $this->recAdresaId = null;
        $this->recAdresaDenumire = '';
        $this->recAdminLasati19l = 0;
        $this->recAdminRecuperati19l = 0;
        $this->recAdminLasati11l = 0;
        $this->recAdminRecuperati11l = 0;
        $this->recAdminData = '';
        $this->recAdminObservatii = '';
        $this->resetErrorBag();
    }

    /**
     * Parseaza string-ul "lat, lng" (format Google Maps) in cele doua coloane DB.
     * Accepta atat "44.4325, 26.1025" cat si "44.4325,26.1025" sau cu mai multe spatii.
     * Returneaza [null, null] pentru input gol; adauga eroare si returneaza [null, null]
     * daca formatul / domeniul de valori e invalid.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function parseazaGps(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [null, null];
        }

        // Format: "<lat>, <lng>" — virgula obligatorie, spatii ignorate.
        if (! preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $input, $m)) {
            $this->addError('adresaGps', 'Format invalid. Foloseste "lat, lng" (ex: 44.4325, 26.1025).');
            return [null, null];
        }

        $lat = (float) $m[1];
        $lng = (float) $m[2];

        if ($lat < -90 || $lat > 90) {
            $this->addError('adresaGps', 'Latitudinea trebuie sa fie intre -90 si 90.');
            return [null, null];
        }
        if ($lng < -180 || $lng > 180) {
            $this->addError('adresaGps', 'Longitudinea trebuie sa fie intre -180 si 180.');
            return [null, null];
        }

        return [$lat, $lng];
    }

    // ===== Tab Contract (Faza 6.2) =====

    /**
     * Incarca contractul curent al clientului in state. Daca lipseste, il
     * genereaza din template-ul global cu placeholderele substituite.
     */
    public function incarcaContract(): void
    {
        $contract = ContracteService::obtineContract($this->client);
        $this->contractId = $contract->id;
        $this->contractHtml = (string) ($contract->continut_html ?? '');
        $this->contractMesaj = null;
        $this->contractEroare = null;
    }

    /**
     * Persisteaza HTML-ul curent al editorului pe contractul clientului.
     */
    public function salveazaContract(): void
    {
        $this->validate([
            'contractHtml' => 'required|string|min:10',
        ], [
            'contractHtml.required' => 'Continutul contractului nu poate fi gol.',
            'contractHtml.min' => 'Continutul contractului este prea scurt.',
        ]);

        $contract = ContracteService::obtineContract($this->client);
        $contract->update(['continut_html' => $this->contractHtml]);
        $this->contractId = $contract->id;
        $this->contractMesaj = 'Contract salvat cu succes.';
        $this->contractEroare = null;
    }

    /**
     * Suprascrie contractul cu o regenerare din template-ul global +
     * placeholdere substituite. Editarile manuale curente sunt pierdute.
     */
    public function regenereazaContract(): void
    {
        $contract = ContracteService::regenereazaDinTemplate($this->client);
        $this->contractId = $contract->id;
        $this->contractHtml = (string) ($contract->continut_html ?? '');
        $this->contractMesaj = 'Contractul a fost regenerat din template-ul global.';
        $this->contractEroare = null;
    }

    public function render()
    {
        // Problemele clientului — afișate în tab-ul `probleme` (Faza 3.2).
        $problemeClient = Problema::with(['adresa', 'masina'])
            ->where('id_client', $this->client->id)
            ->orderByDesc('data_livrare')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Dozatoarele clientului — afișate în tab-ul `dozatoare` (Faza 4.1).
        $dozatoareClient = Dozator::with(['adresa', 'masina', 'produs'])
            ->withCount('vizite')
            ->where('id_client', $this->client->id)
            ->orderByDesc('activ')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Dozatoarele cu filtre ale clientului — afisate in tab-ul `dozatoare` (Faza 4.3).
        // Aceeasi pagina/tab cu Bidoane; admin distinge prin sectiuni separate.
        // ORDER BY status asc => 'activ' inainte de 'retras' (alfabetic, portabil
        // pentru SQLite + MariaDB; FIELD() nu exista in SQLite).
        $dozatoareFiltreClient = DozatorFiltre::with(['adresa', 'masina', 'produs'])
            ->withCount('istoric')
            ->where('id_client', $this->client->id)
            ->orderBy('status', 'asc')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $numarFiltre = $dozatoareFiltreClient->where('status', DozatorFiltre::STATUS_ACTIV)->count();

        // Adresele clientului — incarcate o singura data si refolosite pentru solduri.
        $adrese = $this->client->adrese()
            ->with(['produs.masina', 'produs.depozit'])
            ->orderByDesc('activ')
            ->orderBy('denumire')
            ->get();

        // Sold recipienti per adresa — folosit in tab Adrese (badge) si tab Recipienti (header).
        $solduriRecipienti = [];
        $totalSold19l = 0;
        $totalSold11l = 0;
        foreach ($adrese as $a) {
            $sold = Recipient::soldPerAdresa($a->id);
            $solduriRecipienti[$a->id] = $sold;
            $totalSold19l += $sold['19l'];
            $totalSold11l += $sold['11l'];
        }

        // Jurnal recipienti — toate miscarile clientului, ordonate desc dupa data.
        // Daca user-ul a filtrat pe o adresa anume, restrangem.
        $jurnalRecipienti = Recipient::with(['adresa', 'comanda', 'utilizator'])
            ->where('id_client', $this->client->id)
            ->when($this->recFiltruAdresa, fn ($q, $idA) => $q->where('id_adresa', $idA))
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        // Vizite / Interventii — incarcate la cerere (cand modalul e deschis)
        $viziteList = $this->viziteForDozatorId
            ? Vizita::where('id_dozator', $this->viziteForDozatorId)
                ->orderByDesc('data_vizita')
                ->limit(20)
                ->get()
            : collect();

        $interventiiList = $this->interventieForFiltruId
            ? DozatorFiltreIstoric::where('id_dozator_filtre', $this->interventieForFiltruId)
                ->orderByDesc('data_interventie')
                ->limit(20)
                ->get()
            : collect();

        return view('livewire.clienti.detalii', [
            'adrese' => $adrese,
            'numarAdrese' => $adrese->count(),
            'masiniDisponibile' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depoziteDisponibile' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'produseCatalog' => CostProduct::where('activ', true)->orderBy('denumire')->get(),
            'probleme' => $problemeClient,
            'numarProbleme' => $problemeClient->count(),
            'dozatoare' => $dozatoareClient,
            'numarDozatoare' => $dozatoareClient->where('activ', true)->count(),
            'dozatoareFiltre' => $dozatoareFiltreClient,
            'numarFiltre' => $numarFiltre,
            'solduriRecipienti' => $solduriRecipienti,
            'totalSold19l' => $totalSold19l,
            'totalSold11l' => $totalSold11l,
            'jurnalRecipienti' => $jurnalRecipienti,
            'viziteList' => $viziteList,
            'interventiiList' => $interventiiList,
        ]);
    }
}
