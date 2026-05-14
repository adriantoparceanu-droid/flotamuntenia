<?php

namespace App\Services;

use App\Models\SetariPlatforma;
use Illuminate\Support\Facades\Cache;

/**
 * Serviciu pentru gestionarea modulelor opționale ale platformei.
 *
 * Starea modulelor se stochează în `setari_platforma` ca perechi cheie/valoare
 * (ex: `modul_portal_client = 1`). Valorile sunt cachuite 60 de secunde pentru
 * a evita query-uri repetate la fiecare request. Cache-ul e invalidat imediat
 * după orice toggle.
 *
 * Utilizare:
 *   ModuleService::isActive(SetariPlatforma::MODUL_PORTAL_CLIENT) // true/false
 *   ModuleService::toggle(SetariPlatforma::MODUL_PORTAL_CLIENT, false)
 */
class ModuleService
{
    private const CACHE_KEY = 'module_settings';
    private const CACHE_TTL = 60;

    /**
     * Verifică dacă un modul este activ.
     *
     * Default: activ (true) — un modul neconfigurat în DB e considerat activ,
     * astfel instalările noi funcționează complet fără configurare suplimentară.
     */
    public static function isActive(string $cheie): bool
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SetariPlatforma::where('cheie', 'like', 'modul_%')
                ->pluck('valoare', 'cheie')
                ->toArray();
        });

        return ($settings[$cheie] ?? '1') === '1';
    }

    /**
     * Activează sau dezactivează un modul și invalidează cache-ul imediat.
     */
    public static function toggle(string $cheie, bool $activ): void
    {
        SetariPlatforma::set($cheie, $activ ? '1' : '0');
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Invalidează manual cache-ul (util în teste sau după operațiuni bulk).
     */
    public static function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Returnează toate modulele cu statusul lor curent din DB.
     * Cheile lipsă din DB sunt considerate active (default '1').
     */
    public static function toateModuleleActive(): array
    {
        return collect(self::definitiiModule())->mapWithKeys(function ($definitie) {
            return [$definitie['cheie'] => self::isActive($definitie['cheie'])];
        })->all();
    }

    /**
     * Definițiile statice ale tuturor modulelor opționale.
     * Ordine: de la modulele cu impact vizibil cel mai mare la cele mai tehnice.
     */
    public static function definitiiModule(): array
    {
        return [
            SetariPlatforma::MODUL_PORTAL_CLIENT => [
                'slug'       => 'portal_client',
                'cheie'      => SetariPlatforma::MODUL_PORTAL_CLIENT,
                'nume'       => 'Portal Client',
                'descriere'  => 'Clienții pot face cont și plasa comenzi online, cu aprobare obligatorie din admin.',
                'icon'       => 'user-group',
                'culoare'    => 'sky',
                'blocheaza'  => 'Rute /portal/*, butonul "Aprobare comenzi" din meniu.',
                'avertizare' => 'Dacă Email & Notificări e dezactivat, clienții nu primesc confirmare cont.',
            ],
            SetariPlatforma::MODUL_COMENZI_RAPIDE => [
                'slug'       => 'comenzi_rapide',
                'cheie'      => SetariPlatforma::MODUL_COMENZI_RAPIDE,
                'nume'       => 'Comenzi Rapide',
                'descriere'  => 'Comenzi ad-hoc fără cont de client, cu adresă liberă și coordonate GPS.',
                'icon'       => 'bolt',
                'culoare'    => 'amber',
                'blocheaza'  => 'Meniu și rute /comenzi-rapide/*.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_PROBLEME => [
                'slug'       => 'probleme',
                'cheie'      => SetariPlatforma::MODUL_PROBLEME,
                'nume'       => 'Probleme / Intervenții',
                'descriere'  => 'Înregistrare probleme sau intervenții la adresele de livrare, incluse în traseu.',
                'icon'       => 'exclamation-triangle',
                'culoare'    => 'orange',
                'blocheaza'  => 'Meniu și rute /probleme/*.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_DOZATOARE => [
                'slug'       => 'dozatoare',
                'cheie'      => SetariPlatforma::MODUL_DOZATOARE,
                'nume'       => 'Dozatoare',
                'descriere'  => 'Gestiune dozatoare cu bidoane și cu filtre, igienizări, mentenanță și vizite.',
                'icon'       => 'cube',
                'culoare'    => 'violet',
                'blocheaza'  => 'Secțiunea Dozatoare din meniu, rute /dozatoare/*.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_RECIPIENTI => [
                'slug'       => 'recipienti',
                'cheie'      => SetariPlatforma::MODUL_RECIPIENTI,
                'nume'       => 'Recipienți (Bidoane Custodie)',
                'descriere'  => 'Urmărire sold bidoane 19L/11L la clienți, jurnal mișcări și corecții manuale.',
                'icon'       => 'archive-box',
                'culoare'    => 'teal',
                'blocheaza'  => 'Tab Recipienți pe fișa clientului, soldul din lista zilnică.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_STOC => [
                'slug'       => 'stoc',
                'cheie'      => SetariPlatforma::MODUL_STOC,
                'nume'       => 'Stoc & Costuri',
                'descriere'  => 'Nomenclator produse, facturi de achiziție și mișcări de stoc IN/OUT/CUSTODIE.',
                'icon'       => 'banknotes',
                'culoare'    => 'emerald',
                'blocheaza'  => 'Meniu și rute /cheltuieli/*. Comenzile nu mai generează mișcări de stoc.',
                'avertizare' => 'Raportul Cheltuieli vs Vânzări devine incomplet fără date de stoc.',
            ],
            SetariPlatforma::MODUL_FACTURARE => [
                'slug'       => 'facturare',
                'cheie'      => SetariPlatforma::MODUL_FACTURARE,
                'nume'       => 'Facturare Electronică',
                'descriere'  => 'Integrare Oblio / SmartBill pentru emiterea facturilor fiscale din aplicație.',
                'icon'       => 'document-text',
                'culoare'    => 'blue',
                'blocheaza'  => 'Butonul "Generează factură" pe comenzi, pagina /setari/facturare.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_CONTRACTE => [
                'slug'       => 'contracte',
                'cheie'      => SetariPlatforma::MODUL_CONTRACTE,
                'nume'       => 'Contracte PDF',
                'descriere'  => 'Generare PDF contracte de prestări servicii per client, cu template editabil.',
                'icon'       => 'document-duplicate',
                'culoare'    => 'indigo',
                'blocheaza'  => 'Tab "Contract" pe fișa clientului, /clienti/{id}/contract.pdf, /setari/contract-template.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_HARTI => [
                'slug'       => 'harti',
                'cheie'      => SetariPlatforma::MODUL_HARTI,
                'nume'       => 'Hărți Google Maps',
                'descriere'  => 'Pin-uri SVG pe harta din lista zilnică și harta șoferului cu navigație live.',
                'icon'       => 'map',
                'culoare'    => 'green',
                'blocheaza'  => 'Harta ascunsă din lista zilnică și traseu șofer (se afișează doar lista text).',
                'avertizare' => 'Necesită GOOGLE_MAPS_API_KEY configurat în .env.',
            ],
            SetariPlatforma::MODUL_RAPOARTE => [
                'slug'       => 'rapoarte',
                'cheie'      => SetariPlatforma::MODUL_RAPOARTE,
                'nume'       => 'Rapoarte',
                'descriere'  => 'Rapoarte: abonamente lipsă, financiar bidoane, stoc curent, cheltuieli vs vânzări.',
                'icon'       => 'chart-pie',
                'culoare'    => 'purple',
                'blocheaza'  => 'Meniu și toate rutele /rapoarte/*.',
                'avertizare' => 'Raportul Cheltuieli vs Vânzări necesită Stoc & Costuri activ.',
            ],
            SetariPlatforma::MODUL_ANAF => [
                'slug'       => 'anaf',
                'cheie'      => SetariPlatforma::MODUL_ANAF,
                'nume'       => 'Validare ANAF',
                'descriere'  => 'Completare automată date firmă prin API ANAF la introducerea CIF-ului.',
                'icon'       => 'magnifying-glass',
                'culoare'    => 'cyan',
                'blocheaza'  => 'Butonul "Validează CIF" din formularul client. Formularul rămâne funcțional manual.',
                'avertizare' => null,
            ],
            SetariPlatforma::MODUL_EMAIL => [
                'slug'       => 'email',
                'cheie'      => SetariPlatforma::MODUL_EMAIL,
                'nume'       => 'Email & Notificări',
                'descriere'  => 'Trimitere email-uri: confirmare cont, comenzi, igienizări, filtre. Șabloane editabile.',
                'icon'       => 'envelope',
                'culoare'    => 'rose',
                'blocheaza'  => 'Toate trimitirile de email. Paginile /setari/template-email și /setari/smtp.',
                'avertizare' => 'Afectează Portal Client (fără confirmare cont) și Cron (fără raport zilnic).',
            ],
            SetariPlatforma::MODUL_CRON => [
                'slug'       => 'cron',
                'cheie'      => SetariPlatforma::MODUL_CRON,
                'nume'       => 'Cron & Automatizări',
                'descriere'  => 'Endpoint cron securizat cu token UUID și task-uri automate zilnice.',
                'icon'       => 'clock',
                'culoare'    => 'gray',
                'blocheaza'  => 'Endpoint-ul cron returnează 403. Secțiunea Cron din setări ascunsă.',
                'avertizare' => 'Necesită Email & Notificări activ pentru rapoartele zilnice de igienizări.',
            ],
        ];
    }
}
