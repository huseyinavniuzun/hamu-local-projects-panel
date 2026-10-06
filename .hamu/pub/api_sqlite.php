<?php
@header('Content-Type: application/json; charset=UTF-8');
@header('X-Content-Type-Options: nosniff');

// Sadece POST + JSON
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) { http_response_code(400); echo json_encode(['error'=>'bad_json']); exit; }

// Paylaşılan gizli anahtar (ENV veya güvenli dosya)
$SECRET = getenv('HAMU_API_SECRET'); // .env içine koy
if (!$SECRET) { http_response_code(500); echo json_encode(['error'=>'no_secret']); exit; }

// HMAC doğrulama (Authorization: HMAC <base64sig> + X-Timestamp)
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$ts   = (int)($_SERVER['HTTP_X_TIMESTAMP'] ?? 0);
if (!preg_match('/^HMAC\s+(.+)$/i', $auth, $m)) { http_response_code(401); echo json_encode(['error'=>'no_auth']); exit; }
$sig = trim($m[1]);
if (abs(time() - $ts) > 300) { http_response_code(401); echo json_encode(['error'=>'ts_skew']); exit; } // 5 dk pencere
$calc = base64_encode(hash_hmac('sha256', $raw.$ts, $SECRET, true));
if (!hash_equals($calc, $sig)) { http_response_code(401); echo json_encode(['error'=>'bad_sig']); exit; }

// Proje/DB seçimi
$project = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)($in['project'] ?? 'default'));
$baseDir = dirname(__DIR__).'/data/'.$project;
$dbFile  = $baseDir.'/data.sqlite';
if (!is_dir($baseDir)) { @mkdir($baseDir, 0700, true); }
try {
  $pdo = new PDO('sqlite:'.$dbFile, null, null, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);
  $pdo->exec('PRAGMA foreign_keys=ON;');
  $pdo->exec('PRAGMA busy_timeout=5000;');
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['error'=>'db_open_failed']); exit;
}

// Whitelist’li işlemler
$op = (string)($in['op'] ?? '');
try {
  switch ($op) {

    case 'insert_customer': {
      $name  = trim((string)($in['name']  ?? ''));
      $email = trim((string)($in['email'] ?? ''));
      if ($name === '' || $email === '') { http_response_code(400); echo json_encode(['error'=>'missing']); exit; }
      $pdo->prepare('CREATE TABLE IF NOT EXISTS customers(id INTEGER PRIMARY KEY, name TEXT, email TEXT UNIQUE)')->execute();
      $st = $pdo->prepare('INSERT INTO customers(name,email) VALUES(:n,:e)');
      $st->execute([':n'=>$name, ':e'=>$email]);
      echo json_encode(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]); 
      break;
    }

    case 'update_order_status': {
      $id = (int)($in['id'] ?? 0);
      $st = (string)($in['status'] ?? '');
      if ($id<=0 || $st==='') { http_response_code(400); echo json_encode(['error'=>'missing']); exit; }
      $stt = $pdo->prepare('UPDATE orders SET status=:s WHERE id=:i');
      $stt->execute([':s'=>$st, ':i'=>$id]);
      echo json_encode(['ok'=>true, 'affected'=>$stt->rowCount()]);
      break;
    }

    case 'bulk_upsert_prices': {
      $rows = $in['rows'] ?? null;
      if (!is_array($rows)) { http_response_code(400); echo json_encode(['error'=>'missing_rows']); exit; }
      $pdo->exec('CREATE TABLE IF NOT EXISTS prices (sku TEXT PRIMARY KEY, price REAL)');
      $pdo->beginTransaction();
      $st = $pdo->prepare('INSERT INTO prices(sku,price) VALUES(:sku,:p) ON CONFLICT(sku) DO UPDATE SET price=excluded.price');
      $n=0; foreach ($rows as $r) {
        $sku = isset($r['sku']) ? (string)$r['sku'] : '';
        $pr  = isset($r['price']) ? (float)$r['price'] : null;
        if ($sku==='' || $pr===null) continue;
        $st->execute([':sku'=>$sku, ':p'=>$pr]); $n += $st->rowCount();
      }
      $pdo->commit();
      echo json_encode(['ok'=>true, 'affected'=>$n]);
      break;
    }

    default:
      http_response_code(400);
      echo json_encode(['error'=>'bad_op','allowed'=>['insert_customer','update_order_status','bulk_upsert_prices']]);
  }
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>'write_failed']);
}
