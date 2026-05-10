<?php
/**
 * Webhook de deploy pentru FlotaMuntenia.
 *
 * Acceseaza: https://app.flotamuntenia.ro/deploy.php?token=TOKEN
 *
 * Token-ul se citeste din `<home>/.deploy-token` (fisier in afara document root,
 * inaccesibil prin HTTP). Cream fisierul prin File Manager o singura data.
 *
 * Securitate:
 *  - Token obligatoriu, comparat cu hash_equals (timing-safe)
 *  - HTTPS obligatoriu (refuza HTTP)
 *  - Log apel in storage/logs/deploy.log
 *
 * Resilient la disable_functions: incearca proc_open / shell_exec / exec / passthru
 * in aceasta ordine. Daca toate sunt blocate, afiseaza clar solutiile.
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
    exit("Token file missing — creeaza {$tokenFile} cu token-ul tau");
}

$expectedToken = trim((string) file_get_contents($tokenFile));
$providedToken = (string) ($_GET['token'] ?? '');

if (empty($expectedToken) || strlen($providedToken) < 16 || !hash_equals($expectedToken, $providedToken)) {
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

// === 5. Detecteaza ce executoare sunt disponibile ===
$available = [
    'proc_open'  => function_exists('proc_open'),
    'shell_exec' => function_exists('shell_exec'),
    'exec'       => function_exists('exec'),
    'passthru'   => function_exists('passthru'),
    'system'     => function_exists('system'),
];

echo "PHP exec methods: ";
foreach ($available as $fn => $ok) {
    echo $fn . '=' . ($ok ? 'OK' : 'BLOCKED') . ' ';
}
echo "\n";

$executor = null;
foreach (['proc_open', 'shell_exec', 'exec', 'passthru', 'system'] as $fn) {
    if ($available[$fn]) {
        $executor = $fn;
        break;
    }
}

if ($executor === null) {
    echo "\n[FATAL] Toate functiile de executie shell sunt DEZACTIVATE in php.ini.\n\n";
    echo "Functii necesare (cel putin una): proc_open, shell_exec, exec, passthru, system\n\n";
    echo "SOLUTII:\n";
    echo " 1) cPanel > Select PHP Version > tab \"Options\" > scoate functia din\n";
    echo "    'disable_functions' (de regula proc_open e cel mai sigur de activat).\n";
    echo " 2) Daca optiunea nu apare, cere providerului de hosting sa permita proc_open\n";
    echo "    pentru contul tau (e safe la directory-level).\n";
    echo " 3) Alternativa fara shell exec: cron job care ruleaza scriptul de deploy.\n";
    echo "    Spune-mi sa-ti generez varianta cron daca asta e blocat definitiv.\n";
    exit(1);
}

echo "Folosesc: {$executor}\n";
echo "============================\n";
@file_put_contents($logFile, sprintf("[%s] DEPLOY START via=%s\n", date('c'), $executor), FILE_APPEND);

// === 6. Wrapper unitar peste cele 5 metode ===
function runCommand(string $cmd, string $executor): array
{
    $output = '';
    $code = -1;

    if ($executor === 'proc_open') {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]) ?: '';
            $stderr = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);
            $output = $stdout;
            if ($stderr !== '') {
                $output .= ($output !== '' ? "\n" : '') . '[stderr] ' . $stderr;
            }
            return [$output, $code];
        }
        return ['proc_open failed to start', -1];
    }

    if ($executor === 'shell_exec') {
        $res = @shell_exec($cmd . ' 2>&1 ; echo "__EXIT_$?__"');
        if ($res === null || $res === false) {
            return ['shell_exec returned null', -1];
        }
        if (preg_match('/__EXIT_(\d+)__/', $res, $m)) {
            $code = (int) $m[1];
            $res = preg_replace('/__EXIT_\d+__\n?/', '', $res);
        } else {
            $code = 0;
        }
        return [(string) $res, $code];
    }

    if ($executor === 'exec') {
        $arr = [];
        @exec($cmd . ' 2>&1', $arr, $code);
        return [implode("\n", $arr), $code];
    }

    if ($executor === 'passthru') {
        ob_start();
        @passthru($cmd . ' 2>&1', $code);
        return [(string) ob_get_clean(), $code];
    }

    if ($executor === 'system') {
        ob_start();
        @system($cmd . ' 2>&1', $code);
        return [(string) ob_get_clean(), $code];
    }

    return ['no executor', -1];
}

// === 7. Comenzi de rulat ===
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

// === 8. Ruleaza ===
$failed = false;
foreach ($commands as $name => $cmd) {
    $tStep = microtime(true);
    echo "\n>>> {$name}\n";
    [$output, $code] = runCommand($cmd, $executor);
    echo $output !== '' ? $output : '(no output)';
    $elapsed = round(microtime(true) - $tStep, 2);
    if ($code === 0) {
        echo "\n--- OK ({$elapsed}s)\n";
    } else {
        echo "\n--- FAIL exit={$code} ({$elapsed}s)\n";
        $failed = true;
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
