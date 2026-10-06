<?php
/**
 * Quick SQLite try page
 * Kaydet: /sqlite_try.php
 * URL   : /sqlite_try.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/html; charset=utf-8');

$page_title      = "SqLite Manager";                // Sayfa Başlığı
$body_class      = "";                        // <body> tagı CSS stili
$include_db      = 0;                         // 0 = hayır, 1 = evet
$menu_type       = 1;                         // 0 = Sadece mobil, 1 = Her ekranda gösteriliyor
$side_bar        = 1;                         // 0 = sidebar ekleme, 1 = sidebar ekle
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/header.php'; // Header


$DOCROOT = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/\\');
$DIR     = $DOCROOT . '/.hamu/sqlite';
$DBFILE  = $DIR . '/demo_app.sqlite';

// 1) Klasör garanti
if (!is_dir($DIR)) @mkdir($DIR, 0775, true);

// 2) İlk kurulum (db yoksa oluştur + örnek veri)
$initDone = false;
try {
  $needInit = !is_file($DBFILE);
  $pdo = new PDO('sqlite:' . $DBFILE);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($needInit) {
    $pdo->exec("
      PRAGMA journal_mode=WAL;
      CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sku TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        price REAL NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
      );
      INSERT INTO products (sku,name,price) VALUES
        ('SKU-1001','Kahve Kupası',149.90),
        ('SKU-1002','Mekanik Klavye',2399.00),
        ('SKU-1003','Type-C Kablo',129.00);
    ");
    $initDone = true;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "<pre>SQLite init error: ".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}

// 3) Basit sorgu çalıştırma (demo amaçlı)
// Güvenlik notu: bu dosya prod’da açık kalmasın. Yalnızca lokal test için.
$resultHtml = '';
$defaultSql = "SELECT id, sku, name, price, created_at FROM products ORDER BY id DESC;";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = trim((string)($_POST['sql'] ?? ''));
  if ($sql !== '') {
    try {
      // Çok basit bir koruma: tek statement ve SELECT/PRAGMA/EXPLAIN dışında engelle
      // (test için yeterli; prod için tam SQL editörünü kullanın)
      if (preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $sql) !== 1) {
        throw new RuntimeException('Sadece SELECT/PRAGMA/EXPLAIN izinli (demo).');
      }

      $stmt = $pdo->query($sql);
      $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
      if (!$rows) {
        $resultHtml = "<div class='alert alert-info'>Sıfır satır.</div>";
      } else {
        $head = array_keys($rows[0]);
        $resultHtml = "<div class='table-responsive'><table class='table table-sm table-bordered align-middle'><thead><tr>";
        foreach ($head as $h) $resultHtml .= "<th>".htmlspecialchars($h)."</th>";
        $resultHtml .= "</tr></thead><tbody>";
        foreach ($rows as $r) {
          $resultHtml .= "<tr>";
          foreach ($head as $h) {
            $resultHtml .= "<td>".htmlspecialchars((string)$r[$h])."</td>";
          }
          $resultHtml .= "</tr>";
        }
        $resultHtml .= "</tbody></table></div>";
      }
    } catch (Throwable $e) {
      $resultHtml = "<div class='alert alert-danger'>Hata: ".htmlspecialchars($e->getMessage())."</div>";
    }
  }
}
?>
<div class="container-module">
<div class="content-medium py-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">SQLite Quick Try</h3>
    <span class="badge bg-secondary">/.hamu/sqlite/demo_app.sqlite</span>
  </div>

  <?php if ($initDone): ?>
    <div class="alert alert-success py-2">Demo veritabanı oluşturuldu ve örnek veriler eklendi.</div>
  <?php endif; ?>

  <form method="post" class="card shadow-sm mb-3">
    <div class="card-body">
      <label class="form-label">SQL</label>
      <textarea name="sql" class="form-control" rows="5" placeholder="SELECT ..."><?=
        htmlspecialchars($_POST['sql'] ?? $defaultSql)
      ?></textarea>
      <div class="form-text">Bu demo’da sadece SELECT/PRAGMA/EXPLAIN komutlarına izin verildi.</div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-primary"><i class="fa fa-play me-1"></i> Çalıştır</button>
    </div>
  </form>

  <?php if ($resultHtml): ?>
    <div class="card shadow-sm">
      <div class="card-header">Sonuç</div>
      <div class="card-body">
        <?= $resultHtml ?>
      </div>
    </div>
  <?php endif; ?>
</div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/footer.php';?>

