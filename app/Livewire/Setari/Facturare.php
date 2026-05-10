<?php

namespace App\Livewire\Setari;

use App\Models\FacturareSetari;
use App\Services\Facturare\FacturareException;
use App\Services\Facturare\FacturareService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.1 — Configurare furnizori de facturare electronica.
 *
 * UI cu un card per furnizor disponibil (Oblio, SmartBill), care expune
 * exact campurile cerute de docs API-ul respectivului furnizor:
 *   - Oblio: client_id, client_secret, cif, seriesName, language, currency, dueDateOffsetDays
 *   - SmartBill: username, token, companyVatCode, seriesName, currency, dueDateOffsetDays, isDraft
 *
 * Buton „Salveaza" pe fiecare card persisteaza setarile criptat in DB.
 * Buton „Test conexiune" face un apel real la API (Oblio) sau valideaza
 * setarile (SmartBill stub).
 * Buton „Activeaza" marcheaza furnizorul ca activ si dezactiveaza ceilalti
 * (regula DOCUMENTATION.md §7.4: un singur furnizor activ).
 */
#[Layout('layouts.app')]
class Facturare extends Component
{
    /**
     * Valorile formularelor pentru fiecare furnizor.
     * Cheia = cod furnizor; valoarea = array cu campurile specifice.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $formulare = [];

    /**
     * Care furnizor a fost ultima data testat (pentru afisarea mesajului).
     */
    public ?string $ultimulTestFurnizor = null;
    public ?bool $ultimulTestRezultat = null;
    public ?string $ultimulTestMesaj = null;

    public function mount(): void
    {
        // Pre-incarca setarile existente in formulare; daca nu exista
        // inregistrare pentru un furnizor, foloseste valori default.
        foreach (array_keys(FacturareService::furnizoriDisponibili()) as $cod) {
            $existent = FacturareSetari::where('furnizor', $cod)->first();
            $this->formulare[$cod] = $this->cuValoriDefault($cod, $existent?->setari ?? []);
        }
    }

    /**
     * Returneaza setarile incarcate cu valori default pentru campurile
     * lipsa (currency=RON, language=RO, dueDateOffsetDays=15).
     *
     * @param  array<string, mixed>  $existente
     * @return array<string, mixed>
     */
    private function cuValoriDefault(string $cod, array $existente): array
    {
        $default = match ($cod) {
            FacturareSetari::FURNIZOR_OBLIO => [
                'client_id' => '',
                'client_secret' => '',
                'cif' => '',
                'seriesName' => 'WF',
                'language' => 'RO',
                'currency' => 'RON',
                'dueDateOffsetDays' => 15,
            ],
            FacturareSetari::FURNIZOR_SMARTBILL => [
                'username' => '',
                'token' => '',
                'companyVatCode' => '',
                'seriesName' => 'WF',
                'currency' => 'RON',
                'dueDateOffsetDays' => 15,
                'isDraft' => false,
            ],
            default => [],
        };
        return array_merge($default, $existente);
    }

    /**
     * Salveaza setarile pentru un furnizor (creeaza/updateOrCreate intrarea
     * in DB). Nu activeaza automat — adminul apasa „Activeaza" separat.
     */
    public function salveaza(string $cod): void
    {
        $this->validateazaFormular($cod);
        $setari = $this->formulare[$cod] ?? [];

        FacturareSetari::updateOrCreate(
            ['furnizor' => $cod],
            ['setari' => $setari]
        );

        // Reset cache token Oblio dupa schimbare credentiale (relevant doar
        // daca era deja configurat; idempotent altfel).
        if ($cod === FacturareSetari::FURNIZOR_OBLIO) {
            try {
                $svc = FacturareService::pentruFurnizor($cod, $setari);
                if (method_exists($svc, 'stergeTokenCache')) {
                    $svc->stergeTokenCache();
                }
            } catch (\Throwable) {
                // Nu blocheaza save-ul daca cache forget esueaza.
            }
        }

        session()->flash('mesaj', "Setari {$this->etichetaFurnizor($cod)} salvate.");
    }

    /**
     * Test conexiune cu API-ul furnizorului.
     */
    public function testeazaConexiune(string $cod): void
    {
        $setari = $this->formulare[$cod] ?? [];
        try {
            $svc = FacturareService::pentruFurnizor($cod, $setari);
            $rez = $svc->testConexiune();
            $this->ultimulTestFurnizor = $cod;
            $this->ultimulTestRezultat = (bool) $rez['ok'];
            $this->ultimulTestMesaj = $rez['mesaj'];
        } catch (FacturareException $e) {
            $this->ultimulTestFurnizor = $cod;
            $this->ultimulTestRezultat = false;
            $this->ultimulTestMesaj = $e->getMessage();
        }
    }

    /**
     * Activeaza un furnizor: marcheaza activ=true pe el si activ=false pe
     * toti ceilalti (regula: un singur activ).
     */
    public function activeaza(string $cod): void
    {
        $setari = FacturareSetari::where('furnizor', $cod)->first();
        if (! $setari) {
            session()->flash('eroare', 'Salveaza intai setarile inainte de activare.');
            return;
        }
        if (! $setari->esteConfigurat()) {
            session()->flash('eroare', "Setarile {$setari->eticheta()} sunt incomplete. Completeaza toate campurile obligatorii.");
            return;
        }

        FacturareSetari::query()->update(['activ' => false]);
        $setari->activ = true;
        $setari->save();

        session()->flash('mesaj', "Furnizor activ: {$setari->eticheta()}.");
    }

    public function dezactiveaza(string $cod): void
    {
        FacturareSetari::where('furnizor', $cod)->update(['activ' => false]);
        session()->flash('mesaj', "Furnizor dezactivat: {$this->etichetaFurnizor($cod)}.");
    }

    /**
     * Validare per furnizor — campurile diferite cer reguli diferite.
     */
    private function validateazaFormular(string $cod): void
    {
        $reguli = match ($cod) {
            FacturareSetari::FURNIZOR_OBLIO => [
                "formulare.{$cod}.client_id" => 'required|email|max:255',
                "formulare.{$cod}.client_secret" => 'required|string|max:255',
                "formulare.{$cod}.cif" => 'required|string|max:20',
                "formulare.{$cod}.seriesName" => 'required|string|max:20',
                "formulare.{$cod}.language" => 'required|string|in:RO,EN',
                "formulare.{$cod}.currency" => 'required|string|size:3',
                "formulare.{$cod}.dueDateOffsetDays" => 'required|integer|min:0|max:365',
            ],
            FacturareSetari::FURNIZOR_SMARTBILL => [
                "formulare.{$cod}.username" => 'required|email|max:255',
                "formulare.{$cod}.token" => 'required|string|max:255',
                "formulare.{$cod}.companyVatCode" => 'required|string|max:20',
                "formulare.{$cod}.seriesName" => 'required|string|max:20',
                "formulare.{$cod}.currency" => 'required|string|size:3',
                "formulare.{$cod}.dueDateOffsetDays" => 'required|integer|min:0|max:365',
                "formulare.{$cod}.isDraft" => 'boolean',
            ],
            default => [],
        };

        $mesaje = [
            "formulare.{$cod}.client_id.required" => 'Email-ul contului Oblio este obligatoriu.',
            "formulare.{$cod}.client_id.email" => 'Trebuie sa fie o adresa email valida.',
            "formulare.{$cod}.client_secret.required" => 'API token Oblio (din Settings > Account Data) este obligatoriu.',
            "formulare.{$cod}.cif.required" => 'CIF-ul firmei este obligatoriu (ex: RO46043131).',
            "formulare.{$cod}.seriesName.required" => 'Seria de factura este obligatorie (ex: WF).',
            "formulare.{$cod}.username.required" => 'Username-ul SmartBill (email cont) este obligatoriu.',
            "formulare.{$cod}.token.required" => 'API token SmartBill este obligatoriu.',
            "formulare.{$cod}.companyVatCode.required" => 'CIF-ul firmei este obligatoriu pentru SmartBill.',
        ];

        $this->validate($reguli, $mesaje);
    }

    public function etichetaFurnizor(string $cod): string
    {
        return match ($cod) {
            FacturareSetari::FURNIZOR_OBLIO => 'Oblio.eu',
            FacturareSetari::FURNIZOR_SMARTBILL => 'SmartBill',
            default => ucfirst($cod),
        };
    }

    public function render()
    {
        $setariDb = FacturareSetari::all()->keyBy('furnizor');

        return view('livewire.setari.facturare', [
            'furnizori' => array_keys(FacturareService::furnizoriDisponibili()),
            'setariDb' => $setariDb,
        ]);
    }
}
