<?php
// Script temporar de import one-time. DE STERS dupa folosire.
// Accepta fisier SQL via POST multipart, il salveaza, ruleaza comanda Artisan, sterge fisierul.

$token = 'da3149b7a4cd0f9b24dfda8fd9315584fbd702bff975d94c63399b0f865c28d0';
if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    exit('Forbidden');
}

$tip = $_GET['tip'] ?? '';
if (! in_array($tip, ['clienti', 'adrese', 'comenzi'], true)) {
    exit("Parametru 'tip' invalid. Valori: clienti, adrese, comenzi\n");
}

$comenziMap = [
    'clienti' => 'import:clienti-vechi',
    'adrese'  => 'import:adrese-vechi',
    'comenzi' => 'import:comenzi-vechi',
];

// Primeste fisierul SQL
if (empty($_FILES['sql']['tmp_name'])) {
    exit("Fisier SQL lipsa. Trimite via: curl -F 'sql=@/cale/fisier.sql'\n");
}

$tmpPath = sys_get_temp_dir() . '/import_' . $tip . '_' . time() . '.sql';
if (! move_uploaded_file($_FILES['sql']['tmp_name'], $tmpPath)) {
    exit("Eroare la salvarea fisierului temporar.\n");
}

$root   = dirname(__DIR__);
$php    = '/opt/cpanel/ea-php83/root/usr/bin/php';
$artisan = $root . '/artisan';
$cmd    = $comenziMap[$tip];

echo "=== Import {$tip} ===\n";
echo "Fisier: {$tmpPath}\n";
echo "Comanda: {$cmd}\n\n";

flush();

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open(
    "{$php} {$artisan} {$cmd} {$tmpPath}",
    $descriptors,
    $pipes,
    $root
);

if (! is_resource($process)) {
    unlink($tmpPath);
    exit("Eroare: nu s-a putut porni procesul.\n");
}

fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

// Sterge fisierul temporar
unlink($tmpPath);

// Strip ANSI color codes pentru output lizibil
$output = preg_replace('/\x1B\[[0-9;]*[mGKHF]/u', '', $output);
$errors = preg_replace('/\x1B\[[0-9;]*[mGKHF]/u', '', $errors);

echo $output;
if ($errors) {
    echo "\nSTDERR:\n" . $errors;
}
echo "\nExit code: {$exitCode}\n";
echo ($exitCode === 0) ? "STATUS: OK\n" : "STATUS: FAIL\n";
