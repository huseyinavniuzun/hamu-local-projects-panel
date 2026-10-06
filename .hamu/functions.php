<?php
/**
 * HAMU DevPanel - Functions
 * Location: /.hamu/functions.php
 * [TR] Proje genelinde kullanılan yardımcı fonksiyonları içerir (klasör tarama, modül yönetimi, FTP, sıralama vb.).
 * [EN] Contains helper functions used throughout the project (folder scanning, module management, FTP, sorting, etc.).
 */
hamu_session();

/* -------------------------- Dil seçimi (EN/TR) -------------------------- */
// GET üzerinden dil parametresi varsa ve geçerliyse güncelle
if (isset($_GET['lang']) && in_array($_GET['lang'], ['EN','TR'], true)) {
    $_SESSION['hamu_lang'] = $_GET['lang'];
    setcookie('hamu_lang', $_GET['lang'], time() + 86400*30, '/');
} elseif (isset($_COOKIE['hamu_lang'])) {
    $_SESSION['hamu_lang'] = $_COOKIE['hamu_lang'];
} else {
    $_SESSION['hamu_lang'] = 'EN';
    setcookie('hamu_lang', 'EN', time() + 86400*30, '/');
}

/**
 * Config'den gelen URL değeri üzerinden nihai linki oluşturur.
 *
 * @param string $configLink JSON veya diğer konfigürasyon kaynağından gelen URL değeri.
 * @return string İşlenmiş nihai URL.
 */
$rootDir       =  HAMU_DOCROOT;
$includeHidden = getShowHiddenProjects(); // config.php -> getToggle('showHiddenProjects','hidden')
$folders       = scanFolders($rootDir, $includeHidden);
$modules       = scanModules(); // **Modüller çağrılmadan önce kontrol**

/*
[TR] scanFolders fonksiyonu, belirtilen kök dizindeki tüm klasörleri tarar.
      Eğer klasör adı nokta ('.') ile başlıyorsa ya da ".hamu" ise, bu klasörler hariç tutulur.
[EN] The scanFolders function scans through all directories in the specified root directory.
      If the folder name starts with a dot ('.') or is ".hamu", it will be excluded.
*/

function jsonLink(string $configLink): string {
    if (preg_match('~^https?://~i', $configLink)) return $configLink;
    if (preg_match('~^www\.~i', $configLink))     return 'http://' . $configLink;
    if ($configLink !== '' && $configLink[0] === ':') return rtrim(base_url(), '/') . $configLink;
    return rtrim(base_url(), '/') . '/' . ltrim($configLink, '/');
}

function translate($text) {
    $lowerText = strtolower($text);
    $translated = __l($lowerText);
    return ($translated === $lowerText) ? $text : $translated;
}

function mname($filename) {
    $name = preg_replace('/\.php$/i', '', $filename);
    $replacements = [
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        'Ç' => 'C', 'Ğ' => 'G', 'İ' => 'I', 'Ö' => 'O', 'Ş' => 'S', 'Ü' => 'U'
    ];
    $name = strtr($name, $replacements);
    $name = str_replace('_', '-', $name);
    return strtolower($name);
}

if (!function_exists('hamu_2fa_email_send')) {
    function hamu_2fa_email_send(string $to): void {
      // api.php’deki ile aynı HMAC+TTL üretim mantığını buraya taşı
      // PHPMailer varsayılanlarını da burada yap.
    }
  }

/**
 * scanModules
 * [TR] .hamu/modules dizinindeki .php dosyalarını tarar,
 * <title>(.*?)</title> yakalayıp menüde gösterir.
 * [EN] Finds .php files in .hamu/modules, extracts <title> for the menu label.
 */
function scanModules() {
    global $config;
    if (empty($config["modul_s"])) return [];

    $modules = [];
    $path = HAMU_MODULE;
    if (!is_dir($path)) return $modules;

    $files = glob($path . '/*.php');
    foreach ($files as $f) {
        $fileName = basename($f);
        if ($fileName[0] === '.') continue;

        $content = file_get_contents($f);
        if ($content === false) {
            error_log("Error reading file: " . $f);
            continue;
        }

        $title = $fileName;

        if (preg_match('/\$page_title\s*=\s*[\'"](.*?)[\'"]/', $content, $m)) {
            $rawTitle = trim($m[1]);
            $title = translate($rawTitle);
        } elseif (preg_match('/<title>(.*?)<\/title>/i', $content, $m)) {
            $title = trim($m[1]);
        }

        $modules[] = [
            'file'  => mname($fileName),
            'title' => $title
        ];
    }
    return $modules;
}
function module_href($slug){ return '?p=modules&md='.rawurlencode($slug); }

/**
 * scanFolders
 * [TR] root/ klasörü içindeki klasörleri tarar, .png logosu var mı bakar
 * [EN] Scans the root folder for subfolders, checks .png logo if it exists.
 */
function getShowHiddenFlag(): bool {
    return getShowHiddenProjects();
}

/**
 * Klasör tara (gizli klasörleri isteğe bağlı dahil et)
 * $includeHidden NULL ise cookie/GET üzerinden otomatik belirler
 */
function scanFolders(string $rootDir, ?bool $includeHidden = null): array {
    if ($includeHidden === null) $includeHidden = getShowHiddenFlag();

    $folders = [];
    $items = @scandir($rootDir, SCANDIR_SORT_ASCENDING);
    if (!is_array($items)) return $folders;

    foreach ($items as $bn) {
        if ($bn === '.' || $bn === '..') continue;
        if ($bn === '.hamu') continue;
        if (!$includeHidden && isset($bn[0]) && $bn[0] === '.') continue;

        $dir = rtrim($rootDir, '/\\') . DIRECTORY_SEPARATOR . $bn;
        if (is_dir($dir)) $folders[] = $dir;
    }
    return $folders;
}

/**
 * findIndexFile
 * [TR] Belirtilen klasörde index dosyası olup olmadığını kontrol eder.
 * [EN] Checks for the presence of an index file in the given directory.
 */
function findIndexFile($dir) {
    $candidates = ['index.html', 'index.htm', 'index.php', 'default.php'];
    if (!is_dir($dir) || !is_readable($dir)) return null;
    foreach ($candidates as $c) {
        $filePath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $c;
        if (file_exists($filePath)) return $c;
    }
    return null;
}

// Klasör adını kısalt
function short($word, $limit = 13) {
    return (mb_strlen($word) > $limit) ? mb_substr($word, 0, $limit) . '..' : $word;
}

/**
 * Server info
 */
$phpVersion         = phpversion();
$hostName           = gethostname();
$serverName         = $_SERVER['SERVER_NAME']      ?? 'localhost';
$httpServerSoftware = $_SERVER['SERVER_SOFTWARE']  ?? '';
$phpSapi            = PHP_SAPI;

$webServerName     = 'Other';
$webServerVersion  = '';

if ($phpSapi === 'cli-server' || stripos($httpServerSoftware, 'development server') !== false) {
    $webServerName    = 'PHP Built-in Server';
    $webServerVersion = $phpVersion;
} else {
    $patterns = [
        ['OpenResty',         '~\bopenresty(?:/([\w\.\-]+))?~i'],
        ['Tengine',           '~\btengine(?:/([\w\.\-]+))?~i'],
        ['LiteSpeed',         '~\b(openlitespeed|litespeed)(?:/([\w\.\-]+))?~i', 2],
        ['Apache',            '~\bApache(?:/([\w\.\-]+))?~i'],
        ['Nginx',             '~\bnginx(?:/([\w\.\-]+))?~i'],
        ['Microsoft-IIS',     '~\bMicrosoft-IIS(?:/([\w\.\-]+))?~i'],
        ['Caddy',             '~\bcaddy(?:/([\w\.\-]+))?~i'],
        ['lighttpd',          '~\blighttpd(?:/([\w\.\-]+))?~i'],
        ['Cherokee',          '~\bcherokee(?:/([\w\.\-]+))?~i'],
        ['H2O',               '~\bh2o(?:/([\w\.\-]+))?~i'],
        ['Apache Tomcat',     '~\bApache[- ]?Tomcat(?:/([\w\.\-]+))?~i'],
        ['Varnish',           '~\bvarnish(?:/([\w\.\-]+))?~i'],
    ];
    foreach ($patterns as $p) {
        [$name, $regex] = $p;
        $verGroup = $p[2] ?? 1;
        if (preg_match($regex, $httpServerSoftware, $m)) {
            $webServerName    = $name;
            $webServerVersion = isset($m[$verGroup]) ? $m[$verGroup] : '';
            break;
        }
    }
    if ($webServerName === 'Other' && $phpSapi === 'apache2handler') {
        $webServerName    = 'Apache';
        $webServerVersion = '';
    }
}
if (stripos($httpServerSoftware, 'openlitespeed') !== false) {
    $webServerName = 'OpenLiteSpeed';
}

// ini helper
function format_ini_value($key, $value) {
    $bool_directives = ['allow_url_fopen', 'file_uploads', 'display_errors'];
    if (in_array($key, $bool_directives)) {
        if ($value === '' || $value === false) return 'On';
        if ($value === '0') return 'Off';
    }
    return $value;
}
$ini_all = ini_get_all();
ksort($ini_all);

/** Simple markdown (minimal) */
function markdown($md_file) {
    if (empty($md_file)) return "<p>Dosya adı tanımlı değil.</p>";
    if (!file_exists($md_file)) return "<p>Readme dosyası bulunamadı.</p>";

    $mdContent = file_get_contents($md_file);

    $html = preg_replace_callback('/^(#{1,6})\s*(.+)$/m', function ($matches) {
        $level = strlen($matches[1]);
        return "<h{$level}>" . htmlspecialchars(trim($matches[2])) . "</h{$level}>";
    }, $mdContent);

    $html = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $html);
    $html = preg_replace('/(\*|_)(.*?)\1/', '<em>$2</em>', $html);
    $html = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
        $text = htmlspecialchars(trim($matches[1]));
        $url  = htmlspecialchars(trim($matches[2]));
        return "<a href=\"{$url}\" target=\"_blank\">{$text}</a>";
    }, $html);

    $paragraphs = preg_split('/\n\s*\n/', $html);
    $html = '';
    foreach ($paragraphs as $paragraph) $html .= '<p>' . nl2br(trim($paragraph)) . "</p>\n";
    $html = preg_replace('/(<\/h[1-6]>|<\/p>)<br\s*\/?>/', '$1', $html);
return $html;
}

/** Simple log reader */
function logReader($log_file) {
if (empty($log_file)) return "<p>Log dosyası tanımlı değil.</p>";
if (!file_exists($log_file)) return "<p>Log dosyası bulunamadı.</p>";

$logContent = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($logContent === false) return "<p>Log dosyası okunamadı.</p>";

$logContent = array_reverse($logContent);
$html = '';
foreach ($logContent as $line) {
$line = preg_replace('/^\[(.*?)\]/', '<strong>[$1]</strong>', $line);
$line = preg_replace('/\[(.*?)\]/', '[$1]', $line);
$html .= '<p>' . htmlspecialchars($line) . '</p>';
}
return $html;
}

/**
* Basit log yazıcı + rotasyon
*/
function rotate_if_big(string $fp, int $maxBytes): void {
if (is_file($fp) && filesize($fp) > $maxBytes) {
$ts = date('Ymd-His');
@rename($fp, dirname($fp).'/ftp_actions.'.$ts.'.log');
}
}
function logMessage(string $line): bool {
global $LOG_FILE_FTP, $LOG_DIR;
$dir = $LOG_DIR ?? (defined('HAMU_LOG') ? HAMU_LOG : (__DIR__.'/logs'));
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

$fp = $LOG_FILE_FTP ?? ($dir.'/project_actions.log');

if (is_file($fp) && @filesize($fp) > 5 * 1024 * 1024) {
@rename($fp, dirname($fp).'/ftp_actions.'.date('Ymd-His').'.log');
}

$ok = @file_put_contents($fp, '['.date('Y-m-d H:i:s').'] '.$line.PHP_EOL, FILE_APPEND);
if ($ok === false) {
@error_log("[HAMU][FTP-LOG] yazılamadı: $fp line=". $line);
return false;
}
return true;
}

/* --------------------------------------------------
* FTP ext kontrolleri
* --------------------------------------------------*/
function ensureFtpExtensions(bool $needSsl=false): ?string {
if (!extension_loaded('ftp') || !function_exists('ftp_connect')) return 'ftp_err1';
if ($needSsl && !function_exists('ftp_ssl_connect')) return 'ftp_err2';
return null;
}

/* --------------------------------------------------
* FTP JSON yardımcıları (flat + nested)
* --------------------------------------------------*/
function _ftp_collect_post(array $src): array {
$first = function(array $keys, string $def='') use ($src) {
foreach ($keys as $k) if (isset($src[$k]) && $src[$k] !== '') return trim((string)$src[$k]);
return $def;
};
$sslRaw = $first(['ftp_ssl','psmFtpSSL'],'false');
$ssl = strtolower($sslRaw);
$ssl = ($ssl==='1' || $ssl==='true' || $ssl==='on') ? 'true' : 'false';

return [
'ftp_host' => $first(['ftp_host','psmFtpHost']),
'ftp_port' => $first(['ftp_port','psmFtpPort'],'21'),
'ftp_ssl' => $ssl,
'ftp_user' => $first(['ftp_user','psmFtpUser']),
'ftp_pass' => $first(['ftp_pass','psmFtpPass'], ''),
'ftp_folder' => $first(['ftp_folder','psmFtpFolder']),
'host_url' => $first(['host_url','psmHostUrl']),
'description' => $first(['description','psmDesc']),
'download_folder' => $first(['download_folder','psmDown']),
];
}
function _ftp_read_block(string $jsonFile): array {
if (!is_file($jsonFile)) return [];
$raw = @file_get_contents($jsonFile);
$j = @json_decode((string)$raw, true);
if (!is_array($j)) return [];
$src = (isset($j['ftp']) && is_array($j['ftp'])) ? $j['ftp'] : $j;

$out = [
'ftp_host' => $src['ftp_host'] ?? '',
'ftp_port' => (string)($src['ftp_port'] ?? '21'),
'ftp_ssl' => (string)($src['ftp_ssl'] ?? 'false'),
'ftp_user' => $src['ftp_user'] ?? '',
'ftp_folder' => $src['ftp_folder'] ?? '',
'host_url' => $src['host_url'] ?? '',
'description' => $src['description'] ?? '',
'download_folder' => $src['download_folder'] ?? '',
'ftp_pass_enc' => $src['ftp_pass'] ?? '',
];
$out['ftp_port'] = ctype_digit($out['ftp_port']) ? $out['ftp_port'] : '21';
$out['ftp_ssl'] = (in_array(strtolower($out['ftp_ssl']), ['1','true'], true) ? 'true' : 'false');
$out['ftp_pass_plain']= ($out['ftp_pass_enc']!=='') ? decryptData((string)$out['ftp_pass_enc']) : '';
return $out;
}
function _ftp_write_block(string $jsonFile, array $incoming): bool {
$cur = [];
if (is_file($jsonFile)) {
$tmp = @json_decode((string)@file_get_contents($jsonFile), true);
if (is_array($tmp)) $cur = $tmp;
}
if (!isset($cur['ftp']) || !is_array($cur['ftp'])) $cur['ftp'] = [];

$map = ['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_folder','host_url','description','download_folder'];
foreach ($map as $k) {
if (array_key_exists($k, $incoming)) {
$val = (string)$incoming[$k];
if ($k==='ftp_port' && !ctype_digit($val)) $val = '21';
if ($k==='ftp_ssl') {
$v = strtolower($val);
$val = ($v==='1' || $v==='true' || $v==='on') ? 'true' : 'false';
}
$cur['ftp'][$k] = $val;
}
}
if (array_key_exists('ftp_pass', $incoming)) {
$plain = (string)$incoming['ftp_pass'];
if ($plain !== '') $cur['ftp']['ftp_pass'] = encryptData($plain);
}
$json = json_encode($cur, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
return $json!==false && (bool)@file_put_contents($jsonFile, $json);
}

/* --------------------------------------------------
* Yol yardımcıları (FTP)
* --------------------------------------------------*/
function remote_join(string $base, string $rel): string {
$base = preg_replace('#/+#','/',str_replace('\\','/',$base));
$rel = preg_replace('#/+#','/',str_replace('\\','/',$rel));
$base = rtrim($base,'/'); $rel = ltrim($rel,'/');
if ($base === '' || $base === '.') return ($rel===''?'.':$rel);
return $rel==='' ? $base : $base.'/'.$rel;
}
function ftp_base_relative($cid, string $fold): string {
$home = @ftp_pwd($cid) ?: '/';
$home = rtrim(str_replace('\\','/',$home), '/');
$fold = trim(str_replace('\\','/',$fold));
if ($fold === '' || $fold === '.') return '';
if ($fold[0] !== '/') return trim($fold,'/');
if ($home !== '' && strpos($fold, $home) === 0) {
$rest = substr($fold, strlen($home));
return trim($rest,'/');
}
return trim($fold,'/');
}
function ftp_mkdirs($cid, string $path): void {
$path = preg_replace('#/+#','/',str_replace('\\','/',$path));
$path = trim($path);
if ($path === '' || $path === '.') return;
$variants = [ ltrim($path,'/'), '/'.ltrim($path,'/') ];
foreach ($variants as $v) {
$parts = array_filter(explode('/', trim($v,'/')));
$walk = '';
foreach ($parts as $seg) {
$walk = ($walk===''? $seg : $walk.'/'.$seg);
@ftp_mkdir($cid, $walk);
}
}
}
function ftp_upload_directory($cid, string $localBase, string $remoteBase, bool $overwrite=true): void {
$localBase = rtrim(str_replace('\\','/',$localBase), '/');
$remoteBase = trim(str_replace('\\','/',$remoteBase), '/');
if ($remoteBase !== '') ftp_mkdirs($cid, $remoteBase);

$it = new RecursiveIteratorIterator(
new RecursiveDirectoryIterator($localBase, FilesystemIterator::SKIP_DOTS),
RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $fs) {
/** @var SplFileInfo $fs */
$rel = ltrim(str_replace($localBase,'', str_replace('\\','/',$fs->getPathname())), '/');
$remotePath = ($remoteBase===''? $rel : $remoteBase.'/'.$rel);

if ($fs->isDir()) {
if ($remotePath !== '') ftp_mkdirs($cid, $remotePath);
} else {
if (!$overwrite) {
$sz = @ftp_size($cid, $remotePath);
if ($sz !== -1) continue;
}
$dir = str_replace('\\','/', dirname($remotePath));
if ($dir !== '.' && $dir !== '') ftp_mkdirs($cid, $dir);
@ftp_put($cid, $remotePath, $fs->getPathname(), FTP_BINARY);
}
}
}

/* --------------------------------------------------
* FTP LIST — showHidden=true iken -a/-al varyantlarını dene
* --------------------------------------------------*/
function ftp_list_dir_smart($cid, string $dirPath, bool $showHidden): array {
$variants = [$dirPath];
if ($dirPath !== '.' && $dirPath !== '') {
$variants[] = ltrim($dirPath,'/');
$variants[] = '/'.ltrim($dirPath,'/');
}

$mkRawlistArgs = function(string $path) use ($showHidden) {
if ($showHidden) return ["-a $path", "$path -a", "-al $path", "$path -al", $path];
return [$path];
};

foreach (array_unique($variants) as $path) {
foreach ($mkRawlistArgs($path) as $arg) {
$raw = @ftp_rawlist($cid, $arg);
if (is_array($raw) && count($raw)) {
$list = [];
foreach ($raw as $line) {
$parts = preg_split('/\s+/', $line, 9);
if (count($parts) < 9) continue; $perm=$parts[0] ?? '' ; $name=$parts[8] ?? '' ; if ($name==='' || $name==='.' ||
     $name==='..' ) continue; if (!$showHidden && $name[0]==='.' ) continue; $isDir=(isset($perm[0]) && $perm[0]==='d'
     ); $full=remote_join($path, $name); $size=0; if (!$isDir) { $sz=@ftp_size($cid, $full); if ($sz===-1) {
     $isDir=true; } else { if ($sz>=0) $size = $sz; }
     }
     $list[] = ['name'=>$name, 'path'=>$full, 'isDir'=>$isDir, 'size'=>$size];
     }
     if (!empty($list)) return $list;
     }
     }

     $names = @ftp_nlist($cid, $path);
     if (is_array($names) && count($names)) {
     $list = [];
     foreach ($names as $n) {
     $bn = basename($n);
     if ($bn === '' || $bn === '.' || $bn === '..') continue;
     if (!$showHidden && $bn[0] === '.') continue;
     $full = (strpos($n,'/')===0 || $path==='.') ? $n : remote_join($path, $bn);
     $sz = @ftp_size($cid, $full);
     $isDir = ($sz === -1);
     $size = (!$isDir && $sz >= 0) ? $sz : 0;
     $list[] = ['name'=>$bn, 'path'=>$full, 'isDir'=>$isDir, 'size'=>$size];
     }
     if (!empty($list)) return $list;
     }
     }
     logMessage("[FTP] list_dir_smart EMPTY for path={$dirPath}");
     return [];
     }

     /* --------------------------------------------------
     * Gizli dosya göstergesi (cookie) — PRG
     * --------------------------------------------------*/
     $cookieName = 'showHiddenFtp';

     if (
     $_SERVER['REQUEST_METHOD'] === 'POST' &&
     isset($_POST['toggleHiddenForm']) &&
     !isset($_POST['ftpAction']) &&
     !isset($_POST['localAction']) &&
     !isset($_POST['save_json']) &&
     !isset($_POST['upload_project'])
     ) {
     $want = isset($_POST['showHidden']) ? '1' : '0';
     setcookie($cookieName, $want, [
     'expires' => time() + 86400*365,
     'path' => '/',
     'samesite' => 'Lax',
     'httponly' => false,
     ]);
     logMessage("[PREF] showHiddenFtp={$want}");
     safe_redirect($_SERVER['REQUEST_URI'] ?? '/?p=projects');
     }
     $showHiddenFtp = (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === '1');

     /* --------------------------------------------------
     * Route & Proje listesi
     * --------------------------------------------------*/
     $mode = 'home';
     if (isset($_GET['action'])) {
     if ($_GET['action']==='local') $mode = 'local';
     if ($_GET['action']==='ftpmanager') $mode = 'ftp';
     }

     $ignoredDirs = config('ignore_folders_s_text')
     ? array_map('trim', explode(',', (string)config('ignore_folders_s_text')))
     : [];
     $ignoredPatterns = [ '/^_old/i', '/-backup$/i' ];
     $all = @scandir(HAMU_DOCROOT) ?: [];
     $projects = [];
     foreach ($all as $p) {
     if ($p === '.' || $p === '..') continue;
     if ($p[0] === '.' && !$showHiddenFtp) continue;
     if (in_array($p, $ignoredDirs, true)) continue;
     foreach ($ignoredPatterns as $rx) { if (preg_match($rx, $p)) continue 2; }
     $fp = HAMU_DOCROOT . '/' . $p;
     if (!is_dir($fp)) continue;
     $projects[] = $p;
     }
     $selectedProject = isset($_GET['project']) ? trim($_GET['project'], '/') : '';
     if ($selectedProject && !in_array($selectedProject, $projects, true)) {
     $selectedProject = $projects[0] ?? '';
     }

     /* --------------------------------------------------
     * POST işlemleri (redirect gerektirenler) — HEADER ÖNCESİ
     * --------------------------------------------------*/
     $file_path = '/?p=projects&';
     $CSRF_REQUIRED = function() {
     if (!csrf_check_from_post()) {
     setMsgL('csrf_error');
     safe_redirect('?p=projects');
     }
     };

     // JSON kaydet
     if (
     $_SERVER['REQUEST_METHOD']==='POST' && ($mode==='home') &&
     (isset($_POST['save_json']) || (($_POST['action'] ?? '')==='project_ftp_write'))
     ) {
     $CSRF_REQUIRED();
     $pn = trim($_POST['projName']??'');
     if ($pn!=='') {
     $incoming = _ftp_collect_post($_POST);
     $jf = HAMU_DOCROOT.'/'.$pn.'/'.$pn.'.json';
     $ok = _ftp_write_block($jf, $incoming);
     if ($ok) { setMsgL('jsonSaved'); logMessage("[CFG] jsonSaved => $jf"); }
     else { setMsgL('error_json'); logMessage("[CFG] jsonSave FAIL => $jf"); }
     }
     safe_redirect('?p=projects&project='.urlencode($pn));
     }

     /* ---------------- Local File Manager ---------------- */
     if ($mode==='local' && $selectedProject) {
     $p = isset($_GET['d'])? trim($_GET['d'],'/'): '';

     // uploadSingle
     if ($_SERVER['REQUEST_METHOD']==='POST' && (($_POST['localAction']??'')==='uploadSingle')){
     $CSRF_REQUIRED();
     $relFile = trim($_POST['relFile']??'');
     if ($relFile!==''){
     $jf=HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.json';
     $cfg=_ftp_read_block($jf);
     if (empty($cfg)){ setMsgL('error_json');
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }
     $host=$cfg['ftp_host']; $port=(int)$cfg['ftp_port']; $ssl=($cfg['ftp_ssl']==='true');
     $user=$cfg['ftp_user']; $pass=$cfg['ftp_pass_plain']; $fold=trim($cfg['ftp_folder']);
     if (!isset($pass) || trim((string)$pass)===''){ setMsgL('ftp_error_pass_empty');
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }
     if ($warn=ensureFtpExtensions($ssl)){ setMsgL($warn);
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }

     $cid = $ssl ? @ftp_ssl_connect($host,$port) : @ftp_connect($host,$port);
     if (!$cid){ setMsgL('ftp_error'); logMessage("[FTP] CONNECT FAIL host={$host}:{$port} ssl=".($ssl?'1':'0'));
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }
     if (!@ftp_login($cid,$user,$pass)){ @ftp_close($cid); setMsgL('ftp_error_login'); logMessage("[FTP] LOGIN FAIL
     user={$user} host={$host}:{$port}");
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }
     @ftp_pasv($cid,true);
     logMessage("[FTP] LOGIN OK user={$user} host={$host}:{$port} ssl=".($ssl?'1':'0')." base={$fold}");

     $localFile = HAMU_DOCROOT.'/'.$selectedProject.'/'.ltrim($relFile,'/');
     if (!is_file($localFile)){ setMsgL('file_not_found'); @ftp_close($cid);
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); }

     $remoteBaseRel = ftp_base_relative($cid, $fold);
     $relDir = trim(dirname($relFile),'/');
     if ($remoteBaseRel !== '' && $relDir !== '') $remoteRoot = $remoteBaseRel.'/'.$relDir;
     elseif ($remoteBaseRel !== '') $remoteRoot = $remoteBaseRel;
     else $remoteRoot = $relDir;

     $remoteFile = ($remoteRoot===''? basename($relFile) : $remoteRoot.'/'.basename($relFile));
     if ($remoteRoot!=='') ftp_mkdirs($cid, $remoteRoot);

     $ok = @ftp_put($cid, $remoteFile, $localFile, FTP_BINARY);
     @ftp_close($cid);

     if ($ok){ setMsgL('upload_ok_single'); logMessage("[FTP] uploadSingle OK {$relFile} -> {$remoteFile}"); }
     else { setMsgL('upload_fail_single'); logMessage("[FTP] uploadSingle FAIL {$relFile} -> {$remoteFile}"); }
     }
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p));
     }

     // uploadFolder
     if ($_SERVER['REQUEST_METHOD']==='POST' && (($_POST['localAction']??'')==='uploadFolder')){
     $CSRF_REQUIRED();
     $relDir = trim($_POST['relDir']??'');
     if ($relDir!==''){
     $jf=HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.json';
     $cfg=_ftp_read_block($jf);
     if (empty($cfg)){ setMsgL('error_json');
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }
     $host=$cfg['ftp_host']; $port=(int)$cfg['ftp_port']; $ssl=($cfg['ftp_ssl']==='true');
     $user=$cfg['ftp_user']; $pass=$cfg['ftp_pass_plain']; $fold=trim($cfg['ftp_folder']);
     if (!isset($pass) || trim((string)$pass)===''){ setMsgL('ftp_error_pass_empty');
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }
     if ($warn=ensureFtpExtensions($ssl)){ setMsgL($warn);
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }

     $timeout = 8;
     $cid = $ssl ? @ftp_ssl_connect($host,$port,$timeout) : @ftp_connect($host,$port,$timeout);
     if (!$cid){ setMsgL($ssl ? 'ftp_error_ssl_connect' : 'ftp_error_connect_host'); logMessage("[FTP] CONNECT FAIL
     host={$host}:{$port} ssl=".($ssl?'1':'0'));
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }
     @ftp_set_option($cid, FTP_TIMEOUT_SEC, $timeout);

     if (!@ftp_login($cid,$user,$pass)){ @ftp_close($cid); setMsgL('ftp_error_login'); logMessage("[FTP] LOGIN FAIL
     user={$user} host={$host}:{$port}");
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }
     if (!@ftp_pasv($cid,true)){ @ftp_close($cid); setMsgL('ftp_error_pasv'); logMessage("[FTP] PASV FAIL
     host={$host}:{$port}");
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }
     logMessage("[FTP] LOGIN OK user={$user} host={$host}:{$port} ssl=".($ssl?'1':'0')." base={$fold}");

     $localDir = HAMU_DOCROOT.'/'.$selectedProject.'/'.ltrim($relDir,'/');
     if (!is_dir($localDir)){ setMsgL('file_not_found'); @ftp_close($cid);
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return; }

     $remoteBaseRel = ftp_base_relative($cid, $fold);
     $remoteDir = ($remoteBaseRel===''? $relDir : ($relDir===''? $remoteBaseRel : $remoteBaseRel.'/'.$relDir));
     ftp_upload_directory($cid, $localDir, $remoteDir, true);
     @ftp_close($cid);
     setMsgL('upload_ok_single'); logMessage("[FTP] uploadFolder OK {$relDir} -> {$remoteDir}");
     }
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return;
     }

     // Yerel FS new/delete/rename
     if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['localAction'])){
     $CSRF_REQUIRED();
     $base = HAMU_DOCROOT.'/'.$selectedProject;
     $cur = $p? $base.'/'.$p : $base;

     switch($_POST['localAction']){
     case 'newFolder':
     $fn= trim($_POST['folderName']??'');
     if ($fn!=='' && !preg_match('/^(?:\.{1,2})$/',$fn)) {
     $path = $cur.'/'.$fn;
     $ok = @mkdir($path,0777,true);
     logMessage($ok ? "[LOCAL] MKDIR {$path}" : "[LOCAL] MKDIR FAIL {$path}");
     }
     break;

     case 'newFile':
     $fn= trim($_POST['fileName']??'');
     if ($fn!=='' && !preg_match('/^(?:\.{1,2})$/',$fn)) {
     $path = $cur.'/'.$fn;
     $ok = @touch($path);
     logMessage($ok ? "[LOCAL] NEWFILE {$path}" : "[LOCAL] NEWFILE FAIL {$path}");
     }
     break;

     case 'delete':
     $tg= $_POST['target']??'';
     if ($tg!==''){
     $path = $cur.'/'.$tg;
     logMessage("[LOCAL] DELETE start {$path}");
     if (is_dir($path)){
     $rii=new RecursiveIteratorIterator(
     new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
     RecursiveIteratorIterator::CHILD_FIRST
     );
     foreach($rii as $fi){ $fi->isDir()? @rmdir($fi->getPathname()): @unlink($fi->getPathname()); }
     $ok = @rmdir($path);
     } else {
     $ok = @unlink($path);
     }
     logMessage($ok ? "[LOCAL] DELETE done {$path}" : "[LOCAL] DELETE FAIL {$path}");
     }
     break;

     case 'rename':
     $oldN= $_POST['target']??''; $newN= $_POST['newName']??'';
     if ($oldN && $newN) {
     $o = $cur.'/'.$oldN; $n = $cur.'/'.$newN;
     $ok = @rename($o,$n);
     logMessage($ok ? "[LOCAL] RENAME {$o} -> {$n}" : "[LOCAL] RENAME FAIL {$o} -> {$n}");
     }
     break;
     }
     safe_redirect($file_path.'action=local&project='.urlencode($selectedProject).'&d='.urlencode($p)); return;
     }
     }

     /* ---------------- Toplu Upload (ana karttan) ---------------- */
     if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($mode==='home') && isset($_POST['upload_project'])) {
     $CSRF_REQUIRED();
     $pn = trim($_POST['projName'] ?? '');
     $jf = HAMU_DOCROOT . '/' . $pn . '/' . $pn . '.json';
     if (!is_file($jf)) { setMsgL('no_json'); safe_redirect('?p=projects&project=' . urlencode($pn)); return; }
     $arr = @json_decode(@file_get_contents($jf), true);
     if (!is_array($arr)) { setMsgL('error_json'); safe_redirect('?p=projects&project=' . urlencode($pn)); return; }

     $req = ['ftp_host','ftp_port','ftp_ssl','ftp_user','ftp_pass','ftp_folder','host_url','download_folder'];
     foreach ($req as $rk) { if (empty($arr[$rk]) && $arr[$rk] !== '0') { setMsgL('missing_fields');
     safe_redirect('?p=projects&project=' . urlencode($pn)); return; } }

     $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === '1';
     $host = $arr['ftp_host']; $port = (int)$arr['ftp_port']; $ssl = ($arr['ftp_ssl'] === 'true');
     $user = $arr['ftp_user']; $pass = decryptData($arr['ftp_pass']); $fold = $arr['ftp_folder'];
     if (!isset($pass) || trim((string)$pass)==='') { setMsgL('ftp_error_pass_empty');
     safe_redirect('?p=projects&project=' .
     urlencode($pn)); return; }

     $extWarn = ensureFtpExtensions($ssl);
     if ($extWarn) { setMsgL($extWarn); safe_redirect('?p=projects&project='.urlencode($pn)); return; }

     $timeout = 8;
     $cid = $ssl ? @ftp_ssl_connect($host, $port, $timeout) : @ftp_connect($host, $port, $timeout);
     if (!$cid) { setMsgL($ssl ? 'ftp_error_ssl_connect' : 'ftp_error_connect_host'); logMessage("[FTP] CONNECT FAIL
     host={$host}:{$port} ssl=".($ssl?'1':'0'));
     safe_redirect('?p=projects&project=' . urlencode($pn)); return; }
     @ftp_set_option($cid, FTP_TIMEOUT_SEC, $timeout);

     if (!@ftp_login($cid, $user, $pass)) { @ftp_close($cid); setMsgL('ftp_error_login'); logMessage("[FTP] LOGIN FAIL
     user={$user} host={$host}:{$port}"); safe_redirect('?p=projects&project=' . urlencode($pn)); return; }
     if (!@ftp_pasv($cid, true)) { @ftp_close($cid); setMsgL('ftp_error_pasv'); logMessage("[FTP] PASV FAIL
     host={$host}:{$port}");
     safe_redirect('?p=projects&project=' . urlencode($pn)); return; }
     logMessage("[FTP] LOGIN OK user={$user} host={$host}:{$port} ssl=".($ssl?'1':'0')." base={$fold}");

     $localDir = HAMU_DOCROOT . '/' . $pn;
     $resolvedBase = ftp_base_relative($cid, $fold);

     $uploadRec = function($cid,string $localDir,string $remoteDir,bool $overwrite) use (&$uploadRec) {
     $remoteDir = trim(str_replace('\\','/',$remoteDir),'/');
     if ($remoteDir !== '') ftp_mkdirs($cid, $remoteDir);

     $sc= @scandir($localDir)?:[];
     foreach($sc as $s){
     if ($s==='.'||$s==='..') continue;
     if ($s[0]==='.') continue;
     $lp=$localDir.'/'.$s;
     $rp= ($remoteDir===''? $s : $remoteDir.'/'.$s);

     if (is_dir($lp)){
     if ($rp !== '') ftp_mkdirs($cid, $rp);
     $uploadRec($cid,$lp,$rp,$overwrite);
     } else {
     if (!$overwrite){
     $sz=@ftp_size($cid,$rp);
     if ($sz!==-1) continue;
     }
     @ftp_put($cid,$rp,$lp,FTP_BINARY);
     }
     }
     };
     $uploadRec($cid, $localDir, $resolvedBase, $overwrite);
     @ftp_close($cid);

     setMsgL('upload_ok_single');
     logMessage("[FTP] Upload project={$pn}, overwrite=" . ($overwrite ? 'yes' : 'no'));
     safe_redirect('?p=projects&project=' . urlencode($pn)); return;
     }

     /* ---------------- FTP Manager (uzak) ---------------- */
     if ($mode==='ftp' && $selectedProject && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ftpAction'])) {
     $CSRF_REQUIRED();
     $jf=HAMU_DOCROOT.'/'.$selectedProject.'/'.$selectedProject.'.json';
     if (!is_file($jf)){ setMsgL('no_json'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); return;
     }
     $cfg=_ftp_read_block($jf);
     if (empty($cfg)){ setMsgL('error_json'); safe_redirect('?p=projects&project='.urlencode($selectedProject)); return;
     }

     $host=$cfg['ftp_host']; $port=(int)$cfg['ftp_port']; $ssl= ($cfg['ftp_ssl']==='true');
     $user=$cfg['ftp_user']; $pass= $cfg['ftp_pass_plain']; $fold= trim($cfg['ftp_folder']);
     if (!isset($pass) || trim((string)$pass)===''){ setMsgL('ftp_error_pass_empty');
     safe_redirect('?p=projects&project='.urlencode($selectedProject)); return; }
     if ($warn=ensureFtpExtensions($ssl)){ setMsgL($warn);
     safe_redirect('?p=projects&project='.urlencode($selectedProject));
     return; }

     $timeout = 8;
     $cid = $ssl ? @ftp_ssl_connect($host,$port,$timeout) : @ftp_connect($host,$port,$timeout);
     if (!$cid){ setMsgL($ssl ? 'ftp_error_ssl_connect' : 'ftp_error_connect_host'); logMessage("[FTP] CONNECT FAIL
     host={$host}:{$port} ssl=".($ssl?'1':'0'));
     safe_redirect('?p=projects&project='.urlencode($selectedProject)); return; }
     @ftp_set_option($cid, FTP_TIMEOUT_SEC, $timeout);

     if (!@ftp_login($cid,$user,$pass)){ @ftp_close($cid); setMsgL('ftp_error_login'); logMessage("[FTP] LOGIN FAIL
     user={$user} host={$host}:{$port}"); safe_redirect('?p=projects&project='.urlencode($selectedProject)); return; }
     if (!@ftp_pasv($cid,true)){ @ftp_close($cid); setMsgL('ftp_error_pasv'); logMessage("[FTP] PASV FAIL
     host={$host}:{$port}");
     safe_redirect('?p=projects&project='.urlencode($selectedProject)); return; }
     logMessage("[FTP] LOGIN OK user={$user} host={$host}:{$port} ssl=".($ssl?'1':'0')." base={$fold}");

     $p = isset($_GET['d'])? trim($_GET['d'],'/'): '';
     $baseRel = ftp_base_relative($cid, $fold);
     $curDir = ($baseRel===''? $p : ($p===''? $baseRel : $baseRel.'/'.$p));

     switch($_POST['ftpAction']){
     case 'newFolder':
     $fn= trim($_POST['folderName']??'');
     if ($fn!=='' && !preg_match('/^(?:\.{1,2})$/',$fn)){
     $full = remote_join($curDir, $fn);
     $ok = @ftp_mkdir($cid, $full);
     logMessage($ok ? "[FTP] MKDIR {$full}" : "[FTP] MKDIR FAIL {$full}");
     }
     break;


     case 'newFile':
     $fn= trim($_POST['fileName']??'');
     if ($fn!=='' && !preg_match('/^(?:\.{1,2})$/',$fn)){
     $full = remote_join($curDir, $fn);
     $tmpf = tempnam(sys_get_temp_dir(),'newf');
     $ok = @ftp_put($cid, $full, $tmpf, FTP_BINARY);
     @unlink($tmpf);
     logMessage($ok ? "[FTP] NEWFILE {$full}" : "[FTP] NEWFILE FAIL {$full}");
     }
     break;

     case 'delete':
     $tg= $_POST['target']??'';
     if ($tg!==''){
     $full = remote_join($curDir, $tg);
     logMessage("[FTP] DELETE start {$full}");
     if (!@ftp_delete($cid,$full)){ // klasör olabilir
     $items=@ftp_nlist($cid,$full)?:[];
     foreach($items as $it){
     $bn=basename($it);
     if ($bn==='.'||$bn==='..') continue;
     $child=(strpos($it,'/')===0)?$it:remote_join($full,$bn);
     $stack=[$child];
     while($stack){
     $node=array_pop($stack);
     if (!@ftp_delete($cid,$node)){
     $sub=@ftp_nlist($cid,$node)?:[];
     if (empty($sub)){ @ftp_rmdir($cid,$node); }
     else{
     foreach($sub as $s){
     $bb=basename($s);
     if ($bb==='.'||$bb==='..') continue;
     $stack[]=(strpos($s,'/')===0)?$s:remote_join($node,$bb);
     }
     }
     }
     }
     }
     }
     @ftp_rmdir($cid,$full);
     logMessage("[FTP] DELETE done {$full}");
     }
     break;

     case 'rename':
     $oldN= $_POST['target']??''; $newN= $_POST['newName']??'';
     if ($oldN && $newN){
     $o= remote_join($curDir, $oldN);
     $n= remote_join($curDir, $newN);
     $ok = @ftp_rename($cid,$o,$n);
     logMessage($ok ? "[FTP] RENAME {$o} -> {$n}" : "[FTP] RENAME FAIL {$o} -> {$n}");
     }
     break;

     case 'download':
     $tg= $_POST['target']??'';
     if ($tg!==''){
     $full = remote_join($curDir, $tg);
     $downfolder=rtrim($cfg['download_folder']??'','/');
     $dlDir=HAMU_DOCROOT.'/'.$selectedProject.'/'.$downfolder;
     if (!is_dir($dlDir)){ @mkdir($dlDir,0777,true); }
     $lf=$dlDir.'/'.basename($tg);
     if (@ftp_get($cid, $lf, $full, FTP_BINARY)) {
     setMsgL('downloaded_to_folder', $downfolder);
     logMessage("[FTP] DOWNLOAD OK remote={$full} -> local={$lf}");
     } else {
     setMsgL('download_failed', $downfolder);
     logMessage("[FTP] DOWNLOAD FAIL remote={$full} -> local={$lf}");
     }
     }
     break;
     }
     @ftp_close($cid);
     safe_redirect($file_path.'action=ftpmanager&project='.urlencode($selectedProject).'&d='.urlencode($p));
     }

     /* ---------------- Ortak mesaj + CSRF ---------------- */
     $msgGlobal = getMsg__l();
     $csrf = csrf_token();

     /* ---------------- Sıralama kalıcılığı (COOKIE) ---------------- */
     $SORT_ALLOWED = ['name_asc','name_desc','mtime_desc','mtime_asc','ctime_desc','ctime_asc'];
     $sortCookieOrDefault = $_GET['sort'] ?? ($_COOKIE['sortProjects'] ?? 'name_asc');
     if (!in_array($sortCookieOrDefault, $SORT_ALLOWED, true)) {
     $sortCookieOrDefault = 'name_asc';
     }
     setcookie('sortProjects', $sortCookieOrDefault, [
     'expires' => time() + 365*24*60*60,
     'path' => '/',
     'secure' => isset($_SERVER['HTTPS']),
     'httponly' => false,
     'samesite' => 'Lax'
     ]);

     /* ---------------- Görsel etiketler ---------------- */
     $sortOptions = [
     'name_asc' => __l('name_asc'),
     'name_desc' => __l('name_desc'),
     'ctime_desc' => __l('ctime_desc'),
     'ctime_asc' => __l('ctime_asc'),
     'mtime_desc' => __l('mtime_desc'),
     'mtime_asc' => __l('mtime_asc'),
     ];

     /* ======================= FİLTRE/SIRALAMA/PAGING ======================= */
     $q = trim($_GET['q'] ?? '');
     $sort = $_GET['sort'] ?? $sortCookieOrDefault;
     $favOnly = !empty($_GET['fav']);
     $showHidden = getShowHiddenProjects();
     $hasIndex = !empty($_GET['hasindex']);
     $per = max(1, (int)($_GET['per'] ?? 24));
     $page = max(1, (int)($_GET['page'] ?? 1));
     $currentSortLabel = isset($sortOptions[$sort]) ? $sortOptions[$sort] : '';

     $metaFile = HAMU_CACHE . '/projects_meta.json';
     $ignored = config('ignore_folders_s')
     ? array_map('trim', explode(',', (string)config('ignore_folders_s_text')))
     : [];

     $norm = static function(string $s): string {
     $s = mb_strtolower($s, 'UTF-8');
     $s = str_replace(['İ','I'], ['i','ı'], $s);
     return $s;
     };

     $prepared = [];
     foreach ($folders as $dir) {
     $folderName = basename($dir);
     if (in_array($folderName, $ignored, true)) continue;

     $isHidden = ($folderName !== '' && $folderName[0] === '.');
     if ($isHidden && !$showHidden) continue;

     $indexFile = findIndexFile($dir);
     if ($hasIndex && $indexFile === null) continue;

     $items = [];
     if (is_file($metaFile)) {
     $meta = json_decode(@file_get_contents($metaFile), true);
     if (is_array($meta)) { $items = $meta['items'] ?? []; }
     }
     $m = $items[$folderName] ?? [];
     $isFav = !empty($m['favorite']) || !empty(($m['flags']['favorite'] ?? 0));

     if ($favOnly && !$isFav) continue;

     $badgeText = trim($m['badge_text'] ?? '');
     if ($badgeText === '' && !empty($m['badge_name'])) {
     $badgeText = trim((string)$m['badge_name']);
     }
     if ($badgeText === '') {
     $badgeText = __l('preview');
     }
     $badgeColor = trim($m['badge_color'] ?? '');

     if ($q !== '') {
     $needle = $norm($q);
     $hay = $norm($folderName);
     if (!empty($m['tags'])) {
     $hay .= ' ' . $norm(is_array($m['tags']) ? implode(' ', $m['tags']) : (string)$m['tags']);
     }
     if ($badgeText !== '') {
     $hay .= ' ' . $norm($badgeText);
     }
     if (mb_strpos($hay, $needle) === false) continue;
     }

     $prepared[] = [
     'dir' => $dir,
     'name' => $folderName,
     'isHidden' => $isHidden,
     'isFav' => $isFav,
     'indexFile' => $indexFile,
     'mtime' => @filemtime($dir) ?: 0,
     'ctime' => @filectime($dir) ?: 0,
     'meta' => $m,
     'badge_text'=> $badgeText,
     'badge_color'=>$badgeColor,
     ];
     }

     usort($prepared, function($a, $b) use ($sort) {
     switch ($sort) {
     case 'name_desc': return strnatcasecmp($b['name'], $a['name']);
     case 'mtime_desc': return ($b['mtime'] <=> $a['mtime']);
          case 'mtime_asc': return ($a['mtime'] <=> $b['mtime']);
               case 'ctime_desc': return ($b['ctime'] <=> $a['ctime']);
                    case 'ctime_asc': return ($a['ctime'] <=> $b['ctime']);
                         case 'name_asc':
                         default: return strnatcasecmp($a['name'], $b['name']);
                         }
                         });

                         $total = count($prepared);
                         $pages = max(1, (int)ceil($total / $per));
                         $page = min($page, $pages);
                         $offset = ($page - 1) * $per;
                         $rows = array_slice($prepared, $offset, $per);

                         function build_query(array $overrides = []): string {
                         $keep = $_GET ?? [];
                         foreach ($overrides as $k => $v) {
                         if ($v === null) unset($keep[$k]);
                         else $keep[$k] = $v;
                         }
                         return http_build_query($keep);
                         }
                         function url_self(): string {
                         return $_SERVER['REQUEST_URI'] ?? '/?p=projects';
                         }

                         /* Flash msg helpers */
                         function setMsgL(string $langKey, string $extraText = ''): void {
                         $_SESSION['msg_key'] = $langKey;
                         $_SESSION['msg_extra'] = $extraText;
                         }
                         function getMsg__l(): string {
                         $k = $_SESSION['msg_key'] ?? '';
                         $extra = $_SESSION['msg_extra'] ?? '';
                         unset($_SESSION['msg_key'], $_SESSION['msg_extra']);
                         if ($k === '') return '';
                         $text = __l($k);
                         return $extra !== '' ? $text . ' [' . $extra . ']' : $text;
                         }