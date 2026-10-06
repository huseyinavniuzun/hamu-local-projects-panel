<?php
/**
 * HAMU DevPanel - Database
 * Location: /.hamu/database.php
 * [TR] Veritabanı bağlantılarını ve yönetimini merkezileştirir. PDO DSN oluşturma, aktif sürücüyü bulma, veritabanlarını listeleme, sürüm bilgisi alma ve oturum bazlı veritabanı seçimini yönetme gibi yardımcı fonksiyonları içerir.
 * [EN] Centralizes database connections and management. Includes helper functions for building PDO DSNs, finding the active driver, listing databases, getting version information, and managing session-based database selection.
 */
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* -----------------------------
 * Require config
 * ----------------------------- */
$paths = [
    HAMU_DIR . '/config.php',  // Yapılandırma
];
$includedFiles = get_included_files();
foreach ($paths as $path) {
    if (!in_array($path, $includedFiles, true) && is_file($path)) {
        require_once $path;
    }
}

/* -----------------------------
 * Config yükle
 * ----------------------------- */
if (!isset($GLOBALS['config'])) {
    $configPath = HAMU_CACHE . "/app_config.json";
    $GLOBALS['config'] = json_decode(@file_get_contents($configPath), true) ?: [];
}
$config =& $GLOBALS['config'];

// db sürücüsü & sqlite klasörü konfig’den gelsin
function hamu_active_db_prefs(): array {
  $drv   = (string)(config('db_driver_s') ?? '');
  $sqliteDir = (string)(config('sqlite_folder_s') ?? '');
  $drv = $drv !== '' ? strtolower($drv) : 'auto';
  return [$drv, $sqliteDir];
}

/* -----------------------------
 * SQLite yardımcıları
 * ----------------------------- */
function hamu_sqlite_dir(): string {
    // sqlite_folder_s override
    if (isset($GLOBALS['config']['sqlite_folder_s']) && $GLOBALS['config']['sqlite_folder_s'] !== '') {
        $dir = (string)$GLOBALS['config']['sqlite_folder_s'];
    } else {
        $dir = rtrim(HAMU_DOCROOT,'/\\').'/.hamu/sqlite';
    };
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}
function hamu_sqlite_safename(string $db): string {
    return preg_replace('/[^A-Za-z0-9_-]/','_', $db) . '.sqlite';
}

/* -----------------------------
 * Global DB kimlikleri
 * ----------------------------- */
$GLOBALS['db_server'] = !empty($config['db_server_s']) ? $config['db_server_s'] : 'localhost';
$GLOBALS['db_user']   = !empty($config['db_user_s'])   ? decryptData($config['db_user_s']) : 'root';
$GLOBALS['db_pass']   = !empty($config['db_pass_s'])   ? decryptData($config['db_pass_s']) : '';

/* -----------------------------
 * DSN adayları (SQLite en sonda)
 * ----------------------------- */
$dsnList = [
    'MySQL'      => "mysql:host={$GLOBALS['db_server']};charset=utf8",
    'PostgreSQL' => "pgsql:host={$GLOBALS['db_server']}",
    'SQLServer'  => "sqlsrv:Server={$GLOBALS['db_server']}",
    'Oracle'     => "oci:dbname=//{$GLOBALS['db_server']}/xe;charset=UTF8",
    'SQLite'     => "sqlite::memory:",
];

/* -----------------------------
 * DSN kurucu
 * ----------------------------- */
function buildDSN($driver, $host, $db = '') {
        // Konfig tercihleri: db_driver_s / sqlite_folder_s
        $cfg = isset($GLOBALS['config']) && is_array($GLOBALS['config']) ? $GLOBALS['config'] : [];
        $pref = isset($cfg['db_driver_s']) && $cfg['db_driver_s'] !== '' ? strtolower((string)$cfg['db_driver_s']) : 'auto';
        if ($pref !== 'auto') {
            $driver = $pref; // kullanıcı tercihi sürücüye baskın
        }
    switch ($driver) {
        case 'mysql':
            return "mysql:host={$host};charset=utf8" . ($db ? ";dbname={$db}" : "");
        case 'pgsql':
            return "pgsql:host={$host}" . ($db ? ";dbname={$db}" : "");
        case 'sqlsrv':
            return "sqlsrv:Server={$host}" . ($db ? ";Database={$db}" : "");
        case 'oci':
            return $db ? "oci:dbname=//{$host}/{$db};charset=UTF8"
                       : "oci:dbname=//{$host};charset=UTF8";
        case 'sqlite':
            if (!$db) return "sqlite::memory:";
            // sqlite_folder_s tercihi
            $cfgDir = isset($cfg['sqlite_folder_s']) ? (string)$cfg['sqlite_folder_s'] : '';
            $dir  = $cfgDir !== '' ? $cfgDir : hamu_sqlite_dir();
            $safe = hamu_sqlite_safename($db);
            // DİKKAT: Burada dosya yoksa bile throw ETME; sadece DSN döndür.
            return "sqlite:" . rtrim($dir,'/\\') . '/' . $safe;
        default:
            return "";
    }
}

/* -----------------------------
 * Sürücü versiyonu
 * ----------------------------- */
function getVersion(PDO $pdo, $driver) {
    switch ($driver) {
        case 'mysql':  return $pdo->query("SELECT VERSION()")->fetchColumn();
        case 'pgsql':  return $pdo->query("SELECT version()")->fetchColumn();
        case 'sqlite': return $pdo->query("SELECT sqlite_version()")->fetchColumn();
        case 'sqlsrv': return $pdo->query("SELECT @@VERSION")->fetchColumn();
        case 'oci':
            $stmt = $pdo->query("SELECT banner FROM v\$version WHERE banner LIKE 'Oracle Database%'");
            return $stmt->fetchColumn();
        default:       return 'Unknown';
    }
}

/* -----------------------------
 * Veritabanlarını listele
 * ----------------------------- */
function listDatabases(PDO $pdo, $driver) {
    $dbs = [];
    $mysqlSys  = ['information_schema','performance_schema','mysql','sys'];
    $pgsqlSys  = ['postgres','template0','template1'];
    $sqlsrvSys = ['master','tempdb','model','msdb'];

    switch ($driver) {
        case 'mysql':
            $stmt = $pdo->query("SHOW DATABASES");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $d = $row['Database'];
                if (!in_array($d, $mysqlSys, true)) $dbs[] = $d;
            }
            break;
        case 'pgsql':
            $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $d = $row['datname'];
                if (!in_array($d, $pgsqlSys, true)) $dbs[] = $d;
            }
            break;
        case 'sqlite':
            $dir = hamu_sqlite_dir();
            $files = glob($dir.'/*.sqlite') ?: [];
            foreach ($files as $fp) {
                $name = pathinfo($fp, PATHINFO_FILENAME);
                if ($name !== '') $dbs[] = $name;
            }
            sort($dbs, SORT_NATURAL | SORT_FLAG_CASE);
            break;
        case 'sqlsrv':
            $stmt = $pdo->query("SELECT name FROM sys.databases");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $d = $row['name'];
                if (!in_array($d, $sqlsrvSys, true)) $dbs[] = $d;
            }
            break;
        case 'oci':
            $dbs[] = 'OracleDB';
            break;
    }
    return $dbs;
}

/* -----------------------------
 * DB var mı? (USE/CREATE/DROP kontrolleri)
 * ----------------------------- */
function checkDatabaseExists(PDO $pdo, $driver, $dbName) {
    switch ($driver) {
        case 'mysql':
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbName]);
            return ($stmt->rowCount() > 0);
        case 'pgsql':
            $stmt = $pdo->prepare("SELECT datname FROM pg_database WHERE datname = ?");
            $stmt->execute([$dbName]);
            return ($stmt->rowCount() > 0);
        case 'sqlsrv':
            $stmt = $pdo->prepare("SELECT name FROM sys.databases WHERE name = ?");
            $stmt->execute([$dbName]);
            return ($stmt->rowCount() > 0);
        case 'oci':
            $stmt = $pdo->prepare("SELECT username FROM all_users WHERE username = UPPER(?)");
            $stmt->execute([$dbName]);
            return ($stmt->rowCount() > 0);
        case 'sqlite':
            $file = hamu_sqlite_dir() . '/' . hamu_sqlite_safename($dbName);
            return is_file($file);
    }
    return false;
}

/* -----------------------------
 * İlk ulaşılabilen sürücüyü yakala
 * ----------------------------- */
function getActiveDatabaseInfo($dsnList) {
    foreach ($dsnList as $dbName => $dsn) {
        try {
            $pdo = new PDO($dsn, $GLOBALS['db_user'], $GLOBALS['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $driver  = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $version = getVersion($pdo, $driver);
            return [
                'dbName'  => $dbName,
                'version' => $version,
                'driver'  => $driver,
                'pdo'     => $pdo
            ];
        } catch (PDOException $e) {
            // sıradaki adaya geç
        }
    }
    return null;
}

/* -----------------------------
 * Fallback DB seçimi
 * ----------------------------- */
function fallbackToLastDatabase(PDO $pdo, $driver) {
    $dbList = listDatabases($pdo, $driver);
    if (!empty($dbList)) {
        $fallback_db = end($dbList);
        $_SESSION['selected_db'] = $fallback_db;
        return $fallback_db;
    }
    $_SESSION['selected_db'] = 'test';
    return 'test';
}

/* =================================================
 * 1) Aktif sürücü/DB bilgisi ve session seçimi
 * ================================================= */
$activeDBInfo = getActiveDatabaseInfo($dsnList);
$active_db    = $_SESSION['selected_db'] ?? '';

/* =================================================
 * 2) Eğer SQLite’a düşmüşsek ve seçili dosya yoksa seçimi temizle
 * ================================================= */
if (!empty($active_db) && $activeDBInfo && $activeDBInfo['driver'] === 'sqlite') {
    $dir  = hamu_sqlite_dir();
    $fp   = rtrim($dir,'/\\') . '/' . hamu_sqlite_safename($active_db);
    if (!is_file($fp)) {
        $_SESSION['selected_db'] = '';
        $active_db = '';
    }
}

/* =================================================
 * 3) Aktif DB’ye bağlanmayı dene; olmuyorsa seçimi sıfırla
 * ================================================= */
if (!empty($active_db) && $activeDBInfo) {
    $driver  = $activeDBInfo['driver'];

    if ($driver === 'sqlite') {
        $dir = hamu_sqlite_dir();
        $fp  = rtrim($dir,'/\\') . '/' . hamu_sqlite_safename($active_db);
        if (!is_file($fp)) {
            $_SESSION['selected_db'] = '';
            $active_db = '';
        } else {
            $testDsn = buildDSN($driver, $GLOBALS['db_server'], $active_db);
            try {
                $pdoTest = new PDO($testDsn, $GLOBALS['db_user'], $GLOBALS['db_pass']);
                $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdoTest->query("SELECT 1");
            } catch (PDOException $e) {
                $_SESSION['selected_db'] = '';
                $active_db = '';
            }
        }
    } else {
        $testDsn = buildDSN($driver, $GLOBALS['db_server'], $active_db);
        try {
            $pdoTest = new PDO($testDsn, $GLOBALS['db_user'], $GLOBALS['db_pass']);
            $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdoTest->query("SELECT 1");
        } catch (PDOException $e) {
            $_SESSION['selected_db'] = '';
            $active_db = '';
        }
    }
} else {
    if (!$activeDBInfo) {
        $_SESSION['selected_db'] = '';
        $active_db = '';
    }
}

/* =================================================
 * 4) Seçimi stabilize et (listede yoksa ilkini seç)
 * ================================================= */
$databases = [];
if ($activeDBInfo) {
    $pdo    = $activeDBInfo['pdo'];
    $driver = $activeDBInfo['driver'];
    $databases = listDatabases($pdo, $driver);

    if (empty($active_db) || !in_array($active_db, $databases, true)) {
        if (!empty($databases)) {
            $_SESSION['selected_db'] = $databases[0];
            $active_db = $databases[0];
        } else {
            $_SESSION['selected_db'] = '';
            $active_db = '';
        }
    }
} else {
    $_SESSION['selected_db'] = '';
    $active_db = '';
}

/* -----------------------------
 * Dışarıya sağlayıcı
 * ----------------------------- */
function getActiveDatabase() {
    global $config, $dsnList;
    $active_db = $_SESSION['selected_db'] ?? '';

    if (empty($config["database_s"])) {
        $_SESSION['selected_db'] = '';
        $active_db = '';
        return [
            'active_db'    => $active_db,
            'activeDBInfo' => null,
            'databases'    => []
        ];
    }

    $activeDBInfo = getActiveDatabaseInfo($dsnList);
    $databases = [];

    if ($activeDBInfo) {
        $pdo    = $activeDBInfo['pdo'];
        $driver = $activeDBInfo['driver'];
        $databases = listDatabases($pdo, $driver);

        if (empty($active_db) || !in_array($active_db, $databases, true)) {
            if (!empty($databases)) {
                $_SESSION['selected_db'] = $databases[0];
                $active_db = $databases[0];
            } else {
                $_SESSION['selected_db'] = '';
                $active_db = '';
            }
        }
    } else {
        $_SESSION['selected_db'] = '';
        $active_db = '';
    }

    return [
        'active_db'    => $active_db,
        'activeDBInfo' => $activeDBInfo,
        'databases'    => $databases
    ];
}