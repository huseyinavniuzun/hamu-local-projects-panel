<?php
require_once __DIR__.'/security.php';
/**
 * HAMU DevPanel - Configuration
 * Location: /.hamu/config.php
 * [TR] Projenin merkezi yapılandırma dosyası. Sabitleri tanımlar, .env dosyasını yükler, app_config.json dosyasını yönetir (okuma/yazma/ilk oluşturma), hassas veriler için şifreleme sağlar, güvenlik yardımcılarını (CSRF, .htaccess) içerir ve yapılandırma API isteklerini yönlendirir.
 * [EN] The central configuration file for the project. Defines constants, loads .env, manages app_config.json (read/write/initial creation), provides encryption for sensitive data, includes security helpers (CSRF, .htaccess), and routes configuration API requests.
 */


//  Yol sabitleri
if (!defined('HAMU_DOCROOT'))   define('HAMU_DOCROOT', str_replace('\\', '/', dirname(__DIR__)));
if (!defined('HAMU_DIR'))       define('HAMU_DIR', HAMU_DOCROOT . '/.hamu');
if (!defined('HAMU_CACHE'))     define('HAMU_CACHE', HAMU_DIR . '/cache');
if (!defined('HAMU_LOG'))       define('HAMU_LOG',   HAMU_DIR . '/logs');
if (!defined('HAMU_MODULE'))    define('HAMU_MODULE',   HAMU_DIR . '/modules');
if (!defined('HAMU_MD'))        define('HAMU_MD',   HAMU_DOCROOT . '/readme');
if (!defined('HAMU_IMAGE'))     define('HAMU_IMAGE',   HAMU_DIR . '/assets/images');

// Yol birleştirici
if (!function_exists('path_join')) {
  function path_join(string ...$parts): string {
    $p=[]; foreach ($parts as $s) { $p[] = trim(str_replace('\\','/',$s), '/'); }
    $out = implode('/', array_filter($p, fn($x)=>$x!==''));
    return ($out === '') ? '/' : '/'.$out;
  }
}

// Proxy dostu base_url
if (!function_exists('base_url')) {
  function base_url(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        // Host güvenliği: yalnızca güvenli karakterlere izin ver
        $host  = preg_replace('/[^A-Za-z0-9\.\-:\[\]]/', '', (string)$host);
        // Port ekle (HTTP_HOST'ta yoksa ve default değilse)
        $port  = (string)($_SERVER['SERVER_PORT'] ?? '');
        $needPort = $port !== '' && !preg_match('~:\d+$~', $host)
                    && !(($proto === 'http' && $port === '80') || ($proto === 'https' && $port === '443'));
        $authority = $host . ($needPort ? (':' . $port) : '');
        return $proto . '://' . $authority;
  }
}

$DOCROOT   = HAMU_DOCROOT;
$HAMU_DIR  = HAMU_DIR ;
$CACHE_DIR = HAMU_CACHE;
$LOG_DIR   = HAMU_LOG;
$MODULE_DIR   = HAMU_MODULE;
$IMAGE_DIR   = HAMU_IMAGE;
$MD_DIR   = HAMU_MD;

if (!is_dir($CACHE_DIR)) { @mkdir($CACHE_DIR, 0775, true); }
if (!is_dir($LOG_DIR))   { @mkdir($LOG_DIR,   0775, true); }

$CONFIG_FP = $CACHE_DIR . '/app_config.json';
$META_FP   = $CACHE_DIR . '/projects_meta.json';

$LOG_FILE_ACTIONS = $LOG_DIR . '/meta_actions.log';
$LOG_FILE_META    = $LOG_DIR . '/projects_meta.log';
$LOG_FILE_SESSIONS = $LOG_DIR . '/app_sessions.log';
$LOG_FILE_FTP = $LOG_DIR . '/project_actions.log';

// footer'i çağıralım
function get_footer(): string {
  // header’da hazırlanan değişkenleri footer’a aktar
  global $custom_js, $include_db, $config, $active_db;

  $path = HAMU_DIR.'/footer.php';
  if (!is_file($path)) return '';
  ob_start();
  include $path;
  return ob_get_clean();
}


if (!function_exists('hamu_session')) {
  function hamu_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    // Güçlü cookie & oturum ayarları
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_trans_sid', '0');

    // SameSite (ini ile garanti et), Secure/HTTPOnly
    $isHttps = (
      (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
      (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    // PHP >= 7.3 için session_set_cookie_params ile de set edeceğiz
    @ini_set('session.cookie_samesite', 'Lax');

    // PHP 7.3 SameSite destekli çağrı (mümkünse)
    if (PHP_VERSION_ID >= 70300) {
      session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
      ]);
    }
    session_start();
  }
}
hamu_session();

/* -------------------------------------------------------------------------- */
/* 1) LEGACY fallback key (sadece eski kayıtları çözmek için)                  */
/* -------------------------------------------------------------------------- */
if (!defined('ENCRYPTION_KEY')) {
  define('ENCRYPTION_KEY', 'hamu-local-project-manager');
}

/* -------------------------------------------------------------------------- */
/* 2) .env yükleyici                                                          */
/* -------------------------------------------------------------------------- */
function loadEnv($path) {
  if (!is_file($path)) return;
  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name !== '' && $value !== '') {
            // Çevrili tırnakları temizle: "VALUE" veya 'VALUE'
            if (strlen($value) >= 2 && (
                ($value[0] === '"'  && substr($value, -1) === '"') ||
                ($value[0] === "'"  && substr($value, -1) === "'"))) {
              $value = substr($value, 1, -1);
            }
            putenv("$name=$value");
          }
  }
}
loadEnv( HAMU_DIR . '/.env');

/* -------------------------------------------------------------------------- */
/* JSON yardımcıları                                                       */
/* -------------------------------------------------------------------------- */
function writeJsonAtomic(string $filepath, array $data, int $perm = 0640): void {
  $tmp  = $filepath . '.tmp';
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) throw new RuntimeException("JSON encode başarısız: " . json_last_error_msg());

  $fp = @fopen($tmp, 'wb');
  if (!$fp) throw new RuntimeException("Geçici dosya açılamadı: {$tmp}");
  try {
    if (!flock($fp, LOCK_EX)) throw new RuntimeException("LOCK_EX alınamadı: {$tmp}");
    if (fwrite($fp, $json) === false) throw new RuntimeException("JSON yazılamadı: {$tmp}");
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $fp = null;

    if (!@rename($tmp, $filepath)) {
      @unlink($filepath);
      if (!@rename($tmp, $filepath)) {
        throw new RuntimeException("rename başarısız: {$tmp} → {$filepath}");
      }
    }
    @chmod($filepath, $perm);
  } finally {
    if (is_resource($fp)) { @fclose($fp); }
    @unlink($tmp); // kalmışsa temizle
  }
}
function readJsonSafe(string $filepath): array {
  if (!is_file($filepath)) return [];
  $raw = @file_get_contents($filepath);
  if ($raw === false || $raw === '') return [];
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : [];
}



/* -------------------------------------------------------------------------- */
/* Config yükle                                                            */
/* -------------------------------------------------------------------------- */
$config = readJsonSafe($CONFIG_FP);

/* -------------------------------------------------------------------------- */
/* "Toggle"/cookie/GET yardımcı (iki farklı anahtarı tek kalıba indirir)       */
/* -------------------------------------------------------------------------- */
if (!function_exists('getToggle')) {
  function getToggle(string $cookieName, ?string $getParam = null, string $trueVal='1'): bool {
    if ($getParam !== null && isset($_GET[$getParam])) return ((string)$_GET[$getParam] === $trueVal);
    if (isset($_COOKIE[$cookieName])) return ((string)$_COOKIE[$cookieName] === $trueVal);
    return false;
  }
}

function getShowHiddenProjects(): bool { return getToggle('showHiddenProjects', 'hidden'); }
function getShowHiddenFM(): bool       { return getToggle('showHiddenFM', 'fm_hidden'); }
function getShowHiddenFtp(): bool      { return getToggle('showHiddenFtp', null); }

/* -------------------------------------------------------------------------- */
/* Master(ENV) + Install Secret(CONFIG) → Aktif 32B Key                    */
/* -------------------------------------------------------------------------- */

// ENV'den 32 bayt master anahtar üret (b64/hex). Yoksa legacy sabitten SHA-256 (32B).
function _getEnvMasterKeyBin(): string {
  $b64 = getenv('HAMU_MASTER_KEY_B64') ?: '';
  if ($b64 !== '') {
    $bin = base64_decode($b64, true);
    if (is_string($bin) && strlen($bin) >= 32) return substr($bin, 0, 32);
  }
  $hex = getenv('HAMU_MASTER_KEY_HEX') ?: '';
  if ($hex !== '') {
    $hex = preg_replace('/\s+/', '', $hex);
    $bin = @hex2bin($hex);
    if (is_string($bin) && strlen($bin) >= 32) return substr($bin, 0, 32);
  }
  return hash('sha256', ENCRYPTION_KEY, true); // 32B fallback
}

// config.json içinde "kurulum sırrı" oluştur veya oku
function _ensureInstallSecret(): string {
  global $CONFIG_FP, $config;
  $cur = (string)($config['enc_install_secret_b64'] ?? '');
  if ($cur !== '') {
    $bin = base64_decode($cur, true);
    if (is_string($bin) && strlen($bin) === 32) return $cur;
  }
  $bin = random_bytes(32);
  $b64 = base64_encode($bin);
  $config['enc_install_secret_b64'] = $b64;
  try { writeJsonAtomic($CONFIG_FP, $config, 0640); } catch (\Throwable $e) { /* ignore */ }
  return $b64;
}

// Aktif 32B şifreleme anahtarını türet (HKDF-SHA256)
function getActiveEncKeyBin(): string {
  $master = _getEnvMasterKeyBin();
  $saltB64 = _ensureInstallSecret();
  $salt = base64_decode($saltB64, true);
  if (!is_string($salt) || strlen($salt) !== 32) $salt = random_bytes(32);

  if (function_exists('hash_hkdf')) {
    return hash_hkdf('sha256', $master, 32, 'hamu-aes', $salt);
  }
  // Basit HKDF fallback
  $prk = hash_hmac('sha256', $master, $salt, true);
  $t = ''; $okm = ''; $i = 1;
  while (strlen($okm) < 32) {
    $t = hash_hmac('sha256', $t . 'hamu-aes' . chr($i++), $prk, true);
    $okm .= $t;
  }
  return substr($okm, 0, 32);
}

/* -------------------------------------------------------------------------- */
/* Crypto yardımcıları (Encrypt/Decrypt)                                   */
/* -------------------------------------------------------------------------- */
function encryptData($data) {
  if ($data === '' || $data === null) return '';
  $iv = random_bytes(12);
  $tag = '';
  $c = openssl_encrypt((string)$data, 'aes-256-gcm', getActiveEncKeyBin(), OPENSSL_RAW_DATA, $iv, $tag);
  if ($c === false) throw new RuntimeException('Encryption failed');
  return 'v2:' . base64_encode($iv . $tag . $c);
}
function decryptData($data) {
  if ($data === '' || $data === null) return '';
  if (str_starts_with((string)$data, 'v2:')) {
    $raw = base64_decode(substr($data, 3), true);
    if ($raw === false || strlen($raw) < 29) return '';
    return openssl_decrypt(substr($raw, 28), 'aes-256-gcm', getActiveEncKeyBin(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16)) ?: '';
  }
  $raw = base64_decode($data, true); if ($raw === false) return '';
  $m = 'AES-256-CBC';
  $ivlen = openssl_cipher_iv_length($m);
  if (strlen($raw) <= $ivlen) return '';
  $iv  = substr($raw, 0, $ivlen);
  $enc = substr($raw, $ivlen);

  // Aktif anahtar ile dene
  $key = getActiveEncKeyBin();
  $dec = openssl_decrypt($enc, $m, $key, OPENSSL_RAW_DATA, $iv);
  if ($dec !== false && $dec !== null) return $dec;

  // 2) Legacy fallback (eski sabit key)
  $legacy = openssl_decrypt($enc, $m, hash('sha256', ENCRYPTION_KEY, true), OPENSSL_RAW_DATA, $iv);
  return ($legacy !== false && $legacy !== null) ? $legacy : '';
}

/* -------------------------------------------------------------------------- */
/* .htaccess & nginx sample (koruma ve mime)                                */
/* -------------------------------------------------------------------------- */
function hamu_write_htaccess(string $hamuDir): void {
  $fp = rtrim($hamuDir, "/\\") . '/.htaccess';
  $tpl = <<<'HT'
Require all denied
HT;

  $cur = @file_get_contents($fp);
  if ($cur === false || strpos($cur, 'HAMU-AUTO') !== false) {
    @file_put_contents($fp, $tpl);
  }
}
function hamu_write_nginx_sample(string $hamuDir): void {
  $fp = rtrim($hamuDir, "/\\") . '/nginx.sample.conf';
  $tpl = <<<'NGX'
location ~ /\. { deny all; }
NGX;

  if (!file_exists($fp)) { @file_put_contents($fp, $tpl); }
}
hamu_write_htaccess($HAMU_DIR);
hamu_write_nginx_sample($HAMU_DIR);

/* -------------------------------------------------------------------------- */
/* Basit yardımcılar                                                       */
/* -------------------------------------------------------------------------- */
if (!function_exists('safe_redirect')) {
  function safe_redirect(string $url): void {
    if (!headers_sent())  {
      header('Location: '.$url);
      exit;
    }
    echo '<script>location.replace('.json_encode($url).');</script>';
    exit;
  }
}
/* --------------------------------------------------
 * CSRF yardımcıları
 * --------------------------------------------------*/
function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function csrf_check_from_post(): bool {
  $in   = $_POST['csrf'] ?? $_POST['csrf_token'] ?? null;
  $sess = $_SESSION['csrf_token'] ?? null;
  return $in && $sess && hash_equals((string)$sess, (string)$in);
}

/* -------------------------------------------------------------------------- */
/* Güvenli izinler (kısa blok)                                            */
/* -------------------------------------------------------------------------- */
$isUnix   = (DIRECTORY_SEPARATOR === '/');
$oldUmask = null;
if ($isUnix) { $oldUmask = umask(0027); } // dosya ~0640, klasör ~0755/0750

/* -------------------------------------------------------------------------- */
/* 11) app_config.json oluştur (yoksa)                                        */
/* -------------------------------------------------------------------------- */
if (!file_exists($CONFIG_FP)) {
    $defaultConfig = [
        "ask_pass_s"            => false,
        "app_pass_s"            => "",
        "theme_s"               => false,
        "database_s"            => false,
        "db_driver_s"           => "",
        "sqlite_folder_s"       => "",
        "db_server_s"           => "",
        "db_user_s"             => "",
        "db_pass_s"             => "",
        "modul_s"               => false,
        "phpmyadmin_s"          => false,
        "phpmyadmin_s_url"      => "",
        "mail_server_s"         => false,
        "mail_server_s_url"     => "",
        "ftp_server_s"          => false,
        "ftp_server_s_url"      => "",
        "ignore_folders"        => "docs,vendor,node_modules,logs,.hamu",
        "ignore_folders_s"      => true,
        "ignore_folders_s_text" => "docs,vendor,node_modules,logs,.hamu",
        "listing_per_page"      => 24,

        // Include sekmesi
        "bootstrap_s"                => true,
        "bootstrap_version_s"        => "5.3.3",
        "bootstrap_bundle_s"         => true,
        "bootstrap_bundle_version_s" => "5.3.3",
        "google_font_s"              => false,
        "google_font_url_s"          => "",
        "fontawesome_s"              => true,
        "fontawesome_version_s"      => "6.5.2",
        "custom_css_s"               => false,
        "custom_css_list_s"          => "",
        "jquery_s"                   => true,
        "jquery_version_s"           => "3.7.1",
        "jqueryui_s"                 => true,
        "jqueryui_version_s"         => "1.13.3",
        "custom_js_s"                => false,
        "custom_js_list_s"           => "",

        /* ------------------------------------------------------------------ */
        /* 2FA Ayarları (yeni)                                                */
        /* ------------------------------------------------------------------ */
        "2fa_s"                => false,     // 2FA genel anahtar
        "2fa_method_s"         => "email",   // "email" veya "app"
        "email_address_s"      => "",        // 2FA için e-posta adresi
        "email_verified_at"    => null,      // e-posta doğrulandı mı
        "use_auth_app"         => false,     // Authenticator aktif mi
        "totp_secret_b32_enc"  => "",        // TOTP secret (env anahtarıyla şifrelenmiş)
        "totp_enrolled_at"     => null,      // Kullanıcı uygulamayı eşledi mi
        "backup_codes_sha256"  => [],        // Yedek kod hash’leri
        "remember_device_days" => 30,        // Cihaz hatırlama süresi

        "first_setup"          => true
    ];
    writeJsonAtomic($CONFIG_FP, $defaultConfig, 0640);
}

/* -------------------------------------------------------------------------- */
/* Config erişim helper’ı                                                    */
/* -------------------------------------------------------------------------- */
$config = readJsonSafe($CONFIG_FP);
function config($key){ global $config; return $config[$key] ?? null; }
/* -------------------------------------------------------------------------- */
/* 13) Doğrudan erişim koruması                                               */
/* -------------------------------------------------------------------------- */
$isAjax = (
  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
  (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);
$__direct = basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '');

$authOn   = (bool)($config['ask_pass_s'] ?? false);
$hasPass  = !empty($config['app_pass_s'] ?? '');
$needAuth = ($authOn && $hasPass && (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true));

if ($__direct) {
  if ($needAuth) {
    if ($isAjax) {
      J(false, 'Unauthorized', [], 401);
    } else {
      header('Location: /index.php');
      exit;
    }
  }

  $isJsonPost = ($_SERVER['REQUEST_METHOD'] === 'POST'
                 && isset($_SERVER['CONTENT_TYPE'])
                 && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
  $isprj = (isset($_GET['_prj']) && $_GET['_prj']=='1') || (isset($_POST['_hamu_project']) && $_POST['_hamu_project']=='1');

  // Backup codes txt indirme istisnası (form POST ile gelebilir)
  if (!$isJsonPost && isset($_POST['action']) && $_POST['action']==='backup_download_txt') {
    $codes = $_POST['codes'] ?? [];
    if (is_string($codes)) $codes = preg_split('/[\r\n,]+/', $codes);
    $codes = array_filter(array_map('trim', (array)$codes));
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="hamu-backup-codes.txt"');
    echo implode("\n", $codes);
    exit;
  }
  if (!$isJsonPost && !$isprj) {
    header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
    exit;
  }
}

// printf tarzı dil keyi düzenle

if (!function_exists('_fmt')) {
  function _fmt(string $text, array $params) {
      if (!$params) return $text;

      // printf tarzı: %s, %1$s ...
      if (preg_match('/%(\d+\$)?s/', $text)) {
          return @vsprintf($text, $params);
      }

      // %p, %1$p, %2$p ...
      $iParams = array_values($params);
      return preg_replace_callback('/%(\d+\$)?p/', function($m) use ($iParams) {
          if (!empty($m[1])) { // %2$p
              $idx = max(((int) rtrim($m[1], '$')) - 1, 0);
              return array_key_exists($idx, $iParams) ? (string)$iParams[$idx] : '';
          }
          return isset($iParams[0]) ? (string)$iParams[0] : '';
      }, $text);
  }
}

if (!function_exists('J')) {
  function J($ok_or_key, $msg_or_param = null, array $extra = [], ?int $status = null): void {
      global $languages, $lang;

      // Parametreleri normalize et
      if (is_bool($ok_or_key)) {
          // Eski mod: (bool $ok, $msg, array $extra, ?int $status)
          $ok     = $ok_or_key;
          $msg    = $msg_or_param;
          $params = [];
          if (isset($extra['_params'])) {
              $params = is_array($extra['_params']) ? $extra['_params'] : [$extra['_params']];
              unset($extra['_params']);
          }
      } else {
          // Yeni kısayol: ($msgKey, $paramsOrScalar = null, array $extra = [], ?int $status = null)
          $ok  = true;
          $msg = $ok_or_key;
          if (is_array($msg_or_param)) {
              $params = $msg_or_param;
          } elseif ($msg_or_param !== null) {
              $params = [$msg_or_param];
          } else {
              $params = [];
          }
      }

      // Anahtar mı? (array_key_exists ile net ayırım)
      $isKey = is_string($msg) && isset($languages[$lang]) && array_key_exists($msg, $languages[$lang]);

      // Anahtar ise __l() (havuzu günceller), değilse _fmt() (havuzu kirletmez)
      $translated = $isKey ? __l($msg, ...$params) : (is_string($msg) ? _fmt($msg, $params) : $msg);

      if ($status === null) { $status = $ok ? 200 : 400; }
      http_response_code($status);
      header('Content-Type: application/json; charset=UTF-8');

      echo json_encode(
          array_merge([
              'ok'      => $ok,
              'msg'     => $translated,
              // backward-compat:
              'success' => $ok,
              'message' => $translated,
          ], $extra),
          JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
      );
      exit;
  }
}

/* -------------------------------------------------------------------------- */
/* JSON app ayar kaydı (atomic) + 2FA ACTION ROUTER                          */
/* -------------------------------------------------------------------------- */
if (
  ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
  (($_GET['api'] ?? '') === 'config' || basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') &&
  isset($_SERVER['CONTENT_TYPE']) &&
  strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) {
  $authOn  = (bool)($config['ask_pass_s'] ?? false);
  $hasPass = !empty($config['app_pass_s'] ?? '');
  if ($authOn && $hasPass) {
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
      J(false,'Unauthorized',[],401);
    }
  }

  $jsonRaw  = file_get_contents('php://input');
  $jsonData = json_decode($jsonRaw, true);
  if (!is_array($jsonData)) { J(false, 'json_error'); }

  /* --------------------- 2FA ACTION ROUTER (yalnız 2FA) --------------------- */
  $act = (string)($jsonData['action'] ?? '');
  if ($act === 'twofa_email_send') {
    $to = (string)($jsonData['to'] ?? $jsonData['email'] ?? ($config['email_address_s'] ?? ''));
    twofa_email_send($to);
  }
  if ($act === 'twofa_email_verify') {
    twofa_email_verify((string)($jsonData['code'] ?? ''));
  }
  if ($act === 'totp_enroll_start') {
    $issuer  = (string)($jsonData['issuer'] ?? 'HAMU DevPanel');
    $account = (string)($jsonData['account'] ?? ($config['email_address_s'] ?? 'user@localhost'));
    totp_enroll_start($issuer, $account);
  }
  if ($act === 'totp_enroll_verify') {
    totp_enroll_verify($config, (string)($jsonData['code'] ?? ''), $CONFIG_FP);
  }
  if ($act === 'totp_check') {
    totp_check_from_config($config, (string)($jsonData['code'] ?? ''));
  }
  if ($act === 'backup_codes_generate') {
    $n   = max(1, (int)($jsonData['n'] ?? 10));
    $len = max(6, (int)($jsonData['len'] ?? 10));
    ok(['codes'=>backup_codes_generate($n,$len)]);
  }
  if ($act === 'backup_codes_save') {
    $codes = $jsonData['codes'] ?? [];
    if (is_string($codes)) { $codes = preg_split('/[\r\n,]+/', $codes); }
    $codes = array_values(array_filter(array_map('trim', (array)$codes)));
    $shown = backup_codes_save($config, $codes, $CONFIG_FP);
    ok(['saved'=>count($shown), 'codes'=>$shown]); // düz metin UI’da bir kez göster
  }
  if ($act === 'backup_code_consume') {
    backup_code_consume($config, (string)($jsonData['code'] ?? ''), $CONFIG_FP);
  }

  /* ------------------- /2FA ACTION ROUTER (bitti) ------------------- */

  // (DEVAM) — config kaydetme akışı (2FA harici)
  if (!$hamuLocal && array_key_exists('ask_pass_s', $jsonData) && !filter_var($jsonData['ask_pass_s'], FILTER_VALIDATE_BOOLEAN)) J(false, 'Production authentication is mandatory', [], 400);
  if (isset($jsonData['app_pass_s']) && $jsonData['app_pass_s'] !== '' && strlen((string)$jsonData['app_pass_s']) < 16) J(false, 'Password must contain at least 16 characters', [], 400);
  // 1) app_pass_s (boş gelirse mevcut değeri koru; doluysa hashle)
  if (array_key_exists('app_pass_s', $jsonData)) {
    if (strlen((string)$jsonData['app_pass_s']) > 0) {
      $jsonData['app_pass_s'] = password_hash($jsonData['app_pass_s'], PASSWORD_DEFAULT);
    } else {
      $jsonData['app_pass_s'] = $config['app_pass_s'] ?? "";
    }
  }

  // 2) DB kullanıcı/parola (boş gelirse mevcut değer kalsın; doluysa şifrele)
  if (array_key_exists('db_user_s', $jsonData)) {
    $jsonData['db_user_s'] = ($jsonData['db_user_s']!=='')
      ? encryptData($jsonData['db_user_s'])
      : ($config['db_user_s'] ?? "");
  }
  if (array_key_exists('db_pass_s', $jsonData)) {
    $jsonData['db_pass_s'] = ($jsonData['db_pass_s']!=='')
      ? encryptData($jsonData['db_pass_s'])
      : ($config['db_pass_s'] ?? "");
  }

  // 3) ignore_folders -> text alanına da yaz (normalize)
  if (isset($jsonData['ignore_folders'])) {
    $jsonData['ignore_folders_s']      = true;
    $jsonData['ignore_folders_s_text'] = (string)$jsonData['ignore_folders'];
  }

  // 4) Virgüllü listeleri normalize et
  foreach (['custom_css_list_s','custom_js_list_s','ignore_folders_s_text'] as $csvKey) {
    if (isset($jsonData[$csvKey])) {
      $parts = array_filter(array_map('trim', explode(',', (string)$jsonData[$csvKey])));
      $jsonData[$csvKey] = implode(',', $parts);
    }
  }
  if (isset($jsonData['google_font_url_s'])) {
    $jsonData['google_font_url_s'] = trim((string)$jsonData['google_font_url_s']);
  }

  /* ---------------------------------------------------------------------- */
  /* 5) 2FA alanları (mevcut mantık — KORUNDU)                               */
  /* ---------------------------------------------------------------------- */

  // 5.a) 2FA method normalizasyonu (sadece "email" | "app")
  if (isset($jsonData['2fa_method_s'])) {
    $method = strtolower((string)$jsonData['2fa_method_s']);
    if (!in_array($method, ['email','app'], true)) {
      $method = $config['2fa_method_s'] ?? 'email';
    }
    $jsonData['2fa_method_s'] = $method;
  }

  // 5.b) E-posta adresi (trim + temel doğrulama; boş gelirse eskisini koru)
  if (array_key_exists('email_address_s', $jsonData)) {
    $em = trim((string)$jsonData['email_address_s']);
    if ($em === '') {
      $jsonData['email_address_s'] = $config['email_address_s'] ?? '';
    } else {
      if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $jsonData['email_address_s'] = $em;
      } else {
        $jsonData['email_address_s'] = $config['email_address_s'] ?? '';
      }
    }
  }

  // 5.c) TOTP secret kaydı
  if (array_key_exists('totp_secret_b32', $jsonData)) {
    $plain = trim((string)$jsonData['totp_secret_b32']);
    if ($plain !== '') {
      $jsonData['totp_secret_b32_enc'] = encryptData($plain);
    }
    unset($jsonData['totp_secret_b32']); // plain'i asla config'te tutma
  }
  if (array_key_exists('totp_secret_b32_enc', $jsonData)) {
    $enc = (string)$jsonData['totp_secret_b32_enc'];
    if ($enc === '') {
      $jsonData['totp_secret_b32_enc'] = $config['totp_secret_b32_enc'] ?? '';
    } else {
      $jsonData['totp_secret_b32_enc'] = $enc;
    }
  }

  // 5.d) Enrollment işaretleri
  if (array_key_exists('totp_enrolled_at', $jsonData)) {
    if ($jsonData['totp_enrolled_at'] === 'now') {
      $jsonData['totp_enrolled_at'] = date('c');
    } elseif ($jsonData['totp_enrolled_at'] === '' || $jsonData['totp_enrolled_at'] === null) {
      $jsonData['totp_enrolled_at'] = $config['totp_enrolled_at'] ?? null;
    }
  }

  // 5.e) Backup kodları
  if (isset($jsonData['backup_codes'])) {
    $codes = $jsonData['backup_codes'];
    if (is_string($codes)) {
      $codes = preg_split('/[\r\n,]+/', $codes);
    }
    $codes = array_filter(array_map('trim', (array)$codes));
    $hashes = [];
    foreach ($codes as $c) {
      if ($c !== '') $hashes[] = hash('sha256', $c);
    }
    $jsonData['backup_codes_sha256'] = $hashes;
    unset($jsonData['backup_codes']); // plain'i tutma
  }

  // 5.f) remember_device_days
  if (isset($jsonData['remember_device_days'])) {
    $days = (int)$jsonData['remember_device_days'];
    if ($days < 0) $days = 0;
    if ($days > 365) $days = 365;
    $jsonData['remember_device_days'] = $days;
  }

  // --- yardımcı: güvenli boolean çevirici ---
if (!function_exists('boolish')) {
  function boolish($v): bool {
    if (is_bool($v)) return $v;
    if (is_int($v))  return $v === 1;
    if (is_string($v)) {
      $t = strtolower(trim($v));
      if ($t === '1' || $t === 'true' || $t === 'on' || $t === 'yes')  return true;
      if ($t === '0' || $t === 'false' || $t === 'off' || $t === 'no' || $t === '') return false;
    }
    return (bool)$v; // son çare
  }
}

  /* ---------------------------------------------------------------------- */
  /* 6) Merge ve kaydet                                                      */
  /* ---------------------------------------------------------------------- */
  $newConfig = array_merge($config, $jsonData);

  try {
    writeJsonAtomic($CONFIG_FP, $newConfig, 0640);
    $config = $newConfig;
    J(true,'jsonSaved');
  } catch (Throwable $e) {
    @error_log("[config.php/save] " . $e->getMessage());
    J(false,'no_write_json');
  }
}


/* ------------------------ 2FA: yardımcılar ------------------------ */

// env key'inden türetilmiş HMAC anahtarı (email kodu vs. için)
function twofa_hmac_key(): string {
  $envKeyB64 = getenv('HAMU_ENC_KEY_B64') ?: ''; // senin env değişken adın neyse onu kullan
  $envKeyRaw = $envKeyB64 ? base64_decode($envKeyB64, true) : '';
  if ($envKeyRaw === '' || $envKeyRaw === false) {
    // ENV yoksa aktif enc key’den türet (deterministik ve güvenli)
    $envKeyRaw = getActiveEncKeyBin();
  }
  // ek bağlam (context) ile derive:
  return hash('sha256', $envKeyRaw . '|hamu-2fa', true);
}

// güvenli karşılaştırma
function safe_eq($a, $b): bool {
  return hash_equals((string)$a, (string)$b);
}

// maskeli e-posta (mbstring yoksa fallback)
function mask_email($em) {
  if (!is_string($em) || strpos($em,'@')===false) return $em;
  [$u,$d] = explode('@',$em,2);
  if (function_exists('mb_substr') && function_exists('mb_strlen')) {
    $u2 = mb_substr($u,0,2) . str_repeat('•', max(0, mb_strlen($u)-2));
  } else {
    $u2 = substr($u,0,2) . str_repeat('•', max(0, strlen($u)-2));
  }
  return $u2 . '@' . $d;
}

// basit cevap
function ok($data=[]){ J(true,'ok',$data); }
function err($msg='error', $code=400){ J(false,$msg,[], $code); }


/* ------------------------ 2FA: EMAIL kodu ------------------------ */

// kod üret & sakla & mail at
function twofa_email_send(string $to, int $digits=6, int $ttl=600) {
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) err('invalid_email');

  // rate limit (60sn)
  $now = time();
  $last = $_SESSION['2fa_email_last_sent'] ?? 0;
  if ($now - $last < 60) err('rate_limited');

  $min = (10 ** ($digits-1));
  $max = (10 ** $digits) - 1;
  $code = (string)random_int($min, $max);

  $key  = twofa_hmac_key();
  $hash = hash_hmac('sha256', $code, $key);

  $_SESSION['2fa_email'] = [
    'hash' => $hash,
    'exp'  => $now + $ttl,
    'tries'=> 0,
    'to'   => $to,
  ];
  $_SESSION['2fa_email_last_sent'] = $now;

  // ---- mail gönderimi ----
  $subject = 'Giriş Doğrulama Kodu';
  $body    = "Tek kullanımlık kodunuz: {$code}\nBu kod {$ttl} sn geçerlidir.";
  $sent    = false;

  // PHPMailer varsa kullan (opsiyonel)
  if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    try {
      $phpMailerClass = 'PHPMailer\PHPMailer\PHPMailer';
      $m = new $phpMailerClass(true);
      // $m->isSMTP(); $m->Host=...; $m->SMTPAuth=true; ...
      $m->setFrom('no-reply@localhost','HAMU');
      $m->addAddress($to);
      $m->Subject = $subject;
      $m->Body    = $body;
      $sent = $m->send();
    } catch(Throwable $e) { $sent = false; }
  }

  // PHPMailer yoksa mail() dene
  if (!$sent) {
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($to, $subject, $body, $headers);
  }

  ok([
    'to_masked' => mask_email($to),
    'ttl'       => $ttl,
    'sent'      => true
  ]);
}

function twofa_email_verify(string $code) {
  $ctx = $_SESSION['2fa_email'] ?? null;
  if (!$ctx) err('no_code');

  $now = time();
  if (($ctx['exp'] ?? 0) < $now) {
    unset($_SESSION['2fa_email']);
    err('expired');
  }

  // 5 deneme limiti
  $_SESSION['2fa_email']['tries'] = (int)($ctx['tries'] ?? 0) + 1;
  if ($_SESSION['2fa_email']['tries'] > 5) {
    unset($_SESSION['2fa_email']);
    err('too_many_attempts');
  }

  $key  = twofa_hmac_key();
  $hash = hash_hmac('sha256', (string)$code, $key);
  if (!safe_eq($hash, $ctx['hash'])) err('invalid_code');

  unset($_SESSION['2fa_email']); // tek kullanımlık
  ok(['verified'=>true]);
}


/* ------------------------ 2FA: TOTP ------------------------ */

// BASE32 decode (RFC 4648)
function b32_decode($b32) {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string)$b32));
  $bits = '';
  for ($i=0; $i<strlen($b32); $i++) {
    $v = strpos($alphabet, $b32[$i]);
    if ($v === false) continue;
    $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
  }
  $out = '';
  for ($j=0; $j+8 <= strlen($bits); $j+=8) {
    $out .= chr(bindec(substr($bits,$j,8)));
  }
  return $out;
}

function totp_now($secret_b32, $period=30, $digits=6, $algo='sha1', $t=null) {
  $t = $t ?? time();
  $counter = floor($t / $period);
  return hotp($secret_b32, $counter, $digits, $algo);
}

function hotp($secret_b32, $counter, $digits=6, $algo='sha1') {
  $key = b32_decode($secret_b32);
  $binCounter = pack('N*', 0) . pack('N*', $counter);
  $hash = hash_hmac($algo, $binCounter, $key, true);
  $offset = ord(substr($hash,-1)) & 0x0F;
  $trunc = ((ord($hash[$offset]) & 0x7F) << 24)
         | ((ord($hash[$offset+1]) & 0xFF) << 16)
         | ((ord($hash[$offset+2]) & 0xFF) << 8)
         | (ord($hash[$offset+3]) & 0xFF);
  $otp = $trunc % (10 ** $digits);
  return str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
}

function totp_verify($secret_b32, $code, $period=30, $digits=6, $algo='sha1', $window=1) {
  $t = time();
  for ($i=-$window; $i<=$window; $i++) {
    if (safe_eq(totp_now($secret_b32, $period, $digits, $algo, $t + $i*$period), (string)$code)) {
      return true;
    }
  }
  return false;
}

// kayıt başlat: secret üret, otpauth URI döndür (secret'ı HENÜZ config'e yazmıyoruz)
function totp_enroll_start(string $issuer, string $account) {
  $raw = random_bytes(20); // 160-bit
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $b32 = '';
  // gerçek Base32 encoder (kısa)
  $bin = '';
  for ($i=0; $i<strlen($raw); $i++) $bin .= str_pad(decbin(ord($raw[$i])), 8, '0', STR_PAD_LEFT);
  for ($j=0; $j<strlen($bin); $j+=5) {
    $chunk = substr($bin,$j,5);
    if (strlen($chunk)<5) $chunk = str_pad($chunk,5,'0',STR_PAD_RIGHT);
    $b32 .= $alphabet[bindec($chunk)];
  }

  $_SESSION['totp_enroll_secret_b32'] = $b32; // geçici

  $issuerQ  = rawurlencode($issuer);
  $accountQ = rawurlencode($account);
  $uri = "otpauth://totp/{$issuerQ}:{$accountQ}?secret={$b32}&issuer={$issuerQ}&period=30&digits=6&algorithm=SHA1";

  ok([
    'secret_b32' => $b32,    // UI'da BİR KEZ göster
    'otpauth_uri'=> $uri     // Frontend QR kitaplığı ile QR’a çevir
  ]);
}

// kayıt doğrula: kullanıcıdan kod al, doğruysa secret'ı ŞİFRELEYİP config'e kalıcı yaz
function totp_enroll_verify(array &$config, string $code, string $confPath) {
  $secret = $_SESSION['totp_enroll_secret_b32'] ?? '';
  if (!$secret) err('no_enroll');

  if (!totp_verify($secret, (string)$code)) err('invalid_code');

  // şifreleyip kalıcılaştır
  $enc = encryptData($secret);
  $config['totp_secret_b32_enc'] = $enc;
  $config['totp_enrolled_at']    = date('c');

  // >>> KRİTİK: TOTP kurulunca 2FA'yı APP olarak aktifleştir
  $config['use_auth_app']  = true;
  $config['2fa_s']         = true;
  $config['2fa_method_s']  = 'app';

  writeJsonAtomic($confPath, $config, 0640);
  unset($_SESSION['totp_enroll_secret_b32']);

  ok(['enrolled'=>true]);
}

// normal doğrulama (giriş sırasında)
function totp_check_from_config(array $config, string $code) {
  $enc = $config['totp_secret_b32_enc'] ?? '';
  if ($enc === '') err('not_enrolled');
  $secret = decryptData($enc);
  if (!totp_verify($secret, (string)$code)) err('invalid_code');
  ok(['verified'=>true]);
}

/* ------------------------ 2FA: Backup kodlar ------------------------ */

function backup_codes_generate(int $n=10, int $len=10): array {
  $codes = [];
  for ($i=0; $i<$n; $i++) {
    $raw = rtrim(strtr(base64_encode(random_bytes(8)), '+/', '9A'), '='); // 12-13 char
    $code = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($raw)), 0, $len);
    $codes[] = $code;
  }
  return $codes;
}

function backup_codes_save(array &$config, array $codes, string $confPath) {
  $hashes = [];
  foreach ($codes as $c) $hashes[] = hash('sha256', $c);
  $config['backup_codes_sha256'] = $hashes;
  writeJsonAtomic($confPath, $config, 0640);
  return $codes; // düz metni UI’da BİR KEZ göster
}

function backup_code_consume(array &$config, string $code, string $confPath) {
  $h = hash('sha256', $code);
  $list = $config['backup_codes_sha256'] ?? [];
  $idx = array_search($h, $list, true);
  if ($idx === false) err('invalid_backup');
  array_splice($list, $idx, 1);
  $config['backup_codes_sha256'] = $list;
  writeJsonAtomic($confPath, $config, 0640);
  ok(['used'=>true, 'remaining'=>count($list)]);
}




/* -------------------------------------------------------------------------- */
/* PROJE API (_prj / _hamu_project)                                   */
/* -------------------------------------------------------------------------- */
$__prj = (isset($_GET['_prj']) && $_GET['_prj']=='1') || (isset($_POST['_hamu_project']) && $_POST['_hamu_project']=='1');

if ($__prj) {
  $authOn  = (bool)($config['ask_pass_s'] ?? false);
  $hasPass = !empty($config['app_pass_s'] ?? '');
  $act     = $_POST['action'] ?? '';
  if ($authOn && $hasPass && $act !== 'auth_login') {
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
      J(false,'Unauthorized',[],401);
    }
  }
}

if ($__prj && ($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && isset($_POST['action'])) {

  /* ---- helpers ---- */
  function isAdmin(){
    global $config;
    $authOn  = (bool)($config['ask_pass_s'] ?? false);
    $hasPass = !empty($config['app_pass_s'] ?? '');
    if (!($authOn && $hasPass)) return true; // koruma kapalıysa serbest
    return ($_SESSION['authenticated'] ?? false) === true;
  }
    function csrfOk(): bool {
        $in   = $_POST['csrf'] ?? $_POST['csrf_token'] ?? null;
        $sess = $_SESSION['csrf_token'] ?? null;
        return is_string($in) && is_string($sess) && $in !== '' && hash_equals($sess, $in);
      }
  function logAct($act,$folder,$det=[]){ global $LOG_FILE_ACTIONS; @file_put_contents($LOG_FILE_ACTIONS, sprintf("[%s] user=%s ip=%s action=%s folder=%s details=%s\n", date('Y-m-d H:i:s'), $_SESSION['username']??'unknown', $_SERVER['REMOTE_ADDR']??'-', $act, $folder, json_encode($det,JSON_UNESCAPED_UNICODE)), FILE_APPEND); }
  function safeJoin($base,$part){ $p=rtrim($base,'/').'/'.ltrim($part,'/'); $rb=realpath($base)?:$base; $rp=realpath($p); if($rp===false) return $p; return (strpos($rp,$rb)===0)?$rp:false; }
  function validName($n){ if($n===''||$n==='.'||$n==='..') return false; return (bool)preg_match('/^[A-Za-z0-9._-]+$/u',$n); }

  // Merkezi meta JSON
  function readMeta(){ global $META_FP; if(!is_file($META_FP)) return ['items'=>[], '_generated_at'=>time()]; $a=json_decode(@file_get_contents($META_FP),true); return is_array($a)?$a:['items'=>[], '_generated_at'=>time()]; }
  function writeMeta($d){ global $META_FP,$LOG_FILE_META; $d['_generated_at']=time(); writeJsonAtomic($META_FP, $d, 0640); @file_put_contents($LOG_FILE_META, "[".date('Y-m-d H:i:s')."] WRITE count=".count(($d['items']??[]))."\n", FILE_APPEND); }

  // Kapak/Resim
  function ensureGd(){ return function_exists('imagecreatetruecolor'); }
  function png150($src,$dst){ if(!ensureGd())return false; $bin=@file_get_contents($src); if($bin===false)return false; $im=@imagecreatefromstring($bin); if(!$im)return false; $W=imagesx($im); $H=imagesy($im); $D=imagecreatetruecolor(150,150); imagealphablending($D,false); imagesavealpha($D,true); $c=min($W,$H); $sx=(int)(($W-$c)/2); $sy=(int)(($H-$c)/2); imagecopyresampled($D,$im,0,0,$sx,$sy,150,150,$c,$c); $ok=imagepng($D,$dst); imagedestroy($im); imagedestroy($D); return $ok; }
  function moveCover($folderPath,$finalName){ if(empty($_FILES['cover']['tmp_name'])) return [false,'no_file']; $tmp=$_FILES['cover']['tmp_name']; $dst=rtrim($folderPath,'/').'/'.$finalName; $ok=png150($tmp,$dst); if(!$ok){ @move_uploaded_file($tmp,$dst); $ok=is_file($dst); } return [$ok,$dst]; }

  // Proje klasörü JSON (per-folder)
  function folderJsonPath($folder){
    return rtrim(HAMU_DOCROOT,'/').'/'.trim($folder,'/').'/'.basename($folder).'.json';
  }  function readFolderJson($folder){ $fp=folderJsonPath($folder); if(!is_file($fp)) return []; $j=json_decode(@file_get_contents($fp),true); return is_array($j)?$j:[]; }
  function writeFolderJson($folder,$data){
    $fp = folderJsonPath($folder);
    $cur = readFolderJson($folder);

    // ftp alanlarını topla (nested)
    $ftpKeys=['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_pass','ftp_folder','host_url','description','download_folder'];
    $ftp = isset($cur['ftp']) && is_array($cur['ftp']) ? $cur['ftp'] : [];
    foreach($ftpKeys as $k){
      if(array_key_exists($k,$data)){
        $v=(string)$data[$k];
        if($k==='ftp_pass'){
          if($v!=='') $ftp[$k]=$v; // boş gelirse eski kalsın
        } else {
          $ftp[$k]=$v;
        }
        unset($data[$k]);
      }
    }
    if(!empty($ftp)) $data['ftp']=$ftp;

    $merged = array_merge($cur,$data);
    writeJsonAtomic($fp, $merged, 0640);
    return $merged;
  }

  /* ---- CSRF ---- */
  if (!csrfOk()) { J(false,'csrf_error'); }

  /* ---- ADMIN KAPISI ---- */
  if (!isAdmin()) { J(false,'no_admin'); }

  /* ---- ACTION ROUTER ---- */
  $act = $_POST['action'];

  /* AUTH LOGIN */
  if ($act === 'auth_login') {
    J(false, 'Use the main login flow with two-factor verification', [], 403);
    $pass = (string)($_POST['password'] ?? '');
    $authOn  = (bool)($config['ask_pass_s'] ?? false);
    $hasPass = !empty($config['app_pass_s'] ?? '');
    if (!($authOn && $hasPass)) J(false, 'auth_disabled');
    if (password_verify($pass, $config['app_pass_s'])) {
      $_SESSION['authenticated'] = true;
      $_SESSION['is_admin'] = true;
      csrf_token();
      J(true, 'login_ok');
    } else {
      J(false, 'invalid_password');
    }
  }

  /* META WRITE (UI + FTP alanlarını da kabul eder) */
  if ($act==='project_meta_write') {
    $folder=trim($_POST['folder']??''); if(!validName($folder)) J(false,'invalid_folder_name');
    $abs=safeJoin($DOCROOT,$folder); if($abs===false||!is_dir($abs)) J(false,'no_folder');

    $meta=readMeta(); $items=$meta['items']??[]; $m=$items[$folder]??[];

    // UI meta
    if(isset($_POST['favorite']))   $m['favorite'] = !empty($_POST['favorite'])?1:0;
    if(isset($_POST['badge_text'])) $m['badge_text']= trim($_POST['badge_text']);
    if(isset($_POST['badge_color']))$m['badge_color']= trim($_POST['badge_color']);
    if(isset($_POST['tags'])){
      $tagsRaw=trim((string)$_POST['tags']);
      $m['tags'] = ($tagsRaw==='')? [] : array_values(array_filter(array_map('trim', preg_split('/[;,\n]+/',$tagsRaw))));
    }

    // FTP alanlarını da kabul et
    $fj  = readFolderJson($folder);
    $ftp = isset($fj['ftp']) && is_array($fj['ftp']) ? $fj['ftp'] : (isset($m['ftp'])?$m['ftp']:[]);
    foreach(['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_pass','ftp_folder','host_url','description','download_folder'] as $k){
      if(!array_key_exists($k,$_POST)) continue; $v=trim((string)$_POST[$k]);
      if($k==='ftp_pass'){
        if($v!=='') $ftp[$k]=encryptData($v);
      } elseif($k==='ftp_ssl'){
        $ftp[$k] = ($v==='1'||strtolower($v)==='true')?'true':'false';
      } else {
        $ftp[$k]=$v;
      }
    }
    if(!empty($ftp)) $m['ftp']=$ftp;

    // Kapak
    if(!empty($_FILES['cover']) && is_uploaded_file($_FILES['cover']['tmp_name'])){
      [$ok,$dst]=moveCover($abs, basename($folder).'.png'); if(!$ok) J(false,'cover_upload_failed');
      $m['project_icon']="/{$folder}/".basename($folder).".png";
    } else {
      $maybe=$abs.'/'.basename($folder).'.png'; if(is_file($maybe)) $m['project_icon']="/{$folder}/".basename($folder).".png";
    }

    // Merkez meta yaz
    $items[$folder]=$m; $meta['items']=$items; writeMeta($meta);
    // Klasör JSON (ftp dahil)
    $jsonMerged = writeFolderJson($folder, $m);

    @file_put_contents($LOG_FILE_META,"[".date('Y-m-d H:i:s')."] WRITE_META folder={$folder} meta=".json_encode($m,JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    logAct('WRITE_META',$folder,['favorite'=>$m['favorite']??0,'badge'=>$m['badge_color']??'','tags'=>$m['tags']??[]]);
    J(true,'settings_ok',['meta'=>$m,'folder_json'=>$jsonMerged]);
  }

  /* RENAME */
  if ($act==='project_rename') {
    $old=trim($_POST['old']??''); $new=trim($_POST['new']??'');
    if(!validName($old)||!validName($new)) J(false,'invalid_name');
    $absOld=safeJoin($DOCROOT,$old); $absNew=safeJoin($DOCROOT,$new);
    if($absOld===false||!is_dir($absOld)) J(false,'no_folder_res');
    if($absNew!==false && file_exists($absNew)) J(false,'file_here');

    $dstPath = $absNew ?: (dirname($absOld).'/'.$new);
    $ok=@rename($absOld,$dstPath); if(!$ok) J(false,'rename_failed');

    // Kapak taşı
    foreach (['png','jpg','jpeg','webp'] as $ext) {
      $dst = $DOCROOT."/{$new}/{$new}.{$ext}";
      $candidates = [
        $DOCROOT."/{$new}/{$old}.{$ext}",
        $DOCROOT."/{$old}/{$old}.{$ext}",
      ];
      foreach ($candidates as $src) {
        if (is_file($src)) { @rename($src, $dst); break 2; }
      }
    }

    // Merkez meta anahtar taşıma
    $meta=readMeta(); $items=$meta['items']??[];
    if(isset($items[$old])){
      $items[$new]=$items[$old];
      if(!empty($items[$new]['project_icon'])) $items[$new]['project_icon']="/{$new}/{$new}.png";
      unset($items[$old]);
      $meta['items']=$items; writeMeta($meta);
    }

    // Ortak klasör JSON taşı
    $oldJsonPre  = $DOCROOT."/{$old}/{$old}.json";
    $oldJsonPost = $DOCROOT."/{$new}/{$old}.json";
    $newJson     = $DOCROOT."/{$new}/{$new}.json";
    if (is_file($oldJsonPre)) {
      @rename($oldJsonPre, $newJson);
    } elseif (is_file($oldJsonPost)) {
      if (!is_file($newJson)) {
        @rename($oldJsonPost, $newJson);
      } else {
        $o = @json_decode(@file_get_contents($oldJsonPost), true);
        $n = @json_decode(@file_get_contents($newJson), true);
        if (!is_array($n)) $n = [];
        if (!is_array($o)) $o = [];
        if (isset($o['ftp'])) {
          if (!isset($n['ftp']) || !is_array($n['ftp'])) $n['ftp'] = [];
          foreach ($o['ftp'] as $k => $v) {
            if (!isset($n['ftp'][$k]) || $n['ftp'][$k]==='') $n['ftp'][$k] = $v;
          }
        } else {
          $keys = ['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_pass','ftp_folder','host_url','description','download_folder'];
          $flat = [];
          foreach ($keys as $k) if (isset($o[$k])) $flat[$k] = $o[$k];
          if ($flat) {
            if (!isset($n['ftp']) || !is_array($n['ftp'])) $n['ftp'] = [];
            $n['ftp'] = array_replace($flat, $n['ftp']);
          }
        }
        writeJsonAtomic($newJson, $n, 0640);
        @unlink($oldJsonPost);
      }
    }
    logAct('RENAME',$old,['new'=>$new]);
    J(true,'rename_success',['folder'=>$new,'project_icon'=>"/{$new}/{$new}.png"]);
  }

  /* HIDE / UNHIDE */
  if ($act==='project_hide' || $act==='project_unhide') {
    $name = trim($_POST['folder'] ?? ''); if ($name === '' || !validName($name)) J(false,'invalid_name');
    $hide = ($act === 'project_hide');

    $base = ltrim($name, '.'); // "ProjA"
    $dot  = '.'.$base;         // ".ProjA"

    if ($hide) {
      if ($name[0]==='.') J(false,'already_hidden');
      $src = $base;  // "ProjA"
      $dst = $dot;   // ".ProjA"
    } else {
      $src = $dot;   // ".ProjA"
      $dst = $base;  // "ProjA"
    }

    $absSrc=safeJoin($DOCROOT,$src); $absDst=safeJoin($DOCROOT,$dst);
    if($absSrc===false||!is_dir($absSrc)) J(false,'no_folder_res');
    if($absDst!==false && file_exists($absDst)) J(false,'file_here');
    $ok=@rename($absSrc,$absDst ?: (dirname($absSrc).'/'.$dst)); if(!$ok) J(false,'op_failed');

    $oldPng=$DOCROOT."/{$dst}/".($hide? $name : '.'.$dst).".png"; $newPng=$DOCROOT."/{$dst}/{$dst}.png";
    if(is_file($oldPng)) @rename($oldPng,$newPng);

    // Merkez meta anahtar taşı
    $meta=readMeta(); $items=$meta['items']??[];
    if(isset($items[$src])){
      $items[$dst]=$items[$src];
      if(!empty($items[$dst]['project_icon'])) $items[$dst]['project_icon']="/{$dst}/{$dst}.png";
      unset($items[$src]); $meta['items']=$items; writeMeta($meta);
    }

    // Ortak klasör JSON
    $oldJson = folderJsonPath($src); $newJson = folderJsonPath($dst); if(is_file($oldJson)) @rename($oldJson,$newJson);
    logAct($hide?'HIDE':'UNHIDE',$src,['to'=>$dst]);
    J(true, $hide ? 'hide_success' : 'unhide_success', ['folder'=>$dst]);
  }

  /* DELETE */
  if ($act==='delete_project') {
    $folder=trim($_POST['folder']??''); if(!validName($folder)) J(false,'invalid_name');
    $abs=safeJoin($DOCROOT,$folder); if($abs===false||!is_dir($abs)) J(false,'no_folder');

    // Güvenli tara: root veya .hamu silinmesin
    $real=realpath($abs);
    foreach ([realpath($DOCROOT),realpath($DOCROOT.'/.hamu')] as $g){
      if($g && $real===$g) J(false,'protected_path');
    }
    // recursive remove
    $rii = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach($rii as $fi){ $fi->isDir()? @rmdir($fi->getPathname()): @unlink($fi->getPathname()); }
    @rmdir($abs);

    $meta=readMeta(); if(!empty($meta['items'][$folder])){ unset($meta['items'][$folder]); writeMeta($meta); }
    logAct('DELETE',$folder);
    J(true,'folder_deleted_permanent');
  }

  /* FAVORITE TOGGLE */
  if ($act==='project_favorite_toggle') {
    $folder=trim($_POST['folder']??''); if(!validName($folder)) J(false,'invalid_name');
    $meta=readMeta(); $items=$meta['items']??[]; $m=$items[$folder]??[]; $cur=!empty($m['favorite']); if($cur) unset($m['favorite']); else $m['favorite']=1; $items[$folder]=$m; $meta['items']=$items; writeMeta($meta);
    // per-folder JSON'a da yansıt (opsiyonel)
    $fj=readFolderJson($folder); $fj['favorite']=empty($cur)?1:0; writeJsonAtomic(folderJsonPath($folder), $fj, 0640);
    logAct('FAV_TOGGLE',$folder,['favorite'=>!$cur]); J(true,'favorite_update_success',['favorite'=>!$cur]);
  }

  /* FTP WRITE (yalnız ftp alanlarını günceller) */
  if ($act==='project_ftp_write') {
    $folder=trim($_POST['folder']??''); if(!validName($folder)) J(false,'invalid_name');
    $abs=safeJoin($DOCROOT,$folder); if($abs===false||!is_dir($abs)) J(false,'no_folder');

    $curJson = readFolderJson($folder);
    $ftp = isset($curJson['ftp']) && is_array($curJson['ftp']) ? $curJson['ftp'] : [];
    foreach(['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_pass','ftp_folder','host_url','description','download_folder'] as $k){
      if(!array_key_exists($k,$_POST)) continue; $v=trim((string)$_POST[$k]);
      if($k==='ftp_pass'){ if($v!=='') $ftp[$k]=encryptData($v); }
      elseif($k==='ftp_ssl'){ $ftp[$k] = ($v==='1'||strtolower($v)==='true')?'true':'false'; }
      else { $ftp[$k]=$v; }
    }
    $curJson['ftp']=$ftp;
    writeJsonAtomic(folderJsonPath($folder), $curJson, 0640);

    // Merkez meta: ftp dışı alanlara dokunma
    $meta=readMeta(); if(!isset($meta['items'][$folder])) $meta['items'][$folder]=[]; writeMeta($meta);

    logAct('FTP_WRITE',$folder,['has_ftp'=>!empty($ftp)]);
    J(true,'ftp_info_saved', ['folder_json'=>$curJson]);
  }

  /* META READ (UI doldurma) */
  if ($act==='project_meta_read') {
    $folder=trim($_POST['folder']??''); if(!validName($folder)) J(false,'invalid_name');
    $meta=readMeta(); $m=$meta['items'][$folder]??[]; $fj=readFolderJson($folder);
    $combined=$m;
    foreach(['badge_text','badge_color','tags','favorite','project_icon'] as $k){ if(!isset($combined[$k]) && isset($fj[$k])) $combined[$k]=$fj[$k]; }
    if(isset($fj['ftp'])) $combined['ftp']=$fj['ftp'];
    // Şifre plaintext asla dönmüyor
    J(true,'ok',['meta'=>$combined, 'haspass'=> !empty(($fj['ftp']['ftp_pass'] ?? '')) ]);
  }

  J(false,'unknown_op');
}

/* -------------------------------------------------------------------------- */
/* 16) Tema (include edilen sayfalar)                                         */
/* -------------------------------------------------------------------------- */
$theme = config('theme_s') ? 'dark' : 'light';
$lang =  $_COOKIE['hamu_lang'] ?? 'EN';
/* -------------------------------------------------------------------------- */
/* 17) (Opsiyonel) HYDRATE                                                    */
/* -------------------------------------------------------------------------- */
/* Eğer istersen $META_FP yoksa projeleri tarayıp başlangıç meta doldurabilirsin. */

if ($isUnix && $oldUmask !== null) { umask($oldUmask); }
