<?php
/**
 * HAMU DevPanel - Public API (TEK DOSYA, DÜZENLENMİŞ)
 * Kullanım: /index.php?api=...
 * İçerir: i18n, yönlendirme (db/config/projects), 2FA (email/app/backup)
 */

require_once dirname(__DIR__).'/security.php';
require_once dirname(__DIR__).'/config.php';

/* ============================ I18N yardımcıları ============================ */
if (!function_exists('__l')) {
  function __l($key, ...$params) {
    global $languages, $lang, $used_lang_keys;
    if (!is_array($used_lang_keys)) { $used_lang_keys = []; }
    if (!in_array($key, $used_lang_keys, true)) { $used_lang_keys[] = $key; }
    $text = $languages[$lang][$key] ?? $key;
    if (!$params) return $text;
    if (preg_match('/%(\d+\$)?s/', $text)) { return @vsprintf($text, $params); }
    $iParams = array_values($params);
    $text = preg_replace_callback('/%(\d+\$)?p/', function($m) use ($iParams) {
      if (!empty($m[1])) { $idx = max(((int) rtrim($m[1], '$')) - 1, 0); return array_key_exists($idx, $iParams) ? (string)$iParams[$idx] : ''; }
      return isset($iParams[0]) ? (string)$iParams[0] : '';
    }, $text);
    return $text;
  }
}

/* ============================ Genel yardımcılar ============================ */
function _json_input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
      $j = json_decode($raw, true);
      if (is_array($j)) return $j;
    }
  }
  return [];
}

function _collect_keys(array $src, array $json): array {
  $c = [];
  foreach (['keys','key','k'] as $p) {
    if (isset($src[$p])) {
      $v = $src[$p];
      if (is_array($v)) $c = array_merge($c, $v);
      else $c = array_merge($c, preg_split('/\s*,\s*/', (string)$v, -1, PREG_SPLIT_NO_EMPTY));
    }
  }
  foreach (['keys','key','k'] as $p) {
    if (isset($json[$p])) {
      $v = $json[$p];
      if (is_array($v)) $c = array_merge($c, $v);
      else $c[] = (string)$v;
    }
  }
  $c = array_map('strval', $c);
  $c = array_map('trim', $c);
  $c = array_filter($c, fn($s)=>$s!=='');
  $c = array_values(array_unique($c));
  return $c;
}

function _cur_lang(): string {
  $L = $_SESSION['hamu_lang'] ?? 'EN';
  return strtoupper($L);
}

/* ============================ Router seviye-1 ============================ */
$op = $_GET['api'] ?? $_POST['api'] ?? '';

/* ============================ I18N endpoint ============================ */
if ($op === 'i18n') {
  require_once dirname(__DIR__) . '/lang.php';

  header('Content-Type: application/json; charset=UTF-8');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: no-store, no-cache, must-revalidate');

  $json = _json_input();
  $src  = array_merge($_GET, $_POST);
  $keys = _collect_keys($src, $json);

  $curLang   = _cur_lang();
  $translate = function_exists('__l') ? '__l' : fn($k)=>$k;

  $wantAll = (empty($keys) || in_array('all', array_map('strtolower',$keys), true));
  if ($wantAll) {
    $dict = [];
    if (function_exists('get_used_lang_array')) {
      try { $arr = get_used_lang_array(); if (is_array($arr)) $dict = $arr; } catch (\Throwable $e) {}
    }
    echo json_encode([
      'success'         => true,
      'currentLanguage' => $curLang,
      'languageData'    => $dict,
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $out = [];
  foreach ($keys as $k) { $out[$k] = $translate($k); }
  echo json_encode([
    'success'         => true,
    'currentLanguage' => $curLang,
    'languageData'    => $out,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================ 2FA endpoint (tek dosya içinde) ============================ */
/*  NOT: Bu blok, /index.php?api=2fa&op=... isteklerini işler.  */
if ($op === '2fa') {
  /* -------- küçük yardımcılar -------- */
  $ok = function($extra = [], $code = 200) {
    if (!headers_sent()) {
      http_response_code($code);
      header('Content-Type: application/json; charset=UTF-8');
      header('X-Content-Type-Options: nosniff');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    echo json_encode(['success'=>true] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
  };
  $err = function($msg='error', $code=400) {
    if (!headers_sent()) {
      http_response_code($code);
      header('Content-Type: application/json; charset=UTF-8');
      header('X-Content-Type-Options: nosniff');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    echo json_encode(['success'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
  };
  $jsonIn = function() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
      $raw = file_get_contents('php://input');
      if ($raw !== '' && $raw !== false) {
        $j = json_decode($raw, true);
        if (is_array($j)) return $j;
      }
    }
    return [];
  };
  $safe_eq = function($a,$b){ return hash_equals((string)$a,(string)$b); };
  $mask_email = function($em){
    if (!is_string($em) || strpos($em,'@')===false) return $em;
    [$u,$d]=explode('@',$em,2);
    $u2 = mb_substr($u,0,2) . str_repeat('•', max(0, mb_strlen($u)-2));
    return $u2.'@'.$d;
  };
  $twofa_hmac_key = function(){
    $envB64 = getenv('HAMU_ENC_KEY_B64') ?: '';
    $raw    = $envB64 ? base64_decode($envB64, true) : '';
    // Prod’da ENV zorunlu olsun istersen burada assert edebilirsin.
    return hash('sha256', ($raw ?: session_id()) . '|hamu-2fa', true);
  };

  /* ---- app_config.json erişimi (tek dosya içinde) ---- */
  $cfg_path = function () {
    $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2), '/');
    return $docroot.'/.hamu/cache/app_config.json';
  };
  $cfg_read = function () use ($cfg_path) {
    $fp = $cfg_path();
    if (!is_file($fp)) return [];
    $txt = @file_get_contents($fp);
    $j = json_decode($txt ?: '[]', true);
    
    // Boolean değerleri normalize et
    if (is_array($j)) {
        // 2fa_s değerini normalize et
        if (isset($j['2fa_s'])) {
            $v = $j['2fa_s'];
            if ($v === 'false' || $v === '0' || $v === false) {
                $j['2fa_s'] = false;
            } else {
                $j['2fa_s'] = true;
            }
        }
        
        // use_auth_app değerini normalize et  
        if (isset($j['use_auth_app'])) {
            $v = $j['use_auth_app'];
            if ($v === 'false' || $v === '0' || $v === false) {
                $j['use_auth_app'] = false;
            } else {
                $j['use_auth_app'] = true;
            }
        }
    }
    
    return is_array($j) ? $j : [];
};
// Config yazma fonksiyonuna log ekle
$cfg_write = function (array $fields) use ($cfg_path, &$cfg_read) {
    $fp = $cfg_path();
    $cur = $cfg_read();
    
    // DEBUG: Hangi alanların değiştirildiğini logla
    $changed_fields = [];
    foreach ($fields as $k=>$v) {
        $old_val = $cur[$k] ?? 'NOT_SET';
        if ($v === '__UNSET__') { 
            unset($cur[$k]); 
            $changed_fields[$k] = "UNSET (was: " . json_encode($old_val) . ")";
        } else {
            $cur[$k] = $v;
            $changed_fields[$k] = "SET_TO: " . json_encode($v) . " (was: " . json_encode($old_val) . ")";
        }
    }
    
    // Never log configuration values or 2FA secrets.
    
    @file_put_contents($fp, json_encode($cur, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
    @chmod($fp, 0640);
    return $cur;
};

  /* ---- TOTP yardımcıları ---- */
  $b32_decode = function($b32){
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = preg_replace('/[^A-Z2-7]/','', strtoupper($b32));
    $bin=''; for($i=0;$i<strlen($b32);$i++){ $v=strpos($alphabet,$b32[$i]); if($v===false) continue; $bin.=str_pad(decbin($v),5,'0',STR_PAD_LEFT); }
    $out=''; for($j=0;$j+8<=strlen($bin);$j+=8){ $out.=chr(bindec(substr($bin,$j,8))); }
    return $out;
  };
  $totp_now = function($secret_b32, $period=30, $digits=6, $algo='sha1', $t=null) use ($b32_decode) {
    $t = $t ?? time();
    $counterNum = floor($t / $period);
    $counter = pack('N*', 0) . pack('N*', $counterNum);
    $secret  = $b32_decode($secret_b32);
    $hash = hash_hmac($algo, $counter, $secret, true);
    $offset = ord($hash[strlen($hash)-1]) & 0x0F; // dinamik offset
    $trunc = ((ord($hash[$offset]) & 0x7F) << 24) |
             ((ord($hash[$offset+1]) & 0xFF) << 16) |
             ((ord($hash[$offset+2]) & 0xFF) << 8) |
             (ord($hash[$offset+3]) & 0xFF);
    $code = (string)($trunc % (10 ** $digits));
    return str_pad($code, $digits, '0', STR_PAD_LEFT);
  };
  $totp_verify_window = function($secret_b32, $code, $period=30, $digits=6, $algo='sha1', $win=1) use ($totp_now,$safe_eq) {
    $t=time(); $code=(string)$code;
    for($i=-$win;$i<=$win;$i++){
      if($safe_eq($totp_now($secret_b32,$period,$digits,$algo,$t+$i*$period),$code)) return true;
    }
    return false;
  };
  $b32_encode = function($raw){
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bin=''; for($i=0;$i<strlen($raw);$i++){ $bin.=str_pad(decbin(ord($raw[$i])),8,'0',STR_PAD_LEFT); }
    $out=''; for($j=0;$j<strlen($bin);$j+=5){
      $chunk=substr($bin,$j,5); if(strlen($chunk)<5) $chunk=str_pad($chunk,5,'0',STR_PAD_RIGHT);
      $out.=$alphabet[bindec($chunk)];
    }
    return $out;
  };

  /* ---- “ikisi de kapalıysa temizle” ---- */
  $clear_when_all_off = function (array $cfg_now) use ($cfg_write) {
    $emailOn = !empty($cfg_now['email_verified_at']);
    $appOn   = !empty($cfg_now['totp_secret_b32_enc']) || !empty($cfg_now['totp_secret_b32']);
    if (!$emailOn && !$appOn) {
      $cfg_write([
        'backup_codes_sha256' => '__UNSET__',
        'backup_codes_ack'    => '__UNSET__',
        'backup_ack_at'       => '__UNSET__',
        '2fa_s'               => false,
        '2fa_method_s'        => '',
      ]);
    }
  };

  /* ---------- router(2) ---------- */
  $op2 = $_GET['op'] ?? $_POST['op'] ?? '';
  $p   = $jsonIn();

  switch ($op2) {
    /* ---- GENEL DURUM ---- */
    case 'status': {
      $c = $cfg_read();
      $email_verified = !empty($c['email_verified_at']);
      $app_linked     = !empty($c['totp_secret_b32_enc']) || !empty($c['totp_secret_b32']);
      $backup_has     = is_array($c['backup_codes_sha256'] ?? null) && count($c['backup_codes_sha256']) > 0;

      // tip güvenli 2fa_s normalizasyonu
      $twofa_on = false;
      if (isset($c['2fa_s'])) {
        $v = $c['2fa_s'];
        $twofa_on = ($v === true) || ($v === 1) ||
                    (is_string($v) && in_array(strtolower($v), ['1','true','on','yes'], true));
      }

      // 2FA tamamen kapalıysa method anlamsız
      $method = $twofa_on ? ($c['2fa_method_s'] ?? null) : null;

      $ok([
        'email_verified' => $email_verified,
        'app_linked'     => $app_linked,
        'backup_has'     => $backup_has,
        'method'         => $method,
        'twofa_on'       => $twofa_on,
        'available'      => [
          'email' => $email_verified,
          'app'   => $app_linked,
        ],
      ]);
    } break;

    /* ---- EMAIL ---- */
    case 'email_send': {
      $to = (string)($p['to'] ?? '');
      if ($to === '') { $err('email_required'); }
      if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $err('invalid_email');

      $now  = time();
      $last = $_SESSION['2fa_email_last_sent'] ?? 0;
      if ($now - $last < 60) $err('rate_limited');

      $digits = max(4, min(8, (int)($p['digits'] ?? 6)));
      $min = 10 ** ($digits-1); $max = (10 ** $digits) - 1;
      $code = (string)random_int($min, $max);
      $ttl  = max(60, min(1800, (int)($p['ttl'] ?? 600)));

      $key  = $twofa_hmac_key();
      $hash = hash_hmac('sha256', $code, $key);

      $_SESSION['2fa_email'] = ['hash'=>$hash,'exp'=>$now+$ttl,'tries'=>0,'email_address_s'=>$to];
      $_SESSION['2fa_email_last_sent'] = $now;

      $subject = 'Giriş Doğrulama Kodu';
      $body    = "Tek kullanımlık kodunuz: {$code}\nBu kod {$ttl} sn geçerlidir.";
      $sent = false;
      if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
          $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
          $m = new $phpMailerClass(true);
          $m->setFrom('no-reply@localhost','HAMU');
          $m->addAddress($to);
          $m->Subject = $subject;
          $m->Body    = $body;
          $sent = $m->send();
        } catch (Throwable $e) { $sent = false; }
      }
      if (!$sent) {
        $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($to, $subject, $body, $headers);
      }
      $ok(['to_masked'=>$mask_email($to),'ttl'=>$ttl,'digits'=>$digits]);
    } break;

    case 'email_verify': {
      $code = (string)($p['code'] ?? '');
      if ($code === '') $err('code_required');
      $sess = $_SESSION['2fa_email'] ?? null;
      if (!$sess || !is_array($sess)) $err('no_session');
      if (($sess['exp'] ?? 0) < time()) $err('expired');
      $_SESSION['2fa_email']['tries'] = (int)($sess['tries'] ?? 0) + 1;
      if ($_SESSION['2fa_email']['tries'] > 8) $err('too_many_attempts');

      $key  = $twofa_hmac_key();
      $hash = hash_hmac('sha256', $code, $key);
      if (!$safe_eq($hash, (string)$sess['hash'])) $err('invalid');

      // doğrulandı: config'e yaz
      $cfg = $cfg_write([
        'email_verified_at' => date('c'),
        '2fa_s'             => true,
        '2fa_method_s'      => 'email',
      ]);
      $ok(['verified'=>true, 'email'=>$mask_email($sess['email_address_s'] ?? '')]);
    } break;

    case 'email_disable': {
      $c = $cfg_read();
      $app_linked = !empty($c['totp_secret_b32_enc']) || !empty($c['totp_secret_b32']);

      if ($app_linked) {
        // E-posta kapandı ama uygulama 2FA açık: yöntem app olarak kalsın, 2FA açık
        $cfg = $cfg_write([
          'email_verified_at' => null,
          '2fa_method_s'      => 'app',
          '2fa_s'             => true,
        ]);
      } else {
        // Hiç 2FA kalmadı → tamamen kapat
        $cfg = $cfg_write([
          'email_verified_at' => null,
          '2fa_method_s'      => '',
          '2fa_s'             => false,
        ]);
      }
      $clear_when_all_off($cfg);
      $ok(['disabled'=>true]);
    } break;

    /* ---- APP (TOTP) ---- */
    case 'totp_enroll_start': {
      $issuer  = (string)($p['issuer']  ?? 'HAMU DevPanel');
      $account = (string)($p['account'] ?? 'user@local');
      $raw = random_bytes(20);
      $secret_b32 = $b32_encode($raw);
      $_SESSION['totp_enroll_secret_b32'] = $secret_b32;

      // otpauth URI
      $label = rawurlencode($issuer) . ':' . rawurlencode($account);
      $otpauth = "otpauth://totp/{$label}?secret={$secret_b32}&issuer=".rawurlencode($issuer)."&digits=6&period=30&algorithm=SHA1";
      $ok(['otpauth_uri'=>$otpauth,'secret_b32'=>$secret_b32]);
    } break;

    case 'totp_enroll_check': {
      $code = (string)($p['code'] ?? '');
      $secret_b32 = $_SESSION['totp_enroll_secret_b32'] ?? '';
      if ($code === '' || $secret_b32 === '') $err('missing');
      if (!$totp_verify_window($secret_b32, $code)) $err('invalid_code');
  
      // DEBUG: Önce mevcut config'i oku
      $current_cfg = $cfg_read();
      
      // Config yazma işlemini daha güvenli hale getir
      $cfg_updates = [
          'totp_secret_b32_enc' => encryptData($secret_b32),
          'totp_enrolled_at'    => date('c'),
          'use_auth_app'        => true,
          '2fa_s'               => true,
          '2fa_method_s'        => 'app',
      ];
      
      $new_cfg = $cfg_write($cfg_updates);
      
      // DEBUG: Yazma sonrası kontrol
      
      unset($_SESSION['totp_enroll_secret_b32']);
      $ok(['enrolled'=>true]);
  } break;

    case 'totp_disable': {
      unset($_SESSION['totp_enroll_secret_b32']);
      $cfg = $cfg_write([
        'use_auth_app'        => false,
        'totp_enrolled_at'    => null,
        'totp_secret_b32_enc' => null,
        'totp_secret_b32'     => '__UNSET__', // eski düz-metni de sil
      ]);

      $email_on = !empty($cfg['email_verified_at']);
      if ($email_on) {
        // e-posta 2FA duruyor
        $cfg = $cfg_write([
          '2fa_method_s' => 'email',
          '2fa_s'        => true,
        ]);
      } else {
        // hiçbir 2FA kalmadı
        $cfg = $cfg_write([
          '2fa_method_s' => '',
          '2fa_s'        => false,
        ]);
      }

      $clear_when_all_off($cfg);
      $ok(['disabled'=>true]);
    } break;

    /* ---- BACKUP ---- */
    case 'backup_list': {
      $c = $cfg_read();
      $arr   = $c['backup_codes_sha256'] ?? [];
      $count = is_array($arr) ? count($arr) : 0;
      $ok(['has' => $count>0, 'count'=>$count, 'codes'=>[]]); // kodlar asla dönmez
    } break;

    case 'backup_generate': {
      // geçici üret; kaydetme UI 'save' ile
      $n = max(5, min(20, (int)($p['n'] ?? 10)));
      $len = max(8, min(16, (int)($p['len'] ?? 10)));
      $codes = [];
      for ($i=0; $i<$n; $i++) {
        $raw = rtrim(strtr(base64_encode(random_bytes(8)), '+/', '9A'), '=');
        $code = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($raw)), 0, $len);
        $codes[] = $code;
      }
      $_SESSION['backup_proposed'] = $codes;
      $ok(['success'=>true,'codes'=>$codes]);
    } break;

    case 'backup_save': {
      $codes = $p['codes'] ?? ($_SESSION['backup_proposed'] ?? []);
      if (!is_array($codes) || !count($codes)) $err('no_codes');
      $hashes = []; foreach ($codes as $c) { $hashes[] = hash('sha256', $c); }
      $cfg = $cfg_write(['backup_codes_sha256'=>$hashes]);
      $cfg = $cfg_write(['backup_codes_ack'=>true, 'backup_ack_at'=>date('c')]);
      unset($_SESSION['backup_proposed']);
      $ok(['saved'=>true]);
    } break;

    case 'backup_ack': {
      $cfg = $cfg_write(['backup_codes_ack'=>true, 'backup_ack_at'=>date('c')]);
      $ok(['ack'=>true]);
    } break;

    case 'backup_clear': {
      $cfg = $cfg_write([
        'backup_codes_sha256' => '__UNSET__',
        'backup_codes_ack'    => '__UNSET__',
        'backup_ack_at'       => '__UNSET__',
      ]);
      $ok(['cleared'=>true]);
    } break;

    default: $err('unknown_op', 404);
  }
  exit;
}

/* ============================ Router seviye-2 (diğer API’ler) ============================ */
$allowed = [
  'db'       => dirname(__DIR__) . '/actions.php',
  'config'   => dirname(__DIR__) . '/config.php',
  'projects' => dirname(__DIR__) . '/projects.php',
];

if (!isset($allowed[$op])) {
  http_response_code(404);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['success'=>false,'error'=>'Not found']);
  exit;
}

/* normalize */
$_GET['_prj']  = $_GET['_prj']  ?? '1';
$_POST['_hamu_project'] = $_POST['_hamu_project'] ?? '1';

/* delege et */
require_once $allowed[$op];
