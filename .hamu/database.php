<?php
/**
 * DATABASE.PHP
 *
 * [TR] Veritabanı ayarları. DSN listesi, session'daki $active_db, sunucu kapalıysa devre dışı. fallbackToLastDatabase vb.
 * [EN] Database settings. DSN list, $active_db from session, if server is down => disabled, fallbackToLastDatabase, etc.
 */


/**
 * buildDSN
 * Driver + host + db parametresine göre PDO DSN stringini döndürür.
 */
if (!isset($GLOBALS['config'])) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . "/.hamu/config.json";
    $GLOBALS['config'] = json_decode(file_get_contents($configPath), true) ?: [];
}

// Global veritabanı ayarlarını config üzerinden atayalım
$GLOBALS['db_server'] = isset($config['db_server_s']) && $config['db_server_s'] !== "" ? $config['db_server_s'] : 'localhost';
$GLOBALS['db_user']   = isset($config['db_user_s']) && $config['db_user_s'] !== "" ? $config['db_user_s'] : 'root';
$GLOBALS['db_pass']   = isset($config['db_pass_s']) && $config['db_pass_s'] !== "" ? $config['db_pass_s'] : '';

// DSN Listesi (MySQL, PostgreSQL, vs.)
$dsnList = [
    'MySQL'      => "mysql:host={$GLOBALS['db_server']};charset=utf8",
    'PostgreSQL' => "pgsql:host={$GLOBALS['db_server']}",
    'SQLite'     => "sqlite::memory:",
    'SQLServer'  => "sqlsrv:Server={$GLOBALS['db_server']}",
    'Oracle'     => "oci:dbname=//{$GLOBALS['db_server']}/xe;charset=UTF8"
];

// Session'da seçili DB var mı
$active_db = $_SESSION['selected_db'] ?? '';

// DSN içinden ilk başarılı sürücüyü bul:
$activeDBInfo = getActiveDatabaseInfo($dsnList);

 
function buildDSN($driver, $host, $db = '')
{
    switch ($driver) {
        case 'mysql':
            // charset + db name
            return "mysql:host={$host};charset=utf8" . ($db ? ";dbname={$db}" : "");
        case 'pgsql':
            return "pgsql:host={$host}" . ($db ? ";dbname={$db}" : "");
        case 'sqlsrv':
            // Windows SQL Server
            return "sqlsrv:Server={$host}" . ($db ? ";Database={$db}" : "");
        case 'oci':
            // Oracle
            if ($db) {
                return "oci:dbname=//{$host}/{$db};charset=UTF8";
            } else {
                return "oci:dbname=//{$host};charset=UTF8";
            }
        case 'sqlite':
            // $db burada dosya yolu veya :memory: olabilir
            return "sqlite:" . ($db ?: ":memory:");
        default:
            return "";
    }
}

/**
 * fallbackToLastDatabase
 * Bir DB silindiğinde veya geçersiz olduğunda, veritabanı listesindeki sonuncusunu seçer.
 */
function fallbackToLastDatabase(PDO $pdo, $driver)
{
    $dbList = listDatabases($pdo, $driver);
    if (!empty($dbList)) {
        $fallback_db = end($dbList);
        $_SESSION['selected_db'] = $fallback_db;
        return $fallback_db;
    } else {
        $_SESSION['selected_db'] = 'test';
        return 'test';
    }
}

// Sunucu kapalı veya DB yoksa $active_db'yi boşalt
if (!empty($active_db) && $activeDBInfo) {
    $driver  = $activeDBInfo['driver'];
    $testDsn = buildDSN($driver, $GLOBALS['db_server'], $active_db);
    try {
        $pdoTest = new PDO($testDsn, $GLOBALS['db_user'], $GLOBALS['db_pass']);
        $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Basit sorgu
        $pdoTest->query("SELECT 1");
    } catch (PDOException $e) {
        // Bağlanamadı => devre dışı
        $_SESSION['selected_db'] = '';
        $active_db = '';
    }
} else {
    // Bağlanamadıysak
    if (!$activeDBInfo) {
        $_SESSION['selected_db'] = '';
        $active_db = '';
    }
}

/**
 * getActiveDatabaseInfo
 * DSN listesinde ilk bağlanabilen sürücüyü döndürür (pdo, driver, version).
 */
function getActiveDatabaseInfo($dsnList)
{
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
            // Devam
        }
    }
    return null;
}

/**
 * getVersion
 */
function getVersion(PDO $pdo, $driver)
{
    switch ($driver) {
        case 'mysql':
            return $pdo->query("SELECT VERSION()")->fetchColumn();
        case 'pgsql':
            return $pdo->query("SELECT version()")->fetchColumn();
        case 'sqlite':
            return $pdo->query("SELECT sqlite_version()")->fetchColumn();
        case 'sqlsrv':
            return $pdo->query("SELECT @@VERSION")->fetchColumn();
        case 'oci':
            $stmt = $pdo->query("SELECT banner FROM v\$version WHERE banner LIKE 'Oracle Database%'");
            return $stmt->fetchColumn();
        default:
            return 'Unknown';
    }
}

/**
 * listDatabases
 * Farklı sürücülerde veritabanı isimlerini döndürür.
 */
function listDatabases(PDO $pdo, $driver)
{
    $dbs = [];

    // Sistem DB'leri gizlemek
    $mysqlSys  = ['information_schema','performance_schema','mysql','sys'];
    $pgsqlSys  = ['postgres','template0','template1'];
    $sqlsrvSys = ['master','tempdb','model','msdb'];

    switch ($driver) {
        case 'mysql':
            $stmt = $pdo->query("SHOW DATABASES");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dbName = $row['Database'];
                if (!in_array($dbName, $mysqlSys, true)) {
                    $dbs[] = $dbName;
                }
            }
            break;
        case 'pgsql':
            $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dbName = $row['datname'];
                if (!in_array($dbName, $pgsqlSys, true)) {
                    $dbs[] = $dbName;
                }
            }
            break;
        case 'sqlite':
            $dbs[] = 'SQLiteDb';
            break;
        case 'sqlsrv':
            $stmt = $pdo->query("SELECT name FROM sys.databases");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dbName = $row['name'];
                if (!in_array($dbName, $sqlsrvSys, true)) {
                    $dbs[] = $dbName;
                }
            }
            break;
        case 'oci':
            $dbs[] = 'OracleDB';
            break;
    }
    return $dbs;
}

/**
 * checkDatabaseExists
 */

function checkDatabaseExists(PDO $pdo, $driver, $dbName)
{
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
            // Tek dosya => tam mantık yok
            return false;
    }
    return false;
}

 // Veritabanı destek durumu kontrolü kaldırıldı, doğrudan session'da seçili DB'yi alıyoruz.
$active_db = $_SESSION['selected_db'] ?? '';

// Aktif veritabanı bilgilerini çekelim
$activeDBInfo = getActiveDatabaseInfo($dsnList);
$databases = [];

if ($activeDBInfo) {
    // Sunucu çalışıyor, PDO ve driver bilgilerini alıyoruz
    $pdo = $activeDBInfo['pdo'];
    $driver = $activeDBInfo['driver'];
    $databases = listDatabases($pdo, $driver);

    // Eğer session'da seçili DB boş veya listede yoksa fallback işlemi yapıyoruz
    if (empty($active_db) || !in_array($active_db, $databases, true)) {
        if (!empty($databases)) {
            // Varsayılan olarak listedeki ilk veritabanını seçiyoruz
            $_SESSION['selected_db'] = $databases[0];
            $active_db = $databases[0];
        } else {
            // Hiç veritabanı bulunamadıysa session boş kalıyor
            $_SESSION['selected_db'] = '';
            $active_db = '';
        }
    }
} else {
    // Sunucu kapalı durumda da session temizleniyor
    $_SESSION['selected_db'] = '';
    $active_db = '';
}
/**
 * Veritabanı
 * [TR] Veritabanını dahil etme ve oturum ayarları
 * [EN] Import database and session options
 */
function getActiveDatabase() {
    global $config, $dsnList;
    $active_db = $_SESSION['selected_db'] ?? '';

    if (empty($config["database_s"]) || !$config["database_s"]) {
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
        $pdo = $activeDBInfo['pdo'];
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