<?php
/** Shared perimeter: load before routers or configuration mutations. */
if (PHP_SAPI === 'cli' && !isset($_SERVER['REMOTE_ADDR'])) return;
function hamu_deny(int $status, string $error): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['success'=>false, 'error'=>$error]);
    exit;
}
$hamuLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$hamuHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!$hamuLocal && !$hamuHttps) hamu_deny(403, 'HTTPS required');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$hamuHttps,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
$hamuCfg = json_decode((string)@file_get_contents(__DIR__.'/cache/app_config.json'), true) ?: [];
$hamuAuth = filter_var($hamuCfg['ask_pass_s'] ?? false, FILTER_VALIDATE_BOOLEAN);
$hamuHash = (string)($hamuCfg['app_pass_s'] ?? '');
if (!$hamuLocal && (!$hamuAuth || !password_get_info($hamuHash)['algo'])) {
    hamu_deny(503, 'Configure a strong administrator password locally before deployment');
}
if (!empty($_SESSION['authenticated']) && time() - (int)($_SESSION['last_activity'] ?? 0) > 3600) {
    session_unset();
    session_regenerate_id(true);
}
$hamuApi = (string)($_GET['api'] ?? '');
$hamuPage = (string)($_GET['p'] ?? '');
if ($hamuApi !== '' && $hamuApi !== 'i18n' && $hamuAuth && $hamuHash !== '' && ($_SESSION['authenticated'] ?? false) !== true) {
    hamu_deny(401, 'Unauthorized');
}
if ($hamuApi === '2fa' && !in_array($_GET['op'] ?? '', ['status','backup_status'], true) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') hamu_deny(405, 'POST required');
// Production panel intentionally has no remote code/file/SQL/FTP operations.
if (!$hamuLocal && (in_array($hamuPage, ['fmi','file-manager','projects','modules','test'], true) || in_array($hamuApi, ['db','projects'], true))) {
    hamu_deny(403, 'This development tool is restricted to loopback');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !is_string($_SESSION['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $token)) {
        hamu_deny(403, 'Invalid CSRF token');
    }
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if (($_SESSION['authenticated'] ?? false) === true) $_SESSION['last_activity'] = time();

// Per-IP persistent attempt budget also covers fresh browser sessions.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (isset($_POST['app_pass_s']) || isset($_POST['twofa_code']))) {
    $dir = __DIR__.'/cache/login-limits';
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) hamu_deny(503, 'Login limiter unavailable');
    $fp = fopen($dir.'/'.hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '').'.json', 'c+');
    if (!$fp || !flock($fp, LOCK_EX)) hamu_deny(503, 'Login limiter unavailable');
    $state = json_decode(stream_get_contents($fp), true) ?: ['start'=>time(), 'count'=>0];
    if (time() - (int)$state['start'] >= 900) $state = ['start'=>time(), 'count'=>0];
    if ((int)$state['count'] >= 10) { fclose($fp); header('Retry-After: 900'); hamu_deny(429, 'Too many login attempts'); }
    $state['count']++;
    rewind($fp); ftruncate($fp, 0); fwrite($fp, json_encode($state)); fflush($fp); flock($fp, LOCK_UN); fclose($fp);
}
