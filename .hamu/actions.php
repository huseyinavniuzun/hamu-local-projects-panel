<?php
/**
 * HAMU DevPanel - Database API
 * Location: /.hamu/actions.php
 * [TR] SQL Terminali için arka uç API'si. SQL sorgularını çalıştırır, veritabanı/tablo listelerini (autocomplete) ve şema bilgilerini (tooltip) sağlar.
 *      `USE`, `CREATE/DROP DATABASE` gibi özel komutları ve çoklu sorgu yürütmeyi destekler.
 * [EN] Backend API for the SQL Terminal. Executes SQL queries, provides database/table lists (for autocomplete) and schema information (for tooltips).
 *      Supports special commands like `USE`, `CREATE/DROP DATABASE`, and multiple statement execution.
 *
 * --- USAGE ---
 * API Endpoint: /?api=db
 *
 * Actions:
 * (POST) sql=<query> : Executes one or more SQL queries.
 * (GET) action=tooltip&database=<db_name> : Returns schema info for a database.
 * (GET) action=autocomplete&db=<active_db> : Returns DB and table names for autocomplete.
 * (POST) refresh_db=1 : Returns the current active DB and the list of all databases.
 */

/* -------------------------------------------------
 * Gerekli dosyalar
 * ------------------------------------------------- */
$requires = [
    __DIR__ . '/auth.php',       // Dil
    __DIR__ . '/database.php',   // DB helper'lar (buildDSN, getActiveDatabaseInfo, listDatabases, hamu_sqlite_dir, ...)  // Diğer fonksiyonlar (varsa)
];
$loaded = get_included_files();
foreach ($requires as $p) {
    if (is_file($p) && !in_array($p, $loaded, true)) {
        require_once $p;
    }
}

/* *****************************************
 * LOG YARDIMCI
 * *****************************************/
function writeLog($message) {
    $logDir = HAMU_LOG ;
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/db_terminal.log';
    $time = date('d.m.Y H:i:s');
    @file_put_contents($logFile, "[$time] $message\n", FILE_APPEND);
}

/* *****************************************
 * YARDIMCI FONKSİYONLAR (SQL TERMINAL)
 * *****************************************/

/** String literal içindeki ; işaretlerini dikkate alarak SQL’i cümlelere böler. */
function splitSqlStatements(string $sql): array {
    $out = [];
    $cur = '';
    $inStr = false;
    $q = '';
    $esc = false;
    $n = strlen($sql);

    for ($i=0; $i<$n; $i++) {
        $ch = $sql[$i];

        if ($inStr) {
            if ($esc) { $esc = false; $cur .= $ch; continue; }
            if ($ch === '\\') { $esc = true; $cur .= $ch; continue; }
            if ($ch === $q) { $inStr = false; }
            $cur .= $ch;
            continue;
        }

        if ($ch === '\'' || $ch === '"' || $ch === '`') {
            $inStr = true;
            $q = $ch;
            $cur .= $ch;
            continue;
        }

        if ($ch === ';') {
            $trim = trim($cur);
            if ($trim !== '') $out[] = $trim;
            $cur = '';
            continue;
        }

        $cur .= $ch;
    }

    $trim = trim($cur);
    if ($trim !== '') $out[] = $trim;
    return $out;
}

/** Dinamik SQL yürütme – tablo adıyla birlikte şema (db.table) gelirse o DB’ye ayrı DSN ile bağlanır. */
function executeSQL(string $sql, string $driver, PDO $defaultPDO) {
    if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
        if (preg_match('/^(CREATE|DROP|INSERT|UPDATE|DELETE|ALTER|TRUNCATE)\s+\S*\s*([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/i', $sql, $m)) {
            $parts = explode('.', $m[2], 2);
            if (count($parts) === 2) {
                $explicit = $parts[0];

                // SQLite için: dosya var mı? (Otomatik oluşturmayı engelle)
                if ($driver === 'sqlite') {
                    $dir  = function_exists('hamu_sqlite_dir') ? hamu_sqlite_dir() : (rtrim(HAMU_DIR,'/\\').'/sqlite');
                    $safe = preg_replace('/[^A-Za-z0-9_-]/','_', $explicit) . '.sqlite';
                    $fp   = rtrim($dir,'/\\') . '/' . $safe;
                    if (!is_file($fp)) {
                        throw new RuntimeException("SQLite DB bulunamadı: $safe");
                    }
                }

                $dsn = buildDSN($driver, $GLOBALS['db_server'], $explicit);
                $pdo = new PDO($dsn, $GLOBALS['db_user'], $GLOBALS['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                return $pdo->exec($sql);
            }
        }
    }
    return $defaultPDO->exec($sql);
}

/** Sunucudaki veritabanı listesinden ilkini döndürür. */
function getDefaultDatabase(array $dsnList) {
    $adi = getActiveDatabaseInfo($dsnList);
    if ($adi) {
        $dbs = listDatabases($adi['pdo'], $adi['driver']);
        if (!empty($dbs)) return $dbs[0];
    }
    return false;
}

/** Transaction kısayolu. */
function handleTransaction(string $sql, PDO $pdo): void {
    $cmd = strtoupper(trim(strtok($sql, " ")));
    try {
        if ($cmd === 'BEGIN') {
            $pdo->beginTransaction();
            echo "<div class='sql-message success'>" . __l('transaction_started') . "</div>";
        } elseif ($cmd === 'COMMIT') {
            $pdo->commit();
            echo "<div class='sql-message success'>" . __l('transaction_committed') . "</div>";
        } elseif ($cmd === 'ROLLBACK') {
            $pdo->rollBack();
            echo "<div class='sql-message success'>" . __l('transaction_rolledback') . "</div>";
        }
    } catch (PDOException $e) {
        writeLog("Transaction error: " . $e->getMessage());
        echo "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
    }
    exit;
}

/** Tek SQL sorgusu çalıştırır ve HTML döndürür. */
function runSingleQuery(string $query, array $dsnList, string $driver, string $host, string $user, string $pass): string {
    // explicit db (db.table) varsa yakala
    $explicit = '';
    if (!preg_match('/^\s*SELECT\s+/i', $query)) {
        if (preg_match('/^(CREATE|DROP|INSERT|UPDATE|DELETE|ALTER|TRUNCATE)\s+\S*\s*([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/i', $query, $m)) {
            $parts = explode('.', $m[2], 2);
            if (count($parts) === 2) $explicit = $parts[0];
        }
    }

    // aktif db
    $active = $_SESSION['selected_db'] ?? '';
    if ($active === '' && $explicit === '') {
        $adi = getActiveDatabaseInfo($dsnList);
        if ($adi) {
            $dbs = listDatabases($adi['pdo'], $adi['driver']);
            if (!empty($dbs)) {
                $active = $dbs[0];
                $_SESSION['selected_db'] = $active;
            } else {
                return "<div class='sql-message error'>" . __l('no_db_selected') . "</div>";
            }
        } else {
            return "<div class='sql-message error'>" . __l('noconn') . "</div>";
        }
    }
    if ($explicit !== '') $active = $explicit;
    if ($active === '') return "<div class='sql-message warning'>" . __l('no_db_selected') . "</div>";

    // SQLite ise dosya var mı? (new PDO çağrısından önce)
    if ($driver === 'sqlite') {
        $dir  = function_exists('hamu_sqlite_dir') ? hamu_sqlite_dir() : (rtrim(HAMU_DIR,'/\\').'/sqlite');
        $safe = preg_replace('/[^A-Za-z0-9_-]/','_', $active) . '.sqlite';
        $fp   = rtrim($dir,'/\\') . '/' . $safe;
        if (!is_file($fp)) {
            return "<div class='sql-message error'>Seçili SQLite veritabanı bulunamadı: " . htmlspecialchars($safe) . "</div>";
        }
    }

    // bağlan
    $dsn = buildDSN($driver, $host, $active);
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        // transaction komutları
        if (preg_match('/^\s*(BEGIN|COMMIT|ROLLBACK)\s*;?\s*$/i', $query)) {
            handleTransaction($query, $pdo);
        }

        if (preg_match('/^\s*SELECT\s+/i', $query)) {
            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) return "<div class='sql-message info'>" . __l('zero_results') . "</div>";

            $html = "<table class='table table-sm table-bordered'><thead><tr>";
            foreach (array_keys($rows[0]) as $col) $html .= "<th>" . htmlspecialchars($col) . "</th>";
            $html .= "</tr></thead><tbody>";
            foreach ($rows as $r) {
                $html .= "<tr>";
                foreach ($r as $cell) {
                    $html .= "<td>" . htmlspecialchars((string)$cell) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
            return $html;
        } else {
            $affected = $pdo->exec($query);
            $cmd = strtoupper(strtok(ltrim($query), " "));
            switch ($cmd) {
                case 'INSERT':   $msg = sprintf(__l('data_inserted'), $affected); break;
                case 'UPDATE':   $msg = sprintf(__l('data_updated'),  $affected); break;
                case 'ALTER':    $msg = sprintf(__l('alter_success'), $affected); break;
                case 'TRUNCATE': $msg = sprintf(__l('table_truncated'), $affected); break;
                case 'DROP':     $msg = sprintf(__l('data_dropped'),  $affected); break;
                case 'CREATE':   $msg = sprintf(__l('data_created'),  $affected); break;
                default:         $msg = sprintf(__l('query_success'), $affected);
            }
            return "<div class='sql-message success'>{$msg}</div>";
        }
    } catch (Throwable $e) {
        writeLog("Query error: {$e->getMessage()} [Query: $query]");
        return "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
    }
}

/* -------------------------------------------------
 * ORTAK: aktif sürücü / PDO / DSN list
 * ------------------------------------------------- */
if (!isset($dsnList)) { $dsnList = []; } // database.php sağlamazsa boş dizi
$activeDBInfo = getActiveDatabaseInfo($dsnList);
$driver = $activeDBInfo['driver'] ?? null;
$pdoMain = $activeDBInfo['pdo'] ?? null;

/* *****************************************
 * B) ROUTING
 * *****************************************/

/* (B-1) Refresh: Aktif veritabanı ve veritabanı listesini döndür. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_db'])) {
    if ($activeDBInfo) {
        $dbList = listDatabases($pdoMain, $driver);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "active_db" => $_SESSION['selected_db'] ?? "",
            "db_list"   => $dbList
        ], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "active_db" => "",
            "db_list"   => []
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/* (B-2) SQL Terminal */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {
    $sql = trim((string)$_POST['sql']);

    // Spam koruması (2 sn)
    if (isset($_SESSION['last_sql'], $_SESSION['last_sql_time'])
        && $_SESSION['last_sql'] === $sql
        && (time() - $_SESSION['last_sql_time'] < 2)) {
        echo "<div class='sql-message warning'>" . __l('sql_spam') . "</div>";
        exit;
    }
    $_SESSION['last_sql'] = $sql;
    $_SESSION['last_sql_time'] = time();

    if (!$activeDBInfo) {
        echo "<div class='sql-message error'>" . __l('noconn') . "</div>";
        exit;
    }

    $host = $GLOBALS['db_server'];
    $user = $GLOBALS['db_user'];
    $pass = $GLOBALS['db_pass'];

    // USE db;
    if (preg_match('/^\s*USE\s+([A-Za-z0-9_\-]+)\s*;?\s*$/i', $sql, $m)) {
        $requested = $m[1];

        $ok = false;
        if ($driver === 'sqlite') {
            // SQLite: dosya var mı?
            $dir  = function_exists('hamu_sqlite_dir') ? hamu_sqlite_dir() : (rtrim(HAMU_DIR,'/\\').'/sqlite');
            $safe = preg_replace('/[^A-Za-z0-9_-]/','_', $requested) . '.sqlite';
            $fp   = rtrim($dir,'/\\') . '/' . $safe;
            $ok   = is_file($fp);
        } else {
            // MySQL/PG/SQLSRV/OCI: metadata ile kontrol
            $ok = checkDatabaseExists($pdoMain, $driver, $requested);
        }

        if ($ok) {
            $_SESSION['selected_db'] = $requested;
            echo "<div class='sql-message info'>" . sprintf(__l('db_selected'), htmlspecialchars($requested)) . "</div>";
        } else {
            echo "<div class='sql-message warning'>" . sprintf(__l('db_not_found_fallback'), htmlspecialchars($requested)) . "</div>";
            $fallback = fallbackToLastDatabase($pdoMain, $driver);
            if ($fallback) {
                $_SESSION['selected_db'] = $fallback;
                echo "<div class='sql-message info'>" . sprintf(__l('db_fallback'), htmlspecialchars($fallback)) . "</div>";
            } else {
                echo "<div class='sql-message error'>" . __l('no_database_found') . "</div>";
            }
        }
        exit;
    }

    // CREATE/DROP DATABASE
    if (preg_match('/^\s*(CREATE|DROP)\s+DATABASE(?:\s+IF\s+(?:NOT\s+EXISTS|EXISTS))?\s+([A-Za-z0-9_\-]+)\s*;?\s*$/i', $sql, $m)) {
        $op = strtoupper($m[1]);
        $dbName = $m[2];

        try {
            if ($op === 'CREATE') {
                if ($driver === 'sqlite') {
                    // SQLite'ta CREATE DATABASE yok; dosya oluşturmayı burada bilinçli olarak desteklemiyoruz.
                    echo "<div class='sql-message error'>" . __l('create_db_not_supported') . "</div>";
                    exit;
                }
                if (checkDatabaseExists($pdoMain, $driver, $dbName)) {
                    echo "<div class='sql-message warning'>" . sprintf(__l('db_already_exists'), htmlspecialchars($dbName)) . "</div>";
                } else {
                    switch ($driver) {
                        case 'mysql': $pdoMain->exec("CREATE DATABASE `$dbName`"); break;
                        case 'pgsql': $pdoMain->exec("CREATE DATABASE \"$dbName\""); break;
                        default:
                            echo "<div class='sql-message error'>" . __l('create_db_not_supported') . "</div>";
                            exit;
                    }
                    $_SESSION['selected_db'] = $dbName;
                    echo "<div class='sql-message success'>" . sprintf(__l('db_created'), htmlspecialchars($dbName)) . "</div>";
                }
            } else { // DROP
                if ($driver === 'sqlite') {
                    echo "<div class='sql-message error'>" . __l('drop_db_not_supported') . "</div>";
                    exit;
                }
                if (!checkDatabaseExists($pdoMain, $driver, $dbName)) {
                    echo "<div class='sql-message warning'>" . sprintf(__l('db_not_exists'), htmlspecialchars($dbName)) . "</div>";
                } else {
                    switch ($driver) {
                        case 'mysql': $pdoMain->exec("DROP DATABASE `$dbName`"); break;
                        case 'pgsql': $pdoMain->exec("DROP DATABASE \"$dbName\""); break;
                        default:
                            echo "<div class='sql-message error'>" . __l('drop_db_not_supported') . "</div>";
                            exit;
                    }
                    echo "<div class='sql-message success'>" . sprintf(__l('db_dropped'), htmlspecialchars($dbName)) . "</div>";
                    if (($_SESSION['selected_db'] ?? '') === $dbName) {
                        $fallback = fallbackToLastDatabase($pdoMain, $driver);
                        if ($fallback) {
                            $_SESSION['selected_db'] = $fallback;
                            echo "<div class='sql-message info'>" . sprintf(__l('db_auto_fallback'), htmlspecialchars($fallback)) . "</div>";
                        } else {
                            $_SESSION['selected_db'] = '';
                            echo "<div class='sql-message warning'>" . __l('no_db_session_closed') . "</div>";
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            writeLog("CREATE/DROP DATABASE error: " . $e->getMessage());
            echo "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
        }
        exit;
    }

    // Çoklu sorgu çalıştır
    $queries = splitSqlStatements($sql);
    if (empty($queries)) {
        echo "<div class='sql-message warning'>" . __l('empty_query') . "</div>";
        exit;
    }

    $out = "";
    foreach ($queries as $q) {
        $out .= runSingleQuery($q, $dsnList, $driver, $host, $user, $pass) . "<hr/>";
    }
    echo $out;
    exit;
}

/* *****************************************
 * C) KALAN İŞLEMLER: Tooltip ve Autocomplete
 * *****************************************/

// Ortak: istemci cache'ini kapat
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === '') {
    echo __l('invalid_op');
    exit;
}

function hamu_json_output($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function hamu_safe_cache_path(string $baseName): string {
    $cacheDir = rtrim(HAMU_CACHE, '/\\');
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
    return $cacheDir . '/' . $baseName;
}

switch ($action) {
    /* -----------------------------
     * TOOLTIP: tablo/view/routine listesi (HTML)
     * ----------------------------- */
    case 'tooltip': {
        if (!$activeDBInfo) { echo __l('noconn'); exit; }

        $dbName = trim((string)($_POST['database'] ?? $_GET['database'] ?? ''));
        if ($dbName === '') { echo __l('db_not_selected'); exit; }

        // SQLite ise dosya kontrolü
        if ($driver === 'sqlite') {
            $dir  = function_exists('hamu_sqlite_dir') ? hamu_sqlite_dir() : (rtrim(HAMU_DIR,'/\\').'/sqlite');
            $safe = preg_replace('/[^A-Za-z0-9_-]/','_', $dbName) . '.sqlite';
            if (!is_file(rtrim($dir,'/\\').'/'.$safe)) {
                echo __l('db_not_selected'); // ya da anlamlı bir mesaj
                exit;
            }
        }

        $dsnNew = buildDSN($driver, $GLOBALS['db_server'], $dbName);

        try {
            $pdo = new PDO($dsnNew, $GLOBALS['db_user'], $GLOBALS['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $tables = $views = $procedures = $functions = [];

            switch ($driver) {
                case 'mysql':
                    // Tablolar + View'lar
                    $stmt = $pdo->query("SHOW FULL TABLES");
                    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                        if (strcasecmp($row[1] ?? '', 'BASE TABLE') === 0) {
                            $tables[] = $row[0];
                        } elseif (strcasecmp($row[1] ?? '', 'VIEW') === 0) {
                            $views[] = $row[0];
                        }
                    }
                    // Rutinler
                    $stmt = $pdo->prepare("
                        SELECT ROUTINE_NAME, ROUTINE_TYPE
                          FROM information_schema.ROUTINES
                         WHERE ROUTINE_SCHEMA = :schema
                    ");
                    $stmt->execute([':schema' => $dbName]);
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (($r['ROUTINE_TYPE'] ?? '') === 'PROCEDURE') $procedures[] = $r['ROUTINE_NAME'];
                        if (($r['ROUTINE_TYPE'] ?? '') === 'FUNCTION')  $functions[]  = $r['ROUTINE_NAME'];
                    }
                    break;

                        case 'sqlite':
                              // Tablolar/Views
                              $stmt = $pdo->query("SELECT name, type FROM sqlite_master WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' ORDER BY type,name");
                              while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if (($r['type'] ?? '') === 'table') $tables[] = $r['name'];
                                if (($r['type'] ?? '') === 'view')  $views[]  = $r['name'];
                              }
                              // Kolon bilgisi gerekiyorsa PRAGMA table_info('tbl')
                              // örn: bir tablo seçilmişse:
                              // $cols = $pdo->query("PRAGMA table_info(" . $pdo->quote($tbl) . ")")->fetchAll(PDO::FETCH_ASSOC);
                              break;

                case 'pgsql':
                    $stmt = $pdo->query("
                        SELECT table_name, table_type
                          FROM information_schema.tables
                         WHERE table_schema = 'public'
                         ORDER BY table_type, table_name
                    ");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (strcasecmp($r['table_type'] ?? '', 'BASE TABLE') === 0) $tables[] = $r['table_name'];
                        elseif (strcasecmp($r['table_type'] ?? '', 'VIEW') === 0)   $views[]  = $r['table_name'];
                    }
                    break;

                case 'sqlsrv':
                    $stmt = $pdo->query("
                        SELECT TABLE_NAME, TABLE_TYPE
                          FROM INFORMATION_SCHEMA.TABLES
                         WHERE TABLE_TYPE IN ('BASE TABLE','VIEW')
                         ORDER BY TABLE_TYPE, TABLE_NAME
                    ");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (($r['TABLE_TYPE'] ?? '') === 'BASE TABLE') $tables[] = $r['TABLE_NAME'];
                        if (($r['TABLE_TYPE'] ?? '') === 'VIEW')       $views[]  = $r['TABLE_NAME'];
                    }
                    $stmt = $pdo->query("
                        SELECT SPECIFIC_NAME, ROUTINE_TYPE
                          FROM INFORMATION_SCHEMA.ROUTINES
                         WHERE ROUTINE_SCHEMA = 'dbo'
                    ");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (($r['ROUTINE_TYPE'] ?? '') === 'PROCEDURE') $procedures[] = $r['SPECIFIC_NAME'];
                        if (($r['ROUTINE_TYPE'] ?? '') === 'FUNCTION')  $functions[]  = $r['SPECIFIC_NAME'];
                    }
                    break;

                case 'oci':
                    $stmt = $pdo->query("SELECT table_name FROM user_tables ORDER BY table_name");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $tables[] = $r['table_name'];

                    $stmt = $pdo->query("SELECT view_name FROM user_views ORDER BY view_name");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $views[] = $r['view_name'];

                    $stmt = $pdo->query("
                        SELECT object_name, object_type
                          FROM user_objects
                         WHERE object_type IN ('PROCEDURE','FUNCTION')
                         ORDER BY object_type, object_name
                    ");
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (($r['object_type'] ?? '') === 'PROCEDURE') $procedures[] = $r['object_name'];
                        if (($r['object_type'] ?? '') === 'FUNCTION')  $functions[]  = $r['object_name'];
                    }
                    break;
            }

            // HTML çıktısı
            $out = [];
            if (!empty($tables))     $out[] = "<b>" . __l('tables')     . ":</b> " . implode(", ", array_map('htmlspecialchars', $tables));
            if (!empty($views))      $out[] = "<b>" . __l('views')      . ":</b> " . implode(", ", array_map('htmlspecialchars', $views));
            if (!empty($procedures)) $out[] = "<b>" . __l('procedures') . ":</b> " . implode(", ", array_map('htmlspecialchars', $procedures));
            if (!empty($functions))  $out[] = "<b>" . __l('functions')  . ":</b> " . implode(", ", array_map('htmlspecialchars', $functions));

            header('Content-Type: text/html; charset=utf-8');
            echo $out ? implode("<br>", $out) : __l('notable');
        } catch (Throwable $e) {
            writeLog("Tooltip error: " . $e->getMessage());
            header('Content-Type: text/plain; charset=utf-8');
            echo __l('error') . ' ' . htmlspecialchars($e->getMessage());
        }
        exit;
    }

    /* -----------------------------
     * AUTOCOMPLETE: veritabanları + (seçili DB varsa) tablolar
     * ----------------------------- */
    case 'autocomplete': {
        if (!$activeDBInfo) hamu_json_output([]);

        $selectedDb = trim((string)($_POST['db'] ?? $_GET['db'] ?? ''));
        $allDatabases = listDatabases($pdoMain, $driver);
        $tables = [];

        // Kısa süreli cache
        $ttl = 5;
        $cacheFile = hamu_safe_cache_path("cache_autocomplete.json");
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            header('Content-Type: application/json; charset=utf-8');
            readfile($cacheFile);
            exit;
        }

        if ($selectedDb !== '') {
            // SQLite ise dosya yoksa bağlanma
            if ($driver === 'sqlite') {
                $dir  = function_exists('hamu_sqlite_dir') ? hamu_sqlite_dir() : (rtrim(HAMU_DIR,'/\\').'/sqlite');
                $safe = preg_replace('/[^A-Za-z0-9_-]/','_', $selectedDb) . '.sqlite';
                if (!is_file(rtrim($dir,'/\\').'/'.$safe)) {
                    // sadece veritabanı listesi döner
                    $tables = [];
                } else {
                    $dsnNew = buildDSN($driver, $GLOBALS['db_server'], $selectedDb);
                    try {
                        $pdoDb = new PDO($dsnNew, $GLOBALS['db_user'], $GLOBALS['db_pass'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        ]);
                        $stmt = $pdoDb->query("SELECT name FROM sqlite_master WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' ORDER BY name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $tables[] = $row['name'];
                    } catch (Throwable $e) {
                        writeLog("Autocomplete error (sqlite): " . $e->getMessage());
                    }
                }
            } else {
                $dsnNew = buildDSN($driver, $GLOBALS['db_server'], $selectedDb);
                try {
                    $pdoDb = new PDO($dsnNew, $GLOBALS['db_user'], $GLOBALS['db_pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    switch ($driver) {
                        case 'mysql':
                            $stmt = $pdoDb->query("SHOW TABLES");
                            while ($row = $stmt->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];
                            break;
                        case 'pgsql':
                            $stmt = $pdoDb->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $tables[] = $row['table_name'];
                            break;
                        case 'sqlsrv':
                            $stmt = $pdoDb->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE IN ('BASE TABLE','VIEW')");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $tables[] = $row['TABLE_NAME'];
                            break;
                        case 'oci':
                            $stmt = $pdoDb->query("SELECT table_name AS name FROM user_tables UNION ALL SELECT view_name AS name FROM user_views");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $tables[] = $row['name'];
                            break;
                    }
                } catch (Throwable $e) {
                    writeLog("Autocomplete error: " . $e->getMessage());
                }
            }
        }

        $final = array_values(array_unique(array_merge($allDatabases, $tables)));
        $json = json_encode($final, JSON_UNESCAPED_UNICODE);

        if ($json !== false) {
            $tmp = $cacheFile . '.tmp';
            @file_put_contents($tmp, $json, LOCK_EX);
            @rename($tmp, $cacheFile);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo $json !== false ? $json : '[]';
        exit;
    }

    default:
        echo __l('unknown_op');
        exit;
}