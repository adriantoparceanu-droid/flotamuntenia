<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detectează clienți duplicați (același CIF, ID-uri diferite) și îi fuzionează.
 *
 * Strategia: pentru fiecare grup de CIF duplicat, se păstrează clientul cu cel mai mic ID
 * (cel mai vechi în CI3). Toate referințele FK din tabelele dependente se redirecționează
 * spre clientul păstrat, iar duplicatele se șterg.
 *
 * Rulare raport (fără modificări):
 *   php artisan clienti:deduplicare --dry-run
 *
 * Rulare efectivă:
 *   php artisan clienti:deduplicare
 */
class DeduplicareClienti extends Command
{
    protected $signature = 'clienti:deduplicare {--dry-run : Afișează ce s-ar face, fără modificări}';
    protected $description = 'Fuzionează clienți duplicați (același CIF, ID-uri diferite)';

    // Tabele cu FK spre clienti.id → coloana FK
    private array $tabeleDependente = [
        'adresa_livrare'   => 'id_client',
        'comenzi'          => 'id_client',
        'dozator'          => 'id_client',
        'produs'           => 'id_client',
        'recipienti'       => 'id_client',
        'users'            => 'id_client',
        'contracte_clienti'=> 'id_client',
        'documente_clienti'=> 'id_client',
        'vizite'           => 'id_client',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('▶ MOD DRY-RUN — nicio modificare nu va fi salvată');
        } else {
            $this->warn('▶ MOD LIVE — modificările vor fi aplicate permanent!');
            if (!$this->confirm('Ești sigur că vrei să continui?')) {
                $this->info('Operație anulată.');
                return 0;
            }
        }

        // Găsim toate grupurile de CIF duplicate
        $grupuri = DB::table('clienti')
            ->select('cif', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id ORDER BY id ASC) as ids_asc'))
            ->whereNotNull('cif')
            ->where('cif', '!=', '')
            ->groupBy('cif')
            ->having('cnt', '>', 1)
            ->orderByDesc('cnt')
            ->get();

        if ($grupuri->isEmpty()) {
            $this->info('✅ Nu există clienți duplicați după CIF.');
            return 0;
        }

        $this->info("Găsite {$grupuri->count()} grupuri CIF duplicate:");
        $this->newLine();

        $totalFuzionate = 0;
        $totalSterse    = 0;

        foreach ($grupuri as $grup) {
            $ids = array_map('intval', explode(',', $grup->ids_asc));
            $idPastreat = $ids[0]; // cel mai mic ID = cel mai vechi
            $idsDeSters = array_slice($ids, 1);

            $clientPastreat = DB::table('clienti')->find($idPastreat);
            $codClientiSterse = DB::table('clienti')
                ->whereIn('id', $idsDeSters)
                ->pluck('cod_client')
                ->implode(', ');

            $this->line(sprintf(
                '<fg=cyan>CIF %s</> | păstrat ID <fg=green>%d</> (%s) | de șters: %s',
                $grup->cif,
                $idPastreat,
                $clientPastreat->cod_client ?? '?',
                implode(', ', array_map(
                    fn($id) => "ID {$id} (cod: " . (DB::table('clienti')->find($id)->cod_client ?? '?') . ")",
                    $idsDeSters
                ))
            ));

            // Redirecționează FK în toate tabelele dependente
            foreach ($this->tabeleDependente as $tabel => $coloana) {
                if (!$this->tabelExista($tabel)) {
                    continue;
                }

                $nrAfectate = DB::table($tabel)->whereIn($coloana, $idsDeSters)->count();

                if ($nrAfectate > 0) {
                    $this->line("   → {$tabel}.{$coloana}: {$nrAfectate} rânduri redirecționate");

                    if (!$dryRun) {
                        DB::table($tabel)
                            ->whereIn($coloana, $idsDeSters)
                            ->update([$coloana => $idPastreat]);
                    }

                    $totalFuzionate += $nrAfectate;
                }
            }

            // Șterge duplicatele
            if (!$dryRun) {
                $sterse = DB::table('clienti')->whereIn('id', $idsDeSters)->delete();
                $totalSterse += $sterse;
            } else {
                $totalSterse += count($idsDeSters);
            }

            $this->line("   → clienti: " . count($idsDeSters) . " duplicate șterse");
            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn("DRY-RUN SUMAR:");
        } else {
            $this->info("✅ SUMAR:");
        }
        $this->line("   Grupuri procesate: {$grupuri->count()}");
        $this->line("   Rânduri FK redirecționate: {$totalFuzionate}");
        $this->line("   Clienți duplicați șterși: {$totalSterse}");

        if ($dryRun) {
            $this->newLine();
            $this->warn("Rulează fără --dry-run pentru a aplica modificările.");
        }

        return 0;
    }

    private function tabelExista(string $tabel): bool
    {
        try {
            DB::table($tabel)->limit(1)->get();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
