<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$password = getenv('HAMU_SETUP_PASSWORD') ?: '';
if (strlen($password) < 16) { fwrite(STDERR, "HAMU_SETUP_PASSWORD must contain at least 16 characters.\n"); exit(1); }
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
if (!is_file(__DIR__.'/.hamu/.env')) file_put_contents(__DIR__.'/.hamu/.env', 'HAMU_MASTER_KEY_B64='.base64_encode(random_bytes(32)).PHP_EOL, LOCK_EX);
require __DIR__.'/.hamu/config.php';
$config['ask_pass_s'] = true;
$config['app_pass_s'] = password_hash($password, PASSWORD_DEFAULT);
$config['first_setup'] = false;
writeJsonAtomic($CONFIG_FP, $config);
echo "Administrator configured. Never copy local credentials or databases to the public repository.\n";
