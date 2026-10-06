<?php
/**
 * HAMU DevPanel - Auth Control (GÜNCEL)
 * Konum: /.hamu/auth.php
 * Oturum/parola + 2FA (email/TOTP/Backup Codes) akışını yönetir.
 */

require_once __DIR__.'/config.php';
hamu_session();

require_once HAMU_DIR.'/lang.php';
require_once HAMU_DIR.'/functions.php';

if (!function_exists('log_session_event')) {
    function log_session_event(string $event, array $ctx = []): void {
        unset($ctx['sid']);
        $ua  = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 200);
        $ip  = (string)($_SERVER['REMOTE_ADDR'] ?? '-');
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '-');
        $line = sprintf(
            "[%s] ip=%s ua=%s event=%s uri=%s ctx=%s\n",
            date('Y-m-d H:i:s'), $ip, $ua, $event, $uri,
            json_encode($ctx, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        );
        @file_put_contents(HAMU_LOG.'/app_sessions.log', $line, FILE_APPEND);
    }
}

/* Şifre koruması kapalı ise sayfaya devam */
$authEnabled = (bool) config('ask_pass_s');
$storedHash  = (string) (config('app_pass_s') ?? '');
if (!$authEnabled || $storedHash === '') {
    return;
}

/* ===== 2FA konfig ===== */
$twoFAEnabled = (bool) config('2fa_s');
$twoFAMethod  = (string) (config('2fa_method_s') ?: ''); // "email" | "app" | ""
$twoFAEmail   = (string) (config('email_address_s') ?: '');

/* TOTP secret (şifreli → plain) */
$totpSecretB32Enc = (string) (config('totp_secret_b32_enc') ?: '');
$totpSecretB32 = '';
if ($totpSecretB32Enc !== '') {
    $dec = decryptData($totpSecretB32Enc);
    if (is_string($dec) && $dec !== '') {
        $totpSecretB32 = $dec;
    } else {
        // Şifreleme yoksa (ya da decrypt edilemediyse) ve değer base32 gibi görünüyorsa düz kabul et.
        if (preg_match('/^[A-Z2-7]+$/', $totpSecretB32Enc)) {
            $totpSecretB32 = $totpSecretB32Enc;
        }
    }
}

/* Durumlar */
$hasEmailVerified = (bool) config('email_verified_at');  // tarih/true ise e-posta kurulu kabul
$hasTotpEnrolled  = ($totpSecretB32 !== '');             // TOTP gerçekten var mı?

/**
 * Efektif yöntem seçimi:
 * 1) Radio seçimi geçerliyse (email/app) ve o yöntem gerçekten kuruluysa onu kullan.
 * 2) Değilse kurulu olana göre fallback (önce app, sonra email).
 * 3) Hiçbiri yoksa yöntem yok (2FA aşaması açılmaz).
 * NOT: use_auth_app BAYRAĞINA GÜVENME — yalnızca gerçek sinyaller.
 */
$effectiveMethod = '';
if ($twoFAEnabled) {
    if ($twoFAMethod === 'email' && $hasEmailVerified) {
        $effectiveMethod = 'email';
    } elseif ($twoFAMethod === 'app' && $hasTotpEnrolled) {
        $effectiveMethod = 'app';
    } else {
        if ($hasTotpEnrolled)       $effectiveMethod = 'app';
        elseif ($hasEmailVerified)  $effectiveMethod = 'email';
        else                        $effectiveMethod = '';
    }
}
if ($twoFAEnabled && $effectiveMethod === '') hamu_deny(503, 'Configured two-factor method unavailable');
$twoFAActive = ($twoFAEnabled && $effectiveMethod !== '');

/* ===== LOGOUT ===== */
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    log_session_event('logout', ['sid' => session_id()]);
    session_unset();
    session_destroy();
    hamu_session();
    session_regenerate_id(true);
    $clean = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (function_exists('ob_get_length') && ob_get_length()) @ob_clean();
    if (!headers_sent()) header('Location: '.$clean);
    exit;
}

/* ===== Timeout kontrolü ===== */
$SESSION_TTL = 3600;
if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $SESSION_TTL)) {
        log_session_event('timeout', ['sid' => session_id()]);
        session_unset();
        session_destroy();
        hamu_session();
    } else {
        $_SESSION['last_activity'] = time();
        return;
    }
}

/* ===== yardımcı: backup kod doğrulama & tüketme ===== */
function _backup_config_path_(): string {
    $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 1), '/');
    return $docroot.'/.hamu/cache/app_config.json';
}
function _consume_backup_code_(string $code): bool {
    $code = strtoupper(preg_replace('/[^A-Z0-9]/','', $code));
    if ($code === '') return false;
    $fp = _backup_config_path_();
    if (!is_file($fp) || !is_readable($fp)) return false;
    $j = json_decode(@file_get_contents($fp), true);
    if (!is_array($j)) return false;
    $list = isset($j['backup_codes_sha256']) && is_array($j['backup_codes_sha256']) ? $j['backup_codes_sha256'] : [];
    if (!$list) return false;
    $h  = hash('sha256', $code);
    $ix = array_search($h, $list, true);
    if ($ix === false) return false;
    // tek kullanımlık → sil
    array_splice($list, (int)$ix, 1);
    $j['backup_codes_sha256'] = array_values($list);
    // gösterilmiş say → ack
    $j['backup_codes_ack'] = true;
    $j['backup_ack_at']    = date('c');
    @file_put_contents($fp, json_encode($j, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    return true;
}

/* ===== Buraya geldik: ya giriş yok ya da 2FA aşaması ===== */
$error = null;

/* ---------------- Parola POST ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_pass_s']) && empty($_POST['twofa_code']) && empty($_POST['cancel2fa'])) {
    $input = (string) $_POST['app_pass_s'];
    if (password_verify($input, $storedHash)) {
        session_regenerate_id(true);

        if ($twoFAActive) {
            // 2FA aşamasına geç
            $_SESSION['2fa_pending']   = true;
            $_SESSION['2fa_method']    = $effectiveMethod; // "email" | "app"
            $_SESSION['last_activity'] = time();
            $_SESSION['sid_regen_at']  = time();
            log_session_event('login_pass_ok_wait_2fa', ['sid'=>session_id(),'m'=>$effectiveMethod]);

            // Sadece email akışında, ilk kez 2FA moduna girildiğinde 1 kere otomatik gönder
            if ($effectiveMethod === 'email') {
                if (empty($_SESSION['2fa_email']) || empty($_SESSION['2fa_email']['auto_sent'])) {
                    _send_email_code_($twoFAEmail);
                    $_SESSION['2fa_email']['auto_sent'] = 1;
                }
            }
        } else {
            // 2FA gerekmiyor → tamamla
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['sid_regen_at']  = time();
            log_session_event('login_ok', ['sid' => session_id()]);
            $regenAt = (int)($_SESSION['sid_regen_at'] ?? 0);
            if (time() - $regenAt > 300) {
                session_regenerate_id(true);
                $_SESSION['sid_regen_at'] = time();
            }
            $target = $_SERVER['REQUEST_URI'] ?? '/';
            if (function_exists('ob_get_length') && ob_get_length()) @ob_clean();
            if (!headers_sent()) header('Location: '.$target);
            exit;
        }
    } else {
        log_session_event('login_fail', ['sid' => session_id()]);
        $error = __l('pass_error');
    }
}

/* ------- 2FA iptal (şifre ekranına dön) ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel2fa'])) {
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_method'], $_SESSION['2fa_email']);
    log_session_event('login_2fa_cancel', ['sid'=>session_id()]);
    $error = null; // parola ekranına geri düş
}

/* ------- E-posta kod yardımcı: üret+gönder ------- */
function _send_email_code_(string $to): void {
    $digits = 6; $ttl = 300;
    $min = 10 ** ($digits-1); $max = (10 ** $digits) - 1;
    $code = (string) random_int($min, $max);
    $exp  = time() + $ttl;

    // Bu oturuma özel HMAC key
    $key  = hash('sha256', (session_id() ?: 'sess').'|hamu-2fa', true);
    $hash = hash_hmac('sha256', $code, $key);

    $autoSent = !empty($_SESSION['2fa_email']['auto_sent']) ? 1 : 0;
    $_SESSION['2fa_email'] = [
        'hash'      => $hash,
        'exp'       => $exp,
        'tries'                   => 0,
        'email_address_s'        => $to,
        'auto_sent' => $autoSent
    ];

    $subj = 'Giriş Doğrulama Kodu';
    $body = "Giriş Kodunuz: {$code}\n{$ttl} saniye geçerlidir.";

    $sent = false;
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
            $m = new $phpMailerClass(true);
            $m->setFrom('no-reply@localhost','HAMU');
            $m->addAddress($to ?: 'user@local');
            $m->Subject = $subj;
            $m->Body    = $body;
            $sent = $m->send();
        } catch (Throwable $e) { $sent = false; }
    }
    if (!$sent) {
        @mail($to ?: 'user@local', $subj, $body, "Content-Type: text/plain; charset=UTF-8\r\n");
    }
}

/* ------- E-posta 2FA resend ------- */
if (!empty($_SESSION['2fa_pending']) && ($_SESSION['2fa_method'] ?? '')==='email' && isset($_POST['resend'])) {
  _send_email_code_($twoFAEmail);
  $error = null;
}

/* ------- 2FA POST (kod doğrulama) ------- */
if (!empty($_SESSION['2fa_pending']) && isset($_POST['twofa_code'])) {
    $code   = trim((string)$_POST['twofa_code']);
    $method = (string)($_SESSION['2fa_method'] ?? '');
    $ok = false;

    // 1) Seçilen yönteme göre doğrula
    if ($method === 'email') {
        $ctx = $_SESSION['2fa_email'] ?? null;
        if ($ctx && ($ctx['exp'] ?? 0) >= time()) {
            $key  = hash('sha256', (session_id() ?: 'sess').'|hamu-2fa', true);
            $hash = hash_hmac('sha256', $code, $key);
            if (hash_equals($hash, (string)$ctx['hash'])) $ok = true;
        }
    } elseif ($method === 'app' && $totpSecretB32 !== '') {
        // window=0 → sadece o anki 30 sn kod
        $ok = totp_verify($totpSecretB32, $code, 30, 6, 'sha1', 0);
    }

    // 2) Olmadıysa yedek kod dene (tek kullanımlık)
    if (!$ok) {
        $ok = _consume_backup_code_($code);
        if ($ok) {
            log_session_event('login_2fa_backup_used', ['sid'=>session_id()]);
        }
    }

    if ($ok) {
        unset($_SESSION['2fa_pending'], $_SESSION['2fa_method'], $_SESSION['2fa_email']);
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['sid_regen_at']  = time();
        log_session_event('login_2fa_ok', ['sid' => session_id(), 'm'=>$method ?: 'backup']);

        $target = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        if (function_exists('ob_get_length') && ob_get_length()) @ob_clean();
        if (!headers_sent()) header('Location: '.$target);
        exit;
    } else {
        $error = __l('invalid_or_expired_code');
    }
}

/* ===== HTML ÇIKIŞ ===== */
if (!headers_sent()) {
    header('Content-Type: text/html; charset=' . __l('charset'));
}

$lang  = $_SESSION['hamu_lang'] ?? 'EN';
$theme = (config('theme_s') ? 'dark' : 'light'); // mevcut $theme yoksa güvenli varsayılan

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(strtolower($lang)) ?>" data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="utf-8">
  <title><?= __l('login')." - HAMU DevPanel" ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php if (config('bootstrap_s')): ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/<?= htmlspecialchars(config('bootstrap_version_s')?:'5.3.3') ?>/css/bootstrap.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/<?= htmlspecialchars(config('bootstrap_version_s')?:'5.3.3') ?>/js/bootstrap.bundle.min.js" defer></script>
  <?php endif; ?>
  <link rel="stylesheet" href="/?a=assets/css/hamu.main.min.css&v=1.0.0">
</head>
<body class="login">
  <div class="login-container">
    <div class="h-100 d-flex align-items-center justify-content-center">
      <?php $logoh = 50; include HAMU_DIR.'/assets/images/logo.svg'?>
    </div>
    <h2>DevPanel</h2>
    <hr>
    <p class="muted">
      <?php if (empty($_SESSION['2fa_pending'])): ?>
        <?= __l('pass_entry') ?>
      <?php else: ?>
        <?= __l('enter_2fa_code') ?>
      <?php endif; ?>
    </p>
    <?php if (!empty($error)): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (empty($_SESSION['2fa_pending'])): ?>
      <!-- Parola formu -->
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <div class="mb-2">
          <input class="inp" type="password" name="app_pass_s" placeholder="<?= __l('password') ?>" required>
        </div>
        <button class="btn" type="submit"><?= __l('login') ?></button>
      </form>
    <?php else: ?>
      <!-- 2FA formu -->
      <?php $m = (string)($_SESSION['2fa_method'] ?? 'email'); ?>
      <form method="post" autocomplete="off" id="twofaForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <div class="mb-2">
          <input class="inp" type="text" name="twofa_code" placeholder="<?= __l('enter_code') ?>" inputmode="numeric" autocomplete="one-time-code">
        </div>
        <div class="d-flex gap-2">
          <button class="btn" type="submit"><?= __l('verify') ?></button>
          <?php if ($m==='email'): ?>
            <button class="btn btn-secondary" name="resend" value="1" type="submit" formnovalidate id="btnResend"><?= __l('send_code') ?></button>
          <?php endif; ?>
          <button class="btn btn-outline-secondary" name="cancel2fa" value="1" type="submit" formnovalidate><?= __l('cancel') ?></button>
        </div>

        <?php if ($m==='email'): ?>
          <?php
            $exp = (int)($_SESSION['2fa_email']['exp'] ?? 0);
            $remain = max(0, $exp - time());
          ?>
          <div class="mt-2 small text-muted" id="twofaExpiryWrap">
            <?= __l('code_expires_in', '<span id="twofaCountdown">'.(int)$remain.'</span>') ?>
          </div>
          <script>
            (function(){
              var el  = document.getElementById('twofaCountdown');
              var btn = document.getElementById('btnResend');
              if(!el) return;
              var t = parseInt(el.textContent||'0',10)||0;
              if (btn && t>0) btn.setAttribute('disabled','disabled');
              var iv = setInterval(function(){
                if(t>0){
                  t--;
                  el.textContent = t;
                  if (t === 0 && btn) btn.removeAttribute('disabled');
                } else {
                  clearInterval(iv);
                }
              }, 1000);
            })();
          </script>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
exit;
