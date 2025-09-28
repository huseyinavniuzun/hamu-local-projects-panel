<?php
/**
 * db_actions.php
 *
 * Terminal sorguları, veritabanı listeleme, tooltip, autocomplete ve
 * transaction yönetimi gibi işlemleri gerçekleştiren birleşik dosya.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/lang.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/functions.php';

/* *****************************************
 * LOG YARDIMCI FONKSİYONU
 * *****************************************/
function writeLog($message) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/db_terminal.log';
    $time = date('Y-m-d H:i:s');
    $logMessage = "[$time] " . $message . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/* *****************************************
 * A) YARDIMCI FONKSİYONLAR (SQL TERMINAL)
 * *****************************************/

// SQL sorgusunu, noktalı virgül (;) karakterine göre, string literal içindekileri göz ardı ederek böler.
function splitSqlStatements($sql) {
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    $escaped = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        if ($inString) {
            if ($escaped) {
                $escaped = false;
                $currentStatement .= $char;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                $currentStatement .= $char;
                continue;
            }
            if ($char === $stringChar) {
                $inString = false;
            }
            $currentStatement .= $char;
        } else {
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
                $currentStatement .= $char;
            } elseif ($char === ';') {
                $trimmed = trim($currentStatement);
                if (!empty($trimmed)) {
                    $statements[] = $trimmed;
                }
                $currentStatement = '';
            } else {
                $currentStatement .= $char;
            }
        }
    }
    $trimmed = trim($currentStatement);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }
    return $statements;
}

function updateActiveDatabaseFromPost() {
    if (!empty($_POST['active_db'])) {
        $_SESSION['selected_db'] = trim($_POST['active_db']);
    }
}

function ensureActiveDatabase($dsnList) {
    if (!empty($_SESSION['selected_db'])) {
        return $_SESSION['selected_db'];
    }
    $default = getDefaultDatabase($dsnList);
    if ($default !== false) {
        $_SESSION['selected_db'] = $default;
        return $default;
    }
    return "";
}

// Dinamik SQL sorguları için (kullanıcıdan gelen sorgular) hazır ifadeler tam uygulanamıyor;
// bu yüzden kontrol edilen bölümlerde prepared statement kullanıyoruz (örn. checkDatabaseExists).
function executeSQL($sql, $driver, $defaultPDO) {
    if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
        if (preg_match('/^(CREATE|DROP|INSERT|UPDATE|DELETE|ALTER|TRUNCATE)\s+\S*\s*([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/i', $sql, $m)) {
            $parts = explode('.', $m[2]);
            if (count($parts) == 2) {
                $explicit_db = $parts[0];
                $dsn = buildDSN($driver, $GLOBALS['db_server'], $explicit_db);
                $pdoNew = new PDO($dsn, $GLOBALS['db_user'], $GLOBALS['db_pass']);
                $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdoNew->setAttribute(PDO::ATTR_TIMEOUT, 5); // Performans için zaman aşımı
                return $pdoNew->exec($sql);
            }
        }
    }
    return $defaultPDO->exec($sql);
}

function getDefaultDatabase($dsnList) {
    $activeDBInfo = getActiveDatabaseInfo($dsnList);
    if ($activeDBInfo) {
        $dbList = listDatabases($activeDBInfo['pdo'], $activeDBInfo['driver']);
        if (!empty($dbList)) {
            return $dbList[0];
        }
    }
    return false;
}

function usedDatabaseMessage($objectName) {
    if (strpos($objectName, '.') !== false) {
        list($explicit_db, $dummy) = explode('.', $objectName, 2);
        return sprintf(" (" . __l('used_explicit_db') . ")", htmlspecialchars($explicit_db));
    } else {
        return sprintf(" (" . __l('used_session_db') . ")", htmlspecialchars($_SESSION['selected_db'] ?? '---'));
    }
}

// Basit transaction yönetimi: BEGIN, COMMIT, ROLLBACK
function handleTransaction($sql, $pdo) {
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

// Tek sorguyu çalıştırır. SELECT sorguları sonuçları tabloya döner, diğerleri etkilenen satır sayısı ile mesaj verir.
function runSingleQuery($query) {
    global $dsnList;

    $explicit_db = '';
    if (!preg_match('/^\s*SELECT\s+/i', $query)) {
        if (preg_match('/^(CREATE|DROP|INSERT|UPDATE|DELETE|ALTER|TRUNCATE)\s+\S*\s*([a-zA-Z0-9_]+\.[a-zA-Z0-9_]+)/i', $query, $m)) {
            $parts = explode('.', $m[2]);
            if (count($parts) == 2) {
                $explicit_db = $parts[0];
            }
        }
    }
    $active_db = $_SESSION['selected_db'] ?? '';
    if (empty($active_db) && empty($explicit_db)) {
        $activeDBInfo = getActiveDatabaseInfo($dsnList);
        if ($activeDBInfo) {
            $fallback = fallbackToLastDatabase($activeDBInfo['pdo'], $activeDBInfo['driver']);
            if (!$fallback) {
                $dbList = listDatabases($activeDBInfo['pdo'], $activeDBInfo['driver']);
                if (!empty($dbList)) {
                    $fallback = $dbList[0];
                }
            }
            if ($fallback) {
                $_SESSION['selected_db'] = $fallback;
                $active_db = $fallback;
            } else {
                return "<div class='sql-message error'>" . __l('no_db_selected') . "</div>";
            }
        } else {
            return "<div class='sql-message error'>" . __l('noconn') . "</div>";
        }
    }
    if (!empty($explicit_db)) {
        $active_db = $explicit_db;
    }
    if (!$active_db) {
        return "<div class='sql-message warning'>" . __l('no_db_selected') . "</div>";
    }

    $activeDBInfo = getActiveDatabaseInfo($dsnList);
    if (!$activeDBInfo) {
        return "<div class='sql-message error'>" . __l('noconn') . "</div>";
    }

    $driver = $activeDBInfo['driver'];
    $host   = $GLOBALS['db_server'];
    $user   = $GLOBALS['db_user'];
    $pass   = $GLOBALS['db_pass'];
    $dsnNew = buildDSN($driver, $host, $active_db);

    try {
        $pdoNew = new PDO($dsnNew, $user, $pass);
        $pdoNew->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdoNew->setAttribute(PDO::ATTR_TIMEOUT, 5); // Zaman aşımı

        // Transaction komutlarını kontrol edelim
        if (preg_match('/^\s*(BEGIN|COMMIT|ROLLBACK)\s*;?\s*$/i', $query)) {
            handleTransaction($query, $pdoNew);
        }

        if (preg_match('/^\s*SELECT\s+/i', $query)) {
            $stmt = $pdoNew->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                return "<div class='sql-message info'>" . __l('zero_results') . "</div>";
            }
            $html = "<table class='table table-sm table-bordered'><thead><tr>";
            foreach (array_keys($rows[0]) as $col) {
                $html .= "<th>" . htmlspecialchars($col) . "</th>";
            }
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
            $affected = $pdoNew->exec($query);
            // İşlem türüne göre mesajı seçelim:
            $trimmed = ltrim($query);
            $cmd = strtoupper(strtok($trimmed, " "));
            $message = "";
            switch ($cmd) {
                case 'INSERT':
                    $message = sprintf(__l('data_inserted'), $affected);
                    break;
                case 'UPDATE':
                    $message = sprintf(__l('data_updated'), $affected);
                    break;
                case 'ALTER':
                    $message = sprintf(__l('alter_success'), $affected);
                    break;
                case 'TRUNCATE':
                    $message = sprintf(__l('table_truncated'), $affected);
                    break;
                case 'DROP':
                    // DROP TABLE veya DROP VIEW
                    $message = sprintf(__l('data_dropped'), $affected);
                    break;
                case 'CREATE':
                    // CREATE TABLE veya CREATE VIEW veya CREATE DATABASE
                    $message = sprintf(__l('data_created'), $affected);
                    break;
                default:
                    $message = sprintf(__l('query_success'), $affected);
            }
            return "<div class='sql-message success'>" . $message . "</div>";
        }
    } catch (PDOException $e) {
        writeLog("Query error in runSingleQuery: " . $e->getMessage() . " [Query: " . $query . "]");
        return "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
    }
}

/* *****************************************
 * B) YÖNLENDİRME (Routing)
 * *****************************************/

// (B-1) Refresh: Aktif veritabanı ve veritabanı listesini döndür.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_db'])) {
    $activeDBInfo = getActiveDatabaseInfo($dsnList);
    if ($activeDBInfo) {
        $pdo = $activeDBInfo['pdo'];
        $driver = $activeDBInfo['driver'];
        $dbList = listDatabases($pdo, $driver);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            "active_db" => $_SESSION['selected_db'] ?? "",
            "db_list"   => $dbList
        ));
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            "active_db" => "",
            "db_list"   => array()
        ));
    }
    exit;
}

// (B-2) SQL Sorgusu: Terminal işlevleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {
    $sql = trim($_POST['sql']);

    // Spam koruması
    if (isset($_SESSION['last_sql']) && $_SESSION['last_sql'] === $sql &&
        isset($_SESSION['last_sql_time']) && (time() - $_SESSION['last_sql_time'] < 2)) {
        echo "<div class='sql-message warning'>" . __l('sql_spam') . "</div>";
        exit;
    }
    $_SESSION['last_sql'] = $sql;
    $_SESSION['last_sql_time'] = time();

    $activeDBInfo = getActiveDatabaseInfo($dsnList);
    if (!$activeDBInfo) {
        echo "<div class='sql-message error'>" . __l('noconn') . "</div>";
        exit;
    }
    $driver = $activeDBInfo['driver'];
    $pdo    = $activeDBInfo['pdo'];

    // USE sorgusu
    if (preg_match('/^\s*USE\s+([a-zA-Z0-9_]+)\s*;?\s*$/i', $sql, $m)) {
        $requested_db = $m[1];
        if (checkDatabaseExists($pdo, $driver, $requested_db)) {
            $_SESSION['selected_db'] = $requested_db;
            echo "<div class='sql-message info'>" . sprintf(__l('db_selected'), htmlspecialchars($requested_db)) . "</div>";
        } else {
            echo "<div class='sql-message warning'>" . sprintf(__l('db_not_found_fallback'), htmlspecialchars($requested_db)) . "</div>";
            $fallback = fallbackToLastDatabase($pdo, $driver);
            if ($fallback) {
                $_SESSION['selected_db'] = $fallback;
                echo "<div class='sql-message info'>" . sprintf(__l('db_fallback'), htmlspecialchars($fallback)) . "</div>";
            } else {
                echo "<div class='sql-message error'>" . __l('no_database_found') . "</div>";
            }
        }
        exit;
    }

    // CREATE DATABASE sorgusu – noktalı virgül opsiyonu
    if (preg_match('/^\s*CREATE\s+DATABASE(?:\s+IF\s+NOT\s+EXISTS)?\s+([a-zA-Z0-9_]+)\s*;?\s*$/i', $sql, $m)) {
        $dbName = $m[1];
        if (checkDatabaseExists($pdo, $driver, $dbName)) {
            echo "<div class='sql-message warning'>" . sprintf(__l('db_already_exists'), htmlspecialchars($dbName)) . "</div>";
        } else {
            try {
                switch ($driver) {
                    case 'mysql':
                        $pdo->exec("CREATE DATABASE `$dbName`");
                        break;
                    case 'pgsql':
                        $pdo->exec("CREATE DATABASE \"$dbName\"");
                        break;
                    default:
                        echo "<div class='sql-message error'>" . __l('create_db_not_supported') . "</div>";
                        exit;
                }
                $_SESSION['selected_db'] = $dbName;
                echo "<div class='sql-message success'>" . sprintf(__l('db_created'), htmlspecialchars($dbName)) . "</div>";
            } catch (PDOException $e) {
                writeLog("Error in CREATE DATABASE: " . $e->getMessage());
                echo "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
            }
        }
        exit;
    }

    // DROP DATABASE sorgusu – noktalı virgül opsiyonu
    if (preg_match('/^\s*DROP\s+DATABASE(?:\s+IF\s+EXISTS)?\s+([a-zA-Z0-9_]+)\s*;?\s*$/i', $sql, $m)) {
        $dbName = $m[1];
        if (!checkDatabaseExists($pdo, $driver, $dbName)) {
            echo "<div class='sql-message warning'>" . sprintf(__l('db_not_exists'), htmlspecialchars($dbName)) . "</div>";
        } else {
            try {
                switch ($driver) {
                    case 'mysql':
                        $pdo->exec("DROP DATABASE `$dbName`");
                        break;
                    case 'pgsql':
                        $pdo->exec("DROP DATABASE \"$dbName\"");
                        break;
                    default:
                        echo "<div class='sql-message error'>" . __l('drop_db_not_supported') . "</div>";
                        exit;
                }
                echo "<div class='sql-message success'>" . sprintf(__l('db_dropped'), htmlspecialchars($dbName)) . "</div>";
            } catch (PDOException $e) {
                writeLog("Error in DROP DATABASE: " . $e->getMessage());
                echo "<div class='sql-message error'>" . sprintf(__l('query_error'), htmlspecialchars($e->getMessage())) . "</div>";
                exit;
            }
            if (isset($_SESSION['selected_db']) && $_SESSION['selected_db'] === $dbName) {
                $fallback = fallbackToLastDatabase($pdo, $driver);
                if ($fallback) {
                    $_SESSION['selected_db'] = $fallback;
                    echo "<div class='sql-message info'>" . sprintf(__l('db_auto_fallback'), htmlspecialchars($fallback)) . "</div>";
                } else {
                    echo "<div class='sql-message warning'>" . __l('no_db_session_closed') . "</div>";
                }
            }
        }
        exit;
    }

    // Diğer SQL işlemleri: CREATE/DROP TABLE, VIEW, INSERT, UPDATE, ALTER, TRUNCATE vs.
    $queries = splitSqlStatements($sql);
    if (empty($queries)) {
        echo "<div class='sql-message warning'>" . __l('empty_query') . "</div>";
        exit;
    }
    $output = "";
    foreach ($queries as $q) {
        $output .= runSingleQuery($q) . "<hr/>";
    }
    echo $output;
    exit;
}

/* *****************************************
 * C) KALAN İŞLEMLER: Tooltip ve Autocomplete
 * (refresh_db, action=tooltip, action=autocomplete)
 * *****************************************/

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!$action) {
    echo "Geçersiz işlem türü!";
    exit;
}

switch ($action) {
    case 'tooltip':
        $dbName = $_POST['database'] ?? $_GET['database'] ?? '';
        if (!$dbName) {
            echo __l('db_not_selected');
            exit;
        }
        $dsnNew = buildDSN($driver, $GLOBALS['db_server'], $dbName);
        try {
            $pdo = new PDO($dsnNew, $GLOBALS['db_user'], $GLOBALS['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $tables = $views = $procedures = $functions = array();
            if ($driver === 'mysql') {
                $stmt = $pdo->query("SHOW FULL TABLES");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    if (strcasecmp($row[1], 'BASE TABLE') === 0) {
                        $tables[] = $row[0];
                    } elseif (strcasecmp($row[1], 'VIEW') === 0) {
                        $views[] = $row[0];
                    }
                }
                $stmt = $pdo->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='$dbName'");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($r['ROUTINE_TYPE'] === 'PROCEDURE') {
                        $procedures[] = $r['ROUTINE_NAME'];
                    } elseif ($r['ROUTINE_TYPE'] === 'FUNCTION') {
                        $functions[] = $r['ROUTINE_NAME'];
                    }
                }
            }
            $output = "";
            if (!empty($tables)) {
                $output .= "<b>" . __l('tables') . ":</b> " . implode(", ", $tables) . "<br>";
            }
            if (!empty($views)) {
                $output .= "<b>" . __l('views') . ":</b> " . implode(", ", array_map('htmlspecialchars', $views)) . "<br>";
            }
            if (!empty($procedures)) {
                $output .= "<b>" . __l('procedures') . ":</b> " . implode(", ", array_map('htmlspecialchars', $procedures)) . "<br>";
            }
            if (!empty($functions)) {
                $output .= "<b>" . __l('functions') . ":</b> " . implode(", ", array_map('htmlspecialchars', $functions));
            }
            echo $output ? $output : __l('notable');
        } catch (PDOException $e) {
            writeLog("Tooltip error: " . $e->getMessage());
            echo __l('error') . htmlspecialchars($e->getMessage());
        }
        exit;
        break;

    case 'autocomplete':
        $selectedDbParam = $_POST['db'] ?? $_GET['db'] ?? '';
        $pdoMain = $activeDBInfo['pdo'] ?? null;
        $allDatabases = $pdoMain ? listDatabases($pdoMain, $driver) : array();
        $tables = array();
        if (!empty($selectedDbParam)) {
            // Basit önbellekleme: 60 saniye
           $cacheFile = $_SERVER['DOCUMENT_ROOT'] . '/.hamu/cache/cache_autocomplete.json';
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
                header('Content-Type: application/json; charset=utf-8');
                echo file_get_contents($cacheFile);
                exit;
            }
            $dsnNew = buildDSN($driver, $GLOBALS['db_server'], $selectedDbParam);
            try {
                $pdoDb = new PDO($dsnNew, $GLOBALS['db_user'], $GLOBALS['db_pass']);
                $pdoDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                switch ($driver) {
                    case 'mysql':
                        $stmt = $pdoDb->query("SHOW TABLES");
                        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                            $tables[] = $row[0];
                        }
                        break;
                    case 'pgsql':
                        $stmt = $pdoDb->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tables[] = $row['table_name'];
                        }
                        break;
                    case 'sqlsrv':
                        $stmt = $pdoDb->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tables[] = $row['TABLE_NAME'];
                        }
                        break;
                    case 'oci':
                        $stmt = $pdoDb->query("SELECT table_name FROM user_tables");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tables[] = $row['table_name'];
                        }
                        break;
                    case 'sqlite':
                        $stmt = $pdoDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tables[] = $row['name'];
                        }
                        break;
                }
            } catch (PDOException $e) {
                writeLog("Autocomplete error: " . $e->getMessage());
            }
        }
        $finalSuggestions = array_merge($allDatabases, $tables);
        $jsonData = json_encode($finalSuggestions);
        file_put_contents($cacheFile, $jsonData);
        header('Content-Type: application/json; charset=utf-8');
        echo $jsonData;
        exit;
        break;

    default:
        echo "Bilinmeyen işlem türü!";
        exit;
}
?>
