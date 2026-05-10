<?php
/**
 * Webhook de deploy pentru FlotaMuntenia.
 *
 * Acceseaza: https://app.flotamuntenia.ro/deploy.php?token=TOKEN
 *
 * Token-ul se citeste din `~/.deploy-token` (fisier in afara document root,
 * inaccesibil prin HTTP). Cream fisierul prin File Manager o singura data.
 *
 * Securitate:
 *  - Token obligatoriu, comparat cu hash_equals (timing-safe)
 *  - HTTPS obligatoriu (refuza HTTP)
 *  - Log apel in storage/logs/deploy.log
 */

declare(strict_types=1);

// === 1. Forteaza HTTPS ===
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    || ($_SERVER['SERVER_PORT'] ?? '') == 443;

if (!$isHttps) {
    http_response_code(400);
    exit('HTTPS required');
}

// === 2. Cai ===
$rootPath = realpath(__DIR__ . '/..');
$tokenFile = dirname($rootPath) . '/.deploy-token';
$logFile = $rootPath . '/storage/logs/deploy.log';

// === 3. Verifica token ===
if (!file_exists($tokenFile)) {
    http_response_code(500);
    exit('Token file missing (.deploy-token nu exista in home folder)');
}

$expectedToken = trim((string) file_get_contents($tokenFile));
$providedToken = (string) ($_GET['token'] ?? '');

if (empty($expectedToken) || strlen($providedToken) < 16 || !hash_equals($expectedToken, $providedToken)) {
    // Log incercare suspecta
    @file_put_contents($logFile, sprintf(
        "[%s] DENIED from %s\n",
        date('c'),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ), FILE_APPEND);
    http_response_code(403);
    exit('Forbidden');
}

// === 4. Pregateste output streaming ===
@set_time_limit(600);
ignore_user_abort(true);
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
@ob_implicit_flush(true);
while (ob_get_level() > 0) {
    ob_end_flush();
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
echo "=== Deploy FlotaMuntenia ===\n";
echo 'Inceput: ' . date('Y-m-d H:i:s') . "\n";
echo "Root: {$rootPath}\n";
echo "============================\n";

@file_put_contents($logFile, sprintf("[%s] DEPLOY START\n", date('c')), FILE_APPEND);

// === 5. Comenzi de rulat ===
$php = '/usr/local/bin/php';
$composer = '/usr/local/bin/composer';
$cd = 'cd ' . escapeshellarg($rootPath);

$commands = [
    'Git fetch'                  => "{$cd} && git fetch origin main 2>&1",
    'Git reset hard origin/main' => "{$cd} && git reset --hard origin/main 2>&1",
    'Composer install'           => "{$cd} && export COMPOSER_HOME={$rootPath}/.composer && {$composer} install --no-dev --optimize-autoloader --no-interaction 2>&1",
    'Migrate'                    => "{$cd} && {$php} artisan migrate --force 2>&1",
    'Seed production'            => "{$cd} && {$php} artisan db:seed --class=ProductionSeeder --force --no-interaction 2>&1",
    'Bootstrap admin'            => "{$cd} && {$php} artisan app:bootstrap-admin 2>&1",
    'Storage link'               => "{$cd} && {$php} artisan storage:link 2>&1",
    'Optimize clear'             => "{$cd} && {$php} artisan optimize:clear 2>&1",
    'Config cache'               => "{$cd} && {$php} artisan config:cache 2>&1",
    'Route cache'                => "{$cd} && {$php} artisan route:cache 2>&1",
    'View cache'                 => "{$cd} && {$php} artisan view:cache 2>&1",
    'Event cache'                => "{$cd} && {$php} artisan event:cache 2>&1",
];

// === 6. Verifica ca shell_exec este permis ===
if (!function_exists('shell_exec')) {
    echo "\n[FATAL] shell_exec() este DEZACTIVAT in php.ini (disable_functions).\n";
    echo "Solutie: cPanel > Select PHP Version > Options > scoate 'shell_exec' din disable_functions.\n";
    exit(1);
}

// === 7. Ruleaza ===
$failed = false;
foreach ($commands as $name => $cmd) {
    $tStep = microtime(true);
    echo "\n>>> {$name}\n";
    $output = shell_exec($cmd . '; echo "__EXITCODE_$?__"');
    $exitCode = 0;
    if (preg_match('/__EXITCODE_(\d+)__/', $output ?? '', $m)) {
        $exitCode = (int) $m[1];
        $output = preg_replace('/__EXITCODE_\d+__\n?/', '', $output);
    }
    echo $output ?: '(no output)';
    $elapsed = round(microtime(true) - $tStep, 2);
    if ($exitCode === 0) {
        echo "\n--- OK ({$elapsed}s)\n";
    } else {
        echo "\n--- FAIL exit={$exitCode} ({$elapsed}s)\n";
        $failed = true;
        // Nu oprim — vrem sa vedem toate erorile
    }
}

$total = round(microtime(true) - $startTime, 2);
echo "\n============================\n";
echo $failed ? "STATUS: PARTIAL FAIL\n" : "STATUS: OK\n";
echo "Total: {$total}s\n";

@file_put_contents($logFile, sprintf(
    "[%s] DEPLOY END status=%s duration=%ss\n",
    date('c'),
    $failed ? 'FAIL' : 'OK',
    $total
), FILE_APPEND);
