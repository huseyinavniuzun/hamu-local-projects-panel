<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
 if (isset($_GET['lang']) && in_array($_GET['lang'], ['TR', 'EN'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/config.php';	      // Yapılandırma Dosyası
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/lang.php';		 // Dil Dosyası
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/functions.php';	// Fonksiyon Dosyası
if ($include_db == 1) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/database.php';
    $dbInfo = getActiveDatabase();
    $active_db = $dbInfo['active_db'];
}
$translated = __l(strtolower($page_title));
$page_title = ($translated === strtolower($page_title)) ? $page_title : $translated;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>"  data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="<?= __l('charset') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Uygulama Manifest Dosyası / App Manifest -->
	<link rel="manifest" href="/.hamu/images/favicon/manifest.json">

	<!-- Favicon ve Apple Touch Icon -->
	<link rel="icon" type="image/png" sizes="32x32" href="/.hamu/images/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/.hamu/images/favicon/favicon-16x16.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/.hamu/images/favicon/apple-touch-icon.png">

	<!-- Tarayıcı Tema Rengi / Browser Theme Color -->
	<meta name="theme-color" content="#007bff"> <!-- [TR] Tarayıcı araç çubuğu rengi / [EN] Browser toolbar color -->

	<!-- Microsoft Tile Ayarları / Microsoft Tile Settings -->
	<meta name="msapplication-TileColor" content="#007bff">
	<meta name="msapplication-config" content="/.hamu/images/favicon/browserconfig.xml">

	<!-- Apple Web App Başlığı / Apple Web App Title -->
	<meta name="apple-mobile-web-app-title" content="HAMU Local Panel">
		
    <!-- Özel CSS -->
    <link rel="stylesheet" href="/.hamu/css/custom.css">

<title><?= $page_title; ?> - HAMU</title>
</head>
<body<?php if (empty($body_class)) { echo '';} else {echo ' class="'.$body_class.'"';} ?>>
<input type="hidden" id="langValue" value="<?php echo $lang; ?>">
<?php
if (!isset($menu_type)) {
    $menu_type = 0;
}
if ($menu_type != 0) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_sidebar.php';
}
?>