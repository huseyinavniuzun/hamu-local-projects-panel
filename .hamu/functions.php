<?php
// .hamu/functions.php

/**
 * Config'den gelen URL değeri üzerinden nihai linki oluşturur.
 *
 * @param string $configLink JSON veya diğer konfigürasyon kaynağından gelen URL değeri.
 * @return string İşlenmiş nihai URL.
 */

$rootDir = $_SERVER['DOCUMENT_ROOT'];
$folders = scanFolders($rootDir);
$modules = scanModules(); // **Modüller çağrılmadan önce kontrol**

function jsonLink($configLink) {
    // Sunucunun protokol ve host bilgisi ile base_url oluşturuluyor
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $b_url = $protocol . $_SERVER['HTTP_HOST'];

    // 1) Eğer link "http://", "https://" veya "www." ile başlıyorsa:
    if (preg_match('/^(http:\/\/|https:\/\/|www\.)/i', $configLink)) {
        return $configLink;
    }

    // 3) Eğer link ":" ile başlıyorsa (örneğin, ":8025"):
    if (preg_match('/^:/', $configLink)) {
        return rtrim($b_url, '/') . $configLink;
    }

    // 2) Diğer durumlarda; örneğin link "/" ile başlıyorsa:
    return rtrim($b_url, '/') . '/' . ltrim($configLink, '/');
}

function translate($text) {
    // Metnin küçük halini al
    $lowerText = strtolower($text);
    // __l fonksiyonuyla çeviriyi al
    $translated = __l($lowerText);
    // Eğer çeviri, küçük haline eşitse orijinal metni döndür, değilse çeviriyi döndür.
    return ($translated === $lowerText) ? $text : $translated;
}

/**
 * scanModules
 * [TR] .hamu/modules dizinindeki .php dosyalarını tarar,
 * <title>(.*?)</title> yakalayıp menüde gösterir.
 * [EN] Finds .php files in .hamu/modules, extracts <title> for the menu label.
 */
 
// Proje klasörleri (örneğin index.php vs. barındıran yerel projeler)

function scanModules() {
    global $config;

    // Eğer modül kapalıysa boş dizi döndür
    if (empty($config["modul_s"])) {
        return [];
    }

    $modules = [];
    $path = $_SERVER['DOCUMENT_ROOT'] . '/.hamu/modules';
    if (!is_dir($path)) return $modules;

    $files = glob($path . '/*.php');
    foreach ($files as $f) {
        $fileName = basename($f);
        // Nokta ile başlayan dosyaları atla
        if ($fileName[0] === '.') {
            continue;
        }

        $content = file_get_contents($f);
        if ($content === false) {
            error_log("Error reading file: " . $f);
            continue;
        }

        // Varsayılan olarak dosya adını kullan
        $title = $fileName;

        // Öncelikle $page_title değerini ara
        if (preg_match('/\$page_title\s*=\s*[\'"](.*?)[\'"]/', $content, $m)) {
            $rawTitle = trim($m[1]);
            // Çeviriyi kontrol et; eğer çeviri varsa, onu kullan, yoksa orijinal değeri.
            $title = translate($rawTitle);
        }
        // Eğer $page_title yoksa, <title> etiketini kontrol et
        else if (preg_match('/<title>(.*?)<\/title>/i', $content, $m)) {
            $title = trim($m[1]);
        }

        $modules[] = [
            'file' => $fileName,
            'title' => $title
        ];
    }
    return $modules;
}

/**
 * scanFolders
 * [TR] root/ klasörü içindeki klasörleri tarar, .png logosu var mı bakar
 * [EN] Scans the root folder for subfolders, checks .png logo if it exists.
 */

function scanFolders($rootDir) {
    $folders = [];
    foreach (glob($rootDir . '/*') as $dir) {
        if (is_dir($dir)) {
            $bn = basename($dir);
            // Gizli klasörleri, .hamu gibi sistem klasörlerini atlamak istersen:
            if ($bn[0] !== '.' && $bn !== '.hamu') {
                $folders[] = $dir;
            }
        }
    }
    return $folders;
}

/**
 * findIndexFile
 * [TR] Belirtilen klasörde index dosyası olup olmadığını kontrol eder.
 * [EN] Checks for the presence of an index file in the given directory.
 */
function findIndexFile($dir) {
    // Öncelik sırasına göre aranacak dosyalar
    $candidates = ['index.html', 'index.htm', 'index.php', 'default.php'];
    
    // Dizin yolunun geçerli ve erişilebilir olup olmadığını kontrol et
    if (!is_dir($dir) || !is_readable($dir)) {
        return null; // Geçersiz veya erişilemez dizin
    }
    
    // Her bir aday dosya için kontrol et
    foreach ($candidates as $c) {
        $filePath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $c;
        if (file_exists($filePath)) {
            return $c; // Bulunan dosya adı döndürülür
        }
    }
    return null; // Hiçbiri bulunamazsa
}

// Klasör adını kısalt
function short($word, $limit = 13) { 
    if (mb_strlen($word) > $limit) {
        return mb_substr($word, 0, $limit) . '..';
    }
    return $word;
}

/**
 * Server
 * [TR] Sunucu bilgileri ve php versiyonu
 * [EN] Server informations and php version
 */

// Sunucu Yazılımı (Apache, Nginx, vs.) ve PHP sürümü

$phpVersion        = phpversion();
$webServerName    = 'Diğer';
// Makine adı + Zaman
$hostName = gethostname();
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$httpServerSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$webServerName      = 'Other';
$webServerVersion   = '';
if (stripos($httpServerSoftware, 'apache') !== false) {
    $webServerName = 'Apache';
    if (preg_match('/Apache\/([^\s]+)/', $httpServerSoftware, $m)) {
        $webServerVersion = $m[1];
    }
} elseif (stripos($httpServerSoftware, 'nginx') !== false) {
    $webServerName = 'Nginx';
    if (preg_match('/nginx\/([^\s]+)/i', $httpServerSoftware, $m)) {
        $webServerVersion = $m[1];
    }
}
// Boolean direktiflerin değerlerini biçimlendiren yardımcı fonksiyon
function format_ini_value($key, $value) {
    // Boolean olması beklenen direktifler
    $bool_directives = ['allow_url_fopen', 'file_uploads', 'display_errors'];
    if (in_array($key, $bool_directives)) {
        if ($value === '' || $value === false) {
            return 'On';
        }
        if ($value === '0') {
            return 'Off';
        }
    }
    return $value;
}
$ini_all = ini_get_all();
ksort($ini_all);

function markdown($md_file) {
    if (empty($md_file)) {
        return "<p>Dosya adı tanımlı değil.</p>";
    }

    if (!file_exists($md_file)) {
        return "<p>Readme dosyası bulunamadı.</p>";
    }

    $mdContent = file_get_contents($md_file);

    // Başlıklar (#)
    $html = preg_replace_callback('/^(#{1,6})\s*(.+)$/m', function ($matches) {
        $level = strlen($matches[1]);
        return "<h{$level}>" . htmlspecialchars(trim($matches[2])) . "</h{$level}>";
    }, $mdContent);

    // Kalın metin (**text** veya __text__)
    $html = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $html);

    // İtalik metin (*text* veya _text_)
    $html = preg_replace('/(\*|_)(.*?)\1/', '<em>$2</em>', $html);

    // Linkler ([text](url))
    $html = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
        $text = htmlspecialchars(trim($matches[1]));
        $url = htmlspecialchars(trim($matches[2]));
        return "<a href=\"{$url}\" target=\"_blank\">{$text}</a>";
    }, $html);

    // Satır aralarındaki boşlukları paragrafla değiştir
    $paragraphs = preg_split('/\n\s*\n/', $html);
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $html .= '<p>' . nl2br(trim($paragraph)) . "</p>\n";
    }

    // Gereksiz satır aralarını temizle
    $html = preg_replace('/(<\/h[1-6]>|<\/p>)<br\s*\/?>/', '$1', $html);

    return $html;
}