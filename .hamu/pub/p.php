<?php
/**
 * HAMU DevPanel - Public Page & Asset Proxy
 * Konum: /.hamu/pub/p.php
 * Kullanım:
 *   - Asset:  /index.php?a=assets/css/custom.css   (veya js/... images/... fonts/...)
 *   - Sayfa:  /index.php?p=reader - /?p=reader
 *   - Modül:  /index.php?p=module&md=<slug>   (örn: md=build-sqlite) - /?p=module&md=<slug>
 */

$rootdir = dirname(__DIR__); // /.hamu

/* ============================================================
 * 1) EARLY ASSET PROXY (NO SESSION / NO COOKIES)
 * ============================================================ */
if (isset($_GET['a'])) {
  $a   = (string)$_GET['a'];
  $ver = isset($_GET['v']) ? preg_replace('/[^A-Za-z0-9._-]/', '', (string)$_GET['v']) : null; // opsiyonel versiyon paramı

  // Asset isteklerinde session istemiyoruz
  if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) @session_write_close();
  if (function_exists('session_cache_limiter')) @session_cache_limiter('');

  // Kök ve uzantı doğrulama
  if (!preg_match('#^assets/[A-Za-z0-9._/\-]+$#', $a)) { http_response_code(404); exit; }
  $ext    = strtolower(pathinfo($a, PATHINFO_EXTENSION));
  $okExts = ['css','js','map','png','jpg','jpeg','gif','webp','svg','ico','woff','woff2','ttf','otf','webmanifest','json'];
  if (!in_array($ext, $okExts, true)) { http_response_code(404); exit; }

  /**
   * v => dosya adına enjekte etme kuralı
   * Ör:
   *   a=assets/css/hamu.main.min.css & v=1.0.0  => css/hamu.main.v.1.0.0.min.css
   *   a=assets/js/hamu.main.min.js   & v=1.0.0  => js/hamu.main.v.1.0.0.min.js
   *   a=assets/css/app.css           & v=2      => css/app.v.2.css
   */
  $aResolved = $a; // varsayılan: gelen yol
  if ($ver !== null && $ver !== '' && ($ext === 'css' || $ext === 'js')) {
      $dir    = str_replace('\\', '/', dirname($a));
      $dir    = ($dir === '.' ? '' : $dir);
      $base   = basename($a);
      $nameNoExt = substr($base, 0, - (strlen($ext) + 1)); // .ext çıkartılmış ad

      if (preg_match('/\.min$/', $nameNoExt)) {
          // ...min  →  ...v.<ver>.min
          $nameVer = preg_replace('/\.min$/', '.v.' . $ver . '.min', $nameNoExt);
      } else {
          // ... → ...v.<ver>
          $nameVer = $nameNoExt . '.v.' . $ver;
      }
      $aVersioned = ($dir ? ($dir . '/') : '') . $nameVer . '.' . $ext;

      // Önce versiyonlu dosyayı dene; yoksa orijinale düş
      $candidate = realpath($rootdir . DIRECTORY_SEPARATOR . $aVersioned);
      if ($candidate !== false && is_file($candidate)) {
          $aResolved = $aVersioned;
      } else {
          // Versiyonlu bulunamadıysa, orijinal dosya adıyla devam (fallback)
          $aResolved = $a;
      }
  }

  // Güvenli gerçek yol
  $hamuBase = realpath($rootdir . '/assets');
  $path     = realpath($rootdir . DIRECTORY_SEPARATOR . $aResolved);
  if ($hamuBase === false || $path === false ||
      strpos($path, $hamuBase . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404); exit;
  }

  // MIME
  $types = [
    'css'=>'text/css', 'js'=>'application/javascript', 'map'=>'application/json',
    'png'=>'image/png', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'gif'=>'image/gif',
    'webp'=>'image/webp', 'svg'=>'image/svg+xml', 'ico'=>'image/x-icon',
    'woff'=>'font/woff', 'woff2'=>'font/woff2', 'ttf'=>'font/ttf', 'otf'=>'font/otf',
    'webmanifest'=>'application/manifest+json', 'json'=>'application/json',
  ];

  header('X-Content-Type-Options: nosniff');
  header('Content-Disposition: inline');
  header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));

  // Cache: v varsa 1 yıl + immutable, yoksa 1 hafta
  $mtime = filemtime($path);
  $size  = filesize($path);

  // ETag'i aResolved + dosya meta üzerinden üret (v etkisini kapsasın)
  $etagBase = sprintf('%x-%x', $size, $mtime);
  // aResolved'i da dahil ederek cache key ayrışmasını güçlendir
  $etag     = 'W/"' . $etagBase . '-' . substr(sha1($aResolved), 0, 12) . '"';

  header('ETag: ' . $etag);
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

  if ($ver !== null && $ver !== '') {
    header('Cache-Control: public, max-age=31536000, immutable');
  } else {
    header('Cache-Control: public, max-age=604800');
  }

  // Cookie & legacy başlıkları temizle
  @header_remove('Set-Cookie');
  @header_remove('Pragma');
  @header_remove('Expires');

  // 304
  $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
  $ifModSince  = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
  if (($ifNoneMatch && trim($ifNoneMatch) === $etag) || ($ifModSince && $ifModSince >= $mtime)) { http_response_code(304); exit; }

  // SW üst scope
  if ($ext === 'js' && preg_match('#(^|/)sw\.js$#', $aResolved)) header('Service-Worker-Allowed: /');

  readfile($path);
  exit;
}

/* ============================================================
 * 1a) MODÜL HARİTASI (sadece modüller için slug üretir)
 * ============================================================ */
function p_module_name($filename) {
  // .php uzantısını at
  $name = preg_replace('/\.php$/i', '', (string)$filename);
  // TR karakter sadeleştir
  $tr = [
    'ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u',
    'Ç'=>'C','Ğ'=>'G','İ'=>'I','Ö'=>'O','Ş'=>'S','Ü'=>'U'
  ];
  $name = strtr($name, $tr);
  // _ → -
  $name = str_replace('_', '-', $name);
  // küçük harf
  return strtolower($name);
}

function moduleRegistry($rootdir) {
  $map = [];
  $dir = rtrim($rootdir, '/').'/modules';
  if (!is_dir($dir)) return $map;

  foreach (glob($dir.'/*.php') as $f) {
    $fileName = basename($f);
    if ($fileName === '' || $fileName[0] === '.') continue; // gizli dosya atla
    $slug = p_module_name($fileName);                        // sadece modül slug'ı
    $map[$slug] = $dir . '/' . $fileName;
  }
  return $map;
}

$MODULES = moduleRegistry($rootdir);  // slug => fullpath

/* ============================================================
 * 2) SADECE p VARSA SAYFA ROUTER’A GİR
 * ============================================================ */
$hasP = array_key_exists('p', $_GET);
$pVal = $hasP ? trim((string)$_GET['p']) : '';

// p yoksa veya boşsa: hiçbir şey yapma → root index.php akışı devam etsin
if (!$hasP || $pVal === '') {
  return;
}

// (Gerekirse) dil paramı – sadece p varken işleyelim
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!empty($_GET['lang']) || !empty($_POST['lang'])) {
  $lang = (string)($_GET['lang'] ?? $_POST['lang']);
  $lang = preg_replace('/[^A-Za-z0-9_-]/', '', $lang);
  if ($lang !== '') {
    $_SESSION['hamu_lang'] = $lang;
    setcookie('hamu_lang', $lang, time()+31536000, '/', '', false, true);
  }
}

/* ============================================================
 * 3) PAGE ROUTER
 * ============================================================ */
$pages = [
  'reader'            => $rootdir . '/reader.php',
  'file-manager'      => $rootdir . '/filemanager.php',
  'projects'          => $rootdir . '/projects.php',
  'fmi'               => $rootdir . '/include/filemanager/tinyfilemanager.php',
  'test'              => $rootdir . '/modules/file.php', // test için kullanılıyor
];

// p= ile servis edilen asset-like öğeler
$assetLikePages = [
  'logo' => [
    'type'        => 'file',
    'path'        => $rootdir . '/assets/images/logo.svg',
    'contentType' => 'image/svg+xml',
    'cacheMaxAge' => 604800,
  ],
  'manifest' => [
    'type'        => 'file',
    'path'        => $rootdir . '/assets/images/icons/manifest.webmanifest',
    'contentType' => 'application/manifest+json',
    'cacheMaxAge' => 604800,
  ],
  'favicon' => [
    'type'        => 'file',
    'path'        => $rootdir . '/assets/images/icons/favicon.ico',
    'contentType' => 'image/x-icon',
    'cacheMaxAge' => 604800,
  ],
  'fmi_lang' => [
    'type'        => 'file',
    'path'        => $rootdir . '/include/filemanager/translation.json',
    'contentType' => 'application/json',
    'cacheMaxAge' => 604800,
  ],
];

// 3.a) asset-like ise hemen servis et
if (isset($assetLikePages[$pVal])) {
  $cfg  = $assetLikePages[$pVal];
  $mime = $cfg['contentType'] ?? 'application/octet-stream';

  header('X-Content-Type-Options: nosniff');
  header('Content-Disposition: inline');
  // manifest’i istersen prettify etmek için: /index.php?p=manifest&view=1
  if ($mime === 'application/manifest+json' && isset($_GET['view'])) {
    header('Content-Type: application/json; charset=UTF-8');
  } else {
    header('Content-Type: ' . $mime . '; charset=UTF-8');
  }

  if (($cfg['type'] ?? '') === 'file') {
    $path = $cfg['path'] ?? '';
    if (!is_file($path)) { http_response_code(404); exit; }

    $mtime = filemtime($path);
    $size  = filesize($path);
    $etag  = 'W/"' . sprintf('%x-%x', $size, $mtime) . '"';
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    $maxAge = (int)($cfg['cacheMaxAge'] ?? 0);
    if ($maxAge > 0) header('Cache-Control: public, max-age=' . $maxAge);

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
    $ifModSince  = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
    if (($ifNoneMatch && trim($ifNoneMatch) === $etag) || ($ifModSince && $ifModSince >= $mtime)) { http_response_code(304); exit; }

    readfile($path);
    exit;
  }

  http_response_code(500);
  echo 'Invalid asset-like config';
  exit;
}

/* 3.b) MODÜL ROUTER: ?p=module&md=<slug> */
if ($pVal === 'modules') {
  $md = isset($_GET['md']) ? strtolower(trim((string)$_GET['md'])) : '';

  if ($md !== '' && isset($MODULES[$md])) {
    require $MODULES[$md];
    exit;
  }

  // Fallback: md bulunamazsa dosya adaylarını dene
  $dir = rtrim($rootdir, '/').'/modules';
  if ($md !== '' && preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $md)) {
    $candidates = [
      $dir . '/' . $md . '.php',                         // test-email.php
      $dir . '/' . str_replace('-', '_', $md) . '.php',  // test_email.php
    ];
    foreach ($candidates as $cand) {
      if (is_file($cand)) {
        require $cand;
        exit;
      }
    }
  }

  // Tanı koymaya yardımcı olsun
  @error_log("HAMU module router: '$md' not found. Available slugs: " . implode(',', array_keys($MODULES)));
  http_response_code(404);
  echo 'Module not found';
  exit;
}

/* 3.c) GERİ UYUMLULUK: p=<slug> bir modül ise 301 ile yeni yapıya taşı */
$modKey = strtolower($pVal);
if (isset($MODULES[$modKey])) {
  $new = 'index.php?p=modules&md=' . rawurlencode($modKey);
  if (!headers_sent()) {
    header('Location: ' . $new, true, 301);
  }
  echo '<a href="'.$new.'">Moved</a>';
  exit;
}

/* 3.d) Normal sayfa route */
if (!isset($pages[$pVal])) {
  http_response_code(404);
  echo 'Page not found';
  exit;
}
require $pages[$pVal];
exit;