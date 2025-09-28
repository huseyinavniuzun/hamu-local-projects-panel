<?php
$md_file		 = "readme.md";
$page_title		 = "readme";		  	  		  						 // Sayfa Başlığı
$body_class	 	 = "";			   			  							// <body> tagı css sitili
$include_db	 	 = 0;			   	  		 						   // 0 = hayır, 1 = evet
$menu_type		 = 1;												  // 0 = Sadece mobil ekranlarda gösteriliyor, 1 = Her ekranda gösteriliyor
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_header.php');	 // Header 
require_once $_SERVER['DOCUMENT_ROOT'] . '/.hamu/h_navbar.php';		// Navbar
?>
<!-- İçerik başlangıcı -->

<div class="container-module">
 <div class="content-large">
  <h1 class="mb-4"><?= $md_file?></h1>
  <div class="card">
    <div class="card-body markdown-content">
      <?php echo markdown($md_file); ?>
    </div>
  </div>
  </div>
</div>

<!-- İçerik sonu -->

<?php
require_once $_SERVER['DOCUMENT_ROOT']. ('/.hamu/h_footer.php');		// Header 
?>