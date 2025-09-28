<?php
$configPath = $_SERVER['DOCUMENT_ROOT'] . "/.hamu/config.json";

// Eğer config.json yoksa, oluştur
if (!file_exists($configPath)) {
    $defaultConfig = [
        "theme_s"           => false,
        "database_s"        => false,
		"db_server_s"		=> "",
		"db_user_s"			=> "",
		"db_pass_s"			=> "",
        "modul_s"           => false,
        "file_manager_s"    => false,
        "file_manager_s_url"=> "/.hamu/modules/file_manager.php",
        "phpmyadmin_s"      => false,
        "phpmyadmin_s_url"  => "",
        "mail_server_s"     => false,
        "mail_server_s_url" => "",
        "ftp_server_s"      => false,
        "ftp_server_s_url"  => "",
        "first_setup"       => true
    ];
    file_put_contents($configPath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}

// JSON dosyasını oku
$config = json_decode(file_get_contents($configPath), true) ?: [];

// AJAX POST isteği geldiyse JSON'u güncelle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // Yanıtın JSON formatında olmasını sağla

    // Gelen JSON verisini oku
    $jsonData = json_decode(file_get_contents('php://input'), true);

    // Eğer JSON geçersizse, hata döndür
    if (!$jsonData) {
        echo json_encode(["success" => false, "error" => "Geçersiz JSON verisi"]);
        exit;
    }

    // Mevcut config ile gelen veriyi birleştir (gelen değerler üzerine yazar)
    $mergedConfig = array_merge($config, $jsonData);

    // JSON dosyasını güncelle
    if (file_put_contents($configPath, json_encode($mergedConfig, JSON_PRETTY_PRINT))) {
        echo json_encode(["success" => true, "message" => "Ayarlar kaydedildi"]);
    } else {
        echo json_encode(["success" => false, "error" => "Dosya yazma hatası"]);
    }
    exit;
}

// Config okuma fonksiyonu
function config($key) {
    global $config;
    return $config[$key] ?? null;
}
 // Sayfa yüklenirken tema ayarını belirle
$theme = config('theme_s') ? 'dark' : 'light';