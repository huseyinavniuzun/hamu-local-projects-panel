<?php
// ----- TEŞHİS BLOĞU (en başta dursun) -----
$__debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($__debug) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting(E_ALL);
  register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
      $msg = htmlspecialchars($e['message'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $file= htmlspecialchars($e['file'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $line= (int)($e['line'] ?? 0);
      echo "<pre style='white-space:pre-wrap;background:#200;color:#faa;padding:12px;border:1px solid #a44;'>
FATAL: {$msg}
{$file}:{$line}
</pre>";
    }
  });
} else {
  // Prod’da notice/strict gizli kalsın
  error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
  ini_set('display_errors','0');
}

// ----- SAYFA META / HEADER -----
$page_title  = "Server Info";  // Sayfa Başlığı
$body_class  = "";
$include_db  = 0;
$menu_type   = 1;
$side_bar    = 1;
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/header.php';

// ----- GÜVENLİ YARDIMCILAR -----
if (!function_exists('hamu_h')) {
  function hamu_h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}
if (!function_exists('hamu_is_local')) {
  function hamu_is_local(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($ip, ['127.0.0.1','::1'], true);
  }
}
$allow_full = hamu_is_local() || $__debug;

if (!function_exists('hamu_mask_value')) {
  function hamu_mask_value(string $key, $val): string {
    $s = (string)$val;
    if ($s === '') return '';
    $key = strtoupper($key);

    $sensitive = [
      'SERVER_ADDR','DOCUMENT_ROOT','SCRIPT_FILENAME','SCRIPT_NAME','PHP_SELF',
      'REMOTE_ADDR','REMOTE_HOST','SERVER_ADMIN','PATH_TRANSLATED',
    ];
    if (in_array($key, $sensitive, true)) {
      if (filter_var($s, FILTER_VALIDATE_IP)) {
        $parts = explode('.', $s);
        if (count($parts) === 4) return $parts[0].'.'.$parts[1].'.*.***';
        return substr($s, 0, 3).'*.***';
      }
      $len = strlen($s);
      if ($len <= 8) return substr($s, 0, max(0,$len-2)).'**';
      return substr($s,0,4).'...'.substr($s,-4);
    }
    if (strlen($s) > 256) return substr($s,0,200).'...'.substr($s,-30);
    return $s;
  }
}
if (!function_exists('hamu_format_bytes')) {
  function hamu_format_bytes($bytes): string {
    if (!is_numeric($bytes) || $bytes < 0) return 'N/A';
    $u = ['B','KB','MB','GB','TB','PB']; $i = 0;
    while ($bytes >= 1024 && $i < count($u)-1) { $bytes/=1024; $i++; }
    return number_format($bytes, 2).' '.$u[$i];
  }
}

// ----- SERVER KEY HARİTASI -----
$map = [
  'SERVER_NAME'           => ['Sunucu Adı', 'Server name'],
  'SERVER_ADDR'           => ['Sunucu IP Adresi', 'Server IP address'],
  'SERVER_SOFTWARE'       => ['Sunucu Yazılımı', 'Server software'],
  'SERVER_PROTOCOL'       => ['Protokol', 'Protocol'],
  'SERVER_PORT'           => ['Sunucu Portu', 'Server port'],
  'DOCUMENT_ROOT'         => ['Kök Dizin', 'Document root'],
  'REQUEST_METHOD'        => ['İstek Metodu', 'Request method'],
  'REQUEST_URI'           => ['İstek URI', 'Request URI'],
  'QUERY_STRING'          => ['Sorgu Parametreleri', 'Query string'],
  'HTTP_HOST'             => ['Domain Adı', 'HTTP host'],
  'HTTP_USER_AGENT'       => ['Tarayıcı Bilgisi', 'User agent'],
  'HTTP_REFERER'          => ['Yönlendiren Sayfa', 'Referrer page'],
  'HTTP_ACCEPT_LANGUAGE'  => ['Dil Tercihi', 'Accepted languages'],
  'HTTPS'                 => ['HTTPS Durumu', 'HTTPS status'],
  'REMOTE_ADDR'           => ['Kullanıcı IP Adresi', 'Client IP address'],
  'REMOTE_PORT'           => ['Kullanıcı Portu', 'Client port'],
  'SCRIPT_FILENAME'       => ['Tam Dosya Yolu', 'Script filename'],
  'SCRIPT_NAME'           => ['Betik Adı', 'Script name'],
  'PHP_SELF'              => ['Çalışan Betik', 'Executing script'],
  'REQUEST_TIME'          => ['İstek Zamanı', 'Request timestamp'],
  'REQUEST_TIME_FLOAT'    => ['İstek Zamanı (Float)', 'Request time (float)'],
  'SERVER_ADMIN'          => ['Sunucu Yöneticisi', 'Server admin email'],
  'PATH_INFO'             => ['Yol Bilgisi', 'Path info'],
  'PATH_TRANSLATED'       => ['Yol Çözümlemesi', 'Path translated'],
  'SCRIPT_URI'            => ['Tam URI', 'Full script URI'],
  'SCRIPT_URL'            => ['URL', 'Script URL'],
  'GATEWAY_INTERFACE'     => ['Gateway Arayüzü', 'Gateway interface'],
  'SERVER_SIGNATURE'      => ['Sunucu İmzası', 'Server signature'],
  'HTTP_CONNECTION'       => ['Bağlantı Tipi', 'Connection type'],
  'HTTP_ACCEPT'           => ['Kabul Edilen Tipler', 'Accepted types'],
  'HTTP_ACCEPT_ENCODING'  => ['Kabul Edilen Kodlamalar', 'Accepted encodings'],
  'REMOTE_HOST'           => ['Kullanıcı Ana Bilgisayarı', 'Remote host'],
  'REMOTE_USER'           => ['Kimlik Doğrulanmış Kullanıcı', 'Authenticated user'],
  'AUTH_TYPE'             => ['Kimlik Türü', 'Authentication type'],
  'CONTEXT_DOCUMENT_ROOT' => ['Bağlam Kök Dizini', 'Context document root'],
  'CONTEXT_PREFIX'        => ['Bağlam Ön Eki', 'Context prefix'],
  'REQUEST_SCHEME'        => ['İstek Şeması', 'Request scheme (http/https)'],
];

// ----- DİSK ÖLÇÜMLERİ (güvenli dizin) -----
$diskBase = sys_get_temp_dir();
if (!is_dir($diskBase)) $diskBase = __DIR__;
$diskTotal = @disk_total_space($diskBase);
$diskFree  = @disk_free_space($diskBase);
?>

<?= $side ?>

<div class="container-module">
     <div class="content-large">
          <div class="d-flex justify-content-between align-items-center">
               <h3 class="mb-4">PHP Sunucu Bilgileri (Server Information)</h3>
               <span
                    class="card  small text-<?= $allow_full ? 'white' : 'dark' ?> p-1 bg-<?= $allow_full ? 'success' : 'secondary' ?>">
                    <?= $allow_full ? 'Tam Gösterim' : 'Güvenli Mod' ?>
               </span>
          </div>

          <div class="table-responsive">
               <table class="table table-striped table-bordered align-middle">
                    <thead>
                         <tr>
                              <th scope="col">Değişken (Key)</th>
                              <th scope="col">Türkçe Açıklama</th>
                              <th scope="col">English Description</th>
                              <th scope="col">Değer (Value)</th>
                         </tr>
                    </thead>
                    <tbody>
                         <?php foreach ($map as $key => $desc): ?>
                         <?php
              $raw = $_SERVER[$key] ?? '';
              $val = $allow_full ? (string)$raw : hamu_mask_value($key, $raw);
            ?>
                         <tr>
                              <td class="text-dark"><code><?= hamu_h("\$_SERVER['$key']") ?></code></td>
                              <td><?= hamu_h($desc[0]) ?></td>
                              <td><?= hamu_h($desc[1]) ?></td>
                              <td class="text-success"><?= hamu_h($val) ?></td>
                         </tr>
                         <?php endforeach; ?>
                    </tbody>
               </table>
          </div>

          <h4 class="mt-5">Ek Bilgiler (Extra)</h4>
          <div class="table-responsive">
               <table class="table table-bordered table-striped align-middle">
                    <tbody>
                         <?php
            $uname   = php_uname();
            $version = PHP_VERSION;
            $sapi    = PHP_SAPI;
            $cwd     = getcwd();
            $mem     = memory_get_usage();
            $memPk   = memory_get_peak_usage();

            if (!$allow_full) {
              $cwd   = hamu_mask_value('SCRIPT_FILENAME', $cwd);
              if (strlen($uname) > 64) $uname = substr($uname,0,64).'…';
            }
          ?>
                         <tr>
                              <td>php_uname()</td>
                              <td><?= hamu_h($uname) ?></td>
                         </tr>
                         <tr>
                              <td>PHP_VERSION</td>
                              <td><?= hamu_h($version) ?></td>
                         </tr>
                         <tr>
                              <td>PHP_SAPI</td>
                              <td><?= hamu_h($sapi) ?></td>
                         </tr>
                         <tr>
                              <td>getcwd()</td>
                              <td><?= hamu_h($cwd) ?></td>
                         </tr>
                         <tr>
                              <td>sys_get_temp_dir()</td>
                              <td><?= hamu_h($diskBase) ?></td>
                         </tr>
                         <tr>
                              <td>memory_get_usage()</td>
                              <td><?= hamu_h(number_format($mem)) ?> B <small
                                        class="text-muted ms-2"><?= hamu_h(hamu_format_bytes($mem)) ?></small></td>
                         </tr>
                         <tr>
                              <td>memory_get_peak_usage()</td>
                              <td><?= hamu_h(number_format($memPk)) ?> B <small
                                        class="text-muted ms-2"><?= hamu_h(hamu_format_bytes($memPk)) ?></small></td>
                         </tr>
                         <tr>
                              <td>disk_total_space('<?= hamu_h($diskBase) ?>')</td>
                              <td>
                                   <?= $diskTotal !== false ? hamu_h(number_format($diskTotal)).' B' : 'N/A' ?>
                                   <small
                                        class="text-muted ms-2"><?= $diskTotal !== false ? hamu_h(hamu_format_bytes($diskTotal)) : '' ?></small>
                              </td>
                         </tr>
                         <tr>
                              <td>disk_free_space('<?= hamu_h($diskBase) ?>')</td>
                              <td>
                                   <?= $diskFree !== false ? hamu_h(number_format($diskFree)).' B' : 'N/A' ?>
                                   <small
                                        class="text-muted ms-2"><?= $diskFree !== false ? hamu_h(hamu_format_bytes($diskFree)) : '' ?></small>
                              </td>
                         </tr>
                    </tbody>
               </table>
          </div>

          <p class="small text-muted mt-3">
               Not: Hata görmüyorsan ama 500 alıyorsan, URL’ye <code>?debug=1</code> ekle; fatal hata mesajı sayfanın
               üstünde çıkacaktır.
          </p>
     </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/footer.php'; ?>