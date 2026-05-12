<?php
/**
 * Runner TEMPORAR — migrare date CI3 → Laravel.
 * ȘTERGE ACEST FIȘIER imediat după ce migrarea s-a terminat cu succes!
 */
define('TOKEN', 'ci3mig_8f4a2b7d3e9c1f6a5b0d4e8f2a7c3b9d');

if (($_GET['token'] ?? '') !== TOKEN) {
    http_response_code(403);
    die('Forbidden');
}

$root = dirname(__DIR__);
$php  = '/opt/cpanel/ea-php83/root/usr/bin/php';

header('Content-Type: text/plain; charset=utf-8');
// Flush imediat — nu buffering
while (ob_get_level()) ob_end_flush();
set_time_limit(600);

$startTotal = microtime(true);

echo "=== Migrat CI3 → Laravel ===\n";
echo "Start: " . date('Y-m-d H:i:s') . "\n";
echo "Root: {$root}\n\n";
flush();

function ruleazaComanda(string $php, string $root, string $comanda): array
{
    $cmd = "{$php} {$root}/artisan {$comanda} 2>&1";
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $t0      = microtime(true);
    $process = proc_open($cmd, $descriptors, $pipes, $root);
    if (!is_resource($process)) {
        return ['output' => 'proc_open eșuat', 'code' => 1, 'timp' => 0];
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return ['output' => $output, 'code' => $code, 'timp' => round(microtime(true) - $t0, 2)];
}

$statusGlobal = 'OK';

// ── Pas 1: migrate:fresh ──────────────────────────────────────────────────────
echo ">>> migrate:fresh --force\n";
flush();
$r = ruleazaComanda($php, $root, 'migrate:fresh --force');
echo $r['output'];
if ($r['code'] === 0) {
    echo "--- OK ({$r['timp']}s)\n\n";
} else {
    echo "--- FAIL exit={$r['code']} ({$r['timp']}s)\n\n";
    echo "STATUS: FAIL\n";
    flush();
    exit;
}
flush();

// ── Pas 2: MigrareCI3Seeder ───────────────────────────────────────────────────
echo ">>> db:seed --class=MigrareCI3Seeder\n";
flush();
$r = ruleazaComanda($php, $root, 'db:seed --class=MigrareCI3Seeder');
echo $r['output'];
if ($r['code'] === 0) {
    echo "--- OK ({$r['timp']}s)\n\n";
} else {
    echo "--- FAIL exit={$r['code']} ({$r['timp']}s)\n\n";
    $statusGlobal = 'FAIL';
}
flush();

// ── Pas 3: recachează config/route/view ──────────────────────────────────────
if ($statusGlobal === 'OK') {
    foreach (['config:cache', 'route:cache', 'view:cache'] as $cmd) {
        echo ">>> {$cmd}\n";
        $r = ruleazaComanda($php, $root, $cmd);
        echo ($r['code'] === 0 ? "--- OK\n" : "--- FAIL\n");
    }
    echo "\n";
    flush();
}

$durataTotal = round(microtime(true) - $startTotal, 2);
echo "============================\n";
echo "STATUS: {$statusGlobal}\n";
echo "Total: {$durataTotal}s\n";
echo "End: " . date('Y-m-d H:i:s') . "\n";
echo "\n";
if ($statusGlobal === 'OK') {
    echo "IMPORTANT: Sterge public/migrat-ci3.php de pe server!\n";
}
