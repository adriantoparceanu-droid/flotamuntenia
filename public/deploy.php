<?php
/**
 * Webhook de deploy pentru FlotaMuntenia.
 *
 * Acceseaza: https://app.flotamuntenia.ro/deploy.php?token=TOKEN
 *
 * Token-ul se citeste din `<home>/.deploy-token` (fisier in afara document root,
 * inaccesibil prin HTTP). Cream fisierul prin File Manager o singura data.
 *
 * Resilient:
 *  - Fallback automat proc_open / shell_exec / exec / passthru / system
 *  - Auto-discovery binar PHP (cauta in mai multe locatii standard cPanel)
 *  - Auto-discovery binar composer; daca lipseste, download composer.phar
 *
 * Securitate:
 *  - Token obligatoriu, timing-safe (hash_equals)
 *  - HTTPS obligatoriu
 *  - Log apel in storage/logs/deploy.log
 */

declare(strict_types=1);

// === 1. HTTPS only ===
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    || ($_SERVER['SERVER_PORT'] ?? '') == 443;

if (!$isHttps) {
    http_response_code(400);
    exit('HTTPS required');
}

// === 2. Cai ===
$rootPath = realpath(__DIR__ . '/..');
$home = rtrim($_SERVER['HOME'] ?? dirname($rootPath), '/');
$tokenFile = $home . '/.deploy-token';
$logFile = $rootPath . '/storage/logs/deploy.log';

// Asigura ca storage/logs exista (la primul deploy poate lipsi)
@mkdir(dirname($logFile), 0775, true);

// === 3. Token ===
if (!file_exists($tokenFile)) {
    http_response_code(500);
    exit("Token file missing — creeaza {$tokenFile} cu token-ul tau");
}

$expectedToken = trim((string) file_get_contents($tokenFile));
$providedToken = (string) ($_GET['token'] ?? '');

if (empty($expectedToken) || strlen($providedToken) < 16 || !hash_equals($expectedToken, $providedToken)) {
    @file_put_contents($logFile, sprintf("[%s] DENIED from %s\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? 'unknown'), FILE_APPEND);
    http_response_code(403);
    exit('Forbidden');
}

// === 4. Output streaming ===
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
echo "Home: {$home}\n";

// === 5. Detecteaza executor shell ===
$available = [
    'proc_open'  => function_exists('proc_open'),
    'shell_exec' => function_exists('shell_exec'),
    'exec'       => function_exists('exec'),
    'passthru'   => function_exists('passthru'),
    'system'     => function_exists('system'),
];

echo 'PHP exec methods: ';
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
    echo "\n[FATAL] Toate functiile de executie shell sunt DEZACTIVATE.\n";
    echo "cPanel > Select PHP Version > tab Options > scoate proc_open din disable_functions.\n";
    exit(1);
}

echo "Executor: {$executor}\n";

// === 6. Wrapper unitar ===
function runCommand(string $cmd, string $executor): array
{
    if ($executor === 'proc_open') {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open($cmd, $desc, $pipes);
        if (!is_resource($proc)) {
            return ['proc_open failed', -1];
        }
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
    if ($executor === 'shell_exec') {
        $res = @shell_exec($cmd . ' 2>&1 ; echo "__EXIT_$?__"');
        if ($res === null) return ['shell_exec null', -1];
        $code = 0;
        if (preg_match('/__EXIT_(\d+)__/', $res, $m)) {
            $code = (int) $m[1];
            $res = preg_replace('/__EXIT_\d+__\n?/', '', $res);
        }
        return [(string) $res, $code];
    }
    if ($executor === 'exec') {
        $arr = []; $code = -1;
        @exec($cmd . ' 2>&1', $arr, $code);
        return [implode("\n", $arr), $code];
    }
    if ($executor === 'passthru') {
        ob_start(); $code = -1;
        @passthru($cmd . ' 2>&1', $code);
        return [(string) ob_get_clean(), $code];
    }
    if ($executor === 'system') {
        ob_start(); $code = -1;
        @system($cmd . ' 2>&1', $code);
        return [(string) ob_get_clean(), $code];
    }
    return ['unknown executor', -1];
}

// === 7. Auto-discovery binar PHP ===
function findFirst(array $candidates): ?string
{
    foreach ($candidates as $p) {
        if ($p && @is_file($p)) {
            return $p;
        }
    }
    return null;
}

$phpCandidates = [
    '/opt/cpanel/ea-php83/root/usr/bin/php',
    '/opt/cpanel/ea-php84/root/usr/bin/php',
    '/usr/local/bin/php',
    '/usr/bin/php83',
    '/opt/alt/php83/usr/bin/php',
    PHP_BINARY,
    '/usr/bin/php',
];
$php = findFirst($phpCandidates);
if ($php === null) {
    echo "[FATAL] Nu gasesc binar PHP. Cauta: " . implode(', ', $phpCandidates) . "\n";
    exit(1);
}

[$phpVer, ] = runCommand(escapeshellarg($php) . " -r 'echo PHP_VERSION;'", $executor);
echo "PHP: {$php} (v{$phpVer})\n";

// === 8. Auto-discovery binar Composer ===
$composerCandidates = [
    '/usr/local/bin/composer',
    '/usr/local/cpanel/3rdparty/bin/composer',
    $home . '/bin/composer',
    $home . '/composer.phar',
    $rootPath . '/composer.phar',
];
$composer = findFirst($composerCandidates);

// Fallback: download composer.phar
if ($composer === null) {
    echo "Composer nu este instalat — download composer.phar...\n";
    $pharPath = $rootPath . '/composer.phar';
    $url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'flota-deploy/1.0']]);
    $phar = @file_get_contents($url, false, $ctx);
    if ($phar === false || strlen($phar) < 100000) {
        echo "[FATAL] Nu pot descarca composer.phar.\n";
        echo "Solutie: descarca manual composer.phar de la https://getcomposer.org/composer.phar\n";
        echo "         si upload prin File Manager in {$home}/composer.phar\n";
        exit(1);
    }
    if (file_put_contents($pharPath, $phar) === false) {
        echo "[FATAL] Nu pot scrie {$pharPath} (permisiuni).\n";
        exit(1);
    }
    @chmod($pharPath, 0755);
    $composer = $pharPath;
    echo "Composer instalat: {$composer} (" . number_format(strlen($phar)) . " bytes)\n";
}

$composerCmd = (substr($composer, -5) === '.phar')
    ? escapeshellarg($php) . ' ' . escapeshellarg($composer)
    : escapeshellarg($composer);
echo "Composer: {$composer}\n";

echo "============================\n";
@file_put_contents($logFile, sprintf("[%s] DEPLOY START via=%s\n", date('c'), $executor), FILE_APPEND);

// === 9. Comenzi ===
$cd = 'cd ' . escapeshellarg($rootPath);
$phpQ = escapeshellarg($php);

$commands = [
    'Git fetch'                  => "{$cd} && git fetch origin main 2>&1",
    'Git reset hard origin/main' => "{$cd} && git reset --hard origin/main 2>&1",
    'Composer install'           => "{$cd} && export COMPOSER_HOME={$rootPath}/.composer && {$composerCmd} install --no-dev --optimize-autoloader --no-interaction 2>&1",
    'Migrate'                    => "{$cd} && {$phpQ} artisan migrate --force 2>&1",
    'Seed production'            => "{$cd} && {$phpQ} artisan db:seed --class=ProductionSeeder --force --no-interaction 2>&1",
    'Bootstrap admin'            => "{$cd} && {$phpQ} artisan app:bootstrap-admin 2>&1",
    'Optimize clear'             => "{$cd} && {$phpQ} artisan optimize:clear 2>&1",
    'Config cache'               => "{$cd} && {$phpQ} artisan config:cache 2>&1",
    'Route cache'                => "{$cd} && {$phpQ} artisan route:cache 2>&1",
    'View cache'                 => "{$cd} && {$phpQ} artisan view:cache 2>&1",
    'Event cache'                => "{$cd} && {$phpQ} artisan event:cache 2>&1",
];

// === 10. Ruleaza ===
$failed = false;
foreach ($commands as $name => $cmd) {
    $t = microtime(true);
    echo "\n>>> {$name}\n";
    [$out, $code] = runCommand($cmd, $executor);
    echo $out !== '' ? $out : '(no output)';
    $el = round(microtime(true) - $t, 2);
    if ($code === 0) {
        echo "\n--- OK ({$el}s)\n";
    } else {
        echo "\n--- FAIL exit={$code} ({$el}s)\n";
        $failed = true;
    }
}

// === 11. Storage symlink — PHP nativ (artisan storage:link foloseste exec()
//        care e dezactivat pe acest hosting) ===
$tStep = microtime(true);
echo "\n>>> Storage link (PHP native)\n";
$linkTarget = $rootPath . '/storage/app/public';
$linkPath = $rootPath . '/public/storage';

@mkdir($linkTarget, 0775, true);

if (file_exists($linkPath) || is_link($linkPath)) {
    echo "Symlink/file exista deja la {$linkPath}\n";
    echo '--- OK (' . round(microtime(true) - $tStep, 2) . "s)\n";
} elseif (!function_exists('symlink')) {
    echo "[FAIL] symlink() este DEZACTIVAT in disable_functions.\n";
    echo "Solutie: cPanel > Select PHP Version > Options > scoate symlink.\n";
    echo '--- FAIL (' . round(microtime(true) - $tStep, 2) . "s)\n";
    $failed = true;
} elseif (@symlink($linkTarget, $linkPath)) {
    echo "Symlink creat: {$linkPath} -> {$linkTarget}\n";
    echo '--- OK (' . round(microtime(true) - $tStep, 2) . "s)\n";
} else {
    $err = error_get_last();
    echo '[FAIL] symlink() a esuat: ' . ($err['message'] ?? 'unknown') . "\n";
    echo '--- FAIL (' . round(microtime(true) - $tStep, 2) . "s)\n";
    $failed = true;
}

$total = round(microtime(true) - $startTime, 2);
echo "\n============================\n";
echo $failed ? "STATUS: PARTIAL FAIL\n" : "STATUS: OK\n";
echo "Total: {$total}s\n";

@file_put_contents($logFile, sprintf("[%s] DEPLOY END status=%s duration=%ss\n", date('c'), $failed ? 'FAIL' : 'OK', $total), FILE_APPEND);
