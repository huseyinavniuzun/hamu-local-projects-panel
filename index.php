<?php
/**
 * INDEX.PHP
 *
 * [TR] Ana sayfa. Çoklu dil desteği (TR/EN), klasör listeleme (6'şar sütun),
 * modül tarama (.hamu/modules), sunucu bilgisi (Apache/Nginx & PHP sürümü),
 * veritabanı paneli, SQL terminali.
 *
 * [EN] Main landing page. Multi-language (TR/EN), folder listing (6 columns),
 * module scanning (.hamu/modules), server info detection (Apache/Nginx & PHP version),
 * DB panel, SQL terminal.
 */
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
 if (isset($_GET['lang']) && in_array($_GET['lang'], ['TR', 'EN'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/config.php';       	  // Veritabanı ayarları
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/lang.php';  		 // Dil dizisi ($languages)
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/database.php';				// Veritabanı
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/functions.php';       // Fonksiyonlar
$dbInfo = getActiveDatabase();
$active_db = $dbInfo['active_db'];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" data-bs-theme="<?php header('Content-Type: text/html; charset=' . __l('charset')); echo htmlspecialchars($theme) ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="<?= __l('charset') ?>">
    <title><?= __l('title') ?></title>


    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

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
	<meta name="theme-color" content="#007bff">

	<!-- Microsoft Tile Ayarları / Microsoft Tile Settings -->
	<meta name="msapplication-TileColor" content="#007bff">
	<meta name="msapplication-config" content="/.hamu/images/favicon/browserconfig.xml">

	<!-- Apple Web App Başlığı / Apple Web App Title -->
	<meta name="apple-mobile-web-app-title" content="Your App Name">
		
    <!-- Özel CSS -->
    <link rel="stylesheet" href="/.hamu/css/custom.css">

</head>
<body>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';       // Navbar
?>
<!-- [TR] Desktop Layout: Dashboard, Sidebar, Main Content -->
<!-- [EN] Desktop Layout: Dashboard, Sidebar, Main Content -->
<div class="dashboard">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_sidebar.php';  ?>
<div class="main-content">
	<div class="container">
		<!-- Projeler / Projects Header -->
		<div class="row pb-3">
	<div class="col-md-9 text-start">
		<h4><i class="fas fa-folder"></i> <?= __l('local_projects') ?></h4>
	</div>
	<div class="col-md-3 text-end d-none d-md-block">
		<a href=".hamu/readme.php" alt="<?= __l('about_title') ?>" target="_self">
			<i class="fa-solid fa-question h5"></i>
		</a>
	</div>
</div>
		<!-- Klasör listeleme / Folder Listing -->
		<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-3">
			<?php foreach ($folders as $dir):
				$folderName = basename($dir);
				$indexFile = findIndexFile($dir);
				$projectUrl = ($indexFile !== null) ? "/{$folderName}/{$indexFile}" : '#';
				$imagePath = "{$dir}/{$folderName}.png";
				$hasLogo = file_exists($imagePath);
				$iconClass = ($indexFile !== null) ? 'fas fa-folder' : 'far fa-folder';
				$tooltip = ($indexFile === null) ? 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . __l('noindexfile') . '"' : '';
			?>
				<div class="col">
					<div class="folder text-center" <?= $tooltip ?> <?= ($indexFile !== null) ? "onclick=\"window.open('{$projectUrl}','_blank')\"" : '' ?>>
						<?php if ($hasLogo): ?>
							<img src='<?= "/{$folderName}/{$folderName}.png" ?>' alt='<?= $folderName ?>' class='img-fluid'>
						<?php else: ?>
							<i class='<?= $iconClass ?>'></i>
						<?php endif; ?>
						<div class="folder-word mt-2"><?= htmlspecialchars(short($folderName)) ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_footer.php';?>