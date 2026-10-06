<?php
/**
 * HAMU DevPanel - Log & Markdown Viewer
 * Location: /.hamu/reader.php
 * [TR] Markdown (.md) ve log (.log) dosyalarını görüntülemek için kullanılır.
 *      Markdown dosyalarını HTML'e dönüştürür, log dosyalarını ise ters kronolojik sırada (en yeni en üstte) gösterir.
 *      URL parametreleri ile esnek kontrol sağlar.
 * [EN] Used to display Markdown (.md) and log (.log) files.
 *      It converts Markdown files to HTML and displays log files in reverse chronological order (newest first).
 *      Provides flexible control via URL parameters.
 *
 * --- KULLANIM / USAGE ---
 * API Endpoint: /?p=reader
 *
 * Parametreler / Parameters:
 * ?md=<dosya_yolu>
 *   [TR] Belirtilen Markdown dosyasını render eder. Arama sırası: /.hamu/readme, / (kök dizin).
 *   [EN] Renders the specified Markdown file. Search order: /.hamu/readme, / (root).
 *   Örnek / Example: /?p=reader&md=docs/INSTALL.md
 *
 * ?log=<dosya_yolu>
 *   [TR] Belirtilen log dosyasını gösterir. Arama sırası: /.hamu/logs, /logs.
 *   [EN] Displays the specified log file. Search order: /.hamu/logs, /logs.
 *   Örnek / Example: /?p=reader&log=db_terminal.log&lines=100&hl=error,warn
 *
 * &raw=1
 *   [TR] Dosyayı tarayıcıda göstermek yerine indirme (download) başlatır.
 *   [EN] Forces a download of the file instead of displaying it in the browser.
 */

/* ------------------------------ Tanımlar ------------------------------ */
// [TR] Sayfa yapılandırma değişkenleri
// [EN] Page configuration variables
$page_title = "reader";       // [TR] Sayfa başlığı için dil anahtarı. [EN] Language key for the page title.
$body_class = "";             // [TR] <body> etiketine eklenecek özel CSS sınıfı. [EN] Custom CSS class for the <body> tag.
$include_db = 0;              // [TR] Veritabanı bağlantısı gerekli mi? (1=Evet, 0=Hayır). [EN] Is a database connection required? (1=Yes, 0=No).
$side_bar   = 0;              // [TR] Sol menü (sidebar) gösterilsin mi? (1=Evet, 0=Hayır). [EN] Should the left sidebar be displayed? (1=Yes, 0=No).
$menu_type  = 1;              // [TR] Menü davranışını kontrol eder. 0: Mobil menü butonu gösterilir. 1: Her zaman görünür menü. [EN] Controls menu behavior. 0: Show mobile menu button. 1: Always visible menu.


/* ----------------------------------------------------------
 * 1) BOOTSTRAP (config/func/lang/auth)
 * -------------------------------------------------------- */
@require_once __DIR__ . '/auth.php';


if (!function_exists('__l')) {
  function __l($k){ return $k; }
}
if (!function_exists('get_footer')) {
  function get_footer(){ return ""; }
}
if (!isset($side)) { $side = ""; }

/* DOCROOT yoksa, .hamu/.. üst dizini docroot kabul et */
if (!isset($DOCROOT) || !is_dir($DOCROOT)) {
  $DOCROOT = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

/* ----------------------------------------------------------
 * 2) SABİTLER / AYARLAR
 * -------------------------------------------------------- */
$DEFAULT_LOG_REL        = HAMU_LOG .'/db_terminal.log';
$DEFAULT_LOG_MAX_LINES  = 5000;
$DEFAULT_LOG_ORDER      = 'desc';
$DEFAULT_MD_CANDIDATES  = [
  'README.md','readme.md','Readme.md','README.MD',
  'docs/README.md','docs/readme.md'
];

$LOG_ROOTS = [
  HAMU_LOG,
  HAMU_DOCROOT .'/logs',
];
$MD_ROOTS = [
  HAMU_MD,
  HAMU_DOCROOT,
];

/* ----------------------------------------------------------
 * 3) YARDIMCILAR
 * -------------------------------------------------------- */
function _h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function reader_url(array $params): string {
  $path = $_SERVER['PHP_SELF'] ?? ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
  $params = array_merge(['p' => 'reader'], $params);
  return $path . '?' . http_build_query($params);
}

function safe_resolve_under_roots(string $DOCROOT, array $roots, string $relOrFile): array {
  $in = trim((string)$relOrFile);
  $in = str_replace(['..','~'], '', $in);
  $in = ltrim($in, "/\\");
  if ($in === '' || !in_array(strtolower(pathinfo($in, PATHINFO_EXTENSION)), ['md','log'], true)) return [];

  $candidates = [];
  if (strpos($in, '/') === false) {
    foreach ($roots as $rootAbs) $candidates[] = $rootAbs . '/' . $in;
  } else {
    $candidates[] = rtrim($DOCROOT,'/') . '/' . $in;
    foreach ($roots as $rootAbs) $candidates[] = $rootAbs . '/' . ltrim($in, '/');
  }

  foreach ($candidates as $abs) {
    $rp = @realpath($abs);
    if (!$rp || !is_file($rp)) continue;
    $rp_n = str_replace('\\','/',$rp);
    foreach ($roots as $root) {
      $root_n = str_replace('\\','/',$root);
      if (strpos($rp_n, rtrim($root_n, '/') . '/') === 0) {
        $rel = str_replace('\\','/', str_replace(rtrim($DOCROOT,'/').'/','',$rp_n));
        return ['abs'=>$rp_n, 'rel'=>$rel];
      }
    }
  }
  return [];
}

function read_file_utf8(string $abs): string {
  $raw = @file_get_contents($abs);
  return ($raw === false) ? '' : $raw;
}

function tail_read(string $file, $lines = 5000) {
  if ($lines === 'all') {
    $arr = @file($file, FILE_IGNORE_NEW_LINES);
    return $arr === false ? [] : array_reverse($arr);
  }
  $lines = max(1, (int)$lines);
  try { $f = new SplFileObject($file, 'r'); } catch (Throwable $e) { return []; }
  $f->seek(PHP_INT_MAX);
  $last = $f->key();
  $buffer = [];
  for ($line = $last; $line >= 0 && count($buffer) < $lines; $line--) {
    $f->seek($line);
    $buffer[] = rtrim($f->current(), "\r\n");
  }
  return $buffer; // newest-first
}

/* ---- Markdown yardımcıları ---- */
function md_resolve_url(string $url, string $baseDirRel): string {
  $u = trim($url);
  if ($u === '') return $u;
  if (preg_match('#^(https?:)?//#i', $u) || preg_match('#^(data:|mailto:)#i', $u)) return $u;
  if (substr($u,0,1) === '/') return $u;
  if (stripos($u, 'docs/') === 0) return '/'.$u;
  $base = trim($baseDirRel, '/');
  return '/'.($base === '' ? $u : ($base.'/'.$u));
}
function is_image_url(string $url): bool {
  $path = parse_url($url, PHP_URL_PATH) ?? '';
  return (bool)preg_match('#\.(png|jpe?g|gif|webp|svg)$#i', $path);
}
function is_badge_url(string $url): bool {
  if (stripos($url, 'shields.io') !== false) return true;
  if (stripos($url, 'badge') !== false && is_image_url($url)) return true;
  return false;
}

/* ---- TABLE ---- */
function _md_split_table_row(string $line): array {
  $line = trim($line);
  if ($line !== '' && $line[0] === '|') $line = substr($line, 1);
  if ($line !== '' && substr($line, -1) === '|') $line = substr($line, 0, -1);
  $cells = preg_split('/(?<!\\\\)\|/', $line);
  return array_map(function($c){
    $c = trim($c);
    return str_replace('\\|', '|', $c);
  }, $cells);
}
function _md_is_table_sep_line(string $line): bool {
  $cells = _md_split_table_row($line);
  if (count($cells) < 1) return false;
  foreach ($cells as $c) {
    if (!preg_match('/^\s*:?-{3,}:?\s*$/', $c)) return false;
  }
  return true;
}
function _md_parse_aligns_from_sep(string $line): array {
  $cells = _md_split_table_row($line);
  $aligns = [];
  foreach ($cells as $c) {
    $c = trim($c);
    $left  = strlen($c) && $c[0] === ':';
    $right = strlen($c) && substr($c, -1) === ':';
    if     ($left && $right) $aligns[] = 'center';
    elseif ($right)          $aligns[] = 'right';
    else                     $aligns[] = 'left';
  }
  return $aligns;
}
function md_parse_tables(string $text): string {
  $lines = explode("\n", $text);
  $N = count($lines);
  $out = '';
  for ($i = 0; $i < $N; $i++) {
    $line = $lines[$i];
    if ($i + 1 < $N && strpos($line, '|') !== false && _md_is_table_sep_line($lines[$i+1])) {
      $headerCells = _md_split_table_row($line);
      $aligns      = _md_parse_aligns_from_sep($lines[$i+1]);

      $rows = [];
      $k = $i + 2;
      while ($k < $N) {
        $ln = $lines[$k];
        if (trim($ln) === '' || strpos($ln, '|') === false) break;
        $rows[] = _md_split_table_row($ln);
        $k++;
      }

      $out .= '<table class="md-table"><thead><tr>';
      $colCount = max(count($headerCells), count($aligns));
      for ($c = 0; $c < $colCount; $c++) {
        $th = $headerCells[$c] ?? '';
        $al = $aligns[$c]      ?? 'left';
        $out .= '<th style="text-align:'.$al.'">'.$th.'</th>';
      }
      $out .= '</tr></thead><tbody>';
      foreach ($rows as $r) {
        $out .= '<tr>';
        for ($c = 0; $c < $colCount; $c++) {
          $td = $r[$c] ?? '';
          $al = $aligns[$c] ?? 'left';
          $out .= '<td style="text-align:'.$al.'">'.$td.'</td>';
        }
        $out .= '</tr>';
      }
      $out .= '</tbody></table>'."\n";

      $i = $k - 1;
      continue;
    }
    $out .= $line."\n";
  }
  return $out;
}

/* ---- BLOCKQUOTE ---- */
function md_parse_blockquotes_nested(string $text): string {
  $lines = explode("\n", $text);
  $out = '';
  $open = 0;

  foreach ($lines as $ln) {
    if (preg_match('/^\s*((?:&gt;|>)+)\s?(.*)$/', $ln, $m)) {
      $prefix  = str_replace('&gt;', '>', $m[1]);
      $lvl     = substr_count($prefix, '>');
      $content = $m[2];

      while ($open < $lvl) { $out .= "<blockquote>\n"; $open++; }
      while ($open > $lvl) { $out .= "</blockquote>\n"; $open--; }

      $out .= $content . "\n";
    } else {
      while ($open > 0) { $out .= "</blockquote>\n"; $open--; }
      $out .= $ln . "\n";
    }
  }
  while ($open > 0) { $out .= "</blockquote>\n"; $open--; }
  return $out;
}

/* ---- LISTS ---- */
function md_parse_lists_nested(string $text): string {
  $lines = explode("\n", $text);
  $out = '';
  $stack = [];
  $inLi = false;
  $indentUnit = 2;

  $closeAll = function() use (&$stack, &$inLi, &$out) {
    if ($inLi) { $out .= '</li>'; $inLi = false; }
    while (!empty($stack)) { $out .= '</'.array_pop($stack)['tag'].'>'; }
  };

  foreach ($lines as $ln) {
    if (preg_match('/^(\s*)([*+-])\s+(.*)$/', $ln, $m)) {
      $spaces = str_replace("\t", '  ', $m[1]);
      $level  = intdiv(strlen($spaces), $indentUnit);
      $tag    = 'ul';
      $content= $m[3];

      while (!empty($stack) && end($stack)['level'] > $level) {
        if ($inLi) { $out .= '</li>'; $inLi = false; }
        $out .= '</'.array_pop($stack)['tag'].'>';
      }
      if (!empty($stack) && end($stack)['level'] == $level && end($stack)['tag'] !== $tag) {
        if ($inLi) { $out .= '</li>'; $inLi = false; }
        $out .= '</'.array_pop($stack)['tag'].'>';
      }
      while (empty($stack) || end($stack)['level'] < $level) {
        $out .= '<'.$tag.'>';
        $stack[] = ['tag'=>$tag,'level'=> (empty($stack)?0:end($stack)['level']) + 1];
      }
      if (empty($stack) || end($stack)['level'] < $level || end($stack)['tag'] !== $tag) {
        $out .= '<'.$tag.'>';
        $stack[] = ['tag'=>$tag,'level'=>$level];
      } elseif (end($stack)['level'] == $level && $inLi) {
        $out .= '</li>'; $inLi = false;
      }
      $out .= '<li>'.$content;
      $inLi = true;
      continue;
    }

    if (preg_match('/^(\s*)(\d+)[.)]\s+(.*)$/', $ln, $m)) {
      $spaces = str_replace("\t", '  ', $m[1]);
      $level  = intdiv(strlen($spaces), $indentUnit);
      $tag    = 'ol';
      $content= $m[3];

      while (!empty($stack) && end($stack)['level'] > $level) {
        if ($inLi) { $out .= '</li>'; $inLi = false; }
        $out .= '</'.array_pop($stack)['tag'].'>';
      }
      if (!empty($stack) && end($stack)['level'] == $level && end($stack)['tag'] !== $tag) {
        if ($inLi) { $out .= '</li>'; $inLi = false; }
        $out .= '</'.array_pop($stack)['tag'].'>';
      }
      while (empty($stack) || end($stack)['level'] < $level) {
        $out .= '<'.$tag.'>';
        $stack[] = ['tag'=>$tag,'level'=> (empty($stack)?0:end($stack)['level']) + 1];
      }
      if (empty($stack) || end($stack)['level'] < $level || end($stack)['tag'] !== $tag) {
        $out .= '<'.$tag.'>';
        $stack[] = ['tag'=>$tag,'level'=>$level];
      } elseif (end($stack)['level'] == $level && $inLi) {
        $out .= '</li>'; $inLi = false;
      }
      $out .= '<li>'.$content;
      $inLi = true;
      continue;
    }

    if (trim($ln) === '') {
      if ($inLi) { $out .= '</li>'; $inLi = false; }
      while (!empty($stack)) { $out .= '</'.array_pop($stack)['tag'].'>'; }
      $out .= "\n";
    } else {
      if ($inLi) { $out .= '</li>'; $inLi = false; }
      while (!empty($stack)) { $out .= '</'.array_pop($stack)['tag'].'>'; }
      $out .= $ln."\n";
    }
  }
  $closeAll();
  return $out;
}

/* ----------------------------------------------------------
 * 4) PARAMETRELER + DOSYA ÇÖZÜMLEME (HEADER'DAN ÖNCE!)
 * -------------------------------------------------------- */
$paramLog = isset($_GET['log']) ? (string)$_GET['log'] : '';
$paramMd  = isset($_GET['md'])  ? (string)$_GET['md']  : '';

$defaultMdRes = null;
foreach ($DEFAULT_MD_CANDIDATES as $cand) {
  $try = safe_resolve_under_roots($DOCROOT, $MD_ROOTS, $cand);
  if ($try) { $defaultMdRes = $try; break; }
}

if ($paramMd === '' && $paramLog === '') {
  if ($defaultMdRes) $paramMd = $defaultMdRes['rel'];
  else               $paramLog = $DEFAULT_LOG_REL;
}

$viewType = ($paramMd !== '') ? 'md' : 'log';
if ($viewType === 'log' && $paramLog === '') { $paramLog = $DEFAULT_LOG_REL; }

$resolved_abs = null;
$resolved_rel = null;
$is_md        = ($viewType === 'md');

if ($viewType === 'log') {
  $res = safe_resolve_under_roots($DOCROOT, $LOG_ROOTS, $paramLog);
  if (!$res) $res = safe_resolve_under_roots($DOCROOT, $LOG_ROOTS, $DEFAULT_LOG_REL);
  if ($res) { $resolved_abs = $res['abs']; $resolved_rel = $res['rel']; }
} else {
  $res = safe_resolve_under_roots($DOCROOT, $MD_ROOTS, $paramMd);
  if ($res) { $resolved_abs = $res['abs']; $resolved_rel = $res['rel']; }
}

/* ---- RAW indirme: header.php ÇAĞRILMADAN ÖNCE! ---- */
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
  if ($resolved_abs && is_file($resolved_abs)) {
    /* Herhangi bir output varsa temizlemeye çalış */
    if (function_exists('ob_get_level') && ob_get_level()) { while (ob_get_level()) { @ob_end_clean(); } }
    header('X-Content-Type-Options: nosniff');
    $ctype = $is_md ? 'text/markdown; charset=utf-8' : 'text/plain; charset=utf-8';
    header('Content-Type: '.$ctype);
    header('Content-Disposition: attachment; filename="'.basename($resolved_rel ?: $resolved_abs).'"');
    $size = @filesize($resolved_abs);
    if ($size !== false) header('Content-Length: '.$size);
    readfile($resolved_abs);
    exit;
  }
  if (function_exists('ob_get_level') && ob_get_level()) { while (ob_get_level()) { @ob_end_clean(); } }
  header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
  header('Content-Type: text/plain; charset=utf-8');
  echo "Not found";
  exit;
}

/* ----------------------------------------------------------
 * 5) GÖRÜNÜM BİLGİLERİ
 * -------------------------------------------------------- */
$display_rel  = $resolved_rel ?: ($is_md ? ($paramMd ?: '') : ($paramLog ?: $DEFAULT_LOG_REL));
$display_base = basename($display_rel ?: ($is_md ? 'README.md' : 'log.txt'));
$display_dir  = dirname($display_rel);
$display_dir  = ($display_dir === '.' ? '' : $display_dir);

/* ----------------------------------------------------------
 * 6) HEADER (artık güvenle çağrılabilir)
 * -------------------------------------------------------- */
require_once HAMU_DIR.'/header.php';

/* ----------------------------------------------------------
 * 7) MARKDOWN RENDERER
 * -------------------------------------------------------- */
function render_markdown_githubish(string $text, string $mdRelPath): string {
  $text = str_replace(["\r\n","\r"], "\n", $text);
  $baseDir = trim(dirname($mdRelPath), '/');

  // (0) <p><a><img></a></p> whitelisted HTML — placeholder
  $rawHtmlBlocks = [];
  $text = preg_replace_callback(
    '#<p[^>]*>\s*<a[^>]*?href\s*=\s*"([^"]+)"[^>]*>\s*<img[^>]*?src\s*=\s*"([^"]+)"[^>]*?(?:alt\s*=\s*"([^"]*)")?[^>]*>\s*</a>\s*</p>#is',
    function($m) use (&$rawHtmlBlocks){
      $href = trim($m[1]);
      $src  = trim($m[2]);
      $alt  = isset($m[3]) ? $m[3] : '';
      if (!preg_match('#^(https?://|/)#i', $href)) $href = '/'.ltrim($href,'/');
      if (!preg_match('#^(https?://|/)#i', $src))  $src  = '/'.ltrim($src,'/');
      $html = '<p class="md-center"><a href="'. _h($href) .'" target="_blank" rel="noopener"><img class="md-img" src="'. _h($src) .'" alt="'. _h($alt) .'"></a></p>';
      $ph = '__RAWHTML__'.count($rawHtmlBlocks).'__';
      $rawHtmlBlocks[] = ['html'=>$html];
      return $ph;
    },
    $text
  );

  // (1) code fence placeholder
  $codeBlocks = [];
  $text = preg_replace_callback('#```([a-zA-Z0-9_-]+)?\n([\s\S]*?)\n```#', function($m) use (&$codeBlocks){
    $lang = isset($m[1]) ? trim($m[1]) : '';
    $code = $m[2] ?? '';
    $ph = '__CODEBLOCK__'.count($codeBlocks).'__';
    $codeBlocks[] = [
      'html' => '<pre><code'.($lang ? ' class="language-'.htmlspecialchars($lang).'">' : '>') . _h($code) . '</code></pre>'
    ];
    return $ph;
  }, $text);

  // (2) escape
  $text = _h($text);

  // (3) başlıklar + hr
  $text = preg_replace('/^###### (.*)$/m', '<h6>$1</h6>', $text);
  $text = preg_replace('/^##### (.*)$/m',  '<h5>$1</h5>', $text);
  $text = preg_replace('/^#### (.*)$/m',   '<h4>$1</h4>', $text);
  $text = preg_replace('/^### (.*)$/m',    '<h3>$1</h3>', $text);
  $text = preg_replace('/^## (.*)$/m',     '<h2>$1</h2>', $text);
  $text = preg_replace('/^# (.*)$/m',      '<h1>$1</h1>', $text);
  $text = preg_replace('/^\s*([-*_])(\s*\1){2,}\s*$/m', '<hr>', $text);

  // (4) blockquote
  $text = md_parse_blockquotes_nested($text);

  // (5) tablolar
  $text = md_parse_tables($text);

  // (6) listeler
  $text = md_parse_lists_nested($text);

  // (7) Linkler (image hariç) — local .md → reader
  $text = preg_replace_callback('/(?<!\!)\[(.*?)\]\((.*?)\)/', function($m) use ($baseDir){
    $label  = $m[1];
    $urlRaw = htmlspecialchars_decode($m[2]);

    $isExternal = preg_match('#^https?://#i', $urlRaw) || preg_match('#^(data:|mailto:)#i', $urlRaw);
    $normalizedLocal = $urlRaw;
    if (!$isExternal && substr($urlRaw, 0, 1) !== '/') {
      $normalizedLocal = '/'.ltrim($urlRaw, '/');
    }
    $url = $isExternal ? md_resolve_url($urlRaw, $baseDir) : $normalizedLocal;

    if (!$isExternal && preg_match('#\.md$#i', parse_url($normalizedLocal, PHP_URL_PATH) ?? '')) {
      $mdPath  = ltrim(parse_url($normalizedLocal, PHP_URL_PATH) ?? '', '/');
      $mdParam = implode('/', array_map('rawurlencode', explode('/', $mdPath)));
      return '<a href="'. _h(reader_url(['md'=>$mdParam])) .'" target="_blank" rel="noopener">'.$label.'</a>';
    }

    if (is_badge_url($url)) {
      return '<img class="md-badge" src="'. _h($url) .'" alt="'. _h($label) .'">';
    }
    $isDocs = (stripos(ltrim($url, '/'), 'docs/') === 0);
    if ($isDocs && is_image_url($url)) {
      return '<img class="md-img" src="'. _h($url) .'" alt="'. _h($label) .'">';
    }
    return '<a href="'. _h($url) .'" target="_blank" rel="noopener">'.$label.'</a>';
  }, $text);

  // (8) Resimler
  $text = preg_replace_callback('/!\[(.*?)\]\((.*?)\)/', function($m) use ($baseDir){
    $alt    = $m[1];
    $srcRaw = htmlspecialchars_decode($m[2]);
    $src    = md_resolve_url($srcRaw, $baseDir);
    if (is_badge_url($src)) {
      return '<img class="md-badge" src="'. _h($src) .'" alt="'. _h($alt) .'">';
    }
    return '<img class="md-img" src="'. _h($src) .'" alt="'. _h($alt) .'">';
  }, $text);

  // (9) Inline
  $text = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $text);
  $text = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $text);
  $text = preg_replace('/\*([^\*]+)\*/',     '<em>$1</em>', $text);

  // (10) Autolink
  $text = preg_replace('/(?<!["\'=\]>])(https?:\/\/[^\s<]+)(?![^<]*>)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);

  // (11) Tek başına image URL satırı
  $text = preg_replace_callback('/^(https?:\/\/[^\s<]+?\.(?:png|jpg|jpeg|gif|webp|svg))$/im', function($m){
    $u = $m[1];
    return '<p><img class="md-img" src="'. _h($u) .'" alt=""></p>';
  }, $text);

  // (12) Paragraflar
  $parts = preg_split("/\n{2,}/", $text);
  foreach ($parts as &$p) {
    if (preg_match('#^\s*<(?:h[1-6]|ul|ol|pre|blockquote|hr|table|img|p|div|code)#i', $p)) {
      // block
    } else {
      $p = '<p>'.preg_replace("/\n/", "<br>", trim($p)).'</p>';
    }
  }
  $html = implode("\n\n", $parts);

  // (13) placeholders geri koy
  foreach ($codeBlocks as $i=>$blk)   $html = str_replace('__CODEBLOCK__'.$i.'__', $blk['html'], $html);
  foreach ($rawHtmlBlocks as $i=>$rb) $html = str_replace('__RAWHTML__'.$i.'__',   $rb['html'], $html);

  return '<div class="markdown-body">'.$html.'</div>';
}

function markdown_githubish_css(): string {
  return <<<CSS
<style>
.markdown-body { line-height:1.65; font-size:16px; }
.markdown-body h1,.markdown-body h2{ border-bottom:1px solid rgba(0,0,0,.1); padding-bottom:.25em; }
.markdown-body pre{ background:rgba(127,127,127,.08); padding:.75rem; border-radius:.5rem; overflow:auto; }
.markdown-body code{ background:rgba(127,127,127,.15); padding:.12rem .35rem; border-radius:.35rem; }
.markdown-body pre code{ background:transparent; padding:0; }
.markdown-body blockquote{ border-left:4px solid rgba(127,127,127,.35); margin:0; padding:.5rem 1rem; color:#6a737d; background:rgba(127,127,127,.08); border-radius:.25rem; }
.markdown-body ul, .markdown-body ol{ padding-left:1.5rem; }
.markdown-body a{ text-decoration: underline; }
.markdown-body img.md-img{ max-width:100%; max-height:80vh; height:auto; width:auto; object-fit:contain; border-radius:.25rem; }
.markdown-body img.md-badge{ height:20px; max-height:20px; width:auto; vertical-align:middle; }
.markdown-body .md-center { text-align:center; }
.markdown-body table.md-table{ border-collapse: collapse; width: 100%; margin: .75rem 0; }
.markdown-body table.md-table th,
.markdown-body table.md-table td{ border:1px solid rgba(0,0,0,.12); padding:.5rem .75rem; vertical-align:top; }
.markdown-body table.md-table thead th{ background:rgba(0,0,0,.04); }
.log-viewer{
  white-space:pre-wrap; overflow:auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size:13px; line-height:1.45;
  max-height:75vh;
  padding: .25rem .5rem;
  background: rgba(127,127,127,.06);
  border-radius:.5rem;
}
.small-path {font-size:.875rem; color: var(--bs-secondary-color, #6c757d);}
.badge-hint{ font-size:.8rem; color: var(--bs-secondary-color, #6c757d); }
</style>
CSS;
}
?>

<div class="container-module container-fluid">
     <div class="content-large py-3">

          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
               <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-file-lines h4 m-0"></i>
                    <div class="m-0">
                         <div class="h4 mb-0"><?= _h($display_base) ?></div>
                         <?php if ($display_dir): ?>
                         <div class="small-path"><?= _h($display_dir) ?></div>
                         <?php endif; ?>
                    </div>
               </div>

               <div class="d-flex flex-wrap gap-2">
                    <a href="<?= _h($back_url = (!empty($_SERVER['HTTP_REFERER']) && (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?? '') === ($_SERVER['HTTP_HOST'] ?? '') ? $_SERVER['HTTP_REFERER'] : reader_url([]))) ?>"
                         class="btn btn-secondary btn-sm" onclick="if(history.length>1){history.back();return false;}">
                         <i class="fa fa-arrow-left me-1"></i> <?= __l('gotoback') ?>
                    </a>

                    <?php if (!$is_md): ?>
                    <a href="#" onclick="window.location.reload(true);" class="btn btn-outline-secondary btn-sm">
                         <i class="fa fa-rotate me-1"></i> <?= __l('refresh') ?? 'Refresh' ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($resolved_abs && is_file($resolved_abs)): ?>
                    <?php if ($is_md): ?>
                    <?php $dlUrl = reader_url(['md'=>$display_rel,'raw'=>1]); ?>
                    <a href="<?= _h($dlUrl) ?>" class="btn btn-outline-primary btn-sm">
                         <i class="fa fa-download me-1"></i> <?= __l('download') ?? 'Download' ?>
                    </a>
                    <?php else: ?>
                    <?php $dlUrl = reader_url(['log'=>$display_rel,'raw'=>1]); ?>
                    <a href="<?= _h($dlUrl) ?>" class="btn btn-outline-primary btn-sm">
                         <i class="fa fa-download me-1"></i> <?= __l('download') ?? 'Download' ?>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
               </div>
          </div>

          <div class="card shadow-sm">
               <div class="card-body markdown-content">
                    <?= markdown_githubish_css(); ?>
                    <?php
        if ($resolved_abs && is_file($resolved_abs)) {
          if ($is_md) {
            $content = read_file_utf8($resolved_abs);
            echo render_markdown_githubish($content, $display_rel);
          } else {
            $linesParam = isset($_GET['lines']) ? $_GET['lines'] : $DEFAULT_LOG_MAX_LINES;
            $order      = ((isset($_GET['order']) ? $_GET['order'] : $DEFAULT_LOG_ORDER) === 'asc') ? 'asc' : 'desc';
            $hlParam    = (string)($_GET['hl'] ?? '');
            $hlCase     = ((string)($_GET['hcase'] ?? '') === '1');
            $autoHL     = ((string)($_GET['auto_hl'] ?? '1') !== '0');

            $arr = tail_read($resolved_abs, $linesParam);
            if ($order === 'asc') { $arr = array_reverse($arr); }

            $datePattern = '#\b(' .
                '\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?Z?|' .
                '\d{2}\.\d{2}\.\d{4}[ T]\d{2}:\d{2}:\d{2}|' .
                '\d{4}/\d{2}/\d{2}[ T]\d{2}:\d{2}:\d{2}|' .
                '\d{4}-\d{2}-\d{2}|' .
                '\d{2}\.\d{2}\.\d{4}' .
            ')\b#';

            $hlWords = array_values(array_filter(array_map('trim', preg_split('/[;,]+/', $hlParam))));
            if ($autoHL && empty($hlWords)) {
              $hlWords = ['ERROR','ERR','WARN','WARNING','CRITICAL','FATAL'];
            }
            $hlRegex = null;
            if (!empty($hlWords)) {
              $hlRegex = '/(' . implode('|', array_map(fn($w)=>preg_quote($w, '/'), $hlWords)) . ')/' . ($hlCase ? '' : 'i');
            }

            echo '<pre class="log-viewer">';
            foreach ($arr as $line) {
              $lineHtml = _h($line);
              $lineHtml = preg_replace_callback($datePattern, fn($m) => '<b>' . $m[1] . '</b>', $lineHtml);
              if ($hlRegex) {
                $lineHtml = preg_replace($hlRegex, '<mark>$1</mark>', $lineHtml);
              }
              echo $lineHtml . "\n";
            }
            echo '</pre>';

            if ($linesParam !== 'all') {
              echo '<div class="badge-hint mt-2">'. _h("Son ".(int)$linesParam." satır gösteriliyor. Tamamı için URL'ye &lines=all ekleyin.") .'</div>';
            }
            echo '<div class="badge-hint">'. _h("İpucu: &order=asc|desc, &hl=ERROR,WARN,CRITICAL, &hcase=1, &auto_hl=0") .'</div>';
          }
        } else {
          echo '<div class="alert alert-danger mb-0">'.(__l('file_not_found') ?? 'Dosya bulunamadı').'</div>';
        }
        ?>
               </div>
          </div>

     </div>
</div>
<?= $side ?>
<?= get_footer(); ?>